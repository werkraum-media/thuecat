.. include:: ../Includes.txt
.. _developers-filter-fields:

=============
Filter fields
=============

The search-and-filter form of tourist attractions (see :ref:`frontend-output`) is built from filter
fields. This page covers how to add one and how the form resolves its context.

.. _frontend-output-filter-fields:

Registering a filter field
==========================

Which filters the search mask offers is not a list in the controller. A filter field is a tagged
service implementing :php:`WerkraumMedia\ThueCat\Service\FilterField\FilterFieldDefinition`, tagged
``search.filter.field``. :php:`WerkraumMedia\ThueCat\Service\SearchFilterOptionsService` discovers
every implementation through that tag and builds it. Another extension adds a filter without editing
any class here.

Two storage shapes are supported, each with an abstract base to extend. Pick the one matching where
the values live. Both live in :file:`Classes/Service/FilterField/`; the examples are the shipped
fields, in the same directory.

Values in a comma-separated uid column on the record itself, i.e. a TCA select without ``MM`` — base
:php:`WerkraumMedia\ThueCat\Service\FilterField\CommaColumnField`:

.. code-block:: php

   <?php

   namespace Vendor\Extension\Service\FilterField;

   use WerkraumMedia\ThueCat\Service\FilterField\CommaColumnField;

   final class TownFilterField extends CommaColumnField
   {
       public function __construct()
       {
           parent::__construct(
               name: 'towns',
               recordColumn: 'town',
               optionTable: 'tx_thuecat_town',
           );
       }
   }

Values in an MM table over a parent/child option table, offered as the tree below a configured
anchor — base :php:`WerkraumMedia\ThueCat\Service\FilterField\HierarchicalMmField`:

.. code-block:: php

   <?php

   namespace Vendor\Extension\Service\FilterField;

   use WerkraumMedia\ThueCat\Import\Settings\CategoryAnchorSetting;
   use WerkraumMedia\ThueCat\Service\FilterField\HierarchicalMmField;

   final class CategoryFilterField extends HierarchicalMmField
   {
       public function __construct()
       {
           parent::__construct(
               name: 'categories',
               mmTable: 'sys_category_record_mm',
               mmFieldName: 'categories',
               optionTable: 'sys_category',
               parentColumn: 'parent',
               anchorSetting: CategoryAnchorSetting::CategoryParent,
           );
       }
   }

With ``autoconfigure`` enabled the tag is applied by the interface, so no entry in
:file:`Services.yaml` is needed.

What each argument means
------------------------

``name``
   The demand property and the view key. The template binds the option set under this name, so a
   field named ``towns`` is rendered from :fluid:`{towns}`. It is not the MM field name: the two
   happen to coincide today but answer to different owners.

``recordColumn``
   The column holding the comma-separated uids. A record without a value carries the placeholder
   ``0``, which is never offered as an option.

``mmFieldName``
   Which field of a shared MM table the relation belongs to. Categories and keywords share
   :sql:`sys_category_record_mm` and are told apart by this alone, so it is part of every query.

``anchorSetting``
   A case of :php:`WerkraumMedia\ThueCat\Import\Settings\CategoryAnchorSetting`, naming the site
   setting that holds the category the offered tree starts below. The declaration carries the
   *setting*, never a resolved uid — anchors are per site and are resolved per request. An
   unconfigured anchor offers nothing.

The record table is deliberately absent. It belongs to the scope, not to the field, so one field
definition serves every record kind that offers it.

What a field does not decide
----------------------------

A field declares where its values are read from and nothing else. Scoping is resolved once per
request and applied to every field alike:

* Only values carried by records the sibling list can return are offered. With no list on the page,
  each field offers what its storage holds.
* Values are bound to the site. A value living outside the list's storage pages is still offered;
  one belonging to another site is not.
* Option sets never depend on the visitor's current selection. Choosing one town does not remove the
  others from the form.

Adding a further storage shape
------------------------------

If neither base fits, the shape needs its own provider: implement
:php:`WerkraumMedia\ThueCat\Service\FilterField\OptionProvider\FilterOptionProvider`, tagged
``search.filter.option.provider``, returning ``true`` from :php:`supports()` for the field class it
reads. Providers are discovered by that tag, so the service picks one up without being edited.

Extending :php:`WerkraumMedia\ThueCat\Service\FilterField\OptionProvider\AbstractOptionProvider`
supplies the restriction handling every option query needs — deleted, disabled, language, storage
pages and the site bound — which is easy to get subtly wrong when writing a provider from scratch.
The shipped providers, :php:`CommaColumnOptionProvider` and :php:`HierarchicalOptionProvider`, sit
in the same namespace as worked examples.

How the search form finds its list
==================================

The search-and-filter form does not read its own settings to learn the preset. A resolver inspects
the current page for a list content element, reads its FlexForm, and returns the active preset:

* No list on the page -- the form targets the configured search result page.
* A list on the page -- the form stays on the page; a filtered list additionally contributes the
  preselected fields.

The lookup is language- and overlay-aware, because it reads the stored content element through the
frontend's record retrieval.

The form is rendered by ``TouristAttractionSearch`` but submits under ``TouristAttractionList``, so
the demand travels in the list's namespace. On re-render the form adopts that demand to re-populate
its fields, and the editor preset is forced onto the locked fields, so the hidden inputs always
carry the editor's values.
