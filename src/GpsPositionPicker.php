<?php

namespace VojtechDobes\NetteForms;

use Nette\Forms\Container;
use Nette\Forms\Form;
use Nette\Forms\IControl;


/**
 * Picker of single point
 *
 * @author Vojtěch Dobeš
 */
class GpsPositionPicker extends GpsPicker
{

	/** @var float */
	private $lat = 50.083;

	/** @var float */
	private $lng = 14.423;



	protected function getSupportedDrivers()
	{
		return array(
			self::DRIVER_GOOGLE,
			self::DRIVER_NOKIA,
			self::DRIVER_OPENSTREETMAP,
			self::DRIVER_SEZNAM,
		);
	}



	protected function getShape()
	{
		return 'point';
	}



	protected function getParts()
	{
		return array(
			'lat' => array(
				'label' => 'Latitude',
				'rules' => array(GpsPicker::MAX_LAT, GpsPicker::MIN_LAT),
				'attrs' => array(
					'step' => 'any',
				),
			),
			'lng' => array(
				'label' => 'Longitude',
				'rules' => array(GpsPicker::MAX_LNG, GpsPicker::MIN_LNG),
				'attrs' => array(
					'step' => 'any',
				),
			),
		);
	}



	public function loadHttpData()
	{
		parent::loadHttpData();
		$this->lat = $this->getHttpData(Form::DATA_LINE, '[lat]');
		$this->lng = $this->getHttpData(Form::DATA_LINE, '[lng]');
	}



	/**
	 * Returns coordinates enveloped in Gps instance
	 *
	 * @return GpsPoint
	 */
	public function getValue()
	{
		return new GpsPoint($this->lat, $this->lng, $this->search);
	}



	public function setValue($coordinates)
	{
		if ($coordinates instanceof GpsPoint || $coordinates instanceof \stdClass) {
			$this->lat = $coordinates->lat;
			$this->lng = $coordinates->lng;
		} elseif (isset($coordinates['lat'])) {
			$this->lat = (float) $coordinates['lat'];
			$this->lng = (float) $coordinates['lng'];
		} else {
			list($this->lat, $this->lng) = $coordinates;
		}
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
	 *
	 * @param  string default driver
	 * @param  string default type
	 */
	public static function register($driver = GpsPicker::DRIVER_GOOGLE, $type = GpsPicker::TYPE_ROADMAP)
	{
		Container::extensionMethod('addGpsPicker', function ($container, $name, $caption = NULL, $options = array()) use ($driver, $type) {
			if (!isset($options['driver'])) {
				$options['driver'] = $driver;
			}
			if (!isset($options['type'])) {
				$options['type'] = $type;
			}
			return $container[$name] = new GpsPositionPicker($caption, $options);
		});
	}

}
