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
 * Implements the Tx_Commerce_Tree_Leaf_View for the Category
 */
class Tx_Commerce_ViewHelpers_Browselinks_CategoryView extends Tx_Commerce_Tree_Leaf_View {
	/**
	 * DB Table ##isnt this read automatically?###
	 *
	 * @var string
	 */
	protected $table = 'tx_commerce_categories';

	/**
	 * @var string
	 */
	protected $domIdPrefix = 'txcommerceCategory';

	/**
	 * the linked category
	 *
	 * @var integer
	 */
	protected $openCat = 0;

	/**
	 * returns the link from the tree used to jump to a destination
	 *
	 * @param array $row - Array with the ID Information
	 * @return string
	 */
	public function getJumpToParam($row) {
		if (!is_array($row)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('getJumpToParam (Tx_Commerce_Tree_Leaf_View) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return '';
		}

		return 'commerce:tx_commerce_categories:' . $row['uid'];
	}

	/**
	 * @param $uid
	 * @return void
	 */
	public function setOpenCategory($uid) {
		$this->openCat = $uid;
	}

	/**
	 * Wrapping $title in a-tags.
	 *
	 * @param string $title string
	 * @param string $row record
	 * @param integer $bank pointer (which mount point number)
	 * @return	string
	 * @access private
	 */
	public function wrapTitle($title, $row, $bank = 0) {
		if (!is_array($row) || !is_numeric($bank)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('wrapTitle (Tx_Commerce_Tree_Leaf_View) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return '';
		}

			// Max. size for Title of 30
		$title = ('' != $title) ? t3lib_div::fixed_lgd_cs($title, 30) : $this->getLL('leaf.noTitle');

		$aOnClick = 'return link_commerce(\'' . $this->getJumpToParam($row) . '\');';
		$style = ($row['uid'] == $this->openCat && 0 != $this->openCat) ? 'style="color: red; font-weight: bold"' : '';
		$res = (($this->noRootOnclick && 0 == $row['uid']) || $this->noOnclick) ?
			$title :
			'<a href="#" onclick="' . htmlspecialchars($aOnClick) . '" ' . $style . '>' . $title . '</a>';

		return $res;
	}
}
