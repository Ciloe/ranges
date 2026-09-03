# DateRange

The `DateRange` class allows you to represent and manipulate date ranges. It offers a complete API for creating, comparing, and transforming ranges of dates.

## Basic Usage

```php
use Ciloe\Ranges\DateRange;

// Create a range from 2023-01-01 to 2023-01-10
$range = new DateRange(
    new DateTimeImmutable('2023-01-01'),
    new DateTimeImmutable('2023-01-10'),
    '[',
    ']'
);

// Check if a date is in the range
$range->contains(new DateTimeImmutable('2023-01-05')); // true
$range->contains(new DateTimeImmutable('2023-01-15')); // false

// Get the length of the range (number of days)
$range->length(); // 10

// Generate a series of dates in the range
$dates = $range->generateSeries(); // Array of DateTimeImmutable objects from 2023-01-01 to 2023-01-10

// Create a range with a specific step (every 2 days)
$range = new DateRange(
    new DateTimeImmutable('2023-01-01'),
    new DateTimeImmutable('2023-01-10'),
    '[',
    ']',
    new DateInterval('P2D')
);
$dates = $range->generateSeries(); // [2023-01-01, 2023-01-03, 2023-01-05, 2023-01-07, 2023-01-09]
```

## Creating Ranges

### Constructor

```php
new DateRange(
    ?DateTimeImmutable $lower = null,     // Lower bound (null for -∞)
    ?DateTimeImmutable $upper = null,     // Upper bound (null for +∞)
    string $lowerBound = '(',             // Lower bound type: '[' (inclusive) or '(' (exclusive)
    string $upperBound = ')',             // Upper bound type: ']' (inclusive) or ')' (exclusive)
    DateInterval $step = new DateInterval('P1D') // Step for series generation (default: 1 day)
);
```

The step must be a strictly positive date-only interval: an `InvalidDateIntervalException` is thrown if it contains time components (hours, minutes, seconds), if it is zero, or if it is negative/inverted.

### Factories

```php
DateRange::fromMonth(2025, 6); // [2025-06-01,2025-06-30]
DateRange::fromYear(2025);     // [2025-01-01,2025-12-31]
DateRange::fromWeek(2025, 23); // [2025-06-02,2025-06-08] (ISO week, Monday to Sunday)
```

`fromMonth()` throws an `InvalidArgumentException` for a month outside 1-12, and `fromWeek()` for a week that does not exist in the given ISO year.

### From a String

```php
// Format: (lower,upper) where parentheses can be [ or ] for inclusion
// Dates are in Y-m-d format
DateRange::fromString('(2023-01-01,2023-01-10)');  // Range (2023-01-01,2023-01-10) - exclusive
DateRange::fromString('[2023-01-01,2023-01-10]');  // Range [2023-01-01,2023-01-10] - inclusive
DateRange::fromString('(,2023-01-10]');            // Range (-∞,2023-01-10]
DateRange::fromString('[2023-01-01,)');            // Range [2023-01-01,+∞)
DateRange::fromString('(,)');                      // Range (-∞,+∞)
```

## Main Methods

### Verification and Properties

- `isEmpty()` : Checks if the range is empty
- `isBoundsValid()` : Checks if the bounds are valid (lower ≤ upper)
- `getLowerBoundValue()` : Returns the effective value of the lower bound
- `getUpperBoundValue()` : Returns the effective value of the upper bound
- `contains(DateTimeInterface $value)` : Checks if a date is in the range (the time part of the value is ignored)
- `containsRange(DateRange $range)` : Checks if the range fully contains another range
- `length()` : Calculates the number of values in the range, considering the step (null for infinite ranges)
- `clamp(DateTimeInterface $value)` : Brings a date back within the effective bounds
- `random()` : Picks a random date from the range, aligned on the step
- `getStep()` : Returns the step interval

### Operations Between Ranges

- `overlap(DateRange $range)` : Checks if two ranges overlap
- `isBefore(DateRange $range)` / `isAfter(DateRange $range)` : Checks if the range is strictly before/after another
- `isAdjacent(DateRange $range)` : Checks if two ranges touch within exactly one step, without overlapping
- `union(DateRange $range)` : Calculates the union of two ranges (returns null if the steps differ)
- `intersection(DateRange $range)` : Calculates the intersection of two ranges (returns null if the steps differ or the ranges do not intersect)
- `difference(DateRange $range)` : Subtracts a range, returning zero, one or two remaining ranges (null if the steps differ)
- `gap(DateRange $range)` : Returns the range between two disjoint ranges (null if they overlap, are adjacent, or the steps differ)
- `equals(DateRange $range)` : Checks if two ranges are equal (effective bounds and step)

Steps are compared by their equivalent number of days, measured from a fixed reference date so the result does not depend on when the code runs.

### Transformations

- `generateSeries()` : Generates an array of dates in the range
- `iterate()` : Lazily iterates over the dates (a Generator; an infinite upper bound never stops)
- `chunk(int $count)` : Splits the range into consecutive sub-ranges of at most `$count` values
- `split(DateTimeInterface $point)` : Divides the range into two at the specified date (returns the original range alone if the point is outside)
- `clone()` : Creates a copy of the range
- `shift(DateInterval $offset)` : Shifts the range by the specified interval (the offset must not contain time components; an inverted interval shifts backwards)
- `expand(DateInterval $amount)` / `shrink(DateInterval $amount)` : Widens/narrows both bounds by the specified date-only interval
- `scale(DateInterval $factor)` : Not supported, always throws an `InvalidArgumentException`
- `__toString()` : Converts the range to a string

## Advanced Examples

### Range Manipulation

```php
// Intersection of two ranges
$range1 = new DateRange(
    new DateTimeImmutable('2023-01-01'),
    new DateTimeImmutable('2023-01-15'),
    '[',
    ']'
);
$range2 = new DateRange(
    new DateTimeImmutable('2023-01-10'),
    new DateTimeImmutable('2023-01-20'),
    '[',
    ']'
);
$intersection = $range1->intersection($range2); // [2023-01-10,2023-01-15]

// Union of two ranges
$union = $range1->union($range2); // [2023-01-01,2023-01-20]

// Check overlap
$range1->overlap($range2); // true

// Split a range
$range = new DateRange(
    new DateTimeImmutable('2023-01-01'),
    new DateTimeImmutable('2023-01-10'),
    '[',
    ']'
);
[$left, $right] = $range->split(new DateTimeImmutable('2023-01-05'));
// [2023-01-01,2023-01-05) and [2023-01-05,2023-01-10]

// Shift a range
$range = new DateRange(
    new DateTimeImmutable('2023-01-01'),
    new DateTimeImmutable('2023-01-10'),
    '[',
    ']'
);
$shifted = $range->shift(new DateInterval('P5D')); // [2023-01-06,2023-01-15]
```

### Infinite Ranges

```php
// Range without lower bound
$range = new DateRange(
    null,
    new DateTimeImmutable('2023-01-10'),
    '(',
    ']'
);
$range->contains(new DateTimeImmutable('1900-01-01')); // true
$range->contains(new DateTimeImmutable('2023-01-10')); // true
$range->contains(new DateTimeImmutable('2023-01-11')); // false

// Range without upper bound
$range = new DateRange(
    new DateTimeImmutable('2023-01-01'),
    null,
    '[',
    ')'
);
$range->contains(new DateTimeImmutable('2023-01-01')); // true
$range->contains(new DateTimeImmutable('2100-01-01')); // true
$range->contains(new DateTimeImmutable('2022-12-31')); // false

// Completely open range
$range = new DateRange(null, null, '(', ')');
$range->contains(new DateTimeImmutable('1900-01-01')); // true
$range->contains(new DateTimeImmutable('2023-01-01')); // true
$range->contains(new DateTimeImmutable('2100-01-01')); // true
```

### The Time Part Is Ignored

`contains()` compares dates only, so a value carrying a time component still belongs to the range of its day:

```php
$range = new DateRange(
    new DateTimeImmutable('2023-01-01'),
    new DateTimeImmutable('2023-01-10'),
    '[',
    ']'
);
$range->contains(new DateTimeImmutable('2023-01-10 15:30:00')); // true
$range->contains(new DateTimeImmutable('2023-01-11 00:00:00')); // false
```

### Step and Exclusive Bounds

The step defines the distance between two consecutive values. An exclusive bound is shifted by one step:

```php
$range = new DateRange(
    new DateTimeImmutable('2023-01-01'),
    new DateTimeImmutable('2023-01-10'),
    '(',
    ')',
    new DateInterval('P2D')
);
$range->getLowerBoundValue(); // 2023-01-03
$range->getUpperBoundValue(); // 2023-01-08
$range->generateSeries(); // [2023-01-03, 2023-01-05, 2023-01-07]
$range->length(); // 3
```

### Working with Different Step Intervals

```php
// Create a range with monthly steps
$range = new DateRange(
    new DateTimeImmutable('2023-01-01'),
    new DateTimeImmutable('2023-06-01'),
    '[',
    ']',
    new DateInterval('P1M')
);
$dates = $range->generateSeries(); // [2023-01-01, 2023-02-01, 2023-03-01, 2023-04-01, 2023-05-01, 2023-06-01]

// Create a range with weekly steps
$range = new DateRange(
    new DateTimeImmutable('2023-01-01'),
    new DateTimeImmutable('2023-01-31'),
    '[',
    ']',
    new DateInterval('P1W')
);
$dates = $range->generateSeries(); // [2023-01-01, 2023-01-08, 2023-01-15, 2023-01-22, 2023-01-29]
```
