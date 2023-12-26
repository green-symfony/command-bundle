<?php

namespace GS\Command\Contracts\IO;

use Symfony\Component\Console\Style\SymfonyStyle;
use GS\Command\Command\AbstractCommand;

class FormattedIODumper extends AbstractIODumper
{
	protected readonly string $formatter;

	public function __construct(
		?string $formatter = null,
	) {
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
		if (\is_array($message)) {
			\array_walk(
				$message,
				$this->getFormatted(...),
			);
		} else {
			$message = $this->getFormatted($message);
		}
		
		return $message;
	}
	
	//###< CAN OVERRIDE ###
}
