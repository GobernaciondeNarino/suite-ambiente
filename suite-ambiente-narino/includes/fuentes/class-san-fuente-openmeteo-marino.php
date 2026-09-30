<?php
/**
 * Open-Meteo Marine: oleaje y temperatura superficial del mar frente al
 * litoral de Nariño (modelos ECMWF WAM, MeteoFrance MFWAM, Copernicus).
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Fuente_Openmeteo_Marino extends SAN_Fuente_Openmeteo {

	const BASE = 'https://marine-api.open-meteo.com/v1/marine';

	/** Puntos costa afuera (el modelo no cubre celdas de tierra). */
	const PUNTOS = array(
		'tumaco'    => array(
			'nombre' => 'Bahía de Tumaco',
			'lat'    => 1.85,
			'lon'    => -78.85,
		),
		'sanquianga' => array(
			'nombre' => 'Frente a Sanquianga (Mosquera)',
			'lat'    => 2.55,
			'lon'    => -78.55,
		),
	);

	/** @return string */
	public function id() {
		return 'openmeteo_marino';
	}

	/** @return string */
	public function nombre() {
		return 'Open-Meteo · Océano Pacífico';
	}

	/** @return string */
	public function nombre_corto() {
		return 'Océano';
	}

	/** @return string */
	public function categoria() {
		return 'oceano';
	}

	/** @return string */
	public function descripcion() {
		return 'Altura, periodo y dirección del oleaje y temperatura superficial del mar frente a Tumaco y Sanquianga, con pronóstico a 7 días.';
	}

	/** @return string[] */
	public function hosts() {
		return array( 'marine-api.open-meteo.com' );
	}

	/** @return string */
	public function atribucion() {
		return 'Open-Meteo.com (CC BY 4.0) · ECMWF WAM / Copernicus Marine';
	}

	/** @return string */
	public function url_doc() {
		return 'https://open-meteo.com/en/docs/marine-weather-api';
	}

	/** @return string */
	public function frecuencia() {
		return 'Cada hora (modelos de oleaje cada 6–12 h)';
	}

	/** @return array */
	public function config_defecto() {
		$c        = parent::config_defecto();
		$c['ttl'] = 120;
		return $c;
	}

	/** @return array */
	public function campos_params() {
		$op = array();
		foreach ( self::PUNTOS as $k => $p ) {
			$op[ $k ] = $p['nombre'];
		}
		return array(
			'punto' => array(
				'etiqueta' => 'Punto principal',
				'tipo'     => 'select',
				'opciones' => $op,
				'defecto'  => 'tumaco',
			),
		);
	}

	/**
	 * Serie horaria (ayer + 7 días) de los puntos costeros.
	 *
	 * @return array 'datos' => [punto => objeto].
	 */
	public function puntos() {
		$lat = array();
		$lon = array();
		foreach ( self::PUNTOS as $p ) {
			$lat[] = $p['lat'];
			$lon[] = $p['lon'];
		}
		$url = $this->url(
			self::BASE,
			array(
				'latitude'      => implode( ',', $lat ),
				'longitude'     => implode( ',', $lon ),
				'current'       => 'wave_height,wave_period,wave_direction,sea_surface_temperature',
				'hourly'        => 'wave_height,wave_period,wave_direction,swell_wave_height,sea_surface_temperature',
				'past_days'     => 1,
				'forecast_days' => 7,
			)
		);
		$r   = $this->get_cacheado( 'puntos', $url );
		if ( $r['ok'] ) {
			$claves = array_keys( self::PUNTOS );
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
		$p   = self::PUNTOS['tumaco'];
		$url = $this->url(
			self::BASE,
			array(
				'latitude'  => $p['lat'],
				'longitude' => $p['lon'],
				'current'   => 'wave_height,sea_surface_temperature',
			)
		);
		$h   = SAN_Http::get( $this->id(), $url, array( 'timeout' => (int) $this->config()['timeout'] ) );
		$ok  = $h['ok'] && isset( $h['datos']['current']['wave_height'] );
		return $this->resultado_prueba(
			$h,
			$ok,
			$ok ? sprintf( 'Tumaco: ola %s m, mar %s °C.', $h['datos']['current']['wave_height'], $h['datos']['current']['sea_surface_temperature'] ?? '—' ) : 'La respuesta no trae current.wave_height.',
			$ok ? (string) $h['datos']['current']['time'] : '',
			$ok ? array( 'current' => $h['datos']['current'] ) : array()
		);
	}
}
