<?php
/**
 * Registro de eventos (tabla `{prefix}san_logs`).
 *
 * Cada llamada a una API, prueba de conexión, sincronización y error queda
 * registrada con nivel, fuente, código HTTP y duración. Las URL se guardan
 * sin parámetros sensibles (claves, tokens).
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Logger {

	/** Niveles y su peso (se registran los de peso >= al configurado). */
	const NIVELES = array(
		'depuracion'  => 10,
		'info'        => 20,
		'advertencia' => 30,
		'error'       => 40,
	);

	/** Parámetros de URL o claves de contexto que nunca se guardan en claro. */
	const SENSIBLES = array( 'key', 'api_key', 'apikey', 'token', 'map_key', 'x-api-key', 'clave', 'password', 'secret' );

	/**
	 * @param string $fuente  Fuente o subsistema.
	 * @param string $evento  Evento corto (p. ej. `http`, `prueba`).
	 * @param string $mensaje Mensaje legible.
	 * @param array  $ctx     Contexto (url, http_codigo, duracion_ms…).
	 */
	public static function depuracion( $fuente, $evento, $mensaje, array $ctx = array() ) {
		self::registrar( 'depuracion', $fuente, $evento, $mensaje, $ctx );
	}

	/** @see depuracion() */
	public static function info( $fuente, $evento, $mensaje, array $ctx = array() ) {
		self::registrar( 'info', $fuente, $evento, $mensaje, $ctx );
	}

	/** @see depuracion() */
	public static function advertencia( $fuente, $evento, $mensaje, array $ctx = array() ) {
		self::registrar( 'advertencia', $fuente, $evento, $mensaje, $ctx );
	}

	/** @see depuracion() */
	public static function error( $fuente, $evento, $mensaje, array $ctx = array() ) {
		self::registrar( 'error', $fuente, $evento, $mensaje, $ctx );
	}

	/**
	 * Inserta un registro si su nivel alcanza el umbral configurado.
	 *
	 * @param string $nivel   Nivel.
	 * @param string $fuente  Fuente.
	 * @param string $evento  Evento.
	 * @param string $mensaje Mensaje.
	 * @param array  $ctx     Contexto.
	 */
	public static function registrar( $nivel, $fuente, $evento, $mensaje, array $ctx = array() ) {
		global $wpdb;
		if ( ! isset( self::NIVELES[ $nivel ] ) ) {
			$nivel = 'info';
		}
		$umbral = SAN_Ajustes::general( 'nivel_log', 'info' );
		if ( self::NIVELES[ $nivel ] < ( self::NIVELES[ $umbral ] ?? 20 ) ) {
			return;
		}

		$http = isset( $ctx['http_codigo'] ) ? (int) $ctx['http_codigo'] : null;
		$ms   = isset( $ctx['duracion_ms'] ) ? (int) $ctx['duracion_ms'] : null;
		unset( $ctx['http_codigo'], $ctx['duracion_ms'] );
		$ctx = self::redactar( $ctx );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert(
			$wpdb->prefix . 'san_logs',
			array(
				'fecha'       => gmdate( 'Y-m-d H:i:s' ),
				'nivel'       => $nivel,
				'fuente'      => substr( sanitize_key( $fuente ), 0, 40 ),
				'evento'      => substr( sanitize_key( $evento ), 0, 60 ),
				'mensaje'     => wp_strip_all_tags( (string) $mensaje ),
				'contexto'    => $ctx ? wp_json_encode( $ctx ) : null,
				'http_codigo' => $http,
				'duracion_ms' => $ms,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d' )
		);
	}

	/**
	 * Consulta paginada para el visor de registros.
	 *
	 * @param array $filtro { nivel, fuente, buscar, pagina, por_pagina }.
	 * @return array { filas, total }
	 */
	public static function consultar( array $filtro ) {
		global $wpdb;
		$tabla  = $wpdb->prefix . 'san_logs';
		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $filtro['nivel'] ) && isset( self::NIVELES[ $filtro['nivel'] ] ) ) {
			$where[]  = 'nivel = %s';
			$params[] = $filtro['nivel'];
		}
		if ( ! empty( $filtro['fuente'] ) ) {
			$where[]  = 'fuente = %s';
			$params[] = sanitize_key( $filtro['fuente'] );
		}
		if ( ! empty( $filtro['buscar'] ) ) {
			$where[]  = '(mensaje LIKE %s OR evento LIKE %s)';
			$like     = '%' . $wpdb->esc_like( sanitize_text_field( $filtro['buscar'] ) ) . '%';
			$params[] = $like;
			$params[] = $like;
		}

		$por_pagina = max( 10, min( 500, (int) ( $filtro['por_pagina'] ?? 50 ) ) );
		$pagina     = max( 1, (int) ( $filtro['pagina'] ?? 1 ) );
		$desde      = ( $pagina - 1 ) * $por_pagina;
		$cond       = implode( ' AND ', $where );

		$sql_total = "SELECT COUNT(*) FROM {$tabla} WHERE {$cond}";
		$sql_filas = "SELECT * FROM {$tabla} WHERE {$cond} ORDER BY id DESC LIMIT %d OFFSET %d";

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$total = (int) ( $params ? $wpdb->get_var( $wpdb->prepare( $sql_total, $params ) ) : $wpdb->get_var( $sql_total ) );
		$filas = $wpdb->get_results( $wpdb->prepare( $sql_filas, array_merge( $params, array( $por_pagina, $desde ) ) ), ARRAY_A );
		// phpcs:enable

		return array(
			'filas'      => is_array( $filas ) ? $filas : array(),
			'total'      => $total,
			'pagina'     => $pagina,
			'por_pagina' => $por_pagina,
		);
	}

	/**
	 * Conteos por nivel en las últimas horas (para el panel).
	 *
	 * @param int $horas Ventana en horas.
	 * @return array nivel => conteo.
	 */
	public static function resumen( $horas = 24 ) {
		global $wpdb;
		$tabla = $wpdb->prefix . 'san_logs';
		$desde = gmdate( 'Y-m-d H:i:s', time() - (int) $horas * HOUR_IN_SECONDS );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$filas = $wpdb->get_results( $wpdb->prepare( "SELECT nivel, COUNT(*) AS n FROM {$tabla} WHERE fecha >= %s GROUP BY nivel", $desde ), ARRAY_A );
		$out   = array_fill_keys( array_keys( self::NIVELES ), 0 );
		foreach ( (array) $filas as $f ) {
			$out[ $f['nivel'] ] = (int) $f['n'];
		}
		return $out;
	}

	/**
	 * Fuentes distintas presentes en los registros (para el filtro).
	 *
	 * @return string[]
	 */
	public static function fuentes() {
		global $wpdb;
		$tabla = $wpdb->prefix . 'san_logs';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return array_map( 'strval', (array) $wpdb->get_col( "SELECT DISTINCT fuente FROM {$tabla} ORDER BY fuente" ) );
	}

	/**
	 * Borra registros más antiguos que la retención configurada, o todos.
	 *
	 * @param bool $todos Si es true vacía la tabla.
	 * @return int Filas borradas.
	 */
	public static function purgar( $todos = false ) {
		global $wpdb;
		$tabla = $wpdb->prefix . 'san_logs';
		if ( $todos ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			return (int) $wpdb->query( "DELETE FROM {$tabla}" );
		}
		$dias  = (int) SAN_Ajustes::general( 'retencion_logs', 30 );
		$corte = gmdate( 'Y-m-d H:i:s', time() - $dias * DAY_IN_SECONDS );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$tabla} WHERE fecha < %s", $corte ) );
	}

	/**
	 * Quita datos sensibles del contexto (claves y parámetros de URL).
	 *
	 * @param array $ctx Contexto.
	 * @return array
	 */
	public static function redactar( array $ctx ) {
		foreach ( $ctx as $k => $v ) {
			if ( in_array( strtolower( (string) $k ), self::SENSIBLES, true ) ) {
				$ctx[ $k ] = '***';
			} elseif ( is_string( $v ) && preg_match( '#^https?://#i', $v ) ) {
				$ctx[ $k ] = self::redactar_url( $v );
			} elseif ( is_array( $v ) ) {
				$ctx[ $k ] = self::redactar( $v );
			}
		}
		return $ctx;
	}

	/**
	 * Enmascara parámetros sensibles y segmentos de ruta que parezcan claves
	 * (la API de FIRMS lleva la MAP_KEY dentro de la ruta).
	 *
	 * @param string $url URL.
	 * @return string
	 */
	public static function redactar_url( $url ) {
		$url = preg_replace_callback(
			'/([?&])([^=&]+)=([^&]*)/',
			function ( $m ) {
				return in_array( strtolower( rawurldecode( $m[2] ) ), self::SENSIBLES, true ) ? $m[1] . $m[2] . '=***' : $m[0];
			},
			$url
		);
		// Segmentos hexadecimales/alfanuméricos largos (≥ 24) en la ruta.
		return preg_replace( '#/([A-Za-z0-9]{24,})(?=/|$)#', '/***', $url );
	}
}
