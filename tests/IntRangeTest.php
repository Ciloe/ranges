<?php

declare(strict_types=1);

namespace Tests\Ciloe\Ranges;

use Ciloe\Ranges\Exception\CantGenerateSeriesBecauseTheArrayIsTooLarge;
use Ciloe\Ranges\Exception\InvalidBoundException;
use Ciloe\Ranges\Exception\InvalidInfiniteBoundException;
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
        try {
            IntRange::fromString('invalid');
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true);
        }

        try {
            IntRange::fromString('[1;2)');
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true);
        }

        try {
            IntRange::fromString('(1,2');
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true);
        }

        try {
            IntRange::fromString('1,2)');
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true);
        }

        try {
            IntRange::fromString('[,2)');
            $this->fail('Expected InvalidInfiniteBoundException was not thrown');
        } catch (InvalidInfiniteBoundException $e) {
            $this->assertTrue(true);
        }

        try {
            IntRange::fromString('(2,]');
            $this->fail('Expected InvalidInfiniteBoundException was not thrown');
        } catch (InvalidInfiniteBoundException $e) {
            $this->assertTrue(true);
        }

        try {
            IntRange::fromString('[,]');
            $this->fail('Expected InvalidInfiniteBoundException was not thrown');
        } catch (InvalidInfiniteBoundException $e) {
            $this->assertTrue(true);
        }

        try {
            IntRange::fromString('[3,1)');
            $this->fail('Expected InvalidBoundException was not thrown');
        } catch (InvalidBoundException $e) {
            $this->assertTrue(true);
        }

        try {
            IntRange::fromString('(5,3]');
            $this->fail('Expected InvalidBoundException was not thrown');
        } catch (InvalidBoundException $e) {
            $this->assertTrue(true);
        }

        try {
            IntRange::fromString('(3,2)');
            $this->fail('Expected InvalidBoundException was not thrown');
        } catch (InvalidBoundException $e) {
            $this->assertTrue(true);
        }

        try {
            IntRange::fromString('(abc,xyz)');
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true);
        }

        try {
            IntRange::fromString('(1.5,5.5)');
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true);
        }

        try {
            IntRange::fromString('(5,5)');
            $this->fail('Expected InvalidBoundException was not thrown');
        } catch (InvalidBoundException $e) {
            $this->assertTrue(true);
        }
    }

    public function testExclusiveBoundsAreShiftedByStep()
    {
        $range = new IntRange(4, 20, '(', ']', 5);
        $this->assertEquals(9, $range->getLowerBoundValue());
        $this->assertEquals(20, $range->getUpperBoundValue());

        $range = new IntRange(0, 20, '[', ')', 5);
        $this->assertEquals(0, $range->getLowerBoundValue());
        $this->assertEquals(15, $range->getUpperBoundValue());

        $range = new IntRange(0, 20, '(', ')', 5);
        $this->assertEquals(5, $range->getLowerBoundValue());
        $this->assertEquals(15, $range->getUpperBoundValue());
        $this->assertEquals([5, 10, 15], $range->generateSeries());
    }

    public function testContainsWithInclusiveBounds()
    {
        $range = new IntRange(1, 10, '[', ']');
        $this->assertTrue($range->contains(1));
        $this->assertTrue($range->contains(10));
        $this->assertTrue($range->contains(5));
        $this->assertFalse($range->contains(0));
        $this->assertFalse($range->contains(11));

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

        $range = new IntRange(null, null, '[', ']');
        $this->assertTrue($range->contains(-1000000));
        $this->assertTrue($range->contains(0));
        $this->assertTrue($range->contains(1000000));

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

        $range1 = new IntRange(null, null, '[', ']');
        $range2 = new IntRange(-100, 100, '[', ']');
        $this->assertTrue($range1->overlap($range2));

        $range1 = new IntRange(null, null, '(', ')');
        $range2 = new IntRange(null, null, '[', ']');
        $this->assertTrue($range1->overlap($range2));

        $range1 = new IntRange(5, 5, '(', ')');
        $range2 = new IntRange(null, null, '(', ')');
        $this->assertFalse($range1->overlap($range2));
    }

    public function testContainsRange()
    {
        $range = new IntRange(1, 10, '[', ']');

        $this->assertTrue($range->containsRange(new IntRange(3, 5, '[', ']')));
        $this->assertTrue($range->containsRange(new IntRange(1, 10, '[', ']')));
        $this->assertFalse($range->containsRange(new IntRange(5, 15, '[', ']')));
        $this->assertFalse($range->containsRange(new IntRange(0, 5, '[', ']')));
        $this->assertFalse($range->containsRange(new IntRange(null, 5, '(', ']')));
        $this->assertFalse($range->containsRange(new IntRange(5, null, '[', ')')));

        // An empty range is contained in any range
        $this->assertTrue($range->containsRange(new IntRange(5, 5, '(', ')')));

        // An empty range contains nothing but an empty range
        $empty = new IntRange(5, 5, '(', ')');
        $this->assertFalse($empty->containsRange($range));
        $this->assertTrue($empty->containsRange(new IntRange(3, 3, '(', ')')));

        // Infinite ranges contain finite ones
        $unbounded = new IntRange(null, 10, '(', ']');
        $this->assertTrue($unbounded->containsRange(new IntRange(1, 5, '[', ']')));
        $this->assertTrue($unbounded->containsRange(new IntRange(null, 5, '(', ']')));
        $this->assertFalse($unbounded->containsRange(new IntRange(5, 15, '[', ']')));

        $all = new IntRange(null, null, '(', ')');
        $this->assertTrue($all->containsRange($range));
        $this->assertTrue($all->containsRange($unbounded));
    }

    public function testIsBeforeAndIsAfter()
    {
        $range1 = new IntRange(1, 5, '[', ']');
        $range2 = new IntRange(10, 20, '[', ']');

        $this->assertTrue($range1->isBefore($range2));
        $this->assertFalse($range2->isBefore($range1));
        $this->assertTrue($range2->isAfter($range1));
        $this->assertFalse($range1->isAfter($range2));

        // Overlapping ranges are neither before nor after
        $range3 = new IntRange(4, 12, '[', ']');
        $this->assertFalse($range1->isBefore($range3));
        $this->assertFalse($range1->isAfter($range3));
        $this->assertFalse($range3->isBefore($range1));

        // Touching ranges are not strictly before
        $range4 = new IntRange(5, 10, '[', ']');
        $this->assertFalse($range1->isBefore($range4));

        // Infinite bounds
        $this->assertFalse((new IntRange(null, 20, '(', ']'))->isBefore($range2));
        $this->assertTrue((new IntRange(null, 5, '(', ']'))->isBefore($range2));
        $this->assertFalse((new IntRange(1, null, '[', ')'))->isBefore($range2));

        // Empty ranges are never before nor after
        $empty = new IntRange(3, 3, '(', ')');
        $this->assertFalse($empty->isBefore($range2));
        $this->assertFalse($range2->isAfter($empty));
    }

    public function testIsAdjacent()
    {
        $range1 = new IntRange(1, 5, '[', ']');

        $this->assertTrue($range1->isAdjacent(new IntRange(6, 10, '[', ']')));
        $this->assertTrue((new IntRange(6, 10, '[', ']'))->isAdjacent($range1));
        $this->assertFalse($range1->isAdjacent(new IntRange(7, 10, '[', ']')));
        $this->assertFalse($range1->isAdjacent(new IntRange(5, 10, '[', ']')));

        // Adjacency follows the step
        $range = new IntRange(0, 10, '[', ']', 5);
        $this->assertTrue($range->isAdjacent(new IntRange(15, 20, '[', ']', 5)));
        $this->assertFalse($range->isAdjacent(new IntRange(11, 20, '[', ']', 5)));

        // Different steps are never adjacent
        $this->assertFalse($range1->isAdjacent(new IntRange(6, 10, '[', ']', 2)));

        // Empty ranges are never adjacent
        $empty = new IntRange(3, 3, '(', ')');
        $this->assertFalse($empty->isAdjacent($range1));
        $this->assertFalse($range1->isAdjacent($empty));

        // Infinite touching sides are never adjacent
        $this->assertFalse((new IntRange(null, 5, '(', ']'))->isAdjacent(new IntRange(null, 10, '(', ']')));
    }

    public function testDifference()
    {
        $range = new IntRange(1, 10, '[', ']');

        // No overlap: the original range is returned
        $result = $range->difference(new IntRange(15, 20, '[', ']'));
        $this->assertCount(1, $result);
        $this->assertEquals('[1,10]', (string) $result[0]);

        // Subtracted range covers the left part
        $result = $range->difference(new IntRange(1, 5, '[', ']'));
        $this->assertCount(1, $result);
        $this->assertEquals('[6,10]', (string) $result[0]);

        // Subtracted range covers the right part
        $result = $range->difference(new IntRange(8, 15, '[', ']'));
        $this->assertCount(1, $result);
        $this->assertEquals('[1,7]', (string) $result[0]);

        // Subtracted range in the middle: two parts remain
        $result = $range->difference(new IntRange(4, 6, '[', ']'));
        $this->assertCount(2, $result);
        $this->assertEquals('[1,3]', (string) $result[0]);
        $this->assertEquals('[7,10]', (string) $result[1]);

        // Subtracted range covers everything
        $this->assertCount(0, $range->difference(new IntRange(0, 15, '[', ']')));

        // Different steps: null
        $this->assertNull($range->difference(new IntRange(4, 6, '[', ']', 2)));

        // Null bounds
        $result = (new IntRange(null, 10, '(', ']'))->difference(new IntRange(5, 15, '[', ']'));
        $this->assertCount(1, $result);
        $this->assertEquals('(,4]', (string) $result[0]);

        $result = $range->difference(new IntRange(null, 5, '(', ']'));
        $this->assertCount(1, $result);
        $this->assertEquals('[6,10]', (string) $result[0]);

        // The step is preserved
        $result = (new IntRange(0, 20, '[', ']', 2))->difference(new IntRange(10, 14, '[', ']', 2));
        $this->assertCount(2, $result);
        $this->assertEquals(2, $result[0]->getStep());
        $this->assertEquals('[0,8]', (string) $result[0]);
        $this->assertEquals('[16,20]', (string) $result[1]);
    }

    public function testGap()
    {
        $range1 = new IntRange(1, 5, '[', ']');
        $range2 = new IntRange(10, 20, '[', ']');

        $gap = $range1->gap($range2);
        $this->assertNotNull($gap);
        $this->assertEquals('[6,9]', (string) $gap);

        // Symmetric
        $this->assertEquals('[6,9]', (string) $range2->gap($range1));

        // Overlapping ranges have no gap
        $this->assertNull($range1->gap(new IntRange(3, 8, '[', ']')));

        // Adjacent ranges have no gap
        $this->assertNull($range1->gap(new IntRange(6, 10, '[', ']')));

        // Single-value gap
        $gap = $range1->gap(new IntRange(7, 10, '[', ']'));
        $this->assertEquals('[6,6]', (string) $gap);

        // Different steps: null
        $this->assertNull($range1->gap(new IntRange(10, 20, '[', ']', 2)));

        // The step is preserved and drives the gap bounds
        $gap = (new IntRange(0, 5, '[', ']', 5))->gap(new IntRange(20, 25, '[', ']', 5));
        $this->assertEquals('[10,15]', (string) $gap);
        $this->assertEquals(5, $gap->getStep());
    }

    public function testIsEmptyWithEmptyRange()
    {
        $range = new IntRange(5, 5, '(', ')');
        $this->assertTrue($range->isEmpty());

        $range = new IntRange(0, 0, '(', ')');
        $this->assertTrue($range->isEmpty());

        $range = new IntRange(-5, -5, '(', ')');
        $this->assertTrue($range->isEmpty());
    }

    public function testIsEmptyWithNonEmptyRange()
    {
        $range = new IntRange(5, 10, '[', ']');
        $this->assertFalse($range->isEmpty());

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

        $range = new IntRange(0, 0, '[', ']');
        $this->assertFalse($range->isEmpty());

        $range = new IntRange(-5, -5, '[', ']');
        $this->assertFalse($range->isEmpty());
    }

    public function testIsEmpty()
    {
        $range = new IntRange(5, 5, '(', ']');
        $this->assertFalse($range->isEmpty());

        $range = new IntRange(5, 5, '[', ')');
        $this->assertFalse($range->isEmpty());

        $range = new IntRange(5, 5, '[', ']');
        $this->assertFalse($range->isEmpty());

        $range = new IntRange(5, 5, '(', ')');
        $this->assertTrue($range->isEmpty());
    }

    public function testIsEmptyWithNullBounds()
    {
        $range = new IntRange(null, 5, '(', ']');
        $this->assertFalse($range->isEmpty());

        $range = new IntRange(5, null, '[', ')');
        $this->assertFalse($range->isEmpty());

        $range = new IntRange(null, null, '(', ')');
        $this->assertFalse($range->isEmpty());
    }

    public function testIsBoundsValidWithValidBounds()
    {
        $range = new IntRange(5, 10, '[', ']');
        $this->assertTrue($range->isBoundsValid());

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

    public function testUnionPreservesStep()
    {
        $range1 = new IntRange(0, 10, '[', ']', 2);
        $range2 = new IntRange(5, 15, '[', ']', 2);
        $result = $range1->union($range2);

        $this->assertNotNull($result);
        $this->assertEquals(2, $result->getStep());
    }

    public function testUnionWithDifferentStep()
    {
        $range1 = new IntRange(5, 10, '[', ']', 1);
        $range2 = new IntRange(8, 15, '[', ']', 2);
        $result = $range1->union($range2);

        $this->assertNull($result);

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
        $range1 = new IntRange(null, 10, '(', ']');
        $range2 = new IntRange(5, 15, '[', ']');
        $result = $range1->union($range2);

        $this->assertNotNull($result);
        $this->assertNull($result->lower);
        $this->assertEquals(15, $result->getUpperBoundValue());

        $range1 = new IntRange(5, null, '[', ')');
        $range2 = new IntRange(0, 10, '[', ']');
        $result = $range1->union($range2);

        $this->assertNotNull($result);
        $this->assertEquals(0, $result->getLowerBoundValue());
        $this->assertNull($result->upper);

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

    public function testIntersectionPreservesStep()
    {
        $range1 = new IntRange(0, 10, '[', ']', 2);
        $range2 = new IntRange(5, 15, '[', ']', 2);
        $result = $range1->intersection($range2);

        $this->assertNotNull($result);
        $this->assertEquals(2, $result->getStep());
    }

    public function testIntersectionWithDifferentStep()
    {
        $range1 = new IntRange(5, 15, '[', ']', 1);
        $range2 = new IntRange(10, 20, '[', ']', 2);
        $result = $range1->intersection($range2);

        $this->assertNull($result);

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

    public function testUnionAndIntersectionWithNullBoundsRoundTripThroughFromString()
    {
        $union = (new IntRange(null, 10, '(', ']'))->union(new IntRange(5, 15, '[', ']'));
        $this->assertEquals('(,15]', (string) $union);
        $this->assertTrue(IntRange::fromString((string) $union)->equals($union));

        $union = (new IntRange(null, 10, '(', ']'))->union(new IntRange(5, null, '[', ')'));
        $this->assertEquals('(,)', (string) $union);
        $this->assertTrue(IntRange::fromString((string) $union)->equals($union));

        $intersection = (new IntRange(null, 10, '(', ']'))->intersection(new IntRange(null, 5, '(', ']'));
        $this->assertEquals('(,5]', (string) $intersection);
        $this->assertTrue(IntRange::fromString((string) $intersection)->equals($intersection));
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

        $range = new IntRange(null, 0, '(', ']', 3);
        $this->assertNull($range->length());

        $range = new IntRange(null, -10, '(', ']', 5);
        $this->assertNull($range->length());
    }

    public function testLengthWithNullUpperBound()
    {
        $range = new IntRange(5, null, '[', ')', 2);
        $this->assertNull($range->length());

        $range = new IntRange(0, null, '[', ')', 3);
        $this->assertNull($range->length());

        $range = new IntRange(-10, null, '[', ')', 4);
        $this->assertNull($range->length());
    }

    public function testLengthWithNullBounds()
    {
        $range = new IntRange(null, null, '(', ')', 2);
        $this->assertNull($range->length());

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

        $range = new IntRange(0, 10, '[', ']', 2);
        $this->assertEquals([0, 2, 4, 6, 8, 10], $range->generateSeries());

        $range = new IntRange(-10, 0, '[', ']', 2);
        $this->assertEquals([-10, -8, -6, -4, -2, 0], $range->generateSeries());

        $range = new IntRange(-5, 5, '[', ']', 2);
        $this->assertEquals([-5, -3, -1, 1, 3, 5], $range->generateSeries());

        $range = new IntRange(1, 10, '(', ')', 2);
        $this->assertEquals([3, 5, 7], $range->generateSeries());

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
        // Consistent with length(): the series contains at least the lower bound
        $range = new IntRange(1, 5, '[', ']', 10);
        $this->assertEquals([1], $range->generateSeries());
        $this->assertEquals(1, $range->length());
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

    public function testClamp()
    {
        $range = new IntRange(1, 10, '[', ']');
        $this->assertEquals(1, $range->clamp(0));
        $this->assertEquals(5, $range->clamp(5));
        $this->assertEquals(10, $range->clamp(15));

        // Effective bounds are used
        $range = new IntRange(1, 10, '(', ')');
        $this->assertEquals(2, $range->clamp(1));
        $this->assertEquals(9, $range->clamp(10));

        // Infinite bounds never clamp on their side
        $range = new IntRange(null, 10, '(', ']');
        $this->assertEquals(-999999, $range->clamp(-999999));
        $this->assertEquals(10, $range->clamp(20));
    }

    public function testClampWithEmptyRange()
    {
        $range = new IntRange(5, 5, '(', ')');
        $this->expectException(InvalidArgumentException::class);
        $range->clamp(5);
    }

    public function testExpandAndShrink()
    {
        $range = new IntRange(5, 10, '[', ']');
        $this->assertEquals('[3,12]', (string) $range->expand(2));
        $this->assertEquals('[7,8]', (string) $range->shrink(2));

        // Bound types and step are preserved
        $range = new IntRange(5, 10, '(', ')', 2);
        $expanded = $range->expand(2);
        $this->assertEquals('(3,12)', (string) $expanded);
        $this->assertEquals(2, $expanded->getStep());

        // Infinite bounds are left untouched
        $range = new IntRange(null, 10, '(', ']');
        $this->assertEquals('(,12]', (string) $range->expand(2));
        $this->assertEquals('(,8]', (string) $range->shrink(2));
    }

    public function testShrinkBeyondBoundsThrows()
    {
        $range = new IntRange(5, 7, '[', ']');
        $this->expectException(InvalidBoundException::class);
        $range->shrink(2);
    }

    public function testExpandWithNegativeAmountThrows()
    {
        $range = new IntRange(5, 10, '[', ']');
        $this->expectException(InvalidArgumentException::class);
        $range->expand(-2);
    }

    public function testRandom()
    {
        $range = new IntRange(1, 10, '[', ']', 3);
        $series = $range->generateSeries();

        for ($i = 0; $i < 10; $i++) {
            $this->assertContains($range->random(), $series);
        }

        $range = new IntRange(5, 5, '[', ']');
        $this->assertEquals(5, $range->random());
    }

    public function testRandomWithInfiniteBoundThrows()
    {
        $range = new IntRange(null, 10, '(', ']');
        $this->expectException(InvalidArgumentException::class);
        $range->random();
    }

    public function testIterate()
    {
        $range = new IntRange(1, 5, '[', ']', 2);
        $this->assertEquals([1, 3, 5], iterator_to_array($range->iterate(), false));

        $range = new IntRange(5, 5, '(', ')');
        $this->assertEquals([], iterator_to_array($range->iterate(), false));

        // Lazy: a huge range can be partially consumed without materializing it
        $range = new IntRange(1, 1000000000, '[', ']');
        $values = [];
        foreach ($range->iterate() as $value) {
            $values[] = $value;
            if (count($values) === 3) {
                break;
            }
        }
        $this->assertEquals([1, 2, 3], $values);

        // An infinite upper bound yields values lazily too
        $range = new IntRange(5, null, '[', ')');
        $values = [];
        foreach ($range->iterate() as $value) {
            $values[] = $value;
            if (count($values) === 2) {
                break;
            }
        }
        $this->assertEquals([5, 6], $values);
    }

    public function testIterateWithInfiniteLowerBoundThrows()
    {
        $range = new IntRange(null, 10, '(', ']');
        $this->expectException(InvalidArgumentException::class);
        $range->iterate();
    }

    public function testChunk()
    {
        $range = new IntRange(1, 100, '[', ']');
        $chunks = $range->chunk(30);
        $this->assertCount(4, $chunks);
        $this->assertEquals('[1,30]', (string) $chunks[0]);
        $this->assertEquals('[31,60]', (string) $chunks[1]);
        $this->assertEquals('[61,90]', (string) $chunks[2]);
        $this->assertEquals('[91,100]', (string) $chunks[3]);

        // The step drives the chunk boundaries and is preserved
        $range = new IntRange(1, 10, '[', ']', 2);
        $chunks = $range->chunk(3);
        $this->assertCount(2, $chunks);
        $this->assertEquals('[1,5]', (string) $chunks[0]);
        $this->assertEquals('[7,10]', (string) $chunks[1]);
        $this->assertEquals(2, $chunks[0]->getStep());

        // An empty range yields no chunk
        $range = new IntRange(5, 5, '(', ')');
        $this->assertEquals([], $range->chunk(10));
    }

    public function testChunkWithInvalidCountThrows()
    {
        $range = new IntRange(1, 10, '[', ']');
        $this->expectException(InvalidArgumentException::class);
        $range->chunk(0);
    }

    public function testChunkWithInfiniteBoundThrows()
    {
        $range = new IntRange(1, null, '[', ')');
        $this->expectException(InvalidArgumentException::class);
        $range->chunk(10);
    }

    public function testToString()
    {
        $range = new IntRange(1, 10, '[', ']');
        $this->assertEquals('[1,10]', (string) $range);

        $range = new IntRange(1, 10, '(', ')');
        $this->assertEquals('(1,10)', (string) $range);

        $range = new IntRange(1, 10, '[', ')');
        $this->assertEquals('[1,10)', (string) $range);

        $range = new IntRange(1, 10, '(', ']');
        $this->assertEquals('(1,10]', (string) $range);

        $range = new IntRange(null, 10, '(', ']');
        $this->assertEquals('(,10]', (string) $range);

        $range = new IntRange(1, null, '[', ')');
        $this->assertEquals('[1,)', (string) $range);

        $range = new IntRange(null, null, '(', ')');
        $this->assertEquals('(,)', (string) $range);

        $range = new IntRange(-10, -1, '[', ']');
        $this->assertEquals('[-10,-1]', (string) $range);

        $range = new IntRange(1, 10, '[', ']', 2);
        $this->assertEquals('[1,10]', (string) $range);
    }

    public function testEquals()
    {
        $range1 = new IntRange(1, 10, '[', ']');
        $range2 = new IntRange(1, 10, '[', ']');
        $this->assertTrue($range1->equals($range2));

        $range1 = new IntRange(1, 10, '[', ']');
        $range2 = new IntRange(2, 10, '[', ']');
        $this->assertFalse($range1->equals($range2));

        $range1 = new IntRange(1, 10, '(', ']');
        $range2 = new IntRange(2, 11, '[', ')');
        $this->assertTrue($range1->equals($range2));

        $range1 = new IntRange(1, 10, '[', ']');
        $range2 = new IntRange(1, 11, '[', ']');
        $this->assertFalse($range1->equals($range2));

        $range1 = new IntRange(1, 10, '[', ']');
        $range2 = new IntRange(1, 10, '(', ']');
        $this->assertFalse($range1->equals($range2));

        $range1 = new IntRange(1, 10, '[', ']');
        $range2 = new IntRange(1, 10, '[', ')');
        $this->assertFalse($range1->equals($range2));

        $range1 = new IntRange(1, 10, '[', ']', 1);
        $range2 = new IntRange(1, 10, '[', ']', 2);
        $this->assertFalse($range1->equals($range2));

        $range1 = new IntRange(null, 10, '(', ']');
        $range2 = new IntRange(null, 10, '(', ']');
        $this->assertTrue($range1->equals($range2));

        $range1 = new IntRange(1, null, '[', ')');
        $range2 = new IntRange(1, null, '[', ')');
        $this->assertTrue($range1->equals($range2));

        $range1 = new IntRange(null, null, '(', ')');
        $range2 = new IntRange(null, null, '(', ')');
        $this->assertTrue($range1->equals($range2));

        $range1 = new IntRange(null, 10, '(', ']');
        $range2 = new IntRange(1, 10, '[', ']');
        $this->assertFalse($range1->equals($range2));
    }

    public function testSplit()
    {
        $range = new IntRange(1, 10, '[', ']');
        $result = $range->split(5);
        $this->assertCount(2, $result);
        $this->assertEquals('[1,5)', (string) $result[0]);
        $this->assertEquals('[5,10]', (string) $result[1]);

        $range = new IntRange(1, 10, '[', ']');
        $result = $range->split(1);
        $this->assertCount(2, $result);
        $this->assertEquals('[1,1)', (string) $result[0]);
        $this->assertEquals('[1,10]', (string) $result[1]);

        $range = new IntRange(1, 10, '[', ']');
        $result = $range->split(10);
        $this->assertCount(2, $result);
        $this->assertEquals('[1,10)', (string) $result[0]);
        $this->assertEquals('[10,10]', (string) $result[1]);

        $range = new IntRange(1, 10, '[', ']');
        $result = $range->split(0);
        $this->assertCount(1, $result);
        $this->assertEquals('[1,10]', (string) $result[0]);

        $range = new IntRange(1, 10, '[', ']');
        $result = $range->split(11);
        $this->assertCount(1, $result);
        $this->assertEquals('[1,10]', (string) $result[0]);

        $range = new IntRange(1, 10, '(', ')');
        $result = $range->split(5);
        $this->assertCount(2, $result);
        $this->assertEquals('(1,5)', (string) $result[0]);
        $this->assertEquals('[5,10)', (string) $result[1]);

        $range = new IntRange(1, 10, '[', ')');
        $result = $range->split(5);
        $this->assertCount(2, $result);
        $this->assertEquals('[1,5)', (string) $result[0]);
        $this->assertEquals('[5,10)', (string) $result[1]);

        $range = new IntRange(null, 10, '(', ']');
        $result = $range->split(0);
        $this->assertCount(2, $result);
        $this->assertEquals('(,0)', (string) $result[0]);
        $this->assertEquals('[0,10]', (string) $result[1]);

        $range = new IntRange(1, null, '[', ')');
        $result = $range->split(5);
        $this->assertCount(2, $result);
        $this->assertEquals('[1,5)', (string) $result[0]);
        $this->assertEquals('[5,)', (string) $result[1]);
    }

    public function testClone()
    {
        $range = new IntRange(1, 10, '[', ']');
        $clone = $range->clone();
        $this->assertTrue($range->equals($clone));
        $this->assertNotSame($range, $clone);

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

        $range = new IntRange(1, 10, '[', ']', 2);
        $clone = $range->clone();
        $this->assertTrue($range->equals($clone));
        $this->assertNotSame($range, $clone);
        $this->assertEquals(2, $clone->step);
    }

    public function testShift()
    {
        $range = new IntRange(1, 10, '[', ']');
        $shifted = $range->shift(5);
        $this->assertEquals('[6,15]', (string) $shifted);

        $range = new IntRange(1, 10, '[', ']');
        $shifted = $range->shift(-5);
        $this->assertEquals('[-4,5]', (string) $shifted);

        $range = new IntRange(null, 10, '(', ']');
        $shifted = $range->shift(5);
        $this->assertEquals('(,15]', (string) $shifted);

        $range = new IntRange(1, null, '[', ')');
        $shifted = $range->shift(5);
        $this->assertEquals('[6,)', (string) $shifted);

        $range = new IntRange(null, null, '(', ')');
        $shifted = $range->shift(5);
        $this->assertEquals('(,)', (string) $shifted);

        $range = new IntRange(1, 10, '[', ']');
        $shifted = $range->shift(5);
        $this->assertEquals('[1,10]', (string) $range);

        $range = new IntRange(1, 10, '(', ')');
        $shifted = $range->shift(5);
        $this->assertEquals('(6,15)', (string) $shifted);

        $range = new IntRange(1, 10, '[', ']', 2);
        $shifted = $range->shift(5);
        $this->assertEquals(2, $shifted->step);
    }

    public function testScale()
    {
        $range = new IntRange(1, 10, '[', ']');
        $scaled = $range->scale(2);
        $this->assertEquals('[2,20]', (string) $scaled);

        $range = new IntRange(1, 10, '[', ']');
        $scaled = $range->scale(-2);
        $this->assertEquals('[-20,-2]', (string) $scaled);

        $range = new IntRange(null, 10, '(', ']');
        $scaled = $range->scale(2);
        $this->assertEquals('(,20]', (string) $scaled);

        $range = new IntRange(1, null, '[', ')');
        $scaled = $range->scale(2);
        $this->assertEquals('[2,)', (string) $scaled);

        $range = new IntRange(null, null, '(', ')');
        $scaled = $range->scale(2);
        $this->assertEquals('(,)', (string) $scaled);

        $range = new IntRange(1, 10, '[', ']');
        $scaled = $range->scale(2);
        $this->assertEquals('[2,20]', (string) $scaled);

        $range = new IntRange(1, 10, '(', ')');
        $scaled = $range->scale(2);
        $this->assertEquals('(2,20)', (string) $scaled);

        $range = new IntRange(1, 10, '(', ')');
        $scaled = $range->scale(-2);
        $this->assertEquals('(-20,-2)', (string) $scaled);

        $range = new IntRange(1, 10, '[', ']');
        $scaled = $range->scale(-2);
        $this->assertEquals('[-20,-2]', (string) $scaled);

        $range = new IntRange(1, 10, '[', ')');
        $scaled = $range->scale(-2);
        $this->assertEquals('(-20,-2]', (string) $scaled);

        $range = new IntRange(1, 10, '(', ']');
        $scaled = $range->scale(-2);
        $this->assertEquals('[-20,-2)', (string) $scaled);

        $range = new IntRange(1, 10, '[', ']', 2);
        $scaled = $range->scale(3);
        $this->assertEquals(6, $scaled->step);

        $range = new IntRange(1, 10, '[', ']', 2);
        $scaled = $range->scale(-3);
        $this->assertEquals(6, $scaled->step);

        $range = new IntRange(1, 10, '[', ']');
        $this->expectException(InvalidArgumentException::class);
        $range->scale(0);
    }
}
