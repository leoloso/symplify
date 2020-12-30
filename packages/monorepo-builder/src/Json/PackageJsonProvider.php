<?php

declare(strict_types=1);

namespace Symplify\MonorepoBuilder\Json;

use Nette\Utils\Strings;
use Symplify\MonorepoBuilder\Package\PackageProvider;
use Symplify\MonorepoBuilder\Parameter\ParameterSupplier;
use Symplify\MonorepoBuilder\ValueObject\Option;
use Symplify\PackageBuilder\Parameter\ParameterProvider;
use Symplify\SymplifyKernel\Exception\ShouldNotHappenException;

final class PackageJsonProvider
{
    /**
     * @var PackageProvider
     */
    private $packageProvider;

    /**
     * @var array<string, mixed[]>
     */
    private $packageDirectoriesData = [];

    public function __construct(
        PackageProvider $packageProvider,
        ParameterProvider $parameterProvider,
        ParameterSupplier $parameterSupplier
    ) {
        $this->packageProvider = $packageProvider;
        $this->packageDirectoriesData = $parameterSupplier->fillPackageDirectoriesWithDefaultData(
            $parameterProvider->provideArrayParameter(Option::PACKAGE_DIRECTORIES)
        );
    }

    /**
     * @return array<string[]>
     */
    public function providePackageEntries(): array
    {
        $packageEntries = [];
        foreach ($this->packageProvider->provide() as $package) {
            $packageEntries[] = [
                'name' => $package->getShortName(),
                'path' => $package->getRelativePath(),
                'directory' => dirname($package->getRealPath()),
            ];
        }

        return $packageEntries;
    }

    /**
     * @return array<string, mixed[]>
     */
    public function providePackageDirData(): array
    {
        return $this->packageDirectoriesData;
    }
}
