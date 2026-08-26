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

use Exception;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use WerkraumMedia\ThueCat\Import\Importer\FetchData;
use WerkraumMedia\ThueCat\Import\Importer\FetchData\ResourceNotFoundException;
use WerkraumMedia\ThueCat\Import\Parser\DataHandlerPayload;
use WerkraumMedia\ThueCat\Import\Parser\Entity\AbstractEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\AddressEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Events\Support\StaleDateReaper;
use WerkraumMedia\ThueCat\Import\Parser\Entity\KeywordTermEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Support\CurieExpander;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Support\MediaFieldMap;
use WerkraumMedia\ThueCat\Import\Parser\Entity\TrailConditionEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\TrailLocationEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\TrailWayTypeEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\TransientEntity\AccessibilitySpecificationEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\TransientEntity\MediaEntity;
use WerkraumMedia\ThueCat\Import\Parser\Parser;
use WerkraumMedia\ThueCat\Import\Repositories\SysCategoryRepository;
use WerkraumMedia\ThueCat\Import\SysCategory\ChainBuilder;
use WerkraumMedia\ThueCat\Import\SysCategory\ParentStrategies;
use WerkraumMedia\ThueCat\Import\SysCategory\SysCategoryAnchor;
use WerkraumMedia\ThueCat\Import\SysCategory\SysCategoryProvisioner;
use WerkraumMedia\ThueCat\Import\SysCategory\SysCategoryProvisioningState;
use WerkraumMedia\ThueCat\Import\SysCategory\SysCategoryTerm;
use WerkraumMedia\ThueCat\Import\SysCategory\TitleResolver;
use WerkraumMedia\ThueCat\Import\Vocabulary\VocabularyIndex;
use WerkraumMedia\ThueCat\Import\Vocabulary\VocabularyProvider;

#[Autoconfigure(public: true)]
class Resolver
{
    /** Destination relation column for keyword categories. */
    protected const KEYWORD_FIELD = 'keywords';

    /** Marks a sys_category identifier as this run's keyword tree. */
    protected const KEYWORD_IDENTIFIER_PREFIX = 'keyword:';

    /** Marks a sys_category identifier as this run's `@type` tree. */
    protected const TYPE_IDENTIFIER_PREFIX = 'type:';

    /**
     * Transient bucket → [target table → relation field on the owning row].
     *
     * Every target table gets its own field, because Extbase resolves a
     * relation through one concrete class. Most buckets name exactly one
     * table; containedInPlace names several, since upstream puts whatever
     * contains a record behind that one property.
     *
     * The field is chosen by the table the referenced record ACTUALLY imported
     * into. A reference whose table has no entry for its bucket is reported
     * unrelatable rather than silently dropped, so this map doubles as the
     * allowlist — adding a target table means adding it here AND adding its
     * field to the TCA of every owner table.
     *
     * Order matters: the pre-fetch lookup probes a bucket's tables in
     * sequence and takes the first hit, and the first entry is the default
     * used while a reference's table is still unknown. Commonest kind first.
     */
    protected const BUCKET_MAP = [
        'managedBy' => [
            'tx_thuecat_organisation' => 'managed_by',
        ],
        'containedInPlace' => [
            'tx_thuecat_town' => 'town',
            'tx_thuecat_organisation' => 'contained_in_organisation',
            'tx_thuecat_tourist_attraction' => 'contained_in_attraction',
            'tx_thuecat_tourist_information' => 'contained_in_tourist_information',
            'tx_thuecat_parking_facility' => 'contained_in_parking_facility',
            'tx_thuecat_trail' => 'contained_in_trail',
        ],
        'parkingFacilityNearBy' => [
            'tx_thuecat_parking_facility' => 'parking_facility_near_by',
        ],
        // Date → Event back-reference: child Date rows manufactured by
        // EventEntity stage the parent's remote_id under this bucket; the
        // Resolver dereferences it to the parent uid (which is in the same
        // payload) and writes the `event` FK on each Date row.
        'event' => [
            'tx_events_domain_model_event' => 'event',
        ],
    ];

    /**
     * Inline IRRE children that the parent entity manufactures locally (the
     * child row and its parent are in the same payload — no fetch). Unlike the
     * generic BUCKET_MAP (which writes an FK onto the owner row), inline IRRE
     * needs the PARENT to list the child in an inline field; DataHandler then
     * fills the child's parentid + parenttable. The parent's remote_id is the
     * prefix of the child's own remote_id, split on `separator`. `fieldByValue`
     * picks the inline field from a column on the child row (e.g. opening hours
     * route regular vs special to different fields); `field` names one target
     * field unconditionally, for children that have only one. Keyed by child
     * table.
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
        'tx_thuecat_address' => [
            'separator' => AddressEntity::SEPARATOR,
            'field' => 'address_inline',
            'reapOrphans' => true,
        ],
        'tx_thuecat_trail_way_type' => [
            'separator' => TrailWayTypeEntity::SEPARATOR,
            'field' => 'way_types',
            'reapOrphans' => true,
        ],
        'tx_thuecat_trail_condition' => [
            'separator' => TrailConditionEntity::SEPARATOR,
            'field' => 'conditions',
            'reapOrphans' => true,
        ],
        'tx_thuecat_trail_location' => [
            'separator' => TrailLocationEntity::SEPARATOR,
            'column' => 'location_type',
            'fieldByValue' => [
                TrailLocationEntity::TYPE_START => 'start_location',
                TrailLocationEntity::TYPE_END => 'end_location',
            ],
            'reapOrphans' => true,
        ],
    ];

    public function __construct(
        protected readonly ConnectionPool $connectionPool,
        protected readonly FetchData $fetchData,
        protected readonly Parser $parser,
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
        protected readonly MediaFileDownloader $mediaFileDownloader,
        protected readonly SysCategoryRepository $sysCategoryRepository,
        protected readonly ImportLogger $importLogger,
        protected readonly StaleDateReaper $staleDateReaper,
        protected readonly MediaFieldMap $mediaFieldMap,
        protected readonly FetchFailureVerdict $fetchFailureVerdict,
        protected readonly SysCategoryProvisioner $sysCategoryProvisioner,
        protected readonly ChainBuilder $chainBuilder,
        protected readonly TitleResolver $titleResolver,
        protected readonly ParentStrategies $parentStrategies,
        protected readonly VocabularyProvider $vocabularyProvider,
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
        $this->drainTranslationsUsing($payload, $context, $context->categoryKeyByRemoteId);
        $this->amendMatchReports($payload, $context);
        $this->preserveEditorRelations($payload, $this->categoryFieldsWritten($payload));
        $this->drainTranslationsAgainstExistingRows($payload, $context);
        $this->staleDateReaper->reap($payload);

        return $payload;
    }

    /**
     * Runs after the last root: a keyword shared across roots must not be
     * staged by one and reaped by the next. Parents are created before their
     * children so the tree exists top-down.
     */
    public function flushCollectedKeywords(DataHandlerPayload $payload, ResolverContext $context): void
    {
        if ($context->collectedKeywords === [] || $context->keywordParentUid === 0) {
            return;
        }

        $sitePageIds = $context->sitePageIds;

        // Ancestors first: a term collected before its set would otherwise be
        // created while the set has no key yet and silently hang off the anchor.
        $byRemoteId = [];
        foreach ($context->collectedKeywords as $keyword) {
            $byRemoteId[$keyword->remoteId] = $keyword;
        }
        foreach ($context->collectedKeywords as $keyword) {
            $this->createKeywordAncestors($payload, $context, $sitePageIds, $keyword, $byRemoteId, []);
        }

        foreach ($context->collectedKeywords as $keyword) {
            // Ancestors are created above; relating them too would make every
            // record look like it carries each group above its keywords.
            if (!$keyword->isCited) {
                continue;
            }

            $ownerKey = $context->remoteIdToKey[$keyword->ownerKey] ?? $keyword->ownerKey;
            if (!$this->tableHasField($keyword->ownerTable, $keyword->targetField)) {
                $context->categoriesFieldMissing[$keyword->ownerTable . '.' . $keyword->targetField] = true;
                continue;
            }

            $categoryKey = $this->keywordCategoryKey($payload, $context, $sitePageIds, $keyword);
            $payload->setRelationField(
                $keyword->ownerTable,
                $ownerKey,
                $keyword->targetField,
                $categoryKey
            );
        }

        $keywordFields = [];
        foreach ($context->collectedKeywords as $keyword) {
            $keywordFields[$keyword->targetField] = true;
        }
        $this->preserveEditorRelations($payload, array_keys($keywordFields));

        $this->preserveKeywordsBehindFailures($payload, $context);
        // Keywords are staged after the last root, so their translations miss
        // the per-root drain entirely.
        $this->drainTranslationsUsing($payload, $context, $context->keywordKeyByRemoteId);
    }

    /**
     * Category relation fields this payload writes into. Taken from the staged
     * categories rather than a constant: the destination column comes from the
     * mapper's kind() and differs per record kind.
     *
     * @return list<string>
     */
    protected function categoryFieldsWritten(DataHandlerPayload $payload): array
    {
        $fields = [];
        foreach ($payload->getCategories() as $categoriesByOwner) {
            foreach ($categoriesByOwner as $categories) {
                foreach ($categories as $category) {
                    $fields[$category['field']] = true;
                }
            }
        }

        return array_keys($fields);
    }

    /**
     * Carry an owner's editor-added categories into every relation list this
     * run submits. Submitting a list replaces the stored one, so a category the
     * import never created is removed unless it is put back — silently, since
     * nothing distinguishes it at the point of writing.
     *
     * Runs over the datamap rather than a register of writes: the fields worth
     * examining are exactly the ones a value was written into.
     *
     * @param list<string> $fields
     */
    protected function preserveEditorRelations(DataHandlerPayload $payload, array $fields): void
    {
        foreach ($payload->getDataMap() as $table => $rows) {
            foreach ($rows as $key => $row) {
                $key = (string)$key;
                // A NEW… owner has no stored relations to lose.
                if (!MathUtility::canBeInterpretedAsInteger($key)) {
                    continue;
                }

                foreach ($fields as $field) {
                    if (!isset($row[$field]) || !$this->tableHasField($table, $field)) {
                        continue;
                    }

                    foreach ($this->sysCategoryRepository->findRelatedUidsWithoutRemoteId(
                        $table,
                        (int)$key,
                        $field
                    ) as $uid) {
                        $payload->setRelationField($table, $key, $field, (string)$uid);
                    }
                }
            }
        }
    }

    /**
     * Carry an owner's stored keyword uids into the submitted list where its
     * resolution failed technically. Submitting the list is what removes, so a
     * keyword left out by an outage would be dropped and restored on the next
     * healthy run.
     */
    protected function preserveKeywordsBehindFailures(
        DataHandlerPayload $payload,
        ResolverContext $context
    ): void {
        foreach (array_keys($context->keywordFailureByField) as $failedField) {
            [$ownerTable, $ownerKey, $targetField] = explode('|', (string)$failedField, 3);

            $resolvedOwnerKey = $context->remoteIdToKey[$ownerKey] ?? $ownerKey;
            if (!MathUtility::canBeInterpretedAsInteger($resolvedOwnerKey)) {
                // Never stored, so nothing can be lost.
                continue;
            }

            foreach ($this->sysCategoryRepository->findRelatedUids(
                $ownerTable,
                (int)$resolvedOwnerKey,
                $targetField
            ) as $uid) {
                $payload->setRelationField($ownerTable, $resolvedOwnerKey, $targetField, (string)$uid);
            }
        }
    }

    /**
     * @param list<int> $sitePageIds
     * @param array<string, CollectedKeyword> $byRemoteId
     * @param list<string> $visited
     */
    protected function createKeywordAncestors(
        DataHandlerPayload $payload,
        ResolverContext $context,
        array $sitePageIds,
        CollectedKeyword $keyword,
        array $byRemoteId,
        array $visited
    ): void {
        if (in_array($keyword->remoteId, $visited, true)) {
            return;
        }
        $visited[] = $keyword->remoteId;

        $parent = $keyword->parentRemoteId === null ? null : ($byRemoteId[$keyword->parentRemoteId] ?? null);
        if ($parent !== null) {
            $this->createKeywordAncestors($payload, $context, $sitePageIds, $parent, $byRemoteId, $visited);
        }

        $this->keywordCategoryKey($payload, $context, $sitePageIds, $keyword);
    }

    /**
     * A term's titles keyed by language code. The entity records translations
     * against sys_language_uid, which the run's language map inverts back to
     * the codes a keyword is carried by.
     *
     * @return array<string, string>
     */
    protected function keywordTitles(ResolverContext $context, KeywordTermEntity $term): array
    {
        $titles = [$context->language => $term->getTitle()];

        $translations = $term->getTranslations();
        foreach ($context->translationLanguages as $language => $sysLanguageUid) {
            $title = $translations[$sysLanguageUid]['title'] ?? null;
            if (is_string($title) && $title !== '') {
                $titles[$language] = $title;
            }
        }

        return $titles;
    }

    /**
     * Find-or-create the category for one keyword; assumes its parent exists.
     *
     * @param list<int> $sitePageIds
     */
    protected function keywordCategoryKey(
        DataHandlerPayload $payload,
        ResolverContext $context,
        array $sitePageIds,
        CollectedKeyword $keyword
    ): string {
        // The state binds to the context's own map, so keys stay where
        // promoteNewKeys() expects to find them between rounds.
        $state = new SysCategoryProvisioningState($context->keywordKeyByRemoteId);

        $key = $this->sysCategoryProvisioner->provision(
            $payload,
            $state,
            $this->keywordAnchor($context),
            new SysCategoryTerm(
                self::keywordSourceValue($keyword->remoteId),
                $keyword->titles,
                $keyword->parentRemoteId === null
                    ? null
                    : self::keywordSourceValue($keyword->parentRemoteId)
            ),
            $sitePageIds,
            $context->language,
            $context->translationLanguages
        );

        // A keyword without a usable title never reaches here: the collector
        // skips a husk before it is collected at all.
        return (string)$key;
    }

    /**
     * Resolve keyword URIs to terms and collect them; nothing is staged here.
     * Staging happens once at flush, which is the only point holding an owner's
     * complete set — the precondition for deciding what to reap.
     *
     * @param list<string>|list<array{id: string, title?: string, usageType: string|null, field: string}> $references
     */
    protected function resolveKeywords(
        DataHandlerPayload $payload,
        ResolverContext $context,
        string $ownerTable,
        string $ownerRemoteId,
        string $ownerKey,
        array $references
    ): void {
        foreach ($references as $reference) {
            if (!is_array($reference) || !is_string($reference['id'] ?? null)) {
                continue;
            }

            $uri = $reference['id'];
            $usageType = $reference['usageType'] ?? null;
            $title = $reference['title'] ?? null;
            // The owning entity names its own relation column.
            $targetField = $reference['field'];

            if (is_string($title)) {
                // Free text arrives resolved; there is nothing to fetch, and
                // nothing upstream to translate it with.
                $context->collectKeyword(new CollectedKeyword(
                    $ownerTable,
                    $ownerKey,
                    $targetField,
                    $uri,
                    [$context->language => $title]
                ));
            } else {
                $this->collectKeywordChain(
                    $context,
                    $ownerTable,
                    $ownerKey,
                    $uri,
                    is_string($usageType) ? $usageType : null,
                    [],
                    true,
                    $targetField
                );
            }

            $payload->removeTransient(
                $ownerTable,
                $ownerRemoteId,
                AbstractEntity::KEYWORD_BUCKET,
                $uri
            );
        }
    }

    /**
     * Walk a term and its ancestors upward, collecting each. Terminates on a
     * node without a parent, on a repeat (cycle) or at the depth bound.
     *
     * Skips unusable ancestors.
     *
     * @param list<string> $visited
     */
    protected function collectKeywordChain(
        ResolverContext $context,
        string $ownerTable,
        string $ownerKey,
        string $uri,
        ?string $usageType,
        array $visited,
        bool $isCited = true,
        string $targetField = self::KEYWORD_FIELD
    ): ?string {
        if (in_array($uri, $visited, true) || count($visited) >= ResolverContext::MAX_KEYWORD_DEPTH) {
            return null;
        }
        $visited[] = $uri;

        $term = $this->fetchKeywordTerm($context, $uri, $technicalFailure);
        if ($term === null) {
            if ($technicalFailure === true) {
                $context->keywordFailureByField[
                    $ownerTable . '|' . $ownerKey . '|' . $targetField
                ] = true;
            }

            // Unreachable: nothing to climb from.
            return null;
        }

        if (!$term->isUsable()) {
            // Husk: climb on so the level below adopts a usable ancestor.
            $parentUri = $term->getParentRemoteId();
            if ($parentUri === null) {
                return $usageType === null
                    ? null
                    : $this->collectUsageTypeGroup(
                        $context,
                        $ownerTable,
                        $ownerKey,
                        $usageType,
                        $visited,
                        $targetField
                    );
            }

            return $this->collectKeywordChain(
                $context,
                $ownerTable,
                $ownerKey,
                $parentUri,
                $usageType,
                $visited,
                false,
                $targetField
            );
        }

        $parentRemoteId = null;
        if ($term->getParentRemoteId() !== null) {
            $parentRemoteId = $this->collectKeywordChain(
                $context,
                $ownerTable,
                $ownerKey,
                $term->getParentRemoteId(),
                null,
                $visited,
                false,
                $targetField
            );
        } elseif ($usageType !== null) {
            // Resolving the CURIE is what gives the enum an upstream title.
            $parentRemoteId = $this->collectUsageTypeGroup(
                $context,
                $ownerTable,
                $ownerKey,
                $usageType,
                $visited,
                $targetField
            );
        }

        $context->collectKeyword(new CollectedKeyword(
            $ownerTable,
            $ownerKey,
            $targetField,
            self::keywordRemoteId($uri),
            $this->keywordTitles($context, $term),
            $parentRemoteId,
            $isCited,
        ));

        return self::keywordRemoteId($uri);
    }

    /**
     * Resolve the enum a typed literal was used with (`thuecat:Ambiance`) into
     * its own category, so the literal hangs off a titled group.
     *
     * An unresolvable prefix leaves the literal parentless, placing it under
     * the anchor — better than a title-less group.
     *
     * @param list<string> $visited
     */
    protected function collectUsageTypeGroup(
        ResolverContext $context,
        string $ownerTable,
        string $ownerKey,
        string $usageType,
        array $visited,
        string $targetField = self::KEYWORD_FIELD
    ): ?string {
        $uri = (new CurieExpander())->expand($usageType);
        if ($uri === null) {
            $this->importLogger->recordSkippedReference(
                'sys_category',
                $usageType,
                self::KEYWORD_FIELD,
                $usageType,
                'Keyword usage type uses an unknown CURIE prefix.'
            );

            return null;
        }

        // The enum groups the literal; it is never itself a cited keyword.
        return $this->collectKeywordChain(
            $context,
            $ownerTable,
            $ownerKey,
            $uri,
            null,
            $visited,
            false,
            $targetField
        );
    }

    /**
     * Null only when unreachable. A label-less husk is returned parsed so the
     * walk can read its parent and climb past it; callers check isUsable().
     *
     * $technicalFailure is set when the term could not be fetched for a reason
     * that does not mean upstream withdrew it — see meansKeywordIsGone().
     */
    protected function fetchKeywordTerm(
        ResolverContext $context,
        string $uri,
        ?bool &$technicalFailure = null
    ): ?KeywordTermEntity {
        $technicalFailure = false;

        try {
            $context->reportProgress($uri);
            $response = $this->fetchData->jsonLDFromUrl($uri, $context->apiKey);
        } catch (Exception $e) {
            $technicalFailure = !$this->fetchFailureVerdict->failureMeansGone($e);
            $this->importLogger->recordSkippedReference(
                'sys_category',
                $uri,
                self::KEYWORD_FIELD,
                $uri,
                $e::class . ': ' . $e->getMessage()
            );
            return null;
        }

        // Resource URIs answer with an @graph; ontology terms answer with the
        // bare node.
        $graph = $response['@graph'] ?? null;
        $node = is_array($graph) && isset($graph[0]) && is_array($graph[0]) ? $graph[0] : $response;

        $term = new KeywordTermEntity();
        $term->parse($node, $context->language, $context->parserContext, $context->translationLanguages);

        if (!$term->isUsable()) {
            $this->importLogger->recordSkippedReference(
                'sys_category',
                $uri,
                self::KEYWORD_FIELD,
                $uri,
                'Keyword resource carries no usable label.'
            );
        }

        return $term;
    }

    public static function keywordRemoteId(string $uri): string
    {
        return self::KEYWORD_IDENTIFIER_PREFIX . $uri;
    }

    /**
     * The bare value behind a keyword identifier. Keywords are carried already
     * prefixed, while the provisioner prefixes what it is given.
     */
    protected static function keywordSourceValue(string $remoteId): string
    {
        return str_starts_with($remoteId, self::KEYWORD_IDENTIFIER_PREFIX)
            ? substr($remoteId, strlen(self::KEYWORD_IDENTIFIER_PREFIX))
            : $remoteId;
    }

    protected function keywordAnchor(ResolverContext $context): SysCategoryAnchor
    {
        return new SysCategoryAnchor(
            $context->keywordParentUid,
            $context->keywordStoragePid,
            self::KEYWORD_IDENTIFIER_PREFIX
        );
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

        $sitePageIds = $context->sitePageIds;
        $anchor = new SysCategoryAnchor($parentUid, $categoryPid, self::TYPE_IDENTIFIER_PREFIX);
        // Bound to the context's map so keys survive promoteNewKeys() between
        // rounds, as they did before the provisioner took this over.
        $state = new SysCategoryProvisioningState($context->categoryKeyByRemoteId);
        $index = $this->vocabularyProvider->index($context->apiKey);

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

                    $categoryKey = $this->provisionTypeChain(
                        $payload,
                        $context,
                        $anchor,
                        $state,
                        $index,
                        $category,
                        $sitePageIds,
                        $table,
                        (string)$ownerRemoteId
                    );
                    if ($categoryKey === null) {
                        continue;
                    }

                    // Only the type the payload names becomes a relation; its
                    // ancestors exist to give the tree its levels.
                    $payload->setRelationField($table, $ownerKey, $field, $categoryKey);
                }
            }
        }
    }

    /**
     * Provision the type's whole ancestor chain and answer the key of the type
     * itself, top-down so each level exists before the one hanging beneath it.
     *
     * A type the vocabulary knows nothing about still gets its own category:
     * losing it would take away structure editors already have, and the missing
     * hierarchy is the lesser degradation.
     *
     * @param array{field: string, remoteId: string, title: string} $category
     * @param list<int>                                             $sitePageIds
     */
    protected function provisionTypeChain(
        DataHandlerPayload $payload,
        ResolverContext $context,
        SysCategoryAnchor $anchor,
        SysCategoryProvisioningState $state,
        VocabularyIndex $index,
        array $category,
        array $sitePageIds,
        string $ownerTable,
        string $ownerRemoteId
    ): ?string {
        $sourceValue = self::typeSourceValue($category['remoteId']);
        $strategy = $this->parentStrategies->forTable($ownerTable);
        $chain = $this->chainBuilder->build(
            $index,
            $sourceValue,
            $strategy,
            function (string $class, string $chosen, array $discarded) use ($context, $strategy): void {
                // Once per class per run: the same branch recurs on every record
                // carrying the type, and the report is about the class.
                if (isset($context->reportedBranches[$class])) {
                    return;
                }
                $context->reportedBranches[$class] = true;
                $this->importLogger->recordDiscardedParents(
                    $class,
                    $chosen,
                    $discarded,
                    $strategy->name()
                );
            }
        );
        if ($chain === []) {
            $chain = [$sourceValue];
            if (!isset($context->reportedMissingHierarchy[$sourceValue])) {
                $context->reportedMissingHierarchy[$sourceValue] = true;
                $this->importLogger->recordMissingHierarchy($sourceValue);
            }
        }

        $key = null;
        $parentValue = null;
        foreach ($chain as $class) {
            $key = $this->sysCategoryProvisioner->provision(
                $payload,
                $state,
                $anchor,
                new SysCategoryTerm(
                    $class,
                    $this->typeTitles($context, $index, $class, $category),
                    $parentValue
                ),
                $sitePageIds,
                $context->language,
                $context->translationLanguages
            );
            $parentValue = $class;
        }

        return $key;
    }

    /**
     * Titles for one class of the chain. The mapper's title applies only to the
     * type the record named; an ancestor is titled from upstream, or from the
     * fallback map under its own source value.
     *
     * @param array{field: string, remoteId: string, title: string} $category
     *
     * @return array<string, string>
     */
    protected function typeTitles(
        ResolverContext $context,
        VocabularyIndex $index,
        string $class,
        array $category
    ): array {
        $languages = array_merge([$context->language], array_keys($context->translationLanguages));
        $named = self::typeSourceValue($category['remoteId']);

        $resolution = $this->titleResolver->resolve(
            $class,
            $index->get($class),
            $languages,
            $class === $named ? [$class => $category['title']] : [],
            $context->language
        );

        // Only the type the record named is reported; an ancestor is nobody's
        // to maintain.
        if ($class === $named) {
            $context->fallbackUsedByType[$named] = $resolution->usedFallback;
        }

        return $resolution->titles;
    }

    /**
     * Drop from the report every type upstream titled by itself. What remains
     * is what the fallback map was consulted for — mapped where it answered,
     * unmatched where it did not — which is what someone still maintains.
     */
    protected function amendMatchReports(DataHandlerPayload $payload, ResolverContext $context): void
    {
        if ($context->fallbackUsedByType === []) {
            return;
        }

        $amended = [];
        foreach ($payload->getMatchReports() as $report) {
            $report['matched'] = array_filter(
                $report['matched'],
                static fn (string $value): bool => $context->fallbackUsedByType[$value] ?? true,
                ARRAY_FILTER_USE_KEY
            );
            $amended[] = $report;
        }

        $payload->setMatchReports($amended);
    }

    /** The bare type behind a category identifier. */
    protected static function typeSourceValue(string $remoteId): string
    {
        return str_starts_with($remoteId, self::TYPE_IDENTIFIER_PREFIX)
            ? substr($remoteId, strlen(self::TYPE_IDENTIFIER_PREFIX))
            : $remoteId;
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
        $this->drainTranslationsUsing($payload, $context, $context->remoteIdToKey);
    }

    /**
     * Categories are keyed by their own identifiers rather than by a record's
     * remote_id, so the map to resolve owners through is an argument: the same
     * staging serves both, only the bookkeeping differs.
     *
     * @param array<string, string> $keyByIdentifier
     */
    protected function drainTranslationsUsing(
        DataHandlerPayload $payload,
        ResolverContext $context,
        array $keyByIdentifier
    ): void {
        foreach ($payload->getTranslations() as $table => $rowsByRemoteId) {
            foreach ($rowsByRemoteId as $remoteId => $perLanguage) {
                $ownerKey = $keyByIdentifier[$remoteId] ?? null;
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
                    $uid = $this->findUidByRemoteId($table, $remoteId, $context->sitePageIds);
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
        $this->importInlineMedia($payload, $context);
    }

    protected function isAlreadyRekeyed(string $outerKey): bool
    {
        return ctype_digit($outerKey) || str_starts_with($outerKey, 'NEW');
    }

    /**
     * Write the relation on the field the bucket gives the table the
     * referenced record actually imported into. A record that imported into a
     * table the bucket has no field for is reported: it exists, and the
     * relation to it is what was dropped. A reference with no table at all
     * resolved to no model and was never imported, so there is nothing to
     * report.
     */
    protected function relateOrReport(
        DataHandlerPayload $payload,
        ResolverContext $context,
        string $ownerTable,
        string $ownerKey,
        string $ownerRemoteId,
        string $bucket,
        string $reference,
        string|int $value
    ): void {
        $actualTable = $context->remoteIdToTable[$reference] ?? '';

        $field = self::BUCKET_MAP[$bucket][$actualTable] ?? null;
        if ($field !== null) {
            $payload->setRelationField($ownerTable, $ownerKey, $field, $value);
            return;
        }

        if ($actualTable === '') {
            return;
        }

        $this->importLogger->recordUnrelatableReference(
            $ownerTable,
            $ownerRemoteId,
            $this->defaultFieldFor($bucket),
            $reference,
            $this->defaultTableFor($bucket),
            $actualTable
        );
    }

    /**
     * A bucket's first target table, used while a reference's own table is
     * still unknown and as the "expected" table in the unrelatable report.
     */
    protected function defaultTableFor(string $bucket): string
    {
        return (string)(array_key_first(self::BUCKET_MAP[$bucket] ?? []) ?? '');
    }

    protected function defaultFieldFor(string $bucket): string
    {
        $fields = array_values(self::BUCKET_MAP[$bucket] ?? []);
        return (string)($fields[0] ?? '');
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

                        if ($bucket === 'media') {
                            // Exempt from MAX_FETCH_DEPTH: an asset fetch stages
                            // no rows and follows no references, so it cannot
                            // fan out the way cross-referenced POIs do. Under
                            // the cap a record discovered through a relation
                            // would silently lose its own images.
                            // Emptying the bucket is what makes the drain loop
                            // terminate. Without folders there is nowhere to put
                            // an asset, so the bucket is dropped rather than
                            // drained.
                            if ($context->skipMedia
                                || $context->targetFolder === null
                                || $context->stagingFolder === null
                            ) {
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
                                // @phpstan-ignore argument.type (bucket name implies the shape)
                                $references
                            );
                            $progress = true;
                            continue;
                        }

                        if ($bucket === 'accessibilitySpecification') {
                            // Exempt from MAX_FETCH_DEPTH: shaping the blob
                            // stages no rows and follows no references, so it
                            // cannot fan out the way cross-referenced POIs do.
                            // Under the cap a record discovered through a
                            // relation would silently lose its specification.
                            $this->shapeAccessibilityBlob(
                                $payload,
                                $context,
                                $ownerTable,
                                $ownerRemoteId,
                                $ownerKey,
                                // @phpstan-ignore argument.type (bucket name implies the shape)
                                $references
                            );
                            $progress = true;
                            continue;
                        }

                        if ($bucket === AbstractEntity::KEYWORD_BUCKET) {
                            // Exempt from MAX_FETCH_DEPTH: terms sit at depth 1
                            // and their sets at depth 2, so the generic cap
                            // would drop every intermediate category. Bounded
                            // instead by its own depth limit and cycle guard.
                            $this->resolveKeywords(
                                $payload,
                                $context,
                                $ownerTable,
                                $ownerRemoteId,
                                $ownerKey,
                                // @phpstan-ignore argument.type (bucket name implies the shape)
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
                                $this->relateOrReport(
                                    $payload,
                                    $context,
                                    $ownerTable,
                                    $ownerKey,
                                    $ownerRemoteId,
                                    $bucket,
                                    $reference,
                                    $remoteIdToKey[$reference]
                                );
                                $payload->removeTransient($ownerTable, $ownerRemoteId, $bucket, $reference);
                                $progress = true;
                                continue;
                            }

                            [$foundTable, $uid] = $this->findExistingRecord(
                                $bucket,
                                $reference,
                                $context->sitePageIds
                            );
                            if ($uid > 0) {
                                $foundField = self::BUCKET_MAP[$bucket][$foundTable] ?? $this->defaultFieldFor($bucket);
                                $payload->setRelationField($ownerTable, $ownerKey, $foundField, $uid);
                                $remoteIdToKey[$reference] = (string)$uid;
                                $context->remoteIdToTable[$reference] = $foundTable;
                                $context->markFound($reference);

                                if (($context->depthByRemoteId[$ownerRemoteId] ?? 0) >= ResolverContext::MAX_FETCH_DEPTH) {
                                    $payload->removeTransient($ownerTable, $ownerRemoteId, $bucket, $reference);
                                    $progress = true;
                                    continue;
                                }

                                $this->resolveReferenceOrSkip(
                                    $payload,
                                    $context,
                                    $ownerTable,
                                    $ownerKey,
                                    $ownerRemoteId,
                                    $bucket,
                                    $reference,
                                    $remoteIdToKey
                                );
                                $payload->removeTransient($ownerTable, $ownerRemoteId, $bucket, $reference);
                                $progress = true;
                                continue;
                            }

                            if (isset($remoteIdToKey[$reference])) {
                                $this->relateOrReport(
                                    $payload,
                                    $context,
                                    $ownerTable,
                                    $ownerKey,
                                    $ownerRemoteId,
                                    $bucket,
                                    $reference,
                                    $remoteIdToKey[$reference]
                                );
                                $payload->removeTransient($ownerTable, $ownerRemoteId, $bucket, $reference);
                                $progress = true;
                                continue;
                            }

                            if (($context->depthByRemoteId[$ownerRemoteId] ?? 0) >= ResolverContext::MAX_FETCH_DEPTH) {
                                $payload->removeTransient($ownerTable, $ownerRemoteId, $bucket, $reference);
                                $progress = true;
                                continue;
                            }

                            $this->resolveReferenceOrSkip(
                                $payload,
                                $context,
                                $ownerTable,
                                $ownerKey,
                                $ownerRemoteId,
                                $bucket,
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
     * Fetch + parse a transient reference and merge it into the payload, or
     * skip the reference if any of that fails. Catches Exception, not
     * Throwable: an Error is our own defect and must still fail the run.
     * The caller drains the transient either way.
     *
     * @param array<string, string> $remoteIdToKey
     */
    protected function resolveReferenceOrSkip(
        DataHandlerPayload $payload,
        ResolverContext $context,
        string $ownerTable,
        string $ownerKey,
        string $ownerRemoteId,
        string $bucket,
        string $reference,
        array &$remoteIdToKey
    ): void {
        if ($context->hasReferenceFailed($reference)) {
            $this->importLogger->recordSkippedReference(
                $ownerTable,
                $ownerRemoteId,
                $this->defaultFieldFor($bucket),
                $reference,
                (string)$context->getReferenceFailureReason($reference)
            );
            return;
        }

        try {
            $context->reportProgress($reference);
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

            $this->relateOrReport(
                $payload,
                $context,
                $ownerTable,
                $ownerKey,
                $ownerRemoteId,
                $bucket,
                $reference,
                $remoteIdToKey[$reference] ?? ''
            );
        } catch (Exception $e) {
            $reason = $e::class . ': ' . $e->getMessage();
            $context->markReferenceFailed($reference, $reason);
            $this->importLogger->recordSkippedReference(
                $ownerTable,
                $ownerRemoteId,
                $this->defaultFieldFor($bucket),
                $reference,
                $reason
            );
        }
    }

    /**
     * Download each media entry's file and stage a sys_file_reference on the
     * owner, in the field that owner declares for the entry's slot. Video is
     * skipped (no file).
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

        foreach ($entries as $entry) {
            // An entry we cannot interpret costs that entry only: upstream
            // authoring is data drift, like an image that will not download.
            if (!is_array($entry)) {
                $this->importLogger->recordSkippedReference(
                    $ownerTable,
                    $ownerRemoteId,
                    '',
                    $entry,
                    'Media entry is not a {kind,id} tuple.'
                );
                continue;
            }

            $reference = $entry['id'];
            if (!$this->isFetchableUrl($reference)) {
                $this->importLogger->recordSkippedReference(
                    $ownerTable,
                    $ownerRemoteId,
                    $this->mediaFieldMap->fieldFor($ownerTable, $entry['kind']),
                    $reference,
                    'Media reference is neither a fetchable URL nor an inline node.'
                );
                continue;
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

            // Derived before the download so the skip below can name the field.
            $targetField = $this->mediaFieldMap->fieldFor($ownerTable, $entry['kind']);

            $this->downloadAndCollect(
                $context,
                $target,
                $staging,
                $mediaEntity,
                $downloadUrl,
                $ownerTable,
                $ownerRemoteId,
                $ownerKey,
                $targetField,
            );
        }

        // Skipped entries reach here too, so both shapes must drain — a
        // survivor would spin drainTransients() forever.
        foreach ($entries as $entry) {
            $payload->removeTransient(
                $ownerTable,
                $ownerRemoteId,
                'media',
                is_array($entry) ? $entry['id'] : $entry
            );
        }
    }

    /**
     * Runs after the last root: an asset shared across roots must not be
     * reaped by one and recreated by the next. Entries keep collection order;
     * schema:position is not consulted.
     */
    public function flushCollectedMedia(DataHandlerPayload $payload, ResolverContext $context): void
    {
        $entriesByOwnerField = [];
        foreach ($context->collectedMedia as $media) {
            $entriesByOwnerField[$media->ownerTable][$media->ownerKey][$media->targetField][] = $media;
        }

        foreach ($entriesByOwnerField as $ownerTable => $byOwnerKey) {
            foreach ($byOwnerKey as $ownerKey => $byField) {
                $ownerKey = (string)$ownerKey;
                // A NEW… owner has never been stored: nothing to reuse.
                $isStored = MathUtility::canBeInterpretedAsInteger($ownerKey);

                foreach ($byField as $targetField => $entries) {
                    $existing = $isStored
                        ? $this->findExistingReferences($ownerTable, (int)$ownerKey, (string)$targetField)
                        : [];

                    foreach ($entries as $media) {
                        $this->stageFileReference(
                            $payload,
                            $ownerTable,
                            $ownerKey,
                            (string)$targetField,
                            $media,
                            $context->storagePid,
                            $existing[$media->fileUid] ?? null,
                        );
                        unset($existing[$media->fileUid]);
                    }

                    // A technical failure cannot be told from a removal, so keep.
                    if (isset($context->mediaFailureByField[$ownerTable . '|' . $ownerKey . '|' . $targetField])) {
                        continue;
                    }

                    // A type:file relation appends, so unsubmitted references
                    // survive unless deleted explicitly.
                    foreach ($existing as $staleRefUid) {
                        $payload->addCmdMap('sys_file_reference', (string)$staleRefUid, 'delete', 1);
                    }
                }
            }
        }
    }

    /** Reusing the stored uid keeps a re-import from stacking a second row. */
    protected function stageFileReference(
        DataHandlerPayload $payload,
        string $ownerTable,
        string $ownerKey,
        string $targetField,
        CollectedMedia $media,
        int $pid,
        ?int $existingRefUid = null
    ): void {
        $referenceKey = $existingRefUid !== null
            ? (string)$existingRefUid
            : StringUtility::getUniqueId('NEW');
        $payload->addRow('sys_file_reference', $referenceKey, [
            'pid' => $pid,
            'uid_local' => $media->fileUid,
            'title' => $media->title,
            'description' => $media->description,
        ]);
        $payload->setRelationField($ownerTable, $ownerKey, $targetField, $referenceKey);
    }

    /** Shared by both media shapes; the URL cache is what removes repeat downloads. */
    protected function downloadAndCollect(
        ResolverContext $context,
        Folder $target,
        Folder $staging,
        MediaEntity $mediaEntity,
        string $downloadUrl,
        string $ownerTable,
        string $ownerRemoteId,
        string $ownerKey,
        string $targetField,
    ): void {
        $fileUid = $context->fileUidByDownloadUrl[$downloadUrl] ?? null;

        if ($fileUid === null) {
            if (isset($context->failedReferenceUrls[$downloadUrl])) {
                // Marked from the first attempt's verdict, since this owner
                // never retried. Unclassified = not a media download, so keep.
                if (($context->assetGoneByUrl[$downloadUrl] ?? false) !== true) {
                    $context->mediaFailureByField[$ownerTable . '|' . $ownerKey . '|' . $targetField] = true;
                }
                $this->importLogger->recordSkippedReference(
                    $ownerTable,
                    $ownerRemoteId,
                    $targetField,
                    $downloadUrl,
                    $context->failedReferenceUrls[$downloadUrl]
                );
                return;
            }

            $context->reportProgress($downloadUrl);
            $failureDetail = null;
            $failureStatus = null;
            $file = $this->mediaFileDownloader->download(
                $target,
                $staging,
                $downloadUrl,
                (string)$context->apiKey,
                $context->parserContext->apiDomain,
                $failureDetail,
                $failureStatus,
            );
            if ($file === null) {
                $assetIsGone = $this->meansAssetIsGone($failureStatus);
                $context->assetGoneByUrl[$downloadUrl] = $assetIsGone;
                if (!$assetIsGone) {
                    $context->mediaFailureByField[$ownerTable . '|' . $ownerKey . '|' . $targetField] = true;
                }
                $reason = $this->downloadFailureReason($failureDetail);
                $context->markReferenceFailed($downloadUrl, $reason);
                $this->importLogger->recordSkippedReference(
                    $ownerTable,
                    $ownerRemoteId,
                    $targetField,
                    $downloadUrl,
                    $reason
                );
                return;
            }

            // Several owners may claim one file; the last title written wins.
            $file->getMetaData()->add(array_filter([
                'title' => $mediaEntity->getOriginalFileName(),
                'description' => $mediaEntity->getDescription(),
                'creator' => $mediaEntity->getAuthor(),
                'copyright' => $mediaEntity->getCopyright(),
            ], static fn (string $value): bool => $value !== ''));

            $fileUid = $file->getUid();
            $context->fileUidByDownloadUrl[$downloadUrl] = $fileUid;
        }

        $context->collectMedia(new CollectedMedia(
            $ownerTable,
            $ownerKey,
            $targetField,
            $fileUid,
            $mediaEntity->getOriginalFileName(),
            $mediaEntity->getDescription(),
        ));
    }

    /**
     * Download inline media and relate it to its owner. Runs where the owner
     * row is already in hand: an inline node carries its own data, so it needs
     * no fetch and must not go through the drain loop.
     */
    protected function importInlineMedia(DataHandlerPayload $payload, ResolverContext $context): void
    {
        if ($context->skipMedia) {
            foreach ($payload->getInlineMedia() as $ownerTable => $rowsByRemoteId) {
                foreach (array_keys($rowsByRemoteId) as $ownerRemoteId) {
                    $payload->clearInlineMedia($ownerTable, (string)$ownerRemoteId);
                }
            }
            return;
        }

        $target = $context->targetFolder;
        $staging = $context->stagingFolder;
        if ($target === null || $staging === null) {
            return;
        }

        foreach ($payload->getInlineMedia() as $ownerTable => $rowsByRemoteId) {
            foreach ($rowsByRemoteId as $ownerRemoteId => $entries) {
                $ownerKey = $context->remoteIdToKey[$ownerRemoteId] ?? null;
                if ($ownerKey === null) {
                    continue;
                }

                foreach ($entries as $entry) {
                    if ($entry['kind'] === 'video') {
                        continue;
                    }

                    $mediaEntity = new MediaEntity();
                    $mediaEntity->configure($entry['node'], $entry['kind'], $context->language);

                    $downloadUrl = $mediaEntity->getUrl();
                    if ($downloadUrl === '') {
                        continue;
                    }

                    // The node's @id is a per-response blank-node label, so the
                    // download URL is the stable identity.
                    $this->downloadAndCollect(
                        $context,
                        $target,
                        $staging,
                        $mediaEntity,
                        $downloadUrl,
                        $ownerTable,
                        (string)$ownerRemoteId,
                        $ownerKey,
                        $this->mediaFieldMap->fieldFor($ownerTable, $entry['kind']),
                    );
                }

                $payload->clearInlineMedia($ownerTable, $ownerRemoteId);
            }
        }
    }

    protected function wireInlineChildrenToParents(
        DataHandlerPayload $payload,
        ResolverContext $context
    ): void {
        foreach (self::INLINE_CHILD_PARENTS as $childTable => $config) {
            // Survivor set the reaping below is judged against.
            $stagedByParent = [];

            foreach ($payload->getDataMap()[$childTable] ?? [] as $childKey => $childRow) {
                $childKey = (string)$childKey;
                $childRemoteId = (string)($childRow['remote_id'] ?? '');
                $parentRemoteId = explode($config['separator'], $childRemoteId, 2)[0];

                $parentKey = $context->remoteIdToKey[$parentRemoteId] ?? null;
                $parentTable = $context->remoteIdToTable[$parentRemoteId] ?? null;
                if ($parentKey === null || $parentTable === null) {
                    continue;
                }

                // One target field, or one chosen by a column on the child row.
                $inlineField = $config['field'] ?? null;
                if ($inlineField === null) {
                    $columnValue = (string)($childRow[$config['column'] ?? ''] ?? '');
                    $inlineField = $config['fieldByValue'][$columnValue] ?? null;
                }
                if ($inlineField === null) {
                    continue;
                }

                $stagedByParent[$parentTable][$parentKey][$childRemoteId] = true;
                $payload->setRelationField($parentTable, $parentKey, $inlineField, $childKey);
            }

            if (($config['reapOrphans'] ?? false) === true) {
                $this->reapOrphanedInlineChildren($payload, $childTable, $stagedByParent);
            }
        }
    }

    /**
     * Delete stored children the upstream record no longer carries.
     *
     * Judged only for parents whose payload carried children this round: a
     * round staging nothing for a parent says nothing about upstream, and
     * later rounds of the importer loop fill only translation rows.
     *
     * Child nodes ride inside the parent's payload rather than being fetched
     * separately, so within such a round an absent child means upstream
     * dropped it and no technical-failure guard is needed.
     *
     * @param array<string, array<string, array<string, true>>> $stagedByParent parentTable => parentKey => remoteId => true
     */
    protected function reapOrphanedInlineChildren(
        DataHandlerPayload $payload,
        string $childTable,
        array $stagedByParent
    ): void {
        foreach ($stagedByParent as $parentTable => $byParentKey) {
            foreach ($byParentKey as $parentKey => $survivors) {
                $parentKey = (string)$parentKey;
                // A NEW… parent has no stored children to reap.
                if (!MathUtility::canBeInterpretedAsInteger($parentKey)) {
                    continue;
                }

                foreach ($this->findStoredInlineChildren($childTable, $parentTable, (int)$parentKey) as $uid => $remoteId) {
                    if (isset($survivors[$remoteId])) {
                        continue;
                    }
                    $payload->addCmdMap($childTable, (string)$uid, 'delete', 1);
                }
            }
        }
    }

    /**
     * Stored children of one parent row, as uid => remote_id. Translations are
     * excluded: they follow their default-language row and are removed with it.
     *
     * @return array<int, string>
     */
    protected function findStoredInlineChildren(string $childTable, string $parentTable, int $parentUid): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($childTable);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction())
        ;
        $queryBuilder->select('uid', 'remote_id')
            ->from($childTable)
            ->where(
                $queryBuilder->expr()->eq('parentid', $queryBuilder->createNamedParameter($parentUid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('parenttable', $queryBuilder->createNamedParameter($parentTable))
            )
        ;

        $language = $this->languageCapabilityFor($childTable);
        if ($language !== null) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq(
                $language['languageField'],
                $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
            ));
        }

        $rows = $queryBuilder->executeQuery()->fetchAllAssociative();

        $children = [];
        foreach ($rows as $row) {
            $uid = $row['uid'] ?? null;
            $remoteId = $row['remote_id'] ?? null;
            if (!is_numeric($uid) || !is_string($remoteId)) {
                continue;
            }
            $children[(int)$uid] = $remoteId;
        }

        return $children;
    }

    /**
     * Only "the asset is gone" may cost a stored reference. 401/403 and 429
     * arrive for every asset on a host at once, so treating them as removals
     * would wipe one provider's media on a single fault.
     */
    protected function meansAssetIsGone(?int $failureStatus): bool
    {
        return $this->fetchFailureVerdict->statusMeansGone($failureStatus);
    }

    // Appends only what the downloader could add, so a plain rejection keeps
    // the message it has always had.
    protected function downloadFailureReason(?string $failureDetail): string
    {
        if ($failureDetail === null || $failureDetail === '') {
            return 'Image could not be downloaded.';
        }

        return sprintf('Image could not be downloaded, %s.', $failureDetail);
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
        $context->reportProgress($url);
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

        throw new MalformedGraphException(
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
     * Locate an already-imported record for a reference by probing the
     * bucket's target tables in map order, taking the first hit — remote_id is
     * unique per record, so one hit settles the question and the order only
     * decides how many queries run before it. Most buckets have one table.
     *
     * @param list<int> $sitePageIds
     *
     * @return array{0: string, 1: int} table the record was found in, and its
     *         uid; uid 0 means no table holds it
     */
    protected function findExistingRecord(
        string $bucket,
        string $reference,
        array $sitePageIds
    ): array {
        foreach (array_keys(self::BUCKET_MAP[$bucket] ?? []) as $candidate) {
            $uid = $this->findUidByRemoteId($candidate, $reference, $sitePageIds);
            if ($uid > 0) {
                return [$candidate, $uid];
            }
        }

        return [$this->defaultTableFor($bucket), 0];
    }

    /**
     * Look up the default-language row for a given remote_id, within the
     * importing site.
     *
     * @param list<int> $sitePageIds Pages of the importing site; empty means
     *        no scope is known and the match stays instance-wide.
     */
    protected function findUidByRemoteId(string $table, string $remoteId, array $sitePageIds): int
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

        // A record of another site is not ours to read or update; treated as
        // absent so the import creates its own within its own site. Empty
        // means no scope is known (a context built outside the Importer), and
        // the lookup stays instance-wide as it was before scoping.
        if ($sitePageIds !== []) {
            $queryBuilder->andWhere($queryBuilder->expr()->in(
                'pid',
                $queryBuilder->createNamedParameter($sitePageIds, Connection::PARAM_INT_ARRAY)
            ));
        }

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
