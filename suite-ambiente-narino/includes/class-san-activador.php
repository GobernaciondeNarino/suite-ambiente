<?php
/**
 * Ciclo de vida: activación, desactivación y migraciones de esquema.
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Activador {

	/**
	 * Activación: crea tablas, siembra ajustes y agenda el cron.
	 */
	public static function activar() {
		self::crear_tablas();
		SAN_Ajustes::sembrar();
		SAN_Cron::agendar();
		update_option( 'san_db_version', SAN_DB_VERSION, false );
		SAN_Logger::info( 'sistema', 'activacion', 'Plugin activado (versión ' . SAN_VERSION . ').' );
	}

	/**
	 * Desactivación: retira el cron. Los datos se conservan hasta desinstalar.
	 */
	public static function desactivar() {
		SAN_Cron::desagendar();
	}

	/**
	 * Aplica migraciones si la versión del esquema cambió (al actualizar el
	 * plugin sin reactivarlo).
	 */
	public static function migrar_si_necesario() {
		if ( get_option( 'san_db_version' ) === SAN_DB_VERSION ) {
			return;
		}
		self::activar();
	}

	/**
	 * Crea o actualiza las tablas de caché y registros con dbDelta.
	 */
	public static function crear_tablas() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$cache   = $wpdb->prefix . 'san_cache';
		$logs    = $wpdb->prefix . 'san_logs';

		dbDelta(
			"CREATE TABLE {$cache} (
				clave varchar(191) NOT NULL,
				grupo varchar(40) NOT NULL DEFAULT 'general',
				valor longtext NOT NULL,
				creado datetime NOT NULL,
				expira datetime NOT NULL,
				PRIMARY KEY  (clave),
				KEY grupo (grupo),
				KEY expira (expira)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$logs} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				fecha datetime NOT NULL,
				nivel varchar(12) NOT NULL,
				fuente varchar(40) NOT NULL,
				evento varchar(60) NOT NULL,
				mensaje text NOT NULL,
				contexto longtext NULL,
				http_codigo smallint(5) unsigned NULL,
				duracion_ms int(10) unsigned NULL,
				PRIMARY KEY  (id),
				KEY fecha (fecha),
				KEY nivel (nivel),
				KEY fuente (fuente)
			) {$charset};"
		);
	}
}
