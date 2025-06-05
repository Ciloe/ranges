<?php

declare(strict_types=1);

namespace Ciloe\Ranges\Exception;

use InvalidArgumentException;

class InvalidDateIntervalException extends InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct(
            'DateInterval must not contain minutes, hours, or seconds.
            Only days, months, and years are allowed.'
        );
    }
}
