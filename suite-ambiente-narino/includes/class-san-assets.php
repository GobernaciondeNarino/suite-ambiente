<?php
/**
 * Registro de scripts y estilos del front.
 *
 * D3 v7 y D3plus v4 se sirven desde el propio plugin por defecto (sin
 * dependencias externas); opcionalmente desde jsDelivr con integridad SRI.
 * Los recursos solo se encolan cuando la página contiene un shortcode del
 * plugin.
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Assets {

	/** Versiones de las librerías incluidas en assets/vendor. */
	const D3_VERSION     = '7.9.0';
	const D3PLUS_VERSION = '4.5.0';

	/** Hashes SRI (sha384) de los bundles, idénticos en local y en jsDelivr. */
	const SRI = array(
		'san-d3'     => 'sha384-CjloA8y00+1SDAUkjs099PVfnY2KmDC2BZnws9kh8D/lX1s46w6EPhpXdqMfjK6i',
		'san-d3plus' => 'sha384-qlYEmkF5QB9ZKXpZGpQcL8l+2rfieTsBf4yuyAZSXpxesgG338iXZ8s9jnEBDwJJ',
	);

	/** Paleta categórica validada (modo claro / oscuro), máx. 6 series. */
	const PALETA = array(
		'claro'  => array( '#10A13B', '#1F5C99', '#E0A100', '#A4519A', '#2B93D2', '#D14124' ),
		'oscuro' => array( '#10A13B', '#4A7FC1', '#B98000', '#C35F9E', '#2B93D2', '#E0553A' ),
	);

	/** Shortcodes que activan la carga de recursos. */
	const SHORTCODES = array( 'san_grafico', 'san_card', 'san_tablero', 'san_mapa', 'san_datos', 'san_datos_abiertos', 'san_estado_apis', 'san_descripcion', 'san_analisis_cualitativo', 'san_analisis_cuantitativo' );

	/**
	 * Hooks.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'registrar' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'encolar_si_hay_shortcode' ), 20 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'registrar' ), 5 );
		add_filter( 'script_loader_tag', array( $this, 'atributos_sri' ), 10, 3 );
	}

	/**
	 * Registra los recursos (sin encolarlos).
	 */
	public static function registrar() {
		if ( wp_script_is( 'san-core', 'registered' ) ) {
			return;
		}
		$cdn = 'cdn' === SAN_Ajustes::general( 'libreria', 'local' );

		wp_register_script(
			'san-d3',
			$cdn ? 'https://cdn.jsdelivr.net/npm/d3@' . self::D3_VERSION . '/dist/d3.min.js' : SAN_URL . 'assets/vendor/d3/d3.min.js',
			array(),
			self::D3_VERSION,
			true
		);
		wp_register_script(
			'san-d3plus',
			$cdn ? 'https://cdn.jsdelivr.net/npm/@d3plus/core@' . self::D3PLUS_VERSION . '/umd/d3plus-core.full.min.js' : SAN_URL . 'assets/vendor/d3plus/d3plus-core.full.min.js',
			array(),
			self::D3PLUS_VERSION,
			true
		);

		wp_register_script( 'san-core', SAN_URL . 'assets/js/san-core.js', array(), SAN_VERSION, true );
		wp_register_script( 'san-d3-graficos', SAN_URL . 'assets/js/san-d3.js', array( 'san-core', 'san-d3' ), SAN_VERSION, true );
		wp_register_script( 'san-graficos', SAN_URL . 'assets/js/san-graficos.js', array( 'san-core', 'san-d3', 'san-d3plus', 'san-d3-graficos' ), SAN_VERSION, true );
		wp_add_inline_script( 'san-core', 'window.SAN_CONFIG = ' . wp_json_encode( self::config_js() ) . ';', 'before' );

		if ( SAN_Ajustes::general( 'fuentes_google', true ) ) {
			wp_register_style( 'san-fuentes', 'https://fonts.googleapis.com/css2?family=Hind+Madurai:wght@400;600;700&family=Nunito+Sans:opsz,wght@6..12,400;6..12,600;6..12,700&display=swap', array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
			wp_register_style( 'san-front', SAN_URL . 'assets/css/san-front.css', array( 'san-fuentes' ), SAN_VERSION );
		} else {
			wp_register_style( 'san-front', SAN_URL . 'assets/css/san-front.css', array(), SAN_VERSION );
		}
	}

	/**
	 * Encola los recursos del front (idempotente).
	 */
	public static function encolar() {
		self::registrar();
		wp_enqueue_style( 'san-front' );
		wp_enqueue_script( 'san-graficos' );
	}

	/**
	 * Encola en el <head> si el contenido principal ya contiene un shortcode,
	 * para evitar el parpadeo de estilos cargados tarde.
	 */
	public function encolar_si_hay_shortcode() {
		if ( ! is_singular() ) {
			return;
		}
		$post = get_post();
		if ( ! $post ) {
			return;
		}
		foreach ( self::SHORTCODES as $sc ) {
			if ( has_shortcode( $post->post_content, $sc ) ) {
				self::encolar();
				return;
			}
		}
	}

	/**
	 * Configuración expuesta al JavaScript (sin datos sensibles).
	 *
	 * @return array
	 */
	public static function config_js() {
		$g = SAN_Ajustes::get( 'general' );
		return array(
			'rest'       => esc_url_raw( rest_url( SAN_REST_NS . '/' ) ),
			// Solo usuarios con sesión: un nonce en una página en caché de un visitante
			// anónimo caduca y haría fallar la API pública con 403.
			'nonce'      => is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : '',
			'geo'        => array(
				'municipios'   => SAN_URL . 'data/narino_municipios.geojson',
				'subregiones'  => SAN_URL . 'data/narino_subregiones.geojson',
				'departamento' => SAN_URL . 'data/narino_departamento.geojson',
			),
			'municipios' => SAN_Municipios::opciones(),
			'defecto'    => $g['municipio_defecto'],
			'tema'       => $g['tema'],
			'paleta'     => self::PALETA,
			'd3plus'     => array(
				'renderer'  => $g['renderer'],
				'zoom'      => (bool) $g['d3plus_zoom'],
				'busqueda'  => (bool) $g['d3plus_busqueda'],
				'tabla'     => (bool) $g['d3plus_tabla'],
				'minimapa'  => (bool) $g['d3plus_minimapa'],
				'locale'    => 'es-ES',
			),
			'atribucion' => (bool) $g['atribucion'],
			'textos'     => array(
				'cargando'    => __( 'Cargando datos…', 'suite-ambiente-narino' ),
				'error'       => __( 'No fue posible cargar los datos. Intente de nuevo más tarde.', 'suite-ambiente-narino' ),
				'sin_datos'   => __( 'La fuente no reporta datos para esta consulta.', 'suite-ambiente-narino' ),
				'vencido'     => __( 'Mostrando la última copia disponible: la fuente no respondió.', 'suite-ambiente-narino' ),
				'tipo'        => __( 'Tipo de gráfico', 'suite-ambiente-narino' ),
				'municipio'   => __( 'Municipio', 'suite-ambiente-narino' ),
				'csv'         => __( 'CSV', 'suite-ambiente-narino' ),
				'json'        => __( 'JSON', 'suite-ambiente-narino' ),
				'png'         => __( 'PNG', 'suite-ambiente-narino' ),
				'fuente'      => __( 'Fuente', 'suite-ambiente-narino' ),
				'actualizado' => __( 'Actualizado', 'suite-ambiente-narino' ),
				'tabla'       => __( 'Tabla', 'suite-ambiente-narino' ),
				'tabla_cap'   => __( 'Datos del gráfico', 'suite-ambiente-narino' ),
			),
		);
	}

	/**
	 * Añade integrity/crossorigin a las librerías cuando se sirven por CDN.
	 *
	 * @param string $tag    Etiqueta <script>.
	 * @param string $handle Handle.
	 * @param string $src    URL.
	 * @return string
	 */
	public function atributos_sri( $tag, $handle, $src ) {
		if ( isset( self::SRI[ $handle ] ) && 0 === strpos( $src, 'https://cdn.jsdelivr.net/' ) ) {
			$tag = str_replace( ' src=', ' integrity="' . esc_attr( self::SRI[ $handle ] ) . '" crossorigin="anonymous" src=', $tag );
		}
		return $tag;
	}
}
