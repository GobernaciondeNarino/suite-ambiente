<?php
/**
 * Visualizaciones del océano Pacífico nariñense (Open-Meteo Marine).
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Viz_Oceano extends SAN_Viz {

	const F = 'openmeteo_marino';

	/**
	 * Definiciones.
	 *
	 * @return array
	 */
	public static function definiciones() {
		$c = __CLASS__;
		return array(
			'oceano_oleaje'      => array(
				'titulo'      => 'Altura del oleaje (7 días)',
				'fuente'      => self::F,
				'subgrupo'    => 'Oleaje',
				'descripcion' => 'Altura significativa de las olas pronosticada hora a hora frente a Tumaco y a Sanquianga.',
				'lectura'     => 'La altura significativa es el promedio del tercio de olas más altas; olas individuales pueden duplicarla.',
				'tipo'        => 'line',
				'tipos'       => array( 'line', 'area' ),
				'procesador'  => array( $c, 'oleaje' ),
			),
			'oceano_rosa_oleaje' => array(
				'titulo'      => 'Dirección del oleaje en Tumaco',
				'fuente'      => self::F,
				'subgrupo'    => 'Oleaje',
				'descripcion' => 'Rosa con la dirección de procedencia del oleaje frente a Tumaco y su altura, a partir del pronóstico horario.',
				'lectura'     => 'Los pétalos indican de dónde viene el oleaje; los colores, su altura.',
				'tipo'        => 'rosa',
				'tipos'       => array( 'rosa' ),
				'procesador'  => array( $c, 'rosa_oleaje' ),
			),
			'oceano_temperatura' => array(
				'titulo'      => 'Temperatura superficial del mar',
				'fuente'      => self::F,
				'subgrupo'    => 'Temperatura del mar',
				'descripcion' => 'Temperatura del agua en superficie frente a Tumaco y a Sanquianga (ayer y 7 días de pronóstico).',
				'lectura'     => 'El mar cálido favorece la evaporación y la lluvia en el litoral; aguas anormalmente cálidas pueden asociarse a El Niño.',
				'tipo'        => 'line',
				'tipos'       => array( 'line', 'area' ),
				'procesador'  => array( $c, 'temperatura' ),
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
			'oceano_horario' => array(
				'titulo'      => 'Oleaje y temperatura del mar (horario)',
				'descripcion' => 'Serie horaria (ayer + 7 días) frente a Tumaco y Sanquianga: altura, periodo y dirección del oleaje, mar de fondo y temperatura superficial.',
				'fuente'      => self::F,
				'formatos'    => array( 'json', 'csv' ),
				'campos'      => array(
					'punto'       => 'Punto costa afuera',
					'fecha'       => 'Fecha y hora local',
					'ola'         => 'Altura significativa (m)',
					'periodo'     => 'Periodo (s)',
					'direccion'   => 'Dirección de procedencia (grados)',
					'mar_fondo'   => 'Altura del mar de fondo (m)',
					'temperatura' => 'Temperatura superficial del mar (°C)',
				),
				'generador'   => array( __CLASS__, 'gen_horario' ),
			),
		);
	}

	/**
	 * Filas horarias.
	 *
	 * @return array { resp, filas }
	 */
	private static function filas() {
		$resp = SAN_Fuentes::obtener( self::F )->puntos();
		if ( ! $resp['ok'] ) {
			return array(
				'resp'  => $resp,
				'filas' => array(),
			);
		}
		$filas = array();
		foreach ( SAN_Fuente_Openmeteo_Marino::PUNTOS as $k => $p ) {
			foreach ( SAN_Fuente_Openmeteo::filas( $resp['datos'][ $k ]['hourly'] ?? array() ) as $h ) {
				$filas[] = array(
					'punto'       => $p['nombre'],
					'clave'       => $k,
					'fecha'       => $h['time'],
					'ola'         => $h['wave_height'],
					'periodo'     => $h['wave_period'],
					'direccion'   => $h['wave_direction'],
					'mar_fondo'   => $h['swell_wave_height'],
					'temperatura' => $h['sea_surface_temperature'],
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
	public static function gen_horario() {
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
	 * Serie de una variable por punto.
	 *
	 * @param array  $filas Filas.
	 * @param string $campo Campo.
	 * @return array
	 */
	private static function serie( array $filas, $campo ) {
		$out = array();
		foreach ( $filas as $f ) {
			if ( null !== $f[ $campo ] ) {
				$out[] = array(
					'fecha' => $f['fecha'],
					'serie' => $f['punto'],
					'valor' => $f[ $campo ],
				);
			}
		}
		return $out;
	}

	/**
	 * Oleaje.
	 *
	 * @return array
	 */
	public static function oleaje() {
		$a = self::filas();
		if ( ! $a['resp']['ok'] ) {
			return SAN_Catalogo::fallo( $a['resp'] );
		}
		$tum   = array_values(
			array_filter(
				$a['filas'],
				function ( $f ) {
					return 'tumaco' === $f['clave'];
				}
			)
		);
		$est   = SAN_Analisis::estadisticos( array_column( $tum, 'ola' ) );
		$pico  = SAN_Analisis::extremo( $a['filas'], 'ola', true );
		$ep    = SAN_Analisis::estadisticos( array_column( $tum, 'periodo' ) );
		$mar   = SAN_Umbrales::oleaje( $pico['ola'] );
		$tend  = SAN_Analisis::tendencia( array_column( $tum, 'ola' ) );

		return self::ok(
			self::serie( $a['filas'], 'ola' ),
			array(
				'x'          => 'fecha',
				'y'          => 'valor',
				'grupo'      => 'serie',
				'tiempo'     => true,
				'hora'       => true,
				'unidad'     => 'm',
				'decimales'  => 2,
				'etiqueta_x' => 'Fecha y hora',
				'etiqueta_y' => 'Altura significativa (m)',
			),
			array(
				'nivel'                => $mar['nivel'],
				'titular'              => sprintf( 'Oleaje máximo previsto de %s en %s el %s: %s.', SAN_Analisis::con_unidad( $pico['ola'], 'm', 2 ), $pico['punto'], SAN_Analisis::fecha( $pico['fecha'], true ), $mar['nombre'] ),
				'cualitativo'          => array(
					sprintf( 'Frente a Tumaco la ola promedio es de %s con periodos de %s: %s.', SAN_Analisis::con_unidad( $est['media'], 'm', 2 ), SAN_Analisis::con_unidad( $ep['media'], 's', 0 ), $ep['media'] >= 12 ? 'mar de fondo de largo recorrido, que rompe con fuerza en la costa' : 'oleaje local generado por el viento' ),
					sprintf( 'La tendencia de la semana es %s.', SAN_Analisis::frase_tendencia( $tend['pendiente'] * 24, $est['desviacion'] ?: 0.1, 'm', 'día' ) ),
				),
				'recomendaciones'      => in_array( $mar['nivel'], array( 'alerta', 'critico' ), true ) ? array( 'Pesca artesanal y transporte fluvial-marítimo: extremar precauciones y consultar a la Capitanía de Puerto de Tumaco (DIMAR).' ) : array(),
				'cuantitativo'         => array(
					self::cifra( 'Máximo', SAN_Analisis::con_unidad( $pico['ola'], 'm', 2 ), $pico['punto'] ),
					self::cifra( 'Media Tumaco', SAN_Analisis::con_unidad( $est['media'], 'm', 2 ) ),
					self::cifra( 'Periodo medio', SAN_Analisis::con_unidad( $ep['media'], 's', 1 ) ),
					self::cifra( 'P90 Tumaco', SAN_Analisis::con_unidad( $est['p90'], 'm', 2 ) ),
				),
				'resumen_cuantitativo' => sprintf( '%d horas analizadas por punto; desviación estándar en Tumaco %s.', $est['n'], SAN_Analisis::con_unidad( $est['desviacion'], 'm', 2 ) ),
				'metodo'               => 'wave_height horario (modelos de oleaje combinados por Open-Meteo); estado del mar por escala Douglas simplificada.',
			),
			$a['resp']
		);
	}

	/**
	 * Rosa del oleaje en Tumaco.
	 *
	 * @return array
	 */
	public static function rosa_oleaje() {
		$a = self::filas();
		if ( ! $a['resp']['ok'] ) {
			return SAN_Catalogo::fallo( $a['resp'] );
		}
		$sect   = array( 'N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE', 'S', 'SSO', 'SO', 'OSO', 'O', 'ONO', 'NO', 'NNO' );
		$rangos = array( array( 0, 0.5, 'Menos de 0,5 m' ), array( 0.5, 1, '0,5–1 m' ), array( 1, 1.5, '1–1,5 m' ), array( 1.5, 99, 'Más de 1,5 m' ) );
		$cuenta = array();
		$porsec = array();
		$n      = 0;
		foreach ( $a['filas'] as $f ) {
			if ( 'tumaco' !== $f['clave'] || null === $f['direccion'] || null === $f['ola'] ) {
				continue;
			}
			$s = $sect[ (int) round( fmod( (float) $f['direccion'] + 360, 360 ) / 22.5 ) % 16 ];
			foreach ( $rangos as $r ) {
				if ( $f['ola'] >= $r[0] && $f['ola'] < $r[1] ) {
					$cuenta[ $s ][ $r[2] ] = ( $cuenta[ $s ][ $r[2] ] ?? 0 ) + 1;
					break;
				}
			}
			$porsec[ $s ] = ( $porsec[ $s ] ?? 0 ) + 1;
			++$n;
		}
		if ( ! $n ) {
			return array(
				'ok'    => false,
				'error' => 'Sin datos de dirección del oleaje.',
			);
		}
		$filas = array();
		foreach ( $sect as $s ) {
			foreach ( $rangos as $r ) {
				if ( isset( $cuenta[ $s ][ $r[2] ] ) ) {
					$filas[] = array(
						'sector'     => $s,
						'altura'     => $r[2],
						'frecuencia' => round( $cuenta[ $s ][ $r[2] ] / $n * 100, 1 ),
					);
				}
			}
		}
		arsort( $porsec );
		$dom = (string) array_key_first( $porsec );

		return self::ok(
			$filas,
			array(
				'direccion' => 'sector',
				'grupo'     => 'altura',
				'valor'     => 'frecuencia',
				'sectores'  => $sect,
				'unidad'    => '%',
			),
			array(
				'nivel'                => 'info',
				'titular'              => sprintf( 'El oleaje llega a Tumaco principalmente del %s (%s de las horas).', $dom, SAN_Analisis::num( $porsec[ $dom ] / $n * 100, 0 ) . ' %' ),
				'cualitativo'          => array( 'El oleaje del suroeste suele ser mar de fondo generado en el Pacífico sur; el del noroeste, oleaje local asociado a vientos del Chocó.' ),
				'recomendaciones'      => array(),
				'cuantitativo'         => array(
					self::cifra( 'Dirección dominante', $dom ),
					self::cifra( 'Horas analizadas', (string) $n ),
					self::cifra( 'Sectores con oleaje', (string) count( $porsec ), 'de 16' ),
				),
				'resumen_cuantitativo' => 'Frecuencias en % de las horas por sector de 22,5° y rango de altura.',
				'metodo'               => 'wave_direction y wave_height horarios en la bahía de Tumaco.',
			),
			$a['resp']
		);
	}

	/**
	 * Temperatura superficial del mar.
	 *
	 * @return array
	 */
	public static function temperatura() {
		$a = self::filas();
		if ( ! $a['resp']['ok'] ) {
			return SAN_Catalogo::fallo( $a['resp'] );
		}
		$est  = SAN_Analisis::estadisticos( array_column( $a['filas'], 'temperatura' ) );
		$tum  = array_column(
			array_filter(
				$a['filas'],
				function ( $f ) {
					return 'tumaco' === $f['clave'];
				}
			),
			'temperatura'
		);
		$et   = SAN_Analisis::estadisticos( $tum );
		$tend = SAN_Analisis::tendencia( $tum );

		return self::ok(
			self::serie( $a['filas'], 'temperatura' ),
			array(
				'x'          => 'fecha',
				'y'          => 'valor',
				'grupo'      => 'serie',
				'tiempo'     => true,
				'hora'       => true,
				'unidad'     => '°C',
				'decimales'  => 1,
				'etiqueta_x' => 'Fecha y hora',
				'etiqueta_y' => 'Temperatura del mar (°C)',
			),
			array(
				'nivel'                => $et['media'] >= 29 ? 'moderado' : 'info',
				'titular'              => sprintf( 'El mar frente a Tumaco está en %s en promedio (rango %s a %s).', SAN_Analisis::con_unidad( $et['media'], '°C' ), SAN_Analisis::con_unidad( $et['min'], '°C' ), SAN_Analisis::con_unidad( $et['max'], '°C' ) ),
				'cualitativo'          => array(
					$et['media'] >= 28 ? 'Aguas cálidas (≥ 28 °C): favorecen lluvias intensas en el litoral y estrés térmico en arrecifes y manglares.' : 'Temperaturas dentro del rango habitual del Pacífico colombiano (26–28 °C).',
					sprintf( 'La tendencia semanal es %s. Para diagnosticar El Niño se requieren anomalías sostenidas frente a la climatología, no valores de pocos días.', SAN_Analisis::frase_tendencia( $tend['pendiente'] * 24, $et['desviacion'] ?: 0.1, '°C', 'día' ) ),
				),
				'recomendaciones'      => array(),
				'cuantitativo'         => array(
					self::cifra( 'Media Tumaco', SAN_Analisis::con_unidad( $et['media'], '°C' ) ),
					self::cifra( 'Media general', SAN_Analisis::con_unidad( $est['media'], '°C' ) ),
					self::cifra( 'Máxima', SAN_Analisis::con_unidad( $est['max'], '°C' ) ),
					self::cifra( 'Mínima', SAN_Analisis::con_unidad( $est['min'], '°C' ) ),
				),
				'resumen_cuantitativo' => sprintf( 'Oscilación en Tumaco: %s (desv. est. %s).', SAN_Analisis::con_unidad( $et['rango'], '°C' ), SAN_Analisis::con_unidad( $et['desviacion'], '°C', 2 ) ),
				'metodo'               => 'sea_surface_temperature horaria del modelo marino.',
			),
			$a['resp']
		);
	}
}
