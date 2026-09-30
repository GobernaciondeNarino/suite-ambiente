<?php
/**
 * Utilidades de seguridad: saneamiento con listas blancas, límite de
 * peticiones por IP y cifrado de claves de API en reposo.
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Seguridad {

	/**
	 * Código DIVIPOLA válido de Nariño (lista blanca), o '' si no existe.
	 * Acepta también el nombre del municipio.
	 *
	 * @param mixed $valor Valor crudo.
	 * @return string
	 */
	public static function sanear_divipola( $valor ) {
		$m = SAN_Municipios::buscar( (string) $valor );
		return $m ? $m['divipola'] : '';
	}

	/**
	 * Identificador interno (visualización, fuente, recurso): [a-z0-9_].
	 *
	 * @param mixed $valor Valor crudo.
	 * @return string
	 */
	public static function sanear_id( $valor ) {
		$valor = strtolower( (string) $valor );
		$valor = preg_replace( '/[^a-z0-9_]/', '', $valor );
		return substr( $valor, 0, 64 );
	}

	/**
	 * Elige un valor de una lista permitida o devuelve el por defecto.
	 *
	 * @param mixed  $valor     Valor crudo.
	 * @param array  $permitido Valores permitidos.
	 * @param string $defecto   Valor por defecto.
	 * @return string
	 */
	public static function elegir( $valor, array $permitido, $defecto ) {
		$valor = (string) $valor;
		return in_array( $valor, $permitido, true ) ? $valor : $defecto;
	}

	/**
	 * Entero acotado.
	 *
	 * @param mixed $valor Valor crudo.
	 * @param int   $min   Mínimo.
	 * @param int   $max   Máximo.
	 * @param int   $def   Por defecto si no es numérico.
	 * @return int
	 */
	public static function entero( $valor, $min, $max, $def ) {
		if ( ! is_numeric( $valor ) ) {
			return $def;
		}
		return max( $min, min( $max, (int) $valor ) );
	}

	/**
	 * Límite de peticiones por IP en una ventana deslizante simple.
	 *
	 * @param string $ambito  Ámbito (p. ej. `rest`).
	 * @param int    $max     Peticiones permitidas.
	 * @param int    $ventana Segundos de la ventana.
	 * @return bool true si la petición está permitida.
	 */
	public static function limitar( $ambito, $max, $ventana = 60 ) {
		$clave = 'san_rl_' . $ambito . '_' . md5( self::ip_cliente() . wp_salt( 'nonce' ) );
		$datos = get_transient( $clave );
		$ahora = time();
		if ( ! is_array( $datos ) || $datos['inicio'] + $ventana < $ahora ) {
			$datos = array(
				'inicio' => $ahora,
				'n'      => 0,
			);
		}
		++$datos['n'];
		set_transient( $clave, $datos, $ventana );
		return $datos['n'] <= $max;
	}

	/**
	 * IP del cliente. Solo se usa REMOTE_ADDR: las cabeceras X-Forwarded-For
	 * son falsificables salvo que un proxy de confianza las reescriba, lo que
	 * se puede declarar con el filtro `san_ip_cliente`.
	 *
	 * @return string
	 */
	public static function ip_cliente() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
		$ip = filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
		return (string) apply_filters( 'san_ip_cliente', $ip );
	}

	/**
	 * Cifra un texto con AES-256-GCM usando una clave derivada de las sales
	 * de WordPress. Si OpenSSL no está disponible se guarda en base64 con
	 * prefijo `b64:` (y el panel lo advierte).
	 *
	 * @param string $texto Texto plano.
	 * @return string
	 */
	public static function cifrar( $texto ) {
		$texto = (string) $texto;
		if ( '' === $texto ) {
			return '';
		}
		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return 'b64:' . base64_encode( $texto ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
		}
		$iv     = random_bytes( 12 );
		$tag    = '';
		$cifrad = openssl_encrypt( $texto, 'aes-256-gcm', self::clave(), OPENSSL_RAW_DATA, $iv, $tag );
		return 'gcm:' . base64_encode( $iv . $tag . $cifrad ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
	}

	/**
	 * Descifra un texto producido por cifrar().
	 *
	 * @param string $paquete Texto cifrado.
	 * @return string '' si no se puede descifrar.
	 */
	public static function descifrar( $paquete ) {
		$paquete = (string) $paquete;
		if ( 0 === strpos( $paquete, 'b64:' ) ) {
			return (string) base64_decode( substr( $paquete, 4 ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
		}
		if ( 0 !== strpos( $paquete, 'gcm:' ) || ! function_exists( 'openssl_decrypt' ) ) {
			return '';
		}
		$bin = base64_decode( substr( $paquete, 4 ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
		if ( false === $bin || strlen( $bin ) < 29 ) {
			return '';
		}
		$plano = openssl_decrypt( substr( $bin, 28 ), 'aes-256-gcm', self::clave(), OPENSSL_RAW_DATA, substr( $bin, 0, 12 ), substr( $bin, 12, 16 ) );
		return false === $plano ? '' : $plano;
	}

	/**
	 * Clave de 32 bytes derivada de las sales del sitio.
	 *
	 * @return string
	 */
	private static function clave() {
		return hash( 'sha256', wp_salt( 'auth' ) . 'suite-ambiente-narino', true );
	}

	/**
	 * Enmascara una clave para mostrarla en el panel: `abcd…wxyz`.
	 *
	 * @param string $clave Clave en claro.
	 * @return string
	 */
	public static function enmascarar( $clave ) {
		$clave = (string) $clave;
		if ( strlen( $clave ) <= 8 ) {
			return '' === $clave ? '' : str_repeat( '•', strlen( $clave ) );
		}
		return substr( $clave, 0, 4 ) . '…' . substr( $clave, -4 );
	}
}
