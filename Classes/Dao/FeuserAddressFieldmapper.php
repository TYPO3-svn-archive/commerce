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
 * This class handles basic database storage by object mapping.
 * It defines how to insert, update, find and delete a transfer object in
 * the database.
 * The class needs a parser for object <-> model (transfer object) mapping.
 *
 * Class Tx_Commerce_Dao_FeuserAddressFieldmapper
 *
 * @author 2005-2012 Carsten Lausen <cl@e-netconsulting.de>
 */
class Tx_Commerce_Dao_FeuserAddressFieldmapper {
	/**
	 * Mapping
	 *
	 * @var string
	 */
	protected $mapping;

	/**
	 * Frontend user fields
	 *
	 * @var array
	 */
	protected $feuserFields = array();

	/**
	 * Address fields
	 *
	 * @var array
	 */
	protected $addressFields = array();

	/**
	 * Constructor
	 *
	 * @return self
	 */
	public function __construct() {
		$this->mapping = trim($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['feuser_address_mapping'], ' ;');
	}

	/**
	 * Getter
	 *
	 * @return array
	 */
	public function getAddressFields() {
		if (empty($this->addressFields)) {
			$this->explodeMapping();
		}

		return $this->addressFields;
	}

	/**
	 * Getter
	 *
	 * @return array
	 */
	public function getFeuserFields() {
		if (empty($this->feuserFields)) {
			$this->explodeMapping();
		}

		return $this->feuserFields;
	}

	/**
	 * Map feuser to address
	 *
	 * @param Tx_Commerce_Dao_FeuserDao $feuser Frontend user
	 * @param Tx_Commerce_Dao_AddressDao $address Address
	 *
	 * @return void
	 */
	public function mapFeuserToAddress(Tx_Commerce_Dao_FeuserDao &$feuser, Tx_Commerce_Dao_AddressDao &$address) {
		if (empty($this->feuserFields)) {
			$this->explodeMapping();
		}
		foreach ($this->feuserFields as $key => $field) {
			$address->set($this->addressFields[$key], $feuser->get($field));
		}
	}

	/**
	 * Map address to feuser
	 *
	 * @param Tx_Commerce_Dao_AddressDao $address Address
	 * @param Tx_Commerce_Dao_FeuserDao $feuser Frontend user
	 *
	 * @return void
	 */
	public function mapAddressToFeuser(Tx_Commerce_Dao_AddressDao &$address, Tx_Commerce_Dao_FeuserDao &$feuser) {
		if (empty($this->addressFields)) {
			$this->explodeMapping();
		}
		foreach ($this->addressFields as $key => $field) {
			$feuser->set($this->feuserFields[$key], $address->get($field));
		}
	}

	/**
	 * Explode mapping
	 *
	 * @return void
	 */
	protected function explodeMapping() {
		$map = explode(';', $this->mapping);
		foreach ($map as $singleMap) {
			$singleFields = explode(',', $singleMap);
			$this->feuserFields[] = $singleFields[0];
			$this->addressFields[] = $singleFields[1];
		}
	}
}
