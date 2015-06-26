<?php
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Constants definition for Attribute correlation_types
 * Add new contants to array in alib class
 */

/**
 * Attribute correlation type selector
 *
 * @var int
 * @see sql tx_commerce_attribute_correlationtypes
 */
define ('ATTRIB_SELECTOR', 1);
define ('ATTRIB_selector', ATTRIB_SELECTOR);

/**
 * Attribute correlation type shall
 *
 * @var int
 * @see sql tx_commerce_attribute_correlationtypes
 */
define ('ATTRIB_SHAL', 2);
define ('ATTRIB_shal', ATTRIB_SHAL);

/**
 * Attribute correlation type can
 *
 * @var int
 * @see sql tx_commerce_attribute_correlationtypes
 */
define ('ATTRIB_CAN', 3);
define ('ATTRIB_can', ATTRIB_CAN);

/**
 * Attribute correlation type product
 *
 * @var int
 * @see sql tx_commerce_attribute_correlationtypes
 */
define ('ATTRIB_PRODUCT', 4);
define ('ATTRIB_product', ATTRIB_PRODUCT);

/**
 * Basic abtract Class for element
 * tx_commerce_product
 * tx_commerce_article
 * tx_commerce_category
 * tx_commerce_attribute
 *
 * Class Tx_Commerce_Domain_Model_AbstractEntity
 *
 * @author 2005-2012 Ingo Schmitt <is@marketing-factory.de>
 */
class Tx_Commerce_Domain_Model_AbstractEntity {
	/**
	 * Uid of element
	 *
	 * @var int
	 */
	protected $uid = 0;

	/**
	 * Language uid
	 *
	 * @var int
	 */
	protected $lang_uid = 0;

	/**
	 * Language uid
	 *
	 * @var int
	 */
	protected $l18n_parent;

	/**
	 * Database class for inhertitation
	 *
	 * @var string
	 */
	protected $databaseClass = 'Tx_Commerce_Domain_Repository_Repository';

	/**
	 * Database connection
	 *
	 * @var Tx_Commerce_Domain_Repository_Repository
	 */
	protected $databaseConnection;

	/**
	 * Fieldlist for inhertitation
	 *
	 * @var array
	 */
	protected $fieldlist = array(
		'title',
		'lang_uid',
		'l18n_parent',
		'_LOCALIZED_UID'
	);

	/**
	 * Changes hier must be made, if a new correewlation_type is invented
	 *
	 * @var array of possible attribute correlation_types
	 */
	public $correlation_types = array(
		ATTRIB_SELECTOR,
		ATTRIB_SHAL,
		ATTRIB_CAN,
		ATTRIB_PRODUCT
	);

	/**
	 * Default add where for deleted hidden and more
	 *
	 * @var string
	 */
	protected $default_add_where = ' AND hidden = 0 AND deleted = 0';

	/**
	 * Attribute UIDs
	 *
	 * @var array
	 */
	protected $attributes_uids = array();

	/**
	 * Attributes
	 *
	 * @var array
	 */
	protected $attribute = array();

	/**
	 * Translation Mode for getRecordOverlay
	 *
	 * @var string
	 */
	protected $translationMode = 'hideNonTranslated';

	/**
	 * Flag if record is translaed
	 *
	 * @return bool
	 */
	protected $recordTranslated = FALSE;

	/**
	 * Localized UID
	 * the uid of the localized record
	 *
	 * @var int
	 */
	public $_LOCALIZED_UID;

	/**
	 * Database record
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * Possible attributes
	 *
	 * @param array $attributeCorelationTypeList Attribut correlation types
	 *
	 * @return array
	 */
	public function getAttributes(array $attributeCorelationTypeList = array()) {
		$result = array();
		if (($this->attributes_uids = $this->databaseConnection->getAttributes($this->uid, $attributeCorelationTypeList))) {
			foreach ($this->attributes_uids as $attributeUid) {
				/**
				 * Attribute
				 *
				 * @var Tx_Commerce_Domain_Model_Attribute $attribute
				 */
				$attribute = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
					'Tx_Commerce_Domain_Model_Attribute',
					$attributeUid,
					$this->lang_uid
				);
				$attribute->loadData();

				$this->attribute[$attributeUid] = $attribute;
			}
			$result = $this->attributes_uids;
		}

		return $result;
	}

	/**
	 * Set a given field, only to use with custom field without own method
	 * Warning: commerce provides getMethods for all default fields. For
	 * compatibility reasons always use the built in methods. Only use this
	 * method with you own added fields
	 *
	 * @param string $field Fieldname
	 * @param mixed $value Value
	 *
	 * @return void
	 */
	public function setField($field, $value) {
		$this->$field = $value;
	}

	/**
	 * Get a given field value, only to use with custom field without own method
	 * Warning: commerce provides getMethods for all default fields. For
	 * compatibility reasons always use the built in methods. Only use this
	 * method with you own added fields
	 *
	 * @param string $field Fieldname
	 *
	 * @return mixed Value of the field
	 */
	public function getField($field) {
		return $this->$field;
	}

	/**
	 * Get data array
	 *
	 * @return array
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * Language uid
	 *
	 * @return int
	 */
	public function getLang() {
		return (int) $this->lang_uid;
	}

	/**
	 * L18n parent uid
	 *
	 * @return int l18n_partent uid
	 */
	public function getL18nParent() {
		return (int) $this->l18n_parent;
	}

	/**
	 * Localized uid
	 *
	 * @return int
	 */
	public function getLocalizedUid() {
		return (int) $this->_LOCALIZED_UID;
	}

	/**
	 * Get uid
	 *
	 * @return int Uid
	 */
	public function getUid() {
		return (int) $this->uid;
	}


	/**
	 * Loads the Data from the database
	 * via the named database class $databaseClass
	 *
	 * @param bool $translationMode Translation mode of the record,
	 * 	default false to use the default way of translation
	 *
	 * @return array
	 */
	public function loadData($translationMode = FALSE) {
		if ($translationMode) {
			$this->translationMode = $translationMode;
		}

		if (!$this->databaseConnection) {
			$this->databaseConnection = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($this->databaseClass);
		}

		$this->data = $this->databaseConnection->getData($this->uid, $this->lang_uid, $translationMode);

		if (!$this->data) {
			$this->recordTranslated = FALSE;

			return FALSE;
		} else {
			$this->recordTranslated = TRUE;
		}

		foreach ($this->fieldlist as $field) {
			$this->$field = $this->data[$field];
		}

		if ($this->data['_LOCALIZED_UID']) {
			$this->_LOCALIZED_UID = $this->data['_LOCALIZED_UID'];
		}

		return $this->data;
	}

	/**
	 * Adds a field to the $fieldlist variable
	 * used for hooks to add own fields to the output
	 * Basically it creates an array with the string as value
	 * and calls $this->add_fields_to_fieldlist
	 *
	 * @param string $fieldname Database fieldname
	 *
	 * @return void
	 */
	public function addFieldToFieldlist($fieldname) {
		$this->addFieldsToFieldlist(array(trim($fieldname)));
	}

	/**
	 * Adds a set of fields to the $fieldlist variable
	 * used for hooks to add own fields to the output
	 *
	 * @param array $fieldarray Databse filednames
	 *
	 * @return void
	 */
	public function addFieldsToFieldlist(array $fieldarray) {
		$this->fieldlist = array_merge($this->fieldlist, (array) $fieldarray);
	}

	/**
	 * Checks in the Database if object is
	 * basically checks against the enableFields
	 *
	 * @return bool If is accessible [TRUE|FALSE]
	 */
	public function isAccessible() {
		if (!$this->databaseConnection) {
			$this->databaseConnection = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($this->databaseClass);
		}

		return $this->databaseConnection->isAccessible($this->uid);
	}

	/**
	 * Returns true, if a translation for the initialised Language is available
	 *
	 * @return bool
	 */
	public function isTranslated() {
		return $this->recordTranslated;
	}

	/**
	 * Checks if the uid is valid and available in the database
	 *
	 * @return bool true if uid is valid
	 */
	public function isValidUid() {
		if (!$this->databaseConnection) {
			$this->databaseConnection = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($this->databaseClass);
		}

		return $this->databaseConnection->isUid($this->uid);
	}

	/**
	 * Returns the data of this object als array
	 *
	 * @param string $prefix Prefix for the keys or returnung array optional
	 *
	 * @return array Assoc array of data
	 */
	public function returnAssocArray($prefix = '') {
		$data = array();

		foreach ($this->fieldlist as $field) {
			$data[$prefix . $field] = $this->$field;
		}

		return $data;
	}

	/**
	 * Sets the PageTitle titile from via the TSFE
	 *
	 * @param string $field Setting of page title
	 *
	 * @return void
	 */
	public function setPageTitle($field = 'title') {
		$this->getFrontendController()->page['title'] = $this->$field . ' : ' . $GLOBALS['TSFE']->page['title'];
		// set pagetitle for indexed search also
		$this->getFrontendController()->indexedDocTitle = $this->$field . ' : ' . $GLOBALS['TSFE']->indexedDocTitle;
	}


	/**
	 * Renders values from fieldlist to markers
	 *
	 * @param ContentObjectRenderer $cobj Content object
	 * @param array $conf Configuration for this viewmode
	 * @param string $prefix Optional prefix for marker
	 *
	 * @return array
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use tx_commerce_pibase->renderElement in combination with $this->returnAssocArray instead
	 */
	public function getMarkerArray(ContentObjectRenderer &$cobj, array $conf, $prefix = '') {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		$markContentArray = $this->returnAssocArray('');
		$markerArray = array();
		foreach ($markContentArray as $k => $v) {
			$vr = '';
			switch (strtoupper($conf[$k])) {
				case 'IMGTEXT':
					// fall through
				case 'IMAGE':
					$i = 1;
					$imgArray = explode(';', $v);
					foreach ($imgArray as $img) {
						$conf[$k . '.'][$i . '.'] = $conf[$k . '.']['defaultImgConf.'];
						$conf[$k . '.'][$i . '.']['file'] = $conf['imageFolder'] . $img;
						$vr = $cobj->IMAGE($conf[$k . '.'][$i . '.']);
					}
					break;

				case 'STDWRAP':
					$vr = $cobj->stdWrap($v, $conf[$k . '.']);
					break;

				default:
					$vr = $v;
			}
			$markerArray['###' . strtoupper($prefix . $k) . '###'] = $vr;
		}

		return $markerArray;
	}

	/**
	 * Get uid of object
	 *
	 * @return int uid
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getUid instead
	 */
	public function get_uid() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return $this->getUid();
	}

	/**
	 * Returns the UID of the localized Record
	 *
	 * @return int _LOCALIZED_UID
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getLocalizedUid instead
	 */
	public function get_LOCALIZED_UID() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return $this->getLocalizedUid();
	}

	/**
	 * Get language
	 *
	 * @return int language id
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getLang instead
	 */
	public function get_lang() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return $this->getLang();
	}

	/**
	 * Returns  the data of this object als array
	 *
	 * @param string $prefix Prefix for the keys or returnung array optional
	 *
	 * @return array Assoc Arry of data
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use returnAssocArray instead
	 */
	public function return_assoc_array($prefix = '') {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return $this->returnAssocArray($prefix);
	}

	/**
	 * Adds a field to the $fieldlist variable
	 * used for hooks to add own fields to the output
	 * Basically it creates an array with the string as value
	 * and calls $this->add_fields_to_fieldlist
	 *
	 * @param string $fieldname Database fieldname
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use addFieldToFieldlist instead
	 */
	public function add_field_to_fieldlist($fieldname) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		$this->addFieldToFieldlist($fieldname);
	}

	/**
	 * Adds a set of fields to the $fieldlist variable
	 * used for hooks to add own fields to the output
	 *
	 * @param array $fieldarray Field array
	 *
	 * @return void
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use addFieldsToFieldlist instead
	 */
	public function add_fields_to_fieldlist($fieldarray) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		$this->addFieldsToFieldlist($fieldarray);
	}

	/**
	 * Checks if the UID is valid and available in the database
	 *
	 * @return bool true if uid is valid
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use isValidUid instead
	 */
	public function is_valid_uid() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return $this->isValidUid();
	}

	/**
	 * Returns the possible attributes
	 *
	 * @param array $attributeCorelationTypeList Array of attribut_correlation_types
	 *
	 * @return array
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getAttributes instead
	 */
	public function get_attributes($attributeCorelationTypeList = array()) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return $this->getAttributes($attributeCorelationTypeList);
	}

	/**
	 * Loads the Data from the database
	 * via the named database class $databaseClass
	 *
	 * @param bool $translationMode Translation Mode of the record,
	 * 	default false to use the default way of translation
	 *
	 * @return array
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use loadData instead
	 */
	public function load_data($translationMode = FALSE) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return $this->loadData($translationMode);
	}


	/**
	 * Get typoscript frontend controller
	 *
	 * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
	 */
	protected function getFrontendController() {
		return $GLOBALS['TSFE'];
	}
}
