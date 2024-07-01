<?php

declare(strict_types=1);

namespace DelOlmo\Container;

use stdClass;

class ServiceWithConstructorArgs
{
    public function __construct(private readonly stdClass $object)
    {
    }
}
