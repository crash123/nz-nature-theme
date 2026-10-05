/* global L */
/**
 * NZ Nature — Leaflet map bootstrapping.
 * Finds every .nz-map element, reads its data-nz-map JSON and renders a map
 * with clustered markers and thumbnail popups.
 */
( function () {
	'use strict';

	var TILES = {
		osm: {
			url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
			attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
			maxZoom: 19
		},
		topo: {
			url: 'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',
			attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>, tiles &copy; <a href="https://opentopomap.org">OpenTopoMap</a> (CC-BY-SA)',
			maxZoom: 17
		}
	};

	function el( tag, props ) {
		var node = document.createElement( tag );
		if ( props ) {
			Object.keys( props ).forEach( function ( k ) {
				node.setAttribute( k, props[ k ] );
			} );
		}
		return node;
	}

	function popupContent( point ) {
		var wrap = el( 'div', { 'class': 'nz-map-popup' } );
		if ( point.thumb ) {
			var link = point.url ? el( 'a', { href: point.url } ) : el( 'span' );
			link.appendChild( el( 'img', { src: point.thumb, alt: '' } ) );
			wrap.appendChild( link );
		}
		if ( point.title ) {
			var title = el( 'p', { 'class': 'nz-map-popup__title' } );
			title.textContent = point.title;
			wrap.appendChild( title );
		}
		return wrap;
	}

	function initMap( node ) {
		var data;
		try {
			data = JSON.parse( node.getAttribute( 'data-nz-map' ) );
		} catch ( e ) {
			return;
		}
		if ( ! data || ! Array.isArray( data.points ) || ! data.points.length ) {
			return;
		}

		var tile = TILES[ data.tile ] || TILES.osm;
		var map = L.map( node, {
			scrollWheelZoom: false,
			worldCopyJump: true
		} );
		L.tileLayer( tile.url, {
			attribution: tile.attribution,
			maxZoom: tile.maxZoom
		} ).addTo( map );

		var markers = [];
		var group = ( typeof L.markerClusterGroup === 'function' )
			? L.markerClusterGroup( { showCoverageOnHover: false, maxClusterRadius: 50 } )
			: L.layerGroup();

		data.points.forEach( function ( point ) {
			var lat = parseFloat( point.lat );
			var lng = parseFloat( point.lng );
			if ( isNaN( lat ) || isNaN( lng ) ) {
				return;
			}
			var marker = L.marker( [ lat, lng ] );
			marker.bindPopup( popupContent( point ) );
			group.addLayer( marker );
			markers.push( [ lat, lng ] );
		} );

		if ( ! markers.length ) {
			return;
		}

		map.addLayer( group );

		if ( data.center ) {
			var parts = String( data.center ).split( ',' );
			if ( parts.length === 2 ) {
				map.setView( [ parseFloat( parts[ 0 ] ), parseFloat( parts[ 1 ] ) ], parseInt( data.zoom, 10 ) || 10 );
			}
		}
		if ( ! map._loaded || data.center ) {
			// no-op: handled above
		}
		if ( ! data.center ) {
			if ( markers.length === 1 ) {
				map.setView( markers[ 0 ], parseInt( data.zoom, 10 ) || 13 );
			} else {
				map.fitBounds( markers, { padding: [ 40, 40 ], maxZoom: 14 } );
			}
		}

		// Enable scroll zoom only once the user interacts with the map.
		map.on( 'click', function () {
			map.scrollWheelZoom.enable();
		} );

		setTimeout( function () {
			map.invalidateSize();
		}, 200 );
	}

	function boot() {
		if ( typeof L === 'undefined' ) {
			return;
		}
		document.querySelectorAll( '.nz-map[data-nz-map]' ).forEach( initMap );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )();
