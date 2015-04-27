<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2013 Ingo Schmitt <is@marketing-factory.de>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Frontend libary for handling the basket. This class should be used
 * when rendering the basket and changing the basket items.
 *
 * The basket object is stored as object within the Frontend user
 * fe_user->tx_commerce_basket, you could acces the Basket object in the Frontend
 * via $GLOBALS['TSFE']->fe_user->tx_commerce_basket;
 *
 * Do not acces class variables directly, allways use the get and set methods,
 * variables will be changed in php5 to private
 *
 * Basic class for basket_handeling inhertited from tx_commerce_basic_basket
 */
class Tx_Commerce_Domain_Model_Basket extends Tx_Commerce_Domain_Model_BasicBasket {
	/**
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $database;

	/**
	 * @var string  Storage-type for the data
	 */
	protected $storageType = 'database';

	/**
	 * @var string  Not session id, as session_id is PHP5 method
	 */
	protected $sessionId = '';

	/**
	 * @var array The unserialized commerce configuration from localconf.php
	 */
	protected $extensionConfigration = array();

	/**
	 * @var bool
	 */
	protected $isAlreadyLoaded = FALSE;

	/**
	 * Constructor for a commerce basket. Loads configuration data
	 */
	public function __construct() {
		$this->extensionConfigration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['commerce']);
		if ($this->extensionConfigration['basketType'] == 'persistent') {
			$this->storageType = 'persistent';
		}

		$this->database = $GLOBALS['TYPO3_DB'];
	}

	/**
	 * Set the session ID
	 *
	 * @param string $sessionId Session ID
	 * @return void
	 */
	public function setSessionId($sessionId) {
		$this->sessionId = $sessionId;
	}

	/**
	 * Returns the session ID
	 *
	 * @return string
	 */
	public function getSessionId() {
		return $this->sessionId;
	}


	/**
	 * Finish order
	 *
	 * @return void
	 */
	public function finishOrder() {
		switch ($this->storageType) {
			case 'persistent':
				$GLOBALS['TSFE']->fe_user->setKey('ses', 'txCommercePersistantSessionId', '');
				$GLOBALS['TSFE']->fe_user->storeSessionData();
				$this->finishOrderInDatabase();
				break;

			case 'database':
				$this->finishOrderInDatabase();
				break;

			default:
		}
	}

	/**
	 * Set finish date in database
	 *
	 * @return void
	 */
	protected function finishOrderInDatabase() {
		$updateArray = array(
			'finished_time' => $GLOBALS['EXEC_TIME'],
		);

		$this->database->exec_UPDATEquery(
			'tx_commerce_baskets',
			'sid = ' . $this->database->fullQuoteStr($this->sessionId, 'tx_commerce_baskets') . ' AND finished_time = 0',
			$updateArray
		);
	}

	/**
	 * Loads basket data from session / database depending
	 * on $this->storageType
	 * Only database storage is implemented until now
	 * cloud be used as per session or per user /presistent)
	 *
	 * @return void
	 */
	public function loadData() {
		if ($this->isAlreadyLoaded) {
			return;
		}

		switch ($this->storageType) {
			case 'persistent':
				$this->restoreBasket();
				break;

			case 'database':
				$this->loadDataFromDatabase();
				break;

			default:
		}
			// Method of Parent: Load the payment articcle if availiable
		parent::loadData();

		$this->setLoaded();
	}

	/**
	 * @return void
	 */
	public function setUnloaded() {
		$this->isAlreadyLoaded = FALSE;
	}

	/**
	 * @return void
	 */
	public function setLoaded() {
		$this->isAlreadyLoaded = TRUE;
	}

	/**
	 * Loads basket data from database
	 *
	 * @return void
	 */
	protected function loadDataFromDatabase() {
		$where = '';
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['BasketStoragePid'] > 0) {
			$where .= ' AND pid = ' . $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['BasketStoragePid'];
		}

		$rows = $this->database->exec_SELECTgetRows(
			'*',
			'tx_commerce_baskets',
			'sid = ' . $this->database->fullQuoteStr($this->sessionId, 'tx_commerce_baskets') .
				' AND finished_time = 0' . $where,
			'',
			'pos'
		);

		if (is_array($rows) && count($rows)) {
			$hookObjectsArr = array();
			if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_basket.php']['load_data_from_database'])) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog('
					hook
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_basket.php\'][\'load_data_from_database\']
					is deprecated since commerce 1.0.0, this hook will be removed in commerce 1.4.0, please use instead
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Model/Basket.php\'][\'loadDataFromDatabase\']
				');
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_basket.php']['load_data_from_database'] as $classRef) {
					$hookObjectsArr[] = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
				}
			}
			if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/Basket.php']['loadDataFromDatabase'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/Basket.php']['loadDataFromDatabase'] as $classRef) {
					$hookObjectsArr[] = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
				}
			}

			$basketReadonly = FALSE;
			foreach ($rows as $returnData) {
				if (($returnData['quantity'] > 0) && ($returnData['price_id'] > 0)) {
					$this->addArticle($returnData['article_id'], $returnData['quantity'], $returnData['price_id']);
					$this->changePrices($returnData['article_id'], $returnData['price_gross'], $returnData['price_net']);
					$this->crdate = $returnData['crdate'];
					if (is_array($hookObjectsArr)) {
						foreach ($hookObjectsArr as $hookObj) {
							if (method_exists($hookObj, 'load_data_from_database')) {
								\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog('
									hook method load_data_from_database
									is deprecated since commerce 1.0.0, this hook will be removed in commerce 1.4.0, please use loadDataFromDatabase instead
								');
								$hookObj->load_data_from_database($returnData, $this);
							}
							if (method_exists($hookObj, 'loadDataFromDatabase')) {
								$hookObj->loadDataFromDatabase($returnData, $this);
							}
						}
					}
				}
				if ($returnData['readonly'] == 1) {
					$basketReadonly = TRUE;
				}
			}

			if ($basketReadonly === TRUE) {
				$this->setReadOnly();
			}
		}
	}

	/**
	 * Loads the Basket Data from the database
	 *
	 * @param string $sessionId
	 * @return void
	 * @todo handling for special prices
	 */
	protected function loadPersistentDataFromDatabase($sessionId) {
		$rows = $this->database->exec_SELECTgetRows(
			'*',
			'tx_commerce_baskets',
			'sid = ' . $this->database->fullQuoteStr($sessionId, 'tx_commerce_baskets') . ' AND finished_time = 0 AND pid = ' .
				$this->extensionConfigration['BasketStoragePid'],
			'',
			'pos'
		);

		if (is_array($rows) && count($rows)) {
			$hookObjectsArr = array();
			if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/Basket.php']['loadPersistantDataFromDatabase'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/Basket.php']['loadPersistantDataFromDatabase'] as $classRef) {
					$hookObjectsArr[] = &\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
				}
			}

			foreach ($rows as $returnData) {
				if ($returnData['quantity'] > 0 && $returnData['price_id'] > 0) {
					$this->addArticle($returnData['article_id'], $returnData['quantity']);
					$this->crdate = $returnData['crdate'];
					if (is_array($hookObjectsArr)) {
						foreach ($hookObjectsArr as $hookObj) {
							if (method_exists($hookObj, 'loadPersistantDataFromDatabase')) {
								$hookObj->loadPersistantDataFromDatabase($returnData, $this);
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Restores the Basket from the persistent storage
	 *
	 * @return void
	 */
	private function restoreBasket() {
		if ($GLOBALS['TSFE']->fe_user->user) {
			$userSessionId = $GLOBALS['TSFE']->fe_user->getKey('user', 'txCommercePersistantSessionId');
			if ($userSessionId && $userSessionId != $this->sessionId) {
				$this->loadPersistentDataFromDatabase($userSessionId);
				$this->loadDataFromDatabase();
				$GLOBALS['TSFE']->fe_user->setKey('user', 'txCommercePersistantSessionId', $this->sessionId);
				$this->storeDataToDatabase();
			} else {
				$this->loadDataFromDatabase();
			}
		} else {
			$this->loadDataFromDatabase();
		}
	}

	/**
	 * Store basket data in session / database depending
	 * on $this->storageType
	 * Only database storage is implemented until now
	 *
	 * @return void
	 */
	public function storeData() {
		switch ($this->storageType) {
			case 'persistent':
				// fallthrough
			case 'database':
				$this->storeDataToDatabase();
				break;

			default:
		}
	}

	/**
	 * Store basket data to database
	 *
	 * @return void
	 */
	protected function storeDataToDatabase() {
		$this->database->exec_DELETEquery(
			'tx_commerce_baskets',
			'sid = ' . $this->database->fullQuoteStr($this->sessionId, 'tx_commerce_baskets') . ' AND finished_time = 0'
		);
		$hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_basket.php']['store_data_to_database'])) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_basket.php\'][\'store_data_to_database\']
				is deprecated since commerce 1.0.0, this hook will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Model/Basket.php\'][\'storeDataToDatabase\']
			');
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_basket.php']['store_data_to_database'] as $classRef) {
				$hookObjectsArr[] = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
			}
		}
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/Basket.php']['storeDataToDatabase'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/Basket.php']['storeDataToDatabase'] as $classRef) {
				$hookObjectsArr[] = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
			}
		}

		// Get array keys from basket items to store correct position in basket
		$arBasketItemsKeys = array_keys($this->basketItems);
		// After getting the keys in a array, flip it to get the position of each item
		$arBasketItemsKeys = array_flip($arBasketItemsKeys);

		$oneuid = 0;
		/** @var Tx_Commerce_Domain_Model_BasketItem $oneItem */
		foreach ($this->basketItems as $oneuid => $oneItem) {
			$insertData = array();
			$insertData['pid'] = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['BasketStoragePid'];
			$insertData['pos'] = $arBasketItemsKeys[$oneuid];
			$insertData['sid'] = $this->sessionId;
			$insertData['article_id'] = $oneItem->getArticleUid();
			$insertData['price_id'] = $oneItem->getPriceUid();
			$insertData['price_net'] = $oneItem->getPriceNet();
			$insertData['price_gross'] = $oneItem->getPriceGross();
			$insertData['quantity'] = $oneItem->getQuantity();
			$insertData['readonly'] = $this->getReadOnly();
			$insertData['tstamp'] = $GLOBALS['EXEC_TIME'];

			if ($this->crdate > 0) {
				$insertData['crdate'] = $this->crdate;
			} else {
				$insertData['crdate'] = $insertData['tstamp'];
			}

			if (is_array($hookObjectsArr)) {
				foreach ($hookObjectsArr as $hookObj) {
					if (method_exists($hookObj, 'store_data_to_database')) {
						\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog('
							hook
							$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_basket.php\'][\'store_data_to_database\']
							is deprecated since commerce 1.0.0, this hook will be removed in commerce 1.4.0, please use instead
							$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Model/Basket.php\'][\'storeDataToDatabase\']
						');
						$insertData = $hookObj->store_data_to_database($oneItem, $insertData);
					}
					if (method_exists($hookObj, 'storeDataToDatabase')) {
						$insertData = $hookObj->storeDataToDatabase($oneItem, $insertData);
					}
				}
			}

			$this->database->exec_INSERTquery('tx_commerce_baskets', $insertData);
		}

		$oneItem = $this->basketItems[$oneuid];
		if (is_object($oneItem)) {
			$oneItem->calculateNetSum();
			$oneItem->calculateGrossSum();
		}
	}


	/**
	 * Loads the Basket Data from the database
	 *
	 * @param string $sessionId
	 * @return void
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use loadPersistentDataFromDatabase instead
	 */
	protected function loadPersistantDataFromDatabase($sessionId) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		$this->loadPersistentDataFromDatabase($sessionId);
	}

	/**
	 * Loads basket data from database
	 *
	 * @return void
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use loadDataFromDatabase instead
	 */
	protected function load_data_from_database() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		$this->loadDataFromDatabase();
	}

	/**
	 * Store basket data to database
	 *
	 * @return void
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use storeDataToDatabase instead
	 */
	protected function store_data_to_database() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		$this->storeDataToDatabase();
	}

	/**
	 * Store basket data in session / database depending
	 * on $this->storageType
	 * Only database storage is implemented until now
	 *
	 * @return void
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use storeData instead
	 */
	public function store_data() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		$this->storeData();
	}

	/**
	 * Set the session ID
	 *
	 * @param string $sessionId Session ID
	 * @return void
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use setSessionId instead
	 */
	public function set_session_id($sessionId) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		$this->setSessionId($sessionId);
	}

	/**
	 * Returns the session ID
	 *
	 * @return string
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getSessionId instead
	 */
	public function get_session_id() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		return $this->getSessionId();
	}
}
