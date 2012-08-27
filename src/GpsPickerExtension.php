<?php

namespace VojtechDobes\NetteForms;

use Nette\Config\CompilerExtension;
use Nette\Utils\PhpGenerator\ClassType;


/**
 * Registers macros and add helpers
 *
 * @author Vojtěch Dobeš
 */
class GpsPickerExtension extends CompilerExtension
{

	public function loadConfiguration()
	{
		$container = $this->getContainerBuilder();

		$latte = $container->getDefinition('nette.latte');
		$latte->addSetup('VojtechDobes\NetteForms\GpsPickerMacros::install(?->compiler)', array('@self'));
	}



	public function afterCompile(ClassType $class)
	{
		$initialize = $class->methods['initialize'];
		$initialize->addBody('VojtechDobes\NetteForms\GpsPositionPicker::register();');
	}

}
