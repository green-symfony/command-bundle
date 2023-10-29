<?php

namespace GS\Command\Trait;

use function Symfony\Component\String\u;

use Symfony\Component\Finder\SplFileInfo;
use Carbon\Carbon;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\{
    Path,
    Filesystem
};
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
    ConfigService,
    DumpInfoService,
    BoolService,
    ArrayService,
    RegexService,
    StringService,
    FilesystemService
};
use App\Contracts\{
    AbstractConstructedFromToPathsDataSupplier
};
use GS\Command\Contracts\PatternAbleCommandInterface;

/*
    Realize your own parser in a certain class:
        PARSERS DESCRIPTIONS
        PARSERS API
        PARSER HELPERS
*/
trait AbstractPatternAbleCommandTrait
{
    /*###> MUST CONTAIN ###

        use AbstractPatternAbleCommandTrait;

        public function __construct(
			$devLogger,
			$t,
			array $progressBarSpin,
			//
            protected readonly StringService $stringService,
            protected readonly DumpInfoService $dumpInfoService,
            protected readonly FilesystemService $filesystemService,
            protected readonly ConfigService $configService,
            protected readonly ArrayService $arrayService,
            protected readonly RegexService $regexService,
            protected readonly BoolService $boolService,
            protected readonly string $yearRegexWithoutB,
            protected readonly string $monthRegex,
            protected readonly string $boardRegexSoft,
        ) {
            parent::__construct(
				devLogger:			$devLogger,
				t:					$t,
				progressBarSpin:	$progressBarSpin,
			);
        }
    */

    private ?string $stringPattern      = null;
    private array $explodedPatterns     = [];

    //###> PARSERS DESCRIPTIONS ###
    //###< PARSERS DESCRIPTIONS ###

    public function patternAbleCommandDuringExecute(
        InputInterface $input,
        OutputInterface $output,
    ): void {
        /* GUARANTEE THAT THE stringPattern WAS GIVEN */
        if ($this->stringPattern !== null) {
            $this->explodedPatterns = $this->getCalculatedExplodedPatterns(
                $this->stringPattern,
            );
        }
    }

    public function patternAbleCommandDuringConfigure(): void
    {
        $this->configurePatternArgument();
    }

    public function patternAbleCommandDuringInitialize(
        InputInterface $input,
        OutputInterface $output,
    ) {
        $this->initializePatternArgument(
            $input,
            $output,
        );
    }


    //###> PARSERS API ###
    //###< PARSERS API ###

    //###> API ###

    protected function getStringPattern(): ?string
    {
        return $this->stringPattern;
    }

    protected function getExplodedPatterns(): array
    {
        return $this->explodedPatterns;
    }

    //###< API ###


    //###> ABSTRACT ###

    /* GUARANTEED THAT stringPattern ALREADY GIVES NOT NULL */
    /* AbstractPatternAbleConstructedFromToCommand
        USE PARSERS API HERE

        EXAMPLE:
            return $this-><use*Parser>(
                $stringPattern,
                <>::SECTION_DELIMITER,
            );
    */
    abstract protected function getCalculatedExplodedPatterns(
        string $stringPattern,
    ): array;
    /* AbstractPatternAbleConstructedFromToCommand */
    abstract protected function getPatternName(): string;
    /* AbstractPatternAbleConstructedFromToCommand */
    abstract protected function getPatternMode(): int;
    /* AbstractPatternAbleConstructedFromToCommand */
    abstract protected function getPatternDescription(): string;

    //###< ABSTRACT ###


    //###> HELPER ###

    private function configurePatternArgument(): void
    {
        $this->addArgument(
            $this->getPatternName(),
            $this->getPatternMode(),
            'Строка наподобии: "' . $this->getPatternDescription() . '"',
        );
    }

    private function initializePatternArgument(
        InputInterface $input,
        OutputInterface $output,
    ): void {
        $this->initializeArgument(
            $input,
            $output,
            $this->getPatternName(),
            $this->stringPattern,
        );
    }

    //###< HELPER ###


    //###> PARSER HELPERS ###
    //###< PARSER HELPERS ###
}
