<?php
/**
 * Servicio Geológico Colombiano — sismos de la Red Sismológica Nacional.
 *
 * Endpoint público que alimenta el mapa de sismicidad de sgc.gov.co
 * (`api.sgc.gov.co/biweekly/biweekly_earthquakes`), con unos 30 minutos de
 * retraso. No está documentado oficialmente: se valida la forma de la
 * respuesta en cada lectura y el panel alerta si cambia. `enddate` es
 * exclusivo y la consulta es nacional: el recorte a Nariño se hace aquí.
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Fuente_Sgc_Sismos extends SAN_Fuente {

	const BASE = 'https://api.sgc.gov.co/biweekly/biweekly_earthquakes';

	/** @return string */
	public function id() {
		return 'sgc_sismos';
	}

	/** @return string */
	public function nombre() {
		return 'Servicio Geológico Colombiano · Sismos';
	}

	/** @return string */
	public function nombre_corto() {
		return 'Sismos';
	}

	/** @return string */
	public function categoria() {
		return 'sismos';
	}

	/** @return string */
	public function descripcion() {
		return 'Sismos localizados por la Red Sismológica Nacional (incluido el Observatorio Vulcanológico de Pasto) en Nariño y su entorno, con magnitud, profundidad y poblaciones cercanas.';
	}

	/** @return string[] */
	public function hosts() {
		return array( 'api.sgc.gov.co' );
	}

	/** @return string */
	public function atribucion() {
		return 'Servicio Geológico Colombiano — Red Sismológica Nacional de Colombia';
	}

	/** @return string */
	public function licencia() {
		return 'Información pública (Ley 1712 de 2014)';
	}

	/** @return string */
	public function url_doc() {
		return 'https://www.sgc.gov.co/sismos';
	}

	/** @return string */
	public function frecuencia() {
		return 'Continua (≈ 30 min de retraso)';
	}

	/** @return array */
	public function config_defecto() {
		$c        = parent::config_defecto();
		$c['ttl'] = 15;
		$c['timeout'] = 25;
		return $c;
	}

	/** @return array */
	public function campos_params() {
		return array(
			'dias'   => array(
				'etiqueta' => 'Días consultados',
				'tipo'     => 'numero',
				'min'      => 1,
				'max'      => 15,
				'defecto'  => 15,
				'ayuda'    => 'El servicio conserva unas dos semanas.',
			),
			'margen' => array(
				'etiqueta' => 'Margen alrededor de Nariño (grados)',
				'tipo'     => 'numero',
				'min'      => 0,
				'max'      => 2,
				'defecto'  => 0.3,
				'ayuda'    => '0,3° ≈ 33 km: incluye la frontera con Ecuador y el Cauca.',
			),
		);
	}

	/**
	 * Sismos de la región de Nariño en los últimos días.
	 *
	 * @return array 'datos' => filas normalizadas.
	 */
	public function sismos() {
		$dias   = (int) $this->param( 'dias', 15 );
		$margen = (float) $this->param( 'margen', 0.3 );
		$tz     = new \DateTimeZone( 'America/Bogota' );
		$desde  = ( new \DateTime( 'now', $tz ) )->modify( '-' . ( $dias - 1 ) . ' days' )->format( 'Y-m-d' );
		$hasta  = ( new \DateTime( 'now', $tz ) )->modify( '+1 day' )->format( 'Y-m-d' );
		$url    = add_query_arg(
			array(
				'startdate' => $desde,
				'enddate'   => $hasta,
			),
			self::BASE
		);
		return $this->get_procesado(
			'sismos:' . $dias . ':' . $margen,
			$url,
			function ( $geo ) use ( $margen ) {
				if ( ! is_array( $geo ) || ! isset( $geo['features'] ) || ! is_array( $geo['features'] ) ) {
					return null;
				}
				$out = array();
				foreach ( $geo['features'] as $f ) {
					$c = $f['geometry']['coordinates'] ?? array();
					$p = $f['properties'] ?? array();
					if ( count( $c ) < 2 || ! SAN_Geo::en_region( (float) $c[1], (float) $c[0], $margen ) ) {
						continue;
					}
					$div   = SAN_Geo::municipio_de( $c[1], $c[0] );
					$out[] = array(
						'id'           => (string) ( $f['id'] ?? '' ),
						'fecha_utc'    => (string) ( $p['utcTime'] ?? '' ),
						'fecha_local'  => (string) ( $p['localTime'] ?? '' ),
						'magnitud'     => isset( $p['mag'] ) ? (float) $p['mag'] : null,
						'tipo_mag'     => (string) ( $p['magType'] ?? '' ),
						'profundidad'  => isset( $p['depth'] ) ? (float) $p['depth'] : ( $c[2] ?? null ),
						'lat'          => round( (float) $c[1], 4 ),
						'lon'          => round( (float) $c[0], 4 ),
						'lugar'        => (string) ( $p['place'] ?? '' ),
						'cercanos'     => (string) ( $p['closerTowns'] ?? '' ),
						'estado'       => (string) ( $p['status'] ?? '' ),
						'sentido'      => (int) ( $p['felt'] ?? 0 ),
						'divipola'     => $div,
						'municipio'    => $div ? SAN_Municipios::nombre( $div ) : '',
						'en_narino'    => '' !== $div,
					);
				}
				usort(
					$out,
					function ( $a, $b ) {
						return strcmp( $b['fecha_utc'], $a['fecha_utc'] );
					}
				);
				return $out;
			}
		);
	}

	/** @return array */
	public function probar() {
		$tz  = new \DateTimeZone( 'America/Bogota' );
		$url = add_query_arg(
			array(
				'startdate' => ( new \DateTime( 'now', $tz ) )->format( 'Y-m-d' ),
				'enddate'   => ( new \DateTime( 'now', $tz ) )->modify( '+1 day' )->format( 'Y-m-d' ),
			),
			self::BASE
		);
		$h   = SAN_Http::get( $this->id(), $url, array( 'timeout' => (int) $this->config()['timeout'] ) );
		$ok  = $h['ok'] && isset( $h['datos']['features'] ) && is_array( $h['datos']['features'] );
		$ult = $ok && $h['datos']['features'] ? $h['datos']['features'][0]['properties'] : array();
		foreach ( $ok ? $h['datos']['features'] : array() as $f ) {
			if ( ( $f['properties']['utcTime'] ?? '' ) > ( $ult['utcTime'] ?? '' ) ) {
				$ult = $f['properties'];
			}
		}
		return $this->resultado_prueba(
			$h,
			$ok,
			$ok ? sprintf( '%d sismos hoy en Colombia; último: M%s %s.', count( $h['datos']['features'] ), $ult['mag'] ?? '—', $ult['place'] ?? '' ) : 'La respuesta no es un FeatureCollection (¿cambió el servicio?).',
			$ult['utcTime'] ?? '',
			$ult ? array( 'ultimo' => $ult ) : array()
		);
	}
}
