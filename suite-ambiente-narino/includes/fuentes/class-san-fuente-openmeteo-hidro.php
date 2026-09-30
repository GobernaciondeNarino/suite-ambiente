<?php
/**
 * Open-Meteo Flood API: caudal diario simulado por GloFAS v4 (Copernicus
 * Emergency Management Service) a 0,05°, con 30 días de pronóstico.
 *
 * Los puntos de los ríos se eligieron probando la rejilla de GloFAS: una
 * coordenada sobre tierra firme puede caer en una celda sin cauce (valores
 * nulos o de un afluente menor).
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Fuente_Openmeteo_Hidro extends SAN_Fuente_Openmeteo {

	const BASE = 'https://flood-api.open-meteo.com/v1/flood';

	/** Ríos principales (celdas verificadas el 2026-09-30). */
	const RIOS = array(
		'patia_bajo'  => array(
			'nombre' => 'Río Patía (bajo)',
			'lat'    => 2.0,
			'lon'    => -78.5,
		),
		'mira'        => array(
			'nombre' => 'Río Mira',
			'lat'    => 1.4,
			'lon'    => -78.7,
		),
		'patia_medio' => array(
			'nombre' => 'Río Patía (medio)',
			'lat'    => 1.9,
			'lon'    => -77.8,
		),
		'guaitara'    => array(
			'nombre' => 'Río Guáitara',
			'lat'    => 1.0,
			'lon'    => -77.5,
		),
	);

	/** @return string */
	public function id() {
		return 'openmeteo_hidro';
	}

	/** @return string */
	public function nombre() {
		return 'Open-Meteo · Caudal de ríos (GloFAS)';
	}

	/** @return string */
	public function nombre_corto() {
		return 'Ríos';
	}

	/** @return string */
	public function categoria() {
		return 'agua';
	}

	/** @return string */
	public function descripcion() {
		return 'Caudal diario simulado de los ríos Patía, Mira y Guáitara con el sistema global de alerta de inundaciones GloFAS, desde hace 30 días hasta 30 días de pronóstico, con su media y percentiles de referencia.';
	}

	/** @return string[] */
	public function hosts() {
		return array( 'flood-api.open-meteo.com' );
	}

	/** @return string */
	public function atribucion() {
		return 'Open-Meteo.com (CC BY 4.0) · GloFAS v4, Copernicus EMS';
	}

	/** @return string */
	public function url_doc() {
		return 'https://open-meteo.com/en/docs/flood-api';
	}

	/** @return string */
	public function frecuencia() {
		return 'Diaria (pronóstico a 30 días)';
	}

	/** @return array */
	public function config_defecto() {
		$c        = parent::config_defecto();
		$c['ttl'] = 360;
		return $c;
	}

	/**
	 * Serie diaria de todos los ríos (una petición).
	 *
	 * @return array 'datos' => [rio => objeto].
	 */
	public function rios() {
		$lat = array();
		$lon = array();
		foreach ( self::RIOS as $r ) {
			$lat[] = $r['lat'];
			$lon[] = $r['lon'];
		}
		$url = $this->url(
			self::BASE,
			array(
				'latitude'      => implode( ',', $lat ),
				'longitude'     => implode( ',', $lon ),
				'daily'         => 'river_discharge,river_discharge_mean,river_discharge_median,river_discharge_max,river_discharge_min,river_discharge_p25,river_discharge_p75',
				'past_days'     => 30,
				'forecast_days' => 30,
			)
		);
		$r   = $this->get_cacheado( 'rios', $url );
		if ( $r['ok'] ) {
			$claves = array_keys( self::RIOS );
			$out    = array();
			foreach ( $this->lista( $r['datos'] ) as $i => $obj ) {
				if ( isset( $claves[ $i ] ) ) {
					$out[ $claves[ $i ] ] = $obj;
				}
			}
			$r['datos'] = $out;
		}
		return $r;
	}

	/** @return array */
	public function probar() {
		$p   = self::RIOS['patia_bajo'];
		$url = $this->url(
			self::BASE,
			array(
				'latitude'      => $p['lat'],
				'longitude'     => $p['lon'],
				'daily'         => 'river_discharge',
				'forecast_days' => 3,
			)
		);
		$h   = SAN_Http::get( $this->id(), $url, array( 'timeout' => (int) $this->config()['timeout'] ) );
		$v   = $h['datos']['daily']['river_discharge'][0] ?? null;
		$ok  = $h['ok'] && is_numeric( $v );
		return $this->resultado_prueba(
			$h,
			$ok,
			$ok ? sprintf( 'Río Patía (bajo): %s m³/s hoy.', SAN_Analisis::num( $v, 0 ) ) : 'La celda no devolvió caudal.',
			$ok ? (string) $h['datos']['daily']['time'][0] : '',
			$ok ? array( 'daily' => array_map( function ( $a ) { return array_slice( (array) $a, 0, 3 ); }, $h['datos']['daily'] ) ) : array() // phpcs:ignore
		);
	}
}
