<?php

declare(strict_types=1);

namespace Pharmaline\Phlorder\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Event\Configuration\BeforeFlexFormConfigurationOverrideEvent;

/**
 * Uebersetzt das FlexForm-Feld settings.sourcePid ("Quelle der Bestellungen",
 * Sheet sSource des Plugins phlorder_order) in persistence.storagePid.
 *
 * Warum ueberhaupt ein Listener?
 *
 * Der naheliegende Weg - das FlexForm-Feld direkt "persistence.storagePid" zu
 * nennen - hat einen Haken: ein LEERES Feld ueberschreibt den TypoScript-Wert mit
 * "", Extbase fragt danach pid=0 ab und die Bestellliste bleibt kommentarlos leer.
 * "Feld nicht ausgefuellt" muss aber "TypoScript-Default benutzen" heissen.
 * (ignoreFlexFormSettingsIfEmpty hilft nicht, das greift nur fuer settings.*.)
 *
 * Der zweite naheliegende Weg - den Wert im Controller ueber
 * configurationManager->setConfiguration() zu setzen - funktioniert hier ebenfalls
 * nicht: FrontendConfigurationManager::getContextSpecificFrameworkConfiguration()
 * merged in overrideConfigurationFromPlugin() das TypoScript des Plugins ein
 * NACHDEM die per setConfiguration() uebergebenen Werte verrechnet wurden. Solange
 * plugin.tx_phlorder_order.persistence.storagePid gesetzt ist (Konstante: 17),
 * gewinnt also immer das TypoScript. Im FE verifiziert.
 *
 * Der FlexForm-Merge laeuft als LETZTER Schritt derselben Methode - deshalb greift
 * dieser Weg zuverlaessig.
 */
final class ApplySourcePidToStoragePid
{
    #[AsEventListener('phlorder/apply-source-pid-to-storage-pid')]
    public function __invoke(BeforeFlexFormConfigurationOverrideEvent $event): void
    {
        // Das Event laeuft fuer jedes Extbase-Plugin der Seite.
        if (($event->getFrameworkConfiguration()['extensionName'] ?? '') !== 'Phlorder') {
            return;
        }

        $flexFormConfiguration = $event->getFlexFormConfiguration();
        $sourcePid = $this->normalizePidList((string)($flexFormConfiguration['settings']['sourcePid'] ?? ''));
        if ($sourcePid === '') {
            return;	//leer = TypoScript-Default gilt weiter
        }

        $flexFormConfiguration['persistence']['storagePid'] = $sourcePid;
        $event->setFlexFormConfiguration($flexFormConfiguration);
    }

    /** Das Feld ist ein group-Feld auf "pages" und liefert eine komma-separierte
    * uid-Liste. Aeltere TYPO3-Staende schreiben "pages_17" statt "17" - die Ziffern
    * werden deshalb pro Eintrag herausgezogen (gleiche Absicherung wie in phlvote).
    *
    *@return string komma-separierte Pid-Liste, leer wenn nichts ausgewaehlt ist
    */
    private function normalizePidList(string $value): string
    {
        $pids = [];
        foreach (GeneralUtility::trimExplode(',', $value, true) as $item) {
            $pid = (int)preg_replace('/\D/', '', $item);
            if ($pid > 0) {
                $pids[] = $pid;
            }
        }
        return implode(',', $pids);
    }
}
