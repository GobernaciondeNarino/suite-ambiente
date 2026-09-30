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

require SAN_DIR . 'includes/class-san-autoload.php';
\GobernacionNarino\SuiteAmbiente\SAN_Autoload::registrar();
