.. include:: ../Includes.txt
.. _developers-backend-relation-scoping:

========================
Backend relation scoping
========================

What the scoping does for editors is described in :ref:`backend-relation-scoping`. This page covers
how it is wired and what to decide when a record kind is added.

To achieve a seamless integration, TCA and Flexform are manipulated after being compiled and the
result is then cached. PSR-14 events take care of the manipulation. The final result can be
inspected via the :guilabel:`Sites -> Page TSconfig` and :guilabel:`Configuration -> TCA` backend
modules.

.. _developers-backend-relation-scoping-criterion:

The criterion
=============

:php:`WerkraumMedia\ThueCat\Service\SiteScopedSelectFields` decides which fields are scoped and what
clause they carry. A column matches when it is a :php:`select` field, its :php:`foreign_table` is
listed in :php:`SiteScopedSelectFields::SCOPED_TABLES`, and it is not the table's translation parent
(``ctrl.transOrigPointerField``).

A matching column receives two conditions on the foreign table:

.. code-block:: sql

   AND {#<foreign_table>}.{#pid} IN (###PAGE_TSCONFIG_IDLIST###)
   AND {#<foreign_table>}.{#sys_language_uid} IN (0, -1)

Each condition is appended to the column's existing :php:`foreign_table_where` only when that clause
does not already state it, compared with whitespace removed. Whatever else the clause holds stays
untouched.

.. _developers-backend-relation-scoping-listeners:

Event listeners
===============

Three PSR-14 listeners apply the criterion. Each one derives the fields from it rather than from a
list, so the fields carrying the clause and the fields receiving an id list cannot drift apart.

:php:`SiteScopedRelationsTcaListener` on :php:`AfterTcaCompilationEvent`
   Writes the clause into every matching column of every table in the compiled TCA.

:php:`SiteScopedRelationsFlexFormListener` on :php:`AfterFlexFormDataStructureParsedEvent`
   Writes the clause into matching fields of each parsed FlexForm sheet, so a content element
   selecting ThueCat records is scoped too. A sheet has no ``ctrl``, so no field there is excluded
   as a translation parent.

:php:`SiteScopedRelationsPageTsConfigListener` on :php:`ModifyLoadedPageTsConfigEvent`
   Supplies the value of ``###PAGE_TSCONFIG_IDLIST###``. Core resolves the marker per table and
   field and offers no wildcard, so the listener emits one line per scoped field:

   .. code-block:: typoscript

      TCEFORM.<table>.<field>.PAGE_TSCONFIG_IDLIST = <ids>
      TCEFORM.<table>.<flexField>.<recordType>.<sheet>.<field>.PAGE_TSCONFIG_IDLIST = <ids>

   FlexForm field names containing a dot, such as :typoscript:`settings.towns`, are escaped, because
   TSconfig reads the dot as a path separator. The FlexForm data structure is parsed per record
   type; a type whose structure cannot be resolved contributes no lines.

   The ids are the pages of the site holding the current page, the deepest entry of the rootline,
   resolved by :php:`WerkraumMedia\ThueCat\Service\SitePageIds` — the service the import uses for
   the same question. A page outside any site yields ``0``: an unresolved marker would leave the
   clause offering the whole table.

The type-ahead wizard is limited by :typoscript:`TCEFORM.suggest.default.addWhere` in
:file:`Configuration/page.tsconfig`, reading the same marker. Its columns are unqualified, because
the wizard queries one table at a time, while the dropdown's query joins and needs the table name.

Core's :php:`SuggestWizardController` ignores a field's :php:`foreign_table_where` while TSconfig
sets an ``addWhere``. The language condition is therefore part of the TSconfig block as well: fields
carrying it only in their own :php:`foreign_table_where` would lose it on the wizard while keeping
it on the dropdown.

Category and keyword fields are not part of this mechanism. Their tree start is resolved by the form
data provider :php:`WerkraumMedia\ThueCat\Typo3\FormDataProvider\AnchorStartingPoints` from the
``###THUECAT_ANCHOR###`` marker, see :ref:`frontend-output-plugin-settings-trees`.

.. _developers-backend-relation-scoping-new-record-kind:

Adding a record kind
====================

:php:`SCOPED_TABLES` is the statement of what counts as a ThueCat record, and it is maintained by
hand.

**When a new record kind is introduced, decide whether its table belongs in that list.** Nothing
detects the omission: a relation field pointing at a table missing from it keeps working and offers
the records of the whole installation, in every site, on both the dropdown and the suggest wizard.

Adding the table is the usual answer, but not automatic. A table deliberately shared across sites,
or one never used as a relation target, is correctly left out.

Append :php:`WerkraumMedia\ThueCat\Service\SiteScopedSelectFields::SCOPED_TABLES` with the new
table, this is all it takes.