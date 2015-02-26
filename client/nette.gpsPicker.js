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

if (!$) {
	console.error("'nette-forms-gpspicker' requires jQuery.");
	return;
}

var shapes = {
	point: (function () {
		var PointShape = function ($el, $inputs) {
			this._el = $el;
			this._latInput = $inputs.filter('[id$=lat]');
			this._lngInput = $inputs.filter('[id$=lng]');
		};
		PointShape.prototype.fill = function (lat, lng) {
			this._latInput.val(lat);
			this._lngInput.val(lng);
			this._el.trigger('change.gpspicker', [{
				lat: lat,
				lng: lng
			}]);
		};
		PointShape.prototype.setValue = function (lat, lng) {
			lat = lat * 1;
			lng = lng * 1;
			this.fill(lat, lng);
			this._onSetValue(lat, lng);
		};
		PointShape.prototype.getValue = function () {
			return {
				lat: this._latInput.val(),
				lng: this._lngInput.val()
			};
		};
		return PointShape;
	})()
};

var drivers = {
	google: {
		isSupported: !!google,
		createMap: function ($container, options) {
			return new google.maps.Map($container[0], {
				mapTypeId: google.maps.MapTypeId[options.type] || google.maps.MapTypeId.ROADMAP
			});
		},
		search: function ($search) {
			return new google.maps.places.Autocomplete($search.get(0), {});
		},
		shapes: {
			point: function (shape, options) {
				var val = shape.getValue();
				var position = new google.maps.LatLng(val.lat * 1, val.lng * 1);
				shape.map.setCenter(position);
				shape.map.setZoom(options.zoom);

				shape.marker = new google.maps.Marker({
					position: position,
					map: shape.map,
					draggable: !options.disabled
				});
				shape._onSetValue = function (lat, lng) {
					this.marker.setPosition(new google.maps.LatLng(lat, lng));
				};

				if (!options.disabled) {
					google.maps.event.addListener(shape.marker, 'mouseup', function (e) {
						shape.fill(e.latLng.lat(), e.latLng.lng());
					});

					var timeout;
					google.maps.event.addListener(shape.map, 'click', function (e) {
						timeout = setTimeout(function () {
							shape.marker.setPosition(e.latLng);
							shape.marker.setMap(shape.map);
							shape.fill(e.latLng.lat(), e.latLng.lng());
						}, 200);
					});
					google.maps.event.addListener(shape.map, 'dblclick', function (e) {
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
								shape.map.fitBounds(place.geometry.viewport);
							} else {
								shape.map.setCenter(location);
								shape.map.setZoom(17);
							}
							shape.marker.setPosition(location);
							shape.fill(location.lat(), location.lng());
						});
					}
				}
			}
		}
	},
	nokia: {
		isSupported: !!nokia,
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
			point: function (shape, options) {
				var val = shape.getValue();
				var coordinate = new nokia.maps.geo.Coordinate(val.lat * 1, val.lng * 1);

				shape.marker = new nokia.maps.map.StandardMarker(coordinate, {
					draggable: !options.disabled
				});
				shape.map.objects.add(shape.marker);
				shape._onSetValue = function (lat, lng) {
					this.marker.set('coordinate', new nokia.maps.geo.Coordinate(lat, lng));
				};

				shape.map.setCenter(coordinate);
				shape.map.setZoomLevel(options.zoom);

				if (!options.disabled) {
					shape.marker.addListener('dragend', function () {
						shape.fill(shape.coordinate.latitude, shape.coordinate.longitude);
					}, false);

					var timeout;
					shape.map.addListener('click', function (e) {
						if (timeout) {
							clearTimeout(timeout);
						}
						timeout = setTimeout(function () {
							var coordinate = shape.map.pixelToGeo(e.displayX, e.displayY);
							shape.marker.set('coordinate', coordinate);
							shape.fill(coordinate.latitude, coordinate.longitude);
						}, 200);
					});
					shape.map.addListener('mapviewchangestart', function (e) {
						if (timeout) {
							clearTimeout(timeout);
							timeout = null;
						}
					});
				}
			}
		}
	},
	seznam: {
		isSupported: !!SMap,
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
			point: function (shape, options) {
				var val = shape.getValue();
				var position = SMap.Coords.fromWGS84(val.lng * 1, val.lat * 1);

				var layer = new SMap.Layer.Marker();
				shape.map.addLayer(layer);
				layer.enable();

				shape.marker = new SMap.Marker(position, '', {});
				if (!options.disabled) {
					var mouse = new SMap.Control.Mouse(SMap.MOUSE_PAN | SMap.MOUSE_WHEEL | SMap.MOUSE_ZOOM);
					shape.map.addControl(mouse);
					shape.marker.decorate(SMap.Marker.Feature.Draggable);
				}
				layer.addMarker(shape.marker);
				shape._onSetValue = function (lat, lng) {
					this.marker.setCoords(SMap.Coords.fromWGS84(lng, lat));
				};

				shape.map.setCenterZoom(position, options.zoom);

				if (!options.disabled) {
					var signals = shape.map.getSignals();
					signals.addListener(window, 'marker-drag-stop', function (e) {
						var coords = e.target.getCoords().toWGS84();
						shape.fill(coords[1], coords[0]);
					});

					var timeout;
					signals.addListener(shape.map, 'map-click', function (e) {
						if (timeout) {
							clearTimeout(timeout);
						}
						timeout = setTimeout(function () {
							var coords = SMap.Coords.fromEvent(e.data.event, shape.map);
							shape.marker.setCoords(coords);
							coords = coords.toWGS84();
							shape.fill(coords[1], coords[0]);
						}, 200);
					});
					signals.addListener(shape.map, 'zoom-start', function (e) {
						if (timeout) {
							clearTimeout(timeout);
							timeout = null;
						}
					});
				}
			}
		}
	}
};
drivers.openstreetmap = {
	isSupported: !!google,
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
	},
	shapes: drivers.google.shapes
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
		$el.find('label:not([for$=search])').hide();

		var driver = drivers[options.driver];
		if (!driver.isSupported) {
			console.error("Driver '" + options.driver + "' misses appropriate API SDK.");
			return $el;
		}
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

		var shape = new shapes[options.shape]($el, $inputs);
		shape.map = map;
		driver.shapes[options.shape](shape, options);
		return $el.data('gpspicker', shape);
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
			Nette.validators.VojtechDobesNetteFormsGpsPositionPicker_validateMaxLat = function (elem, arg, value) {
				return value <= arg;
			};
			Nette.validators.VojtechDobesNetteFormsGpsPositionPicker_validateMaxLng = function (elem, arg, value) {
				return value <= arg;
			};
			Nette.validators.VojtechDobesNetteFormsGpsPositionPicker_validateMinLat = function (elem, arg, value) {
				return value >= arg;
			};
			Nette.validators.VojtechDobesNetteFormsGpsPositionPicker_validateMinLng = function (elem, arg, value) {
				return value >= arg;
			};
		}

		that.load();
	});
};

var GpsPicker = window.NetteGpsPicker = window.NetteGpsPicker || new GpsPicker();

})(window);
