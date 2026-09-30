<?php
/**
 * Base común de las APIs de Open-Meteo (pronóstico, aire, inundaciones,
 * marino). Todas aceptan varias coordenadas en una sola petición y
 * devuelven un arreglo con un objeto por coordenada, en el mismo orden.
 *
 * Licencia CC BY 4.0: la atribución es obligatoria. El uso gratuito es no
 * comercial (≤ 10 000 llamadas/día; cada coordenada cuenta como una).
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

abstract class SAN_Fuente_Openmeteo extends SAN_Fuente {

	/** @return string */
	public function licencia() {
		return 'CC BY 4.0';
	}

	/**
	 * Parámetros de coordenadas para los 64 municipios (cabeceras).
	 *
	 * @return array { latitude, longitude, divipolas }
	 */
	protected function coords_municipios() {
		$lat = array();
		$lon = array();
		$div = array();
		foreach ( SAN_Municipios::todos() as $m ) {
			$lat[] = $m['lat'];
			$lon[] = $m['lon'];
			$div[] = $m['divipola'];
		}
		return array(
			'latitude'  => implode( ',', $lat ),
			'longitude' => implode( ',', $lon ),
			'divipolas' => $div,
		);
	}

	/**
	 * Construye la URL.
	 *
	 * @param string $base   URL base.
	 * @param array  $params Parámetros.
	 * @return string
	 */
	protected function url( $base, array $params ) {
		$params['timezone'] = $params['timezone'] ?? 'America/Bogota';
		// add_query_arg codifica los valores; las comas se conservan legibles.
		return str_replace( '%2C', ',', add_query_arg( array_map( 'rawurlencode', $params ), $base ) );
	}

	/**
	 * Normaliza la respuesta a lista (una coordenada devuelve objeto).
	 *
	 * @param mixed $datos Respuesta.
	 * @return array[]
	 */
	protected function lista( $datos ) {
		if ( ! is_array( $datos ) ) {
			return array();
		}
		return isset( $datos['latitude'] ) ? array( $datos ) : array_values( $datos );
	}

	/**
	 * Convierte bloques columnares {time:[], var:[]} en filas.
	 *
	 * @param array $bloque Bloque hourly/daily.
	 * @return array[]
	 */
	public static function filas( array $bloque ) {
		$filas = array();
		$n     = isset( $bloque['time'] ) && is_array( $bloque['time'] ) ? count( $bloque['time'] ) : 0;
		for ( $i = 0; $i < $n; $i++ ) {
			$f = array();
			foreach ( $bloque as $k => $vals ) {
				$f[ $k ] = is_array( $vals ) ? ( $vals[ $i ] ?? null ) : null;
			}
			$filas[] = $f;
		}
		return $filas;
	}
}
