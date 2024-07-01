<?php

declare(strict_types=1);

namespace DelOlmo\Container;

use PHPUnit\Framework\TestCase;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;
use stdClass;

class ResolverTest extends TestCase
{
    public function testResolveWithClassParameter(): void
    {
        $container = $this->createMock(Container::class);
        $service   = $this->createMock(stdClass::class);
        $parameter = $this->createMock(ReflectionParameter::class);
        $type      = $this->createMock(ReflectionNamedType::class);

        $parameter->method('getType')
                  ->willReturn($type);

        $parameter->method('getName')
                  ->willReturn('service');

        $type->method('isBuiltin')
             ->willReturn(false);

        $type->method('getName')
             ->willReturn(stdClass::class);

        $container->method('get')
                  ->with(stdClass::class)
                  ->willReturn($service);

        $resolver = new Resolver($container);
        $resolved = $resolver->resolve($parameter);

        $this->assertSame($service, $resolved);
    }

    public function testResolveWithOptionalParameter(): void
    {
        $container = $this->createMock(Container::class);
        $parameter = $this->createMock(ReflectionParameter::class);

        $parameter->method('getType')
                  ->willReturn(null);
        $parameter->method('isOptional')
                  ->willReturn(true);
        $parameter->method('getDefaultValue')
                  ->willReturn('default');

        $resolver = new Resolver($container);
        $resolved = $resolver->resolve($parameter);

        $this->assertSame('default', $resolved);
    }

    public function testResolveThrowsExceptionForUnresolvableParameter(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Parameter 'service' is not resolvable");

        $container = $this->createMock(Container::class);
        $parameter = $this->createMock(ReflectionParameter::class);
        $type      = $this->createMock(ReflectionNamedType::class);

        $parameter->method('getType')
                  ->willReturn($type);
        $parameter->method('getName')
                  ->willReturn('service');

        $type->method('isBuiltin')
             ->willReturn(false);
        $type->method('getName')
             ->willReturn('UnresolvableClass');

        $container->method('get')
                  ->with('UnresolvableClass')
                  ->willReturn(new stdClass());

        $resolver = new Resolver($container);
        $resolver->resolve($parameter);
    }

    public function testResolveThrowsExceptionForInvalidType(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Parameter 'service' is not resolvable");

        $container = $this->createMock(Container::class);
        $parameter = $this->createMock(ReflectionParameter::class);
        $type      = $this->createMock(ReflectionNamedType::class);

        $parameter->method('getType')
                  ->willReturn($type);

        $parameter->method('getName')
                  ->willReturn('service');

        $type->method('isBuiltin')
             ->willReturn(true);

        $type->method('getName')
             ->willReturn(stdClass::class);

        $container->method('get')
                  ->with(stdClass::class)
                  ->willReturn(new stdClass());

        $resolver = new Resolver($container);
        $resolver->resolve($parameter);
    }

    public function testResolveAll(): void
    {
        $container = $this->createMock(Container::class);
        $service   = $this->createMock(stdClass::class);
        $parameter = $this->createMock(ReflectionParameter::class);
        $type      = $this->createMock(ReflectionNamedType::class);

        $parameter->method('getType')
                  ->willReturn($type);
        $parameter->method('getName')
                  ->willReturn('service');

        $type->method('isBuiltin')
             ->willReturn(false);
        $type->method('getName')
             ->willReturn(stdClass::class);

        $container->method('get')
                  ->with(stdClass::class)
                  ->willReturn($service);

        $resolver = new Resolver($container);
        $resolved = $resolver->resolveAll([$parameter]);

        $this->assertSame([$service], $resolved);
    }
}
