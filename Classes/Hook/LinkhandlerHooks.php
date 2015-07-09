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
 * Class Tx_Commerce_Hook_LinkhandlerHooks
 *
 * @author 2008-2009 Ingo Schmitt <is@marketing-factory.de>
 */
class Tx_Commerce_Hook_LinkhandlerHooks {
	/**
	 * Parent object
	 *
	 * @var ContentObjectRenderer
	 */
	protected $pObj;

	/**
	 * Main function
	 *
	 * @param string $linktxt Link text
	 * @param array $conf Configuration
	 * @param string $linkHandlerKeyword Keyword
	 * @param string $linkHandlerValue Value
	 * @param string $linkParameter Link parameter
	 * @param ContentObjectRenderer $pObj Parent

	 * @return string
	 */
	public function main($linktxt, array $conf, $linkHandlerKeyword, $linkHandlerValue, $linkParameter,
		ContentObjectRenderer &$pObj
	) {
		$this->pObj = &$pObj;

		$linkHandlerData = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('|', $linkHandlerValue);

		$addparams = '';
		foreach ($linkHandlerData as $linkData) {
			$params = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(':', $linkData);
			if (isset($params[0])) {
				if ($params[0] == 'tx_commerce_products') {
					$addparams .= '&tx_commerce_pi1[showUid]=' . (int) $params[1];
				} elseif ($params[0] == 'tx_commerce_categories') {
					$addparams .= '&tx_commerce_pi1[catUid]=' . (int) $params[1];
				}
			}
			if (isset($params[2])) {
				if ($params[2] == 'tx_commerce_products') {
					$addparams .= '&tx_commerce_pi1[showUid]=' . (int) $params[3];
				} elseif ($params[2] == 'tx_commerce_categories') {
					$addparams .= '&tx_commerce_pi1[catUid]=' . (int) $params[3];
				}
			}
		}

		if (strlen($addparams) <= 0) {
			return $linktxt;
		}

		/**
		 * Local content object
		 *
		 * @var ContentObjectRenderer $localcObj
		 */
		$localcObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');

		$displayPageId = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_commerce_pi1.']['overridePid'];
		if (empty($displayPageId)) {
			$displayPageId = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['previewPageID'];
		}

		// remove the first param of '$link_param' (this is the page id wich is
		// set by $DisplayPID) and add all params left (e.g. css class,
		// target...) to the value of $lconf['paramter']
		$linkParamArray = explode(' ', $linkParameter);
		if (is_array($linkParamArray)) {
			$linkParamArray = array_splice($linkParamArray, 1);
			if (count($linkParamArray) > 0) {
				$linkParameter = $displayPageId . ' ' . implode(' ', $linkParamArray);
			} else {
				$linkParameter = $displayPageId;
			}
		} else {
			$linkParameter = $displayPageId;
		}

		$lconf = $conf;
		unset($lconf['parameter.']);
		$lconf['parameter'] = $linkParameter;
		$lconf['additionalParams'] .= $addparams;

		return $localcObj->typoLink($linktxt, $lconf);
	}
}
