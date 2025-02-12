<?php

declare(strict_types=1);

namespace Ciloe\Ranges\Exception;

use Exception;

final class InvalidInfinitBoundException extends Exception
{
    public function __construct() 
    {
        parent::__construct('The infinit bound can\'t be included');
    }
}