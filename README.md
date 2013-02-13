## For Nette Framework

GPS coordinates picker. [Try it now!](http://vojtechdobes.com/gpspicker/)

##### Drivers

- Google Maps API v3
- Mapy.cz API v4
- Nokia Maps API v2
- OpenStreetMap (using Google Maps API v3)

##### License

New BSD

##### Dependencies

Nette 2.0.0

##### Demo

http://vojtechdobes.com/gpspicker/

## Installation

1. Get the source code from Github or via Composer (`vojtech-dobes/nette-forms-gpspicker`).
2. Register `VojtechDobes\NetteForms\GpsPickerExtension` as extension.
3. Link appropriate maps API SDK and `client/nette.gpsPicker.js` in `app/templates/@layout.latte`.

```neon
extensions:
	gpspicker: VojtechDobes\NetteForms\GpsPickerExtension
```

> In Nette 2.0, registration is done in `app/bootstrap.php`:
```php
$configurator->onCompile[] = function ($configurator, $compiler) {
	$compiler->addExtension('gpspicker', new VojtechDobes\NetteForms\GpsPickerExtension);
};
```

```html
	<script src="http://maps.googleapis.com/maps/api/js?libraries=places&sensor=false"></script>
	<script src="{$basePath}/libs/nette.gpsPicker.js"></script>
</body>
```

*(for example when using Google Maps API v3)*

## Usage

```php
$form->addGpsPicker('coords', 'Coordinates:');
```

You can add some options (every option has also some like setter thing):

```php
$form->addGpsPicker('coords', 'Coordinates:', array(
	'type' => VojtechDobes\NetteForms\GpsPicker::TYPE_SATELLITE,
	'zoom' => 1, // something like whole planet I guess
	'size' => array(
		'x' => 411,
		'y' => 376,
	),
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

You can set value (respectively default value) like this:

```php
$form['coords']->setDefaultValue(array(
	'lat' => 50.103245,
	'lng' => 14.474691,
));
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
	->addRule(Gps::MIN_DISTANCE_FROM, 'Minimal distance from Prague must be %d m.', array(15000, array(
		'lat' => 50.083,
		'lng' => 14.423,
	)));
	->addRule(Gps::MAX_DISTANCE_FROM, 'Maximum distance from Prague must be %d m.', array(100000, array(
		'lat' => 50.083,
		'lng' => 14.423,
	)));
```

First four rules will be also validated client-side.

### Manual rendering

If the user doesn't support Javascript or gets offline, picker provides several inputs for setting coordinates manually. You can easily render them manually as well as whole complete element.

```html
{form formName}
	...

	{gpspicker coords}
		{label lat}Latitude:{/label} {input lat}
		{label lng}Longitude:{/label} {input lng}
	{/gpspicker}
{/form}
```

Keep in mind that you cannot render any other inputs inside of `{gpspicker}` macro.

### Search by address

Enabled by default, GpsPicker supports searching map by typing the address. Extra `<input>` element will be prepended to map,
enhanced by Google Places Autocomplete service.

If you like to render it manually, use `search` key:

```html
{gpspicker coords}
	{label search /} {input search}
{/gpspicker}
```

You can disable/enable search feature with `enableSearch`/`disableSearch` pair of methods:

```php
$form->addGpsPicker('coords', 'Coordinates:')
	->disableSearch();
```

Or in constructor:

```php
$form->addGpsPicker('coords', 'Coordinates:', array(
	'search' => FALSE,
));
```

### Drivers

If you are afraid of exhausting API rate limit of Google Maps, you can use alternative provider as well. All you have to do is to link their appropriate API SDK and set the driver:

```php
$form->addGpsPicker('coords', 'Coordinates:')
	->setDriver(Gps::DRIVER_SEZNAM);
```

Available providers are:

##### `Gps::DRIVER_GOOGLE` (search: yes)

```html
<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?libraries=places&sensor=false"></script>
```

##### `Gps::DRIVER_NOKIA` (search: no)

```html
<script src="http://api.maps.nokia.com/2.2.1/jsl.js?with=all" charset="utf-8"></script>
<script>
	nokia.Settings.set('appId', 'XXX');
	nokia.Settings.set('authenticationToken', 'XXX');
</script>
```

##### `Gps::DRIVER_OPENSTREETMAP` (search: no)

```html
<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?sensor=false"></script>
```

##### `Gps::DRIVER_SEZNAM` (search: no)

```html
<script src="http://api4.mapy.cz/loader.js"></script>
<script>Loader.load()</script>
```
