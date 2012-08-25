## For Nette Framework

Google Maps based coordinates picker

## License

New BSD

## Dependencies

Nette 2.0.0

## Installation

1. Get the source code from Github.
2. Register in bootstrap via `VojtechDobes\NetteForms\GpsPositionPicker::register();`.
3. Link Google Maps API v3 and `client/nette.gpsPicker.js` in `app/templates/@layout.latte`.

## Usage

```php
$form->addGpsPicker('coords', 'Coordinates:');
```

You can add some options:

```php
$form->addGpsPicker('coords', 'Coordinates:', array(
	'type' => VojtechDobes\NetteForms\GpsPicker::TYPE_SATELLITE,
	'zoom' => 1, // something like whole planet I guess
	'size' => array(
		'x' => 411,
		'y' => 376,
	)
));
```

If you prefer manual rendering, caption can be omitted:

```php
$form->addGpsPicker('coords', array(
	'type' => ...
	...
));
```

Returned value is instance of `stdClass` with `lat` and `lng` keys.

```php
$lat = $form->values->coords->lat;
$lng = $form->values->coords->lng;
```
