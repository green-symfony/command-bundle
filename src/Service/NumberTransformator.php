<?php

namespace GS\Command\Service;

abstract class NumberTransformator
{
	//###> ABSTRACT ###
	
	abstract public function getTransformed(
		$number,
	);
	
	//###< ABSTRACT ###
}
