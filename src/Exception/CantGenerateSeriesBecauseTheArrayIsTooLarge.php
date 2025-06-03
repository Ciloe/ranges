<?php

declare(strict_types=1);

namespace Ciloe\Ranges\Exception;

use Exception;
use Throwable;

final class CantGenerateSeriesBecauseTheArrayIsTooLarge extends Exception
{
    public function __construct(Throwable $previous = null)
    {
        parent::__construct('The range that will be generated is too large', previous: $previous);
    }
}
