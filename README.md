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

Returned value is instance of `GpsPoint` with `lat` and `lng` properties. It inherits from `Nette\Object`.

```php
$lat = $form->values->coords->lat;
$lng = $form->values->coords->lng;
```

### Validation

```php
use VojtechDobes\NetteForms\GpsPicker as Gps;
```

Now you can easily add various constraints on desired GPS:

```php
$form->addGpsPicker('coords')
	->addRule(Gps::MIN_LAT, 'Minimal latitude must be %f.', 20)
	->addRule(Gps::MIN_LNG, 'Minimal longitude must be %f.', 40)
	->addRule(Gps::MAX_LAT, 'Maximum latitude must be %f.', 20)
	->addRule(Gps::MAX_LNG, 'Maximum longitude must be %f.', 40)
	->addRule(Gps::MIN_DISTANCE_FROM, 'Minimal distance from Prague must be %i m.', array(
		'lat' => 50.083,
		'lng' => 14.423,
	), 15000);
	->addRule(Gps::MAX_DISTANCE_FROM, 'Maximum distance from Prague must be %i m.', array(
		'lat' => 50.083,
		'lng' => 14.423,
	), 100000);
```

First four rules will be also validated client-side.
