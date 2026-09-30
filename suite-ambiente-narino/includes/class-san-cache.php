<?php
/**
 * Caché persistente en tabla propia (`{prefix}san_cache`).
 *
 * A diferencia de los transients, conserva la última copia buena aunque haya
 * vencido: si una API falla, se sirve el dato anterior marcado como vencido
 * ("stale-if-error") en lugar de dejar el gráfico vacío.
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Cache {

	/** @var array Caché en memoria por petición. */
	private static $memoria = array();

	/**
	 * Nombre de la tabla.
	 *
	 * @return string
	 */
	private static function tabla() {
		global $wpdb;
		return $wpdb->prefix . 'san_cache';
	}

	/**
	 * Lee una entrada.
	 *
	 * @param string $clave         Clave.
	 * @param bool   $aceptar_vieja Si es true devuelve la entrada aunque esté vencida.
	 * @return array|null { valor, creado, expira, vencida } o null.
	 */
	public static function get( $clave, $aceptar_vieja = false ) {
		global $wpdb;
		$clave = self::normalizar( $clave );

		if ( isset( self::$memoria[ $clave ] ) ) {
			$fila = self::$memoria[ $clave ];
		} else {
			$tabla = self::tabla();
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$fila = $wpdb->get_row( $wpdb->prepare( "SELECT valor, creado, expira FROM {$tabla} WHERE clave = %s", $clave ), ARRAY_A );
			if ( ! $fila ) {
				return null;
			}
			self::$memoria[ $clave ] = $fila;
		}

		$vencida = strtotime( $fila['expira'] . ' UTC' ) < time();
		if ( $vencida && ! $aceptar_vieja ) {
			return null;
		}
		return array(
			'valor'   => json_decode( $fila['valor'], true ),
			'creado'  => $fila['creado'],
			'expira'  => $fila['expira'],
			'vencida' => $vencida,
		);
	}

	/**
	 * Guarda una entrada.
	 *
	 * @param string $clave Clave.
	 * @param mixed  $valor Valor serializable a JSON.
	 * @param int    $ttl   Segundos de vigencia.
	 * @param string $grupo Grupo (normalmente el id de la fuente).
	 * @return bool
	 */
	public static function set( $clave, $valor, $ttl, $grupo = 'general' ) {
		global $wpdb;
		$clave = self::normalizar( $clave );
		$json  = wp_json_encode( $valor );
		if ( false === $json ) {
			return false;
		}
		$ahora = time();
		$fila  = array(
			'clave'  => $clave,
			'grupo'  => substr( sanitize_key( $grupo ), 0, 40 ),
			'valor'  => $json,
			'creado' => gmdate( 'Y-m-d H:i:s', $ahora ),
			'expira' => gmdate( 'Y-m-d H:i:s', $ahora + max( 1, (int) $ttl ) ),
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$ok = $wpdb->replace( self::tabla(), $fila, array( '%s', '%s', '%s', '%s', '%s' ) );
		self::$memoria[ $clave ] = array(
			'valor'  => $json,
			'creado' => $fila['creado'],
			'expira' => $fila['expira'],
		);
		return false !== $ok;
	}

	/**
	 * Borra entradas de un grupo, o todas si el grupo es null.
	 *
	 * @param string|null $grupo Grupo.
	 * @return int Filas borradas.
	 */
	public static function vaciar( $grupo = null ) {
		global $wpdb;
		self::$memoria = array();
		$tabla         = self::tabla();
		if ( null === $grupo ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			return (int) $wpdb->query( "DELETE FROM {$tabla}" );
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$tabla} WHERE grupo = %s", sanitize_key( $grupo ) ) );
	}

	/**
	 * Elimina entradas vencidas hace más de 7 días (se conservan las recientes
	 * como respaldo ante fallos).
	 *
	 * @return int
	 */
	public static function purgar() {
		global $wpdb;
		$tabla = self::tabla();
		$corte = gmdate( 'Y-m-d H:i:s', time() - 7 * DAY_IN_SECONDS );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$tabla} WHERE expira < %s", $corte ) );
	}

	/**
	 * Estadísticas por grupo para el panel de configuración.
	 *
	 * @return array[] Filas { grupo, entradas, vigentes, bytes, ultima }.
	 */
	public static function estadisticas() {
		global $wpdb;
		$tabla = self::tabla();
		$ahora = gmdate( 'Y-m-d H:i:s' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$filas = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT grupo, COUNT(*) AS entradas, SUM(expira >= %s) AS vigentes, SUM(LENGTH(valor)) AS bytes, MAX(creado) AS ultima FROM {$tabla} GROUP BY grupo ORDER BY grupo",
				$ahora
			),
			ARRAY_A
		);
		return is_array( $filas ) ? $filas : array();
	}

	/**
	 * Normaliza la clave a ≤191 caracteres (límite del índice utf8mb4).
	 *
	 * @param string $clave Clave.
	 * @return string
	 */
	private static function normalizar( $clave ) {
		$clave = (string) $clave;
		return strlen( $clave ) > 150 ? substr( $clave, 0, 100 ) . ':' . md5( $clave ) : $clave;
	}
}
