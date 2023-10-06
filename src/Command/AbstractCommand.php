<?php

namespace GS\Command\Command;

use function Symfony\Component\String\u;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\{
    Path
};
use Symfony\Component\Console\Question\{
    Question,
    ConfirmationQuestion
};
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Component\Console\Command\{
    Command,
    LockableTrait,
    SignalableCommandInterface
};
use Symfony\Component\Console\Helper\{
    ProgressBar,
    Table,
    TableStyle,
    TableSeparator
};
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\{
    AsCommand
};
use Symfony\Component\Console\Input\{
    InputInterface
};
use Symfony\Component\Console\Output\{
    OutputInterface
};

// PROJECT_DIR/bin/console <command>
/*
#[AsCommand(
    name: '<>',
    description: '<>',
    hidden: <bool>,
)]
*/
abstract class AbstractCommand extends Command implements
    SignalableCommandInterface,
    ServiceSubscriberInterface
{
    //###> ! CHANGE ME !
    protected const WIDTH_PROGRESS_BAR          = 40;
    protected const EMPTY_COLOR_PROGRESS_BAR    = 'black';
    protected const PROGRESS_COLOR_PROGRESS_BAR = 'bright-blue';
    //###< ! CHANGE ME !

    protected SymfonyStyle $style;
    protected $formatter;
    protected $progressBar;
    protected $table;

    public readonly string $initialCwd;

    public function __construct(
        protected $devLogger,
        protected readonly array $progressBarSpin,
    ) {
        $this->initialCwd = Path::normalize(\getcwd());

        parent::__construct();

        ProgressBar::setPlaceholderFormatterDefinition(
            'spin',
            static function (
                ProgressBar $progressBar,
                OutputInterface $output,
            ) use (&$progressBarSpin) {
                static $i = 0;
                if ($i >= \count($progressBarSpin)) {
                    $i = 0;
                }
                return $progressBarSpin[$i++];
            }
        );

        ProgressBar::setFormatDefinition('normal', '%bar% %percent:2s%% %spin%');
        ProgressBar::setFormatDefinition('normal_nomax', '%bar% progress: %current% %spin%');
    }


    //###> PUBLIC API ###

    public function getIo(): SymfonyStyle
    {
        return $this->io;
    }

    public function getTable(): Table
    {
        return $this->table;
    }

    //###< PUBLIC API ###


    //###> API ###

    protected function configureOption(
        string $name,
        string $description,
        int $mode,
        mixed $default = null,
        string|array $shortcut = null,
    ): void {
        if ($shortcut === null) {
            $this
                ->addOption(
                    name:           $name,
                    mode:           $mode,
                    description:    $this->getInfoDescription($mode, $description, $default),
                    default:        $default,
                )
            ;
            return;
        }

        $this
            ->addOption(
                name:           $name,
                shortcut:       $shortcut,
                mode:           $mode,
                description:    $this->getInfoDescription($mode, $description, $default),
                default:        $default,
            )
        ;
    }

    protected function configureArgument(
        string $name,
        int $mode,
        ?string $description = null,
    ) {
        if ($description === null) {
            $this
                ->addArgument(
                    $name,
                    $mode,
                )
            ;
            return;
        }

        $this
            ->addArgument(
                $name,
                $mode,
                $description,
            )
        ;
    }

    protected function initializeOption(
        InputInterface $input,
        OutputInterface $output,
        string $name,
        &$option,
        // predicat callback
        \Closure|\callable|null $predicat = null,
        // set callback
        \Closure|\callable|null $set = null,
    ) {
        /* $userOption always string, != more suitable */
        $predicat ??= static fn(?string $userOption, &$option/*by ref*/)
            => $userOption !== null && $option != $userOption;

        $set ??= static fn(?string $userOption, &$option/*by ref*/) => $option = $userOption;

        $userOption = $input->getOption($name);
        if ($predicat(userOption: $userOption, option: $option)) {
            $set(userOption: $userOption, option: $option);
        }
    }

    protected function initializeArgument(
        InputInterface $input,
        OutputInterface $output,
        string $name,
        &$argument,
        // predicat callback
        \Closure|\callable $predicat = null,
        // set callback
        \Closure|\callable $set = null,
    ) {
        /* $userArgument always string, != more suitable */
        $predicat ??= static fn(?string $userArgument, &$argument/*by ref*/)
            => $userArgument !== null && $argument != $userArgument;

        $set ??= static fn(?string $userArgument, &$argument/*by ref*/) => $argument = $userArgument;

        $userArgument = $input->getArgument($name);
        if ($predicat(userArgument: $userArgument, argument: $argument)) {
            $set(userArgument: $userArgument, argument: $argument);
        }
    }

    protected function getInfoDescription(
        int $mode,
        string $description,
        mixed $default,
    ): string {
        if ($mode === InputOption::VALUE_NEGATABLE && gettype($default) === 'boolean') {
            return (string) u(((string) u($description)->ensureEnd(' ')) . $this->getDefaultValueNegatableForHelp($default))->collapseWhitespace();
        }

        return (string) u(((string) u($description)->ensureEnd(' ')) . $this->getDefaultValueForHelp($default))->collapseWhitespace();
    }

    protected function getDefaultValueForHelp(
        ?string $default,
    ): string {
        if ($default === null) {
            return '';
        }
        return '<bg=black;fg=yellow>[default: ' . $default . ']</>';
    }

    protected function getDefaultValueNegatableForHelp(
        ?bool $bool,
    ): string {
        if ($bool === null) {
            return '';
        }
        return $this->getDefaultValueForHelp($bool ? '"yes"' : '"no"');
    }

    //###< API ###


    //###> REALIZE ABSTRACT ###

    /* Command */
    protected function configure(): void
    {
        //\pcntl_signal(\SIGINT, $this->shutdown(...));
        //\register_shutdown_function($this->shutdown(...));

        /*###> AT THE END ###*/
        parent::configure();
    }

    /* Command */
    protected function initialize(
        InputInterface $input,
        OutputInterface $output,
    ) {
        /*###> AT THE BEGINNING ###*/
        parent::initialize(
            $input,
            $output,
        );

        //###> Objects
        $this->io = new SymfonyStyle($input, $output);

        //###> Style
        $this->setFormatter(
            $input,
            $output,
        );
        $this->setProgressBar(
            $input,
            $output,
        );
        $this->setTable(
            $input,
            $output,
        );
    }

    /* Command */
    protected function interact(
        InputInterface $input,
        OutputInterface $output,
    ) {
        // get missed options/arguments
    }

    /* Command

        Command::<CODE>

            // ок
            return Command::SUCCESS;

            // неправильное использование
            return Command::INVALID;

            // ошибка
            return Command::FAILURE;

        protected function execute(
            InputInterface $input,
            OutputInterface $output,
        ): int {
            return Command::SUCCESS;
        }
    */

    /* Command

        protected function interact(
            InputInterface $input,
            OutputInterface $output,
        ) {
            // get missed options/arguments
        }
    */

    /* SignalableCommandInterface */
    public function getSubscribedSignals(): array
    {
        return [
            //\SIGINT,
            //\SIGTERM,
        ];
    }

    /* SignalableCommandInterface */
    public function handleSignal(int $signal): void
    {
        /*
        if (\SIGINT == $signal) {
            $this->shutdown();
        }
        */
    }

    /* ServiceSubscriberInterface */
    public static function getSubscribedServices(): array
    {
        return [
            'logger' => '?Psr\Log\LoggerInterface',
        ];
    }

    //###< REALIZE ABSTRACT ###


    //###> API ###

    protected function isOk(
        array|string $message = 'Ok?',
        bool $default = true,
        bool $exitWhenDisagree = false,
    ) {
        $agree = $this->io->askQuestion(
            new ConfirmationQuestion(
                \is_array($message) ? \implode(\PHP_EOL, $message) : $message,
                $default,
            )
        );

        if ($exitWhenDisagree && !$agree) {
            $this->exit('EXIT');
        }

        return $agree;
    }

    protected function exit(
        array|string|null $message = null,
        \Closure|callable|null $callback = null,
    ) {
        $this->devLogger->info(__METHOD__);

        //###> message
        $this->io->writeln('');
        if ($message !== null) {
            $this->io->warning($message);
        } else {
            $this->io->warning('Exit');
        }

        //###> callback before the exit
        if (!\is_null($callback)) {
            $callback();
        }

        //###> the exit
        exit(Command::INVALID);
    }

    protected function shutdown(): void
    {
        $this->exit(
            $this->t->trans(
                'Команда %command% остановлена',
                parameters: [
                    '%command%' => $this->getName(),
                ],
            )
        );
    }

    //###< API ###


    //###> OVERRIDE IT ###

    protected function setFormatter(
        InputInterface $input,
        OutputInterface $output,
    ): void {
        $this->formatter = $this->getHelper('formatter');
    }

    /*
        WHEN YOU HAVE THE MAX STEPS USE IT:
            $this->progressBar->setMaxSteps(<int>);
            $this->progressBar->start();
    */
    protected function setProgressBar(
        InputInterface $input,
        OutputInterface $output,
    ): void {
        $this->progressBar = $this->io->createProgressBar();
        $this->progressBar->setEmptyBarCharacter("<bg=" . static::EMPTY_COLOR_PROGRESS_BAR . "> </>");
        $this->progressBar->setProgressCharacter("<bg=" . static::EMPTY_COLOR_PROGRESS_BAR . ";fg=" . static::EMPTY_COLOR_PROGRESS_BAR . "> </>");
        $this->progressBar->setBarCharacter("<bg=" . static::PROGRESS_COLOR_PROGRESS_BAR . "> </>");
        $this->progressBar->setBarWidth(static::WIDTH_PROGRESS_BAR);
    }

    protected function setTable(
        InputInterface $input,
        OutputInterface $output,
    ): void {
        //###> create table
        $this->table = new Table($output); //$this->io->createTable();
        $tableStyle = new TableStyle();

        //###> customize style
        $tableStyle
            ->setHorizontalBorderChars(' ')
            ->setVerticalBorderChars(' ')
            ->setDefaultCrossingChar(' ')
        ;

        //###> set style
        $this->table->setStyle($tableStyle);
    }

    //###> OVERRIDE IT ###
}
