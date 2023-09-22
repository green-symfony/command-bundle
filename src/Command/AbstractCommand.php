<?php

namespace GS\Command\Command;

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
abstract class AbstractCommand extends Command
	implements SignalableCommandInterface,
	ServiceSubscriberInterface
{
	//###> ! CHANGE ME !
    protected const WIDTH_PROGRESS_BAR			= 40;
    protected const EMPTY_COLOR_PROGRESS_BAR	= 'black';
    protected const PROGRESS_COLOR_PROGRESS_BAR = 'cyan';
	//###< ! CHANGE ME !

    protected SymfonyStyle $style;
    protected $formatter;
    protected $progressBar;
    protected $table;

    public function __construct(
		protected $devLogger,
	) {
        parent::__construct();
		
        ProgressBar::setPlaceholderFormatterDefinition(
            'spin',
            static function (
                ProgressBar $progressBar,
                OutputInterface $output,
            ) {
                static $i = 0;
                //https://raw.githubusercontent.com/sindresorhus/cli-spinners/master/spinners.json
                $spin = [
                    "🕐 ",
                    "🕑 ",
                    "🕒 ",
                    "🕓 ",
                    "🕔 ",
                    "🕕 ",
                    "🕖 ",
                    "🕗 ",
                    "🕘 ",
                    "🕙 ",
                    "🕚 ",
                    "🕛 ",
                ];
                if ($i >= \count($spin)) {
                    $i = 0;
                }
                return $spin[$i++];
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
		
		/* AT THE END parent::configure(); */
    }

	/* Command */
    protected function initialize(
        InputInterface $input,
        OutputInterface $output,
    ) {
		/* AT THE BEGINNING
		
		parent::initialize(
            $input,
            $output,
        );
		*/
        
		//###> Locale/Charset
        //\ini_set('mbstring.internal_encoding', 'UTF-8');
        \setlocale(LC_ALL, 'Russian');
		
        //###> Objects
        $this->io = new SymfonyStyle($input, $output);
		
        //###> Style
        $this->setFormatter();
        $this->setProgressBar();
        $this->setTable();
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
        null|array|string $message = null,
    ) {
        if ($message !== null) {
            $this->io->warning($message);
        } else {
            $this->io->warning('Exit');
		}
        exit(Command::INVALID);
    }
	
	protected function shutdown(): void {
		$this->devLogger->info(__METHOD__);
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
	
	
	//###> HELPER ###

    private function setFormatter()
    {
        $this->formatter = $this->getHelper('formatter');
    }

    /*
        protected const WIDTH_PROGRESS_BAR = 20;

        $this->progressBar->setMaxSteps(<int>);
        $this->progressBar->start();
    */
    private function setProgressBar()
    {
        $this->progressBar = $this->io->createProgressBar();
        $this->progressBar->setEmptyBarCharacter("<bg=" . static::EMPTY_COLOR_PROGRESS_BAR . "> </>");
        $this->progressBar->setProgressCharacter("<bg=" . static::EMPTY_COLOR_PROGRESS_BAR . ";fg=" . static::EMPTY_COLOR_PROGRESS_BAR . "> </>");
        $this->progressBar->setBarCharacter("<bg=" . static::PROGRESS_COLOR_PROGRESS_BAR . "> </>");
        $this->progressBar->setBarWidth(static::WIDTH_PROGRESS_BAR);
    }

    private function setTable()
    {
        $this->table = $this->io->createTable();
        $this->table->setStyle('box-double');
		/*
        $this->table->setStyle(
            (new TableStyle())
            ->setHorizontalBorderChars('-')
            ->setVerticalBorderChars('|')
            ->setDefaultCrossingChar('+')
        );
		*/
    }
	
	//###> HELPER ###
}
