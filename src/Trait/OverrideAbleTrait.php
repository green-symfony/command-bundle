<?php

namespace GS\Command\Trait;

use Symfony\Component\Console\Question\ConfirmationQuestion;
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
use App\Command\AbstractCommand;
use GS\Service\Service\{
    ConfigService,
    FilesystemService,
    DumpInfoService,
    StringService,
    ParametersService
};

trait OverrideAbleTrait
{
    protected bool $override        = false;
    protected bool $askOverride     = true;

    protected function configureOverrideOptions(): void
    {
        $this->configureOption(
            name:           'override',
            default:        $this->override,
            description:    '!ОПАСНЫЙ ФЛАГ! Перезаписывать даже более новые файлы',
            mode:           InputOption::VALUE_NEGATABLE,
            shortcut:       'o',
        );

        $this->configureOption(
            name:           'ask-override',
            default:        $this->askOverride,
            description:    'Спрашивать об уверенности перезаписи',
            mode:           InputOption::VALUE_NEGATABLE,
        );
    }

    protected function initializeOverrideOptions(
        InputInterface $input,
        OutputInterface $output,
        AbstractCommand $command,
    ): void {
        $this->initializeOption(
            $input,
            $output,
            'ask-override',
            $this->askOverride,
        );

        $do = function (
            string $overrideUserOption,
            &$override,/*by ref*/
        ) use ($command) {
            if ($overrideUserOption == true) {
                if ($this->askOverride) {
                    $anwer = $command->getIo()->askQuestion(
                        new ConfirmationQuestion(
                            'Overwrite even newer files?',
                            $override,
                        )
                    );
                } else {
                    $anwer = true;
                }
                if ($anwer == true) {
                    $override = true;
                }
            } else {
                $override = $overrideUserOption;
            }
        };
        $this->initializeOption(
            $input,
            $output,
            name:           'override',
            option:         $this->override,
            predicat:       static fn(?string $userOption, &$option/*by ref*/)
                => $userOption !== null && $option !== true,
            set:            static fn(?string $userOption, &$option/*by ref*/)
                => $do($userOption, $option),
        );
    }
}
