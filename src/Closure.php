<?php

declare(strict_types=1);

namespace DelOlmo\Container;

use InvalidArgumentException;
use ReflectionClass;
use RuntimeException;

use function get_debug_type;
use function sprintf;

/**
 * @internal
 *
 * @template T of object
 */
final readonly class Closure
{
    /** @var ReflectionClass<T> $reflClass */
    private ReflectionClass $reflClass;

    /** @param class-string<T> $className */
    public function __construct(
        private string $className,
    ) {
        $this->reflClass = new ReflectionClass(
            $this->className,
        );

        if (! $this->reflClass->isInstantiable()) {
            throw new InvalidArgumentException(
                'Object is not instantiable',
            );
        }
    }

    /** @return T */
    public function __invoke(Container $container)
    {
        $resolver = $container->get(Resolver::class);

        if (! $resolver instanceof Resolver) {
            throw new RuntimeException(sprintf(
                'Expecting object of type \'%s\', \'%s\' given instead',
                Resolver::class,
                get_debug_type($resolver),
            ));
        }

        $constructor = $this->reflClass->getConstructor();

        $arguments = [];

        if ($constructor !== null) {
            $arguments = $resolver->resolveAll(
                $constructor->getParameters(),
            );
        }

        return $this->reflClass->newInstance(...$arguments);
    }
}
