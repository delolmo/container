<?php

declare(strict_types=1);

namespace DelOlmo\Container;

use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;

use function is_a;
use function is_object;
use function sprintf;

final readonly class Resolver
{
    public function __construct(
        private Container $container,
    ) {
    }

    /**
     * @param list<ReflectionParameter> $parameters
     *
     * @return list<mixed>
     */
    public function resolveAll(array $parameters): iterable
    {
        /** @var list<mixed> $results */
        $results = [];

        foreach ($parameters as $parameter) {
            /** @var mixed $resolved */
            $resolved = $this->resolve($parameter);

            /** @psalm-suppress MixedAssignment */
            $results[] = $resolved;
        }

        return $results;
    }

    public function resolve(ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if (
            $type instanceof ReflectionNamedType &&
            $type->isBuiltin() === false
        ) {
            /** @var class-string $className */
            $className = $type->getName();

            /** @var mixed $value */
            $value = $this->container->get($className);

            if (! is_object($value) || ! is_a($value, $className)) {
                throw new RuntimeException(sprintf(
                    'Parameter \'%s\' is not resolvable',
                    $parameter->getName(),
                ));
            }

            return $value;
        }

        if ($parameter->isOptional()) {
              /** @var mixed $value */
            $value = $parameter->getDefaultValue();

            /** @psalm-suppress MixedAssignment */
            return $value;
        }

        throw new RuntimeException(sprintf(
            'Parameter \'%s\' is not resolvable',
            $parameter->getName(),
        ));
    }
}
