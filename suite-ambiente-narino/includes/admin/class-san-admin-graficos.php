<?php
/**
 * Módulo Gráficos: pestañas por API, subgrupos y una tarjeta por
 * visualización con sus shortcodes (gráfico, descripción, análisis
 * cualitativo, análisis cuantitativo y tarjeta completa) y vista previa.
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Admin_Graficos {

	/**
	 * Renderiza la página.
	 */
	public static function pagina() {
		if ( ! current_user_can( SAN_Admin::CAPACIDAD ) ) {
			return;
		}
		$grupos   = SAN_Catalogo::por_fuente();
		$pestanas = array();
		foreach ( $grupos as $fid => $subs ) {
			$f = SAN_Fuentes::obtener( $fid );
			if ( ! $f ) {
				continue;
			}
			$n                = array_sum( array_map( 'count', $subs ) );
			$pestanas[ $fid ] = esc_html( $f->nombre_corto() ) . ' <span class="san-contador">' . (int) $n . '</span>';
		}
		$pestanas['complementos'] = esc_html__( 'Complementos', 'suite-ambiente-narino' );
		$activa                   = SAN_Admin::pestana_activa( array_keys( $pestanas ), (string) array_key_first( $pestanas ) );

		echo '<div class="wrap san-admin">';
		SAN_Admin::encabezado(
			__( 'Gráficos', 'suite-ambiente-narino' ),
			sprintf(
				/* translators: 1: visualizaciones, 2: fuentes */
				__( '%1$d visualizaciones predefinidas sobre %2$d fuentes de datos. Cada tarjeta trae los shortcodes del gráfico, la descripción y los análisis cualitativo y cuantitativo.', 'suite-ambiente-narino' ),
				count( SAN_Catalogo::definiciones() ),
				count( $grupos )
			)
		);
		SAN_Admin::pestanas( 'san-graficos', $pestanas, $activa );

		if ( 'complementos' === $activa ) {
			self::complementos();
		} else {
			self::pestana_fuente( $activa, $grupos[ $activa ] ?? array() );
		}
		echo '</div>';
	}

	/**
	 * Contenido de la pestaña de una fuente.
	 *
	 * @param string $fid  Id de la fuente.
	 * @param array  $subs subgrupo => ids.
	 */
	private static function pestana_fuente( $fid, array $subs ) {
		$f = SAN_Fuentes::obtener( $fid );
		if ( ! $f ) {
			return;
		}
		$estado = SAN_Cron::estado()[ $fid ] ?? null;
		echo '<section class="san-fuente-resumen">';
		echo '<div><h2>' . esc_html( $f->nombre() ) . '</h2><p>' . esc_html( $f->descripcion() ) . '</p>';
		echo '<p class="san-meta"><span>' . esc_html( $f->frecuencia() ) . '</span><span>' . esc_html( $f->licencia() ) . '</span><a href="' . esc_url( $f->url_doc() ) . '" target="_blank" rel="noopener">' . esc_html__( 'Documentación', 'suite-ambiente-narino' ) . '</a></p></div>';
		echo '<div class="san-fuente-resumen__estado">' . self::insignia_estado( $f, $estado );
		echo '<a class="button" href="' . esc_url( admin_url( 'admin.php?page=san-config&pestana=apis#fuente-' . $fid ) ) . '">' . esc_html__( 'Configurar y verificar', 'suite-ambiente-narino' ) . '</a></div>';
		echo '</section>';

		if ( ! $f->disponible() ) {
			echo '<div class="notice notice-warning inline"><p>' . esc_html(
				$f->requiere_clave()
					? __( 'Esta fuente necesita una clave de API. Mientras no se configure, los gráficos mostrarán un aviso en lugar de datos.', 'suite-ambiente-narino' )
					: __( 'Esta fuente está desactivada. Actívela en Configuración → APIs para publicar sus gráficos.', 'suite-ambiente-narino' )
			) . '</p></div>';
		}

		// Índice de subgrupos (navegación interna).
		if ( count( $subs ) > 1 ) {
			echo '<nav class="san-subgrupos" aria-label="' . esc_attr__( 'Subgrupos', 'suite-ambiente-narino' ) . '">';
			foreach ( array_keys( $subs ) as $sg ) {
				echo '<a href="#sg-' . esc_attr( sanitize_title( $sg ) ) . '">' . esc_html( $sg ) . ' <span class="san-contador">' . count( $subs[ $sg ] ) . '</span></a>';
			}
			echo '</nav>';
		}

		foreach ( $subs as $sg => $ids ) {
			echo '<section class="san-subgrupo" id="sg-' . esc_attr( sanitize_title( $sg ) ) . '"><h2 class="san-subgrupo__titulo">' . esc_html( $sg ) . '</h2><div class="san-rejilla-tarjetas">';
			foreach ( $ids as $id ) {
				self::tarjeta( $id );
			}
			echo '</div></section>';
		}
	}

	/**
	 * Tarjeta de una visualización.
	 *
	 * @param string $id Id.
	 */
	private static function tarjeta( $id ) {
		$d      = SAN_Catalogo::obtener( $id );
		$sc     = SAN_Catalogo::shortcodes( $id );
		$con_m  = in_array( 'municipio', $d['params'], true );
		$nombres = array(
			'line'         => 'Líneas',
			'area'         => 'Área',
			'stacked_area' => 'Áreas apiladas',
			'bar'          => 'Barras',
			'barh'         => 'Barras horizontales',
			'stacked_bar'  => 'Barras apiladas',
			'pie'          => 'Torta',
			'donut'        => 'Dona',
			'treemap'      => 'Mapa de árbol',
			'pack'         => 'Burbujas',
			'radar'        => 'Radar',
			'box'          => 'Cajas y bigotes',
			'scatter'      => 'Dispersión',
			'bump'         => 'Ranking',
			'chord'        => 'Cuerdas (chord)',
			'sankey'       => 'Sankey',
			'matrix'       => 'Matriz',
			'mapa'         => 'Mapa coroplético',
			'puntos'       => 'Mapa de puntos',
			'calor'        => 'Mapa de calor',
			'medidor'      => 'Medidor',
			'rosa'         => 'Rosa de vientos',
		);

		echo '<article class="san-tarjeta" data-viz="' . esc_attr( $id ) . '" data-tipo="' . esc_attr( $d['tipo'] ) . '" data-tipos="' . esc_attr( implode( ',', $d['tipos'] ) ) . '">';
		echo '<header class="san-tarjeta__cabecera"><h3>' . esc_html( $d['titulo'] ) . '</h3>';
		echo '<p class="san-etiquetas"><span class="san-etiqueta san-etiqueta--motor">' . esc_html( 'd3' === $d['motor'] ? 'D3.js v7' : 'D3plus v4' ) . '</span>';
		foreach ( $d['tipos'] as $t ) {
			echo '<span class="san-etiqueta">' . esc_html( $nombres[ $t ] ?? $t ) . '</span>';
		}
		echo '</p></header>';
		echo '<p class="san-tarjeta__desc">' . esc_html( $d['descripcion'] ) . '</p>';
		echo '<p class="san-tarjeta__id"><code>' . esc_html( $id ) . '</code></p>';

		// Personalización: actualiza los shortcodes en vivo.
		echo '<fieldset class="san-personalizar"><legend>' . esc_html__( 'Personalizar', 'suite-ambiente-narino' ) . '</legend>';
		if ( count( $d['tipos'] ) > 1 ) {
			echo '<label>' . esc_html__( 'Tipo', 'suite-ambiente-narino' ) . ' <select data-attr="tipo">';
			foreach ( $d['tipos'] as $t ) {
				echo '<option value="' . esc_attr( $t ) . '"' . selected( $t, $d['tipo'], false ) . '>' . esc_html( $nombres[ $t ] ?? $t ) . '</option>';
			}
			echo '</select></label>';
		}
		if ( $con_m ) {
			echo '<label>' . esc_html__( 'Municipio', 'suite-ambiente-narino' ) . ' <select data-attr="municipio"><option value="">' . esc_html__( 'Por defecto', 'suite-ambiente-narino' ) . '</option>';
			foreach ( SAN_Municipios::opciones() as $cod => $nom ) {
				echo '<option value="' . esc_attr( $cod ) . '">' . esc_html( $nom ) . '</option>';
			}
			echo '</select></label>';
		}
		echo '<label>' . esc_html__( 'Alto (px)', 'suite-ambiente-narino' ) . ' <input type="number" min="200" max="1200" step="20" value="420" data-attr="alto"></label>';
		echo '</fieldset>';

		echo '<div class="san-shortcodes">';
		echo SAN_Admin::campo_shortcode( __( 'Gráfico', 'suite-ambiente-narino' ), $sc['grafico'], 'grafico' ); // phpcs:ignore WordPress.Security.EscapeOutput -- escapado en campo_shortcode().
		echo SAN_Admin::campo_shortcode( __( 'Descripción', 'suite-ambiente-narino' ), $sc['descripcion'], 'descripcion' ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo SAN_Admin::campo_shortcode( __( 'Análisis cualitativo', 'suite-ambiente-narino' ), $sc['cualitativo'], 'cualitativo' ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo SAN_Admin::campo_shortcode( __( 'Análisis cuantitativo', 'suite-ambiente-narino' ), $sc['cuantitativo'], 'cuantitativo' ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<details class="san-sc-extra"><summary>' . esc_html__( 'Tarjeta completa (las cuatro piezas juntas)', 'suite-ambiente-narino' ) . '</summary>';
		echo SAN_Admin::campo_shortcode( __( 'Tarjeta', 'suite-ambiente-narino' ), $sc['card'], 'card' ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo '</details></div>';

		echo '<div class="san-tarjeta__acciones"><button type="button" class="button button-primary san-previa" aria-expanded="false">' . esc_html__( 'Vista previa', 'suite-ambiente-narino' ) . '</button>';
		echo '<a class="button" href="' . esc_url( SAN_Shortcodes::url_csv( $id ) ) . '">' . esc_html__( 'Datos CSV', 'suite-ambiente-narino' ) . '</a></div>';
		echo '<div class="san-tarjeta__previa" hidden></div>';
		echo '</article>';
	}

	/**
	 * Pestaña de complementos: tablero, mapas, datos y estado.
	 */
	private static function complementos() {
		$items = array(
			array( __( 'Tablero completo', 'suite-ambiente-narino' ), '[san_tablero]', __( 'Rejilla de tarjetas configurada en Configuración → Tablero. Admite ids="a,b,c" y columnas="3".', 'suite-ambiente-narino' ) ),
			array( __( 'Mapa por variable', 'suite-ambiente-narino' ), '[san_mapa variable="temperatura"]', sprintf( /* translators: %s: variables */ __( 'Atajo a los mapas coropléticos. Variables: %s.', 'suite-ambiente-narino' ), implode( ', ', array_keys( SAN_Catalogo::mapas() ) ) ) ),
			array( __( 'Botón de descarga de datos', 'suite-ambiente-narino' ), '[san_datos recurso="municipios" formato="csv,json"]', __( 'Enlaces de descarga de un recurso de Datos abiertos.', 'suite-ambiente-narino' ) ),
			array( __( 'Catálogo de datos abiertos', 'suite-ambiente-narino' ), '[san_datos_abiertos]', __( 'Tabla pública con todos los conjuntos de datos y sus descargas.', 'suite-ambiente-narino' ) ),
			array( __( 'Estado de las APIs', 'suite-ambiente-narino' ), '[san_estado_apis]', __( 'Panel público de funcionamiento de las fuentes (última verificación).', 'suite-ambiente-narino' ) ),
		);
		echo '<div class="san-rejilla-tarjetas">';
		foreach ( $items as $it ) {
			echo '<article class="san-tarjeta"><header class="san-tarjeta__cabecera"><h3>' . esc_html( $it[0] ) . '</h3></header><p class="san-tarjeta__desc">' . esc_html( $it[2] ) . '</p>';
			echo SAN_Admin::campo_shortcode( __( 'Shortcode', 'suite-ambiente-narino' ), $it[1] ); // phpcs:ignore WordPress.Security.EscapeOutput
			echo '</article>';
		}
		echo '</div>';
		echo '<h2>' . esc_html__( 'Atributos comunes', 'suite-ambiente-narino' ) . '</h2><table class="widefat striped san-tabla-atributos"><thead><tr><th>' . esc_html__( 'Atributo', 'suite-ambiente-narino' ) . '</th><th>' . esc_html__( 'Valores', 'suite-ambiente-narino' ) . '</th><th>' . esc_html__( 'Efecto', 'suite-ambiente-narino' ) . '</th></tr></thead><tbody>';
		$atributos = array(
			array( 'id', 'id del catálogo', 'Visualización a mostrar (obligatorio).' ),
			array( 'municipio', 'DIVIPOLA (52001) o nombre', 'Municipio consultado en las visualizaciones que lo admiten.' ),
			array( 'tipo', 'uno de los tipos compatibles', 'Tipo de gráfico inicial; el visitante puede cambiarlo.' ),
			array( 'alto', '200–1200', 'Alto del gráfico en píxeles.' ),
			array( 'selector', 'si | no', 'Muestra el selector de municipio.' ),
			array( 'herramientas', 'si | no', 'Muestra la barra (tipo, CSV, JSON, PNG).' ),
			array( 'titulo', 'si | no', 'Muestra el título del gráfico o de la tarjeta.' ),
			array( 'analisis', 'si | no', 'En [san_card]: incluye los análisis.' ),
			array( 'tema', 'claro | oscuro | auto', 'Fuerza el tema de color.' ),
		);
		foreach ( $atributos as $a ) {
			echo '<tr><td><code>' . esc_html( $a[0] ) . '</code></td><td>' . esc_html( $a[1] ) . '</td><td>' . esc_html( $a[2] ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}

	/**
	 * Insignia de estado de una fuente.
	 *
	 * @param SAN_Fuente $f      Fuente.
	 * @param array|null $estado Último resultado de prueba.
	 * @return string
	 */
	public static function insignia_estado( $f, $estado ) {
		if ( ! $f->disponible() ) {
			$n   = SAN_Umbrales::nivel( 'moderado' );
			$txt = $f->requiere_clave() && '' === (string) $f->config()['clave'] ? __( 'Requiere clave', 'suite-ambiente-narino' ) : __( 'Desactivada', 'suite-ambiente-narino' );
		} elseif ( ! $estado ) {
			$n   = SAN_Umbrales::nivel( 'info' );
			$txt = __( 'Sin verificar', 'suite-ambiente-narino' );
		} elseif ( $estado['ok'] ) {
			$n   = SAN_Umbrales::nivel( 'bueno' );
			$txt = sprintf( /* translators: %d: ms */ __( 'Funciona · %d ms', 'suite-ambiente-narino' ), (int) $estado['ms'] );
		} else {
			$n   = SAN_Umbrales::nivel( 'critico' );
			$txt = __( 'Con fallas', 'suite-ambiente-narino' );
		}
		return '<span class="san-insignia" style="--san-insignia:' . esc_attr( $n['color'] ) . '"><span aria-hidden="true">' . esc_html( $n['icono'] ) . '</span> ' . esc_html( $txt ) . '</span>';
	}
}
