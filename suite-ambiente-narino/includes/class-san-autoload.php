<?php
/**
 * Autocargador de clases del plugin.
 *
 * Convención: la clase `SAN_Fuente_Usgs` vive en `class-san-fuente-usgs.php`
 * dentro de alguna de las carpetas registradas en `$carpetas`.
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Autoload {

	/** @var string[] Carpetas (relativas a includes/) donde se buscan clases. */
	private static $carpetas = array( '', 'fuentes/', 'visualizaciones/', 'admin/' );

	/**
	 * Registra el autocargador.
	 */
	public static function registrar() {
		spl_autoload_register( array( __CLASS__, 'cargar' ) );
	}

	/**
	 * Carga el archivo de una clase del espacio de nombres del plugin.
	 *
	 * @param string $clase Nombre completo de la clase.
	 */
	public static function cargar( $clase ) {
		$prefijo = __NAMESPACE__ . '\\';
		if ( 0 !== strpos( $clase, $prefijo ) ) {
			return;
		}
		$corta   = substr( $clase, strlen( $prefijo ) );
		$archivo = 'class-' . strtolower( str_replace( '_', '-', $corta ) ) . '.php';
		foreach ( self::$carpetas as $carpeta ) {
			$ruta = SAN_DIR . 'includes/' . $carpeta . $archivo;
			if ( is_readable( $ruta ) ) {
				require_once $ruta;
				return;
			}
		}
	}
}
