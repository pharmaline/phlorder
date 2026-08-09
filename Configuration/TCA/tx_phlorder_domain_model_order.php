<?php
return [
    'ctrl' => [
        'title'	=> 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_order',
        'label' => 'last_name',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
		'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
		'delete' => 'deleted',
		'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
		'searchFields' => 'phluserfid,feusefrid,orderid,ordernumber,status,last_name,first_name,address,zip,city,phone,email,mobil,delivery,payment,i_b_a_n,order_image,note,tolog,toitem',
        'iconfile' => 'EXT:phlorder/Resources/Public/Icons/tx_phlorder_domain_model_order.gif',
        // Ersetzt ExtensionManagementUtility::allowTableOnStandardPages() (in v13 entfernt)
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'types' => [
		'1' => ['showitem' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, phluserfid, feusefrid,timestamp, orderid,ordernumber, status, company,salutation, last_name, first_name, address, zip, city, phone, email, mobil, delivery, payment, i_b_a_n, order_image, note, tolog, toitem, --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access, starttime, endtime'],
    ],
    // Die Systemfelder sys_language_uid, l10n_parent, l10n_diffsource,
    // t3ver_label, hidden, starttime und endtime werden von TcaEnrichment
    // automatisch erzeugt und sind hier bewusst NICHT definiert (v13-korrekte
    // Defaults, u. a. starttime/endtime als type=datetime statt input+eval).
    'columns' => [
        'timestamp' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_order.timestamp',
	        'config' => [
			    // v13: type=datetime statt input+eval (sonst haengt die FormEngine
			    // am Lade-Spinner). nullable statt Legacy-Default '0000-00-00 ...'.
			    'type' => 'datetime',
			    'dbType' => 'datetime',
			    'nullable' => true,
			    'size' => 12,
			],
	    ],
        'phluserfid' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_order.phluserfid',
	        'config' => [
			    // v13: eval=int -> eigener Feldtyp
			    'type' => 'number',
			    'size' => 4,
			]
	    ],
	    'feusefrid' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_order.feusefrid',
	        'config' => [
			    // v13: eval=int -> eigener Feldtyp
			    'type' => 'number',
			    'size' => 4,
			]
	    ],
	    'orderid' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_order.orderid',
	        'config' => [
			    'type' => 'input',
			    'size' => 30,
			    'eval' => 'trim'
			],
	    ],
		'ordernumber' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_order.ordernumber',
	        'config' => [
			    'type' => 'input',
			    'size' => 30,
			    'eval' => 'trim'
			],
	    ],
	    'status' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_order.status',
	        'config' => [
			    'type' => 'input',
			    'size' => 30,
			    'eval' => 'trim'
			],
	    ],
	    'salutation' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_order.salutation',
	        'config' => [
			    'type' => 'input',
			    'size' => 30,
			    'eval' => 'trim'
			],
	    ],

	  'company' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_order.company',
	        'config' => [
			    'type' => 'input',
			    'size' => 60,
			    'eval' => 'trim'
			],
	    ],
	    'last_name' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_order.last_name',
	        'config' => [
			    'type' => 'input',
			    'size' => 30,
			    'eval' => 'trim'
			],
	    ],
	    'first_name' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_order.first_name',
	        'config' => [
			    'type' => 'input',
			    'size' => 30,
			    'eval' => 'trim'
			],
	    ],
	    'address' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_order.address',
	        'config' => [
			    'type' => 'input',
			    'size' => 30,
			    'eval' => 'trim'
			],
	    ],
	    'zip' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_order.zip',
	        'config' => [
			    'type' => 'input',
			    'size' => 30,
			    'eval' => 'trim'
			],
	    ],
	    'city' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_order.city',
	        'config' => [
			    'type' => 'input',
			    'size' => 30,
			    'eval' => 'trim'
			],
	    ],
	    'phone' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_order.phone',
	        'config' => [
			    'type' => 'input',
			    'size' => 30,
			    'eval' => 'trim'
			],
	    ],
	    'email' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_order.email',
	        'config' => [
			    'type' => 'input',
			    'size' => 30,
			    'eval' => 'trim'
			],
	    ],
	    'mobil' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_order.mobil',
	        'config' => [
			    'type' => 'input',
			    'size' => 30,
			    'eval' => 'trim'
			],
	    ],
	    'delivery' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_order.delivery',
	        'config' => [
			    'type' => 'input',
			    'size' => 30,
			    'eval' => 'trim'
			],
	    ],
	    'payment' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_order.payment',
	        'config' => [
			    'type' => 'input',
			    'size' => 30,
			    'eval' => 'trim'
			],
	    ],
	    'i_b_a_n' => [
	        'exclude' => 0,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_order.i_b_a_n',
	        'config' => [
			    'type' => 'input',
			    'size' => 30,
			    'eval' => 'trim'
			],
	    ],
	    'order_image' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_order.order_image',
	        // Frueher ExtensionManagementUtility::getFileFieldTCAConfig() - in v13 entfernt.
	        // Mit weg: die foreign_types-Paletten (inkl. der LLL:EXT:lang-Pfade) und die
	        // Konstanten \TYPO3\CMS\Core\Resource\File::FILETYPE_*.
	        'config' => [
			    'type' => 'file',
			    'allowed' => 'common-image-types',
			    'maxitems' => 5,
			    'appearance' => [
			        'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference',
			    ],
			],
	    ],
	    'note' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_order.note',
	        'config' => [
			    'type' => 'text',
			    'cols' => 40,
			    'rows' => 15
			],
	    ],
	    'tolog' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_order.tolog',
	        'config' => [
			    'type' => 'inline',
			    'foreign_table' => 'tx_phlorder_domain_model_log',
			    'foreign_field' => 'tx_order',
			    'maxitems' => 9999,
			    'appearance' => [
			        'collapseAll' => 0,
			        'levelLinksPosition' => 'top',
			        'showSynchronizationLink' => 1,
			        'showPossibleLocalizationRecords' => 1,
			        'showAllLocalizationLink' => 1
			    ],
			],
	    ],
	    'toitem' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_order.toitem',
	        'config' => [
			    'type' => 'inline',
			    'foreign_table' => 'tx_phlorder_domain_model_item',
			    'foreign_field' => 'tx_order',
			    'maxitems' => 9999,
			    'appearance' => [
			        'collapseAll' => 0,
			        'levelLinksPosition' => 'top',
			        'showSynchronizationLink' => 1,
			        'showPossibleLocalizationRecords' => 1,
			        'showAllLocalizationLink' => 1
			    ],
			],
	    ],
    ],
];
