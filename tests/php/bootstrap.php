<?php
/**
 * Arranque mínimo para probar las clases puras del plugin sin WordPress.
 * Solo se definen las funciones de WordPress que esas clases usan.
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'SAN_DIR', dirname( __DIR__, 2 ) . '/suite-ambiente-narino/' );
define( 'SAN_URL', 'https://ejemplo.gov.co/wp-content/plugins/suite-ambiente-narino/' );
define( 'SAN_VERSION', 'test' );
define( 'SAN_REST_NS', 'suite-ambiente/v1' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'HOUR_IN_SECONDS', 3600 );

function __( $t ) {
	return $t;
}
function apply_filters( $hook, $valor ) {
	return $valor;
}
function wp_salt( $s = 'auth' ) {
	return 'sal-de-prueba-' . $s;
}
function wp_json_encode( $v, $f = 0 ) {
	return json_encode( $v, $f | JSON_UNESCAPED_UNICODE );
}
function wp_strip_all_tags( $s ) {
	return trim( strip_tags( preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', (string) $s ) ) );
}
function sanitize_text_field( $s ) {
	return trim( wp_strip_all_tags( (string) $s ) );
}
function sanitize_key( $s ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $s ) );
}
function wp_unslash( $v ) {
	return $v;
}
function remove_accents( $s ) {
	return strtr( $s, array( 'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n' ) );
}

function wp_parse_url( $url, $componente = -1 ) {
	return parse_url( $url, $componente );
}
function wp_parse_str( $cadena, &$salida ) {
	parse_str( (string) $cadena, $salida );
}
function add_query_arg( $args, $url ) {
	return $url . ( false === strpos( $url, '?' ) ? '?' : '&' ) . http_build_query( $args, '', '&', PHP_QUERY_RFC3986 );
}
// Opciones en memoria.
$GLOBALS['san_opciones_prueba'] = array();
function get_option( $nombre, $defecto = false ) {
	return array_key_exists( $nombre, $GLOBALS['san_opciones_prueba'] ) ? $GLOBALS['san_opciones_prueba'][ $nombre ] : $defecto;
}
function update_option( $nombre, $valor, $autoload = null ) {
	$GLOBALS['san_opciones_prueba'][ $nombre ] = $valor;
	return true;
}
function delete_option( $nombre ) {
	unset( $GLOBALS['san_opciones_prueba'][ $nombre ] );
	return true;
}

require SAN_DIR . 'includes/class-san-autoload.php';
\GobernacionNarino\SuiteAmbiente\SAN_Autoload::registrar();
