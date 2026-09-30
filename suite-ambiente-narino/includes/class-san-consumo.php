<?php
/**
 * Consumo del cupo de Open-Meteo.
 *
 * El plan gratuito de Open-Meteo admite menos de 10 000 llamadas al día,
 * 5 000 por hora, 600 por minuto y 300 000 al mes, y cuenta cada coordenada
 * como una llamada. Una petición con más de 10 variables o más de 2 semanas
 * de datos cuenta como varias, de forma fraccionaria (15 variables = 1,5;
 * 4 semanas = 2). Esta clase aplica esas reglas a cada petición correcta y
 * guarda los totales por hora y por día (UTC) para mostrarlos en el panel.
 *
 * Es una medición del lado del plugin: si otras aplicaciones del mismo
 * servidor consultan Open-Meteo, su consumo no aparece aquí.
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Consumo {

	const OPCION = 'san_consumo';

	/** Límites del plan gratuito (https://open-meteo.com/en/terms). */
	const LIMITES = array(
		'minuto' => 600,
		'hora'   => 5000,
		'dia'    => 10000,
		'mes'    => 300000,
	);

	/** Días de pronóstico por defecto de cada API cuando no se indican. */
	const DIAS_DEFECTO = array(
		'api.open-meteo.com'             => 7,
		'air-quality-api.open-meteo.com' => 5,
		'marine-api.open-meteo.com'      => 7,
		'flood-api.open-meteo.com'       => 92,
	);

	/** Parámetros que listan variables. */
	const BLOQUES = array( 'current', 'minutely_15', 'hourly', 'daily' );

	/**
	 * ¿La URL es de Open-Meteo (plan gratuito o comercial)?
	 *
	 * @param string $url URL.
	 * @return bool
	 */
	public static function es_openmeteo( $url ) {
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$sufijo = '.open-meteo.com';
		return 'open-meteo.com' === $host || ( strlen( $host ) > strlen( $sufijo ) && substr( $host, -strlen( $sufijo ) ) === $sufijo );
	}

	/**
	 * Llamadas equivalentes de una petición según las reglas de Open-Meteo.
	 *
	 * @param string $url URL completa.
	 * @return float
	 */
	public static function peso( $url ) {
		$q = array();
		wp_parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $q );

		$lat       = isset( $q['latitude'] ) ? trim( (string) $q['latitude'] ) : '';
		$ubicacion = '' === $lat ? 1 : count( array_filter( array_map( 'trim', explode( ',', $lat ) ), 'strlen' ) );

		$variables = 0;
		foreach ( self::BLOQUES as $b ) {
			if ( ! empty( $q[ $b ] ) ) {
				$variables += count( array_filter( array_map( 'trim', explode( ',', (string) $q[ $b ] ) ), 'strlen' ) );
			}
		}

		$host = preg_replace( '/^customer-/', '', strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) ) );
		if ( ! empty( $q['start_date'] ) && ! empty( $q['end_date'] ) ) {
			$dias = 1 + (int) round( ( strtotime( (string) $q['end_date'] ) - strtotime( (string) $q['start_date'] ) ) / DAY_IN_SECONDS );
		} else {
			$dias = ( isset( $q['forecast_days'] ) ? (int) $q['forecast_days'] : ( self::DIAS_DEFECTO[ $host ] ?? 7 ) ) + ( isset( $q['past_days'] ) ? (int) $q['past_days'] : 0 );
		}

		return max( 1, $ubicacion ) * max( 1.0, $variables / 10 ) * max( 1.0, max( 1, $dias ) / 14 );
	}

	/**
	 * Suma una petición correcta al consumo.
	 *
	 * @param string $fuente Id de la fuente.
	 * @param string $url    URL consultada.
	 * @return void
	 */
	public static function registrar( $fuente, $url ) {
		if ( ! self::es_openmeteo( $url ) ) {
			return;
		}
		$peso = self::peso( $url );
		$d    = self::datos();
		$dia  = gmdate( 'Y-m-d' );
		$hora = gmdate( 'Y-m-d H' );
		$min  = gmdate( 'Y-m-d H:i' );

		$d['dias'][ $dia ]               = ( $d['dias'][ $dia ] ?? 0 ) + $peso;
		$d['horas'][ $hora ]             = ( $d['horas'][ $hora ] ?? 0 ) + $peso;
		$d['minutos'][ $min ]            = ( $d['minutos'][ $min ] ?? 0 ) + $peso;
		$d['peticiones'][ $dia ]         = ( $d['peticiones'][ $dia ] ?? 0 ) + 1;
		$d['fuentes'][ $dia ][ $fuente ] = ( $d['fuentes'][ $dia ][ $fuente ] ?? 0 ) + $peso;
		self::guardar( $d );
	}

	/**
	 * Cuenta una respuesta HTTP 429 (límite superado) de Open-Meteo.
	 *
	 * @param string $url URL consultada.
	 * @return void
	 */
	public static function registrar_rechazo( $url ) {
		if ( ! self::es_openmeteo( $url ) ) {
			return;
		}
		$d                     = self::datos();
		$dia                   = gmdate( 'Y-m-d' );
		$d['rechazos'][ $dia ] = ( $d['rechazos'][ $dia ] ?? 0 ) + 1;
		self::guardar( $d );
	}

	/**
	 * Resumen para el panel y WP-CLI.
	 *
	 * @return array
	 */
	public static function resumen() {
		$d    = self::datos();
		$dia  = gmdate( 'Y-m-d' );
		$hora = gmdate( 'Y-m-d H' );
		$min  = gmdate( 'Y-m-d H:i' );

		$ult30 = array();
		for ( $i = 29; $i >= 0; $i-- ) {
			$f           = gmdate( 'Y-m-d', time() - $i * DAY_IN_SECONDS );
			$ult30[ $f ] = round( (float) ( $d['dias'][ $f ] ?? 0 ), 1 );
		}
		$previos = array_slice( $ult30, 22, 7, true ); // Los 7 días anteriores a hoy.
		$con_uso = array_filter( $previos );

		$out = array(
			'minuto'      => round( (float) ( $d['minutos'][ $min ] ?? 0 ), 1 ),
			'hora'        => round( (float) ( $d['horas'][ $hora ] ?? 0 ), 1 ),
			'dia'         => round( (float) ( $d['dias'][ $dia ] ?? 0 ), 1 ),
			'mes'         => round( array_sum( $ult30 ), 1 ),
			'promedio_7d' => $con_uso ? round( array_sum( $con_uso ) / count( $con_uso ), 1 ) : null,
			'peticiones'  => (int) ( $d['peticiones'][ $dia ] ?? 0 ),
			'rechazos'    => (int) ( $d['rechazos'][ $dia ] ?? 0 ),
			'fuentes'     => array_map(
				function ( $v ) {
					return round( (float) $v, 1 );
				},
				(array) ( $d['fuentes'][ $dia ] ?? array() )
			),
			'dias'        => $ult30,
			'limites'     => self::LIMITES,
			'desde'       => (string) ( $d['desde'] ?? '' ),
		);
		foreach ( array( 'hora', 'dia', 'mes' ) as $k ) {
			$out[ 'pct_' . $k ] = round( 100 * $out[ $k ] / self::LIMITES[ $k ], 1 );
		}
		return $out;
	}

	/**
	 * Borra el historial de consumo.
	 *
	 * @return void
	 */
	public static function reiniciar() {
		delete_option( self::OPCION );
	}

	/**
	 * @return array
	 */
	private static function datos() {
		$d = get_option( self::OPCION, array() );
		$d = is_array( $d ) ? $d : array();
		foreach ( array( 'dias', 'horas', 'minutos', 'peticiones', 'fuentes', 'rechazos' ) as $k ) {
			$d[ $k ] = isset( $d[ $k ] ) && is_array( $d[ $k ] ) ? $d[ $k ] : array();
		}
		if ( empty( $d['desde'] ) ) {
			$d['desde'] = gmdate( 'Y-m-d H:i' );
		}
		return $d;
	}

	/**
	 * Guarda recortando el historial (35 días, 48 horas, 10 minutos).
	 *
	 * @param array $d Datos.
	 * @return void
	 */
	private static function guardar( array $d ) {
		$recortar = function ( array $a, $n ) {
			ksort( $a );
			return array_slice( $a, -$n, null, true );
		};
		$d['dias']       = $recortar( $d['dias'], 35 );
		$d['peticiones'] = $recortar( $d['peticiones'], 35 );
		$d['rechazos']   = $recortar( $d['rechazos'], 35 );
		$d['fuentes']    = $recortar( $d['fuentes'], 8 );
		$d['horas']      = $recortar( $d['horas'], 48 );
		$d['minutos']    = $recortar( $d['minutos'], 10 );
		update_option( self::OPCION, $d, false );
	}
}
