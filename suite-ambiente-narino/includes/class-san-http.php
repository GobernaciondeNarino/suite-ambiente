<?php
/**
 * Cliente HTTP de las fuentes de datos.
 *
 * - Solo permite hosts declarados por las fuentes registradas (defensa SSRF:
 *   ninguna URL llega aquí desde la entrada del usuario).
 * - Usa wp_safe_remote_get (rechaza IP privadas y redirecciones inseguras).
 * - Mide duración y tamaño y deja constancia en los registros.
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Http {

	/**
	 * GET que decodifica JSON.
	 *
	 * @param string $fuente Id de la fuente (para registros y lista blanca).
	 * @param string $url    URL completa.
	 * @param array  $opc    { timeout, headers, formato: json|texto }.
	 * @return array { ok, codigo, datos, error, ms, bytes }
	 */
	public static function get( $fuente, $url, array $opc = array() ) {
		$formato = $opc['formato'] ?? 'json';
		$inicio  = microtime( true );

		if ( ! self::host_permitido( $fuente, $url ) ) {
			SAN_Logger::error( $fuente, 'http_bloqueado', 'Host no permitido para la fuente.', array( 'url' => $url ) );
			return self::resultado( false, 0, null, 'Host no permitido', 0, 0 );
		}

		$args = array(
			'timeout'     => (int) ( $opc['timeout'] ?? 15 ),
			'redirection' => 3,
			'user-agent'  => $opc['user_agent'] ?? 'SuiteAmbienteNarino/' . SAN_VERSION . ' (+' . home_url( '/' ) . ')',
			'headers'     => array_merge( array( 'Accept' => 'json' === $formato ? 'application/json' : '*/*' ), $opc['headers'] ?? array() ),
		);

		$resp = wp_safe_remote_get( $url, $args );
		if ( is_wp_error( $resp ) ) {
			// Un reintento ante fallos de red transitorios (TLS, conexión, timeout).
			usleep( 800000 );
			$resp = wp_safe_remote_get( $url, $args );
		}
		$ms   = (int) round( ( microtime( true ) - $inicio ) * 1000 );

		if ( is_wp_error( $resp ) ) {
			$msg = $resp->get_error_message();
			SAN_Logger::error(
				$fuente,
				'http_error',
				'Error de red: ' . $msg,
				array(
					'url'         => $url,
					'duracion_ms' => $ms,
				)
			);
			return self::resultado( false, 0, null, $msg, $ms, 0 );
		}

		$codigo = (int) wp_remote_retrieve_response_code( $resp );
		$cuerpo = (string) wp_remote_retrieve_body( $resp );
		$bytes  = strlen( $cuerpo );

		if ( $codigo < 200 || $codigo >= 300 ) {
			SAN_Logger::error(
				$fuente,
				'http_estado',
				'La API respondió HTTP ' . $codigo . '.',
				array(
					'url'         => $url,
					'http_codigo' => $codigo,
					'duracion_ms' => $ms,
					'extracto'    => substr( wp_strip_all_tags( $cuerpo ), 0, 300 ),
				)
			);
			if ( 429 === $codigo ) {
				SAN_Consumo::registrar_rechazo( $url );
			}
			return self::resultado( false, $codigo, null, 'HTTP ' . $codigo, $ms, $bytes );
		}

		// Cupo de Open-Meteo: cuenta toda respuesta correcta, aunque el cuerpo
		// no sea válido, porque el servicio ya la contabilizó.
		SAN_Consumo::registrar( $fuente, $url );

		$datos = $cuerpo;
		if ( 'json' === $formato ) {
			$datos = json_decode( $cuerpo, true );
			if ( null === $datos && JSON_ERROR_NONE !== json_last_error() ) {
				SAN_Logger::error(
					$fuente,
					'json_invalido',
					'Respuesta no es JSON válido: ' . json_last_error_msg(),
					array(
						'url'         => $url,
						'http_codigo' => $codigo,
						'duracion_ms' => $ms,
					)
				);
				return self::resultado( false, $codigo, null, 'JSON inválido', $ms, $bytes );
			}
		}

		SAN_Logger::depuracion(
			$fuente,
			'http',
			'GET correcto (' . size_format( $bytes ) . ').',
			array(
				'url'         => $url,
				'http_codigo' => $codigo,
				'duracion_ms' => $ms,
			)
		);
		return self::resultado( true, $codigo, $datos, '', $ms, $bytes );
	}

	/**
	 * ¿El host de la URL está declarado por la fuente?
	 *
	 * @param string $fuente Id de la fuente.
	 * @param string $url    URL.
	 * @return bool
	 */
	public static function host_permitido( $fuente, $url ) {
		$partes = wp_parse_url( $url );
		if ( empty( $partes['scheme'] ) || 'https' !== strtolower( $partes['scheme'] ) || empty( $partes['host'] ) ) {
			return false;
		}
		$obj = SAN_Fuentes::obtener( $fuente );
		if ( ! $obj ) {
			return false;
		}
		return in_array( strtolower( $partes['host'] ), $obj->hosts(), true );
	}

	/**
	 * Estructura uniforme de resultado.
	 *
	 * @return array
	 */
	private static function resultado( $ok, $codigo, $datos, $error, $ms, $bytes ) {
		return array(
			'ok'     => (bool) $ok,
			'codigo' => (int) $codigo,
			'datos'  => $datos,
			'error'  => (string) $error,
			'ms'     => (int) $ms,
			'bytes'  => (int) $bytes,
		);
	}
}
