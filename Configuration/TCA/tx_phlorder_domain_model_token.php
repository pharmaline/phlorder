<?php
return [
    'ctrl' => [
        'title'	=> 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_token',
        'label' => 'phluserfid',
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
		'searchFields' => 'phluserfid,token,timestamp,status,free',
        'iconfile' => 'EXT:phlorder/Resources/Public/Icons/tx_phlorder_domain_model_token.gif',
        // Ersetzt ExtensionManagementUtility::allowTableOnStandardPages() (in v13 entfernt)
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'types' => [
		'1' => ['showitem' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, phluserfid, token, timestamp, status, free, --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access, starttime, endtime'],
    ],
    // Die Systemfelder sys_language_uid, l10n_parent, l10n_diffsource,
    // t3ver_label, hidden, starttime und endtime werden von TcaEnrichment
    // automatisch erzeugt und sind hier bewusst NICHT definiert (v13-korrekte
    // Defaults, u. a. starttime/endtime als type=datetime statt input+eval).
    'columns' => [
        'phluserfid' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_token.phluserfid',
	        'config' => [
			    // v13: eval=int -> eigener Feldtyp
			    'type' => 'number',
			    'size' => 4,
			]
	    ],
	    'token' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_token.token',
	        'config' => [
			    'type' => 'input',
			    'size' => 30,
			    'eval' => 'trim'
			],
	    ],
	    'timestamp' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_token.timestamp',
	        'config' => [
			    'type' => 'input',
			    'size' => 30,
			    'eval' => 'trim'
			],
	    ],
	    'status' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_token.status',
	        'config' => [
			    'type' => 'input',
			    'size' => 30,
			    'eval' => 'trim'
			],
	    ],
	    'free' => [
	        'exclude' => true,
	        'label' => 'LLL:EXT:phlorder/Resources/Private/Language/locallang_db.xlf:tx_phlorder_domain_model_token.free',
	        'config' => [
			    'type' => 'input',
			    'size' => 30,
			    'eval' => 'trim'
			],
	    ],
    ],
];
