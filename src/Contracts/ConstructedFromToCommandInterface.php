<?php

namespace GS\Command\Contracts;

use Symfony\Component\Console\Input\{
    InputInterface
};
use Symfony\Component\Console\Output\{
    OutputInterface
};
use GS\Service\Service\{
    StringService,
    DumpInfoService,
    FilesystemService,
    ConfigService,
    ArrayService,
    RegexService
};

interface ConstructedFromToCommandInterface
{
    // число отставания от цикла выполнения
    public const PROGRESS_BAR_DISPLAY_FREQUENCY     = 0;
	
	/*###> MUST CONTAIN
	
	use OverrideAbleTrait, ConstructedFromToCommandTrait;

	//###> Параметры связаны с command options, указать в классе вручную
	protected bool $override        = false;
	protected bool $askOverride     = true;
	
    public function __construct(
        protected readonly StringService $stringService,
        protected readonly DumpInfoService $dumpInfoService,
        protected readonly FilesystemService $filesystemService,
        protected readonly ConfigService $configService,
        protected readonly ArrayService $arrayService,
        protected readonly RegexService $regexService,
		...
    ) {
        parent::__construct();
    }
	*/
	
	protected function constructedFromToCommandDuringConfigure(): void;
	
	protected function constructedFromToCommandDuringInitialize(
        InputInterface $input,
        OutputInterface $output,
    );
	
	protected function command(
        InputInterface $input,
        OutputInterface $output,
    ): int;
}
