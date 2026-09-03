# RangeCollection

The `RangeCollection` class is an immutable collection of ranges of the same type and step. It provides set-level operations that single ranges cannot express: normalizing overlapping ranges, finding the free slots between them, and summing their lengths.

## Basic Usage

```php
use Ciloe\Ranges\IntRange;
use Ciloe\Ranges\RangeCollection;

$collection = new RangeCollection(
    new IntRange(10, 12, '[', ']'),
    new IntRange(1, 5, '[', ']'),
    new IntRange(3, 8, '[', ']'),
);

// Normalize into sorted, disjoint, non-adjacent ranges
$collection->merge()->getRanges(); // [1,8], [10,12]

// The free slots between the merged ranges
$collection->gaps()->getRanges(); // [9,9]

// Membership across all ranges
$collection->contains(4);  // true
$collection->contains(9);  // false

// Total number of values, overlaps counted once
$collection->totalLength(); // 11
```

## Constructor

```php
new RangeCollection(RangeInterface ...$ranges);
```

All ranges must be of the same class (`IntRange`, `BigIntRange`, `DateRange`, or `TimeRange`) and share the same step; an `InvalidArgumentException` is thrown otherwise. The collection is immutable: every operation returns a new instance.

## Methods

- `add(RangeInterface $range)` : Returns a new collection with the range appended
- `getRanges()` : Returns the ranges as an array
- `count()` : Number of ranges in the collection
- `isEmpty()` : Checks if the collection holds no range
- `merge()` : Returns a new collection where overlapping and adjacent ranges are unioned, empty ranges dropped, and the result sorted
- `gaps()` : Returns the collection of ranges between the merged ranges (the "free slots")
- `contains(mixed $value)` : Checks if any range of the collection contains the value
- `totalLength()` : Sums the lengths of the merged ranges (`int` or numeric string depending on the range type; null if the collection is empty or a range is infinite)

## Example: Free Slots in a Schedule

```php
use Ciloe\Ranges\DateRange;
use Ciloe\Ranges\RangeCollection;
use DateTimeImmutable;

$bookings = new RangeCollection(
    new DateRange(new DateTimeImmutable('2025-06-10'), new DateTimeImmutable('2025-06-15'), '[', ']'),
    new DateRange(new DateTimeImmutable('2025-06-01'), new DateTimeImmutable('2025-06-12'), '[', ']'),
    new DateRange(new DateTimeImmutable('2025-06-20'), new DateTimeImmutable('2025-06-25'), '[', ']'),
);

$bookings->merge()->getRanges();
// [2025-06-01,2025-06-15] and [2025-06-20,2025-06-25]

$bookings->gaps()->getRanges();
// [2025-06-16,2025-06-19] — the free slot

$bookings->contains(new DateTimeImmutable('2025-06-17')); // false
```
