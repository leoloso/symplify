<?php

declare(strict_types=1);

namespace Symplify\EasyCodingStandard\DependencyInjection;

use Symfony\Component\Config\FileLocator as SimpleFileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\GlobFileLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Config\FileLocator;
use Symfony\Component\HttpKernel\KernelInterface;
use Symplify\PackageBuilder\DependencyInjection\FileLoader\ParameterMergingPhpFileLoader;

final class DelegatingLoaderFactory
{
    public function createFromContainerBuilderAndKernel(
        ContainerBuilder $containerBuilder,
        KernelInterface $kernel
    ): DelegatingLoader {
        $kernelFileLocator = new FileLocator($kernel);

        return $this->createFromContainerBuilderAndFileLocator($containerBuilder, $kernelFileLocator);
    }

    /**
     * For tests
     */
    public function createContainerBuilderAndConfig(
        ContainerBuilder $containerBuilder,
        string $config
    ): DelegatingLoader {
        $directory = dirname($config);
        $fileLocator = new SimpleFileLocator($directory);

        return $this->createFromContainerBuilderAndFileLocator($containerBuilder, $fileLocator);
    }

    private function createFromContainerBuilderAndFileLocator(
        ContainerBuilder $containerBuilder,
        SimpleFileLocator $simpleFileLocator
    ): DelegatingLoader {
        $loaders = [
            new GlobFileLoader($simpleFileLocator),
            new ParameterMergingPhpFileLoader($containerBuilder, $simpleFileLocator),
        ];
        $loaderResolver = new LoaderResolver($loaders);
        return new DelegatingLoader($loaderResolver);
    }
}
