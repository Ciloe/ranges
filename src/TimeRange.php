<?php

declare(strict_types=1);

namespace Ciloe\Ranges;

use Ciloe\Ranges\Exception\CantGenerateSeriesBecauseTheArrayIsTooLarge;
use Ciloe\Ranges\Exception\InvalidBoundException;
use Ciloe\Ranges\Exception\InvalidInfiniteBoundException;
use Ciloe\Ranges\Exception\InvalidStepToGenerateSeriesException;
use Ciloe\Ranges\Exception\InvalidTimeIntervalException;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
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
        $this->validateDateInterval($step);
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
        $lower = ($lowerStr === 'null' || $lowerStr === '') ? null : $referenceDate->modify($lowerStr);
        $upper = ($upperStr === 'null' || $upperStr === '') ? null : $referenceDate->modify($upperStr);

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

        $stepSeconds = $this->getStepSeconds();
        $length = (int) ceil($diffSeconds / $stepSeconds) + 1;

        return max($length, 0);
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

        return new self($lower, $upper, '[', ']', $this->getStep());
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

        return new self($lower, $upper, '[', ']', $this->getStep());
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
        $diffSeconds = $upperSeconds - $lowerSeconds;
        $stepSeconds = $this->getStepSeconds();

        if ($diffSeconds > 0 && $diffSeconds < $stepSeconds) {
            throw new InvalidStepToGenerateSeriesException();
        }

        $series = [];
        $current = clone $lower;

        while ($this->compareTimeOnly($current, $upper) <= 0) {
            $series[] = clone $current;
            $current = $current->add($this->getStep());
        }

        return $series;
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
