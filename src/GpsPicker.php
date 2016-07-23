<?php

namespace VojtechDobes\NetteForms;

use Nette\Forms;
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
	const MAX_LAT = 'VojtechDobes\NetteForms\GpsPositionPicker::validateMaxLat';
	const MAX_LNG = 'VojtechDobes\NetteForms\GpsPositionPicker::validateMaxLng';
	const MIN_LAT = 'VojtechDobes\NetteForms\GpsPositionPicker::validateMinLat';
	const MIN_LNG = 'VojtechDobes\NetteForms\GpsPositionPicker::validateMinLng';
	const MAX_DISTANCE_FROM = 'VojtechDobes\NetteForms\GpsPositionPicker::validateMaxDistanceFrom';
	const MIN_DISTANCE_FROM = 'VojtechDobes\NetteForms\GpsPositionPicker::validateMinDistanceFrom';

	/** float */
	const GREAT_CIRCLE_RADIUS = 6372.795;

	/** string */
	const DRIVER_GOOGLE = 'google';
	const DRIVER_NOKIA = 'nokia';
	const DRIVER_OPENSTREETMAP = 'openstreetmap';
	const DRIVER_SEZNAM = 'seznam';

	/** string */
	const TYPE_BASE = 'BASE'; // Seznam, like ROADMAP
	const TYPE_BIKE = 'BIKE'; // Seznam
	const TYPE_HISTORIC = 'HISTORIC'; // Seznam
	const TYPE_HYBRID = 'HYBRID'; // Google, Seznam
	const TYPE_NORMAL = 'NORMAL'; // Nokia, like ROADMAP
	const TYPE_OPHOTO = 'OPHOTO'; // Seznam, like SATELLITE
	const TYPE_ROADMAP = 'ROADMAP'; // Google
	const TYPE_SATELLITE = 'SATELLITE'; // Google, Nokia
	const TYPE_TERRAIN = 'TERRAIN'; // Google, Nokia
	const TYPE_TRAIL = 'TRAIL'; // Seznam
	const TYPE_TURIST = 'TURIST'; // Seznam, like TERRAIN

	/** int */
	const DEFAULT_ZOOM = 8;
	const DEFAULT_SIZE_X = 400;
	const DEFAULT_SIZE_Y = 300;
	const DEFAULT_TYPE = self::TYPE_ROADMAP;
	const DEFAULT_USE_GOOGLE = TRUE;

	/** @var string|NULL */
	protected $search;

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
	 * Default driver
	 * @var string
	 */
	private $driver = self::DRIVER_GOOGLE;

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
	 * Should be address returned?
	 * @var bool
	 */
	private $addressRetrieval = FALSE;

	/**
	 * Should be manual input of coordinates be allowed?
	 * @var bool
	 */
	private $manualInput = FALSE;

	/** @var Html */
	private $searchControlPrototype;

	/**
	 * Exported rules
	 * @var array
	 */
	private $exportedRules;

	/**
	 * Which driver supports search
	 * @var array
	 */
	public static $searchSupport = array(
		self::DRIVER_GOOGLE => TRUE,
		self::DRIVER_NOKIA => FALSE,
		self::DRIVER_OPENSTREETMAP => FALSE,
		self::DRIVER_SEZNAM => FALSE,
	);

	/**
	 * Which driver supports which types
	 * @var array
	 */
	public static $typeSupport = array(
		self::DRIVER_GOOGLE => array(
			self::TYPE_HYBRID,
			self::TYPE_ROADMAP,
			self::TYPE_SATELLITE,
			self::TYPE_TERRAIN,
		),
		self::DRIVER_NOKIA => array(
			self::TYPE_NORMAL,
			self::TYPE_ROADMAP,
			self::TYPE_SATELLITE,
			self::TYPE_TERRAIN,
		),
		self::DRIVER_OPENSTREETMAP => array(
			self::TYPE_ROADMAP,
		),
		self::DRIVER_SEZNAM => array(
			self::TYPE_BASE,
			self::TYPE_BIKE,
			self::TYPE_HISTORIC,
			self::TYPE_HYBRID,
			self::TYPE_OPHOTO,
			self::TYPE_ROADMAP,
			self::TYPE_SATELLITE,
			self::TYPE_TERRAIN,
			self::TYPE_TRAIL,
			self::TYPE_TURIST,
		),
	);



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
		foreach (array('zoom', 'driver', 'type') as $key) {
			if (isset($options[$key])) {
				$this->{'set' . ucfirst($key)}($options[$key]);
			}
		}
		if (isset($options['size'])) {
			$this->setSize($options['size']['x'], $options['size']['y']);
		}
		if (isset($options['search'])) {
			if ((bool) $options['search']) {
				$this->enableSearch();
			} else {
				$this->disableSearch();
			}
		}
		if (isset($options['manualInput'])) {
			$this->manualInput = (bool) $options['manualInput'];
		}
	}



	public function loadHttpData()
	{
		$this->search = $this->getHttpData(Form::DATA_LINE, '[search]');
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
		$container->id = $control->id;

		if (!$onlyContainer) {
			if ($this->showSearch) {
				$container->addHtml((string) $this->getSearchControlPrototype());
			}

			foreach ($this->getParts() as $part => $options) {
				$container->addHtml((string) $this->getPartialControl($part));
			}
		}

		$attrs = array(
			'size' => array(
				'x' => $this->size['x'],
				'y' => $this->size['y'],
			),
			'zoom' => $this->zoom,
			'driver' => $this->driver,
			'type' => $this->type,
			'search' => $this->showSearch,
			'shape' => $this->getShape(),
			'disabled' => $this->isDisabled(),
		);

		if ($this->manualInput) {
			$attrs['manualInput'] = $this->manualInput;
		}

		$container->data('nette-gpspicker', $this->prepareDataAttributes($attrs));

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

		if (isset($options['attrs'])) {
			foreach ($options['attrs'] as $key => $value) {
				$control->$key = $value;
			}
		}

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
				'style' => 'display:none'
			));
		}
		$this->searchControlPrototype->value = $this->search;
		return $this->searchControlPrototype;
	}



	private function getExportedRules()
	{
		if (!isset($this->exportedRules)) {
			if (method_exists('Nette\Forms\Helpers', 'exportRules')) {
				$this->exportedRules = Forms\Helpers::exportRules($this->getRules());
			} else {
				$this->exportedRules = self::exportRules($this->getRules());
			}
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
	 * Sets default driver of map
	 *
	 * @param  string self::DRIVER_*
	 * @return provides a fluent interface
	 * @throws InvalidArgumentException if provided driver is not supported for current shape
	 */
	public function setDriver($driver)
	{
		$driver = (string) $driver;
		if (in_array($driver, $this->getSupportedDrivers()) === FALSE) {
			throw new InvalidDriverException("Driver '$driver' is not supported for '{$this->getShape()}' shape.");
		}
		if ($this->showSearch && !self::$searchSupport[$driver]) {
			$this->showSearch = FALSE;
		}
		$this->driver = $driver;

		if (in_array($this->type, self::$typeSupport[$this->driver]) === FALSE) {
			throw new UnsupportedTypeException("Driver '{$this->driver}' doesn't support '{$this->type}' type.");
		}

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
		$type = (string) $type;
		if (in_array($type, self::$typeSupport[$this->driver]) === FALSE) {
			throw new UnsupportedTypeException("Driver '{$this->driver}' doesn't support '$type' type.");
		}
		$this->type = $type;

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
		if (!self::$searchSupport[$this->driver]) {
			throw new UnsupportedSearchException("Driver '{$this->driver}' doesn't support search.");
		}

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



	/**
	 * Enables manual input of coordinates
	 *
	 * @return GpsPicker provides a fluent interface
	 */
	public function enableManualInput()
	{
		$this->manualInput = TRUE;

		return $this;
	}



	/**
	 * Disables manual input of coordinates
	 *
	 * @return GpsPicker provides a fluent interface
	 */
	public function disableManualInput()
	{
		$this->manualInput = FALSE;

		return $this;
	}



/* === Interface ============================================================ */



	/**
	 * Should return list of drivers
	 *
	 * @return string[]
	 */
	abstract protected function getSupportedDrivers();



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

}

/**
 * Unsupported driver for used shape
 */
class InvalidDriverException extends \InvalidArgumentException {}

/**
 * Selected driver doesn's support search
 */
class UnsupportedSearchException extends \InvalidArgumentException {}

/**
 * Selected driver doesn's support given type
 */
class UnsupportedTypeException extends \InvalidArgumentException {}
