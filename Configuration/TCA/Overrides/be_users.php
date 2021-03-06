<?php

// extend beusers/begroups for access control
$tempColumns = array(
	'tx_commerce_mountpoints' => array(
		'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:label.tx_commerce_mountpoints',
		'config' => array(
			// a special format is stored - that's why 'passthrough'
			// see: flag TCEFormsSelect_prefixTreeName
			// see: tx_dam_treelib_tceforms::getMountsForTree()
			'type' => 'passthrough',
			'form_type' => 'user',
			'userFunc' => 'Tx_Commerce_ViewHelpers_TceFunc->getSingleField_selectCategories',
			'treeViewBrowseable' => TRUE,
			'size' => 10,
			'autoSizeMax' => 30,
			'minitems' => 0,
			'maxitems' => 20,
		),
	),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_users', $tempColumns, 1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
	'be_users', 'tx_commerce_mountpoints', '', 'after:fileoper_perms'
);
