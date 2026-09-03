<?php

declare(strict_types=1);

namespace Tests\Ciloe\Ranges;

use Ciloe\Ranges\BigIntRange;
use Ciloe\Ranges\DateRange;
use Ciloe\Ranges\IntRange;
use Ciloe\Ranges\RangeCollection;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RangeCollectionTest extends TestCase
{
    public function testConstructorRejectsMixedTypes()
    {
        $this->expectException(InvalidArgumentException::class);
        new RangeCollection(new IntRange(1, 5, '[', ']'), new BigIntRange('1', '5', '[', ']'));
    }

    public function testConstructorRejectsMixedSteps()
    {
        $this->expectException(InvalidArgumentException::class);
        new RangeCollection(new IntRange(1, 5, '[', ']', 1), new IntRange(10, 20, '[', ']', 2));
    }

    public function testBasics()
    {
        $collection = new RangeCollection();
        $this->assertTrue($collection->isEmpty());
        $this->assertEquals(0, $collection->count());

        $collection = new RangeCollection(new IntRange(1, 5, '[', ']'), new IntRange(10, 12, '[', ']'));
        $this->assertFalse($collection->isEmpty());
        $this->assertEquals(2, $collection->count());
        $this->assertCount(2, $collection->getRanges());
    }

    public function testAddIsImmutable()
    {
        $collection = new RangeCollection(new IntRange(1, 5, '[', ']'));
        $extended = $collection->add(new IntRange(10, 12, '[', ']'));

        $this->assertEquals(1, $collection->count());
        $this->assertEquals(2, $extended->count());
        $this->assertNotSame($collection, $extended);
    }

    public function testMerge()
    {
        // Overlapping and adjacent ranges collapse; disjoint ones stay apart, sorted
        $collection = new RangeCollection(
            new IntRange(10, 12, '[', ']'),
            new IntRange(1, 5, '[', ']'),
            new IntRange(3, 8, '[', ']'),
        );

        $merged = $collection->merge()->getRanges();
        $this->assertCount(2, $merged);
        $this->assertEquals('[1,8]', (string) $merged[0]);
        $this->assertEquals('[10,12]', (string) $merged[1]);

        // Adjacent ranges are merged too
        $collection = new RangeCollection(new IntRange(1, 5, '[', ']'), new IntRange(6, 10, '[', ']'));
        $merged = $collection->merge()->getRanges();
        $this->assertCount(1, $merged);
        $this->assertEquals('[1,10]', (string) $merged[0]);

        // A chain of overlaps collapses regardless of input order
        $collection = new RangeCollection(
            new IntRange(1, 10, '[', ']'),
            new IntRange(20, 30, '[', ']'),
            new IntRange(5, 25, '[', ']'),
        );
        $merged = $collection->merge()->getRanges();
        $this->assertCount(1, $merged);
        $this->assertEquals('[1,30]', (string) $merged[0]);
    }

    public function testMergeDropsEmptyRanges()
    {
        $collection = new RangeCollection(new IntRange(5, 5, '(', ')'), new IntRange(1, 3, '[', ']'));
        $merged = $collection->merge()->getRanges();

        $this->assertCount(1, $merged);
        $this->assertEquals('[1,3]', (string) $merged[0]);
    }

    public function testGaps()
    {
        $collection = new RangeCollection(
            new IntRange(20, 22, '[', ']'),
            new IntRange(1, 5, '[', ']'),
            new IntRange(10, 12, '[', ']'),
        );

        $gaps = $collection->gaps()->getRanges();
        $this->assertCount(2, $gaps);
        $this->assertEquals('[6,9]', (string) $gaps[0]);
        $this->assertEquals('[13,19]', (string) $gaps[1]);

        // A fully merged collection has no gap
        $collection = new RangeCollection(new IntRange(1, 5, '[', ']'), new IntRange(3, 10, '[', ']'));
        $this->assertTrue($collection->gaps()->isEmpty());
    }

    public function testContains()
    {
        $collection = new RangeCollection(new IntRange(1, 5, '[', ']'), new IntRange(10, 12, '[', ']'));

        $this->assertTrue($collection->contains(4));
        $this->assertTrue($collection->contains(11));
        $this->assertFalse($collection->contains(7));
        $this->assertFalse((new RangeCollection())->contains(4));
    }

    public function testTotalLength()
    {
        $collection = new RangeCollection(new IntRange(1, 5, '[', ']'), new IntRange(10, 12, '[', ']'));
        $this->assertEquals(8, $collection->totalLength());

        // Overlaps are only counted once
        $collection = new RangeCollection(new IntRange(1, 5, '[', ']'), new IntRange(3, 8, '[', ']'));
        $this->assertEquals(8, $collection->totalLength());

        // An infinite range makes the total unknown
        $collection = new RangeCollection(new IntRange(1, 5, '[', ']'), new IntRange(10, null, '[', ')'));
        $this->assertNull($collection->totalLength());

        $this->assertNull((new RangeCollection())->totalLength());

        // BigIntRange sums as numeric strings
        $collection = new RangeCollection(
            new BigIntRange('9223372036854775808', '9223372036854775810', '[', ']'),
            new BigIntRange('1', '5', '[', ']'),
        );
        $this->assertSame('8', $collection->totalLength());
    }

    public function testWorksWithDateRanges()
    {
        $collection = new RangeCollection(
            new DateRange(new DateTimeImmutable('2025-06-10'), new DateTimeImmutable('2025-06-15'), '[', ']'),
            new DateRange(new DateTimeImmutable('2025-06-01'), new DateTimeImmutable('2025-06-12'), '[', ']'),
            new DateRange(new DateTimeImmutable('2025-06-20'), new DateTimeImmutable('2025-06-25'), '[', ']'),
        );

        $merged = $collection->merge()->getRanges();
        $this->assertCount(2, $merged);
        $this->assertEquals('[2025-06-01,2025-06-15]', (string) $merged[0]);
        $this->assertEquals('[2025-06-20,2025-06-25]', (string) $merged[1]);

        $gaps = $collection->gaps()->getRanges();
        $this->assertCount(1, $gaps);
        $this->assertEquals('[2025-06-16,2025-06-19]', (string) $gaps[0]);

        $this->assertTrue($collection->contains(new DateTimeImmutable('2025-06-22')));
        $this->assertFalse($collection->contains(new DateTimeImmutable('2025-06-17')));
    }
}
