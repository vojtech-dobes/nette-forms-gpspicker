<?php

namespace VojtechDobes\NetteForms;

use Nette\Forms\Container;
use Nette\Forms\IControl;


/**
 * Picker of single point
 *
 * @author Vojtěch Dobeš
 */
class GpsPositionPicker extends GpsPicker
{

	protected function getParts()
	{
		return array(
			'lat' => array(
				'label' => 'Latitude',
				'rules' => array(GpsPicker::MAX_LAT, GpsPicker::MIN_LAT),
			),
			'lng' => array(
				'label' => 'Longitude',
				'rules' => array(GpsPicker::MAX_LNG, GpsPicker::MIN_LNG),
			),
		);
	}



	protected function getShape()
	{
		return 'point';
	}



	protected function getDefaultValue()
	{
		return array('lat' => 50.083, 'lng' => 14.423);
	}



	protected function createValue($args)
	{
		return new GpsPoint($args['lat'], $args['lng'], isset($args['search']) ? $args['search'] : NULL);
	}



/* === Validation =========================================================== */



	public static function validateMaxLat(IControl $control, $maxLat)
	{
		return $control->getValue()->getLat() <= $maxLat;
	}



	public static function validateMaxLng(IControl $control, $maxLng)
	{
		return $control->getValue()->getLng() <= $maxLng;
	}



	public static function validateMinLat(IControl $control, $minLat)
	{
		return $control->getValue()->getLat() >= $minLat;
	}



	public static function validateMinLng(IControl $control, $minLng)
	{
		return $control->getValue()->getLng() >= $minLng;
	}



	public static function validateMaxDistanceFrom(IControl $control, array $args)
	{
		list($distance, $point) = $args;
		return $control->getValue()->getDistanceTo(new GpsPoint($point)) <= $distance;
	}



	public static function validateMinDistanceFrom(IControl $control, array $args)
	{
		list($distance, $point) = $args;
		return $control->getValue()->getDistanceTo(new GpsPoint($point)) >= $distance;
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
