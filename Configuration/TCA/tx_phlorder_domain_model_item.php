<?php
return [
    'ctrl' => [
        'title'	=> 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_item',
        'label' => 'pzn',
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
		'searchFields' => 'pzn,name,size,dar,qty,price,diff,imgfid',
        'iconfile' => 'EXT:phlorder/Resources/Public/Icons/tx_phlorder_domain_model_item.gif',
        // Ersetzt ExtensionManagementUtility::allowTableOnStandardPages() (in v13 entfernt)
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'types' => [
		'1' => ['showitem' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, pzn, name, size, dar, qty, price, diff, imgfid, --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access, starttime, endtime'],
    ],
    // Die Systemfelder sys_language_uid, l10n_parent, l10n_diffsource,
    // t3ver_label, hidden, starttime und endtime werden von TcaEnrichment
    // automatisch erzeugt und sind hier bewusst NICHT definiert (v13-korrekte
    // Defaults, u. a. starttime/endtime als type=datetime statt input+eval).
    'columns' => [
        'pzn' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_item.pzn',
	        'config' => [
			    'type' => 'input',
			    'size' => 30,
			    'eval' => 'trim'
			],
	    ],
	    'name' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_item.name',
	        'config' => [
			    'type' => 'input',
			    'size' => 30,
			    'eval' => 'trim'
			],
	    ],
	    'size' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_item.size',
	        'config' => [
			    'type' => 'input',
			    'size' => 30,
			    'eval' => 'trim'
			],
	    ],
	    'dar' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_item.dar',
	        'config' => [
			    'type' => 'input',
			    'size' => 30,
			    'eval' => 'trim'
			],
	    ],
	    'qty' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_item.qty',
	        'config' => [
			    'type' => 'input',
			    'size' => 30,
			    'eval' => 'trim'
			],
	    ],
	    'price' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_item.price',
	        'config' => [
			    'type' => 'input',
			    'size' => 30,
			    'eval' => 'trim'
			],
	    ],
	    'diff' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_item.diff',
	        'config' => [
			    'type' => 'input',
			    'size' => 30,
			    'eval' => 'trim'
			],
	    ],
	    'imgfid' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_item.imgfid',
	        'config' => [
			    'type' => 'input',
			    'size' => 30,
			    'eval' => 'trim'
			],
	    ],
        'tx_order' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
];
