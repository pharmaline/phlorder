<?php
defined('TYPO3') or die();

// Zwei getrennte Plugins (SCA ist in v13 entfernt) - ersetzt den frueheren
// FlexForm-Schalter "switchableControllerActions" im Sheet sDEF.
// Die Plugins sind eigene CTypes (PLUGIN_TYPE_CONTENT_ELEMENT in ext_localconf.php),
// nicht list_type-Subtypen: list_type ist in v13 deprecated und faellt in v14 weg.
// Es existierten keine tt_content-Datensaetze mit phlorder_order, eine
// Record-Migration war daher nicht noetig.
//
// Keine FlexForm mehr: die alte flexform_order.xml enthielt ausschliesslich
// switchableControllerActions. Damit bleibt kein einziges Feld uebrig, das an ein
// Plugin zu haengen waere - die Datei ist geloescht.

$pluginIcon = 'EXT:phlorder/Resources/Public/Icons/user_plugin_order.svg';

// Plugin "Order" (Signatur phlorder_order) - Cockpit-/Listenansicht.
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Phlorder',
    'Order',
    'Bestellung (Cockpit)',
    $pluginIcon
);

// Plugin "Orderstatus" (Signatur phlorder_orderstatus) - Statusseite, ?t=<orderid>.
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Phlorder',
    'Orderstatus',
    'Bestellung (Status)',
    $pluginIcon
);
