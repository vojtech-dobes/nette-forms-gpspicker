/**
 * Unobtrusive handler for GpsPicker
 * 
 * @author Vojtěch Dobeš
 * @license New BSD
 * 
 * @dependency jQuery
 * @dependency Google Maps V3
 * @dependency netteForms.js
 */

(function(window, undefined) {

var google = window.google;
var $ = window.jQuery;
var Nette = window.Nette;

if (!google || !$) {
	return;
}

var GpsPicker = function () {
	var that = this;
	var handlers = {};

	var parseDataAttribute = function (el) {
		return eval('[{' + (el.getAttribute('data-nette-gpspicker') || '') + '}]')[0];
	};

	$(function () {
		$('[data-nette-gpspicker]').each(function () {
			var $el = $(this);
			var options = parseDataAttribute(this);

			var x = options.size.x;
			var y = options.size.y;

			var $mapContainer = $('<div>', {
				width: typeof x == 'number' ? x + 'px' : x,
				height: typeof y == 'number' ? y + 'px' : y,
				position: 'relative'
			}).prependTo($el);

			$el.find('label').hide();
			new handlers[options.shape]($el, $el.find('input').hide(), new google.maps.Map($mapContainer[0], {
				mapTypeId: google.maps.MapTypeId[options.type] || google.maps.MapTypeId.ROADMAP
			}), options);
		});
	});

	this.registerHandler = function (type, handler, callback) {
		handlers[type] = handler;
		callback(Nette);
	};
};

var GpsPicker = window.NetteGpsPicker = window.NetteGpsPicker || new GpsPicker();

// single GPS position
GpsPicker.registerHandler('point', function ($el, $inputs, map, options) {
	var $latInput = $inputs.filter('[id$=lat]');
	var $lngInput = $inputs.filter('[id$=lng]');

	var position = new google.maps.LatLng($latInput.val() * 1, $lngInput.val() * 1);

	var marker = new google.maps.Marker({
		position: position,
		map: map,
		draggable: true
	});

	map.setCenter(position);
	map.setZoom(options.zoom);

	google.maps.event.addListener(marker, 'mouseup', function (e) {
		$latInput.val(e.latLng.lat());
		$lngInput.val(e.latLng.lng());
	});
}, function (Nette) {
	Nette.validators.maxLat = function (elem, arg, value) {
		return value <= arg;
	};
	Nette.validators.maxLng = function (elem, arg, value) {
		console.log(value, arg);
		return value <= arg;
	};
	Nette.validators.minLat = function (elem, arg, value) {
		console.log(value, arg);
		return value >= arg;
	};
	Nette.validators.minLng = function (elem, arg, value) {
		return value >= arg;
	};
});

})(window);
