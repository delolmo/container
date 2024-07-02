<?php

declare(strict_types=1);

namespace DelOlmo\Container;

use DelOlmo\ClassFinder\ClassFinder;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;
use stdClass;

use function sprintf;

class ContainerTest extends TestCase
{
    public function testAutowireWithInstantiableClass(): void
    {
        $container = new Container();

        $classFinder = $this->createMock(ClassFinder::class);

        $classFinder
            ->method('findAll')
            ->willReturn([stdClass::class]);

        $container->autowire('./', $classFinder);

        Assert::assertTrue($container->has(stdClass::class));
    }

    public function testAutowireWithNonInstantiableClass(): void
    {
        $container = new Container();

        $classFinder = $this->createMock(ClassFinder::class);

        $classFinder
            ->method('findAll')
            ->willReturn([NonInstantiableService::class]);

        $container->autowire('./', $classFinder);

        Assert::assertFalse($container->has(NonInstantiableService::class));
    }

    public function testGetReturnsSelfForContainerClass(): void
    {
        $container = new Container();

        Assert::assertSame($container, $container->get(Container::class));
        Assert::assertSame($container, $container->get(ContainerInterface::class));
    }

    public function testReturnsResolverClass(): void
    {
        $container = new Container();

        Assert::assertTrue($container->get(Resolver::class) instanceof Resolver);
    }

    public function testGetThrowsExceptionForUnknownService(): void
    {
        $container      = new Container();
        $unknownService = 'NonExistentService';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'Unable to find service of type \'%s\'',
            $unknownService,
        ));

        $container->get($unknownService);
    }

    public function testGetReturnsServiceIfExists(): void
    {
        $container = new Container();
        $service   = new stdClass();
        $container->set(stdClass::class, $service);

        Assert::assertSame($service, $container->get(stdClass::class));
    }

    public function testGetReturnsServiceIfParentExists(): void
    {
        $container = new Container();
        $service   = new InstantiableService();
        $container->set(stdClass::class, $service);

        Assert::assertSame($service, $container->get(InstantiableService::class));
    }

    public function testHasReturnsFalseForUnknownService(): void
    {
        $container = new Container();

        Assert::assertFalse($container->has('NonExistentService'));
    }

    public function testHasReturnsTrueForKnownService(): void
    {
        $container = new Container();
        $container->set(stdClass::class, new stdClass());

        Assert::assertTrue($container->has(stdClass::class));

        $container->set('service_key', new stdClass());

        Assert::assertTrue($container->has('service_key'));
    }

    public function testRegisterServiceProvider(): void
    {
        $container = new Container();

        $provider = $this->createMock(ServiceProvider::class);

        $provider
            ->expects(self::once())
            ->method('register')
            ->with($container);

        $container->register($provider);
    }

    public function testSetAddsServiceToContainer(): void
    {
        $container = new Container();
        $service   = new stdClass();
        $container->set(stdClass::class, $service);

        Assert::assertTrue($container->has(stdClass::class));
        Assert::assertSame($service, $container->get(stdClass::class));
    }

    public function testSetStaticFunctionAndGetInstance(): void
    {
        $container = new Container();
        $container->set(InstantiableService::class, static fn () => new InstantiableService());

        Assert::assertTrue($container->has(InstantiableService::class));
        Assert::assertTrue($container->get(InstantiableService::class) instanceof InstantiableService);
    }

    public function testSetCallableAndGetInstance(): void
    {
        $container = new Container();
        $container->set(InstantiableService::class, new class () {
            public function __invoke(): InstantiableService
            {
                return new InstantiableService();
            }
        });

        Assert::assertTrue($container->has(InstantiableService::class));
        Assert::assertTrue($container->get(InstantiableService::class) instanceof InstantiableService);
    }

    public function testSetClosureAndGetInstance(): void
    {
        $container = new Container();
        $container->set(InstantiableService::class, new Closure(InstantiableService::class));

        Assert::assertTrue($container->has(InstantiableService::class));
        Assert::assertTrue($container->get(InstantiableService::class) instanceof InstantiableService);
    }
}
