# ThüCAT integration into TYPO3 CMS

ThüCAT is ¨Thüringer Content Architektur Tourismus¨.
This is an extension for TYPO3 CMS (https://typo3.org/) to integrate ThüCAT.
The existing API is integrated and allows importing data into the system.

Full documentation available at https://docs.typo3.org/p/werkraummedia/thuecat/master/en-us/.

## Current state

The extension already allows:

* Create configuration to import:

  * specified resources via static configuration,
    e.g. defined organisation or towns.

  * sync scope, a syncScopeId to always update delivered resources.

* Support multiple languages

* Import of the following types (not all properties are supported):

  * Organisations (responsible for content)

  * Towns

  * Tourist information

  * Tourist attraction

* Backend module:

  * To inspect current existing organisations
    and their towns and tourist information

  * To inspect import configuration

  * To create import configuration

  * To inspect past imports and possible errors,
    as well as number of affected records

* Frontend:

    * Content element to display tourist attraction

    * Page Type to reference tourist attraction

    * Descriptions are formatted HTML, use `f:format.html` ViewHelper for rendering.

## Short time goals

* Content element to display town, tourist information and organisation.

* Extending import to include further properties

## Installation

Please configure API Key via Extension Configuration.

Configuration records need to be created, e.g. by visiting the ThüCAT module.
Those can then be imported via the same module.
