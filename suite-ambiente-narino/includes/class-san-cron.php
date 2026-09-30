<?php
/**
 * Tareas programadas:
 *  - `san_sincronizar` (cada hora): precalienta la caché de las
 *    visualizaciones del tablero y verifica la salud de las fuentes.
 *  - `san_mantenimiento` (diaria): purga registros y caché vieja.
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Cron {

	const SYNC  = 'san_sincronizar';
	const MANT  = 'san_mantenimiento';
	const SALUD = 'san_salud';

	/**
	 * Registra los manejadores.
	 */
	public function __construct() {
		add_action( self::SYNC, array( __CLASS__, 'sincronizar' ) );
		add_action( self::MANT, array( __CLASS__, 'mantenimiento' ) );
	}

	/**
	 * Agenda los eventos si no existen.
	 */
	public static function agendar() {
		if ( ! wp_next_scheduled( self::SYNC ) ) {
			wp_schedule_event( time() + 120, 'hourly', self::SYNC );
		}
		if ( ! wp_next_scheduled( self::MANT ) ) {
			wp_schedule_event( time() + 600, 'daily', self::MANT );
		}
	}

	/**
	 * Retira los eventos.
	 */
	public static function desagendar() {
		wp_clear_scheduled_hook( self::SYNC );
		wp_clear_scheduled_hook( self::MANT );
	}

	/**
	 * Sincronización horaria.
	 */
	public static function sincronizar() {
		$inicio = microtime( true );
		self::verificar_todas();

		$ids  = SAN_Ajustes::get( 'tablero' )['visualizaciones'] ?? array();
		$ok   = 0;
		$fall = 0;
		foreach ( $ids as $id ) {
			$r = SAN_Catalogo::resolver( $id, array() );
			if ( ! empty( $r['ok'] ) ) {
				++$ok;
			} else {
				++$fall;
			}
		}
		SAN_Logger::info(
			'sistema',
			'sincronizacion',
			sprintf( 'Sincronización: %d visualizaciones listas, %d con error.', $ok, $fall ),
			array( 'duracion_ms' => (int) round( ( microtime( true ) - $inicio ) * 1000 ) )
		);
	}

	/**
	 * Mantenimiento diario.
	 */
	public static function mantenimiento() {
		$logs  = SAN_Logger::purgar();
		$cache = SAN_Cache::purgar();
		SAN_Logger::info( 'sistema', 'mantenimiento', sprintf( 'Mantenimiento: %d registros y %d entradas de caché eliminadas.', $logs, $cache ) );
	}

	/**
	 * Prueba todas las fuentes activas y guarda el historial de salud.
	 *
	 * @return array id => resultado.
	 */
	public static function verificar_todas() {
		$out = array();
		foreach ( SAN_Fuentes::todas() as $id => $fuente ) {
			if ( ! $fuente->disponible() ) {
				continue;
			}
			$out[ $id ] = self::verificar( $id );
		}
		return $out;
	}

	/**
	 * Prueba una fuente y guarda el resultado en el historial.
	 *
	 * @param string $id Id de la fuente.
	 * @return array
	 */
	public static function verificar( $id ) {
		$fuente = SAN_Fuentes::obtener( $id );
		if ( ! $fuente ) {
			return array(
				'ok'      => false,
				'mensaje' => 'Fuente desconocida',
			);
		}
		try {
			$r = $fuente->probar();
		} catch ( \Throwable $e ) {
			$r = array(
				'ok'      => false,
				'codigo'  => 0,
				'ms'      => 0,
				'mensaje' => 'Excepción: ' . $e->getMessage(),
			);
		}
		$r['fecha'] = gmdate( 'Y-m-d H:i:s' );

		$salud        = get_option( self::SALUD, array() );
		$salud        = is_array( $salud ) ? $salud : array();
		$hist         = isset( $salud[ $id ] ) && is_array( $salud[ $id ] ) ? $salud[ $id ] : array();
		$registro     = array_intersect_key( $r, array_flip( array( 'ok', 'codigo', 'ms', 'mensaje', 'frescura', 'fecha' ) ) );
		array_unshift( $hist, $registro );
		$salud[ $id ] = array_slice( $hist, 0, 20 );
		update_option( self::SALUD, $salud, false );

		$nivel = $r['ok'] ? 'info' : 'error';
		SAN_Logger::registrar(
			$nivel,
			$id,
			'prueba',
			( $r['ok'] ? 'Prueba correcta: ' : 'Prueba fallida: ' ) . $r['mensaje'],
			array(
				'http_codigo' => $r['codigo'] ?? 0,
				'duracion_ms' => $r['ms'] ?? 0,
			)
		);
		return $r;
	}

	/**
	 * Último estado conocido de cada fuente.
	 *
	 * @return array id => { ok, codigo, ms, mensaje, frescura, fecha, historial[] }
	 */
	public static function estado() {
		$salud = get_option( self::SALUD, array() );
		$out   = array();
		foreach ( (array) $salud as $id => $hist ) {
			if ( ! is_array( $hist ) || ! $hist ) {
				continue;
			}
			$out[ $id ]              = $hist[0];
			$out[ $id ]['historial'] = $hist;
		}
		return $out;
	}
}
