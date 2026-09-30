<?php
/**
 * Visualizaciones de eventos naturales con alerta (GDACS).
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Viz_Eventos extends SAN_Viz {

	const F = 'gdacs';

	/** Colores oficiales de alerta GDACS (con texto en la leyenda). */
	const COLORES = array(
		'Green'  => '#2E9D3A',
		'Orange' => '#EF7D00',
		'Red'    => '#D12A1F',
	);

	/** Nombres de alerta. */
	const ALERTAS = array(
		'Green'  => 'Verde',
		'Orange' => 'Naranja',
		'Red'    => 'Roja',
	);

	/**
	 * Definiciones.
	 *
	 * @return array
	 */
	public static function definiciones() {
		$c = __CLASS__;
		return array(
			'eventos_tipos'   => array(
				'titulo'      => 'Eventos naturales del último año por tipo',
				'fuente'      => self::F,
				'subgrupo'    => 'Alertas GDACS',
				'descripcion' => 'Número de eventos con alerta GDACS en Colombia y Ecuador durante los últimos 12 meses, según su tipo.',
				'lectura'     => 'GDACS emite alerta verde (impacto humanitario bajo), naranja (moderado) o roja (alto) a partir de modelos de exposición.',
				'tipo'        => 'donut',
				'tipos'       => array( 'donut', 'pie', 'bar', 'treemap' ),
				'procesador'  => array( $c, 'tipos' ),
			),
			'eventos_mapa'    => array(
				'titulo'      => 'Mapa de eventos con alerta',
				'fuente'      => self::F,
				'subgrupo'    => 'Alertas GDACS',
				'descripcion' => 'Ubicación de los eventos del último año con su nivel de alerta.',
				'lectura'     => 'El color indica el nivel de alerta; el mapa se ajusta para incluir todos los eventos.',
				'tipo'        => 'puntos',
				'tipos'       => array( 'puntos' ),
				'procesador'  => array( $c, 'mapa' ),
			),
			'eventos_mensual' => array(
				'titulo'      => 'Eventos por mes',
				'fuente'      => self::F,
				'subgrupo'    => 'Alertas GDACS',
				'descripcion' => 'Eventos por mes y tipo durante el último año.',
				'lectura'     => 'Las barras apiladas permiten ver qué tipo de evento predomina en cada temporada.',
				'tipo'        => 'stacked_bar',
				'tipos'       => array( 'stacked_bar', 'bar', 'stacked_area' ),
				'procesador'  => array( $c, 'mensual' ),
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
			'eventos_gdacs' => array(
				'titulo'      => 'Eventos naturales con alerta (GDACS)',
				'descripcion' => 'Eventos del último año en Colombia y Ecuador con tipo, nivel de alerta, fechas y ubicación.',
				'fuente'      => self::F,
				'formatos'    => array( 'json', 'csv', 'geojson' ),
				'campos'      => array(
					'id'          => 'Identificador GDACS',
					'tipo'        => 'Tipo de evento',
					'nombre'      => 'Nombre',
					'alerta'      => 'Nivel de alerta (Green/Orange/Red)',
					'pais'        => 'País consultado',
					'desde'       => 'Fecha de inicio',
					'hasta'       => 'Fecha de fin',
					'lat'         => 'Latitud',
					'lon'         => 'Longitud',
					'descripcion' => 'Descripción',
					'enlace'      => 'Informe GDACS',
				),
				'generador'   => array( __CLASS__, 'gen' ),
			),
		);
	}

	/** @return array */
	public static function gen() {
		$r = SAN_Fuentes::obtener( self::F )->eventos();
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
	 * @param array $filas Eventos.
	 * @return array
	 */
	private static function analisis( array $filas ) {
		$tipos  = SAN_Analisis::agrupar( $filas, 'tipo' );
		$alert  = SAN_Analisis::agrupar( $filas, 'alerta' );
		$narino = array_filter(
			$filas,
			function ( $f ) {
				return null !== $f['lat'] && SAN_Geo::en_region( $f['lat'], $f['lon'], 0.2 );
			}
		);
		$graves = ( $alert['Orange'] ?? 0 ) + ( $alert['Red'] ?? 0 );
		$top    = (string) array_key_first( $tipos );
		return array(
			'nivel'                => ! empty( $alert['Red'] ) ? 'critico' : ( $graves ? 'alerta' : 'info' ),
			'titular'              => sprintf( '%d eventos con alerta en el último año; el tipo más frecuente es %s (%d). %d ocurrieron en Nariño o su entorno.', count( $filas ), mb_strtolower( $top ), $tipos[ $top ] ?? 0, count( $narino ) ),
			'cualitativo'          => array_values(
				array_filter(
					array(
						$graves ? sprintf( '%d evento%s alcanzaron alerta naranja o roja.', $graves, 1 === $graves ? '' : 's' ) : 'Todos los eventos tuvieron alerta verde (impacto humanitario estimado bajo).',
						$narino ? 'En Nariño: ' . implode( '; ', array_map( function ( $f ) { return $f['tipo'] . ' (' . $f['desde'] . ')'; }, array_slice( $narino, 0, 5 ) ) ) . '.' : '', // phpcs:ignore
						'GDACS prioriza eventos de gran escala: deslizamientos, crecientes súbitas locales y emergencias menores se registran en la UNGRD y los consejos municipales de gestión del riesgo.',
					)
				)
			),
			'recomendaciones'      => array(),
			'cuantitativo'         => array(
				self::cifra( 'Eventos', (string) count( $filas ) ),
				self::cifra( 'Alerta verde', (string) ( $alert['Green'] ?? 0 ) ),
				self::cifra( 'Naranja o roja', (string) $graves ),
				self::cifra( 'En Nariño', (string) count( $narino ) ),
			),
			'resumen_cuantitativo' => 'Por tipo: ' . implode( '; ', array_map( function ( $k, $v ) { return $k . ' ' . $v; }, array_keys( $tipos ), $tipos ) ) . '.', // phpcs:ignore
			'metodo'               => 'Lista de eventos GDACS (API geteventlist) por país; "Nariño" = coordenadas dentro de la caja departamental ± 0,2°.',
		);
	}

	/** @return array */
	public static function tipos() {
		$r = SAN_Fuentes::obtener( self::F )->eventos();
		if ( ! $r['ok'] ) {
			return SAN_Catalogo::fallo( $r );
		}
		$filas = (array) $r['datos'];
		$datos = array();
		foreach ( SAN_Analisis::agrupar( $filas, 'tipo' ) as $t => $n ) {
			$datos[] = array(
				'tipo'    => $t,
				'eventos' => $n,
			);
		}
		return self::ok(
			self::plegar_otros( $datos, 'tipo', 'eventos' ),
			array(
				'x'          => 'tipo',
				'y'          => 'eventos',
				'etiqueta'   => 'tipo',
				'unidad'     => 'eventos',
				'decimales'  => 0,
				'etiqueta_y' => 'Eventos',
			),
			self::analisis( $filas ),
			$r
		);
	}

	/** @return array */
	public static function mapa() {
		$r = SAN_Fuentes::obtener( self::F )->eventos();
		if ( ! $r['ok'] ) {
			return SAN_Catalogo::fallo( $r );
		}
		$filas = array();
		foreach ( (array) $r['datos'] as $f ) {
			if ( null === $f['lat'] ) {
				continue;
			}
			$f['color']        = self::COLORES[ $f['alerta'] ] ?? '#6b6b6b';
			$f['alerta_texto'] = self::ALERTAS[ $f['alerta'] ] ?? $f['alerta'];
			$f['peso']         = array_search( $f['alerta'], array_keys( self::COLORES ), true ) + 1;
			$filas[]           = $f;
		}
		$leyenda = array();
		foreach ( self::COLORES as $k => $c ) {
			$leyenda[] = array(
				'color' => $c,
				'texto' => 'Alerta ' . mb_strtolower( self::ALERTAS[ $k ] ),
			);
		}
		return self::ok(
			$filas,
			array(
				'lat'             => 'lat',
				'lon'             => 'lon',
				'tamano'          => 'peso',
				'color_campo'     => 'color',
				'etiqueta'        => 'nombre',
				'ajustar_a_datos' => true,
				'leyenda'         => $leyenda,
				'tooltip'         => array( array( 'Tipo', 'tipo' ), array( 'Alerta', 'alerta_texto' ), array( 'Desde', 'desde' ), array( 'País', 'pais' ) ),
			),
			self::analisis( (array) $r['datos'] ),
			$r
		);
	}

	/** @return array */
	public static function mensual() {
		$r = SAN_Fuentes::obtener( self::F )->eventos();
		if ( ! $r['ok'] ) {
			return SAN_Catalogo::fallo( $r );
		}
		$filas = (array) $r['datos'];
		$tipos = array_slice( array_keys( SAN_Analisis::agrupar( $filas, 'tipo' ) ), 0, 5 );
		$cuen  = array();
		foreach ( $filas as $f ) {
			$mes  = substr( $f['desde'], 0, 7 );
			$tipo = in_array( $f['tipo'], $tipos, true ) ? $f['tipo'] : 'Otros';
			$cuen[ $mes ][ $tipo ] = ( $cuen[ $mes ][ $tipo ] ?? 0 ) + 1;
		}
		ksort( $cuen );
		$datos = array();
		foreach ( $cuen as $mes => $por ) {
			foreach ( $por as $t => $n ) {
				$datos[] = array(
					'fecha'   => $mes . '-01',
					'serie'   => $t,
					'eventos' => $n,
				);
			}
		}
		return self::ok(
			$datos,
			array(
				'x'          => 'fecha',
				'y'          => 'eventos',
				'grupo'      => 'serie',
				'tiempo'     => true,
				'unidad'     => 'eventos',
				'decimales'  => 0,
				'etiqueta_x' => 'Mes',
				'periodo'    => 'mes',
				'etiqueta_y' => 'Eventos',
			),
			self::analisis( $filas ),
			$r
		);
	}
}
