<?php

namespace Pharmaline\Phlorder\Middleware;

use Pharmaline\Phlorder\Utility\Ajax\phlorderEid;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Faengt die AJAX-Aufrufe von phlorder ab (Marker "mw=phlorderEID" in der URL)
 * und delegiert an den Worker. Ersetzt das frueher ueber
 * $TYPO3_CONF_VARS['FE']['eID_include'] eingebundene eID-Skript.
 *
 * Warum kein eID mehr: die Core-Middleware EidHandler laeuft direkt nach
 * "normalized-params-attribute" und damit VOR "site", "tsfe" und der
 * FE-User-Authentifizierung. Ein eID-Request hat also weder TypoScript
 * (frontend.typoscript) noch einen angemeldeten FE-User - beides braucht der
 * Worker. Der frueher hier stehende manuelle TSFE-Bootstrap
 * (TypoScriptFrontendController, connectToDB(), determineId(), initTemplate(),
 * getConfigArray(), settingLanguage(), settingLocale(), loadCachedTca()) ist in
 * v13 vollstaendig entfernt und nicht ersetzbar.
 */
final class PhlorderEidMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly phlorderEid $worker
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        if (($request->getQueryParams()['mw'] ?? '') !== 'phlorderEID') {
            return $handler->handle($request);
        }

        // Kein Frontend-TypoScript -> kein gerenderter FE-Request, durchreichen.
        if ($request->getAttribute('frontend.typoscript') === null) {
            return $handler->handle($request);
        }

        return $this->worker->main($request);
    }
}
