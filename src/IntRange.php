<?php

declare(strict_types=1);

namespace Ciloe\Ranges;

use Ciloe\Ranges\Exception\CantGenerateSeriesBecauseTheArrayIsTooLarge;
use Ciloe\Ranges\Exception\InvalidBoundException;
use Ciloe\Ranges\Exception\InvalidInfiniteBoundException;
use Ciloe\Ranges\Exception\InvalidStepToGenerateSeriesException;
use Exception;
use InvalidArgumentException;

class IntRange
{
    public function __construct(
        readonly public ?int $lower = null,
        readonly public ?int $upper = null,
        readonly public string $lowerBound = '(',
        readonly public string $upperBound = ')',
        public int $step = 1,
    ) {
        if ($step <= 0) {
            throw new InvalidArgumentException('Step must be positive');
        }
    }

    public function __toString(): string
    {
        $lowerValue = $this->lower === null ? '' : $this->lower;
        $upperValue = $this->upper === null ? '' : $this->upper;

        return $this->lowerBound . $lowerValue . ',' . $upperValue . $this->upperBound;
    }

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

    public function isEmpty(): bool
    {
        return $this->lower === $this->upper && ($this->lowerBound === '(' || $this->upperBound === ')') && $this->lower !== null;
    }

    public function isBoundsValid(): bool
    {
        return ($this->getLowerBoundValue() ?? PHP_INT_MIN) <= ($this->getUpperBoundValue() ?? PHP_INT_MAX);
    }

    public function getLowerBoundValue(): ?int
    {
        return $this->lower === null ? null : ($this->lowerBound === '[' ? $this->lower : $this->lower + 1);
    }

    public function getUpperBoundValue(): ?int
    {
        return $this->upper === null ? null : ($this->upperBound === ']' ? $this->upper : $this->upper - 1);
    }

    public function contains(int $int): bool
    {
        $lower = $this->getLowerBoundValue() ?? (int) PHP_INT_MIN;
        $upper = $this->getUpperBoundValue() ?? (int) PHP_INT_MAX;

        return $lower <= $int && $int <= $upper;
    }

    public function overlap(self $range): bool
    {
        if ($this->isEmpty() || $range->isEmpty()) {
            return false;
        }

        $a1 = $this->getLowerBoundValue() ?? PHP_INT_MIN;
        $a2 = $this->getUpperBoundValue() ?? PHP_INT_MAX;
        $b1 = $range->getLowerBoundValue() ?? PHP_INT_MIN;
        $b2 = $range->getUpperBoundValue() ?? PHP_INT_MAX;

        return $a2 >= $b1 && $b2 >= $a1;
    }

    public function length(): ?int
    {
        $lower = $this->getLowerBoundValue();
        $upper = $this->getUpperBoundValue();

        if ($lower === null || $upper === null) {
            return null;
        }

        $diff = ($upper - $lower);
        $includeUpper = $diff % $this->step === 0;

        $length = (int) ceil(($upper - $lower) / $this->step) + ($includeUpper ? 1 : 0);

        return max($length, 0);
    }

    public function union(self $range): ?self
    {
        if ($this->step !== $range->step) {
            return null;
        }

        $lower = min($this->getLowerBoundValue(), $range->getLowerBoundValue());
        $upper = $this->getUpperBoundValue() === null || $range->getUpperBoundValue() === null ? null : max($this->getUpperBoundValue(), $range->getUpperBoundValue());

        return new self($lower, $upper, '[', ']');
    }

    public function intersection(self $range): ?self
    {
        if ($this->step !== $range->step) {
            return null;
        }

        $lower = max($this->getLowerBoundValue() ?? PHP_INT_MIN, $range->getLowerBoundValue() ?? PHP_INT_MIN);
        $upper = min($this->getUpperBoundValue() ?? PHP_INT_MAX, $range->getUpperBoundValue() ?? PHP_INT_MAX);

        if (($lower ?? PHP_INT_MIN) > ($upper ?? PHP_INT_MAX)) {
            return null;
        }

        return new self($lower === PHP_INT_MIN ? null : $lower, $upper === PHP_INT_MAX ? null : $upper, '[', ']');
    }

    /**
     * @return int[]
     */
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

        if ($upper !== $lower && ($upper - $lower) < $this->step) {
            throw new InvalidStepToGenerateSeriesException();
        }

        try {
            return range($lower, $upper, $this->step);
        } catch (Exception $e) {
            throw new CantGenerateSeriesBecauseTheArrayIsTooLarge($e);
        }
    }

    public function equals(self $range): bool
    {
        return $this->getLowerBoundValue() === $range->getLowerBoundValue() &&
               $this->getUpperBoundValue() === $range->getUpperBoundValue() &&
               $this->step === $range->step;
    }

    /**
     * @return array<IntRange>
     */
    public function split(int $point): array
    {
        if (! $this->contains($point)) {
            return [$this];
        }

        $leftRange = new self(
            $this->lower,
            $point,
            $this->lowerBound,
            ')',
            $this->step
        );

        $rightRange = new self(
            $point,
            $this->upper,
            '[',
            $this->upperBound,
            $this->step
        );

        return [$leftRange, $rightRange];
    }

    public function clone(): self
    {
        return new self(
            $this->lower,
            $this->upper,
            $this->lowerBound,
            $this->upperBound,
            $this->step
        );
    }

    public function shift(int $offset): self
    {
        $newLower = $this->lower === null ? null : $this->lower + $offset;
        $newUpper = $this->upper === null ? null : $this->upper + $offset;

        return new self(
            $newLower,
            $newUpper,
            $this->lowerBound,
            $this->upperBound,
            $this->step
        );
    }

    public function scale(int $factor): self
    {
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
            $this->step * abs($factor)
        );
    }
}
