<?php

namespace Pharmaline\Phlorder\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Frontend\Cache\CacheInstruction;

/**
 * Deaktiviert den Seiten-Cache fuer AJAX-Requests der eID-Middleware
 * (mw=phlorderEID).
 *
 * Grund: laeuft VOR "prepare-tsfe-rendering". Ist der Cache deaktiviert, gilt
 * dort needsFullSetup=true -> TYPO3 parst den VOLLEN TypoScript-Setup-Baum. Nur
 * dann findet der Worker plugin.tx_phlorder_order.settings (orderPid, qrcode,
 * mail-Konfiguration). Auf einer gecachten Seite waere getSetupArray() leer
 * (hasSetup()=false) und der Mailversand liefe ohne jede Konfiguration.
 *
 * Betrifft ausschliesslich die eigenen AJAX-Requests, die ohnehin dynamisch und
 * nicht cachebar sind - normale Seitenaufrufe bleiben unberuehrt.
 */
final class PhlorderEidCacheDisabler implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (($request->getQueryParams()['mw'] ?? '') === 'phlorderEID') {
            $cacheInstruction = $request->getAttribute('frontend.cache.instruction', new CacheInstruction());
            $cacheInstruction->disableCache('EXT:phlorder: eID/AJAX request must not use page cache (full TypoScript setup needed for plugin settings).');
            $request = $request->withAttribute('frontend.cache.instruction', $cacheInstruction);
        }
        return $handler->handle($request);
    }
}
