<?php

declare(strict_types=1);

namespace Tests\Ciloe\Ranges;

use Ciloe\Ranges\BigIntRange;
use Ciloe\Ranges\Exception\CantGenerateSeriesBecauseTheArrayIsTooLarge;
use Ciloe\Ranges\Exception\InvalidBoundException;
use Ciloe\Ranges\Exception\InvalidInfiniteBoundException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class BigIntRangeTest extends TestCase
{
    public function testFromStringValidRanges(): void
    {
        $range = BigIntRange::fromString('[9223372036854775808,19223372036854775809]');
        $this->assertSame('9223372036854775808', $range->lower);
        $this->assertSame('19223372036854775809', $range->upper);
        $this->assertSame('[', $range->lowerBound);
        $this->assertSame(']', $range->upperBound);

        $range = BigIntRange::fromString('(9223372036854775808,19223372036854775809)');
        $this->assertSame('9223372036854775808', $range->lower);
        $this->assertSame('19223372036854775809', $range->upper);
        $this->assertSame('(', $range->lowerBound);
        $this->assertSame(')', $range->upperBound);

        $range = BigIntRange::fromString('[9223372036854775808,19223372036854775809)');
        $this->assertSame('9223372036854775808', $range->lower);
        $this->assertSame('19223372036854775809', $range->upper);
        $this->assertSame('[', $range->lowerBound);
        $this->assertSame(')', $range->upperBound);

        $range = BigIntRange::fromString('(9223372036854775808,19223372036854775809]');
        $this->assertSame('9223372036854775808', $range->lower);
        $this->assertSame('19223372036854775809', $range->upper);
        $this->assertSame('(', $range->lowerBound);
        $this->assertSame(']', $range->upperBound);
    }

    public function testFromStringInvalidRanges(): void
    {
        $this->expectException(InvalidArgumentException::class);
        BigIntRange::fromString('invalid');
    }

    public function testExclusiveBoundsAreShiftedByStep(): void
    {
        $range = new BigIntRange('4', '20', '(', ']', '5');
        $this->assertSame('9', $range->getLowerBoundValue());
        $this->assertSame('20', $range->getUpperBoundValue());

        $range = new BigIntRange('0', '20', '[', ')', '5');
        $this->assertSame('0', $range->getLowerBoundValue());
        $this->assertSame('15', $range->getUpperBoundValue());

        $range = new BigIntRange('9223372036854775803', '9223372036854775823', '(', ')', '5');
        $this->assertSame('9223372036854775808', $range->getLowerBoundValue());
        $this->assertSame('9223372036854775818', $range->getUpperBoundValue());
    }

    public function testContainsWithVeryLargeIntegers(): void
    {
        $range = new BigIntRange('9223372036854775808', '9223372036854775810', '[', ']');

        $this->assertTrue($range->contains('9223372036854775808'));
        $this->assertTrue($range->contains('9223372036854775809'));
        $this->assertTrue($range->contains('9223372036854775810'));
        $this->assertFalse($range->contains('9223372036854775807'));
        $this->assertFalse($range->contains('9223372036854775811'));
    }

    public function testOverlapWithVeryLargeIntegers(): void
    {
        $range1 = new BigIntRange('9223372036854775808', '9223372036854775810', '[', ']');
        $range2 = new BigIntRange('9223372036854775809', '9223372036854775811', '[', ']');
        $range3 = new BigIntRange('9223372036854775811', '9223372036854775812', '[', ']');

        $this->assertTrue($range1->overlap($range2));
        $this->assertFalse($range1->overlap($range3));
    }

    public function testLengthWithVeryLargeIntegers(): void
    {
        $range = new BigIntRange('9223372036854775808', '9223372036854775810', '[', ']');
        $this->assertSame('3', $range->length());

        $range = new BigIntRange('9223372036854775808', '9223372036854775810', '(', ')');
        $this->assertSame('1', $range->length());

        $range = new BigIntRange('-9223372036854775808', '9223372036854775810', '(', ')');
        $this->assertSame('18446744073709551617', $range->length());
    }

    public function testUnionWithVeryLargeIntegers(): void
    {
        $range1 = new BigIntRange('9223372036854775808', '9223372036854775810', '[', ']');
        $range2 = new BigIntRange('9223372036854775809', '9223372036854775811', '[', ']');

        $union = $range1->union($range2);
        $this->assertSame('9223372036854775808', $union->getLowerBoundValue());
        $this->assertSame('9223372036854775811', $union->getUpperBoundValue());
    }

    public function testIntersectionWithVeryLargeIntegers(): void
    {
        $range1 = new BigIntRange('9223372036854775808', '9223372036854775810', '[', ']');
        $range2 = new BigIntRange('9223372036854775809', '9223372036854775811', '[', ']');

        $intersection = $range1->intersection($range2);
        $this->assertSame('9223372036854775809', $intersection->getLowerBoundValue());
        $this->assertSame('9223372036854775810', $intersection->getUpperBoundValue());
    }

    public function testGenerateSeriesWithVeryLargeIntegers(): void
    {
        $range = new BigIntRange('9223372036854775808', '9223372036854775810', '[', ']');
        $series = $range->generateSeries();

        $this->assertCount(3, $series);
        $this->assertSame('9223372036854775808', $series[0]);
        $this->assertSame('9223372036854775809', $series[1]);
        $this->assertSame('9223372036854775810', $series[2]);
    }

    public function testShiftWithVeryLargeIntegers(): void
    {
        $range = new BigIntRange('9223372036854775808', '9223372036854775810', '[', ']');
        $shifted = $range->shift('1');

        $this->assertSame('9223372036854775809', $shifted->lower);
        $this->assertSame('9223372036854775811', $shifted->upper);
    }

    public function testScaleWithVeryLargeIntegers(): void
    {
        $range = new BigIntRange('9223372036854775808', '9223372036854775810', '[', ']');
        $scaled = $range->scale('2');

        $this->assertSame('18446744073709551616', $scaled->lower);
        $this->assertSame('18446744073709551620', $scaled->upper);
    }

    public function testInvalidInputs(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new BigIntRange('not_a_number', '10');
    }

    public function testInvalidStep(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new BigIntRange('1', '10', '[', ']', '0');
    }

    public function testSplitWithVeryLargeIntegers(): void
    {
        $range = new BigIntRange('9223372036854775808', '9223372036854775810', '[', ']');
        $split = $range->split('9223372036854775809');

        $this->assertCount(2, $split);
        $this->assertSame('9223372036854775808', $split[0]->lower);
        $this->assertSame('9223372036854775809', $split[0]->upper);
        $this->assertSame('9223372036854775809', $split[1]->lower);
        $this->assertSame('9223372036854775810', $split[1]->upper);
    }

    public function testFromStringWithNegativeIntegers(): void
    {
        $range = BigIntRange::fromString('[-10,-1]');
        $this->assertSame('-10', $range->lower);
        $this->assertSame('-1', $range->upper);
        $this->assertSame('[', $range->lowerBound);
        $this->assertSame(']', $range->upperBound);

        $range = BigIntRange::fromString('(-10,-1)');
        $this->assertSame('-10', $range->lower);
        $this->assertSame('-1', $range->upper);
        $this->assertSame('(', $range->lowerBound);
        $this->assertSame(')', $range->upperBound);

        $range = BigIntRange::fromString('[-9223372036854775808,-9223372036854775807]');
        $this->assertSame('-9223372036854775808', $range->lower);
        $this->assertSame('-9223372036854775807', $range->upper);
        $this->assertSame('[', $range->lowerBound);
        $this->assertSame(']', $range->upperBound);
    }

    public function testContainsWithNegativeIntegers(): void
    {
        $range = new BigIntRange('-10', '-1', '[', ']');

        $this->assertTrue($range->contains('-10'));
        $this->assertTrue($range->contains('-5'));
        $this->assertTrue($range->contains('-1'));
        $this->assertFalse($range->contains('-11'));
        $this->assertFalse($range->contains('0'));

        $range = new BigIntRange('-9223372036854775810', '-9223372036854775808', '[', ']');

        $this->assertTrue($range->contains('-9223372036854775810'));
        $this->assertTrue($range->contains('-9223372036854775809'));
        $this->assertTrue($range->contains('-9223372036854775808'));
        $this->assertFalse($range->contains('-9223372036854775811'));
        $this->assertFalse($range->contains('-9223372036854775807'));
    }

    public function testOverlapWithNegativeIntegers(): void
    {
        $range1 = new BigIntRange('-10', '-1', '[', ']');
        $range2 = new BigIntRange('-5', '5', '[', ']');
        $range3 = new BigIntRange('-20', '-11', '[', ']');

        $this->assertTrue($range1->overlap($range2));
        $this->assertFalse($range1->overlap($range3));

        $range1 = new BigIntRange('-9223372036854775810', '-9223372036854775808', '[', ']');
        $range2 = new BigIntRange('-9223372036854775809', '-9223372036854775807', '[', ']');
        $range3 = new BigIntRange('-9223372036854775807', '-9223372036854775806', '[', ']');

        $this->assertTrue($range1->overlap($range2));
        $this->assertFalse($range1->overlap($range3));
    }

    public function testLengthWithNegativeIntegers(): void
    {
        $range = new BigIntRange('-10', '-1', '[', ']');
        $this->assertSame('10', $range->length());

        $range = new BigIntRange('-10', '-1', '(', ')');
        $this->assertSame('8', $range->length());

        $range = new BigIntRange('-9223372036854775810', '-9223372036854775808', '[', ']');
        $this->assertSame('3', $range->length());

        $range = new BigIntRange('-9223372036854775810', '-9223372036854775808', '(', ')');
        $this->assertSame('1', $range->length());
    }

    public function testUnionWithNegativeIntegers(): void
    {
        $range1 = new BigIntRange('-10', '-1', '[', ']');
        $range2 = new BigIntRange('-5', '5', '[', ']');

        $union = $range1->union($range2);
        $this->assertSame('-10', $union->getLowerBoundValue());
        $this->assertSame('5', $union->getUpperBoundValue());

        $range1 = new BigIntRange('-9223372036854775810', '-9223372036854775808', '[', ']');
        $range2 = new BigIntRange('-9223372036854775809', '-9223372036854775807', '[', ']');

        $union = $range1->union($range2);
        $this->assertSame('-9223372036854775810', $union->getLowerBoundValue());
        $this->assertSame('-9223372036854775807', $union->getUpperBoundValue());
    }

    public function testIntersectionWithNegativeIntegers(): void
    {
        $range1 = new BigIntRange('-10', '-1', '[', ']');
        $range2 = new BigIntRange('-5', '5', '[', ']');

        $intersection = $range1->intersection($range2);
        $this->assertSame('-5', $intersection->getLowerBoundValue());
        $this->assertSame('-1', $intersection->getUpperBoundValue());

        $range1 = new BigIntRange('-9223372036854775810', '-9223372036854775808', '[', ']');
        $range2 = new BigIntRange('-9223372036854775809', '-9223372036854775807', '[', ']');

        $intersection = $range1->intersection($range2);
        $this->assertSame('-9223372036854775809', $intersection->getLowerBoundValue());
        $this->assertSame('-9223372036854775808', $intersection->getUpperBoundValue());
    }

    public function testGenerateSeriesWithNegativeIntegers(): void
    {
        $range = new BigIntRange('-5', '-1', '[', ']');
        $series = $range->generateSeries();

        $this->assertCount(5, $series);
        $this->assertSame('-5', $series[0]);
        $this->assertSame('-4', $series[1]);
        $this->assertSame('-3', $series[2]);
        $this->assertSame('-2', $series[3]);
        $this->assertSame('-1', $series[4]);

        $range = new BigIntRange('-10', '-1', '[', ']', '3');
        $series = $range->generateSeries();

        $this->assertCount(4, $series);
        $this->assertSame('-10', $series[0]);
        $this->assertSame('-7', $series[1]);
        $this->assertSame('-4', $series[2]);
        $this->assertSame('-1', $series[3]);
    }

    public function testShiftWithNegativeIntegers(): void
    {
        $range = new BigIntRange('-10', '-1', '[', ']');

        $shifted = $range->shift('5');
        $this->assertSame('-5', $shifted->lower);
        $this->assertSame('4', $shifted->upper);

        $shifted = $range->shift('-5');
        $this->assertSame('-15', $shifted->lower);
        $this->assertSame('-6', $shifted->upper);

        $range = new BigIntRange('-9223372036854775810', '-9223372036854775808', '[', ']');
        $shifted = $range->shift('-1');

        $this->assertSame('-9223372036854775811', $shifted->lower);
        $this->assertSame('-9223372036854775809', $shifted->upper);
    }

    public function testScaleWithNegativeIntegers(): void
    {
        $range = new BigIntRange('-10', '-1', '[', ']');

        $scaled = $range->scale('2');
        $this->assertSame('-20', $scaled->lower);
        $this->assertSame('-2', $scaled->upper);

        $scaled = $range->scale('-1');
        $this->assertSame('1', $scaled->lower);
        $this->assertSame('10', $scaled->upper);
        $this->assertSame('[', $scaled->lowerBound);
        $this->assertSame(']', $scaled->upperBound);

        $range = new BigIntRange('-9223372036854775810', '-9223372036854775808', '[', ']');
        $scaled = $range->scale('2');

        $this->assertSame('-18446744073709551620', $scaled->lower);
        $this->assertSame('-18446744073709551616', $scaled->upper);
    }

    public function testSplitWithNegativeIntegers(): void
    {
        $range = new BigIntRange('-10', '-1', '[', ']');
        $split = $range->split('-5');

        $this->assertCount(2, $split);
        $this->assertSame('-10', $split[0]->lower);
        $this->assertSame('-5', $split[0]->upper);
        $this->assertSame('-5', $split[1]->lower);
        $this->assertSame('-1', $split[1]->upper);

        $range = new BigIntRange('-9223372036854775810', '-9223372036854775808', '[', ']');
        $split = $range->split('-9223372036854775809');

        $this->assertCount(2, $split);
        $this->assertSame('-9223372036854775810', $split[0]->lower);
        $this->assertSame('-9223372036854775809', $split[0]->upper);
        $this->assertSame('-9223372036854775809', $split[1]->lower);
        $this->assertSame('-9223372036854775808', $split[1]->upper);
    }

    public function testRangeWithMixedSignIntegers(): void
    {
        $range = new BigIntRange('-10', '10', '[', ']');

        $this->assertTrue($range->contains('-10'));
        $this->assertTrue($range->contains('0'));
        $this->assertTrue($range->contains('10'));
        $this->assertFalse($range->contains('-11'));
        $this->assertFalse($range->contains('11'));

        $this->assertSame('21', $range->length());

        $series = $range->generateSeries();
        $this->assertCount(21, $series);
        $this->assertSame('-10', $series[0]);
        $this->assertSame('0', $series[10]);
        $this->assertSame('10', $series[20]);
    }

    public function testIsEmptyWithEmptyRange(): void
    {
        $range = new BigIntRange('5', '5', '(', ')');
        $this->assertTrue($range->isEmpty());

        $range = new BigIntRange('5', '5', '[', ')');
        $this->assertFalse($range->isEmpty());

        $range = new BigIntRange('5', '5', '(', ']');
        $this->assertFalse($range->isEmpty());
    }

    public function testIsEmptyWithNonEmptyRange(): void
    {
        $range = new BigIntRange('5', '5', '[', ']');
        $this->assertFalse($range->isEmpty());

        $range = new BigIntRange('5', '10', '(', ')');
        $this->assertFalse($range->isEmpty());

        $range = new BigIntRange('5', '10', '[', ']');
        $this->assertFalse($range->isEmpty());
    }

    public function testIsEmptyWithNullBounds(): void
    {
        $range = new BigIntRange(null, null, '(', ')');
        $this->assertFalse($range->isEmpty());

        $range = new BigIntRange('5', null, '[', ')');
        $this->assertFalse($range->isEmpty());

        $range = new BigIntRange(null, '5', '(', ']');
        $this->assertFalse($range->isEmpty());
    }

    public function testIsBoundsValidWithValidBounds(): void
    {
        $range = new BigIntRange('5', '10', '[', ']');
        $this->assertTrue($range->isBoundsValid());

        $range = new BigIntRange('5', '5', '[', ']');
        $this->assertTrue($range->isBoundsValid());

        $range = new BigIntRange('-10', '10', '[', ']');
        $this->assertTrue($range->isBoundsValid());
    }

    public function testIsBoundsValidWithInvalidBounds(): void
    {
        $range = new BigIntRange('10', '5', '[', ']');
        $this->assertFalse($range->isBoundsValid());

        $range = new BigIntRange('10', '5', '(', ')');
        $this->assertFalse($range->isBoundsValid());
    }

    public function testIsBoundsValidWithNullBounds(): void
    {
        $range = new BigIntRange(null, null, '(', ')');
        $this->assertTrue($range->isBoundsValid());

        $range = new BigIntRange('5', null, '[', ')');
        $this->assertTrue($range->isBoundsValid());

        $range = new BigIntRange(null, '5', '(', ']');
        $this->assertTrue($range->isBoundsValid());
    }

    public function testEquals(): void
    {
        $range1 = new BigIntRange('5', '10', '[', ']');
        $range2 = new BigIntRange('5', '10', '[', ']');
        $range3 = new BigIntRange('5', '10', '(', ']');
        $range4 = new BigIntRange('5', '10', '[', ')', '2');

        $this->assertTrue($range1->equals($range2));
        $this->assertFalse($range1->equals($range3));
        $this->assertFalse($range1->equals($range4));

        $range1 = new BigIntRange('9223372036854775808', '9223372036854775810', '[', ']');
        $range2 = new BigIntRange('9223372036854775808', '9223372036854775810', '[', ']');
        $this->assertTrue($range1->equals($range2));
    }

    public function testClone(): void
    {
        $range = new BigIntRange('5', '10', '[', ']', '2');
        $cloned = $range->clone();

        $this->assertNotSame($range, $cloned);
        $this->assertTrue($range->equals($cloned));

        $range = new BigIntRange('9223372036854775808', '9223372036854775810', '[', ']', '2');
        $cloned = $range->clone();

        $this->assertNotSame($range, $cloned);
        $this->assertTrue($range->equals($cloned));
    }

    public function testToString(): void
    {
        $range = new BigIntRange('5', '10', '[', ']');
        $this->assertSame('[5,10]', (string) $range);

        $range = new BigIntRange('5', '10', '(', ')');
        $this->assertSame('(5,10)', (string) $range);

        $range = new BigIntRange(null, '10', '(', ')');
        $this->assertSame('(,10)', (string) $range);

        $range = new BigIntRange('5', null, '[', ')');
        $this->assertSame('[5,)', (string) $range);

        $range = new BigIntRange(null, null, '(', ')');
        $this->assertSame('(,)', (string) $range);

        $range = new BigIntRange('9223372036854775808', '9223372036854775810', '[', ']');
        $this->assertSame('[9223372036854775808,9223372036854775810]', (string) $range);
    }

    public function testGenerateSeriesWithEmptyRange(): void
    {
        $range = new BigIntRange('5', '5', '(', ')');
        $this->assertSame([], $range->generateSeries());
    }

    public function testGenerateSeriesWithSinglePointRange(): void
    {
        $range = new BigIntRange('5', '5', '[', ']');
        $series = $range->generateSeries();

        $this->assertCount(1, $series);
        $this->assertSame('5', $series[0]);
    }

    public function testGenerateSeriesWithDifferentSteps(): void
    {
        $range = new BigIntRange('1', '10', '[', ']', '2');
        $series = $range->generateSeries();

        $this->assertCount(5, $series);
        $this->assertSame('1', $series[0]);
        $this->assertSame('3', $series[1]);
        $this->assertSame('5', $series[2]);
        $this->assertSame('7', $series[3]);
        $this->assertSame('9', $series[4]);

        $range = new BigIntRange('1', '10', '[', ']', '3');
        $series = $range->generateSeries();

        $this->assertCount(4, $series);
        $this->assertSame('1', $series[0]);
        $this->assertSame('4', $series[1]);
        $this->assertSame('7', $series[2]);
        $this->assertSame('10', $series[3]);
    }

    public function testFromStringWithNullBounds(): void
    {
        $range = BigIntRange::fromString('(,10)');
        $this->assertNull($range->lower);
        $this->assertSame('10', $range->upper);
        $this->assertSame('(', $range->lowerBound);
        $this->assertSame(')', $range->upperBound);
        $this->assertSame('9', $range->getUpperBoundValue());

        $range = BigIntRange::fromString('(10,)');
        $this->assertSame('10', $range->lower);
        $this->assertNull($range->upper);
        $this->assertSame('(', $range->lowerBound);
        $this->assertSame(')', $range->upperBound);
        $this->assertSame('11', $range->getLowerBoundValue());

        $range = BigIntRange::fromString('(,)');
        $this->assertNull($range->lower);
        $this->assertNull($range->upper);
        $this->assertNull($range->getLowerBoundValue());
        $this->assertNull($range->getUpperBoundValue());
    }

    public function testFromStringInvalidInfiniteBoundsException(): void
    {
        $this->expectException(InvalidInfiniteBoundException::class);
        BigIntRange::fromString('[,10)');
    }

    public function testFromStringInvalidBoundsException(): void
    {
        $this->expectException(InvalidBoundException::class);
        BigIntRange::fromString('[10,5]');
    }

    public function testOverlapWithNullBounds(): void
    {
        $range1 = new BigIntRange(null, '10', '(', ']');
        $range2 = new BigIntRange('5', '15', '[', ']');
        $this->assertTrue($range1->overlap($range2));

        // null lower but upper doesn't reach — should NOT overlap
        $range3 = new BigIntRange(null, '5', '(', ']');
        $range4 = new BigIntRange('10', '20', '[', ']');
        $this->assertFalse($range3->overlap($range4));

        $range5 = new BigIntRange('5', null, '[', ')');
        $range6 = new BigIntRange('0', '10', '[', ']');
        $this->assertTrue($range5->overlap($range6));

        $range7 = new BigIntRange(null, null, '(', ')');
        $range8 = new BigIntRange('-100', '100', '[', ']');
        $this->assertTrue($range7->overlap($range8));
    }

    public function testUnionWithNullBounds(): void
    {
        $range1 = new BigIntRange(null, '10', '(', ']');
        $range2 = new BigIntRange('5', '15', '[', ']');
        $result = $range1->union($range2);

        $this->assertNotNull($result);
        $this->assertNull($result->getLowerBoundValue());
        $this->assertSame('15', $result->getUpperBoundValue());

        $range1 = new BigIntRange('5', null, '[', ')');
        $range2 = new BigIntRange('0', '10', '[', ']');
        $result = $range1->union($range2);

        $this->assertNotNull($result);
        $this->assertSame('0', $result->getLowerBoundValue());
        $this->assertNull($result->getUpperBoundValue());

        $range1 = new BigIntRange(null, null, '(', ')');
        $range2 = new BigIntRange('-10', '10', '[', ']');
        $result = $range1->union($range2);

        $this->assertNotNull($result);
        $this->assertNull($result->getLowerBoundValue());
        $this->assertNull($result->getUpperBoundValue());
    }

    public function testIntersectionWithNullBounds(): void
    {
        $range1 = new BigIntRange(null, '10', '(', ']');
        $range2 = new BigIntRange('5', null, '[', ')');
        $result = $range1->intersection($range2);

        $this->assertNotNull($result);
        $this->assertSame('5', $result->getLowerBoundValue());
        $this->assertSame('10', $result->getUpperBoundValue());

        $range1 = new BigIntRange(null, '10', '(', ']');
        $range2 = new BigIntRange(null, '5', '(', ']');
        $result = $range1->intersection($range2);

        $this->assertNotNull($result);
        $this->assertNull($result->getLowerBoundValue());
        $this->assertSame('5', $result->getUpperBoundValue());

        $range1 = new BigIntRange('5', null, '[', ')');
        $range2 = new BigIntRange('10', null, '[', ')');
        $result = $range1->intersection($range2);

        $this->assertNotNull($result);
        $this->assertSame('10', $result->getLowerBoundValue());
        $this->assertNull($result->getUpperBoundValue());
    }

    public function testUnionAndIntersectionWithNullBoundsRoundTripThroughFromString(): void
    {
        $union = (new BigIntRange(null, '10', '(', ']'))->union(new BigIntRange('5', '15', '[', ']'));
        $this->assertSame('(,15]', (string) $union);
        $this->assertTrue(BigIntRange::fromString((string) $union)->equals($union));

        $union = (new BigIntRange(null, '10', '(', ']'))->union(new BigIntRange('5', null, '[', ')'));
        $this->assertSame('(,)', (string) $union);
        $this->assertTrue(BigIntRange::fromString((string) $union)->equals($union));

        $intersection = (new BigIntRange(null, '10', '(', ']'))->intersection(new BigIntRange(null, '5', '(', ']'));
        $this->assertSame('(,5]', (string) $intersection);
        $this->assertTrue(BigIntRange::fromString((string) $intersection)->equals($intersection));
    }

    public function testIntersectionWithNonOverlappingRanges(): void
    {
        $range1 = new BigIntRange('1', '5', '[', ']');
        $range2 = new BigIntRange('10', '20', '[', ']');
        $this->assertNull($range1->intersection($range2));

        $range1 = new BigIntRange('1', '5', '[', ')');
        $range2 = new BigIntRange('5', '10', '[', ']');
        $this->assertNull($range1->intersection($range2));
    }

    public function testGenerateSeriesWithNullBounds(): void
    {
        $this->expectException(CantGenerateSeriesBecauseTheArrayIsTooLarge::class);
        $range = new BigIntRange(null, '10', '(', ']');
        $range->generateSeries();
    }

    public function testGenerateSeriesWithNullUpperBound(): void
    {
        $this->expectException(CantGenerateSeriesBecauseTheArrayIsTooLarge::class);
        $range = new BigIntRange('1', null, '[', ')');
        $range->generateSeries();
    }

    public function testGenerateSeriesWithStepGreaterThanRange(): void
    {
        // Consistent with length(): the series contains at least the lower bound
        $range = new BigIntRange('1', '5', '[', ']', '10');
        $this->assertSame(['1'], $range->generateSeries());
        $this->assertSame('1', $range->length());
    }

    public function testSplitWithPointOutsideRange(): void
    {
        $range = new BigIntRange('1', '10', '[', ']');
        $result = $range->split('0');

        $this->assertCount(1, $result);
        $this->assertSame($range, $result[0]);

        $result = $range->split('11');
        $this->assertCount(1, $result);
        $this->assertSame($range, $result[0]);
    }

    public function testSplitWithNonNumericPoint(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $range = new BigIntRange('1', '10', '[', ']');
        $range->split('not_a_number');
    }

    public function testShiftWithNullBounds(): void
    {
        $range = new BigIntRange(null, '10', '(', ']');
        $shifted = $range->shift('5');
        $this->assertNull($shifted->lower);
        $this->assertSame('15', $shifted->upper);

        $range = new BigIntRange('5', null, '[', ')');
        $shifted = $range->shift('5');
        $this->assertSame('10', $shifted->lower);
        $this->assertNull($shifted->upper);
    }

    public function testShiftWithNonNumericOffset(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $range = new BigIntRange('1', '10', '[', ']');
        $range->shift('not_a_number');
    }

    public function testScaleWithNullBounds(): void
    {
        $range = new BigIntRange(null, '10', '(', ']');
        $scaled = $range->scale('2');
        $this->assertNull($scaled->lower);
        $this->assertSame('20', $scaled->upper);

        $range = new BigIntRange('5', null, '[', ')');
        $scaled = $range->scale('2');
        $this->assertSame('10', $scaled->lower);
        $this->assertNull($scaled->upper);
    }

    public function testScaleWithZeroFactor(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $range = new BigIntRange('1', '10', '[', ']');
        $range->scale('0');
    }

    public function testScaleWithNonNumericFactor(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $range = new BigIntRange('1', '10', '[', ']');
        $range->scale('not_a_number');
    }

    public function testContainsWithInvalidInput(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $range = new BigIntRange('1', '10', '[', ']');
        $range->contains('not_a_number');
    }

    public function testContainsWithNullBounds(): void
    {
        $range = new BigIntRange(null, '10', '(', ']');
        $this->assertTrue($range->contains('-999999999999999999999'));
        $this->assertTrue($range->contains('10'));
        $this->assertFalse($range->contains('11'));

        $range = new BigIntRange('5', null, '[', ')');
        $this->assertTrue($range->contains('5'));
        $this->assertTrue($range->contains('999999999999999999999'));
        $this->assertFalse($range->contains('4'));

        $range = new BigIntRange(null, null, '(', ')');
        $this->assertTrue($range->contains('-999999999999999999999'));
        $this->assertTrue($range->contains('0'));
        $this->assertTrue($range->contains('999999999999999999999'));
    }

    public function testLengthWithNullBounds(): void
    {
        $range = new BigIntRange(null, '10', '(', ']');
        $this->assertNull($range->length());

        $range = new BigIntRange('5', null, '[', ')');
        $this->assertNull($range->length());

        $range = new BigIntRange(null, null, '(', ')');
        $this->assertNull($range->length());
    }

    public function testNegativeStep(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new BigIntRange('1', '10', '[', ']', '-1');
    }

    public function testEqualsWithNullBounds(): void
    {
        $range1 = new BigIntRange(null, '10', '(', ']');
        $range2 = new BigIntRange(null, '10', '(', ']');
        $this->assertTrue($range1->equals($range2));

        $range3 = new BigIntRange('5', '10', '[', ']');
        $this->assertFalse($range1->equals($range3));

        $range4 = new BigIntRange(null, null, '(', ')');
        $range5 = new BigIntRange(null, null, '(', ')');
        $this->assertTrue($range4->equals($range5));
    }
}
