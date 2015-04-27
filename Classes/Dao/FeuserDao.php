<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2011 Carsten Lausen <cl@e-netconsulting.de>
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
 * feuser Dao
 * This class handles object persistence using the Dao design pattern.
 * It extends the basic Dao.
 */
class Tx_Commerce_Dao_FeuserDao extends Tx_Commerce_Dao_BasicDao {
	/**
	 * Initialization
	 *
	 * @return void
	 */
	protected function init() {
		$this->parser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Dao_FeuserDaoParser');
		$this->mapper = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Dao_FeuserDaoMapper', $this->parser);
		$this->object = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Dao_FeuserDaoObject');
	}
}
