<?php

namespace GS\Command\Service;

abstract class NumberTransformator
{
	//###> ABSTRACT ###
	
	abstract public function getTransformed(
		int|float $number,
	): int|float;
	
	//###< ABSTRACT ###
}
