/**
 * Suite Ambiente Nariño — gráficos propios en D3.js v7.
 *
 *  mapa     Coroplético de los 64 municipios con contorno de subregiones.
 *  puntos   Mapa de eventos puntuales (sismos, focos de calor, registros).
 *  calor    Mapa de calor (p. ej. hora × día).
 *  medidor  Medidor semicircular por bandas (ICA, índice UV).
 *  rosa     Rosa de vientos (dirección × intensidad).
 *
 * Todos comparten tooltip accesible, tokens de color del tema y se
 * redibujan al cambiar el tamaño del contenedor.
 */
( function () {
	'use strict';

	var SAN = window.SAN;
	var d3 = window.d3;

	/** Valor numérico o null. */
	function numero( v ) {
		var n = v === null || v === undefined || v === '' ? NaN : Number( v );
		return isFinite( n ) ? n : null;
	}

	/** Filas del tooltip según config.tooltip = [[etiqueta, campo, unidad, dec]]. */
	function filasTooltip( d, cfg ) {
		return ( cfg.tooltip || [] ).map( function ( t ) {
			var v = d[ t[ 1 ] ];
			var n = numero( v );
			return t[ 0 ] + ': ' + ( n === null ? ( v === undefined || v === null || v === '' ? '—' : v ) : SAN.num( n, t[ 3 ] === undefined ? 1 : t[ 3 ] ) + ( t[ 2 ] ? ' ' + t[ 2 ] : '' ) );
		} );
	}

	/** SVG responsivo en el lienzo. */
	function lienzoSvg( lienzo, etiqueta, reserva ) {
		lienzo.textContent = '';
		var w = Math.max( 280, lienzo.clientWidth );
		var h = Math.max( 180, lienzo.clientHeight - ( reserva || 0 ) );
		var svg = d3.select( lienzo ).append( 'svg' )
			.attr( 'viewBox', '0 0 ' + w + ' ' + h )
			.attr( 'width', w )
			.attr( 'height', h )
			.attr( 'role', 'img' )
			.attr( 'aria-label', etiqueta || '' );
		return { svg: svg, w: w, h: h };
	}

	/** Leyenda HTML (muestras de color + texto: nunca solo color). */
	function leyenda( lienzo, items ) {
		var l = document.createElement( 'div' );
		l.className = 'san-leyenda';
		items.forEach( function ( it ) {
			var s = document.createElement( 'span' );
			s.className = 'san-leyenda__item';
			var m = document.createElement( 'span' );
			m.className = 'san-leyenda__muestra';
			m.style.background = it.color;
			s.appendChild( m );
			s.appendChild( document.createTextNode( it.texto ) );
			l.appendChild( s );
		} );
		lienzo.appendChild( l );
		return l;
	}

	/** Leyenda de gradiente para escalas secuenciales. */
	function leyendaGradiente( svg, x, y, ancho, escala, dominio, unidad, tk ) {
		var id = 'san-grad-' + Math.random().toString( 36 ).slice( 2 );
		var def = svg.append( 'defs' ).append( 'linearGradient' ).attr( 'id', id );
		d3.range( 0, 1.01, 0.1 ).forEach( function ( t ) {
			def.append( 'stop' ).attr( 'offset', t * 100 + '%' ).attr( 'stop-color', escala( dominio[ 0 ] + t * ( dominio[ 1 ] - dominio[ 0 ] ) ) );
		} );
		var g = svg.append( 'g' ).attr( 'transform', 'translate(' + x + ',' + y + ')' );
		g.append( 'rect' ).attr( 'width', ancho ).attr( 'height', 10 ).attr( 'rx', 3 ).attr( 'fill', 'url(#' + id + ')' );
		[ 0, 0.5, 1 ].forEach( function ( t ) {
			g.append( 'text' )
				.attr( 'x', t * ancho ).attr( 'y', 26 )
				.attr( 'text-anchor', t === 0 ? 'start' : ( t === 1 ? 'end' : 'middle' ) )
				.attr( 'fill', tk.tinta2 ).attr( 'font-size', 12 )
				.text( SAN.num( dominio[ 0 ] + t * ( dominio[ 1 ] - dominio[ 0 ] ), 1 ) + ( unidad ? ' ' + unidad : '' ) );
		} );
	}

	/** Proyección ajustada a una colección. */
	function proyectar( coleccion, w, h, margen ) {
		return d3.geoMercator().fitExtent( [ [ margen, margen ], [ w - margen, h - margen ] ], coleccion );
	}

	var motores = {};

	/* ------------------------------------------------------------------
	 * Mapa coroplético
	 * ---------------------------------------------------------------- */
	motores.mapa = function ( lienzo, resp ) {
		var cfg = resp.config || {};
		var tk = SAN.tokens( lienzo );
		var dark = SAN.oscuro( lienzo );
		return Promise.all( [ SAN.geo( 'municipios' ), SAN.geo( 'subregiones' ) ] ).then( function ( geo ) {
			var mun = geo[ 0 ];
			var sub = geo[ 1 ];
			var porClave = {};
			( resp.datos || [] ).forEach( function ( d ) {
				porClave[ String( d[ cfg.clave || 'divipola' ] ) ] = d;
			} );
			var valores = ( resp.datos || [] ).map( function ( d ) {
				return numero( d[ cfg.y ] );
			} ).filter( function ( v ) {
				return v !== null;
			} );
			var dominio = cfg.dominio || d3.extent( valores );
			if ( dominio[ 0 ] === dominio[ 1 ] ) {
				dominio = [ dominio[ 0 ] - 1, dominio[ 1 ] + 1 ];
			}
			var rampa = SAN.rampa( cfg.tono || 'verde', dark );
			var escala = d3.scaleSequential( d3.interpolateRgb( rampa[ 0 ], rampa[ 1 ] ) ).domain( dominio );

			var base = lienzoSvg( lienzo, resp.titulo, cfg.color_campo ? 44 : 0 );
			var altoLeyenda = cfg.color_campo ? 0 : 40;
			var proy = proyectar( mun, base.w, base.h - altoLeyenda, 8 );
			var path = d3.geoPath( proy );

			base.svg.append( 'g' ).selectAll( 'path' ).data( mun.features ).join( 'path' )
				.attr( 'd', path )
				.attr( 'fill', function ( f ) {
					var d = porClave[ f.properties.MPIO_CDPMP ];
					if ( ! d ) {
						return tk.plano;
					}
					if ( cfg.color_campo && d[ cfg.color_campo ] ) {
						return d[ cfg.color_campo ];
					}
					var v = numero( d[ cfg.y ] );
					return v === null ? tk.plano : escala( v );
				} )
				.attr( 'stroke', tk.superficie )
				.attr( 'stroke-width', 0.8 )
				.attr( 'tabindex', 0 )
				.attr( 'role', 'button' )
				.attr( 'aria-label', function ( f ) {
					var d = porClave[ f.properties.MPIO_CDPMP ] || {};
					var v = numero( d[ cfg.y ] );
					return ( d[ cfg.etiqueta || 'municipio' ] || f.properties.MPIO_CNMBR ) + ': ' + ( v === null ? 'sin dato' : SAN.num( v, cfg.decimales ) + ' ' + ( cfg.unidad || '' ) );
				} )
				.style( 'cursor', 'pointer' )
				.on( 'mousemove focus', function ( ev, f ) {
					var d = porClave[ f.properties.MPIO_CDPMP ] || { municipio: f.properties.MPIO_CNMBR };
					d3.select( this ).attr( 'stroke', tk.tinta ).attr( 'stroke-width', 2 ).raise();
					var p = ev.type === 'focus' ? this.getBoundingClientRect() : null;
					SAN.tooltip.mostrar( p ? { clientX: p.x + p.width / 2, clientY: p.y } : ev, d[ cfg.etiqueta || 'municipio' ] || f.properties.MPIO_CNMBR, filasTooltip( d, cfg ) );
				} )
				.on( 'mouseleave blur', function () {
					d3.select( this ).attr( 'stroke', tk.superficie ).attr( 'stroke-width', 0.8 );
					SAN.tooltip.ocultar();
				} );

			base.svg.append( 'g' ).attr( 'pointer-events', 'none' ).selectAll( 'path' ).data( sub.features ).join( 'path' )
				.attr( 'd', path ).attr( 'fill', 'none' ).attr( 'stroke', tk.tinta2 ).attr( 'stroke-width', 1.4 ).attr( 'stroke-opacity', 0.7 );

			if ( cfg.color_campo ) {
				var vistos = {};
				var items = [];
				( resp.datos || [] ).forEach( function ( d ) {
					var k = d[ cfg.categoria_campo || 'categoria' ];
					if ( k && ! vistos[ k ] ) {
						vistos[ k ] = true;
						items.push( { color: d[ cfg.color_campo ], texto: k, orden: numero( d[ cfg.orden_campo ] ) || 0 } );
					}
				} );
				items.sort( function ( a, b ) {
					return a.orden - b.orden;
				} );
				leyenda( lienzo, items );
			} else if ( valores.length ) {
				leyendaGradiente( base.svg, 12, base.h - 34, Math.min( 260, base.w - 24 ), escala, dominio, cfg.unidad, tk );
			}
		} );
	};

	/* ------------------------------------------------------------------
	 * Mapa de puntos
	 * ---------------------------------------------------------------- */
	motores.puntos = function ( lienzo, resp ) {
		var cfg = resp.config || {};
		var tk = SAN.tokens( lienzo );
		var pal = SAN.paleta( lienzo );
		return Promise.all( [ SAN.geo( 'municipios' ), SAN.geo( 'departamento' ) ] ).then( function ( geo ) {
			var mun = geo[ 0 ];
			var datos = ( resp.datos || [] ).filter( function ( d ) {
				return numero( d[ cfg.lat || 'lat' ] ) !== null && numero( d[ cfg.lon || 'lon' ] ) !== null;
			} );
			var contorno = geo[ 1 ].type === 'FeatureCollection' ? geo[ 1 ].features : [ geo[ 1 ] ];
			var extension = {
				type: 'FeatureCollection',
				features: mun.features.concat( datos.length ? [ {
					type: 'Feature',
					geometry: { type: 'MultiPoint', coordinates: datos.map( function ( d ) {
						return [ +d[ cfg.lon || 'lon' ], +d[ cfg.lat || 'lat' ] ];
					} ) },
				} ] : [] ),
			};
			var base = lienzoSvg( lienzo, resp.titulo, cfg.leyenda ? 44 : 0 );
			var proy = proyectar( cfg.ajustar_a_datos ? extension : mun, base.w, base.h, 12 );
			var path = d3.geoPath( proy );

			// Recorte para que los municipios no invadan la leyenda al ajustar a datos lejanos.
			base.svg.append( 'g' ).selectAll( 'path' ).data( mun.features ).join( 'path' )
				.attr( 'd', path ).attr( 'fill', tk.plano ).attr( 'stroke', tk.eje ).attr( 'stroke-width', 0.6 );
			base.svg.append( 'g' ).attr( 'pointer-events', 'none' ).selectAll( 'path' ).data( contorno ).join( 'path' )
				.attr( 'd', path ).attr( 'fill', 'none' ).attr( 'stroke', tk.tinta2 ).attr( 'stroke-width', 1.2 );

			// Dominio fijo opcional (p. ej. niveles de alerta): el tamaño no
			// depende de qué valores aparezcan en los datos del día.
			var tam = cfg.tamano ? d3.scaleSqrt().domain( cfg.tamano_dominio || d3.extent( datos, function ( d ) {
				return numero( d[ cfg.tamano ] );
			} ) ).range( [ 4, 18 ] ) : function () {
				return 6;
			};
			datos.sort( function ( a, b ) {
				return ( numero( b[ cfg.tamano ] ) || 0 ) - ( numero( a[ cfg.tamano ] ) || 0 );
			} );
			base.svg.append( 'g' ).selectAll( 'circle' ).data( datos ).join( 'circle' )
				.attr( 'cx', function ( d ) {
					return proy( [ +d[ cfg.lon || 'lon' ], +d[ cfg.lat || 'lat' ] ] )[ 0 ];
				} )
				.attr( 'cy', function ( d ) {
					return proy( [ +d[ cfg.lon || 'lon' ], +d[ cfg.lat || 'lat' ] ] )[ 1 ];
				} )
				.attr( 'r', function ( d ) {
					var v = numero( d[ cfg.tamano ] );
					return v === null ? 4 : tam( v );
				} )
				.attr( 'fill', function ( d ) {
					return cfg.color_campo && d[ cfg.color_campo ] ? d[ cfg.color_campo ] : pal[ 5 ] || '#D14124';
				} )
				.attr( 'fill-opacity', 0.75 )
				.attr( 'stroke', tk.superficie )
				.attr( 'stroke-width', 2 )
				.attr( 'tabindex', 0 )
				.attr( 'aria-label', function ( d ) {
					return ( d[ cfg.etiqueta ] || '' ) + '. ' + filasTooltip( d, cfg ).join( '. ' );
				} )
				.on( 'mousemove focus', function ( ev, d ) {
					d3.select( this ).attr( 'stroke', tk.tinta );
					var p = ev.type === 'focus' ? this.getBoundingClientRect() : null;
					SAN.tooltip.mostrar( p ? { clientX: p.x, clientY: p.y } : ev, d[ cfg.etiqueta ] || '', filasTooltip( d, cfg ) );
				} )
				.on( 'mouseleave blur', function () {
					d3.select( this ).attr( 'stroke', tk.superficie );
					SAN.tooltip.ocultar();
				} );

			if ( cfg.leyenda ) {
				leyenda( lienzo, cfg.leyenda );
			}
		} );
	};

	/* ------------------------------------------------------------------
	 * Mapa de calor
	 * ---------------------------------------------------------------- */
	motores.calor = function ( lienzo, resp ) {
		var cfg = resp.config || {};
		var tk = SAN.tokens( lienzo );
		var datos = resp.datos || [];
		var xs = [];
		var ys = [];
		datos.forEach( function ( d ) {
			if ( xs.indexOf( d[ cfg.x ] ) < 0 ) {
				xs.push( d[ cfg.x ] );
			}
			if ( ys.indexOf( d[ cfg.y ] ) < 0 ) {
				ys.push( d[ cfg.y ] );
			}
		} );
		// Horas "HH:MM" en orden natural (el primer día del pronóstico empieza tarde).
		if ( xs.every( function ( v ) {
			return /^\d{2}:\d{2}$/.test( String( v ) );
		} ) ) {
			xs.sort();
		}
		var base = lienzoSvg( lienzo, resp.titulo );
		var largo = d3.max( ys, function ( v ) {
			return String( v ).length;
		} ) || 8;
		var m = { t: 8, r: 8, b: 64, l: Math.min( 190, Math.max( 70, largo * 7 + 12 ) ) };
		var x = d3.scaleBand().domain( xs ).range( [ m.l, base.w - m.r ] ).padding( 0.06 );
		var y = d3.scaleBand().domain( ys ).range( [ m.t, base.h - m.b ] ).padding( 0.06 );
		var vals = datos.map( function ( d ) {
			return numero( d[ cfg.valor ] );
		} ).filter( function ( v ) {
			return v !== null;
		} );
		var dominio = d3.extent( vals );
		var rampa = SAN.rampa( cfg.tono || 'naranja', SAN.oscuro( lienzo ) );
		var escala = d3.scaleSequential( d3.interpolateRgb( rampa[ 0 ], rampa[ 1 ] ) ).domain( dominio[ 0 ] === dominio[ 1 ] ? [ dominio[ 0 ] - 1, dominio[ 1 ] + 1 ] : dominio );

		base.svg.append( 'g' ).selectAll( 'rect' ).data( datos ).join( 'rect' )
			.attr( 'x', function ( d ) {
				return x( d[ cfg.x ] );
			} )
			.attr( 'y', function ( d ) {
				return y( d[ cfg.y ] );
			} )
			.attr( 'width', x.bandwidth() )
			.attr( 'height', y.bandwidth() )
			.attr( 'rx', 2 )
			.attr( 'fill', function ( d ) {
				var v = numero( d[ cfg.valor ] );
				return v === null ? tk.plano : escala( v );
			} )
			.on( 'mousemove', function ( ev, d ) {
				SAN.tooltip.mostrar( ev, d[ cfg.y ] + ' · ' + d[ cfg.x ], filasTooltip( d, cfg ) );
			} )
			.on( 'mouseleave', SAN.tooltip.ocultar );

		var paso = Math.ceil( xs.length / Math.max( 1, Math.floor( ( base.w - m.l ) / 36 ) ) );
		base.svg.append( 'g' ).attr( 'transform', 'translate(0,' + ( base.h - m.b + 4 ) + ')' )
			.call( d3.axisBottom( x ).tickValues( xs.filter( function ( v, i ) {
				return i % paso === 0;
			} ) ).tickSize( 0 ) )
			.call( function ( g ) {
				g.select( '.domain' ).remove();
				g.selectAll( 'text' ).attr( 'fill', tk.tinta3 ).attr( 'font-size', 11 );
			} );
		base.svg.append( 'g' ).attr( 'transform', 'translate(' + ( m.l - 4 ) + ',0)' )
			.call( d3.axisLeft( y ).tickSize( 0 ) )
			.call( function ( g ) {
				g.select( '.domain' ).remove();
				g.selectAll( 'text' ).attr( 'fill', tk.tinta2 ).attr( 'font-size', 12 );
			} );
		if ( vals.length ) {
			leyendaGradiente( base.svg, m.l, base.h - 30, Math.min( 260, base.w - m.l - 12 ), escala, escala.domain(), cfg.unidad, tk );
		}
		return Promise.resolve();
	};

	/* ------------------------------------------------------------------
	 * Medidor semicircular por bandas
	 * ---------------------------------------------------------------- */
	motores.medidor = function ( lienzo, resp ) {
		var cfg = resp.config || {};
		var tk = SAN.tokens( lienzo );
		var fila = ( resp.datos || [] )[ 0 ] || {};
		var valor = numero( fila[ cfg.valor ] );
		var max = cfg.max || 500;
		var bandas = cfg.bandas || [];
		var base = lienzoSvg( lienzo, resp.titulo );
		var r = Math.max( 60, Math.min( base.w / 2 - 16, base.h - 96 ) );
		var cx = base.w / 2;
		var cy = r + 16;
		var ang = d3.scaleLinear().domain( [ 0, max ] ).range( [ -Math.PI / 2, Math.PI / 2 ] ).clamp( true );
		var arco = d3.arc().innerRadius( r * 0.72 ).outerRadius( r );
		var g = base.svg.append( 'g' ).attr( 'transform', 'translate(' + cx + ',' + cy + ')' );

		g.selectAll( 'path' ).data( bandas ).join( 'path' )
			.attr( 'd', function ( b ) {
				return arco( { startAngle: ang( b.min ), endAngle: ang( Math.min( b.max, max ) ) } );
			} )
			.attr( 'fill', function ( b ) {
				return b.color;
			} )
			.attr( 'stroke', tk.superficie )
			.attr( 'stroke-width', 2 )
			.on( 'mousemove', function ( ev, b ) {
				SAN.tooltip.mostrar( ev, b.nombre, [ SAN.num( b.min, 0 ) + ' – ' + SAN.num( b.max, 0 ) ] );
			} )
			.on( 'mouseleave', SAN.tooltip.ocultar );

		if ( valor !== null ) {
			var a = ang( valor ) - Math.PI / 2;
			g.append( 'line' ).attr( 'x1', 0 ).attr( 'y1', 0 )
				.attr( 'x2', Math.cos( a ) * r * 0.9 ).attr( 'y2', Math.sin( a ) * r * 0.9 )
				.attr( 'stroke', tk.tinta ).attr( 'stroke-width', 3 ).attr( 'stroke-linecap', 'round' );
			g.append( 'circle' ).attr( 'r', 6 ).attr( 'fill', tk.tinta );
		}
		var tam = Math.min( 44, Math.max( 22, r * 0.22 ) );
		g.append( 'text' ).attr( 'y', 14 + tam ).attr( 'text-anchor', 'middle' )
			.attr( 'fill', tk.tinta ).attr( 'font-size', tam ).attr( 'font-weight', 700 )
			.text( valor === null ? '—' : SAN.num( valor, cfg.decimales || 0 ) );
		g.append( 'text' ).attr( 'y', 14 + tam + 22 ).attr( 'text-anchor', 'middle' )
			.attr( 'fill', tk.tinta2 ).attr( 'font-size', 15 ).attr( 'font-weight', 600 )
			.text( fila[ cfg.etiqueta ] || '' );
		return Promise.resolve();
	};

	/* ------------------------------------------------------------------
	 * Rosa de vientos
	 * ---------------------------------------------------------------- */
	motores.rosa = function ( lienzo, resp ) {
		var cfg = resp.config || {};
		var tk = SAN.tokens( lienzo );
		var pal = SAN.paleta( lienzo );
		var datos = resp.datos || [];
		var sectores = cfg.sectores || [ 'N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE', 'S', 'SSO', 'SO', 'OSO', 'O', 'ONO', 'NO', 'NNO' ];
		var grupos = [];
		datos.forEach( function ( d ) {
			if ( grupos.indexOf( d[ cfg.grupo ] ) < 0 ) {
				grupos.push( d[ cfg.grupo ] );
			}
		} );
		var tabla = sectores.map( function ( s ) {
			var fila = { sector: s };
			grupos.forEach( function ( gr ) {
				fila[ gr ] = 0;
			} );
			datos.forEach( function ( d ) {
				if ( d[ cfg.direccion ] === s ) {
					fila[ d[ cfg.grupo ] ] = numero( d[ cfg.valor ] ) || 0;
				}
			} );
			return fila;
		} );
		var apilado = d3.stack().keys( grupos )( tabla );
		var base = lienzoSvg( lienzo, resp.titulo, 44 );
		var alto = base.h;
		var rExt = Math.min( base.w, alto ) / 2 - 24;
		var maxV = d3.max( apilado[ apilado.length - 1 ] || [ [ 0, 1 ] ], function ( d ) {
			return d[ 1 ];
		} ) || 1;
		var y = d3.scaleRadial().domain( [ 0, maxV ] ).range( [ 18, rExt ] );
		var x = d3.scaleBand().domain( sectores ).range( [ -Math.PI / 16, 2 * Math.PI - Math.PI / 16 ] ).padding( 0.08 );
		var g = base.svg.append( 'g' ).attr( 'transform', 'translate(' + base.w / 2 + ',' + ( alto / 2 + 4 ) + ')' );

		y.ticks( 3 ).forEach( function ( t ) {
			g.append( 'circle' ).attr( 'r', y( t ) ).attr( 'fill', 'none' ).attr( 'stroke', tk.linea );
			g.append( 'text' ).attr( 'y', -y( t ) - 2 ).attr( 'text-anchor', 'middle' ).attr( 'fill', tk.tinta3 ).attr( 'font-size', 10 ).text( SAN.num( t, 0 ) + ' %' );
		} );
		g.selectAll( 'g.capa' ).data( apilado ).join( 'g' ).attr( 'class', 'capa' )
			.attr( 'fill', function ( d, i ) {
				return pal[ i % pal.length ];
			} )
			.selectAll( 'path' ).data( function ( d ) {
				return d.map( function ( p ) {
					p.clave = d.key;
					return p;
				} );
			} ).join( 'path' )
			.attr( 'd', d3.arc()
				.innerRadius( function ( d ) {
					return y( d[ 0 ] );
				} )
				.outerRadius( function ( d ) {
					return y( d[ 1 ] );
				} )
				.startAngle( function ( d ) {
					return x( d.data.sector );
				} )
				.endAngle( function ( d ) {
					return x( d.data.sector ) + x.bandwidth();
				} )
				.padAngle( 0.01 ).padRadius( 18 ) )
			.attr( 'stroke', tk.superficie ).attr( 'stroke-width', 1 )
			.on( 'mousemove', function ( ev, d ) {
				SAN.tooltip.mostrar( ev, d.data.sector + ' · ' + d.clave, [ SAN.num( d[ 1 ] - d[ 0 ], 1 ) + ' % del tiempo' ] );
			} )
			.on( 'mouseleave', SAN.tooltip.ocultar );
		g.selectAll( 'text.sector' ).data( [ 'N', 'E', 'S', 'O' ] ).join( 'text' ).attr( 'class', 'sector' )
			.attr( 'x', function ( s, i ) {
				return [ 0, rExt + 12, 0, -rExt - 12 ][ i ];
			} )
			.attr( 'y', function ( s, i ) {
				return [ -rExt - 8, 4, rExt + 16, 4 ][ i ];
			} )
			.attr( 'text-anchor', 'middle' ).attr( 'fill', tk.tinta ).attr( 'font-weight', 700 ).attr( 'font-size', 13 )
			.text( function ( s ) {
				return s;
			} );
		leyenda( lienzo, grupos.map( function ( gr, i ) {
			return { color: pal[ i % pal.length ], texto: gr };
		} ) );
		return Promise.resolve();
	};

	/**
	 * Dibuja un gráfico D3 propio y lo redibuja al cambiar el tamaño.
	 * @param {string}  tipo   mapa | puntos | calor | medidor | rosa.
	 * @param {Element} lienzo Contenedor.
	 * @param {Object}  resp   Respuesta de la API.
	 * @returns {Promise<{destruir: Function}>}
	 */
	window.SAN_D3 = {
		tipos: Object.keys( motores ),
		dibujar: function ( tipo, lienzo, resp ) {
			var fn = motores[ tipo ];
			if ( ! fn ) {
				return Promise.reject( new Error( 'Tipo D3 desconocido: ' + tipo ) );
			}
			var ancho = lienzo.clientWidth;
			var espera = null;
			var obs = new ResizeObserver( function () {
				if ( Math.abs( lienzo.clientWidth - ancho ) < 8 ) {
					return;
				}
				ancho = lienzo.clientWidth;
				clearTimeout( espera );
				espera = setTimeout( function () {
					fn( lienzo, resp );
				}, 150 );
			} );
			return Promise.resolve( fn( lienzo, resp ) ).then( function () {
				obs.observe( lienzo );
				return {
					destruir: function () {
						obs.disconnect();
						SAN.tooltip.ocultar();
					},
				};
			} );
		},
	};
}() );
