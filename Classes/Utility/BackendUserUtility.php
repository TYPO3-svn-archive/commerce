<?php
namespace CommerceTeam\Commerce\Utility;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2012 Thomas Hempel <thomas@work.de>
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
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * A metaclass for creating inputfield fields in the backend.
 */
class BackendUserUtility implements SingletonInterface {
	/**
	 * Returns a combined binary representation of the current users permissions
	 * for the page-record, $row. The perms for user, group and everybody is
	 * OR'ed together (provided that the page-owner is the user
	 * and for the groups that the user is a member of the group.
	 * If the user is admin, 31 is returned	(full permissions for all five flags)
	 *
	 * @param array $row Input page row with all perms_* fields available.
	 * @return integer Bitwise representation of the users permissions in
	 * 	relation to input page row, $row
	 */
	public function calcPerms($row) {
		$backendUser = $this->getBackendUser();
		// Return 31 for admin users.
		if ($backendUser->isAdmin()) {
			return 31;
		}
		// Return 0 if page is not within the allowed web mount
		if (!$this->isInWebMount($row['uid'])) {
			return 0;
		}
		$out = 0;
		if (
			isset($row['perms_userid']) && isset($row['perms_user'])
			&& isset($row['perms_groupid']) && isset($row['perms_group'])
			&& isset($row['perms_everybody']) && isset($backendUser->groupList)
		) {
			if ($backendUser->user['uid'] == $row['perms_userid']) {
				$out |= $row['perms_user'];
			}
			if ($backendUser->isMemberOfGroup($row['perms_groupid'])) {
				$out |= $row['perms_group'];
			}
			$out |= $row['perms_everybody'];
		}
		return $out;
	}

	/**
	 * Checks if the page id, $id, is found within the webmounts set up for the user.
	 * This should ALWAYS be checked for any page id a user works with, whether it's about reading, writing or whatever.
	 * The point is that this will add the security that a user can NEVER touch parts outside his mounted
	 * pages in the page tree. This is otherwise possible if the raw page permissions allows for it.
	 * So this security check just makes it easier to make safe user configurations.
	 * If the user is admin OR if this feature is disabled
	 * (fx. by setting TYPO3_CONF_VARS['BE']['lockBeUserToDBmounts']=0) then it returns "1" right away
	 * Otherwise the function will return the uid of the webmount which was first found in the rootline of the input page $id
	 *
	 * @param integer $id Page ID to check
	 * @param string $readPerms Content of "->getPagePermsClause(1)" (read-permissions). If not set, they will be internally calculated (but if you have the correct value right away you can save that database lookup!)
	 * @param bool|int $exitOnError If set, then the function will exit with an error message.
	 * @throws \RuntimeException
	 * @return int|NULL The page UID of a page in the rootline that matched a mount point
	 * @todo Define visibility
	 */
	public function isInWebMount($id, $readPerms = '', $exitOnError = 0) {
		if (!$GLOBALS['TYPO3_CONF_VARS']['BE']['lockBeUserToDBmounts'] || $this->getBackendUser()->isAdmin()) {
			return 1;
		}
		$id = (int)$id;
		// Check if input id is an offline version page
		// in which case we will map id to the online version:
		$checkRec = BackendUtility::getRecord('tx_commerce_categories', $id, 'uid,t3ver_oid');
		if ($checkRec['uid'] == -1) {
			$id = (int)$checkRec['t3ver_oid'];
		}
		if (!$readPerms) {
			$readPerms = $this->getPagePermsClause(1);
		}
		if ($id > 0) {
			$wM = $this->returnWebmounts();
			$rL = $this->beGetRootLine($id, ' AND ' . $readPerms);
			foreach ($rL as $v) {
				if ($v['uid'] && in_array($v['uid'], $wM)) {
					return $v['uid'];
				}
			}
		}
		if ($exitOnError) {
			throw new \RuntimeException('Access Error: This page is not within your DB-mounts', 1294586445);
		}
		return NULL;
	}

	/**
	 * Returns a WHERE-clause for the pages-table where user permissions
	 * according to input argument, $perms, is validated.
	 * $perms is the "mask" used to select. Fx. if $perms is 1 then
	 * you'll get all pages that a user can actually see!
	 * 2^0 = show (1)
	 * 2^1 = edit (2)
	 * 2^2 = delete (4)
	 * 2^3 = new (8)
	 * If the user is 'admin' " 1=1" is returned (no effect)
	 * If the user is not set at all (->user is not an array),
	 * then " 1=0" is returned (will cause no selection results at all)
	 * The 95% use of this function is "->getPagePermsClause(1)" which will
	 * return WHERE clauses for *selecting* pages in backend listings
	 * - in other words this will check read permissions.
	 *
	 * @param integer $perms Permission mask to use, see function description
	 * @return string Part of where clause. Prefix " AND " to this.
	 * @todo Define visibility
	 */
	public function getPagePermsClause($perms) {
		if (is_array($this->getBackendUser()->user)) {
			$backenduser = $this->getBackendUser();
			if ($backenduser->isAdmin()) {
				return ' 1=1';
			}
			$perms = (int)$perms;
			// Make sure it's integer.
			$str = ' ( (tx_commerce_categories.perms_everybody & ' . $perms . ' = ' . $perms .
				') OR (tx_commerce_categories.perms_userid = ' . $backenduser->user['uid'] .
				' AND tx_commerce_categories.perms_user & ' . $perms . ' = ' . $perms . ')';

			// User
			if ($backenduser->groupList) {
				// Group (if any is set)
				$str .= ' OR (tx_commerce_categories.perms_groupid in (' . $backenduser->groupList .
					') AND tx_commerce_categories.perms_group & ' . $perms . ' = ' . $perms . ')';
			}
			$str .= ')';

			return $str;
		}
		return ' 1=0';
	}

	/**
	 * Returns an array with the webmounts.
	 * If no webmounts, and empty array is returned.
	 * NOTICE: Deleted tx_commerce_categories WILL NOT be filtered out! So
	 * if a mounted page has been deleted
	 *         it is STILL coming out as a webmount.
	 * This is not checked due to performance.
	 *
	 * @return array
	 * @todo Define visibility
	 */
	protected function returnWebmounts() {
		$groups = $this->getDatabaseConnection()->exec_SELECTgetRows(
			'tx_commerce_mountpoints',
			'be_groups',
			'uid IN (' . $this->getBackendUser()->groupList . ')'
		);

		$mountPoints = array();
		foreach ($groups as $group) {
			$mount = current($group);
			if (!empty($mount)) {
				$mountPoints[] = $mount;
			}
		}

		return $mountPoints;
	}

	/**
	 * Returns what is called the 'RootLine'. That is an array with information
	 * about the page records from a page id ($uid) and back to the root.
	 * By default deleted pages are filtered.
	 * This RootLine will follow the tree all the way to the root. This is
	 * opposite to another kind of root line known from the frontend where the
	 * rootline stops when a root-template is found.
	 *
	 * @param integer $uid Page id for which to create the root line.
	 * @param string $clause Clause can be used to select other criteria. It
	 * 	would typically be where-clauses that stops the process if we meet a
	 * 	page, the user has no reading access to.
	 * @param boolean $workspaceOverlay If TRUE, version overlay is applied. This
	 * 	must be requested specifically because it is usually only wanted when
	 * 	the rootline is used for visual output while for permission checking
	 * 	you want the raw thing!
	 * @return array Root line array, all the way to the page tree root
	 * 	(or as far as $clause allows!)
	 */
	protected function beGetRootLine($uid, $clause = '') {
		static $categoryRootlineCache = array();

		if (is_array($categoryRootlineCache[$uid])) {
			$output = $categoryRootlineCache[$uid];
		} else {
			/** @var \Tx_Commerce_Domain_Repository_CategoryRepository $repository */
			$repository = GeneralUtility::makeInstance('Tx_Commerce_Domain_Repository_CategoryRepository');
			$output = $repository->getCategoryRootline((int) $uid, $clause);

			$categoryRootlineCache[$uid] = $output;
		}

		return $output;
	}


	/**
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}

	/**
	 * @return \TYPO3\CMS\Dbal\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}
}