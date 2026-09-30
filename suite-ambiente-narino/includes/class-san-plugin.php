<?php
/**
 * Orquestador del plugin (singleton).
 *
 * Única responsabilidad: instanciar los subsistemas, cada uno de los cuales
 * registra sus propios hooks en el constructor.
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Plugin {

	/** @var SAN_Plugin|null */
	private static $instancia = null;

	/** @var SAN_Assets */
	public $assets;
	/** @var SAN_Rest */
	public $rest;
	/** @var SAN_Shortcodes */
	public $shortcodes;
	/** @var SAN_Cron */
	public $cron;
	/** @var SAN_Admin|null */
	public $admin = null;

	/**
	 * Devuelve (creando si hace falta) la instancia única.
	 *
	 * @return SAN_Plugin
	 */
	public static function instancia() {
		if ( null === self::$instancia ) {
			self::$instancia = new self();
		}
		return self::$instancia;
	}

	/**
	 * Registra los subsistemas.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'cargar_textdomain' ) );
		add_action( 'admin_init', array( SAN_Activador::class, 'migrar_si_necesario' ) );

		$this->assets     = new SAN_Assets();
		$this->rest       = new SAN_Rest();
		$this->shortcodes = new SAN_Shortcodes();
		$this->cron       = new SAN_Cron();

		if ( is_admin() ) {
			$this->admin = new SAN_Admin();
		}

		if ( defined( 'WP_CLI' ) && WP_CLI && class_exists( '\\WP_CLI' ) ) {
			\WP_CLI::add_command( 'suite-ambiente', SAN_Cli::class );
		}
	}

	/**
	 * Carga las traducciones.
	 */
	public function cargar_textdomain() {
		load_plugin_textdomain( 'suite-ambiente-narino', false, dirname( SAN_BASENAME ) . '/languages' );
	}

	/** Singleton: sin clonación ni deserialización. */
	private function __clone() {}

	/**
	 * @throws \Exception Siempre.
	 */
	public function __wakeup() {
		throw new \Exception( 'SAN_Plugin no se puede deserializar.' );
	}
}
