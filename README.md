# Ranges

This library provides classes for working with ranges of values.

## Installation

```bash
composer require ciloe/ranges
```

## Available Range Types

### IntRange

The `IntRange` class allows you to represent and manipulate integer ranges. It offers a complete API for creating, comparing, and transforming ranges of integer numbers.

For detailed documentation on the IntRange class, see [IntRange Documentation](documentation/IntRange.md).

## Quick Example

```php
use Ciloe\Ranges\IntRange;

// Create a range [1, 10]
$range = new IntRange(1, 10, '[', ']');

// Check if a value is in the range
$range->contains(5); // true

// Generate a series of values in the range
$range->generateSeries(); // [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
```

## Exceptions

- `InvalidArgumentException` : Invalid range format
- `InvalidBoundException` : Invalid bounds (lower > upper)
- `InvalidInfiniteBoundException` : Infinite bound with inclusion
- `InvalidStepToGenerateSeriesException` : Invalid step for generating a series
- `CantGenerateSeriesBecauseTheArrayIsTooLarge` : Series too large to be generated

## Notes

- Ranges can have infinite bounds (null)
- Bounds can be inclusive ([, ]) or exclusive ((, ))
- The step only affects series generation, not other operations
- Operations between ranges (union, intersection) require the same step
