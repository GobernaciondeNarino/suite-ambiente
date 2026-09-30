/**
 * Suite Ambiente Nariño — interacciones del panel de administración.
 *
 *  - Copiar shortcodes y URL con confirmación accesible.
 *  - Personalizar una tarjeta (tipo, municipio, alto) → shortcodes en vivo.
 *  - Vista previa del gráfico y de los análisis en la propia tarjeta.
 *  - Probar APIs, vaciar caché, purgar registros (REST con nonce).
 *  - Vista previa de recursos de datos abiertos.
 */
( function () {
	'use strict';

	var A = window.SAN_ADMIN || {};
	var T = A.textos || {};

	function api( metodo, ruta, cuerpo ) {
		return fetch( A.rest + ruta, {
			method: metodo,
			credentials: 'same-origin',
			headers: { 'X-WP-Nonce': A.nonce, 'Content-Type': 'application/json', Accept: 'application/json' },
			body: cuerpo ? JSON.stringify( cuerpo ) : undefined,
		} ).then( function ( r ) {
			return r.json().then( function ( j ) {
				if ( ! r.ok && ! ( j && j.datos ) ) {
					throw new Error( ( j && ( j.message || j.error ) ) || 'HTTP ' + r.status );
				}
				return j;
			} );
		} );
	}

	function el( tag, clase, texto ) {
		var n = document.createElement( tag );
		if ( clase ) {
			n.className = clase;
		}
		if ( texto !== undefined ) {
			n.textContent = texto;
		}
		return n;
	}

	/* ---------- Copiar ---------- */
	document.addEventListener( 'click', function ( ev ) {
		var b = ev.target.closest( '.san-copiar' );
		if ( ! b ) {
			return;
		}
		var input = document.getElementById( b.getAttribute( 'data-copiar' ) );
		if ( ! input ) {
			return;
		}
		var hecho = function () {
			b.textContent = T.copiado || 'Copiado';
			b.classList.add( 'san-copiado' );
			setTimeout( function () {
				b.textContent = T.copiar || 'Copiar';
				b.classList.remove( 'san-copiado' );
			}, 1600 );
		};
		if ( navigator.clipboard && window.isSecureContext ) {
			navigator.clipboard.writeText( input.value ).then( hecho );
		} else {
			input.select();
			document.execCommand( 'copy' );
			hecho();
		}
	} );

	/* ---------- Personalizar tarjeta ---------- */
	function atributosTarjeta( t ) {
		var a = {};
		t.querySelectorAll( '.san-personalizar [data-attr]' ).forEach( function ( c ) {
			a[ c.getAttribute( 'data-attr' ) ] = c.value;
		} );
		return a;
	}

	function actualizarShortcodes( t ) {
		var id = t.getAttribute( 'data-viz' );
		var a = atributosTarjeta( t );
		var extra = '';
		if ( a.municipio ) {
			extra += ' municipio="' + a.municipio + '"';
		}
		if ( a.tipo && a.tipo !== t.getAttribute( 'data-tipo' ) ) {
			extra += ' tipo="' + a.tipo + '"';
		}
		if ( a.alto && a.alto !== '420' ) {
			extra += ' alto="' + a.alto + '"';
		}
		var base = ' id="' + id + '"';
		var mapa = {
			grafico: '[san_grafico' + base + extra + ']',
			descripcion: '[san_descripcion' + base + ']',
			cualitativo: '[san_analisis_cualitativo' + base + ( a.municipio ? ' municipio="' + a.municipio + '"' : '' ) + ']',
			cuantitativo: '[san_analisis_cuantitativo' + base + ( a.municipio ? ' municipio="' + a.municipio + '"' : '' ) + ']',
			card: '[san_card' + base + extra + ']',
		};
		t.querySelectorAll( '.san-sc__codigo[data-rol]' ).forEach( function ( i ) {
			var rol = i.getAttribute( 'data-rol' );
			if ( mapa[ rol ] ) {
				i.value = mapa[ rol ];
			}
		} );
	}

	document.addEventListener( 'change', function ( ev ) {
		var t = ev.target.closest( '.san-tarjeta[data-viz]' );
		if ( t && ev.target.closest( '.san-personalizar' ) ) {
			actualizarShortcodes( t );
			var previa = t.querySelector( '.san-tarjeta__previa' );
			if ( previa && ! previa.hidden ) {
				mostrarPrevia( t, true );
			}
		}
	} );

	/* ---------- Vista previa de visualización ---------- */
	function htmlAnalisis( resp ) {
		var cont = el( 'div', 'san-previa-analisis' );
		if ( ! resp || ! resp.ok ) {
			cont.appendChild( el( 'p', 'san-error', ( resp && resp.error ) || T.error ) );
			return cont;
		}
		var an = resp.analisis || {};
		var c1 = el( 'section' );
		c1.appendChild( el( 'h4', '', 'Análisis cualitativo' ) );
		if ( an.titular ) {
			c1.appendChild( el( 'p', 'san-analisis__titular', an.titular ) );
		}
		( an.cualitativo || [] ).forEach( function ( p ) {
			c1.appendChild( el( 'p', '', p ) );
		} );
		if ( an.recomendaciones && an.recomendaciones.length ) {
			var ul = el( 'ul' );
			an.recomendaciones.forEach( function ( r ) {
				ul.appendChild( el( 'li', '', r ) );
			} );
			c1.appendChild( ul );
		}
		var c2 = el( 'section' );
		c2.appendChild( el( 'h4', '', 'Análisis cuantitativo' ) );
		var dl = el( 'dl', 'san-cifras' );
		( an.cuantitativo || [] ).forEach( function ( c ) {
			var d = el( 'div', 'san-cifra' );
			d.appendChild( el( 'dt', '', c.etiqueta ) );
			d.appendChild( el( 'dd', '', c.valor ) );
			if ( c.detalle ) {
				d.appendChild( el( 'dd', 'san-cifra__detalle', c.detalle ) );
			}
			dl.appendChild( d );
		} );
		c2.appendChild( dl );
		if ( an.resumen_cuantitativo ) {
			c2.appendChild( el( 'p', 'san-analisis__resumen', an.resumen_cuantitativo ) );
		}
		cont.appendChild( c1 );
		cont.appendChild( c2 );
		return cont;
	}

	function mostrarPrevia( t, recargar ) {
		var previa = t.querySelector( '.san-tarjeta__previa' );
		var boton = t.querySelector( '.san-previa' );
		var a = atributosTarjeta( t );
		var id = t.getAttribute( 'data-viz' );
		var params = a.municipio ? { municipio: a.municipio } : {};

		previa.hidden = false;
		boton.setAttribute( 'aria-expanded', 'true' );
		previa.textContent = '';

		var fig = el( 'figure', 'san-grafico' );
		fig.id = 'san-previa-' + id + '-' + Date.now();
		fig.setAttribute( 'data-viz', id );
		fig.setAttribute( 'data-tipo', a.tipo || t.getAttribute( 'data-tipo' ) );
		fig.setAttribute( 'data-tipos', t.getAttribute( 'data-tipos' ) );
		fig.setAttribute( 'data-params', JSON.stringify( params ) );
		fig.setAttribute( 'data-selector', '0' );
		fig.style.setProperty( '--san-alto', ( parseInt( a.alto, 10 ) || 420 ) + 'px' );
		fig.appendChild( el( 'div', 'san-grafico__barra' ) );
		fig.appendChild( el( 'div', 'san-grafico__lienzo' ) );
		fig.appendChild( el( 'p', 'san-grafico__pie' ) );
		previa.appendChild( fig );

		var g = new window.SAN.graficos.Grafico( fig );
		g.cargar().then( function () {
			if ( g.resp ) {
				previa.appendChild( htmlAnalisis( g.resp ) );
			}
		} );
	}

	document.addEventListener( 'click', function ( ev ) {
		var b = ev.target.closest( '.san-previa' );
		if ( ! b ) {
			return;
		}
		var t = b.closest( '.san-tarjeta' );
		var previa = t.querySelector( '.san-tarjeta__previa' );
		if ( ! previa.hidden ) {
			previa.hidden = true;
			previa.textContent = '';
			b.setAttribute( 'aria-expanded', 'false' );
			return;
		}
		mostrarPrevia( t );
	} );

	/* ---------- Vista previa de datos abiertos ---------- */
	document.addEventListener( 'click', function ( ev ) {
		var b = ev.target.closest( '.san-previa-datos' );
		if ( ! b ) {
			return;
		}
		var t = b.closest( '[data-recurso]' );
		var caja = t.querySelector( '.san-previa-tabla' );
		if ( ! caja.hidden ) {
			caja.hidden = true;
			b.setAttribute( 'aria-expanded', 'false' );
			return;
		}
		caja.hidden = false;
		b.setAttribute( 'aria-expanded', 'true' );
		caja.textContent = T.probando || '…';
		api( 'GET', 'datos/' + t.getAttribute( 'data-recurso' ) ).then( function ( r ) {
			caja.textContent = '';
			var filas = ( r.filas || [] ).slice( 0, 10 );
			if ( ! filas.length ) {
				caja.textContent = r.error || '—';
				return;
			}
			var cols = Object.keys( filas[ 0 ] );
			var tabla = el( 'table', 'widefat striped' );
			var thead = el( 'thead' );
			var tr = el( 'tr' );
			cols.forEach( function ( c ) {
				tr.appendChild( el( 'th', '', c ) );
			} );
			thead.appendChild( tr );
			tabla.appendChild( thead );
			var tb = el( 'tbody' );
			filas.forEach( function ( f ) {
				var fila = el( 'tr' );
				cols.forEach( function ( c ) {
					var v = f[ c ];
					fila.appendChild( el( 'td', '', v === null || v === undefined ? '' : ( typeof v === 'object' ? JSON.stringify( v ) : String( v ) ) ) );
				} );
				tb.appendChild( fila );
			} );
			tabla.appendChild( tb );
			var envoltura = el( 'div', 'san-tabla-desplazable' );
			envoltura.appendChild( tabla );
			caja.appendChild( envoltura );
			caja.appendChild( el( 'p', 'description', ( r.filas || [] ).length + ' filas · ' + ( r.actualizado ? 'consultado ' + r.actualizado + ' UTC' : '' ) ) );
		} ).catch( function ( e ) {
			caja.textContent = e.message;
		} );
	} );

	/* ---------- Probar APIs ---------- */
	function pintarResultado( caja, r ) {
		caja.hidden = false;
		caja.textContent = '';
		caja.className = 'san-resultado-prueba ' + ( r.ok ? 'es-ok' : 'es-falla' );
		var cab = el( 'p', 'san-resultado-prueba__cab' );
		cab.appendChild( el( 'strong', '', ( r.ok ? '● ' + ( T.ok || 'Funciona' ) : '■ ' + ( T.falla || 'Falla' ) ) ) );
		cab.appendChild( document.createTextNode( ' · HTTP ' + ( r.codigo || '—' ) + ' · ' + ( r.ms || 0 ) + ' ms' + ( r.bytes ? ' · ' + Math.round( r.bytes / 1024 ) + ' KB' : '' ) ) );
		caja.appendChild( cab );
		caja.appendChild( el( 'p', '', r.mensaje || '' ) );
		if ( r.frescura ) {
			caja.appendChild( el( 'p', 'san-muted', 'Dato más reciente: ' + r.frescura ) );
		}
		if ( r.muestra && Object.keys( r.muestra ).length ) {
			var det = el( 'details' );
			det.appendChild( el( 'summary', '', 'Muestra de la respuesta' ) );
			det.appendChild( el( 'pre', '', JSON.stringify( r.muestra, null, 2 ) ) );
			caja.appendChild( det );
		}
	}

	document.addEventListener( 'click', function ( ev ) {
		var b = ev.target.closest( '.san-probar' );
		if ( ! b ) {
			return;
		}
		var card = b.closest( '.san-fuente' );
		var caja = card.querySelector( '.san-resultado-prueba' );
		b.disabled = true;
		b.textContent = T.probando || 'Probando…';
		api( 'POST', 'admin/probar/' + b.getAttribute( 'data-fuente' ) ).then( function ( r ) {
			pintarResultado( caja, r );
		} ).catch( function ( e ) {
			pintarResultado( caja, { ok: false, mensaje: e.message } );
		} ).then( function () {
			b.disabled = false;
			b.textContent = 'Probar ahora';
		} );
	} );

	var todas = document.getElementById( 'san-probar-todas' );
	if ( todas ) {
		todas.addEventListener( 'click', function () {
			var estado = document.querySelector( '.san-estado-accion' );
			todas.disabled = true;
			estado.textContent = T.probando || 'Probando…';
			api( 'POST', 'admin/probar' ).then( function ( r ) {
				var ok = 0;
				var n = 0;
				Object.keys( r ).forEach( function ( id ) {
					n++;
					if ( r[ id ].ok ) {
						ok++;
					}
					var card = document.getElementById( 'fuente-' + id );
					if ( card ) {
						pintarResultado( card.querySelector( '.san-resultado-prueba' ), r[ id ] );
					}
				} );
				estado.textContent = ok + ' / ' + n + ' fuentes funcionando.';
			} ).catch( function ( e ) {
				estado.textContent = e.message;
			} ).then( function () {
				todas.disabled = false;
			} );
		} );
	}

	function vaciar( fuente, estado ) {
		return api( 'POST', 'admin/cache/vaciar', fuente ? { fuente: fuente } : {} ).then( function ( r ) {
			if ( estado ) {
				estado.textContent = r.borradas + ' entradas de caché eliminadas.';
			}
		} );
	}

	var vc = document.getElementById( 'san-vaciar-cache' );
	if ( vc ) {
		vc.addEventListener( 'click', function () {
			if ( window.confirm( T.confirmar ) ) {
				vaciar( '', document.querySelector( '.san-estado-accion' ) );
			}
		} );
	}
	document.addEventListener( 'click', function ( ev ) {
		var b = ev.target.closest( '.san-vaciar-fuente' );
		if ( b ) {
			var caja = b.closest( '.san-fuente' ).querySelector( '.san-resultado-prueba' );
			vaciar( b.getAttribute( 'data-fuente' ), null ).then( function () {
				caja.hidden = false;
				caja.className = 'san-resultado-prueba';
				caja.textContent = 'Caché de la fuente vaciada.';
			} );
		}
	} );

	/* ---------- Registros ---------- */
	function purgar( todos ) {
		if ( ! window.confirm( T.confirmar ) ) {
			return;
		}
		fetch( A.rest + 'admin/logs' + ( todos ? '?todos=1' : '' ), {
			method: 'DELETE',
			credentials: 'same-origin',
			headers: { 'X-WP-Nonce': A.nonce },
		} ).then( function () {
			window.location.reload();
		} );
	}
	var pl = document.getElementById( 'san-purgar-logs' );
	if ( pl ) {
		pl.addEventListener( 'click', function () {
			purgar( false );
		} );
	}
	var vl = document.getElementById( 'san-vaciar-logs' );
	if ( vl ) {
		vl.addEventListener( 'click', function () {
			purgar( true );
		} );
	}
}() );
