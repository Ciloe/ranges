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
- `contains(DateTimeInterface $value)` : Checks if a date is in the range
- `length()` : Calculates the length of the range (number of days)
- `getStep()` : Returns the step interval

### Operations Between Ranges

- `overlap(DateRange $range)` : Checks if two ranges overlap
- `union(DateRange $range)` : Calculates the union of two ranges
- `intersection(DateRange $range)` : Calculates the intersection of two ranges
- `equals(DateRange $range)` : Checks if two ranges are equal

### Transformations

- `generateSeries()` : Generates an array of dates in the range
- `split(DateTimeInterface $point)` : Divides the range into two at the specified date
- `clone()` : Creates a copy of the range
- `shift(DateInterval $offset)` : Shifts the range by the specified time interval
- `scale(DateInterval $factor)` : This function is not supported for DateRange
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
