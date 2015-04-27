<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c)  2005 Robert Lemke <robert@typo3.org>, Franz Ripfel <typo3@abezet.de>
 *  (c)  2010 Ingo Schmitt <is@marketing-factory.de>
 * All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Code library for display of different currencies
 * widely used in EXT: commerce
 *
 * @author Robert Lemke <robert@typo3.org>
 * @author Franz Ripfel <typo3@abezet.de>
 * @author Ingo Schmitt <is@marketing-factory.de>
 */
class Tx_Commerce_ViewHelpers_Money {
	/**
	 * use this function from TS, example:
	 * includeLibs.moneylib = EXT:commerce/Classes/ViewHelpers/Money.php
	 * price_net = stdWrap
	 * price_net.postUserFunc = Tx_Commerce_ViewHelpers_Money->user_tsFormat
	 * price_net.postUserFunc.currency = EUR
	 * price_net.postUserFunc.withSymbol = 0
	 *
	 * @param string $content
	 * @param string $conf
	 * @return string representation of the amount including currency symbol(s)
	 */
	public function user_tsFormat($content, $conf) {
		$withSymbol = is_null($conf['withSymbol']) ? TRUE : $conf['withSymbol'];
		return (string)self::format($content, $conf['currency'], $withSymbol);
	}

	/**
	 * Returns the given amount as a formatted string according to the
	 * given currency.
	 * IMPORTANT NOTE:
	 * The amount must always be the smallest unit passed as a string
	 * or integer! It is a very bad idea to use float for monetary
	 * calculations if you need exact values, therefore
	 * this method won't accept float values.
	 * Examples:
	 *        format (500, 'EUR');        --> '5,00 EUR'
	 *        format (4.23, 'EUR');    --> FALSE
	 *        format ('872331', 'EUR');    --> '8.723,31 EUR'
	 *
	 * @param int|string $amount Amount to be formatted. Must be the smalles unit
	 * @param string $currency ISO 3 letter code of the currency, for example "EUR"
	 * @param boolean $withSymbol If set the currency symbol will be rendered
	 * @return string|bool String representation of the amount including currency
	 * 	symbol(s) or FALSE if $amount was of the type float
	 */
	public static function format($amount, $currency, $withSymbol = TRUE) {
		/** @var \TYPO3\CMS\Core\Database\DatabaseConnection $database */
		$database = $GLOBALS['TYPO3_DB'];

		if (is_float($amount)) {
			return FALSE;
		}

		$row = $database->exec_SELECTgetSingleRow(
			'cu_symbol_left, cu_symbol_right, cu_sub_symbol_left, cu_sub_symbol_right, cu_decimal_point, cu_thousands_point,
				cu_decimal_digits, cu_sub_divisor',
			'static_currencies',
			'cu_iso_3 = ' . $database->fullQuoteStr(strtoupper($currency), 'static_currencies')
		);

		if (!is_array($row)) {
			return FALSE;
		}

		$formattedAmount = number_format(
			$amount / $row['cu_sub_divisor'], $row['cu_decimal_digits'], $row['cu_decimal_point'], $row['cu_thousands_point']
		);

		if ($withSymbol) {
			$wholeString = $formattedAmount;
			if (!empty($row['cu_symbol_left'])) {
				$wholeString = $row['cu_symbol_left'] . chr(32) . $wholeString;
			}
			if (!empty($row['cu_symbol_right'])) {
				$wholeString .= chr(32) . $row['cu_symbol_right'];
			}
		} else {
			$wholeString = $formattedAmount;
		}

		return $wholeString;
	}
}
