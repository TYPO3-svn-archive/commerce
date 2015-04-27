<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 - 2006 Thomas Hempel <thomas@work.de>
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
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
 * Implements the dynaflex config for the 'tx_commerce_products' table
 */
class Tx_Commerce_Configuration_Dca_Products {
	/**
	 * @var array
	 */
	public $rowChecks = array();

	/**
	 * @var array
	 */
	public $DCA = array(
		/**
		 * This is the configuration for the correlationtype fields on tab
		 * "select attributes" We fetch all correlationtypes from the database and for
		 * every ct we create two fields. The first one is field of type none. The only
		 * reason for this field is to display all attributes from the parent categories
		 * the product is assigned to. This field is filled the tcehooks class.
		 * The second field is a little bit more complex, because the user can select
		 * some attributes from the db here. It's a normal select field which is handled
		 * by TYPO3. Only writing the relations into the database is done in class
		 * tcehooks.
		 */
		0 => array(
			'path' => 'tx_commerce_products/columns/attributes/config/ds/default',
			'cleanup' => array(
				'table' => 'tx_commerce_products',
				'field' => 'attributes',
			),
			'modifications' => array(
				array(
					'method' => 'add',
					'path' => 'ROOT/el',
					'type' => 'fields',
					'source' => 'db',
					'source_type' => 'entry_count',
					'source_config' => array(
						'table' => 'tx_commerce_attribute_correlationtypes',
						'select' => '*',
						'where' => 'uid = 1',
					),
					'field_config' => array(
						1 => array(
							'name' => 'ct_###uid###',
							'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce.ct_###title###',
							'config' => array(
								'type' => 'select',
								'foreign_table' => 'tx_commerce_attributes',
								'foreign_label' => 'title',
								'foreign_table_where' => '  AND sys_language_uid in (0,-1) AND has_valuelist=1 AND multiple=0 ORDER BY title',
								'size' => 5,
								'minitems' => 0,
								'maxitems' => 50,
								'autoSizeMax' => 20,
								'renderMode' => 'tree',
								'treeConfig' => array(
									'parentField' => 'parent',
									'appearance' => array(
										'expandAll' => TRUE,
										'showHeader' => TRUE,
									),
								),
							),
						),
					),
				),
			),
		),
		1 => array(
			'path' => 'tx_commerce_products/columns/attributes/config/ds/default',
			'modifications' => array(
				array(
					'method' => 'add',
					'path' => 'ROOT/el',
					'type' => 'fields',
					'source' => 'db',
					'source_type' => 'entry_count',
					'source_config' => array(
						'table' => 'tx_commerce_attribute_correlationtypes',
						'select' => '*',
						'where' => 'uid != 1',
					),
					'field_config' => array(
						1 => array(
							'name' => 'ct_###uid###',
							'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce.ct_###title###',
							'config' => array(
								'type' => 'select',
								'foreign_table' => 'tx_commerce_attributes',
								'foreign_label' => 'title',
								'foreign_table_where' => '  AND sys_language_uid in (0,-1) ORDER BY title',
								'size' => 5,
								'minitems' => 0,
								'maxitems' => 50,
								'autoSizeMax' => 20,
								'renderMode' => 'tree',
								'treeConfig' => array(
									'parentField' => 'parent',
									'appearance' => array(
										'expandAll' => TRUE,
										'showHeader' => TRUE,
									),
								),
							),
						),
					),
				),
			),
		),
		/**
		 * Here we define the fields on "edit attributes" tab. They will be
		 * defined by a userfunction. This userfunction IS NOT the same as the
		 * userdefined field thing of TYPO3. It's something dynaflex related!
		 * We fetch all attributes with ct 4 for this product and pass the data
		 * to a userfuntion. This function creates a dynaflex field
		 * configuration (Which is actually a "normal" TYPO3 TCA field
		 * configuration) and returns it to dynaflex, which creates the field
		 * in the TCA. Irritated? No problem... ;)
		 */
		2 => array(
			'path' => 'tx_commerce_products/columns/attributesedit/config/ds/default',
			'modifications' => array(
				array(
					'method' => 'add',
					'path' => 'ROOT/el',
					'type' => 'fields',
					'condition' => array(
						'if' => 'hasValues',
						'source' => 'db',
						'table' => 'tx_commerce_products_attributes_mm',
						'select' => 'uid_foreign',
						'where' => 'uid_local = ###uid### AND uid_correlationtype=4',
						'orderby' => 'sorting',
					),
					'source' => 'db',
					'source_config' => array(
						'table' => 'tx_commerce_products_attributes_mm',
						'select' => '*',
						'where' => 'uid_local = ###uid### AND uid_correlationtype=4',
						'orderby' => 'sorting',
					),
					'field_config' => array(
						'singleUserFunc' => 'Tx_Commerce_Utility_AttributeEditorUtility->getAttributeEditField',
					),
				),
			),
		),
		/**
		 * At last we have to decide which tabs have to be displayed. We do this with a
		 * dynaflex condition and if it triggers, we append something at the showitem
		 * value in the products TCA.
		 */
		3 => array(
			'path' => 'tx_commerce_products/types/0/showitem',
			'parseXML' => FALSE,
			'modifications' => array(
				// display the "select attributes only in def language
				array(
					'method' => 'add',
					'type' => 'append',
					'condition' => array(
						'source' => 'language',
						'if' => 'isEqual',
						'compareTo' => 'DEF',
					),
					'config' => array(
						'text' => ',--div--;LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.select_attributes, attributes',
					),
				),
				// add "edit attributes" tab if minimum one attribute with correlationtype 4
				// exists for this product this also recognizes attributes from categories
				// of this product.
				array(
					'method' => 'add',
					'type' => 'append',
					'conditions' => array(
						array(
							'if' => 'isGreater',
							'table' => 'tx_commerce_products_attributes_mm pa',
							'select' => 'COUNT(*)',
							'where' => 'uid_correlationtype=4 AND uid_local=###uid###',
							'isXML' => FALSE,
							'compareTo' => 0,
						),
						array(
							'source' => 'language',
							'if' => 'isEqual',
							'compareTo' => 'DEF'
						),
					),
					'config' => array(
						'text' => ',--div--;LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.edit_attributes, attributesedit',
					),
				),
				// add "localise attributes" tab if minimum one attribute with correlationtype
				// 4 exists for this product and we are in a localised view
				array(
					'method' => 'add',
					'type' => 'append',
					'conditions' => array(
						array(
							'if' => 'isGreater',
							'table' => 'tx_commerce_products_attributes_mm pa',
							'select' => 'COUNT(*)',
							'where' => 'uid_correlationtype=4 AND uid_local=###uid###',
							'isXML' => FALSE,
							'compareTo' => 0,
						),
						array(
							'source' => 'language',
							'if' => 'notEqual',
							'compareTo' => 'DEF'
						),
					),
					'config' => array(
						'text' => ',--div--;LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.localedit_attributes,attributesedit',
					),
				),
				// add "create articles" tab if minimum one attribute with correlationtype 1
				// exists for this product this also recognizes attributes from categories
				// of this product. The fields on the tab are allready defined in the TCA!
				array(
					'method' => 'add',
					'type' => 'append',
					'condition' => array(
						'source' => 'language',
						'if' => 'isEqual',
						'compareTo' => 'DEF',
					),
					'config' => array(
						'text' => ',--div--;LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.create_articles,articles',
					),
				),
				// add "Localisze Articel" tab if we are in a localised language
				array(
					'method' => 'add',
					'type' => 'append',
					'condition' => array(
						'table' => 'tx_commerce_products',
						'select' => 'l18n_parent',
						'where' => 'uid=###uid### AND 0=',
						'isXML' => FALSE,
						'if' => 'isGreater',
						'compareTo' => 0,
					),
					'config' => array(
						'text' => ',--div--;LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.lokalise_articles,articleslok',
					),
				),
				// add "Localize Articel" tab if we are in a localised language
				array(
					'method' => 'add',
					'type' => 'append',
					'condition' => array(
						'table' => 'tx_commerce_products',
						'select' => 'l18n_parent',
						'where' => 'uid=###uid### AND 0!=',
						'isXML' => FALSE,
						'if' => 'isGreater',
						'compareTo' => 0,
					),
					'config' => array(
						'text' => ',--div--;LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.lokalise_articles,articles',
					),
				),
				array(
					'method' => 'add',
					'type' => 'append',
					'condition' => array(
						'source' => 'language',
						'if' => 'isEqual',
						'compareTo' => 'DEF',
					),
					'config' => array(
						'text' => ',--div--;LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.extras'
					),
				),
				array(
					'method' => 'move',
					'type' => 'extraFields',
					'table' => 'tx_commerce_products',
				),
			),
		),
	);

	/**
	 * @var string
	 */
	public $cleanUpField = 'attributes';

	/**
	 * @var array
	 */
	public $hooks = array('tx_commerce_configuration_dca_products');

	/**
	 * @return self
	 */
	public function __construct() {
		$simpleMode = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['simpleMode'];

		if ($simpleMode == 1) {
			$this->DCA[1]['modifications'][0]['source_config']['where'] = 'uid = 4';
		}

		$this->DCA[3]['modifications'][4]['condition']['where'] .= $simpleMode;
		$this->DCA[3]['modifications'][5]['condition']['where'] .= $simpleMode;

		$postEdit = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('edit');
		if (is_array($postEdit['tx_commerce_products'])) {
			$uid = array_keys($postEdit['tx_commerce_products']);

			if ($postEdit['tx_commerce_products'][$uid[0]] == 'new') {
				$uid = 0;
			} else {
				$uid = $uid[0];
			}

			$this->DCA[0]['uid'] = $uid;
			$this->DCA[1]['uid'] = $uid;
		}
	}

	/**
	 * @param array $resultDca
	 * @return void
	 */
	public function alterDCA_onLoad(&$resultDca) {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

		if (
			!(
				\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('data') == NULL ||
				\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('createArticles') == 'create'
			) &&
			$backendUser->uc['txcommerce_afterDatabaseOperations'] != 1
		) {
			$resultDca = array();
		}
	}
}
