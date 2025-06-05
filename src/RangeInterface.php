<?php

declare(strict_types=1);

namespace Ciloe\Ranges;

use Ciloe\Ranges\Exception\CantGenerateSeriesBecauseTheArrayIsTooLarge;
use Ciloe\Ranges\Exception\InvalidBoundException;
use Ciloe\Ranges\Exception\InvalidInfiniteBoundException;
use Ciloe\Ranges\Exception\InvalidStepToGenerateSeriesException;
use InvalidArgumentException;

/**
 * @template T
 * @template Y
 */
interface RangeInterface
{
    /**
     * Returns a string representation of the range.
     */
    public function __toString(): string;

    /**
     * Will return an object representation of the range
     *
     * @return RangeInterface<T, Y> The range class
     * @throws InvalidArgumentException If the range is invalid
     * @throws InvalidInfiniteBoundException When the includes bounds are used with infinite bounds
     * @throws InvalidBoundException When the upper bound is lower than the lower bound
     */
    public static function fromString(string $range): self;

    /**
     * Checks if the range is empty.
     *
     * @return bool True if the range is empty, false otherwise
     */
    public function isEmpty(): bool;

    /**
     * Checks if the bounds of the range are valid.
     *
     * @return bool True if the bounds are valid, false otherwise
     */
    public function isBoundsValid(): bool;

    /**
     * Gets the effective lower bound value.
     *
     * @return T|null The lower bound value, or null if unbounded
     */
    public function getLowerBoundValue(): mixed;

    /**
     * Gets the effective upper bound value.
     *
     * @return T|null The upper bound value, or null if unbounded
     */
    public function getUpperBoundValue(): mixed;

    /**
     * Checks if the range contains a value.
     *
     * @param T $value The value to check
     * @return bool True if the range contains the value, false otherwise
     * @throws InvalidArgumentException If the value is invalid
     */
    public function contains(mixed $value): bool;

    /**
     * Checks if this range overlaps with another range.
     *
     * @param RangeInterface<T, Y> $range The range to check for overlap
     * @return bool True if the ranges overlap, false otherwise
     * @throws InvalidArgumentException If the value is invalid
     */
    public function overlap(self $range): bool;

    /**
     * Calculates the length of the range.
     *
     * @return T|null The length of the range, or null if unbounded
     */
    public function length();

    /**
     * Creates a union of this range with another range.
     *
     * @param RangeInterface<T, Y> $range The range to union with
     * @return RangeInterface<T, Y>|null The union range, or null if the ranges cannot be unioned
     * @throws InvalidArgumentException If the value is invalid
     */
    public function union(self $range): ?self;

    /**
     * Creates an intersection of this range with another range.
     *
     * @param RangeInterface<T, Y> $range The range to intersect with
     * @return RangeInterface<T, Y>|null The intersection range, or null if the ranges do not intersect
     * @throws InvalidArgumentException If the value is invalid
     */
    public function intersection(self $range): ?self;

    /**
     * Generates a series of values in the range.
     *
     * @return array<T> The series of values
     * @throws CantGenerateSeriesBecauseTheArrayIsTooLarge If the series is too large to generate
     * @throws InvalidStepToGenerateSeriesException If the step is invalid for generating a series
     */
    public function generateSeries(): array;

    /**
     * Checks if this range equals another range.
     *
     * @param RangeInterface<T, Y> $range The range to compare with
     * @return bool True if the ranges are equal, false otherwise
     * @throws InvalidArgumentException If the value is invalid
     */
    public function equals(self $range): bool;

    /**
     * Splits the range at a specific point.
     *
     * @param T $point The point to split at
     * @return array<RangeInterface<T, Y>> An array of ranges resulting from the split
     * @throws InvalidArgumentException If the point is invalid
     */
    public function split(mixed $point): array;

    /**
     * Creates a clone of this range.
     *
     * @return RangeInterface<T, Y> The cloned range
     */
    public function clone(): self;

    /**
     * Creates a new range by shifting this range by an offset.
     *
     * @param Y $offset The offset to shift by
     * @return RangeInterface<T, Y> The shifted range
     * @throws InvalidArgumentException If the offset is invalid
     */
    public function shift(mixed $offset): self;

    /**
     * Creates a new range by scaling this range by a factor.
     *
     * @param Y $factor The factor to scale by
     * @return RangeInterface<T, Y> The scaled range
     * @throws InvalidArgumentException If the factor is invalid
     */
    public function scale(mixed $factor): self;

    /**
     * Gets the step value used for generating series and calculating length.
     *
     * @return Y The step value
     */
    public function getStep(): mixed;
}
