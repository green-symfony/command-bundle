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

trait MoveAbleTrait
{
	protected bool $move = false;

    protected function configureMoveOption(): void
    {
        $this->configureOption(
            name:           'move',
            default:        $this->move,
            description:    'Переместить вместо копирования',
            mode:           InputOption::VALUE_NEGATABLE,
        );
    }

    protected function initializeMoveOption(
        InputInterface $input,
        OutputInterface $output,
    ): void {
        $this->initializeOption(
            $input,
            $output,
            'move',
            $this->move,
        );
    }
}
