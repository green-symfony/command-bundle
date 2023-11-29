<?php

namespace GS\Command\Command;

use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Finder\Finder;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Helper\{
    ProgressBar,
    Table
};
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Validator\{
    Constraints,
    Validation
};
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\{
    TableSeparator
};
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Completion\{
    CompletionSuggestions,
    CompletionInput
};
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\{
    AsCommand
};
use Symfony\Component\Console\Input\{
    InputArgument,
    InputOption,
    InputInterface
};
use Symfony\Component\Console\Output\{
    OutputInterface
};
use GS\Service\Service\{
    StringService,
    RegexService,
    ClipService,
    ConfigService
};
use GS\Command\Contracts\DataCallbackConnector;

abstract class AbstractDisplayCommand extends AbstractCommand
{
    private ?string $userPattern = null;
    private array $excludeKeys = [];
    private int $counter = 0;
    private $clip = null;
	
    public function __construct(
        $devLogger,
        $t,
        array $progressBarSpin,
        //
        protected readonly StringService $stringService,
        protected readonly ClipService $clipService,
        protected readonly RegexService $regexService,
    ) {
        parent::__construct(
            devLogger:          $devLogger,
            t:                  $t,
            progressBarSpin:    $progressBarSpin,
        );
    }
	
	
	//###> ABSTRACT ###
	
	/* EXAMPLE: 
		[
			'',
			...
		]
	*/
	/* AbstractGrepCommand */
	abstract protected function getDataCallbackConnectorsIntoCycle(
        InputInterface $input,
        OutputInterface $output,
	): \Generator;
	
	/* AbstractGrepCommand */
	abstract protected function getExcludedKeys(
        InputInterface $input,
        OutputInterface $output,
	): array;
	
	/* AbstractGrepCommand */
	abstract protected function getTranslationPrefix(
        InputInterface $input,
        OutputInterface $output,
	): string;
	
	/* AbstractGrepCommand */
	abstract protected function isEscapeRegexCharacters(): bool;
	
	/* AbstractGrepCommand */
	abstract protected function isCopyFirstResult(): bool;
	
	/* AbstractGrepCommand */
	abstract protected function isShowResultsQuantity(): bool;
	
	
	//###< ABSTRACT ###
	
	
	//###> CAN OVERRIDE ###
	
	/* AbstractGrepCommand */
	protected function isSkipStringForDisplay(): bool {
		return false;
	}
	
	/* AbstractGrepCommand */
	protected function displayInfo(
        InputInterface $input,
        OutputInterface $output,
        string|int|float $title,
        mixed $dop = null,
    ): void {
		if (\is_array($dop)) {
			$getTranslationPrefix = $this->getTranslationPrefix(...);
            $resultShow = [];

            $getTranslateKey = static fn(
				string $name,
			) => $getTranslationPrefix($input, $output) . $name;
            $translatedMessages = \array_map(
				fn($v) => $this->t->trans(
					$getTranslateKey($v)
				),
				\array_keys($dop),
			);
            
			\array_walk($dop, function ($v, $k) use (&$resultShow, &$dop, &$getTranslateKey, &$translatedMessages) {
                if (\is_array($v)) {
                    return;
                }

                $translatedMessage      = $this->t->trans($getTranslateKey($k));
                $width = $this->stringService->getOptimalWidthForStrPad($translatedMessage, $translatedMessages);
                $resultShow[] = ""
                    . \str_pad($translatedMessage . ":", $width)
                    . $v
                ;
            });
            $this->getIo()->note([$title, ...$resultShow]);
            return;
        } else {
            $dop ? $this->getIo()->note([$title, $dop]) : $this->getIo()->note($title);
            return;
        }
    }
	
	//###< CAN OVERRIDE ###
	
	
	//###> API ###
	
	/*
		Use it into the cycle
	*/
	protected function process(
        InputInterface $input,
        OutputInterface $output,
        $stringForComparsionWithUserPattern,
        $dop = null,
    ): void {
		$s = $stringForComparsionWithUserPattern;
		
		if (
            false
            || \is_null($s)
            || (!\is_string($s) && !\is_int($s) && !\is_float($s))
            || !\preg_match('~' . $this->userPattern . '~ui', $s)
            || $this->isSkipStringForDisplay($s)
        ) {
            return;
        }
        $this->counter++;

		$this->removeExcludedKeysFromResult(
			$input,
			$output,
			$dop,
		);

        $this->clip(
            $s,
        );

        $this->displayInfo(
            $input,
            $output,
            $s,
            $dop,
        );
    }
	
	//###< API ###
	

    //###> ABSTRACT REALIZATION ###

    protected function configure()
    {
        $this->configureArgument(
            'pattern',
            mode:           InputArgument::REQUIRED,
            description:    $this->t->trans('Строка поиска (регулярое выражение)'),
        );

        parent::configure();
    }

    public function initialize(
        InputInterface $input,
        OutputInterface $output,
    ) {
        parent::initialize(
            $input,
            $output,
        );
		
		//###>
		$ek = $this->getExcludedKeys(
			$input,
			$output,
		);
		$this->excludeKeys = \array_combine($ek, $ek);

		//###>
		$getEscapedStrings = $this->regexService->getEscapedStrings(...);
		$isEscapeRegexCharacters = $this->isEscapeRegexCharacters();
		$userPatternSetter = static function(
			?string $userArgument,
			&$argument/*by ref*/
		) use (&$getEscapedStrings, $isEscapeRegexCharacters) {
			
			if ($isEscapeRegexCharacters) {
				$userArgument = $getEscapedStrings($userArgument);
			}
			$argument = $userArgument;
		};
        $this->initializeArgument(
            $input,
            $output,
            'pattern',
            $this->userPattern,
			set: $userPatternSetter,
        );
    }

    /* AbstractCommand */
    protected function command(
        InputInterface $input,
        OutputInterface $output,
    ): int {
		
        foreach ($this->getDataCallbackConnectorsIntoCycle($input, $output) as $dcc) {
			$dccType = \gettype($dcc);
			if (!$dcc instanceof DataCallbackConnector) {
				throw new \Exception(
					$this->t->trans(
						'gs_command.exception.type',
						[
							'%given_type%' => $dccType == 'object' ? \get_class($dcc) : $dccType,
							'%expected_type%' => DataCallbackConnector::class,
						],
					),
				);
			}
            $dcc();
        }
		
		$this->dumpClipped(
			$input,
			$output,
		);
        
		$this->dumpCount(
			$input,
			$output,
		);

        return Command::SUCCESS;
    }

    //###< ABSTRACT REALIZATION ###


    //###> HELPER ###

    private function removeExcludedKeysFromResult(
        InputInterface $input,
        OutputInterface $output,
		&$data,
	): void {
		if (!\is_array($data)) return;
		
		$excludeKeys = $this->excludeKeys;
		$data = \array_diff_key($data, $excludeKeys);
    }

    private function dumpClipped(
        InputInterface $input,
        OutputInterface $output,
    ): void {
		if (!$this->isCopyFirstResult()) return;
		
		if ($this->clip === null) {
			$copiedMess = 'Нечего копировать';
		} else {
			$copiedMess = '"' . $this->clip . '" copied';
		}
		$output->writeln('<bg=green;fg=black>' . $copiedMess . '</>');
    }

    private function dumpCount(
        InputInterface $input,
        OutputInterface $output,
    ): void {
        if ($this->isShowResultsQuantity()) {
			$output->writeln('<bg=yellow;fg=black>Результатов: ' . $this->counter . '</>');
		}
    }

    private function clip(
        $string,
    ): bool {
		if ($this->isCopyFirstResult() && $this->clip === null) {
			$this->clip = $string;
			$this->clipService->copy($string);
			return true;
		}
        return false;
    }
	
    //###< HELPER ###
}
