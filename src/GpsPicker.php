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

	const TYPE_ROADMAP = 'ROADMAP';
	const TYPE_SATELLITE = 'SATELLITE';
	const TYPE_HYBRID = 'HYBRID';
	const TYPE_TERRAIN = 'TERRAIN';

	const DEFAULT_ZOOM = 8;
	const DEFAULT_SIZE_X = 400;
	const DEFAULT_SIZE_Y = 300;
	const DEFAULT_TYPE = self::TYPE_ROADMAP;

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
		foreach (array('zoom', 'type') as $key) {
			if (isset($options[$key])) {
				$this->{'set' . ucfirst($key)}($options[$key]);
			}
		}
	}



/* === Value ================================================================ */



	/**
	 * Returns coordinates enveloped in Gps instance
	 * 
	 * @return Geography\Gps
	 */
	public function getValue()
	{
		return (object) array_intersect_key(parent::getValue() ?: $this->getDefaultValue(), $this->getParts());
	}



/* === Rendering ============================================================ */



	/**
	 * Finalizes and returns control's element
	 * 
	 * @return Html
	 */
	public function getControl()
	{
		$control = parent::getControl();
		$container = Html::el('div');
		$id = $control->id;
		$name = $control->name;

		$value = $this->getValue();

		foreach ($this->getParts() as $part => $title) {
			$control->id = "$id-$part";
			$control->name = $name . "[$part]";
			$control->type = 'number';
			$control->class[] = "gpspicker-$part";
			$control->value = $value->$part;
			$container->add((string) $control);
		}

		$container->data('nette-gpspicker', $this->prepareDataAttributes(array(
			'size' => array(
				'x' => $this->size['x'],
				'y' => $this->size['y'],
			),
			'zoom' => $this->zoom,
			'type' => $this->type,
			'shape' => $this->getShape(),
		)));

		return $container;
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

}
