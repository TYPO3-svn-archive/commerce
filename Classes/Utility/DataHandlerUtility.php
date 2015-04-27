<?php
/***************************************************************
 *  Copyright notice
 *  (c) 2008-2011 Erik Frister <typo3@marketing-factory.de>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Implements the Commerce Engine
 */

unset($MCONF);
define('TYPO3_MOD_PATH', '../typo3conf/ext/commerce/Classes/Utility/');
$BACK_PATH = '../../../../../typo3/';

$MLANG['default']['ll_ref'] = 'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_cce.xml';
/** @noinspection PhpIncludeInspection */
require_once($BACK_PATH . 'init.php');
/** @noinspection PhpIncludeInspection */
require_once($BACK_PATH . 'template.php');

/** @var language $LANG */
$LANG->includeLLFile('EXT:commerce/Resources/Private/Language/locallang_mod_cce.xml');

/**
 * Class Tx_Commerce_Utility_DataHandlerUtility
 */
class Tx_Commerce_Utility_DataHandlerUtility {
	/**
	 * @var integer
	 */
	protected $id;

	// Internal, static: GPvar
	/**
	 * Redirect URL. Script will redirect to this location after performing operations (unless errors has occured)
	 *
	 * @var string
	 */
	protected $redirect;

	/**
	 * Boolean. If set, errors will be printed on screen instead of redirection. Should always be used, otherwise you will see no errors if they happen.
	 *
	 * @var boolean
	 */
	protected $prErr;

	/**
	 * Clipboard command array. May trigger changes in "cmd"
	 *
	 * @var array
	 */
	protected $CB;

	/**
	 * Verification code
	 *
	 * @var string
	 */
	protected $vC;

	/**
	 * Boolean. Update Page Tree Trigger. If set and the manipulated records are tx_commerce_categories then the update page tree signal will be set.
	 *
	 * @var boolean
	 */
	protected $uPT;

	/**
	 * Command
	 *
	 * @var string
	 */
	public $cmd;

	/**
	 * @var \TYPO3\CMS\Backend\Clipboard\Clipboard
	 */
	public $clipObj;

	/**
	 * @var string
	 */
	public $content;

	/**
	 * @var
	 */
	public $pageinfo;

	/**
	 * holds the sorting of copied record
	 *
	 * @var integer
	 */
	public $sorting;

	/**
	 * @var
	 */
	public $locales;

	/**
	 * @var array
	 */
	public $data;

	/**
	 * Document Template Object
	 *
	 * @var template
	 */
	public $doc;

	/**
	 * Commerce Core Engine
	 *
	 * @var Tx_Commerce_Utility_DataHandlerUtility
	 */
	public $cce;

	/**
	 * @var array
	 */
	protected $MOD_SETTINGS;

	/**
	 * @var array
	 */
	protected $MOD_MENU;

	/**
	 * Initialization of the class
	 *
	 * @return    void
	 */
	public function init() {
		// GPvars:
		$this->id = (int) \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id');
		$this->redirect = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('redirect');
		$this->prErr = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('prErr');
		$this->CB = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('CB');
		$this->vC = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('vC');
		$this->uPT = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('uPT');
		$this->sorting = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('sorting');
		$this->locales = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('locale');
		$this->cmd = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('cmd');
		$this->clipObj = NULL;
		$this->content = '';

		$cbString = (isset($this->CB['overwrite'])) ?
			'CB[overwrite]=' . rawurlencode($this->CB['overwrite']) . '&CB[pad]=' . $this->CB['pad'] :
			'CB[paste]=' . rawurlencode($this->CB['paste']) . '&CB[pad]=' . $this->CB['pad'];

		// Initializing document template object:
		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('template');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->docType = 'xhtml_trans';
		$this->doc->setModuleTemplate(PATH_TXCOMMERCE . 'Resources/Private/Backend/mod_index.html');
		$this->doc->loadJavascriptLib('contrib/prototype/prototype.js');
		$this->doc->loadJavascriptLib($this->doc->backPath . PATH_TXCOMMERCE_REL . 'Resources/Public/Javascript/copyPaste.js');
		$this->doc->form = '<form action="DataHandlerUtility.php?' . $cbString . '&vC=' . $this->vC . '&uPT=' . $this->uPT .
			'&redirect=' . rawurlencode($this->redirect) .
			'&prErr=' . $this->prErr . '&cmd=commit" method="post" name="localeform" id="localeform">';
	}

	/**
	 * Clipboard pasting and deleting.
	 *
	 * @return    void
	 */
	public function initClipboard() {
		if (is_array($this->CB)) {
			/** @var \TYPO3\CMS\Backend\Clipboard\Clipboard $clipObj */
			$clipObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Clipboard\\Clipboard');
			$clipObj->initializeClipboard();
			$clipObj->setCurrentPad($this->CB['pad']);
			$this->clipObj = $clipObj;
		}
	}

	/**
	 * Executing the posted actions ...
	 *
	 * @return void
	 */
	public function main() {
		/**
		 * @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication $backendUser
		 */
		$backendUser = $GLOBALS['BE_USER'];

		// Checking referer / executing
		$refInfo = parse_url(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_REFERER'));
		$httpHost = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY');

		if ($httpHost != $refInfo['host']
			&& $this->vC != $backendUser->veriCode()
			&& !$GLOBALS['TYPO3_CONF_VARS']['SYS']['doNotCheckReferer']
		) {
			// writelog($type, $action, $error, $details_nr,
			// $details, $data, $tablename, $recuid, $recpid)
			$backendUser->writelog(
				1, 2, 3, 0, 'Referer host "%s" and server host "%s" did not match and veriCode was not valid either!', array(
					$refInfo['host'],
					$httpHost
				)
			);
		} else {
			// get current item in clipboard
			$item = $this->clipObj->getSelectedRecord();
			$uidClip = $item['uid'];
			$uidTarget = 0;

			// check which command we actually want to execute
			$command = '';

			if (isset($this->CB['overwrite'])) {
				// overwrite a product
				$command = 'overwrite';

				$uidTarget = current(array_slice(explode('|', $this->CB['overwrite']), 1, 1));
			} elseif (isset($this->CB['paste'])) {
				// paste either a product into a category or a category into a category
				$command = ($this->clipObj->getSelectedRecord('tx_commerce_categories', $uidClip) == NULL) ?
					'pasteProduct' :
					'pasteCategory';

				$uidTarget = current(array_slice(explode('|', $this->CB['paste']), 1, 1));
			}

			if ($this->cmd == NULL) {
				// locale and sorting position haven't been chosen yet
				$this->showCopyWizard($uidClip, $uidTarget, $command);
			} else {
				$this->commitCommand($uidClip, $uidTarget, $command);
			}
		}
	}

	/**
	 * Shows the copy wizard
	 *
	 * @param integer $uidClip uid of the clipped item
	 * @param integer $uidTarget
	 * @param string $command
	 * @return void
	 */
	protected function showCopyWizard($uidClip, $uidTarget, $command) {
		/** @var language $language */
		$language = $GLOBALS['LANG'];

		$str = '';

		$this->pageinfo = Tx_Commerce_Utility_BackendUtility::readCategoryAccess(
			$uidTarget, Tx_Commerce_Utility_BackendUtility::getCategoryPermsClause(1)
		);

		$str .= $this->doc->header($language->getLL('Copy'));
		$str .= $this->doc->spacer(5);

		// flag if neither sorting nor localizations
		// are existing and we can immediately copy
		$noActionReq = FALSE;

		// First prepare user defined objects (if any)
		// for hooks which extend this function:
		$hookObjectsArr = array();
		if (is_array(
			$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/mod_cce/class.tx_commerce_cce_db.php']['copyWizardClass']
		)
		) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog(
				'
					hook
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/mod_cce/class.tx_commerce_cce_db.php\'][\'copyWizardClass\']
					is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Utility/DataHandlerUtility.php\'][\'copyWizard\']
				'
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/mod_cce/class.tx_commerce_cce_db.php']['copyWizardClass'] as $classRef) {
				$hookObjectsArr[] = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
			}
		}
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Utility/DataHandlerUtility.php']['copyWizard'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Utility/DataHandlerUtility.php']['copyWizard'] as $classRef) {
				$hookObjectsArr[] = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
			}
		}

		switch ($command) {
			case 'overwrite':
				// fallthrough
			case 'pasteProduct':
				// chose local to copy from product
				/** @var Tx_Commerce_Domain_Model_Product $product */
				$product = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Domain_Model_Product', $uidClip);
				$product->loadData();
				$prods = $product->getL18nProducts();

				if (0 != count($prods)) {

					$str .= '<h1>' . $language->getLL('copy.head.l18n') . '</h1>
						<h2>' . $language->getLL('copy.product') . ': ' . $product->getTitle() . '</h2>
						<ul>';

					// walk the l18n and get the selector box
					$l = count($prods);

					for ($i = 0; $i < $l; $i++) {
						$tmpProd = $prods[$i];

						$flag = ($tmpProd['flag'] != '') ?
							'<img src="' . $this->doc->backPath . 'gfx/flags/' . $tmpProd['flag'] . '" alt="Flag" />' :
							'';

						$str .= '<li><input type="checkbox" name="locale[]" id="loc_' . $tmpProd['uid'] . '" value="' .
							$tmpProd['sys_language'] . '" /><label for="loc_' . $tmpProd['uid'] . '">' . $flag .
							$tmpProd['title'] . '</label></li>';
					}

					$str .= '</ul>';
				}

				$records = array();
				// chose sorting position
				if ($command != 'overwrite') {
					// Initialize tree object:
					/** @var Tx_Commerce_Tree_Leaf_ProductData $treedb */
					$treedb = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Tree_Leaf_ProductData');
					$treedb->init();

					$records = $treedb->getRecordsDbList($uidTarget);
				}
				$l = count($records['pid'][$uidTarget]);

				// Hook: beforeFormClose
				$userIgnoreClose = FALSE;

				foreach ($hookObjectsArr as $hookObj) {
					if (method_exists($hookObj, 'beforeFormClose')) {
						// set $user_ignoreClose to true if you want to
						// force the script to print out the execute button
						$str .= $hookObj->beforeFormClose($uidClip, $uidTarget, $command, $userIgnoreClose);
					}
				}

				if (0 >= $l && (0 != count($prods) || $userIgnoreClose)) {
					// no child object - sorting position is irrelevant - just print
					// a submit button and notify users that there are not products
					// in the category yet
					$str .= '<input type="submit" value="' . $language->getLL('copy.submit') . '" />';
				} elseif (0 < $l) {
					// at least 1 item - offer choice
					$icon = '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg(
							$this->doc->backPath, 'gfx/newrecord_marker_d.gif', 'width="281" height="8"'
						) . ' alt="" title="Insert the category" />';
					$prodIcon = \TYPO3\CMS\Backend\Utility\IconUtility::getIconImage(
						'tx_commerce_products', array('uid' => $uidTarget), $this->doc->backPath, 'align="top" class="c-recIcon"'
					);
					$str .= '<h1>' . $language->getLL('copy.position') . '</h1>';

					$str .= '<span class="nobr"><a href="javascript:void(0)" onclick="submitForm(' .
						$records['pid'][$uidTarget][0]['uid'] . ')">' . $icon . '</a></span><br />';

					for ($i = 0; $i < $l; $i++) {
						$record = $records['pid'][$uidTarget][$i];

						$str .= '<span class="nobr">' . $prodIcon . $record['title'] . '</span><br />';
						$str .= '<span class="nobr"><a href="javascript:void(0)" onclick="submitForm(-' .
							$record['uid'] . ')">' . $icon . '</a></span><br />';
					}
				} else {
					$noActionReq = TRUE;
				}
				break;

			case 'pasteCategory':
				// chose locale to copy from category
				/** @var Tx_Commerce_Domain_Model_Category $cat */
				$cat = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Domain_Model_Category', $uidClip);
				$cat->loadData();
				$cats = $cat->getL18nCategories();

				if (0 != count($cats)) {
					$str .= '<h1>' . $language->getLL('copy.head.l18n') . '</h1>
						<h2>' . $language->getLL('copy.category') . ': ' . $cat->getTitle() . '</h2>
						<ul>';

					// walk the l18n and get the selector box
					$l = count($cats);

					for ($i = 0; $i < $l; $i++) {
						$tmpCat = $cats[$i];

						$flag = ($tmpCat['flag'] != '') ?
							'<img src="' . $this->doc->backPath . 'gfx/flags/' . $tmpCat['flag'] . '" alt="Flag" />' :
							'';

						$str .= '<li><input type="checkbox" name="locale[]" id="loc_' . $tmpCat['uid'] . '" value="' .
							$tmpCat['sys_language'] . '" /><label for="loc_' . $tmpCat['uid'] . '">' . $flag . $tmpCat['title'] .
							'</label></li>';
					}

					$str .= '</ul>';
				}

				// chose sorting position
				// Initialize tree object:
				/** @var Tx_Commerce_Tree_Leaf_CategoryData $treedb */
				$treedb = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Tree_Leaf_CategoryData');
				$treedb->init();

				$records = $treedb->getRecordsDbList($uidTarget);

				$l = count($records['pid'][$uidTarget]);

				// Hook: beforeFormClose
				$userIgnoreClose = FALSE;

				foreach ($hookObjectsArr as $hookObj) {
					if (method_exists($hookObj, 'beforeFormClose')) {
						$str .= $hookObj->beforeFormClose($uidClip, $uidTarget, $command, $userIgnoreClose);
					}
				}

				if (0 == $l && (0 != count($cats) || $userIgnoreClose)) {
					// no child object - sorting position is irrelevant - just print a submit button
					$str .= '<input type="submit" value="' . $language->getLL('copy.submit') . '" />';
				} elseif (0 < $l) {
					// at least 1 item - offer choice
					$icon = '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg(
							$this->doc->backPath, 'gfx/newrecord_marker_d.gif', 'width="281" height="8"'
						) . ' alt="" title="Insert the category" />';
					$catIcon = \TYPO3\CMS\Backend\Utility\IconUtility::getIconImage(
						'tx_commerce_categories', array('uid' => $uidTarget), $this->doc->backPath,
						'align="top" class="c-recIcon"'
					);
					$str .= '<h1>' . $language->getLL('copy.position') . '</h1>';

					$str .= '<span class="nobr"><a href="javascript:void(0)" onclick="submitForm(' .
						$records['pid'][$uidTarget][0]['uid'] . ')">' . $icon . '</a></span><br />';

					for ($i = 0; $i < $l; $i++) {
						$record = $records['pid'][$uidTarget][$i];

						$str .= '<span class="nobr">' . $catIcon . $record['title'] . '</span><br />
							<span class="nobr"><a href="javascript:void(0)" onclick="submitForm(-' .
							$record['uid'] . ')">' . $icon . '</a></span><br />';
					}
				} else {
					$noActionReq = TRUE;
				}
				break;

			default:
				die('unknown command');
		}

		// skip transforming and execute the command
		// if there are no locales and no positions
		if ($noActionReq) {
			$this->commitCommand($uidClip, $uidTarget, $command);

			return;
		}

		// Hook: beforeTransform
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'beforeTransform')) {
				$str .= $hookObj->beforeTransform($uidClip, $uidTarget, $command);
			}
		}

		$this->content .= $str;

		$markers = array(
			'CSH' => '',
			'CONTENT' => $this->content,
			'CATINFO' => '',
			'CATPATH' => '',
		);
		$markers['FUNC_MENU'] = $this->doc->funcMenu(
			'', \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu(
				$this->id, 'SET[mode]', $this->MOD_SETTINGS['mode'], $this->MOD_MENU['mode']
			)
		);

		$this->content = $this->doc->startPage($language->getLL('Copy'));
		$this->content .= $this->doc->moduleBody($this->pageinfo, array(), $markers);
		$this->content .= $this->doc->endPage();
	}

	/**
	 * Commits the given command
	 *
	 * @param integer $uidClip
	 * @param integer $uidTarget
	 * @param string $command
	 * @return void
	 */
	protected function commitCommand($uidClip, $uidTarget, $command) {
		// First prepare user defined objects (if any) for hooks which extend this function:
		$hookObjectsArr = array();
		if (is_array(
			$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/mod_cce/class.tx_commerce_cce_db.php']['commitCommandClass']
		)
		) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog(
				'
								hook
								$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/mod_cce/class.tx_commerce_cce_db.php\'][\'storeDataToDatabase\']
								is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
								$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Utility/DataHandlerUtility.php\'][\'commitCommand\']
							'
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/mod_cce/class.tx_commerce_cce_db.php']['commitCommandClass'] as $classRef) {
				$hookObjectsArr[] = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
			}
		}
		if (is_array(
			$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Utility/DataHandlerUtility.php']['commitCommand']
		)
		) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Utility/DataHandlerUtility.php']['commitCommand'] as $classRef) {
				$hookObjectsArr[] = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
			}
		}
		// Hook: beforeCommit
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'beforeCommit')) {
				$hookObj->beforeCommit($uidClip, $uidTarget, $command);
			}
		}

		// we got all info we need - commit command
		switch ($command) {
			case 'overwrite':
				Tx_Commerce_Utility_BackendUtility::overwriteProduct($uidClip, $uidTarget, $this->locales);
				break;

			case 'pasteProduct':
				Tx_Commerce_Utility_BackendUtility::copyProduct($uidClip, $uidTarget, FALSE, $this->locales, $this->sorting);
				break;

			case 'pasteCategory':
				Tx_Commerce_Utility_BackendUtility::copyCategory($uidClip, $uidTarget, $this->locales, $this->sorting);
				break;

			default:
				die('unknown command');
				break;
		}

		// Hook: afterCommit
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'afterCommit')) {
				$hookObj->afterCommit($uidClip, $uidTarget, $command);
			}
		}

		// Update page tree?
		if ($this->uPT
			&& (isset($this->data['tx_commerce_categories']) || isset($this->cmd['tx_commerce_categories']))
			&& (isset($this->data['tx_commerce_products']) || isset($this->cmd['tx_commerce_products']))
		) {
			\TYPO3\CMS\Backend\Utility\BackendUtility::setUpdateSignal('updateFolderTree');
		}
	}

	/**
	 * Redirecting the user after the processing has been done.
	 * Might also display error messages directly, if any.
	 *
	 * @return void
	 */
	public function finish() {
		if ($this->content != '') {
			echo $this->content;
		} elseif ($this->redirect) {
			header('Location: ' . \TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl($this->redirect));
		}
	}
}

// Make instance:
/** @var Tx_Commerce_Utility_DataHandlerUtility $SOBE */
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Utility_DataHandlerUtility');
$SOBE->init();
$SOBE->initClipboard();
$SOBE->main();
$SOBE->finish();
