<?php

declare(strict_types=1);

namespace Ciloe\Ranges;

use Ciloe\Ranges\Exception\CantGenerateSeriesBecauseTheArrayIsTooLarge;
use Ciloe\Ranges\Exception\InvalidBoundException;
use Ciloe\Ranges\Exception\InvalidDateIntervalException;
use Ciloe\Ranges\Exception\InvalidInfiniteBoundException;
use DateInterval;
use DateMalformedStringException;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use Override;

/**
 * @implements RangeInterface<DateTimeImmutable, DateInterval>
 */
readonly class DateRange implements RangeInterface
{
    /**
     * @throws InvalidDateIntervalException
     */
    public function __construct(
        public ?DateTimeImmutable $lower = null,
        public ?DateTimeImmutable $upper = null,
        public string $lowerBound = '(',
        public string $upperBound = ')',
        public DateInterval $step = new DateInterval('P1D'),
    ) {
        $this->validateStep($step);
    }

    #[Override]
    public function __toString(): string
    {
        $lowerValue = $this->lower === null ? '' : $this->lower->format('Y-m-d');
        $upperValue = $this->upper === null ? '' : $this->upper->format('Y-m-d');

        return $this->lowerBound . $lowerValue . ',' . $upperValue . $this->upperBound;
    }

    #[Override]
    public static function fromString(string $range): self
    {
        $matchResult = \Safe\preg_match(
            '/^(\[|\()([0-9]{4}-[0-9]{2}-[0-9]{2}|null)?,([0-9]{4}-[0-9]{2}-[0-9]{2}|null)?(\]|\))$/',
            $range,
            $matches,
        );

        if ($matches === null || $matchResult === 0) {
            throw new InvalidArgumentException('Invalid range format');
        }

        list(, $lowerBound, $lowerStr, $upperStr, $upperBound) = $matches;

        try {
            $lower = ($lowerStr === 'null' || $lowerStr === '') ? null : new DateTimeImmutable($lowerStr);
            $upper = ($upperStr === 'null' || $upperStr === '') ? null : new DateTimeImmutable($upperStr);
        } catch (DateMalformedStringException $e) {
            throw new InvalidArgumentException('Invalid range format', 0, $e);
        }

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
        if ($this->lower === null || $this->upper === null) {
            return false;
        }

        return $this->lower == $this->upper &&
            $this->lowerBound === '(' && $this->upperBound === ')';
    }

    #[Override]
    public function isBoundsValid(): bool
    {
        $lower = $this->getLowerBoundValue();
        $upper = $this->getUpperBoundValue();

        if ($lower === null || $upper === null) {
            return true;
        }

        return $lower <= $upper;
    }

    #[Override]
    public function getLowerBoundValue(): ?DateTimeImmutable
    {
        if ($this->lower === null) {
            return null;
        }

        if ($this->lowerBound === '[') {
            return $this->lower;
        }

        return (clone $this->lower)->add($this->getStep());
    }

    #[Override]
    public function getUpperBoundValue(): ?DateTimeImmutable
    {
        if ($this->upper === null) {
            return null;
        }

        if ($this->upperBound === ']') {
            return $this->upper;
        }

        return (clone $this->upper)->sub($this->getStep());
    }

    /**
     * @param DateTimeInterface $value
     */
    #[Override]
    public function contains(mixed $value): bool
    {
        if (! $value instanceof DateTimeInterface) {
            throw new InvalidArgumentException('Value must be a DateTimeInterface instance');
        }

        if ($this->isEmpty()) {
            return false;
        }

        if ($value instanceof DateTime) {
            $value = DateTimeImmutable::createFromMutable($value);
        }

        $lower = $this->getLowerBoundValue();
        $upper = $this->getUpperBoundValue();

        if ($lower === null && $upper === null) {
            return true;
        }

        if ($lower === null) {
            return $this->compareDateOnly($value, $upper) <= 0;
        }

        if ($upper === null) {
            return $this->compareDateOnly($value, $lower) >= 0;
        }

        return $this->compareDateOnly($value, $lower) >= 0 && $this->compareDateOnly($value, $upper) <= 0;
    }

    #[Override]
    public function overlap(RangeInterface $range): bool
    {
        if (! $range instanceof self) {
            throw new InvalidArgumentException('Range must be an instance of DateRange');
        }

        if ($this->isEmpty() || $range->isEmpty()) {
            return false;
        }

        $a1 = $this->getLowerBoundValue();
        $a2 = $this->getUpperBoundValue();
        $b1 = $range->getLowerBoundValue();
        $b2 = $range->getUpperBoundValue();

        if ($a1 === null && $a2 === null) {
            return true;
        }
        if ($b1 === null && $b2 === null) {
            return true;
        }
        if ($a1 === null && $b2 === null) {
            return true;
        }
        if ($a2 === null && $b1 === null) {
            return true;
        }
        if ($a1 === null && $b1 === null) {
            return true;
        }
        if ($a2 === null && $b2 === null) {
            return true;
        }

        if ($a1 === null) {
            return $a2 >= $b1;
        }
        if ($a2 === null) {
            return $b2 >= $a1;
        }
        if ($b1 === null) {
            return $b2 >= $a1;
        }
        if ($b2 === null) {
            return $a2 >= $b1;
        }

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

        if ($lower > $upper) {
            return 0;
        }

        $diff = $lower->diff($upper);
        $days = $diff->days + 1;

        $stepDays = $this->getStepDays();
        $length = (int) ceil($days / $stepDays);

        return max($length, 0);
    }

    #[Override]
    public function union(RangeInterface $range): ?self
    {
        if (! $range instanceof self) {
            throw new InvalidArgumentException('Range must be an instance of DateRange');
        }

        if (! $this->isSameStepUnit($range)) {
            return null;
        }

        $thisLower = $this->getLowerBoundValue();
        $thisUpper = $this->getUpperBoundValue();
        $rangeLower = $range->getLowerBoundValue();
        $rangeUpper = $range->getUpperBoundValue();

        if ($thisLower === null || $rangeLower === null) {
            $lower = null;
        } else {
            $lower = $this->minDate($thisLower, $rangeLower);
        }

        if ($thisUpper === null || $rangeUpper === null) {
            $upper = null;
        } else {
            $upper = $this->maxDate($thisUpper, $rangeUpper);
        }

        return new self($lower, $upper, $lower === null ? '(' : '[', $upper === null ? ')' : ']', $this->getStep());
    }

    #[Override]
    public function intersection(RangeInterface $range): ?self
    {
        if (! $range instanceof self) {
            throw new InvalidArgumentException('Range must be an instance of DateRange');
        }

        if (! $this->isSameStepUnit($range)) {
            return null;
        }

        $thisLower = $this->getLowerBoundValue();
        $thisUpper = $this->getUpperBoundValue();
        $rangeLower = $range->getLowerBoundValue();
        $rangeUpper = $range->getUpperBoundValue();

        if ($thisLower === null) {
            $lower = $rangeLower;
        } elseif ($rangeLower === null) {
            $lower = $thisLower;
        } else {
            $lower = $this->maxDate($thisLower, $rangeLower);
        }

        if ($thisUpper === null) {
            $upper = $rangeUpper;
        } elseif ($rangeUpper === null) {
            $upper = $thisUpper;
        } else {
            $upper = $this->minDate($thisUpper, $rangeUpper);
        }

        if ($lower !== null && $upper !== null && $lower > $upper) {
            return null;
        }

        return new self($lower, $upper, $lower === null ? '(' : '[', $upper === null ? ')' : ']', $this->getStep());
    }

    /**
     * @return DateTimeImmutable[]
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

        $series = [];
        $current = clone $lower;

        while ($current <= $upper) {
            $series[] = clone $current;
            $current = $current->add($this->getStep());
        }

        return $series;
    }

    #[Override]
    public function equals(RangeInterface $range): bool
    {
        if (! $range instanceof self) {
            throw new InvalidArgumentException('Range must be an instance of DateRange');
        }

        $thisLower = $this->getLowerBoundValue();
        $thisUpper = $this->getUpperBoundValue();
        $rangeLower = $range->getLowerBoundValue();
        $rangeUpper = $range->getUpperBoundValue();

        if (
            ($thisLower === null && $rangeLower !== null) ||
            ($thisLower !== null && $rangeLower === null)
        ) {
            return false;
        }
        if ($thisLower !== null && $rangeLower !== null && $thisLower != $rangeLower) {
            return false;
        }

        if (
            ($thisUpper === null && $rangeUpper !== null) ||
            ($thisUpper !== null && $rangeUpper === null)
        ) {
            return false;
        }
        if ($thisUpper !== null && $rangeUpper !== null && $thisUpper != $rangeUpper) {
            return false;
        }

        return $this->isSameStepUnit($range);
    }

    /**
     * @param DateTimeImmutable $point
     * @return array<DateRange>
     */
    #[Override]
    public function split(mixed $point): array
    {
        if (! $point instanceof DateTimeInterface) {
            throw new InvalidArgumentException('Point must be a DateTimeInterface instance');
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
     * @param DateInterval $offset
     * @throws InvalidDateIntervalException
     */
    #[Override]
    public function shift(mixed $offset): self
    {
        if (! $offset instanceof DateInterval) {
            throw new InvalidArgumentException('Offset must be a DateInterval instance');
        }

        $this->validateDateInterval($offset);

        $newLower = $this->lower?->add($offset);
        $newUpper = $this->upper?->add($offset);

        return new self(
            $newLower,
            $newUpper,
            $this->lowerBound,
            $this->upperBound,
            $this->getStep()
        );
    }

    /**
     * @param DateInterval $factor
     */
    #[Override]
    public function scale(mixed $factor): self
    {
        throw new InvalidArgumentException('Scale operation is not supported for DateRange');
    }

    #[Override]
    public function getStep(): DateInterval
    {
        return $this->step;
    }

    /**
     * @throws InvalidDateIntervalException
     */
    private function validateDateInterval(DateInterval $interval): void
    {
        if ($interval->h !== 0 || $interval->i !== 0 || $interval->s !== 0 || $interval->f !== 0.0) {
            throw new InvalidDateIntervalException();
        }
    }

    /**
     * The step must move forward by at least one day, otherwise length() divides by zero
     * and generateSeries() never terminates.
     *
     * @throws InvalidDateIntervalException
     */
    private function validateStep(DateInterval $step): void
    {
        $this->validateDateInterval($step);

        if ($step->invert !== 0 || $step->y < 0 || $step->m < 0 || $step->d < 0) {
            throw new InvalidDateIntervalException();
        }

        if ($step->y === 0 && $step->m === 0 && $step->d === 0) {
            throw new InvalidDateIntervalException();
        }
    }

    private function compareDateOnly(DateTimeInterface $date1, DateTimeInterface $date2): int
    {
        return $date1->format('Y-m-d') <=> $date2->format('Y-m-d');
    }

    private function getStepDays(): int
    {
        // Fixed reference to keep the result deterministic: measured from "now",
        // a P1M step would be worth 28 to 31 days depending on the current month
        $reference = new DateTimeImmutable('2001-01-01');
        $after = $reference->add($this->getStep());

        return (int) $reference->diff($after)->days;
    }

    private function isSameStepUnit(self $range): bool
    {
        return $this->getStepDays() === $range->getStepDays();
    }

    private function minDate(?DateTimeImmutable $date1, ?DateTimeImmutable $date2): ?DateTimeImmutable
    {
        if ($date1 === null) {
            return $date2;
        }

        if ($date2 === null) {
            return $date1;
        }

        return $date1 < $date2 ? $date1 : $date2;
    }

    private function maxDate(?DateTimeImmutable $date1, ?DateTimeImmutable $date2): ?DateTimeImmutable
    {
        if ($date1 === null) {
            return $date1;
        }

        if ($date2 === null) {
            return $date2;
        }

        return $date1 > $date2 ? $date1 : $date2;
    }
}
