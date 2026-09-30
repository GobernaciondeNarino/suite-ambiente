/**
 * Suite Ambiente Nariño — utilidades compartidas del front.
 *
 * Expone window.SAN con: configuración, petición a la API, formato numérico
 * en español, paleta según tema, tooltip accesible y descargas.
 */
( function () {
	'use strict';

	var CFG = window.SAN_CONFIG || {};
	var cacheGeo = {};

	var SAN = {
		cfg: CFG,

		/**
		 * GET a la API REST del plugin.
		 * @param {string} ruta   Ruta relativa (p. ej. "visualizaciones/clima_mapa").
		 * @param {Object} params Parámetros de consulta.
		 * @returns {Promise<Object>}
		 */
		api: function ( ruta, params ) {
			var url = new URL( CFG.rest + ruta, window.location.href );
			Object.keys( params || {} ).forEach( function ( k ) {
				if ( params[ k ] !== '' && params[ k ] != null ) {
					url.searchParams.set( k, params[ k ] );
				}
			} );
			var opc = { credentials: 'same-origin', headers: { Accept: 'application/json' } };
			if ( CFG.nonce ) {
				opc.headers[ 'X-WP-Nonce' ] = CFG.nonce;
			}
			return fetch( url.toString(), opc ).then( function ( r ) {
				return r.json().then( function ( j ) {
					if ( ! r.ok && ! ( j && j.datos ) ) {
						var e = new Error( ( j && ( j.error || j.message ) ) || 'HTTP ' + r.status );
						e.respuesta = j;
						throw e;
					}
					return j;
				} );
			} );
		},

		/**
		 * GeoJSON con caché en memoria (municipios, subregiones, departamento).
		 * @param {string} nombre Clave en CFG.geo.
		 * @returns {Promise<Object>}
		 */
		geo: function ( nombre ) {
			if ( ! cacheGeo[ nombre ] ) {
				cacheGeo[ nombre ] = fetch( CFG.geo[ nombre ] ).then( function ( r ) {
					return r.json();
				} ).then( SAN.reorientar );
			}
			return cacheGeo[ nombre ];
		},

		/**
		 * GeoJSON (RFC 7946: anillo exterior antihorario) → convención de
		 * d3-geo (exterior horario, huecos antihorarios). Sin esto, d3 interpreta
		 * cada polígono como "todo el globo menos el municipio".
		 * @param {Object} geo GeoJSON.
		 * @returns {Object}
		 */
		reorientar: function ( geo ) {
			var horario = function ( anillo ) {
				var s = 0;
				for ( var i = 0, n = anillo.length; i < n; i++ ) {
					var a = anillo[ i ];
					var b = anillo[ ( i + 1 ) % n ];
					s += ( b[ 0 ] - a[ 0 ] ) * ( b[ 1 ] + a[ 1 ] );
				}
				return s > 0;
			};
			var poligono = function ( anillos ) {
				anillos.forEach( function ( anillo, i ) {
					var debeSerHorario = i === 0;
					if ( horario( anillo ) !== debeSerHorario ) {
						anillo.reverse();
					}
				} );
			};
			var geometria = function ( g ) {
				if ( ! g ) {
					return;
				}
				if ( g.type === 'Polygon' ) {
					poligono( g.coordinates );
				} else if ( g.type === 'MultiPolygon' ) {
					g.coordinates.forEach( poligono );
				} else if ( g.type === 'GeometryCollection' ) {
					g.geometries.forEach( geometria );
				}
			};
			( geo.type === 'FeatureCollection' ? geo.features : [ geo ] ).forEach( function ( f ) {
				geometria( f.type === 'Feature' ? f.geometry : f );
			} );
			return geo;
		},

		/**
		 * Número con coma decimal.
		 * @param {number} v   Valor.
		 * @param {number} dec Decimales.
		 * @returns {string}
		 */
		num: function ( v, dec ) {
			if ( v === null || v === undefined || isNaN( v ) ) {
				return '—';
			}
			return Number( v ).toLocaleString( 'es-CO', {
				minimumFractionDigits: dec === undefined ? 0 : dec,
				maximumFractionDigits: dec === undefined ? 1 : dec,
			} );
		},

		/**
		 * Fecha corta en español.
		 * @param {Date|string} d    Fecha.
		 * @param {boolean}     hora Incluir hora.
		 * @returns {string}
		 */
		fecha: function ( d, hora ) {
			var f = d instanceof Date ? d : new Date( d );
			if ( isNaN( f ) ) {
				return String( d );
			}
			var o = { weekday: 'short', day: 'numeric', month: 'short' };
			if ( hora ) {
				o.hour = '2-digit';
				o.minute = '2-digit';
				o.hourCycle = 'h23';
			}
			return f.toLocaleString( 'es-CO', o );
		},

		/**
		 * ¿El elemento se muestra en tema oscuro?
		 * @param {Element} el Elemento.
		 * @returns {boolean}
		 */
		oscuro: function ( el ) {
			if ( el.closest( '.san-tema-claro' ) ) {
				return false;
			}
			if ( el.closest( '.san-tema-oscuro' ) || CFG.tema === 'oscuro' ) {
				return true;
			}
			return CFG.tema !== 'claro' && window.matchMedia && window.matchMedia( '(prefers-color-scheme: dark)' ).matches;
		},

		/**
		 * Paleta categórica validada según el tema del elemento.
		 * @param {Element} el Elemento.
		 * @returns {string[]}
		 */
		paleta: function ( el ) {
			return ( CFG.paleta || {} )[ SAN.oscuro( el ) ? 'oscuro' : 'claro' ] || [ '#10A13B' ];
		},

		/**
		 * Tokens de tinta y líneas leídos de las variables CSS.
		 * @param {Element} el Elemento.
		 * @returns {Object}
		 */
		tokens: function ( el ) {
			var cs = getComputedStyle( el );
			var v = function ( n, d ) {
				return ( cs.getPropertyValue( n ) || '' ).trim() || d;
			};
			return {
				tinta: v( '--san-tinta', '#1f1f1f' ),
				tinta2: v( '--san-tinta-2', '#4a4a4a' ),
				tinta3: v( '--san-tinta-3', '#6b6b6b' ),
				linea: v( '--san-linea', '#e1e3de' ),
				eje: v( '--san-eje', '#c3c6bf' ),
				superficie: v( '--san-superficie', '#ffffff' ),
				plano: v( '--san-plano', '#f6f7f5' ),
				fuente: v( '--san-fuente-texto', 'system-ui, sans-serif' ),
			};
		},

		/**
		 * Rampa secuencial de un solo tono (claro → oscuro).
		 * @param {string}  tono  verde | azul | naranja | rojo | violeta.
		 * @param {boolean} dark  Tema oscuro.
		 * @returns {string[]} [claro, oscuro]
		 */
		rampa: function ( tono, dark ) {
			// En oscuro el valor bajo se funde con la superficie y el alto se aclara.
			var claro = {
				verde: [ '#e3f4e8', '#0b5d22' ],
				azul: [ '#e3edf8', '#0d366b' ],
				naranja: [ '#fdeedd', '#8a3b00' ],
				rojo: [ '#fbe4e1', '#8c1c13' ],
				violeta: [ '#f1e6f0', '#5c2257' ],
			};
			var oscuro = {
				verde: [ '#183a22', '#7fe0a0' ],
				azul: [ '#15273f', '#8ab8ef' ],
				naranja: [ '#3a2512', '#f5b26b' ],
				rojo: [ '#3b1a17', '#f08a7e' ],
				violeta: [ '#2f1a2d', '#d9a0d2' ],
			};
			var tabla = dark ? oscuro : claro;
			return tabla[ tono ] || tabla.verde;
		},

		/* ---------- Tooltip compartido ---------- */
		tooltip: ( function () {
			var nodo = null;
			function asegurar() {
				if ( ! nodo ) {
					nodo = document.createElement( 'div' );
					nodo.className = 'san-tooltip';
					nodo.setAttribute( 'role', 'tooltip' );
					nodo.hidden = true;
					document.body.appendChild( nodo );
				}
				return nodo;
			}
			return {
				mostrar: function ( ev, titulo, filas ) {
					var n = asegurar();
					n.textContent = '';
					var t = document.createElement( 'strong' );
					t.textContent = titulo;
					n.appendChild( t );
					( filas || [] ).forEach( function ( f ) {
						var l = document.createElement( 'div' );
						l.textContent = f;
						n.appendChild( l );
					} );
					n.hidden = false;
					SAN.tooltip.mover( ev );
				},
				mover: function ( ev ) {
					if ( ! nodo || nodo.hidden ) {
						return;
					}
					var x = ev.clientX + 14;
					var y = ev.clientY + 14;
					var w = nodo.offsetWidth;
					var h = nodo.offsetHeight;
					if ( x + w > window.innerWidth - 8 ) {
						x = ev.clientX - w - 14;
					}
					if ( y + h > window.innerHeight - 8 ) {
						y = ev.clientY - h - 14;
					}
					nodo.style.left = x + 'px';
					nodo.style.top = y + 'px';
				},
				ocultar: function () {
					if ( nodo ) {
						nodo.hidden = true;
					}
				},
			};
		} )(),

		/* ---------- Descargas ---------- */
		descargar: function ( nombre, contenido, tipo ) {
			var blob = contenido instanceof Blob ? contenido : new Blob( [ contenido ], { type: tipo } );
			var a = document.createElement( 'a' );
			a.href = URL.createObjectURL( blob );
			a.download = nombre;
			document.body.appendChild( a );
			a.click();
			setTimeout( function () {
				URL.revokeObjectURL( a.href );
				a.remove();
			}, 500 );
		},

		/**
		 * Filas → CSV con protección contra inyección de fórmulas.
		 * @param {Object[]} filas Filas.
		 * @returns {string}
		 */
		csv: function ( filas ) {
			if ( ! filas || ! filas.length ) {
				return '';
			}
			var cols = [];
			filas.forEach( function ( f ) {
				Object.keys( f ).forEach( function ( k ) {
					if ( cols.indexOf( k ) < 0 && k.charAt( 0 ) !== '_' ) {
						cols.push( k );
					}
				} );
			} );
			var esc = function ( v ) {
				if ( v === null || v === undefined ) {
					return '';
				}
				if ( v instanceof Date ) {
					v = v.toISOString();
				}
				v = typeof v === 'object' ? JSON.stringify( v ) : String( v );
				if ( /^[=+\-@\t\r]/.test( v ) && isNaN( Number( v ) ) ) {
					v = "'" + v;
				}
				return /[",\n\r]/.test( v ) ? '"' + v.replace( /"/g, '""' ) + '"' : v;
			};
			return '﻿' + [ cols.join( ',' ) ].concat( filas.map( function ( f ) {
				return cols.map( function ( c ) {
					return esc( f[ c ] );
				} ).join( ',' );
			} ) ).join( '\r\n' );
		},

		/**
		 * Exporta el SVG o canvas del lienzo a PNG (2x).
		 * @param {Element} lienzo Contenedor.
		 * @param {string}  nombre Nombre de archivo.
		 */
		png: function ( lienzo, nombre ) {
			var canvas = lienzo.querySelector( 'canvas' );
			if ( canvas ) {
				canvas.toBlob( function ( b ) {
					SAN.descargar( nombre, b );
				} );
				return;
			}
			var svg = lienzo.querySelector( 'svg' );
			if ( ! svg ) {
				return;
			}
			var caja = svg.getBoundingClientRect();
			var clon = svg.cloneNode( true );
			clon.setAttribute( 'xmlns', 'http://www.w3.org/2000/svg' );
			clon.setAttribute( 'width', caja.width );
			clon.setAttribute( 'height', caja.height );
			var fondo = document.createElementNS( 'http://www.w3.org/2000/svg', 'rect' );
			fondo.setAttribute( 'width', '100%' );
			fondo.setAttribute( 'height', '100%' );
			fondo.setAttribute( 'fill', SAN.tokens( lienzo ).superficie );
			clon.insertBefore( fondo, clon.firstChild );
			var datos = new XMLSerializer().serializeToString( clon );
			var img = new Image();
			img.onload = function () {
				var c = document.createElement( 'canvas' );
				c.width = caja.width * 2;
				c.height = caja.height * 2;
				var ctx = c.getContext( '2d' );
				ctx.scale( 2, 2 );
				ctx.drawImage( img, 0, 0 );
				c.toBlob( function ( b ) {
					SAN.descargar( nombre, b );
				} );
			};
			img.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent( datos );
		},
	};

	window.SAN = SAN;
}() );
