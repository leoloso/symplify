<?php

declare(strict_types=1);

namespace Symplify\MonorepoBuilder\Command;

use Nette\Utils\Json;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symplify\MonorepoBuilder\Json\PackageJsonProvider;
use Symplify\PackageBuilder\Console\Command\AbstractSymplifyCommand;
use Symplify\PackageBuilder\Console\ShellCode;

final class PackageDirDataJsonCommand extends AbstractSymplifyCommand
{
    /**
     * @var PackageJsonProvider
     */
    private $packageJsonProvider;

    public function __construct(PackageJsonProvider $packageJsonProvider)
    {
        $this->packageJsonProvider = $packageJsonProvider;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Provides organizations for each package directory, in json format. Useful for GitHub Actions Workflow');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $packageDirData = $this->packageJsonProvider->providePackageDirData();

        // must be without spaces, otherwise it breaks GitHub Actions json
        $json = Json::encode($packageDirData);
        $this->symfonyStyle->writeln($json);

        return ShellCode::SUCCESS;
    }
}
