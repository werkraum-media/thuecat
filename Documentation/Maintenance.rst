.. _maintenance:

Maintenance
===========

List of changes that need to be done for maintenance reasons.
Those affect the extension itself, not users of the extension.

E.g. changes once we drop a certain TYPO3 version.
We might have new code backported for compatibility in older TYPO3 versions.
Those changes are documented so we know what to do once we drop an older version.

TCA `searchFields`
------------------

Drop TCA `ctrl/searchFields`. Those are kept for TYPO3 v13 backwards compatibility
and can be dropped once we drop v13 support.

PHPDoc Blocks with type hints mentioning `Necessary for Extbase/Symfony.`
-------------------------------------------------------------------------

Those are necessary (at least with TYPO3 v12) because of Extbase and the underlying
Symfony component.

Extbase uses the PHPDocExtractor first, before using the `ReflectionExtractor`, both part of Symfony property-info package.
The `ReflectionExtractor` will check the mutator followed by accessors prior checking the property itself.
Some of our properties have different return values by accessors than the stored value that is set to the property.
We therefore need to keep the PHPDoc block as this is checked first.
