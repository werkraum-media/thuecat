# ThüCAT integration into TYPO3 CMS

ThüCAT is the "Thüringer Content Architektur Tourismus", the central database for touristic
data in Thuringia. This TYPO3 extension imports that data via the ThüCAT API into a TYPO3
instance, so it can be maintained in ThüCAT and presented on your own site.

Imported are tourist attractions, trails and the places and organisations around them. Together
with [EXT:events](https://packagist.org/packages/werkraummedia/events), a required dependency,
events and their dates are imported as well. The
[overview](https://docs.typo3.org/p/werkraummedia/thuecat/main/en-us/Features.html) lists
everything that is supported.

The extension respects the TYPO3 site: each site imports and shows only its own data, in the
languages it defines, in the frontend as well as in the backend.

## Requirements

* TYPO3 13.4 LTS or 14
* An API key for ThüCAT

## Getting started

1. Install the extension: `composer require werkraummedia/thuecat`
2. Enter the API key in the Extension Configuration.
3. Add the site set `werkraummedia/thuecat-import` to the `dependencies` of your site.
4. Create an import configuration record and run it:
   `vendor/bin/typo3 thuecat:importviaconfiguration <uid>`,
   on the command line or as a scheduler task.
5. Provide frontend output. The extension ships Extbase plugin configuration and example templates, but no
   ready-made content elements: the output is meant to be fitted to your site.

Every import writes a log that can be inspected in the backend module.

## Documentation

The full manual is available at
https://docs.typo3.org/p/werkraummedia/thuecat/main/en-us/.

* [For integrators](https://docs.typo3.org/p/werkraummedia/thuecat/main/en-us/Integration/Index.html):
  installation, configuration, import and frontend output.
* [For developers](https://docs.typo3.org/p/werkraummedia/thuecat/main/en-us/Developers/Index.html):
  extension points such as filter fields and backend relation scoping.
* [Importer](https://docs.typo3.org/p/werkraummedia/thuecat/main/en-us/Importer/Index.html):
  architecture of the import, category-based values and tuning.
* [Changelog](https://docs.typo3.org/p/werkraummedia/thuecat/main/en-us/Changelog.html)

The sources of the manual live in [`Documentation/`](Documentation/).

## Contributing

Issues and pull requests are welcome at
[Forgejo](https://forgejo.werkraum-media.de/typo3/thuecat).