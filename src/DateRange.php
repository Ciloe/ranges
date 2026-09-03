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
use Generator;
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

    /**
     * Creates the inclusive range covering a whole calendar month.
     *
     * @throws InvalidArgumentException If the month is not between 1 and 12
     * @throws InvalidDateIntervalException
     */
    public static function fromMonth(int $year, int $month): self
    {
        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException('Month must be between 1 and 12');
        }

        $firstDay = (new DateTimeImmutable())->setDate($year, $month, 1)->setTime(0, 0);

        return new self($firstDay, $firstDay->modify('last day of this month'), '[', ']');
    }

    /**
     * Creates the inclusive range covering a whole calendar year.
     *
     * @throws InvalidDateIntervalException
     */
    public static function fromYear(int $year): self
    {
        $firstDay = (new DateTimeImmutable())->setDate($year, 1, 1)->setTime(0, 0);

        return new self($firstDay, $firstDay->setDate($year, 12, 31), '[', ']');
    }

    /**
     * Creates the inclusive range covering a whole ISO week (Monday to Sunday).
     *
     * @throws InvalidArgumentException If the week does not exist in the given ISO year
     * @throws InvalidDateIntervalException
     */
    public static function fromWeek(int $year, int $week): self
    {
        if ($week < 1 || $week > 53) {
            throw new InvalidArgumentException('Week must be between 1 and 53');
        }

        $monday = (new DateTimeImmutable())->setISODate($year, $week)->setTime(0, 0);

        if ($monday->format('o-W') !== sprintf('%04d-%02d', $year, $week)) {
            throw new InvalidArgumentException(sprintf('Week %d does not exist in ISO year %d', $week, $year));
        }

        return new self($monday, $monday->modify('+6 days'), '[', ']');
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

    #[Override]
    public function containsRange(RangeInterface $range): bool
    {
        if (! $range instanceof self) {
            throw new InvalidArgumentException('Range must be an instance of DateRange');
        }

        if ($range->isEmpty()) {
            return true;
        }

        if ($this->isEmpty()) {
            return false;
        }

        $thisLower = $this->getLowerBoundValue();
        $thisUpper = $this->getUpperBoundValue();
        $rangeLower = $range->getLowerBoundValue();
        $rangeUpper = $range->getUpperBoundValue();

        $lowerCheck = $thisLower === null ||
            ($rangeLower !== null && $this->compareDateOnly($rangeLower, $thisLower) >= 0);
        $upperCheck = $thisUpper === null ||
            ($rangeUpper !== null && $this->compareDateOnly($rangeUpper, $thisUpper) <= 0);

        return $lowerCheck && $upperCheck;
    }

    #[Override]
    public function isBefore(RangeInterface $range): bool
    {
        if (! $range instanceof self) {
            throw new InvalidArgumentException('Range must be an instance of DateRange');
        }

        if ($this->isEmpty() || $range->isEmpty()) {
            return false;
        }

        $upper = $this->getUpperBoundValue();
        $lower = $range->getLowerBoundValue();

        return $upper !== null && $lower !== null && $this->compareDateOnly($upper, $lower) < 0;
    }

    #[Override]
    public function isAfter(RangeInterface $range): bool
    {
        if (! $range instanceof self) {
            throw new InvalidArgumentException('Range must be an instance of DateRange');
        }

        return $range->isBefore($this);
    }

    #[Override]
    public function isAdjacent(RangeInterface $range): bool
    {
        if (! $range instanceof self) {
            throw new InvalidArgumentException('Range must be an instance of DateRange');
        }

        if (! $this->isSameStepUnit($range)) {
            return false;
        }

        if ($this->isEmpty() || $range->isEmpty()) {
            return false;
        }

        $thisLower = $this->getLowerBoundValue();
        $thisUpper = $this->getUpperBoundValue();
        $rangeLower = $range->getLowerBoundValue();
        $rangeUpper = $range->getUpperBoundValue();

        $touchesRight = $thisUpper !== null && $rangeLower !== null &&
            $this->compareDateOnly($thisUpper->add($this->getStep()), $rangeLower) === 0;
        $touchesLeft = $rangeUpper !== null && $thisLower !== null &&
            $this->compareDateOnly($rangeUpper->add($this->getStep()), $thisLower) === 0;

        return $touchesRight || $touchesLeft;
    }

    /**
     * @return array<DateRange>|null
     */
    #[Override]
    public function difference(RangeInterface $range): ?array
    {
        if (! $range instanceof self) {
            throw new InvalidArgumentException('Range must be an instance of DateRange');
        }

        if (! $this->isSameStepUnit($range)) {
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

        if ($rangeLower !== null && ($thisLower === null || $this->compareDateOnly($thisLower, $rangeLower) < 0)) {
            $parts[] = new self(
                $thisLower,
                $rangeLower->sub($this->getStep()),
                $thisLower === null ? '(' : '[',
                ']',
                $this->getStep()
            );
        }

        if ($rangeUpper !== null && ($thisUpper === null || $this->compareDateOnly($thisUpper, $rangeUpper) > 0)) {
            $parts[] = new self(
                $rangeUpper->add($this->getStep()),
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
            throw new InvalidArgumentException('Range must be an instance of DateRange');
        }

        if (! $this->isSameStepUnit($range)) {
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

        return new self(
            $leftUpper->add($this->getStep()),
            $rightLower->sub($this->getStep()),
            '[',
            ']',
            $this->getStep()
        );
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

    /**
     * @param DateTimeInterface $value
     */
    #[Override]
    public function clamp(mixed $value): DateTimeImmutable
    {
        if (! $value instanceof DateTimeInterface) {
            throw new InvalidArgumentException('Value must be a DateTimeInterface instance');
        }

        if ($this->isEmpty()) {
            throw new InvalidArgumentException('Cannot clamp a value on an empty range');
        }

        if (! $value instanceof DateTimeImmutable) {
            $value = DateTimeImmutable::createFromInterface($value);
        }

        $lower = $this->getLowerBoundValue();
        $upper = $this->getUpperBoundValue();

        if ($lower !== null && $this->compareDateOnly($value, $lower) < 0) {
            return $lower;
        }

        if ($upper !== null && $this->compareDateOnly($value, $upper) > 0) {
            return $upper;
        }

        return $value;
    }

    /**
     * @param DateInterval $amount
     * @throws InvalidDateIntervalException
     */
    #[Override]
    public function expand(mixed $amount): self
    {
        if (! $amount instanceof DateInterval) {
            throw new InvalidArgumentException('Amount must be a DateInterval instance');
        }

        $this->validateStep($amount);

        return new self(
            $this->lower?->sub($amount),
            $this->upper?->add($amount),
            $this->lowerBound,
            $this->upperBound,
            $this->getStep()
        );
    }

    /**
     * @param DateInterval $amount
     * @throws InvalidDateIntervalException
     */
    #[Override]
    public function shrink(mixed $amount): self
    {
        if (! $amount instanceof DateInterval) {
            throw new InvalidArgumentException('Amount must be a DateInterval instance');
        }

        $this->validateStep($amount);

        $shrunk = new self(
            $this->lower?->add($amount),
            $this->upper?->sub($amount),
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
    public function random(): DateTimeImmutable
    {
        if ($this->isEmpty()) {
            throw new InvalidArgumentException('Cannot pick a random value from an empty range');
        }

        if ($this->getLowerBoundValue() === null || $this->getUpperBoundValue() === null) {
            throw new InvalidArgumentException('Cannot pick a random value from an infinite range');
        }

        $series = $this->generateSeries();

        return $series[array_rand($series)];
    }

    /**
     * The generator never stops when the upper bound is infinite.
     *
     * @return Generator<int, DateTimeImmutable>
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
     * @return array<DateRange>
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

        $chunkSpan = $this->scaleStep($count - 1);
        $chunks = [];
        $start = $lower;

        while ($this->compareDateOnly($start, $upper) <= 0) {
            $candidate = $start->add($chunkSpan);
            $end = $this->compareDateOnly($candidate, $upper) > 0 ? $upper : $candidate;
            $chunks[] = new self($start, $end, '[', ']', $this->getStep());
            $start = $end->add($this->getStep());
        }

        return $chunks;
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

    /**
     * @return Generator<int, DateTimeImmutable>
     */
    private function iterateValues(): Generator
    {
        if ($this->isEmpty()) {
            return;
        }

        $lower = $this->getLowerBoundValue();
        $upper = $this->getUpperBoundValue();

        if ($lower === null) {
            return;
        }

        $current = $lower;

        while ($upper === null || $current <= $upper) {
            yield $current;

            $current = $current->add($this->getStep());
        }
    }

    /**
     * Multiplies the step interval by an integer factor, component by component.
     */
    private function scaleStep(int $times): DateInterval
    {
        return new DateInterval(sprintf(
            'P%dY%dM%dD',
            $this->getStep()->y * $times,
            $this->getStep()->m * $times,
            $this->getStep()->d * $times
        ));
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
