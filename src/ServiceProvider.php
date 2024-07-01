<?php

declare(strict_types=1);

namespace DelOlmo\Container;

interface ServiceProvider
{
    /** @param array<array-key, mixed> $parameters */
    public function register(Container $container, array $parameters): void;
}
