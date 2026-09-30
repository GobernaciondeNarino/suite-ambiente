<?php
/**
 * Visualizaciones de caudal de ríos (GloFAS vía Open-Meteo).
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Viz_Agua extends SAN_Viz {

	const F = 'openmeteo_hidro';

	/**
	 * Definiciones.
	 *
	 * @return array
	 */
	public static function definiciones() {
		$c = __CLASS__;
		return array(
			'hidro_caudal_rios' => array(
				'titulo'      => 'Caudal de los ríos frente a su media histórica',
				'fuente'      => self::F,
				'subgrupo'    => 'Caudales',
				'descripcion' => 'Caudal diario de los ríos Patía, Mira y Guáitara expresado como porcentaje de su media histórica para esa fecha, desde hace 30 días hasta 30 días de pronóstico.',
				'lectura'     => '100 % es un día normal. Por encima de 100 % el río lleva más agua de lo habitual; por debajo, menos. Expresarlo en porcentaje permite comparar ríos de tamaños muy distintos.',
				'tipo'        => 'line',
				'tipos'       => array( 'line', 'area' ),
				'procesador'  => array( $c, 'caudal_rios' ),
			),
			'hidro_estado_rios' => array(
				'titulo'      => 'Estado actual de los ríos',
				'fuente'      => self::F,
				'subgrupo'    => 'Caudales',
				'descripcion' => 'Caudal de hoy de cada río comparado con el percentil 75 de su serie histórica (umbral de caudal alto).',
				'lectura'     => 'Una barra por encima de 100 % indica que el río supera su umbral de caudal alto: condición de vigilancia por posibles desbordamientos.',
				'tipo'        => 'barh',
				'tipos'       => array( 'barh', 'bar', 'radar' ),
				'procesador'  => array( $c, 'estado_rios' ),
			),
		);
	}

	/**
	 * Recursos de datos abiertos.
	 *
	 * @return array
	 */
	public static function recursos() {
		return array(
			'caudal_rios' => array(
				'titulo'      => 'Caudal diario de los ríos (GloFAS)',
				'descripcion' => 'Caudal diario simulado (m³/s) de los ríos Patía, Mira y Guáitara: 30 días pasados y 30 de pronóstico, con media, mediana, mínimo, máximo y percentiles 25 y 75 históricos.',
				'fuente'      => self::F,
				'formatos'    => array( 'json', 'csv' ),
				'campos'      => array(
					'rio'       => 'Río (punto de la rejilla GloFAS)',
					'lat'       => 'Latitud del punto',
					'lon'       => 'Longitud del punto',
					'fecha'     => 'Fecha',
					'caudal'    => 'Caudal (m³/s)',
					'media'     => 'Media histórica (m³/s)',
					'mediana'   => 'Mediana histórica (m³/s)',
					'p25'       => 'Percentil 25 (m³/s)',
					'p75'       => 'Percentil 75 (m³/s)',
					'maximo'    => 'Máximo histórico del día (m³/s)',
					'minimo'    => 'Mínimo histórico del día (m³/s)',
					'pronostico' => 'true si la fecha es futura',
				),
				'generador'   => array( __CLASS__, 'gen_caudal' ),
			),
		);
	}

	/**
	 * Filas planas por río y día.
	 *
	 * @return array { resp, filas }
	 */
	private static function filas() {
		$resp = SAN_Fuentes::obtener( self::F )->rios();
		if ( ! $resp['ok'] ) {
			return array(
				'resp'  => $resp,
				'filas' => array(),
			);
		}
		$hoy   = self::hoy();
		$filas = array();
		foreach ( SAN_Fuente_Openmeteo_Hidro::RIOS as $k => $rio ) {
			$d = $resp['datos'][ $k ]['daily'] ?? array();
			foreach ( SAN_Fuente_Openmeteo::filas( $d ) as $f ) {
				if ( null === $f['river_discharge'] ) {
					continue;
				}
				$filas[] = array(
					'rio'        => $rio['nombre'],
					'clave'      => $k,
					'lat'        => $rio['lat'],
					'lon'        => $rio['lon'],
					'fecha'      => $f['time'],
					'caudal'     => $f['river_discharge'],
					'media'      => $f['river_discharge_mean'],
					'mediana'    => $f['river_discharge_median'],
					'p25'        => $f['river_discharge_p25'],
					'p75'        => $f['river_discharge_p75'],
					'maximo'     => $f['river_discharge_max'],
					'minimo'     => $f['river_discharge_min'],
					'pronostico' => $f['time'] > $hoy,
				);
			}
		}
		return array(
			'resp'  => $resp,
			'filas' => $filas,
		);
	}

	/**
	 * Generador de datos abiertos.
	 *
	 * @return array
	 */
	public static function gen_caudal() {
		$a = self::filas();
		return array(
			'ok'          => $a['resp']['ok'],
			'filas'       => array_map(
				function ( $f ) {
					unset( $f['clave'] );
					return $f;
				},
				$a['filas']
			),
			'actualizado' => $a['resp']['actualizado'],
			'vencido'     => $a['resp']['vencido'],
			'error'       => $a['resp']['error'],
		);
	}

	/**
	 * Caudal relativo a la media.
	 *
	 * @return array
	 */
	public static function caudal_rios() {
		$a = self::filas();
		if ( ! $a['resp']['ok'] ) {
			return SAN_Catalogo::fallo( $a['resp'] );
		}
		$datos = array();
		$picos = array();
		$hoy   = self::hoy();
		foreach ( $a['filas'] as $f ) {
			if ( ! $f['media'] ) {
				continue;
			}
			$pct     = $f['caudal'] / $f['media'] * 100;
			$datos[] = array(
				'fecha'   => $f['fecha'],
				'serie'   => $f['rio'],
				'valor'   => round( $pct, 1 ),
				'caudal'  => $f['caudal'],
				'media'   => $f['media'],
			);
			if ( $f['fecha'] >= $hoy && ( ! isset( $picos[ $f['rio'] ] ) || $pct > $picos[ $f['rio'] ]['pct'] ) ) {
				$picos[ $f['rio'] ] = array(
					'pct'    => $pct,
					'fecha'  => $f['fecha'],
					'caudal' => $f['caudal'],
				);
			}
		}
		$frases = array();
		$alto   = 0;
		foreach ( $picos as $rio => $p ) {
			$frases[] = sprintf( '%s: máximo pronosticado de %s (%s de su media) el %s', $rio, SAN_Analisis::con_unidad( $p['caudal'], 'm³/s', 0 ), SAN_Analisis::num( $p['pct'], 0 ) . ' %', SAN_Analisis::fecha( $p['fecha'] ) );
			if ( $p['pct'] >= 150 ) {
				++$alto;
			}
		}
		$hoy_vals = array_filter(
			$datos,
			function ( $d ) use ( $hoy ) {
				return $d['fecha'] === $hoy;
			}
		);
		$est      = SAN_Analisis::estadisticos( array_column( $hoy_vals, 'valor' ) );

		return self::ok(
			$datos,
			array(
				'x'          => 'fecha',
				'y'          => 'valor',
				'grupo'      => 'serie',
				'tiempo'     => true,
				'unidad'     => '%',
				'decimales'  => 0,
				'etiqueta_x' => 'Fecha',
				'etiqueta_y' => 'Caudal (% de la media histórica)',
				'tooltip'    => array( array( '% de la media', 'valor', '%', 0 ), array( 'Caudal', 'caudal', 'm³/s', 0 ), array( 'Media', 'media', 'm³/s', 0 ) ),
			),
			array(
				'nivel'                => $alto ? 'alerta' : ( $est['max'] >= 120 ? 'moderado' : 'bueno' ),
				'titular'              => sprintf( 'Hoy los ríos corren en promedio al %s de su caudal medio; %s el 150 %% en los próximos 30 días.', SAN_Analisis::num( $est['media'], 0 ) . ' %', 0 === $alto ? 'ninguno superaría' : ( 1 === $alto ? 'un río superaría' : $alto . ' ríos superarían' ) ),
				'cualitativo'          => array_merge( array( implode( '; ', $frases ) . '.' ), array( 'GloFAS simula el caudal a partir de la lluvia y el estado del suelo: es una guía de tendencia regional, no reemplaza las mediciones en estaciones hidrológicas ni las alertas oficiales del IDEAM.' ) ),
				'recomendaciones'      => $alto ? array( 'Revisar los boletines hidrológicos del IDEAM y alertar a las comunidades ribereñas de las partes bajas.' ) : array(),
				'cuantitativo'         => array_values(
					array_map(
						function ( $rio, $p ) {
							return self::cifra( $rio, SAN_Analisis::num( $p['pct'], 0 ) . ' %', 'pico ' . SAN_Analisis::fecha( $p['fecha'] ) );
						},
						array_keys( $picos ),
						$picos
					)
				),
				'resumen_cuantitativo' => sprintf( 'Hoy: mínimo %s y máximo %s de la media entre los %d ríos.', SAN_Analisis::num( $est['min'], 0 ) . ' %', SAN_Analisis::num( $est['max'], 0 ) . ' %', $est['n'] ),
				'metodo'               => 'river_discharge / river_discharge_mean × 100 (GloFAS v4, climatología del reanálisis).',
			),
			$a['resp'],
			'Caudal simulado (GloFAS).'
		);
	}

	/**
	 * Estado actual frente al percentil 75.
	 *
	 * @return array
	 */
	public static function estado_rios() {
		$a = self::filas();
		if ( ! $a['resp']['ok'] ) {
			return SAN_Catalogo::fallo( $a['resp'] );
		}
		$hoy   = self::hoy();
		$datos = array();
		foreach ( $a['filas'] as $f ) {
			if ( $f['fecha'] !== $hoy || ! $f['p75'] ) {
				continue;
			}
			$ratio   = $f['mediana'] ? $f['caudal'] / $f['mediana'] : null;
			$estado  = SAN_Umbrales::caudal( (float) $ratio );
			$datos[] = array(
				'rio'       => $f['rio'],
				'indice'    => round( $f['caudal'] / $f['p75'] * 100, 1 ),
				'caudal'    => $f['caudal'],
				'p75'       => $f['p75'],
				'mediana'   => $f['mediana'],
				'estado'    => $estado['nombre'],
				'nivel'     => $estado['nivel'],
				'serie'     => 'Hoy',
			);
		}
		usort(
			$datos,
			function ( $x, $y ) {
				return $y['indice'] <=> $x['indice'];
			}
		);
		$sobre = array_filter(
			$datos,
			function ( $d ) {
				return $d['indice'] > 100;
			}
		);

		return self::ok(
			$datos,
			array(
				'x'          => 'rio',
				'y'          => 'indice',
				'etiqueta'   => 'rio',
				'grupo'      => 'serie',
				'unidad'     => '%',
				'decimales'  => 0,
				'etiqueta_y' => 'Caudal (% del percentil 75)',
				'tooltip'    => array( array( '% del P75', 'indice', '%', 0 ), array( 'Caudal', 'caudal', 'm³/s', 0 ), array( 'Mediana', 'mediana', 'm³/s', 0 ), array( 'Estado', 'estado' ) ),
			),
			array(
				'nivel'                => self::peor( array_column( $datos, 'nivel' ) ),
				'titular'              => $sobre ? sprintf( '%d río%s por encima de su umbral de caudal alto (percentil 75).', count( $sobre ), 1 === count( $sobre ) ? '' : 's' ) : 'Ningún río supera hoy su umbral de caudal alto.',
				'cualitativo'          => array_map(
					function ( $d ) {
						return sprintf( '%s: %s (%s, %s del P75).', $d['rio'], $d['estado'], SAN_Analisis::con_unidad( $d['caudal'], 'm³/s', 0 ), SAN_Analisis::num( $d['indice'], 0 ) . ' %' );
					},
					$datos
				),
				'recomendaciones'      => array(),
				'cuantitativo'         => array_map(
					function ( $d ) {
						return self::cifra( $d['rio'], SAN_Analisis::con_unidad( $d['caudal'], 'm³/s', 0 ), SAN_Analisis::num( $d['indice'], 0 ) . ' % del P75' );
					},
					$datos
				),
				'resumen_cuantitativo' => 'Índice = caudal de hoy / percentil 75 histórico × 100.',
				'metodo'               => 'GloFAS v4; estado según la razón caudal / mediana (clasificación operativa del plugin).',
			),
			$a['resp'],
			'Caudal simulado (GloFAS).'
		);
	}
}
