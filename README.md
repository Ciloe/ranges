# Ranges

This library provides classes for working with ranges of values.

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

## Exceptions

- `InvalidArgumentException` : Invalid range format
- `InvalidBoundException` : Invalid bounds (lower > upper)
- `InvalidInfiniteBoundException` : Infinite bound with inclusion
- `InvalidStepToGenerateSeriesException` : Invalid step for generating a series
- `CantGenerateSeriesBecauseTheArrayIsTooLarge` : Series too large to be generated

## Notes

- Ranges can have infinite bounds (null)
- Bounds can be inclusive (`[`, `]`) or exclusive (`(`, `)`)
- The step only affects series generation, not other operations
- Operations between ranges (union, intersection) require the same step
