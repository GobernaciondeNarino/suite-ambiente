<?php
/**
 * Open-Meteo Air Quality: contaminantes del modelo CAMS global (Copernicus)
 * a ~40 km de resolución, con pronóstico de 5 días, actualizado varias
 * veces al día. Son estimaciones de modelo, no mediciones de estaciones:
 * sirven para orientar y comparar, no reemplazan la red SISAIRE.
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Fuente_Openmeteo_Aire extends SAN_Fuente_Openmeteo {

	const BASE = 'https://air-quality-api.open-meteo.com/v1/air-quality';

	/** Contaminantes consultados. */
	const CONTAMINANTES = 'pm2_5,pm10,ozone,nitrogen_dioxide,sulphur_dioxide,carbon_monoxide';

	/** @return string */
	public function id() {
		return 'openmeteo_aire';
	}

	/** @return string */
	public function nombre() {
		return 'Open-Meteo · Calidad del aire (CAMS)';
	}

	/** @return string */
	public function nombre_corto() {
		return 'Calidad del aire';
	}

	/** @return string */
	public function categoria() {
		return 'aire';
	}

	/** @return string */
	public function descripcion() {
		return 'Material particulado PM2.5 y PM10, ozono, dióxido de nitrógeno, dióxido de azufre, monóxido de carbono e índice UV estimados por el modelo CAMS de Copernicus, con pronóstico a 5 días.';
	}


	/** @return string */
	public function atribucion() {
		return 'Open-Meteo.com (CC BY 4.0) · CAMS / Copernicus';
	}

	/** @return string */
	public function url_doc() {
		return 'https://open-meteo.com/en/docs/air-quality-api';
	}

	/** @return string */
	public function frecuencia() {
		return 'Cada hora (modelo CAMS: varias corridas al día)';
	}

	/** @return array */
	public function config_defecto() {
		$c        = parent::config_defecto();
		$c['ttl'] = 60;
		return $c;
	}

	/**
	 * Últimas 24 h + hoy, horario, para los 64 municipios.
	 *
	 * @return array 'datos' => [divipola => objeto].
	 */
	public function municipios() {
		$c   = $this->coords_municipios();
		$url = $this->url(
			self::BASE,
			array(
				'latitude'      => $c['latitude'],
				'longitude'     => $c['longitude'],
				'current'       => self::CONTAMINANTES . ',uv_index,us_aqi',
				'hourly'        => 'pm2_5,pm10',
				'past_days'     => 1,
				'forecast_days' => 1,
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
	 * Serie horaria (ayer + 5 días) de un municipio.
	 *
	 * @param string $divipola Código.
	 * @return array
	 */
	public function serie( $divipola ) {
		$m = SAN_Municipios::por_divipola( $divipola );
		if ( ! $m ) {
			return $this->respuesta( false, null, '', false, 'Municipio desconocido', false );
		}
		$url = $this->url(
			self::BASE,
			array(
				'latitude'      => $m['lat'],
				'longitude'     => $m['lon'],
				'current'       => self::CONTAMINANTES . ',uv_index',
				'hourly'        => self::CONTAMINANTES . ',uv_index',
				'past_days'     => 1,
				'forecast_days' => 5,
			)
		);
		return $this->get_cacheado( 'serie:' . $divipola, $url, array(), $this->ttl_municipio() );
	}

	/** @return array */
	public function probar() {
		$url = $this->url(
			self::BASE,
			array(
				'latitude'  => '1.21235',
				'longitude' => '-77.2788',
				'current'   => 'pm2_5,pm10,us_aqi',
			)
		);
		$h   = SAN_Http::get( $this->id(), $url, array( 'timeout' => (int) $this->config()['timeout'] ) );
		$ok  = $h['ok'] && isset( $h['datos']['current']['pm2_5'] );
		return $this->resultado_prueba(
			$h,
			$ok,
			$ok ? sprintf( 'Pasto: PM2.5 %s µg/m³, PM10 %s µg/m³.', $h['datos']['current']['pm2_5'], $h['datos']['current']['pm10'] ?? '—' ) : 'La respuesta no trae current.pm2_5.',
			$ok ? (string) $h['datos']['current']['time'] : '',
			$ok ? array( 'current' => $h['datos']['current'] ) : array()
		);
	}
}
