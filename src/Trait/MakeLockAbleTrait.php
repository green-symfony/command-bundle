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

trait MakeLockAbleTrait
{
    /*###> MUST CONTAIN ### (not included in connection with the inability to display the default value in cmd)
    protected bool $makeLock = true;
    */

    private function configureLockOption(): void
    {
        $this->configureOption(
            name:           'make-lock',
            default:        $this->makeLock,
            description:    'Запретить одновременное выполнение одной и той же команды',
            mode:           InputOption::VALUE_NEGATABLE,
        );
    }

    private function initializeLockOption(
        InputInterface $input,
        OutputInterface $output,
    ): void {
        $this->initializeOption(
            $input,
            $output,
            'make-lock',
            $this->makeLock,
        );
    }
}
