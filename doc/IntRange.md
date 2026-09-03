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
    int $step = 1           // Distance between two consecutive values (must be positive)
);
```

The step must be strictly positive: an `InvalidArgumentException` is thrown otherwise.

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
- `containsRange(IntRange $range)` : Checks if the range fully contains another range
- `length()` : Calculates the number of values in the range, considering the step (null for infinite ranges)
- `clamp(int $value)` : Brings a value back within the effective bounds
- `random()` : Picks a random value from the range, aligned on the step
- `getStep()` : Returns the step

### Operations Between Ranges

- `overlap(IntRange $range)` : Checks if two ranges overlap
- `isBefore(IntRange $range)` / `isAfter(IntRange $range)` : Checks if the range is strictly before/after another
- `isAdjacent(IntRange $range)` : Checks if two ranges touch within exactly one step, without overlapping
- `union(IntRange $range)` : Calculates the union of two ranges (returns null if the steps differ)
- `intersection(IntRange $range)` : Calculates the intersection of two ranges (returns null if the steps differ or the ranges do not intersect)
- `difference(IntRange $range)` : Subtracts a range, returning zero, one or two remaining ranges (null if the steps differ)
- `gap(IntRange $range)` : Returns the range between two disjoint ranges (null if they overlap, are adjacent, or the steps differ)
- `equals(IntRange $range)` : Checks if two ranges are equal (effective bounds and step)

### Transformations

- `generateSeries()` : Generates an array of values in the range
- `iterate()` : Lazily iterates over the values (a Generator; handles huge ranges in constant memory)
- `chunk(int $count)` : Splits the range into consecutive sub-ranges of at most `$count` values
- `split(int $point)` : Divides the range into two at the specified point (returns the original range alone if the point is outside)
- `clone()` : Creates a copy of the range
- `shift(int $offset)` : Shifts the range by the specified value
- `expand(int $amount)` / `shrink(int $amount)` : Widens/narrows both bounds by the specified amount
- `scale(int $factor)` : Multiplies the bounds and the step by the specified factor (bounds are swapped when the factor is negative)
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

### Position Predicates, Difference and Gap

```php
$range = new IntRange(1, 10, '[', ']');

$range->containsRange(new IntRange(3, 5, '[', ']')); // true
$range->isBefore(new IntRange(15, 20, '[', ']'));    // true
$range->isAdjacent(new IntRange(11, 20, '[', ']'));  // true (touches within one step)

// Subtract a range: zero, one or two parts remain
$range->difference(new IntRange(4, 6, '[', ']')); // [[1,3], [7,10]]

// The range between two disjoint ranges
(new IntRange(1, 5, '[', ']'))->gap(new IntRange(10, 20, '[', ']')); // [6,9]
```

### Values, Iteration and Chunking

```php
$range = new IntRange(1, 10, '[', ']');

$range->clamp(15); // 10
$range->random();  // a value between 1 and 10

// Lazy iteration: constant memory, works on huge ranges
foreach ((new IntRange(1, 1000000000, '[', ']'))->iterate() as $value) {
    // ...
}

// Batch processing by sub-ranges
$range = new IntRange(1, 100, '[', ']');
$range->chunk(30); // [1,30], [31,60], [61,90], [91,100]

// Add or remove a margin around the bounds
$range = new IntRange(5, 10, '[', ']');
$range->expand(2); // [3,12]
$range->shrink(2); // [7,8]
```

### Step and Exclusive Bounds

The step defines the distance between two consecutive values of the range. An exclusive bound is shifted by one step (not by 1):

```php
$range = new IntRange(0, 20, '(', ')', 5);
$range->getLowerBoundValue(); // 5
$range->getUpperBoundValue(); // 15
$range->generateSeries(); // [5, 10, 15]
$range->length(); // 3

// A step larger than the span still yields the lower bound
$range = new IntRange(1, 5, '[', ']', 10);
$range->generateSeries(); // [1]
$range->length(); // 1
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
