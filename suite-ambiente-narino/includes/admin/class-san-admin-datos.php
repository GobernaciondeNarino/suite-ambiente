<?php
/**
 * Módulo Datos abiertos: recursos publicados, endpoints, diccionario de
 * campos, shortcode de descarga y vista previa.
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Admin_Datos {

	/**
	 * Renderiza la página.
	 */
	public static function pagina() {
		if ( ! current_user_can( SAN_Admin::CAPACIDAD ) ) {
			return;
		}
		$base = rest_url( SAN_REST_NS . '/' );
		echo '<div class="wrap san-admin">';
		SAN_Admin::encabezado(
			__( 'Datos abiertos', 'suite-ambiente-narino' ),
			__( 'Conjuntos de datos que el plugin publica en JSON, CSV y GeoJSON, con licencia, periodicidad y diccionario de campos. Cumple los criterios de publicación del Manual de Sitios Web: descargables, sin restricciones y procesables por máquina.', 'suite-ambiente-narino' )
		);

		echo '<section class="san-panel san-api-info"><h2>' . esc_html__( 'API pública', 'suite-ambiente-narino' ) . '</h2><dl class="san-dl">';
		$endpoints = array(
			__( 'Catálogo de datos', 'suite-ambiente-narino' )         => $base . 'datos',
			__( 'Un recurso', 'suite-ambiente-narino' )                => $base . 'datos/{recurso}?formato=json|csv|geojson&municipio=52001',
			__( 'Catálogo de visualizaciones', 'suite-ambiente-narino' ) => $base . 'visualizaciones',
			__( 'Datos de una visualización', 'suite-ambiente-narino' ) => $base . 'visualizaciones/{id}?municipio=52001&formato=json|csv',
			__( 'Municipios', 'suite-ambiente-narino' )                => $base . 'municipios',
			__( 'Estado de las fuentes', 'suite-ambiente-narino' )     => $base . 'estado',
		);
		foreach ( $endpoints as $k => $v ) {
			echo '<div><dt>' . esc_html( $k ) . '</dt><dd><code>GET ' . esc_html( $v ) . '</code></dd></div>';
		}
		echo '</dl><p class="description">' . esc_html(
			sprintf(
				/* translators: %d: límite */
				__( 'Acceso anónimo de solo lectura con CORS abierto y límite de %d peticiones por minuto e IP (configurable en Configuración → General).', 'suite-ambiente-narino' ),
				(int) SAN_Ajustes::general( 'limite_peticiones', 300 )
			)
		) . '</p></section>';

		echo '<div class="san-rejilla-tarjetas san-rejilla-tarjetas--ancha">';
		foreach ( SAN_Datos_Abiertos::definiciones() as $id => $d ) {
			$disp = SAN_Datos_Abiertos::disponible( $id );
			echo '<article class="san-tarjeta" data-recurso="' . esc_attr( $id ) . '">';
			echo '<header class="san-tarjeta__cabecera"><h3>' . esc_html( $d['titulo'] ) . '</h3><p class="san-etiquetas">';
			foreach ( $d['formatos'] as $fmt ) {
				echo '<span class="san-etiqueta">' . esc_html( strtoupper( $fmt ) ) . '</span>';
			}
			if ( ! $disp ) {
				echo '<span class="san-etiqueta san-etiqueta--aviso">' . esc_html__( 'Fuente inactiva', 'suite-ambiente-narino' ) . '</span>';
			}
			echo '</p></header>';
			echo '<p class="san-tarjeta__desc">' . esc_html( $d['descripcion'] ) . '</p>';
			echo '<p class="san-meta"><span>' . esc_html( $d['fuente_nombre'] ) . '</span><span>' . esc_html( $d['licencia'] ) . '</span><span>' . esc_html( $d['frecuencia'] ) . '</span></p>';

			foreach ( $d['formatos'] as $fmt ) {
				echo SAN_Admin::campo_shortcode( sprintf( /* translators: %s: formato */ __( 'URL %s', 'suite-ambiente-narino' ), strtoupper( $fmt ) ), SAN_Datos_Abiertos::url( $id, $fmt ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			echo SAN_Admin::campo_shortcode( __( 'Shortcode de descarga', 'suite-ambiente-narino' ), '[san_datos recurso="' . $id . '" formato="' . implode( ',', $d['formatos'] ) . '"]' ); // phpcs:ignore WordPress.Security.EscapeOutput

			if ( ! empty( $d['campos'] ) ) {
				echo '<details class="san-campos"><summary>' . esc_html__( 'Diccionario de campos', 'suite-ambiente-narino' ) . ' (' . count( $d['campos'] ) . ')</summary><table class="widefat striped"><tbody>';
				foreach ( $d['campos'] as $c => $desc ) {
					echo '<tr><td><code>' . esc_html( $c ) . '</code></td><td>' . esc_html( $desc ) . '</td></tr>';
				}
				echo '</tbody></table></details>';
			}
			if ( in_array( 'municipio', $d['params'], true ) ) {
				echo '<p class="description">' . esc_html__( 'Admite el parámetro municipio (código DIVIPOLA).', 'suite-ambiente-narino' ) . '</p>';
			}
			if ( $disp && empty( $d['archivo'] ) ) {
				echo '<div class="san-tarjeta__acciones"><button type="button" class="button san-previa-datos" aria-expanded="false">' . esc_html__( 'Vista previa (10 filas)', 'suite-ambiente-narino' ) . '</button></div><div class="san-previa-tabla" hidden></div>';
			}
			echo '</article>';
		}
		echo '</div></div>';
	}
}
