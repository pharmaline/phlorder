<?php
defined('TYPO3') or die();

// Zwei getrennte Plugins (SCA ist in v13 entfernt) - ersetzt den frueheren
// FlexForm-Schalter "switchableControllerActions" im Sheet sDEF.
// Die Plugins sind eigene CTypes (PLUGIN_TYPE_CONTENT_ELEMENT in ext_localconf.php),
// nicht list_type-Subtypen: list_type ist in v13 deprecated und faellt in v14 weg.
// Es existierten keine tt_content-Datensaetze mit phlorder_order, eine
// Record-Migration war daher nicht noetig.
//
// Die alte flexform_order.xml enthielt ausschliesslich switchableControllerActions
// und wurde deshalb in Phase 6 geloescht. Die neue flexform_order.xml haengt nur am
// Cockpit-Plugin und legt die Quelle der Bestellungen fest (settings.sourcePid);
// ausgewertet in OrderController::initStoragePid().

$pluginIcon = 'EXT:phlorder/Resources/Public/Icons/user_plugin_order.svg';

// Plugin "Order" (Signatur phlorder_order) - Cockpit-/Listenansicht.
$signatureOrder = \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Phlorder',
    'Order',
    'Bestellung (Cockpit)',
    $pluginIcon
);

// FlexForm nur fuer das Cockpit-Plugin. Bei eigenen CTypes (kein list_type) laeuft
// das ueber addToAllTCAtypes + addPiFlexFormValue mit dem CType als drittem
// Argument - subtypes_addlist greift hier nicht.
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    '--div--;Konfiguration,pi_flexform,',
    $signatureOrder,
    'after:subheader'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    '*',
    'FILE:EXT:phlorder/Configuration/FlexForms/flexform_order.xml',
    $signatureOrder
);

// Plugin "Orderstatus" (Signatur phlorder_orderstatus) - Statusseite, ?t=<orderid>.
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Phlorder',
    'Orderstatus',
    'Bestellung (Status)',
    $pluginIcon
);
