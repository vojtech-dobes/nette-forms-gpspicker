<?php

namespace VojtechDobes\NetteForms;

use Nette\Forms\Container;


/**
 * Picker of single point
 *
 * @author Vojtěch Dobeš
 */
class GpsPositionPicker extends GpsPicker
{

	protected function getParts()
	{
		return array('lat' => 'Latitude', 'lng' => 'Longitude');
	}



	protected function getShape()
	{
		return 'point';
	}



	protected function getDefaultValue()
	{
		return array('lat' => 50.083, 'lng' => 14.423);
	}



/* === Use helper =========================================================== */



	/**
	 * Registers method 'addGpsPicker' adding GpsPositionPicker to form
	 */
	public static function register()
	{
		Container::extensionMethod('addGpsPicker', function ($container, $name, $caption = NULL, $options = array()) {
			return $container[$name] = new GpsPositionPicker($caption, $options);
		});
	}

}
