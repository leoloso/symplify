<?php

declare(strict_types=1);

namespace Symplify\PHPStanRules\Tests\Rules\NoStaticPropertyRule\Fixture;

use PHPStan\DependencyInjection\Container;

final class SkipStaticContainerPHPStan
{
    /**
     * @var Container[]
     */
    public static $containers = [];

    public function getContainers(): array
    {
        return self::$containers;
    }
}
