<?php

namespace GS\Command\Contracts\IO;

use Symfony\Component\Console\Style\SymfonyStyle;

class DefaultIODumper extends AbstractIODumper
{
	public const FORMATTER = 'bg=black;fg=green';
	
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
		$getFormatted = static fn(&$s) => $s = '<' . self::FORMATTER . '>' . $s . '</>';
		
		if (\is_array($message)) {
			\array_walk($message, $getFormatted(...));
		} else {
			$message = $getFormatted($message);
		}
		
		return $message;
	}
	
	//###< CAN OVERRIDE ###
}
