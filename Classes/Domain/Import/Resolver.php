<?php

declare(strict_types=1);

/*
 * Copyright (C) 2024 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301, USA.
 */

namespace WerkraumMedia\ThueCat\Domain\Import;

use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\StringUtility;
use WerkraumMedia\ThueCat\Domain\Import\Importer\FetchData;
use WerkraumMedia\ThueCat\Domain\Import\Parser\DataHandlerPayload;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\TransientEntity\AccessibilitySpecificationEntity;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\TransientEntity\MediaEntity;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Parser;

#[Autoconfigure(public: true)]
class Resolver
{
    /**
     * Hard-coded mapping from transient bucket name to
     * [target table, target relation field on the owning row].
     * Extend as new buckets get ref→uid resolution.
     */
    private const BUCKET_MAP = [
        'managedBy' => ['tx_thuecat_organisation', 'managed_by'],
        'containedInPlace' => ['tx_thuecat_town', 'town'],
        'parkingFacilityNearBy' => ['tx_thuecat_parking_facility', 'parking_facility_near_by'],
    ];

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly FetchData $fetchData,
        private readonly Parser $parser,
        private readonly TcaSchemaFactory $tcaSchemaFactory,
    ) {
    }

    /**
     * Rewrites the payload in place so each row's outer key becomes either
     * the existing DB uid (as string) or a StringUtility::getUniqueId('NEW')
     * placeholder, injects `pid`, and drains the transient buckets — either
     * against the DB or, for unknown remote_ids, by fetching the resource
     * from ThueCat and merging the parsed rows back into the payload.
     */
    public function resolve(DataHandlerPayload $payload, ResolverContext $context): DataHandlerPayload
    {
        // remote_id → current outer key (uid string or NEW… placeholder).
        // Populated as rows get rekeyed; used to locate the owning row for
        // a transient write without re-probing the DB.
        $remoteIdToKey = [];

        $this->rekeyRowsAndInjectPid($payload, $context->storagePid, $remoteIdToKey);
        $this->drainTransients($payload, $context, $remoteIdToKey);
        $this->drainTranslationsAgainstExistingRows($payload, $remoteIdToKey);

        return $payload;
    }

    /**
     * Very-happy-path scenario: the parent row resolved to a real DB uid and
     * a translation row already exists for the requested sys_language_uid.
     * Look up the existing translation row's uid (one query per parent), add
     * it to the datamap under its own key with just the translated fields,
     * and drop the bucket entry. Languages with no matching translation row
     * stay in the bucket for scenarios 2/3 to handle.
     *
     * @param array<string, string> $remoteIdToKey
     */
    private function drainTranslationsAgainstExistingRows(
        DataHandlerPayload $payload,
        array $remoteIdToKey
    ): void {
        foreach ($payload->getTranslations() as $table => $rowsByRemoteId) {
            foreach ($rowsByRemoteId as $remoteId => $perLanguage) {
                $ownerKey = $remoteIdToKey[$remoteId] ?? null;
                if ($ownerKey === null) {
                    // Second-pass entry: datamap got cleared between Importer
                    // rounds, so $remoteIdToKey is empty. Resolve the parent
                    // uid directly from the DB (default-language row only —
                    // see findUidByRemoteId).
                    $uid = $this->findUidByRemoteId($table, $remoteId);
                    if ($uid > 0) {
                        $ownerKey = (string)$uid;
                    }
                }
                if ($ownerKey === null || !ctype_digit($ownerKey)) {
                    // Parent row is NEW… or still missing: scenario 3.
                    continue;
                }

                $existing = $this->findTranslationUidsByParent($table, (int)$ownerKey);

                foreach ($perLanguage as $sysLanguageUid => $fields) {
                    $translationUid = $existing[$sysLanguageUid] ?? null;
                    if ($translationUid === null) {
                        // Scenario 2: parent uid known, translation row
                        // missing. Stage a localize cmdmap so DataHandler
                        // creates the row; the bucket entry stays in place
                        // so a second resolver pass (after process_cmdmap)
                        // can drain it via the scenario-1 branch once the
                        // new translation uid is in the DB.
                        $payload->addCmdMap(
                            $table,
                            $ownerKey,
                            'localize',
                            $sysLanguageUid
                        );
                        continue;
                    }

                    $payload->addTranslationRow($table, (string)$translationUid, $fields);
                    $payload->removeTranslation($table, $remoteId, $sysLanguageUid);
                }
            }
        }
    }

    /**
     * @return array<int, int> sys_language_uid => translation row uid
     */
    private function findTranslationUidsByParent(string $table, int $parentUid): array
    {
        $language = $this->languageCapabilityFor($table);
        if ($language === null) {
            return [];
        }
        $parentField = $language['parent'];
        $languageField = $language['languageField'];

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction())
        ;
        $queryBuilder->select('uid', $languageField)
            ->from($table)
            ->where($queryBuilder->expr()->eq(
                $parentField,
                $queryBuilder->createNamedParameter($parentUid, Connection::PARAM_INT)
            ))
        ;

        $result = [];
        foreach ($queryBuilder->executeQuery()->fetchAllAssociative() as $row) {
            $sysLanguageUid = $row[$languageField] ?? null;
            $uid = $row['uid'] ?? null;
            if (!is_numeric($sysLanguageUid) || !is_numeric($uid)) {
                continue;
            }
            $result[(int)$sysLanguageUid] = (int)$uid;
        }
        return $result;
    }

    /**
     * @param array<string, string> $remoteIdToKey
     */
    private function rekeyRowsAndInjectPid(
        DataHandlerPayload $payload,
        int $storagePid,
        array &$remoteIdToKey
    ): void {
        foreach ($payload->getDataMap() as $table => $rows) {
            foreach (array_keys($rows) as $outerKey) {
                $outerKey = (string)$outerKey;
                // Skip rows already rekeyed (uid-numeric or NEW… placeholder) —
                // the API-fetch branch calls us again after merging new rows,
                // and we must not disturb previously assigned keys.
                if ($this->isAlreadyRekeyed($outerKey)) {
                    continue;
                }

                $remoteId = $outerKey;
                $uid = $this->findUidByRemoteId($table, $remoteId);
                $newKey = $uid > 0 ? (string)$uid : StringUtility::getUniqueId('NEW');

                $payload->rekeyRow($table, $remoteId, $newKey);
                $payload->setField($table, $newKey, 'pid', $storagePid);
                $remoteIdToKey[$remoteId] = $newKey;
            }
        }
    }

    private function isAlreadyRekeyed(string $outerKey): bool
    {
        return ctype_digit($outerKey) || str_starts_with($outerKey, 'NEW');
    }

    private function isFetchableUrl(string $reference): bool
    {
        if (filter_var($reference, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $scheme = parse_url($reference, PHP_URL_SCHEME);
        return $scheme === 'https' || $scheme === 'http';
    }

    /**
     * Outer loop over remaining transients. Each pass must either drop an @id
     * or bring new rows in via an API fetch, otherwise we'd spin forever —
     * throw instead.
     *
     * @param array<string, string> $remoteIdToKey
     */
    private function drainTransients(
        DataHandlerPayload $payload,
        ResolverContext $context,
        array &$remoteIdToKey
    ): void {
        while ($payload->getTransients() !== []) {
            $progress = false;

            foreach ($payload->getTransients() as $ownerTable => $rowsByRemoteId) {
                foreach ($rowsByRemoteId as $ownerRemoteId => $buckets) {
                    foreach ($buckets as $bucket => $references) {
                        $ownerKey = $remoteIdToKey[$ownerRemoteId] ?? null;
                        if ($ownerKey === null) {
                            continue;
                        }

                        if ($bucket === 'media') {
                            $this->shapeMediaBlob(
                                $payload,
                                $context,
                                $ownerTable,
                                $ownerRemoteId,
                                $ownerKey,
                                $references
                            );
                            $progress = true;
                            continue;
                        }

                        if ($bucket === 'accessibilitySpecification') {
                            $this->shapeAccessibilityBlob(
                                $payload,
                                $context,
                                $ownerTable,
                                $ownerRemoteId,
                                $ownerKey,
                                $references
                            );
                            $progress = true;
                            continue;
                        }

                        if (!isset(self::BUCKET_MAP[$bucket])) {
                            // Unknown bucket — parser emitted something the
                            // resolver has no mapping for. Loud failure is
                            // better than spinning through the drain loop.
                            throw new RuntimeException(
                                sprintf(
                                    'Unknown transient bucket "%s" on %s[%s].',
                                    $bucket,
                                    $ownerTable,
                                    $ownerRemoteId
                                ),
                                1745100005
                            );
                        }

                        [$targetTable, $targetField] = self::BUCKET_MAP[$bucket];

                        foreach ($references as $reference) {
                            // Ref→uid buckets only ever hold string @ids; the
                            // media bucket (handled above) is the sole case of
                            // tuple entries. A non-string here means the
                            // parser emitted tuples for the wrong bucket.
                            if (!is_string($reference)) {
                                throw new InvalidTransientReferenceException(
                                    sprintf(
                                        'Transient bucket "%s" on %s[%s] is not a list of @ids.',
                                        $bucket,
                                        $ownerTable,
                                        $ownerRemoteId
                                    ),
                                    1745100004
                                );
                            }

                            // Guard before any DB/API work: the reference must
                            // be a fetchable URL. Anything else (empty string,
                            // a bare id, an already-resolved uid leaking back
                            // in) means the parser wrote junk into the bucket
                            // and we'd loop forever or fire a bogus request.
                            if (!$this->isFetchableUrl($reference)) {
                                throw new InvalidTransientReferenceException(
                                    sprintf(
                                        'Transient reference "%s" in bucket "%s" on %s[%s] is not a fetchable URL.',
                                        $reference,
                                        $bucket,
                                        $ownerTable,
                                        $ownerRemoteId
                                    ),
                                    1745100000
                                );
                            }

                            $uid = $this->findUidByRemoteId($targetTable, $reference);
                            if ($uid > 0) {
                                $payload->setRelationField($ownerTable, $ownerKey, $targetField, $uid);
                                $payload->removeTransient($ownerTable, $ownerRemoteId, $bucket, $reference);
                                $remoteIdToKey[$reference] = (string)$uid;
                                $progress = true;
                                continue;
                            }

                            // Unknown remote_id — already rekeyed as a NEW
                            // placeholder on a previous pass means the owning
                            // row is in the payload; wire the relation up.
                            if (isset($remoteIdToKey[$reference])) {
                                $payload->setRelationField(
                                    $ownerTable,
                                    $ownerKey,
                                    $targetField,
                                    $remoteIdToKey[$reference]
                                );
                                $payload->removeTransient($ownerTable, $ownerRemoteId, $bucket, $reference);
                                $progress = true;
                                continue;
                            }

                            // Neither in DB nor in the payload — fetch from
                            // ThueCat and decide. The fetched node may not
                            // shape into $targetTable at all (containedInPlace
                            // notoriously mixes towns with regions and oatour
                            // entries — only town-typed nodes belong in the
                            // tx_thuecat_town bucket). When the parsed payload
                            // carries no row in $targetTable for this @id, we
                            // silently drop the bucket entry instead of
                            // merging unrelated entities into the datamap.
                            $merged = $this->fetchAndMaybeMerge(
                                $payload,
                                $context,
                                $reference,
                                $targetTable,
                                $remoteIdToKey
                            );
                            if (!$merged) {
                                $payload->removeTransient($ownerTable, $ownerRemoteId, $bucket, $reference);
                            }
                            $progress = true;
                        }
                    }
                }
            }

            if (!$progress) {
                throw new RuntimeException(
                    'Resolver made no progress draining transients; remaining: '
                    . json_encode($payload->getTransients()),
                    1745000000
                );
            }
        }
    }

    /**
     * Fetch + parse a transient reference into a throwaway payload, then
     * merge into the real payload only if the fetched node actually shapes
     * into the bucket's expected target table. Returns true on merge, false
     * when the reference is type-incompatible and the caller should drop
     * the bucket entry without further work.
     *
     * @param array<string, string> $remoteIdToKey
     */
    private function fetchAndMaybeMerge(
        DataHandlerPayload $payload,
        ResolverContext $context,
        string $reference,
        string $targetTable,
        array &$remoteIdToKey
    ): bool {
        $response = $this->fetchData->jsonLDFromUrl($reference, $context->apiKey);
        $graph = $response['@graph'] ?? [];
        if (!is_array($graph)) {
            $graph = [];
        }

        $fetchedPayload = $this->parser->parseFresh($graph, $context->language);
        if (!$fetchedPayload->hasRow($targetTable, $reference)) {
            return false;
        }

        $payload->mergeFrom($fetchedPayload);

        // Bring the newly merged rows into the rekey-map and inject pid.
        $this->rekeyRowsAndInjectPid($payload, $context->storagePid, $remoteIdToKey);
        return true;
    }

    /**
     * Drain the whole `media` bucket for one owner in a single pass. Each
     * entry is a `{kind, id}` tuple — kind (photo|image|video) drives the
     * `mainImage` + `type` fields on the shaped output, id points at a
     * dms_* MediaObject resource. When an entry's schema:author is an @id
     * ref (not a literal string), the Person node is fetched first and the
     * shaped name passed into MediaEntity.
     *
     * The media column is a JSON-encoded list, not a relation — emitting
     * the full list at once keeps order predictable and lets us write with
     * a single setField() + one clear of the whole bucket.
     *
     * @param list<string>|list<array{kind: string, id: string}> $entries
     */
    private function shapeMediaBlob(
        DataHandlerPayload $payload,
        ResolverContext $context,
        string $ownerTable,
        string $ownerRemoteId,
        string $ownerKey,
        array $entries
    ): void {
        $shaped = [];
        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                throw new InvalidTransientReferenceException(
                    sprintf(
                        'Media bucket entry on %s[%s] is not a {kind,id} tuple.',
                        $ownerTable,
                        $ownerRemoteId
                    ),
                    1745100001
                );
            }

            $reference = $entry['id'];
            if (!$this->isFetchableUrl($reference)) {
                throw new InvalidTransientReferenceException(
                    sprintf(
                        'Media reference "%s" on %s[%s] is not a fetchable URL.',
                        $reference,
                        $ownerTable,
                        $ownerRemoteId
                    ),
                    1745100002
                );
            }

            $mediaNode = $this->fetchGraphNode($reference, $context, $reference);
            $resolvedAuthor = null;
            $authorRef = MediaEntity::authorReference($mediaNode);
            if ($authorRef !== null) {
                $personNode = $this->fetchGraphNode($authorRef, $context, $authorRef);
                $resolvedAuthor = MediaEntity::shapePersonName($personNode, $context->language);
            }

            $mediaEntity = new MediaEntity();
            $mediaEntity->configure($mediaNode, $entry['kind'], $context->language, $resolvedAuthor);
            $shaped[] = $mediaEntity->toArray();
        }

        $payload->setField($ownerTable, $ownerKey, 'media', (string)(json_encode($shaped) ?: ''));

        // Drop every entry for this bucket in one go. Iterating the
        // references list with removeTransient would work too, but a
        // single teardown mirrors the "list emitted whole" semantics.
        foreach ($entries as $entry) {
            if (is_array($entry)) {
                $payload->removeTransient($ownerTable, $ownerRemoteId, 'media', $entry['id']);
            }
        }
    }

    /**
     * Drain the `accessibilitySpecification` bucket for one owner. Each
     * reference points at a separate AccessibilitySpecification resource;
     * the fetched node gets shaped into the legacy blob and written to the
     * owning row's `accessibility_specification` column. Multiple entries on
     * the same owner would overwrite each other — in practice the parser
     * emits at most one per owner, but we iterate defensively.
     *
     * @param list<string>|list<array{kind: string, id: string}> $references
     */
    private function shapeAccessibilityBlob(
        DataHandlerPayload $payload,
        ResolverContext $context,
        string $ownerTable,
        string $ownerRemoteId,
        string $ownerKey,
        array $references
    ): void {
        foreach ($references as $reference) {
            if (!is_string($reference)) {
                throw new InvalidTransientReferenceException(
                    sprintf(
                        'accessibilitySpecification bucket on %s[%s] must carry string @ids.',
                        $ownerTable,
                        $ownerRemoteId
                    ),
                    1745100006
                );
            }

            if (!$this->isFetchableUrl($reference)) {
                throw new InvalidTransientReferenceException(
                    sprintf(
                        'accessibilitySpecification reference "%s" on %s[%s] is not a fetchable URL.',
                        $reference,
                        $ownerTable,
                        $ownerRemoteId
                    ),
                    1745100007
                );
            }

            $node = $this->fetchGraphNode($reference, $context, $reference);
            $entity = new AccessibilitySpecificationEntity();
            $entity->configure($node, $context->language);

            $payload->setField(
                $ownerTable,
                $ownerKey,
                'accessibility_specification',
                (string)(json_encode($entity->toArray()) ?: '{}')
            );
            $payload->removeTransient($ownerTable, $ownerRemoteId, 'accessibilitySpecification', $reference);
        }
    }

    /**
     * Fetch a single JSON-LD resource and return the node whose @id matches.
     * Falls back to the first graph node for resources that publish a single
     * anonymous node. Throws if nothing matches so a broken upstream response
     * surfaces loudly instead of silently writing empty values into the blob.
     *
     * @return array<string, mixed>
     */
    private function fetchGraphNode(string $url, ResolverContext $context, string $expectedId): array
    {
        $response = $this->fetchData->jsonLDFromUrl($url, $context->apiKey);
        $graph = $response['@graph'] ?? [];
        if (!is_array($graph)) {
            $graph = [];
        }

        $fallback = null;
        foreach ($graph as $node) {
            if (!is_array($node)) {
                continue;
            }
            /** @var array<string, mixed> $node */
            if (($node['@id'] ?? null) === $expectedId) {
                return $node;
            }
            $fallback ??= $node;
        }

        if ($fallback !== null) {
            return $fallback;
        }

        throw new RuntimeException(
            sprintf('Fetched graph for "%s" is empty or malformed.', $url),
            1745100003
        );
    }

    /**
     * Look up the default-language row for a given remote_id. Translation
     * rows on translatable tables share the parent's remote_id, so without
     * the languageField restriction a second resolver pass could pick up a
     * translation uid as the "parent". Tables without a languageField in
     * TCA (non-translatable) get no restriction beyond DeletedRestriction.
     *
     * @todo workspaces — same shared-remote_id pitfall applies for v* rows.
     */
    private function findUidByRemoteId(string $table, string $remoteId): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction())
        ;
        $queryBuilder->select('uid')
            ->from($table)
            ->where($queryBuilder->expr()->eq(
                'remote_id',
                $queryBuilder->createNamedParameter($remoteId)
            ))
        ;

        $language = $this->languageCapabilityFor($table);
        if ($language !== null) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq(
                $language['languageField'],
                $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
            ));
        }

        $result = $queryBuilder->executeQuery()->fetchOne();
        return is_numeric($result) ? (int)$result : 0;
    }

    /**
     * Resolve the language-aware field names for a translatable table via
     * TcaSchema. Returns null for non-translatable tables — callers fall
     * back to no language restriction in that case.
     *
     * @return array{languageField: string, parent: string}|null
     */
    private function languageCapabilityFor(string $table): ?array
    {
        if (!$this->tcaSchemaFactory->has($table)) {
            return null;
        }
        $schema = $this->tcaSchemaFactory->get($table);
        if (!$schema->hasCapability(TcaSchemaCapability::Language)) {
            return null;
        }
        $capability = $schema->getCapability(TcaSchemaCapability::Language);
        return [
            'languageField' => $capability->getLanguageField()->getName(),
            'parent' => $capability->getTranslationOriginPointerField()->getName(),
        ];
    }
}
