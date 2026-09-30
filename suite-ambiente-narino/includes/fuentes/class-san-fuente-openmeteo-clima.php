<?php
/**
 * Open-Meteo Forecast: condiciones actuales y pronóstico (modelos ECMWF,
 * GFS, ICON… combinados). Se actualiza cada hora; los valores "current"
 * son de intervalos de 15 minutos.
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Fuente_Openmeteo_Clima extends SAN_Fuente_Openmeteo {

	const BASE = 'https://api.open-meteo.com/v1/forecast';

	/** @return string */
	public function id() {
		return 'openmeteo_clima';
	}

	/** @return string */
	public function nombre() {
		return 'Open-Meteo · Clima y pronóstico';
	}

	/** @return string */
	public function nombre_corto() {
		return 'Clima';
	}

	/** @return string */
	public function categoria() {
		return 'clima';
	}

	/** @return string */
	public function descripcion() {
		return 'Temperatura, lluvia, humedad, viento, índice UV y estado del tiempo actuales y pronosticados (hasta 16 días) para las cabeceras de los 64 municipios.';
	}

	/** @return string[] */
	public function hosts() {
		return array( 'api.open-meteo.com' );
	}

	/** @return string */
	public function atribucion() {
		return 'Open-Meteo.com (CC BY 4.0), modelos meteorológicos nacionales';
	}

	/** @return string */
	public function url_doc() {
		return 'https://open-meteo.com/en/docs';
	}

	/** @return string */
	public function frecuencia() {
		return 'Cada hora (actual: 15 min)';
	}

	/** @return array */
	public function config_defecto() {
		$c        = parent::config_defecto();
		$c['ttl'] = 60;
		return $c;
	}

	/** @return array */
	public function campos_params() {
		return array(
			'dias' => array(
				'etiqueta' => 'Días de pronóstico',
				'tipo'     => 'numero',
				'min'      => 3,
				'max'      => 16,
				'defecto'  => 10,
			),
		);
	}

	/**
	 * Estado actual y 7 días para los 64 municipios (una sola petición).
	 *
	 * @return array Respuesta con 'datos' => [divipola => objeto Open-Meteo].
	 */
	public function municipios() {
		$c   = $this->coords_municipios();
		$url = $this->url(
			self::BASE,
			array(
				'latitude'      => $c['latitude'],
				'longitude'     => $c['longitude'],
				'current'       => 'temperature_2m,relative_humidity_2m,apparent_temperature,precipitation,weather_code,cloud_cover,wind_speed_10m,wind_direction_10m,uv_index,is_day',
				'daily'         => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_sum,precipitation_probability_max,uv_index_max',
				'forecast_days' => 7,
			)
		);
		$r   = $this->get_cacheado( 'municipios', $url );
		if ( $r['ok'] ) {
			$out = array();
			foreach ( $this->lista( $r['datos'] ) as $i => $obj ) {
				if ( isset( $c['divipolas'][ $i ] ) ) {
					$out[ $c['divipolas'][ $i ] ] = $obj;
				}
			}
			$r['datos'] = $out;
		}
		return $r;
	}

	/**
	 * Pronóstico diario y horario de un municipio.
	 *
	 * @param string $divipola Código.
	 * @return array
	 */
	public function pronostico( $divipola ) {
		$m = SAN_Municipios::por_divipola( $divipola );
		if ( ! $m ) {
			return $this->respuesta( false, null, '', false, 'Municipio desconocido', false );
		}
		$url = $this->url(
			self::BASE,
			array(
				'latitude'       => $m['lat'],
				'longitude'      => $m['lon'],
				'current'        => 'temperature_2m,relative_humidity_2m,precipitation,weather_code,wind_speed_10m',
				'daily'          => 'weather_code,temperature_2m_max,temperature_2m_min,apparent_temperature_max,precipitation_sum,precipitation_probability_max,uv_index_max,wind_speed_10m_max,wind_direction_10m_dominant,sunshine_duration,et0_fao_evapotranspiration',
				'hourly'         => 'temperature_2m,relative_humidity_2m,precipitation,precipitation_probability,wind_speed_10m,wind_direction_10m',
				'forecast_days'  => (int) $this->param( 'dias', 10 ),
				'forecast_hours' => 168,
			)
		);
		return $this->get_cacheado( 'pronostico:' . $divipola . ':' . (int) $this->param( 'dias', 10 ), $url );
	}

	/** @return array */
	public function probar() {
		$url = $this->url(
			self::BASE,
			array(
				'latitude'  => '1.21235',
				'longitude' => '-77.2788',
				'current'   => 'temperature_2m,precipitation,weather_code',
			)
		);
		$h   = SAN_Http::get( $this->id(), $url, array( 'timeout' => (int) $this->config()['timeout'] ) );
		$ok  = $h['ok'] && isset( $h['datos']['current']['temperature_2m'] );
		return $this->resultado_prueba(
			$h,
			$ok,
			$ok ? sprintf( 'Pasto: %s °C, código de tiempo %s.', $h['datos']['current']['temperature_2m'], $h['datos']['current']['weather_code'] ?? '—' ) : 'La respuesta no trae current.temperature_2m.',
			$ok ? (string) $h['datos']['current']['time'] : '',
			$ok ? array( 'current' => $h['datos']['current'] ) : array()
		);
	}
}
