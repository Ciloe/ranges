# TimeRange

The `TimeRange` class allows you to represent and manipulate time-of-day ranges. All comparisons only use the time part (hours, minutes, seconds) of the `DateTimeImmutable` objects — the date part is ignored.

## Basic Usage

```php
use Ciloe\Ranges\TimeRange;
use DateTimeImmutable;
use DateInterval;

$today = new DateTimeImmutable('today');

// Create a range from 09:00:00 to 17:00:00
$range = new TimeRange(
    $today->setTime(9, 0, 0),
    $today->setTime(17, 0, 0),
    '[',
    ']'
);

// Check if a time is in the range (the date part is ignored)
$range->contains(new DateTimeImmutable('2030-06-15 12:30:00')); // true
$range->contains($today->setTime(17, 0, 1)); // false

// Get the length of the range with a 1-hour step
$range = new TimeRange($today->setTime(9, 0, 0), $today->setTime(17, 0, 0), '[', ']', new DateInterval('PT1H'));
$range->length(); // 9

// Generate a series of times with a 2-hour step
$range = new TimeRange($today->setTime(9, 0, 0), $today->setTime(17, 0, 0), '[', ']', new DateInterval('PT2H'));
$range->generateSeries(); // [09:00:00, 11:00:00, 13:00:00, 15:00:00, 17:00:00]
```

## Creating Ranges

### Constructor

```php
new TimeRange(
    ?DateTimeImmutable $lower = null,             // Lower bound (null for -∞)
    ?DateTimeImmutable $upper = null,             // Upper bound (null for +∞)
    string $lowerBound = '(',                     // Lower bound type: '[' (inclusive) or '(' (exclusive)
    string $upperBound = ')',                     // Upper bound type: ']' (inclusive) or ')' (exclusive)
    DateInterval $step = new DateInterval('PT1S') // Step for series generation (default: 1 second)
);
```

The step must be a strictly positive time-only interval: an `InvalidTimeIntervalException` is thrown if it contains date components (years, months, days), if it is zero, or if it is negative/inverted.

### From a String

```php
// Format: (lower,upper) where parentheses can be [ or ] for inclusion
// Times are in H:i:s format
TimeRange::fromString('(09:00:00,17:00:00)');  // Range (09:00:00,17:00:00) - exclusive
TimeRange::fromString('[09:00:00,17:00:00]');  // Range [09:00:00,17:00:00] - inclusive
TimeRange::fromString('(,17:00:00]');          // Range (-∞,17:00:00]
TimeRange::fromString('[09:00:00,)');          // Range [09:00:00,+∞)
TimeRange::fromString('(,)');                  // Range (-∞,+∞)
```

## Main Methods

### Verification and Properties

- `isEmpty()` : Checks if the range is empty
- `isBoundsValid()` : Checks if the bounds are valid (lower ≤ upper)
- `getLowerBoundValue()` : Returns the effective value of the lower bound
- `getUpperBoundValue()` : Returns the effective value of the upper bound
- `contains(DateTimeInterface $value)` : Checks if a time is in the range (date part ignored)
- `length()` : Calculates the number of values in the range, considering the step (null for infinite ranges)
- `getStep()` : Returns the step interval

### Operations Between Ranges

- `overlap(TimeRange $range)` : Checks if two ranges overlap
- `union(TimeRange $range)` : Calculates the union of two ranges (returns null if the steps differ)
- `intersection(TimeRange $range)` : Calculates the intersection of two ranges (returns null if the steps differ or the ranges do not intersect)
- `equals(TimeRange $range)` : Checks if two ranges are equal (dates ignored, steps compared by their number of seconds)

### Transformations

- `generateSeries()` : Generates an array of times in the range
- `split(DateTimeInterface $point)` : Divides the range into two at the specified time (returns the original range alone if the point is outside)
- `clone()` : Creates a copy of the range
- `shift(DateInterval $offset)` : Shifts the range by the specified time interval (the offset must not contain date components)
- `scale(DateInterval $factor)` : Not supported, always throws an `InvalidArgumentException`
- `__toString()` : Converts the range to a string

## Advanced Examples

### Range Manipulation

```php
$today = new DateTimeImmutable('today');

$range1 = new TimeRange($today->setTime(9, 0, 0), $today->setTime(12, 0, 0), '[', ']');
$range2 = new TimeRange($today->setTime(11, 0, 0), $today->setTime(14, 0, 0), '[', ']');

// Intersection of two ranges
$intersection = $range1->intersection($range2); // [11:00:00,12:00:00]

// Union of two ranges
$union = $range1->union($range2); // [09:00:00,14:00:00]

// Check overlap
$range1->overlap($range2); // true

// Split a range
$range = new TimeRange($today->setTime(9, 0, 0), $today->setTime(17, 0, 0), '[', ']');
[$left, $right] = $range->split($today->setTime(12, 0, 0));
// [09:00:00,12:00:00) and [12:00:00,17:00:00]

// Shift a range
$shifted = $range->shift(new DateInterval('PT1H')); // [10:00:00,18:00:00]
```

### Step and Exclusive Bounds

The step defines the distance between two consecutive values. An exclusive bound is shifted by one step:

```php
$range = new TimeRange(
    $today->setTime(9, 0, 0),
    $today->setTime(10, 0, 0),
    '(',
    ')',
    new DateInterval('PT15M')
);
$range->getLowerBoundValue(); // 09:15:00
$range->getUpperBoundValue(); // 09:45:00
$range->length(); // 3
```

### The Date Part Is Ignored

Two ranges built on different days but with the same times are equal, overlap, and intersect:

```php
$today = new DateTimeImmutable('today');
$tomorrow = new DateTimeImmutable('tomorrow');

$range1 = new TimeRange($today->setTime(20, 0, 0), $today->setTime(21, 0, 0), '[', ']');
$range2 = new TimeRange($tomorrow->setTime(20, 0, 0), $tomorrow->setTime(21, 0, 0), '[', ']');

$range1->equals($range2);  // true
$range1->overlap($range2); // true
```

## Notes

- A series never wraps past midnight: with `[23:00:00,23:59:59]` and a 30-minute step, `generateSeries()` returns `[23:00:00, 23:30:00]` and stops at the upper bound.
- `generateSeries()` throws a `CantGenerateSeriesBecauseTheArrayIsTooLarge` exception for infinite ranges.
