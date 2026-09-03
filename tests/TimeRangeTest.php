<?php

declare(strict_types=1);

namespace Tests\Ciloe\Ranges;

use Ciloe\Ranges\Exception\CantGenerateSeriesBecauseTheArrayIsTooLarge;
use Ciloe\Ranges\Exception\InvalidBoundException;
use Ciloe\Ranges\Exception\InvalidInfiniteBoundException;
use Ciloe\Ranges\Exception\InvalidStepToGenerateSeriesException;
use Ciloe\Ranges\Exception\InvalidTimeIntervalException;
use Ciloe\Ranges\RangeInterface;
use Ciloe\Ranges\TimeRange;
use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TimeRangeTest extends TestCase
{
    public function testFromStringValidRanges()
    {
        $range = TimeRange::fromString('[20:03:20,21:03:23]');
        $this->assertEquals('20:03:20', $range->lower->format('H:i:s'));
        $this->assertEquals('21:03:23', $range->upper->format('H:i:s'));
        $this->assertEquals('[', $range->lowerBound);
        $this->assertEquals(']', $range->upperBound);
        $this->assertEquals('20:03:20', $range->getLowerBoundValue()->format('H:i:s'));
        $this->assertEquals('21:03:23', $range->getUpperBoundValue()->format('H:i:s'));

        $range = TimeRange::fromString('(20:03:20,21:03:23)');
        $this->assertEquals('20:03:20', $range->lower->format('H:i:s'));
        $this->assertEquals('21:03:23', $range->upper->format('H:i:s'));
        $this->assertEquals('(', $range->lowerBound);
        $this->assertEquals(')', $range->upperBound);
        $this->assertEquals('20:03:21', $range->getLowerBoundValue()->format('H:i:s'));
        $this->assertEquals('21:03:22', $range->getUpperBoundValue()->format('H:i:s'));

        $range = TimeRange::fromString('(,21:03:23)');
        $this->assertNull($range->lower);
        $this->assertEquals('21:03:23', $range->upper->format('H:i:s'));
        $this->assertEquals('(', $range->lowerBound);
        $this->assertEquals(')', $range->upperBound);
        $this->assertNull($range->getLowerBoundValue());
        $this->assertEquals('21:03:22', $range->getUpperBoundValue()->format('H:i:s'));

        $range = TimeRange::fromString('(20:03:20,)');
        $this->assertEquals('20:03:20', $range->lower->format('H:i:s'));
        $this->assertNull($range->upper);
        $this->assertEquals('(', $range->lowerBound);
        $this->assertEquals(')', $range->upperBound);
        $this->assertEquals('20:03:21', $range->getLowerBoundValue()->format('H:i:s'));
        $this->assertNull($range->getUpperBoundValue());
    }

    public function testFromStringInvalidRanges()
    {
        $this->expectException(InvalidArgumentException::class);
        TimeRange::fromString('invalid');
    }

    public function testFromStringInvalidInfiniteBounds()
    {
        $this->expectException(InvalidInfiniteBoundException::class);
        TimeRange::fromString('[,21:03:23)');
    }

    public function testFromStringInvalidBounds()
    {
        $this->expectException(InvalidBoundException::class);
        TimeRange::fromString('[21:03:23,20:03:20]');
    }

    public function testContainsWithInclusiveBounds()
    {
        $referenceDate = new DateTimeImmutable('today');
        $range = new TimeRange(
            $referenceDate->setTime(20, 3, 20),
            $referenceDate->setTime(21, 3, 23),
            '[',
            ']'
        );

        $this->assertTrue($range->contains($referenceDate->setTime(20, 3, 20)));
        $this->assertTrue($range->contains($referenceDate->setTime(20, 30, 0)));
        $this->assertTrue($range->contains($referenceDate->setTime(21, 0, 0)));
        $this->assertTrue($range->contains($referenceDate->setTime(21, 3, 23)));
        $this->assertFalse($range->contains($referenceDate->setTime(20, 3, 19)));
        $this->assertFalse($range->contains($referenceDate->setTime(21, 3, 24)));
    }

    public function testContainsWithExclusiveBounds()
    {
        $referenceDate = new DateTimeImmutable('today');
        $range = new TimeRange(
            $referenceDate->setTime(20, 3, 20),
            $referenceDate->setTime(21, 3, 23),
            '(',
            ')'
        );

        $this->assertFalse($range->contains($referenceDate->setTime(20, 3, 20)));
        $this->assertTrue($range->contains($referenceDate->setTime(20, 3, 21)));
        $this->assertTrue($range->contains($referenceDate->setTime(21, 3, 22)));
        $this->assertFalse($range->contains($referenceDate->setTime(21, 3, 23)));
        $this->assertFalse($range->contains($referenceDate->setTime(20, 3, 19)));
        $this->assertFalse($range->contains($referenceDate->setTime(21, 3, 24)));
    }

    public function testLength()
    {
        $referenceDate = new DateTimeImmutable('today');
        $range = new TimeRange(
            $referenceDate->setTime(20, 3, 20),
            $referenceDate->setTime(20, 3, 23),
            '[',
            ']'
        );

        $this->assertEquals(4, $range->length());

        $range = new TimeRange(
            $referenceDate->setTime(20, 3, 20),
            $referenceDate->setTime(20, 3, 23),
            '(',
            ')'
        );

        $this->assertEquals(2, $range->length());

        $range = new TimeRange(
            $referenceDate->setTime(20, 3, 20),
            $referenceDate->setTime(20, 3, 20),
            '[',
            ']'
        );

        $this->assertEquals(1, $range->length());

        $range = new TimeRange(
            $referenceDate->setTime(20, 3, 20),
            $referenceDate->setTime(20, 3, 20),
            '(',
            ')'
        );

        $this->assertEquals(0, $range->length());
    }

    public function testGenerateSeries()
    {
        $referenceDate = new DateTimeImmutable('today');
        $range = new TimeRange(
            $referenceDate->setTime(20, 3, 20),
            $referenceDate->setTime(20, 3, 23),
            '[',
            ']'
        );

        $series = $range->generateSeries();
        $this->assertCount(4, $series);
        $this->assertEquals('20:03:20', $series[0]->format('H:i:s'));
        $this->assertEquals('20:03:21', $series[1]->format('H:i:s'));
        $this->assertEquals('20:03:22', $series[2]->format('H:i:s'));
        $this->assertEquals('20:03:23', $series[3]->format('H:i:s'));

        $range = new TimeRange(
            $referenceDate->setTime(20, 3, 20),
            $referenceDate->setTime(20, 3, 23),
            '(',
            ')'
        );

        $series = $range->generateSeries();
        $this->assertCount(2, $series);
        $this->assertEquals('20:03:21', $series[0]->format('H:i:s'));
        $this->assertEquals('20:03:22', $series[1]->format('H:i:s'));
    }

    public function testToString()
    {
        $referenceDate = new DateTimeImmutable('today');
        $range = new TimeRange(
            $referenceDate->setTime(20, 3, 20),
            $referenceDate->setTime(21, 3, 23),
            '[',
            ']'
        );

        $this->assertEquals('[20:03:20,21:03:23]', (string) $range);

        $range = new TimeRange(
            $referenceDate->setTime(20, 3, 20),
            $referenceDate->setTime(21, 3, 23),
            '(',
            ')'
        );

        $this->assertEquals('(20:03:20,21:03:23)', (string) $range);

        $range = new TimeRange(
            null,
            $referenceDate->setTime(21, 3, 23),
            '(',
            ')'
        );

        $this->assertEquals('(,21:03:23)', (string) $range);

        $range = new TimeRange(
            $referenceDate->setTime(20, 3, 20),
            null,
            '(',
            ')'
        );

        $this->assertEquals('(20:03:20,)', (string) $range);
    }

    public function testOverlap()
    {
        $referenceDate = new DateTimeImmutable('today');
        $range1 = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(22, 0, 0),
            '[',
            ']'
        );

        $range2 = new TimeRange(
            $referenceDate->setTime(21, 0, 0),
            $referenceDate->setTime(23, 0, 0),
            '[',
            ']'
        );

        $this->assertTrue($range1->overlap($range2));

        $range3 = new TimeRange(
            $referenceDate->setTime(22, 0, 1),
            $referenceDate->setTime(23, 0, 0),
            '[',
            ']'
        );

        $this->assertFalse($range1->overlap($range3));

        $range4 = new TimeRange(
            $referenceDate->setTime(22, 0, 0),
            $referenceDate->setTime(23, 0, 0),
            '(',
            ']'
        );

        $this->assertFalse($range1->overlap($range4));
    }

    public function testUnion()
    {
        $referenceDate = new DateTimeImmutable('today');
        $range1 = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(22, 0, 0),
            '[',
            ']'
        );

        $range2 = new TimeRange(
            $referenceDate->setTime(21, 0, 0),
            $referenceDate->setTime(23, 0, 0),
            '[',
            ']'
        );

        $union = $range1->union($range2);
        $this->assertEquals('20:00:00', $union->getLowerBoundValue()->format('H:i:s'));
        $this->assertEquals('23:00:00', $union->getUpperBoundValue()->format('H:i:s'));
    }

    public function testIntersection()
    {
        $referenceDate = new DateTimeImmutable('today');
        $range1 = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(22, 0, 0),
            '[',
            ']'
        );

        $range2 = new TimeRange(
            $referenceDate->setTime(21, 0, 0),
            $referenceDate->setTime(23, 0, 0),
            '[',
            ']'
        );

        $intersection = $range1->intersection($range2);
        $this->assertEquals('21:00:00', $intersection->getLowerBoundValue()->format('H:i:s'));
        $this->assertEquals('22:00:00', $intersection->getUpperBoundValue()->format('H:i:s'));

        $range3 = new TimeRange(
            $referenceDate->setTime(22, 0, 1),
            $referenceDate->setTime(23, 0, 0),
            '[',
            ']'
        );

        $this->assertNull($range1->intersection($range3));
    }

    public function testSplit()
    {
        $referenceDate = new DateTimeImmutable('today');
        $range = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(22, 0, 0),
            '[',
            ']'
        );

        $split = $range->split($referenceDate->setTime(21, 0, 0));
        $this->assertCount(2, $split);
        $this->assertEquals('20:00:00', $split[0]->getLowerBoundValue()->format('H:i:s'));
        $this->assertEquals('20:59:59', $split[0]->getUpperBoundValue()->format('H:i:s'));
        $this->assertEquals('21:00:00', $split[1]->getLowerBoundValue()->format('H:i:s'));
        $this->assertEquals('22:00:00', $split[1]->getUpperBoundValue()->format('H:i:s'));
    }

    public function testShift()
    {
        $referenceDate = new DateTimeImmutable('today');
        $range = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(22, 0, 0),
            '[',
            ']'
        );

        $shifted = $range->shift(new DateInterval('PT1H'));
        $this->assertEquals('21:00:00', $shifted->getLowerBoundValue()->format('H:i:s'));
        $this->assertEquals('23:00:00', $shifted->getUpperBoundValue()->format('H:i:s'));
    }

    public function testScaleNotSupported()
    {
        $referenceDate = new DateTimeImmutable('today');
        $range = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(22, 0, 0),
            '[',
            ']'
        );

        $this->expectException(InvalidArgumentException::class);
        $range->scale(2);
    }

    public function testInvalidTimeIntervalInConstructor()
    {
        $referenceDate = new DateTimeImmutable('today');
        $this->expectException(InvalidTimeIntervalException::class);
        new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(22, 0, 0),
            '[',
            ']',
            new DateInterval('P1MT1S'),
        );
    }

    public function testInvalidTimeIntervalInShift()
    {
        $referenceDate = new DateTimeImmutable('today');
        $range = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(22, 0, 0),
            '[',
            ']'
        );

        $this->expectException(InvalidTimeIntervalException::class);
        $range->shift(new DateInterval('P1MT1S'));
    }

    public function testIsEmpty()
    {
        $referenceDate = new DateTimeImmutable('today');

        $range = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(20, 0, 0),
            '(',
            ')'
        );
        $this->assertTrue($range->isEmpty());

        $range = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(20, 0, 0),
            '[',
            ')'
        );
        $this->assertFalse($range->isEmpty());

        $range = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(20, 0, 0),
            '[',
            ']'
        );
        $this->assertFalse($range->isEmpty());

        $range = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(21, 0, 0),
            '(',
            ')'
        );
        $this->assertFalse($range->isEmpty());

        $range = new TimeRange(
            null,
            $referenceDate->setTime(21, 0, 0),
            '(',
            ')'
        );
        $this->assertFalse($range->isEmpty());

        $range = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            null,
            '(',
            ')'
        );
        $this->assertFalse($range->isEmpty());
    }

    public function testIsBoundsValid()
    {
        $referenceDate = new DateTimeImmutable('today');

        $range = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(21, 0, 0),
            '[',
            ']'
        );
        $this->assertTrue($range->isBoundsValid());

        $range = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(20, 0, 0),
            '[',
            ']'
        );
        $this->assertTrue($range->isBoundsValid());

        $range = new TimeRange(
            $referenceDate->setTime(21, 0, 0),
            $referenceDate->setTime(20, 0, 0),
            '[',
            ']'
        );
        $this->assertFalse($range->isBoundsValid());

        $range = new TimeRange(
            null,
            $referenceDate->setTime(21, 0, 0),
            '(',
            ')'
        );
        $this->assertTrue($range->isBoundsValid());

        $range = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            null,
            '(',
            ')'
        );
        $this->assertTrue($range->isBoundsValid());

        $range = new TimeRange(
            null,
            null,
            '(',
            ')'
        );
        $this->assertTrue($range->isBoundsValid());
    }

    public function testEquals()
    {
        $referenceDate = new DateTimeImmutable('today');

        $range1 = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(21, 0, 0),
            '[',
            ']'
        );

        $range2 = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(21, 0, 0),
            '[',
            ']'
        );
        $this->assertTrue($range1->equals($range2));

        $range3 = new TimeRange(
            $referenceDate->setTime(20, 30, 0),
            $referenceDate->setTime(21, 0, 0),
            '[',
            ']'
        );
        $this->assertFalse($range1->equals($range3));

        $range4 = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(22, 0, 0),
            '[',
            ']'
        );
        $this->assertFalse($range1->equals($range4));

        $range5 = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(21, 0, 0),
            '(',
            ']'
        );
        $this->assertFalse($range1->equals($range5));

        $range6 = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(21, 0, 0),
            '[',
            ']',
            new DateInterval('PT5S')
        );
        $this->assertFalse($range1->equals($range6));

        $differentDate = new DateTimeImmutable('tomorrow');
        $range7 = new TimeRange(
            $differentDate->setTime(20, 0, 0),
            $differentDate->setTime(21, 0, 0),
            '[',
            ']'
        );
        $this->assertTrue($range1->equals($range7));
    }

    public function testClone()
    {
        $referenceDate = new DateTimeImmutable('today');
        $original = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(21, 0, 0),
            '[',
            ']'
        );

        $clone = $original->clone();

        $this->assertEquals('20:00:00', $clone->getLowerBoundValue()->format('H:i:s'));
        $this->assertEquals('21:00:00', $clone->getUpperBoundValue()->format('H:i:s'));
        $this->assertEquals('[', $clone->lowerBound);
        $this->assertEquals(']', $clone->upperBound);

        $this->assertTrue($original->equals($clone));

        $this->assertNotSame($original, $clone);
    }

    public function testCustomStepIntervals()
    {
        $referenceDate = new DateTimeImmutable('today');

        $range = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(21, 0, 0),
            '[',
            ']',
            new DateInterval('PT15M')
        );

        $series = $range->generateSeries();
        $this->assertCount(5, $series);
        $this->assertEquals('20:00:00', $series[0]->format('H:i:s'));
        $this->assertEquals('20:15:00', $series[1]->format('H:i:s'));
        $this->assertEquals('20:30:00', $series[2]->format('H:i:s'));
        $this->assertEquals('20:45:00', $series[3]->format('H:i:s'));
        $this->assertEquals('21:00:00', $series[4]->format('H:i:s'));

        $range = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(20, 2, 0),
            '[',
            ']',
            new DateInterval('PT30S')
        );

        $series = $range->generateSeries();
        $this->assertCount(5, $series);
        $this->assertEquals('20:00:00', $series[0]->format('H:i:s'));
        $this->assertEquals('20:00:30', $series[1]->format('H:i:s'));
        $this->assertEquals('20:01:00', $series[2]->format('H:i:s'));
        $this->assertEquals('20:01:30', $series[3]->format('H:i:s'));
        $this->assertEquals('20:02:00', $series[4]->format('H:i:s'));
    }

    public function testInfiniteBounds()
    {
        $referenceDate = new DateTimeImmutable('today');

        $range = new TimeRange(
            null,
            $referenceDate->setTime(21, 0, 0),
            '(',
            ']'
        );

        $this->assertNull($range->getLowerBoundValue());
        $this->assertEquals('21:00:00', $range->getUpperBoundValue()->format('H:i:s'));
        $this->assertTrue($range->contains($referenceDate->setTime(0, 0, 0)));
        $this->assertTrue($range->contains($referenceDate->setTime(21, 0, 0)));
        $this->assertFalse($range->contains($referenceDate->setTime(21, 0, 1)));

        $range = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            null,
            '[',
            ')'
        );

        $this->assertEquals('20:00:00', $range->getLowerBoundValue()->format('H:i:s'));
        $this->assertNull($range->getUpperBoundValue());
        $this->assertTrue($range->contains($referenceDate->setTime(20, 0, 0)));
        $this->assertTrue($range->contains($referenceDate->setTime(23, 59, 59)));
        $this->assertFalse($range->contains($referenceDate->setTime(19, 59, 59)));

        $range = new TimeRange(
            null,
            null,
            '(',
            ')'
        );

        $this->assertNull($range->getLowerBoundValue());
        $this->assertNull($range->getUpperBoundValue());
        $this->assertTrue($range->contains($referenceDate->setTime(0, 0, 0)));
        $this->assertTrue($range->contains($referenceDate->setTime(12, 0, 0)));
        $this->assertTrue($range->contains($referenceDate->setTime(23, 59, 59)));
    }

    public function testDifferentDatesSameTime()
    {
        $today = new DateTimeImmutable('today');
        $tomorrow = new DateTimeImmutable('tomorrow');

        $range = new TimeRange(
            $today->setTime(20, 0, 0),
            $today->setTime(21, 0, 0),
            '[',
            ']'
        );

        $this->assertTrue($range->contains($tomorrow->setTime(20, 30, 0)));

        $this->assertFalse($range->contains($tomorrow->setTime(19, 59, 59)));
        $this->assertFalse($range->contains($tomorrow->setTime(21, 0, 1)));
    }

    public function testCantGenerateSeriesBecauseTheArrayIsTooLargeWithNullLowerBound()
    {
        $referenceDate = new DateTimeImmutable('today');
        $range = new TimeRange(
            null,
            $referenceDate->setTime(23, 59, 59),
            '(',
            ']'
        );

        $this->expectException(CantGenerateSeriesBecauseTheArrayIsTooLarge::class);
        $range->generateSeries();
    }

    public function testCantGenerateSeriesBecauseTheArrayIsTooLargeWithNullUpperBound()
    {
        $referenceDate = new DateTimeImmutable('today');
        $range = new TimeRange(
            $referenceDate->setTime(0, 0, 0),
            null,
            '[',
            ')'
        );

        $this->expectException(CantGenerateSeriesBecauseTheArrayIsTooLarge::class);
        $range->generateSeries();
    }

    public function testInvalidStepToGenerateSeriesException()
    {
        $referenceDate = new DateTimeImmutable('today');
        $range = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(20, 0, 1),
            '[',
            ']',
            new DateInterval('PT2S')
        );

        $this->expectException(InvalidStepToGenerateSeriesException::class);
        $range->generateSeries();
    }

    public function testCompareTimeOnlyWithDifferentDates()
    {
        $today = new DateTimeImmutable('today');
        $tomorrow = new DateTimeImmutable('tomorrow');
        $nextWeek = new DateTimeImmutable('next week');

        $range1 = new TimeRange(
            $today->setTime(20, 0, 0),
            $today->setTime(21, 0, 0),
            '[',
            ']'
        );

        $range2 = new TimeRange(
            $tomorrow->setTime(20, 0, 0),
            $tomorrow->setTime(21, 0, 0),
            '[',
            ']'
        );

        $range3 = new TimeRange(
            $nextWeek->setTime(20, 0, 0),
            $nextWeek->setTime(21, 0, 0),
            '[',
            ']'
        );

        $this->assertTrue($range1->equals($range2));
        $this->assertTrue($range2->equals($range3));
        $this->assertTrue($range1->equals($range3));

        $this->assertTrue($range1->overlap($range2));
        $this->assertTrue($range2->overlap($range3));
        $this->assertTrue($range1->overlap($range3));

        $intersection = $range1->intersection($range2);
        $this->assertNotNull($intersection);
        $this->assertEquals('20:00:00', $intersection->getLowerBoundValue()->format('H:i:s'));
        $this->assertEquals('21:00:00', $intersection->getUpperBoundValue()->format('H:i:s'));
    }

    public function testInvalidRangeTypeInOverlap()
    {
        $referenceDate = new DateTimeImmutable('today');
        $timeRange = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(21, 0, 0),
            '[',
            ']'
        );

        $mockRange = $this->createMock(RangeInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $timeRange->overlap($mockRange);
    }

    public function testInvalidRangeTypeInUnion()
    {
        $referenceDate = new DateTimeImmutable('today');
        $timeRange = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(21, 0, 0),
            '[',
            ']'
        );

        $mockRange = $this->createMock(RangeInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $timeRange->union($mockRange);
    }

    public function testInvalidRangeTypeInIntersection()
    {
        $referenceDate = new DateTimeImmutable('today');
        $timeRange = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(21, 0, 0),
            '[',
            ']'
        );

        $mockRange = $this->createMock(RangeInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $timeRange->intersection($mockRange);
    }

    public function testInvalidRangeTypeInEquals()
    {
        $referenceDate = new DateTimeImmutable('today');
        $timeRange = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(21, 0, 0),
            '[',
            ']'
        );

        $mockRange = $this->createMock(RangeInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $timeRange->equals($mockRange);
    }

    public function testDifferentStepUnitsInUnion()
    {
        $referenceDate = new DateTimeImmutable('today');
        $range1 = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(21, 0, 0),
            '[',
            ']',
            new DateInterval('PT1S')
        );

        $range2 = new TimeRange(
            $referenceDate->setTime(20, 30, 0),
            $referenceDate->setTime(21, 30, 0),
            '[',
            ']',
            new DateInterval('PT2S')
        );

        $this->assertNull($range1->union($range2));
    }

    public function testDifferentStepUnitsInIntersection()
    {
        $referenceDate = new DateTimeImmutable('today');
        $range1 = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(21, 0, 0),
            '[',
            ']',
            new DateInterval('PT1S')
        );

        $range2 = new TimeRange(
            $referenceDate->setTime(20, 30, 0),
            $referenceDate->setTime(21, 30, 0),
            '[',
            ']',
            new DateInterval('PT2S')
        );

        $this->assertNull($range1->intersection($range2));
    }

    public function testInvalidValueTypeInContains()
    {
        $referenceDate = new DateTimeImmutable('today');
        $range = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(21, 0, 0),
            '[',
            ']'
        );

        $this->expectException(InvalidArgumentException::class);
        $range->contains('not a datetime');
    }

    public function testInvalidPointTypeInSplit()
    {
        $referenceDate = new DateTimeImmutable('today');
        $range = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(21, 0, 0),
            '[',
            ']'
        );

        $this->expectException(InvalidArgumentException::class);
        $range->split('not a datetime');
    }

    public function testInvalidOffsetTypeInShift()
    {
        $referenceDate = new DateTimeImmutable('today');
        $range = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(21, 0, 0),
            '[',
            ']'
        );

        $this->expectException(InvalidArgumentException::class);
        $range->shift('not a dateinterval');
    }

    public function testLengthWithNullBounds()
    {
        $referenceDate = new DateTimeImmutable('today');

        $range = new TimeRange(
            null,
            $referenceDate->setTime(21, 0, 0),
            '(',
            ']'
        );
        $this->assertNull($range->length());

        $range = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            null,
            '[',
            ')'
        );
        $this->assertNull($range->length());

        $range = new TimeRange(null, null, '(', ')');
        $this->assertNull($range->length());
    }

    public function testLengthWithCustomStep()
    {
        $referenceDate = new DateTimeImmutable('today');

        $range = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(21, 0, 0),
            '[',
            ']',
            new DateInterval('PT15M')
        );
        $this->assertEquals(5, $range->length());

        $range = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(20, 0, 10),
            '[',
            ']',
            new DateInterval('PT2S')
        );
        $this->assertEquals(6, $range->length());
    }

    public function testUnionWithNullBounds()
    {
        $referenceDate = new DateTimeImmutable('today');

        $range1 = new TimeRange(
            null,
            $referenceDate->setTime(22, 0, 0),
            '(',
            ']'
        );

        $range2 = new TimeRange(
            null,
            $referenceDate->setTime(21, 0, 0),
            '(',
            ']'
        );

        $union = $range1->union($range2);
        $this->assertNull($union->getLowerBoundValue());
        $this->assertEquals('22:00:00', $union->getUpperBoundValue()->format('H:i:s'));

        $range1 = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            null,
            '[',
            ')'
        );

        $range2 = new TimeRange(
            $referenceDate->setTime(18, 0, 0),
            null,
            '[',
            ')'
        );

        $union = $range1->union($range2);
        $this->assertEquals('18:00:00', $union->getLowerBoundValue()->format('H:i:s'));
        $this->assertNull($union->getUpperBoundValue());

        $range1 = new TimeRange(
            null,
            $referenceDate->setTime(22, 0, 0),
            '(',
            ']'
        );

        $range2 = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            null,
            '[',
            ')'
        );

        $union = $range1->union($range2);
        $this->assertNull($union->getLowerBoundValue());
        $this->assertNull($union->getUpperBoundValue());
    }

    public function testIntersectionWithNullBounds()
    {
        $referenceDate = new DateTimeImmutable('today');

        $range1 = new TimeRange(
            null,
            $referenceDate->setTime(22, 0, 0),
            '(',
            ']'
        );

        $range2 = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            null,
            '[',
            ')'
        );

        $intersection = $range1->intersection($range2);
        $this->assertNotNull($intersection);
        $this->assertEquals('20:00:00', $intersection->getLowerBoundValue()->format('H:i:s'));
        $this->assertEquals('22:00:00', $intersection->getUpperBoundValue()->format('H:i:s'));

        $range1 = new TimeRange(
            null,
            $referenceDate->setTime(22, 0, 0),
            '(',
            ']'
        );

        $range2 = new TimeRange(
            null,
            $referenceDate->setTime(21, 0, 0),
            '(',
            ']'
        );

        $intersection = $range1->intersection($range2);
        $this->assertNotNull($intersection);
        $this->assertNull($intersection->getLowerBoundValue());
        $this->assertEquals('21:00:00', $intersection->getUpperBoundValue()->format('H:i:s'));

        $range1 = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            null,
            '[',
            ')'
        );

        $range2 = new TimeRange(
            $referenceDate->setTime(21, 0, 0),
            null,
            '[',
            ')'
        );

        $intersection = $range1->intersection($range2);
        $this->assertNotNull($intersection);
        $this->assertEquals('21:00:00', $intersection->getLowerBoundValue()->format('H:i:s'));
        $this->assertNull($intersection->getUpperBoundValue());
    }

    public function testSplitOutsideRange()
    {
        $referenceDate = new DateTimeImmutable('today');
        $range = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(22, 0, 0),
            '[',
            ']'
        );

        $result = $range->split($referenceDate->setTime(19, 0, 0));
        $this->assertCount(1, $result);
        $this->assertSame($range, $result[0]);

        $result = $range->split($referenceDate->setTime(23, 0, 0));
        $this->assertCount(1, $result);
        $this->assertSame($range, $result[0]);
    }

    public function testEqualsWithNullBounds()
    {
        $referenceDate = new DateTimeImmutable('today');

        $range1 = new TimeRange(
            null,
            $referenceDate->setTime(21, 0, 0),
            '(',
            ']'
        );

        $range2 = new TimeRange(
            null,
            $referenceDate->setTime(21, 0, 0),
            '(',
            ']'
        );

        $this->assertTrue($range1->equals($range2));

        $range3 = new TimeRange(
            $referenceDate->setTime(20, 0, 0),
            $referenceDate->setTime(21, 0, 0),
            '[',
            ']'
        );

        $this->assertFalse($range1->equals($range3));

        $range4 = new TimeRange(null, null, '(', ')');
        $range5 = new TimeRange(null, null, '(', ')');
        $this->assertTrue($range4->equals($range5));
    }

    public function testCloneWithNullBoundsAndCustomStep()
    {
        $referenceDate = new DateTimeImmutable('today');

        $original = new TimeRange(
            null,
            $referenceDate->setTime(21, 0, 0),
            '(',
            ']',
            new DateInterval('PT30S')
        );

        $clone = $original->clone();
        $this->assertTrue($original->equals($clone));
        $this->assertNotSame($original, $clone);
        $this->assertNull($clone->lower);

        $original = new TimeRange(null, null, '(', ')');
        $clone = $original->clone();
        $this->assertTrue($original->equals($clone));
        $this->assertNull($clone->lower);
        $this->assertNull($clone->upper);
    }
}
