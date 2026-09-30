<?php
/**
 * Módulo Configuración, con pestañas:
 *   Tablero   Resumen de estado + configuración del tablero público.
 *   APIs      Configurar (activar, claves, TTL, parámetros) y verificar
 *             el funcionamiento de cada fuente; caché.
 *   Registros Visor, filtros, exportación y purga de logs.
 *   General   Librerías, tema, D3plus v4, API pública, registros.
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Admin_Config {

	/**
	 * Renderiza la página.
	 */
	public static function pagina() {
		if ( ! current_user_can( SAN_Admin::CAPACIDAD ) ) {
			return;
		}
		$pestanas = array(
			'tablero'   => __( 'Tablero', 'suite-ambiente-narino' ),
			'apis'      => __( 'APIs', 'suite-ambiente-narino' ),
			'registros' => __( 'Registros', 'suite-ambiente-narino' ),
			'general'   => __( 'General', 'suite-ambiente-narino' ),
		);
		$activa   = SAN_Admin::pestana_activa( array_keys( $pestanas ), 'tablero' );

		echo '<div class="wrap san-admin">';
		SAN_Admin::encabezado( __( 'Configuración', 'suite-ambiente-narino' ), __( 'Tablero público, fuentes de datos, registros y preferencias generales.', 'suite-ambiente-narino' ) );
		self::aviso();
		SAN_Admin::pestanas( 'san-config', array_map( 'esc_html', $pestanas ), $activa );

		switch ( $activa ) {
			case 'apis':
				self::apis();
				break;
			case 'registros':
				self::registros();
				break;
			case 'general':
				self::general();
				break;
			default:
				self::tablero();
		}
		echo '</div>';
	}

	/**
	 * Aviso tras guardar.
	 */
	private static function aviso() {
		$a = isset( $_GET['aviso'] ) ? sanitize_key( wp_unslash( $_GET['aviso'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'guardado' === $a ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Cambios guardados.', 'suite-ambiente-narino' ) . '</p></div>';
		}
	}

	/* ------------------------------------------------------------------
	 * Tablero
	 * ---------------------------------------------------------------- */

	/**
	 * Pestaña Tablero.
	 */
	private static function tablero() {
		$fuentes = SAN_Fuentes::todas();
		$estado  = SAN_Cron::estado();
		$activas = 0;
		$ok      = 0;
		foreach ( $fuentes as $id => $f ) {
			if ( $f->disponible() ) {
				++$activas;
				if ( ! empty( $estado[ $id ]['ok'] ) ) {
					++$ok;
				}
			}
		}
		$logs    = SAN_Logger::resumen( 24 );
		$cache   = SAN_Cache::estadisticas();
		$entr    = array_sum( array_map( 'intval', wp_list_pluck( $cache, 'entradas' ) ) );
		$proxima = wp_next_scheduled( SAN_Cron::SYNC );

		echo '<section class="san-kpis" aria-label="' . esc_attr__( 'Resumen de estado', 'suite-ambiente-narino' ) . '">';
		$kpis = array(
			array( __( 'Fuentes activas', 'suite-ambiente-narino' ), $activas . ' / ' . count( $fuentes ), '' ),
			array( __( 'Funcionando', 'suite-ambiente-narino' ), $ok . ' / ' . $activas, $ok < $activas ? 'alerta' : 'bueno' ),
			array( __( 'Errores (24 h)', 'suite-ambiente-narino' ), (string) $logs['error'], $logs['error'] ? 'alerta' : 'bueno' ),
			array( __( 'Advertencias (24 h)', 'suite-ambiente-narino' ), (string) $logs['advertencia'], '' ),
			array( __( 'Entradas en caché', 'suite-ambiente-narino' ), (string) $entr, '' ),
			array( __( 'Próxima sincronización', 'suite-ambiente-narino' ), $proxima ? wp_date( 'H:i', $proxima ) : '—', '' ),
		);
		foreach ( $kpis as $k ) {
			echo '<div class="san-kpi' . ( $k[2] ? ' san-kpi--' . esc_attr( $k[2] ) : '' ) . '"><span class="san-kpi__etiqueta">' . esc_html( $k[0] ) . '</span><span class="san-kpi__valor">' . esc_html( $k[1] ) . '</span></div>';
		}
		echo '</section>';

		$t = SAN_Ajustes::get( 'tablero' );
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="san-panel">';
		wp_nonce_field( 'san_guardar_tablero' );
		echo '<input type="hidden" name="action" value="san_guardar_tablero">';
		echo '<h2>' . esc_html__( 'Tablero público', 'suite-ambiente-narino' ) . '</h2>';
		echo '<p>' . esc_html__( 'Elija las visualizaciones del shortcode [san_tablero] y su orden (número menor primero).', 'suite-ambiente-narino' ) . '</p>';
		echo SAN_Admin::campo_shortcode( __( 'Shortcode del tablero', 'suite-ambiente-narino' ), '[san_tablero]' ); // phpcs:ignore WordPress.Security.EscapeOutput

		echo '<table class="form-table" role="presentation"><tbody>';
		echo '<tr><th scope="row"><label for="san-t-titulo">' . esc_html__( 'Título', 'suite-ambiente-narino' ) . '</label></th><td><input class="regular-text" id="san-t-titulo" name="san[titulo]" value="' . esc_attr( $t['titulo'] ) . '"></td></tr>';
		echo '<tr><th scope="row"><label for="san-t-col">' . esc_html__( 'Columnas', 'suite-ambiente-narino' ) . '</label></th><td><select id="san-t-col" name="san[columnas]">';
		for ( $i = 1; $i <= 4; $i++ ) {
			echo '<option value="' . (int) $i . '"' . selected( $i, (int) $t['columnas'], false ) . '>' . (int) $i . '</option>';
		}
		echo '</select> <span class="description">' . esc_html__( 'En pantallas angostas siempre se apila en una columna.', 'suite-ambiente-narino' ) . '</span></td></tr>';
		echo '<tr><th scope="row">' . esc_html__( 'Análisis', 'suite-ambiente-narino' ) . '</th><td><label><input type="checkbox" name="san[mostrar_analisis]" value="1"' . checked( $t['mostrar_analisis'], true, false ) . '> ' . esc_html__( 'Mostrar análisis cualitativo y cuantitativo en cada tarjeta', 'suite-ambiente-narino' ) . '</label></td></tr>';
		echo '<tr><th scope="row"><label for="san-t-act">' . esc_html__( 'Autoactualizar', 'suite-ambiente-narino' ) . '</label></th><td><input type="number" min="0" max="240" id="san-t-act" name="san[actualizar_min]" value="' . (int) $t['actualizar_min'] . '"> ' . esc_html__( 'minutos (0 = no)', 'suite-ambiente-narino' ) . '</td></tr>';
		echo '</tbody></table>';

		echo '<h3>' . esc_html__( 'Visualizaciones', 'suite-ambiente-narino' ) . '</h3><div class="san-seleccion">';
		$orden = array_flip( $t['visualizaciones'] );
		foreach ( SAN_Catalogo::por_fuente() as $fid => $subs ) {
			$f = SAN_Fuentes::obtener( $fid );
			echo '<fieldset><legend>' . esc_html( $f ? $f->nombre() : $fid ) . '</legend>';
			foreach ( $subs as $ids ) {
				foreach ( $ids as $id ) {
					$d   = SAN_Catalogo::obtener( $id );
					$sel = isset( $orden[ $id ] );
					echo '<div class="san-seleccion__fila"><label><input type="checkbox" name="san[visualizaciones][]" value="' . esc_attr( $id ) . '"' . checked( $sel, true, false ) . '> ' . esc_html( $d['titulo'] ) . '</label>';
					echo '<label class="san-orden"><span class="screen-reader-text">' . esc_html__( 'Orden', 'suite-ambiente-narino' ) . '</span><input type="number" min="1" max="99" name="san[orden][' . esc_attr( $id ) . ']" value="' . ( $sel ? (int) $orden[ $id ] + 1 : '' ) . '" placeholder="#"></label></div>';
				}
			}
			echo '</fieldset>';
		}
		echo '</div>';
		submit_button( __( 'Guardar tablero', 'suite-ambiente-narino' ) );
		echo '</form>';
	}

	/* ------------------------------------------------------------------
	 * APIs
	 * ---------------------------------------------------------------- */

	/**
	 * Pestaña APIs: configurar y verificar.
	 */
	private static function apis() {
		$estado = SAN_Cron::estado();
		$cache  = array();
		foreach ( SAN_Cache::estadisticas() as $c ) {
			$cache[ $c['grupo'] ] = $c;
		}

		echo '<div class="san-barra-acciones"><button type="button" class="button button-primary" id="san-probar-todas">' . esc_html__( 'Probar todas las fuentes activas', 'suite-ambiente-narino' ) . '</button> <button type="button" class="button" id="san-vaciar-cache">' . esc_html__( 'Vaciar toda la caché', 'suite-ambiente-narino' ) . '</button> <span class="san-estado-accion" role="status" aria-live="polite"></span></div>';

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'san_guardar_fuentes' );
		echo '<input type="hidden" name="action" value="san_guardar_fuentes">';

		$por_cat = array();
		foreach ( SAN_Fuentes::todas() as $id => $f ) {
			$por_cat[ $f->categoria() ][ $id ] = $f;
		}
		foreach ( $por_cat as $cat => $fuentes ) {
			echo '<h2 class="san-categoria">' . esc_html( SAN_Fuentes::nombre_categoria( $cat ) ) . '</h2>';
			foreach ( $fuentes as $id => $f ) {
				self::tarjeta_fuente( $f, $estado[ $id ] ?? null, $cache[ $id ] ?? null );
			}
		}
		echo '<div class="san-pie-fijo">';
		submit_button( __( 'Guardar configuración de APIs', 'suite-ambiente-narino' ), 'primary', 'submit', false );
		echo ' <span class="description">' . esc_html__( 'Guardar vacía la caché de las fuentes para aplicar los cambios.', 'suite-ambiente-narino' ) . '</span></div>';
		echo '</form>';
	}

	/**
	 * Tarjeta de configuración y verificación de una fuente.
	 *
	 * @param SAN_Fuente $f      Fuente.
	 * @param array|null $estado Último estado.
	 * @param array|null $cache  Estadísticas de caché.
	 */
	private static function tarjeta_fuente( SAN_Fuente $f, $estado, $cache ) {
		$id  = $f->id();
		$cfg = $f->config();
		$n   = 'fuente[' . $id . ']';

		echo '<article class="san-fuente" id="fuente-' . esc_attr( $id ) . '" data-fuente="' . esc_attr( $id ) . '">';
		echo '<header class="san-fuente__cabecera"><div><h3>' . esc_html( $f->nombre() ) . '</h3><p>' . esc_html( $f->descripcion() ) . '</p>';
		echo '<p class="san-meta"><span>' . esc_html( $f->frecuencia() ) . '</span><span>' . esc_html( $f->licencia() ) . '</span><a href="' . esc_url( $f->url_doc() ) . '" target="_blank" rel="noopener">' . esc_html__( 'Documentación', 'suite-ambiente-narino' ) . '</a>';
		echo '<span>' . esc_html__( 'Hosts:', 'suite-ambiente-narino' ) . ' ' . esc_html( implode( ', ', $f->hosts() ) ) . '</span></p></div>';
		echo '<div class="san-fuente__estado">' . SAN_Admin_Graficos::insignia_estado( $f, $estado ); // phpcs:ignore WordPress.Security.EscapeOutput -- escapado en insignia_estado().
		if ( $estado ) {
			echo '<p class="san-muted">' . esc_html( sprintf( /* translators: %s: fecha */ __( 'Verificada: %s', 'suite-ambiente-narino' ), wp_date( 'j M Y H:i', strtotime( $estado['fecha'] . ' UTC' ) ) ) ) . '</p>';
			if ( ! empty( $estado['frescura'] ) ) {
				echo '<p class="san-muted">' . esc_html( sprintf( /* translators: %s: fecha del dato */ __( 'Dato más reciente: %s', 'suite-ambiente-narino' ), $estado['frescura'] ) ) . '</p>';
			}
			if ( ! empty( $estado['historial'] ) ) {
				echo '<div class="san-historial" aria-label="' . esc_attr__( 'Últimas verificaciones (más reciente a la izquierda)', 'suite-ambiente-narino' ) . '">';
				foreach ( $estado['historial'] as $h ) {
					echo '<span class="' . ( $h['ok'] ? 'ok' : 'falla' ) . '" title="' . esc_attr( $h['fecha'] . ' UTC · ' . ( $h['ok'] ? 'OK' : 'Falla' ) . ' · ' . (int) $h['ms'] . ' ms · ' . $h['mensaje'] ) . '"></span>';
				}
				echo '</div>';
			}
		}
		echo '</div></header>';

		echo '<div class="san-fuente__cuerpo"><div class="san-campos-grid">';
		echo '<label class="san-interruptor"><input type="checkbox" name="' . esc_attr( $n ) . '[activa]" value="1"' . checked( ! empty( $cfg['activa'] ), true, false ) . '> ' . esc_html__( 'Activa', 'suite-ambiente-narino' ) . '</label>';
		echo '<label>' . esc_html__( 'Caché (min)', 'suite-ambiente-narino' ) . ' <input type="number" min="5" max="10080" name="' . esc_attr( $n ) . '[ttl]" value="' . (int) $cfg['ttl'] . '"></label>';
		echo '<label>' . esc_html__( 'Tiempo de espera (s)', 'suite-ambiente-narino' ) . ' <input type="number" min="3" max="60" name="' . esc_attr( $n ) . '[timeout]" value="' . (int) $cfg['timeout'] . '"></label>';

		if ( $f->admite_clave() ) {
			$mask = SAN_Seguridad::enmascarar( (string) $cfg['clave'] );
			echo '<label class="san-campo-ancho">' . esc_html( $f->requiere_clave() ? __( 'Clave de API', 'suite-ambiente-narino' ) : __( 'Clave de API (opcional)', 'suite-ambiente-narino' ) ) . ' <input type="password" autocomplete="new-password" name="' . esc_attr( $n ) . '[clave]" placeholder="' . esc_attr( $mask ? sprintf( /* translators: %s: clave enmascarada */ __( 'Guardada: %s (deje vacío para conservarla)', 'suite-ambiente-narino' ), $mask ) : __( 'Pegue aquí la clave', 'suite-ambiente-narino' ) ) . '"></label>';
			if ( $mask ) {
				echo '<label><input type="checkbox" name="' . esc_attr( $n ) . '[borrar_clave]" value="1"> ' . esc_html__( 'Borrar clave', 'suite-ambiente-narino' ) . '</label>';
			}
			if ( $f->url_clave() ) {
				echo '<p class="description san-campo-ancho"><a href="' . esc_url( $f->url_clave() ) . '" target="_blank" rel="noopener">' . esc_html__( 'Obtener una clave gratuita', 'suite-ambiente-narino' ) . '</a> · ' . esc_html__( 'Se guarda cifrada (AES-256-GCM) y nunca se envía al navegador.', 'suite-ambiente-narino' ) . '</p>';
			}
		}

		foreach ( $f->campos_params() as $k => $c ) {
			$val  = $cfg['params'][ $k ] ?? $c['defecto'];
			$name = $n . '[params][' . $k . ']';
			echo '<label>' . esc_html( $c['etiqueta'] ) . ' ';
			if ( 'select' === $c['tipo'] ) {
				echo '<select name="' . esc_attr( $name ) . '">';
				foreach ( $c['opciones'] as $ov => $ol ) {
					echo '<option value="' . esc_attr( $ov ) . '"' . selected( (string) $ov, (string) $val, false ) . '>' . esc_html( $ol ) . '</option>';
				}
				echo '</select>';
			} elseif ( 'numero' === $c['tipo'] ) {
				echo '<input type="number" step="any" name="' . esc_attr( $name ) . '" value="' . esc_attr( $val ) . '"' . ( isset( $c['min'] ) ? ' min="' . esc_attr( $c['min'] ) . '"' : '' ) . ( isset( $c['max'] ) ? ' max="' . esc_attr( $c['max'] ) . '"' : '' ) . '>';
			} else {
				echo '<input type="text" name="' . esc_attr( $name ) . '" value="' . esc_attr( $val ) . '">';
			}
			echo '</label>';
			if ( ! empty( $c['ayuda'] ) ) {
				echo '<p class="description">' . esc_html( $c['ayuda'] ) . '</p>';
			}
		}
		echo '</div>';

		echo '<div class="san-fuente__acciones"><button type="button" class="button san-probar" data-fuente="' . esc_attr( $id ) . '">' . esc_html__( 'Probar ahora', 'suite-ambiente-narino' ) . '</button> ';
		echo '<button type="button" class="button san-vaciar-fuente" data-fuente="' . esc_attr( $id ) . '">' . esc_html__( 'Vaciar caché', 'suite-ambiente-narino' ) . '</button>';
		if ( $cache ) {
			echo ' <span class="san-muted">' . esc_html( sprintf( /* translators: 1: entradas, 2: vigentes, 3: tamaño */ __( 'Caché: %1$d entradas (%2$d vigentes) · %3$s', 'suite-ambiente-narino' ), (int) $cache['entradas'], (int) $cache['vigentes'], size_format( (int) $cache['bytes'] ) ) ) . '</span>';
		}
		echo '</div><div class="san-resultado-prueba" aria-live="polite" hidden></div>';
		echo '</div></article>';
	}

	/* ------------------------------------------------------------------
	 * Registros
	 * ---------------------------------------------------------------- */

	/**
	 * Pestaña Registros.
	 */
	private static function registros() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- filtros de solo lectura.
		$f = array(
			'nivel'      => isset( $_GET['nivel'] ) ? sanitize_key( wp_unslash( $_GET['nivel'] ) ) : '',
			'fuente'     => isset( $_GET['fuente'] ) ? sanitize_key( wp_unslash( $_GET['fuente'] ) ) : '',
			'buscar'     => isset( $_GET['buscar'] ) ? sanitize_text_field( wp_unslash( $_GET['buscar'] ) ) : '',
			'pagina'     => isset( $_GET['pagina'] ) ? absint( $_GET['pagina'] ) : 1,
			'por_pagina' => 50,
		);
		// phpcs:enable
		$r   = SAN_Logger::consultar( $f );
		$res = SAN_Logger::resumen( 24 );

		echo '<section class="san-kpis san-kpis--compacto" aria-label="' . esc_attr__( 'Últimas 24 horas', 'suite-ambiente-narino' ) . '">';
		foreach ( SAN_Logger::NIVELES as $nivel => $peso ) {
			echo '<div class="san-kpi san-kpi--' . esc_attr( $nivel ) . '"><span class="san-kpi__etiqueta">' . esc_html( ucfirst( $nivel ) ) . ' · 24 h</span><span class="san-kpi__valor">' . (int) $res[ $nivel ] . '</span></div>';
		}
		echo '</section>';

		echo '<form method="get" class="san-filtros"><input type="hidden" name="page" value="san-config"><input type="hidden" name="pestana" value="registros">';
		echo '<label>' . esc_html__( 'Nivel', 'suite-ambiente-narino' ) . ' <select name="nivel"><option value="">' . esc_html__( 'Todos', 'suite-ambiente-narino' ) . '</option>';
		foreach ( array_keys( SAN_Logger::NIVELES ) as $nv ) {
			echo '<option value="' . esc_attr( $nv ) . '"' . selected( $nv, $f['nivel'], false ) . '>' . esc_html( ucfirst( $nv ) ) . '</option>';
		}
		echo '</select></label>';
		echo '<label>' . esc_html__( 'Fuente', 'suite-ambiente-narino' ) . ' <select name="fuente"><option value="">' . esc_html__( 'Todas', 'suite-ambiente-narino' ) . '</option>';
		foreach ( SAN_Logger::fuentes() as $fu ) {
			echo '<option value="' . esc_attr( $fu ) . '"' . selected( $fu, $f['fuente'], false ) . '>' . esc_html( $fu ) . '</option>';
		}
		echo '</select></label>';
		echo '<label>' . esc_html__( 'Buscar', 'suite-ambiente-narino' ) . ' <input type="search" name="buscar" value="' . esc_attr( $f['buscar'] ) . '"></label>';
		echo '<button class="button button-primary">' . esc_html__( 'Filtrar', 'suite-ambiente-narino' ) . '</button>';
		$exportar = wp_nonce_url(
			add_query_arg(
				array(
					'action' => 'san_exportar_logs',
					'nivel'  => $f['nivel'],
					'fuente' => $f['fuente'],
					'buscar' => $f['buscar'],
				),
				admin_url( 'admin-post.php' )
			),
			'san_exportar_logs'
		);
		echo ' <a class="button" href="' . esc_url( $exportar ) . '">' . esc_html__( 'Exportar CSV', 'suite-ambiente-narino' ) . '</a>';
		echo ' <button type="button" class="button" id="san-purgar-logs">' . esc_html( sprintf( /* translators: %d: días */ __( 'Purgar > %d días', 'suite-ambiente-narino' ), (int) SAN_Ajustes::general( 'retencion_logs', 30 ) ) ) . '</button>';
		echo ' <button type="button" class="button button-link-delete" id="san-vaciar-logs">' . esc_html__( 'Vaciar todo', 'suite-ambiente-narino' ) . '</button>';
		echo '</form>';

		echo '<table class="widefat striped san-logs"><thead><tr><th scope="col">' . esc_html__( 'Fecha (UTC)', 'suite-ambiente-narino' ) . '</th><th scope="col">' . esc_html__( 'Nivel', 'suite-ambiente-narino' ) . '</th><th scope="col">' . esc_html__( 'Fuente', 'suite-ambiente-narino' ) . '</th><th scope="col">' . esc_html__( 'Evento', 'suite-ambiente-narino' ) . '</th><th scope="col">' . esc_html__( 'Mensaje', 'suite-ambiente-narino' ) . '</th><th scope="col">HTTP</th><th scope="col">ms</th></tr></thead><tbody>';
		if ( ! $r['filas'] ) {
			echo '<tr><td colspan="7">' . esc_html__( 'No hay registros con esos filtros.', 'suite-ambiente-narino' ) . '</td></tr>';
		}
		foreach ( $r['filas'] as $fila ) {
			echo '<tr class="san-log san-log--' . esc_attr( $fila['nivel'] ) . '"><td>' . esc_html( $fila['fecha'] ) . '</td><td><span class="san-nivel san-nivel--' . esc_attr( $fila['nivel'] ) . '">' . esc_html( $fila['nivel'] ) . '</span></td><td>' . esc_html( $fila['fuente'] ) . '</td><td>' . esc_html( $fila['evento'] ) . '</td><td>' . esc_html( $fila['mensaje'] );
			if ( ! empty( $fila['contexto'] ) ) {
				echo '<details><summary>' . esc_html__( 'Contexto', 'suite-ambiente-narino' ) . '</summary><pre>' . esc_html( wp_json_encode( json_decode( $fila['contexto'], true ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ) . '</pre></details>';
			}
			echo '</td><td>' . esc_html( (string) $fila['http_codigo'] ) . '</td><td>' . esc_html( (string) $fila['duracion_ms'] ) . '</td></tr>';
		}
		echo '</tbody></table>';

		$paginas = (int) ceil( $r['total'] / $r['por_pagina'] );
		if ( $paginas > 1 ) {
			echo '<nav class="san-paginacion" aria-label="' . esc_attr__( 'Paginación', 'suite-ambiente-narino' ) . '">';
			echo wp_kses_post(
				paginate_links(
					array(
						'base'    => add_query_arg( 'pagina', '%#%' ),
						'format'  => '',
						'current' => $r['pagina'],
						'total'   => $paginas,
					)
				)
			);
			echo '</nav>';
		}
		echo '<p class="description">' . esc_html( sprintf( /* translators: 1: total, 2: nivel */ __( '%1$d registros. Nivel mínimo registrado: %2$s (cámbielo en General).', 'suite-ambiente-narino' ), (int) $r['total'], SAN_Ajustes::general( 'nivel_log', 'info' ) ) ) . '</p>';
	}

	/* ------------------------------------------------------------------
	 * General
	 * ---------------------------------------------------------------- */

	/**
	 * Pestaña General.
	 */
	private static function general() {
		$g = SAN_Ajustes::get( 'general' );
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="san-panel">';
		wp_nonce_field( 'san_guardar_general' );
		echo '<input type="hidden" name="action" value="san_guardar_general"><table class="form-table" role="presentation"><tbody>';

		self::fila_select( __( 'Librerías D3 / D3plus', 'suite-ambiente-narino' ), 'libreria', $g['libreria'], array( 'local' => __( 'Incluidas en el plugin (recomendado)', 'suite-ambiente-narino' ), 'cdn' => __( 'jsDelivr con integridad SRI', 'suite-ambiente-narino' ) ), sprintf( 'D3 %s · D3plus %s (@d3plus/core)', SAN_Assets::D3_VERSION, SAN_Assets::D3PLUS_VERSION ) );
		self::fila_check( __( 'Tipografía institucional', 'suite-ambiente-narino' ), 'fuentes_google', $g['fuentes_google'], __( 'Cargar Hind Madurai y Nunito Sans (Google Fonts), según el Manual de Identidad.', 'suite-ambiente-narino' ) );
		self::fila_select( __( 'Municipio por defecto', 'suite-ambiente-narino' ), 'municipio_defecto', $g['municipio_defecto'], SAN_Municipios::opciones(), '' );
		self::fila_select( __( 'Tema de color', 'suite-ambiente-narino' ), 'tema', $g['tema'], array( 'auto' => __( 'Automático (según el sistema)', 'suite-ambiente-narino' ), 'claro' => __( 'Claro', 'suite-ambiente-narino' ), 'oscuro' => __( 'Oscuro', 'suite-ambiente-narino' ) ), '' );
		self::fila_select( __( 'Renderizador D3plus v4', 'suite-ambiente-narino' ), 'renderer', $g['renderer'], array( 'svg' => 'SVG', 'canvas' => 'Canvas' ), __( 'Canvas es más rápido con muchos elementos; SVG es más nítido y accesible.', 'suite-ambiente-narino' ) );
		self::fila_check( __( 'Controles D3plus v4', 'suite-ambiente-narino' ), 'd3plus_tabla', $g['d3plus_tabla'], __( 'Vista de tabla nativa de D3plus (el plugin ya incluye su botón "Tabla"; en algunos temas estos controles reservan espacio de más)', 'suite-ambiente-narino' ) );
		self::fila_check( '', 'd3plus_zoom', $g['d3plus_zoom'], __( 'Zoom y desplazamiento', 'suite-ambiente-narino' ) );
		self::fila_check( '', 'd3plus_busqueda', $g['d3plus_busqueda'], __( 'Búsqueda y resaltado', 'suite-ambiente-narino' ) );
		self::fila_check( '', 'd3plus_minimapa', $g['d3plus_minimapa'], __( 'Minimapa al hacer zoom', 'suite-ambiente-narino' ) );
		self::fila_check( __( 'Atribución', 'suite-ambiente-narino' ), 'atribucion', $g['atribucion'], __( 'Mostrar la fuente bajo cada gráfico (obligatorio por licencia CC BY).', 'suite-ambiente-narino' ) );
		echo '<tr><th scope="row"><label for="san-g-lim">' . esc_html__( 'Límite API pública', 'suite-ambiente-narino' ) . '</label></th><td><input type="number" min="10" max="1000" id="san-g-lim" name="san[limite_peticiones]" value="' . (int) $g['limite_peticiones'] . '"> ' . esc_html__( 'peticiones por minuto e IP', 'suite-ambiente-narino' ) . '</td></tr>';
		self::fila_select( __( 'Nivel de registro', 'suite-ambiente-narino' ), 'nivel_log', $g['nivel_log'], array_combine( array_keys( SAN_Logger::NIVELES ), array_map( 'ucfirst', array_keys( SAN_Logger::NIVELES ) ) ), __( '"Depuracion" registra cada llamada HTTP correcta.', 'suite-ambiente-narino' ) );
		echo '<tr><th scope="row"><label for="san-g-ret">' . esc_html__( 'Retención de registros', 'suite-ambiente-narino' ) . '</label></th><td><input type="number" min="1" max="365" id="san-g-ret" name="san[retencion_logs]" value="' . (int) $g['retencion_logs'] . '"> ' . esc_html__( 'días', 'suite-ambiente-narino' ) . '</td></tr>';
		echo '</tbody></table>';
		submit_button( __( 'Guardar ajustes', 'suite-ambiente-narino' ) );
		echo '</form>';
	}

	/**
	 * Fila de select.
	 */
	private static function fila_select( $etiqueta, $clave, $valor, array $opciones, $ayuda ) {
		$id = 'san-g-' . $clave;
		echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $etiqueta ) . '</label></th><td><select id="' . esc_attr( $id ) . '" name="san[' . esc_attr( $clave ) . ']">';
		foreach ( $opciones as $k => $v ) {
			echo '<option value="' . esc_attr( $k ) . '"' . selected( (string) $k, (string) $valor, false ) . '>' . esc_html( $v ) . '</option>';
		}
		echo '</select>' . ( $ayuda ? ' <p class="description">' . esc_html( $ayuda ) . '</p>' : '' ) . '</td></tr>';
	}

	/**
	 * Fila de casilla.
	 */
	private static function fila_check( $etiqueta, $clave, $valor, $texto ) {
		echo '<tr><th scope="row">' . esc_html( $etiqueta ) . '</th><td><label><input type="checkbox" name="san[' . esc_attr( $clave ) . ']" value="1"' . checked( (bool) $valor, true, false ) . '> ' . esc_html( $texto ) . '</label></td></tr>';
	}
}
