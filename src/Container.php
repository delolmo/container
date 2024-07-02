<?php

declare(strict_types=1);

namespace DelOlmo\Container;

use DelOlmo\ClassFinder\ClassFinder;
use Override;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use RuntimeException;

use function array_keys;
use function class_exists;
use function interface_exists;
use function is_callable;
use function is_subclass_of;
use function sprintf;

final class Container implements ContainerInterface
{
    /** @param array<string, mixed> $services */
    public function __construct(
        private array $services = [],
    ) {
        $this->set(ContainerInterface::class, fn () => $this);
        $this->set(Resolver::class, fn () => new Resolver($this));
    }

    public function autowire(
        string $directory,
        ClassFinder|null $classFinder = null,
    ): void {
        $classFinder ??= new ClassFinder();

        $classNames = $classFinder->findAll($directory);

        foreach ($classNames as $className) {
            $reflClass = new ReflectionClass($className);

            if (! $reflClass->isInstantiable()) {
                continue;
            }

            $this->set($className, new Closure($className));
        }
    }

    #[Override]
    public function get(string $id): mixed
    {
        if ($this->has($id)) {
            /** @psalm-suppress MixedAssignment */
            $service = $this->services[$id];

            if (is_callable($service)) {
                /** @psalm-suppress MixedAssignment */
                $service = $service($this);

                $this->services[$id] = $service;
            }

            return $service;
        }

        /** @var list<string> $keys */
        $keys = array_keys($this->services);

        foreach ($keys as $key) {
            if (! class_exists($key) && ! interface_exists($key)) {
                continue;
            }

            if (! is_subclass_of($id, $key)) {
                continue;
            }

            /** @psalm-suppress MixedAssignment */
            $service = $this->services[$key];

            if (is_callable($service)) {
                /** @psalm-suppress MixedAssignment */
                $service = $service($this);

                $this->services[$key] = $service;
            }

            return $service;
        }

        throw new RuntimeException(sprintf(
            'Unable to find service of type \'%s\'',
            $id,
        ));
    }

    #[Override]
    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }

    public function register(ServiceProvider $provider): void
    {
        $provider->register($this);
    }

    public function set(string $id, mixed $object): void
    {
        $this->services[$id] = $object;
    }
}
