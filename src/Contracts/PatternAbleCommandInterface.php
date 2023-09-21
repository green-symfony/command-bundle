<?php

namespace GS\Command\Contracts;

use Symfony\Component\Console\Input\{
    InputInterface
};
use Symfony\Component\Console\Output\{
    OutputInterface
};

interface PatternAbleCommandInterface
{
    public const Y_NAME     = 'year';
    public const M_NAME     = 'month';
    public const B_N_NAME   = 'boardNumber';
	
    /*###> MUST CONTAIN
	
		use PatternAbleCommandTrait;
		
		public function __construct(
			StringService $stringService,
			DumpInfoService $dumpInfoService,
			FilesystemService $filesystemService,
			ConfigService $configService,
			ArrayService $arrayService,
			RegexService $regexService,
			protected readonly BoolService $boolService,
			protected readonly string $yearRegexWithoutB,
			protected readonly string $monthRegex,
			protected readonly string $boardRegexSoft,
		) {
			parent::__construct();
		}
	*/
	
	protected function patternAbleCommandDuringConfigure(): void;
	
	protected function patternAbleCommandDuringInitialize(
        InputInterface $input,
        OutputInterface $output,
    );
	
	protected function patternAbleCommandDuringExecute(
        InputInterface $input,
        OutputInterface $output,
    ): void;
}
