<?php

declare(strict_types=1);

namespace Ciloe\Ranges;

use Ciloe\Ranges\Exception\CantGenerateSeriesBecauseTheArrayIsTooLarge;
use Ciloe\Ranges\Exception\InvalidBoundException;
use Ciloe\Ranges\Exception\InvalidInfiniteBoundException;
use Exception;
use Generator;
use InvalidArgumentException;
use Override;

/**
 * @implements RangeInterface<int, int>
 */
readonly class IntRange implements RangeInterface
{
    public function __construct(
        public ?int $lower = null,
        public ?int $upper = null,
        public string $lowerBound = '(',
        public string $upperBound = ')',
        public int $step = 1,
    ) {
        if ($step <= 0) {
            throw new InvalidArgumentException('Step must be positive');
        }
    }

    #[Override]
    public function __toString(): string
    {
        $lowerValue = $this->lower === null ? '' : $this->lower;
        $upperValue = $this->upper === null ? '' : $this->upper;

        return $this->lowerBound . $lowerValue . ',' . $upperValue . $this->upperBound;
    }

    #[Override]
    public static function fromString(string $range): self
    {
        if (! preg_match('/^(\[|\()(-?\d+|null)?,(-?\d+|null)?(\]|\))$/', $range, $matches)) {
            throw new InvalidArgumentException('Invalid range format');
        }

        $lowerBound = $matches[1];
        $lower = $matches[2] === 'null' || $matches[2] === '' ? null : (int) $matches[2];
        $upper = $matches[3] === 'null' || $matches[3] === '' ? null : (int) $matches[3];
        $upperBound = $matches[4];

        if (($lower === null && $lowerBound === '[') || ($upper === null && $upperBound === ']')) {
            throw new InvalidInfiniteBoundException();
        }

        $range = new self($lower, $upper, $lowerBound, $upperBound);

        if (! $range->isBoundsValid()) {
            throw new InvalidBoundException();
        }

        return $range;
    }

    #[Override]
    public function isEmpty(): bool
    {
        return $this->lower === $this->upper &&
            $this->lowerBound === '(' && $this->upperBound === ')' &&
            $this->lower !== null;
    }

    #[Override]
    public function isBoundsValid(): bool
    {
        return ($this->getLowerBoundValue() ?? PHP_INT_MIN) <= ($this->getUpperBoundValue() ?? PHP_INT_MAX);
    }

    #[Override]
    public function getLowerBoundValue(): ?int
    {
        if ($this->lower === null) {
            return null;
        }

        return $this->lowerBound === '[' ? $this->lower : $this->lower + $this->getStep();
    }

    #[Override]
    public function getUpperBoundValue(): ?int
    {
        if ($this->upper === null) {
            return null;
        }

        return $this->upperBound === ']' ? $this->upper : $this->upper - $this->getStep();
    }

    /**
     * @param int $value
     */
    #[Override]
    public function contains(mixed $value): bool
    {
        if (! is_int($value)) {
            throw new InvalidArgumentException('Value must be an integer');
        }

        if ($this->isEmpty()) {
            return false;
        }

        $lower = $this->getLowerBoundValue() ?? PHP_INT_MIN;
        $upper = $this->getUpperBoundValue() ?? PHP_INT_MAX;

        return $lower <= $value && $value <= $upper;
    }

    #[Override]
    public function overlap(RangeInterface $range): bool
    {
        if (! $range instanceof self) {
            throw new InvalidArgumentException('Range must be an instance of IntRange');
        }

        if ($this->isEmpty() || $range->isEmpty()) {
            return false;
        }

        $a1 = $this->getLowerBoundValue() ?? PHP_INT_MIN;
        $a2 = $this->getUpperBoundValue() ?? PHP_INT_MAX;
        $b1 = $range->getLowerBoundValue() ?? PHP_INT_MIN;
        $b2 = $range->getUpperBoundValue() ?? PHP_INT_MAX;

        return $a2 >= $b1 && $b2 >= $a1;
    }

    #[Override]
    public function length(): ?int
    {
        $lower = $this->getLowerBoundValue();
        $upper = $this->getUpperBoundValue();

        if ($lower === null || $upper === null) {
            return null;
        }

        $diff = ($upper - $lower);
        $includeUpper = $diff % $this->getStep() === 0;

        $length = (int) ceil(($upper - $lower) / $this->getStep()) + ($includeUpper ? 1 : 0);

        return max($length, 0);
    }

    #[Override]
    public function union(RangeInterface $range): ?self
    {
        if (! $range instanceof self) {
            throw new InvalidArgumentException('Range must be an instance of IntRange');
        }

        if ($this->getStep() !== $range->getStep()) {
            return null;
        }

        $lower = min($this->getLowerBoundValue(), $range->getLowerBoundValue());
        $upper = ($this->getUpperBoundValue() === null || $range->getUpperBoundValue() === null) ?
            null :
            max($this->getUpperBoundValue(), $range->getUpperBoundValue());

        return new self($lower, $upper, $lower === null ? '(' : '[', $upper === null ? ')' : ']', $this->getStep());
    }

    #[Override]
    public function intersection(RangeInterface $range): ?self
    {
        if (! $range instanceof self) {
            throw new InvalidArgumentException('Range must be an instance of IntRange');
        }

        if ($this->getStep() !== $range->getStep()) {
            return null;
        }

        $lower = max($this->getLowerBoundValue() ?? PHP_INT_MIN, $range->getLowerBoundValue() ?? PHP_INT_MIN);
        $upper = min($this->getUpperBoundValue() ?? PHP_INT_MAX, $range->getUpperBoundValue() ?? PHP_INT_MAX);

        if ($lower > $upper) {
            return null;
        }

        $lowerValue = $lower === PHP_INT_MIN ? null : $lower;
        $upperValue = $upper === PHP_INT_MAX ? null : $upper;

        return new self(
            $lowerValue,
            $upperValue,
            $lowerValue === null ? '(' : '[',
            $upperValue === null ? ')' : ']',
            $this->getStep()
        );
    }

    #[Override]
    public function containsRange(RangeInterface $range): bool
    {
        if (! $range instanceof self) {
            throw new InvalidArgumentException('Range must be an instance of IntRange');
        }

        if ($range->isEmpty()) {
            return true;
        }

        if ($this->isEmpty()) {
            return false;
        }

        $lowerCheck = $this->getLowerBoundValue() === null ||
            ($range->getLowerBoundValue() !== null && $range->getLowerBoundValue() >= $this->getLowerBoundValue());
        $upperCheck = $this->getUpperBoundValue() === null ||
            ($range->getUpperBoundValue() !== null && $range->getUpperBoundValue() <= $this->getUpperBoundValue());

        return $lowerCheck && $upperCheck;
    }

    #[Override]
    public function isBefore(RangeInterface $range): bool
    {
        if (! $range instanceof self) {
            throw new InvalidArgumentException('Range must be an instance of IntRange');
        }

        if ($this->isEmpty() || $range->isEmpty()) {
            return false;
        }

        $upper = $this->getUpperBoundValue();
        $lower = $range->getLowerBoundValue();

        return $upper !== null && $lower !== null && $upper < $lower;
    }

    #[Override]
    public function isAfter(RangeInterface $range): bool
    {
        if (! $range instanceof self) {
            throw new InvalidArgumentException('Range must be an instance of IntRange');
        }

        return $range->isBefore($this);
    }

    #[Override]
    public function isAdjacent(RangeInterface $range): bool
    {
        if (! $range instanceof self) {
            throw new InvalidArgumentException('Range must be an instance of IntRange');
        }

        if ($this->getStep() !== $range->getStep()) {
            return false;
        }

        if ($this->isEmpty() || $range->isEmpty()) {
            return false;
        }

        $thisLower = $this->getLowerBoundValue();
        $thisUpper = $this->getUpperBoundValue();
        $rangeLower = $range->getLowerBoundValue();
        $rangeUpper = $range->getUpperBoundValue();

        return ($thisUpper !== null && $rangeLower !== null && $thisUpper + $this->getStep() === $rangeLower) ||
            ($rangeUpper !== null && $thisLower !== null && $rangeUpper + $this->getStep() === $thisLower);
    }

    /**
     * @return array<IntRange>|null
     */
    #[Override]
    public function difference(RangeInterface $range): ?array
    {
        if (! $range instanceof self) {
            throw new InvalidArgumentException('Range must be an instance of IntRange');
        }

        if ($this->getStep() !== $range->getStep()) {
            return null;
        }

        if ($this->isEmpty()) {
            return [];
        }

        if ($range->isEmpty() || ! $this->overlap($range)) {
            return [$this->clone()];
        }

        $thisLower = $this->getLowerBoundValue();
        $thisUpper = $this->getUpperBoundValue();
        $rangeLower = $range->getLowerBoundValue();
        $rangeUpper = $range->getUpperBoundValue();

        $parts = [];

        if ($rangeLower !== null && ($thisLower === null || $thisLower < $rangeLower)) {
            $parts[] = new self(
                $thisLower,
                $rangeLower - $this->getStep(),
                $thisLower === null ? '(' : '[',
                ']',
                $this->getStep()
            );
        }

        if ($rangeUpper !== null && ($thisUpper === null || $thisUpper > $rangeUpper)) {
            $parts[] = new self(
                $rangeUpper + $this->getStep(),
                $thisUpper,
                '[',
                $thisUpper === null ? ')' : ']',
                $this->getStep()
            );
        }

        return $parts;
    }

    #[Override]
    public function gap(RangeInterface $range): ?self
    {
        if (! $range instanceof self) {
            throw new InvalidArgumentException('Range must be an instance of IntRange');
        }

        if ($this->getStep() !== $range->getStep()) {
            return null;
        }

        if ($this->overlap($range) || $this->isAdjacent($range)) {
            return null;
        }

        if ($this->isBefore($range)) {
            [$left, $right] = [$this, $range];
        } elseif ($range->isBefore($this)) {
            [$left, $right] = [$range, $this];
        } else {
            return null;
        }

        $leftUpper = $left->getUpperBoundValue();
        $rightLower = $right->getLowerBoundValue();

        if ($leftUpper === null || $rightLower === null) {
            return null;
        }

        return new self($leftUpper + $this->getStep(), $rightLower - $this->getStep(), '[', ']', $this->getStep());
    }

    /**
     * @return int[]
     */
    #[Override]
    public function generateSeries(): array
    {
        if ($this->isEmpty()) {
            return [];
        }

        $lower = $this->getLowerBoundValue();
        $upper = $this->getUpperBoundValue();

        if ($lower === null || $upper === null) {
            throw new CantGenerateSeriesBecauseTheArrayIsTooLarge();
        }

        if ($lower > $upper) {
            return [];
        }

        // Native range() rejects a step larger than the span; the series is then just [$lower]
        if (($upper - $lower) < $this->getStep()) {
            return [$lower];
        }

        try {
            return range($lower, $upper, $this->getStep());
        } catch (Exception $e) {
            throw new CantGenerateSeriesBecauseTheArrayIsTooLarge($e);
        }
    }

    /**
     * @param int $value
     */
    #[Override]
    public function clamp(mixed $value): int
    {
        if (! is_int($value)) {
            throw new InvalidArgumentException('Value must be an integer');
        }

        if ($this->isEmpty()) {
            throw new InvalidArgumentException('Cannot clamp a value on an empty range');
        }

        $lower = $this->getLowerBoundValue();
        $upper = $this->getUpperBoundValue();

        if ($lower !== null && $value < $lower) {
            return $lower;
        }

        if ($upper !== null && $value > $upper) {
            return $upper;
        }

        return $value;
    }

    /**
     * @param int $amount
     */
    #[Override]
    public function expand(mixed $amount): self
    {
        $this->validateAmount($amount);

        return new self(
            $this->lower === null ? null : $this->lower - $amount,
            $this->upper === null ? null : $this->upper + $amount,
            $this->lowerBound,
            $this->upperBound,
            $this->getStep()
        );
    }

    /**
     * @param int $amount
     */
    #[Override]
    public function shrink(mixed $amount): self
    {
        $this->validateAmount($amount);

        $shrunk = new self(
            $this->lower === null ? null : $this->lower + $amount,
            $this->upper === null ? null : $this->upper - $amount,
            $this->lowerBound,
            $this->upperBound,
            $this->getStep()
        );

        if (! $shrunk->isBoundsValid()) {
            throw new InvalidBoundException();
        }

        return $shrunk;
    }

    #[Override]
    public function random(): int
    {
        if ($this->isEmpty()) {
            throw new InvalidArgumentException('Cannot pick a random value from an empty range');
        }

        $lower = $this->getLowerBoundValue();
        $count = $this->length();

        if ($lower === null || $count === null) {
            throw new InvalidArgumentException('Cannot pick a random value from an infinite range');
        }

        return $lower + random_int(0, $count - 1) * $this->getStep();
    }

    /**
     * The generator stops at the upper bound, or at PHP_INT_MAX when the upper bound is infinite.
     *
     * @return Generator<int, int>
     */
    #[Override]
    public function iterate(): Generator
    {
        if (! $this->isEmpty() && $this->getLowerBoundValue() === null) {
            throw new InvalidArgumentException('Cannot iterate over a range with an infinite lower bound');
        }

        return $this->iterateValues();
    }

    /**
     * @return array<IntRange>
     */
    #[Override]
    public function chunk(int $count): array
    {
        if ($count <= 0) {
            throw new InvalidArgumentException('Chunk size must be positive');
        }

        if ($this->isEmpty()) {
            return [];
        }

        $lower = $this->getLowerBoundValue();
        $upper = $this->getUpperBoundValue();

        if ($lower === null || $upper === null) {
            throw new InvalidArgumentException('Cannot chunk a range with an infinite bound');
        }

        $chunks = [];
        $start = $lower;

        while ($start <= $upper) {
            $end = min($start + ($count - 1) * $this->getStep(), $upper);
            $chunks[] = new self($start, $end, '[', ']', $this->getStep());

            if ($end > PHP_INT_MAX - $this->getStep()) {
                break;
            }

            $start = $end + $this->getStep();
        }

        return $chunks;
    }

    #[Override]
    public function equals(RangeInterface $range): bool
    {
        if (! $range instanceof self) {
            throw new InvalidArgumentException('Range must be an instance of IntRange');
        }

        return $this->getLowerBoundValue() === $range->getLowerBoundValue() &&
               $this->getUpperBoundValue() === $range->getUpperBoundValue() &&
               $this->getStep() === $range->getStep();
    }

    /**
     * @param int $point
     * @return array<IntRange>
     */
    #[Override]
    public function split($point): array
    {
        if (! is_int($point)) {
            throw new InvalidArgumentException('Split point must be an integer');
        }

        if (! $this->contains($point)) {
            return [$this];
        }

        $leftRange = new self(
            $this->lower,
            $point,
            $this->lowerBound,
            ')',
            $this->getStep()
        );

        $rightRange = new self(
            $point,
            $this->upper,
            '[',
            $this->upperBound,
            $this->getStep()
        );

        return [$leftRange, $rightRange];
    }

    #[Override]
    public function clone(): self
    {
        return new self(
            $this->lower,
            $this->upper,
            $this->lowerBound,
            $this->upperBound,
            $this->getStep()
        );
    }

    /**
     * @param int $offset
     */
    #[Override]
    public function shift($offset): self
    {
        $newLower = $this->lower === null ? null : $this->lower + $offset;
        $newUpper = $this->upper === null ? null : $this->upper + $offset;

        return new self(
            $newLower,
            $newUpper,
            $this->lowerBound,
            $this->upperBound,
            $this->getStep()
        );
    }

    /**
     * @param int $factor
     */
    #[Override]
    public function scale($factor): self
    {
        if (! is_int($factor)) {
            throw new InvalidArgumentException('Factor must be an integer');
        }

        if ($factor === 0) {
            throw new InvalidArgumentException('Scale factor cannot be zero');
        }

        $newLower = $this->lower === null ? null : $this->lower * $factor;
        $newUpper = $this->upper === null ? null : $this->upper * $factor;

        $lowerBound = $this->lowerBound;
        $upperBound = $this->upperBound;

        if ($factor < 0) {
            $tempValue = $newLower;
            $newLower = $newUpper;
            $newUpper = $tempValue;

            $lowerBound = $this->upperBound === ']' ? '[' : '(';
            $upperBound = $this->lowerBound === '[' ? ']' : ')';
        }

        return new self(
            $newLower,
            $newUpper,
            $lowerBound,
            $upperBound,
            $this->getStep() * abs($factor)
        );
    }

    #[Override]
    public function getStep(): int
    {
        return $this->step;
    }

    private function validateAmount(mixed $amount): void
    {
        if (! is_int($amount)) {
            throw new InvalidArgumentException('Amount must be an integer');
        }

        if ($amount < 0) {
            throw new InvalidArgumentException('Amount must be positive');
        }
    }

    /**
     * @return Generator<int, int>
     */
    private function iterateValues(): Generator
    {
        if ($this->isEmpty()) {
            return;
        }

        $lower = $this->getLowerBoundValue();
        $upper = $this->getUpperBoundValue() ?? PHP_INT_MAX;

        if ($lower === null) {
            return;
        }

        $current = $lower;

        while ($current <= $upper) {
            yield $current;

            if ($current > PHP_INT_MAX - $this->getStep()) {
                break;
            }

            $current += $this->getStep();
        }
    }
}
