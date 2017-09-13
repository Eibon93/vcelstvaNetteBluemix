/* 
 * Copyright (C) 2017 Jan Bartoška
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

/* global SMap, basePath */

/* CAST PRO GENEROVANI MARKERU S MISTEM PRODEJE MEDU */ 

var createMarkerForStanoviste = function (stanoviste, draggable) {
    var options = {
	title: '' + ((stanoviste.nazev.length > 1)
		? stanoviste.nazev
		: 'Stanoviště včelích úlů')
    };

    var card = new SMap.Card();
    card.getHeader().innerHTML = '<strong>'
	    + ((stanoviste.nazev.length > 1)
		    ? stanoviste.nazev + ': '
		    : 'Stanoviště včelích úlů')
	    + '</strong>';

    card.getBody().innerHTML = 
	    '<p>Počet včelstev: '
	    + stanoviste.pocetVcelstev
	    + '<br>Registrační číslo: ' 
	    + stanoviste.registracniCislo
	    + '<br>Parcela číslo: '
	    + stanoviste.parcelaCislo
	    + '<br>'
	    + stanoviste.obec
	    + '</p>';

    var pozice = SMap.Coords.fromWGS84(stanoviste.lng, stanoviste.lat);

    var marker = new SMap.Marker(pozice, "marker" + stanoviste.id, options);
    marker.setURL(basePath + '/img/marker_stanoviste.png');

    if (draggable)
	marker.decorate(SMap.Marker.Feature.Draggable);

    marker.decorate(SMap.Marker.Feature.Card, card);

    return marker;
};

function markerFactoryStanoviste (seznamStanovist, draggable) {
    if (typeof draggable === "undefined") {
	draggable = false;
    }

    var layer = new SMap.Layer.Marker();

    seznamStanovist.forEach(function (stanoviste) {
	layer.addMarker(createMarkerForStanoviste(stanoviste, draggable));
    });

    return layer;
}

/* CAST PRO GENEROVANI MARKERU S MISTEM PRODEJE MEDU */ 

var createMarkerForProdejna = function (prodejna, draggable) {
    var options = {
	title: '' + ((prodejna.nazev.length > 1)
		? prodejna.nazev
		: 'Prodejní místo medu')
    };

    var card = new SMap.Card();
    card.getHeader().innerHTML = '<strong>'
	    + ((prodejna.nazev.length > 1)
		    ? prodejna.nazev + ': '
		    : 'Prodejní místo medu:')
	    + '</strong>';

    card.getBody().innerHTML = '<p>' + prodejna.informace
	    + '<br>'
	    + prodejna.adresa
	    + ((prodejna.wwwStranky && prodejna.wwwStranky.length > 4)
		? '<br>'
		    + '<a href="' + prodejna.wwwStranky + '" target="_blank">'
		    + prodejna.wwwStranky
		    + '</a>' 
		: '')
	    + '</p>';

    var pozice = SMap.Coords.fromWGS84(prodejna.lng, prodejna.lat);

    var marker = new SMap.Marker(pozice, "marker" + prodejna.id, options);
    marker.setURL(basePath + '/img/marker_prodej_medu.png');

    if (draggable)
	marker.decorate(SMap.Marker.Feature.Draggable);

    marker.decorate(SMap.Marker.Feature.Card, card);

    return marker;
};

function markerFactoryProdejniMistaMedu (prodejniMistaMedu, draggable) {
    if (typeof draggable === "undefined") {
	draggable = false;
    }

    var layer = new SMap.Layer.Marker();

    prodejniMistaMedu.forEach(function (prodejna) {
	layer.addMarker(createMarkerForProdejna(prodejna, draggable));
    });

    return layer;
}
