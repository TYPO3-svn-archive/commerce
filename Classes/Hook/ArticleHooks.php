<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 - 2011 Ingo Schmitt <is@marketing-factory.de>
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
 * Part of the COMMERCE (Advanced Shopping System) extension.
 *
 * Hook for article_class
 * This class is ment as programming-tutorial for programming hooks for delivery_costs
 */
class Tx_Commerce_Hook_ArticleHooks {
	/**
	 * Basic Method to calculate the delivereycost (net)
	 * Ment as Programming tutorial. Mostly you have to change or add some functionality
	 *
	 * @param tx_commerce_article_price $netPrice
	 * @param tx_commerce_article $article article_object (reference)
	 */
	public function calculateDeliveryCostNet(&$netPrice, &$article) {
		$deliveryArticle = $this->getDeliveryArticle($article);
		if ($deliveryArticle) {
			$netPrice = $deliveryArticle->getPriceNet();
		} else {
			$netPrice = 0;
		}
	}

	/**
	 * Basic Method to calculate the delivereycost (gross)
	 * Ment as Programming tutorial. Mostly you have to change or add some functionality
	 * @param price_object
	 * @param article_object (reference)
	 */
	public function calculateDeliveryCostGross(&$gross_price, &$article_obj) {
		$deliveryArticle = $this->getDeliveryArticle($article_obj);
		if ($deliveryArticle) {
			$gross_price = $deliveryArticle->getPriceGross();
		} else {
			$gross_price = 0;
		}
	}

	/**
	 * Load the deliveryArticle
	 *
	 * @param tx_commerce_article $article
	 * @return tx_commerce_article object
	 */
	protected function getDeliveryArticle(&$article) {
		$delivery_conf = ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['SYSPRODUCTS']['DELIVERY']['types']);
		$classname = array_shift(array_keys($delivery_conf));

		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$row = $database->exec_SELECTgetSingleRow(
			'uid',
			'tx_commerce_articles',
			'classname = \'' . $classname . '\''
		);

		$result = FALSE;
		if (!empty($row)) {
			$deliveryArticleUid = $row['uid'];

			/**
			 * Instantiate article class
			 * @var tx_commerce_article $deliveryArticle
			 */
			$deliveryArticle = t3lib_div::makeInstance('tx_commerce_article');
			$deliveryArticle->init($deliveryArticleUid, $article->getLang());

			/**
			 * Do not call loadData at this point, since loadData recalls this hook, so we have a
			 * non endingrecursion
			 */
			if (is_object($deliveryArticle)) {
				$deliveryArticle->loadPrices();
			}
			$result = $deliveryArticle;
		}

		return $result;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Hook/ArticleHooks.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Hook/ArticleHooks.php']);
}

?>