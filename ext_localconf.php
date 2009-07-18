<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 - 2009 Ingo Schmitt <is@marketing-factory.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Base configuration settings for commerce.
 * This file will be merged to typo3conf/temp_CACHED_hash_ext_localconf.php
 * together with all other ext_localconf.php files of other extensions.
 * The code in here will be executed very early on every frontend and backend access.
 *
 * @author Ingo Schmitt <is@marketing-factory.de>
 *
 * @TODO Check which parts could be moved to ext_tables.php to only include in BE processing
 *
 * $Id: ext_localconf.php 562 2007-03-02 10:16:12Z ingo $
 */

if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

// Definition of some helpfull constants
if (!defined ('COMMERCE_EXTkey')) {
	define('COMMERCE_EXTkey', $_EXTKEY);
}
if (!defined ('PATH_txcommerce')) {
	define('PATH_txcommerce', t3lib_extMgm::extPath(COMMERCE_EXTkey));
}
if (!defined ('PATH_txcommerce_rel')) {
	define('PATH_txcommerce_rel', t3lib_extMgm::extRelPath(COMMERCE_EXTkey));
}
if (!defined ('PATH_txcommerce_icon_table_rel')) {
	define('PATH_txcommerce_icon_table_rel', PATH_txcommerce_rel.'res/icons/table/');
}
if (!defined ('PATH_txcommerce_icon_tree_rel')) {
	define('PATH_txcommerce_icon_tree_rel', PATH_txcommerce_rel.'res/icons/table/');
}

// Define special article types
define(NORMALArticleType,1);
define(PAYMENTArticleType,2);
define(DELIVERYArticleType,3);


require_once(PATH_txcommerce.'/treelib/class.tx_commerce_tcefunc.php');


// Unserialize the plugin configuration so we can use it
$_EXTCONF = unserialize($_EXTCONF);

// This array holds global definitions of arbitrary commerce settings
// Add unserialized ext conf settings to global array for easy access of those settings
$TYPO3_CONF_VARS['EXTCONF'][COMMERCE_EXTkey]['extConf'] = $_EXTCONF;

// Payment settings
$TYPO3_CONF_VARS['EXTCONF'][COMMERCE_EXTkey]['SYSPRODUCTS']['PAYMENT'] = array(
	'tablefields' => array(
		'title' => 'SYSTEMPRODUCT_PAYMENT',
		'description' => 'product zum Verwalten der Bezahlung',
	)
);
$TYPO3_CONF_VARS['EXTCONF'][COMMERCE_EXTkey]['SYSPRODUCTS']['PAYMENT']['types']['invoice'] = array(
	'path' => PATH_txcommerce .'payment/class.tx_commerce_payment_invoice.php',
	'class' => 'tx_commerce_payment_invoice',
	'type' => PAYMENTArticleType,
);
$TYPO3_CONF_VARS['EXTCONF'][COMMERCE_EXTkey]['SYSPRODUCTS']['PAYMENT']['types']['prepayment'] = array(
	'path' => PATH_txcommerce .'payment/class.tx_commerce_payment_prepayment.php',
	'class' => 'tx_commerce_payment_prepayment',
	'type' => PAYMENTArticleType,
);
$TYPO3_CONF_VARS['EXTCONF'][COMMERCE_EXTkey]['SYSPRODUCTS']['PAYMENT']['types']['creditcard'] = array(
	'path' => PATH_txcommerce .'payment/class.tx_commerce_payment_creditcard.php',
	'class' => 'tx_commerce_payment_creditcard',
	'type' => PAYMENTArticleType,
	// Language file for external credit card check
	'ccvs_language_files' => PATH_txcommerce . 'payment/ccvs_language',
);
$TYPO3_CONF_VARS['EXTCONF'][COMMERCE_EXTkey]['SYSPRODUCTS']['PAYMENT']['types']['cashondelivery'] = array(
	'path' => PATH_txcommerce .'payment/class.tx_commerce_payment_cashondelivery.php',
	'class' => 'tx_commerce_payment_cashondelivery',
	'type' => PAYMENTArticleType,
);

// Delivery settings
$TYPO3_CONF_VARS['EXTCONF'][COMMERCE_EXTkey]['SYSPRODUCTS']['DELIVERY'] = array(
	'tablefields' => array(
		'title' => 'SYSTEMPRODUCT_DELIVERY',
		'description' => 'product zum Verwalten der Lieferarten',
	)
);
$TYPO3_CONF_VARS['EXTCONF'][COMMERCE_EXTkey]['SYSPRODUCTS']['DELIVERY']['types'] = array(
	'sysdelivery' => array(
		'type' => DELIVERYArticleType
	),
);

// pid for new tt_address records
$TYPO3_CONF_VARS['EXTCONF'][COMMERCE_EXTkey]['create_address_pid'] = $_EXTCONF['create_address_pid'] ? $_EXTCONF['create_address_pid'] : '0';

// fe_user <-> tt_address field mapping
$TYPO3_CONF_VARS['EXTCONF'][COMMERCE_EXTkey]['feuser_address_mapping'] = $_EXTCONF['feuser_address_mapping'] ? $_EXTCONF['feuser_address_mapping'] : 'company,company;name,name;last_name,surname;title,title;address,address;zip,zip;city,city;country,country;telephone,phone;fax,fax;email,email;www,www;';

// Storage pid for baskets
$TYPO3_CONF_VARS['EXTCONF'][COMMERCE_EXTkey]['BasketStoragePid'] = $_EXTCONF['BasketStoragePid'] ? $_EXTCONF['BasketStoragePid'] : 0;

// Basket locking
$TYPO3_CONF_VARS['EXTCONF'][COMMERCE_EXTkey]['lockBasket'] = $_EXTCONF['lockBasket'] ? $_EXTCONF['lockBasket'] : 0;

// Show article number
$TYPO3_CONF_VARS['EXTCONF'][COMMERCE_EXTkey]['showArticleNumber'] = $_EXTCONF['showArticleNumber'] ? $_EXTCONF['showArticleNumber'] : 0;

// Show article name
$TYPO3_CONF_VARS['EXTCONF'][COMMERCE_EXTkey]['showArticleTitle'] = $_EXTCONF['showArticleTitle'] ? $_EXTCONF['showArticleTitle'] : 0;


// Add frontend plugins to content.default static template
t3lib_extMgm::addPItoST43(COMMERCE_EXTkey, 'pi1/class.tx_commerce_pi1.php', '_pi1', 'list_type', 1);
t3lib_extMgm::addPItoST43(COMMERCE_EXTkey, 'pi2/class.tx_commerce_pi2.php', '_pi2', 'list_type', 0);
t3lib_extMgm::addPItoST43(COMMERCE_EXTkey, 'pi3/class.tx_commerce_pi3.php', '_pi3', 'list_type', 0);
t3lib_extMgm::addPItoST43(COMMERCE_EXTkey, 'pi4/class.tx_commerce_pi4.php', '_pi4', 'list_type', 0);
t3lib_extMgm::addPItoST43(COMMERCE_EXTkey, 'pi6/class.tx_commerce_pi6.php', '_pi6', 'list_type', 0);


t3lib_extMgm::addTypoScript(COMMERCE_EXTkey, 'editorcfg', '
	tt_content.CSS_editor.ch.tx_commerce_pi6 = < plugin.tx_commerce_pi6.CSS_editor
', 43);


t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_commerce_products=1
');
t3lib_extMgm::addPageTSConfig('

	# ***************************************************************************************
	# CONFIGURATION of RTE in table "tx_commerce_products", field "description"
	# ***************************************************************************************
RTE.config.tx_commerce_products.description {
  hidePStyleItems = H1, H4, H5, H6
  proc.exitHTMLparser_db=1
  proc.exitHTMLparser_db {
    keepNonMatchedTags=1
    tags.font.allowedAttribs= color
    tags.font.rmTagIfNoAttrib = 1
    tags.font.nesting = global
  }
}

	# ***************************************************************************************
	# CONFIGURATION of RTE in table "tx_commerce_products", field "materialaufbau"
	# ***************************************************************************************
RTE.config.tx_commerce_products.materialaufbau < RTE.config.tx_commerce_products.description

	# ***************************************************************************************
	# CONFIGURATION of RTE in table "tx_commerce_products", field "anwendungsfelder"
	# ***************************************************************************************
RTE.config.tx_commerce_products.anwendungsfelder < RTE.config.tx_commerce_products.description
');
t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_commerce_article_types=1
');
t3lib_extMgm::addPageTSConfig('

	# ***************************************************************************************
	# CONFIGURATION of RTE in table "tx_commerce_articles", field "description_extra"
	# ***************************************************************************************
RTE.config.tx_commerce_articles.description_extra {
  hidePStyleItems = H1, H4, H5, H6
  proc.exitHTMLparser_db=1
  proc.exitHTMLparser_db {
    keepNonMatchedTags=1
    tags.font.allowedAttribs= color
    tags.font.rmTagIfNoAttrib = 1
    tags.font.nesting = global
  }
}
');
t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_commerce_attributes=1
');
t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_commerce_attribute_values=1
');
t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_commerce_categories=1
');
t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_commerce_trackingcodes=1
');
t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_commerce_moveordermails=1
');




// Only in TYPO3 versions greater or equal 4.2
if (t3lib_div::int_from_ver(TYPO3_version) >= '4002000') {
	$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/classes/class.modulemenu.php'] = t3lib_extMgm::extPath(COMMERCE_EXTkey) . 'class.ux_modulemenu.php';
}

// Xclass for version preview
$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/version/cm1/index.php'] = t3lib_extMgm::extPath(COMMERCE_EXTkey) . 'class.ux_versionindex.php';

// Add special in db list, to have the ability to search for OrderIds in TYPO 4.0
$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/class.db_list_extra.inc']=t3lib_extMgm::extPath(COMMERCE_EXTkey) . 'class.ux_localrecordlist.php';

// Add linkhandler for "commerce"
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_content.php']['typolinkLinkHandler']['commerce'] = 'EXT:commerce/hooks/class.tx_commerce_linkhandler.php:&tx_commerce_linkhandler';

$TYPO3_CONF_VARS['SC_OPTIONS']['typo3/class.browse_links.php']['browseLinksHook'][] = 'EXT:commerce/hooks/class.tx_commerce_browselinkshooks.php:tx_commerce_browselinkshooks';

$TYPO3_CONF_VARS['SC_OPTIONS']['ext/rtehtmlarea/mod3/class.tx_rtehtmlarea_browse_links.php']['browseLinksHook'][] = 'EXT:commerce/hooks/class.tx_commerce_browselinkshooks.php:tx_commerce_browselinkshooks';


// Only in TYPO3 versions less than 4.3
// xlass t3lib_parsehtml_proc to fix TYPO3 issue 10331
// @see http://bugs.typo3.org/view.php?id=10331
if (t3lib_div::int_from_ver(TYPO3_version) < '4003000') {
	require_once(t3lib_extMgm::extPath(COMMERCE_EXTkey) . 'class.ux_t3lib_parsehtml_proc.php');
	$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_parsehtml_proc.php'] = t3lib_extMgm::extPath(COMMERCE_EXTkey) . 'class.ux_t3lib_parsehtml_proc.php';
}


// Add ajax listener for tree in linkcommerce
$TYPO3_CONF_VARS['BE']['AJAX']['tx_commerce_browselinkshooks::ajaxExpandCollapse'] = 'EXT:commerce/hooks/class.tx_commerce_browselinkshooks.php:tx_commerce_browselinkshooks->ajaxExpandCollapse';


// Hooks for datamap procesing
// For processing the order sfe, when changing the pid
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'EXT:commerce/hooks/class.tx_commerce_dmhooks.php:tx_commerce_dmhooks';

// Hooks for commandmap processing
// For new drawing of the category tree after having deleted a record
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] = 'EXT:commerce/hooks/class.tx_commerce_cmhooks.php:tx_commerce_cmhooks';

// Hooks for version swap procesing
// For processing the order sfe, when changing the pid
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processVersionSwapClass'][] = 'EXT:commerce/hooks/class.tx_commerce_versionhooks.php:tx_commerce_versionhooks';


// Adding some hooks for tx_commerce_article_processing
// As basic hook for calculation the delivery_costs
if (empty($TYPO3_CONF_VARS['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['calculateDeliveryCost'])) {
    $TYPO3_CONF_VARS['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['calculateDeliveryCost'] = 'EXT:commerce/hooks/class.tx_commerce_articlehooks.php:tx_commerce_articlehooks';
}

if (empty($TYPO3_CONF_VARS['EXTCONF']['commerce/hooks/class.tx_commerce_dmhooks.php']['moveOrders'])) {
    $TYPO3_CONF_VARS['EXTCONF']['commerce/hooks/class.tx_commerce_dmhooks.php']['moveOrders'][] = 'EXT:commerce/hooks/class.tx_commerce_ordermailhooks.php:tx_commerce_ordermailhooks';
}


//Adding the AJAX listeners for Permission change/Browsing the Category tree
$TYPO3_CONF_VARS['BE']['AJAX']['SC_mod_access_perm_ajax::dispatch'] = 'EXT:commerce/mod_access/class.sc_mod_access_perm_ajax.php:SC_mod_access_perm_ajax->dispatch';
$TYPO3_CONF_VARS['BE']['AJAX']['tx_commerce_access_navframe::ajaxExpandCollapse'] = 'EXT:commerce/mod_access/class.tx_commerce_access_navframe.php:tx_commerce_access_navframe->ajaxExpandCollapse';
$TYPO3_CONF_VARS['BE']['AJAX']['tx_commerce_category_navframe::ajaxExpandCollapse'] = 'EXT:commerce/mod_category/class.tx_commerce_category_navframe.php:tx_commerce_category_navframe->ajaxExpandCollapse';


// This line configures to process the code selectConf with the class "tx_commerce_hooks"
require_once(t3lib_extMgm::extPath(COMMERCE_EXTkey) . 'hooks/class.tx_commerce_tcehooksHandler.php');
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'tx_commerce_tcehooksHandler';
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] = 'tx_commerce_tcehooksHandler';

require_once(t3lib_extMgm::extPath(COMMERCE_EXTkey) . 'hooks/class.tx_commerce_irrehooks.php');
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tceforms_inline.php']['tceformsInlineHook'][] = 'tx_commerce_irrehooks';
require_once(t3lib_extMgm::extPath(COMMERCE_EXTkey) . 'hooks/class.tx_commerce_tceforms_hooks.php');
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getSingleFieldClass'][] = 'tx_commerce_tceforms_hooks';

require_once(t3lib_extMgm::extPath(COMMERCE_EXTkey) . 'hooks/class.tx_srfeuserregister_commerce_hooksHandler.php');
$TYPO3_CONF_VARS['EXTCONF']['sr_feuser_register']['tx_srfeuserregister_pi1']['registrationProcess'][] = 'tx_srfeuserregister_commerce_hooksHandler';

require_once(t3lib_extMgm::extPath(COMMERCE_EXTkey) . 'hooks/class.tx_commerce_pi4hooksHandler.php');
$TYPO3_CONF_VARS['EXTCONF']['commerce/pi4/class.tx_commerce_pi4.php']['deleteAddress'][] = 'tx_commerce_pi4hooksHandler';
$TYPO3_CONF_VARS['EXTCONF']['commerce/pi4/class.tx_commerce_pi4.php']['saveAddress'][] = 'tx_commerce_pi4hooksHandler';


// CLI Skript configration
if (TYPO3_MODE == 'BE') {
	// Setting up scripts that can be run from the cli_dispatch.phpsh script
	$TYPO3_CONF_VARS['SC_OPTIONS']['GLOBAL']['cliKeys'][COMMERCE_EXTkey] = array(
		'EXT:' . COMMERCE_EXTkey . '/cli/class.cli_commerce.php',
		'_CLI_commerce'
	);
}

// Register dynaflex dca files
$GLOBALS['T3_VAR']['ext']['dynaflex']['tx_commerce_categories'][] = 'EXT:commerce/dcafiles/class.tx_commerce_categories_dfconfig.php:tx_commerce_categories_dfconfig';

require_once(t3lib_extMgm::extPath(COMMERCE_EXTkey).'lib/class.tx_commerce_forms_select.php');
?>
