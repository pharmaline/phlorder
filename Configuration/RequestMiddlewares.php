<?php
return [
    'frontend' => [
        // Cache-Disabler: muss VOR "prepare-tsfe-rendering" laufen und schaltet
        // fuer mw=phlorderEID den Seiten-Cache ab, damit dort der volle
        // TypoScript-Setup gebaut wird (needsFullSetup). Sonst findet der Worker
        // plugin.tx_phlorder_order.settings nicht.
        'pharmaline/phlorder/eid-cache-disabler' => [
            'target' => \Pharmaline\Phlorder\Middleware\PhlorderEidCacheDisabler::class,
            'after' => [
                'typo3/cms-frontend/tsfe',
            ],
            'before' => [
                'typo3/cms-frontend/prepare-tsfe-rendering',
            ],
        ],
        'pharmaline/phlorder/eid-resolver' => [
            'target' => \Pharmaline\Phlorder\Middleware\PhlorderEidMiddleware::class,
            // NACH "tsfe" und "prepare-tsfe-rendering": erst dann ist
            // "frontend.typoscript" mit vollem Setup gesetzt und der FE-User
            // aufgeloest. VOR "content-length-headers", damit wir die Antwort
            // kurzschliessen, bevor die eigentliche Seite gerendert wird.
            'after' => [
                'typo3/cms-frontend/tsfe',
                'typo3/cms-frontend/prepare-tsfe-rendering',
            ],
            'before' => [
                'typo3/cms-frontend/content-length-headers',
            ],
        ],
    ],
];
