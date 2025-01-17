<?php

namespace Ciloe\Ranges;

use InvalidArgumentException;

class IntRange
{
    public function __construct(
        public ?int $lower = null,
        public ?int $upper = null,
        public ?string $lowerBound = '(',
        public ?string $upperBound = ')',
    ) {}

    public static function fromString(string $range): self
    {
        // Validate the string format
        if (!preg_match('/^(\[|\()(\d+|null)?,(\d+|null)?(\]|\))$/', $range, $matches)) {
            throw new InvalidArgumentException('Invalid range format');
        }

        // Extract the bounds and values
        $lowerBound = $matches[1];
        $lower = $matches[2] === 'null' ? null : (int)$matches[2];
        $upper = $matches[3] === 'null' ? null : (int)$matches[3];
        $upperBound = $matches[4];

        return new self($lower, $upper, $lowerBound, $upperBound);
    }
}