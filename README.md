# Ranges

This library provides classes for working with ranges of values.

## Requirements

- PHP 8.3 or higher
- The BCMath extension (`ext-bcmath`), used by `BigIntRange`

## Installation

```bash
composer require ciloe/ranges
```

## Available Range Types

### IntRange

The `IntRange` class allows you to represent and manipulate integer ranges. It offers a complete API for creating, comparing, and transforming ranges of integer numbers.

For detailed documentation on the IntRange class, see [IntRange Documentation](doc/IntRange.md).

### BigIntRange

The `BigIntRange` class allows you to represent and manipulate arbitrary precision integer ranges. It offers the same API as IntRange but works with string representations of integers, allowing for values beyond PHP's native integer limits (PHP_INT_MAX).

This class uses the BCMath extension for all operations, ensuring accurate calculations with very large integers.

For detailed documentation on the BigIntRange class, see [BigIntRange Documentation](doc/BigIntRange.md).

### DateRange

The `DateRange` class allows you to represent and manipulate date ranges. It offers a complete API for creating, comparing, and transforming ranges of dates using DateTimeImmutable objects.

This class supports custom step intervals (days, weeks, months, etc.) for generating date series and provides operations for date range manipulation.

For detailed documentation on the DateRange class, see [DateRange Documentation](doc/DateRange.md).

### TimeRange

The `TimeRange` class allows you to represent and manipulate time-of-day ranges. All comparisons only use the time part (hours, minutes, seconds) of the DateTimeImmutable objects — the date part is ignored.

This class supports custom step intervals (seconds, minutes, hours) for generating time series and provides the same operations as the other range types.

For detailed documentation on the TimeRange class, see [TimeRange Documentation](doc/TimeRange.md).

### RangeCollection

The `RangeCollection` class is an immutable collection of ranges of the same type and step. It normalizes overlapping ranges (`merge()`), finds the free slots between them (`gaps()`), and sums their lengths (`totalLength()`).

For detailed documentation on the RangeCollection class, see [RangeCollection Documentation](doc/RangeCollection.md).

## Available Operations

Every range type implements the same API:

- **Predicates** : `contains()`, `containsRange()`, `overlap()`, `isBefore()`, `isAfter()`, `isAdjacent()`, `isEmpty()`, `isBoundsValid()`, `equals()`
- **Set operations** : `union()`, `intersection()`, `difference()`, `gap()`
- **Values** : `length()`, `clamp()`, `random()`, `getLowerBoundValue()`, `getUpperBoundValue()`, `getStep()`
- **Iteration** : `generateSeries()`, `iterate()` (lazy Generator), `chunk()`
- **Transformations** : `split()`, `shift()`, `expand()`, `shrink()`, `scale()`, `clone()`, `__toString()` / `fromString()`

`DateRange` also provides the `fromMonth()`, `fromYear()` and `fromWeek()` factories.

## Quick Examples

### IntRange Example

```php
use Ciloe\Ranges\IntRange;

// Create a range [1, 10]
$range = new IntRange(1, 10, '[', ']');

// Check if a value is in the range
$range->contains(5); // true

// Generate a series of values in the range
$range->generateSeries(); // [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
```

### BigIntRange Example

```php
use Ciloe\Ranges\BigIntRange;

// Create a range with values beyond PHP_INT_MAX
// PHP_INT_MAX on 64-bit systems is 9223372036854775807
$range = new BigIntRange('9223372036854775808', '9223372036854775818', '[', ']');

// Check if a value is in the range
$range->contains('9223372036854775810'); // true

// Generate a series of values in the range
$series = $range->generateSeries(); // ['9223372036854775808', '9223372036854775809', ...]

// Perform operations with very large integers
$shifted = $range->shift('1000000000000000000');
// $shifted now represents [10223372036854775808, 10223372036854775818]

$scaled = $range->scale('2');
// $scaled now represents [18446744073709551616, 18446744073709551636]
```

### DateRange Example

```php
use Ciloe\Ranges\DateRange;
use DateTimeImmutable;
use DateInterval;

// Create a date range from 2023-01-01 to 2023-01-10
$range = new DateRange(
    new DateTimeImmutable('2023-01-01'),
    new DateTimeImmutable('2023-01-10'),
    '[',
    ']'
);

// Check if a date is in the range
$range->contains(new DateTimeImmutable('2023-01-05')); // true

// Generate a series of dates in the range (with default 1-day step)
$dates = $range->generateSeries(); // Array of DateTimeImmutable objects from 2023-01-01 to 2023-01-10

// Create a range with weekly steps
$weeklyRange = new DateRange(
    new DateTimeImmutable('2023-01-01'),
    new DateTimeImmutable('2023-01-31'),
    '[',
    ']',
    new DateInterval('P1W')
);
$weeklyDates = $weeklyRange->generateSeries(); // [2023-01-01, 2023-01-08, 2023-01-15, 2023-01-22, 2023-01-29]

// Shift a date range
$shifted = $range->shift(new DateInterval('P1M')); // [2023-02-01, 2023-02-10]
```

### TimeRange Example

```php
use Ciloe\Ranges\TimeRange;
use DateTimeImmutable;
use DateInterval;

$today = new DateTimeImmutable('today');

// Create a time range from 09:00:00 to 17:00:00
$range = new TimeRange(
    $today->setTime(9, 0, 0),
    $today->setTime(17, 0, 0),
    '[',
    ']'
);

// Check if a time is in the range (the date part is ignored)
$range->contains(new DateTimeImmutable('2030-06-15 12:30:00')); // true

// Generate a series of times with a 2-hour step
$range = new TimeRange(
    $today->setTime(9, 0, 0),
    $today->setTime(17, 0, 0),
    '[',
    ']',
    new DateInterval('PT2H')
);
$times = $range->generateSeries(); // [09:00:00, 11:00:00, 13:00:00, 15:00:00, 17:00:00]

// Or parse from a string
$range = TimeRange::fromString('[09:00:00,17:00:00]');
```

### RangeCollection Example

```php
use Ciloe\Ranges\DateRange;
use Ciloe\Ranges\RangeCollection;
use DateTimeImmutable;

$bookings = new RangeCollection(
    new DateRange(new DateTimeImmutable('2025-06-10'), new DateTimeImmutable('2025-06-15'), '[', ']'),
    new DateRange(new DateTimeImmutable('2025-06-01'), new DateTimeImmutable('2025-06-12'), '[', ']'),
    new DateRange(new DateTimeImmutable('2025-06-20'), new DateTimeImmutable('2025-06-25'), '[', ']'),
);

// Normalize into sorted, disjoint ranges
$bookings->merge()->getRanges(); // [2025-06-01,2025-06-15], [2025-06-20,2025-06-25]

// The free slots between the bookings
$bookings->gaps()->getRanges(); // [2025-06-16,2025-06-19]
```

## Exceptions

- `InvalidArgumentException` : Invalid range format, invalid value type, or unsupported operation
- `InvalidBoundException` : Invalid bounds (lower > upper)
- `InvalidInfiniteBoundException` : Infinite bound declared as inclusive
- `InvalidDateIntervalException` : Invalid DateRange interval (time components in a step or offset, zero or negative step)
- `InvalidTimeIntervalException` : Invalid TimeRange interval (date components in a step or offset, zero or negative step)
- `CantGenerateSeriesBecauseTheArrayIsTooLarge` : Series too large to be generated (e.g. infinite bounds)

## Notes

- Ranges can have infinite bounds (null)
- Bounds can be inclusive (`[`, `]`) or exclusive (`(`, `)`)
- An exclusive bound is shifted by one step: with a step of 5, `(0,20)` has effective bounds 5 and 15
- Infinite bounds must be exclusive: `fromString()` rejects `[,10]`, and `union()`/`intersection()` always produce exclusive infinite bounds
- The step drives `length()`, `generateSeries()`, and the effective value of exclusive bounds
- Operations between ranges take the step into account: `union()`/`intersection()` return null when the steps differ, `equals()` returns false
