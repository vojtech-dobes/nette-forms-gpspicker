<?php

namespace VojtechDobes\NetteForms;

use Nette\Forms\Form;
use Nette\Forms\Container;
use Nette\Forms\Controls\BaseControl;
use Nette\InvalidArgumentException;
use Nette\Utils\Html;
use Traversable;


/**
 * Picker of GPS coordinates via Google Maps
 *
 * @author Vojtěch Dobeš
 */
abstract class GpsPicker extends BaseControl
{

	/** string validation rules */
	const MAX_LAT = ':maxLat';
	const MAX_LNG = ':maxLng';
	const MIN_LAT = ':minLat';
	const MIN_LNG = ':minLng';
	const MAX_DISTANCE_FROM = ':maxDistanceFrom';
	const MIN_DISTANCE_FROM = ':minDistanceFrom';

	/** float */
	const GREAT_CIRCLE_RADIUS = 6372.795;

	/** string */
	const TYPE_ROADMAP = 'ROADMAP';
	const TYPE_SATELLITE = 'SATELLITE';
	const TYPE_HYBRID = 'HYBRID';
	const TYPE_TERRAIN = 'TERRAIN';

	/** int */
	const DEFAULT_ZOOM = 8;
	const DEFAULT_SIZE_X = 400;
	const DEFAULT_SIZE_Y = 300;
	const DEFAULT_TYPE = self::TYPE_ROADMAP;
	const DEFAULT_USE_GOOGLE = TRUE;

	/**
	 * Default size
	 * @var array
	 */
	private $size = array(
		'x' => self::DEFAULT_SIZE_X,
		'y' => self::DEFAULT_SIZE_Y,
	);

	/**
	 * Default zoom
	 * @var int
	 */
	private $zoom = self::DEFAULT_ZOOM;

	/**
	 * Default type
	 * @var string
	 */
	private $type = self::DEFAULT_TYPE;

	/**
	 * Should be address input shown?
	 * @var bool
	 */
	private $showSearch = TRUE;

	/**
	 * Should be used original Google Maps tilesets?
	 * @var bool
	 */
	private $useGoogle = self::DEFAULT_USE_GOOGLE;

	/**
	 * Should be address returned?
	 * @var bool
	 */
	private $addressRetrieval = FALSE;

	/** @var Html */
	private $searchControlPrototype;

	/**
	 * Exported rules
	 * @var array
	 */
	private $exportedRules;



	/**
	 * Stores caption and sets default value
	 * 
	 * @param  string caption
	 * @param  array|mixed options
	 */
	public function __construct($caption, $options = array())
	{
		if (is_array($caption)) {
			$options = $caption;
			$caption = NULL;
		}

		parent::__construct($caption);
		$this->control->type = 'text';
	
		$options = (array) $options;
		if (isset($options['size'])) {
			$this->setSize($options['size']['x'], $options['size']['y']);
		}
		if (isset($options['search'])) {
			$this->showSearch = (bool) $options['search'];
		}
		foreach (array('zoom', 'type') as $key) {
			if (isset($options[$key])) {
				$this->{'set' . ucfirst($key)}($options[$key]);
			}
		}
	}



	/**
	 * Returns coordinates enveloped in Gps instance
	 * 
	 * @return \stdClass
	 */
	public function getValue()
	{
		$filter = $this->getParts();
		if ($this->addressRetrieval) {
			$filter['search'] = TRUE;
		}
		return $this->createValue(array_intersect_key(parent::getValue() ?: $this->getDefaultValue(), $filter));
	}



	/**
	 * Finalizes and returns control's element
	 * 
	 * @return Html
	 */
	public function getControl($onlyContainer = FALSE)
	{
		$control = parent::getControl();
		$container = Html::el('div');
		$id = $control->id;
		$name = $control->name;

		if (!$onlyContainer) {
			if ($this->showSearch) {
				$container->add((string) $this->getSearchControlPrototype());
			}

			foreach ($this->getParts() as $part => $options) {
				$container->add((string) $this->getPartialControl($part));
			}
		}

		$container->data('nette-gpspicker', $this->prepareDataAttributes(array(
			'size' => array(
				'x' => $this->size['x'],
				'y' => $this->size['y'],
			),
			'zoom' => $this->zoom,
			'type' => $this->type,
			'search' => $this->showSearch,
			'shape' => $this->getShape(),
			'useGoogle' => $this->useGoogle,
		)));

		return $container;
	}



	public function getPartialControl($name)
	{
		if ($name == 'search') {
			return $this->getSearchControlPrototype();
		}

		$parts = $this->getParts();
		if (!isset($parts[$name])) {
			throw new InvalidArgumentException(get_class($this) . " doesn't have part called '$name'.");
		}
		$options = $parts[$name];

		$value = $this->getValue();
		$rules = $this->getExportedRules();

		$control = clone parent::getControl();
		$control->id = "{$control->id}-$name";
		$control->name = $control->name . "[$name]";
		$control->type = 'number';
		$control->class[] = "gpspicker-$name";
		$control->value = $value->$name;
		$control->data('nette-rules', $this->prepareDataAttributes(array_values(array_filter($rules, function ($rule) use ($options) {
			return in_array($rule['op'], $options['rules']);
		}))) ?: NULL);

		return $control;
	}



	public function getPartialLabel($name)
	{
		if ($name == 'search') {
			$label = clone parent::getLabel();
			$label->for = $label->for . '-search';
			$label->setText($this->translate('Address'));
			return $label;
		}
		
		$parts = $this->getParts();
		if (!isset($parts[$name])) {
			throw new InvalidArgumentException(get_class($this) . " doesn't have part called '$name'.");
		}
		$caption = $parts[$name]['label'];

		$label = clone parent::getLabel();
		$label->for = $label->for . '-' . $name;
		$label->setText($this->translate($caption));

		return $label;
	}



	public function getSearchControlPrototype()
	{
		if (!$this->searchControlPrototype) {
			$control = parent::getControl();
			$this->searchControlPrototype = Html::el('input', array(
				'type' => 'text',
				'id' => $control->id . '-search',
				'name' => $control->name . '[search]',
				'class' => 'gpspicker-search',
				'style' => 'display:none',
			));
		}
		return $this->searchControlPrototype;
	}



	private function getExportedRules()
	{
		if (!isset($this->exportedRules)) {
			$this->exportedRules = self::exportRules($this->rules);
		}
		return $this->exportedRules;
	}



	/**
	 * Transforms array to form suitable for data attributes
	 * 
	 * from Nette/Forms/Controls/BaseControl.php:385
	 *
	 * @param  array $data
	 * @return string 
	 */
	private function prepareDataAttributes(array $data)
	{
		$data = json_encode($data);
		$data = preg_replace('#"([a-z0-9]+)":#i', '$1:', $data);
		$data = preg_replace('#(?<!\\\\)"([^\\\\\',]*)"#i', "'$1'", $data);
		return substr($data, 1, -1);
	}



/* === Options ============================================================== */



	/**
	 * Sets size of map element
	 *
	 * @param  mixed $x
	 * @param  mixed $y
	 * @return provides a fluent interface
	 */
	public function setSize($x, $y)
	{
		$this->size = array(
			'x' => $x ?: ($x === 0 ? 0 : $this->size['x']),
			'y' => $y ?: ($y === 0 ? 0 : $this->size['y']),
		);

		return $this;
	}



	/**
	 * Sets default zoom
	 *
	 * @param  int
	 * @return GpsPicker provides a fluent interface
	 */
	public function setZoom($zoom)
	{
		$this->zoom = (int) $zoom ?: self::DEFAULT_ZOOM;

		return $this;
	}



	/**
	 * Sets default type of map
	 *
	 * @param  string self::TYPE_*
	 * @return provides a fluent interface
	 */
	public function setType($type)
	{
		$this->type = (string) $type;

		return $this;
	}



	/**
	 * Original Google Maps tilesets will be used
	 *
	 * @return GpsPicker provides a fluent interface
	 */
	public function enableGoogle()
	{
		$this->useGoogle = TRUE;

		return $this;
	}



	/**
	 * Instead of original Google Maps tilesets will
	 * be used Open Street Map tilesets
	 *
	 * @return GpsPicker provides a fluent interface
	 */
	public function disableGoogle()
	{
		$this->disableSearch();

		$this->useGoogle = FALSE;

		return $this;
	}



	/**
	 * Enables input for address search
	 *
	 * @param  bool if TRUE, address will be also returned
	 * @return GpsPicker provides a fluent interface
	 */
	public function enableSearch($addressRetrieval = FALSE)
	{
		$this->enableGoogle();

		$this->showSearch = TRUE;
		$this->addressRetrieval = (bool) $addressRetrieval;

		return $this;
	}



	/**
	 * Disables input for address search
	 *
	 * @return GpsPicker provides a fluent interface
	 */
	public function disableSearch()
	{
		$this->showSearch = FALSE;

		return $this;
	}



/* === Interface ============================================================ */



	/**
	 * Should return identifier of proper JS handler
	 *
	 * @return string
	 */
	abstract protected function getShape();



	/**
	 * Should return array of partial inputs
	 *
	 * @return string[] [name => label]
	 */
	abstract protected function getParts();



	/**
	 * Should return default value
	 *
	 * @return array
	 */
	abstract protected function getDefaultValue();



	/**
	 * Should create instance of correct value representation
	 *
	 * @return
	 */
	abstract protected function createValue($args);

}
