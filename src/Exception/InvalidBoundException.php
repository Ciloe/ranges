<?php

declare(strict_types=1);

namespace Ciloe\Ranges\Exception;

use Exception;

final class InvalidBoundException extends Exception
{
    public function __construct() 
    {
        parent::__construct('The lower bound can\'t gretter than the upper');
    }
}