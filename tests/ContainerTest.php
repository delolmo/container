<?php

declare(strict_types=1);

namespace DelOlmo\Container;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;
use stdClass;

use function sprintf;

class ContainerTest extends TestCase
{
    public function testGetReturnsSelfForContainerClass(): void
    {
        $container = new Container();

        Assert::assertSame($container, $container->get(Container::class));
        Assert::assertSame($container, $container->get(ContainerInterface::class));
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
        $service   = new ServiceMock();
        $container->set(stdClass::class, $service);

        Assert::assertSame($service, $container->get(ServiceMock::class));
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

        Assert::assertFalse($container->has('service_key'));
    }

    public function testSetAddsServiceToContainer(): void
    {
        $container = new Container();
        $service   = new stdClass();
        $container->set(stdClass::class, $service);

        Assert::assertTrue($container->has(stdClass::class));
        Assert::assertSame($service, $container->get(stdClass::class));
    }

    public function testSetClosureAndGetInstance(): void
    {
        $container = new Container();
        $container->set(ServiceMock::class, static fn () => new ServiceMock());

        Assert::assertTrue($container->has(ServiceMock::class));
        Assert::assertTrue($container->get(ServiceMock::class) instanceof ServiceMock);
    }
}
