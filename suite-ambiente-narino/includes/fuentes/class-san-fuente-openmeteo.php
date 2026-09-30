<?php
/**
 * Base común de las APIs de Open-Meteo (pronóstico, aire, inundaciones,
 * marino). Todas aceptan varias coordenadas en una sola petición y
 * devuelven un arreglo con un objeto por coordenada, en el mismo orden.
 *
 * Licencia CC BY 4.0: la atribución es obligatoria. El uso gratuito es no
 * comercial (< 10 000 llamadas/día; cada coordenada cuenta como una; ver
 * SAN_Consumo). Con una llave de un plan comercial, las consultas van al
 * host `customer-…` equivalente con el parámetro `apikey`.
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

abstract class SAN_Fuente_Openmeteo extends SAN_Fuente {

	/**
	 * Caché mínima (min) de las consultas por municipio. Los pronósticos se
	 * recalculan cada 1 a 6 horas; con 3 h, aunque los visitantes recorran
	 * los 64 municipios cada hora, el consumo queda lejos del cupo diario.
	 */
	const TTL_MUNICIPIO = 180;

	/**
	 * TTL de una consulta por municipio.
	 *
	 * @return int Minutos.
	 */
	protected function ttl_municipio() {
		return max( self::TTL_MUNICIPIO, (int) $this->config()['ttl'] );
	}

	/** @return string */
	public function licencia() {
		return 'CC BY 4.0';
	}

	/**
	 * Host del plan gratuito y su equivalente comercial.
	 *
	 * @return string[]
	 */
	public function hosts() {
		$host = (string) wp_parse_url( static::BASE, PHP_URL_HOST );
		return array( $host, 'customer-' . $host );
	}

	/**
	 * Llave opcional: solo para un plan comercial de Open-Meteo.
	 *
	 * @return bool
	 */
	public function admite_clave() {
		return true;
	}

	/** @return string */
	public function url_clave() {
		return 'https://open-meteo.com/en/pricing';
	}

	/** @return array */
	public function texto_clave() {
		return array(
			'etiqueta' => 'Llave de plan comercial (opcional)',
			'enlace'   => 'Ver planes de Open-Meteo',
			'ayuda'    => 'Déjela vacía para usar el plan gratuito no comercial. Con una llave, el plugin consulta el servidor comercial (customer-…open-meteo.com).',
		);
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
		$clave              = (string) ( $this->config()['clave'] ?? '' );
		if ( '' !== $clave ) {
			$host             = (string) wp_parse_url( $base, PHP_URL_HOST );
			$base             = preg_replace( '#^https://' . preg_quote( $host, '#' ) . '/#', 'https://customer-' . $host . '/', $base );
			$params['apikey'] = $clave;
		}
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
