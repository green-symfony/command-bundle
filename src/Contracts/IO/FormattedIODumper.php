<?php

namespace GS\Command\Contracts\IO;

use Symfony\Component\Console\Style\SymfonyStyle;
use GS\Command\Command\AbstractCommand;

class FormattedIODumper extends AbstractIODumper
{
	protected readonly string $formatter;

	public function __construct(
		?string $formatter = null,
		int $afterDumpNewLines = 0,
	) {
		parent::__construct(
			afterDumpNewLines: $afterDumpNewLines,
		);
		
		//###>
		$formatter ??= 'bg=black;fg=green';
		$this->formatter = \trim($formatter);
	}
	
	//###> API ###
	
	/*
		Gets colored string
	*/
	protected function getFormatted(
		$string,
	): string {
		if (empty($this->formatter)) {
			return $string;
		}
		
		return '<' . $this->formatter . '>' . $string . '</>';
	}
	
	//###< API ###

	
	//###> ABSTRACT ###
	
	/* AbstractIODumper */
	protected function dump(
		SymfonyStyle &$io,
		mixed $normalizedMessage,
	): void {
		$io->text($normalizedMessage);
	}
	
	//###< ABSTRACT ###
	
	
	//###> CAN OVERRIDE ###
	
	/* AbstractIODumper */
	protected function getNormalizedMessage(
		mixed $message,
	): mixed {
		$getFormatted = $this->getFormatted(...);
		
		if (\is_array($message)) {
			\array_walk(
				$message,
				static fn(&$el) => $el = $getFormatted($el),
			);
		} else {
			$message = $getFormatted($message);
		}
		
		return $message;
	}
	
	//###< CAN OVERRIDE ###
}
