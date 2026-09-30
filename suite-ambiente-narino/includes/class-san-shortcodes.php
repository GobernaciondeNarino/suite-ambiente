<?php
/**
 * Shortcodes públicos.
 *
 * Cada visualización del catálogo se publica con cuatro piezas
 * independientes que se pueden maquetar por separado (p. ej. en columnas
 * de Elementor) o juntas en una tarjeta:
 *
 *   [san_grafico id="…"]                 El gráfico (D3plus v4 o D3 v7).
 *   [san_descripcion id="…"]             Qué muestra y cómo leerlo.
 *   [san_analisis_cualitativo id="…"]    Interpretación y recomendaciones.
 *   [san_analisis_cuantitativo id="…"]   Cifras clave calculadas.
 *   [san_card id="…"]                    Las cuatro piezas en una tarjeta.
 *
 * Complementos: [san_tablero], [san_mapa], [san_datos], [san_datos_abiertos]
 * y [san_estado_apis].
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Shortcodes {

	/** @var int Contador para ids únicos en la página. */
	private static $n = 0;

	/**
	 * Registra los shortcodes.
	 */
	public function __construct() {
		add_shortcode( 'san_grafico', array( $this, 'grafico' ) );
		add_shortcode( 'san_descripcion', array( $this, 'descripcion' ) );
		add_shortcode( 'san_analisis_cualitativo', array( $this, 'cualitativo' ) );
		add_shortcode( 'san_analisis_cuantitativo', array( $this, 'cuantitativo' ) );
		add_shortcode( 'san_card', array( $this, 'card' ) );
		add_shortcode( 'san_tablero', array( $this, 'tablero' ) );
		add_shortcode( 'san_mapa', array( $this, 'mapa' ) );
		add_shortcode( 'san_datos', array( $this, 'datos' ) );
		add_shortcode( 'san_datos_abiertos', array( $this, 'datos_abiertos' ) );
		add_shortcode( 'san_estado_apis', array( $this, 'estado_apis' ) );
	}

	/**
	 * Atributos comunes saneados.
	 *
	 * @param array  $atts  Atributos crudos.
	 * @param string $tag   Shortcode.
	 * @return array|null null si la visualización no existe.
	 */
	private static function atts( $atts, $tag ) {
		$a = shortcode_atts(
			array(
				'id'           => '',
				'municipio'    => '',
				'tipo'         => '',
				'alto'         => '420',
				'selector'     => 'auto',
				'herramientas' => 'si',
				'titulo'       => 'si',
				'analisis'     => 'si',
				'tema'         => '',
			),
			$atts,
			$tag
		);
		$id = SAN_Seguridad::sanear_id( $a['id'] );
		$d  = SAN_Catalogo::obtener( $id );
		if ( ! $d ) {
			return null;
		}
		$a['id']        = $id;
		$a['def']       = $d;
		$a['municipio'] = SAN_Seguridad::sanear_divipola( $a['municipio'] );
		$a['tipo']      = SAN_Seguridad::elegir( $a['tipo'], $d['tipos'], $d['tipo'] );
		$a['alto']      = SAN_Seguridad::entero( $a['alto'], 200, 1200, 420 );
		$a['tema']      = SAN_Seguridad::elegir( $a['tema'], array( 'claro', 'oscuro', 'auto' ), '' );
		foreach ( array( 'herramientas', 'titulo', 'analisis' ) as $b ) {
			$a[ $b ] = ! in_array( strtolower( (string) $a[ $b ] ), array( 'no', '0', 'false' ), true );
		}
		$usa_municipio = in_array( 'municipio', $d['params'], true );
		$a['selector'] = 'auto' === $a['selector'] ? $usa_municipio : ( $usa_municipio && ! in_array( strtolower( (string) $a['selector'] ), array( 'no', '0', 'false' ), true ) );
		return $a;
	}

	/**
	 * Aviso para el editor cuando el id no existe (invisible al público).
	 *
	 * @param string $tag Shortcode.
	 * @return string
	 */
	private static function aviso_id( $tag ) {
		if ( current_user_can( 'edit_posts' ) ) {
			return '<p class="san-aviso">' . esc_html( sprintf( /* translators: %s: shortcode */ __( '[%s]: indique un id de visualización válido (vea Suite Ambiente → Gráficos).', 'suite-ambiente-narino' ), $tag ) ) . '</p>';
		}
		return '';
	}

	/* ------------------------------------------------------------------
	 * Piezas
	 * ---------------------------------------------------------------- */

	/**
	 * [san_grafico]
	 *
	 * @param array $atts Atributos.
	 * @return string
	 */
	public function grafico( $atts ) {
		$a = self::atts( $atts, 'san_grafico' );
		if ( ! $a ) {
			return self::aviso_id( 'san_grafico' );
		}
		SAN_Assets::encolar();
		return self::html_grafico( $a );
	}

	/**
	 * HTML del contenedor del gráfico (sin datos: los pide el JS a la API).
	 *
	 * @param array $a Atributos saneados.
	 * @return string
	 */
	public static function html_grafico( array $a ) {
		++self::$n;
		$d      = $a['def'];
		$uid    = 'san-g-' . $a['id'] . '-' . self::$n;
		$params = array();
		if ( $a['municipio'] ) {
			$params['municipio'] = $a['municipio'];
		}
		$attrs = array(
			'class'             => 'san-grafico' . ( $a['tema'] ? ' san-tema-' . $a['tema'] : '' ),
			'id'                => $uid,
			'data-viz'          => $a['id'],
			'data-tipo'         => $a['tipo'],
			'data-tipos'        => implode( ',', $d['tipos'] ),
			'data-motor'        => $d['motor'],
			'data-params'       => wp_json_encode( (object) $params ),
			'data-selector'     => $a['selector'] ? '1' : '0',
			'data-herramientas' => $a['herramientas'] ? '1' : '0',
			'style'             => '--san-alto:' . (int) $a['alto'] . 'px',
			'aria-labelledby'   => $uid . '-t',
		);
		$html = '<figure';
		foreach ( $attrs as $k => $v ) {
			$html .= ' ' . $k . '="' . esc_attr( $v ) . '"';
		}
		$html .= '>';
		$html .= '<figcaption class="san-grafico__cap' . ( $a['titulo'] ? '' : ' screen-reader-text' ) . '" id="' . esc_attr( $uid ) . '-t">' . esc_html( $d['titulo'] ) . '</figcaption>';
		$html .= '<div class="san-grafico__barra" role="toolbar" aria-label="' . esc_attr__( 'Controles del gráfico', 'suite-ambiente-narino' ) . '"></div>';
		$html .= '<div class="san-grafico__lienzo" aria-live="polite"><div class="san-cargando" role="status"><span class="san-cargando__barra"></span><span class="screen-reader-text">' . esc_html__( 'Cargando datos…', 'suite-ambiente-narino' ) . '</span></div></div>';
		$html .= '<p class="san-grafico__pie"></p>';
		$html .= '<noscript><p>' . esc_html__( 'Active JavaScript para ver el gráfico, o descargue los datos:', 'suite-ambiente-narino' ) . ' <a href="' . esc_url( self::url_csv( $a['id'], $params ) ) . '">CSV</a></p></noscript>';
		$html .= '</figure>';
		return $html;
	}

	/**
	 * [san_descripcion]
	 *
	 * @param array $atts Atributos.
	 * @return string
	 */
	public function descripcion( $atts ) {
		$a = self::atts( $atts, 'san_descripcion' );
		if ( ! $a ) {
			return self::aviso_id( 'san_descripcion' );
		}
		SAN_Assets::encolar();
		return self::html_descripcion( $a['def'] );
	}

	/**
	 * HTML de la descripción.
	 *
	 * @param array $d Definición.
	 * @return string
	 */
	public static function html_descripcion( array $d ) {
		$f    = SAN_Fuentes::obtener( $d['fuente'] );
		$html = '<div class="san-descripcion">';
		$html .= wpautop( esc_html( $d['descripcion'] ) );
		if ( ! empty( $d['lectura'] ) ) {
			$html .= '<p class="san-descripcion__lectura"><strong>' . esc_html__( 'Cómo leerlo:', 'suite-ambiente-narino' ) . '</strong> ' . esc_html( $d['lectura'] ) . '</p>';
		}
		if ( $f ) {
			$html .= '<p class="san-descripcion__fuente"><span>' . esc_html__( 'Fuente:', 'suite-ambiente-narino' ) . '</span> <a href="' . esc_url( $f->url_doc() ) . '" rel="noopener" target="_blank">' . esc_html( $f->nombre() ) . '</a> · ' . esc_html( $f->frecuencia() ) . ' · ' . esc_html( $f->licencia() ) . '</p>';
		}
		$html .= '</div>';
		return $html;
	}

	/**
	 * [san_analisis_cualitativo]
	 *
	 * @param array $atts Atributos.
	 * @return string
	 */
	public function cualitativo( $atts ) {
		$a = self::atts( $atts, 'san_analisis_cualitativo' );
		if ( ! $a ) {
			return self::aviso_id( 'san_analisis_cualitativo' );
		}
		SAN_Assets::encolar();
		$r = SAN_Catalogo::resolver( $a['id'], array( 'municipio' => $a['municipio'] ) );
		return self::html_cualitativo( $r );
	}

	/**
	 * HTML del análisis cualitativo.
	 *
	 * @param array $r Resultado de SAN_Catalogo::resolver().
	 * @return string
	 */
	public static function html_cualitativo( array $r ) {
		if ( empty( $r['ok'] ) ) {
			return '<div class="san-analisis san-analisis--vacio"><p>' . esc_html( $r['error'] ?? __( 'Sin datos disponibles.', 'suite-ambiente-narino' ) ) . '</p></div>';
		}
		$an    = $r['analisis'];
		$nivel = SAN_Umbrales::nivel( $an['nivel'] ?? 'info' );
		$html  = '<div class="san-analisis san-analisis--cualitativo">';
		$html .= '<p class="san-insignia" style="--san-insignia:' . esc_attr( $nivel['color'] ) . '"><span aria-hidden="true">' . esc_html( $nivel['icono'] ) . '</span> ' . esc_html( $an['etiqueta'] ?? $nivel['etiqueta'] ) . '</p>';
		if ( ! empty( $an['titular'] ) ) {
			$html .= '<p class="san-analisis__titular">' . esc_html( $an['titular'] ) . '</p>';
		}
		foreach ( (array) ( $an['cualitativo'] ?? array() ) as $p ) {
			$html .= '<p>' . esc_html( $p ) . '</p>';
		}
		if ( ! empty( $an['recomendaciones'] ) ) {
			$html .= '<p class="san-analisis__sub">' . esc_html__( 'Recomendaciones', 'suite-ambiente-narino' ) . '</p><ul>';
			foreach ( $an['recomendaciones'] as $rec ) {
				$html .= '<li>' . esc_html( $rec ) . '</li>';
			}
			$html .= '</ul>';
		}
		$html .= self::html_actualizado( $r );
		$html .= '</div>';
		return $html;
	}

	/**
	 * [san_analisis_cuantitativo]
	 *
	 * @param array $atts Atributos.
	 * @return string
	 */
	public function cuantitativo( $atts ) {
		$a = self::atts( $atts, 'san_analisis_cuantitativo' );
		if ( ! $a ) {
			return self::aviso_id( 'san_analisis_cuantitativo' );
		}
		SAN_Assets::encolar();
		$r = SAN_Catalogo::resolver( $a['id'], array( 'municipio' => $a['municipio'] ) );
		return self::html_cuantitativo( $r );
	}

	/**
	 * HTML del análisis cuantitativo (tabla de cifras + resumen).
	 *
	 * @param array $r Resultado de SAN_Catalogo::resolver().
	 * @return string
	 */
	public static function html_cuantitativo( array $r ) {
		if ( empty( $r['ok'] ) ) {
			return '<div class="san-analisis san-analisis--vacio"><p>' . esc_html( $r['error'] ?? __( 'Sin datos disponibles.', 'suite-ambiente-narino' ) ) . '</p></div>';
		}
		$an   = $r['analisis'];
		$html = '<div class="san-analisis san-analisis--cuantitativo">';
		if ( ! empty( $an['cuantitativo'] ) ) {
			$html .= '<dl class="san-cifras">';
			foreach ( $an['cuantitativo'] as $c ) {
				$html .= '<div class="san-cifra"><dt>' . esc_html( $c['etiqueta'] ) . '</dt><dd>' . esc_html( $c['valor'] ) . '</dd>';
				if ( ! empty( $c['detalle'] ) ) {
					$html .= '<dd class="san-cifra__detalle">' . esc_html( $c['detalle'] ) . '</dd>';
				}
				$html .= '</div>';
			}
			$html .= '</dl>';
		}
		if ( ! empty( $an['resumen_cuantitativo'] ) ) {
			$html .= '<p class="san-analisis__resumen">' . esc_html( $an['resumen_cuantitativo'] ) . '</p>';
		}
		if ( ! empty( $an['metodo'] ) ) {
			$html .= '<p class="san-analisis__metodo"><strong>' . esc_html__( 'Método:', 'suite-ambiente-narino' ) . '</strong> ' . esc_html( $an['metodo'] ) . '</p>';
		}
		$html .= self::html_actualizado( $r );
		$html .= '</div>';
		return $html;
	}

	/**
	 * Línea "Actualizado … (copia de respaldo)".
	 *
	 * @param array $r Resultado.
	 * @return string
	 */
	private static function html_actualizado( array $r ) {
		if ( empty( $r['actualizado'] ) ) {
			return '';
		}
		$t    = strtotime( $r['actualizado'] . ' UTC' );
		$html = '<p class="san-actualizado">' . esc_html__( 'Datos consultados:', 'suite-ambiente-narino' ) . ' <time datetime="' . esc_attr( gmdate( 'c', $t ) ) . '">' . esc_html( wp_date( 'j M Y, H:i', $t ) ) . '</time>';
		if ( ! empty( $r['vencido'] ) ) {
			$html .= ' · <span class="san-vencido">' . esc_html__( 'copia de respaldo: la fuente no respondió', 'suite-ambiente-narino' ) . '</span>';
		}
		return $html . '</p>';
	}

	/**
	 * [san_card]: las cuatro piezas en una tarjeta.
	 *
	 * @param array $atts Atributos.
	 * @return string
	 */
	public function card( $atts ) {
		$a = self::atts( $atts, 'san_card' );
		if ( ! $a ) {
			return self::aviso_id( 'san_card' );
		}
		SAN_Assets::encolar();
		return self::html_card( $a );
	}

	/**
	 * HTML de la tarjeta.
	 *
	 * @param array $a Atributos saneados.
	 * @return string
	 */
	public static function html_card( array $a ) {
		$d     = $a['def'];
		$f     = SAN_Fuentes::obtener( $d['fuente'] );
		$titul = $a['titulo'];
		$a['titulo'] = false; // El título lo lleva la cabecera de la tarjeta.

		$html  = '<article class="san-card' . ( $a['tema'] ? ' san-tema-' . esc_attr( $a['tema'] ) : '' ) . '">';
		if ( $titul ) {
			$html .= '<header class="san-card__cabecera"><p class="san-card__antetitulo">' . esc_html( ( $f ? $f->nombre() : '' ) . ' · ' . $d['subgrupo'] ) . '</p>';
			$html .= '<h3 class="san-card__titulo">' . esc_html( $d['titulo'] ) . '</h3></header>';
		}
		$html .= self::html_grafico( $a );
		$html .= '<details class="san-card__seccion" open><summary>' . esc_html__( 'Descripción', 'suite-ambiente-narino' ) . '</summary>' . self::html_descripcion( $d ) . '</details>';
		if ( $a['analisis'] ) {
			$r     = SAN_Catalogo::resolver( $a['id'], array( 'municipio' => $a['municipio'] ) );
			$html .= '<div class="san-card__analisis">';
			$html .= '<section><h4>' . esc_html__( 'Análisis cualitativo', 'suite-ambiente-narino' ) . '</h4>' . self::html_cualitativo( $r ) . '</section>';
			$html .= '<section><h4>' . esc_html__( 'Análisis cuantitativo', 'suite-ambiente-narino' ) . '</h4>' . self::html_cuantitativo( $r ) . '</section>';
			$html .= '</div>';
		}
		$html .= '</article>';
		return $html;
	}

	/* ------------------------------------------------------------------
	 * Complementos
	 * ---------------------------------------------------------------- */

	/**
	 * [san_tablero]: cuadrícula configurada en Configuración → Tablero.
	 *
	 * @param array $atts Atributos (columnas, ids separados por coma).
	 * @return string
	 */
	public function tablero( $atts ) {
		$t = SAN_Ajustes::get( 'tablero' );
		$a = shortcode_atts(
			array(
				'ids'      => '',
				'columnas' => $t['columnas'],
				'titulo'   => $t['titulo'],
				'analisis' => $t['mostrar_analisis'] ? 'si' : 'no',
			),
			$atts,
			'san_tablero'
		);
		$ids = '' !== $a['ids'] ? array_map( 'trim', explode( ',', $a['ids'] ) ) : $t['visualizaciones'];
		$ids = array_values( array_filter( array_map( array( SAN_Seguridad::class, 'sanear_id' ), $ids ), array( SAN_Catalogo::class, 'existe' ) ) );
		if ( ! $ids ) {
			return self::aviso_id( 'san_tablero' );
		}
		SAN_Assets::encolar();
		$cols = SAN_Seguridad::entero( $a['columnas'], 1, 4, 2 );
		$html = '<section class="san-tablero" style="--san-columnas:' . (int) $cols . '"' . ( $t['actualizar_min'] ? ' data-actualizar="' . (int) $t['actualizar_min'] . '"' : '' ) . '>';
		if ( '' !== trim( (string) $a['titulo'] ) ) {
			$html .= '<h2 class="san-tablero__titulo">' . esc_html( $a['titulo'] ) . '</h2>';
		}
		$html .= '<div class="san-tablero__rejilla">';
		foreach ( $ids as $id ) {
			$html .= $this->card(
				array(
					'id'       => $id,
					'analisis' => $a['analisis'],
				)
			);
		}
		return $html . '</div></section>';
	}

	/**
	 * [san_mapa variable="…"]: atajo a los mapas coropléticos del catálogo.
	 *
	 * @param array $atts Atributos.
	 * @return string
	 */
	public function mapa( $atts ) {
		$a     = shortcode_atts(
			array(
				'variable' => 'temperatura',
				'alto'     => '520',
			),
			$atts,
			'san_mapa'
		);
		$mapas = SAN_Catalogo::mapas();
		$var   = sanitize_key( $a['variable'] );
		$id    = $mapas[ $var ] ?? reset( $mapas );
		return $this->grafico(
			array(
				'id'   => $id,
				'alto' => $a['alto'],
			)
		);
	}

	/**
	 * [san_datos recurso="…" formato="csv|json|geojson" texto="…"]
	 *
	 * @param array $atts Atributos.
	 * @return string
	 */
	public function datos( $atts ) {
		$a  = shortcode_atts(
			array(
				'recurso'   => '',
				'formato'   => 'csv,json',
				'texto'     => '',
				'municipio' => '',
			),
			$atts,
			'san_datos'
		);
		$id = SAN_Seguridad::sanear_id( $a['recurso'] );
		if ( ! SAN_Datos_Abiertos::existe( $id ) ) {
			return current_user_can( 'edit_posts' ) ? '<p class="san-aviso">' . esc_html__( '[san_datos]: recurso inexistente (vea Suite Ambiente → Datos abiertos).', 'suite-ambiente-narino' ) . '</p>' : '';
		}
		SAN_Assets::encolar();
		$rec     = SAN_Datos_Abiertos::definicion( $id );
		$muni    = SAN_Seguridad::sanear_divipola( $a['municipio'] );
		$formatos = array_intersect( array_map( 'trim', explode( ',', strtolower( $a['formato'] ) ) ), $rec['formatos'] );
		$html    = '<div class="san-datos"><span class="san-datos__texto">' . esc_html( '' !== $a['texto'] ? $a['texto'] : $rec['titulo'] ) . '</span>';
		foreach ( $formatos as $fmt ) {
			$html .= ' <a class="san-boton" href="' . esc_url( SAN_Datos_Abiertos::url( $id, $fmt, $muni ) ) . '" download>' . esc_html( strtoupper( $fmt ) ) . '</a>';
		}
		return $html . '</div>';
	}

	/**
	 * [san_datos_abiertos]: catálogo público de recursos.
	 *
	 * @return string
	 */
	public function datos_abiertos() {
		SAN_Assets::encolar();
		$html = '<div class="san-catalogo-datos"><table class="san-tabla"><caption>' . esc_html__( 'Datos abiertos ambientales de Nariño', 'suite-ambiente-narino' ) . '</caption><thead><tr><th scope="col">' . esc_html__( 'Conjunto de datos', 'suite-ambiente-narino' ) . '</th><th scope="col">' . esc_html__( 'Fuente y licencia', 'suite-ambiente-narino' ) . '</th><th scope="col">' . esc_html__( 'Actualización', 'suite-ambiente-narino' ) . '</th><th scope="col">' . esc_html__( 'Descargas', 'suite-ambiente-narino' ) . '</th></tr></thead><tbody>';
		foreach ( SAN_Datos_Abiertos::definiciones() as $id => $rec ) {
			if ( ! SAN_Datos_Abiertos::disponible( $id ) ) {
				continue;
			}
			$html .= '<tr><td><strong>' . esc_html( $rec['titulo'] ) . '</strong><br><span class="san-muted">' . esc_html( $rec['descripcion'] ) . '</span></td>';
			$html .= '<td>' . esc_html( $rec['fuente_nombre'] ) . '<br><span class="san-muted">' . esc_html( $rec['licencia'] ) . '</span></td>';
			$html .= '<td>' . esc_html( $rec['frecuencia'] ) . '</td><td>';
			foreach ( $rec['formatos'] as $fmt ) {
				$html .= '<a class="san-boton" href="' . esc_url( SAN_Datos_Abiertos::url( $id, $fmt ) ) . '">' . esc_html( strtoupper( $fmt ) ) . '</a> ';
			}
			$html .= '</td></tr>';
		}
		return $html . '</tbody></table></div>';
	}

	/**
	 * [san_estado_apis]: estado público de las fuentes.
	 *
	 * @return string
	 */
	public function estado_apis() {
		SAN_Assets::encolar();
		$estado = SAN_Cron::estado();
		$html   = '<div class="san-estado"><ul class="san-estado__lista">';
		foreach ( SAN_Fuentes::todas() as $id => $f ) {
			if ( ! $f->disponible() ) {
				continue;
			}
			$e     = $estado[ $id ] ?? null;
			$ok    = $e ? (bool) $e['ok'] : null;
			$nivel = SAN_Umbrales::nivel( null === $ok ? 'info' : ( $ok ? 'bueno' : 'critico' ) );
			$txt   = null === $ok ? __( 'Sin verificar', 'suite-ambiente-narino' ) : ( $ok ? __( 'En funcionamiento', 'suite-ambiente-narino' ) : __( 'Con fallas', 'suite-ambiente-narino' ) );
			$html .= '<li><span class="san-insignia" style="--san-insignia:' . esc_attr( $nivel['color'] ) . '"><span aria-hidden="true">' . esc_html( $nivel['icono'] ) . '</span> ' . esc_html( $txt ) . '</span> <strong>' . esc_html( $f->nombre() ) . '</strong>';
			if ( $e ) {
				$html .= ' <span class="san-muted">· ' . esc_html( sprintf( /* translators: 1: ms, 2: fecha */ __( '%1$d ms · verificado %2$s', 'suite-ambiente-narino' ), (int) $e['ms'], wp_date( 'j M H:i', strtotime( $e['fecha'] . ' UTC' ) ) ) ) . '</span>';
			}
			$html .= '</li>';
		}
		return $html . '</ul></div>';
	}

	/**
	 * URL del CSV de una visualización.
	 *
	 * @param string $id     Id.
	 * @param array  $params Parámetros.
	 * @return string
	 */
	public static function url_csv( $id, array $params = array() ) {
		return add_query_arg( array_merge( $params, array( 'formato' => 'csv' ) ), rest_url( SAN_REST_NS . '/visualizaciones/' . $id ) );
	}
}
