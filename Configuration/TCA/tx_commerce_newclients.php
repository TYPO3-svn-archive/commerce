<?php
return array(
	'ctrl' => array(
		'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_newclients',
		'label' => 'uid',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate',
		'readOnly' => '1',
		'adminOnly' => '1',
		'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'newclients.gif',
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'year, month, day, dow, hour, registration',
	),
	'interface' => array(
		'showRecordFieldList' => 'year,month,day,dow,hour,registration'
	),
	'columns' => array(
		'year' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_newclients.year',
			'config' => array(
				'type' => 'input',
				'size' => '11',
				'max' => '11',
				'eval' => 'int',
				'default' => 0
			)
		),
		'month' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_newclients.month',
			'config' => array(
				'type' => 'input',
				'size' => '11',
				'max' => '11',
				'eval' => 'int',
				'default' => 0
			)
		),
		'day' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_newclients.day',
			'config' => array(
				'type' => 'input',
				'size' => '11',
				'max' => '11',
				'eval' => 'int',
				'default' => 0
			)
		),
		'dow' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_newclients.dow',
			'config' => array(
				'type' => 'input',
				'size' => '11',
				'max' => '11',
				'eval' => 'int',
				'default' => 0
			)
		),
		'hour' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_newclients.hour',
			'config' => array(
				'type' => 'input',
				'size' => '11',
				'max' => '11',
				'eval' => 'int',
				'default' => 0
			)
		),
		'registration' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_newclients.registration',
			'config' => array(
				'type' => 'input',
				'size' => '11',
				'max' => '11',
				'eval' => 'int',
				'default' => 0
			)
		),
	),
	'types' => array(
		'0' => array('showitem' => 'year;;;;1-1-1, month, day, dow, hour, registration')
	),
	'palettes' => array(
		'1' => array('showitem' => '')
	)
);
