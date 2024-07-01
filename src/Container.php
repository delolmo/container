<?php

declare(strict_types=1);

namespace DelOlmo\Container;

use Closure;
use DelOlmo\ClassFinder\ClassFinder;
use InvalidArgumentException;
use Override;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;

use function array_keys;
use function class_exists;
use function interface_exists;
use function is_a;
use function is_object;
use function is_subclass_of;
use function sprintf;

final class Container implements ContainerInterface
{
    /** @param array<class-string, mixed> $services */
    public function __construct(private array $services = [])
    {
        $this->set(ContainerInterface::class, fn () => $this);
    }

    public function autowire(string $directory): void
    {
        $classNames = (new ClassFinder())->findAll($directory);

        foreach ($classNames as $className) {
            $reflClass = new ReflectionClass($className);

            if (! $reflClass->isInstantiable()) {
                continue;
            }

            $fn = $this->createClosure($className);

            $this->set($className, $fn);
        }
    }

    #[Override]
    public function get(string $id): mixed
    {
        if ($this->has($id)) {
            /** @psalm-var object|Closure $service */
            $service = $this->services[$id];

            if ($service instanceof Closure) {
                /** @psalm-var object $service */
                $service = $service($this);

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

            /** @psalm-var object|Closure $service */
            $service = $this->services[$key];

            if ($service instanceof Closure) {
                /** @psalm-var object $service */
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

    /** @psalm-assert-if-true class-string $id */
    #[Override]
    public function has(string $id): bool
    {
        if (! class_exists($id) && ! interface_exists($id)) {
            return false;
        }

        return isset($this->services[$id]);
    }

    /**
     * @param class-string<T> $id
     * @param T|Closure       $object
     *
     * @template T of object
     */
    public function set(string $id, $object): void
    {
        $this->services[$id] = $object;
    }

    /**
     * @param class-string<T> $className
     *
     * @return Closure():T
     *
     * @template T of object
     */
    private function createClosure(string $className): Closure
    {
        $reflClass = new ReflectionClass($className);

        if (! $reflClass->isInstantiable()) {
            throw new InvalidArgumentException(
                'Object is not instantiable',
            );
        }

        $constructor = $reflClass->getConstructor();

        $arguments = [];

        if ($constructor !== null) {
            $arguments = $this->resolveArguments(
                $constructor->getParameters(),
            );
        }

        return static fn () => $reflClass->newInstance(...$arguments);
    }

    /**
     * @param list<ReflectionParameter> $parameters
     *
     * @return list<mixed>
     */
    private function resolveArguments(array $parameters): iterable
    {
        /** @var list<mixed> $resolvedParameters */
        $resolvedParameters = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if (
                $type instanceof ReflectionNamedType &&
                $type->isBuiltin() === false
            ) {
                /** @var class-string $className */
                $className = $type->getName();

                /** @var mixed $value */
                $value = $this->get($className);

                if (! is_object($value) || ! is_a($value, $className)) {
                    throw new RuntimeException(sprintf(
                        'Parameter \'%s\' is not resolvable',
                        $parameter->getName(),
                    ));
                }

                $resolvedParameters[] = $value;
                continue;
            }

            if ($parameter->isOptional()) {
                  /** @var mixed $value */
                $value = $parameter->getDefaultValue();

                /** @psalm-suppress MixedAssignment */
                $resolvedParameters[] = $value;
                continue;
            }

            throw new RuntimeException(sprintf(
                'Parameter \'%s\' is not resolvable',
                $parameter->getName(),
            ));
        }

        return $resolvedParameters;
    }
}
