<?php
namespace Pharmaline\Phlorder\Tests\Unit\Utility\Ajax;

use Pharmaline\Phlorder\Domain\Repository\OrderRepository;
use Pharmaline\Phlorder\Utility\Ajax\phlorderEid;
use Pharmaline\Phlqr\Service\QrCodeService;
use Pharmaline\Phlusereditor\Service\PhluserService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testfall fuer die reinen Hilfsfunktionen des eID-Workers.
 *
 * Getestet wird bewusst nur das, was ohne Datenbank und ohne Request auskommt:
 * Telefonnummern-Normalisierung, Praeferenz-Lookup, Zeitfenster-Ermittlung und
 * der Adressbau fuer den Symfony-Mailer. Die Methoden sind protected, deshalb
 * ueber Reflection - das ist hier bewusst in Kauf genommen, weil die Logik sonst
 * gar nicht abgedeckt waere.
 */
final class PhlorderEidTest extends UnitTestCase
{
    protected phlorderEid $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new phlorderEid(
            $this->createStub(OrderRepository::class),
            $this->createStub(PhluserService::class),
            $this->createStub(ViewFactoryInterface::class),
            $this->createStub(PersistenceManagerInterface::class),
            new TypoScriptService(),
            $this->createStub(QrCodeService::class)
        );
    }

    /**
     * @param mixed ...$args
     */
    private function call(string $method, ...$args): mixed
    {
        $ref = new \ReflectionMethod($this->subject, $method);
        return $ref->invoke($this->subject, ...$args);
    }

    public static function whatsAppPhoneProvider(): array
    {
        return [
            'bereits deutsch'          => ['491701234567', '491701234567'],
            'fuehrende Doppelnull'     => ['00491701234567', '491701234567'],
            'Plus-Notation'            => ['+49 170 1234567', '491701234567'],
            'nationale Schreibweise'   => ['0170 1234567', '491701234567'],
            'mit Klammern und Strich'  => ['(0170) 123-4567', '491701234567'],
        ];
    }

    #[Test]
    #[DataProvider('whatsAppPhoneProvider')]
    public function getWhatsAppPhoneNormalisiert(string $input, string $expected): void
    {
        self::assertSame($expected, $this->call('getWhatsAppPhone', $input));
    }

    #[Test]
    public function getPrefFieldFindetDenPassendenSchluessel(): void
    {
        $prefs = [
            ['prefkey' => 'srvOrderUsage', 'prefvalue' => '1', 'preftext' => ''],
            ['prefkey' => 'srvEmail', 'prefvalue' => 'shop@example.org', 'preftext' => '{"cc":[]}'],
        ];

        self::assertSame('1', $this->call('getPrefField', $prefs, 'srvOrderUsage', 'prefvalue'));
        self::assertSame('shop@example.org', $this->call('getPrefField', $prefs, 'srvEmail', 'prefvalue'));
        self::assertSame('{"cc":[]}', $this->call('getPrefField', $prefs, 'srvEmail', 'preftext'));
    }

    #[Test]
    public function getPrefFieldLiefertNullBeiUnbekanntemSchluessel(): void
    {
        self::assertNull($this->call('getPrefField', [['prefkey' => 'a', 'prefvalue' => 'x']], 'b', 'prefvalue'));
    }

    #[Test]
    public function checkTimeLiefertDasNaechsteFenster(): void
    {
        // [Grenze in HHMM, Zielzeit]
        $dayTime = [[1200, '12:30'], [1600, '16:30']];

        self::assertSame('12:30', $this->call('checkTime', $dayTime, 900));
        self::assertSame('16:30', $this->call('checkTime', $dayTime, 1300));
        self::assertFalse($this->call('checkTime', $dayTime, 1800));
    }

    #[Test]
    public function checkTimeLiefertFalseBeiLeeremTag(): void
    {
        self::assertFalse($this->call('checkTime', [], 1000));
    }

    #[Test]
    public function toAddressListAkzeptiertMailNamePaare(): void
    {
        $result = $this->call('toAddressList', ['shop@example.org' => 'Testfirma']);

        self::assertIsArray($result);
        self::assertCount(1, $result);
        self::assertInstanceOf(Address::class, $result[0]);
        self::assertSame('shop@example.org', $result[0]->getAddress());
        self::assertSame('Testfirma', $result[0]->getName());
    }

    #[Test]
    public function toAddressListAkzeptiertEinfacheListe(): void
    {
        $result = $this->call('toAddressList', ['a@example.org', 'b@example.org']);

        self::assertCount(2, $result);
        self::assertSame('a@example.org', $result[0]->getAddress());
        self::assertSame('b@example.org', $result[1]->getAddress());
    }

    /**
     * Die cc/bcc-Listen aus den Praeferenzen enthalten oft [""] - das galt schon
     * in der Altfassung als "nicht gesetzt" und muss null ergeben, sonst wirft
     * der Symfony-Mailer bei leerer Adresse.
     */
    #[Test]
    public function toAddressListLiefertNullBeiLeerenWerten(): void
    {
        self::assertNull($this->call('toAddressList', null));
        self::assertNull($this->call('toAddressList', []));
        self::assertNull($this->call('toAddressList', ['']));
        self::assertNull($this->call('toAddressList', ['   ']));
    }

    /**
     * addAbsPrefix() macht relative Bildquellen absolut, damit sie im Postfach
     * noch aufloesen. Quellen mit eigenem Schema muessen dabei unangetastet
     * bleiben - cid: ist der eingebettete QR-Code (Phase 8).
     */
    #[Test]
    #[DataProvider('absPrefixQuellen')]
    public function addAbsPrefixLaesstFremdeSchemataInRuhe(string $input, string $expected): void
    {
        $this->givenAbsRefPrefix('https://example.org/');

        self::assertSame($expected, $this->call('addAbsPrefix', $input));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function absPrefixQuellen(): array
    {
        return [
            'relative Quelle bekommt den Prefix' => [
                '<img src="fileadmin/bild.png">',
                '<img src="https://example.org/fileadmin/bild.png">',
            ],
            'fuehrender Schraegstrich ergibt keinen doppelten' => [
                '<img src="/fileadmin/bild.png">',
                '<img src="https://example.org/fileadmin/bild.png">',
            ],
            'cid bleibt cid' => [
                '<img src="cid:qrcode">',
                '<img src="cid:qrcode">',
            ],
            'absolute URL bleibt absolut' => [
                '<img src="https://cdn.example.net/bild.png">',
                '<img src="https://cdn.example.net/bild.png">',
            ],
            'data-URI bleibt unangetastet' => [
                '<img src="data:image/png;base64,AAAA">',
                '<img src="data:image/png;base64,AAAA">',
            ],
        ];
    }

    /**
     * Links im Mailtext duerfen dabei nicht angefasst werden. Bis Phase 8 lief
     * ein pauschales str_replace('//', '/') ueber den gesamten Text und machte
     * aus jeder absoluten URL eine kaputte ("https:/host/...").
     */
    #[Test]
    public function addAbsPrefixLaesstLinksUnveraendert(): void
    {
        $this->givenAbsRefPrefix('https://example.org/');

        $html = '<a href="https://example.org/index.php?id=20&t=abc">Status</a>';

        self::assertSame($html, $this->call('addAbsPrefix', $html));
    }

    /**
     * Ohne konfiguriertes absRefPrefix bleibt der Inhalt unveraendert.
     */
    #[Test]
    public function addAbsPrefixOhnePrefixAendertNichts(): void
    {
        $this->givenAbsRefPrefix('');

        self::assertSame('<img src="fileadmin/bild.png">', $this->call('addAbsPrefix', '<img src="fileadmin/bild.png">'));
    }

    /**
     * absRefPrefix kommt aus dem Request-Attribut "frontend.typoscript".
     */
    private function givenAbsRefPrefix(string $absRefPrefix): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setConfigArray($absRefPrefix === '' ? [] : ['absRefPrefix' => $absRefPrefix]);

        $request = (new ServerRequest('https://example.org/'))
            ->withAttribute('frontend.typoscript', $frontendTypoScript);

        $property = new \ReflectionProperty($this->subject, 'serverRequest');
        $property->setValue($this->subject, $request);
    }
}
