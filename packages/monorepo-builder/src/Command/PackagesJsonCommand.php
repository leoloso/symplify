<?php

declare(strict_types=1);

namespace Symplify\MonorepoBuilder\Command;

use Nette\Utils\Json;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symplify\MonorepoBuilder\Json\PackageJsonProvider;
use Symplify\PackageBuilder\Console\Command\AbstractSymplifyCommand;
use Symplify\PackageBuilder\Console\ShellCode;

final class PackagesJsonCommand extends AbstractSymplifyCommand
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
        $this->setDescription('Provides package paths in json format. Useful for GitHub Actions Workflow');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $packageEntries = $this->packageJsonProvider->providePackageEntries();

        // must be without spaces, otherwise it breaks GitHub Actions json
        $json = Json::encode($packageEntries);
        $this->symfonyStyle->writeln($json);

        return ShellCode::SUCCESS;
    }
}
