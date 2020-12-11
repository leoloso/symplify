<?php

declare(strict_types=1);

namespace Symplify\SymfonyStaticDumper\Tests\Routing;

use Symplify\PackageBuilder\Testing\AbstractKernelTestCase;
use Symplify\SymfonyStaticDumper\Routing\RoutesProvider;
use Symplify\SymfonyStaticDumper\Tests\TestProject\HttpKernel\TestSymfonyStaticDumperKernel;

final class RoutesProviderTest extends AbstractKernelTestCase
{
    /**
     * @var RoutesProvider
     */
    private $routesProvider;

    protected function setUp(): void
    {
        $this->bootKernel(TestSymfonyStaticDumperKernel::class);

        $this->routesProvider = $this->getService(RoutesProvider::class);
    }

    /**
     * @requires PHP 8.0
     */
    public function testProvideRoute(): void
    {
        $this->assertCount(6, $this->routesProvider->provide());
    }

    /**
     * @requires PHP <= 7.4
     */
    public function testProvideRouteWithAnnotations(): void
    {
        $this->assertCount(2, $this->routesProvider->provide());
    }

    /**
     * @requires PHP 8.0
     */
    public function testGetRouteWithoutArguments(): void
    {
        $this->assertCount(4, $this->routesProvider->provideRoutesWithoutArguments());
    }

    /**
     * @requires PHP <= 7.4
     */
    public function testGetRouteWithoutArgumentsWithAnnotations(): void
    {
        $this->assertCount(2, $this->routesProvider->provideRoutesWithoutArguments());
    }

    /**
     * @requires PHP 8.0
     */
    public function testGetRouteWithParameters(): void
    {
        $this->assertCount(2, $this->routesProvider->provideRoutesWithParameters());
    }

    /**
     * @requires PHP <= 7.4
     */
    public function testGetRouteWithParametersWithAnnotations(): void
    {
        $this->assertCount(2, $this->routesProvider->provideRoutesWithParameters());
    }
}
