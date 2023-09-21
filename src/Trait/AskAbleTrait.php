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
    ConfigsService,
    FilesystemService,
    DumpInfoService,
    StringService,
    ParametersService
};

trait AskAbleTrait
{
    /*
    protected bool $ask = true;
    */

    private function configureAskOption(): void
    {
        $this->configureOption(
            name:           'ask',
            default:        $this->ask,
            description:    'Спрашивать ок?',
            mode:           InputOption::VALUE_NEGATABLE,
        );
    }

    private function initializeAskOption(
        InputInterface $input,
        OutputInterface $output,
    ): void {
        $this->initializeOption(
            $input,
            $output,
            'ask',
            $this->ask,
        );
    }
}
