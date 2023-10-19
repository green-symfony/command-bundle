<?php

namespace GS\Command\Trait;

use Symfony\Component\Console\Input\{
    InputArgument,
    InputOption,
    InputInterface
};
use Symfony\Component\Console\Output\{
    OutputInterface
};
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use GS\Service\Service\{
    ConfigService,
    FilesystemService,
    DumpInfoService,
    StringService,
    ParametersService
};

trait DumpInfoAbleTrait
{
    /*###> MUST CONTAIN ### (not included in connection with the inability to display the default value in cmd)
    protected bool $dumpInfo = true;
    */

    //###> PUBLIC API ###

    public function isDumpInfo(): bool
    {
        return $this->dumpInfo;
    }

    //###< PUBLIC API ###


    private function configureDumpInfoOption(): void
    {
        $this->configureOption(
            name:           'dump-info',
            default:        $this->dumpInfo,
            description:    'Показывать информацию о выполнении программы?',
            mode:           InputOption::VALUE_NEGATABLE,
        );
    }

    private function initializeDumpInfoOption(
        InputInterface $input,
        OutputInterface $output,
    ): void {
        $this->initializeOption(
            $input,
            $output,
            'dump-info',
            $this->dumpInfo,
        );
    }
}
