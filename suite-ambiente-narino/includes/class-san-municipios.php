<?php
/**
 * Tabla maestra de los 64 municipios de Nariño y sus 14 subregiones.
 *
 * Fuente: `data/narino_municipios.json` (DANE DIVIPOLA + MGN 2018). Sirve
 * como lista blanca de seguridad y para resolver coordenadas de consulta
 * (cabecera municipal) en las APIs meteorológicas.
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Municipios {

	/** Caja envolvente del departamento (sur, oeste, norte, este). */
	const BBOX = array(
		'sur'   => 0.35,
		'oeste' => -79.05,
		'norte' => 2.70,
		'este'  => -76.80,
	);

	/** Subregiones con influencia del litoral Pacífico. */
	const LITORAL = array( 'Sanquianga', 'Pacífico Sur', 'Telembí', 'Pie de Monte Costero' );

	/** @var array[]|null */
	private static $lista = null;

	/** @var array|null divipola => fila. */
	private static $indice = null;

	/** @var array|null nombre normalizado => divipola. */
	private static $nombres = null;

	/**
	 * Los 64 municipios ordenados por nombre.
	 *
	 * @return array[] { divipola, nombre, subregion, lat, lon, centroide_lat, centroide_lon, area_km2, poblacion_censo_2018 }
	 */
	public static function todos() {
		if ( null === self::$lista ) {
			$json        = file_get_contents( SAN_DIR . 'data/narino_municipios.json' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			$datos       = json_decode( (string) $json, true );
			self::$lista = isset( $datos['municipios'] ) && is_array( $datos['municipios'] ) ? $datos['municipios'] : array();
		}
		return self::$lista;
	}

	/**
	 * Municipio por código DIVIPOLA.
	 *
	 * @param string $divipola Código de 5 dígitos.
	 * @return array|null
	 */
	public static function por_divipola( $divipola ) {
		self::indexar();
		return self::$indice[ (string) $divipola ] ?? null;
	}

	/**
	 * Busca por código o por nombre (sin tildes ni mayúsculas).
	 *
	 * @param string $valor Código o nombre.
	 * @return array|null
	 */
	public static function buscar( $valor ) {
		$valor = trim( (string) $valor );
		if ( '' === $valor ) {
			return null;
		}
		if ( preg_match( '/^\d{5}$/', $valor ) ) {
			return self::por_divipola( $valor );
		}
		self::indexar();
		$n = self::normalizar( $valor );
		if ( isset( self::$nombres[ $n ] ) ) {
			return self::$indice[ self::$nombres[ $n ] ];
		}
		// Alias frecuentes.
		$alias = array(
			'tumaco'   => '52835',
			'carlosama' => '52224',
			'cuaspud'  => '52224',
			'tablon'   => '52258',
			'el tablon' => '52258',
		);
		return isset( $alias[ $n ] ) ? self::$indice[ $alias[ $n ] ] : null;
	}

	/**
	 * Subregiones con sus municipios.
	 *
	 * @return array nombre => divipola[]
	 */
	public static function subregiones() {
		$out = array();
		foreach ( self::todos() as $m ) {
			$out[ $m['subregion'] ][] = $m['divipola'];
		}
		ksort( $out );
		return $out;
	}

	/**
	 * ¿El municipio está en el litoral Pacífico?
	 *
	 * @param string $divipola Código.
	 * @return bool
	 */
	public static function es_litoral( $divipola ) {
		$m = self::por_divipola( $divipola );
		return $m && in_array( $m['subregion'], self::LITORAL, true );
	}

	/**
	 * Nombre legible en formato título ("San Andrés de Tumaco").
	 *
	 * @param string $divipola Código.
	 * @return string
	 */
	public static function nombre( $divipola ) {
		$m = self::por_divipola( $divipola );
		if ( ! $m ) {
			return '';
		}
		$t = mb_convert_case( mb_strtolower( $m['nombre'], 'UTF-8' ), MB_CASE_TITLE, 'UTF-8' );
		return str_replace( array( ' De ', ' Del ', ' La ', ' Los ', ' El ' ), array( ' de ', ' del ', ' la ', ' los ', ' el ' ), $t );
	}

	/**
	 * Nombre corto de una subregión (para ejes de gráficos).
	 *
	 * @param string $subregion Nombre completo.
	 * @return string
	 */
	public static function subregion_corta( $subregion ) {
		$cortos = array(
			'Ex-Provincia de Obando'    => 'Obando',
			'Centro-Occidente / Abades' => 'Abades',
			'Pie de Monte Costero'      => 'Piedemonte',
			'Abades-La Llanada'         => 'La Llanada',
			'Frontera Pacífica'         => 'Frontera',
		);
		return $cortos[ $subregion ] ?? $subregion;
	}

	/**
	 * Opciones para selects: divipola => nombre.
	 *
	 * @return array
	 */
	public static function opciones() {
		$out = array();
		foreach ( self::todos() as $m ) {
			$out[ $m['divipola'] ] = self::nombre( $m['divipola'] );
		}
		return $out;
	}

	/**
	 * Construye los índices.
	 */
	private static function indexar() {
		if ( null !== self::$indice ) {
			return;
		}
		self::$indice  = array();
		self::$nombres = array();
		foreach ( self::todos() as $m ) {
			self::$indice[ $m['divipola'] ]                    = $m;
			self::$nombres[ self::normalizar( $m['nombre'] ) ] = $m['divipola'];
		}
	}

	/**
	 * Minúsculas sin tildes ni signos.
	 *
	 * @param string $s Texto.
	 * @return string
	 */
	public static function normalizar( $s ) {
		$s = remove_accents( mb_strtolower( (string) $s, 'UTF-8' ) );
		$s = preg_replace( '/[^a-z0-9 ]/', ' ', $s );
		return trim( preg_replace( '/\s+/', ' ', $s ) );
	}
}
