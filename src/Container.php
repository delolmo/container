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
    /**
     * @param array<class-string, mixed> $services
     * @param array<array-key, mixed>    $parameters
     */
    public function __construct(
        private array $services = [],
        private readonly array $parameters = [],
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
            /** @psalm-var object|callable $service */
            $service = $this->services[$id];

            if (is_callable($service)) {
                /** @psalm-var object $service */
                $service = $service($this, $this->parameters);

                $this->services[$id] = $service;
            }

            return $service;
        }

        /** @var list<class-string> $keys */
        $keys = array_keys($this->services);

        foreach ($keys as $key) {
            if (! is_subclass_of($id, $key)) {
                continue;
            }

            /** @psalm-var object|callable $service */
            $service = $this->services[$key];

            if (is_callable($service)) {
                /** @psalm-var object $service */
                $service = $service($this, $this->parameters);

                $this->services[$key] = $service;
            }

            return $service;
        }

        throw new RuntimeException(sprintf(
            'Unable to find service of type \'%s\'',
            $id,
        ));
    }

    /** @psalm-assert-if-true class-string $id */
    #[Override]
    public function has(string $id): bool
    {
        if (! class_exists($id) && ! interface_exists($id)) {
            return false;
        }

        return isset($this->services[$id]);
    }

    public function register(ServiceProvider $provider): void
    {
        $provider->register($this, $this->parameters);
    }

    /**
     * @param class-string<T>         $id
     * @param T|callable(Container):T $object
     *
     * @template T of object
     */
    public function set(string $id, $object): void
    {
        $this->services[$id] = $object;
    }
}
