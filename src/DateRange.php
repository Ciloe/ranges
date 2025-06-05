<?php

declare(strict_types=1);

namespace Ciloe\Ranges;

use Ciloe\Ranges\Exception\CantGenerateSeriesBecauseTheArrayIsTooLarge;
use Ciloe\Ranges\Exception\InvalidBoundException;
use Ciloe\Ranges\Exception\InvalidInfiniteBoundException;
use DateInterval;
use DateMalformedStringException;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * @implements RangeInterface<DateTimeImmutable, DateInterval>
 */
class DateRange implements RangeInterface
{
    public function __construct(
        readonly public ?DateTimeImmutable $lower = null,
        readonly public ?DateTimeImmutable $upper = null,
        readonly public string $lowerBound = '(',
        readonly public string $upperBound = ')',
        public DateInterval $step = new DateInterval('P1D'),
    ) {
    }

    public function __toString(): string
    {
        $lowerValue = $this->lower === null ? '' : $this->lower->format('Y-m-d');
        $upperValue = $this->upper === null ? '' : $this->upper->format('Y-m-d');

        return $this->lowerBound . $lowerValue . ',' . $upperValue . $this->upperBound;
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function fromString(string $range): self
    {
        if (
            ! preg_match(
                '/^(\[|\()([0-9]{4}-[0-9]{2}-[0-9]{2}|null)?,([0-9]{4}-[0-9]{2}-[0-9]{2}|null)?(\]|\))$/',
                $range,
                $matches,
            )
        ) {
            throw new InvalidArgumentException('Invalid range format');
        }

        $lowerBound = $matches[1];
        $lowerStr = $matches[2];
        $upperStr = $matches[3];
        $upperBound = $matches[4];

        $lower = ($lowerStr === 'null' || $lowerStr === '') ? null : new DateTimeImmutable($lowerStr);
        $upper = ($upperStr === 'null' || $upperStr === '') ? null : new DateTimeImmutable($upperStr);

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
        if ($this->lower === null || $this->upper === null) {
            return false;
        }

        $lowerValue = $this->getLowerBoundValue();
        $upperValue = $this->getUpperBoundValue();

        if ($lowerValue === null || $upperValue === null) {
            return false;
        }

        return $lowerValue == $upperValue &&
            ($this->lowerBound === '(' || $this->upperBound === ')');
    }

    public function isBoundsValid(): bool
    {
        $lower = $this->getLowerBoundValue();
        $upper = $this->getUpperBoundValue();

        if ($lower === null || $upper === null) {
            return true;
        }

        return $lower <= $upper;
    }

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
            return $value <= $upper;
        }

        if ($upper === null) {
            return $value >= $lower;
        }

        return $value >= $lower && $value <= $upper;
    }

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

        $lower = $this->minDate($thisLower, $rangeLower);
        $upper = $this->maxDate($thisUpper, $rangeUpper);

        return new self($lower, $upper, '[', ']', $this->getStep());
    }

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

        $lower = $this->maxDate($thisLower, $rangeLower);
        $upper = $this->minDate($thisUpper, $rangeUpper);

        if ($lower !== null && $upper !== null && $lower > $upper) {
            return null;
        }

        return new self($lower, $upper, '[', ']', $this->getStep());
    }

    /**
     * @return DateTimeImmutable[]
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
     */
    public function shift(mixed $offset): self
    {
        if (! $offset instanceof DateInterval) {
            throw new InvalidArgumentException('Offset must be a DateInterval instance');
        }

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
    public function scale(mixed $factor): self
    {
        throw new InvalidArgumentException('Scale operation is not supported for DateRange');
    }

    public function getStep(): DateInterval
    {
        return $this->step;
    }

    private function getStepDays(): int
    {
        $reference = new DateTimeImmutable();
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
