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

trait DepthAbleTrait
{
    /*###> MUST CONTAIN ###
	(not included in connection with the inability to display the default value in cmd)
    
	protected array|string $depth = '== 0';
    */

    private function configureDepthOption(): void
    {
        $this->configureOption(
            name:           'depth',
            default:        $this->depth,
            description:    'Глубина сканирования файлов: (>= 1, <= 0, == 2, >= 1, == 2)',
            mode:           InputOption::VALUE_REQUIRED,
            shortcut:       'd',
        );
    }

    private function initializeDepthOption(
        InputInterface $input,
        OutputInterface $output,
    ): void {
        $this->initializeOption(
            $input,
            $output,
            'depth',
            $this->depth,
            set:        static fn(?string $userOption, &$option)
                => $option = \array_map(\trim(...), \explode(',', $userOption)),
        );
    }
}
