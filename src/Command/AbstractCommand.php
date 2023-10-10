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
    //###> CONSTANTS CHANGE ME ###
    protected const WIDTH_PROGRESS_BAR = 40;
    protected const EMPTY_COLOR_PROGRESS_BAR = 'black';
    protected const PROGRESS_COLOR_PROGRESS_BAR = 'bright-blue';
    //###< CONSTANTS CHANGE ME ###

    protected SymfonyStyle $style;
    protected $formatter;
    protected $progressBar;
    protected $table;

    public readonly string $initialCwd;

    public function __construct(
        protected $devLogger,
        protected $t,
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

	/*
		gets the \Symfony\Component\Console\Style\SymfonyStyle object
	*/
    public function getIo(): SymfonyStyle
    {
        return $this->io;
    }

	/*
		gets the \Symfony\Component\Console\Helper\Table object
	*/
    public function getTable(): Table
    {
        return $this->table;
    }

    //###< PUBLIC API ###


    //###> API ###

	/*
		Usage:
		
		protected function configure(): void
		{
			$this-><methodName>(
				name:			<NAME>,
				default:        <PROPERTY>,
				description:    <DESCRIPTION>,
				mode:           InputOption::<CONSTANT>,
				shortcut:       <SHORTCUT>,
			);
		}
	*/
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

	/*
		Usage:
		
		protected function configure(): void
		{
			$this-><methodName>(
				name:			<NAME>,
				mode:           InputArgument::<CONSTANT>,
				description:    <DESCRIPTION>,
			);
		}
	*/
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

	/*
		Usage:
		
		protected function initialize(): void
		{
			$this-><methodName>(
				input:			<InputInterface object>,
				output:			<OutputInterface object>,
				name:			<NAME>,
				option:			$this-><OPTION>,
				predicat:		<predicat CALLABLE>,
				set:			<set CALLABLE>,
			);
		}
	*/
    protected function initializeOption(
        InputInterface $input,
        OutputInterface $output,
        string $name,
        &$option,
        \Closure|\callable|null $predicat = null,
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


	/*
		Usage:
		
		protected function initialize(): void
		{
			$this-><methodName>(
				input:			<InputInterface object>,
				output:			<OutputInterface object>,
				name:			<NAME>,
				argument:		$this-><ARGUMENT>,
				predicat:		<predicat CALLABLE>,
				set:			<set CALLABLE>,
			);
		}
	*/
    protected function initializeArgument(
        InputInterface $input,
        OutputInterface $output,
        string $name,
        &$argument,
        \Closure|\callable $predicat = null,
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

	/*
		Gets the TRANSLATED description of the option or argument configuration
	*/
    protected function getInfoDescription(
        int $mode,
        string $description,
        mixed $default,
    ): string {
		$description = $this->t->trans($description);
		
        if ($mode === InputOption::VALUE_NEGATABLE && gettype($default) === 'boolean') {
            return (string) u(((string) u($description)->ensureEnd(' ')) . $this->getDefaultValueNegatableForHelp($default))->collapseWhitespace();
        }

        return (string) u(((string) u($description)->ensureEnd(' ')) . $this->getDefaultValueForHelp($default))->collapseWhitespace();
    }

	/*
		Gets part of the option description
	*/
    protected function getDefaultValueForHelp(
        ?string $default,
    ): string {
        if ($default === null) {
            return '';
        }
        return '<bg=black;fg=yellow>[default: ' . $default . ']</>';
    }

	/*
		Gets boolean part of the option description
	*/
    protected function getDefaultValueNegatableForHelp(
        ?bool $bool,
    ): string {
        if ($bool === null) {
            return '';
        }
        return $this->getDefaultValueForHelp($bool ? '"yes"' : '"no"');
    }

	/*
		Asks user in the console: MOVE ON?
	*/
    protected function isOk(
        array|string $message = 'gs_command.command.default.is_ok',
        bool $default = true,
        bool $exitWhenDisagree = false,
    ) {
		$message = $this->t->trans($message);
		
        $agree = $this->io->askQuestion(
            new ConfirmationQuestion(
                \is_array($message) ? \implode(\PHP_EOL, $message) : $message,
                $default,
            )
        );

        if ($exitWhenDisagree && !$agree) {
            $this->exit('gs_command.command.exit_disagree');
        }

        return $agree;
    }

	/*
		Dumps the TRANSLATED message
		
		Executes callback
		
		And afther all this exits from the command
	*/
    protected function exit(
        array|string|null $message = null,
        \Closure|callable|null $callback = null,
    ) {
        $this->devLogger->info(__METHOD__);

        //###> message
        $this->io->writeln('');
        if ($message !== null) {
            $this->io->warning(
				$this->t->trans($message),
			);
        } else {
            $this->io->warning(
				$this->t->trans('gs_command.command.default.exit'),
			);
        }

        //###> callback before the exit
        if (!\is_null($callback)) {
            $callback();
        }

        //###> the exit
        exit(Command::INVALID);
    }

	/*
		Alias for exit with the defined message
	*/
    protected function shutdown(): void
    {
        $this->exit(
            $this->t->trans(
                'gs_command.command.shutdown',
                parameters: [
                    '%command%' => $this->getName(),
                ],
            )
        );
    }

    //###< API ###


    //###> YOU CAN OVERRIDE IT  ###

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

    //###< YOU CAN OVERRIDE IT ###


    //###> REALIZED ABSTRACT ###

    /*
        protected function execute(
            InputInterface $input,
            OutputInterface $output,
        ): int {
            // OK
            return Command::SUCCESS;

            // Incorrect usage
            return Command::INVALID;

            // Program failure
            return Command::FAILURE;
        }
    */

    /* Command */
    protected function configure(): void
    {
        //\pcntl_signal(\SIGINT, $this->shutdown(...));
        //\register_shutdown_function($this->shutdown(...));

        /*###> parent::configure() AT THE END ###*/
        parent::configure();
    }

    /* Command */
    protected function initialize(
        InputInterface $input,
        OutputInterface $output,
    ) {
        /*###> parent::initialize() AT THE BEGINNING ###*/
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

    //###< REALIZED ABSTRACT ###
}
