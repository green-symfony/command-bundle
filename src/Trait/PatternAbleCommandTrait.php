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

trait PatternAbleCommandTrait
{	
	private ?string $stringPattern      = null;
	private array $explodedPatterns     = [];
		
    //###> PARSER_YEAR_MONTH_BOARD_NUMBER ###
    public const PARSER_YEAR_MONTH_BOARD_NUMBER_SECTION_DELIMITER   = ',';
    public const PARSER_YEAR_MONTH_BOARD_NUMBER_DESCRIPTION         = ''
        . 'Год Месяц Номера_борта (всевозможные человекопонятные варианты, регистронезависимо)'
        . self::PARSER_YEAR_MONTH_BOARD_NUMBER_SECTION_DELIMITER
        . ' ' . '2024 декабрь 22992'
        . self::PARSER_YEAR_MONTH_BOARD_NUMBER_SECTION_DELIMITER
        . ' ' . '24 дек 992'
        . self::PARSER_YEAR_MONTH_BOARD_NUMBER_SECTION_DELIMITER
        . ' ' . '24дек992'
        . self::PARSER_YEAR_MONTH_BOARD_NUMBER_SECTION_DELIMITER
        . ' ' . '24 992дек'
        . self::PARSER_YEAR_MONTH_BOARD_NUMBER_SECTION_DELIMITER
        . ' ' . '24 992'
        . self::PARSER_YEAR_MONTH_BOARD_NUMBER_SECTION_DELIMITER
        . ' ' . '24 дек'
        . self::PARSER_YEAR_MONTH_BOARD_NUMBER_SECTION_DELIMITER
        . ' ' . 'дек24'
        . self::PARSER_YEAR_MONTH_BOARD_NUMBER_SECTION_DELIMITER
        . ' ' . 'дек 992'
        . self::PARSER_YEAR_MONTH_BOARD_NUMBER_SECTION_DELIMITER
        . ' ' . '992 дек'
        . self::PARSER_YEAR_MONTH_BOARD_NUMBER_SECTION_DELIMITER
        . ' ' . '992дек'
        . self::PARSER_YEAR_MONTH_BOARD_NUMBER_SECTION_DELIMITER
        . ' ' . '992'
        . self::PARSER_YEAR_MONTH_BOARD_NUMBER_SECTION_DELIMITER
        . ' ' . 'дек'
        . self::PARSER_YEAR_MONTH_BOARD_NUMBER_SECTION_DELIMITER
        . ' ' . '24'
    ;
    //###< PARSER_YEAR_MONTH_BOARD_NUMBER ###


    protected function patternAbleCommandDuringExecute(
        InputInterface $input,
        OutputInterface $output,
    ): void {
        /* GUARANTEE THAT stringPattern WILL GIVE NOT NULL */
        if ($this->stringPattern !== null) {
            $this->explodedPatterns = $this->getCalculatedExplodedPatterns(
                $this->stringPattern,
            );
			
			/* For correct filtering
				less strict first
				
				Если в первом больше ограничений, идёт ниже
			*/
			\usort(
				$this->explodedPatterns,
				static fn($f, $s) => \count($f) > \count($s),
			);
        }
    }

    protected function patternAbleCommandDuringConfigure(): void
    {
        $this->configurePatternArgument();
    }

    protected function patternAbleCommandDuringInitialize(
        InputInterface $input,
        OutputInterface $output,
    ) {
        $this->initializePatternArgument(
            $input,
            $output,
        );
    }


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
	

    //###> PARSERS API ###

    /*
        INPUT:
            22,22 992,22дек,22дек992

        OUTPUT:
            [
                [
                    'year'          => <>,
                ],
                [
                    'year'          => <>,
                    'boardNumber'   => <>,
                ],
                [
                    'year'          => <>,
                    'month'         => <>,
                ],
                [
                    'year'          => <>,
                    'month'         => <>,
                    'boardNumber'   => <>,
                ],
                ...
            ]
    */
    protected function useParserYearMonthBoardNumber(
        string $stringPattern,
        string $delimiter,
        bool $asIntoConfig = true,
    ): array {
        $yearMonthAndBoardNumber    = [];

        $explodedStringPatterns     = \explode(
            $delimiter,
            $stringPattern,
        );

        $idx = -1; /* ONLY FOR PARSE FUNCTION */
        foreach ($explodedStringPatterns as $explodedStringPattern) {
            ++$idx;

            $explodedStringPattern = \trim($explodedStringPattern);

            $is = static fn(string $regex, string $string)
                => \preg_match($fulRegex = '~^' . $regex . '$~ui', $string) === 1
                    ? $fulRegex
                    : false
                ;

            $Y  = '(?<' . PatternAbleCommandInterface::Y_NAME . '>' . $this->yearRegexWithoutB . ')';
            $M  = '(?<' . PatternAbleCommandInterface::M_NAME . '>' . $this->monthRegex . ')';
            $Bn = '(?<' . PatternAbleCommandInterface::B_N_NAME . '>' . $this->boardRegexSoft . ')';

            //###> Y M Bn

            if ($regex = $is($Y . '\s*' . $M . '\s*' . $Bn, $explodedStringPattern)) {
                $this->parse($yearMonthAndBoardNumber, $regex, $explodedStringPattern, $asIntoConfig, $idx);
                continue;
            }

            if ($regex = $is($Y . '\s+' . $Bn . '\s*' . $M, $explodedStringPattern)) {
                $this->parse($yearMonthAndBoardNumber, $regex, $explodedStringPattern, $asIntoConfig, $idx);
                continue;
            }

            if ($regex = $is($M . '\s*' . $Bn . '\s+' . $Y, $explodedStringPattern)) {
                $this->parse($yearMonthAndBoardNumber, $regex, $explodedStringPattern, $asIntoConfig, $idx);
                continue;
            }

            if ($regex = $is($M . '\s*' . $Y . '\s+' . $Bn, $explodedStringPattern)) {
                $this->parse($yearMonthAndBoardNumber, $regex, $explodedStringPattern, $asIntoConfig, $idx);
                continue;
            }

            if ($regex = $is($Bn . '\s+' . $Y . '\s*' . $M, $explodedStringPattern)) {
                $this->parse($yearMonthAndBoardNumber, $regex, $explodedStringPattern, $asIntoConfig, $idx);
                continue;
            }

            if ($regex = $is($Bn . '\s*' . $M . '\s*' . $Y, $explodedStringPattern)) {
                $this->parse($yearMonthAndBoardNumber, $regex, $explodedStringPattern, $asIntoConfig, $idx);
                continue;
            }

            //###> Y M
            if ($regex = $is($Y . '\s*' . $M, $explodedStringPattern)) {
                $this->parse($yearMonthAndBoardNumber, $regex, $explodedStringPattern, $asIntoConfig, $idx);
                continue;
            }

            if ($regex = $is($M . '\s*' . $Y, $explodedStringPattern)) {
                $this->parse($yearMonthAndBoardNumber, $regex, $explodedStringPattern, $asIntoConfig, $idx);
                continue;
            }

            //###> Y Bn

            if ($regex = $is($Y . '\s+' . $Bn, $explodedStringPattern)) {
                $this->parse($yearMonthAndBoardNumber, $regex, $explodedStringPattern, $asIntoConfig, $idx);
                continue;
            }

            if ($regex = $is($Bn . '\s+' . $Y, $explodedStringPattern)) {
                $this->parse($yearMonthAndBoardNumber, $regex, $explodedStringPattern, $asIntoConfig, $idx);
                continue;
            }

            //###> M Bn

            if ($regex = $is($M . '\s*' . $Bn, $explodedStringPattern)) {
                $this->parse($yearMonthAndBoardNumber, $regex, $explodedStringPattern, $asIntoConfig, $idx);
                continue;
            }

            if ($regex = $is($Bn . '\s*' . $M, $explodedStringPattern)) {
                $this->parse($yearMonthAndBoardNumber, $regex, $explodedStringPattern, $asIntoConfig, $idx);
                continue;
            }

            //###> Y

            if ($regex = $is($Y, $explodedStringPattern)) {
                $this->parse($yearMonthAndBoardNumber, $regex, $explodedStringPattern, $asIntoConfig, $idx);
                continue;
            }

            //###> M

            if ($regex = $is($M, $explodedStringPattern)) {
                $this->parse($yearMonthAndBoardNumber, $regex, $explodedStringPattern, $asIntoConfig, $idx);
                continue;
            }

            //###> Bn

            if ($regex = $is($Bn, $explodedStringPattern)) {
                $this->parse($yearMonthAndBoardNumber, $regex, $explodedStringPattern, $asIntoConfig, $idx);
                continue;
            }
        }

        return $yearMonthAndBoardNumber;
    }

    //###< PARSERS API ###

    //###> ABSTRACT ###
	
    /* GUARANTEED THAT stringPattern ALREADY GIVES NOT NULL */
    /* AbstractPatternAbleConstructedFromToCommand
        USE PARSERS API HERE
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

    private function parse(
        array &$array,
        string $regex,
        string $string,
        bool $asIntoConfig,
        int $idx,
    ): void {
        $matches = [];
        \preg_match($regex, $string, $matches);
        if ($v = $this->boolService->isGet($matches, PatternAbleCommandInterface::Y_NAME)) {
            $y = $this->stringService->getYearBySubstr($v);
            if ($y !== null) {
                $v = $y;
            }

            $array[$idx][PatternAbleCommandInterface::Y_NAME] = $v;
        }
        if ($v = $this->boolService->isGet($matches, PatternAbleCommandInterface::M_NAME)) {
            $m = $this->configService->getMonthBySubstr($v);
            if ($m !== null) {
                $v = $m;
            }

            $array[$idx][PatternAbleCommandInterface::M_NAME] = $v;
        }
        if ($v = $this->boolService->isGet($matches, PatternAbleCommandInterface::B_N_NAME)) {
            $bn = $this->configService->getBoardNumberBySubstr($v);
            if ($bn !== null) {
                $v = $bn;
            }

            $array[$idx][PatternAbleCommandInterface::B_N_NAME] = $v;
        }
    }

    //###< PARSER HELPERS ###
}
