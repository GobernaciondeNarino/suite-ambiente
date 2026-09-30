<?php
/**
 * API REST del plugin (`/wp-json/suite-ambiente/v1/`).
 *
 * Pública (solo lectura, con límite de peticiones por IP):
 *   GET visualizaciones                 Catálogo de visualizaciones.
 *   GET visualizaciones/{id}            Datos + configuración + análisis.
 *   GET datos                           Catálogo de datos abiertos.
 *   GET datos/{recurso}?formato=json|csv|geojson
 *   GET municipios                      Tabla maestra de municipios.
 *   GET estado                          Estado público de las fuentes.
 *
 * Administración (capacidad manage_options + nonce wp_rest):
 *   POST admin/probar/{fuente}          Prueba una fuente.
 *   POST admin/probar                   Prueba todas las fuentes activas.
 *   POST admin/cache/vaciar             Vacía la caché (opcional: fuente).
 *   GET  admin/logs                     Consulta de registros.
 *   DELETE admin/logs                   Purga de registros.
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

final class SAN_Rest {

	/**
	 * Hooks.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'rutas' ) );
		add_filter( 'rest_pre_serve_request', array( $this, 'servir_crudo' ), 10, 4 );
	}

	/**
	 * Registra las rutas.
	 */
	public function rutas() {
		$ns      = SAN_REST_NS;
		$publico = array( $this, 'permiso_publico' );
		$admin   = array( $this, 'permiso_admin' );
		$id      = '(?P<id>[a-z0-9_]{1,64})';

		register_rest_route(
			$ns,
			'/visualizaciones',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'listar_visualizaciones' ),
				'permission_callback' => $publico,
			)
		);
		register_rest_route(
			$ns,
			'/visualizaciones/' . $id,
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'visualizacion' ),
				'permission_callback' => $publico,
				'args'                => array(
					'municipio' => array( 'type' => 'string' ),
					'formato'   => array(
						'type' => 'string',
						'enum' => array( 'json', 'csv' ),
					),
				),
			)
		);
		register_rest_route(
			$ns,
			'/datos',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'catalogo_datos' ),
				'permission_callback' => $publico,
			)
		);
		register_rest_route(
			$ns,
			'/datos/(?P<recurso>[a-z0-9_]{1,64})',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'recurso' ),
				'permission_callback' => $publico,
				'args'                => array(
					'formato'   => array(
						'type' => 'string',
						'enum' => array( 'json', 'csv', 'geojson' ),
					),
					'municipio' => array( 'type' => 'string' ),
				),
			)
		);
		register_rest_route(
			$ns,
			'/municipios',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'municipios' ),
				'permission_callback' => $publico,
			)
		);
		register_rest_route(
			$ns,
			'/estado',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'estado' ),
				'permission_callback' => $publico,
			)
		);

		register_rest_route(
			$ns,
			'/admin/probar/(?P<fuente>[a-z0-9_]{1,40})',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'probar' ),
				'permission_callback' => $admin,
			)
		);
		register_rest_route(
			$ns,
			'/admin/probar',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'probar_todas' ),
				'permission_callback' => $admin,
			)
		);
		register_rest_route(
			$ns,
			'/admin/cache/vaciar',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'vaciar_cache' ),
				'permission_callback' => $admin,
			)
		);
		register_rest_route(
			$ns,
			'/admin/logs',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'logs' ),
					'permission_callback' => $admin,
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'purgar_logs' ),
					'permission_callback' => $admin,
				),
			)
		);
	}

	/* ------------------------------------------------------------------
	 * Permisos
	 * ---------------------------------------------------------------- */

	/**
	 * Lectura pública con límite de peticiones por IP.
	 *
	 * @return true|WP_Error
	 */
	public function permiso_publico() {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		$max = (int) SAN_Ajustes::general( 'limite_peticiones', 300 );
		if ( ! SAN_Seguridad::limitar( 'rest', $max, 60 ) ) {
			return new WP_Error( 'san_limite', __( 'Demasiadas peticiones. Espere un minuto.', 'suite-ambiente-narino' ), array( 'status' => 429 ) );
		}
		return true;
	}

	/**
	 * Solo administradores (la cookie + nonce wp_rest la valida el núcleo).
	 *
	 * @return bool
	 */
	public function permiso_admin() {
		return current_user_can( 'manage_options' );
	}

	/* ------------------------------------------------------------------
	 * Visualizaciones
	 * ---------------------------------------------------------------- */

	/**
	 * Catálogo de visualizaciones (metadatos).
	 *
	 * @return WP_REST_Response
	 */
	public function listar_visualizaciones() {
		$out = array();
		foreach ( SAN_Catalogo::definiciones() as $id => $def ) {
			$out[] = SAN_Catalogo::meta( $id );
		}
		return $this->respuesta( $out, 300 );
	}

	/**
	 * Datos de una visualización.
	 *
	 * @param WP_REST_Request $req Petición.
	 * @return WP_REST_Response|WP_Error
	 */
	public function visualizacion( WP_REST_Request $req ) {
		$id = SAN_Seguridad::sanear_id( $req['id'] );
		if ( ! SAN_Catalogo::existe( $id ) ) {
			return new WP_Error( 'san_no_existe', __( 'Visualización no encontrada.', 'suite-ambiente-narino' ), array( 'status' => 404 ) );
		}
		$r = SAN_Catalogo::resolver( $id, $req->get_params() );

		if ( 'csv' === $req->get_param( 'formato' ) ) {
			return $this->csv( $r['datos'] ?? array(), $id );
		}
		$estado = empty( $r['ok'] ) ? 502 : 200;
		return $this->respuesta( $r, empty( $r['ok'] ) ? 0 : 300, $estado );
	}

	/* ------------------------------------------------------------------
	 * Datos abiertos
	 * ---------------------------------------------------------------- */

	/**
	 * Catálogo de datos abiertos.
	 *
	 * @return WP_REST_Response
	 */
	public function catalogo_datos() {
		return $this->respuesta( SAN_Datos_Abiertos::catalogo(), 3600 );
	}

	/**
	 * Un recurso de datos abiertos.
	 *
	 * @param WP_REST_Request $req Petición.
	 * @return WP_REST_Response|WP_Error
	 */
	public function recurso( WP_REST_Request $req ) {
		$id = SAN_Seguridad::sanear_id( $req['recurso'] );
		if ( ! SAN_Datos_Abiertos::existe( $id ) ) {
			return new WP_Error( 'san_no_existe', __( 'Recurso no encontrado.', 'suite-ambiente-narino' ), array( 'status' => 404 ) );
		}
		$formato = SAN_Seguridad::elegir( $req->get_param( 'formato' ), array( 'json', 'csv', 'geojson' ), 'json' );
		$r       = SAN_Datos_Abiertos::obtener( $id, $req->get_params() );

		if ( ! empty( $r['geojson'] ) ) {
			return $this->respuesta( $r['geojson'], DAY_IN_SECONDS );
		}
		if ( empty( $r['ok'] ) ) {
			return new WP_Error( 'san_fuente', $r['error'] ? $r['error'] : __( 'Fuente no disponible.', 'suite-ambiente-narino' ), array( 'status' => 502 ) );
		}
		if ( 'csv' === $formato ) {
			return $this->csv( $r['filas'], $id );
		}
		if ( 'geojson' === $formato ) {
			return $this->respuesta( SAN_Datos_Abiertos::a_geojson( $r['filas'] ), 600 );
		}
		return $this->respuesta( $r, 600 );
	}

	/**
	 * Tabla maestra de municipios.
	 *
	 * @return WP_REST_Response
	 */
	public function municipios() {
		return $this->respuesta( SAN_Municipios::todos(), DAY_IN_SECONDS );
	}

	/**
	 * Estado público de las fuentes (sin configuración sensible).
	 *
	 * @return WP_REST_Response
	 */
	public function estado() {
		$estado = SAN_Cron::estado();
		$out    = array();
		foreach ( SAN_Fuentes::todas() as $id => $f ) {
			$e     = $estado[ $id ] ?? null;
			$out[] = array(
				'id'         => $id,
				'nombre'     => $f->nombre(),
				'categoria'  => $f->categoria(),
				'activa'     => $f->disponible(),
				'ok'         => $e ? (bool) $e['ok'] : null,
				'ms'         => $e['ms'] ?? null,
				'verificado' => $e['fecha'] ?? null,
				'frescura'   => $e['frescura'] ?? '',
			);
		}
		return $this->respuesta( $out, 120 );
	}

	/* ------------------------------------------------------------------
	 * Administración
	 * ---------------------------------------------------------------- */

	/**
	 * Prueba una fuente.
	 *
	 * @param WP_REST_Request $req Petición.
	 * @return WP_REST_Response|WP_Error
	 */
	public function probar( WP_REST_Request $req ) {
		$id = SAN_Seguridad::sanear_id( $req['fuente'] );
		if ( ! SAN_Fuentes::obtener( $id ) ) {
			return new WP_Error( 'san_no_existe', __( 'Fuente desconocida.', 'suite-ambiente-narino' ), array( 'status' => 404 ) );
		}
		return rest_ensure_response( SAN_Cron::verificar( $id ) );
	}

	/**
	 * Prueba todas las fuentes activas.
	 *
	 * @return WP_REST_Response
	 */
	public function probar_todas() {
		return rest_ensure_response( SAN_Cron::verificar_todas() );
	}

	/**
	 * Vacía la caché (toda o de una fuente).
	 *
	 * @param WP_REST_Request $req Petición.
	 * @return WP_REST_Response
	 */
	public function vaciar_cache( WP_REST_Request $req ) {
		$fuente = SAN_Seguridad::sanear_id( $req->get_param( 'fuente' ) );
		$n      = SAN_Cache::vaciar( $fuente ? $fuente : null );
		SAN_Logger::info( 'sistema', 'cache', sprintf( 'Caché vaciada (%s): %d entradas.', $fuente ? $fuente : 'todas', $n ) );
		return rest_ensure_response( array( 'borradas' => $n ) );
	}

	/**
	 * Consulta de registros.
	 *
	 * @param WP_REST_Request $req Petición.
	 * @return WP_REST_Response
	 */
	public function logs( WP_REST_Request $req ) {
		return rest_ensure_response(
			SAN_Logger::consultar(
				array(
					'nivel'      => (string) $req->get_param( 'nivel' ),
					'fuente'     => (string) $req->get_param( 'fuente' ),
					'buscar'     => (string) $req->get_param( 'buscar' ),
					'pagina'     => (int) $req->get_param( 'pagina' ),
					'por_pagina' => (int) $req->get_param( 'por_pagina' ),
				)
			)
		);
	}

	/**
	 * Purga de registros.
	 *
	 * @param WP_REST_Request $req Petición.
	 * @return WP_REST_Response
	 */
	public function purgar_logs( WP_REST_Request $req ) {
		$todos = (bool) $req->get_param( 'todos' );
		$n     = SAN_Logger::purgar( $todos );
		SAN_Logger::info( 'sistema', 'logs', sprintf( 'Registros purgados: %d.', $n ) );
		return rest_ensure_response( array( 'borrados' => $n ) );
	}

	/* ------------------------------------------------------------------
	 * Utilidades
	 * ---------------------------------------------------------------- */

	/**
	 * Respuesta JSON con cabeceras de caché pública.
	 *
	 * @param mixed $datos  Datos.
	 * @param int   $maxage Segundos de caché HTTP (0 = no-store).
	 * @param int   $estado Código HTTP.
	 * @return WP_REST_Response
	 */
	private function respuesta( $datos, $maxage, $estado = 200 ) {
		$r = new WP_REST_Response( $datos, $estado );
		$r->header( 'Cache-Control', $maxage > 0 ? 'public, max-age=' . (int) $maxage : 'no-store' );
		$r->header( 'Access-Control-Allow-Origin', '*' );
		return $r;
	}

	/**
	 * Respuesta CSV (se sirve cruda en servir_crudo()).
	 *
	 * @param array  $filas  Filas planas.
	 * @param string $nombre Nombre base del archivo.
	 * @return WP_REST_Response
	 */
	private function csv( array $filas, $nombre ) {
		$r = new WP_REST_Response(
			array(
				'_san_csv' => SAN_Datos_Abiertos::a_csv( $filas ),
				'_nombre'  => sanitize_file_name( $nombre . '-' . gmdate( 'Ymd' ) . '.csv' ),
			),
			200
		);
		$r->header( 'Content-Type', 'text/csv; charset=utf-8' );
		$r->header( 'Access-Control-Allow-Origin', '*' );
		return $r;
	}

	/**
	 * Sirve el CSV sin codificarlo como JSON.
	 *
	 * @param bool             $servido  Si ya se sirvió.
	 * @param WP_REST_Response $result   Respuesta.
	 * @param WP_REST_Request  $request  Petición.
	 * @param \WP_REST_Server  $server   Servidor.
	 * @return bool
	 */
	public function servir_crudo( $servido, $result, $request, $server ) {
		if ( $servido || ! $result instanceof WP_REST_Response ) {
			return $servido;
		}
		$d = $result->get_data();
		if ( ! is_array( $d ) || ! isset( $d['_san_csv'] ) || 0 !== strpos( $request->get_route(), '/' . SAN_REST_NS . '/' ) ) {
			return $servido;
		}
		if ( ! headers_sent() ) {
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="' . $d['_nombre'] . '"' );
		}
		echo "\xEF\xBB\xBF" . $d['_san_csv']; // phpcs:ignore WordPress.Security.EscapeOutput -- CSV ya escapado por a_csv().
		return true;
	}
}
