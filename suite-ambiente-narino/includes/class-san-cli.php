<?php
/**
 * Comandos WP-CLI: `wp suite-ambiente <subcomando>`.
 *
 * Pensados para el cron del servidor en producción, donde WP-Cron se
 * desactiva (DISABLE_WP_CRON) para que la sincronización no dependa de las
 * visitas al sitio:
 *
 *     15 * * * *  wp --path=/ruta/wordpress suite-ambiente sincronizar --quiet
 *     40 3 * * *  wp --path=/ruta/wordpress suite-ambiente mantenimiento --quiet
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

/**
 * Administra Suite Ambiente Nariño desde la línea de comandos.
 */
final class SAN_Cli {

	/**
	 * Verifica las fuentes y precalienta la caché de las visualizaciones del tablero.
	 *
	 * Equivale al evento horario `san_sincronizar` de WP-Cron.
	 *
	 * ## EXAMPLES
	 *
	 *     wp suite-ambiente sincronizar
	 *
	 * @param array $args  Argumentos.
	 * @param array $assoc Opciones.
	 */
	public function sincronizar( $args, $assoc ) {
		$r = SAN_Cron::sincronizar();
		$m = sprintf( 'Sincronización en %s s: %d visualizaciones listas, %d con error.', number_format( $r['ms'] / 1000, 1 ), $r['listas'], $r['errores'] );
		if ( $r['errores'] ) {
			\WP_CLI::warning( $m . ' Revise «wp suite-ambiente estado» o Configuración → Registros.' );
			return;
		}
		\WP_CLI::success( $m );
	}

	/**
	 * Purga registros antiguos y caché vencida hace más de 7 días.
	 *
	 * Equivale al evento diario `san_mantenimiento` de WP-Cron.
	 *
	 * @param array $args  Argumentos.
	 * @param array $assoc Opciones.
	 */
	public function mantenimiento( $args, $assoc ) {
		SAN_Cron::mantenimiento();
		\WP_CLI::success( 'Mantenimiento terminado.' );
	}

	/**
	 * Prueba en vivo las fuentes de datos.
	 *
	 * ## OPTIONS
	 *
	 * [--fuente=<id>]
	 * : Probar solo esta fuente (p. ej. firms, openmeteo_clima).
	 *
	 * [--format=<formato>]
	 * : Formato de salida.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 * ---
	 *
	 * [--estricto]
	 * : Terminar con código 1 si alguna fuente falla (útil para monitoreo).
	 *
	 * ## EXAMPLES
	 *
	 *     wp suite-ambiente verificar
	 *     wp suite-ambiente verificar --fuente=firms
	 *
	 * @param array $args  Argumentos.
	 * @param array $assoc Opciones.
	 */
	public function verificar( $args, $assoc ) {
		$id = isset( $assoc['fuente'] ) ? SAN_Seguridad::sanear_id( $assoc['fuente'] ) : '';
		if ( '' !== $id && ! SAN_Fuentes::obtener( $id ) ) {
			\WP_CLI::error( 'Fuente desconocida: ' . $id . '. Fuentes: ' . implode( ', ', array_keys( SAN_Fuentes::todas() ) ) );
		}
		$res    = '' !== $id ? array( $id => SAN_Cron::verificar( $id ) ) : SAN_Cron::verificar_todas();
		$filas  = array();
		$fallas = 0;
		foreach ( $res as $fid => $r ) {
			$fallas += empty( $r['ok'] ) ? 1 : 0;
			$filas[] = array(
				'fuente'  => $fid,
				'estado'  => empty( $r['ok'] ) ? 'FALLA' : 'ok',
				'http'    => (int) ( $r['codigo'] ?? 0 ),
				'ms'      => (int) ( $r['ms'] ?? 0 ),
				'dato'    => (string) ( $r['frescura'] ?? '' ),
				'mensaje' => (string) ( $r['mensaje'] ?? '' ),
			);
		}
		\WP_CLI\Utils\format_items( $assoc['format'] ?? 'table', $filas, array( 'fuente', 'estado', 'http', 'ms', 'dato', 'mensaje' ) );
		if ( $fallas && ! empty( $assoc['estricto'] ) ) {
			\WP_CLI::halt( 1 );
		}
	}

	/**
	 * Muestra el estado del plugin: sincronización, fuentes y cupo de Open-Meteo.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<formato>]
	 * : Formato de salida de la tabla de fuentes.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 * ---
	 *
	 * @param array $args  Argumentos.
	 * @param array $assoc Opciones.
	 */
	public function estado( $args, $assoc ) {
		$ultima  = SAN_Cron::ultima_sincronizacion();
		$proxima = wp_next_scheduled( SAN_Cron::SYNC );
		\WP_CLI::log( 'Versión: ' . SAN_VERSION );
		\WP_CLI::log( 'WP-Cron: ' . ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ? 'desactivado (se espera el cron del servidor)' : 'activo (depende de las visitas)' ) );
		\WP_CLI::log( 'Última sincronización: ' . ( $ultima ? wp_date( 'Y-m-d H:i', $ultima ) . ' (hace ' . human_time_diff( $ultima ) . ')' : 'nunca' ) );
		\WP_CLI::log( 'Próxima sincronización agendada: ' . ( $proxima ? wp_date( 'Y-m-d H:i', $proxima ) : '—' ) );
		$c = SAN_Consumo::resumen();
		\WP_CLI::log( sprintf( 'Cupo de Open-Meteo: hoy %s de %s (%s %%) · última hora %s de %s · 30 días %s de %s', self::n( $c['dia'] ), self::n( $c['limites']['dia'] ), $c['pct_dia'], self::n( $c['hora'] ), self::n( $c['limites']['hora'] ), self::n( $c['mes'] ), self::n( $c['limites']['mes'] ) ) );
		\WP_CLI::log( '' );

		$estado = SAN_Cron::estado();
		$filas  = array();
		foreach ( SAN_Fuentes::todas() as $id => $f ) {
			$e       = $estado[ $id ] ?? null;
			$cfg     = $f->config();
			$filas[] = array(
				'fuente'     => $id,
				'activa'     => $f->disponible() ? 'sí' : 'no',
				'llave'      => $f->admite_clave() ? ( '' !== (string) $cfg['clave'] ? 'configurada' : 'sin llave' ) : '—',
				'estado'     => $e ? ( $e['ok'] ? 'ok' : 'FALLA' ) : 'sin verificar',
				'verificada' => $e ? wp_date( 'Y-m-d H:i', strtotime( $e['fecha'] . ' UTC' ) ) : '',
				'mensaje'    => $e ? (string) $e['mensaje'] : '',
			);
		}
		\WP_CLI\Utils\format_items( $assoc['format'] ?? 'table', $filas, array( 'fuente', 'activa', 'llave', 'estado', 'verificada', 'mensaje' ) );
	}

	/**
	 * Consumo del cupo gratuito de Open-Meteo por día (últimos 30 días, UTC).
	 *
	 * ## OPTIONS
	 *
	 * [--format=<formato>]
	 * : Formato de salida.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 * ---
	 *
	 * @param array $args  Argumentos.
	 * @param array $assoc Opciones.
	 */
	public function consumo( $args, $assoc ) {
		$c     = SAN_Consumo::resumen();
		$filas = array();
		foreach ( $c['dias'] as $dia => $v ) {
			if ( $v > 0 ) {
				$filas[] = array(
					'dia'      => $dia,
					'llamadas' => $v,
					'limite'   => $c['limites']['dia'],
					'uso'      => round( 100 * $v / $c['limites']['dia'], 1 ) . ' %',
				);
			}
		}
		if ( ! $filas ) {
			\WP_CLI::log( 'Aún no hay consumo registrado.' );
			return;
		}
		\WP_CLI\Utils\format_items( $assoc['format'] ?? 'table', $filas, array( 'dia', 'llamadas', 'limite', 'uso' ) );
		if ( 'table' === ( $assoc['format'] ?? 'table' ) ) {
			\WP_CLI::log( sprintf( 'Hoy por fuente: %s', $c['fuentes'] ? implode( ', ', array_map( function ( $k, $v ) {
				return $k . ' ' . $v;
			}, array_keys( $c['fuentes'] ), $c['fuentes'] ) ) : '—' ) );
		}
	}

	/**
	 * Vacía la caché de datos (toda o de una fuente).
	 *
	 * No lo repita en bucle con Open-Meteo: cada recarga de los 64 municipios
	 * consume 64 o más llamadas de su cupo.
	 *
	 * ## OPTIONS
	 *
	 * [--fuente=<id>]
	 * : Vaciar solo la caché de esta fuente.
	 *
	 * @subcommand vaciar-cache
	 *
	 * @param array $args  Argumentos.
	 * @param array $assoc Opciones.
	 */
	public function vaciar_cache( $args, $assoc ) {
		$id = isset( $assoc['fuente'] ) ? SAN_Seguridad::sanear_id( $assoc['fuente'] ) : '';
		if ( '' !== $id && ! SAN_Fuentes::obtener( $id ) ) {
			\WP_CLI::error( 'Fuente desconocida: ' . $id );
		}
		$n = SAN_Cache::vaciar( '' !== $id ? $id : null );
		SAN_Logger::info( 'sistema', 'cache', sprintf( 'Caché vaciada desde WP-CLI (%s): %d entradas.', '' !== $id ? $id : 'todas', $n ) );
		\WP_CLI::success( sprintf( '%d entradas eliminadas.', $n ) );
	}

	/**
	 * @param float|int $v Número.
	 * @return string
	 */
	private static function n( $v ) {
		return SAN_Analisis::num( (float) $v, 0 );
	}
}
