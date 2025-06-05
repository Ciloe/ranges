<?php

declare(strict_types=1);

namespace Ciloe\Ranges;

use Ciloe\Ranges\Exception\CantGenerateSeriesBecauseTheArrayIsTooLarge;
use Ciloe\Ranges\Exception\InvalidBoundException;
use Ciloe\Ranges\Exception\InvalidInfiniteBoundException;
use Ciloe\Ranges\Exception\InvalidStepToGenerateSeriesException;
use Exception;
use InvalidArgumentException;

/**
 * @implements RangeInterface<string, string>
 */
class BigIntRange implements RangeInterface
{
    public function __construct(
        readonly public ?string $lower = null,
        readonly public ?string $upper = null,
        readonly public string $lowerBound = '(',
        readonly public string $upperBound = ')',
        public string $step = '1',
    ) {
        if ($lower !== null && ! is_numeric($lower)) {
            throw new InvalidArgumentException('Lower bound must be a valid numeric string');
        }
        if ($upper !== null && ! is_numeric($upper)) {
            throw new InvalidArgumentException('Upper bound must be a valid numeric string');
        }
        if (! is_numeric($step)) {
            throw new InvalidArgumentException('Step must be a valid numeric string');
        }
        if (bccomp($step, '0') <= 0) {
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
        $lower = $matches[2] === 'null' || $matches[2] === '' ? null : $matches[2];
        $upper = $matches[3] === 'null' || $matches[3] === '' ? null : $matches[3];
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
        return $this->lower === $this->upper &&
            ($this->lowerBound === '(' || $this->upperBound === ')') &&
            $this->lower !== null;
    }

    public function isBoundsValid(): bool
    {
        $lowerValue = $this->getLowerBoundValue();
        $upperValue = $this->getUpperBoundValue();

        if ($lowerValue === null || $upperValue === null) {
            return true;
        }

        return bccomp($lowerValue, $upperValue) <= 0;
    }

    public function getLowerBoundValue(): ?string
    {
        if ($this->lower === null) {
            return null;
        }

        return $this->lowerBound === '[' ? $this->lower : bcadd($this->lower, '1');
    }

    public function getUpperBoundValue(): ?string
    {
        if ($this->upper === null) {
            return null;
        }

        return $this->upperBound === ']' ? $this->upper : bcsub($this->upper, '1');
    }

    /**
     * @param string $value
     */
    public function contains(mixed $value): bool
    {
        if (! is_numeric($value)) {
            throw new InvalidArgumentException('Value must be a valid numeric string');
        }

        if ($this->isEmpty()) {
            return false;
        }

        $lower = $this->getLowerBoundValue();
        $upper = $this->getUpperBoundValue();

        $lowerCheck = $lower === null || bccomp($value, $lower) >= 0;
        $upperCheck = $upper === null || bccomp($value, $upper) <= 0;

        return $lowerCheck && $upperCheck;
    }

    public function overlap(RangeInterface $range): bool
    {
        if (! $range instanceof self) {
            throw new InvalidArgumentException('Range must be an instance of BigIntRange');
        }

        if ($this->isEmpty() || $range->isEmpty()) {
            return false;
        }

        $a1 = $this->getLowerBoundValue();
        $a2 = $this->getUpperBoundValue();
        $b1 = $range->getLowerBoundValue();
        $b2 = $range->getUpperBoundValue();

        if ($a1 === null || $a2 === null || $b1 === null || $b2 === null) {
            return true;
        }

        return bccomp($a2, $b1) >= 0 && bccomp($b2, $a1) >= 0;
    }

    public function length(): ?string
    {
        $lower = $this->getLowerBoundValue();
        $upper = $this->getUpperBoundValue();

        if ($lower === null || $upper === null) {
            return null;
        }

        if (bccomp($lower, $upper) > 0) {
            return '0';
        }

        $diff = bcsub($upper, $lower);

        $includeUpper = bcmod($diff, $this->getStep()) === '0' ? '1' : '0';

        $length = bcadd(bcdiv($diff, $this->getStep(), 0), $includeUpper);

        return $length;
    }

    public function union(RangeInterface $range): ?self
    {
        if (! $range instanceof self) {
            throw new InvalidArgumentException('Range must be an instance of BigIntRange');
        }

        if (bccomp($this->getStep(), $range->getStep()) !== 0) {
            return null;
        }

        $thisLower = $this->getLowerBoundValue();
        $rangeLower = $range->getLowerBoundValue();
        $thisUpper = $this->getUpperBoundValue();
        $rangeUpper = $range->getUpperBoundValue();

        if ($thisLower === null || $rangeLower === null) {
            $lower = null;
        } else {
            $lower = bccomp($thisLower, $rangeLower) <= 0 ? $thisLower : $rangeLower;
        }

        if ($thisUpper === null || $rangeUpper === null) {
            $upper = null;
        } else {
            $upper = bccomp($thisUpper, $rangeUpper) >= 0 ? $thisUpper : $rangeUpper;
        }

        return new self($lower, $upper, '[', ']', $this->getStep());
    }

    public function intersection(RangeInterface $range): ?self
    {
        if (! $range instanceof self) {
            throw new InvalidArgumentException('Range must be an instance of BigIntRange');
        }

        if (bccomp($this->getStep(), $range->getStep()) !== 0) {
            return null;
        }

        $thisLower = $this->getLowerBoundValue();
        $rangeLower = $range->getLowerBoundValue();
        $thisUpper = $this->getUpperBoundValue();
        $rangeUpper = $range->getUpperBoundValue();

        if ($thisLower === null) {
            $lower = $rangeLower;
        } elseif ($rangeLower === null) {
            $lower = $thisLower;
        } else {
            $lower = bccomp($thisLower, $rangeLower) >= 0 ? $thisLower : $rangeLower;
        }

        if ($thisUpper === null) {
            $upper = $rangeUpper;
        } elseif ($rangeUpper === null) {
            $upper = $thisUpper;
        } else {
            $upper = bccomp($thisUpper, $rangeUpper) <= 0 ? $thisUpper : $rangeUpper;
        }

        if ($lower !== null && $upper !== null && bccomp($lower, $upper) > 0) {
            return null;
        }

        return new self($lower, $upper, '[', ']', $this->getStep());
    }

    /**
     * @return string[]
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

        if (bccomp($lower, $upper) > 0) {
            return [];
        }

        if (bccomp($upper, $lower) !== 0 && bccomp(bcsub($upper, $lower), $this->getStep()) < 0) {
            throw new InvalidStepToGenerateSeriesException();
        }

        $estimatedSize = min(1000000, (int) bcadd(bcdiv(bcsub($upper, $lower), $this->getStep(), 0), '1'));
        $series = [];
        $series = array_pad($series, $estimatedSize, null);

        try {
            $count = 0;
            $current = $lower;

            while (bccomp($current, $upper) <= 0) {
                $series[$count++] = $current;
                $current = bcadd($current, $this->getStep());

                if ($count > 1000000) {
                    throw new CantGenerateSeriesBecauseTheArrayIsTooLarge();
                }
            }

            return array_slice($series, 0, $count);
        } catch (Exception $e) {
            throw new CantGenerateSeriesBecauseTheArrayIsTooLarge($e);
        }
    }

    public function equals(RangeInterface $range): bool
    {
        if (! $range instanceof self) {
            throw new InvalidArgumentException('Range must be an instance of BigIntRange');
        }

        return $this->getLowerBoundValue() === $range->getLowerBoundValue() &&
               $this->getUpperBoundValue() === $range->getUpperBoundValue() &&
               $this->getStep() === $range->getStep();
    }

    /**
     * @param string $point
     * @return array<BigIntRange>
     */
    public function split($point): array
    {
        if (! is_numeric($point)) {
            throw new InvalidArgumentException('Split point must be a valid numeric string');
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
     * @param string $offset
     */
    public function shift($offset): self
    {
        if (! is_numeric($offset)) {
            throw new InvalidArgumentException('Offset must be a valid numeric string');
        }

        $newLower = $this->lower === null ? null : bcadd($this->lower, $offset);
        $newUpper = $this->upper === null ? null : bcadd($this->upper, $offset);

        return new self(
            $newLower,
            $newUpper,
            $this->lowerBound,
            $this->upperBound,
            $this->getStep()
        );
    }

    /**
     * @param string $factor
     */
    public function scale($factor): self
    {
        if (! is_numeric($factor)) {
            throw new InvalidArgumentException('Factor must be a valid numeric string');
        }

        if (bccomp($factor, '0') === 0) {
            throw new InvalidArgumentException('Scale factor cannot be zero');
        }

        $newLower = $this->lower === null ? null : bcmul($this->lower, $factor);
        $newUpper = $this->upper === null ? null : bcmul($this->upper, $factor);

        $lowerBound = $this->lowerBound;
        $upperBound = $this->upperBound;

        if (bccomp($factor, '0') < 0) {
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
            bcmul($this->getStep(), str_replace('-', '', $factor))
        );
    }

    public function getStep(): string
    {
        return $this->step;
    }
}
