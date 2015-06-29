<?php
namespace CommerceTeam\Commerce\Payment\Ccvs;
/**
 * Credit Card Validation Solution, PHP Edition.
 * <p>Ensures credit card numbers are keyed in correctly.</p>
 * <p>Complete usage information is in the validateCreditCard() method.</p>
 * <p>Credit Card Validation Solution is a trademark of The Analysis and
 * Solutions Company.</p>
 * <pre>
 * ======================================================================
 * SIMPLE PUBLIC LICENSE                        VERSION 1.1   2003-01-21
 * Copyright (c) The Analysis and Solutions Company
 * http://www.analysisandsolutions.com/
 * 1.  Permission to use, copy, modify, and distribute this software and
 * its documentation, with or without modification, for any purpose and
 * without fee or royalty is hereby granted, provided that you include
 * the following on ALL copies of the software and documentation or
 * portions thereof, including modifications, that you make:
 *     a.  The full text of this license in a location viewable to users
 *     of the redistributed or derivative work.
 *     b.  Notice of any changes or modifications to the files,
 *     including the date changes were made.
 * 2.  The name, servicemarks and trademarks of the copyright holders
 * may NOT be used in advertising or publicity pertaining to the
 * software without specific, written prior permission.
 * 3.  Title to copyright in this software and any associated
 * documentation will at all times remain with copyright holders.
 * 4.  THIS SOFTWARE AND DOCUMENTATION IS PROVIDED "AS IS," AND
 * COPYRIGHT HOLDERS MAKE NO REPRESENTATIONS OR WARRANTIES, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO, WARRANTIES OF MERCHANTABILITY
 * OR FITNESS FOR ANY PARTICULAR PURPOSE OR THAT THE USE OF THE SOFTWARE
 * OR DOCUMENTATION WILL NOT INFRINGE ANY THIRD PARTY PATENTS,
 * COPYRIGHTS, TRADEMARKS OR OTHER RIGHTS.
 * 5.  COPYRIGHT HOLDERS WILL NOT BE LIABLE FOR ANY DAMAGES, INCLUDING
 * BUT NOT LIMITED TO, DIRECT, INDIRECT, SPECIAL OR CONSEQUENTIAL,
 * ARISING OUT OF ANY USE OF THE SOFTWARE OR DOCUMENTATION.
 * ======================================================================
 * </pre>
 *
 * @see        CreditCardValidationSolution::validateCreditCard()
 * @package    CreditCardValidationSolution
 * @author     Daniel Convissor <danielc@analysisandsolutions.com>
 * @copyright  The Analysis and Solutions Company, 2002-2006
 * @version    $Name: rel-5-14 $ $Id: ccvs.inc,v 1.25 2009-02-06 04:41:22 danielc Exp $
 * @link       http://www.analysisandsolutions.com/software/ccvs/ccvs.htm
 */

/**
 * Ensures credit card information is keyed in correctly.
 * <p>Complete usage information is in the validateCreditCard() method.</p>
 *
 * @see        validateCreditCard()
 * @package    CreditCardValidationSolution
 * @author     Daniel Convissor <danielc@analysisandsolutions.com>
 * @copyright  The Analysis and Solutions Company, 2002-2006
 * @version    $Name: rel-5-14 $
 * @link       http://www.analysisandsolutions.com/software/ccvs/ccvs.htm
 * @license    http://www.analysisandsolutions.com/software/license.htm Simple Public License
 */
class CreditCardValidationSolution {
	/**
	 * The credit card number with all non-numeric characters removed.
	 *
	 * @var  string
	 */
	public $CCVSNumber = '';

	/**
	 * @var string
	 */
	public $CCVSCheckNumber = '';

	/**
	 * The first four digits of the card.
	 *
	 * @var string
	 */
	public $CCVSNumberLeft = '';

	/**
	 * The card's last four digits.
	 *
	 * @var string
	 */
	public $CCVSNumberRight = '';

	/**
	 * The name of the type of card presented.
	 * <p>Automatically determined from the first four digits of the
	 * card number.</p>
	 *
	 * @var string
	 */
	public $CCVSType = '';

	/**
	 * The card's expiration date.
	 * <p>Presented only if the <var>RequireExp</var> parameter is
	 * <kbd>Y</kbd> and there are no other problems with the card
	 * number, this variable contains the expiration date in
	 * <samp>MMYY</samp> format.</p>
	 *
	 * @var  string
	 */
	public $CCVSExpiration = '';

	/**
	 * String explaining the first problem detected, if any.
	 *
	 * @var  string
	 */
	public $CCVSError = '';

	/**
	 * Language service
	 *
	 * @var \TYPO3\CMS\Lang\LanguageService
	 */
	protected $language;

	/**
	 * Constructor
	 *
	 * @return self
	 */
	public function __construct() {
		$this->language = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Lang\\LanguageService');
		if (is_object($this->getBackendUser())) {
			$languageKey = $this->getBackendUser()->uc['lang'];
		} else {
			$languageKey = $this->getFrontendController()->config['config']['language'];
		}
		$this->language->init($languageKey);
		$this->language->includeLLFile('EXT:commerce/Resources/Private/Language/locallang_ccsv.xml');
	}

	/**
	 * Ensures credit card information is keyed in correctly.
	 * <p>Checks that the length is correct, the first four digits are
	 * within accepted ranges, the number passes the Mod 10 / Luhn
	 * checksum algorithm and that you accept the given type of card. It
	 * also determines the card's type via the number's first four digits.</p>
	 * <p>The procedure has the option to check the card's expiration date.</p>
	 * <p>Error messages are internationalized through use of variables
	 * defined by files in the <kbd>./language</kbd> subdirectory.  These
	 * files are named after their ISO 639-1 two letter language code.
	 * The language used depends on the code put in the
	 * <var>$Language</var> parameter.</p>
	 * <p>Just to be clear, this process does not check with banks or
	 * credit card companies to see if the card number given is actually
	 * associated with a good account. It just checks to see if the
	 * number matches the expected format.</p>
	 * <p>Warning: this function uses exact number ranges as part of
	 * the validation process.  These ranges are current as of
	 * 30 July 2002.  If presently undefined ranges come into use
	 * in the future, this program will improperly deject card numbers
	 * in such ranges, rendering an error saying "First four digits
	 * indicate unknown card type."  If this happens while entering a
	 * card and type you KNOW are valid, please contact us so we can
	 * update the ranges.</p>
	 * <p>This function requires PHP to be at version 4.0 or above.</p>
	 * <p>Please make a donation to support our open source development.
	 * Update notifications are sent to people who make donations that exceed
	 * the small registration threshold.  See the link below.</p>
	 * <p>Credit Card Validation Solution is a trademark of The Analysis and
	 * Solutions Company.</p>
	 * <p>Several people deserve praise for the Credit Card Validation
	 * Solution. I learned of the Mod 10 Algorithm in some Perl code,
	 * entitled "The Validator," available on Matt's Script Archive,
	 * http://www.scriptarchive.com/ccver.html.  That code was written by
	 * David Paris, who based it on material Melvyn Myers reposted from an
	 * unknown author.  Paris credits Aries Solis for tracking down the data
	 * underlying the algorithm.  I pruned down the algorithm to it's core
	 * components, making things smaller, cleaner and more flexible.  Plus,
	 * I added the expiration date checking routine.  My first attemts at
	 * this were in Visual Basic, on which Allen Browne and Rico Zschau
	 * assisted. Neil Fraser helped a bit on the Perl version.  Steve
	 * Horsley, Roedy Green and Jon Skeet provided tips on the Java Edition.</p>
	 *
	 * @param string $Number the number of the credit card to
	 *                                  validate.
	 * @param string $CheckNumber the ISO 639-1 two letter code of
	 *                                  the language for error messages.
	 * @param array|string $Accepted credit card types you accept.  If
	 *                                  not used in function call, all
	 *                                  known cards are accepted.  Set
	 *                                  it before calling the function: <br /><kbd>
	 *                                  $A = array('Visa', 'JCB');
	 *                                  </kbd><br />
	 *                                       Known types:        <ul>
	 *                                  <li> American Express    </li>
	 *                                  <li> Australian BankCard </li>
	 *                                  <li> Carte Blanche       </li>
	 *                                  <li> Diners Club         </li>
	 *                                  <li> Discover/Novus      </li>
	 *                                  <li> JCB                 </li>
	 *                                  <li> MasterCard          </li>
	 *                                  <li> Visa                </li></ul>
	 * @param string $RequireExp should the expiration date be
	 *                                  checked?  Y or N.
	 * @param int|string $Month the card's expiration month
	 *                                  in M, 0M or MM foramt.
	 * @param int|string $Year the card's expiration year in YYYY format.
	 * @return bool  TRUE if everything is fine.  FALSE if problems.
	 * @version    $Name: rel-5-14 $
	 * @author     Daniel Convissor <danielc@analysisandsolutions.com>
	 * @copyright  The Analysis and Solutions Company, 2002-2006
	 * @link       http://www.analysisandsolutions.com/software/ccvs/ccvs.htm
	 * @link       http://www.loc.gov/standards/iso639-2/langcodes.html
	 * @link       http://www.analysisandsolutions.com/donate/
	 * @license    http://www.analysisandsolutions.com/software/license.htm Simple Public License
	 */
	public function validateCreditCard($Number, $CheckNumber, $Accepted = '', $RequireExp = 'N', $Month = '', $Year = '') {
		$this->CCVSNumber = '';
		$this->CCVSNumberLeft = '';
		$this->CCVSNumberRight = '';
		$this->CCVSType = '';
		$this->CCVSExpiration = '';
		$this->CCVSError = '';

			// Catch malformed input.
		if (empty($Number) || !is_string($Number)) {
			$this->CCVSError = $this->language->getLL('ErrNumberString');

			return FALSE;
		}

			// Ensure number doesn't overrun.
		$Number = substr($Number, 0, 30);

			// Remove non-numeric characters.
		$this->CCVSNumber = preg_replace('/[^0-9]/', '', $Number);

			// Set up variables.
		$this->CCVSCheckNumber = trim($CheckNumber);
		$this->CCVSNumberLeft = substr($this->CCVSNumber, 0, 4);
		$this->CCVSNumberRight = substr($this->CCVSNumber, -4);
		$NumberLength = strlen($this->CCVSNumber);
		$DoChecksum = 'Y';

			// Determine the card type and appropriate length.
		if (($this->CCVSNumberLeft >= 3000) && ($this->CCVSNumberLeft <= 3059)) {
			$this->CCVSType = 'Diners Club';
			$ShouldLength = 14;
		} elseif (($this->CCVSNumberLeft >= 3600) && ($this->CCVSNumberLeft <= 3699)) {
			$this->CCVSType = 'Diners Club';
			$ShouldLength = 14;
		} elseif (($this->CCVSNumberLeft >= 3800) && ($this->CCVSNumberLeft <= 3889)) {
			$this->CCVSType = 'Diners Club';
			$ShouldLength = 14;
		} elseif (($this->CCVSNumberLeft >= 3400) && ($this->CCVSNumberLeft <= 3499)) {
			$this->CCVSType = 'American Express';
			$ShouldLength = 15;
		} elseif (($this->CCVSNumberLeft >= 3700) && ($this->CCVSNumberLeft <= 3799)) {
			$this->CCVSType = 'American Express';
			$ShouldLength = 15;
		} elseif (($this->CCVSNumberLeft >= 3088) && ($this->CCVSNumberLeft <= 3094)) {
			$this->CCVSType = 'JCB';
			$ShouldLength = 16;
		} elseif (($this->CCVSNumberLeft >= 3096) && ($this->CCVSNumberLeft <= 3102)) {
			$this->CCVSType = 'JCB';
			$ShouldLength = 16;
		} elseif (($this->CCVSNumberLeft >= 3112) && ($this->CCVSNumberLeft <= 3120)) {
			$this->CCVSType = 'JCB';
			$ShouldLength = 16;
		} elseif (($this->CCVSNumberLeft >= 3158) && ($this->CCVSNumberLeft <= 3159)) {
			$this->CCVSType = 'JCB';
			$ShouldLength = 16;
		} elseif (($this->CCVSNumberLeft >= 3337) && ($this->CCVSNumberLeft <= 3349)) {
			$this->CCVSType = 'JCB';
			$ShouldLength = 16;
		} elseif (($this->CCVSNumberLeft >= 3528) && ($this->CCVSNumberLeft <= 3589)) {
			$this->CCVSType = 'JCB';
			$ShouldLength = 16;
		} elseif (($this->CCVSNumberLeft >= 3890) && ($this->CCVSNumberLeft <= 3899)) {
			$this->CCVSType = 'Carte Blanche';
			$ShouldLength = 14;
		} elseif (($this->CCVSNumberLeft >= 4000) && ($this->CCVSNumberLeft <= 4999)) {
			$this->CCVSType = 'Visa';
			if ($NumberLength > 14) {
				$ShouldLength = 16;
			} elseif ($NumberLength < 14) {
				$ShouldLength = 13;
			} else {
				$this->CCVSError = $this->language->getLL('ErrVisa14');

				return FALSE;
			}
		} elseif (($this->CCVSNumberLeft >= 5100) && ($this->CCVSNumberLeft <= 5599)) {
			$this->CCVSType = 'MasterCard';
			$ShouldLength = 16;
		} elseif ($this->CCVSNumberLeft == 5610) {
			$this->CCVSType = 'Australian BankCard';
			$ShouldLength = 16;
		} elseif ($this->CCVSNumberLeft == 6011) {
			$this->CCVSType = 'Discover/Novus';
			$ShouldLength = 16;
		} else {
			$this->CCVSError = sprintf($this->language->getLL('ErrUnknown'), $this->CCVSNumberLeft);

			return FALSE;
		}

			// Check acceptance.
		if (!empty($Accepted)) {
			if (!is_array($Accepted)) {
				$this->CCVSError = $this->language->getLL('ErrAccepted');

				return FALSE;
			}
			if (!in_array($this->CCVSType, $Accepted)) {
				$this->CCVSError = sprintf($this->language->getLL('ErrNoAccept'), $this->CCVSType);

				return FALSE;
			}
		}

		/* Check CheckNumber. */
		if (!empty($this->CCVSType)) {
			switch($this->CCVSType) {
				case 'American Express':
					if (strlen($this->CCVSCheckNumber) != 4) {
						$this->CCVSError = sprintf($this->language->getLL('ErrCheckNumber'), $this->CCVSCheckNumber);
						return FALSE;
					}
				break;

				case 'MasterCard':
					if (strlen($this->CCVSCheckNumber) != 3) {
						$this->CCVSError = sprintf($this->language->getLL('ErrCheckNumber'), $this->CCVSCheckNumber);
						return FALSE;
					}
				break;

				case 'Visa':
					if (strlen($this->CCVSCheckNumber) != 3) {
						$this->CCVSError = sprintf($this->language->getLL('ErrCheckNumber'), $this->CCVSCheckNumber);
						return FALSE;
					}
				break;
			}
		}

			// Check length.
		if ($NumberLength <> $ShouldLength) {
			$Missing = $NumberLength - $ShouldLength;
			if ($Missing < 0) {
				$this->CCVSError = sprintf($this->language->getLL('ErrShort'), abs($Missing));
			} else {
				$this->CCVSError = sprintf($this->language->getLL('ErrLong'), $Missing);
			}

			return FALSE;
		}

			// Mod10 checksum process...
		if ($DoChecksum == 'Y') {
			$Checksum = 0;

			/**
			 * Add even digits in even length strings
			 * or odd digits in odd length strings.
			 */
			for ($Location = 1 - ($NumberLength % 2); $Location < $NumberLength; $Location += 2) {
				$Checksum += (int) substr($this->CCVSNumber, $Location, 1);
			}

			/**
			 * Analyze odd digits in even length strings
			 * or even digits in odd length strings.
			 */
			for ($Location = ($NumberLength % 2); $Location < $NumberLength; $Location += 2) {
				$Digit = (int) substr($this->CCVSNumber, $Location, 1) * 2;
				if ($Digit < 10) {
					$Checksum += $Digit;
				} else {
					$Checksum += $Digit - 9;
				}
			}

				// Checksums not divisible by 10 are bad.
			if ($Checksum % 10 != 0) {
				$this->CCVSError = $this->language->getLL('ErrChecksum');

				return FALSE;
			}
		}

			// Expiration date process...
		if ($RequireExp == 'Y') {
			if (empty($Month) || !is_string($Month)) {
				$this->CCVSError = $this->language->getLL('ErrMonthString');

				return FALSE;
			}

			if (!preg_match('/^(0?[1-9]|1[0-2])$/', $Month)) {
				$this->CCVSError = $this->language->getLL('ErrMonthFormat');

				return FALSE;
			}

			if (empty($Year) || !is_string($Year)) {
				$this->CCVSError = $this->language->getLL('ErrYearString');

				return FALSE;
			}

			if (!preg_match('/^[0-9]{4}$/', $Year)) {
				$this->CCVSError = $this->language->getLL('ErrYearFormat');

				return FALSE;
			}

			if ($Year < date('Y')) {
				$this->CCVSError = $this->language->getLL('ErrExpired');

				return FALSE;
			} elseif ($Year == date('Y')) {
				if ($Month < date('m')) {
					$this->CCVSError = $this->language->getLL('ErrExpired');

					return FALSE;
				}
			}

			$this->CCVSExpiration = sprintf('%02d', $Month) . substr($Year, -2);
		}

		return TRUE;
	}


	/**
	 * Get backend user
	 *
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}

	/**
	 * Get typoscript frontend controller
	 *
	 * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
	 */
	protected function getFrontendController() {
		return $GLOBALS['TSFE'];
	}
}
