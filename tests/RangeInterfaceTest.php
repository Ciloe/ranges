<?php

declare(strict_types=1);

namespace Ciloe\Ranges\Tests;

use Ciloe\Ranges\BigIntRange;
use Ciloe\Ranges\IntRange;
use Ciloe\Ranges\RangeInterface;
use PHPUnit\Framework\TestCase;

class RangeInterfaceTest extends TestCase
{
    public function testIntRangeImplementsRangeInterface(): void
    {
        $range = new IntRange(1, 10);
        $this->assertInstanceOf(RangeInterface::class, $range);
    }

    public function testBigIntRangeImplementsRangeInterface(): void
    {
        $range = new BigIntRange('1', '10');
        $this->assertInstanceOf(RangeInterface::class, $range);
    }

    public function testRangeInterfaceCanBeUsedWithIntRange(): void
    {
        $range = $this->createIntRange(1, 10);
        $this->assertEquals(10, $range->length());
        $this->assertTrue($range->contains(5));
        $this->assertFalse($range->contains(11));
    }

    public function testRangeInterfaceCanBeUsedWithBigIntRange(): void
    {
        $range = $this->createBigIntRange('1', '10');
        $this->assertEquals('10', $range->length());
        $this->assertTrue($range->contains('5'));
        $this->assertFalse($range->contains('11'));
    }

    /**
     * @return RangeInterface<int>
     */
    private function createIntRange(int $lower, int $upper): RangeInterface
    {
        return new IntRange($lower, $upper, '[', ']');
    }

    /**
     * @return RangeInterface<string>
     */
    private function createBigIntRange(string $lower, string $upper): RangeInterface
    {
        return new BigIntRange($lower, $upper, '[', ']');
    }
}
