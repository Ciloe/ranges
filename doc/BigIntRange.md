# BigIntRange Documentation

The `BigIntRange` class allows you to represent and manipulate arbitrary precision integer ranges. It uses string representations of integers and the BCMath extension for all operations, enabling work with values beyond PHP's native integer limits (PHP_INT_MAX).

## Class Definition

```php
class BigIntRange
{
    public function __construct(
        readonly public ?string $lower = null,
        readonly public ?string $upper = null,
        readonly public string $lowerBound = '(',
        readonly public string $upperBound = ')',
        public string $step = '1',
    ) {}
}
```

## Properties

- `lower`: The lower bound of the range (as a string or null for negative infinity)
- `upper`: The upper bound of the range (as a string or null for positive infinity)
- `lowerBound`: The type of lower bound, either `[` (inclusive) or `(` (exclusive)
- `upperBound`: The type of upper bound, either `]` (inclusive) or `)` (exclusive)
- `step`: The step value for generating series (as a string)

## Methods

### Constructor

```php
public function __construct(
    readonly public ?string $lower = null,
    readonly public ?string $upper = null,
    readonly public string $lowerBound = '(',
    readonly public string $upperBound = ')',
    public string $step = '1',
) {}
```

Creates a new BigIntRange instance. All numeric values are represented as strings to handle arbitrary precision integers.

### fromString

```php
public static function fromString(string $range): self
```

Creates a range from a string representation like `[1,10]`, `(1,10)`, `[1,10)`, or `(1,10]`.

### isEmpty

```php
public function isEmpty(): bool
```

Checks if the range is empty (contains no values).

### isBoundsValid

```php
public function isBoundsValid(): bool
```

Checks if the bounds of the range are valid (lower <= upper).

### getLowerBoundValue

```php
public function getLowerBoundValue(): ?string
```

Gets the effective lower bound value, considering the bound type (inclusive/exclusive).

### getUpperBoundValue

```php
public function getUpperBoundValue(): ?string
```

Gets the effective upper bound value, considering the bound type (inclusive/exclusive).

### contains

```php
public function contains(string $value): bool
```

Checks if the range contains a specific value.

### overlap

```php
public function overlap(BigIntRange $range): bool
```

Checks if this range overlaps with another range.

### length

```php
public function length(): ?int
```

Calculates the number of values in the range, considering the step. Returns null for infinite ranges.

### union

```php
public function union(BigIntRange $range): ?self
```

Creates a new range that is the union of this range and another range. Returns null if the ranges have different steps.

### intersection

```php
public function intersection(BigIntRange $range): ?self
```

Creates a new range that is the intersection of this range and another range. Returns null if the ranges have different steps or don't overlap.

### generateSeries

```php
public function generateSeries(): array
```

Generates an array of all values in the range, using the step value. Throws an exception for infinite ranges or if the resulting array would be too large.

### __toString

```php
public function __toString(): string
```

Returns a string representation of the range, like `[1,10]`.

### equals

```php
public function equals(BigIntRange $range): bool
```

Checks if this range is equal to another range.

### split

```php
public function split(string $point): array
```

Splits the range at a specific point, returning an array of two ranges.

### clone

```php
public function clone(): self
```

Creates a copy of this range.

### shift

```php
public function shift(string $offset): self
```

Creates a new range by shifting this range by the specified offset.

### scale

```php
public function scale(string $factor): self
```

Creates a new range by scaling this range by the specified factor.

## Examples

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

## Requirements

The BigIntRange class requires the BCMath extension to be enabled in your PHP installation.