<?php

declare(strict_types=1);

namespace Ciloe\Ranges;

use Ciloe\Ranges\Exception\CantGenerateSeriesBecauseTheArrayIsTooLarge;
use Ciloe\Ranges\Exception\InvalidBoundException;
use Ciloe\Ranges\Exception\InvalidInfiniteBoundException;
use Generator;
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
     * Checks if this range fully contains another range.
     *
     * An empty range is contained in any range.
     *
     * @param RangeInterface<T, Y> $range The range to check
     * @return bool True if this range contains the other range, false otherwise
     * @throws InvalidArgumentException If the range is of a different type
     */
    public function containsRange(self $range): bool;

    /**
     * Checks if this range is strictly before another range (no overlap).
     *
     * @param RangeInterface<T, Y> $range The range to compare with
     * @return bool True if this range ends before the other range starts, false otherwise
     * @throws InvalidArgumentException If the range is of a different type
     */
    public function isBefore(self $range): bool;

    /**
     * Checks if this range is strictly after another range (no overlap).
     *
     * @param RangeInterface<T, Y> $range The range to compare with
     * @return bool True if this range starts after the other range ends, false otherwise
     * @throws InvalidArgumentException If the range is of a different type
     */
    public function isAfter(self $range): bool;

    /**
     * Checks if this range touches another range within exactly one step, without overlapping.
     *
     * @param RangeInterface<T, Y> $range The range to compare with
     * @return bool True if the ranges are adjacent, false otherwise (including when the steps differ)
     * @throws InvalidArgumentException If the range is of a different type
     */
    public function isAdjacent(self $range): bool;

    /**
     * Subtracts another range from this range.
     *
     * @param RangeInterface<T, Y> $range The range to subtract
     * @return array<RangeInterface<T, Y>>|null Zero, one or two remaining ranges, or null if the steps differ
     * @throws InvalidArgumentException If the range is of a different type
     */
    public function difference(self $range): ?array;

    /**
     * Returns the range between this range and another disjoint range.
     *
     * @param RangeInterface<T, Y> $range The range to measure the gap with
     * @return RangeInterface<T, Y>|null The gap, or null if the ranges overlap, are adjacent, or the steps differ
     * @throws InvalidArgumentException If the range is of a different type
     */
    public function gap(self $range): ?self;

    /**
     * Generates a series of values in the range.
     *
     * @return array<T> The series of values
     * @throws CantGenerateSeriesBecauseTheArrayIsTooLarge If the series is too large to generate
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
     * Brings a value back within the effective bounds of the range.
     *
     * @param T $value The value to clamp
     * @return T The value itself if inside the range, the nearest effective bound otherwise
     * @throws InvalidArgumentException If the value is invalid or the range is empty
     */
    public function clamp(mixed $value): mixed;

    /**
     * Creates a new range widened by the given amount on both sides.
     *
     * @param Y $amount The amount to widen each bound by (must be positive)
     * @return RangeInterface<T, Y> The expanded range
     * @throws InvalidArgumentException If the amount is invalid
     */
    public function expand(mixed $amount): self;

    /**
     * Creates a new range narrowed by the given amount on both sides.
     *
     * @param Y $amount The amount to narrow each bound by (must be positive)
     * @return RangeInterface<T, Y> The shrunk range
     * @throws InvalidArgumentException If the amount is invalid
     * @throws InvalidBoundException If the resulting bounds are invalid
     */
    public function shrink(mixed $amount): self;

    /**
     * Picks a random value from the range, aligned on the step.
     *
     * @return T A random value of the range series
     * @throws InvalidArgumentException If the range is empty or has an infinite bound
     */
    public function random(): mixed;

    /**
     * Lazily iterates over the values of the range, one step at a time.
     *
     * Unlike generateSeries(), the values are never materialized in an array.
     * Support for infinite bounds is type-specific: see each implementation.
     *
     * @return Generator<int, T> The values of the range
     * @throws InvalidArgumentException If the range has an unsupported infinite bound
     */
    public function iterate(): Generator;

    /**
     * Splits the range into consecutive sub-ranges of at most $count values each.
     *
     * @param int $count The maximum number of values per chunk
     * @return array<RangeInterface<T, Y>> The chunks, in order
     * @throws InvalidArgumentException If the count is not positive or a bound is infinite
     */
    public function chunk(int $count): array;

    /**
     * Gets the step value used for generating series and calculating length.
     *
     * @return Y The step value
     */
    public function getStep(): mixed;
}
