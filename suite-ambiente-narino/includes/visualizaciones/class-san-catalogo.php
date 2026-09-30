<?php
/**
 * Catálogo de visualizaciones predefinidas.
 *
 * Cada clase SAN_Viz_* declara sus visualizaciones con `definiciones()`:
 *
 *   id => array(
 *     'titulo'      => string,
 *     'fuente'      => id de SAN_Fuente,
 *     'subgrupo'    => string (pestaña/subgrupo en el panel),
 *     'descripcion' => qué muestra,
 *     'lectura'     => cómo leerlo,
 *     'tipo'        => tipo por defecto,
 *     'tipos'       => tipos compatibles con la forma de los datos,
 *     'params'      => array( 'municipio' ) si acepta municipio,
 *     'procesador'  => callable( array $params ): array,
 *   )
 *
 * El procesador devuelve { ok, datos, config, analisis, actualizado,
 * vencido, error, nota }. El tipo de gráfico se elige según la forma de los
 * datos (serie temporal, categorías, flujos, geografía, matriz): los tipos
 * compatibles se listan en `tipos` y el usuario puede cambiar entre ellos.
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Catalogo {

	/** Formas de datos → tipos de gráfico compatibles (para documentación y validación). */
	const FORMAS = array(
		'serie'      => array( 'line', 'area', 'bar', 'stacked_area', 'stacked_bar', 'box' ),
		'categorias' => array( 'bar', 'barh', 'pie', 'donut', 'treemap', 'pack', 'radar' ),
		'flujo'      => array( 'chord', 'sankey' ),
		'geo'        => array( 'mapa', 'puntos' ),
		'matriz'     => array( 'calor', 'matrix' ),
		'indicador'  => array( 'medidor' ),
	);

	/** Motor por tipo. */
	const MOTOR_D3 = array( 'mapa', 'puntos', 'calor', 'medidor', 'rosa' );

	/** @var array|null */
	private static $defs = null;

	/** @var array Resultados resueltos en esta petición. */
	private static $memoria = array();

	/**
	 * Clases proveedoras de visualizaciones.
	 *
	 * @return string[]
	 */
	public static function clases() {
		return apply_filters(
			'san_clases_visualizaciones',
			array(
				SAN_Viz_Clima::class,
				SAN_Viz_Aire::class,
				SAN_Viz_Agua::class,
				SAN_Viz_Ideam::class,
				SAN_Viz_Oceano::class,
				SAN_Viz_Sismos::class,
				SAN_Viz_Eventos::class,
				SAN_Viz_Incendios::class,
				SAN_Viz_Radiacion::class,
				SAN_Viz_Biodiversidad::class,
			)
		);
	}

	/**
	 * Todas las definiciones.
	 *
	 * @return array
	 */
	public static function definiciones() {
		if ( null !== self::$defs ) {
			return self::$defs;
		}
		$defs = array();
		foreach ( self::clases() as $clase ) {
			foreach ( call_user_func( array( $clase, 'definiciones' ) ) as $id => $d ) {
				$d['id']     = $id;
				$d['params'] = $d['params'] ?? array();
				$d['tipos']  = $d['tipos'] ?? array( $d['tipo'] );
				$d['motor']  = in_array( $d['tipo'], self::MOTOR_D3, true ) ? 'd3' : 'd3plus';
				$defs[ $id ] = $d;
			}
		}
		self::$defs = apply_filters( 'san_visualizaciones', $defs );
		return self::$defs;
	}

	/**
	 * @param string $id Id.
	 * @return bool
	 */
	public static function existe( $id ) {
		return isset( self::definiciones()[ (string) $id ] );
	}

	/**
	 * @param string $id Id.
	 * @return array|null
	 */
	public static function obtener( $id ) {
		return self::definiciones()[ (string) $id ] ?? null;
	}

	/**
	 * Metadatos públicos (sin el procesador).
	 *
	 * @param string $id Id.
	 * @return array
	 */
	public static function meta( $id ) {
		$d = self::obtener( $id );
		if ( ! $d ) {
			return array();
		}
		$f = SAN_Fuentes::obtener( $d['fuente'] );
		return array(
			'id'          => $id,
			'titulo'      => $d['titulo'],
			'fuente'      => $d['fuente'],
			'fuente_nombre' => $f ? $f->nombre() : '',
			'subgrupo'    => $d['subgrupo'],
			'descripcion' => $d['descripcion'],
			'tipo'        => $d['tipo'],
			'tipos'       => $d['tipos'],
			'motor'       => $d['motor'],
			'params'      => $d['params'],
			'disponible'  => $f ? $f->disponible() : false,
			'shortcodes'  => self::shortcodes( $id ),
		);
	}

	/**
	 * Los cuatro shortcodes de una visualización (+ la tarjeta completa).
	 *
	 * @param string $id    Id.
	 * @param array  $extra Atributos adicionales (municipio, tipo…).
	 * @return array
	 */
	public static function shortcodes( $id, array $extra = array() ) {
		$attr = ' id="' . $id . '"';
		foreach ( $extra as $k => $v ) {
			if ( '' !== (string) $v ) {
				$attr .= ' ' . $k . '="' . $v . '"';
			}
		}
		return array(
			'grafico'      => '[san_grafico' . $attr . ']',
			'descripcion'  => '[san_descripcion id="' . $id . '"]',
			'cualitativo'  => '[san_analisis_cualitativo' . $attr . ']',
			'cuantitativo' => '[san_analisis_cuantitativo' . $attr . ']',
			'card'         => '[san_card' . $attr . ']',
		);
	}

	/**
	 * Agrupa por fuente y subgrupo (pestañas y subgrupos del panel).
	 *
	 * @return array fuente => subgrupo => id[]
	 */
	public static function por_fuente() {
		$out = array();
		foreach ( self::definiciones() as $id => $d ) {
			$out[ $d['fuente'] ][ $d['subgrupo'] ][] = $id;
		}
		return $out;
	}

	/**
	 * Visualizaciones de tipo mapa coroplético (atajo [san_mapa variable=…]).
	 *
	 * @return array variable => id
	 */
	public static function mapas() {
		$out = array();
		foreach ( self::definiciones() as $id => $d ) {
			if ( 'mapa' === $d['tipo'] && ! empty( $d['variable_mapa'] ) ) {
				$out[ $d['variable_mapa'] ] = $id;
			}
		}
		return $out;
	}

	/**
	 * Parámetros saneados de una visualización.
	 *
	 * @param array $d      Definición.
	 * @param array $crudos Parámetros crudos.
	 * @return array
	 */
	public static function params( array $d, array $crudos ) {
		$p = array();
		if ( in_array( 'municipio', $d['params'], true ) ) {
			$m              = SAN_Seguridad::sanear_divipola( $crudos['municipio'] ?? '' );
			$p['municipio'] = $m ? $m : SAN_Ajustes::general( 'municipio_defecto', '52001' );
		}
		return $p;
	}

	/**
	 * Resuelve una visualización: datos, configuración y análisis.
	 *
	 * @param string $id     Id.
	 * @param array  $crudos Parámetros crudos.
	 * @return array
	 */
	public static function resolver( $id, array $crudos ) {
		$d = self::obtener( $id );
		if ( ! $d ) {
			return array(
				'ok'    => false,
				'error' => 'Visualización inexistente',
			);
		}
		$params = self::params( $d, $crudos );
		$clave  = $id . ':' . md5( wp_json_encode( $params ) );
		if ( isset( self::$memoria[ $clave ] ) ) {
			return self::$memoria[ $clave ];
		}

		$fuente = SAN_Fuentes::obtener( $d['fuente'] );
		$base   = array(
			'id'         => $id,
			'titulo'     => $d['titulo'],
			'subgrupo'   => $d['subgrupo'],
			'tipo'       => $d['tipo'],
			'tipos'      => $d['tipos'],
			'motor'      => $d['motor'],
			'parametros' => $params,
			'fuente'     => $fuente ? array(
				'id'         => $fuente->id(),
				'nombre'     => $fuente->nombre(),
				'atribucion' => $fuente->atribucion(),
				'licencia'   => $fuente->licencia(),
				'url_doc'    => $fuente->url_doc(),
			) : null,
		);

		try {
			$r = call_user_func( $d['procesador'], $params );
		} catch ( \Throwable $e ) {
			SAN_Logger::error( $d['fuente'], 'procesador', 'Error procesando ' . $id . ': ' . $e->getMessage() );
			$r = array(
				'ok'    => false,
				'error' => __( 'Error interno al procesar los datos.', 'suite-ambiente-narino' ),
			);
		}

		$r = array_merge(
			$base,
			array(
				'ok'          => false,
				'datos'       => array(),
				'config'      => array(),
				'analisis'    => array(),
				'actualizado' => '',
				'vencido'     => false,
				'error'       => '',
				'nota'        => '',
			),
			$r
		);
		// Listas siempre secuenciales (JSON array, no objeto) y sin vacíos.
		foreach ( array( 'cualitativo', 'recomendaciones', 'cuantitativo' ) as $k ) {
			if ( isset( $r['analisis'][ $k ] ) && is_array( $r['analisis'][ $k ] ) ) {
				$r['analisis'][ $k ] = array_values( array_filter( $r['analisis'][ $k ] ) );
			}
		}
		$r['datos'] = self::sanear_textos( array_values( (array) $r['datos'] ) );
		if ( $r['ok'] && empty( $r['datos'] ) ) {
			$r['ok']    = false;
			$r['error'] = $r['error'] ? $r['error'] : __( 'La fuente no reporta datos para esta consulta.', 'suite-ambiente-narino' );
		}
		if ( ! $r['ok'] && ! $r['error'] ) {
			$r['error'] = __( 'La fuente de datos no está disponible en este momento.', 'suite-ambiente-narino' );
		}

		self::$memoria[ $clave ] = $r;
		return $r;
	}

	/**
	 * Quita etiquetas HTML de todos los textos de los datos. Los textos vienen
	 * de APIs externas y algunas librerías de gráficos (tooltips de D3plus)
	 * los insertan como HTML: se neutralizan aquí, en el origen.
	 *
	 * @param mixed $valor Datos.
	 * @return mixed
	 */
	public static function sanear_textos( $valor ) {
		if ( is_array( $valor ) ) {
			return array_map( array( __CLASS__, 'sanear_textos' ), $valor );
		}
		if ( is_string( $valor ) && preg_match( '/[<>&]/', $valor ) ) {
			return str_replace( array( '<', '>' ), array( '‹', '›' ), wp_strip_all_tags( $valor ) );
		}
		return $valor;
	}

	/**
	 * Resultado de error uniforme a partir de la respuesta de una fuente.
	 *
	 * @param array $resp Respuesta de SAN_Fuente::get_cacheado.
	 * @return array
	 */
	public static function fallo( array $resp ) {
		return array(
			'ok'    => false,
			'error' => $resp['error'] ? sprintf( /* translators: %s: error */ __( 'La fuente no respondió (%s).', 'suite-ambiente-narino' ), $resp['error'] ) : '',
		);
	}
}
