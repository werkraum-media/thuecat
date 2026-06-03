<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class TouristAttractionDemandFactory
{
    /**
     * Single source of truth for editor settings → demand: each property the
     * editor configured is applied AND named, so the form can hide it.
     *
     * @param array<mixed> $settings
     */
    public function fromSettings(array $settings): EditorFilter
    {
        $demand = new TouristAttractionDemand();
        $locked = [];

        if (!empty($settings['towns']) && is_string($settings['towns'])) {
            $demand->setTowns(GeneralUtility::intExplode(',', $settings['towns'], true));
            $locked[] = 'towns';
        }
        if (!empty($settings['petsAllowed'])) {
            $demand->setPetsAllowed(true);
            $locked[] = 'petsAllowed';
        }
        if (!empty($settings['isAccessibleForFree'])) {
            $demand->setIsAccessibleForFree(true);
            $locked[] = 'isAccessibleForFree';
        }
        if (!empty($settings['publicAccess'])) {
            $demand->setPublicAccess(true);
            $locked[] = 'publicAccess';
        }

        return new EditorFilter($demand, $locked);
    }

    /**
     * Force the editor-locked values from $filter onto $demand so a visitor
     * search refines within the editor's set but can never widen past it.
     */
    public function applyEditorFilter(TouristAttractionDemand $demand, EditorFilter $filter): TouristAttractionDemand
    {
        $locked = $filter->getDemand();

        if ($filter->isLocked('towns')) {
            $demand->setTowns($locked->getTowns());
        }
        if ($filter->isLocked('petsAllowed')) {
            $demand->setPetsAllowed($locked->getPetsAllowed());
        }
        if ($filter->isLocked('isAccessibleForFree')) {
            $demand->setIsAccessibleForFree($locked->getIsAccessibleForFree());
        }
        if ($filter->isLocked('publicAccess')) {
            $demand->setPublicAccess($locked->getPublicAccess());
        }

        return $demand;
    }
}
