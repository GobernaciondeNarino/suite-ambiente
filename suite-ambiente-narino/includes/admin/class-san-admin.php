<?php
/**
 * Administración: menú, recursos y manejadores de formularios.
 *
 * Módulos:
 *   Gráficos        Pestañas por API y subgrupos; una tarjeta por
 *                   visualización con sus shortcodes y vista previa.
 *   Datos abiertos  Recursos publicados, endpoints y vista previa.
 *   Configuración   Pestañas Tablero, APIs (configurar y verificar),
 *                   Registros (logs) y General.
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Admin {

	const CAPACIDAD = 'manage_options';

	/** @var string[] Hooks de las páginas del plugin. */
	private $hooks = array();

	/**
	 * Hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'recursos' ) );
		add_action( 'admin_post_san_guardar_general', array( $this, 'guardar_general' ) );
		add_action( 'admin_post_san_guardar_tablero', array( $this, 'guardar_tablero' ) );
		add_action( 'admin_post_san_guardar_fuentes', array( $this, 'guardar_fuentes' ) );
		add_action( 'admin_post_san_exportar_logs', array( $this, 'exportar_logs' ) );
		add_filter( 'plugin_action_links_' . SAN_BASENAME, array( $this, 'enlaces_plugin' ) );
	}

	/**
	 * Menú y submenús.
	 */
	public function menu() {
		$icono = 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="black" d="M10 1C6 5 4 8 4 11a6 6 0 0 0 12 0c0-3-2-6-6-10Zm-1 15.9A4.5 4.5 0 0 1 5.6 12H7a3 3 0 0 0 2 2.8v2.1Z"/></svg>' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions

		$this->hooks[] = add_menu_page( __( 'Suite Ambiente Nariño', 'suite-ambiente-narino' ), __( 'Suite Ambiente', 'suite-ambiente-narino' ), self::CAPACIDAD, 'san-graficos', array( SAN_Admin_Graficos::class, 'pagina' ), $icono, 58 );
		$this->hooks[] = add_submenu_page( 'san-graficos', __( 'Gráficos', 'suite-ambiente-narino' ), __( 'Gráficos', 'suite-ambiente-narino' ), self::CAPACIDAD, 'san-graficos', array( SAN_Admin_Graficos::class, 'pagina' ) );
		$this->hooks[] = add_submenu_page( 'san-graficos', __( 'Datos abiertos', 'suite-ambiente-narino' ), __( 'Datos abiertos', 'suite-ambiente-narino' ), self::CAPACIDAD, 'san-datos', array( SAN_Admin_Datos::class, 'pagina' ) );
		$this->hooks[] = add_submenu_page( 'san-graficos', __( 'Configuración', 'suite-ambiente-narino' ), __( 'Configuración', 'suite-ambiente-narino' ), self::CAPACIDAD, 'san-config', array( SAN_Admin_Config::class, 'pagina' ) );
	}

	/**
	 * Recursos solo en las páginas del plugin.
	 *
	 * @param string $hook Hook de la página.
	 */
	public function recursos( $hook ) {
		if ( ! in_array( $hook, $this->hooks, true ) ) {
			return;
		}
		SAN_Assets::registrar();
		wp_enqueue_style( 'san-front' );
		wp_enqueue_style( 'san-admin', SAN_URL . 'assets/css/san-admin.css', array( 'san-front' ), SAN_VERSION );
		wp_enqueue_script( 'san-graficos' );
		wp_enqueue_script( 'san-admin', SAN_URL . 'assets/js/san-admin.js', array( 'san-graficos' ), SAN_VERSION, true );
		wp_localize_script(
			'san-admin',
			'SAN_ADMIN',
			array(
				'rest'   => esc_url_raw( rest_url( SAN_REST_NS . '/' ) ),
				'nonce'  => wp_create_nonce( 'wp_rest' ),
				'textos' => array(
					'copiado'   => __( 'Copiado', 'suite-ambiente-narino' ),
					'copiar'    => __( 'Copiar', 'suite-ambiente-narino' ),
					'probando'  => __( 'Probando…', 'suite-ambiente-narino' ),
					'ok'        => __( 'Funciona', 'suite-ambiente-narino' ),
					'falla'     => __( 'Falla', 'suite-ambiente-narino' ),
					'confirmar' => __( '¿Seguro? Esta acción no se puede deshacer.', 'suite-ambiente-narino' ),
					'sin_logs'  => __( 'No hay registros con esos filtros.', 'suite-ambiente-narino' ),
					'error'     => __( 'Error al consultar la API del plugin.', 'suite-ambiente-narino' ),
				),
			)
		);
	}

	/**
	 * Enlaces en la lista de plugins.
	 *
	 * @param array $enlaces Enlaces.
	 * @return array
	 */
	public function enlaces_plugin( $enlaces ) {
		array_unshift( $enlaces, '<a href="' . esc_url( admin_url( 'admin.php?page=san-config' ) ) . '">' . esc_html__( 'Configuración', 'suite-ambiente-narino' ) . '</a>' );
		return $enlaces;
	}

	/* ------------------------------------------------------------------
	 * Manejadores (admin-post) — capacidad + nonce
	 * ---------------------------------------------------------------- */

	/**
	 * Verifica permisos y nonce.
	 *
	 * @param string $accion Acción del nonce.
	 */
	private function verificar( $accion ) {
		if ( ! current_user_can( self::CAPACIDAD ) ) {
			wp_die( esc_html__( 'No tiene permisos para esta acción.', 'suite-ambiente-narino' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( $accion );
	}

	/**
	 * Redirige de vuelta con aviso.
	 *
	 * @param string $pestana Pestaña.
	 * @param string $aviso   Código de aviso.
	 */
	private function volver( $pestana, $aviso = 'guardado' ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'san-config',
					'pestana' => $pestana,
					'aviso'   => $aviso,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Guarda los ajustes generales.
	 */
	public function guardar_general() {
		$this->verificar( 'san_guardar_general' );
		$v = isset( $_POST['san'] ) && is_array( $_POST['san'] ) ? wp_unslash( $_POST['san'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- se sanea en SAN_Ajustes::guardar().
		SAN_Ajustes::guardar( 'general', $v );
		SAN_Logger::info( 'sistema', 'ajustes', 'Ajustes generales actualizados.' );
		$this->volver( 'general' );
	}

	/**
	 * Guarda el tablero.
	 */
	public function guardar_tablero() {
		$this->verificar( 'san_guardar_tablero' );
		$v = isset( $_POST['san'] ) && is_array( $_POST['san'] ) ? wp_unslash( $_POST['san'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- se sanea en SAN_Ajustes::guardar().
		SAN_Ajustes::guardar( 'tablero', $v );
		SAN_Logger::info( 'sistema', 'ajustes', 'Tablero actualizado.' );
		$this->volver( 'tablero' );
	}

	/**
	 * Guarda la configuración de las fuentes.
	 */
	public function guardar_fuentes() {
		$this->verificar( 'san_guardar_fuentes' );
		$todas = isset( $_POST['fuente'] ) && is_array( $_POST['fuente'] ) ? $_POST['fuente'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- se sanea por campo en guardar_fuente().
		foreach ( SAN_Fuentes::todas() as $id => $f ) {
			$crudo = isset( $todas[ $id ] ) && is_array( $todas[ $id ] ) ? $todas[ $id ] : array();
			SAN_Ajustes::guardar_fuente( $id, $crudo );
			SAN_Cache::vaciar( $id );
		}
		SAN_Logger::info( 'sistema', 'ajustes', 'Configuración de fuentes actualizada; caché de las fuentes vaciada.' );
		$this->volver( 'apis' );
	}

	/**
	 * Exporta los registros filtrados a CSV.
	 */
	public function exportar_logs() {
		$this->verificar( 'san_exportar_logs' );
		$r = SAN_Logger::consultar(
			array(
				'nivel'      => isset( $_GET['nivel'] ) ? sanitize_key( wp_unslash( $_GET['nivel'] ) ) : '',
				'fuente'     => isset( $_GET['fuente'] ) ? sanitize_key( wp_unslash( $_GET['fuente'] ) ) : '',
				'buscar'     => isset( $_GET['buscar'] ) ? sanitize_text_field( wp_unslash( $_GET['buscar'] ) ) : '',
				'por_pagina' => 500,
			)
		);
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="suite-ambiente-registros-' . gmdate( 'Ymd-His' ) . '.csv"' );
		echo "\xEF\xBB\xBF" . SAN_Datos_Abiertos::a_csv( $r['filas'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- CSV.
		exit;
	}

	/* ------------------------------------------------------------------
	 * Utilidades de presentación compartidas
	 * ---------------------------------------------------------------- */

	/**
	 * Encabezado común de las páginas.
	 *
	 * @param string $titulo    Título.
	 * @param string $subtitulo Subtítulo.
	 */
	public static function encabezado( $titulo, $subtitulo ) {
		echo '<div class="san-admin-cabecera"><div class="san-admin-marca" aria-hidden="true"><span></span><span></span></div><div><p class="san-admin-antetitulo">' . esc_html__( 'Gobernación de Nariño · Suite Ambiente', 'suite-ambiente-narino' ) . '</p><h1>' . esc_html( $titulo ) . '</h1><p class="san-admin-subtitulo">' . esc_html( $subtitulo ) . '</p></div></div>';
	}

	/**
	 * Pestañas accesibles (enlaces reales: funcionan sin JS).
	 *
	 * @param string $pagina  Slug de la página.
	 * @param array  $pestanas slug => etiqueta.
	 * @param string $activa  Pestaña activa.
	 */
	public static function pestanas( $pagina, array $pestanas, $activa ) {
		echo '<nav class="nav-tab-wrapper san-pestanas" aria-label="' . esc_attr__( 'Secciones', 'suite-ambiente-narino' ) . '">';
		foreach ( $pestanas as $slug => $etiqueta ) {
			$url = add_query_arg(
				array(
					'page'    => $pagina,
					'pestana' => $slug,
				),
				admin_url( 'admin.php' )
			);
			printf(
				'<a href="%1$s" class="nav-tab%2$s"%3$s>%4$s</a>',
				esc_url( $url ),
				$slug === $activa ? ' nav-tab-active' : '',
				$slug === $activa ? ' aria-current="page"' : '',
				wp_kses( $etiqueta, array( 'span' => array( 'class' => true ) ) )
			);
		}
		echo '</nav>';
	}

	/**
	 * Pestaña activa desde la URL, validada contra las permitidas.
	 *
	 * @param array  $permitidas Slugs permitidos.
	 * @param string $defecto    Por defecto.
	 * @return string
	 */
	public static function pestana_activa( array $permitidas, $defecto ) {
		$p = isset( $_GET['pestana'] ) ? sanitize_key( wp_unslash( $_GET['pestana'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return in_array( $p, $permitidas, true ) ? $p : $defecto;
	}

	/**
	 * Campo de shortcode con botón copiar.
	 *
	 * @param string $etiqueta Etiqueta.
	 * @param string $codigo   Shortcode.
	 * @param string $rol      Rol (para actualizarlo desde JS).
	 * @return string
	 */
	public static function campo_shortcode( $etiqueta, $codigo, $rol = '' ) {
		$uid = 'san-sc-' . wp_rand( 1000, 999999 );
		return '<div class="san-sc"><label for="' . esc_attr( $uid ) . '">' . esc_html( $etiqueta ) . '</label><div class="san-sc__fila"><input id="' . esc_attr( $uid ) . '" type="text" readonly value="' . esc_attr( $codigo ) . '" data-rol="' . esc_attr( $rol ) . '" class="san-sc__codigo"><button type="button" class="button san-copiar" data-copiar="' . esc_attr( $uid ) . '">' . esc_html__( 'Copiar', 'suite-ambiente-narino' ) . '</button></div></div>';
	}
}
