<?php
namespace Pharmaline\Phlorder\Tests\Unit\Domain\Model;

use Pharmaline\Phlorder\Domain\Model\Item;
use Pharmaline\Phlorder\Domain\Model\Log;
use Pharmaline\Phlorder\Domain\Model\Order;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testfall fuer das Order-Modell.
 *
 * Die Vorgaengerfassung stammte aus dem Extension Builder: sie erbte von der in
 * v13 nicht mehr existierenden Klasse \TYPO3\CMS\Core\Tests\UnitTestCase und
 * bestand aus Testmethoden mit LEEREM Rumpf - also ohne eine einzige Assertion.
 */
final class OrderTest extends UnitTestCase
{
    protected Order $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new Order();
    }

    #[Test]
    public function objectStoragePropertiesAreInitialized(): void
    {
        self::assertInstanceOf(ObjectStorage::class, $this->subject->getOrderImage());
        self::assertInstanceOf(ObjectStorage::class, $this->subject->getTolog());
        self::assertInstanceOf(ObjectStorage::class, $this->subject->getToitem());
        self::assertCount(0, $this->subject->getTolog());
    }

    /**
     * Seit Phase 4 ist die DB-Spalte "timestamp" nullable (Legacy-Default
     * '0000-00-00 00:00:00' entfaellt). Setter und Getter muessen null tragen.
     */
    #[Test]
    public function timestampIsNullByDefault(): void
    {
        self::assertNull($this->subject->getTimestamp());
    }

    #[Test]
    public function timestampAcceptsNull(): void
    {
        $this->subject->setTimestamp(new \DateTime('2020-01-08 19:40:34'));
        $this->subject->setTimestamp(null);

        self::assertNull($this->subject->getTimestamp());
    }

    #[Test]
    public function timestampRoundTripsDateTime(): void
    {
        $date = new \DateTime('2020-01-08 19:40:34');
        $this->subject->setTimestamp($date);

        self::assertSame($date, $this->subject->getTimestamp());
    }

    #[Test]
    public function scalarPropertiesRoundTrip(): void
    {
        $this->subject->setPhluserfid(28);
        $this->subject->setOrderid('29ac0e27-6195-4a75-a018-2599acc90bd1');
        $this->subject->setOrdernumber('0001K0041');
        $this->subject->setStatus('new');
        $this->subject->setCompany('Testfirma');
        $this->subject->setFirstName('Sabrina');
        $this->subject->setLastName('Kurschus');
        $this->subject->setEmail('sabrina@example.org');
        $this->subject->setMobil('+49 170 1234567');
        $this->subject->setDelivery('shipping');

        self::assertSame(28, $this->subject->getPhluserfid());
        self::assertSame('29ac0e27-6195-4a75-a018-2599acc90bd1', $this->subject->getOrderid());
        self::assertSame('0001K0041', $this->subject->getOrdernumber());
        self::assertSame('new', $this->subject->getStatus());
        self::assertSame('Testfirma', $this->subject->getCompany());
        self::assertSame('Sabrina', $this->subject->getFirstName());
        self::assertSame('Kurschus', $this->subject->getLastName());
        self::assertSame('sabrina@example.org', $this->subject->getEmail());
        self::assertSame('+49 170 1234567', $this->subject->getMobil());
        self::assertSame('shipping', $this->subject->getDelivery());
    }

    #[Test]
    public function logsCanBeAddedAndRemoved(): void
    {
        $log = new Log();
        $this->subject->addTolog($log);
        self::assertCount(1, $this->subject->getTolog());

        $this->subject->removeTolog($log);
        self::assertCount(0, $this->subject->getTolog());
    }

    #[Test]
    public function itemsCanBeAddedAndRemoved(): void
    {
        $item = new Item();
        $this->subject->addToitem($item);
        self::assertCount(1, $this->subject->getToitem());

        $this->subject->removeToitem($item);
        self::assertCount(0, $this->subject->getToitem());
    }
}
