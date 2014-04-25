<?php

namespace VojtechDobes\NetteForms;

use Nette\DI;
use Nette\PhpGenerator;

if (!class_exists('Nette\DI\CompilerExtension')) {
	class_alias('Nette\Config\CompilerExtension', 'Nette\DI\CompilerExtension');
}
if (!class_exists('Nette\PhpGenerator\ClassType')) {
	class_alias('Nette\Utils\PhpGenerator\ClassType', 'Nette\PhpGenerator\ClassType');
}


/**
 * Registers macros and add helpers
 *
 * @author Vojtěch Dobeš
 */
class GpsPickerExtension extends DI\CompilerExtension
{

	/** @var array */
	private $defaultConfig = array(
		'driver' => GpsPicker::DRIVER_GOOGLE,
		'type' => GpsPicker::TYPE_ROADMAP,
	);



	public function loadConfiguration()
	{
		$container = $this->getContainerBuilder();

		$latte = $container->getDefinition('nette.latte');
		$latte->addSetup('VojtechDobes\NetteForms\GpsPickerMacros::install(?->getCompiler())', array('@self'));
	}



	public function afterCompile(PhpGenerator\ClassType $class)
	{
		$config = $this->getConfig($this->defaultConfig);
		$type = strtoupper($config['type']);
		if (in_array($type, GpsPicker::$typeSupport[$config['driver']]) === FALSE) {
			throw new UnsupportedTypeException("Driver '$config[driver]' doesn't support '$type' type.");
		}

		$initialize = $class->methods['initialize'];
		$initialize->addBody('VojtechDobes\NetteForms\GpsPositionPicker::register(?, ?);', array(
			$config['driver'],
			$type,
		));
	}

}
