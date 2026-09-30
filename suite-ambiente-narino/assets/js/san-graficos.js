/**
 * Suite Ambiente Nariño — motor de gráficos.
 *
 * Busca los <figure class="san-grafico">, pide los datos a la API del plugin
 * cuando entran en pantalla y dibuja con D3plus v4 (@d3plus/core 4.5) o con
 * los gráficos D3 v7 propios (SAN_D3). Añade la barra de herramientas:
 * municipio, tipo de gráfico (cambio en vivo entre tipos compatibles) y
 * descargas CSV / JSON / PNG.
 */
( function () {
	'use strict';

	var SAN = window.SAN;
	var CFG = SAN.cfg;
	var T = CFG.textos || {};

	/** Nombres legibles de los tipos. */
	var NOMBRES = {
		line: 'Líneas',
		area: 'Área',
		stacked_area: 'Áreas apiladas',
		bar: 'Barras',
		barh: 'Barras horizontales',
		stacked_bar: 'Barras apiladas',
		pie: 'Torta',
		donut: 'Dona',
		treemap: 'Mapa de árbol',
		pack: 'Burbujas agrupadas',
		radar: 'Radar',
		box: 'Cajas y bigotes',
		scatter: 'Dispersión',
		bump: 'Ranking (bump)',
		chord: 'Cuerdas (chord)',
		sankey: 'Sankey',
		matrix: 'Matriz',
		mapa: 'Mapa coroplético',
		puntos: 'Mapa de puntos',
		calor: 'Mapa de calor',
		medidor: 'Medidor',
		rosa: 'Rosa de vientos',
	};

	/** Tipos que dibuja D3 propio; el resto, D3plus. */
	var TIPOS_D3 = [ 'mapa', 'puntos', 'calor', 'medidor', 'rosa' ];

	/** Clase D3plus por tipo. */
	var CLASES = {
		line: 'LinePlot',
		area: 'AreaPlot',
		stacked_area: 'StackedArea',
		bar: 'BarChart',
		barh: 'BarChart',
		stacked_bar: 'BarChart',
		pie: 'Pie',
		donut: 'Donut',
		treemap: 'Treemap',
		pack: 'Pack',
		radar: 'Radar',
		box: 'BoxWhisker',
		scatter: 'Plot',
		bump: 'BumpChart',
		chord: 'Chord',
		sankey: 'Sankey',
		matrix: 'Matrix',
	};

	/** Escapa HTML (D3plus inserta los tooltips con innerHTML). */
	function esc( v ) {
		return String( v === null || v === undefined ? '' : v ).replace( /[&<>"']/g, function ( ch ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ ch ];
		} );
	}

	function numero( v ) {
		var n = v === null || v === undefined || v === '' ? NaN : Number( v );
		return isFinite( n ) ? n : null;
	}

	/**
	 * Convierte "2026-09-30" o "2026-09-30T14:00" a Date local (sin desfase UTC).
	 * @param {string} s Fecha ISO.
	 * @returns {Date}
	 */
	function aFecha( s ) {
		if ( s instanceof Date ) {
			return s;
		}
		var m = String( s ).match( /^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2}))?/ );
		return m ? new Date( +m[ 1 ], +m[ 2 ] - 1, +m[ 3 ], +( m[ 4 ] || 0 ), +( m[ 5 ] || 0 ) ) : new Date( s );
	}

	/* ------------------------------------------------------------------
	 * Motor D3plus v4
	 * ---------------------------------------------------------------- */

	/**
	 * Configuración D3plus para un tipo y una respuesta.
	 * @returns {Object}
	 */
	function configD3plus( tipo, resp, lienzo ) {
		var c = resp.config || {};
		var pal = SAN.paleta( lienzo );
		var tk = SAN.tokens( lienzo );
		// D3plus usa la primera fuente de la lista que considera instalada y
		// guarda esa decisión para toda la página; además da "-apple-system" por
		// instalada siempre. Con la pila completa del tema, en Windows y Linux
		// mediría con una fuente y el navegador pintaría con otra (textos
		// cortados). Se le pasa la fuente institucional y "sans-serif".
		var fuente = String( tk.fuente ).split( ',' )[ 0 ].trim() + ', sans-serif';
		var dp = CFG.d3plus || {};
		var unidad = c.unidad ? ' ' + c.unidad : '';
		var dec = c.decimales === undefined ? 1 : c.decimales;
		var grupo = c.grupo || null;

		// Una sola instancia de Date por valor: los ejes discretos de D3plus
		// comparan fechas por identidad (etiquetas de barras apiladas).
		var instancias = {};
		var datos = ( resp.datos || [] ).map( function ( d ) {
			var o = Object.assign( {}, d );
			if ( c.tiempo && o[ c.x ] !== undefined ) {
				var clave = String( o[ c.x ] );
				if ( ! instancias[ clave ] ) {
					instancias[ clave ] = aFecha( o[ c.x ] );
				}
				o[ c.x ] = instancias[ clave ];
			}
			return o;
		} );

		// Color por entidad en orden fijo (nunca por rango). Sin grupo: un solo color.
		var entidades = [];
		if ( grupo ) {
			datos.forEach( function ( d ) {
				if ( entidades.indexOf( d[ grupo ] ) < 0 ) {
					entidades.push( d[ grupo ] );
				}
			} );
		}
		var color = function ( d ) {
			if ( c.color_campo && d[ c.color_campo ] ) {
				return d[ c.color_campo ];
			}
			if ( ! grupo ) {
				return pal[ 0 ];
			}
			var i = entidades.indexOf( d[ grupo ] );
			return i >= 0 && i < pal.length ? pal[ i ] : tk.tinta3;
		};

		var fmt = function ( v ) {
			return SAN.num( v, dec ) + unidad;
		};
		var fmtEje = function ( v ) {
			// Conteos (decimales = 0): sin marcas fraccionarias en el eje.
			if ( dec === 0 && ! Number.isInteger( v ) ) {
				return '';
			}
			if ( Math.abs( v ) >= 100 || Number.isInteger( v ) ) {
				return SAN.num( v, 0 );
			}
			return SAN.num( v, Math.max( 1, Math.min( 2, dec ) ) );
		};
		var etiquetaX = function ( d ) {
			var v = d[ c.x ];
			if ( v instanceof Date ) {
				return c.periodo ? fmtFecha( v ) : SAN.fecha( v, !! c.hora );
			}
			return v;
		};

		// D3plus evalúa las propiedades de texto como accesores: se pasan funciones.
		var cte = function ( v ) {
			return function () {
				return v;
			};
		};
		// Objetos nuevos en cada llamada: D3plus muta la configuración de los
		// ejes y compartirla entre X e Y corrompe la escala.
		var ejes = function ( extra ) {
			return Object.assign( {
				barConfig: { stroke: tk.eje },
				gridConfig: { stroke: tk.linea, 'stroke-width': 1 },
				shapeConfig: { labelConfig: { fontColor: cte( tk.tinta3 ), fontFamily: cte( fuente ), fontSize: cte( 12 ) }, stroke: tk.eje },
				titleConfig: { fontColor: tk.tinta2, fontFamily: fuente, fontSize: 13 },
			}, extra );
		};
		var fmtFecha = function ( d ) {
			var f = d instanceof Date ? d : new Date( d );
			if ( isNaN( f ) ) {
				return String( d );
			}
			if ( c.periodo === 'anio' ) {
				return String( f.getFullYear() );
			}
			if ( c.periodo === 'mes' ) {
				return f.toLocaleDateString( 'es-CO', { month: 'short', year: 'numeric' } );
			}
			return f.toLocaleDateString( 'es-CO', c.hora ? { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' } : { day: 'numeric', month: 'short' } );
		};

		var base = {
			data: datos,
			locale: dp.locale || 'es-ES',
			renderer: dp.renderer || 'svg',
			zoom: !! dp.zoom,
			search: !! dp.busqueda,
			tableView: !! dp.tabla,
			minimap: !! dp.minimapa,
			// El plugin ya difiere la carga al entrar en pantalla; se desactiva la
			// liberación de gráficos fuera de pantalla de D3plus v4 para conservar
			// el estado (tabla, zoom) y permitir exportar PNG en cualquier momento.
			detectVisible: false,
			detectVisibleUnload: false,
			duration: window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ? 0 : 500,
			legend: entidades.length > 1 || !! c.leyenda,
			legendPosition: 'bottom',
			legendConfig: { label: function ( d ) {
				return grupo ? d[ grupo ] : ( c.leyenda_etiqueta ? d[ c.leyenda_etiqueta ] : '' );
			}, shapeConfig: { labelConfig: { fontColor: cte( tk.tinta2 ), fontFamily: cte( fuente ), fontSize: cte( 13 ) } } },
			shapeConfig: {
				fill: color,
				labelConfig: { fontFamily: cte( fuente ) },
				Line: { stroke: color, strokeWidth: 2, curve: c.curva || 'monotoneX' },
				Bar: { rx: 3 },
			},
			tooltipConfig: {
				title: function ( d ) {
					if ( c.x && d[ c.x ] !== undefined ) {
						return esc( ( grupo && d[ grupo ] ? d[ grupo ] + ' · ' : '' ) + etiquetaX( d ) );
					}
					return esc( d[ c.etiqueta || grupo || 'id' ] || '' );
				},
				tbody: ( c.tooltip || [] ).length ? c.tooltip.map( function ( t ) {
					return [ esc( t[ 0 ] ), function ( d ) {
						var v = numero( d[ t[ 1 ] ] );
						return esc( v === null ? ( d[ t[ 1 ] ] || '—' ) : SAN.num( v, t[ 3 ] === undefined ? dec : t[ 3 ] ) + ( t[ 2 ] ? ' ' + t[ 2 ] : '' ) );
					} ];
				} ) : [ [ esc( c.etiqueta_y || 'Valor' ), function ( d ) {
					return esc( fmt( d[ c.y ] ) );
				} ] ],
			},
			ariaHidden: false,
		};
		if ( grupo ) {
			base.groupBy = grupo;
		}
		// Lienzos angostos (móvil, columnas del tablero): leyenda en columna,
		// un elemento por fila, para no truncar nombres largos.
		if ( ( lienzo.clientWidth || 600 ) < 480 ) {
			base.legendConfig.direction = 'column';
		}

		// Fechas del eje X: si no caben todas en el ancho del lienzo, D3plus las
		// trunca ("1…", "30de...") u oculta. Se rotula una de cada k fechas de
		// los datos (en series horarias, solo horas redondas).
		var etiquetasFecha = function () {
			var vistas = {};
			var fechas = [];
			datos.forEach( function ( d ) {
				var f = d[ c.x ];
				if ( f instanceof Date && ! isNaN( f ) && ! vistas[ +f ] ) {
					vistas[ +f ] = true;
					fechas.push( f );
				}
			} );
			if ( fechas.length < 2 ) {
				return null;
			}
			fechas.sort( function ( a, b ) {
				return a - b;
			} );
			var porEtiqueta = c.hora ? 84 : ( c.periodo === 'mes' ? 72 : ( c.periodo === 'anio' ? 44 : 66 ) );
			var caben = Math.max( 2, Math.floor( Math.max( 120, ( lienzo.clientWidth || 600 ) - 80 ) / porEtiqueta ) );
			var candidatas = fechas;
			if ( c.hora ) {
				var redondas = fechas.filter( function ( f ) {
					return f.getHours() % 6 === 0 && f.getMinutes() === 0;
				} );
				candidatas = redondas.length >= 2 ? redondas : fechas;
			}
			var paso = Math.ceil( candidatas.length / caben );
			var elegidas = candidatas.length <= caben ? candidatas : candidatas.filter( function ( f, i ) {
				return i % paso === 0;
			} );
			return elegidas === fechas ? null : { etiquetas: elegidas, total: fechas.length };
		};
		var ejeX = { title: c.etiqueta_x || '' };
		if ( c.tiempo ) {
			ejeX.tickFormat = fmtFecha;
			var rotulos = etiquetasFecha();
			if ( rotulos ) {
				ejeX.labels = rotulos.etiquetas;
				// Series largas: la cuadrícula sigue a las etiquetas; una línea
				// por dato satura el gráfico.
				if ( rotulos.total > 31 ) {
					ejeX.ticks = rotulos.etiquetas;
				}
			}
		}

		var xy = {
			x: c.x,
			y: c.y,
			xConfig: ejes( ejeX ),
			yConfig: ejes( { title: c.etiqueta_y || '', tickFormat: fmtEje } ),
			timeline: false,
		};

		// Sin etiquetas en cada marca de los gráficos de ejes (el eje y el
		// tooltip ya las dan); sin agrupación, cada barra se identifica por x.
		if ( [ 'bar', 'barh', 'stacked_bar', 'line', 'area', 'stacked_area', 'scatter', 'box', 'bump' ].indexOf( tipo ) >= 0 ) {
			base.shapeConfig.label = false;
		}
		if ( ! grupo && ( tipo === 'bar' || tipo === 'line' || tipo === 'area' ) ) {
			base.groupBy = function () {
				return c.etiqueta_y || 'valor';
			};
		}

		switch ( tipo ) {
			case 'line':
			case 'area':
			case 'stacked_area':
			case 'bump':
				return Object.assign( base, xy, tipo === 'bump' ? {} : {} );
			case 'bar':
				return Object.assign( base, xy, { discrete: 'x' } );
			case 'stacked_bar':
				return Object.assign( base, xy, { discrete: 'x', stacked: true } );
			case 'barh':
				return Object.assign( base, {
					discrete: 'y',
					x: c.y,
					y: c.etiqueta || c.x,
					xConfig: ejes( { title: c.etiqueta_y || '', tickFormat: fmtEje } ),
					yConfig: ejes( { title: '' } ),
					ySort: function ( a, b ) {
						return numero( a[ c.y ] ) - numero( b[ c.y ] );
					},
					timeline: false,
					groupBy: grupo || c.etiqueta || c.x,
					legend: !! grupo && entidades.length > 1,
				} );
			case 'scatter':
				xy.xConfig = ejes( { title: c.etiqueta_x || '', tickFormat: fmtEje } );
				return Object.assign( base, xy, {
					groupBy: c.id || grupo || c.etiqueta,
					size: c.tamano,
					sizeMin: 4,
					sizeMax: 18,
					legend: !! grupo && entidades.length > 1,
				} );
			case 'box':
				return Object.assign( base, xy, { groupBy: [ c.x, c.id || 'id' ], discrete: 'x', legend: false } );
			case 'pie':
			case 'donut':
				return Object.assign( base, {
					groupBy: c.etiqueta || c.x,
					value: function ( d ) {
						return numero( d[ c.y ] ) || 0;
					},
					shapeConfig: Object.assign( base.shapeConfig, { fill: function ( d, i ) {
						return c.color_campo && d[ c.color_campo ] ? d[ c.color_campo ] : pal[ i % pal.length ];
					} } ),
					legend: true,
					legendConfig: { label: function ( d ) {
						return d[ c.etiqueta || c.x ];
					}, shapeConfig: base.legendConfig.shapeConfig },
				} );
			case 'treemap':
			case 'pack':
				return Object.assign( base, {
					groupBy: c.jerarquia || [ c.etiqueta || c.x ],
					sum: function ( d ) {
						return numero( d[ c.y ] ) || 0;
					},
					legend: !! c.jerarquia && c.jerarquia.length > 1,
					shapeConfig: Object.assign( base.shapeConfig, { fill: function ( d ) {
						var top = c.jerarquia ? d[ c.jerarquia[ 0 ] ] : null;
						if ( top ) {
							var ents = [];
							datos.forEach( function ( r ) {
								if ( ents.indexOf( r[ c.jerarquia[ 0 ] ] ) < 0 ) {
									ents.push( r[ c.jerarquia[ 0 ] ] );
								}
							} );
							var i = ents.indexOf( top );
							return i < pal.length ? pal[ i ] : tk.tinta3;
						}
						var campo = c.etiqueta || c.x;
						var cats = [];
						datos.forEach( function ( r ) {
							if ( cats.indexOf( r[ campo ] ) < 0 ) {
								cats.push( r[ campo ] );
							}
						} );
						var k = cats.indexOf( d[ campo ] );
						return k >= 0 && k < pal.length ? pal[ k ] : tk.tinta3;
					} } ),
				} );
			case 'radar':
				return Object.assign( base, {
					groupBy: grupo || c.id || 'serie',
					metric: c.x,
					value: function ( d ) {
						return numero( d[ c.y ] ) || 0;
					},
				} );
			case 'chord':
			case 'sankey':
				return {
					links: ( resp.datos || [] ).map( function ( d ) {
						return { source: d[ c.origen || 'origen' ], target: d[ c.destino || 'destino' ], value: numero( d[ c.valor || 'valor' ] ) || 0 };
					} ),
					value: 'value',
					locale: base.locale,
					renderer: base.renderer,
					zoom: base.zoom,
					tableView: base.tableView,
					search: base.search,
					minimap: base.minimap,
					detectVisible: false,
					detectVisibleUnload: false,
					duration: base.duration,
					shapeConfig: { labelConfig: { fontFamily: cte( fuente ), fontColor: cte( tk.tinta2 ) } },
					tooltipConfig: { tbody: [ [ esc( c.etiqueta_y || 'Valor' ), function ( d ) {
						return d.value !== undefined ? esc( fmt( d.value ) ) : '';
					} ] ] },
				};
			case 'matrix':
				return Object.assign( base, {
					row: c.y,
					column: c.x,
					value: function ( d ) {
						return numero( d[ c.valor ] );
					},
				} );
		}
		return base;
	}

	/**
	 * Dibuja con D3plus.
	 * @returns {Promise<{destruir: Function}>}
	 */
	/* ------------------------------------------------------------------
	 * Fuentes web
	 * ---------------------------------------------------------------- */

	// D3plus y los motores D3 miden los textos al dibujar. Si la tipografía
	// institucional llega después, las etiquetas quedan cortadas ("Frente a
	// Sanquianga…"). Se espera a que cargue (máximo 3 s) y, si llega más
	// tarde, se redibujan los gráficos ya pintados.
	var fuentesCargadas = false;
	var porRedibujar = [];
	var esperaFuentes = null;
	function fuentesListas() {
		if ( esperaFuentes ) {
			return esperaFuentes;
		}
		if ( ! document.fonts || typeof document.fonts.load !== 'function' ) {
			fuentesCargadas = true;
			esperaFuentes = Promise.resolve();
			return esperaFuentes;
		}
		var carga = Promise.all( [
			document.fonts.load( '400 12px "Nunito Sans"' ),
			document.fonts.load( '600 13px "Nunito Sans"' ),
		] ).catch( function () {} ).then( function () {
			fuentesCargadas = true;
			porRedibujar.splice( 0 ).forEach( function ( g ) {
				g.dibujar();
			} );
		} );
		esperaFuentes = Promise.race( [ carga, new Promise( function ( ok ) {
			setTimeout( ok, 3000 );
		} ) ] );
		return esperaFuentes;
	}

	function dibujarD3plus( tipo, lienzo, resp ) {
		var Clase = window.d3plus && window.d3plus[ CLASES[ tipo ] ];
		if ( ! Clase ) {
			return Promise.reject( new Error( 'D3plus no está disponible para el tipo ' + tipo ) );
		}
		lienzo.textContent = '';
		var viz = new Clase().select( lienzo ).config( configD3plus( tipo, resp, lienzo ) );
		return Promise.resolve( viz.render() ).then( function () {
			return {
				destruir: function () {
					if ( typeof viz.destroy === 'function' ) {
						viz.destroy();
					}
				},
			};
		} );
	}

	/* ------------------------------------------------------------------
	 * Controlador de cada figura
	 * ---------------------------------------------------------------- */

	function Grafico( fig ) {
		this.fig = fig;
		this.id = fig.getAttribute( 'data-viz' );
		this.tipo = fig.getAttribute( 'data-tipo' );
		this.tipos = ( fig.getAttribute( 'data-tipos' ) || '' ).split( ',' ).filter( Boolean );
		this.params = {};
		try {
			this.params = JSON.parse( fig.getAttribute( 'data-params' ) || '{}' ) || {};
		} catch ( e ) {
			this.params = {};
		}
		this.selector = fig.getAttribute( 'data-selector' ) === '1';
		this.herramientas = fig.getAttribute( 'data-herramientas' ) !== '0';
		this.lienzo = fig.querySelector( '.san-grafico__lienzo' );
		this.barra = fig.querySelector( '.san-grafico__barra' );
		this.pie = fig.querySelector( '.san-grafico__pie' );
		this.resp = null;
		this.actual = null;
		fig.sanGrafico = this;
	}

	Grafico.prototype.cargar = function () {
		var self = this;
		this.estadoCargando();
		return SAN.api( 'visualizaciones/' + this.id, this.params ).then( function ( resp ) {
			self.resp = resp;
			if ( ! resp.ok || ! resp.datos || ! resp.datos.length ) {
				self.estadoError( resp.error || T.sin_datos );
				self.pintarPie();
				return;
			}
			self.construirBarra();
			self.pintarPie();
			return self.dibujar();
		} ).catch( function ( e ) {
			self.estadoError( ( e && e.respuesta && e.respuesta.error ) || T.error );
		} );
	};

	Grafico.prototype.dibujar = function () {
		var self = this;
		var turno = this.turno = ( this.turno || 0 ) + 1;
		return fuentesListas().then( function () {
			// Otro dibujo (cambio de tipo o municipio) empezó mientras tanto.
			if ( turno !== self.turno ) {
				return;
			}
			if ( self.actual ) {
				self.actual.destruir();
				self.actual = null;
			}
			var fn = TIPOS_D3.indexOf( self.tipo ) >= 0 ? window.SAN_D3.dibujar.bind( window.SAN_D3 ) : dibujarD3plus;
			return fn( self.tipo, self.lienzo, self.resp ).then( function ( h ) {
				self.actual = h;
				self.fig.setAttribute( 'data-estado', 'listo' );
				if ( ! fuentesCargadas && porRedibujar.indexOf( self ) < 0 ) {
					porRedibujar.push( self );
				}
			} );
		} ).catch( function ( e ) {
			window.console && console.error( '[Suite Ambiente]', e );
			self.estadoError( T.error );
		} );
	};

	Grafico.prototype.estadoCargando = function () {
		this.fig.setAttribute( 'data-estado', 'cargando' );
		this.lienzo.innerHTML = '<div class="san-cargando" role="status"><span class="san-cargando__barra"></span><span class="screen-reader-text"></span></div>';
		this.lienzo.querySelector( '.screen-reader-text' ).textContent = T.cargando || '';
	};

	Grafico.prototype.estadoError = function ( msg ) {
		this.fig.setAttribute( 'data-estado', 'error' );
		this.lienzo.textContent = '';
		var d = document.createElement( 'div' );
		d.className = 'san-error';
		d.setAttribute( 'role', 'alert' );
		d.textContent = msg;
		this.lienzo.appendChild( d );
	};

	Grafico.prototype.pintarPie = function () {
		if ( ! this.pie || ! this.resp ) {
			return;
		}
		var r = this.resp;
		var partes = [];
		if ( r.fuente && r.fuente.atribucion && CFG.atribucion ) {
			partes.push( ( T.fuente || 'Fuente' ) + ': ' + r.fuente.atribucion );
		}
		if ( r.actualizado ) {
			partes.push( ( T.actualizado || 'Actualizado' ) + ': ' + SAN.fecha( new Date( r.actualizado.replace( ' ', 'T' ) + 'Z' ), true ) );
		}
		if ( r.nota ) {
			partes.push( r.nota );
		}
		this.pie.textContent = partes.join( ' · ' );
		if ( r.vencido ) {
			var v = document.createElement( 'strong' );
			v.className = 'san-vencido';
			v.textContent = ' · ' + ( T.vencido || '' );
			this.pie.appendChild( v );
		}
	};

	Grafico.prototype.construirBarra = function () {
		var self = this;
		if ( ! this.herramientas || this.barra.childElementCount ) {
			return;
		}
		var uid = this.fig.id;

		if ( this.selector && CFG.municipios ) {
			var lm = document.createElement( 'label' );
			lm.setAttribute( 'for', uid + '-m' );
			lm.textContent = T.municipio || 'Municipio';
			var sm = document.createElement( 'select' );
			sm.id = uid + '-m';
			var actual = this.params.municipio || ( this.resp.parametros && this.resp.parametros.municipio ) || CFG.defecto;
			Object.keys( CFG.municipios ).sort( function ( a, b ) {
				return CFG.municipios[ a ].localeCompare( CFG.municipios[ b ], 'es' );
			} ).forEach( function ( k ) {
				var o = document.createElement( 'option' );
				o.value = k;
				o.textContent = CFG.municipios[ k ];
				o.selected = k === actual;
				sm.appendChild( o );
			} );
			sm.addEventListener( 'change', function () {
				self.params.municipio = sm.value;
				self.cargar();
			} );
			this.barra.appendChild( lm );
			this.barra.appendChild( sm );
		}

		if ( this.tipos.length > 1 ) {
			var lt = document.createElement( 'label' );
			lt.setAttribute( 'for', uid + '-t' );
			lt.textContent = T.tipo || 'Tipo';
			var st = document.createElement( 'select' );
			st.id = uid + '-t';
			this.tipos.forEach( function ( t ) {
				var o = document.createElement( 'option' );
				o.value = t;
				o.textContent = NOMBRES[ t ] || t;
				o.selected = t === self.tipo;
				st.appendChild( o );
			} );
			st.addEventListener( 'change', function () {
				self.tipo = st.value;
				self.fig.setAttribute( 'data-tipo', st.value );
				self.dibujar();
			} );
			this.barra.appendChild( lt );
			this.barra.appendChild( st );
		}

		var esp = document.createElement( 'span' );
		esp.className = 'san-espaciador';
		this.barra.appendChild( esp );
		var grupo = document.createElement( 'span' );
		grupo.className = 'san-descargas';
		this.barra.appendChild( grupo );

		var bTabla = document.createElement( 'button' );
		bTabla.type = 'button';
		bTabla.className = 'san-boton';
		bTabla.textContent = T.tabla || 'Tabla';
		bTabla.setAttribute( 'aria-pressed', 'false' );
		bTabla.addEventListener( 'click', function () {
			var abierta = bTabla.getAttribute( 'aria-pressed' ) === 'true';
			bTabla.setAttribute( 'aria-pressed', abierta ? 'false' : 'true' );
			self.alternarTabla( ! abierta );
		} );
		grupo.appendChild( bTabla );

		[
			[ T.csv || 'CSV', function () {
				SAN.descargar( self.id + '.csv', SAN.csv( self.resp.datos ), 'text/csv;charset=utf-8' );
			} ],
			[ T.json || 'JSON', function () {
				var j = { titulo: self.resp.titulo, fuente: self.resp.fuente, actualizado: self.resp.actualizado, parametros: self.resp.parametros, datos: self.resp.datos };
				SAN.descargar( self.id + '.json', JSON.stringify( j, null, 2 ), 'application/json' );
			} ],
			[ T.png || 'PNG', function () {
				SAN.png( self.lienzo, self.id + '.png' );
			} ],
		].forEach( function ( b ) {
			var btn = document.createElement( 'button' );
			btn.type = 'button';
			btn.className = 'san-boton';
			btn.textContent = b[ 0 ];
			btn.setAttribute( 'aria-label', 'Descargar ' + b[ 0 ] );
			btn.addEventListener( 'click', b[ 1 ] );
			grupo.appendChild( btn );
		} );
	};

	/**
	 * Muestra u oculta la tabla HTML con los datos del gráfico (alternativa
	 * accesible: lectores de pantalla, navegación con teclado, impresión).
	 * @param {boolean} mostrar Mostrar.
	 */
	Grafico.prototype.alternarTabla = function ( mostrar ) {
		var caja = this.fig.querySelector( '.san-tabla-datos' );
		if ( caja ) {
			caja.remove();
		}
		if ( ! mostrar || ! this.resp || ! this.resp.datos ) {
			return;
		}
		var datos = this.resp.datos;
		var ocultas = [ 'color', 'ica_orden', 'peso', 'tam', 'clave', 'nivel' ];
		var cols = Object.keys( datos[ 0 ] || {} ).filter( function ( k ) {
			return k.charAt( 0 ) !== '_' && ocultas.indexOf( k ) < 0;
		} );
		caja = document.createElement( 'div' );
		caja.className = 'san-tabla-datos';
		caja.tabIndex = 0;
		var t = document.createElement( 'table' );
		var cap = document.createElement( 'caption' );
		cap.textContent = ( T.tabla_cap || 'Datos' ) + ' · ' + ( this.resp.titulo || '' ) + ( datos.length > 500 ? ' (500 de ' + datos.length + ' filas; descargue el CSV para verlas todas)' : '' );
		t.appendChild( cap );
		var thead = document.createElement( 'thead' );
		var tr = document.createElement( 'tr' );
		cols.forEach( function ( c ) {
			var th = document.createElement( 'th' );
			th.scope = 'col';
			th.textContent = c.replace( /_/g, ' ' );
			tr.appendChild( th );
		} );
		thead.appendChild( tr );
		t.appendChild( thead );
		var tb = document.createElement( 'tbody' );
		datos.slice( 0, 500 ).forEach( function ( d ) {
			var fila = document.createElement( 'tr' );
			cols.forEach( function ( c ) {
				var td = document.createElement( 'td' );
				var v = d[ c ];
				td.textContent = typeof v === 'number' ? SAN.num( v, Math.abs( v ) >= 100 ? 0 : 2 ) : ( v === null || v === undefined ? '—' : String( v ) );
				fila.appendChild( td );
			} );
			tb.appendChild( fila );
		} );
		t.appendChild( tb );
		caja.appendChild( t );
		this.lienzo.insertAdjacentElement( 'afterend', caja );
	};

	/* ------------------------------------------------------------------
	 * Arranque: carga diferida al entrar en pantalla
	 * ---------------------------------------------------------------- */

	function iniciar( raiz ) {
		var figs = ( raiz || document ).querySelectorAll( '.san-grafico[data-viz]:not([data-estado])' );
		var cargar = function ( fig ) {
			if ( ! fig.sanGrafico ) {
				new Grafico( fig ).cargar();
			}
		};
		if ( ! ( 'IntersectionObserver' in window ) ) {
			figs.forEach( cargar );
			return;
		}
		var io = new IntersectionObserver( function ( entradas ) {
			entradas.forEach( function ( e ) {
				if ( e.isIntersecting ) {
					io.unobserve( e.target );
					cargar( e.target );
				}
			} );
		}, { rootMargin: '200px' } );
		figs.forEach( function ( f ) {
			io.observe( f );
		} );

		// Tablero con autoactualización.
		( raiz || document ).querySelectorAll( '.san-tablero[data-actualizar]' ).forEach( function ( t ) {
			var min = parseInt( t.getAttribute( 'data-actualizar' ), 10 );
			if ( min > 0 ) {
				setInterval( function () {
					t.querySelectorAll( '.san-grafico' ).forEach( function ( f ) {
						if ( f.sanGrafico ) {
							f.sanGrafico.cargar();
						}
					} );
				}, min * 60000 );
			}
		} );
	}

	window.SAN.graficos = { iniciar: iniciar, Grafico: Grafico, nombres: NOMBRES, configD3plus: configD3plus };

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			iniciar();
		} );
	} else {
		iniciar();
	}
}() );
