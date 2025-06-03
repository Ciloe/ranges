<?php

declare(strict_types=1);

namespace Tests\Ciloe\Ranges;

use Ciloe\Ranges\Exception\CantGenerateSeriesBecauseTheArrayIsTooLarge;
use Ciloe\Ranges\Exception\InvalidBoundException;
use Ciloe\Ranges\Exception\InvalidInfiniteBoundException;
use Ciloe\Ranges\Exception\InvalidStepToGenerateSeriesException;
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

        // Additional test cases
        $range = IntRange::fromString('[0,100]');
        $this->assertEquals(0, $range->lower);
        $this->assertEquals(100, $range->upper);
        $this->assertEquals('[', $range->lowerBound);
        $this->assertEquals(']', $range->upperBound);
        $this->assertEquals(0, $range->getLowerBoundValue());
        $this->assertEquals(100, $range->getUpperBoundValue());

        $range = IntRange::fromString('(-10,10)');
        $this->assertEquals(-10, $range->lower);
        $this->assertEquals(10, $range->upper);
        $this->assertEquals('(', $range->lowerBound);
        $this->assertEquals(')', $range->upperBound);
        $this->assertEquals(-9, $range->getLowerBoundValue());
        $this->assertEquals(9, $range->getUpperBoundValue());

        $range = IntRange::fromString('[-100,-50]');
        $this->assertEquals(-100, $range->lower);
        $this->assertEquals(-50, $range->upper);
        $this->assertEquals('[', $range->lowerBound);
        $this->assertEquals(']', $range->upperBound);
        $this->assertEquals(-100, $range->getLowerBoundValue());
        $this->assertEquals(-50, $range->getUpperBoundValue());
    }

    public function testFromStringInvalidRanges()
    {
        // Test with completely invalid format
        try {
            IntRange::fromString('invalid');
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true);
        }

        // Test with mismatched brackets
        try {
            IntRange::fromString('[1;2)');
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true);
        }

        // Test with missing closing bracket
        try {
            IntRange::fromString('(1,2');
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true);
        }

        // Test with missing opening bracket
        try {
            IntRange::fromString('1,2)');
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true);
        }

        // Test with invalid infinite bound (inclusive lower)
        try {
            IntRange::fromString('[,2)');
            $this->fail('Expected InvalidInfiniteBoundException was not thrown');
        } catch (InvalidInfiniteBoundException $e) {
            $this->assertTrue(true);
        }

        // Test with invalid infinite bound (inclusive upper)
        try {
            IntRange::fromString('(2,]');
            $this->fail('Expected InvalidInfiniteBoundException was not thrown');
        } catch (InvalidInfiniteBoundException $e) {
            $this->assertTrue(true);
        }

        // Test with both invalid infinite bounds
        try {
            IntRange::fromString('[,]');
            $this->fail('Expected InvalidInfiniteBoundException was not thrown');
        } catch (InvalidInfiniteBoundException $e) {
            $this->assertTrue(true);
        }

        // Test with lower bound greater than upper bound (inclusive lower)
        try {
            IntRange::fromString('[3,1)');
            $this->fail('Expected InvalidBoundException was not thrown');
        } catch (InvalidBoundException $e) {
            $this->assertTrue(true);
        }

        // Test with lower bound greater than upper bound (inclusive upper)
        try {
            IntRange::fromString('(5,3]');
            $this->fail('Expected InvalidBoundException was not thrown');
        } catch (InvalidBoundException $e) {
            $this->assertTrue(true);
        }

        // Test with lower bound greater than upper bound (both exclusive)
        try {
            IntRange::fromString('(3,2)');
            $this->fail('Expected InvalidBoundException was not thrown');
        } catch (InvalidBoundException $e) {
            $this->assertTrue(true);
        }

        // Additional test cases

        // Test with non-numeric values
        try {
            IntRange::fromString('(abc,xyz)');
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true);
        }

        // Test with decimal values (should be integers)
        try {
            IntRange::fromString('(1.5,5.5)');
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true);
        }

        // Test with equal bounds but exclusive (which makes it invalid)
        try {
            IntRange::fromString('(5,5)');
            $this->fail('Expected InvalidBoundException was not thrown');
        } catch (InvalidBoundException $e) {
            $this->assertTrue(true);
        }
    }

    public function testContainsWithInclusiveBounds()
    {
        $range = new IntRange(1, 10, '[', ']');
        $this->assertTrue($range->contains(1));
        $this->assertTrue($range->contains(10));
        $this->assertTrue($range->contains(5));
        $this->assertFalse($range->contains(0));
        $this->assertFalse($range->contains(11));

        // Additional test cases
        $range = new IntRange(-5, 5, '[', ']');
        $this->assertTrue($range->contains(-5));
        $this->assertTrue($range->contains(0));
        $this->assertTrue($range->contains(5));
        $this->assertFalse($range->contains(-6));
        $this->assertFalse($range->contains(6));

        $range = new IntRange(100, 100, '[', ']');
        $this->assertTrue($range->contains(100));
        $this->assertFalse($range->contains(99));
        $this->assertFalse($range->contains(101));
    }

    public function testContainsWithExclusiveBounds()
    {
        $range = new IntRange(1, 10, '(', ')');
        $this->assertFalse($range->contains(1));
        $this->assertFalse($range->contains(10));
        $this->assertTrue($range->contains(5));
        $this->assertFalse($range->contains(0));
        $this->assertFalse($range->contains(11));

        // Additional test cases
        $range = new IntRange(-5, 5, '(', ')');
        $this->assertFalse($range->contains(-5));
        $this->assertTrue($range->contains(-4));
        $this->assertTrue($range->contains(0));
        $this->assertTrue($range->contains(4));
        $this->assertFalse($range->contains(5));

        $range = new IntRange(100, 102, '(', ')');
        $this->assertTrue($range->contains(101));
        $this->assertFalse($range->contains(100));
        $this->assertFalse($range->contains(102));
    }

    public function testContainsWithMixedBounds()
    {
        $range = new IntRange(1, 10, '[', ')');
        $this->assertTrue($range->contains(1));
        $this->assertFalse($range->contains(10));
        $this->assertTrue($range->contains(5));
        $this->assertFalse($range->contains(0));
        $this->assertFalse($range->contains(11));

        // Additional test cases
        $range = new IntRange(1, 10, '(', ']');
        $this->assertFalse($range->contains(1));
        $this->assertTrue($range->contains(2));
        $this->assertTrue($range->contains(10));

        $range = new IntRange(-10, 0, '[', ')');
        $this->assertTrue($range->contains(-10));
        $this->assertTrue($range->contains(-1));
        $this->assertFalse($range->contains(0));
    }

    public function testContainsWithNullLowerBound()
    {
        $range = new IntRange(null, 10, '(', ']');
        $this->assertTrue($range->contains(PHP_INT_MIN));
        $this->assertTrue($range->contains(5));
        $this->assertTrue($range->contains(10));
        $this->assertFalse($range->contains(11));

        // Additional test cases
        $range = new IntRange(null, 0, '(', ']');
        $this->assertTrue($range->contains(-1000000));
        $this->assertTrue($range->contains(-1));
        $this->assertTrue($range->contains(0));
        $this->assertFalse($range->contains(1));

        $range = new IntRange(null, -10, '(', ')');
        $this->assertTrue($range->contains(-100));
        $this->assertTrue($range->contains(-11));
        $this->assertFalse($range->contains(-10));
    }

    public function testContainsWithNullUpperBound()
    {
        $range = new IntRange(1, null, '[', ')');
        $this->assertTrue($range->contains(1));
        $this->assertTrue($range->contains(5));
        $this->assertTrue($range->contains(PHP_INT_MAX));
        $this->assertFalse($range->contains(0));

        // Additional test cases
        $range = new IntRange(0, null, '[', ')');
        $this->assertTrue($range->contains(0));
        $this->assertTrue($range->contains(1));
        $this->assertTrue($range->contains(1000000));
        $this->assertFalse($range->contains(-1));

        $range = new IntRange(-10, null, '(', ')');
        $this->assertFalse($range->contains(-10));
        $this->assertTrue($range->contains(-9));
        $this->assertTrue($range->contains(0));
        $this->assertTrue($range->contains(100));
    }

    public function testContainsWithBothNullBounds()
    {
        $range = new IntRange(null, null, '(', ')');
        $this->assertTrue($range->contains(PHP_INT_MIN));
        $this->assertTrue($range->contains(0));
        $this->assertTrue($range->contains(PHP_INT_MAX));

        // Additional test cases
        $range = new IntRange(null, null, '[', ']');
        $this->assertTrue($range->contains(-1000000));
        $this->assertTrue($range->contains(0));
        $this->assertTrue($range->contains(1000000));

        // Test with step value
        $range = new IntRange(null, null, '(', ')', 2);
        $this->assertTrue($range->contains(-1000000));
        $this->assertTrue($range->contains(0));
        $this->assertTrue($range->contains(1000000));
    }

    public function testOverlapWithOverlappingRanges()
    {
        $range1 = new IntRange(1, 10, '[', ']');
        $range2 = new IntRange(5, 15, '[', ']');
        $this->assertTrue($range1->overlap($range2));

        // Additional test cases
        $range1 = new IntRange(1, 10, '(', ')');
        $range2 = new IntRange(5, 15, '(', ')');
        $this->assertTrue($range1->overlap($range2));

        $range1 = new IntRange(1, 10, '[', ']');
        $range2 = new IntRange(0, 5, '[', ']');
        $this->assertTrue($range1->overlap($range2));
    }

    public function testOverlapWithNonOverlappingRanges()
    {
        $range1 = new IntRange(1, 10, '[', ']');
        $range2 = new IntRange(11, 20, '[', ']');
        $this->assertFalse($range1->overlap($range2));

        // Additional test cases
        $range1 = new IntRange(1, 5, '[', ']');
        $range2 = new IntRange(6, 10, '[', ']');
        $this->assertFalse($range1->overlap($range2));

        $range1 = new IntRange(-20, -15, '[', ']');
        $range2 = new IntRange(-10, -5, '[', ']');
        $this->assertFalse($range1->overlap($range2));
    }

    public function testOverlapWithTouchingRanges()
    {
        $range1 = new IntRange(1, 10, '[', ']');
        $range2 = new IntRange(10, 20, '[', ']');
        $this->assertTrue($range1->overlap($range2));

        // Additional test cases
        $range1 = new IntRange(1, 10, '[', ')');
        $range2 = new IntRange(10, 20, '[', ']');
        $this->assertFalse($range1->overlap($range2));

        $range1 = new IntRange(1, 10, '[', ']');
        $range2 = new IntRange(10, 20, '(', ']');
        $this->assertFalse($range1->overlap($range2));

        $range1 = new IntRange(-10, 0, '[', ']');
        $range2 = new IntRange(0, 10, '[', ']');
        $this->assertTrue($range1->overlap($range2));
    }

    public function testOverlapWithOneRangeInsideAnother()
    {
        $range1 = new IntRange(1, 20, '[', ']');
        $range2 = new IntRange(5, 15, '[', ']');
        $this->assertTrue($range1->overlap($range2));

        // Additional test cases
        $range1 = new IntRange(1, 20, '(', ')');
        $range2 = new IntRange(5, 15, '[', ']');
        $this->assertTrue($range1->overlap($range2));

        $range1 = new IntRange(5, 15, '[', ']');
        $range2 = new IntRange(1, 20, '[', ']');
        $this->assertTrue($range1->overlap($range2));

        $range1 = new IntRange(5, 5, '[', ']');
        $range2 = new IntRange(1, 10, '[', ']');
        $this->assertTrue($range1->overlap($range2));
    }

    public function testOverlapWithExclusiveBounds()
    {
        $range1 = new IntRange(1, 10, '(', ')');
        $range2 = new IntRange(10, 20, '(', ')');
        $this->assertFalse($range1->overlap($range2));

        // Additional test cases
        $range1 = new IntRange(1, 10, '(', ']');
        $range2 = new IntRange(10, 20, '(', ')');
        $this->assertFalse($range1->overlap($range2));

        $range1 = new IntRange(1, 11, '(', ')');
        $range2 = new IntRange(10, 20, '(', ')');
        $this->assertFalse($range1->overlap($range2));

        $range1 = new IntRange(5, 15, '(', ')');
        $range2 = new IntRange(10, 20, '(', ')');
        $this->assertTrue($range1->overlap($range2));
    }

    public function testOverlapWithNullLowerBound()
    {
        $range1 = new IntRange(null, 10, '(', ']');
        $range2 = new IntRange(5, 15, '[', ']');
        $this->assertTrue($range1->overlap($range2));

        // Additional test cases
        $range1 = new IntRange(null, 5, '(', ']');
        $range2 = new IntRange(5, 15, '(', ']');
        $this->assertFalse($range1->overlap($range2));

        $range1 = new IntRange(null, 0, '(', ']');
        $range2 = new IntRange(-10, -5, '[', ']');
        $this->assertTrue($range1->overlap($range2));

        $range1 = new IntRange(null, -10, '(', ')');
        $range2 = new IntRange(-10, 0, '[', ']');
        $this->assertFalse($range1->overlap($range2));
    }

    public function testOverlapWithNegativeRanges()
    {
        $range1 = new IntRange(-20, -10, '[', ']');
        $range2 = new IntRange(-15, -5, '[', ']');
        $this->assertTrue($range1->overlap($range2));

        // Additional test cases
        $range1 = new IntRange(-20, -10, '(', ')');
        $range2 = new IntRange(-15, -5, '(', ')');
        $this->assertTrue($range1->overlap($range2));

        $range1 = new IntRange(-20, -10, '[', ']');
        $range2 = new IntRange(-30, -15, '[', ']');
        $this->assertTrue($range1->overlap($range2));
    }

    public function testOverlapWithNonOverlappingNegativeRanges()
    {
        $range1 = new IntRange(-20, -15, '[', ']');
        $range2 = new IntRange(-10, -5, '[', ']');
        $this->assertFalse($range1->overlap($range2));

        // Additional test cases
        $range1 = new IntRange(-20, -15, '(', ')');
        $range2 = new IntRange(-10, -5, '(', ')');
        $this->assertFalse($range1->overlap($range2));

        $range1 = new IntRange(-20, -15, '[', ']');
        $range2 = new IntRange(-14, -5, '[', ']');
        $this->assertFalse($range1->overlap($range2));
    }

    public function testOverlapWithTouchingNegativeRanges()
    {
        $range1 = new IntRange(-20, -10, '[', ']');
        $range2 = new IntRange(-10, -5, '[', ']');
        $this->assertTrue($range1->overlap($range2));

        // Additional test cases
        $range1 = new IntRange(-20, -10, '[', ')');
        $range2 = new IntRange(-10, -5, '[', ']');
        $this->assertFalse($range1->overlap($range2));

        $range1 = new IntRange(-20, -10, '[', ']');
        $range2 = new IntRange(-10, -5, '(', ']');
        $this->assertFalse($range1->overlap($range2));
    }

    public function testOverlapWithOneNegativeRangeInsideAnother()
    {
        $range1 = new IntRange(-20, -5, '[', ']');
        $range2 = new IntRange(-15, -10, '[', ']');
        $this->assertTrue($range1->overlap($range2));

        // Additional test cases
        $range1 = new IntRange(-20, -5, '(', ')');
        $range2 = new IntRange(-15, -10, '[', ']');
        $this->assertTrue($range1->overlap($range2));

        $range1 = new IntRange(-15, -10, '[', ']');
        $range2 = new IntRange(-20, -5, '[', ']');
        $this->assertTrue($range1->overlap($range2));
    }

    public function testOverlapWithMixedNegativeAndPositiveRanges()
    {
        $range1 = new IntRange(-10, 10, '[', ']');
        $range2 = new IntRange(5, 15, '[', ']');
        $this->assertTrue($range1->overlap($range2));

        // Additional test cases
        $range1 = new IntRange(-10, 0, '[', ']');
        $range2 = new IntRange(0, 10, '[', ']');
        $this->assertTrue($range1->overlap($range2));

        $range1 = new IntRange(-10, -1, '[', ']');
        $range2 = new IntRange(0, 10, '[', ']');
        $this->assertFalse($range1->overlap($range2));

        $range1 = new IntRange(-10, 0, '[', ')');
        $range2 = new IntRange(0, 10, '[', ']');
        $this->assertFalse($range1->overlap($range2));
    }

    public function testOverlapWithNullUpperBound()
    {
        $range1 = new IntRange(1, null, '[', ')');
        $range2 = new IntRange(5, 15, '[', ']');
        $this->assertTrue($range1->overlap($range2));

        // Additional test cases
        $range1 = new IntRange(20, null, '[', ')');
        $range2 = new IntRange(5, 15, '[', ']');
        $this->assertFalse($range1->overlap($range2));

        $range1 = new IntRange(15, null, '[', ')');
        $range2 = new IntRange(5, 15, '[', ']');
        $this->assertTrue($range1->overlap($range2));

        $range1 = new IntRange(15, null, '(', ')');
        $range2 = new IntRange(5, 15, '[', ']');
        $this->assertFalse($range1->overlap($range2));
    }

    public function testOverlapWithBothNullBounds()
    {
        $range1 = new IntRange(null, null, '(', ')');
        $range2 = new IntRange(5, 15, '[', ']');
        $this->assertTrue($range1->overlap($range2));

        // Additional test cases
        $range1 = new IntRange(null, null, '[', ']');
        $range2 = new IntRange(-100, 100, '[', ']');
        $this->assertTrue($range1->overlap($range2));

        $range1 = new IntRange(null, null, '(', ')');
        $range2 = new IntRange(null, null, '[', ']');
        $this->assertTrue($range1->overlap($range2));

        // Test with empty range
        $range1 = new IntRange(5, 5, '(', ')');
        $range2 = new IntRange(null, null, '(', ')');
        $this->assertFalse($range1->overlap($range2));
    }

    public function testIsEmptyWithEmptyRange()
    {
        $range = new IntRange(5, 5, '(', ')');
        $this->assertTrue($range->isEmpty());

        // Additional test cases
        $range = new IntRange(0, 0, '(', ')');
        $this->assertTrue($range->isEmpty());

        $range = new IntRange(-5, -5, '(', ')');
        $this->assertTrue($range->isEmpty());
    }

    public function testIsEmptyWithNonEmptyRange()
    {
        $range = new IntRange(5, 10, '[', ']');
        $this->assertFalse($range->isEmpty());

        // Additional test cases
        $range = new IntRange(5, 6, '(', ')');
        $this->assertFalse($range->isEmpty());

        $range = new IntRange(-10, -5, '[', ']');
        $this->assertFalse($range->isEmpty());

        $range = new IntRange(-5, 5, '(', ')');
        $this->assertFalse($range->isEmpty());
    }

    public function testIsEmptyWithInclusiveBounds()
    {
        $range = new IntRange(5, 5, '[', ']');
        $this->assertFalse($range->isEmpty());

        // Additional test cases
        $range = new IntRange(0, 0, '[', ']');
        $this->assertFalse($range->isEmpty());

        $range = new IntRange(-5, -5, '[', ']');
        $this->assertFalse($range->isEmpty());
    }

    public function testIsEmptyWithExclusiveLowerBound()
    {
        $range = new IntRange(5, 5, '(', ']');
        $this->assertTrue($range->isEmpty());

        // Additional test cases
        $range = new IntRange(0, 0, '(', ']');
        $this->assertTrue($range->isEmpty());

        $range = new IntRange(-5, -5, '(', ']');
        $this->assertTrue($range->isEmpty());

        $range = new IntRange(5, 6, '(', ']');
        $this->assertFalse($range->isEmpty());
    }

    public function testIsEmptyWithExclusiveUpperBound()
    {
        $range = new IntRange(5, 5, '[', ')');
        $this->assertTrue($range->isEmpty());

        // Additional test cases
        $range = new IntRange(0, 0, '[', ')');
        $this->assertTrue($range->isEmpty());

        $range = new IntRange(-5, -5, '[', ')');
        $this->assertTrue($range->isEmpty());

        $range = new IntRange(5, 6, '[', ')');
        $this->assertFalse($range->isEmpty());
    }

    public function testIsEmptyWithNullBounds()
    {
        // Test with null lower bound
        $range = new IntRange(null, 5, '(', ']');
        $this->assertFalse($range->isEmpty());

        // Test with null upper bound
        $range = new IntRange(5, null, '[', ')');
        $this->assertFalse($range->isEmpty());

        // Test with both null bounds
        $range = new IntRange(null, null, '(', ')');
        $this->assertFalse($range->isEmpty());
    }

    public function testIsBoundsValidWithValidBounds()
    {
        $range = new IntRange(5, 10, '[', ']');
        $this->assertTrue($range->isBoundsValid());

        // Additional test cases
        $range = new IntRange(0, 0, '[', ']');
        $this->assertTrue($range->isBoundsValid());

        $range = new IntRange(-10, -5, '[', ']');
        $this->assertTrue($range->isBoundsValid());

        $range = new IntRange(-10, 10, '[', ']');
        $this->assertTrue($range->isBoundsValid());
    }

    public function testIsBoundsValidWithInvalidBounds()
    {
        $range = new IntRange(10, 5, '[', ']');
        $this->assertFalse($range->isBoundsValid());

        // Additional test cases
        $range = new IntRange(0, -5, '[', ']');
        $this->assertFalse($range->isBoundsValid());

        $range = new IntRange(5, 3, '[', ']');
        $this->assertFalse($range->isBoundsValid());

        $range = new IntRange(-5, -10, '[', ']');
        $this->assertFalse($range->isBoundsValid());
    }

    public function testIsBoundsValidWithNullLowerBound()
    {
        $range = new IntRange(null, 10, '(', ']');
        $this->assertTrue($range->isBoundsValid());

        // Additional test cases
        $range = new IntRange(null, 0, '(', ']');
        $this->assertTrue($range->isBoundsValid());

        $range = new IntRange(null, -10, '(', ']');
        $this->assertTrue($range->isBoundsValid());

        $range = new IntRange(null, PHP_INT_MAX, '(', ']');
        $this->assertTrue($range->isBoundsValid());
    }

    public function testIsBoundsValidWithNullUpperBound()
    {
        $range = new IntRange(5, null, '[', ')');
        $this->assertTrue($range->isBoundsValid());

        // Additional test cases
        $range = new IntRange(0, null, '[', ')');
        $this->assertTrue($range->isBoundsValid());

        $range = new IntRange(-10, null, '[', ')');
        $this->assertTrue($range->isBoundsValid());

        $range = new IntRange(PHP_INT_MIN, null, '[', ')');
        $this->assertTrue($range->isBoundsValid());
    }

    public function testIsBoundsValidWithBothNullBounds()
    {
        $range = new IntRange(null, null, '(', ')');
        $this->assertTrue($range->isBoundsValid());

        // Additional test cases
        $range = new IntRange(null, null, '[', ']');
        $this->assertTrue($range->isBoundsValid());

        $range = new IntRange(null, null, '(', ']');
        $this->assertTrue($range->isBoundsValid());

        $range = new IntRange(null, null, '[', ')');
        $this->assertTrue($range->isBoundsValid());
    }

    public function testIsBoundsValidWithInvalidNullBounds()
    {
        $range = new IntRange(10, null, '[', ')');
        $this->assertTrue($range->isBoundsValid());

        // Additional test cases
        $range = new IntRange(PHP_INT_MAX, null, '[', ')');
        $this->assertTrue($range->isBoundsValid());

        $range = new IntRange(null, PHP_INT_MIN, '(', ']');
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

        // Additional test cases
        $range = new IntRange(-10, null, '[', ')');
        $this->assertNull($range->length());

        $range = new IntRange(null, -10, '(', ']');
        $this->assertNull($range->length());

        $range = new IntRange(PHP_INT_MIN, null, '[', ')');
        $this->assertNull($range->length());

        $range = new IntRange(null, PHP_INT_MAX, '(', ']');
        $this->assertNull($range->length());
    }

    public function testLengthWithBoundValues()
    {
        $range = new IntRange(10, 12, '[', ')');
        $this->assertEquals(2, $range->length());

        $range = new IntRange(10, 12, '[', ']');
        $this->assertEquals(3, $range->length());

        $range = new IntRange(10, 12, '(', ']');
        $this->assertEquals(2, $range->length());

        $range = new IntRange(10, 12, '(', ')');
        $this->assertEquals(1, $range->length());

        // Additional test cases
        $range = new IntRange(0, 5, '[', ']');
        $this->assertEquals(6, $range->length());

        $range = new IntRange(-5, 5, '[', ']');
        $this->assertEquals(11, $range->length());

        $range = new IntRange(-10, -5, '[', ']');
        $this->assertEquals(6, $range->length());

        $range = new IntRange(5, 5, '[', ']');
        $this->assertEquals(1, $range->length());

        $range = new IntRange(5, 5, '(', ')');
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

        // Additional test cases
        $range1 = new IntRange(0, 5, '[', ']');
        $range2 = new IntRange(3, 8, '[', ']');
        $result = $range1->union($range2);

        $this->assertNotNull($result);
        $this->assertEquals(0, $result->getLowerBoundValue());
        $this->assertEquals(8, $result->getUpperBoundValue());

        $range1 = new IntRange(-10, -5, '[', ']');
        $range2 = new IntRange(-7, -2, '[', ']');
        $result = $range1->union($range2);

        $this->assertNotNull($result);
        $this->assertEquals(-10, $result->getLowerBoundValue());
        $this->assertEquals(-2, $result->getUpperBoundValue());
    }

    public function testUnionWithDifferentStep()
    {
        $range1 = new IntRange(5, 10, '[', ']', 1);
        $range2 = new IntRange(8, 15, '[', ']', 2);
        $result = $range1->union($range2);

        $this->assertNull($result);

        // Additional test cases
        $range1 = new IntRange(0, 10, '[', ']', 2);
        $range2 = new IntRange(5, 15, '[', ']', 3);
        $result = $range1->union($range2);

        $this->assertNull($result);

        $range1 = new IntRange(-10, 0, '[', ']', 1);
        $range2 = new IntRange(-5, 5, '[', ']', 5);
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

        // Additional test cases
        $range1 = new IntRange(0, 5, '[', ']');
        $range2 = new IntRange(10, 15, '[', ']');
        $result = $range1->union($range2);

        $this->assertNotNull($result);
        $this->assertEquals(0, $result->getLowerBoundValue());
        $this->assertEquals(15, $result->getUpperBoundValue());

        $range1 = new IntRange(-20, -15, '[', ']');
        $range2 = new IntRange(-10, -5, '[', ']');
        $result = $range1->union($range2);

        $this->assertNotNull($result);
        $this->assertEquals(-20, $result->getLowerBoundValue());
        $this->assertEquals(-5, $result->getUpperBoundValue());

        $range1 = new IntRange(-10, -5, '[', ']');
        $range2 = new IntRange(5, 10, '[', ']');
        $result = $range1->union($range2);

        $this->assertNotNull($result);
        $this->assertEquals(-10, $result->getLowerBoundValue());
        $this->assertEquals(10, $result->getUpperBoundValue());
    }

    public function testUnionWithTouchingRanges()
    {
        $range1 = new IntRange(5, 10, '[', ']');
        $range2 = new IntRange(10, 15, '[', ']');
        $result = $range1->union($range2);

        $this->assertNotNull($result);
        $this->assertEquals(5, $result->getLowerBoundValue());
        $this->assertEquals(15, $result->getUpperBoundValue());

        // Additional test cases
        $range1 = new IntRange(0, 5, '[', ')');
        $range2 = new IntRange(5, 10, '[', ']');
        $result = $range1->union($range2);

        $this->assertNotNull($result);
        $this->assertEquals(0, $result->getLowerBoundValue());
        $this->assertEquals(10, $result->getUpperBoundValue());

        $range1 = new IntRange(-10, -5, '[', ']');
        $range2 = new IntRange(-5, 0, '(', ']');
        $result = $range1->union($range2);

        $this->assertNotNull($result);
        $this->assertEquals(-10, $result->getLowerBoundValue());
        $this->assertEquals(0, $result->getUpperBoundValue());
    }

    public function testUnionWithNullBounds()
    {
        // Test with null lower bound
        $range1 = new IntRange(null, 10, '(', ']');
        $range2 = new IntRange(5, 15, '[', ']');
        $result = $range1->union($range2);

        $this->assertNotNull($result);
        $this->assertNull($result->lower);
        $this->assertEquals(15, $result->getUpperBoundValue());

        // Test with null upper bound
        $range1 = new IntRange(5, null, '[', ')');
        $range2 = new IntRange(0, 10, '[', ']');
        $result = $range1->union($range2);

        $this->assertNotNull($result);
        $this->assertEquals(0, $result->getLowerBoundValue());
        $this->assertNull($result->upper);

        // Test with both null bounds
        $range1 = new IntRange(null, null, '(', ')');
        $range2 = new IntRange(-10, 10, '[', ']');
        $result = $range1->union($range2);

        $this->assertNotNull($result);
        $this->assertNull($result->lower);
        $this->assertNull($result->upper);
    }

    public function testIntersectionWithSameStep()
    {
        $range1 = new IntRange(5, 15, '[', ']');
        $range2 = new IntRange(10, 20, '[', ']');
        $result = $range1->intersection($range2);

        $this->assertNotNull($result);
        $this->assertEquals(10, $result->getLowerBoundValue());
        $this->assertEquals(15, $result->getUpperBoundValue());

        // Additional test cases
        $range1 = new IntRange(0, 10, '[', ']');
        $range2 = new IntRange(5, 15, '[', ']');
        $result = $range1->intersection($range2);

        $this->assertNotNull($result);
        $this->assertEquals(5, $result->getLowerBoundValue());
        $this->assertEquals(10, $result->getUpperBoundValue());

        $range1 = new IntRange(-10, 0, '[', ']');
        $range2 = new IntRange(-5, 5, '[', ']');
        $result = $range1->intersection($range2);

        $this->assertNotNull($result);
        $this->assertEquals(-5, $result->getLowerBoundValue());
        $this->assertEquals(0, $result->getUpperBoundValue());

        $range1 = new IntRange(-20, -10, '[', ']');
        $range2 = new IntRange(-15, -5, '[', ']');
        $result = $range1->intersection($range2);

        $this->assertNotNull($result);
        $this->assertEquals(-15, $result->getLowerBoundValue());
        $this->assertEquals(-10, $result->getUpperBoundValue());
    }

    public function testIntersectionWithDifferentStep()
    {
        $range1 = new IntRange(5, 15, '[', ']', 1);
        $range2 = new IntRange(10, 20, '[', ']', 2);
        $result = $range1->intersection($range2);

        $this->assertNull($result);

        // Additional test cases
        $range1 = new IntRange(0, 10, '[', ']', 2);
        $range2 = new IntRange(5, 15, '[', ']', 3);
        $result = $range1->intersection($range2);

        $this->assertNull($result);

        $range1 = new IntRange(-10, 0, '[', ']', 1);
        $range2 = new IntRange(-5, 5, '[', ']', 5);
        $result = $range1->intersection($range2);

        $this->assertNull($result);
    }

    public function testIntersectionWithNonOverlappingRanges()
    {
        $range1 = new IntRange(5, 10, '[', ']');
        $range2 = new IntRange(15, 20, '[', ']');
        $result = $range1->intersection($range2);

        $this->assertNull($result);

        // Additional test cases
        $range1 = new IntRange(0, 5, '[', ']');
        $range2 = new IntRange(10, 15, '[', ']');
        $result = $range1->intersection($range2);

        $this->assertNull($result);

        $range1 = new IntRange(-20, -15, '[', ']');
        $range2 = new IntRange(-10, -5, '[', ']');
        $result = $range1->intersection($range2);

        $this->assertNull($result);

        $range1 = new IntRange(-10, -5, '[', ']');
        $range2 = new IntRange(5, 10, '[', ']');
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

        // Additional test cases
        $range1 = new IntRange(0, 5, '[', ')');
        $range2 = new IntRange(5, 10, '[', ']');
        $result = $range1->intersection($range2);

        $this->assertNull($result);

        $range1 = new IntRange(-10, -5, '[', ']');
        $range2 = new IntRange(-5, 0, '(', ']');
        $result = $range1->intersection($range2);

        $this->assertNull($result);

        $range1 = new IntRange(5, 10, '[', ']');
        $range2 = new IntRange(10, 15, '(', ']');
        $result = $range1->intersection($range2);

        $this->assertNull($result);
    }

    public function testIntersectionWithNullBounds()
    {
        $range1 = new IntRange(null, 10, '(', ']');
        $range2 = new IntRange(5, null, '[', ')');
        $result = $range1->intersection($range2);

        $this->assertNotNull($result);
        $this->assertEquals(5, $result->getLowerBoundValue());
        $this->assertEquals(10, $result->getUpperBoundValue());

        // Additional test cases
        $range1 = new IntRange(null, 0, '(', ']');
        $range2 = new IntRange(-10, null, '[', ')');
        $result = $range1->intersection($range2);

        $this->assertNotNull($result);
        $this->assertEquals(-10, $result->getLowerBoundValue());
        $this->assertEquals(0, $result->getUpperBoundValue());

        $range1 = new IntRange(5, null, '[', ')');
        $range2 = new IntRange(null, 15, '(', ']');
        $result = $range1->intersection($range2);

        $this->assertNotNull($result);
        $this->assertEquals(5, $result->getLowerBoundValue());
        $this->assertEquals(15, $result->getUpperBoundValue());

        $range1 = new IntRange(null, null, '(', ')');
        $range2 = new IntRange(-10, 10, '[', ']');
        $result = $range1->intersection($range2);

        $this->assertNotNull($result);
        $this->assertEquals(-10, $result->getLowerBoundValue());
        $this->assertEquals(10, $result->getUpperBoundValue());
    }

    public function testIntersectionWithOneRangeInsideAnother()
    {
        $range1 = new IntRange(1, 20, '[', ']');
        $range2 = new IntRange(5, 15, '[', ']');
        $result = $range1->intersection($range2);

        $this->assertNotNull($result);
        $this->assertEquals(5, $result->getLowerBoundValue());
        $this->assertEquals(15, $result->getUpperBoundValue());

        $range1 = new IntRange(5, 15, '[', ']');
        $range2 = new IntRange(1, 20, '[', ']');
        $result = $range1->intersection($range2);

        $this->assertNotNull($result);
        $this->assertEquals(5, $result->getLowerBoundValue());
        $this->assertEquals(15, $result->getUpperBoundValue());

        $range1 = new IntRange(-20, 20, '[', ']');
        $range2 = new IntRange(-10, 10, '[', ']');
        $result = $range1->intersection($range2);

        $this->assertNotNull($result);
        $this->assertEquals(-10, $result->getLowerBoundValue());
        $this->assertEquals(10, $result->getUpperBoundValue());
    }

    public function testLengthWithValidBoundsAndStep()
    {
        $range = new IntRange(5, 15, '[', ']', 2);
        $this->assertEquals(6, $range->length());

        // Additional test cases
        $range = new IntRange(0, 10, '[', ']', 2);
        $this->assertEquals(6, $range->length());

        $range = new IntRange(-10, 0, '[', ']', 2);
        $this->assertEquals(6, $range->length());

        $range = new IntRange(-10, 10, '[', ']', 4);
        $this->assertEquals(6, $range->length());
    }

    public function testLengthWithNullLowerBound()
    {
        $range = new IntRange(null, 15, '(', ']', 2);
        $this->assertNull($range->length());

        // Additional test cases
        $range = new IntRange(null, 0, '(', ']', 3);
        $this->assertNull($range->length());

        $range = new IntRange(null, -10, '(', ']', 5);
        $this->assertNull($range->length());
    }

    public function testLengthWithNullUpperBound()
    {
        $range = new IntRange(5, null, '[', ')', 2);
        $this->assertNull($range->length());

        // Additional test cases
        $range = new IntRange(0, null, '[', ')', 3);
        $this->assertNull($range->length());

        $range = new IntRange(-10, null, '[', ')', 4);
        $this->assertNull($range->length());
    }

    public function testLengthWithNullBounds()
    {
        $range = new IntRange(null, null, '(', ')', 2);
        $this->assertNull($range->length());

        // Additional test cases
        $range = new IntRange(null, null, '[', ']', 3);
        $this->assertNull($range->length());

        $range = new IntRange(null, null, '(', ']', 5);
        $this->assertNull($range->length());

        $range = new IntRange(null, null, '[', ')', 10);
        $this->assertNull($range->length());
    }

    public function testLengthWithStepOne()
    {
        $range = new IntRange(5, 15, '[', ']', 1);
        $this->assertEquals(11, $range->length());

        // Additional test cases
        $range = new IntRange(0, 10, '[', ']', 1);
        $this->assertEquals(11, $range->length());

        $range = new IntRange(-10, 0, '[', ']', 1);
        $this->assertEquals(11, $range->length());

        $range = new IntRange(-10, 10, '[', ']', 1);
        $this->assertEquals(21, $range->length());

        $range = new IntRange(5, 5, '[', ']', 1);
        $this->assertEquals(1, $range->length());
    }

    public function testLengthWithStepMoreThanOne()
    {
        $range = new IntRange(5, 11, '[', ']', 6);
        $this->assertEquals(2, $range->length());

        $range = new IntRange(5, 11, '[', ']', 2);
        $this->assertEquals(4, $range->length());

        $range = new IntRange(5, 12, '[', ']', 2);
        $this->assertEquals(4, $range->length());
    }

    public function testLengthWithStepGreaterThanRange()
    {
        $range = new IntRange(5, 10, '[', ']', 6);
        $this->assertEquals(1, $range->length());

        // Additional test cases
        $range = new IntRange(0, 3, '[', ']', 4);
        $this->assertEquals(1, $range->length());

        $range = new IntRange(-5, -2, '[', ']', 4);
        $this->assertEquals(1, $range->length());

        $range = new IntRange(5, 6, '[', ']', 2);
        $this->assertEquals(1, $range->length());
    }

    public function testGenerateSeriesWithValidBoundsAndStep()
    {
        $range = new IntRange(1, 10, '[', ']', 2);
        $this->assertEquals([1, 3, 5, 7, 9], $range->generateSeries());

        // Additional test cases
        $range = new IntRange(0, 10, '[', ']', 2);
        $this->assertEquals([0, 2, 4, 6, 8, 10], $range->generateSeries());

        $range = new IntRange(-10, 0, '[', ']', 2);
        $this->assertEquals([-10, -8, -6, -4, -2, 0], $range->generateSeries());

        $range = new IntRange(-5, 5, '[', ']', 2);
        $this->assertEquals([-5, -3, -1, 1, 3, 5], $range->generateSeries());

        $range = new IntRange(1, 10, '(', ')', 2);
        $this->assertEquals([2, 4, 6, 8], $range->generateSeries());

        $range = new IntRange(1, 10, '[', ')', 3);
        $this->assertEquals([1, 4, 7], $range->generateSeries());
    }

    public function testGenerateSeriesWithNullLowerBound()
    {
        $this->expectException(CantGenerateSeriesBecauseTheArrayIsTooLarge::class);
        $range = new IntRange(null, 5, '(', ']', 1);
        $range->generateSeries();
    }

    public function testGenerateSeriesWithNullUpperBound()
    {
        $this->expectException(CantGenerateSeriesBecauseTheArrayIsTooLarge::class);
        $range = new IntRange(1, null, '[', ')', 1);
        $range->generateSeries();
    }

    public function testGenerateSeriesWithNullBounds()
    {
        $this->expectException(CantGenerateSeriesBecauseTheArrayIsTooLarge::class);
        $range = new IntRange(null, null, '(', ')', 1);
        $range->generateSeries();
    }

    public function testGenerateSeriesWithStepGreaterThanRange()
    {
        $this->expectException(InvalidStepToGenerateSeriesException::class);
        $range = new IntRange(1, 5, '[', ']', 10);
        $range->generateSeries();
    }

    public function testGenerateSeriesWithSinglePointRange()
    {
        $range = new IntRange(5, 5, '[', ']', 1);
        $this->assertEquals([5], $range->generateSeries());

        $range = new IntRange(0, 0, '[', ']', 1);
        $this->assertEquals([0], $range->generateSeries());

        $range = new IntRange(-5, -5, '[', ']', 1);
        $this->assertEquals([-5], $range->generateSeries());
    }

    public function testGenerateSeriesWithEmptyRange()
    {
        $range = new IntRange(5, 5, '(', ')', 1);
        $this->assertEquals([], $range->generateSeries());

        $range = new IntRange(5, 5, '[', ')', 1);
        $this->assertEquals([], $range->generateSeries());

        $range = new IntRange(5, 5, '(', ']', 1);
        $this->assertEquals([], $range->generateSeries());
    }

    public function testGenerateSeriesWithDifferentSteps()
    {
        $range = new IntRange(1, 10, '[', ']', 3);
        $this->assertEquals([1, 4, 7, 10], $range->generateSeries());

        $range = new IntRange(0, 20, '[', ']', 5);
        $this->assertEquals([0, 5, 10, 15, 20], $range->generateSeries());

        $range = new IntRange(-10, 10, '[', ']', 4);
        $this->assertEquals([-10, -6, -2, 2, 6, 10], $range->generateSeries());
    }

    public function testToString()
    {
        // Test with regular bounds
        $range = new IntRange(1, 10, '[', ']');
        $this->assertEquals('[1,10]', (string)$range);

        // Test with exclusive bounds
        $range = new IntRange(1, 10, '(', ')');
        $this->assertEquals('(1,10)', (string)$range);

        // Test with mixed bounds
        $range = new IntRange(1, 10, '[', ')');
        $this->assertEquals('[1,10)', (string)$range);

        $range = new IntRange(1, 10, '(', ']');
        $this->assertEquals('(1,10]', (string)$range);

        // Test with null lower bound
        $range = new IntRange(null, 10, '(', ']');
        $this->assertEquals('(,10]', (string)$range);

        // Test with null upper bound
        $range = new IntRange(1, null, '[', ')');
        $this->assertEquals('[1,)', (string)$range);

        // Test with both null bounds
        $range = new IntRange(null, null, '(', ')');
        $this->assertEquals('(,)', (string)$range);

        // Test with negative values
        $range = new IntRange(-10, -1, '[', ']');
        $this->assertEquals('[-10,-1]', (string)$range);

        // Test with step value (should not affect string representation)
        $range = new IntRange(1, 10, '[', ']', 2);
        $this->assertEquals('[1,10]', (string)$range);
    }

    public function testEquals()
    {
        // Test with identical ranges
        $range1 = new IntRange(1, 10, '[', ']');
        $range2 = new IntRange(1, 10, '[', ']');
        $this->assertTrue($range1->equals($range2));

        // Test with different lower bounds
        $range1 = new IntRange(1, 10, '[', ']');
        $range2 = new IntRange(2, 10, '[', ']');
        $this->assertFalse($range1->equals($range2));

        // Test with different lower bounds but different includes
        $range1 = new IntRange(1, 10, '(', ']');
        $range2 = new IntRange(2, 11, '[', ')');
        $this->assertTrue($range1->equals($range2));

        // Test with different upper bounds
        $range1 = new IntRange(1, 10, '[', ']');
        $range2 = new IntRange(1, 11, '[', ']');
        $this->assertFalse($range1->equals($range2));

        // Test with different lower bound types
        $range1 = new IntRange(1, 10, '[', ']');
        $range2 = new IntRange(1, 10, '(', ']');
        $this->assertFalse($range1->equals($range2));

        // Test with different upper bound types
        $range1 = new IntRange(1, 10, '[', ']');
        $range2 = new IntRange(1, 10, '[', ')');
        $this->assertFalse($range1->equals($range2));

        // Test with different step values
        $range1 = new IntRange(1, 10, '[', ']', 1);
        $range2 = new IntRange(1, 10, '[', ']', 2);
        $this->assertFalse($range1->equals($range2));

        // Test with null bounds
        $range1 = new IntRange(null, 10, '(', ']');
        $range2 = new IntRange(null, 10, '(', ']');
        $this->assertTrue($range1->equals($range2));

        $range1 = new IntRange(1, null, '[', ')');
        $range2 = new IntRange(1, null, '[', ')');
        $this->assertTrue($range1->equals($range2));

        $range1 = new IntRange(null, null, '(', ')');
        $range2 = new IntRange(null, null, '(', ')');
        $this->assertTrue($range1->equals($range2));

        // Test with different null bounds
        $range1 = new IntRange(null, 10, '(', ']');
        $range2 = new IntRange(1, 10, '[', ']');
        $this->assertFalse($range1->equals($range2));
    }

    public function testSplit()
    {
        // Test splitting in the middle of the range
        $range = new IntRange(1, 10, '[', ']');
        $result = $range->split(5);
        $this->assertCount(2, $result);
        $this->assertEquals('[1,5)', (string)$result[0]);
        $this->assertEquals('[5,10]', (string)$result[1]);

        // Test splitting at the lower bound
        $range = new IntRange(1, 10, '[', ']');
        $result = $range->split(1);
        $this->assertCount(2, $result);
        $this->assertEquals('[1,1)', (string)$result[0]);
        $this->assertEquals('[1,10]', (string)$result[1]);

        // Test splitting at the upper bound
        $range = new IntRange(1, 10, '[', ']');
        $result = $range->split(10);
        $this->assertCount(2, $result);
        $this->assertEquals('[1,10)', (string)$result[0]);
        $this->assertEquals('[10,10]', (string)$result[1]);

        // Test splitting outside the range (below)
        $range = new IntRange(1, 10, '[', ']');
        $result = $range->split(0);
        $this->assertCount(1, $result);
        $this->assertEquals('[1,10]', (string)$result[0]);

        // Test splitting outside the range (above)
        $range = new IntRange(1, 10, '[', ']');
        $result = $range->split(11);
        $this->assertCount(1, $result);
        $this->assertEquals('[1,10]', (string)$result[0]);

        // Test splitting with exclusive bounds
        $range = new IntRange(1, 10, '(', ')');
        $result = $range->split(5);
        $this->assertCount(2, $result);
        $this->assertEquals('(1,5)', (string)$result[0]);
        $this->assertEquals('[5,10)', (string)$result[1]);

        // Test splitting with mixed bounds
        $range = new IntRange(1, 10, '[', ')');
        $result = $range->split(5);
        $this->assertCount(2, $result);
        $this->assertEquals('[1,5)', (string)$result[0]);
        $this->assertEquals('[5,10)', (string)$result[1]);

        // Test splitting with null bounds
        $range = new IntRange(null, 10, '(', ']');
        $result = $range->split(0);
        $this->assertCount(2, $result);
        $this->assertEquals('(,0)', (string)$result[0]);
        $this->assertEquals('[0,10]', (string)$result[1]);

        $range = new IntRange(1, null, '[', ')');
        $result = $range->split(5);
        $this->assertCount(2, $result);
        $this->assertEquals('[1,5)', (string)$result[0]);
        $this->assertEquals('[5,)', (string)$result[1]);
    }

    public function testClone()
    {
        // Test cloning a regular range
        $range = new IntRange(1, 10, '[', ']');
        $clone = $range->clone();
        $this->assertTrue($range->equals($clone));
        $this->assertNotSame($range, $clone);

        // Test cloning a range with null bounds
        $range = new IntRange(null, 10, '(', ']');
        $clone = $range->clone();
        $this->assertTrue($range->equals($clone));
        $this->assertNotSame($range, $clone);

        $range = new IntRange(1, null, '[', ')');
        $clone = $range->clone();
        $this->assertTrue($range->equals($clone));
        $this->assertNotSame($range, $clone);

        $range = new IntRange(null, null, '(', ')');
        $clone = $range->clone();
        $this->assertTrue($range->equals($clone));
        $this->assertNotSame($range, $clone);

        // Test cloning a range with a step value
        $range = new IntRange(1, 10, '[', ']', 2);
        $clone = $range->clone();
        $this->assertTrue($range->equals($clone));
        $this->assertNotSame($range, $clone);
        $this->assertEquals(2, $clone->step);
    }

    public function testShift()
    {
        // Test shifting a regular range
        $range = new IntRange(1, 10, '[', ']');
        $shifted = $range->shift(5);
        $this->assertEquals('[6,15]', (string)$shifted);

        // Test shifting a range with negative offset
        $range = new IntRange(1, 10, '[', ']');
        $shifted = $range->shift(-5);
        $this->assertEquals('[-4,5]', (string)$shifted);

        // Test shifting a range with null lower bound
        $range = new IntRange(null, 10, '(', ']');
        $shifted = $range->shift(5);
        $this->assertEquals('(,15]', (string)$shifted);

        // Test shifting a range with null upper bound
        $range = new IntRange(1, null, '[', ')');
        $shifted = $range->shift(5);
        $this->assertEquals('[6,)', (string)$shifted);

        // Test shifting a range with both null bounds
        $range = new IntRange(null, null, '(', ')');
        $shifted = $range->shift(5);
        $this->assertEquals('(,)', (string)$shifted);

        // Test that original range is not modified
        $range = new IntRange(1, 10, '[', ']');
        $shifted = $range->shift(5);
        $this->assertEquals('[1,10]', (string)$range);

        // Test that bound types are preserved
        $range = new IntRange(1, 10, '(', ')');
        $shifted = $range->shift(5);
        $this->assertEquals('(6,15)', (string)$shifted);

        // Test that step is preserved
        $range = new IntRange(1, 10, '[', ']', 2);
        $shifted = $range->shift(5);
        $this->assertEquals(2, $shifted->step);
    }

    public function testScale()
    {
        // Test scaling a regular range with positive factor
        $range = new IntRange(1, 10, '[', ']');
        $scaled = $range->scale(2);
        $this->assertEquals('[2,20]', (string)$scaled);

        // Test scaling a range with negative factor
        $range = new IntRange(1, 10, '[', ']');
        $scaled = $range->scale(-2);
        $this->assertEquals('[-20,-2]', (string)$scaled);

        // Test scaling a range with null lower bound
        $range = new IntRange(null, 10, '(', ']');
        $scaled = $range->scale(2);
        $this->assertEquals('(,20]', (string)$scaled);

        // Test scaling a range with null upper bound
        $range = new IntRange(1, null, '[', ')');
        $scaled = $range->scale(2);
        $this->assertEquals('[2,)', (string)$scaled);

        // Test scaling a range with both null bounds
        $range = new IntRange(null, null, '(', ')');
        $scaled = $range->scale(2);
        $this->assertEquals('(,)', (string)$scaled);

        // Test that original range is not modified
        $range = new IntRange(1, 10, '[', ']');
        $scaled = $range->scale(2);
        $this->assertEquals('[1,10]', (string)$range);

        // Test that bound types are preserved with positive factor
        $range = new IntRange(1, 10, '(', ')');
        $scaled = $range->scale(2);
        $this->assertEquals('(2,20)', (string)$scaled);

        // Test that bound types are swapped with negative factor
        $range = new IntRange(1, 10, '(', ')');
        $scaled = $range->scale(-2);
        $this->assertEquals('(-20,-2)', (string)$scaled);

        $range = new IntRange(1, 10, '[', ']');
        $scaled = $range->scale(-2);
        $this->assertEquals('[-20,-2]', (string)$scaled);

        $range = new IntRange(1, 10, '[', ')');
        $scaled = $range->scale(-2);
        $this->assertEquals('(-20,-2]', (string)$scaled);

        $range = new IntRange(1, 10, '(', ']');
        $scaled = $range->scale(-2);
        $this->assertEquals('[-20,-2)', (string)$scaled);

        // Test that step is scaled
        $range = new IntRange(1, 10, '[', ']', 2);
        $scaled = $range->scale(3);
        $this->assertEquals(6, $scaled->step);

        $range = new IntRange(1, 10, '[', ']', 2);
        $scaled = $range->scale(-3);
        $this->assertEquals(6, $scaled->step);

        // Test with zero factor (should throw exception)
        $range = new IntRange(1, 10, '[', ']');
        $this->expectException(InvalidArgumentException::class);
        $range->scale(0);
    }
}
