<?php
return [
    'ctrl' => [
        'title'	=> 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_log',
        'label' => 'timestamp',
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
		'searchFields' => 'timestamp,action,result,value1,value2,free,actor',
        'iconfile' => 'EXT:phlorder/Resources/Public/Icons/tx_phlorder_domain_model_log.gif',
        // Ersetzt ExtensionManagementUtility::allowTableOnStandardPages() (in v13 entfernt)
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'types' => [
		'1' => ['showitem' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, timestamp, action, result, value1, value2, free, actor,orderid,ordernumber, --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access, starttime, endtime'],
    ],
    // Die Systemfelder sys_language_uid, l10n_parent, l10n_diffsource,
    // t3ver_label, hidden, starttime und endtime werden von TcaEnrichment
    // automatisch erzeugt und sind hier bewusst NICHT definiert (v13-korrekte
    // Defaults, u. a. starttime/endtime als type=datetime statt input+eval).
    'columns' => [
        'timestamp' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_log.timestamp',
	        'config' => [
			    // v13: type=datetime statt input+eval (sonst haengt die FormEngine
			    // am Lade-Spinner). nullable statt Legacy-Default '0000-00-00 ...'.
			    'type' => 'datetime',
			    'dbType' => 'datetime',
			    'nullable' => true,
			    'size' => 12,
			],
	    ],
	    'action' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_log.action',
	        'config' => [
			    'type' => 'input',
			    'size' => 30,
			    'eval' => 'trim'
			],
	    ],
	    'result' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_log.result',
	        'config' => [
			    'type' => 'input',
			    'size' => 30,
			    'eval' => 'trim'
			],
	    ],
	    'value1' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_log.value1',
	        'config' => [
			    'type' => 'input',
			    'size' => 30,
			    'eval' => 'trim'
			],
	    ],
	    'value2' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_log.value2',
	        'config' => [
			    'type' => 'input',
			    'size' => 30,
			    'eval' => 'trim'
			],
	    ],
	    'free' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_log.free',
	        'config' => [
			    'type' => 'input',
			    'size' => 30,
			    'eval' => 'trim'
			],
	    ],
	    'actor' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_log.actor',
	        'config' => [
			    'type' => 'input',
			    'size' => 30,
			    'eval' => 'trim'
			],
	    ],
	    'orderid' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_log.orderid',
	        'config' => [
			    'type' => 'input',
			    'size' => 30,
			    'eval' => 'trim'
			],
	    ],
		'ordernumber' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_log.ordernumber',
	        'config' => [
			    'type' => 'input',
			    'size' => 10,
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
