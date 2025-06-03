<?php

declare(strict_types=1);

namespace Ciloe\Ranges\Exception;

use Exception;

final class InvalidInfiniteBoundException extends Exception
{
    public function __construct()
    {
        parent::__construct('The infinite bound can\'t be included');
    }
}
