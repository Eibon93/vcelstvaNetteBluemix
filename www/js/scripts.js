/*
 * Copyright (C) 2017 Pavel Junek
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

/* global basePath, Nette, JAK, SMap */

$(function () {

	// *************************************************************************
	// Objekt pro zobrazení mapy

	var Map = function (element) {
		this.element = element;
		this.map = null;
		this.layer = null;
		this.prodejnyMedu = [];
		this.postriky = [];
		this.stanoviste = [];
		this.editor = null;
		this.search = null;
		this.query = null;
	};

	Map.prototype = Object.create(null);
	Map.prototype.constructor = Map;

	Map.prototype.PRODEJNA_MEDU = 'prodejna';
	Map.prototype.STANOVISTE = 'stanoviste';
	Map.prototype.STANOVISTE_REFERENCNI = 'stanoviste_referencni';
	Map.prototype.POSTRIK_BEZPECNY = 'postrik_bezpecny';
	Map.prototype.POSTRIK_NEBEZPECNY = 'postrik_nebezpecny';
	Map.prototype.POSTRIK_ZVLAST_NEBEZPECNY = 'postrik_zvlast_nebezpecny';

	Map.prototype.show = function () {
		var self = this;

		// Počáteční hodnoty pro zobrazení mapy
		var lat = 50.130245107278;
		var lng = 14.373347371164;
		var zoom = 8;

		// Zjistíme počet objektů v mapě
		var count = (this.editor ? 1 : 0) + this.prodejnyMedu.length + this.postriky.length + this.stanoviste.length;

		// Přizpůsobíme zobrazení, pokud máme jen jeden objekt
		if (count === 1) {
			var point = [this.editor ? this.editor.getLocation() : undefined]
					.concat(this.prodejnyMedu.map(function (p) {
						return {
							lat: p.lat,
							lng: p.lng
						};
					}))
					.concat(this.postriky.map(function (p) {
						return {
							lat: p.lat,
							lng: p.lng
						};
					}))
					.concat(this.stanoviste.map(function (s) {
						return {
							lat: s.lat,
							lng: s.lng
						};
					}))[0];
			if (point) {
				lat = point.lat;
				lng = point.lng;
				zoom = 15;
			}
		}

		// Vytvoříme mapu se základními vrstvami
		this.map = new SMap(this.element, SMap.Coords.fromWGS84(lng, lat), zoom);

		this.map.addDefaultLayer(SMap.DEF_OPHOTO);
		this.map.addDefaultLayer(SMap.DEF_OPHOTO0203);
		this.map.addDefaultLayer(SMap.DEF_OPHOTO0406);
		this.map.addDefaultLayer(SMap.DEF_TURIST);
		this.map.addDefaultLayer(SMap.DEF_HISTORIC);
		this.map.addDefaultLayer(SMap.DEF_BASE).enable();

		// Přidáme základní ovládací prvky
		this.map.addDefaultControls();

		// Přidáme ovládací prvek pro přepínání vrstev
		var layerSwitch = new SMap.Control.Layer();
		layerSwitch.addDefaultLayer(SMap.DEF_BASE);
		layerSwitch.addDefaultLayer(SMap.DEF_OPHOTO);
		layerSwitch.addDefaultLayer(SMap.DEF_TURIST);
		layerSwitch.addDefaultLayer(SMap.DEF_OPHOTO0406);
		layerSwitch.addDefaultLayer(SMap.DEF_OPHOTO0203);
		layerSwitch.addDefaultLayer(SMap.DEF_HISTORIC);
		this.map.addControl(layerSwitch, {
			left: "8px",
			top: "9px"
		});

		// Přidáme vrstvu pro zobrazení objektů
		this.layer = new SMap.Layer.Marker();
		this.map.addLayer(this.layer);
		this.layer.enable();

		// Aktivujeme reakce na události
		var signals = this.map.getSignals();
		signals.addListener(window, 'map-click', function (e) {
			var coords = SMap.Coords.fromEvent(e.data.event, self.map);

			if (self.editor) {
				self.editor.handleMapClick(coords);
			}
		});
		signals.addListener(window, 'marker-drag-stop', function (e) {
			var marker = e.target;

			if (self.editor) {
				self.editor.handleMarkerMove(marker);
			}
		});

		// Zobrazíme objekty v mapě
		this.prodejnyMedu.forEach(function (p) {
			this.showProdejnaMedu(p, false);
		}, this);
		this.postriky.forEach(function (p) {
			this.showPostrik(p, false);
		}, this);
		this.stanoviste.forEach(function (s) {
			this.showStanoviste(s, false);
		}, this);

		// Zobrazíme editor
		this.showEditor();

		// Přizpůsobíme zobrazení, pokud máme více objektů
		if (count > 1) {
			this.adjustCenterZoom();
		}

		return this;
	};

	Map.prototype.setEditable = function (options) {
		if (this.editor) {
			throw 'Invalid state';
		}

		if (!$.isPlainObject(options)
				|| !(options.lat instanceof jQuery)
				|| !(options.lng instanceof jQuery)
				|| !(options.text instanceof jQuery)
				|| ($.type(options.type) !== 'string')
				|| ($.type(options.title) !== 'string')) {
			throw 'Invalid arguments';
		}

		var self = this;

		var inputLat = options.lat;
		var inputLng = options.lng;
		var inputText = options.text;
		var markerType = options.type;
		var markerTitle = options.title;

		var marker;

		var addMarker = function (coords) {
			return self.addMarker('editor', coords, markerTitle, markerType, true);
		};

		var updateMarker = function (coords, center) {
			if (marker) {
				marker.setCoords(coords);
			} else {
				marker = addMarker(coords);
			}
			if (center) {
				self.map.setCenterZoom(coords, 15);
			}
			updateControls(coords);
		};

		var updateControls = function (coords) {
			var gps = coords.toWGS84();
			var lat = gps[1];
			var lng = gps[0];

			inputLat.val(lat.toFixed(6));
			inputLng.val(lng.toFixed(6));
			inputText.val(coords.toWGS84(2).reverse().join(', '));

			if (self.query) {
				self.query.handleLocationChanged(coords);
			}
		};

		var show = function () {
			var lat = parseFloat(inputLat.val());
			var lng = parseFloat(inputLng.val());

			if (!isNaN(lat) && !isNaN(lng)) {
				marker = addMarker(SMap.Coords.fromWGS84(lng, lat));
			}
		};

		this.editor = {
			getLocation: function () {
				var lat = parseFloat(inputLat.val());
				var lng = parseFloat(inputLng.val());
				return !isNaN(lat) && !isNaN(lng) ? {lat: lat, lng: lng} : undefined;
			},
			show: function () {
				show();
			},
			handleMapClick: function (coords) {
				updateMarker(coords, false);
			},
			handleMarkerMove: function (movedMarker) {
				if (movedMarker === marker) {
					updateControls(marker.getCoords());
				}
			},
			handleSearch: function (coords) {
				updateMarker(coords, true);
			}
		};

		this.showEditor();

		return this;
	};

	Map.prototype.setSearchable = function (options) {
		if (!this.editor || this.search) {
			throw 'Invalid state';
		}

		if (!$.isPlainObject(options)
				|| !(options.street instanceof jQuery)
				|| !(options.town instanceof jQuery)
				|| !(options.zip instanceof jQuery)
				|| !(options.find instanceof jQuery)) {
			throw 'Invalid arguments';
		}

		var self = this;

		var inputStreet = options.street;
		var inputTown = options.town;
		var inputZip = options.zip;
		var buttonFind = options.find;

		var isEmpty = function () {
			return !inputStreet.val() && !inputTown.val() && !inputZip.val();
		};

		var isFilled = function () {
			return inputStreet.val() && inputTown.val() && inputZip.val();
		};

		var isValid = function () {
			return $().add(inputStreet).add(inputTown).add(inputZip).validate();
		};

		var getAddress = function () {
			return inputStreet.val() + ', ' + inputZip.val() + ' ' + inputTown.val();
		};

		var disableControls = function () {
			buttonFind.attr('disabled', true);
		};

		var enableControls = function () {
			buttonFind.attr('disabled', false);
		};

		var updateLocation = function (coords) {
			self.editor.handleSearch(coords);
		};

		var showError = function () {
			alert('Zadaná adresa nebyla nalezena, prosím upřesněte polohu kliknutím do mapy.');
		};

		var processing = false;
		var automatic = isEmpty();

		var search = function (address) {
			if (!processing) {
				processing = true;
				disableControls();

				JAK.Request.supportsCrossOrigin = function () {
					return false;
				};

				new SMap.Geocoder(address, function (geocoder) {
					if (geocoder.getResults()[0].results.length) {
						processing = false;
						automatic = false;
						enableControls();

						updateLocation(geocoder.getResults()[0].results[0].coords);
					} else {
						processing = false;
						enableControls();

						showError();
					}
				});
			}
		};

		$().add(inputStreet).add(inputTown).add(inputZip).on('blur', function () {
			if (automatic && isFilled() && isValid()) {
				search(getAddress());
			}
		});

		$().add(buttonFind).on('click', function () {
			if (isValid()) {
				search(getAddress());
			}
		});

		this.search = {};

		return this;
	};

	Map.prototype.setQueryable = function (options) {
		if (!this.editor || this.query) {
			throw 'Invalid state';
		}

		if (!$.isPlainObject(options)
				|| !(options.katastralniUzemiId instanceof jQuery)
				|| !(options.parcelaId instanceof jQuery)
				|| !(options.pudniBlokId instanceof jQuery)
				|| !(options.katastralniUzemi instanceof jQuery)
				|| !(options.parcela instanceof jQuery)
				|| !(options.pudniBlok instanceof jQuery)
				|| ($.type(options.url) !== 'string')) {
			throw 'Invalid arguments';
		}

		var inputKatastralniUzemiId = options.katastralniUzemiId;
		var inputParcelaId = options.parcelaId;
		var inputPudniBlokId = options.pudniBlokId;
		var inputKatastralniUzemi = options.katastralniUzemi;
		var inputParcela = options.parcela;
		var inputPudniBlok = options.pudniBlok;

		var url = options.url;

		var showSpinner = function () {
			$('body').showSpinner();
		};

		var hideSpinner = function () {
			$('body').hideSpinner();
		};

		var updateControls = function (data) {
			inputKatastralniUzemiId.val(data.katastralniUzemi.id);
			inputParcelaId.val(data.parcela.id);
			inputPudniBlokId.val(data.pudniBlok ? data.pudniBlok.id : '');
			inputKatastralniUzemi.val(data.katastralniUzemi.nazev + ' [' + data.katastralniUzemi.id + ']');
			inputParcela.val(data.parcela.cislo + (data.parcela.podcislo ? '/' + data.parcela.podcislo : '') + (data.parcela.druh === 's' ? ' (stavební)' : ' (pozemková)'));
			inputPudniBlok.val(data.pudniBlok ? data.pudniBlok.id : '');
		};

		var clearControls = function () {
			inputKatastralniUzemiId.val('');
			inputParcelaId.val('');
			inputPudniBlokId.val('');
			inputKatastralniUzemi.val('');
			inputParcela.val('');
			inputPudniBlok.val('');
		};

		var showError = function () {
			alert('Nepodařilo se načíst data o pozemku. Zkuste to prosím později.');
		};

		var processing = false;

		var query = function (coords) {
			var gps = coords.toWGS84();
			var lat = gps[1];
			var lng = gps[0];

			if (!processing) {
				processing = true;
				showSpinner();

				$.get(url, {
					lat: lat,
					lng: lng
				}).done(function (data) {
					if (data.status === 'success') {
						updateControls(data);
					} else {
						clearControls();
						showError();
					}
				}).fail(function () {
					clearControls();
					showError();
				}).always(function () {
					processing = false;
					hideSpinner();
				});
			}
		};

		this.query = {
			handleLocationChanged: function (coords) {
				query(coords);
			}
		};

		return this;
	};

	Map.prototype.addProdejnyMedu = function (prodejnyMedu) {
		if (!$.isArray(prodejnyMedu)) {
			throw 'Invalid arguments';
		}

		this.prodejnyMedu = this.prodejnyMedu.concat(prodejnyMedu);

		prodejnyMedu.forEach(function (p) {
			this.showProdejnaMedu(p, false);
		}, this);

		this.adjustCenterZoom();

		return this;
	};

	Map.prototype.addPostriky = function (postriky) {
		if (!$.isArray(postriky)) {
			throw 'Invalid arguments';
		}

		this.postriky = this.postriky.concat(postriky);

		postriky.forEach(function (p) {
			this.showPostrik(p, false);
		}, this);

		this.adjustCenterZoom();

		return this;
	};

	Map.prototype.addStanoviste = function (stanoviste) {
		if (!$.isArray(stanoviste)) {
			throw 'Invalid arguments';
		}

		this.stanoviste = this.stanoviste.concat(stanoviste);

		stanoviste.forEach(function (s) {
			this.showStanoviste(s, false);
		}, this);

		this.adjustCenterZoom();

		return this;
	};

	Map.prototype.showProdejnaMedu = function (prodejnaMedu) {
		if (!this.map) {
			return;
		}

		var id = 'prodejna-' + prodejnaMedu.id;
		var coords = SMap.Coords.fromWGS84(prodejnaMedu.lng, prodejnaMedu.lat);
		var title = prodejnaMedu.nazev ? prodejnaMedu.nazev : 'Prodejní místo medu';

		var header = '<h3>' + title + '</h3>';
		var body = [];
		body.push('<p>' + prodejnaMedu.informace + '</p>');
		body.push('<address>' + prodejnaMedu.ulice + '<br>' + prodejnaMedu.psc + ' ' + prodejnaMedu.obec + '</address>');
		if (prodejnaMedu.web || prodejnaMedu.telefon) {
			body.push('<dl class="dl">');
			if (prodejnaMedu.telefon) {
				body.push('<dt>Telefon</dt>');
				body.push('<dd>' + prodejnaMedu.telefon + '</dd>');
			}
			if (prodejnaMedu.web) {
				body.push('<dt>Web</dt>');
				body.push('<dd><a href="' + prodejnaMedu.web + '" target="_blank">' + prodejnaMedu.web + '</a></dd>');
			}
			body.push('</dl>');
		}

		var card = new SMap.Card();
		card.getHeader().innerHTML = header;
		card.getBody().innerHTML = body.join('\n');

		return this.addMarker(id, coords, title, this.PRODEJNA_MEDU, false, card);
	};

	Map.prototype.showPostrik = function (postrik) {
		if (!this.map) {
			return;
		}

		var id = 'postrik-' + postrik.id;
		var coords = SMap.Coords.fromWGS84(postrik.lng, postrik.lat);
		var title = 'Postřik plánovaný na ' + postrik.datum + ' v ' + postrik.cas;

		var header = '<h3>' + title + '</h3>';
		var body = [];
		if (postrik.mimoLetovouAktivitu) {
			body.push('<div class="alert alert-success">Mimo letovou aktivitu včel</div>');
		}
		body.push('<dl class="dl">');
		body.push('<dt>Vliv na včely</dt>');
		body.push(postrik.nebezpecny === 1 ? '<dd>Bez vlivu</dd>' : (postrik.nebezpecny === 2 ? '<dd class="text-warning">Nebezpečný</dd>' : '<dd class="text-danger">Zvlášť nebezpečný</dd>'));
		body.push('<dt>Plodina</dt>');
		body.push('<dd>' + postrik.plodina + ((postrik.kvetouci) ? ' (kvetoucí)' : '') + '</dd>');
		body.push('<dt>Katastrální území</dt>');
		body.push('<dd>' + postrik.katastralniUzemi + '</dd>');
		if (postrik.telefon) {
			body.push('<dt>Telefon</dt>');
			body.push('<dd>' + postrik.telefon + '</dd>');
		}
		if (postrik.web) {
			body.push('<dt>Web</dt>');
			body.push('<dd><a href="' + postrik.web + '" target="_blank">' + postrik.web + '</a></dd>');
		}
		body.push('</dl>');

		var card = new SMap.Card();
		card.getHeader().innerHTML = header;
		card.getBody().innerHTML = body.join('\n');

		return this.addMarker(id, coords, title, postrik.nebezpecny === 1 ? this.POSTRIK_BEZPECNY : (postrik.nebezpecny === 2 ? this.POSTRIK_NEBEZPECNY : this.POSTRIK_ZVLAST_NEBEZPECNY), false, card);
	};

	Map.prototype.showStanoviste = function (stanoviste) {
		if (!this.map) {
			return;
		}

		var id = 'stanoviste-' + stanoviste.id;
		var coords = SMap.Coords.fromWGS84(stanoviste.lng, stanoviste.lat);
		var title = stanoviste.nazev.length > 1 ? stanoviste.nazev : 'Stanoviště včelích úlů';

		var header = '<h3>' + title + '</h3>';
		var body = [];
		body.push('<dl class="dl">');
		body.push('<dt>Počet včelstev</dt>');
		body.push('<dd>' + stanoviste.pocetVcelstev + '</dd>');
		body.push('<dt>Registrační číslo');
		body.push('<dd>' + stanoviste.registracniCislo + '</dd>');
		body.push('<dt>Umístění</dt>');
		body.push('<dd>' + stanoviste.obec + ', parcela č. ' + stanoviste.parcela + '</dd>');
		body.push('</dl>');
		if (stanoviste.vcelstva && stanoviste.vcelstva.length > 0) {
			body.push('<h4>Referenční včelstvo</h4>');
			body.push('<ul>');
			stanoviste.vcelstva.forEach(function (v) {
				body.push('<li><a href="' + basePath + '/vcelstva/detail/' + v.id + '">Včelstvo č. ' + v.poradoveCislo + '</a></li>');
			});
			body.push('</ul>');
		}

		var card = new SMap.Card();
		card.getHeader().innerHTML = header;
		card.getBody().innerHTML = body.join('\n');

		return this.addMarker(id, coords, title, stanoviste.referencni ? this.STANOVISTE_REFERENCNI : this.STANOVISTE, false, card);
	};

	Map.prototype.showEditor = function () {
		if (!this.map || !this.editor) {
			return;
		}

		this.editor.show();
	};

	Map.prototype.adjustCenterZoom = function () {
		if (!this.map) {
			return;
		}

		var adjustRange = function (range, lat, lng) {
			if (range.length === 0) {
				range.push({
					lat: lat,
					lng: lng
				});
				range.push({
					lat: lat,
					lng: lng
				});
			} else if (range.length === 2) {
				if (lat < range[0].lat) {
					range[0].lat = lat;
				}
				if (lng < range[0].lng) {
					range[0].lng = lng;
				}
				if (lat > range[1].lat) {
					range[1].lat = lat;
				}
				if (lng > range[1].lng) {
					range[1].lng = lng;
				}
			}
			return range;
		};

		var range = [];
		range = this.prodejnyMedu.reduce(function (range, p) {
			return adjustRange(range, p.lat, p.lng);
		}, range);
		range = this.postriky.reduce(function (range, p) {
			return adjustRange(range, p.lat, p.lng);
		}, range);
		range = this.stanoviste.reduce(function (range, s) {
			return adjustRange(range, s.lat, s.lng);
		}, range);
		if (this.editor) {
			var location = this.editor.getLocation();
			if (location) {
				range = adjustRange(range, location.lat, location.lng);
			}
		}

		var coords = range.map(function (point) {
			return SMap.Coords.fromWGS84(point.lng, point.lat);
		});

		var args = this.map.computeCenterZoom(coords, 50);

		this.map.setCenterZoom(args[0], args[1]);
	};

	Map.prototype.addMarker = function (id, coords, title, type, draggable, card) {
		if (!this.layer) {
			throw 'Invalid state';
		}

		var url;
		switch (type) {
			case Map.prototype.PRODEJNA_MEDU:
				url = basePath + '/img/marker_prodej_medu.png';
				break;
			case Map.prototype.STANOVISTE:
				url = basePath + '/img/marker_stanoviste.png';
				break;
			case Map.prototype.STANOVISTE_REFERENCNI:
				url = basePath + '/img/marker_stanoviste_referencni.png';
				break;
			case Map.prototype.POSTRIK_BEZPECNY:
				url = basePath + '/img/marker_postrik_bezpecny.png';
				break;
			case Map.prototype.POSTRIK_NEBEZPECNY:
				url = basePath + '/img/marker_postrik_nebezpecny.png';
				break;
			case Map.prototype.POSTRIK_ZVLAST_NEBEZPECNY:
				url = basePath + '/img/marker_postrik_malo_nebezpecny.png';
				break;
			default:
				break;
		}

		var marker = new SMap.Marker(coords, id, {
			title: title,
			url: url
		});

		if (draggable) {
			marker.decorate(SMap.Marker.Feature.Draggable);
		}

		if (card) {
			marker.decorate(SMap.Marker.Feature.Card, card);
		}

		this.layer.addMarker(marker);

		return marker;
	};

	// *************************************************************************
	// jQuery rozšíření o mapu

	jQuery.fn.extend({
		createMap: function () {
			if (this.length < 1) {
				throw 'No matching elements';
			}
			return new Map(this.get(0));
		}
	});

	// *************************************************************************
	// jQuery rozšíření o validaci ovládacích prvků

	jQuery.fn.extend({
		showError: function (message) {
			var target = this.next('.error');
			if (target.length === 0) {
				target = $('<p class="error text-danger">');
				this.after(target);
			}
			target.text(message).show();
			return this;
		},
		hideError: function () {
			this.next('.error').text('').hide();
			return this;
		},
		validate: function () {
			var oldAddError = Nette.addError;
			Nette.addError = function (element, message) {
				$(element).showError(message);
			};
			var result = true;
			this.each(function () {
				if (this.tagName && this.tagName.toLowerCase() in {
					input: 1,
					select: 1,
					textarea: 1,
					button: 1
				}) {
					if (!Nette.validateControl(this)) {
						result = false;
					} else {
						$(this).hideError();
					}
				}
			});
			Nette.addError = oldAddError;
			return result;
		}
	});

	// *************************************************************************
	// jQuery rozšíření o zobrazení spinneru (čekacího okna)

	jQuery.fn.extend({
		showSpinner: function () {
			var spinner = this.children('.spinner').first();
			if (spinner.length === 0) {
				spinner = $('<div class="modal fade spinner" tabindex="-1" role="dialog"><div class="modal-dialog" role="document"><div class="modal-content"><div class="modal-body"><p><img src="' + basePath + '/img/ajax-loader.gif"> Načítám data, prosím čekejte&hellip;</p></div></div></div></div>');
				this.append(spinner);
			}
			spinner.modal({
				backdrop: 'static',
				keyboard: false,
				show: true
			});
			return this;
		},
		hideSpinner: function () {
			var spinner = this.children('.spinner').first();
			if (spinner.length === 0) {
				return;
			}
			spinner.modal('hide');
			return this;
		}
	});

	// *************************************************************************
	// jQuery rozšíření o zobrazování a schovávání obsahu tlačítkem

	jQuery.fn.extend({
		updateTitle: function () {
			this.each(function () {
				var self = $(this);
				var target = $(self.data('switch'));
				var text = target.hasClass('hidden') ? self.data('show') : self.data('hide');
				if (self.prop('tagName') === 'INPUT') {
					self.val(text);
				} else {
					self.text(text);
				}
			});
			return this;
		},
		switchContent: function () {
			this.on('click', function () {
				var self = $(this);
				var target = $(self.data('switch'));
				target.toggleClass('hidden');
				self.updateTitle();
			});
			return this;
		}
	});

	$('[data-switch]').switchContent().updateTitle();

	// *************************************************************************
	// jQuery rozšíření o potvrzování nebezpečné akce

	jQuery.fn.extend({
		confirmAction: function () {
			this.on('click', function (event) {
				var message = $(this).data('confirm');
				if (confirm(message)) {
					return true;
				}
				event.preventDefault();
				return false;
			});
			return this;
		}
	});

	$('[data-confirm]').confirmAction();

	// *************************************************************************
	// jQuery rozšíření o zobrazení políček pro výběr data a času

	jQuery.fn.extend({
		fixDatePicker: function () {
			var formatDate = function (value) {
				if (value) {
					var date = new Date(value);
					return date.getDate() + '.' + (date.getMonth() + 1) + '.' + date.getFullYear();
				} else {
					return '';
				}
			};
			this.each(function () {
				var input = $(this);
				input.attr('type', 'text');
				input.val(formatDate(input.val()));
				input.datetimepicker({
					format: 'd.m.yyyy',
					language: 'cs',
					minView: 2,
					autoclose: true
				});
			});
			return this;
		},
		fixDateTimePicker: function () {
			var formatDateTime = function (value) {
				if (value) {
					var date = new Date(value);
					return date.getDate() + '.' + (date.getMonth() + 1) + '.' + date.getFullYear() + ' ' + (date.getHours() < 10 ? '0' : '') + date.getHours() + ':' + (date.getMinutes() < 10 ? '0' : '') + date.getMinutes();
				} else {
					return '';
				}
			};
			this.each(function () {
				var input = $(this);
				input.attr('type', 'text');
				input.val(formatDateTime(input.val()));
				input.datetimepicker({
					format: 'd.m.yyyy hh:ii',
					language: 'cs',
					autoclose: true
				});
			});
			return this;
		}
	});

	$('input[type=date]').fixDatePicker();
	$('input[type=datetime-local]').fixDateTimePicker();
});
