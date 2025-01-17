<?php

namespace Ciloe\Ranges\Tests;

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
        $this->assertEquals('(', $range->getLowerBound());
        $this->assertEquals(')', $range->getUpperBound());

        $range = IntRange::fromString('(,13]');
        $this->assertNull($range->lower);
        $this->assertEquals(13, $range->upper);
        $this->assertEquals('(', $range->getLowerBound());
        $this->assertEquals(']', $range->getUpperBound());

        $range = IntRange::fromString('(null,13)');
        $this->assertNull($range->lower);
        $this->assertEquals(13, $range->upper);
        $this->assertEquals('(', $range->getLowerBound());
        $this->assertEquals(')', $range->getUpperBound());

        $range = IntRange::fromString('(null,13]');
        $this->assertNull($range->lower);
        $this->assertEquals(13, $range->upper);
        $this->assertEquals('(', $range->getLowerBound());
        $this->assertEquals(']', $range->getUpperBound());
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
    }
}