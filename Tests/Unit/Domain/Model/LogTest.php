<?php
namespace Pharmaline\Phlorder\Tests\Unit\Domain\Model;

use Pharmaline\Phlorder\Domain\Model\Log;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testfall fuer das Log-Modell (Vorgangshistorie einer Bestellung).
 */
final class LogTest extends UnitTestCase
{
    protected Log $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new Log();
    }

    /**
     * Wichtig: 716 der 736 Log-Zeilen wurden in Phase 4 von '0000-00-00 00:00:00'
     * auf NULL migriert. Der Setter muss null annehmen, sonst TypeError beim Mapping.
     */
    #[Test]
    public function timestampIsNullByDefault(): void
    {
        self::assertNull($this->subject->getTimestamp());
    }

    #[Test]
    public function timestampAcceptsNull(): void
    {
        $this->subject->setTimestamp(new \DateTime('2018-11-08 18:47:39'));
        $this->subject->setTimestamp(null);

        self::assertNull($this->subject->getTimestamp());
    }

    #[Test]
    public function timestampRoundTripsDateTime(): void
    {
        $date = new \DateTime('2018-11-08 18:47:39');
        $this->subject->setTimestamp($date);

        self::assertSame($date, $this->subject->getTimestamp());
    }

    #[Test]
    public function scalarPropertiesRoundTrip(): void
    {
        $this->subject->setAction('statuschange');
        $this->subject->setResult('ok');
        $this->subject->setValue1('new');
        $this->subject->setValue2('pending');
        $this->subject->setActor('apotheke');
        $this->subject->setFree('Anmerkung');

        self::assertSame('statuschange', $this->subject->getAction());
        self::assertSame('ok', $this->subject->getResult());
        self::assertSame('new', $this->subject->getValue1());
        self::assertSame('pending', $this->subject->getValue2());
        self::assertSame('apotheke', $this->subject->getActor());
        self::assertSame('Anmerkung', $this->subject->getFree());
    }
}
