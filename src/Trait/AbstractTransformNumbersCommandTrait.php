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
use GS\Command\Service\NumberTransformator;
use Symfony\Component\Console\Command\Command;

/**/
trait AbstractTransformNumbersCommandTrait
{
	/*###> MUST CONTAIN ###
	
		MUST extend \GS\Command\Command\AbstractCommand
    */
	
	//###> ABSTRACT ###

    /* AbstractTransformNumbersCommandTrait */
	abstract protected function getInputNumber(): \Traversable;
	
    /* AbstractTransformNumbersCommandTrait */
	abstract protected function getNumberTransformator(
		$number,
	): NumberTransformator;
	
	/* AbstractTransformNumbersCommandTrait */
	abstract protected function processResult(array $transformedNumbers): void;

	//###< ABSTRACT ###
	
	
	/* AbstractCommand */
    protected function command(
        InputInterface $input,
        OutputInterface $output,
    ): int {
	
		$transformedNumbers = [];
	
		foreach($this->getInputNumber() as $number) {
			$transformedNumbers[] = $this->getNumberTransformator($number)->getTransformed(
				$number,
			);
		}
		
		$this->processResult(
			$transformedNumbers,
		);
		
		return Command::SUCCESS;
	}
	
	//###> HELPER ###
	//###< HELPER ###
}
