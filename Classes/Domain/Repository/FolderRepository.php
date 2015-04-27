<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2011 Eric Frister <ef@marketing-factory.de>
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
 * Misc commerce db functions
 */
class Tx_Commerce_Domain_Repository_FolderRepository {
	/**
	 * Returns pidList of extension Folders
	 *
	 * @param string $module
	 * @return string commalist of PIDs
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, this wont get replaced as it was removed from the api
	 */
	public function getFolderPidList($module = 'commerce') {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return implode(',', array_keys(self::getFolders($module)));
	}

	/**
	 * Find the extension folders or create one.
	 *
	 * @param string $title Folder Title as named in pages table
	 * @param string $module Extension Moduke
	 * @param integer $pid Parent Page id
	 * @param string $parentTitle Parent Folder Title
	 * @return array
	 */
	public static function initFolders($title = 'Commerce', $module = 'commerce', $pid = 0, $parentTitle = '') {
			// creates a Commerce folder on the fly
			// not really a clean way ...
		if ($parentTitle) {
			$parentFolders = self::getFolders($module, $pid, $parentTitle);
			$currentParentFolders = current($parentFolders);
			$pid = $currentParentFolders['uid'];
		}

		$folders = self::getFolders($module, $pid, $title);
		if (!count($folders)) {
			self::createFolder($title, $module, $pid);
			$folders = self::getFolders($module, $pid, $title);
		}

		$currentFolder = current($folders);

		return array($currentFolder['uid'], implode(',', array_keys($folders)));
	}

	/**
	 * Find the extension folders
	 *
	 * @param string $module
	 * @param integer $pid
	 * @param string $title
	 * @return array rows of found extension folders
	 */
	public static function getFolders($module = 'commerce', $pid = 0, $title = '') {
		$row = self::getDatabaseConnection()->exec_SELECTgetSingleRow(
			'uid,pid,title',
			'pages',
			'doktype = 254 AND tx_graytree_foldername = \'' . strtolower($title) . '\' AND pid = ' . (int) $pid . ' AND module=\'' .
				$module . '\' ' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('pages')
		);

		return isset($row['uid']) ? array($row['uid'] => $row) : array();
	}

	/**
	 * Create your database table folder
	 * overwrite this if wanted
	 *
	 * @param string $title
	 * @param string $module
	 * @param integer $pid: ...
	 * @return integer
	 * @todo title aus extkey ziehen
	 * @todo sorting
	 */
	protected function createFolder($title = 'Commerce', $module = 'commerce', $pid = 0) {
		$fieldValues = array();
		$fieldValues['pid'] = $pid;
		$fieldValues['sorting'] = 10111;
		$fieldValues['perms_user'] = 31;
		$fieldValues['perms_group'] = 31;
		$fieldValues['perms_everybody'] = 31;
		$fieldValues['title'] = $title;

		// @todo MAKE IT tx_commerce_foldername
		$fieldValues['tx_graytree_foldername'] =  strtolower($title);
		$fieldValues['doktype'] = 254;
		$fieldValues['module'] = $module;
		$fieldValues['crdate'] = time();
		$fieldValues['tstamp'] = time();

		$this->getDatabaseConnection()->exec_INSERTquery('pages', $fieldValues);

		return $this->getDatabaseConnection()->sql_insert_id();
	}


	/**
	 * @return \TYPO3\CMS\Dbal\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}
}
