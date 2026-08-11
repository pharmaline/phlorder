<?php
namespace Pharmaline\Phlorder\Tests\Unit\Domain\Repository;

use Pharmaline\Phlorder\Domain\Repository\OrderRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testfall fuer die Sortierung der Cockpit-Bestellliste.
 *
 * Nur buildOrderings() ist hier pruefbar - es ist die einzige Methode des
 * Repositories ohne Datenbankzugriff. Sie ist zugleich die sicherheitsrelevante:
 * ihr Rueckgabewert landet in der ORDER-BY-Klausel.
 */
final class OrderRepositoryTest extends UnitTestCase
{
    protected OrderRepository $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new OrderRepository();
    }

    #[Test]
    public function defaultIsNewestFirst(): void
    {
        self::assertSame(
            ['timestamp' => QueryInterface::ORDER_DESCENDING],
            $this->subject->buildOrderings('', '')
        );
    }

    #[Test]
    public function ascendingIsRecognised(): void
    {
        self::assertSame(
            ['ordernumber' => QueryInterface::ORDER_ASCENDING],
            $this->subject->buildOrderings('ordernumber', 'asc')
        );
    }

    /**
     * Gross-/Kleinschreibung und Leerzeichen duerfen die Richtung nicht kippen -
     * der Wert kommt aus einer FlexForm und laesst sich von Hand editieren.
     */
    #[Test]
    public function directionIsNormalised(): void
    {
        self::assertSame(
            ['status' => QueryInterface::ORDER_ASCENDING],
            $this->subject->buildOrderings('status', ' ASC ')
        );
    }

    /**
     * Der eigentliche Schutz: alles, was nicht in SORT_FIELDS steht, faellt auf
     * das Default-Feld zurueck und kann nicht in die Query durchschlagen.
     */
    #[Test]
    #[DataProvider('unknownSortFields')]
    public function unknownFieldFallsBackToDefault(string $field): void
    {
        self::assertSame(
            ['timestamp' => QueryInterface::ORDER_DESCENDING],
            $this->subject->buildOrderings($field, 'desc')
        );
    }

    public static function unknownSortFields(): array
    {
        return [
            'leer' => [''],
            'unbekannte Property' => ['iban'],
            'Spaltenname statt Property' => ['last_name'],
            'SQL-Fragment' => ['uid; DROP TABLE tx_phlorder_domain_model_order'],
            'Funktionsaufruf' => ['RAND()'],
        ];
    }

    #[Test]
    public function everyWhitelistedFieldIsAccepted(): void
    {
        foreach (OrderRepository::SORT_FIELDS as $flexFormValue => $property) {
            self::assertSame(
                [$property => QueryInterface::ORDER_ASCENDING],
                $this->subject->buildOrderings($flexFormValue, 'asc'),
                'Sortierfeld ' . $flexFormValue
            );
        }
    }
}
