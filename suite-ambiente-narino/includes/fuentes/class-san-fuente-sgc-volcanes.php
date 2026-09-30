<?php
/**
 * Servicio Geológico Colombiano — estado de los volcanes.
 *
 * Archivo público `volcanos.json` del portal de volcanes del SGC, con el
 * nivel de actividad vigente y los boletines del Observatorio
 * Vulcanológico y Sismológico de Pasto. Ojo: en `geometry` las
 * coordenadas vienen como [lat, lon]; se usan `properties.latitude/longitude`.
 *
 * El SGC bloquea el acceso automatizado a este archivo (responde 403 a
 * clientes que no se identifican como navegador web). Por defecto el
 * plugin se identifica honestamente y la fuente queda sin datos; el
 * parámetro "acceso" permite identificarse como navegador, opción que solo
 * debe activarse después de acordarlo con el SGC.
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Fuente_Sgc_Volcanes extends SAN_Fuente {

	const URL = 'https://archive.sgc.gov.co/volcanos/volcanos.json';

	/** Niveles de actividad del SGC → color y nivel del plugin. */
	const NIVELES = array(
		4 => array( 'nombre' => 'Verde · activo en reposo', 'color' => '#2E9D3A', 'nivel' => 'bueno' ),
		3 => array( 'nombre' => 'Amarilla · cambios en la actividad', 'color' => '#F2C200', 'nivel' => 'moderado' ),
		2 => array( 'nombre' => 'Naranja · erupción probable', 'color' => '#EF7D00', 'nivel' => 'alerta' ),
		1 => array( 'nombre' => 'Roja · erupción inminente o en curso', 'color' => '#D12A1F', 'nivel' => 'critico' ),
	);

	/** @return string */
	public function id() {
		return 'sgc_volcanes';
	}

	/** @return string */
	public function nombre() {
		return 'Servicio Geológico Colombiano · Volcanes';
	}

	/** @return string */
	public function nombre_corto() {
		return 'Volcanes';
	}

	/** @return string */
	public function categoria() {
		return 'sismos';
	}

	/** @return string */
	public function descripcion() {
		return 'Nivel de actividad vigente de los volcanes de Nariño (Galeras, Cumbal, Chiles–Cerro Negro, Azufral, Doña Juana, Las Ánimas…) y enlace al último boletín del Observatorio Vulcanológico de Pasto.';
	}

	/** @return string[] */
	public function hosts() {
		return array( 'archive.sgc.gov.co' );
	}

	/** @return string */
	public function atribucion() {
		return 'Servicio Geológico Colombiano — Observatorio Vulcanológico y Sismológico de Pasto';
	}

	/** @return string */
	public function licencia() {
		return 'Información pública (Ley 1712 de 2014)';
	}

	/** @return string */
	public function url_doc() {
		return 'https://www2.sgc.gov.co/sgc/volcanes/Paginas/volcanes.aspx';
	}

	/** @return string */
	public function frecuencia() {
		return 'Al cambiar el nivel y con cada boletín semanal';
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
			'acceso' => array(
				'etiqueta' => 'Modo de acceso',
				'tipo'     => 'select',
				'opciones' => array(
					'estandar'  => 'Estándar (se identifica como Suite Ambiente)',
					'navegador' => 'Como navegador web (solo con autorización del SGC)',
				),
				'defecto'  => 'estandar',
				'ayuda'    => 'El SGC restringe el acceso automatizado a volcanos.json. Solicite al SGC un servicio de datos abiertos o autorización antes de usar el modo navegador.',
			),
		);
	}

	/**
	 * Opciones HTTP según el modo de acceso.
	 *
	 * @return array
	 */
	private function opciones_http() {
		if ( 'navegador' !== $this->param( 'acceso', 'estandar' ) ) {
			return array();
		}
		return array(
			'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36',
			'headers'    => array( 'Referer' => 'https://www.sgc.gov.co/' ),
		);
	}

	/**
	 * Volcanes que afectan a Nariño.
	 *
	 * @return array 'datos' => filas.
	 */
	public function volcanes() {
		return $this->get_procesado(
			'volcanes',
			self::URL,
			function ( $geo ) {
				if ( ! is_array( $geo ) || ! isset( $geo['features'] ) ) {
					return null;
				}
				$out = array();
				foreach ( $geo['features'] as $f ) {
					$p = $f['properties'] ?? array();
					if ( false === stripos( (string) ( $p['department'] ?? '' ), 'Nariño' ) ) {
						continue;
					}
					$nivel = (int) ( $p['activityLevel'] ?? 4 );
					$n     = self::NIVELES[ $nivel ] ?? self::NIVELES[4];
					$bol   = is_array( $p['bulletins'] ?? null ) && $p['bulletins'] ? $p['bulletins'][0] : array();
					$out[] = array(
						'volcan'         => (string) ( $p['VolcanoName'] ?? '' ),
						'nivel_codigo'   => $nivel,
						'nivel'          => (string) ( $p['activityLevelTitle'] ?? $n['nombre'] ),
						'descripcion'    => (string) ( $p['activityLevelShortDescription'] ?? '' ),
						'color'          => $n['color'],
						'categoria'      => $n['nombre'],
						'lat'            => (float) ( $p['latitude'] ?? 0 ),
						'lon'            => (float) ( $p['longitude'] ?? 0 ),
						'altitud'        => (string) ( $p['msnm'] ?? '' ),
						'departamentos'  => (string) ( $p['department'] ?? '' ),
						'boletin_fecha'  => (string) ( $bol['publication_date'] ?? '' ),
						'boletin_nombre' => (string) ( $bol['name'] ?? '' ),
						'boletin_url'    => esc_url_raw( (string) ( $bol['file'] ?? '' ) ),
						'enlace'         => esc_url_raw( (string) ( $p['link'] ?? '' ) ),
						'valor'          => 5 - $nivel,
					);
				}
				usort(
					$out,
					function ( $a, $b ) {
						return $a['nivel_codigo'] <=> $b['nivel_codigo'] ?: strcmp( $a['volcan'], $b['volcan'] );
					}
				);
				return $out;
			},
			$this->opciones_http()
		);
	}

	/** @return array */
	public function probar() {
		$h  = SAN_Http::get( $this->id(), self::URL, array_merge( $this->opciones_http(), array( 'timeout' => (int) $this->config()['timeout'] ) ) );
		$ok = $h['ok'] && isset( $h['datos']['features'] );
		$g  = null;
		foreach ( $ok ? $h['datos']['features'] : array() as $f ) {
			if ( false !== stripos( (string) ( $f['properties']['VolcanoName'] ?? '' ), 'Galeras' ) ) {
				$g = $f['properties'];
			}
		}
		if ( 403 === $h['codigo'] ) {
			$h['error'] = 'HTTP 403: el SGC bloquea el acceso automatizado. Vea el parámetro "Modo de acceso".';
		}
		return $this->resultado_prueba(
			$h,
			$ok,
			$ok ? sprintf( '%d volcanes; Galeras: %s.', count( $h['datos']['features'] ), $g['activityLevelTitle'] ?? 'no encontrado' ) : 'La respuesta no es un FeatureCollection.',
			$g['bulletins'][0]['publication_date'] ?? '',
			$g ? array(
				'Galeras' => array(
					'activityLevel'      => $g['activityLevel'] ?? null,
					'activityLevelTitle' => $g['activityLevelTitle'] ?? null,
				),
			) : array()
		);
	}
}
