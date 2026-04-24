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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\StringUtility;
use WerkraumMedia\ThueCat\Domain\Import\Importer\FetchData;
use WerkraumMedia\ThueCat\Domain\Import\Parser\DataHandlerPayload;
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

        return $payload;
    }

    /**
     * @param array<string, string> $remoteIdToKey
     */
    private function rekeyRowsAndInjectPid(
        DataHandlerPayload $payload,
        int $storagePid,
        array &$remoteIdToKey
    ): void {
        foreach ($payload->getPayload() as $table => $rows) {
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

                        if (!isset(self::BUCKET_MAP[$bucket])) {
                            // Unknown bucket (e.g. accessibilitySpecification)
                            // — not in scope for this pass. Leave it so a
                            // later resolver stage can pick it up. We still
                            // need progress elsewhere or the guard will fire.
                            continue;
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

                            // Neither in DB nor in the payload — fetch it from
                            // ThueCat, parse, and merge. The next drain pass
                            // picks the relation up via $remoteIdToKey.
                            $this->fetchAndMerge($payload, $context, $reference, $remoteIdToKey);
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
     * @param array<string, string> $remoteIdToKey
     */
    private function fetchAndMerge(
        DataHandlerPayload $payload,
        ResolverContext $context,
        string $reference,
        array &$remoteIdToKey
    ): void {
        $response = $this->fetchData->jsonLDFromUrl($reference, $context->apiKey);
        $graph = $response['@graph'] ?? [];
        if (!is_array($graph)) {
            $graph = [];
        }

        $fetchedPayload = $this->parser->parseFresh($graph, $context->language);
        $payload->mergeFrom($fetchedPayload);

        // Bring the newly merged rows into the rekey-map and inject pid.
        $this->rekeyRowsAndInjectPid($payload, $context->storagePid, $remoteIdToKey);
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

        $result = $queryBuilder->executeQuery()->fetchOne();
        return is_numeric($result) ? (int)$result : 0;
    }
}
