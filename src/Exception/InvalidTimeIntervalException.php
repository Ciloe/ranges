<?php

declare(strict_types=1);

namespace Ciloe\Ranges\Exception;

use InvalidArgumentException;

class InvalidTimeIntervalException extends InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct(
            'DateInterval must not contain years, months, or days.
            Only hours, minutes, and seconds are allowed.'
        );
    }
}
