<?php
/**
 * GBIF — Global Biodiversity Information Facility (Nodo SiB Colombia).
 *
 * Registros de biodiversidad georreferenciados en Nariño (GADM COL.22_2),
 * resumidos con facetas: reino, clase, categoría de amenaza UICN, año y
 * municipio (GADM nivel 2, mapeado a DIVIPOLA en data/gadm_narino.json).
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Fuente_Gbif extends SAN_Fuente {

	const BASE = 'https://api.gbif.org/v1/';
	const GADM = 'COL.22_2';

	/** Reinos GBIF (clave → nombre). */
	const REINOS = array(
		'0' => 'Incertae sedis',
		'1' => 'Animales',
		'2' => 'Arqueas',
		'3' => 'Bacterias',
		'4' => 'Cromistas',
		'5' => 'Hongos',
		'6' => 'Plantas',
		'7' => 'Protozoos',
		'8' => 'Virus',
	);

	/** Categorías UICN. */
	const UICN = array(
		'EX' => 'Extinta',
		'EW' => 'Extinta en estado silvestre',
		'CR' => 'En peligro crítico',
		'EN' => 'En peligro',
		'VU' => 'Vulnerable',
		'NT' => 'Casi amenazada',
		'LC' => 'Preocupación menor',
		'DD' => 'Datos insuficientes',
		'NE' => 'No evaluada',
	);

	/** @return string */
	public function id() {
		return 'gbif';
	}

	/** @return string */
	public function nombre() {
		return 'GBIF · Biodiversidad (SiB Colombia)';
	}

	/** @return string */
	public function nombre_corto() {
		return 'GBIF';
	}

	/** @return string */
	public function categoria() {
		return 'biodiversidad';
	}

	/** @return string */
	public function descripcion() {
		return 'Cerca de un millón de registros de especies en Nariño publicados por instituciones colombianas e internacionales en GBIF: grupos biológicos, especies amenazadas (UICN), evolución anual y registros por municipio.';
	}

	/** @return string[] */
	public function hosts() {
		return array( 'api.gbif.org' );
	}

	/** @return string */
	public function atribucion() {
		return 'GBIF.org — registros publicados a través del SiB Colombia y otros nodos';
	}

	/** @return string */
	public function licencia() {
		return 'CC0 / CC BY / CC BY-NC (según cada registro)';
	}

	/** @return string */
	public function url_doc() {
		return 'https://techdocs.gbif.org/en/openapi/v1/occurrence';
	}

	/** @return string */
	public function frecuencia() {
		return 'Diaria (nuevos registros e indexación continua)';
	}

	/** @return array */
	public function config_defecto() {
		$c            = parent::config_defecto();
		$c['ttl']     = 1440;
		$c['timeout'] = 25;
		return $c;
	}

	/**
	 * Facetas de ocurrencias en Nariño.
	 *
	 * @return array 'datos' => { total, reinos[], clases[], uicn[], anios[], municipios[] }
	 */
	public function facetas() {
		$url = self::BASE . 'occurrence/search?gadmGid=' . self::GADM . '&limit=0&facet=kingdomKey&facet=classKey&facet=iucnRedListCategory&facet=year&facet=gadmLevel2Gid&facetLimit=70';
		$r   = $this->get_procesado(
			'facetas',
			$url,
			function ( $j ) {
				if ( ! is_array( $j ) || ! isset( $j['count'], $j['facets'] ) ) {
					return null;
				}
				$out = array(
					'total'      => (int) $j['count'],
					'reinos'     => array(),
					'clases'     => array(),
					'uicn'       => array(),
					'anios'      => array(),
					'municipios' => array(),
				);
				$mapa = array(
					'KINGDOM_KEY'            => 'reinos',
					'CLASS_KEY'              => 'clases',
					'IUCN_RED_LIST_CATEGORY' => 'uicn',
					'YEAR'                   => 'anios',
					'GADM_LEVEL_2_GID'       => 'municipios',
				);
				foreach ( $j['facets'] as $f ) {
					$k = $mapa[ $f['field'] ?? '' ] ?? null;
					if ( $k ) {
						foreach ( (array) $f['counts'] as $c ) {
							$out[ $k ][ (string) $c['name'] ] = (int) $c['count'];
						}
					}
				}
				return $out;
			}
		);
		return $r;
	}

	/**
	 * Nombre científico de una clave taxonómica (caché de 30 días).
	 *
	 * @param string $clave Clave GBIF.
	 * @return string
	 */
	public function nombre_taxon( $clave ) {
		$clave = preg_replace( '/\D/', '', (string) $clave );
		$r     = $this->get_procesado(
			'taxon:' . $clave,
			self::BASE . 'species/' . $clave,
			function ( $j ) {
				return is_array( $j ) && isset( $j['canonicalName'] ) ? array(
					'nombre'  => (string) $j['canonicalName'],
					'vulgar'  => (string) ( $j['vernacularName'] ?? '' ),
				) : null;
			},
			array(),
			43200
		);
		return $r['ok'] ? $r['datos']['nombre'] : $clave;
	}

	/** @return array */
	public function probar() {
		$h  = SAN_Http::get( $this->id(), self::BASE . 'occurrence/search?gadmGid=' . self::GADM . '&limit=1', array( 'timeout' => (int) $this->config()['timeout'] ) );
		$ok = $h['ok'] && isset( $h['datos']['count'] );
		$u  = $ok && ! empty( $h['datos']['results'] ) ? $h['datos']['results'][0] : array();
		return $this->resultado_prueba(
			$h,
			$ok,
			$ok ? sprintf( '%s registros de ocurrencia en Nariño.', SAN_Analisis::num( $h['datos']['count'], 0 ) ) : 'Respuesta sin count.',
			(string) ( $u['lastInterpreted'] ?? '' ),
			$u ? array( 'ejemplo' => array_intersect_key( $u, array_flip( array( 'scientificName', 'eventDate', 'datasetName', 'license' ) ) ) ) : array()
		);
	}
}
