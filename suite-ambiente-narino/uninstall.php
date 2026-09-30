<?php
/**
 * Desinstalación: elimina tablas, opciones, transients y eventos del plugin.
 *
 * @package SuiteAmbienteNarino
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

// phpcs:disable WordPress.DB.DirectDatabaseQuery
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}san_cache" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}san_logs" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_san\\_%' OR option_name LIKE '\\_transient\\_timeout\\_san\\_%'" );
// phpcs:enable

foreach ( array( 'san_ajustes', 'san_fuentes', 'san_salud', 'san_db_version' ) as $opcion ) {
	delete_option( $opcion );
}

wp_clear_scheduled_hook( 'san_sincronizar' );
wp_clear_scheduled_hook( 'san_mantenimiento' );
