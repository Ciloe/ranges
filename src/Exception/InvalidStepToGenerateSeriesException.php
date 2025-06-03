<?php

declare(strict_types=1);

namespace Ciloe\Ranges\Exception;

use Exception;

final class InvalidStepToGenerateSeriesException extends Exception
{
    public function __construct() 
    {
        parent::__construct('The step must be greater than the first occurrence in the range');
    }
}