<?php
/**
 * Visualizaciones de focos de calor (NASA FIRMS).
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Viz_Incendios extends SAN_Viz {

	const F = 'firms';

	/**
	 * Definiciones.
	 *
	 * @return array
	 */
	public static function definiciones() {
		$c = __CLASS__;
		return array(
			'incendios_mapa'       => array(
				'titulo'      => 'Focos de calor detectados por satélite',
				'fuente'      => self::F,
				'subgrupo'    => 'Focos de calor',
				'descripcion' => 'Focos de calor en Nariño detectados por los sensores VIIRS/MODIS. El tamaño indica la potencia radiativa del fuego (FRP).',
				'lectura'     => 'Un foco es un píxel de ~375 m con temperatura anómala: puede ser un incendio forestal, una quema agrícola o una fuente industrial. Las nubes impiden la detección.',
				'tipo'        => 'puntos',
				'tipos'       => array( 'puntos' ),
				'procesador'  => array( $c, 'mapa' ),
			),
			'incendios_municipios' => array(
				'titulo'      => 'Focos de calor por municipio',
				'fuente'      => self::F,
				'subgrupo'    => 'Focos de calor',
				'descripcion' => 'Número de focos de calor por municipio en la ventana configurada.',
				'lectura'     => 'Permite priorizar la verificación en campo y la vigilancia de quemas.',
				'tipo'        => 'barh',
				'tipos'       => array( 'barh', 'bar', 'treemap' ),
				'procesador'  => array( $c, 'municipios' ),
			),
			'incendios_diarios'    => array(
				'titulo'      => 'Focos de calor por día',
				'fuente'      => self::F,
				'subgrupo'    => 'Focos de calor',
				'descripcion' => 'Evolución diaria del número de focos de calor en Nariño.',
				'lectura'     => 'Los picos suelen coincidir con días secos y temporadas de preparación de tierras.',
				'tipo'        => 'bar',
				'tipos'       => array( 'bar', 'line' ),
				'procesador'  => array( $c, 'diarios' ),
			),
		);
	}

	/**
	 * Recursos.
	 *
	 * @return array
	 */
	public static function recursos() {
		return array(
			'focos_calor' => array(
				'titulo'      => 'Focos de calor en Nariño (FIRMS)',
				'descripcion' => 'Detecciones satelitales de fuego activo dentro del departamento con municipio, potencia radiativa y confianza.',
				'fuente'      => self::F,
				'formatos'    => array( 'json', 'csv', 'geojson' ),
				'campos'      => array(
					'fecha'     => 'Fecha de adquisición (UTC)',
					'hora_utc'  => 'Hora de adquisición (UTC)',
					'lat'       => 'Latitud',
					'lon'       => 'Longitud',
					'frp'       => 'Potencia radiativa del fuego (MW)',
					'confianza' => 'Confianza de la detección',
					'dia_noche' => 'Día o noche',
					'satelite'  => 'Sensor',
					'divipola'  => 'Código del municipio',
					'municipio' => 'Municipio',
					'subregion' => 'Subregión',
				),
				'generador'   => array( __CLASS__, 'gen' ),
			),
		);
	}

	/** @return array */
	public static function gen() {
		$r = SAN_Fuentes::obtener( self::F )->focos();
		return array(
			'ok'          => $r['ok'],
			'filas'       => (array) $r['datos'],
			'actualizado' => $r['actualizado'],
			'vencido'     => $r['vencido'],
			'error'       => $r['error'],
		);
	}

	/**
	 * Análisis común.
	 *
	 * @param array $filas Focos.
	 * @return array
	 */
	private static function analisis( array $filas ) {
		$n    = count( $filas );
		$muni = SAN_Analisis::agrupar( $filas, 'municipio' );
		$sub  = SAN_Analisis::agrupar( $filas, 'subregion' );
		$frp  = SAN_Analisis::estadisticos( array_column( $filas, 'frp' ) );
		$max  = SAN_Analisis::extremo( $filas, 'frp', true );
		$top  = array_slice( $muni, 0, 3, true );
		return array(
			'nivel'                => $n >= 30 ? 'alerta' : ( $n >= 10 ? 'moderado' : 'bueno' ),
			'titular'              => $n ? sprintf( '%d focos de calor en %d municipios; la subregión más afectada es %s.', $n, count( $muni ), (string) array_key_first( $sub ) ) : 'No se detectaron focos de calor en Nariño en la ventana consultada.',
			'cualitativo'          => $n ? array(
				'Municipios con más focos: ' . implode( ', ', array_map( function ( $k, $v ) { return $k . ' (' . $v . ')'; }, array_keys( $top ), $top ) ) . '.', // phpcs:ignore
				$max ? sprintf( 'El foco más intenso (%s MW) se detectó en %s el %s.', SAN_Analisis::num( $max['frp'] ), $max['municipio'], $max['fecha'] ) : '',
				'La detección depende del paso del satélite y de la nubosidad: la ausencia de focos no garantiza la ausencia de fuego.',
			) : array( 'La nubosidad persistente del Pacífico y la cordillera puede ocultar focos; contraste con reportes de bomberos y CMGRD.' ),
			'recomendaciones'      => $n ? array( 'Reportar quemas al cuerpo de bomberos (línea 119) y evitar quemas agrícolas en temporada seca.' ) : array(),
			'cuantitativo'         => array(
				self::cifra( 'Focos', (string) $n ),
				self::cifra( 'Municipios', (string) count( $muni ) ),
				self::cifra( 'FRP media', SAN_Analisis::con_unidad( $frp['media'], 'MW' ) ),
				self::cifra( 'FRP máxima', SAN_Analisis::con_unidad( $frp['max'], 'MW' ) ),
			),
			'resumen_cuantitativo' => $sub ? 'Por subregión: ' . implode( '; ', array_map( function ( $k, $v ) { return $k . ' ' . $v; }, array_keys( $sub ), $sub ) ) . '.' : '', // phpcs:ignore
			'metodo'               => 'FIRMS NRT recortado al polígono de Nariño (punto en polígono sobre el MGN del DANE).',
		);
	}

	/** @return array */
	public static function mapa() {
		$r = SAN_Fuentes::obtener( self::F )->focos();
		if ( ! $r['ok'] ) {
			return SAN_Catalogo::fallo( $r );
		}
		$filas = (array) $r['datos'];
		if ( ! $filas ) {
			return array(
				'ok'    => false,
				'error' => 'No se detectaron focos de calor en Nariño en la ventana consultada.',
			);
		}
		foreach ( $filas as &$f ) {
			$f['titulo'] = $f['municipio'] . ' · ' . $f['fecha'];
		}
		unset( $f );
		return self::ok(
			$filas,
			array(
				'lat'      => 'lat',
				'lon'      => 'lon',
				'tamano'   => 'frp',
				'etiqueta' => 'titulo',
				'tooltip'  => array( array( 'FRP', 'frp', 'MW', 1 ), array( 'Hora (UTC)', 'hora_utc' ), array( 'Confianza', 'confianza' ), array( 'Sensor', 'satelite' ) ),
			),
			self::analisis( $filas ),
			$r
		);
	}

	/** @return array */
	public static function municipios() {
		$r = SAN_Fuentes::obtener( self::F )->focos();
		if ( ! $r['ok'] ) {
			return SAN_Catalogo::fallo( $r );
		}
		$filas = (array) $r['datos'];
		$datos = array();
		foreach ( SAN_Analisis::agrupar( $filas, 'municipio' ) as $m => $n ) {
			$datos[] = array(
				'municipio' => $m,
				'focos'     => $n,
			);
		}
		if ( ! $datos ) {
			return array(
				'ok'    => false,
				'error' => 'No se detectaron focos de calor en Nariño en la ventana consultada.',
			);
		}
		return self::ok(
			$datos,
			array(
				'x'          => 'municipio',
				'y'          => 'focos',
				'etiqueta'   => 'municipio',
				'unidad'     => 'focos',
				'decimales'  => 0,
				'etiqueta_y' => 'Focos de calor',
			),
			self::analisis( $filas ),
			$r
		);
	}

	/** @return array */
	public static function diarios() {
		$r = SAN_Fuentes::obtener( self::F )->focos();
		if ( ! $r['ok'] ) {
			return SAN_Catalogo::fallo( $r );
		}
		$filas = (array) $r['datos'];
		$por   = array();
		foreach ( $filas as $f ) {
			$por[ $f['fecha'] ] = ( $por[ $f['fecha'] ] ?? 0 ) + 1;
		}
		ksort( $por );
		$datos = array();
		foreach ( $por as $d => $n ) {
			$datos[] = array(
				'fecha' => $d,
				'focos' => $n,
			);
		}
		if ( ! $datos ) {
			return array(
				'ok'    => false,
				'error' => 'No se detectaron focos de calor en Nariño en la ventana consultada.',
			);
		}
		return self::ok(
			$datos,
			array(
				'x'          => 'fecha',
				'y'          => 'focos',
				'tiempo'     => true,
				'unidad'     => 'focos',
				'decimales'  => 0,
				'etiqueta_x' => 'Fecha (UTC)',
				'etiqueta_y' => 'Focos de calor',
			),
			self::analisis( $filas ),
			$r
		);
	}
}
