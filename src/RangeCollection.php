<?php

declare(strict_types=1);

namespace Ciloe\Ranges;

use InvalidArgumentException;

/**
 * An immutable collection of ranges of the same type and step.
 *
 * @template T
 * @template Y
 */
final readonly class RangeCollection
{
    /**
     * @var list<RangeInterface<T, Y>>
     */
    private array $ranges;

    /**
     * @param RangeInterface<T, Y> ...$ranges
     * @throws InvalidArgumentException If the ranges are not all of the same type and step
     */
    public function __construct(RangeInterface ...$ranges)
    {
        $ranges = array_values($ranges);
        $first = $ranges[0] ?? null;

        foreach ($ranges as $range) {
            if ($first === null) {
                continue;
            }

            if ($range::class !== $first::class) {
                throw new InvalidArgumentException('All ranges must be of the same type');
            }

            if ($first->union($range) === null) {
                throw new InvalidArgumentException('All ranges must share the same step');
            }
        }

        $this->ranges = $ranges;
    }

    /**
     * @param RangeInterface<T, Y> $range
     * @return RangeCollection<T, Y>
     */
    public function add(RangeInterface $range): self
    {
        return new self(...[...$this->ranges, $range]);
    }

    /**
     * @return list<RangeInterface<T, Y>>
     */
    public function getRanges(): array
    {
        return $this->ranges;
    }

    public function count(): int
    {
        return count($this->ranges);
    }

    public function isEmpty(): bool
    {
        return $this->ranges === [];
    }

    /**
     * Normalizes the collection into sorted, disjoint, non-adjacent ranges.
     *
     * Overlapping and adjacent ranges are unioned; empty ranges are dropped.
     *
     * @return RangeCollection<T, Y>
     */
    public function merge(): self
    {
        $ranges = array_values(array_filter($this->ranges, fn (RangeInterface $range): bool => ! $range->isEmpty()));

        do {
            $mergedSomething = false;

            foreach ($ranges as $i => $range) {
                foreach ($ranges as $j => $other) {
                    if ($j <= $i) {
                        continue;
                    }

                    if ($range->overlap($other) || $range->isAdjacent($other)) {
                        $union = $range->union($other);

                        if ($union === null) {
                            continue;
                        }

                        $ranges[$i] = $union;
                        unset($ranges[$j]);
                        $ranges = array_values($ranges);
                        $mergedSomething = true;

                        break 2;
                    }
                }
            }
        } while ($mergedSomething);

        usort(
            $ranges,
            fn (RangeInterface $a, RangeInterface $b): int => $a->isBefore($b) ? -1 : 1
        );

        return new self(...$ranges);
    }

    /**
     * Returns the gaps between the merged ranges of the collection.
     *
     * @return RangeCollection<T, Y>
     */
    public function gaps(): self
    {
        $merged = $this->merge()->getRanges();
        $gaps = [];

        foreach ($merged as $i => $range) {
            if (! isset($merged[$i + 1])) {
                break;
            }

            $gap = $range->gap($merged[$i + 1]);

            if ($gap !== null) {
                $gaps[] = $gap;
            }
        }

        return new self(...$gaps);
    }

    /**
     * Checks if any range of the collection contains the value.
     *
     * @param T $value
     * @throws InvalidArgumentException If the value is invalid for the range type
     */
    public function contains(mixed $value): bool
    {
        foreach ($this->ranges as $range) {
            if ($range->contains($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sums the lengths of the merged ranges.
     *
     * @return int|string|null The total length, or null if the collection is empty or a range is infinite
     */
    public function totalLength(): int|string|null
    {
        $total = null;

        foreach ($this->merge()->getRanges() as $range) {
            $length = $range->length();

            if ($length === null) {
                return null;
            }

            if ($total === null) {
                $total = $length;
            } elseif (is_string($total) && is_string($length)) {
                $total = bcadd($total, $length);
            } elseif (is_int($total) && is_int($length)) {
                $total += $length;
            }
        }

        return is_int($total) || is_string($total) ? $total : null;
    }
}
