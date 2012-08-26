<?php

namespace VojtechDobes\NetteForms;

use Nette;


/**
 * Single point
 *
 * @author Vojtěch Dobeš
 */
class GpsPoint extends Nette\Object
{

	/** @var float */
	private $lat;

	/** @var float */
	private $lng;



	/**
	 * @param  float|float[] [lat, lng]
	 * @param  float|NULL
	 */
	public function __construct($lat, $lng = NULL)
	{
		if (is_array($lat)) {
			$lng = $lat['lng'];
			$lat = $lat['lat'];
		} else ($lng === NULL) {
			throw new Nette\InvalidArgumentException('GpsPoint must accept latitude AND longitude, or array with "lat" and "lng" keys.');
		}
		$this->lat = (float) $lat;
		$this->lng = (float) $lng;
	}



	/**
	 * Returns latitude
	 *
	 * @return float
	 */
	public function getLat()
	{
		return $this->lat;
	}



	/**
	 * Returns longitude
	 *
	 * @return float
	 */
	public function getLng()
	{
		return $this->lng;
	}



	/**
	 * Calculates distance of two GPS coordinates
	 *
	 * @author Jakub Vrána
	 * @link   http://php.vrana.cz/vzdalenost-dvou-zemepisnych-bodu.php
	 *
	 * @param  GpsPoint
	 * @return float distance in metres
	 */
	public function getDistanceTo(GpsPoint $point)
	{
		return acos(
			cos(deg2rad($this->lat))*cos(deg2rad($this->lng))*cos(deg2rad($point->lat))*cos(deg2rad($point->lng))
			+ cos(deg2rad($this->lat))*sin(deg2rad($this->lng))*cos(deg2rad($point->lat))*sin(deg2rad($point->lng))
			+ sin(deg2rad($this->lat))*sin(deg2rad($point->lat))
		) * GpsPicker::GREAT_CIRCLE_RADIUS * 1000;
	}

}
