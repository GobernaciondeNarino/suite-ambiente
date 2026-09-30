<?php
/**
 * Visualizaciones de sismos y volcanes (SGC y USGS).
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Viz_Sismos extends SAN_Viz {

	/** Colores por profundidad (se acompañan de leyenda con texto). */
	const COLOR_PROF = array(
		'superficial' => '#D14124',
		'intermedio'  => '#E0A100',
		'profundo'    => '#1F5C99',
	);

	/**
	 * Definiciones.
	 *
	 * @return array
	 */
	public static function definiciones() {
		$c = __CLASS__;
		return array(
			'sismos_mapa'           => array(
				'titulo'      => 'Sismos recientes en Nariño y su entorno',
				'fuente'      => 'sgc_sismos',
				'subgrupo'    => 'Sismicidad',
				'descripcion' => 'Epicentros localizados por la Red Sismológica Nacional en las últimas dos semanas. El tamaño del círculo indica la magnitud y el color, la profundidad.',
				'lectura'     => 'Los sismos superficiales (< 70 km) se sienten más aunque tengan la misma magnitud. En Nariño se concentran alrededor de los volcanes y en la zona de subducción del Pacífico.',
				'tipo'        => 'puntos',
				'tipos'       => array( 'puntos' ),
				'procesador'  => array( $c, 'mapa' ),
			),
			'sismos_diarios'        => array(
				'titulo'      => 'Número de sismos por día',
				'fuente'      => 'sgc_sismos',
				'subgrupo'    => 'Sismicidad',
				'descripcion' => 'Conteo diario de sismos localizados en la región de Nariño.',
				'lectura'     => 'Un aumento sostenido del número de sismos cerca de un volcán es una de las señales que vigila el Observatorio de Pasto.',
				'tipo'        => 'bar',
				'tipos'       => array( 'bar', 'line', 'area' ),
				'procesador'  => array( $c, 'diarios' ),
			),
			'sismos_magnitud_prof'  => array(
				'titulo'      => 'Magnitud y profundidad',
				'fuente'      => 'sgc_sismos',
				'subgrupo'    => 'Sismicidad',
				'descripcion' => 'Cada punto es un sismo: magnitud en el eje horizontal y profundidad (km) en el vertical.',
				'lectura'     => 'Los puntos altos son profundos (subducción de la placa de Nazca); los bajos, superficiales (fallas y volcanes).',
				'tipo'        => 'scatter',
				'tipos'       => array( 'scatter' ),
				'procesador'  => array( $c, 'magnitud_prof' ),
			),
			'sismos_clases'         => array(
				'titulo'      => 'Sismos por rango de magnitud',
				'fuente'      => 'sgc_sismos',
				'subgrupo'    => 'Sismicidad',
				'descripcion' => 'Distribución de los sismos recientes por rango de magnitud.',
				'lectura'     => 'La mayoría de los sismos son micro o menores y no se sienten; cada unidad de magnitud libera unas 32 veces más energía.',
				'tipo'        => 'donut',
				'tipos'       => array( 'donut', 'pie', 'bar' ),
				'procesador'  => array( $c, 'clases' ),
			),
			'volcanes_alerta'       => array(
				'titulo'      => 'Nivel de alerta de los volcanes',
				'fuente'      => 'sgc_volcanes',
				'subgrupo'    => 'Volcanes',
				'descripcion' => 'Nivel de actividad vigente declarado por el Servicio Geológico Colombiano para los volcanes que influyen en Nariño.',
				'lectura'     => 'Verde: activo en reposo. Amarilla: cambios en el comportamiento. Naranja: erupción probable en días o semanas. Roja: erupción inminente o en curso.',
				'tipo'        => 'puntos',
				'tipos'       => array( 'puntos', 'barh' ),
				'procesador'  => array( $c, 'volcanes' ),
			),
			'usgs_regional'         => array(
				'titulo'      => 'Sismicidad regional (último año)',
				'fuente'      => 'usgs',
				'subgrupo'    => 'Contexto regional',
				'descripcion' => 'Sismos de magnitud ≥ 2,5 registrados por el USGS en un radio de 400 km alrededor de Pasto durante el último año, incluidos Ecuador y la costa pacífica.',
				'lectura'     => 'Muestra la actividad de la zona de subducción que genera los sismos fuertes y tsunamis que han afectado a Tumaco.',
				'tipo'        => 'puntos',
				'tipos'       => array( 'puntos' ),
				'procesador'  => array( $c, 'usgs_mapa' ),
			),
			'usgs_magnitud_distancia' => array(
				'titulo'      => 'Magnitud frente a distancia a Pasto',
				'fuente'      => 'usgs',
				'subgrupo'    => 'Contexto regional',
				'descripcion' => 'Magnitud de cada sismo regional según su distancia a Pasto.',
				'lectura'     => 'Los sismos grandes y cercanos (arriba a la izquierda) son los de mayor potencial de daño.',
				'tipo'        => 'scatter',
				'tipos'       => array( 'scatter' ),
				'procesador'  => array( $c, 'usgs_dispersion' ),
			),
		);
	}

	/**
	 * Recursos.
	 *
	 * @return array
	 */
	public static function recursos() {
		return array(
			'sismos_sgc' => array(
				'titulo'      => 'Sismos recientes (SGC)',
				'descripcion' => 'Sismos de las últimas dos semanas en Nariño y su entorno según la Red Sismológica Nacional.',
				'fuente'      => 'sgc_sismos',
				'formatos'    => array( 'json', 'csv', 'geojson' ),
				'campos'      => array(
					'id'          => 'Identificador del evento',
					'fecha_utc'   => 'Fecha y hora UTC',
					'fecha_local' => 'Fecha y hora de Colombia',
					'magnitud'    => 'Magnitud',
					'tipo_mag'    => 'Tipo de magnitud',
					'profundidad' => 'Profundidad (km)',
					'lat'         => 'Latitud',
					'lon'         => 'Longitud',
					'lugar'       => 'Región',
					'cercanos'    => 'Poblaciones cercanas',
					'estado'      => 'Revisión (manual/automático)',
					'divipola'    => 'Municipio de Nariño que contiene el epicentro (vacío si fuera del departamento)',
					'municipio'   => 'Nombre del municipio',
				),
				'generador'   => array( __CLASS__, 'gen_sgc' ),
			),
			'volcanes'   => array(
				'titulo'      => 'Nivel de actividad de los volcanes',
				'descripcion' => 'Volcanes que influyen en Nariño con su nivel de actividad vigente y el último boletín del SGC.',
				'fuente'      => 'sgc_volcanes',
				'formatos'    => array( 'json', 'csv', 'geojson' ),
				'campos'      => array(
					'volcan'         => 'Nombre',
					'nivel_codigo'   => 'Código de nivel (4 verde … 1 roja)',
					'nivel'          => 'Nivel de alerta',
					'descripcion'    => 'Descripción corta',
					'lat'            => 'Latitud',
					'lon'            => 'Longitud',
					'altitud'        => 'Altitud',
					'boletin_fecha'  => 'Fecha del último boletín',
					'boletin_url'    => 'Enlace al boletín (PDF)',
				),
				'generador'   => array( __CLASS__, 'gen_volcanes' ),
			),
			'sismos_usgs' => array(
				'titulo'      => 'Sismicidad regional (USGS)',
				'descripcion' => 'Sismos de magnitud ≥ 2,5 en un radio de 400 km de Pasto durante el último año.',
				'fuente'      => 'usgs',
				'formatos'    => array( 'json', 'csv', 'geojson' ),
				'campos'      => array(
					'id'           => 'Identificador USGS',
					'fecha_utc'    => 'Fecha y hora UTC',
					'magnitud'     => 'Magnitud',
					'profundidad'  => 'Profundidad (km)',
					'lat'          => 'Latitud',
					'lon'          => 'Longitud',
					'lugar'        => 'Región',
					'distancia_km' => 'Distancia a Pasto (km)',
					'enlace'       => 'Página del evento',
				),
				'generador'   => array( __CLASS__, 'gen_usgs' ),
			),
		);
	}

	/* ---------- Generadores ---------- */

	/** @return array */
	public static function gen_sgc() {
		$r = SAN_Fuentes::obtener( 'sgc_sismos' )->sismos();
		return array(
			'ok'          => $r['ok'],
			'filas'       => (array) $r['datos'],
			'actualizado' => $r['actualizado'],
			'vencido'     => $r['vencido'],
			'error'       => $r['error'],
		);
	}

	/** @return array */
	public static function gen_volcanes() {
		$r = SAN_Fuentes::obtener( 'sgc_volcanes' )->volcanes();
		return array(
			'ok'          => $r['ok'],
			'filas'       => array_map(
				function ( $f ) {
					unset( $f['color'], $f['valor'], $f['categoria'] );
					return $f;
				},
				(array) $r['datos']
			),
			'actualizado' => $r['actualizado'],
			'vencido'     => $r['vencido'],
			'error'       => $r['error'],
		);
	}

	/** @return array */
	public static function gen_usgs() {
		$r = SAN_Fuentes::obtener( 'usgs' )->eventos();
		return array(
			'ok'          => $r['ok'],
			'filas'       => (array) $r['datos'],
			'actualizado' => $r['actualizado'],
			'vencido'     => $r['vencido'],
			'error'       => $r['error'],
		);
	}

	/* ---------- SGC ---------- */

	/**
	 * Sismos con campos derivados.
	 *
	 * @return array { resp, filas }
	 */
	private static function sgc() {
		$r = SAN_Fuentes::obtener( 'sgc_sismos' )->sismos();
		if ( ! $r['ok'] ) {
			return array(
				'resp'  => $r,
				'filas' => array(),
			);
		}
		$filas = array();
		foreach ( (array) $r['datos'] as $f ) {
			$prof             = SAN_Umbrales::profundidad( $f['profundidad'] );
			$f['prof_clase']  = $prof;
			$f['color']       = self::COLOR_PROF[ $prof ];
			$f['clase']       = SAN_Umbrales::sismo( $f['magnitud'] )['nombre'];
			$f['dia']         = substr( $f['fecha_local'], 0, 10 );
			$f['titulo']      = 'M' . SAN_Analisis::num( $f['magnitud'] ) . ' · ' . $f['lugar'];
			$filas[]          = $f;
		}
		return array(
			'resp'  => $r,
			'filas' => $filas,
		);
	}

	/**
	 * Resumen común del análisis de sismos.
	 *
	 * @param array $filas Filas.
	 * @return array
	 */
	private static function analisis_sgc( array $filas ) {
		$est     = SAN_Analisis::estadisticos( array_column( $filas, 'magnitud' ) );
		$max     = SAN_Analisis::extremo( $filas, 'magnitud', true );
		$en      = array_filter( array_column( $filas, 'en_narino' ) );
		$sup     = count(
			array_filter(
				$filas,
				function ( $f ) {
					return 'superficial' === $f['prof_clase'];
				}
			)
		);
		$sentido = count(
			array_filter(
				$filas,
				function ( $f ) {
					return (float) $f['magnitud'] >= 4;
				}
			)
		);
		$clase   = $max ? SAN_Umbrales::sismo( $max['magnitud'] ) : array( 'nivel' => 'info', 'nombre' => '' );
		$muni    = SAN_Analisis::agrupar(
			array_filter(
				$filas,
				function ( $f ) {
					return '' !== $f['municipio'];
				}
			),
			'municipio'
		);
		$top     = array_slice( $muni, 0, 3, true );
		return array(
			'nivel'                => $clase['nivel'],
			'titular'              => $max ? sprintf( '%d sismos en la región en los últimos %d días (%d con epicentro en Nariño). El mayor fue de magnitud %s: %s.', count( $filas ), (int) SAN_Fuentes::obtener( 'sgc_sismos' )->param( 'dias', 15 ), count( $en ), SAN_Analisis::num( $max['magnitud'] ), $max['lugar'] ) : 'Sin sismos localizados en el periodo.',
			'cualitativo'          => array_values(
				array_filter(
					array(
						sprintf( 'El %s de los eventos fueron superficiales (< 70 km); el resto, intermedios o profundos, asociados a la subducción de la placa de Nazca.', SAN_Analisis::num( count( $filas ) ? $sup / count( $filas ) * 100 : 0, 0 ) . ' %' ),
						$top ? 'Municipios con más epicentros: ' . implode( ', ', array_map( function ( $k, $v ) { return $k . ' (' . $v . ')'; }, array_keys( $top ), $top ) ) . '.' : '', // phpcs:ignore
						$sentido ? sprintf( '%d sismo%s de magnitud ≥ 4, que pueden sentirse en la zona epicentral.', $sentido, 1 === $sentido ? '' : 's' ) : 'Ningún sismo alcanzó magnitud 4: la actividad corresponde a sismicidad de fondo, en su mayoría imperceptible.',
					)
				)
			),
			'recomendaciones'      => $sentido ? array( 'Repasar el plan familiar de emergencia y los puntos de encuentro; en la costa, ante un sismo fuerte y prolongado, evacuar a zonas altas sin esperar alerta.' ) : array(),
			'cuantitativo'         => array(
				self::cifra( 'Sismos', (string) count( $filas ), count( $en ) . ' en Nariño' ),
				self::cifra( 'Magnitud máxima', SAN_Analisis::num( $est['max'] ), $max['municipio'] ?? '' ),
				self::cifra( 'Magnitud media', SAN_Analisis::num( $est['media'] ) ),
				self::cifra( 'Superficiales', (string) $sup, '< 70 km' ),
			),
			'resumen_cuantitativo' => sprintf( 'Mediana de magnitud %s; percentil 90: %s.', SAN_Analisis::num( $est['mediana'] ), SAN_Analisis::num( $est['p90'] ) ),
			'metodo'               => 'Catálogo de la Red Sismológica Nacional (SGC), recortado a Nariño ± margen configurado; municipio por punto en polígono (MGN DANE).',
		);
	}

	/** @return array */
	public static function mapa() {
		$a = self::sgc();
		if ( ! $a['resp']['ok'] ) {
			return SAN_Catalogo::fallo( $a['resp'] );
		}
		return self::ok(
			$a['filas'],
			array(
				'lat'             => 'lat',
				'lon'             => 'lon',
				'tamano'          => 'magnitud',
				'color_campo'     => 'color',
				'etiqueta'        => 'titulo',
				'ajustar_a_datos' => true,
				'leyenda'         => array(
					array( 'color' => self::COLOR_PROF['superficial'], 'texto' => 'Superficial (< 70 km)' ),
					array( 'color' => self::COLOR_PROF['intermedio'], 'texto' => 'Intermedio (70–300 km)' ),
					array( 'color' => self::COLOR_PROF['profundo'], 'texto' => 'Profundo (> 300 km)' ),
				),
				'tooltip'         => array( array( 'Fecha', 'fecha_local' ), array( 'Magnitud', 'magnitud', '', 1 ), array( 'Profundidad', 'profundidad', 'km', 0 ), array( 'Cercanos', 'cercanos' ) ),
			),
			self::analisis_sgc( $a['filas'] ),
			$a['resp']
		);
	}

	/** @return array */
	public static function diarios() {
		$a = self::sgc();
		if ( ! $a['resp']['ok'] ) {
			return SAN_Catalogo::fallo( $a['resp'] );
		}
		$por = array();
		foreach ( $a['filas'] as $f ) {
			$por[ $f['dia'] ] = ( $por[ $f['dia'] ] ?? 0 ) + 1;
		}
		ksort( $por );
		$datos = array();
		foreach ( $por as $d => $n ) {
			$datos[] = array(
				'fecha' => $d,
				'sismos' => $n,
			);
		}
		$an         = self::analisis_sgc( $a['filas'] );
		$est        = SAN_Analisis::estadisticos( array_values( $por ) );
		$pico       = SAN_Analisis::extremo( $datos, 'sismos', true );
		$tend       = SAN_Analisis::tendencia( array_values( $por ) );
		$an['cuantitativo'] = array(
			self::cifra( 'Promedio diario', SAN_Analisis::num( $est['media'] ) ),
			self::cifra( 'Día más activo', (string) ( $pico['sismos'] ?? 0 ), $pico ? SAN_Analisis::fecha( $pico['fecha'] ) : '' ),
			self::cifra( 'Tendencia', SAN_Analisis::frase_tendencia( $tend['pendiente'], $est['desviacion'] ?: 1, 'sismos', 'día' ) ),
			self::cifra( 'Total', (string) count( $a['filas'] ) ),
		);
		return self::ok(
			$datos,
			array(
				'x'          => 'fecha',
				'y'          => 'sismos',
				'tiempo'     => true,
				'unidad'     => 'sismos',
				'decimales'  => 0,
				'etiqueta_x' => 'Fecha',
				'etiqueta_y' => 'Sismos por día',
			),
			$an,
			$a['resp']
		);
	}

	/** @return array */
	public static function magnitud_prof() {
		$a = self::sgc();
		if ( ! $a['resp']['ok'] ) {
			return SAN_Catalogo::fallo( $a['resp'] );
		}
		return self::ok(
			$a['filas'],
			array(
				'x'           => 'magnitud',
				'y'           => 'profundidad',
				'id'          => 'id',
				'etiqueta'    => 'titulo',
				'color_campo' => 'color',
				'unidad'      => 'km',
				'decimales'   => 0,
				'etiqueta_x'  => 'Magnitud',
				'etiqueta_y'  => 'Profundidad (km)',
				'tooltip'     => array( array( 'Magnitud', 'magnitud', '', 1 ), array( 'Profundidad', 'profundidad', 'km', 0 ), array( 'Fecha', 'fecha_local' ), array( 'Lugar', 'lugar' ) ),
			),
			self::analisis_sgc( $a['filas'] ),
			$a['resp']
		);
	}

	/** @return array */
	public static function clases() {
		$a = self::sgc();
		if ( ! $a['resp']['ok'] ) {
			return SAN_Catalogo::fallo( $a['resp'] );
		}
		$orden = array( 'micro' => 'Menos de 3 (micro)', 'menor' => '3–3,9 (menor)', 'ligero' => '4–4,9 (ligero)', 'moderado' => '5–5,9 (moderado)', 'fuerte' => '6–6,9 (fuerte)', 'mayor' => '≥ 7 (mayor)' );
		$cuen  = array();
		foreach ( $a['filas'] as $f ) {
			$cuen[ $f['clase'] ] = ( $cuen[ $f['clase'] ] ?? 0 ) + 1;
		}
		$datos = array();
		foreach ( $orden as $k => $et ) {
			if ( ! empty( $cuen[ $k ] ) ) {
				$datos[] = array(
					'rango'  => $et,
					'sismos' => $cuen[ $k ],
				);
			}
		}
		return self::ok(
			$datos,
			array(
				'x'          => 'rango',
				'y'          => 'sismos',
				'etiqueta'   => 'rango',
				'unidad'     => 'sismos',
				'decimales'  => 0,
				'etiqueta_y' => 'Sismos',
			),
			self::analisis_sgc( $a['filas'] ),
			$a['resp']
		);
	}

	/* ---------- Volcanes ---------- */

	/** @return array */
	public static function volcanes() {
		$r = SAN_Fuentes::obtener( 'sgc_volcanes' )->volcanes();
		if ( ! $r['ok'] ) {
			if ( false !== strpos( (string) $r['error'], '403' ) ) {
				return array(
					'ok'    => false,
					'error' => 'El Servicio Geológico Colombiano restringe el acceso automatizado a los niveles de alerta volcánica. Consulte el estado oficial en sgc.gov.co/volcanes; el administrador puede habilitar el acceso en Configuración → APIs tras acordarlo con el SGC.',
				);
			}
			return SAN_Catalogo::fallo( $r );
		}
		$filas     = (array) $r['datos'];
		$amarillos = array_filter(
			$filas,
			function ( $f ) {
				return $f['nivel_codigo'] <= 3;
			}
		);
		$peor      = $filas ? $filas[0] : null;
		$n         = $peor ? ( SAN_Fuente_Sgc_Volcanes::NIVELES[ $peor['nivel_codigo'] ] ?? SAN_Fuente_Sgc_Volcanes::NIVELES[4] ) : null;
		$leyenda   = array();
		foreach ( array_reverse( SAN_Fuente_Sgc_Volcanes::NIVELES, true ) as $cod => $nv ) {
			$leyenda[] = array(
				'color' => $nv['color'],
				'texto' => $nv['nombre'],
			);
		}
		foreach ( $filas as &$f ) {
			$f['tam'] = 5 - $f['nivel_codigo'];
		}
		unset( $f );
		return self::ok(
			$filas,
			array(
				'lat'         => 'lat',
				'lon'         => 'lon',
				'tamano'      => 'tam',
				'color_campo' => 'color',
				'etiqueta'    => 'volcan',
				'ajustar_a_datos' => true,
				'x'           => 'volcan',
				'y'           => 'valor',
				'leyenda'     => $leyenda,
				'tooltip'     => array( array( 'Nivel', 'nivel' ), array( 'Estado', 'descripcion' ), array( 'Último boletín', 'boletin_fecha' ) ),
			),
			array(
				'nivel'                => $n ? $n['nivel'] : 'info',
				'titular'              => $amarillos ? sprintf( '%d volcanes en alerta amarilla o superior: %s.', count( $amarillos ), implode( ', ', array_column( $amarillos, 'volcan' ) ) ) : 'Todos los volcanes de Nariño están en nivel verde (activo en reposo).',
				'cualitativo'          => array_map(
					function ( $f ) {
						return sprintf( '%s — %s. %s%s', $f['volcan'], $f['nivel'], $f['descripcion'], $f['boletin_fecha'] ? ' Último boletín: ' . $f['boletin_fecha'] . '.' : '' );
					},
					array_slice( $filas, 0, 6 )
				),
				'recomendaciones'      => $amarillos ? array( 'Seguir únicamente la información oficial del SGC y de los consejos de gestión del riesgo; no ingresar a las zonas de amenaza alta.', 'Conocer las rutas de evacuación del mapa de amenaza de cada volcán.' ) : array(),
				'cuantitativo'         => array(
					self::cifra( 'Volcanes vigilados', (string) count( $filas ) ),
					self::cifra( 'En amarilla o más', (string) count( $amarillos ) ),
					self::cifra( 'En verde', (string) ( count( $filas ) - count( $amarillos ) ) ),
				),
				'resumen_cuantitativo' => 'Niveles del SGC: 4 verde, 3 amarilla, 2 naranja, 1 roja.',
				'metodo'               => 'Archivo volcanos.json del Servicio Geológico Colombiano; se listan los volcanes cuyo departamento incluye Nariño.',
			),
			$r
		);
	}

	/* ---------- USGS ---------- */

	/** @return array */
	public static function usgs_mapa() {
		$r = SAN_Fuentes::obtener( 'usgs' )->eventos();
		if ( ! $r['ok'] ) {
			return SAN_Catalogo::fallo( $r );
		}
		$filas = array();
		foreach ( (array) $r['datos'] as $f ) {
			$prof        = SAN_Umbrales::profundidad( $f['profundidad'] );
			$f['color']  = self::COLOR_PROF[ $prof ];
			$f['titulo'] = 'M' . SAN_Analisis::num( $f['magnitud'] ) . ' · ' . $f['lugar'];
			$filas[]     = $f;
		}
		$est  = SAN_Analisis::estadisticos( array_column( $filas, 'magnitud' ) );
		$max  = SAN_Analisis::extremo( $filas, 'magnitud', true );
		$cerc = SAN_Analisis::extremo( $filas, 'distancia_km', false );
		$m5   = count(
			array_filter(
				$filas,
				function ( $f ) {
					return $f['magnitud'] >= 5;
				}
			)
		);
		return self::ok(
			$filas,
			array(
				'lat'             => 'lat',
				'lon'             => 'lon',
				'tamano'          => 'magnitud',
				'color_campo'     => 'color',
				'etiqueta'        => 'titulo',
				'ajustar_a_datos' => true,
				'leyenda'         => array(
					array( 'color' => self::COLOR_PROF['superficial'], 'texto' => 'Superficial (< 70 km)' ),
					array( 'color' => self::COLOR_PROF['intermedio'], 'texto' => 'Intermedio (70–300 km)' ),
					array( 'color' => self::COLOR_PROF['profundo'], 'texto' => 'Profundo (> 300 km)' ),
				),
				'tooltip'         => array( array( 'Fecha (UTC)', 'fecha_utc' ), array( 'Magnitud', 'magnitud', '', 1 ), array( 'Profundidad', 'profundidad', 'km', 0 ), array( 'Distancia a Pasto', 'distancia_km', 'km', 0 ) ),
			),
			array(
				'nivel'                => $m5 ? 'moderado' : 'info',
				'titular'              => $max ? sprintf( '%d sismos M ≥ 2,5 en 400 km a la redonda en el último año; el mayor, M%s (%s).', count( $filas ), SAN_Analisis::num( $max['magnitud'] ), $max['lugar'] ) : 'Sin sismos en el periodo.',
				'cualitativo'          => array_values(
					array_filter(
						array(
							$cerc ? sprintf( 'El más cercano a Pasto ocurrió a %s km (%s).', SAN_Analisis::num( $cerc['distancia_km'], 0 ), $cerc['lugar'] ) : '',
							sprintf( '%d evento%s de magnitud 5 o más.', $m5, 1 === $m5 ? '' : 's' ),
							'El USGS solo registra de forma completa los sismos de magnitud cercana a 4 o más en esta región; la sismicidad menor la documenta el SGC.',
						)
					)
				),
				'recomendaciones'      => array(),
				'cuantitativo'         => array(
					self::cifra( 'Sismos', (string) count( $filas ) ),
					self::cifra( 'Magnitud máxima', SAN_Analisis::num( $est['max'] ) ),
					self::cifra( 'M ≥ 5', (string) $m5 ),
					self::cifra( 'Más cercano', $cerc ? SAN_Analisis::con_unidad( $cerc['distancia_km'], 'km', 0 ) : '—' ),
				),
				'resumen_cuantitativo' => sprintf( 'Magnitud media %s; mediana %s.', SAN_Analisis::num( $est['media'] ), SAN_Analisis::num( $est['mediana'] ) ),
				'metodo'               => 'Servicio FDSN del USGS (radio y magnitud mínima configurables).',
			),
			$r
		);
	}

	/** @return array */
	public static function usgs_dispersion() {
		$r = self::usgs_mapa();
		if ( empty( $r['ok'] ) ) {
			return $r;
		}
		$r['config'] = array(
			'x'           => 'distancia_km',
			'y'           => 'magnitud',
			'id'          => 'id',
			'etiqueta'    => 'titulo',
			'color_campo' => 'color',
			'unidad'      => '',
			'decimales'   => 1,
			'etiqueta_x'  => 'Distancia a Pasto (km)',
			'etiqueta_y'  => 'Magnitud',
			'tooltip'     => array( array( 'Magnitud', 'magnitud', '', 1 ), array( 'Distancia', 'distancia_km', 'km', 0 ), array( 'Profundidad', 'profundidad', 'km', 0 ), array( 'Fecha (UTC)', 'fecha_utc' ) ),
		);
		return $r;
	}
}
