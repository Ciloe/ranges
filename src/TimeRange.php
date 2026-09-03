<?php

declare(strict_types=1);

namespace Ciloe\Ranges;

use Ciloe\Ranges\Exception\CantGenerateSeriesBecauseTheArrayIsTooLarge;
use Ciloe\Ranges\Exception\InvalidBoundException;
use Ciloe\Ranges\Exception\InvalidInfiniteBoundException;
use Ciloe\Ranges\Exception\InvalidTimeIntervalException;
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
readonly class TimeRange implements RangeInterface
{
    /**
     * @throws InvalidTimeIntervalException
     */
    public function __construct(
        public ?DateTimeImmutable $lower = null,
        public ?DateTimeImmutable $upper = null,
        public string $lowerBound = '(',
        public string $upperBound = ')',
        public DateInterval $step = new DateInterval('PT1S'),
    ) {
        $this->validateStep($step);
    }

    #[Override]
    public function __toString(): string
    {
        $lowerValue = $this->lower === null ? '' : $this->lower->format('H:i:s');
        $upperValue = $this->upper === null ? '' : $this->upper->format('H:i:s');

        return $this->lowerBound . $lowerValue . ',' . $upperValue . $this->upperBound;
    }

    #[Override]
    public static function fromString(string $range): self
    {
        if (
            ! preg_match(
                '/^(\[|\()([0-9]{2}:[0-9]{2}:[0-9]{2}|null)?,([0-9]{2}:[0-9]{2}:[0-9]{2}|null)?(\]|\))$/',
                $range,
                $matches,
            )
        ) {
            throw new InvalidArgumentException('Invalid range format');
        }

        [, $lowerBound, $lowerStr, $upperStr, $upperBound] = $matches;

        $referenceDate = new DateTimeImmutable('today');

        try {
            $lower = ($lowerStr === 'null' || $lowerStr === '') ? null : $referenceDate->modify($lowerStr);
            $upper = ($upperStr === 'null' || $upperStr === '') ? null : $referenceDate->modify($upperStr);
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

        return $this->compareTimeOnly($this->lower, $this->upper) === 0 &&
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

        return $this->compareTimeOnly($lower, $upper) <= 0;
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
            return $this->compareTimeOnly($value, $upper) <= 0;
        }

        if ($upper === null) {
            return $this->compareTimeOnly($value, $lower) >= 0;
        }

        return $this->compareTimeOnly($value, $lower) >= 0 && $this->compareTimeOnly($value, $upper) <= 0;
    }

    #[Override]
    public function overlap(RangeInterface $range): bool
    {
        if (! $range instanceof self) {
            throw new InvalidArgumentException('Range must be an instance of TimeRange');
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
            return $this->compareTimeOnly($a2, $b1) >= 0;
        }
        if ($a2 === null) {
            return $this->compareTimeOnly($b2, $a1) >= 0;
        }
        if ($b1 === null) {
            return $this->compareTimeOnly($b2, $a1) >= 0;
        }
        if ($b2 === null) {
            return $this->compareTimeOnly($a2, $b1) >= 0;
        }

        return $this->compareTimeOnly($a2, $b1) >= 0 && $this->compareTimeOnly($b2, $a1) >= 0;
    }

    #[Override]
    public function length(): ?int
    {
        $lower = $this->getLowerBoundValue();
        $upper = $this->getUpperBoundValue();

        if ($lower === null || $upper === null) {
            return null;
        }

        if ($this->compareTimeOnly($lower, $upper) > 0) {
            return 0;
        }

        $lowerSeconds = (int) $lower->format('H') * 3600 + (int) $lower->format('i') * 60 + (int) $lower->format('s');
        $upperSeconds = (int) $upper->format('H') * 3600 + (int) $upper->format('i') * 60 + (int) $upper->format('s');
        $diffSeconds = $upperSeconds - $lowerSeconds;

        return intdiv($diffSeconds, $this->getStepSeconds()) + 1;
    }

    #[Override]
    public function union(RangeInterface $range): ?self
    {
        if (! $range instanceof self) {
            throw new InvalidArgumentException('Range must be an instance of TimeRange');
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
            $lower = $this->minTime($thisLower, $rangeLower);
        }

        if ($thisUpper === null || $rangeUpper === null) {
            $upper = null;
        } else {
            $upper = $this->maxTime($thisUpper, $rangeUpper);
        }

        return new self($lower, $upper, $lower === null ? '(' : '[', $upper === null ? ')' : ']', $this->getStep());
    }

    #[Override]
    public function intersection(RangeInterface $range): ?self
    {
        if (! $range instanceof self) {
            throw new InvalidArgumentException('Range must be an instance of TimeRange');
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
            $lower = $this->maxTime($thisLower, $rangeLower);
        }

        if ($thisUpper === null) {
            $upper = $rangeUpper;
        } elseif ($rangeUpper === null) {
            $upper = $thisUpper;
        } else {
            $upper = $this->minTime($thisUpper, $rangeUpper);
        }

        if ($lower !== null && $upper !== null && $this->compareTimeOnly($lower, $upper) > 0) {
            return null;
        }

        return new self($lower, $upper, $lower === null ? '(' : '[', $upper === null ? ')' : ']', $this->getStep());
    }

    #[Override]
    public function containsRange(RangeInterface $range): bool
    {
        if (! $range instanceof self) {
            throw new InvalidArgumentException('Range must be an instance of TimeRange');
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
            ($rangeLower !== null && $this->compareTimeOnly($rangeLower, $thisLower) >= 0);
        $upperCheck = $thisUpper === null ||
            ($rangeUpper !== null && $this->compareTimeOnly($rangeUpper, $thisUpper) <= 0);

        return $lowerCheck && $upperCheck;
    }

    #[Override]
    public function isBefore(RangeInterface $range): bool
    {
        if (! $range instanceof self) {
            throw new InvalidArgumentException('Range must be an instance of TimeRange');
        }

        if ($this->isEmpty() || $range->isEmpty()) {
            return false;
        }

        $upper = $this->getUpperBoundValue();
        $lower = $range->getLowerBoundValue();

        return $upper !== null && $lower !== null && $this->compareTimeOnly($upper, $lower) < 0;
    }

    #[Override]
    public function isAfter(RangeInterface $range): bool
    {
        if (! $range instanceof self) {
            throw new InvalidArgumentException('Range must be an instance of TimeRange');
        }

        return $range->isBefore($this);
    }

    #[Override]
    public function isAdjacent(RangeInterface $range): bool
    {
        if (! $range instanceof self) {
            throw new InvalidArgumentException('Range must be an instance of TimeRange');
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

        // Plain second arithmetic: a step crossing midnight never makes ranges adjacent
        $touchesRight = $thisUpper !== null && $rangeLower !== null &&
            $this->toSeconds($thisUpper) + $this->getStepSeconds() === $this->toSeconds($rangeLower);
        $touchesLeft = $rangeUpper !== null && $thisLower !== null &&
            $this->toSeconds($rangeUpper) + $this->getStepSeconds() === $this->toSeconds($thisLower);

        return $touchesRight || $touchesLeft;
    }

    /**
     * @return array<TimeRange>|null
     */
    #[Override]
    public function difference(RangeInterface $range): ?array
    {
        if (! $range instanceof self) {
            throw new InvalidArgumentException('Range must be an instance of TimeRange');
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

        if ($rangeLower !== null && ($thisLower === null || $this->compareTimeOnly($thisLower, $rangeLower) < 0)) {
            $parts[] = new self(
                $thisLower,
                $rangeLower->sub($this->getStep()),
                $thisLower === null ? '(' : '[',
                ']',
                $this->getStep()
            );
        }

        if ($rangeUpper !== null && ($thisUpper === null || $this->compareTimeOnly($thisUpper, $rangeUpper) > 0)) {
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
            throw new InvalidArgumentException('Range must be an instance of TimeRange');
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

        if ($this->compareTimeOnly($lower, $upper) > 0) {
            return [];
        }

        $lowerSeconds = (int) $lower->format('H') * 3600 + (int) $lower->format('i') * 60 + (int) $lower->format('s');
        $upperSeconds = (int) $upper->format('H') * 3600 + (int) $upper->format('i') * 60 + (int) $upper->format('s');
        $stepSeconds = $this->getStepSeconds();

        $series = [];
        $current = $lower;

        // Iterate on seconds to guarantee termination when the step crosses midnight
        for ($seconds = $lowerSeconds; $seconds <= $upperSeconds; $seconds += $stepSeconds) {
            $series[] = $current;
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

        if ($lower !== null && $this->compareTimeOnly($value, $lower) < 0) {
            return $lower;
        }

        if ($upper !== null && $this->compareTimeOnly($value, $upper) > 0) {
            return $upper;
        }

        return $value;
    }

    /**
     * @param DateInterval $amount
     * @throws InvalidTimeIntervalException
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
     * @throws InvalidTimeIntervalException
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

        $lower = $this->getLowerBoundValue();
        $count = $this->length();

        if ($lower === null || $count === null) {
            throw new InvalidArgumentException('Cannot pick a random value from an infinite range');
        }

        $offsetSeconds = random_int(0, $count - 1) * $this->getStepSeconds();

        return $lower->add(new DateInterval('PT' . $offsetSeconds . 'S'));
    }

    /**
     * Both bounds must be finite: the time domain has no natural starting point for -∞.
     *
     * @return Generator<int, DateTimeImmutable>
     */
    #[Override]
    public function iterate(): Generator
    {
        if (
            ! $this->isEmpty() &&
            ($this->getLowerBoundValue() === null || $this->getUpperBoundValue() === null)
        ) {
            throw new InvalidArgumentException('Cannot iterate over a range with an infinite bound');
        }

        return $this->iterateValues();
    }

    /**
     * @return array<TimeRange>
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

        $upperSeconds = $this->toSeconds($upper);
        $chunkSpanSeconds = ($count - 1) * $this->getStepSeconds();
        $chunks = [];
        $start = $lower;
        $startSeconds = $this->toSeconds($lower);

        while ($startSeconds <= $upperSeconds) {
            $endSeconds = min($startSeconds + $chunkSpanSeconds, $upperSeconds);
            $end = $start->add(new DateInterval('PT' . ($endSeconds - $startSeconds) . 'S'));
            $chunks[] = new self($start, $end, '[', ']', $this->getStep());

            $startSeconds = $endSeconds + $this->getStepSeconds();
            $start = $end->add($this->getStep());
        }

        return $chunks;
    }

    #[Override]
    public function equals(RangeInterface $range): bool
    {
        if (! $range instanceof self) {
            throw new InvalidArgumentException('Range must be an instance of TimeRange');
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
        if ($thisLower !== null && $rangeLower !== null && $this->compareTimeOnly($thisLower, $rangeLower) !== 0) {
            return false;
        }

        if (
            ($thisUpper === null && $rangeUpper !== null) ||
            ($thisUpper !== null && $rangeUpper === null)
        ) {
            return false;
        }
        if ($thisUpper !== null && $rangeUpper !== null && $this->compareTimeOnly($thisUpper, $rangeUpper) !== 0) {
            return false;
        }

        return $this->isSameStepUnit($range);
    }

    /**
     * @param DateTimeInterface $point
     * @return array<TimeRange>
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
            $point instanceof DateTimeImmutable ? $point : DateTimeImmutable::createFromMutable($point),
            $this->lowerBound,
            ')',
            $this->getStep()
        );

        $rightRange = new self(
            $point instanceof DateTimeImmutable ? $point : DateTimeImmutable::createFromMutable($point),
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
     * @throws InvalidTimeIntervalException
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
        throw new InvalidArgumentException('Scale operation is not supported for TimeRange');
    }

    #[Override]
    public function getStep(): DateInterval
    {
        return $this->step;
    }

    /**
     * @throws InvalidTimeIntervalException
     */
    private function validateDateInterval(DateInterval $interval): void
    {
        if ($interval->y !== 0 || $interval->m !== 0 || $interval->d !== 0) {
            throw new InvalidTimeIntervalException();
        }
    }

    /**
     * The step must move forward by at least one second, otherwise length() divides by zero
     * and generateSeries() never terminates.
     *
     * @throws InvalidTimeIntervalException
     */
    private function validateStep(DateInterval $step): void
    {
        $this->validateDateInterval($step);

        if ($step->invert !== 0 || $step->h < 0 || $step->i < 0 || $step->s < 0) {
            throw new InvalidTimeIntervalException();
        }

        if ($step->h === 0 && $step->i === 0 && $step->s === 0) {
            throw new InvalidTimeIntervalException();
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

        if ($lower === null || $upper === null) {
            return;
        }

        $upperSeconds = $this->toSeconds($upper);
        $current = $lower;

        // Iterate on seconds to guarantee termination when the step crosses midnight
        for ($seconds = $this->toSeconds($lower); $seconds <= $upperSeconds; $seconds += $this->getStepSeconds()) {
            yield $current;

            $current = $current->add($this->getStep());
        }
    }

    private function toSeconds(DateTimeInterface $time): int
    {
        return (int) $time->format('H') * 3600 + (int) $time->format('i') * 60 + (int) $time->format('s');
    }

    private function getStepSeconds(): int
    {
        return $this->getStep()->h * 3600 + $this->getStep()->i * 60 + $this->getStep()->s;
    }

    private function isSameStepUnit(self $range): bool
    {
        return $this->getStepSeconds() === $range->getStepSeconds();
    }

    private function minTime(?DateTimeImmutable $time1, ?DateTimeImmutable $time2): ?DateTimeImmutable
    {
        if ($time1 === null) {
            return $time2;
        }
        if ($time2 === null) {
            return $time1;
        }

        return $this->compareTimeOnly($time1, $time2) < 0 ? $time1 : $time2;
    }

    private function maxTime(?DateTimeImmutable $time1, ?DateTimeImmutable $time2): ?DateTimeImmutable
    {
        if ($time1 === null) {
            return $time1;
        }
        if ($time2 === null) {
            return $time2;
        }

        return $this->compareTimeOnly($time1, $time2) > 0 ? $time1 : $time2;
    }

    private function compareTimeOnly(?DateTimeInterface $time1, ?DateTimeInterface $time2): int
    {
        if ($time2 === null && $time1 !== null) {
            return 1;
        }

        if ($time1 === null && $time2 !== null) {
            return -1;
        }

        if ($time1 === null && $time2 === null) {
            return 0;
        }

        $time1Seconds = (int) $time1?->format('H') * 3600 +
            (int) $time1?->format('i') * 60 +
            (int) $time1?->format('s');
        $time2Seconds = (int) $time2?->format('H') * 3600 +
            (int) $time2?->format('i') * 60 +
            (int) $time2?->format('s');

        return $time1Seconds <=> $time2Seconds;
    }
}
