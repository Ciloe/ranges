<?php

declare(strict_types=1);

namespace Tests\Ciloe\Ranges;

use Ciloe\Ranges\DateRange;
use Ciloe\Ranges\Exception\InvalidBoundException;
use Ciloe\Ranges\Exception\InvalidInfiniteBoundException;
use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DateRangeTest extends TestCase
{
    public function testFromStringValidRanges()
    {
        $range = DateRange::fromString('[2025-06-04,2025-06-07]');
        $this->assertEquals(new DateTimeImmutable('2025-06-04'), $range->lower);
        $this->assertEquals(new DateTimeImmutable('2025-06-07'), $range->upper);
        $this->assertEquals('[', $range->lowerBound);
        $this->assertEquals(']', $range->upperBound);
        $this->assertEquals(new DateTimeImmutable('2025-06-04'), $range->getLowerBoundValue());
        $this->assertEquals(new DateTimeImmutable('2025-06-07'), $range->getUpperBoundValue());

        $range = DateRange::fromString('(2025-06-04,2025-06-07)');
        $this->assertEquals(new DateTimeImmutable('2025-06-04'), $range->lower);
        $this->assertEquals(new DateTimeImmutable('2025-06-07'), $range->upper);
        $this->assertEquals('(', $range->lowerBound);
        $this->assertEquals(')', $range->upperBound);
        $this->assertEquals(new DateTimeImmutable('2025-06-05'), $range->getLowerBoundValue());
        $this->assertEquals(new DateTimeImmutable('2025-06-06'), $range->getUpperBoundValue());

        $range = DateRange::fromString('(,2025-06-07)');
        $this->assertNull($range->lower);
        $this->assertEquals(new DateTimeImmutable('2025-06-07'), $range->upper);
        $this->assertEquals('(', $range->lowerBound);
        $this->assertEquals(')', $range->upperBound);
        $this->assertNull($range->getLowerBoundValue());
        $this->assertEquals(new DateTimeImmutable('2025-06-06'), $range->getUpperBoundValue());

        $range = DateRange::fromString('(2025-06-04,)');
        $this->assertEquals(new DateTimeImmutable('2025-06-04'), $range->lower);
        $this->assertNull($range->upper);
        $this->assertEquals('(', $range->lowerBound);
        $this->assertEquals(')', $range->upperBound);
        $this->assertEquals(new DateTimeImmutable('2025-06-05'), $range->getLowerBoundValue());
        $this->assertNull($range->getUpperBoundValue());
    }

    public function testFromStringInvalidRanges()
    {
        $this->expectException(InvalidArgumentException::class);
        DateRange::fromString('invalid');
    }

    public function testFromStringInvalidInfiniteBounds()
    {
        $this->expectException(InvalidInfiniteBoundException::class);
        DateRange::fromString('[,2025-06-07)');
    }

    public function testFromStringInvalidBounds()
    {
        $this->expectException(InvalidBoundException::class);
        DateRange::fromString('[2025-06-07,2025-06-04]');
    }

    public function testContainsWithInclusiveBounds()
    {
        $range = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            new DateTimeImmutable('2025-06-07'),
            '[',
            ']'
        );

        $this->assertTrue($range->contains(new DateTimeImmutable('2025-06-04')));
        $this->assertTrue($range->contains(new DateTimeImmutable('2025-06-05')));
        $this->assertTrue($range->contains(new DateTimeImmutable('2025-06-06')));
        $this->assertTrue($range->contains(new DateTimeImmutable('2025-06-07')));
        $this->assertFalse($range->contains(new DateTimeImmutable('2025-06-03')));
        $this->assertFalse($range->contains(new DateTimeImmutable('2025-06-08')));
    }

    public function testContainsWithExclusiveBounds()
    {
        $range = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            new DateTimeImmutable('2025-06-07'),
            '(',
            ')'
        );

        $this->assertFalse($range->contains(new DateTimeImmutable('2025-06-04')));
        $this->assertTrue($range->contains(new DateTimeImmutable('2025-06-05')));
        $this->assertTrue($range->contains(new DateTimeImmutable('2025-06-06')));
        $this->assertFalse($range->contains(new DateTimeImmutable('2025-06-07')));
        $this->assertFalse($range->contains(new DateTimeImmutable('2025-06-03')));
        $this->assertFalse($range->contains(new DateTimeImmutable('2025-06-08')));
    }

    public function testLength()
    {
        $range = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            new DateTimeImmutable('2025-06-07'),
            '[',
            ']'
        );

        $this->assertEquals(4, $range->length());

        $range = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            new DateTimeImmutable('2025-06-07'),
            '(',
            ')'
        );

        $this->assertEquals(2, $range->length());

        $range = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            new DateTimeImmutable('2025-06-04'),
            '[',
            ']'
        );

        $this->assertEquals(1, $range->length());

        $range = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            new DateTimeImmutable('2025-06-04'),
            '(',
            ')'
        );

        $this->assertEquals(0, $range->length());
    }

    public function testGenerateSeries()
    {
        $range = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            new DateTimeImmutable('2025-06-07'),
            '[',
            ']'
        );

        $series = $range->generateSeries();
        $this->assertCount(4, $series);
        $this->assertEquals(new DateTimeImmutable('2025-06-04'), $series[0]);
        $this->assertEquals(new DateTimeImmutable('2025-06-05'), $series[1]);
        $this->assertEquals(new DateTimeImmutable('2025-06-06'), $series[2]);
        $this->assertEquals(new DateTimeImmutable('2025-06-07'), $series[3]);

        $range = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            new DateTimeImmutable('2025-06-07'),
            '(',
            ')'
        );

        $series = $range->generateSeries();
        $this->assertCount(2, $series);
        $this->assertEquals(new DateTimeImmutable('2025-06-05'), $series[0]);
        $this->assertEquals(new DateTimeImmutable('2025-06-06'), $series[1]);
    }

    public function testToString()
    {
        $range = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            new DateTimeImmutable('2025-06-07'),
            '[',
            ']'
        );

        $this->assertEquals('[2025-06-04,2025-06-07]', (string) $range);

        $range = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            new DateTimeImmutable('2025-06-07'),
            '(',
            ')'
        );

        $this->assertEquals('(2025-06-04,2025-06-07)', (string) $range);

        $range = new DateRange(
            null,
            new DateTimeImmutable('2025-06-07'),
            '(',
            ')'
        );

        $this->assertEquals('(,2025-06-07)', (string) $range);

        $range = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            null,
            '(',
            ')'
        );

        $this->assertEquals('(2025-06-04,)', (string) $range);
    }

    public function testOverlap()
    {
        $range1 = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            new DateTimeImmutable('2025-06-07'),
            '[',
            ']'
        );

        $range2 = new DateRange(
            new DateTimeImmutable('2025-06-06'),
            new DateTimeImmutable('2025-06-10'),
            '[',
            ']'
        );

        $this->assertTrue($range1->overlap($range2));

        $range3 = new DateRange(
            new DateTimeImmutable('2025-06-08'),
            new DateTimeImmutable('2025-06-10'),
            '[',
            ']'
        );

        $this->assertFalse($range1->overlap($range3));

        $range4 = new DateRange(
            new DateTimeImmutable('2025-06-07'),
            new DateTimeImmutable('2025-06-10'),
            '(',
            ']'
        );

        $this->assertFalse($range1->overlap($range4));
    }

    public function testUnion()
    {
        $range1 = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            new DateTimeImmutable('2025-06-07'),
            '[',
            ']'
        );

        $range2 = new DateRange(
            new DateTimeImmutable('2025-06-06'),
            new DateTimeImmutable('2025-06-10'),
            '[',
            ']'
        );

        $union = $range1->union($range2);
        $this->assertEquals(new DateTimeImmutable('2025-06-04'), $union->getLowerBoundValue());
        $this->assertEquals(new DateTimeImmutable('2025-06-10'), $union->getUpperBoundValue());
    }

    public function testIntersection()
    {
        $range1 = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            new DateTimeImmutable('2025-06-07'),
            '[',
            ']'
        );

        $range2 = new DateRange(
            new DateTimeImmutable('2025-06-06'),
            new DateTimeImmutable('2025-06-10'),
            '[',
            ']'
        );

        $intersection = $range1->intersection($range2);
        $this->assertEquals(new DateTimeImmutable('2025-06-06'), $intersection->getLowerBoundValue());
        $this->assertEquals(new DateTimeImmutable('2025-06-07'), $intersection->getUpperBoundValue());

        $range3 = new DateRange(
            new DateTimeImmutable('2025-06-08'),
            new DateTimeImmutable('2025-06-10'),
            '[',
            ']'
        );

        $this->assertNull($range1->intersection($range3));
    }

    public function testSplit()
    {
        $range = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            new DateTimeImmutable('2025-06-07'),
            '[',
            ']'
        );

        $split = $range->split(new DateTimeImmutable('2025-06-06'));
        $this->assertCount(2, $split);
        $this->assertEquals(new DateTimeImmutable('2025-06-04'), $split[0]->getLowerBoundValue());
        $this->assertEquals(new DateTimeImmutable('2025-06-05'), $split[0]->getUpperBoundValue());
        $this->assertEquals(new DateTimeImmutable('2025-06-06'), $split[1]->getLowerBoundValue());
        $this->assertEquals(new DateTimeImmutable('2025-06-07'), $split[1]->getUpperBoundValue());
    }

    public function testShift()
    {
        $range = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            new DateTimeImmutable('2025-06-07'),
            '[',
            ']'
        );

        $shifted = $range->shift(new DateInterval('P2D'));
        $this->assertEquals(new DateTimeImmutable('2025-06-06'), $shifted->getLowerBoundValue());
        $this->assertEquals(new DateTimeImmutable('2025-06-09'), $shifted->getUpperBoundValue());
    }

    public function testScaleNotSupported()
    {
        $range = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            new DateTimeImmutable('2025-06-07'),
            '[',
            ']'
        );

        $this->expectException(InvalidArgumentException::class);
        $range->scale(2);
    }
}
