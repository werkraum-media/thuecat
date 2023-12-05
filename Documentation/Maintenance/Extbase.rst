.. _maintenanceExtbase:

Extbase
=======

PHPDoc Blocks with type hints mentioning `Necessary for Extbase/Symfony.`
-------------------------------------------------------------------------

Those are necessary (at least with TYPO3 v12) because of Extbase and the underlying
Symfony component.

Extbase uses the PHPDocExtractor first, before using the `ReflectionExtractor`, both part of Symfony property-info package.
The `ReflectionExtractor` will check the mutator followed by accessors prior checking the property itself.
Some of our properties have different return values by accessors than the stored value that is set to the property.
We therefore need to keep the PHPDoc block as this is checked first.
