<?php
/**
 * Módulo de datos abiertos.
 *
 * Publica, con licencia y diccionario de campos, los conjuntos de datos
 * normalizados que el plugin construye a partir de las fuentes, más la
 * cartografía base (municipios y subregiones). Formatos: JSON, CSV (UTF-8
 * con BOM, apto para Excel) y GeoJSON cuando las filas tienen coordenadas.
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Datos_Abiertos {

	/** @var array|null */
	private static $defs = null;

	/**
	 * Definiciones de recursos.
	 *
	 * @return array id => { titulo, descripcion, fuente, fuente_nombre, licencia, frecuencia, formatos, campos, generador, params }
	 */
	public static function definiciones() {
		if ( null !== self::$defs ) {
			return self::$defs;
		}
		$defs = array(
			'municipios'            => array(
				'titulo'      => 'Municipios de Nariño',
				'descripcion' => 'Tabla maestra de los 64 municipios: código DIVIPOLA, subregión, coordenadas de la cabecera, centroide, área y población censal 2018.',
				'fuente'      => 'dane',
				'frecuencia'  => 'Estática (DIVIPOLA / MGN 2018)',
				'licencia'    => 'Datos abiertos DANE',
				'formatos'    => array( 'json', 'csv', 'geojson' ),
				'campos'      => array(
					'divipola'             => 'Código DIVIPOLA de 5 dígitos',
					'nombre'               => 'Nombre oficial',
					'subregion'            => 'Subregión de planificación',
					'lat'                  => 'Latitud de la cabecera (WGS84)',
					'lon'                  => 'Longitud de la cabecera (WGS84)',
					'centroide_lat'        => 'Latitud del centroide del polígono municipal',
					'centroide_lon'        => 'Longitud del centroide del polígono municipal',
					'area_km2'             => 'Área en km²',
					'poblacion_censo_2018' => 'Personas censadas (CNPV 2018)',
				),
				'generador'   => array( __CLASS__, 'gen_municipios' ),
			),
			'geo_municipios'        => array(
				'titulo'      => 'Límites municipales (GeoJSON)',
				'descripcion' => 'Polígonos de los 64 municipios (MGN DANE 2018).',
				'fuente'      => 'dane',
				'frecuencia'  => 'Estática',
				'licencia'    => 'Datos abiertos DANE',
				'formatos'    => array( 'geojson' ),
				'archivo'     => 'narino_municipios.geojson',
				'campos'      => array(),
			),
			'geo_subregiones'       => array(
				'titulo'      => 'Límites de subregiones (GeoJSON)',
				'descripcion' => 'Contornos de las subregiones de planificación del departamento.',
				'fuente'      => 'dane',
				'frecuencia'  => 'Estática',
				'licencia'    => 'Gobernación de Nariño',
				'formatos'    => array( 'geojson' ),
				'archivo'     => 'narino_subregiones.geojson',
				'campos'      => array(),
			),
		);

		/**
		 * Recursos de las visualizaciones (cada clase SAN_Viz_* declara los suyos).
		 */
		foreach ( SAN_Catalogo::clases() as $clase ) {
			if ( method_exists( $clase, 'recursos' ) ) {
				$defs = array_merge( $defs, call_user_func( array( $clase, 'recursos' ) ) );
			}
		}

		foreach ( $defs as $id => $d ) {
			$f                          = SAN_Fuentes::obtener( $d['fuente'] );
			$defs[ $id ]['fuente_nombre'] = $f ? $f->nombre() : 'DANE · Gobernación de Nariño';
			$defs[ $id ]['licencia']      = $d['licencia'] ?? ( $f ? $f->licencia() : '' );
			$defs[ $id ]['frecuencia']    = $d['frecuencia'] ?? ( $f ? $f->frecuencia() : '' );
			$defs[ $id ]['params']        = $d['params'] ?? array();
		}
		self::$defs = apply_filters( 'san_datos_abiertos', $defs );
		return self::$defs;
	}

	/**
	 * @param string $id Id.
	 * @return bool
	 */
	public static function existe( $id ) {
		return isset( self::definiciones()[ $id ] );
	}

	/**
	 * @param string $id Id.
	 * @return array|null
	 */
	public static function definicion( $id ) {
		return self::definiciones()[ $id ] ?? null;
	}

	/**
	 * ¿La fuente del recurso está disponible?
	 *
	 * @param string $id Id.
	 * @return bool
	 */
	public static function disponible( $id ) {
		$d = self::definicion( $id );
		if ( ! $d ) {
			return false;
		}
		$f = SAN_Fuentes::obtener( $d['fuente'] );
		return ! $f || $f->disponible();
	}

	/**
	 * Obtiene las filas de un recurso.
	 *
	 * @param string $id     Id.
	 * @param array  $crudos Parámetros de la petición.
	 * @return array { ok, filas, campos, actualizado, vencido, error, licencia, fuente }
	 */
	public static function obtener( $id, array $crudos = array() ) {
		$d = self::definicion( $id );
		if ( ! $d ) {
			return array(
				'ok'    => false,
				'filas' => array(),
				'error' => 'Recurso inexistente',
			);
		}
		if ( ! empty( $d['archivo'] ) ) {
			$geo = json_decode( (string) file_get_contents( SAN_DIR . 'data/' . $d['archivo'] ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			return array(
				'ok'       => is_array( $geo ),
				'geojson'  => $geo,
				'filas'    => array(),
				'error'    => '',
				'licencia' => $d['licencia'],
			);
		}
		$params = array();
		if ( in_array( 'municipio', $d['params'], true ) ) {
			$m                   = SAN_Seguridad::sanear_divipola( $crudos['municipio'] ?? '' );
			$params['municipio'] = $m ? $m : SAN_Ajustes::general( 'municipio_defecto', '52001' );
		}
		$r = call_user_func( $d['generador'], $params );
		return array(
			'ok'          => ! empty( $r['ok'] ),
			'recurso'     => $id,
			'titulo'      => $d['titulo'],
			'fuente'      => $d['fuente_nombre'],
			'licencia'    => $d['licencia'],
			'campos'      => $d['campos'],
			'parametros'  => $params,
			'actualizado' => $r['actualizado'] ?? '',
			'vencido'     => ! empty( $r['vencido'] ),
			'error'       => $r['error'] ?? '',
			'filas'       => $r['filas'] ?? array(),
		);
	}

	/**
	 * Catálogo público (compatible en espíritu con DCAT: título, descripción,
	 * licencia, periodicidad y distribuciones).
	 *
	 * @return array
	 */
	public static function catalogo() {
		$out = array(
			'titulo'    => 'Datos abiertos ambientales — Gobernación de Nariño',
			'editor'    => 'Gobernación de Nariño · Secretaría TIC, Innovación y Gobierno Abierto',
			'generado'  => gmdate( 'c' ),
			'conjuntos' => array(),
		);
		foreach ( self::definiciones() as $id => $d ) {
			if ( ! self::disponible( $id ) ) {
				continue;
			}
			$dist = array();
			foreach ( $d['formatos'] as $fmt ) {
				$dist[] = array(
					'formato' => $fmt,
					'url'     => self::url( $id, $fmt ),
				);
			}
			$out['conjuntos'][] = array(
				'id'             => $id,
				'titulo'         => $d['titulo'],
				'descripcion'    => $d['descripcion'],
				'fuente'         => $d['fuente_nombre'],
				'licencia'       => $d['licencia'],
				'periodicidad'   => $d['frecuencia'],
				'parametros'     => $d['params'],
				'campos'         => $d['campos'],
				'distribuciones' => $dist,
			);
		}
		return $out;
	}

	/**
	 * URL pública de un recurso.
	 *
	 * @param string $id        Id.
	 * @param string $formato   json | csv | geojson.
	 * @param string $municipio DIVIPOLA opcional.
	 * @return string
	 */
	public static function url( $id, $formato = 'json', $municipio = '' ) {
		$d = self::definicion( $id );
		if ( $d && ! empty( $d['archivo'] ) ) {
			return SAN_URL . 'data/' . $d['archivo'];
		}
		$args = array( 'formato' => $formato );
		if ( $municipio ) {
			$args['municipio'] = $municipio;
		}
		return add_query_arg( $args, rest_url( SAN_REST_NS . '/datos/' . $id ) );
	}

	/**
	 * Filas → CSV (RFC 4180). Neutraliza inyección de fórmulas en hojas de
	 * cálculo anteponiendo un apóstrofo a celdas que empiezan por = + - @.
	 *
	 * @param array $filas Filas planas.
	 * @return string
	 */
	public static function a_csv( array $filas ) {
		if ( ! $filas ) {
			return '';
		}
		$cols = array();
		foreach ( $filas as $f ) {
			foreach ( array_keys( (array) $f ) as $k ) {
				$cols[ $k ] = true;
			}
		}
		$cols = array_keys( $cols );
		$out  = fopen( 'php://temp', 'r+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		fputcsv( $out, $cols, ',', '"', '\\' );
		foreach ( $filas as $f ) {
			$linea = array();
			foreach ( $cols as $c ) {
				$v = $f[ $c ] ?? '';
				if ( is_array( $v ) ) {
					$v = wp_json_encode( $v );
				} elseif ( is_bool( $v ) ) {
					$v = $v ? 'true' : 'false';
				}
				$v = (string) $v;
				if ( '' !== $v && ! is_numeric( $v ) && in_array( $v[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
					$v = "'" . $v;
				}
				$linea[] = $v;
			}
			fputcsv( $out, $linea, ',', '"', '\\' );
		}
		rewind( $out );
		$csv = stream_get_contents( $out );
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		return (string) $csv;
	}

	/**
	 * Filas con lat/lon → FeatureCollection de puntos.
	 *
	 * @param array $filas Filas.
	 * @return array
	 */
	public static function a_geojson( array $filas ) {
		$feats = array();
		foreach ( $filas as $f ) {
			$lat = SAN_Analisis::numero( $f['lat'] ?? ( $f['latitud'] ?? null ) );
			$lon = SAN_Analisis::numero( $f['lon'] ?? ( $f['longitud'] ?? null ) );
			if ( null === $lat || null === $lon ) {
				continue;
			}
			$feats[] = array(
				'type'       => 'Feature',
				'geometry'   => array(
					'type'        => 'Point',
					'coordinates' => array( $lon, $lat ),
				),
				'properties' => $f,
			);
		}
		return array(
			'type'     => 'FeatureCollection',
			'features' => $feats,
		);
	}

	/**
	 * Generador: tabla maestra de municipios.
	 *
	 * @return array
	 */
	public static function gen_municipios() {
		return array(
			'ok'          => true,
			'filas'       => SAN_Municipios::todos(),
			'actualizado' => '',
		);
	}
}
