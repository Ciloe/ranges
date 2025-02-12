<?php

declare(strict_types=1);

namespace Ciloe\Ranges\Tests;

use Ciloe\Ranges\Exception\InvalidBoundException;
use Ciloe\Ranges\Exception\InvalidInfinitBoundException;
use Ciloe\Ranges\IntRange;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class IntRangeTest extends TestCase
{
    public function testFromStringValidRanges()
    {
        $range = IntRange::fromString('(,13)');
        $this->assertNull($range->lower);
        $this->assertEquals(13, $range->upper);
        $this->assertEquals('(', $range->lowerBound);
        $this->assertEquals(')', $range->upperBound);
        $this->assertEquals(12, $range->getUpperBoundValue());

        $range = IntRange::fromString('(,13]');
        $this->assertNull($range->lower);
        $this->assertEquals(13, $range->upper);
        $this->assertEquals('(', $range->lowerBound);
        $this->assertEquals(']', $range->upperBound);
        $this->assertEquals(13, $range->getUpperBoundValue());

        $range = IntRange::fromString('(null,13)');
        $this->assertNull($range->lower);
        $this->assertEquals(13, $range->upper);
        $this->assertEquals('(', $range->lowerBound);
        $this->assertEquals(')', $range->upperBound);
        $this->assertEquals(12, $range->getUpperBoundValue());

        $range = IntRange::fromString('(null,13]');
        $this->assertNull($range->lower);
        $this->assertEquals(13, $range->upper);
        $this->assertEquals('(', $range->lowerBound);
        $this->assertEquals(']', $range->upperBound);
        $this->assertEquals(13, $range->getUpperBoundValue());

        $range = IntRange::fromString('(13,null)');
        $this->assertNull($range->upper);
        $this->assertEquals(13, $range->lower);
        $this->assertEquals('(', $range->lowerBound);
        $this->assertEquals(')', $range->upperBound);
        $this->assertEquals(14, $range->getLowerBoundValue());

        $range = IntRange::fromString('[13,null)');
        $this->assertNull($range->upper);
        $this->assertEquals(13, $range->lower);
        $this->assertEquals('[', $range->lowerBound);
        $this->assertEquals(')', $range->upperBound);
        $this->assertEquals(13, $range->getLowerBoundValue());

        $range = IntRange::fromString('(13,)');
        $this->assertNull($range->upper);
        $this->assertEquals(13, $range->lower);
        $this->assertEquals('(', $range->lowerBound);
        $this->assertEquals(')', $range->upperBound);
        $this->assertEquals(14, $range->getLowerBoundValue());

        $range = IntRange::fromString('[13,)');
        $this->assertNull($range->upper);
        $this->assertEquals(13, $range->lower);
        $this->assertEquals('[', $range->lowerBound);
        $this->assertEquals(')', $range->upperBound);
        $this->assertEquals(13, $range->getLowerBoundValue());

        $range = IntRange::fromString('(,)');
        $this->assertNull($range->upper);
        $this->assertNull($range->lower);
        $this->assertEquals('(', $range->lowerBound);
        $this->assertEquals(')', $range->upperBound);
    }

    public function testFromStringInvalidRanges()
    {
        $this->expectException(InvalidArgumentException::class);
        IntRange::fromString('invalid');

        $this->expectException(InvalidArgumentException::class);
        IntRange::fromString('[1,2)');

        $this->expectException(InvalidArgumentException::class);
        IntRange::fromString('(1,2');

        $this->expectException(InvalidArgumentException::class);
        IntRange::fromString('1,2)');

        $this->expectException(InvalidInfinitBoundException::class);
        IntRange::fromString('[,2)');

        $this->expectException(InvalidInfinitBoundException::class);
        IntRange::fromString('(2,]');

        $this->expectException(InvalidInfinitBoundException::class);
        IntRange::fromString('[,]');

        $this->expectException(InvalidBoundException::class);
        IntRange::fromString('[3,1)');

        $this->expectException(InvalidBoundException::class);
        IntRange::fromString('(5,3]');

        $this->expectException(InvalidBoundException::class);
        IntRange::fromString('(3,2)');
    }

    public function testContainsWithInclusiveBounds()
    {
        $range = new IntRange(1, 10, '[', ']');
        $this->assertTrue($range->contains(1));
        $this->assertTrue($range->contains(10));
        $this->assertTrue($range->contains(5));
        $this->assertFalse($range->contains(0));
        $this->assertFalse($range->contains(11));
    }

    public function testContainsWithExclusiveBounds()
    {
        $range = new IntRange(1, 10, '(', ')');
        $this->assertFalse($range->contains(1));
        $this->assertFalse($range->contains(10));
        $this->assertTrue($range->contains(5));
        $this->assertFalse($range->contains(0));
        $this->assertFalse($range->contains(11));
    }

    public function testContainsWithMixedBounds()
    {
        $range = new IntRange(1, 10, '[', ')');
        $this->assertTrue($range->contains(1));
        $this->assertFalse($range->contains(10));
        $this->assertTrue($range->contains(5));
        $this->assertFalse($range->contains(0));
        $this->assertFalse($range->contains(11));
    }

    public function testContainsWithNullLowerBound()
    {
        $range = new IntRange(null, 10, '(', ']');
        $this->assertTrue($range->contains(PHP_INT_MIN));
        $this->assertTrue($range->contains(5));
        $this->assertTrue($range->contains(10));
        $this->assertFalse($range->contains(11));
    }

    public function testContainsWithNullUpperBound()
    {
        $range = new IntRange(1, null, '[', ')');
        $this->assertTrue($range->contains(1));
        $this->assertTrue($range->contains(5));
        $this->assertTrue($range->contains(PHP_INT_MAX));
        $this->assertFalse($range->contains(0));
    }

    public function testContainsWithBothNullBounds()
    {
        $range = new IntRange(null, null, '(', ')');
        $this->assertTrue($range->contains(PHP_INT_MIN));
        $this->assertTrue($range->contains(0));
        $this->assertTrue($range->contains(PHP_INT_MAX));
    }

    public function testOverlapWithOverlappingRanges()
    {
        $range1 = new IntRange(1, 10, '[', ']');
        $range2 = new IntRange(5, 15, '[', ']');
        $this->assertTrue($range1->overlap($range2));
    }

    public function testOverlapWithNonOverlappingRanges()
    {
        $range1 = new IntRange(1, 10, '[', ']');
        $range2 = new IntRange(11, 20, '[', ']');
        $this->assertFalse($range1->overlap($range2));
    }

    public function testOverlapWithTouchingRanges()
    {
        $range1 = new IntRange(1, 10, '[', ']');
        $range2 = new IntRange(10, 20, '[', ']');
        $this->assertTrue($range1->overlap($range2));
    }

    public function testOverlapWithOneRangeInsideAnother()
    {
        $range1 = new IntRange(1, 20, '[', ']');
        $range2 = new IntRange(5, 15, '[', ']');
        $this->assertTrue($range1->overlap($range2));
    }

    public function testOverlapWithExclusiveBounds()
    {
        $range1 = new IntRange(1, 10, '(', ')');
        $range2 = new IntRange(10, 20, '(', ')');
        $this->assertFalse($range1->overlap($range2));
    }

    public function testOverlapWithNullLowerBound()
    {
        $range1 = new IntRange(null, 10, '(', ']');
        $range2 = new IntRange(5, 15, '[', ']');
        $this->assertTrue($range1->overlap($range2));
    }

    public function testOverlapWithNegativeRanges()
    {
        $range1 = new IntRange(-20, -10, '[', ']');
        $range2 = new IntRange(-15, -5, '[', ']');
        $this->assertTrue($range1->overlap($range2));
    }

    public function testOverlapWithNonOverlappingNegativeRanges()
    {
        $range1 = new IntRange(-20, -15, '[', ']');
        $range2 = new IntRange(-10, -5, '[', ']');
        $this->assertFalse($range1->overlap($range2));
    }

    public function testOverlapWithTouchingNegativeRanges()
    {
        $range1 = new IntRange(-20, -10, '[', ']');
        $range2 = new IntRange(-10, -5, '[', ']');
        $this->assertTrue($range1->overlap($range2));
    }

    public function testOverlapWithOneNegativeRangeInsideAnother()
    {
        $range1 = new IntRange(-20, -5, '[', ']');
        $range2 = new IntRange(-15, -10, '[', ']');
        $this->assertTrue($range1->overlap($range2));
    }

    public function testOverlapWithMixedNegativeAndPositiveRanges()
    {
        $range1 = new IntRange(-10, 10, '[', ']');
        $range2 = new IntRange(5, 15, '[', ']');
        $this->assertTrue($range1->overlap($range2));
    }

    public function testOverlapWithNullUpperBound()
    {
        $range1 = new IntRange(1, null, '[', ')');
        $range2 = new IntRange(5, 15, '[', ']');
        $this->assertTrue($range1->overlap($range2));
    }

    public function testOverlapWithBothNullBounds()
    {
        $range1 = new IntRange(null, null, '(', ')');
        $range2 = new IntRange(5, 15, '[', ']');
        $this->assertTrue($range1->overlap($range2));
    }

    public function testIsEmptyWithEmptyRange()
    {
        $range = new IntRange(5, 5, '(', ')');
        $this->assertTrue($range->isEmpty());
    }

    public function testIsEmptyWithNonEmptyRange()
    {
        $range = new IntRange(5, 10, '[', ']');
        $this->assertFalse($range->isEmpty());
    }

    public function testIsEmptyWithInclusiveBounds()
    {
        $range = new IntRange(5, 5, '[', ']');
        $this->assertFalse($range->isEmpty());
    }

    public function testIsEmptyWithExclusiveLowerBound()
    {
        $range = new IntRange(5, 5, '(', ']');
        $this->assertTrue($range->isEmpty());
    }

    public function testIsEmptyWithExclusiveUpperBound()
    {
        $range = new IntRange(5, 5, '[', ')');
        $this->assertTrue($range->isEmpty());
    }

    public function testIsBoundsValidWithValidBounds()
    {
        $range = new IntRange(5, 10, '[', ']');
        $this->assertTrue($range->isBoundsValid());
    }

    public function testIsBoundsValidWithInvalidBounds()
    {
        $range = new IntRange(10, 5, '[', ']');
        $this->assertFalse($range->isBoundsValid());
    }

    public function testIsBoundsValidWithNullLowerBound()
    {
        $range = new IntRange(null, 10, '(', ']');
        $this->assertTrue($range->isBoundsValid());
    }

    public function testIsBoundsValidWithNullUpperBound()
    {
        $range = new IntRange(5, null, '[', ')');
        $this->assertTrue($range->isBoundsValid());
    }

    public function testIsBoundsValidWithBothNullBounds()
    {
        $range = new IntRange(null, null, '(', ')');
        $this->assertTrue($range->isBoundsValid());
    }

    public function testIsBoundsValidWithInvalidNullBounds()
    {
        $range = new IntRange(10, null, '[', ')');
        $this->assertTrue($range->isBoundsValid());
    }

    public function testLengthWithInfiniteBounds()
    {
        $range = new IntRange(10, null, '[', ')');
        $this->assertNull($range->length());

        $range = new IntRange(null, 10, '(', ')');
        $this->assertNull($range->length());

        $range = new IntRange(null, null, '(', ')');
        $this->assertNull($range->length());
    }

    public function testLengthWithBoundValues()
    {
        $range = new IntRange(10, 12, '[', ')');
        $this->assertEquals(1, $range->length());

        $range = new IntRange(10, 12, '[', ']');
        $this->assertEquals(2, $range->length());

        $range = new IntRange(10, 12, '(', ']');
        $this->assertEquals(1, $range->length());

        $range = new IntRange(10, 12, '(', ')');
        $this->assertEquals(0, $range->length());
    }

    public function testUnionWithSameStep()
    {
        $range1 = new IntRange(5, 10, '[', ']');
        $range2 = new IntRange(8, 15, '[', ']');
        $result = $range1->union($range2);

        $this->assertNotNull($result);
        $this->assertEquals(5, $result->getLowerBoundValue());
        $this->assertEquals(15, $result->getUpperBoundValue());
    }

    public function testUnionWithDifferentStep()
    {
        $range1 = new IntRange(5, 10, '[', ']', 1);
        $range2 = new IntRange(8, 15, '[', ']', 2);
        $result = $range1->union($range2);

        $this->assertNull($result);
    }

    public function testUnionWithNonOverlappingRanges()
    {
        $range1 = new IntRange(5, 10, '[', ']');
        $range2 = new IntRange(15, 20, '[', ']');
        $result = $range1->union($range2);

        $this->assertNotNull($result);
        $this->assertEquals(5, $result->getLowerBoundValue());
        $this->assertEquals(20, $result->getUpperBoundValue());
    }

    public function testUnionWithTouchingRanges()
    {
        $range1 = new IntRange(5, 10, '[', ']');
        $range2 = new IntRange(10, 15, '[', ']');
        $result = $range1->union($range2);

        $this->assertNotNull($result);
        $this->assertEquals(5, $result->getLowerBoundValue());
        $this->assertEquals(15, $result->getUpperBoundValue());
    }

    public function testIntersectionWithSameStep()
    {
        $range1 = new IntRange(5, 15, '[', ']');
        $range2 = new IntRange(10, 20, '[', ']');
        $result = $range1->intersection($range2);

        $this->assertNotNull($result);
        $this->assertEquals(10, $result->getLowerBoundValue());
        $this->assertEquals(15, $result->getUpperBoundValue());
    }

    public function testIntersectionWithDifferentStep()
    {
        $range1 = new IntRange(5, 15, '[', ']', 1);
        $range2 = new IntRange(10, 20, '[', ']', 2);
        $result = $range1->intersection($range2);

        $this->assertNull($result);
    }

    public function testIntersectionWithNonOverlappingRanges()
    {
        $range1 = new IntRange(5, 10, '[', ']');
        $range2 = new IntRange(15, 20, '[', ']');
        $result = $range1->intersection($range2);

        $this->assertNull($result);
    }

    public function testIntersectionWithTouchingRanges()
    {
        $range1 = new IntRange(5, 10, '[', ']');
        $range2 = new IntRange(10, 15, '[', ']');
        $result = $range1->intersection($range2);

        $this->assertNotNull($result);
        $this->assertEquals(10, $result->getLowerBoundValue());
        $this->assertEquals(10, $result->getUpperBoundValue());
    }

    public function testIntersectionWithNullBounds()
    {
        $range1 = new IntRange(null, 10, '(', ']');
        $range2 = new IntRange(5, null, '[', ')');
        $result = $range1->intersection($range2);

        $this->assertNotNull($result);
        $this->assertEquals(5, $result->getLowerBoundValue());
        $this->assertEquals(10, $result->getUpperBoundValue());
    }

    public function testLengthWithValidBoundsAndStep()
    {
        $range = new IntRange(5, 15, '[', ']', 2);
        $this->assertEquals(5, $range->length());
    }

    public function testLengthWithNullLowerBound()
    {
        $range = new IntRange(null, 15, '(', ']', 2);
        $this->assertNull($range->length());
    }

    public function testLengthWithNullUpperBound()
    {
        $range = new IntRange(5, null, '[', ')', 2);
        $this->assertNull($range->length());
    }

    public function testLengthWithNullBounds()
    {
        $range = new IntRange(null, null, '(', ')', 2);
        $this->assertNull($range->length());
    }

    public function testLengthWithStepOne()
    {
        $range = new IntRange(5, 15, '[', ']', 1);
        $this->assertEquals(10, $range->length());
    }

    public function testLengthWithStepGreaterThanRange()
    {
        $range = new IntRange(5, 10, '[', ']', 6);
        $this->assertEquals(1, $range->length());
    }
}