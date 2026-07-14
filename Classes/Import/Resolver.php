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

namespace WerkraumMedia\ThueCat\Import;

use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use WerkraumMedia\ThueCat\Import\Importer\FetchData;
use WerkraumMedia\ThueCat\Import\Importer\FetchData\ResourceNotFoundException;
use WerkraumMedia\ThueCat\Import\Parser\DataHandlerPayload;
use WerkraumMedia\ThueCat\Import\Parser\Entity\TransientEntity\AccessibilitySpecificationEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\TransientEntity\MediaEntity;
use WerkraumMedia\ThueCat\Import\Parser\Parser;
use WerkraumMedia\ThueCat\Import\Repositories\SysCategoryRepository;

#[Autoconfigure(public: true)]
class Resolver
{
    /**
     * Hard-coded mapping from transient bucket name to
     * [target table, target relation field on the owning row].
     * Extend as new buckets get ref→uid resolution.
     */
    protected const BUCKET_MAP = [
        'managedBy' => ['tx_thuecat_organisation', 'managed_by'],
        'containedInPlace' => ['tx_thuecat_town', 'town'],
        'parkingFacilityNearBy' => ['tx_thuecat_parking_facility', 'parking_facility_near_by'],
        // Date → Event back-reference: child Date rows manufactured by
        // EventEntity stage the parent's remote_id under this bucket; the
        // Resolver dereferences it to the parent uid (which is in the same
        // payload) and writes the `event` FK on each Date row.
        'event' => ['tx_events_domain_model_event', 'event'],
    ];

    /**
     * Inline IRRE children that the parent entity manufactures locally (the
     * child row and its parent are in the same payload — no fetch). Unlike the
     * generic BUCKET_MAP (which writes an FK onto the owner row), inline IRRE
     * needs the PARENT to list the child in an inline field; DataHandler then
     * fills the child's parentid + parenttable. The parent's remote_id is the
     * prefix of the child's own remote_id, split on `separator`. `fieldByValue`
     * picks the inline field from a column on the child row (e.g. opening hours
     * route regular vs special to different fields). Keyed by child table.
     */
    protected const INLINE_CHILD_PARENTS = [
        'tx_thuecat_opening_hours' => [
            'separator' => '::oh::',
            'column' => 'specification_type',
            'fieldByValue' => [
                'regular' => 'opening_hours_inline',
                'special' => 'special_opening_hours_inline',
            ],
        ],
    ];

    public function __construct(
        protected readonly ConnectionPool $connectionPool,
        protected readonly FetchData $fetchData,
        protected readonly Parser $parser,
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
        protected readonly MediaFileDownloader $mediaFileDownloader,
        protected readonly SiteFinder $siteFinder,
        protected readonly PageRepository $pageRepository,
        protected readonly SysCategoryRepository $sysCategoryRepository,
    ) {
    }

    /**
     * Rewrites the payload so each row's outer key becomes either
     * the existing DB uid (as string) or a StringUtility::getUniqueId('NEW')
     * placeholder, injects `pid`, and drains the transient buckets — either
     * against the DB or, for unknown remote_ids, by fetching the resource
     * from ThueCat and merging the parsed rows back into the payload.
     */
    public function resolve(DataHandlerPayload $payload, ResolverContext $context): DataHandlerPayload
    {
        $this->rekeyRowsAndInjectPid($payload, $context, 0);
        $this->drainTransients($payload, $context, $context->remoteIdToKey);
        $this->wireCategories($payload, $context);
        $this->drainTranslationsAgainstExistingRows($payload, $context);

        return $payload;
    }

    /**
     * Find-or-create a category per entry and append it to the row's relation,
     * staged into the same payload. Off (both anchors unset) skips wiring; the
     * on-but-invalid cases are rejected up front by ImportConfigurationValidator.
     */
    protected function wireCategories(DataHandlerPayload $payload, ResolverContext $context): void
    {
        $parentUid = $context->categoryParentUid;
        $categoryPid = $context->categoryStoragePid;
        if ($parentUid === 0 && $categoryPid === 0) {
            return;
        }

        $sitePageIds = $this->sitePageIds($context->storagePid);

        foreach ($payload->getCategories() as $table => $categoriesByOwner) {
            foreach ($categoriesByOwner as $ownerRemoteId => $categories) {
                $ownerKey = $context->remoteIdToKey[$ownerRemoteId] ?? null;
                if ($ownerKey === null) {
                    continue;
                }

                foreach ($categories as $category) {
                    $field = $category['field'];
                    // Guard: a mapper produced categories but the table lacks the
                    // destination field. Skip the write (DataHandler would fail on
                    // an unknown column) and flag it for the Importer to log once.
                    if (!$this->tableHasField($table, $field)) {
                        $context->categoriesFieldMissing[$table . '.' . $field] = true;
                        continue;
                    }

                    $categoryRemoteId = $category['remoteId'];
                    // Reuse the key staged earlier this run (across roots) so a
                    // recurring category yields one row.
                    $categoryKey = $context->categoryKeyByRemoteId[$categoryRemoteId] ?? null;
                    if ($categoryKey === null) {
                        $existingUid = $this->findCategoryUid($parentUid, $sitePageIds, $categoryRemoteId);
                        if ($existingUid > 0) {
                            $categoryKey = (string)$existingUid;
                        } else {
                            $categoryKey = StringUtility::getUniqueId('NEW');
                            $payload->addRow('sys_category', $categoryKey, [
                                'pid' => $categoryPid,
                                'parent' => $parentUid,
                                'title' => $category['title'],
                                'remote_id' => $categoryRemoteId,
                            ]);
                        }
                        $context->categoryKeyByRemoteId[$categoryRemoteId] = $categoryKey;
                    }

                    $payload->setRelationField($table, $ownerKey, $field, $categoryKey);
                }
            }
        }
    }

    /**
     * All page uids within the site the given pid belongs to (rootPid tree,
     * recursive). Empty when the pid maps to no site.
     *
     * @return list<int>
     */
    protected function sitePageIds(int $pid): array
    {
        try {
            $rootPageId = $this->siteFinder->getSiteByPageId($pid)->getRootPageId();
        } catch (SiteNotFoundException) {
            return [];
        }

        return array_values($this->pageRepository->getPageIdsRecursive([$rootPageId], 99));
    }

    /**
     * Existing category matching by remote_id (so it survives renames), accepted
     * only when $parentUid is in its rootline — a match under a different parent
     * is rejected so a fresh one is created under ours.
     *
     * @param list<int> $sitePageIds
     */
    protected function findCategoryUid(int $parentUid, array $sitePageIds, string $remoteId): int
    {
        foreach ($this->sysCategoryRepository->findUidsByRemoteId($remoteId, $sitePageIds) as $uid) {
            if ($this->hasParentInRootline($uid, $parentUid)) {
                return $uid;
            }
        }

        return 0;
    }

    /**
     * Walk parent upward from $uid; true if $ancestorUid is met. Cycle-guarded.
     */
    protected function hasParentInRootline(int $uid, int $ancestorUid): bool
    {
        $seen = [];
        $current = $uid;
        while ($current > 0 && !isset($seen[$current])) {
            $seen[$current] = true;
            $current = $this->sysCategoryRepository->findParent($current);

            if ($current === $ancestorUid) {
                return true;
            }
        }

        return false;
    }

    /**
     * possible scenarios:
     *
     * - parent row resolved to a real DB uid and a translation row already exists for the requested sys_language_uid.
     * - parent row resolves, but no translation row exists
     * - no parent and therefore no translation exists
     *
     * - Languages with no matching translation row stay in the bucket for scenarios 2/3 to handle.
     *
     * Each (remote_id, sys_language_uid) pair is staged at most once per
     * importer run: the translation status map on the context short-circuits
     * any later sighting via isTranslationUpdated().
     */
    protected function drainTranslationsAgainstExistingRows(
        DataHandlerPayload $payload,
        ResolverContext $context
    ): void {
        foreach ($payload->getTranslations() as $table => $rowsByRemoteId) {
            foreach ($rowsByRemoteId as $remoteId => $perLanguage) {
                $ownerKey = $context->remoteIdToKey[$remoteId] ?? null;
                if ($ownerKey === null || !ctype_digit($ownerKey)) {
                    continue;
                }

                $existing = $this->findTranslationUidsByParent($table, (int)$ownerKey);

                foreach ($perLanguage as $sysLanguageUid => $fields) {
                    if ($context->isTranslationUpdated($remoteId, $sysLanguageUid)) {
                        // Already staged for this (remote_id, language) earlier, no need to do it again
                        $payload->removeTranslation($table, $remoteId, $sysLanguageUid);
                        continue;
                    }

                    $translationUid = $existing[$sysLanguageUid] ?? null;
                    if ($translationUid === null) {
                        // Scenario 2: parent uid known, translation row
                        // missing. Stage it here for creation, next run will
                        // promote the uid
                        $payload->addCmdMap(
                            $table,
                            $ownerKey,
                            'localize',
                            $sysLanguageUid
                        );
                        $context->markTranslationCreated($remoteId, $sysLanguageUid);
                        continue;
                    }

                    $payload->addTranslationRow($table, (string)$translationUid, $fields);
                    $payload->removeTranslation($table, $remoteId, $sysLanguageUid);
                    $context->markTranslationUpdated($remoteId, $sysLanguageUid);
                }
            }
        }
    }

    /**
     * @return array<int, int> sys_language_uid => translation row uid
     */
    protected function findTranslationUidsByParent(string $table, int $parentUid): array
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
     * replace remote_id with either NEW... placeholder or resolved uid
     */
    protected function rekeyRowsAndInjectPid(
        DataHandlerPayload $payload,
        ResolverContext $context,
        int $depth
    ): void {
        foreach ($payload->getDataMap() as $table => $rows) {
            foreach (array_keys($rows) as $outerKey) {
                $outerKey = (string)$outerKey;
                if ($this->isAlreadyRekeyed($outerKey)) {
                    continue;
                }

                $remoteId = $outerKey;

                if ($context->isUpdated($remoteId)) {
                    $payload->dropRow($table, $remoteId);
                    continue;
                }

                $existingKey = $context->remoteIdToKey[$remoteId] ?? null;
                if ($existingKey !== null) {
                    $newKey = $existingKey;
                } else {
                    $uid = $this->findUidByRemoteId($table, $remoteId);
                    $newKey = $uid > 0 ? (string)$uid : StringUtility::getUniqueId('NEW');
                }

                $payload->rekeyRow($table, $remoteId, $newKey);
                $payload->setField($table, $newKey, 'pid', $context->storagePid);
                $context->remoteIdToKey[$remoteId] = $newKey;
                $context->remoteIdToTable[$remoteId] = $table;
                if (!isset($context->depthByRemoteId[$remoteId])) {
                    $context->depthByRemoteId[$remoteId] = $depth;
                }

                $context->markUpdated($remoteId);
            }
        }

        $this->wireInlineChildrenToParents($payload, $context);
    }

    protected function isAlreadyRekeyed(string $outerKey): bool
    {
        return ctype_digit($outerKey) || str_starts_with($outerKey, 'NEW');
    }

    protected function isFetchableUrl(string $reference): bool
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
    protected function drainTransients(
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

                        $atCap = (($context->depthByRemoteId[$ownerRemoteId] ?? 0) >= ResolverContext::MAX_FETCH_DEPTH);

                        if ($bucket === 'media') {
                            if ($atCap) {
                                foreach ($references as $entry) {
                                    if (is_array($entry)) {
                                        $payload->removeTransient($ownerTable, $ownerRemoteId, 'media', $entry['id']);
                                    } elseif (is_string($entry)) {
                                        $payload->removeTransient($ownerTable, $ownerRemoteId, 'media', $entry);
                                    }
                                }
                                $progress = true;
                                continue;
                            }
                            $this->importMediaFiles(
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
                            if ($atCap) {
                                foreach ($references as $reference) {
                                    if (is_string($reference)) {
                                        $payload->removeTransient(
                                            $ownerTable,
                                            $ownerRemoteId,
                                            'accessibilitySpecification',
                                            $reference
                                        );
                                    }
                                }
                                $progress = true;
                                continue;
                            }
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
                            // resolver has no mapping for.
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

                            if ($context->isUpdated($reference)) {
                                if (($context->remoteIdToTable[$reference] ?? null) === $targetTable) {
                                    $payload->setRelationField(
                                        $ownerTable,
                                        $ownerKey,
                                        $targetField,
                                        $remoteIdToKey[$reference]
                                    );
                                }
                                $payload->removeTransient($ownerTable, $ownerRemoteId, $bucket, $reference);
                                $progress = true;
                                continue;
                            }

                            $uid = $this->findUidByRemoteId($targetTable, $reference);
                            if ($uid > 0) {
                                $payload->setRelationField($ownerTable, $ownerKey, $targetField, $uid);
                                $remoteIdToKey[$reference] = (string)$uid;
                                $context->remoteIdToTable[$reference] = $targetTable;
                                $context->markFound($reference);

                                if (($context->depthByRemoteId[$ownerRemoteId] ?? 0) >= ResolverContext::MAX_FETCH_DEPTH) {
                                    $payload->removeTransient($ownerTable, $ownerRemoteId, $bucket, $reference);
                                    $progress = true;
                                    continue;
                                }

                                $this->fetchAndMerge(
                                    $payload,
                                    $context,
                                    $ownerTable,
                                    $ownerKey,
                                    $ownerRemoteId,
                                    $targetTable,
                                    $targetField,
                                    $reference,
                                    $remoteIdToKey
                                );
                                $payload->removeTransient($ownerTable, $ownerRemoteId, $bucket, $reference);
                                $progress = true;
                                continue;
                            }

                            if (isset($remoteIdToKey[$reference])) {
                                if (($context->remoteIdToTable[$reference] ?? null) === $targetTable) {
                                    $payload->setRelationField(
                                        $ownerTable,
                                        $ownerKey,
                                        $targetField,
                                        $remoteIdToKey[$reference]
                                    );
                                }
                                $payload->removeTransient($ownerTable, $ownerRemoteId, $bucket, $reference);
                                $progress = true;
                                continue;
                            }

                            if (($context->depthByRemoteId[$ownerRemoteId] ?? 0) >= ResolverContext::MAX_FETCH_DEPTH) {
                                $payload->removeTransient($ownerTable, $ownerRemoteId, $bucket, $reference);
                                $progress = true;
                                continue;
                            }

                            $this->fetchAndMerge(
                                $payload,
                                $context,
                                $ownerTable,
                                $ownerKey,
                                $ownerRemoteId,
                                $targetTable,
                                $targetField,
                                $reference,
                                $remoteIdToKey
                            );
                            $payload->removeTransient($ownerTable, $ownerRemoteId, $bucket, $reference);
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
     * Fetch + parse a transient reference and merge whatever the parser
     * produced into the real payload.
     *
     * @param array<string, string> $remoteIdToKey
     */
    protected function fetchAndMerge(
        DataHandlerPayload $payload,
        ResolverContext $context,
        string $ownerTable,
        string $ownerKey,
        string $ownerRemoteId,
        string $targetTable,
        string $targetField,
        string $reference,
        array &$remoteIdToKey
    ): void {
        $response = $this->fetchData->jsonLDFromUrl($reference, $context->apiKey);
        $graph = $response['@graph'] ?? [];
        if (!is_array($graph)) {
            $graph = [];
        }

        $fetchedPayload = $this->parser->parseFresh(
            $graph,
            $context->parserContext,
            $context->language,
            $context->translationLanguages
        );
        $payload->mergeFrom($fetchedPayload);

        $childDepth = ($context->depthByRemoteId[$ownerRemoteId] ?? 0) + 1;
        $this->rekeyRowsAndInjectPid($payload, $context, $childDepth);

        if (($context->remoteIdToTable[$reference] ?? null) === $targetTable) {
            $payload->setRelationField(
                $ownerTable,
                $ownerKey,
                $targetField,
                $remoteIdToKey[$reference]
            );
        }
    }

    /**
     * Download each media entry's file and stage a sys_file_reference on the
     * owner: photo → main_image, image → media_files. video skipped (no file).
     *
     * @param list<string>|list<array{kind: string, id: string}> $entries
     */
    protected function importMediaFiles(
        DataHandlerPayload $payload,
        ResolverContext $context,
        string $ownerTable,
        string $ownerRemoteId,
        string $ownerKey,
        array $entries
    ): void {
        $target = $context->targetFolder;
        $staging = $context->stagingFolder;
        if ($target === null || $staging === null) {
            throw new InvalidTransientReferenceException(
                sprintf(
                    'Media bucket on %s[%s] cannot be drained without resolved '
                    . 'target/staging folders on the context.',
                    $ownerTable,
                    $ownerRemoteId
                ),
                1748520004
            );
        }

        $existingReferences = [];
        $claimedReferences = [];
        if (MathUtility::canBeInterpretedAsInteger($ownerKey)) {
            $ownerUid = (int)$ownerKey;
            foreach (['main_image', 'media_files'] as $field) {
                $existingReferences[$field] = $this->findExistingReferences($ownerTable, $ownerUid, $field);
            }
        }

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

            if ($entry['kind'] === 'video') {
                continue;
            }

            try {
                $mediaNode = $this->fetchGraphNode($reference, $context, $reference);
            } catch (ResourceNotFoundException) {
                // Upstream removed the media — drop the reference
                continue;
            }
            $resolvedAuthor = null;
            $authorRef = MediaEntity::authorReference($mediaNode);
            if ($authorRef !== null) {
                try {
                    $personNode = $this->fetchGraphNode($authorRef, $context, $authorRef);
                    $resolvedAuthor = MediaEntity::shapePersonName($personNode, $context->language);
                } catch (ResourceNotFoundException) {
                    // Author resource gone; leave the media without an author.
                }
            }

            $mediaEntity = new MediaEntity();
            $mediaEntity->configure($mediaNode, $entry['kind'], $context->language, $resolvedAuthor);

            $downloadUrl = $mediaEntity->getUrl();
            if ($downloadUrl === '') {
                continue;
            }

            $file = $this->mediaFileDownloader->download(
                $target,
                $staging,
                $downloadUrl,
                $this->extractDmsId($reference),
                $mediaEntity->getOriginalFileName(),
            );
            if ($file === null) {
                continue;
            }

            $file->getMetaData()->add(array_filter([
                'title' => $mediaEntity->getOriginalFileName(),
                'description' => $mediaEntity->getDescription(),
                'creator' => $mediaEntity->getAuthor(),
                'copyright' => $mediaEntity->getCopyright(),
            ], static fn (string $value): bool => $value !== ''));

            $targetField = $mediaEntity->isMainImage() ? 'main_image' : 'media_files';
            $fileUid = $file->getUid();
            $existingRefUid = $existingReferences[$targetField][$fileUid] ?? null;
            if ($existingRefUid !== null) {
                $claimedReferences[$existingRefUid] = true;
            }
            $this->stageFileReference(
                $payload,
                $ownerTable,
                $ownerKey,
                $targetField,
                $fileUid,
                $mediaEntity,
                $context->storagePid,
                $existingRefUid,
            );
        }

        // Delete references the upstream set no longer contain
        foreach ($existingReferences as $byFile) {
            foreach ($byFile as $refUid) {
                if (!isset($claimedReferences[$refUid])) {
                    $payload->addCmdMap('sys_file_reference', (string)$refUid, 'delete', 1);
                }
            }
        }

        foreach ($entries as $entry) {
            $payload->removeTransient($ownerTable, $ownerRemoteId, 'media', $entry['id']);
        }
    }

    protected function stageFileReference(
        DataHandlerPayload $payload,
        string $ownerTable,
        string $ownerKey,
        string $targetField,
        int $fileUid,
        MediaEntity $media,
        int $pid,
        ?int $existingRefUid = null
    ): void {
        $referenceKey = $existingRefUid !== null
            ? (string)$existingRefUid
            : StringUtility::getUniqueId('NEW');
        $payload->addRow('sys_file_reference', $referenceKey, [
            'pid' => $pid,
            'uid_local' => $fileUid,
            'title' => $media->getOriginalFileName(),
            'description' => $media->getDescription(),
        ]);
        $payload->setRelationField($ownerTable, $ownerKey, $targetField, $referenceKey);
    }

    protected function wireInlineChildrenToParents(
        DataHandlerPayload $payload,
        ResolverContext $context
    ): void {
        foreach (self::INLINE_CHILD_PARENTS as $childTable => $config) {
            foreach ($payload->getDataMap()[$childTable] ?? [] as $childKey => $childRow) {
                $childKey = (string)$childKey;
                $childRemoteId = (string)($childRow['remote_id'] ?? '');
                $parentRemoteId = explode($config['separator'], $childRemoteId, 2)[0];

                $parentKey = $context->remoteIdToKey[$parentRemoteId] ?? null;
                $parentTable = $context->remoteIdToTable[$parentRemoteId] ?? null;
                if ($parentKey === null || $parentTable === null) {
                    continue;
                }

                $columnValue = (string)($childRow[$config['column']] ?? '');
                $inlineField = $config['fieldByValue'][$columnValue] ?? null;
                if ($inlineField === null) {
                    continue;
                }

                $payload->setRelationField($parentTable, $parentKey, $inlineField, $childKey);
            }
        }
    }

    /**
     * Last path segment of a dms_* resource URL, the stable basis for the
     * stored filename: "https://thuecat.org/resources/dms_5159216"
     * → "dms_5159216".
     */
    protected function extractDmsId(string $reference): string
    {
        $path = (string)(parse_url($reference, PHP_URL_PATH) ?: $reference);
        $segment = basename($path);
        return $segment !== '' ? $segment : $reference;
    }

    /**
     * @param list<string>|list<array{kind: string, id: string}> $references
     */
    protected function shapeAccessibilityBlob(
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

            try {
                $node = $this->fetchGraphNode($reference, $context, $reference);
            } catch (ResourceNotFoundException) {
                // Upstream removed the spec
                $payload->removeTransient($ownerTable, $ownerRemoteId, 'accessibilitySpecification', $reference);
                continue;
            }
            $entity = new AccessibilitySpecificationEntity();
            $entity->configure($node, $context->language);

            $payload->setField(
                $ownerTable,
                $ownerKey,
                'accessibility_specification',
                (string)(json_encode($entity->toArray()) ?: '{}')
            );

            // The blob is built by the resolver (not the parser), so per-
            // language translation rows would otherwise inherit the default-
            // language text. Re-shape per translation language and stage each
            // blob into the translations bucket so drainTranslationsAgainstExistingRows
            // writes them onto the existing translation rows. Only languages
            // the parser already staged a translation for (i.e. the source
            // graph carries text in that language) get a blob — otherwise we'd
            // create an empty translation row for site languages the upstream
            // doesn't cover.
            $existingTranslations = $payload->getTranslations()[$ownerTable][$ownerRemoteId] ?? [];
            foreach ($context->translationLanguages as $languageCode => $sysLanguageUid) {
                if (!isset($existingTranslations[$sysLanguageUid])) {
                    continue;
                }
                $translatedEntity = new AccessibilitySpecificationEntity();
                $translatedEntity->configure($node, $languageCode);
                $payload->addTranslationField(
                    $ownerTable,
                    $ownerRemoteId,
                    $sysLanguageUid,
                    'accessibility_specification',
                    (string)(json_encode($translatedEntity->toArray()) ?: '{}')
                );
            }

            $payload->removeTransient($ownerTable, $ownerRemoteId, 'accessibilitySpecification', $reference);
        }
    }

    /**
     * Fetch a single JSON-LD resource and return the node whose @id matches.
     * Falls back to the first graph node for resources that publish a single
     * anonymous node. Throws if nothing matches so a broken upstream response
     * surfaces loudly instead of silently writing empty values into the field.
     *
     * @return array<string, mixed>
     */
    protected function fetchGraphNode(string $url, ResolverContext $context, string $expectedId): array
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
     * @return array<int, int> uid_local => sys_file_reference.uid
     */
    protected function findExistingReferences(
        string $ownerTable,
        int $ownerUid,
        string $ownerField
    ): array {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction())
        ;
        $rows = $queryBuilder->select('uid', 'uid_local')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq(
                    'tablenames',
                    $queryBuilder->createNamedParameter($ownerTable)
                ),
                $queryBuilder->expr()->eq(
                    'fieldname',
                    $queryBuilder->createNamedParameter($ownerField)
                ),
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($ownerUid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        $byFile = [];
        foreach ($rows as $row) {
            $byFile[(int)(is_numeric($row['uid_local']) ? $row['uid_local'] : 0)]
                = (int)(is_numeric($row['uid']) ? $row['uid'] : 0);
        }
        return $byFile;
    }

    /**
     * Look up the default-language row for a given remote_id.
     */
    protected function findUidByRemoteId(string $table, string $remoteId): int
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

    protected function tableHasField(string $table, string $field): bool
    {
        if (!$this->tcaSchemaFactory->has($table)) {
            return false;
        }
        return $this->tcaSchemaFactory->get($table)->hasField($field);
    }

    /**
     * @return array{languageField: string, parent: string}|null
     */
    protected function languageCapabilityFor(string $table): ?array
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
