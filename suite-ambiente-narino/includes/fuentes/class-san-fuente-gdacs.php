<?php
/**
 * GDACS — Global Disaster Alert and Coordination System (ONU / Comisión
 * Europea): alertas de sismos, inundaciones, ciclones, volcanes, sequías e
 * incendios forestales en Colombia y Ecuador.
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Fuente_Gdacs extends SAN_Fuente {

	const BASE = 'https://www.gdacs.org/gdacsapi/api/events/geteventlist/SEARCH';

	/** Tipos de evento GDACS. */
	const TIPOS = array(
		'EQ' => 'Sismo',
		'FL' => 'Inundación',
		'TC' => 'Ciclón tropical',
		'VO' => 'Volcán',
		'DR' => 'Sequía',
		'WF' => 'Incendio forestal',
		'TS' => 'Tsunami',
	);

	/** @return string */
	public function id() {
		return 'gdacs';
	}

	/** @return string */
	public function nombre() {
		return 'GDACS · Alertas de desastres';
	}

	/** @return string */
	public function nombre_corto() {
		return 'Eventos';
	}

	/** @return string */
	public function categoria() {
		return 'eventos';
	}

	/** @return string */
	public function descripcion() {
		return 'Eventos naturales con alerta verde, naranja o roja del sistema GDACS en Colombia y Ecuador durante el último año: sismos, inundaciones, volcanes, sequías e incendios forestales.';
	}

	/** @return string[] */
	public function hosts() {
		return array( 'www.gdacs.org' );
	}

	/** @return string */
	public function atribucion() {
		return 'GDACS — Global Disaster Alert and Coordination System (ONU, Comisión Europea)';
	}

	/** @return string */
	public function licencia() {
		return 'Uso con atribución (términos de GDACS)';
	}

	/** @return string */
	public function url_doc() {
		return 'https://www.gdacs.org/Knowledge/overview.aspx';
	}

	/** @return string */
	public function frecuencia() {
		return 'Continua (minutos después de cada evento)';
	}

	/** @return array */
	public function config_defecto() {
		$c            = parent::config_defecto();
		$c['ttl']     = 120;
		$c['timeout'] = 25;
		return $c;
	}

	/** @return array */
	public function campos_params() {
		return array(
			'paises' => array(
				'etiqueta' => 'Países',
				'tipo'     => 'select',
				'opciones' => array(
					'Colombia'         => 'Colombia',
					'Colombia;Ecuador' => 'Colombia y Ecuador',
				),
				'defecto'  => 'Colombia;Ecuador',
			),
		);
	}

	/**
	 * Eventos del último año.
	 *
	 * @return array 'datos' => filas.
	 */
	public function eventos() {
		$paises = (string) $this->param( 'paises', 'Colombia;Ecuador' );
		$filas  = array();
		$ultima = null;
		foreach ( explode( ';', $paises ) as $pais ) {
			$url = add_query_arg(
				array(
					'country'    => rawurlencode( $pais ),
					'fromdate'   => gmdate( 'Y-m-d', time() - 365 * DAY_IN_SECONDS ),
					'todate'     => gmdate( 'Y-m-d', time() + DAY_IN_SECONDS ),
					'alertlevel' => 'green;orange;red',
				),
				self::BASE
			);
			$r   = $this->get_procesado(
				'eventos:' . sanitize_key( $pais ),
				$url,
				function ( $geo ) use ( $pais ) {
					if ( ! is_array( $geo ) || ! isset( $geo['features'] ) ) {
						return null;
					}
					$out = array();
					foreach ( $geo['features'] as $f ) {
						$p     = $f['properties'] ?? array();
						$c     = $f['geometry']['coordinates'] ?? array( null, null );
						$tipo  = (string) ( $p['eventtype'] ?? '' );
						$out[] = array(
							'id'        => $tipo . '-' . ( $p['eventid'] ?? '' ),
							'tipo'      => SAN_Fuente_Gdacs::TIPOS[ $tipo ] ?? $tipo,
							'nombre'    => (string) ( $p['name'] ?? '' ),
							'alerta'    => (string) ( $p['alertlevel'] ?? '' ),
							'pais'      => $pais,
							'desde'     => substr( (string) ( $p['fromdate'] ?? '' ), 0, 10 ),
							'hasta'     => substr( (string) ( $p['todate'] ?? '' ), 0, 10 ),
							'lat'       => is_numeric( $c[1] ?? null ) ? round( (float) $c[1], 3 ) : null,
							'lon'       => is_numeric( $c[0] ?? null ) ? round( (float) $c[0], 3 ) : null,
							'descripcion' => wp_strip_all_tags( (string) ( $p['htmldescription'] ?? $p['description'] ?? '' ) ),
							'enlace'    => esc_url_raw( (string) ( $p['url']['report'] ?? '' ) ),
						);
					}
					return $out;
				}
			);
			if ( ! $r['ok'] ) {
				return $r;
			}
			$filas  = array_merge( $filas, (array) $r['datos'] );
			$ultima = $r;
		}
		usort(
			$filas,
			function ( $a, $b ) {
				return strcmp( $b['desde'], $a['desde'] );
			}
		);
		$ultima['datos'] = $filas;
		return $ultima;
	}

	/** @return array */
	public function probar() {
		$url = add_query_arg(
			array(
				'country'    => 'Colombia',
				'fromdate'   => gmdate( 'Y-m-d', time() - 60 * DAY_IN_SECONDS ),
				'todate'     => gmdate( 'Y-m-d', time() + DAY_IN_SECONDS ),
				'alertlevel' => 'green;orange;red',
			),
			self::BASE
		);
		$h   = SAN_Http::get( $this->id(), $url, array( 'timeout' => (int) $this->config()['timeout'] ) );
		$ok  = $h['ok'] && isset( $h['datos']['features'] );
		$u   = $ok && $h['datos']['features'] ? $h['datos']['features'][0]['properties'] : array();
		return $this->resultado_prueba(
			$h,
			$ok,
			$ok ? sprintf( '%d eventos en Colombia (60 días). Último: %s.', count( $h['datos']['features'] ), $u['name'] ?? '—' ) : 'Respuesta inesperada.',
			(string) ( $u['fromdate'] ?? '' ),
			$u ? array( 'ultimo' => array_intersect_key( $u, array_flip( array( 'eventtype', 'name', 'alertlevel', 'fromdate' ) ) ) ) : array()
		);
	}
}
