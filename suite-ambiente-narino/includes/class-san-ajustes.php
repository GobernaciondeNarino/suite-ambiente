<?php
/**
 * Ajustes del plugin: valores por defecto, lectura y saneamiento.
 *
 * Se guardan en dos opciones:
 *  - `san_ajustes`: generales y del tablero (autoload).
 *  - `san_fuentes`: configuración por fuente de datos (sin autoload), con
 *    las claves de API cifradas.
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Ajustes {

	const OPCION         = 'san_ajustes';
	const OPCION_FUENTES = 'san_fuentes';

	/** @var array|null Caché en memoria. */
	private static $cache = null;

	/**
	 * Valores por defecto de los ajustes generales y del tablero.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'general' => array(
				'libreria'          => 'local',   // local | cdn.
				'fuentes_google'    => true,      // Hind Madurai + Nunito Sans (identidad visual).
				'municipio_defecto' => '52001',   // Pasto.
				'tema'              => 'auto',    // auto | claro | oscuro.
				'renderer'          => 'svg',     // svg | canvas (D3plus v4).
				'd3plus_zoom'       => false,
				'd3plus_busqueda'   => false,
				'd3plus_tabla'      => false,     // El plugin trae su propia vista de tabla.
				'd3plus_minimapa'   => false,
				'limite_peticiones' => 300,       // Por minuto e IP en la API pública (una página con muchas tarjetas hace ~50 peticiones).
				'nivel_log'         => 'info',    // depuracion | info | advertencia | error.
				'retencion_logs'    => 30,        // Días.
				'atribucion'        => true,
			),
			'tablero' => array(
				'titulo'           => 'Observatorio ambiental de Nariño',
				'visualizaciones'  => array( 'clima_mapa_temperatura', 'clima_pronostico_temperatura', 'aire_ica_municipios', 'hidro_caudal_rios', 'sismos_mapa', 'bio_reinos' ),
				'columnas'         => 2,
				'mostrar_analisis' => true,
				'actualizar_min'   => 0,
			),
		);
	}

	/**
	 * Siembra los ajustes por defecto sin pisar los existentes.
	 */
	public static function sembrar() {
		$actual = get_option( self::OPCION, array() );
		$actual = is_array( $actual ) ? $actual : array();
		update_option( self::OPCION, self::fusionar( self::defaults(), $actual ), true );
		if ( false === get_option( self::OPCION_FUENTES, false ) ) {
			add_option( self::OPCION_FUENTES, array(), '', false );
		}
		self::$cache = null;
	}

	/**
	 * Devuelve todos los ajustes o un grupo.
	 *
	 * @param string|null $grupo general | tablero.
	 * @return array
	 */
	public static function get( $grupo = null ) {
		if ( null === self::$cache ) {
			$guardado    = get_option( self::OPCION, array() );
			self::$cache = self::fusionar( self::defaults(), is_array( $guardado ) ? $guardado : array() );
		}
		if ( null === $grupo ) {
			return self::$cache;
		}
		return isset( self::$cache[ $grupo ] ) ? self::$cache[ $grupo ] : array();
	}

	/**
	 * Lee un valor general.
	 *
	 * @param string $clave   Clave.
	 * @param mixed  $defecto Valor por defecto.
	 * @return mixed
	 */
	public static function general( $clave, $defecto = null ) {
		$g = self::get( 'general' );
		return array_key_exists( $clave, $g ) ? $g[ $clave ] : $defecto;
	}

	/**
	 * Guarda un grupo de ajustes ya saneado.
	 *
	 * @param string $grupo  general | tablero.
	 * @param array  $valores Valores crudos.
	 */
	public static function guardar( $grupo, array $valores ) {
		$todos = self::get();
		if ( 'general' === $grupo ) {
			$todos['general'] = self::sanear_general( $valores, $todos['general'] );
		} elseif ( 'tablero' === $grupo ) {
			$todos['tablero'] = self::sanear_tablero( $valores );
		}
		update_option( self::OPCION, $todos, true );
		self::$cache = null;
	}

	/**
	 * Sanea los ajustes generales.
	 *
	 * @param array $v      Valores crudos.
	 * @param array $previo Valores actuales.
	 * @return array
	 */
	private static function sanear_general( array $v, array $previo ) {
		$d   = self::defaults()['general'];
		$out = $previo;

		$out['libreria']          = in_array( $v['libreria'] ?? '', array( 'local', 'cdn' ), true ) ? $v['libreria'] : $d['libreria'];
		$out['tema']              = in_array( $v['tema'] ?? '', array( 'auto', 'claro', 'oscuro' ), true ) ? $v['tema'] : $d['tema'];
		$out['renderer']          = in_array( $v['renderer'] ?? '', array( 'svg', 'canvas' ), true ) ? $v['renderer'] : $d['renderer'];
		$out['nivel_log']         = in_array( $v['nivel_log'] ?? '', array_keys( SAN_Logger::NIVELES ), true ) ? $v['nivel_log'] : $d['nivel_log'];
		$municipio                = SAN_Seguridad::sanear_divipola( $v['municipio_defecto'] ?? '' );
		$out['municipio_defecto'] = $municipio ? $municipio : $d['municipio_defecto'];
		$out['limite_peticiones'] = max( 10, min( 1000, absint( $v['limite_peticiones'] ?? $d['limite_peticiones'] ) ) );
		$out['retencion_logs']    = max( 1, min( 365, absint( $v['retencion_logs'] ?? $d['retencion_logs'] ) ) );

		foreach ( array( 'fuentes_google', 'd3plus_zoom', 'd3plus_busqueda', 'd3plus_tabla', 'd3plus_minimapa', 'atribucion' ) as $bool ) {
			$out[ $bool ] = ! empty( $v[ $bool ] );
		}
		return $out;
	}

	/**
	 * Sanea los ajustes del tablero.
	 *
	 * @param array $v Valores crudos.
	 * @return array
	 */
	private static function sanear_tablero( array $v ) {
		$ids     = isset( $v['visualizaciones'] ) && is_array( $v['visualizaciones'] ) ? $v['visualizaciones'] : array();
		$orden   = isset( $v['orden'] ) && is_array( $v['orden'] ) ? $v['orden'] : array();
		$validos = array();
		foreach ( $ids as $id ) {
			$id = SAN_Seguridad::sanear_id( $id );
			if ( $id && SAN_Catalogo::existe( $id ) && ! in_array( $id, $validos, true ) ) {
				$validos[] = $id;
			}
		}
		// Orden indicado en el formulario (vacío = al final, estable).
		$pos = array_flip( $validos );
		usort(
			$validos,
			function ( $a, $b ) use ( $orden, $pos ) {
				$oa = isset( $orden[ $a ] ) && is_numeric( $orden[ $a ] ) ? (int) $orden[ $a ] : 1000 + $pos[ $a ];
				$ob = isset( $orden[ $b ] ) && is_numeric( $orden[ $b ] ) ? (int) $orden[ $b ] : 1000 + $pos[ $b ];
				return $oa === $ob ? $pos[ $a ] - $pos[ $b ] : $oa - $ob;
			}
		);
		return array(
			'titulo'           => sanitize_text_field( $v['titulo'] ?? '' ),
			'visualizaciones'  => $validos,
			'columnas'         => max( 1, min( 4, absint( $v['columnas'] ?? 2 ) ) ),
			'mostrar_analisis' => ! empty( $v['mostrar_analisis'] ),
			'actualizar_min'   => min( 240, absint( $v['actualizar_min'] ?? 0 ) ),
		);
	}

	/* ------------------------------------------------------------------
	 * Configuración por fuente
	 * ---------------------------------------------------------------- */

	/**
	 * Configuración efectiva de una fuente (defaults de la clase + guardado).
	 * La clave de API se devuelve descifrada: úsese solo en el servidor.
	 *
	 * @param string $id Identificador de la fuente.
	 * @return array
	 */
	public static function fuente( $id ) {
		$fuente = SAN_Fuentes::obtener( $id );
		$base   = $fuente ? $fuente->config_defecto() : array();
		$todas  = get_option( self::OPCION_FUENTES, array() );
		$propia = is_array( $todas ) && isset( $todas[ $id ] ) && is_array( $todas[ $id ] ) ? $todas[ $id ] : array();
		$cfg    = self::fusionar( $base, $propia );
		if ( ! empty( $cfg['clave'] ) ) {
			$cfg['clave'] = SAN_Seguridad::descifrar( $cfg['clave'] );
		}
		return $cfg;
	}

	/**
	 * Guarda la configuración de una fuente a partir del formulario.
	 *
	 * @param string $id    Identificador de la fuente.
	 * @param array  $valor Valores crudos del formulario.
	 */
	public static function guardar_fuente( $id, array $valor ) {
		$fuente = SAN_Fuentes::obtener( $id );
		if ( ! $fuente ) {
			return;
		}
		$todas = get_option( self::OPCION_FUENTES, array() );
		$todas = is_array( $todas ) ? $todas : array();
		$prev  = isset( $todas[ $id ] ) && is_array( $todas[ $id ] ) ? $todas[ $id ] : array();
		$def   = $fuente->config_defecto();

		$nuevo = array(
			'activa'  => ! empty( $valor['activa'] ),
			'ttl'     => max( 5, min( 10080, absint( $valor['ttl'] ?? $def['ttl'] ) ) ),
			'timeout' => max( 3, min( 60, absint( $valor['timeout'] ?? $def['timeout'] ) ) ),
			'params'  => $fuente->sanear_params( isset( $valor['params'] ) && is_array( $valor['params'] ) ? $valor['params'] : array() ),
		);

		// La clave solo se reemplaza si se escribe una nueva; un campo vacío la conserva
		// y la casilla "borrar" la elimina.
		if ( ! empty( $valor['borrar_clave'] ) ) {
			$nuevo['clave'] = '';
		} elseif ( isset( $valor['clave'] ) && '' !== trim( (string) $valor['clave'] ) ) {
			$nuevo['clave'] = SAN_Seguridad::cifrar( sanitize_text_field( wp_unslash( $valor['clave'] ) ) );
		} else {
			$nuevo['clave'] = $prev['clave'] ?? '';
		}

		$todas[ $id ] = $nuevo;
		update_option( self::OPCION_FUENTES, $todas, false );
	}

	/**
	 * Fusión recursiva que conserva claves por defecto ausentes en `$guardado`.
	 * Las listas (arrays con índices numéricos) del valor guardado reemplazan
	 * a las por defecto en lugar de mezclarse.
	 *
	 * @param array $defecto  Valores por defecto.
	 * @param array $guardado Valores guardados.
	 * @return array
	 */
	public static function fusionar( array $defecto, array $guardado ) {
		foreach ( $guardado as $k => $v ) {
			if ( is_array( $v ) && isset( $defecto[ $k ] ) && is_array( $defecto[ $k ] ) && ! self::es_lista( $v ) && ! self::es_lista( $defecto[ $k ] ) ) {
				$defecto[ $k ] = self::fusionar( $defecto[ $k ], $v );
			} else {
				$defecto[ $k ] = $v;
			}
		}
		return $defecto;
	}

	/**
	 * ¿Es un array con índices 0..n-1?
	 *
	 * @param array $a Array.
	 * @return bool
	 */
	private static function es_lista( array $a ) {
		return array() === $a || array_keys( $a ) === range( 0, count( $a ) - 1 );
	}
}
