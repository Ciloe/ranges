# BigIntRange

The `BigIntRange` class allows you to represent and manipulate arbitrary precision integer ranges. It uses string representations of integers and the BCMath extension for all operations, enabling work with values beyond PHP's native integer limits (PHP_INT_MAX).

## Basic Usage

```php
use Ciloe\Ranges\BigIntRange;

// Create a range with values beyond PHP_INT_MAX
// PHP_INT_MAX on 64-bit systems is 9223372036854775807
$range = new BigIntRange('9223372036854775808', '9223372036854775818', '[', ']');

// Check if a value is in the range
$range->contains('9223372036854775810'); // true

// Get the length of the range (as a numeric string)
$range->length(); // '11'

// Generate a series of values in the range
$series = $range->generateSeries(); // ['9223372036854775808', '9223372036854775809', ...]
```

## Creating Ranges

### Constructor

```php
new BigIntRange(
    ?string $lower = null,    // Lower bound (null for -∞), as a numeric string
    ?string $upper = null,    // Upper bound (null for +∞), as a numeric string
    string $lowerBound = '(', // Lower bound type: '[' (inclusive) or '(' (exclusive)
    string $upperBound = ')', // Upper bound type: ']' (inclusive) or ')' (exclusive)
    string $step = '1'        // Distance between two consecutive values (must be positive)
);
```

All numeric values are represented as strings to handle arbitrary precision integers. An `InvalidArgumentException` is thrown if a bound or the step is not a valid numeric string, or if the step is not strictly positive.

### From a String

```php
// Format: (lower,upper) where parentheses can be [ or ] for inclusion
BigIntRange::fromString('(1,10)');                    // Range (1,10) - exclusive
BigIntRange::fromString('[1,10]');                    // Range [1,10] - inclusive
BigIntRange::fromString('[9223372036854775808,)');    // Range [9223372036854775808,+∞)
BigIntRange::fromString('(,10]');                     // Range (-∞,10]
BigIntRange::fromString('(,)');                       // Range (-∞,+∞)
```

## Main Methods

### Verification and Properties

- `isEmpty()` : Checks if the range is empty
- `isBoundsValid()` : Checks if the bounds are valid (lower ≤ upper)
- `getLowerBoundValue()` : Returns the effective value of the lower bound (an exclusive bound is shifted by one step)
- `getUpperBoundValue()` : Returns the effective value of the upper bound (an exclusive bound is shifted by one step)
- `contains(string $value)` : Checks if a value is in the range
- `containsRange(BigIntRange $range)` : Checks if the range fully contains another range
- `length()` : Calculates the number of values in the range as a numeric string, considering the step (null for infinite ranges)
- `clamp(string $value)` : Brings a value back within the effective bounds
- `random()` : Picks a random value from the range, aligned on the step (finite ranges only)
- `getStep()` : Returns the step

### Operations Between Ranges

- `overlap(BigIntRange $range)` : Checks if two ranges overlap
- `isBefore(BigIntRange $range)` / `isAfter(BigIntRange $range)` : Checks if the range is strictly before/after another
- `isAdjacent(BigIntRange $range)` : Checks if two ranges touch within exactly one step, without overlapping
- `union(BigIntRange $range)` : Calculates the union of two ranges (returns null if the steps differ)
- `intersection(BigIntRange $range)` : Calculates the intersection of two ranges (returns null if the steps differ or the ranges do not intersect)
- `difference(BigIntRange $range)` : Subtracts a range, returning zero, one or two remaining ranges (null if the steps differ)
- `gap(BigIntRange $range)` : Returns the range between two disjoint ranges (null if they overlap, are adjacent, or the steps differ)
- `equals(BigIntRange $range)` : Checks if two ranges are equal (effective bounds and step)

### Transformations

- `generateSeries()` : Generates an array of values in the range (capped at 1,000,000 values)
- `iterate()` : Lazily iterates over the values (a Generator; an infinite upper bound never stops)
- `chunk(int $count)` : Splits the range into consecutive sub-ranges of at most `$count` values
- `split(string $point)` : Divides the range into two at the specified point (returns the original range alone if the point is outside)
- `clone()` : Creates a copy of the range
- `shift(string $offset)` : Shifts the range by the specified value
- `expand(string $amount)` / `shrink(string $amount)` : Widens/narrows both bounds by the specified amount
- `scale(string $factor)` : Multiplies the bounds and the step by the specified factor (bounds are swapped when the factor is negative)
- `__toString()` : Converts the range to a string

## Advanced Examples

### Range Manipulation

```php
// Operations with very large integers
$range = new BigIntRange('9223372036854775808', '9223372036854775818', '[', ']');

$shifted = $range->shift('1000000000000000000');
// $shifted now represents [10223372036854775808, 10223372036854775818]

$scaled = $range->scale('2');
// $scaled now represents [18446744073709551616, 18446744073709551636]

// Subtract a range: zero, one or two parts remain
$range = new BigIntRange('1', '10', '[', ']');
$range->difference(new BigIntRange('4', '6', '[', ']')); // [[1,3], [7,10]]

// The range between two disjoint ranges
(new BigIntRange('1', '5', '[', ']'))->gap(new BigIntRange('10', '20', '[', ']')); // [6,9]

// Batch processing by sub-ranges
$range = new BigIntRange('1', '10', '[', ']');
$range->chunk(4); // [1,4], [5,8], [9,10]
```

### Step and Exclusive Bounds

The step defines the distance between two consecutive values of the range. An exclusive bound is shifted by one step:

```php
$range = new BigIntRange('0', '20', '(', ')', '5');
$range->getLowerBoundValue(); // '5'
$range->getUpperBoundValue(); // '15'
$range->generateSeries(); // ['5', '10', '15']
$range->length(); // '3'
```

## Requirements

The BigIntRange class requires PHP 8.3 or higher and the BCMath extension to be enabled in your PHP installation.
