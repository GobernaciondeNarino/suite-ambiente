<?php
/**
 * Servicio Geológico Colombiano — nivel de actividad de los volcanes.
 *
 * Fuente: la lista pública «Boletines-Comunicados» del sitio de noticias del
 * SGC (API REST de SharePoint en www2.sgc.gov.co), la misma que alimenta la
 * página de boletines del Observatorio Vulcanológico y Sismológico de Pasto
 * (OVSP). Responde sin autenticación y a clientes que se identifican como
 * tales; `robots.txt` no restringe la ruta. No está documentada como datos
 * abiertos: puede cambiar sin aviso, por eso se consulta con caché y se
 * valida la forma de la respuesta.
 *
 * Reglas:
 *  - El nivel se toma del boletín semanal o extraordinario más reciente que
 *    trate de un solo volcán (o del par Chiles–Cerro Negro). Los boletines
 *    mensuales agrupan varios volcanes y su campo de nivel no es confiable
 *    («NA» casi siempre): de ellos solo se toma el enlace.
 *  - Se piden solo los campos necesarios (`$select`): el registro completo
 *    trae datos de los funcionarios que publican, que no se necesitan.
 *
 * El archivo `archive.sgc.gov.co/volcanos/volcanos.json`, que se usó antes,
 * bloquea el acceso automatizado (HTTP 403) y ya no se consulta.
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Fuente_Sgc_Volcanes extends SAN_Fuente {

	const SITIO  = 'https://www2.sgc.gov.co';
	const LISTA  = "/Noticias/_api/web/lists/getbytitle('Boletines-Comunicados')/items";
	const CAMPOS = 'Title,Emision,Tipo_x002d_comunicado,Observatorio,Nivel_x0020_de_x0020_actividad,Volc_x00e1_n,FileRef';

	/** Niveles de actividad del SGC → color y nivel del plugin (0 = sin dato). */
	const NIVELES = array(
		4 => array( 'nombre' => 'Verde · activo en reposo', 'color' => '#2E9D3A', 'nivel' => 'bueno' ),
		3 => array( 'nombre' => 'Amarilla · cambios en la actividad', 'color' => '#F2C200', 'nivel' => 'moderado' ),
		2 => array( 'nombre' => 'Naranja · erupción probable', 'color' => '#EF7D00', 'nivel' => 'alerta' ),
		1 => array( 'nombre' => 'Roja · erupción inminente o en curso', 'color' => '#D12A1F', 'nivel' => 'critico' ),
		0 => array( 'nombre' => 'Sin nivel en la lista (ver boletín)', 'color' => '#8C8F94', 'nivel' => 'info' ),
	);

	/** Descripción oficial de cada nivel. */
	const DESCRIPCIONES = array(
		4 => 'Volcán activo en reposo.',
		3 => 'Cambios en el comportamiento de la actividad volcánica.',
		2 => 'Erupción probable en términos de días o semanas.',
		1 => 'Erupción inminente o en curso.',
		0 => 'La lista de boletines no trae el nivel; consulte el último boletín.',
	);

	/**
	 * Volcanes vigilados por el OVSP en Nariño. Coordenadas de la capa
	 * oficial de volcanes del SGC (srvags.sgc.gov.co, Hosted/Volcanes).
	 */
	const VOLCANES = array(
		'galeras'     => array( 'volcan' => 'Galeras', 'lat' => 1.2214, 'lon' => -77.3590 ),
		'cumbal'      => array( 'volcan' => 'Cumbal', 'lat' => 0.9513, 'lon' => -77.8890 ),
		'chiles'      => array( 'volcan' => 'Chiles', 'lat' => 0.8171, 'lon' => -77.9356 ),
		'cerro_negro' => array( 'volcan' => 'Cerro Negro de Mayasquer', 'lat' => 0.8306, 'lon' => -77.9640 ),
		'azufral'     => array( 'volcan' => 'Azufral', 'lat' => 1.0904, 'lon' => -77.7219 ),
		'dona_juana'  => array( 'volcan' => 'Doña Juana', 'lat' => 1.5005, 'lon' => -76.9379 ),
		'las_animas'  => array( 'volcan' => 'Las Ánimas', 'lat' => 1.5692, 'lon' => -76.8618 ),
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
		return 'Nivel de actividad de los volcanes de Nariño (Galeras, Cumbal, Chiles, Cerro Negro, Azufral, Doña Juana y Las Ánimas) según los boletines del Observatorio Vulcanológico y Sismológico de Pasto, con enlace a cada boletín.';
	}

	/** @return string[] */
	public function hosts() {
		return array( 'www2.sgc.gov.co' );
	}

	/** @return string */
	public function atribucion() {
		return 'Servicio Geológico Colombiano — Observatorio Vulcanológico y Sismológico de Pasto';
	}

	/** @return string */
	public function licencia() {
		return 'Información pública (Ley 1712 de 2014)';
	}

	/**
	 * Página pública de volcanes del SGC (para personas: rechaza clientes
	 * automatizados, por eso no se consulta desde el plugin).
	 *
	 * @return string
	 */
	public function url_doc() {
		return 'https://www.sgc.gov.co/volcanes';
	}

	/** @return string */
	public function frecuencia() {
		return 'Boletín semanal (martes) y extraordinarios el mismo día';
	}

	/** @return array */
	public function config_defecto() {
		$c            = parent::config_defecto();
		$c['ttl']     = 60;
		$c['timeout'] = 20;
		return $c;
	}

	/**
	 * URL de la lista de boletines del OVSP.
	 *
	 * @param int $cantidad Boletines más recientes.
	 * @return string
	 */
	private function url_lista( $cantidad ) {
		return self::SITIO . self::LISTA . '?$select=' . self::CAMPOS
			. '&$filter=' . rawurlencode( "Observatorio eq 'Observatorio Pasto'" )
			. '&$orderby=' . rawurlencode( 'Emision desc' )
			. '&$top=' . (int) $cantidad;
	}

	/**
	 * Opciones HTTP: respuesta JSON sin metadatos de OData.
	 *
	 * @return array
	 */
	private function opciones_http() {
		return array( 'headers' => array( 'Accept' => 'application/json;odata=nometadata' ) );
	}

	/**
	 * Código de nivel (4 verde … 1 roja; 0 sin dato) a partir del texto.
	 *
	 * @param mixed $texto Valor del campo de nivel.
	 * @return int
	 */
	public static function codigo_nivel( $texto ) {
		$t = remove_accents( mb_strtolower( trim( (string) $texto ), 'UTF-8' ) );
		if ( 0 === strpos( $t, 'verde' ) ) {
			return 4;
		}
		if ( 0 === strpos( $t, 'amarill' ) ) {
			return 3;
		}
		if ( 0 === strpos( $t, 'naranja' ) ) {
			return 2;
		}
		if ( 0 === strpos( $t, 'roj' ) ) {
			return 1;
		}
		return 0;
	}

	/**
	 * Claves de los volcanes que nombra un valor del campo «Volcán».
	 *
	 * @param string $nombre Nombre tal como viene (puede traer espacios de ancho cero).
	 * @return string[]
	 */
	public static function claves_volcan( $nombre ) {
		$n = remove_accents( mb_strtolower( preg_replace( '/[\x{200B}-\x{200D}\x{FEFF}]/u', '', (string) $nombre ), 'UTF-8' ) );
		if ( false !== strpos( $n, 'chiles' ) && false !== strpos( $n, 'negro' ) ) {
			return array( 'chiles', 'cerro_negro' );
		}
		$mapa = array(
			'galeras'     => 'galeras',
			'cumbal'      => 'cumbal',
			'chiles'      => 'chiles',
			'cerro negro' => 'cerro_negro',
			'azufral'     => 'azufral',
			'dona juana'  => 'dona_juana',
			'animas'      => 'las_animas',
		);
		foreach ( $mapa as $buscar => $clave ) {
			if ( false !== strpos( $n, $buscar ) ) {
				return array( $clave );
			}
		}
		return array();
	}

	/**
	 * URL absoluta del PDF del boletín, solo dentro de la biblioteca esperada.
	 *
	 * @param string $ruta FileRef.
	 * @return string
	 */
	public static function url_boletin( $ruta ) {
		$ruta = (string) $ruta;
		if ( 0 !== strpos( $ruta, '/Noticias/boletinesDocumentos/' ) || false !== strpos( $ruta, '..' ) ) {
			return '';
		}
		return esc_url_raw( self::SITIO . implode( '/', array_map( 'rawurlencode', explode( '/', $ruta ) ) ) );
	}

	/**
	 * Estado de cada volcán a partir de la lista de boletines (más reciente
	 * primero). Pública para las pruebas unitarias.
	 *
	 * @param array $items Elementos `value` de la lista.
	 * @return array[] Filas por volcán, de mayor a menor nivel.
	 */
	public static function estado_volcanes( array $items ) {
		$nivel  = array();
		$ultimo = array();
		foreach ( $items as $it ) {
			if ( ! is_array( $it ) ) {
				continue;
			}
			$claves = array();
			foreach ( (array) ( $it['Volc_x00e1_n'] ?? array() ) as $v ) {
				$claves = array_merge( $claves, self::claves_volcan( $v ) );
			}
			$claves = array_values( array_unique( $claves ) );
			if ( ! $claves ) {
				continue;
			}
			$tipo    = (string) ( $it['Tipo_x002d_comunicado'] ?? '' );
			$datos   = array(
				'fecha'  => substr( (string) ( $it['Emision'] ?? '' ), 0, 10 ),
				'nombre' => trim( preg_replace( '/[\x{200B}-\x{200D}\x{FEFF}]/u', '', wp_strip_all_tags( (string) ( $it['Title'] ?? '' ) ) ) ),
				'tipo'   => $tipo,
				'url'    => self::url_boletin( $it['FileRef'] ?? '' ),
			);
			$codigo  = self::codigo_nivel( $it['Nivel_x0020_de_x0020_actividad'] ?? '' );
			$mensual = false !== stripos( $tipo, 'mensual' );
			foreach ( $claves as $k ) {
				if ( ! isset( $ultimo[ $k ] ) ) {
					$ultimo[ $k ] = $datos;
				}
				// Solo boletines de un volcán (o del par Chiles–Cerro Negro) fijan el nivel.
				if ( ! isset( $nivel[ $k ] ) && $codigo && ! $mensual && count( $claves ) <= 2 ) {
					$nivel[ $k ] = array( 'codigo' => $codigo ) + $datos;
				}
			}
		}

		$out = array();
		foreach ( self::VOLCANES as $k => $v ) {
			$b     = $nivel[ $k ] ?? ( $ultimo[ $k ] ?? array() );
			$cod   = (int) ( $nivel[ $k ]['codigo'] ?? 0 );
			$n     = self::NIVELES[ $cod ];
			$out[] = array(
				'volcan'         => $v['volcan'],
				'nivel_codigo'   => $cod,
				'nivel'          => $n['nombre'],
				'descripcion'    => self::DESCRIPCIONES[ $cod ],
				'color'          => $n['color'],
				'categoria'      => $n['nombre'],
				'lat'            => $v['lat'],
				'lon'            => $v['lon'],
				'boletin_fecha'  => (string) ( $b['fecha'] ?? '' ),
				'boletin_tipo'   => (string) ( $b['tipo'] ?? '' ),
				'boletin_nombre' => (string) ( $b['nombre'] ?? '' ),
				'boletin_url'    => (string) ( $b['url'] ?? '' ),
				'valor'          => $cod ? 5 - $cod : 0,
			);
		}
		usort(
			$out,
			function ( $a, $b ) {
				$pa = $a['nivel_codigo'] ?: 9;
				$pb = $b['nivel_codigo'] ?: 9;
				return $pa <=> $pb ?: strcmp( $a['volcan'], $b['volcan'] );
			}
		);
		return $out;
	}

	/**
	 * Volcanes de Nariño con su nivel y último boletín.
	 *
	 * @return array 'datos' => filas.
	 */
	public function volcanes() {
		return $this->get_procesado(
			'boletines',
			$this->url_lista( 100 ),
			function ( $json ) {
				if ( ! is_array( $json ) || ! isset( $json['value'] ) || ! is_array( $json['value'] ) ) {
					return null;
				}
				return self::estado_volcanes( $json['value'] );
			},
			$this->opciones_http()
		);
	}

	/** @return array */
	public function probar() {
		$h  = SAN_Http::get( $this->id(), $this->url_lista( 10 ), array_merge( $this->opciones_http(), array( 'timeout' => (int) $this->config()['timeout'] ) ) );
		$ok = $h['ok'] && isset( $h['datos']['value'] ) && is_array( $h['datos']['value'] ) && $h['datos']['value'];
		$ul = $ok ? $h['datos']['value'][0] : array();
		$g  = $ok ? current(
			array_filter(
				self::estado_volcanes( $h['datos']['value'] ),
				function ( $f ) {
					return 'Galeras' === $f['volcan'];
				}
			)
		) : null;
		return $this->resultado_prueba(
			$h,
			$ok,
			$ok ? sprintf( 'Último boletín: %s. Galeras: %s.', wp_strip_all_tags( (string) ( $ul['Title'] ?? '' ) ), $g ? $g['nivel'] : 'sin boletín reciente' ) : 'La respuesta no trae la lista de boletines (¿cambió la API del SGC?).',
			$ok ? substr( (string) ( $ul['Emision'] ?? '' ), 0, 16 ) . ' UTC' : '',
			$g ? array( 'Galeras' => array( 'nivel' => $g['nivel'], 'boletin' => $g['boletin_fecha'] ) ) : array()
		);
	}
}
