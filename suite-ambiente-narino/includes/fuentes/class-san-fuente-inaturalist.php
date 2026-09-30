<?php
/**
 * iNaturalist — ciencia ciudadana (Naturalista Colombia). Observaciones
 * recientes en Nariño (place_id 12737): se actualiza cada minuto.
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Fuente_Inaturalist extends SAN_Fuente {

	const BASE  = 'https://api.inaturalist.org/v1/';
	const LUGAR = 12737;

	/** Grupos icónicos → nombre en español. */
	const GRUPOS = array(
		'Aves'           => 'Aves',
		'Insecta'        => 'Insectos',
		'Plantae'        => 'Plantas',
		'Amphibia'       => 'Anfibios',
		'Reptilia'       => 'Reptiles',
		'Mammalia'       => 'Mamíferos',
		'Fungi'          => 'Hongos',
		'Arachnida'      => 'Arácnidos',
		'Mollusca'       => 'Moluscos',
		'Actinopterygii' => 'Peces',
		'Animalia'       => 'Otros animales',
		'Chromista'      => 'Cromistas',
		'Protozoa'       => 'Protozoos',
	);

	/** @return string */
	public function id() {
		return 'inaturalist';
	}

	/** @return string */
	public function nombre() {
		return 'iNaturalist · Ciencia ciudadana';
	}

	/** @return string */
	public function nombre_corto() {
		return 'iNaturalist';
	}

	/** @return string */
	public function categoria() {
		return 'biodiversidad';
	}

	/** @return string */
	public function descripcion() {
		return 'Observaciones de fauna, flora y hongos registradas por la ciudadanía en Nariño a través de iNaturalist/Naturalista durante los últimos días, por grupo biológico y por día.';
	}

	/** @return string[] */
	public function hosts() {
		return array( 'api.inaturalist.org' );
	}

	/** @return string */
	public function atribucion() {
		return 'iNaturalist.org / Naturalista Colombia — observaciones de la comunidad';
	}

	/** @return string */
	public function licencia() {
		return 'CC0 / CC BY / CC BY-NC (según cada observación)';
	}

	/** @return string */
	public function url_doc() {
		return 'https://api.inaturalist.org/v1/docs/';
	}

	/** @return string */
	public function frecuencia() {
		return 'Continua';
	}

	/** @return array */
	public function config_defecto() {
		$c        = parent::config_defecto();
		$c['ttl'] = 180;
		return $c;
	}

	/** @return array */
	public function campos_params() {
		return array(
			'dias' => array(
				'etiqueta' => 'Días recientes',
				'tipo'     => 'numero',
				'min'      => 7,
				'max'      => 365,
				'defecto'  => 30,
			),
		);
	}

	/**
	 * Resumen reciente: conteos por grupo icónico y por día.
	 *
	 * @return array 'datos' => { grupos: {nombre: n}, dias: {fecha: n}, recientes: [] }
	 */
	public function resumen() {
		$dias  = (int) $this->param( 'dias', 30 );
		$d1    = gmdate( 'Y-m-d', time() - $dias * DAY_IN_SECONDS );
		$base  = array(
			'place_id' => self::LUGAR,
			'd1'       => $d1,
		);
		$g     = $this->get_procesado(
			'grupos:' . $dias,
			add_query_arg( $base, self::BASE . 'observations/iconic_taxa_counts' ),
			function ( $j ) {
				if ( ! isset( $j['results'] ) || ! is_array( $j['results'] ) ) {
					return null;
				}
				$out = array();
				foreach ( $j['results'] as $r ) {
					$n         = (string) ( $r['taxon']['name'] ?? '' );
					$out[ SAN_Fuente_Inaturalist::GRUPOS[ $n ] ?? $n ] = (int) $r['count'];
				}
				arsort( $out );
				return $out;
			}
		);
		if ( ! $g['ok'] ) {
			return $g;
		}
		$h = $this->get_procesado(
			'dias:' . $dias,
			add_query_arg( array_merge( $base, array( 'interval' => 'day', 'date_field' => 'observed' ) ), self::BASE . 'observations/histogram' ),
			function ( $j ) {
				return isset( $j['results']['day'] ) && is_array( $j['results']['day'] ) ? $j['results']['day'] : null;
			}
		);
		$o = $this->get_procesado(
			'recientes',
			add_query_arg( array_merge( $base, array( 'order_by' => 'observed_on', 'order' => 'desc', 'per_page' => 30, 'quality_grade' => 'research', 'photos' => 'true' ) ), self::BASE . 'observations' ),
			function ( $j ) {
				if ( ! isset( $j['results'] ) ) {
					return null;
				}
				$out = array();
				foreach ( $j['results'] as $r ) {
					$loc   = explode( ',', (string) ( $r['location'] ?? '' ) );
					$out[] = array(
						'fecha'      => (string) ( $r['observed_on'] ?? '' ),
						'especie'    => (string) ( $r['taxon']['name'] ?? '' ),
						'comun'      => (string) ( $r['taxon']['preferred_common_name'] ?? '' ),
						'grupo'      => SAN_Fuente_Inaturalist::GRUPOS[ $r['taxon']['iconic_taxon_name'] ?? '' ] ?? (string) ( $r['taxon']['iconic_taxon_name'] ?? '' ),
						'amenaza'    => (string) ( $r['taxon']['conservation_status']['status'] ?? '' ),
						'lat'        => isset( $loc[1] ) ? round( (float) $loc[0], 4 ) : null,
						'lon'        => isset( $loc[1] ) ? round( (float) $loc[1], 4 ) : null,
						'licencia'   => (string) ( $r['license_code'] ?? '' ),
						'enlace'     => esc_url_raw( (string) ( $r['uri'] ?? '' ) ),
					);
				}
				return $out;
			}
		);
		$g['datos'] = array(
			'grupos'    => $g['datos'],
			'dias'      => $h['ok'] ? $h['datos'] : array(),
			'recientes' => $o['ok'] ? $o['datos'] : array(),
		);
		return $g;
	}

	/** @return array */
	public function probar() {
		$h  = SAN_Http::get( $this->id(), add_query_arg( array( 'place_id' => self::LUGAR, 'per_page' => 1, 'order_by' => 'created_at' ), self::BASE . 'observations' ), array( 'timeout' => (int) $this->config()['timeout'] ) );
		$ok = $h['ok'] && isset( $h['datos']['total_results'] );
		$u  = $ok && ! empty( $h['datos']['results'] ) ? $h['datos']['results'][0] : array();
		return $this->resultado_prueba(
			$h,
			$ok,
			$ok ? sprintf( '%s observaciones en Nariño; la más reciente: %s.', SAN_Analisis::num( $h['datos']['total_results'], 0 ), $u['taxon']['name'] ?? ( $u['species_guess'] ?? 'sin identificar' ) ) : 'Respuesta sin total_results.',
			(string) ( $u['created_at'] ?? '' ),
			$u ? array( 'reciente' => array( 'taxon' => $u['taxon']['name'] ?? '', 'observed_on' => $u['observed_on'] ?? '' ) ) : array()
		);
	}
}
