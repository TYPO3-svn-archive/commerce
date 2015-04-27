<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2006-2011 Joerg Sprung <jsp@marketing-factory.de>
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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class contains some hooks for processing formdata.
 * Hook for saving order data and order_articles.
 */
class Tx_Commerce_Hook_OrdermailHooks {
	/**
	 * The cObj from class.tslib_content.php
	 *
	 * @var tslib_cObj
	 */
	protected $cObj;

	/**
	 * The Conversionobject from class.t3lib_cs.php
	 *
	 * @var t3lib_cs
	 */
	protected $csConvObj;

	/**
	 * the content of the TEmplate in Progress
	 *
	 * @var string
	 */
	protected $templateCode = '';

	/**
	 * @var string
	 */
	protected $templateCodeHtml;

	/**
	 * Path where finding Templates in CMS-File Structure
	 *
	 * @var string
	 */
	protected $templatePath;

	/**
	 * Caontaing the actual Usermailadress which is in Progress
	 *
	 * @var string
	 */
	protected $customermailadress = '';

	/**
	 * Tablename of table containing the Template for the specified Situations
	 *
	 * @var string
	 */
	protected $tablename = 'tx_commerce_moveordermails';

	/**
	 * Containing the Module configurationoptions
	 *
	 * @var array
	 */
	protected $extConf;

	/**
	 * This is just a constructor to instanciate the backend library
	 *
	 * @return self
	 */
	public function __construct() {
		$this->extConf = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce']['extConf'];
		$this->cObj = GeneralUtility::makeInstance('tslib_cObj');
		$this->csConvObj = GeneralUtility::makeInstance('t3lib_cs');
		$this->templatePath = PATH_site . 'uploads/tx_commerce/';
	}

	/**
	 * This method converts an sends mails.
	 *
	 * @param array $mailconf
	 * @param array &$orderdata
	 * @param string &$template
	 * @return boolean of t3lib_div::plainMailEncoded
	 */
	protected function ordermoveSendMail($mailconf, &$orderdata, &$template) {
			// First line is subject
		$parts = explode(chr(10), $mailconf['plain']['content'], 2);
			// add mail subject
		$mailconf['alternateSubject'] = trim($parts[0]);
			// replace plaintext content
		$mailconf['plain']['content'] = trim($parts[1]);

		/**
		 * Convert Text to charset
		 */
		$this->csConvObj->initCharset('utf-8');
		$this->csConvObj->initCharset('8bit');

		$mailconf['plain']['content'] = $this->csConvObj->conv($mailconf['plain']['content'], 'utf-8', 'utf-8');
		$mailconf['alternateSubject'] = $this->csConvObj->conv($mailconf['alternateSubject'], 'utf-8', 'utf-8');

		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Hook/class.tx_commerce_ordermailhooks.php']['ordermoveSendMail'])) {
			GeneralUtility::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Hook/class.tx_commerce_ordermailhooks.php\'][\'ordermoveSendMail\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Hook/OrdermailHooks.php\'][\'ordermoveSendMail\']
			');
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Hook/class.tx_commerce_ordermailhooks.php']['ordermoveSendMail'] as $classRef) {
				$hookObj = GeneralUtility::getUserObj($classRef);
				if (method_exists($hookObj, 'postOrdermoveSendMail')) {
					$hookObj->postOrdermoveSendMail($mailconf, $orderdata, $template);
				}
			}
		}
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Hook/OrdermailHooks.php']['ordermoveSendMail'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Hook/OrdermailHooks.php']['ordermoveSendMail'] as $classRef) {
				$hookObj = GeneralUtility::getUserObj($classRef);
				if (method_exists($hookObj, 'postOrdermoveSendMail')) {
					$hookObj->postOrdermoveSendMail($mailconf, $orderdata, $template);
				}
			}
		}

		return Tx_Commerce_Utility_GeneralUtility::sendMail($mailconf, $this);
	}

	/**
	 * Getting a template with all Templatenames in the Mailtemplaterecords
	 * according to the given mailkind and pid
	 *
	 * @param int $mailkind 0 move in and 1 move out the Order in the Orderfolder
	 * @param int $pid The PID of the order to move
	 * @param int $orderSysLanguageUid
	 * @return array of templatenames found in Filelist
	 */
	protected function generateTemplateArray($mailkind, $pid, $orderSysLanguageUid) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];
		/** @var t3lib_pageSelect $t3libPage */
		$t3libPage = GeneralUtility::makeInstance('t3lib_pageSelect');

		$rows = $database->exec_SELECTgetRows(
			'*',
			$this->tablename,
			'sys_language_uid = 0 AND pid = ' . $pid . ' AND mailkind = ' . $mailkind .
				\TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields($this->tablename)
		);

		$templates = array();
		foreach ($rows as $row) {
			$templates[] = $t3libPage->getRecordOverlay($this->tablename, $row, $orderSysLanguageUid);
		}

		return $templates;
	}

	/**
	 * This method will be used by the initial methods before and after the Order
	 * will be moved to another Orderstate
	 *
	 * @param array &$orderdata Containing the orderdatea like UID and PID
	 * @param array &$detaildata Containing the detaildata to Order like
	 * 		order_id and CustomerUIDs
	 * @param int $mailkind
	 * @return void
	 */
	protected function processOrdermails(&$orderdata, &$detaildata, $mailkind) {
		$pid = $orderdata['pid'] ? $orderdata['pid'] : $detaildata['pid'];
		$templates = $this->generateTemplateArray($mailkind, $pid, $detaildata['order_sys_language_uid']);

		foreach ($templates as $template) {
			$this->templateCode = GeneralUtility::getURL($this->templatePath . $template['mailtemplate']);
			$this->templateCodeHtml = GeneralUtility::getURL($this->templatePath . $template['htmltemplate']);

			$senderemail = $template['senderemail'] == '' ? $this->extConf['defEmailAddress'] : $template['senderemail'];
			if ($template['sendername'] == '') {
				if ($senderemail == $this->extConf['defEmailAddress']) {
					$sendername = $this->extConf['defEmailSendername'];
				} else {
					$sendername = $senderemail;
				}
			} else {
				$sendername = $template['sendername'];
			}

			// Mailconf for tx_commerce_div::sendMail($mailconf);
			$mailconf = array(
				'plain' => array(
					'content' => $this->generateMail($orderdata['order_id'], $detaildata, $this->templateCode),
				),
				'html' => array(
					'content' => $this->generateMail($orderdata['order_id'], $detaildata, $this->templateCodeHtml),
					'path' => '',
					'useHtml' => ($this->templateCodeHtml) ? '1' : '',
				),
				'defaultCharset' => 'utf-8',
				'encoding' => '8bit',
				'attach' => '',
				'alternateSubject' => 'TYPO3 :: commerce',
				'recipient' => '',
				'recipient_copy' =>  $template['BCC'],
				'fromEmail' => $senderemail,
				'fromName' => $sendername,
				'replyTo' => $this->cObj->conf['usermail.']['from'],
				'priority' => '3',
				'callLocation' => 'processOrdermails'
			);

			if ($template['otherreceiver'] != '') {
				$mailconf['recipient'] = $template['otherreceiver'];
				$this->ordermoveSendMail($mailconf, $orderdata, $template);
			} else {
				$mailconf['recipient'] = $this->customermailadress;
				$this->ordermoveSendMail($mailconf, $orderdata, $template);
			}
		}
	}

	/**
	 * Initial method for hook that will be performed after the Order
	 * will be moved to another Orderstate
	 *
	 * @param array &$orderdata Containing the orderdatea like UID and
	 * 		PID after moving
	 * @param array &$detaildata Containing the detaildata to Order like
	 * 		order_id and CustomerUIDs
	 * @return void
	 */
	public function moveOrders_preMoveOrder(&$orderdata, &$detaildata) {
		$this->processOrdermails($orderdata, $detaildata, 1);
	}

	/**
	 * Initial method for hook that will be performed before the Order
	 * will be moved to another Orderstate
	 *
	 * @param array &$orderdata Containing the orderdatea like UID and
	 * 		PID before moving
	 * @param array &$detaildata Containing the detaildata to Order like
	 * 		order_id and CustomerUIDs
	 * @return void
	 */
	public function moveOrders_postMoveOrder(&$orderdata, &$detaildata) {
		$this->processOrdermails($orderdata, $detaildata, 0);
	}

	/**
	 * Renders on Adress in the template
	 * This Method will not replace the Subpart, you have to replace your subpart
	 * in your template by you own
	 *
	 * @param array $addressArray Address (als Resultset from Select DB or Session)
	 * @param array $subpartMarker Template subpart
	 * @return string $content HTML-Content from the given Subpart.
	 */
	protected function makeAdressView($addressArray, $subpartMarker) {
		$template = $this->cObj->getSubpart($this->templateCode, $subpartMarker);
		$content = $this->cObj->substituteMarkerArray($template, $addressArray, '###|###', 1);
		return $content;
	}

	/**
	 * This Method generates a Mailcontent with $this->templatecode
	 * as Mailtemplate. First Line in Template represents the Mailsubject.
	 * The other required data can be queried from database by Parameters.
	 *
	 * @param string $orderUid The uid for the specified Order
	 * @param array $orderData Contaning additional data like Customer UIDs.
	 * @param string $templateCode
	 * @return string The built Mailcontent
	 */
	protected function generateMail($orderUid, $orderData, $templateCode) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$markerArray = array();
		$markerArray['###ORDERID###'] = $orderUid;

		/**
		 * Since The first line of the mail is the Subject, trim the template
		 */
		$content = ltrim($this->cObj->getSubpart($templateCode, '###MAILCONTENT###'));

			// Get The addresses
		$deliveryAdress = '';
		if ($orderData['cust_deliveryaddress']) {
			$data = $database->exec_SELECTgetSingleRow('*', 'tt_address', 'uid=' . (int) $orderData['cust_deliveryaddress']);
			if (is_array($data)) {
				$deliveryAdress = $this->makeAdressView($data, '###DELIVERY_ADDRESS###');
			}
		}
		$content = $this->cObj->substituteSubpart($content, '###DELIVERY_ADDRESS###', $deliveryAdress);

		$billingAdress = '';
		if ($orderData['cust_invoice']) {
			$data = $database->exec_SELECTgetSingleRow('*', 'tt_address', 'uid=' . (int) $orderData['cust_invoice']);
			if (is_array($data)) {
				$billingAdress = $this->makeAdressView($data, '###BILLING_ADDRESS###');
				$this->customermailadress = $data['email'];
			}
		}
		$content = $this->cObj->substituteSubpart($content, '###BILLING_ADDRESS###', $billingAdress);

		$invoicelist = '';
		$content = $this->cObj->substituteSubpart($content, '###INVOICE_VIEW###', $invoicelist);

		/**
		 * Hook for processing Marker Array
		 */
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce_ordermails/mod1/class.tx_commerce_moveordermail.php']['generateMail'])) {
			GeneralUtility::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce_ordermails/mod1/class.tx_commerce_moveordermail.php\'][\'generateMail\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Hook/OrdermailHooks.php\'][\'generateMail\']
			');
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce_ordermails/mod1/class.tx_commerce_moveordermail.php']['generateMail'] as $classRef) {
				$hookObj = GeneralUtility::getUserObj($classRef);
				if (method_exists($hookObj, 'ProcessMarker')) {
					$markerArray = $hookObj->ProcessMarker($markerArray, $this);
				}
			}
		}
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Hook/OrdermailHooks.php']['generateMail'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Hook/OrdermailHooks.php']['generateMail'] as $classRef) {
				$hookObj = GeneralUtility::getUserObj($classRef);
				if (method_exists($hookObj, 'ProcessMarker')) {
					$markerArray = $hookObj->ProcessMarker($markerArray, $this);
				}
			}
		}
		$content = $this->cObj->substituteMarkerArray($content, $markerArray);

		return ltrim($content);
	}
}
