<?php
defined('TYPO3') or die();

// Zwei getrennte Plugins statt des frueheren FlexForm-Schalters
// "switchableControllerActions" (in v13 entfernt). Die alten SCA-Varianten waren:
//   Display = Order->list;Order->status    -> Plugin "Order"
//   Status  = Order->status;Order->list    -> Plugin "Orderstatus"
//   Test    = Order->test                  -> entfallen (testAction war Debug-Code)
//
// WICHTIG: Ohne SCA ist jede hier registrierte Action per URL dispatchbar. Deshalb
// stehen hier nur noch "list" und "status" - also genau das, was die SCA-Listen
// freigegeben haben. Frueher waren zusaetzlich Order->show/delete sowie die
// kompletten Controller Log, Item und Token registriert; ueber die SCA war davon
// nichts erreichbar, ohne SCA waeren es u. a. drei ungeschuetzte delete-Actions
// gewesen ("publicly accessible unless you implement an access check").

// Plugin "Order": Cockpit-/Listenansicht (Default-Action = Order->list).
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Phlorder',
    'Order',
    [
        \Pharmaline\Phlorder\Controller\OrderController::class => 'list, status',
    ],
    // non-cacheable actions
    [
        \Pharmaline\Phlorder\Controller\OrderController::class => 'status',
    ],
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);

// Plugin "Orderstatus": Statusseite einer Bestellung (Default-Action = Order->status),
// aufgerufen mit ?t=<orderid>.
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Phlorder',
    'Orderstatus',
    [
        \Pharmaline\Phlorder\Controller\OrderController::class => 'status, list',
    ],
    // non-cacheable actions
    [
        \Pharmaline\Phlorder\Controller\OrderController::class => 'status',
    ],
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);

// Die fruehere eID-Registrierung ($TYPO3_CONF_VARS['FE']['eID_include']['phlorderEID'])
// ist ersatzlos entfallen. Der Endpunkt laeuft seit Phase 7 als PSR-15-Middleware,
// registriert in Configuration/RequestMiddlewares.php.
//
// Grund: die Core-Middleware EidHandler laeuft VOR "site", "tsfe" und der
// FE-User-Authentifizierung. Ein eID-Request haette also weder TypoScript noch
// einen angemeldeten FE-User - beides braucht der Worker.
//
// ACHTUNG, externe Aufrufer: die URL aendert sich von
//     index.php?eID=phlorderEID&p=<pid>&_f=smtco&mt=...&oto=...&hs=...
// auf
//     index.php?id=<pid>&mw=phlorderEID&_f=smtco&mt=...&oto=...&hs=...
// Siehe Documentation/Genesis.md.

// Damit der PageArgumentValidator (FE.cacheHash.enforceValidation, im Projekt
// aktiv) diese Requests nicht als "cachebar mit fehlendem cHash" mit 404 abweist,
// muessen ALLE Parameter des AJAX-Pfades von der cHash-Berechnung ausgenommen
// werden - nicht nur "mw". Sonst rendert TYPO3 die 404-Seite, die ihrerseits
// durch diese Middleware laeuft und in der irrefuehrenden Meldung
// 'Funktion unbekannt:' endet.
foreach (['mw', '_f', 'p', 'mt', 'oto', 'hs', 'ct', 'ot', 'on', 't', 'lang', 'csh', '_'] as $ajaxParameter) {
    $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = $ajaxParameter;
}
