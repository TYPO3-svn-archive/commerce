<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2011 Erik Frister <typo3@marketing-factory.de>
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
 * Implements the data view of the leaf
 */
class Tx_Commerce_Tree_Leaf_Data extends Tx_Commerce_Tree_Leaf_Base {
	/**
	 * Complete Array of position IDs
	 *
	 * @var array
	 */
	protected $positionArray;

	/**
	 * Array with only the position uids of the current leaf
	 *
	 * @var array
	 */
	protected $positionUids;

	/**
	 * Holds an array with the positionUids per mount [mount] => array(pos1, pos2,...,posX)
	 *
	 * @var array
	 */
	protected $positionMountUids;

	/**
	 * Item UID of the Mount for this Data
	 *
	 * @var integer
	 */
	protected $bank;

	/**
	 * Name of the Table to read from
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * Array with the Mount IDs (UID of Items that act as mounts OR the root mount)
	 *
	 * @var array
	 */
	protected $mountIds;

	/**
	 * DB Table Statement
	 *
	 * @var string
	 */
	protected $from = '';

	/**
	 * DB Limit Statement
	 *
	 * @var string
	 */
	protected $limit = '';

	/**
	 * DB Order Statement
	 *
	 * @var string
	 */
	protected $order = '';

	/**
	 * Used to load additional fields - for extending classes
	 *
	 * @var string
	 */
	protected $extendedFields = '';

	/**
	 * WHERE-Clause of the SELECT; will be calculated depending on if we read them recursively or by Mountpoints
	 *
	 * @var string
	 */
	protected $whereClause;

	/**
	 * Default Fields that will be read
	 *
	 * @var string
	 */
	protected $defaultFields = 'uid, pid';

	/**
	 * field that will be aliased as item_parent; MANDATORY!
	 *
	 * @var string
	 */
	protected $item_parent = '';

	/**
	 * table to read the leafitems from
	 *
	 * @var string
	 */
	protected $itemTable;

	/**
	 * table that is to be used to find parent items
	 *
	 * @var string
	 */
	protected $mmTable;

	/**
	 * if no mm table is used, this field will be used to get the parents
	 *
	 * @var string
	 */
	protected $itemParentField;

	/**
	 * Flag if mm table is to be used or the parent field
	 *
	 * @var string
	 */
	protected $useMMTable;

	/**
	 * Array with uids to the uid_local and uid_foreign field if mm is used
	 *
	 * @var string
	 */
	protected $where;

	/**
	 * @var boolean
	 */
	protected $sorted = FALSE;

	/**
	 * @var array
	 */
	protected $sortedArray = NULL;

	/**
	 * Holds the records
	 *
	 * @var array
	 */
	protected $records = NULL;


	/**
	 * Returns the table name
	 *
	 * @return string Table name
	 */
	public function getTable() {
		return $this->table;
	}

	/**
	 * Returns the position Uids for the items
	 *
	 * @return array
	 */
	public function getPositionsUids() {
		return $this->positionUids;
	}

	/**
	 * Returns the positions for the supplied mount (has to be set by setBank)
	 *
	 * @return array
	 */
	public function getPositionsByMountpoint() {
		$ret = $this->positionMountUids[$this->bank];
		return ($ret != NULL) ? $ret : array();
	}

	/**
	 * Returns true if this leaf is currently expanded
	 *
	 * @param integer $uid uid of the current row
	 * @return boolean
	 */
	public function isExpanded($uid) {
		if (!is_numeric($uid)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('isExpanded (Tx_Commerce_Tree_Leaf_Data) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return FALSE;
		}
			// Check if the UID is in the Position-Array
		return (in_array($uid, $this->getPositionsByMountpoint()));
	}

	/**
	 * Sets the position Ids
	 *
	 * @return void
	 * @param array $positionIds - Array with the Category uids which are current positions of the user
	 */
	public function setPositions(&$positionIds) {
		if (!is_array($positionIds)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('setPositions (Tx_Commerce_Tree_Leaf_Data) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return;
		}
		$this->positionArray = $positionIds;
	}

	/**
	 * Returns an array of Positions
	 *
	 * @return array
	 * @param integer $index Index of this leaf
	 * @param array $indices Parent Indices
	 */
	public function getPositionsByIndices($index, $indices) {
		if (!is_numeric($index) || !is_array($indices)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('getPositionsByIndices (productdata) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return array();
		}

		$m = count($indices);

			// Construct the Array of Position Ids
		$firstIndex = (0 >= $m) ? $index : $indices[0];

			// normally we read the mounts
		$mounts = $this->mountIds;
		$l = count($mounts);

			// if we didn't find mounts, exit
		if ($l == 0) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('getPositionsByIndices (Tx_Commerce_Tree_Leaf_Data) cannot proceed because it did not find mounts', COMMERCE_EXTKEY, 3);
			}
			return array();
		}

		$positions = array();

		for ($i = 0; $i < $l; $i ++) {
			$posIds = $this->positionArray[$firstIndex][$mounts[$i]];

				// Go to the correct Leaf in the Positions
			if (0 < $m) {
					// Go to correct parentleaf
				for ($j = 1; $j < $m; $j ++) {
					$posIds = $posIds[$indices[$j]];
				}
					// select current leaf
				$posIds = $posIds[$index];
			}

				// If no Items are set for the current Leaf, skip it
			if (!is_array($posIds['items'])) {
				continue;
			}

				// Get the position uids
			$positionUids = array_keys($posIds['items']);

				// Store in the Mount - PosUids Array
			$this->positionMountUids[$mounts[$i]] = $positionUids;
				// Store in Array of all UIDS
			$positions = array_merge($positions, $positionUids);
		}

		$this->positionUids = $positions;

		return $positions;
	}

	/**
	 * Sets the bank
	 *
	 * @param integer $bank - Category UID of the Mount (aka Bank)
	 * @return void
	 */
	public function setBank($bank) {
		if (!is_numeric($bank)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('setBank (Tx_Commerce_Tree_Leaf_Data) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return;
		}
		$this->bank = $bank;
	}

	/**
	 * Returns the records
	 *
	 * @return array Records of the leaf
	 */
	public function getRecords() {
		return $this->records;
	}

	/**
	 * Returns the open uids of this leaf
	 *
	 * @return array Open uids
	 */
	public function getOpenRecordUids() {
		return $this->positionUids;
	}

	/**
	 * Returns the Uids of the records in an array
	 *
	 * @return array Uids of the records
	 */
	public function getRecordsUids() {
		if (!$this->isLoaded() || !is_array($this->records['uid'])) {
			return array();
		}

		return array_keys($this->records['uid']);
	}

	/**
	 * Returns whether this Tx_Commerce_Tree_Leaf_Data has been loaded
	 *
	 * @return boolean
	 */
	public function isLoaded() {
		return ($this->records != NULL);
	}

	/**
	 * Sorts the records to represent the linar structure of the tree
	 * Stores the resulting array in an internal variable
	 *
	 * @param integer $rootUid - UID of the Item that will act as the root to the tree
	 * @param integer $depth
	 * @param boolean $last
	 * @param integer $crazyRecursionLimiter
	 * @return void
	 */
	public function sort($rootUid, $depth = 0, $last = FALSE, $crazyRecursionLimiter = 999) {
		if (!is_numeric($rootUid) || !is_numeric($depth) || !is_numeric($crazyRecursionLimiter)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('sort (Tx_Commerce_Tree_Leaf_Data) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return;
		}

			// Return if the records are already sorted
		if ($this->sorted || $crazyRecursionLimiter <= 0) {
			return;
		}

		if (isset($this->records['uid'][$rootUid])) {

				// Place the current record in the array
			$entry = array();
			$entry['record'] = $this->records['uid'][$rootUid];
			$entry['depth']  = $depth;
			$entry['last']   = $last;

			$this->sortedArray[] = $entry;

				// Get the children and iterate
			$children = $this->getChildrenByPid($rootUid);

			$l = count($children);

			for ($i = 0; $i < $l; $i ++) {
				$this->sort($children[$i]['uid'], $depth + 1, ($i == $l - 1), $crazyRecursionLimiter - 1);
			}
		}

			// Set sorted to True to block further sorting - only after all recursion is done
		if (0 == $depth) {
			$this->sorted = TRUE;
		}
	}

	/**
	 * Returns the sorted array
	 * False if the data has not been sorted yet
	 *
	 * @return array|boolean
	 */
	public function &getSortedArray() {
		if (!$this->sorted) {
			return FALSE;
		}

		return $this->sortedArray;
	}

	/**
	 * Returns if the data has loaded any records
	 *
	 * @return boolean
	 */
	public function hasRecords() {
		if (!$this->isLoaded()) {
			return FALSE;
		}
		return (count($this->records['uid']) > 0 && count($this->records['pid']) > 0);
	}

	/**
	 * Returns a record from the 'uid' array
	 * Returns null if the index is not found
	 *
	 * @param integer $uid - UID for which we will look
	 * @return array
	 */
	public function &getChildByUid($uid) {
		if (!is_numeric($uid)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('getChildByUid (Tx_Commerce_Tree_Leaf_Data) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return NULL;
		}

		if (!is_array($this->records) || !isset($this->records['uid']) || !is_array($this->records['uid'])) {
			return NULL;
		}

		return $this->records['uid'][$uid];
	}

	/**
	 * Returns a subset of records from the 'pid' array
	 * Returns null if PID is not found
	 *
	 * @param integer $pid
	 * @return array
	 */
	public function &getChildrenByPid($pid) {
		if (!is_numeric($pid)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('getChildrenByPid (Tx_Commerce_Tree_Leaf_Data) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return NULL;
		}

		if (!is_array($this->records) || !isset($this->records['pid']) || !is_array($this->records['pid'])) {
			return NULL;
		}

		return $this->records['pid'][$pid];
	}

	/**
	 * Loads the records of a given query and stores it
	 *
	 * @return array Records array
	 */
	public function loadRecords() {
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/tree/class.leafData.php']['loadRecords'])) {
			t3lib_div::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/tree/class.leafData.php\'][\'loadRecords\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Tree/Leaf/Data.php\'][\'loadRecords\']
			');
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/tree/class.leafData.php']['loadRecords'] as $classRef) {
				$hookObj = &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'addExtendedFields')) {
					$this->extendedFields .= $hookObj->addExtendedFields($this->itemTable, $this->extendedFields);
				}
			}
		}
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Tree/Leaf/Data.php']['loadRecords'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Tree/Leaf/Data.php']['loadRecords'] as $classRef) {
				$hookObj = &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'addExtendedFields')) {
					$this->extendedFields .= $hookObj->addExtendedFields($this->itemTable, $this->extendedFields);
				}
			}
		}

			// Add the extended fields to the select statement
		$select = (is_string($this->extendedFields) && '' != $this->extendedFields) ?
			$this->defaultFields . ',' . $this->extendedFields :
			$this->defaultFields;

			// add item parent
		$select .= ',' . $this->item_parent . ' AS item_parent';

			// add the item search
		$where = '';
		if ($this->useMMTable) {
			$where .= ('' == $this->whereClause) ? '' : ' AND ' . $this->whereClause;
			$where .= ' AND (uid_foreign IN (' . $this->where['uid_foreign'] . ') OR uid_local IN (' . $this->where['uid_local'] . '))';
		} else {
			$where  = $this->whereClause;
			$where .= ('' == $this->whereClause) ? '' : ' AND ';
			$where .= '(' . $this->itemParentField . ' IN (' . $this->where[$this->itemParentField] . ') OR uid IN(' . $this->where['uid'] . '))';
		}

		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

			// exec the query
		if ($this->useMMTable) {
			$res = $database->exec_SELECT_mm_query($select, $this->itemTable, $this->mmTable, '', $where, '', $this->order, $this->limit);
		} else {
			$res = $database->exec_SELECTquery($select, $this->itemTable, $where, '', $this->order, $this->limit);
		}

		if ($database->sql_error()) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('loadRecords (Tx_Commerce_Tree_Leaf_Data) could not load records. Possible sql error. Empty rows returned.', COMMERCE_EXTKEY, 3);
			}
			return array();
		}

			// Will hold a record to check rights against after this loop.
		$checkRightRow = FALSE;

		$rows = array();
		while ($row = $database->sql_fetch_assoc($res)) {
				// get the version overlay if wanted
				// store parent item
			$parentItem = $row['item_parent'];
				// unset the pseudo-field (no pseudo-fields allowed for workspaceOL)
			unset($row['item_parent']);

			t3lib_BEfunc::workspaceOL($this->itemTable, $row);

			if (!is_array($row)) {
				debug('There was an error overlaying a record with its workspace version.');
				continue;
			} else {
					// write the pseudo field again
				$row['item_parent'] = $parentItem;
			}

				// the row will by default start with being the last node
			$row['lastNode'] = FALSE;

				// Set the row in the 'uid' part
			$rows['uid'][$row['uid']] = $row;

				// Set the row in the 'pid' part
			if (!isset($rows['pid'][$row['item_parent']])) {
				$rows['pid'][$row['item_parent']] = array($row);
			} else {
					// store
				$rows['pid'][$row['item_parent']][] = $row;
			}

			$checkRightRow = ($checkRightRow === FALSE) ? $row : $checkRightRow;
		}

		$database->sql_free_result($res);

			// Check perms on Commerce folders.
		if ($checkRightRow !== FALSE && !$this->checkAccess($this->itemTable, $checkRightRow)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('loadRecords (Tx_Commerce_Tree_Leaf_Data) could not load records because it doesnt have permissions on the commerce folder. Return empty array.', COMMERCE_EXTKEY, 3);
			}
			return array();
		}

			// Calculate the records which are last
		if (is_array($rows['pid'])) {
			$keys = array_keys($rows['pid']);
			$l = count($keys);
			$lastIndex = NULL;

			for ($i = 0; $i < $l; $i ++) {
				$lastIndex = end(array_keys($rows['pid'][$keys[$i]]));

					// Change last-attribute in 'uid' and 'pid' array - this now holds under which pids the record is last
				$uidItem = $rows['uid'][$rows['pid'][$keys[$i]][$lastIndex]['uid']];

				$rows['uid'][$rows['pid'][$keys[$i]][$lastIndex]['uid']]['lastNode'] =
					($uidItem['lastNode'] !== FALSE) ? $uidItem['lastNode'] . ',' . $keys[$i] : $keys[$i];
				$rows['pid'][$keys[$i]][$lastIndex]['lastNode'] = $keys[$i];
			}
		}

		$this->records = $rows;

		return $this->records;
	}

	/**
	 * Checks the page access rights (Code for access check mostly taken from alt_doc.php)
	 * as well as the table access rights of the user.
	 *
	 * @see tx_recycler
	 * @param string $table: The table to check access for
	 * @param string $row: The record uid of the table
	 * @return	boolean		Returns true is the user has access, or false if not
	 */
	public function checkAccess($table, $row) {
			// Checking if the user has permissions? (Only working as a precaution, because the final permission check
			// is always down in TCE. But it's good to notify the user on beforehand...)
			// First, resetting flags.
		$hasAccess = 0;
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = & $GLOBALS['BE_USER'];

		$calcPRec = $row;
		t3lib_BEfunc::fixVersioningPid($table, $calcPRec);
		if (is_array($calcPRec)) {
				// If pages:
			if ($table == 'pages') {
				$CALC_PERMS = $backendUser->calcPerms($calcPRec);
				$hasAccess = $CALC_PERMS & 2 ? 1 : 0;
			} else {
					// Fetching pid-record first.
				$CALC_PERMS = $backendUser->calcPerms(t3lib_BEfunc::getRecord('pages', $calcPRec['pid']));
				$hasAccess = $CALC_PERMS & 16 ? 1 : 0;
			}
		}

		if ($hasAccess) {
			$hasAccess = $backendUser->isInWebMount($calcPRec['pid'], '1=1');
		}

		return $hasAccess ? TRUE : FALSE;
	}
}

class_alias('Tx_Commerce_Tree_Leaf_Data', 'leafData');

?>