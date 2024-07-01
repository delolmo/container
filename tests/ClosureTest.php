<?php

declare(strict_types=1);

namespace DelOlmo\Container;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

class ClosureTest extends TestCase
{
    public function testInvokeWithInstantiableClass(): void
    {
        $className = stdClass::class;
        $container = $this->createMock(Container::class);
        $resolver  = $this->createMock(Resolver::class);

        $container->method('get')
                  ->with(Resolver::class)
                  ->willReturn($resolver);

        $resolver->method('resolve')
                 ->willReturn([]);

        $closure  = new Closure($className);
        $instance = $closure($container);

        $this->assertInstanceOf($className, $instance);
    }

    public function testInvokeWithNonInstantiableClass(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $container = $this->createMock(Container::class);

        $closure = new Closure(NonInstantiableService::class);
        $closure($container);
    }

    public function testInvokeWithInvalidResolver(): void
    {
        $this->expectException(RuntimeException::class);

        $className = stdClass::class;
        $container = $this->createMock(Container::class);

        $container->method('get')
                  ->with(Resolver::class)
                  ->willReturn(new stdClass());

        $closure = new Closure($className);
        $closure($container);
    }

    public function testInvokeWithServiceWithConstructorArgs(): void
    {
        $container = $this->createMock(Container::class);

        $resolver = $this->createMock(Resolver::class);

        $container->method('get')
                  ->with(Resolver::class)
                  ->willReturn($resolver);

        $resolver->expects(self::once())
                 ->method('resolveAll')
                 ->willReturn([new stdClass()]);

        $closure  = new Closure(ServiceWithConstructorArgs::class);
        $instance = $closure($container);

        $this->assertTrue($instance instanceof ServiceWithConstructorArgs);
    }
}
