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

	$.fn.gpspicker = function (options) {
		return this.each(function () {
			that.initialize(this, options);
		});
	};

	this.load = function () {
		return $('[data-nette-gpspicker]').gpspicker();
	};

	this.initialize = function (el, options) {
		var $el = $(el), gpspicker;
		if (gpspicker = $el.data('gpspicker')) return gpspicker;

		var options = $.extend(parseDataAttribute(el), options || {});

		var x = options.size.x;
		var y = options.size.y;

		var $mapContainer = $('<div>', {
			width: typeof x == 'number' ? x + 'px' : x,
			height: typeof y == 'number' ? y + 'px' : y,
			position: 'relative'
		}).prependTo($el);
		var $inputs = $el.find('input:not([id$=search])').hide();
		$el.find('label').hide();

		if (options.search) {
			var $search = $el.find('[id$=search]');
			if ($search.length) {
				$search.show();
			} else {
				$search = $('<input>', {
					type: 'text'
				}).prependTo($el);
			}
			options.search = new google.maps.places.Autocomplete($search[0], {});
		}

		var map = new google.maps.Map($mapContainer[0], {
			mapTypeId: google.maps.MapTypeId[options.type] || google.maps.MapTypeId.ROADMAP
			, draggable: !options.disabled
		});

		return $el.data('gpspicker', $.extend({
			map: map
		}, new handlers[options.shape]($el, $inputs, map, options) || {}));
	};

	this.registerHandler = function (type, handler, callback) {
		handlers[type] = handler;
		if (Nette) {
			callback(Nette);
		}
	};

	$(function () {
		that.load();
	});
};

var GpsPicker = window.NetteGpsPicker = window.NetteGpsPicker || new GpsPicker();

// single GPS position
GpsPicker.registerHandler('point', function ($el, $inputs, map, options) {
	var $latInput = $inputs.filter('[id$=lat]');
	var $lngInput = $inputs.filter('[id$=lng]');
	var trigger = function (lat, lng) {
		$el.trigger('change.gpspicker', [{
			lat: lat,
			lng: lng
		}]);
	};

	var position = new google.maps.LatLng($latInput.val() * 1, $lngInput.val() * 1);

	var marker = new google.maps.Marker({
		position: position,
		map: map,
		draggable: !options.disabled
	});

	map.setCenter(position);
	map.setZoom(options.zoom);

	google.maps.event.addListener(marker, 'mouseup', function (e) {
		$latInput.val(e.latLng.lat());
		$lngInput.val(e.latLng.lng());
		trigger(e.latLng.lat(), e.latLng.lng());
	});

	var timeout;
	google.maps.event.addListener(map, 'click', function (e) {
		timeout = setTimeout(function () {
			marker.setPosition(e.latLng);
			marker.setMap(map);
			$latInput.val(e.latLng.lat());
			$lngInput.val(e.latLng.lng());
			trigger(e.latLng.lat(), e.latLng.lng());
		}, 200);
	});
	google.maps.event.addListener(map, 'dblclick', function (e) {
		if (timeout) {
			clearTimeout(timeout);
			timeout = null;
		}
	});

	if (options.search) {
		google.maps.event.addListener(options.search, 'place_changed', function () {
			var place = options.search.getPlace();
			if (!place.geometry) return;

			var location = place.geometry.location;
			if (place.geometry.viewport) {
				map.fitBounds(place.geometry.viewport);
			} else {
				map.setCenter(location);
				map.setZoom(17);
			}
			marker.setPosition(location);
			$latInput.val(location.lat());
			$lngInput.val(location.lng());
			trigger(location.lat(), location.lng());
		});
	}

	return {
		marker: marker,
		getValue: function () {
			return {
				lat: $latInput.val(),
				lng: $lngInput.val()
			};
		},
		setValue: function (lat, lng) {
			lat = lat * 1;
			lng = lng * 1;
			$latInput.val(lat);
			$lngInput.val(lng);
			marker.setPosition(new google.maps.LatLng(lat, lng));
			trigger(lat, lng);
		}
	};
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
