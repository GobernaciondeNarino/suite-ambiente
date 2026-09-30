<?php
/**
 * Visualizaciones de observaciones del IDEAM (estaciones automáticas).
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Viz_Ideam extends SAN_Viz {

	const F = 'ideam';

	/**
	 * Definiciones.
	 *
	 * @return array
	 */
	public static function definiciones() {
		$c = __CLASS__;
		return array(
			'ideam_lluvia_estaciones' => array(
				'titulo'      => 'Lluvia observada por estación',
				'fuente'      => self::F,
				'subgrupo'    => 'Precipitación observada',
				'descripcion' => 'Lluvia acumulada medida en cada estación automática del IDEAM en Nariño durante los últimos días.',
				'lectura'     => 'Son mediciones reales (pluviómetros), a diferencia de los pronósticos de modelo. Barras más largas = más lluvia caída.',
				'tipo'        => 'barh',
				'tipos'       => array( 'barh', 'bar', 'treemap' ),
				'procesador'  => array( $c, 'lluvia_estaciones' ),
			),
			'ideam_lluvia_diaria'     => array(
				'titulo'      => 'Lluvia diaria en la red de estaciones',
				'fuente'      => self::F,
				'subgrupo'    => 'Precipitación observada',
				'descripcion' => 'Promedio y máximo diarios de lluvia entre las estaciones del IDEAM en Nariño.',
				'lectura'     => 'El promedio muestra la lluvia generalizada; el máximo, los aguaceros locales más intensos.',
				'tipo'        => 'bar',
				'tipos'       => array( 'bar', 'line' ),
				'procesador'  => array( $c, 'lluvia_diaria' ),
			),
			'ideam_temperatura'       => array(
				'titulo'      => 'Temperatura observada por estación',
				'fuente'      => self::F,
				'subgrupo'    => 'Temperatura observada',
				'descripcion' => 'Distribución de las temperaturas medias diarias medidas en cada estación del IDEAM durante los últimos días.',
				'lectura'     => 'Cada caja resume los días de una estación; su posición refleja la altitud y su altura, la variabilidad.',
				'tipo'        => 'box',
				'tipos'       => array( 'box' ),
				'procesador'  => array( $c, 'temperatura' ),
			),
			'ideam_nivel_rios'        => array(
				'titulo'      => 'Nivel de los ríos en las estaciones hidrológicas',
				'fuente'      => self::F,
				'subgrupo'    => 'Nivel de ríos',
				'descripcion' => 'Nivel medio diario (m) medido en las estaciones hidrológicas automáticas del IDEAM en Nariño.',
				'lectura'     => 'Cada fila es una estación y cada columna un día: tonos más intensos indican niveles más altos respecto a los demás registros.',
				'tipo'        => 'calor',
				'tipos'       => array( 'calor' ),
				'procesador'  => array( $c, 'nivel' ),
			),
		);
	}

	/**
	 * Recursos.
	 *
	 * @return array
	 */
	public static function recursos() {
		$campos = array(
			'estacion'  => 'Nombre de la estación',
			'codigo'    => 'Código IDEAM',
			'municipio' => 'Municipio',
			'lat'       => 'Latitud',
			'lon'       => 'Longitud',
			'fecha'     => 'Día',
			'n'         => 'Número de observaciones del día',
			'ultima'    => 'Última observación del día',
		);
		return array(
			'ideam_precipitacion' => array(
				'titulo'      => 'Precipitación diaria observada (IDEAM)',
				'descripcion' => 'Lluvia total diaria por estación automática del IDEAM en Nariño.',
				'fuente'      => self::F,
				'formatos'    => array( 'json', 'csv', 'geojson' ),
				'campos'      => $campos + array( 'total' => 'Lluvia total del día (mm)' ),
				'generador'   => array( __CLASS__, 'gen_precipitacion' ),
			),
			'ideam_temperatura'   => array(
				'titulo'      => 'Temperatura diaria observada (IDEAM)',
				'descripcion' => 'Temperatura media, mínima y máxima diaria por estación automática del IDEAM en Nariño.',
				'fuente'      => self::F,
				'formatos'    => array( 'json', 'csv', 'geojson' ),
				'campos'      => $campos + array( 'media' => 'Media (°C)', 'minima' => 'Mínima (°C)', 'maxima' => 'Máxima (°C)' ),
				'generador'   => array( __CLASS__, 'gen_temperatura' ),
			),
			'ideam_nivel'         => array(
				'titulo'      => 'Nivel diario de ríos (IDEAM)',
				'descripcion' => 'Nivel medio, mínimo y máximo diario por estación hidrológica automática del IDEAM en Nariño.',
				'fuente'      => self::F,
				'formatos'    => array( 'json', 'csv', 'geojson' ),
				'campos'      => $campos + array( 'media' => 'Nivel medio (m)', 'minima' => 'Nivel mínimo (m)', 'maxima' => 'Nivel máximo (m)' ),
				'generador'   => array( __CLASS__, 'gen_nivel' ),
			),
		);
	}

	/**
	 * Adaptador para generadores.
	 *
	 * @param array $r Respuesta de la fuente.
	 * @return array
	 */
	private static function gen( array $r ) {
		return array(
			'ok'          => $r['ok'],
			'filas'       => (array) $r['datos'],
			'actualizado' => $r['actualizado'],
			'vencido'     => $r['vencido'],
			'error'       => $r['error'],
		);
	}

	/** @return array */
	public static function gen_precipitacion() {
		return self::gen( SAN_Fuentes::obtener( self::F )->precipitacion() );
	}

	/** @return array */
	public static function gen_temperatura() {
		return self::gen( SAN_Fuentes::obtener( self::F )->temperatura() );
	}

	/** @return array */
	public static function gen_nivel() {
		return self::gen( SAN_Fuentes::obtener( self::F )->nivel() );
	}

	/**
	 * Fecha más reciente con datos.
	 *
	 * @param array $filas Filas.
	 * @return string
	 */
	private static function ultima( array $filas ) {
		$u = '';
		foreach ( $filas as $f ) {
			$u = max( $u, (string) $f['ultima'] );
		}
		return $u;
	}

	/** @return array */
	public static function lluvia_estaciones() {
		$r = SAN_Fuentes::obtener( self::F )->precipitacion();
		if ( ! $r['ok'] ) {
			return SAN_Catalogo::fallo( $r );
		}
		$tot = array();
		$mun = array();
		foreach ( (array) $r['datos'] as $f ) {
			$tot[ $f['estacion'] ] = ( $tot[ $f['estacion'] ] ?? 0 ) + (float) $f['total'];
			$mun[ $f['estacion'] ] = $f['municipio'];
		}
		arsort( $tot );
		$datos = array();
		foreach ( $tot as $e => $v ) {
			$datos[] = array(
				'estacion'  => $e,
				'municipio' => $mun[ $e ],
				'lluvia'    => round( $v, 1 ),
			);
		}
		$est  = SAN_Analisis::estadisticos( array_values( $tot ) );
		$top  = $datos[0] ?? null;
		$secas = count(
			array_filter(
				$tot,
				function ( $v ) {
					return $v < 1;
				}
			)
		);
		$dias = count( array_unique( array_column( (array) $r['datos'], 'fecha' ) ) );
		return self::ok(
			$datos,
			array(
				'x'          => 'estacion',
				'y'          => 'lluvia',
				'etiqueta'   => 'estacion',
				'unidad'     => 'mm',
				'decimales'  => 1,
				'etiqueta_y' => 'Lluvia acumulada (mm)',
				'tooltip'    => array( array( 'Lluvia', 'lluvia', 'mm', 1 ), array( 'Municipio', 'municipio' ) ),
			),
			array(
				'nivel'                => $top && $top['lluvia'] >= 100 ? 'alerta' : 'info',
				'titular'              => $top ? sprintf( 'En %d días la estación más lluviosa fue %s (%s), con %s.', $dias, $top['estacion'], $top['municipio'], SAN_Analisis::con_unidad( $top['lluvia'], 'mm' ) ) : 'Sin datos de precipitación.',
				'cualitativo'          => array(
					sprintf( '%d de %d estaciones registraron menos de 1 mm en todo el periodo.', $secas, count( $tot ) ),
					sprintf( 'Datos crudos del IDEAM hasta %s (hora de Colombia), cargados una vez al día y sin validación; pueden contener errores de sensor.', self::ultima( (array) $r['datos'] ) ),
				),
				'recomendaciones'      => array(),
				'cuantitativo'         => array(
					self::cifra( 'Estaciones', (string) count( $tot ) ),
					self::cifra( 'Máximo', SAN_Analisis::con_unidad( $est['max'], 'mm' ), $top['estacion'] ?? '' ),
					self::cifra( 'Media', SAN_Analisis::con_unidad( $est['media'], 'mm' ) ),
					self::cifra( 'Mediana', SAN_Analisis::con_unidad( $est['mediana'], 'mm' ) ),
				),
				'resumen_cuantitativo' => sprintf( 'Periodo de %d días; desviación entre estaciones %s.', $dias, SAN_Analisis::con_unidad( $est['desviacion'], 'mm' ) ),
				'metodo'               => 'Suma de valorobservado (mm) por estación (dataset s54a-sgyg, agregado con SoQL).',
			),
			$r,
			'Datos crudos IDEAM sin validar.'
		);
	}

	/** @return array */
	public static function lluvia_diaria() {
		$r = SAN_Fuentes::obtener( self::F )->precipitacion();
		if ( ! $r['ok'] ) {
			return SAN_Catalogo::fallo( $r );
		}
		$por = array();
		foreach ( (array) $r['datos'] as $f ) {
			$por[ $f['fecha'] ][] = (float) $f['total'];
		}
		ksort( $por );
		$datos = array();
		foreach ( $por as $d => $vals ) {
			$datos[] = array( 'fecha' => $d, 'serie' => 'Promedio de estaciones', 'valor' => round( array_sum( $vals ) / count( $vals ), 1 ) );
			$datos[] = array( 'fecha' => $d, 'serie' => 'Estación más lluviosa', 'valor' => round( max( $vals ), 1 ) );
		}
		$prom = array_column(
			array_filter(
				$datos,
				function ( $x ) {
					return 'Promedio de estaciones' === $x['serie'];
				}
			),
			'valor'
		);
		$pico = SAN_Analisis::extremo( $datos, 'valor', true );
		return self::ok(
			$datos,
			array(
				'x'          => 'fecha',
				'y'          => 'valor',
				'grupo'      => 'serie',
				'tiempo'     => true,
				'unidad'     => 'mm',
				'decimales'  => 1,
				'etiqueta_x' => 'Día',
				'etiqueta_y' => 'Lluvia (mm)',
			),
			array(
				'nivel'                => $pico && $pico['valor'] >= 50 ? 'alerta' : 'info',
				'titular'              => $pico ? sprintf( 'El aguacero más fuerte medido fue de %s el %s.', SAN_Analisis::con_unidad( $pico['valor'], 'mm' ), SAN_Analisis::fecha( $pico['fecha'] ) ) : '',
				'cualitativo'          => array( sprintf( 'La lluvia promedio de la red fue de %s por día.', SAN_Analisis::con_unidad( SAN_Analisis::estadisticos( $prom )['media'], 'mm' ) ) ),
				'recomendaciones'      => array(),
				'cuantitativo'         => array(
					self::cifra( 'Días', (string) count( $por ) ),
					self::cifra( 'Promedio diario', SAN_Analisis::con_unidad( SAN_Analisis::estadisticos( $prom )['media'], 'mm' ) ),
					self::cifra( 'Máximo puntual', SAN_Analisis::con_unidad( $pico['valor'] ?? null, 'mm' ) ),
				),
				'resumen_cuantitativo' => 'Serie de promedio y máximo diarios entre estaciones.',
				'metodo'               => 'Totales diarios por estación (s54a-sgyg) agregados por día.',
			),
			$r,
			'Datos crudos IDEAM sin validar.'
		);
	}

	/** @return array */
	public static function temperatura() {
		$r = SAN_Fuentes::obtener( self::F )->temperatura();
		if ( ! $r['ok'] ) {
			return SAN_Catalogo::fallo( $r );
		}
		$datos = array();
		$med   = array();
		foreach ( (array) $r['datos'] as $f ) {
			if ( null === $f['media'] ) {
				continue;
			}
			$datos[]                 = array(
				'id'       => $f['codigo'] . '-' . $f['fecha'],
				'estacion' => $f['estacion'],
				'valor'    => $f['media'],
				'fecha'    => $f['fecha'],
				'minima'   => $f['minima'],
				'maxima'   => $f['maxima'],
			);
			$med[ $f['estacion'] ][] = (float) $f['media'];
		}
		$prom = array();
		foreach ( $med as $e => $v ) {
			$prom[ $e ] = array_sum( $v ) / count( $v );
		}
		arsort( $prom );
		$cal  = (string) array_key_first( $prom );
		$fri  = (string) array_key_last( $prom );
		$abs  = SAN_Analisis::extremo( (array) $r['datos'], 'minima', false );
		return self::ok(
			$datos,
			array(
				'x'          => 'estacion',
				'y'          => 'valor',
				'id'         => 'id',
				'unidad'     => '°C',
				'decimales'  => 1,
				'etiqueta_x' => 'Estación',
				'etiqueta_y' => 'Temperatura media diaria (°C)',
			),
			array(
				'nivel'                => $abs && $abs['minima'] <= 2 ? 'moderado' : 'info',
				'titular'              => $cal ? sprintf( 'La estación más cálida es %s (%s de media) y la más fría %s (%s).', $cal, SAN_Analisis::con_unidad( $prom[ $cal ], '°C' ), $fri, SAN_Analisis::con_unidad( $prom[ $fri ], '°C' ) ) : '',
				'cualitativo'          => array(
					$abs ? sprintf( 'La mínima absoluta medida fue %s en %s (%s).', SAN_Analisis::con_unidad( $abs['minima'], '°C' ), $abs['estacion'], $abs['fecha'] ) : '',
					'Las diferencias entre estaciones responden sobre todo a la altitud: del litoral a los páramos hay más de 20 °C de diferencia.',
				),
				'recomendaciones'      => array(),
				'cuantitativo'         => array(
					self::cifra( 'Estaciones', (string) count( $prom ) ),
					self::cifra( 'Más cálida', SAN_Analisis::con_unidad( $prom[ $cal ] ?? null, '°C' ), $cal ),
					self::cifra( 'Más fría', SAN_Analisis::con_unidad( $prom[ $fri ] ?? null, '°C' ), $fri ),
					self::cifra( 'Mínima absoluta', SAN_Analisis::con_unidad( $abs['minima'] ?? null, '°C' ) ),
				),
				'resumen_cuantitativo' => sprintf( '%d días-estación analizados.', count( $datos ) ),
				'metodo'               => 'avg/min/max diarios de valorobservado (sbwg-7ju4) por estación.',
			),
			$r,
			'Datos crudos IDEAM sin validar.'
		);
	}

	/** @return array */
	public static function nivel() {
		$r = SAN_Fuentes::obtener( self::F )->nivel();
		if ( ! $r['ok'] ) {
			return SAN_Catalogo::fallo( $r );
		}
		$datos = array();
		$por   = array();
		foreach ( (array) $r['datos'] as $f ) {
			if ( null === $f['media'] ) {
				continue;
			}
			$datos[]                  = array(
				'estacion' => $f['estacion'],
				'dia'      => SAN_Analisis::fecha( $f['fecha'] ),
				'fecha'    => $f['fecha'],
				'nivel'    => $f['media'],
				'maxima'   => $f['maxima'],
			);
			$por[ $f['estacion'] ][] = (float) $f['media'];
		}
		usort(
			$datos,
			function ( $a, $b ) {
				return strcmp( $a['fecha'], $b['fecha'] ) ?: strcmp( $a['estacion'], $b['estacion'] );
			}
		);
		$cambios = array();
		foreach ( $por as $e => $v ) {
			if ( count( $v ) >= 2 ) {
				$cambios[ $e ] = end( $v ) - reset( $v );
			}
		}
		arsort( $cambios );
		$sube = (string) array_key_first( $cambios );
		$max  = SAN_Analisis::extremo( (array) $r['datos'], 'maxima', true );
		return self::ok(
			$datos,
			array(
				'x'       => 'dia',
				'y'       => 'estacion',
				'valor'   => 'nivel',
				'unidad'  => 'm',
				'tono'    => 'azul',
				'tooltip' => array( array( 'Nivel medio', 'nivel', 'm', 2 ), array( 'Máximo del día', 'maxima', 'm', 2 ) ),
			),
			array(
				'nivel'                => $sube && $cambios[ $sube ] >= 0.5 ? 'moderado' : 'info',
				'titular'              => $sube ? sprintf( 'El mayor ascenso del periodo se registra en %s (%s).', $sube, ( $cambios[ $sube ] >= 0 ? '+' : '−' ) . SAN_Analisis::con_unidad( abs( $cambios[ $sube ] ), 'm', 2 ) ) : '',
				'cualitativo'          => array(
					$max ? sprintf( 'El nivel máximo instantáneo fue %s en %s (%s).', SAN_Analisis::con_unidad( $max['maxima'], 'm', 2 ), $max['estacion'], $max['fecha'] ) : '',
					'Un nivel alto no implica desbordamiento: cada sección de río tiene su propia cota de alerta, definida por el IDEAM.',
				),
				'recomendaciones'      => array(),
				'cuantitativo'         => array_map(
					function ( $e, $c ) {
						return self::cifra( $e, ( $c >= 0 ? '+' : '−' ) . SAN_Analisis::con_unidad( abs( $c ), 'm', 2 ), 'cambio en el periodo' );
					},
					array_slice( array_keys( $cambios ), 0, 4 ),
					array_slice( $cambios, 0, 4 )
				),
				'resumen_cuantitativo' => sprintf( '%d estaciones hidrológicas con datos.', count( $por ) ),
				'metodo'               => 'Nivel instantáneo (bdmn-sqnh) promediado por día y estación; cambio = último día − primer día.',
			),
			$r,
			'Datos crudos IDEAM sin validar.'
		);
	}
}
