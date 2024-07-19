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

    public function extend(string $id, callable $callable): void
    {
        if (! $this->has($id)) {
            throw new RuntimeException(sprintf(
                'Unable to find service of type \'%s\'',
                $id,
            ));
        }

        /** @psalm-suppress MixedAssignment */
        $rawService = $this->services[$id];

        /** @psalm-suppress MissingClosureReturnType */
        $this->services[$id] = static function (ContainerInterface $container) use ($rawService, $callable) {
            /** @psalm-suppress MixedAssignment */
            $resolvedService = is_callable($rawService) ? $rawService($container) : $rawService;

            $callable($resolvedService, $container);

            return $resolvedService;
        };
    }

    #[Override]
    public function get(string $id): mixed
    {
        $service = null;

        if ($this->has($id)) {
            /** @psalm-suppress MixedAssignment */
            $service = $this->services[$id];
        }

        /** @var list<string> $keys */
        $keys = array_keys($this->services);

        foreach ($keys as $key) {
            if ($service !== null) {
                break;
            }

            if (! class_exists($key) && ! interface_exists($key)) {
                continue;
            }

            if (! is_subclass_of($id, $key)) {
                continue;
            }

            /** @psalm-suppress MixedAssignment */
            $service = $this->services[$key];

            break;
        }

        if ($service === null) {
            throw new RuntimeException(sprintf(
                'Unable to find service of type \'%s\'',
                $id,
            ));
        }

        return is_callable($service) ? $service($this) : $service;
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

    public function set(string $id, mixed $value): void
    {
        $this->services[$id] = $value;
    }
}
