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

/**
 * Hook for the extension takeaday feuser
 * The method registrationProcess_afterSaveCreate() is called by save()
 * The method registrationProcess_afterSaveEdit() is called by save()
 *
 * This class handles frontend feuser updates
 *
 * Class Tx_Commerce_Hook_SrfeuserregisterPi1Hook
 *
 * @author 2005-2008 Carsten Lausen <cl@e-netconsulting.de>
 */
class Tx_Commerce_Hook_SrfeuserregisterPi1Hook {
	/**
	 * After save create
	 *
	 * Sr_feuser_register registration process after saving new dataset
	 *
	 * @param string $theTable Table
	 * @param array $dataArray Complete array of feuser fields
	 *
	 * @return void
	 */
	public function registrationProcess_afterSaveCreate($theTable, array $dataArray) {
		// notify observer
		Tx_Commerce_Dao_FeuserObserver::update('new', $dataArray['uid']);
	}

	/**
	 * After edit create
	 *
	 * Sr_feuser_register registration process after saving edited dataset
	 *
	 * @param string $theTable Table
	 * @param array $dataArray Complete array of feuser fields
	 *
	 * @return void
	 */
	public function registrationProcess_afterSaveEdit($theTable, array $dataArray) {
			// notify observer
		Tx_Commerce_Dao_FeuserObserver::update('update', $dataArray['uid']);
	}
}
