# IntRange

The `IntRange` class allows you to represent and manipulate integer ranges. It offers a complete API for creating, comparing, and transforming ranges of integer numbers.

## Basic Usage

```php
use Ciloe\Ranges\IntRange;

// Create a range [1, 10]
$range = new IntRange(1, 10, '[', ']');

// Check if a value is in the range
$range->contains(5); // true
$range->contains(0); // false

// Get the length of the range
$range->length(); // 10

// Generate a series of values in the range
$range->generateSeries(); // [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]

// Create a range with a specific step
$range = new IntRange(1, 10, '[', ']', 2);
$range->generateSeries(); // [1, 3, 5, 7, 9]
```

## Creating Ranges

### Constructor

```php
new IntRange(
    ?int $lower = null,     // Lower bound (null for -∞)
    ?int $upper = null,     // Upper bound (null for +∞)
    string $lowerBound = '(', // Lower bound type: '[' (inclusive) or '(' (exclusive)
    string $upperBound = ')', // Upper bound type: ']' (inclusive) or ')' (exclusive)
    int $step = 1           // Step for series generation
);
```

### From a String

```php
// Format: (lower,upper) where parentheses can be [ or ] for inclusion
IntRange::fromString('(1,10)');  // Range (1,10) - exclusive
IntRange::fromString('[1,10]');  // Range [1,10] - inclusive
IntRange::fromString('(,10]');   // Range (-∞,10]
IntRange::fromString('[1,)');    // Range [1,+∞)
IntRange::fromString('(,)');     // Range (-∞,+∞)
```

## Main Methods

### Verification and Properties

- `isEmpty()` : Checks if the range is empty
- `isBoundsValid()` : Checks if the bounds are valid (lower ≤ upper)
- `getLowerBoundValue()` : Returns the effective value of the lower bound
- `getUpperBoundValue()` : Returns the effective value of the upper bound
- `contains(int $value)` : Checks if a value is in the range
- `length()` : Calculates the length of the range (number of integers)

### Operations Between Ranges

- `overlap(IntRange $range)` : Checks if two ranges overlap
- `union(IntRange $range)` : Calculates the union of two ranges
- `intersection(IntRange $range)` : Calculates the intersection of two ranges
- `equals(IntRange $range)` : Checks if two ranges are equal

### Transformations

- `generateSeries()` : Generates an array of values in the range
- `split(int $point)` : Divides the range into two at the specified point
- `clone()` : Creates a copy of the range
- `shift(int $offset)` : Shifts the range by the specified value
- `scale(int $factor)` : Multiplies the bounds by the specified factor
- `__toString()` : Converts the range to a string

## Advanced Examples

### Range Manipulation

```php
// Intersection of two ranges
$range1 = new IntRange(1, 10, '[', ']');
$range2 = new IntRange(5, 15, '[', ']');
$intersection = $range1->intersection($range2); // [5,10]

// Union of two ranges
$union = $range1->union($range2); // [1,15]

// Check overlap
$range1->overlap($range2); // true

// Split a range
$range = new IntRange(1, 10, '[', ']');
[$left, $right] = $range->split(5); // [1,5) and [5,10]

// Shift a range
$range = new IntRange(1, 10, '[', ']');
$shifted = $range->shift(5); // [6,15]

// Scale a range
$range = new IntRange(1, 10, '[', ']');
$scaled = $range->scale(2); // [2,20]
$negativeScaled = $range->scale(-1); // [-10,-1]
```

### Infinite Ranges

```php
// Range without lower bound
$range = new IntRange(null, 10, '(', ']');
$range->contains(PHP_INT_MIN); // true
$range->contains(10); // true
$range->contains(11); // false

// Range without upper bound
$range = new IntRange(1, null, '[', ')');
$range->contains(1); // true
$range->contains(PHP_INT_MAX); // true
$range->contains(0); // false

// Completely open range
$range = new IntRange(null, null, '(', ')');
$range->contains(PHP_INT_MIN); // true
$range->contains(0); // true
$range->contains(PHP_INT_MAX); // true
```