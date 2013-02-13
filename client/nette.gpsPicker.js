/**
 * Unobtrusive handler for GpsPicker
 *
 * @author Vojtěch Dobeš
 * @license New BSD
 *
 * @dependency jQuery
 * @dependency Google Maps API v3 || Nokia Maps v1.2 || Mapy.cz API v4
 * @dependency netteForms.js (optional)
 */

(function(window, undefined) {

var google = window.google;
var nokia = window.nokia;
var SMap = window.SMap;
var $ = window.jQuery;
var Nette = window.Nette;

if ((!google && !nokia && !SMap) || !$) {
	return;
}

var drivers = {
	google: {
		createMap: function ($container, options) {
			return new google.maps.Map($container[0], {
				mapTypeId: google.maps.MapTypeId[options.type] || google.maps.MapTypeId.ROADMAP
			});
		},
		search: function ($search) {
			return new google.maps.places.Autocomplete($search.get(0), {});
		},
		shapes: {
			point: function ($el, $inputs, map, options) {
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

				if (options.disabled) {
					return {
						marker: marker,
						getValue: function () {
							return {
								lat: $latInput.val(),
								lng: $lngInput.val()
							};
						}
					};
				}

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
			}
		}
	},
	nokia: {
		createMap: function ($container, options) {
			if (options.type === 'ROADMAP') {
				options.type = 'NORMAL';
			}
			return new nokia.maps.map.Display($container[0], {
				baseMapType: nokia.maps.map.Display[options.type],
				components: [
					new nokia.maps.map.component.Behavior(),
					new nokia.maps.map.component.ZoomBar(),
					new nokia.maps.map.component.Overview(),
					new nokia.maps.map.component.TypeSelector(),
					new nokia.maps.map.component.ScaleBar()
				]
			});
		},
		shapes: {
			point: function ($el, $inputs, map, options) {
				var $latInput = $inputs.filter('[id$=lat]');
				var $lngInput = $inputs.filter('[id$=lng]');
				var trigger = function (lat, lng) {
					$el.trigger('change.gpspicker', [{
						lat: lat,
						lng: lng
					}]);
				};

				var coordinate = new nokia.maps.geo.Coordinate($latInput.val() * 1, $lngInput.val() * 1);

				var marker = new nokia.maps.map.StandardMarker(coordinate, {
					draggable: !options.disabled
				});
				map.objects.add(marker);

				map.setCenter(coordinate);
				map.setZoomLevel(options.zoom);

				if (options.disabled) {
					return {
						marker: marker,
						getValue: function () {
							return {
								lat: $latInput.val(),
								lng: $lngInput.val()
							};
						}
					};
				}

				marker.addListener('dragend', function () {
					$latInput.val(marker.coordinate.latitude);
					$lngInput.val(marker.coordinate.longitude);
					trigger(marker.coordinate.latitude, marker.coordinate.longitude);
				}, false);

				var timeout;
				map.addListener('click', function (e) {
					if (timeout) {
						clearTimeout(timeout);
					}
					timeout = setTimeout(function () {
						var coordinate = map.pixelToGeo(e.displayX, e.displayY);
						marker.set('coordinate', coordinate);
						$latInput.val(coordinate.latitude);
						$lngInput.val(coordinate.longitude);
						trigger(coordinate.latitude, coordinate.longitude);
					}, 200);
				});
				map.addListener('mapviewchangestart', function (e) {
					if (timeout) {
						clearTimeout(timeout);
						timeout = null;
					}
				});

				/*if (options.search) {
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
				}*/

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
						marker.set('coordinate', new nokia.maps.geo.Coordinate(lat, lng));
						trigger(lat, lng);
					}
				};
			}
		}
	},
	openstreetmap: {
		createMap: function ($container, options) {
			var map = new google.maps.Map($container[0], {
				mapTypeId: 'OSM',
				mapTypeControlOptions: {
					mapTypeIds: []
				}
			});
			map.mapTypes.set('OSM', new google.maps.ImageMapType({
				getTileUrl: function(coord, zoom) {
					return 'http://tile.openstreetmap.org/' + zoom + '/' + coord.x + '/' + coord.y + '.png';
				},
				tileSize: new google.maps.Size(256, 256),
				name: 'OpenStreetMap',
				maxZoom: 18
			}));
			return map;
		}
	},
	seznam: {
		createMap: function ($container, options) {
			var map = new SMap(
				$container.get(0),
				SMap.Coords.fromWGS84(14.41, 50.08),
				10
			);
			if (options.type === 'ROADMAP') {
				options.type = 'BASE';
			} else if (options.type === 'SATELLITE') {
				options.type = 'OPHOTO';
			}
			map.addDefaultLayer(SMap['DEF_'+ options.type]).enable();
			map.addDefaultControls();
			return map;
		},
		shapes: {
			point: function ($el, $inputs, map, options) {
				var $latInput = $inputs.filter('[id$=lat]');
				var $lngInput = $inputs.filter('[id$=lng]');
				var trigger = function (lat, lng) {
					$el.trigger('change.gpspicker', [{
						lat: lat,
						lng: lng
					}]);
				};

				var position = SMap.Coords.fromWGS84($lngInput.val() * 1, $latInput.val() * 1);

				var layer = new SMap.Layer.Marker();
				map.addLayer(layer);
				layer.enable();

				var marker = new SMap.Marker(position, '', {});
				if (!options.disabled) {
					var mouse = new SMap.Control.Mouse(SMap.MOUSE_PAN | SMap.MOUSE_WHEEL | SMap.MOUSE_ZOOM);
					map.addControl(mouse);
					marker.decorate(SMap.Marker.Feature.Draggable);
				}
				layer.addMarker(marker);

				map.setCenterZoom(position, options.zoom);

				if (options.disabled) {
					return {
						marker: marker,
						getValue: function () {
							return {
								lat: $latInput.val(),
								lng: $lngInput.val()
							};
						}
					};
				}

				var signals = map.getSignals();
				signals.addListener(window, 'marker-drag-stop', function (e) {
					var coords = e.target.getCoords().toWGS84();
					$latInput.val(coords[1]);
					$lngInput.val(coords[0]);
					trigger(coords[1], coords[0]);
				});

				var timeout;
				signals.addListener(map, 'map-click', function (e) {
					if (timeout) {
						clearTimeout(timeout);
					}
					timeout = setTimeout(function () {
						var coords = SMap.Coords.fromEvent(e.data.event, map);
						marker.setCoords(coords);
						coords = coords.toWGS84();
						$latInput.val(coords[1]);
						$lngInput.val(coords[0]);
						trigger(coords[1], coords[0]);
					}, 200);
				});
				signals.addListener(map, 'zoom-start', function (e) {
					if (timeout) {
						clearTimeout(timeout);
						timeout = null;
					}
				});

				/*if (options.search) {
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
				}*/

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
						marker.setCoords(SMap.Coords.fromWGS84(lat, lng));
						trigger(lat, lng);
					}
				};
			}
		}
	}
};

var GpsPicker = function () {
	var that = this;

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
		var $inputs = $el.find('input:not([id$=search])');
		if (!options.manualInput) {
			$inputs.hide();
		} else {
			$inputs.on('change.gpspicker input.gpspicker', function () {
				var args = [];
				$inputs.each(function () {
					args.push($(this).val());
				});
				$el.data('gpspicker').setValue.apply($el.data('gpspicker'), args);
			});
		}
		$el.find('label').hide();

		var driver = drivers[options.driver];
		var map = driver.createMap($mapContainer, options);

		if (options.search && driver.search) {
			var $search = $el.find('[id$=search]');
			if ($search.length) {
				$search.show();
			} else {
				$search = $('<input>', {
					type: 'text'
				}).prependTo($el);
			}
			options.search = driver.search($search);
		}

		return $el.data('gpspicker', $.extend({
			map: map
		}, driver.shapes[options.shape]($el, $inputs, map, options) || {}));
	};

	$(function () {
		// Twitter Bootstrap
		var rules = [
			'[data-nette-gpspicker] img { max-width: none; }'
		];
		var stylesheet = window.document.styleSheets[0];
		var method = stylesheet.cssRules ? 'insertRule' : 'addRule';
		for (var i = 0; i < rules.length; i++) {
			stylesheet[method].call(stylesheet,	 rules[i], 0);
		}

		if (Nette) {
			Nette.validators.maxLat = function (elem, arg, value) {
				return value <= arg;
			};
			Nette.validators.maxLng = function (elem, arg, value) {
				return value <= arg;
			};
			Nette.validators.minLat = function (elem, arg, value) {
				return value >= arg;
			};
			Nette.validators.minLng = function (elem, arg, value) {
				return value >= arg;
			};
		}

		that.load();
	});
};

var GpsPicker = window.NetteGpsPicker = window.NetteGpsPicker || new GpsPicker();

})(window);
