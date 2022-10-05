.. _integration:

===========
Integration
===========

Menu with tourist attractions
=============================

The extension provides a custom page type which references tourist attraction.
This can be used in conjunction with TYPO3 menus if generated via data processing,
see 

An concrete example would be to extend the content element ``menu_subpages``:

.. code-block:: typoscript

   tt_content.menu_subpages {
       dataProcessing {
           10 {
               dataProcessing {
                   20 = WerkraumMedia\ThueCat\Frontend\DataProcessing\ResolveEntities
                   20 {
                       as = touristAttraction
                       if {
                           isInList.data = field:doktype
                           value = 950
                       }
                       table = tx_thuecat_tourist_attraction
                       uids.data = flexform:tx_thuecat_flexform:touristAttraction
                   }
               }
           }
       }
   }

This will add the additional data processor delivered by the extension.
It will extend each page of the menu with the model of the referenced tourist attraction.
