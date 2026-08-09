<?php
namespace Pharmaline\Phlorder\Utility\Ajax;

use Pharmaline\Phlorder\Domain\Repository\OrderRepository;
use Pharmaline\Phlqr\Service\QrCodeService;
use Pharmaline\Phlusereditor\Service\PhluserService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request as ExtbaseRequest;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

/***
 *
 * This file is part of the "phlorder Bestellung" Extension for TYPO3 CMS.
 *
 *  (c) 2018 Christian Platt <christian.platt@pharmaline.de>, pharmaline
 *
 ***/

/**
 * Worker fuer die AJAX-/Mail-Endpunkte von phlorder.
 *
 * Frueher ein eID-Skript mit prozeduralem TSFE-Bootstrap am Dateikopf (Zeilen 1-49
 * der Altfassung). Der Bootstrap ist in v13 vollstaendig tot; die Klasse ist jetzt
 * eine normale, per DI erzeugte Klasse ohne Code auf Dateiebene und wird von
 * PhlorderEidMiddleware aufgerufen.
 *
 * Der Klassenname bleibt bewusst nicht-PSR-konform ("phlorderEid"), damit
 * Dateiname = Klassenname gilt und das PSR-4-Autoloading greift.
 *
 * Funktionsschalter ist der Request-Parameter "_f":
 *   smtco   Bestellmail an die Apotheke        (Hash: sha1(mt + oto + date('dmy')))
 *   smtcu   Bestellmail an die Kundin          (Hash: dito)
 *   smocomp wie smtco (Altvariante)            (Hash: dito - siehe HINWEIS unten)
 *   smo     freie Mail aus dem POST-Body       (standardmaessig DEAKTIVIERT)
 *   gqc     QR-Code zur Bestellung             (Dienst fehlt - siehe HINWEIS unten)
 *   lii     Redirect auf ein 1x1-Zaehlpixel
 *
 * JSON-Kontrakt zum Frontend unveraendert: {"success": "true"|"false", "message": ...}
 */
class phlorderEid
{
    /**
     * Content-ID, unter der der QR-Code in die Mail eingebettet wird.
     * Die Mail-Templates verweisen mit <img src="cid:qrcode"> darauf.
     */
    protected const QR_CODE_CID = 'qrcode';

    protected array $settings = [];
    protected array $params = [];
    protected ?ServerRequestInterface $serverRequest = null;
    protected string $resourcePath;

    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly PhluserService $phluserService,
        private readonly ViewFactoryInterface $viewFactory,
        private readonly PersistenceManagerInterface $persistenceManager,
        private readonly TypoScriptService $typoScriptService,
        private readonly QrCodeService $qrCodeService
    ) {
        $this->resourcePath = GeneralUtility::getFileAbsFileName('EXT:phlorder/Resources/Private/');
    }

    /**
     * Einstiegspunkt, aufgerufen von der Middleware.
     *
     * Frueher: main() ohne Argumente, Daten aus $_REQUEST/$_FILES, Antwort per
     * echo + exit. Jetzt PSR-7 rein und raus - kein exit() mehr im ganzen Worker.
     */
    public function main(ServerRequestInterface $request): ResponseInterface
    {
        $this->serverRequest = $request;
        $this->params = array_merge(
            $request->getQueryParams(),
            is_array($request->getParsedBody()) ? $request->getParsedBody() : []
        );
        $this->initSettings();

        switch ((string)($this->params['_f'] ?? '')) {
            case 'smtco':
            case 'smocomp':
                // HINWEIS: smocomp pruefte den Hash frueher NICHT (es wurde nur
                // geprueft, ob der Parameter ueberhaupt da ist). Beide Varianten
                // rufen dieselbe Funktion auf, deshalb gilt jetzt fuer beide
                // dieselbe Pruefung wie bisher schon fuer smtco.
                if (($err = $this->requireOrderHash()) !== null) {
                    return $err;
                }
                return $this->controlSendOrderMailToCompany();

            case 'smtcu':
                if (($err = $this->requireOrderHash()) !== null) {
                    return $err;
                }
                return $this->controlSendOrderMailToCustomer();

            case 'smo':
                return $this->controlSendFreeMail();

            case 'gqc':
                return $this->controlGetQrCodeForOrder();

            case 'lii': // list invisible image
                $target = (string)($this->settings['pathToBlindImg'] ?? '');
                if ($target === '') {
                    return $this->message(false, 'Kein pathToBlindImg konfiguriert.');
                }
                // EXT:-Pfade in eine oeffentliche URL aufloesen: in v13 liegen die
                // publizierten Assets unter /_assets/<hash>/, ein fester Pfad in der
                // Konfiguration waere falsch.
                if (str_starts_with($target, 'EXT:')) {
                    $target = PathUtility::getPublicResourceWebPath($target);
                }
                return new RedirectResponse($target, 301);

            default:
                return $this->message(false, 'Funktion unbekannt: ' . (string)($this->params['_f'] ?? ''));
        }
    }

    // ---------------------------------------------------------------- Controller

    /**
     * Bestellmail an die Apotheke (Company).
     */
    protected function controlSendOrderMailToCompany(): ResponseInterface
    {
        $userPrefs = $this->loadUserPrefs();
        if ($userPrefs instanceof ResponseInterface) {
            return $userPrefs;
        }
        [$user, $prefs] = $userPrefs;

        $order = $this->loadOrderByToken((string)($this->params['oto'] ?? ''));
        if ($order === null) {
            return $this->message(false, 'Die Bestellung konnte nicht gefunden werden. (947)');
        }

        if (!$order->getOrdernumber()) {
            $this->assignOrdernumber($order);
        }

        $data = [];
        if ($this->getPrefField($prefs, 'srvOrderTimes', 'prefvalue') === '1') {
            $arrivalTimes = json_decode((string)$this->getPrefField($prefs, 'srvOrderTimes', 'preftext'), true) ?: [];
            $data['arrivalTime'] = $this->getArrivalTime($arrivalTimes, $order->getDelivery());
        }
        $data['ordertime'] = date('d-m-Y H:i:s');
        $data['mobilwa'] = 'https://wa.me/' . $this->getWhatsAppPhone((string)$order->getMobil());

        // Der QR-Code wird auch hier vorbereitet, weil settings.qrcode.* im
        // Template zur Verfuegung stehen soll. Eingebettet wird er nur in der
        // Kundenmail - nur deren Template zeigt ihn an.
        $this->prepareQrCode($prefs, $order);

        $content = $this->addAbsPrefix($this->renderComponent(
            'Mail/MailOrderToCompany.html',
            ['order' => $order, 'client' => $user, 'prefs' => $prefs, 'data' => $data]
        ));

        $subjectPrefix = $this->getPrefField($prefs, 'srvOrderMailSubject', 'prefvalue');
        $subject = ($subjectPrefix ?: 'Bestellung von')
            . ' ' . $order->getFirstName() . ' ' . $order->getLastName() . ' - ' . $order->getOrdernumber();

        $sendTo = [new Address((string)$this->getPrefField($prefs, 'srvEmail', 'prefvalue'), (string)($user['company'] ?? ''))];

        // cc/bcc stecken als JSON im preftext. Leere Listen bzw. Listen mit einem
        // leeren String bewusst wie bisher als "nicht gesetzt" behandeln.
        $prefText = json_decode((string)$this->getPrefField($prefs, 'srvEmail', 'preftext'), true) ?: [];
        $cc = $this->toAddressList($prefText['cc'] ?? null);
        $bcc = $this->toAddressList($prefText['bcc'] ?? null);

        if ($this->getPrefField($prefs, 'srvOrderMailCustomerMailToCompany', 'prefvalue') === '1') {
            $sendFrom = [new Address((string)$order->getEmail(), $order->getFirstName() . ' ' . $order->getLastName())];
        } else {
            $sendFrom = [new Address('noreply@pharmaline.de', 'noReply Pharmaline')];
        }

        $ok = $this->sendMail($sendTo, $sendFrom, $subject, $content, [], $cc, $bcc, (string)$order->getEmail());

        return $ok
            ? $this->message(true, 'Bestellmail wurde an ' . $sendTo[0]->getAddress() . ' versendet')
            : $this->message(false, 'Fehler beim Versenden der Bestellmail an ' . $sendTo[0]->getAddress() . '.');
    }

    /**
     * Bestellbestaetigung an die Kundin.
     */
    protected function controlSendOrderMailToCustomer(): ResponseInterface
    {
        $userPrefs = $this->loadUserPrefs();
        if ($userPrefs instanceof ResponseInterface) {
            return $userPrefs;
        }
        [$user, $prefs] = $userPrefs;

        $order = $this->loadOrderByToken((string)($this->params['oto'] ?? ''));
        if ($order === null) {
            return $this->message(false, 'Die Bestellung konnte nicht gefunden werden. (947)');
        }

        if (!$order->getOrdernumber()) {
            $this->assignOrdernumber($order);
        }

        $data = [];
        if ($this->getPrefField($prefs, 'srvOrderTimes', 'prefvalue') === '1') {
            $arrivalTimes = json_decode((string)$this->getPrefField($prefs, 'srvOrderTimes', 'preftext'), true) ?: [];
            $data['arrivalTime'] = $this->getArrivalTime($arrivalTimes, $order->getDelivery());
        }
        $data['mobilwa'] = 'https://wa.me/' . $this->getWhatsAppPhone((string)$order->getMobil());

        $qrCodeFile = $this->prepareQrCode($prefs, $order);

        $content = $this->addAbsPrefix($this->renderComponent(
            'Mail/MailOrderToCustomer.html',
            ['order' => $order, 'client' => $user, 'prefs' => $prefs, 'data' => $data]
        ));

        $subject = ' Bestellung bei ' . ($user['company'] ?? '') . ' am ' . date('d.m.Y H:i')
            . ' von ' . $order->getFirstName() . ' ' . $order->getLastName();

        $mailTo = (string)$order->getEmail();
        $sendTo = [new Address($mailTo, (string)($user['company'] ?? ''))];
        $sendFrom = [new Address(
            (string)$this->getPrefField($prefs, 'srvOrderSenderMail', 'prefvalue'),
            'Bestellung ' . ($user['company'] ?? '')
        )];

        $ok = $this->sendMail(
            $sendTo,
            $sendFrom,
            $subject,
            $content,
            [],
            null,
            null,
            '',
            $qrCodeFile !== '' ? [self::QR_CODE_CID => $qrCodeFile] : []
        );

        return $ok
            ? $this->message(true, 'Bestellmail wurde an ' . $mailTo . ' versendet')
            : $this->message(false, 'Fehler beim Versenden der Bestellmail an ' . $mailTo . '.');
    }

    /**
     * Freie Mail mit Empfaenger/Betreff/Body aus dem Request.
     *
     * ACHTUNG - bewusste Verhaltensaenderung bei der Migration:
     * Diese Funktion nimmt Empfaenger, Betreff und Body ungefiltert entgegen. Der
     * einzige Schutz war ein Hash aus sha1(mt + festes Secret + fester String) -
     * er haengt also nur von "mt" ab. Wer ein einziges gueltiges (mt, hs)-Paar
     * kennt, kann darueber beliebige Mails ueber diesen Server verschicken.
     * Der Endpunkt ist deshalb standardmaessig AUS und muss bewusst aktiviert
     * werden:
     *
     *   plugin.tx_phlorder_order.settings.enableFreeMailEndpoint = 1
     *
     * Im Repo gibt es keinen Aufrufer. Vor dem Einschalten sollte der Hash durch
     * eine echte Autorisierung ersetzt werden (FE-Session oder Empfaenger-Whitelist).
     */
    protected function controlSendFreeMail(): ResponseInterface
    {
        if (empty($this->settings['enableFreeMailEndpoint'])) {
            return $this->message(
                false,
                'Endpunkt _f=smo ist deaktiviert (settings.enableFreeMailEndpoint).'
            );
        }

        $secret = (string)($this->settings['freeMailSecret'] ?? '');
        if ($secret === '') {
            return $this->message(false, 'Endpunkt _f=smo ist nicht konfiguriert (settings.freeMailSecret fehlt).');
        }
        $expected = sha1((string)($this->params['mt'] ?? '') . $secret);
        if (!hash_equals($expected, (string)($this->params['hs'] ?? ''))) {
            return $this->message(false, 'Sicherheitstoken ist falsch (948)');
        }

        $userPrefs = $this->loadUserPrefs();
        if ($userPrefs instanceof ResponseInterface) {
            return $userPrefs;
        }

        $sendTo = $this->toAddressList($this->params['sendTo'] ?? null);
        $sendFrom = $this->toAddressList($this->params['sendFrom'] ?? null);
        if ($sendTo === null || $sendFrom === null) {
            return $this->message(false, 'Empfaenger oder Absender fehlt.');
        }

        $ok = $this->sendMail(
            $sendTo,
            $sendFrom,
            (string)($this->params['subject'] ?? ''),
            (string)($this->params['body'] ?? '')
        );

        return $ok ? $this->message(true, 'mail send') : $this->message(false, 'error sending mail');
    }

    /**
     * QR-Code zu einer Bestellung.
     *
     * Antwortet - anders als die uebrigen Endpunkte - nicht mit JSON, sondern mit
     * dem Bild selbst. Damit ist der Endpunkt direkt als Bildquelle verwendbar:
     * <img src="...&mw=phlorderEID&_f=gqc&oto=<token>">.
     *
     * SVG, weil die Antwort im Browser landet und dort beliebig skaliert; fuer den
     * Mailweg erzeugt prepareQrCode() eine PNG-Datei.
     *
     * Die Absicherung ist der Bestell-Token (oto) - wie bisher. Ein zusaetzlicher
     * Hash wie bei den Mail-Endpunkten wuerde bestehende Aufrufer brechen.
     */
    protected function controlGetQrCodeForOrder(): ResponseInterface
    {
        $order = $this->loadOrderByToken((string)($this->params['oto'] ?? ''));
        if ($order === null) {
            return $this->message(false, 'Konnte Bestellung nicht finden (1050)');
        }

        $base = trim((string)($this->settings['qrcode']['text'] ?? ''));
        if ($base === '') {
            return $this->message(false, 'Keine Basis-URL fuer den QR-Code konfiguriert (Setting phlorder.qrcodeText).');
        }

        try {
            $svg = $this->qrCodeService->getQrAsSvg($base . '&t=' . $order->getOrderid(), [
                'width' => (int)($this->settings['qrcode']['width'] ?? 300),
            ]);
        } catch (\Throwable $e) {
            return $this->message(false, 'QR-Code konnte nicht erzeugt werden: ' . $e->getMessage());
        }

        return new HtmlResponse($svg, 200, ['Content-Type' => 'image/svg+xml; charset=utf-8']);
    }

    // -------------------------------------------------------------------- Helper

    /**
     * Pruefung des Bestell-Hashes: sha1(mt + oto + date('dmy')).
     * @return ResponseInterface|null Fehlerantwort oder null, wenn alles passt
     */
    protected function requireOrderHash(): ?ResponseInterface
    {
        $mt = (string)($this->params['mt'] ?? '');
        $oto = (string)($this->params['oto'] ?? '');
        $hs = (string)($this->params['hs'] ?? '');

        if ($mt === '') {
            return $this->message(false, 'Token fehlt. (950)');
        }
        if ($oto === '') {
            return $this->message(false, 'OrderToken fehlt. (951)');
        }
        if ($hs === '') {
            return $this->message(false, 'Hash fehlt. (952)');
        }
        if (!hash_equals(sha1($mt . $oto . date('dmy')), $hs)) {
            return $this->message(false, 'Sicherheitstoken ist falsch (948)');
        }
        return null;
    }

    /**
     * Phluser + dessen srvOrder-Praeferenzen laden und die Bestellberechtigung pruefen.
     * @return array{0: array, 1: array}|ResponseInterface
     */
    protected function loadUserPrefs(): array|ResponseInterface
    {
        // Frueher: GeneralUtility::makeInstanceService('extPhluser'). Das
        // Service-Subsystem ist in v13 weg; phlusereditor stellt dieselbe API als
        // regulaeren DI-Service bereit.
        $user = $this->phluserService->getUserByToken((string)($this->params['mt'] ?? ''));
        if (!is_array($user) || empty($user['uid'])) {
            return $this->message(false, 'Fehler bei der Userverarbeitung (958)');
        }

        $prefArray = $this->phluserService->selectPrefOfUser([$user], ['srvOrder']);
        $prefs = $prefArray[$user['uid']] ?? null;
        if (!$prefs) {
            return $this->message(false, 'Fehler bei der Userverarbeitung (958)');
        }

        if (!$this->getPrefField($prefs, 'srvOrderUsage', 'prefvalue')) {
            return $this->message(false, 'Benutzer nicht zur Bestellung berechtigt. (959)');
        }

        return [$user, $prefs];
    }

    /**
     * Bestellung ueber ihr oeffentliches Token (orderid) laden.
     */
    protected function loadOrderByToken(string $token): ?\Pharmaline\Phlorder\Domain\Model\Order
    {
        if ($token === '') {
            return null;
        }
        $querySettings = $this->orderRepository->createQuery()->getQuerySettings();
        $querySettings->setRespectStoragePage(false);
        $this->orderRepository->setDefaultQuerySettings($querySettings);

        $orders = $this->orderRepository->getOrderByToken($token);
        return $orders->count() ? $orders->current() : null;
    }

    /**
     * Bestellnummer vergeben und persistieren.
     *
     * ACHTUNG: Die Nummer entsteht weiterhin aus der ANZAHL bestehender
     * Bestellungen des Phlusers. Das erzeugt Duplikate, sobald je eine Bestellung
     * geloescht wurde oder zwei gleichzeitig eingehen. Verhalten bewusst
     * unveraendert gelassen - die Korrektur (MAX(ordernumber) bzw. echte Sequenz)
     * ist eine fachliche Entscheidung, siehe CLAUDE.md #13.
     * Frueher stand hier zusaetzlich ein debugster()-Aufruf (Fatal) und eine
     * unbenutzte lokale Variable $ordernumber.
     */
    protected function assignOrdernumber(\Pharmaline\Phlorder\Domain\Model\Order $order): void
    {
        $existing = $this->orderRepository->getOrdernumberlatest($order->getPhluserfid());
        $ordernumber = substr('00000' . $order->getPhluserfid(), -5) . date('ymd') . $existing->count();
        $order->setOrdernumber($ordernumber);
        $this->orderRepository->update($order);
        $this->persistenceManager->persistAll();
    }

    /**
     * QR-Code fuer die Statusseite vorbereiten, wenn die Praeferenz es verlangt.
     *
     * Seit Phase 8 wieder in Betrieb: die Kodierung macht
     * Pharmaline\Phlqr\Service\QrCodeService (bacon/bacon-qr-code). Frueher lief
     * das ueber GeneralUtility::makeInstanceService('extPhlqr') - ein Legacy-"_sv"-
     * Service, den es in v13 nicht mehr gibt.
     *
     * Bewusst PNG und nicht SVG: der Code geht in eine E-Mail, und SVG rendert in
     * Outlook und den meisten Webmailern nicht.
     *
     * @return string absoluter Pfad der erzeugten Datei, '' wenn kein Code entsteht
     */
    protected function prepareQrCode(array $prefs, \Pharmaline\Phlorder\Domain\Model\Order $order): string
    {
        if ($this->getPrefField($prefs, 'srvOrderSendMailQr', 'prefvalue') !== '1') {
            return '';
        }

        $base = trim((string)($this->settings['qrcode']['text'] ?? ''));
        if ($base === '') {
            // Ohne Basis-URL zeigte der Code auf "&t=<orderid>" - unbrauchbar.
            // Die URL kommt aus der Site-Konfiguration (Setting phlorder.qrcodeText).
            $this->settings['qrcode']['filepath'] = '';
            return '';
        }

        $url = $base . '&t=' . $order->getOrderid();
        $this->settings['edit']['statusurl'] = $url;
        $this->settings['qrcode']['imagename'] = 'op' . $order->getOrdernumber();

        try {
            $relativePath = $this->qrCodeService->getQrForUrl($url, [
                'extension' => 'png',
                'width' => (int)($this->settings['qrcode']['width'] ?? 300),
                'subfolder' => 'phlorder/',
                'imagename' => $this->settings['qrcode']['imagename'],
                // Fester Dateiname aus der Bestellnummer -> ohne override bliebe
                // eine Datei aus einer frueheren Bestellung mit gleicher Nummer.
                'imageoverride' => 1,
            ]);
        } catch (\Throwable $e) {
            // Ein fehlender QR-Code darf den Mailversand nicht verhindern.
            $this->settings['qrcode']['filepath'] = '';
            return '';
        }

        $this->settings['qrcode']['filepath'] = $relativePath;

        return Environment::getPublicPath() . '/' . $relativePath;
    }

    /**
     * Fluid-Rendering ueber die ViewFactory (frueher StandaloneView via makeInstance).
     * Der Extbase-Request setzt den Extension-Namen, damit die f:translate-Kurzkeys
     * in den Mail-Partials aufloesen.
     */
    protected function renderComponent(string $template, array $assign = []): string
    {
        $viewFactoryData = new ViewFactoryData(
            templateRootPaths: [$this->resourcePath . 'Templates/'],
            partialRootPaths: [$this->resourcePath . 'Partials/'],
            layoutRootPaths: [$this->resourcePath . 'Layouts/'],
            templatePathAndFilename: $this->resourcePath . 'Templates/' . $template,
            request: $this->buildViewRequest(),
        );
        $view = $this->viewFactory->create($viewFactoryData);

        $assign['settings'] = $this->settings;
        $assign['fe_user'] = $this->getFrontendUserRecord();
        $view->assignMultiple($assign);

        return str_replace(chr(10), '', $view->render());
    }

    protected function buildViewRequest(): ExtbaseRequest
    {
        $extbaseParameters = new ExtbaseRequestParameters();
        $extbaseParameters->setControllerExtensionName('Phlorder');

        return new ExtbaseRequest(
            $this->serverRequest->withAttribute('extbase', $extbaseParameters)
        );
    }

    /**
     * Mailversand ueber den Symfony-Mailer (frueher ObjectManager + SwiftMailer
     * mit setBody()/addPart()/isSent() und Swift_Attachment).
     *
     * @param Address[] $sendTo
     * @param Address[] $sendFrom
     * @param string[]  $attachments absolute Dateipfade
     * @param Address[]|null $cc
     * @param Address[]|null $bcc
     * @param array<string, string> $inlineImages [Content-ID => absoluter Pfad];
     *        im Template als <img src="cid:<Content-ID>"> referenzierbar
     */
    protected function sendMail(
        array $sendTo,
        array $sendFrom,
        string $subject = 'Subjekt',
        string $emailBody = '',
        array $attachments = [],
        ?array $cc = null,
        ?array $bcc = null,
        string $replyTo = '',
        array $inlineImages = []
    ): bool {
        if ($sendTo === [] || $sendFrom === []) {
            return false;
        }

        $message = GeneralUtility::makeInstance(MailMessage::class);
        $message->to(...$sendTo)
            ->from(...$sendFrom)
            ->subject($subject)
            ->html($emailBody);

        if ($cc) {
            $message->cc(...$cc);
        }
        if ($bcc) {
            $message->bcc(...$bcc);
        }
        if ($replyTo !== '') {
            $message->replyTo($replyTo);
        }
        foreach ($attachments as $attachment) {
            if (is_string($attachment) && file_exists($attachment)) {
                $message->attachFromPath($attachment);
            }
        }

        // Inline statt Anhang: eingebettete Bilder zeigen die Mail-Clients direkt
        // an, ohne dass sie etwas vom Server nachladen muessten. Ein per URL
        // eingebundener QR-Code bliebe hinter der Sperre fuer externe Inhalte.
        foreach ($inlineImages as $cid => $path) {
            if (is_string($path) && $path !== '' && file_exists($path)) {
                $message->embedFromPath($path, (string)$cid);
            }
        }

        $message->send();

        return $message->isSent();
    }

    /**
     * Wert eines Praeferenzfeldes.
     */
    protected function getPrefField(array $prefs, string $key, string $field): mixed
    {
        foreach ($prefs as $val) {
            if (($val['prefkey'] ?? null) === $key) {
                return $val[$field] ?? null;
            }
        }
        return null;
    }

    /**
     * Naechstes Abhol-/Lieferfenster bestimmen.
     */
    protected function getArrivalTime(array $times, string $delivery): ?array
    {
        $today = (int)date('N');       // Wochentag 1-7
        $todayDate = date('d.m.Y');
        $time = (int)date('Hi');

        for ($i = 0; $i < 7; $i++) {
            $at = $this->checkTime($times[$delivery][$today] ?? [], $time);
            if ($at) {
                return ['date' => $todayDate, 'time' => date('H:i', strtotime($at)), 'delivery' => $delivery];
            }
            $today = ($today + 1 < 8) ? $today + 1 : 1;
            $time = 0;
            $todayDate = date('d.m.Y', strtotime($todayDate . ' +1 day'));
        }
        return null;
    }

    protected function checkTime(array $dayTime, int $time): string|false
    {
        foreach ($dayTime as $value) {
            if ($time <= $value[0]) {
                return $value[1];
            }
        }
        return false;
    }

    /**
     * Telefonnummer in das von wa.me erwartete Format bringen.
     */
    protected function getWhatsAppPhone(string $phone): string
    {
        if (str_starts_with($phone, '00')) {
            $phone = substr($phone, 2);
        }
        $phone = str_replace(['+', '-', ' ', '(', ')'], '', $phone);

        if (str_starts_with($phone, '49')) {
            return $phone;
        }
        if (substr($phone, 0, 4) <= '180') {
            $phone = '49' . substr($phone, 1);
        }
        return $phone;
    }

    /**
     * absRefPrefix vor relative src-Angaben setzen, damit Bilder in der Mail
     * absolute URLs bekommen.
     *
     * Zwei Korrekturen aus Phase 8:
     *
     * 1. Quellen mit eigenem Schema bleiben unangetastet. Vorher bekam jedes src
     *    den Prefix vorangestellt - aus src="cid:qrcode" (eingebetteter QR-Code)
     *    waere src="https://…/cid:qrcode" geworden.
     * 2. Das pauschale str_replace('//', '/') ueber den **gesamten** Mailtext ist
     *    entfallen. Es sollte doppelte Schraegstriche nach dem Voranstellen des
     *    Prefix aufloesen, traf aber jede URL im Text: aus
     *    href="https://phl13.ddev.site/…" wurde "https:/phl13.ddev.site/…" - im
     *    Postfach ein toter Link. Betroffen waren der Link unter dem QR-Code und
     *    der WhatsApp-Link. Der Doppel-Schraegstrich kann jetzt gar nicht mehr
     *    entstehen, weil der Callback den fuehrenden Slash der Quelle abschneidet.
     */
    protected function addAbsPrefix(string $content): string
    {
        $config = $this->serverRequest?->getAttribute('frontend.typoscript')?->getConfigArray() ?? [];
        $absRefPrefix = (string)($config['absRefPrefix'] ?? '');
        if ($absRefPrefix === '') {
            return $content;
        }

        return (string)preg_replace_callback(
            '/(\ssrc=)(["\'])(.*?)\2/',
            static function (array $match) use ($absRefPrefix): string {
                $source = $match[3];
                // cid:, data:, http:, https:, mailto: - alles mit Schema bleibt.
                if ($source === '' || preg_match('#^[a-z][a-z0-9+.-]*:#i', $source) === 1) {
                    return $match[0];
                }
                $source = $absRefPrefix . ltrim($source, '/');

                return $match[1] . $match[2] . $source . $match[2];
            },
            $content
        );
    }

    /**
     * Mail-Adressen aus einem String oder einer Liste bauen.
     * Leere Listen bzw. Listen mit ausschliesslich leeren Strings -> null.
     * @return Address[]|null
     */
    protected function toAddressList(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }
        $list = is_array($value) ? $value : [$value];
        $addresses = [];
        foreach ($list as $key => $entry) {
            // ['mail@x' => 'Name'] genauso zulassen wie ['mail@x']
            $mail = is_string($key) && str_contains($key, '@') ? $key : (string)$entry;
            $name = is_string($key) && str_contains($key, '@') ? (string)$entry : '';
            $mail = trim($mail);
            if ($mail === '') {
                continue;
            }
            $addresses[] = new Address($mail, $name);
        }
        return $addresses === [] ? null : $addresses;
    }

    /**
     * Datensatz des angemeldeten FE-Users (frueher $GLOBALS['TSFE']->fe_user->user).
     */
    protected function getFrontendUserRecord(): array
    {
        $frontendUser = $this->serverRequest?->getAttribute('frontend.user');
        return is_array($frontendUser->user ?? null) ? $frontendUser->user : [];
    }

    /**
     * Plugin-Settings aus dem TypoScript des Requests.
     *
     * Frueher las der Bootstrap $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_phlorder_cockpit.'] -
     * diesen Zweig gibt es im Setup gar nicht (es heisst tx_phlorder_order), die
     * Property war also seit jeher leer.
     */
    protected function initSettings(): void
    {
        $frontendTypoScript = $this->serverRequest?->getAttribute('frontend.typoscript');
        $setup = ($frontendTypoScript !== null && $frontendTypoScript->hasSetup())
            ? $frontendTypoScript->getSetupArray()
            : [];

        $settings = $setup['plugin.']['tx_phlorder_order.']['settings.'] ?? [];
        $this->settings = $this->typoScriptService->convertTypoScriptArrayToPlainArray($settings);
    }

    /**
     * JSON-Antwort im unveraenderten Kontrakt {"success": "true"|"false", "message": ...}.
     * Frueher: echo json_encode(...) + exit.
     */
    protected function message(bool $success, string $message): ResponseInterface
    {
        return new JsonResponse([
            'success' => $success ? 'true' : 'false',
            'message' => trim($message),
        ]);
    }
}
