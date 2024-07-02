<?php

declare(strict_types=1);

namespace DelOlmo\Container;

interface ServiceProvider
{
    public function register(Container $container): void;
}
