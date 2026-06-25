<?php

declare(strict_types=1);

namespace Tests\Ciloe\Ranges;

use Ciloe\Ranges\DateRange;
use Ciloe\Ranges\Exception\CantGenerateSeriesBecauseTheArrayIsTooLarge;
use Ciloe\Ranges\Exception\InvalidBoundException;
use Ciloe\Ranges\Exception\InvalidDateIntervalException;
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

    public function testContainsWithNullBounds()
    {
        $range = new DateRange(
            null,
            new DateTimeImmutable('2025-06-07'),
            '(',
            ']'
        );

        $this->assertTrue($range->contains(new DateTimeImmutable('2025-06-01')));
        $this->assertTrue($range->contains(new DateTimeImmutable('2025-06-07')));
        $this->assertFalse($range->contains(new DateTimeImmutable('2025-06-08')));

        $range = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            null,
            '[',
            ')'
        );

        $this->assertTrue($range->contains(new DateTimeImmutable('2025-06-04')));
        $this->assertTrue($range->contains(new DateTimeImmutable('2025-06-10')));
        $this->assertFalse($range->contains(new DateTimeImmutable('2025-06-03')));

        $range = new DateRange(
            null,
            null,
            '(',
            ')'
        );

        $this->assertTrue($range->contains(new DateTimeImmutable('2025-06-04')));
        $this->assertTrue($range->contains(new DateTimeImmutable('2025-06-07')));
        $this->assertTrue($range->contains(new DateTimeImmutable('1900-01-01')));
        $this->assertTrue($range->contains(new DateTimeImmutable('2100-12-31')));
    }

    public function testContainsWithDateTime()
    {
        $range = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            new DateTimeImmutable('2025-06-07'),
            '[',
            ']'
        );

        $this->assertTrue($range->contains(new \DateTime('2025-06-04')));
        $this->assertTrue($range->contains(new \DateTime('2025-06-05')));
        $this->assertFalse($range->contains(new \DateTime('2025-06-03')));
    }

    public function testContainsWithInvalidType()
    {
        $range = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            new DateTimeImmutable('2025-06-07'),
            '[',
            ']'
        );

        $this->expectException(InvalidArgumentException::class);
        $range->contains('2025-06-05');
    }

    public function testContainsWithEmptyRange()
    {
        $range = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            new DateTimeImmutable('2025-06-04'),
            '(',
            ')'
        );

        $this->assertFalse($range->contains(new DateTimeImmutable('2025-06-04')));
        $this->assertFalse($range->contains(new DateTimeImmutable('2025-06-05')));
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

    public function testLengthWithDifferentSteps()
    {

        $range = new DateRange(
            new DateTimeImmutable('2025-06-01'),
            new DateTimeImmutable('2025-06-10'),
            '[',
            ']',
            new DateInterval('P2D')
        );

        $this->assertEquals(5, $range->length());

        $range = new DateRange(
            new DateTimeImmutable('2025-06-01'),
            new DateTimeImmutable('2025-06-10'),
            '[',
            ']',
            new DateInterval('P3D')
        );

        $this->assertEquals(4, $range->length());

        $range = new DateRange(
            new DateTimeImmutable('2025-06-01'),
            new DateTimeImmutable('2025-06-29'),
            '[',
            ']',
            new DateInterval('P7D')
        );

        $this->assertEquals(5, $range->length());
    }

    public function testLengthWithNullBounds()
    {
        $range = new DateRange(
            null,
            new DateTimeImmutable('2025-06-07'),
            '(',
            ']'
        );

        $this->assertNull($range->length());

        $range = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            null,
            '[',
            ')'
        );

        $this->assertNull($range->length());

        $range = new DateRange(
            null,
            null,
            '(',
            ')'
        );

        $this->assertNull($range->length());
    }

    public function testLengthWithInvalidBounds()
    {
        $range = new DateRange(
            new DateTimeImmutable('2025-06-07'),
            new DateTimeImmutable('2025-06-04'),
            '[',
            ']'
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

    public function testGenerateSeriesWithCustomStep()
    {
        $range = new DateRange(
            new DateTimeImmutable('2025-06-01'),
            new DateTimeImmutable('2025-06-10'),
            '[',
            ']',
            new DateInterval('P2D')
        );

        $series = $range->generateSeries();
        $this->assertCount(5, $series);
        $this->assertEquals(new DateTimeImmutable('2025-06-01'), $series[0]);
        $this->assertEquals(new DateTimeImmutable('2025-06-03'), $series[1]);
        $this->assertEquals(new DateTimeImmutable('2025-06-05'), $series[2]);
        $this->assertEquals(new DateTimeImmutable('2025-06-07'), $series[3]);
        $this->assertEquals(new DateTimeImmutable('2025-06-09'), $series[4]);
    }

    public function testGenerateSeriesWithEmptyRange()
    {
        $range = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            new DateTimeImmutable('2025-06-04'),
            '(',
            ')'
        );

        $series = $range->generateSeries();
        $this->assertCount(0, $series);

        $range = new DateRange(
            new DateTimeImmutable('2025-06-07'),
            new DateTimeImmutable('2025-06-04'),
            '[',
            ']'
        );

        $series = $range->generateSeries();
        $this->assertCount(0, $series);
    }

    public function testGenerateSeriesWithInfiniteBounds()
    {
        $range = new DateRange(
            null,
            new DateTimeImmutable('2025-06-07'),
            '(',
            ']'
        );

        $this->expectException(CantGenerateSeriesBecauseTheArrayIsTooLarge::class);
        $range->generateSeries();
    }

    public function testGenerateSeriesWithInfiniteUpperBound()
    {
        $range = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            null,
            '[',
            ')'
        );

        $this->expectException(CantGenerateSeriesBecauseTheArrayIsTooLarge::class);
        $range->generateSeries();
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

    public function testOverlapWithNullBounds()
    {
        $range1 = new DateRange(
            null,
            new DateTimeImmutable('2025-06-07'),
            '(',
            ']'
        );

        $range2 = new DateRange(
            new DateTimeImmutable('2025-06-05'),
            new DateTimeImmutable('2025-06-10'),
            '[',
            ']'
        );

        $this->assertTrue($range1->overlap($range2));

        $noOverlap = new DateRange(
            new DateTimeImmutable('2025-06-10'),
            new DateTimeImmutable('2025-06-20'),
            '[',
            ']'
        );

        $this->assertFalse($range1->overlap($noOverlap));

        $range3 = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            null,
            '[',
            ')'
        );

        $range4 = new DateRange(
            new DateTimeImmutable('2025-06-10'),
            new DateTimeImmutable('2025-06-20'),
            '[',
            ']'
        );

        $this->assertTrue($range3->overlap($range4));

        $range5 = new DateRange(null, null, '(', ')');
        $range6 = new DateRange(
            new DateTimeImmutable('2025-01-01'),
            new DateTimeImmutable('2025-12-31'),
            '[',
            ']'
        );

        $this->assertTrue($range5->overlap($range6));
    }

    public function testOverlapWithEmptyRange()
    {
        $empty = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            new DateTimeImmutable('2025-06-04'),
            '(',
            ')'
        );

        $range = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            new DateTimeImmutable('2025-06-07'),
            '[',
            ']'
        );

        $this->assertFalse($empty->overlap($range));
        $this->assertFalse($range->overlap($empty));
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

    public function testUnionWithDifferentSteps()
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
            ']',
            new DateInterval('P2D')
        );

        $this->assertNull($range1->union($range2));
    }

    public function testUnionWithNullBounds()
    {

        $range1 = new DateRange(
            null,
            new DateTimeImmutable('2025-06-07'),
            '(',
            ']'
        );

        $range2 = new DateRange(
            null,
            new DateTimeImmutable('2025-06-10'),
            '(',
            ']'
        );

        $union = $range1->union($range2);
        $this->assertNull($union->getLowerBoundValue());
        $this->assertEquals(new DateTimeImmutable('2025-06-10'), $union->getUpperBoundValue());

        $range1 = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            null,
            '[',
            ')'
        );

        $range2 = new DateRange(
            new DateTimeImmutable('2025-06-06'),
            null,
            '[',
            ')'
        );

        $union = $range1->union($range2);
        $this->assertEquals(new DateTimeImmutable('2025-06-04'), $union->getLowerBoundValue());
        $this->assertNull($union->getUpperBoundValue());

        $range1 = new DateRange(
            null,
            new DateTimeImmutable('2025-06-07'),
            '(',
            ']'
        );

        $range2 = new DateRange(
            new DateTimeImmutable('2025-06-06'),
            null,
            '[',
            ')'
        );

        $union = $range1->union($range2);
        $this->assertNull($union->getLowerBoundValue());
        $this->assertNull($union->getUpperBoundValue());
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

    public function testIntersectionWithDifferentSteps()
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
            ']',
            new DateInterval('P2D')
        );

        $this->assertNull($range1->intersection($range2));
    }

    public function testIntersectionWithNullBounds()
    {
        $range1 = new DateRange(
            null,
            new DateTimeImmutable('2025-06-07'),
            '(',
            ']'
        );

        $range2 = new DateRange(
            null,
            new DateTimeImmutable('2025-06-10'),
            '(',
            ']'
        );

        $intersection = $range1->intersection($range2);
        $this->assertNull($intersection->getLowerBoundValue());
        $this->assertEquals(new DateTimeImmutable('2025-06-07'), $intersection->getUpperBoundValue());

        $range1 = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            null,
            '[',
            ')'
        );

        $range2 = new DateRange(
            new DateTimeImmutable('2025-06-06'),
            null,
            '[',
            ')'
        );

        $intersection = $range1->intersection($range2);
        $this->assertEquals(new DateTimeImmutable('2025-06-06'), $intersection->getLowerBoundValue());
        $this->assertNull($intersection->getUpperBoundValue());

        $range1 = new DateRange(
            null,
            new DateTimeImmutable('2025-06-07'),
            '(',
            ']'
        );

        $range2 = new DateRange(
            new DateTimeImmutable('2025-06-06'),
            null,
            '[',
            ')'
        );

        $intersection = $range1->intersection($range2);
        $this->assertEquals(new DateTimeImmutable('2025-06-06'), $intersection->getLowerBoundValue());
        $this->assertEquals(new DateTimeImmutable('2025-06-07'), $intersection->getUpperBoundValue());
    }

    public function testIntersectionTouchingBounds()
    {
        $range1 = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            new DateTimeImmutable('2025-06-07'),
            '[',
            ']'
        );

        $range2 = new DateRange(
            new DateTimeImmutable('2025-06-07'),
            new DateTimeImmutable('2025-06-10'),
            '(',
            ']'
        );

        $this->assertNull($range1->intersection($range2));

        $range3 = new DateRange(
            new DateTimeImmutable('2025-06-07'),
            new DateTimeImmutable('2025-06-10'),
            '[',
            ']'
        );

        $intersection = $range1->intersection($range3);
        $this->assertNotNull($intersection);
        $this->assertEquals(new DateTimeImmutable('2025-06-07'), $intersection->getLowerBoundValue());
        $this->assertEquals(new DateTimeImmutable('2025-06-07'), $intersection->getUpperBoundValue());
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

    public function testSplitOutsideRange()
    {
        $range = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            new DateTimeImmutable('2025-06-07'),
            '[',
            ']'
        );

        $result = $range->split(new DateTimeImmutable('2025-06-01'));
        $this->assertCount(1, $result);
        $this->assertSame($range, $result[0]);

        $result = $range->split(new DateTimeImmutable('2025-06-10'));
        $this->assertCount(1, $result);
        $this->assertSame($range, $result[0]);
    }

    public function testSplitAtBounds()
    {
        $range = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            new DateTimeImmutable('2025-06-07'),
            '[',
            ']'
        );

        // Split at lower bound: left = [lower, lower) → effective upper = lower - step = 2025-06-03
        $split = $range->split(new DateTimeImmutable('2025-06-04'));
        $this->assertCount(2, $split);
        $this->assertEquals(new DateTimeImmutable('2025-06-04'), $split[0]->getLowerBoundValue());
        $this->assertEquals(new DateTimeImmutable('2025-06-03'), $split[0]->getUpperBoundValue());
        $this->assertEquals(new DateTimeImmutable('2025-06-04'), $split[1]->getLowerBoundValue());
        $this->assertEquals(new DateTimeImmutable('2025-06-07'), $split[1]->getUpperBoundValue());

        // Split at upper bound: left = [lower, upper) → effective upper = upper - step = 2025-06-06
        $split = $range->split(new DateTimeImmutable('2025-06-07'));
        $this->assertCount(2, $split);
        $this->assertEquals(new DateTimeImmutable('2025-06-04'), $split[0]->getLowerBoundValue());
        $this->assertEquals(new DateTimeImmutable('2025-06-06'), $split[0]->getUpperBoundValue());
        $this->assertEquals(new DateTimeImmutable('2025-06-07'), $split[1]->getLowerBoundValue());
        $this->assertEquals(new DateTimeImmutable('2025-06-07'), $split[1]->getUpperBoundValue());
    }

    public function testSplitWithInvalidType()
    {
        $range = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            new DateTimeImmutable('2025-06-07'),
            '[',
            ']'
        );

        $this->expectException(InvalidArgumentException::class);
        $range->split('2025-06-05');
    }

    public function testShiftWithNullBounds()
    {
        $range = new DateRange(
            null,
            new DateTimeImmutable('2025-06-07'),
            '(',
            ']'
        );

        $shifted = $range->shift(new DateInterval('P3D'));
        $this->assertNull($shifted->lower);
        $this->assertEquals(new DateTimeImmutable('2025-06-10'), $shifted->getUpperBoundValue());

        $range = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            null,
            '[',
            ')'
        );

        $shifted = $range->shift(new DateInterval('P3D'));
        $this->assertEquals(new DateTimeImmutable('2025-06-07'), $shifted->getLowerBoundValue());
        $this->assertNull($shifted->upper);
    }

    public function testShiftWithInvalidType()
    {
        $range = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            new DateTimeImmutable('2025-06-07'),
            '[',
            ']'
        );

        $this->expectException(InvalidArgumentException::class);
        $range->shift('3 days');
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

    public function testInvalidDateIntervalInConstructor()
    {
        $this->expectException(InvalidDateIntervalException::class);
        new DateRange(
            new DateTimeImmutable('2025-06-04'),
            new DateTimeImmutable('2025-06-07'),
            '[',
            ']',
            new DateInterval('PT1H')
        );
    }

    public function testInvalidDateIntervalInShift()
    {
        $range = new DateRange(
            new DateTimeImmutable('2025-06-04'),
            new DateTimeImmutable('2025-06-07'),
            '[',
            ']'
        );

        $this->expectException(InvalidDateIntervalException::class);
        $range->shift(new DateInterval('PT30M'));
    }

    public function testIsEmpty()
    {
        $referenceDate = new DateTimeImmutable('today');

        $range = new DateRange(
            $referenceDate,
            $referenceDate,
            '(',
            ')'
        );
        $this->assertTrue($range->isEmpty());

        $range = new DateRange(
            $referenceDate,
            $referenceDate,
            '[',
            ']'
        );
        $this->assertFalse($range->isEmpty());

        $range = new DateRange(
            $referenceDate,
            $referenceDate->modify('+1 day'),
            '(',
            ')'
        );
        $this->assertFalse($range->isEmpty());

        $range = new DateRange(
            null,
            $referenceDate,
            '(',
            ')'
        );
        $this->assertFalse($range->isEmpty());

        $range = new DateRange(
            $referenceDate,
            null,
            '(',
            ')'
        );
        $this->assertFalse($range->isEmpty());
    }

    public function testEquals()
    {
        $referenceDate = new DateTimeImmutable('today');

        $range1 = new DateRange(
            $referenceDate,
            $referenceDate->modify('+3 days'),
            '[',
            ']'
        );

        $range2 = new DateRange(
            $referenceDate,
            $referenceDate->modify('+3 days'),
            '[',
            ']'
        );

        $this->assertTrue($range1->equals($range2));

        $range3 = new DateRange(
            $referenceDate,
            $referenceDate->modify('+3 days'),
            '(',
            ']'
        );

        $this->assertFalse($range1->equals($range3));

        $range4 = new DateRange(
            $referenceDate,
            $referenceDate->modify('+3 days'),
            '[',
            ')'
        );

        $this->assertFalse($range1->equals($range4));

        $range5 = new DateRange(
            $referenceDate->modify('+1 day'),
            $referenceDate->modify('+3 days'),
            '[',
            ']'
        );

        $this->assertFalse($range1->equals($range5));

        $range6 = new DateRange(
            $referenceDate,
            $referenceDate->modify('+4 days'),
            '[',
            ']'
        );

        $this->assertFalse($range1->equals($range6));

        $range7 = new DateRange(
            null,
            $referenceDate->modify('+3 days'),
            '(',
            ']'
        );

        $this->assertFalse($range1->equals($range7));

        $range8 = new DateRange(
            $referenceDate,
            null,
            '[',
            ')'
        );

        $this->assertFalse($range1->equals($range8));

        $range9 = new DateRange(
            null,
            $referenceDate->modify('+3 days'),
            '(',
            ']'
        );

        $range10 = new DateRange(
            null,
            $referenceDate->modify('+3 days'),
            '(',
            ']'
        );

        $this->assertTrue($range9->equals($range10));

        $range11 = new DateRange(
            $referenceDate,
            null,
            '[',
            ')'
        );

        $range12 = new DateRange(
            $referenceDate,
            null,
            '[',
            ')'
        );

        $this->assertTrue($range11->equals($range12));

        $range13 = new DateRange(
            $referenceDate,
            $referenceDate->modify('+3 days'),
            '[',
            ']',
            new DateInterval('P2D')
        );

        $this->assertFalse($range1->equals($range13));
    }

    public function testClone()
    {
        $referenceDate = new DateTimeImmutable('today');

        $range = new DateRange(
            $referenceDate,
            $referenceDate->modify('+3 days'),
            '[',
            ']'
        );

        $clonedRange = $range->clone();

        $this->assertTrue($range->equals($clonedRange));

        $this->assertNotSame($range, $clonedRange);

        $range = new DateRange(
            null,
            $referenceDate->modify('+3 days'),
            '(',
            ']'
        );

        $clonedRange = $range->clone();
        $this->assertTrue($range->equals($clonedRange));
        $this->assertNotSame($range, $clonedRange);

        $range = new DateRange(
            $referenceDate,
            null,
            '[',
            ')'
        );

        $clonedRange = $range->clone();
        $this->assertTrue($range->equals($clonedRange));
        $this->assertNotSame($range, $clonedRange);

        $range = new DateRange(
            $referenceDate,
            $referenceDate->modify('+10 days'),
            '[',
            ']',
            new DateInterval('P2D')
        );

        $clonedRange = $range->clone();
        $this->assertTrue($range->equals($clonedRange));
        $this->assertNotSame($range, $clonedRange);
    }
}
