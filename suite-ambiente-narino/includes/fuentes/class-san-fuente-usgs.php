<?php
/**
 * USGS Earthquake Hazards Program (FDSN event service): sismicidad regional
 * de magnitud ≥ 2,5 alrededor de Nariño. Complementa al SGC con la
 * perspectiva regional (Ecuador y la zona de subducción del Pacífico).
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Fuente_Usgs extends SAN_Fuente {

	const BASE = 'https://earthquake.usgs.gov/fdsnws/event/1/query';

	/** @return string */
	public function id() {
		return 'usgs';
	}

	/** @return string */
	public function nombre() {
		return 'USGS · Sismicidad regional';
	}

	/** @return string */
	public function nombre_corto() {
		return 'USGS';
	}

	/** @return string */
	public function categoria() {
		return 'sismos';
	}

	/** @return string */
	public function descripcion() {
		return 'Catálogo mundial de sismos del Servicio Geológico de EE. UU.: eventos de magnitud ≥ 2,5 en un radio configurable alrededor de Pasto durante el último año.';
	}

	/** @return string[] */
	public function hosts() {
		return array( 'earthquake.usgs.gov' );
	}

	/** @return string */
	public function atribucion() {
		return 'U.S. Geological Survey, Earthquake Hazards Program';
	}

	/** @return string */
	public function licencia() {
		return 'Dominio público (USGS)';
	}

	/** @return string */
	public function url_doc() {
		return 'https://earthquake.usgs.gov/fdsnws/event/1/';
	}

	/** @return string */
	public function frecuencia() {
		return 'Continua (≈ 1 min)';
	}

	/** @return array */
	public function config_defecto() {
		$c        = parent::config_defecto();
		$c['ttl'] = 30;
		return $c;
	}

	/** @return array */
	public function campos_params() {
		return array(
			'radio_km' => array(
				'etiqueta' => 'Radio alrededor de Pasto (km)',
				'tipo'     => 'numero',
				'min'      => 100,
				'max'      => 1000,
				'defecto'  => 400,
			),
			'min_mag'  => array(
				'etiqueta' => 'Magnitud mínima',
				'tipo'     => 'numero',
				'min'      => 1,
				'max'      => 6,
				'defecto'  => 2.5,
			),
			'dias'     => array(
				'etiqueta' => 'Días consultados',
				'tipo'     => 'numero',
				'min'      => 7,
				'max'      => 730,
				'defecto'  => 365,
			),
		);
	}

	/**
	 * Eventos regionales.
	 *
	 * @return array 'datos' => filas.
	 */
	public function eventos() {
		$url = add_query_arg(
			array(
				'format'       => 'geojson',
				'starttime'    => gmdate( 'Y-m-d', time() - (int) $this->param( 'dias', 365 ) * DAY_IN_SECONDS ),
				'latitude'     => '1.2124',
				'longitude'    => '-77.2788',
				'maxradiuskm'  => (int) $this->param( 'radio_km', 400 ),
				'minmagnitude' => (float) $this->param( 'min_mag', 2.5 ),
				'orderby'      => 'time',
			),
			self::BASE
		);
		return $this->get_procesado(
			'eventos:' . md5( $url ),
			$url,
			function ( $geo ) {
				if ( ! is_array( $geo ) || ! isset( $geo['features'] ) ) {
					return null;
				}
				$out = array();
				foreach ( $geo['features'] as $f ) {
					$p     = $f['properties'] ?? array();
					$c     = $f['geometry']['coordinates'] ?? array( 0, 0, 0 );
					$out[] = array(
						'id'          => (string) ( $f['id'] ?? '' ),
						'fecha_utc'   => gmdate( 'Y-m-d H:i:s', (int) ( ( $p['time'] ?? 0 ) / 1000 ) ),
						'fecha'       => gmdate( 'Y-m-d', (int) ( ( $p['time'] ?? 0 ) / 1000 ) - 5 * HOUR_IN_SECONDS ),
						'magnitud'    => isset( $p['mag'] ) ? (float) $p['mag'] : null,
						'tipo_mag'    => (string) ( $p['magType'] ?? '' ),
						'profundidad' => isset( $c[2] ) ? round( (float) $c[2], 1 ) : null,
						'lat'         => round( (float) $c[1], 4 ),
						'lon'         => round( (float) $c[0], 4 ),
						'lugar'       => (string) ( $p['place'] ?? '' ),
						'distancia_km' => round( SAN_Geo::distancia_km( 1.2124, -77.2788, (float) $c[1], (float) $c[0] ) ),
						'enlace'      => esc_url_raw( (string) ( $p['url'] ?? '' ) ),
					);
				}
				return $out;
			}
		);
	}

	/** @return array */
	public function probar() {
		$h  = SAN_Http::get( $this->id(), 'https://earthquake.usgs.gov/earthquakes/feed/v1.0/summary/2.5_day.geojson', array( 'timeout' => (int) $this->config()['timeout'] ) );
		$ok = $h['ok'] && isset( $h['datos']['features'] );
		$ul = $ok && $h['datos']['features'] ? $h['datos']['features'][0]['properties'] : array();
		return $this->resultado_prueba(
			$h,
			$ok,
			$ok ? sprintf( '%d sismos M2,5+ en el mundo hoy; último: M%s %s.', count( $h['datos']['features'] ), $ul['mag'] ?? '—', $ul['place'] ?? '' ) : 'Respuesta inesperada.',
			isset( $h['datos']['metadata']['generated'] ) ? gmdate( 'Y-m-d H:i', (int) ( $h['datos']['metadata']['generated'] / 1000 ) ) . ' UTC' : '',
			$ul ? array( 'ultimo' => array_intersect_key( $ul, array_flip( array( 'mag', 'place', 'time' ) ) ) ) : array()
		);
	}
}
