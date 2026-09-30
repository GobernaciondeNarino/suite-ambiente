<?php
/**
 * Visualizaciones de radiación solar y agroclima (NASA POWER).
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Viz_Radiacion extends SAN_Viz {

	const F = 'nasa_power';

	/**
	 * Definiciones.
	 *
	 * @return array
	 */
	public static function definiciones() {
		$c = __CLASS__;
		return array(
			'power_radiacion'    => array(
				'titulo'      => 'Radiación solar diaria',
				'fuente'      => self::F,
				'subgrupo'    => 'Energía solar',
				'descripcion' => 'Irradiación solar global diaria sobre superficie horizontal (kWh/m²/día) en el municipio seleccionado durante los últimos meses.',
				'lectura'     => 'Es la energía solar disponible por metro cuadrado. Valores de 4 a 5 kWh/m²/día son adecuados para sistemas solares fotovoltaicos.',
				'tipo'        => 'area',
				'tipos'       => array( 'area', 'line', 'bar' ),
				'params'      => array( 'municipio' ),
				'procesador'  => array( $c, 'radiacion' ),
			),
			'power_temperatura'  => array(
				'titulo'      => 'Temperatura diaria observada por satélite',
				'fuente'      => self::F,
				'subgrupo'    => 'Agroclima',
				'descripcion' => 'Temperaturas máxima, media y mínima diarias (NASA POWER) en el municipio seleccionado.',
				'lectura'     => 'Serie de meses recientes para evaluar la tendencia térmica; complementa el pronóstico.',
				'tipo'        => 'line',
				'tipos'       => array( 'line', 'area' ),
				'params'      => array( 'municipio' ),
				'procesador'  => array( $c, 'temperatura' ),
			),
			'power_lluvia_mensual' => array(
				'titulo'      => 'Lluvia mensual estimada',
				'fuente'      => self::F,
				'subgrupo'    => 'Agroclima',
				'descripcion' => 'Precipitación acumulada por mes (mm) estimada por satélite y reanálisis en el municipio seleccionado.',
				'lectura'     => 'Muestra la alternancia de meses lluviosos y secos del régimen bimodal andino o el régimen monomodal del Pacífico.',
				'tipo'        => 'bar',
				'tipos'       => array( 'bar', 'line', 'area' ),
				'params'      => array( 'municipio' ),
				'procesador'  => array( $c, 'lluvia_mensual' ),
			),
			'power_box_mensual'  => array(
				'titulo'      => 'Variabilidad de la temperatura por mes',
				'fuente'      => self::F,
				'subgrupo'    => 'Agroclima',
				'descripcion' => 'Distribución de la temperatura media diaria de cada mes en el municipio seleccionado.',
				'lectura'     => 'Cajas más largas indican meses con más variabilidad día a día.',
				'tipo'        => 'box',
				'tipos'       => array( 'box' ),
				'params'      => array( 'municipio' ),
				'procesador'  => array( $c, 'box_mensual' ),
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
			'power_diario' => array(
				'titulo'      => 'Agroclima diario (NASA POWER)',
				'descripcion' => 'Serie diaria de temperatura, precipitación, humedad, viento y radiación solar para un municipio.',
				'fuente'      => self::F,
				'formatos'    => array( 'json', 'csv' ),
				'params'      => array( 'municipio' ),
				'campos'      => array(
					'fecha'             => 'Fecha',
					'T2M'               => 'Temperatura media a 2 m (°C)',
					'T2M_MAX'           => 'Temperatura máxima (°C)',
					'T2M_MIN'           => 'Temperatura mínima (°C)',
					'PRECTOTCORR'       => 'Precipitación corregida (mm/día)',
					'ALLSKY_SFC_SW_DWN' => 'Irradiación global (MJ/m²/día)',
					'RH2M'              => 'Humedad relativa (%)',
					'WS2M'              => 'Viento a 2 m (m/s)',
				),
				'generador'   => array( __CLASS__, 'gen' ),
			),
		);
	}

	/**
	 * @param array $p Parámetros.
	 * @return array
	 */
	public static function gen( array $p ) {
		$r = SAN_Fuentes::obtener( self::F )->serie( $p['municipio'] );
		return array(
			'ok'          => $r['ok'],
			'filas'       => (array) $r['datos'],
			'actualizado' => $r['actualizado'],
			'vencido'     => $r['vencido'],
			'error'       => $r['error'],
		);
	}

	/**
	 * Radiación solar.
	 *
	 * @param array $p Parámetros.
	 * @return array
	 */
	public static function radiacion( array $p ) {
		$r = SAN_Fuentes::obtener( self::F )->serie( $p['municipio'] );
		if ( ! $r['ok'] ) {
			return SAN_Catalogo::fallo( $r );
		}
		$datos = array();
		foreach ( (array) $r['datos'] as $f ) {
			if ( null !== $f['ALLSKY_SFC_SW_DWN'] ) {
				$datos[] = array(
					'fecha' => $f['fecha'],
					'kwh'   => round( $f['ALLSKY_SFC_SW_DWN'] / 3.6, 2 ),
				);
			}
		}
		$vals = array_column( $datos, 'kwh' );
		$est  = SAN_Analisis::estadisticos( $vals );
		$ult  = array_slice( $vals, -30 );
		$eu   = SAN_Analisis::estadisticos( $ult );
		$max  = SAN_Analisis::extremo( $datos, 'kwh', true );
		// Energía de 1 kWp fotovoltaico con rendimiento global del 75 %.
		$pv  = $est['media'] * 0.75 * 30;
		$nom = self::muni( $p['municipio'] );
		return self::ok(
			$datos,
			array(
				'x'          => 'fecha',
				'y'          => 'kwh',
				'tiempo'     => true,
				'unidad'     => 'kWh/m²/día',
				'decimales'  => 2,
				'etiqueta_x' => 'Fecha',
				'etiqueta_y' => 'Irradiación (kWh/m²/día)',
			),
			array(
				'nivel'                => 'info',
				'titular'              => sprintf( '%s recibe en promedio %s de energía solar; los últimos 30 días promedian %s.', $nom, SAN_Analisis::con_unidad( $est['media'], 'kWh/m²/día', 2 ), SAN_Analisis::con_unidad( $eu['media'], 'kWh/m²/día', 2 ) ),
				'cualitativo'          => array(
					$est['media'] >= 4 ? 'Recurso solar bueno para sistemas fotovoltaicos en instituciones educativas, centros de salud y zonas no interconectadas.' : 'Recurso solar moderado, limitado por la nubosidad frecuente; los sistemas fotovoltaicos requieren mayor dimensionamiento.',
					sprintf( 'Un sistema de 1 kWp produciría del orden de %s al mes con estas condiciones (rendimiento global supuesto del 75 %%).', SAN_Analisis::con_unidad( $pv, 'kWh', 0 ) ),
				),
				'recomendaciones'      => array(),
				'cuantitativo'         => array(
					self::cifra( 'Media del periodo', SAN_Analisis::con_unidad( $est['media'], 'kWh/m²/d', 2 ) ),
					self::cifra( 'Últimos 30 días', SAN_Analisis::con_unidad( $eu['media'], 'kWh/m²/d', 2 ) ),
					self::cifra( 'Día más soleado', SAN_Analisis::con_unidad( $est['max'], 'kWh/m²', 2 ), $max ? SAN_Analisis::fecha( $max['fecha'] ) : '' ),
					self::cifra( 'Producción 1 kWp', SAN_Analisis::con_unidad( $pv, 'kWh/mes', 0 ), 'estimada' ),
				),
				'resumen_cuantitativo' => sprintf( '%d días con dato; coeficiente de variación %s.', $est['n'], SAN_Analisis::pct( null === $est['cv'] ? null : $est['cv'] * 100 ) ),
				'metodo'               => 'ALLSKY_SFC_SW_DWN (MJ/m²/día) ÷ 3,6. Producción = irradiación media × 0,75 × 30 días por kWp instalado.',
			),
			$r,
			'NASA POWER: rezago de ~5 días en radiación.'
		);
	}

	/**
	 * Temperatura diaria.
	 *
	 * @param array $p Parámetros.
	 * @return array
	 */
	public static function temperatura( array $p ) {
		$r = SAN_Fuentes::obtener( self::F )->serie( $p['municipio'] );
		if ( ! $r['ok'] ) {
			return SAN_Catalogo::fallo( $r );
		}
		$datos = array();
		foreach ( (array) $r['datos'] as $f ) {
			foreach ( array( 'Máxima' => 'T2M_MAX', 'Media' => 'T2M', 'Mínima' => 'T2M_MIN' ) as $s => $k ) {
				if ( null !== $f[ $k ] ) {
					$datos[] = array(
						'fecha' => $f['fecha'],
						'serie' => $s,
						'valor' => $f[ $k ],
					);
				}
			}
		}
		$media = array_column( (array) $r['datos'], 'T2M' );
		$est   = SAN_Analisis::estadisticos( $media );
		$tend  = SAN_Analisis::tendencia( $media );
		$ult   = SAN_Analisis::estadisticos( array_slice( $media, -30 ) );
		return self::ok(
			$datos,
			array(
				'x'          => 'fecha',
				'y'          => 'valor',
				'grupo'      => 'serie',
				'tiempo'     => true,
				'unidad'     => '°C',
				'decimales'  => 1,
				'etiqueta_x' => 'Fecha',
				'etiqueta_y' => 'Temperatura (°C)',
			),
			array(
				'nivel'                => 'info',
				'titular'              => sprintf( 'Temperatura media del periodo en %s: %s; últimos 30 días: %s.', self::muni( $p['municipio'] ), SAN_Analisis::con_unidad( $est['media'], '°C' ), SAN_Analisis::con_unidad( $ult['media'], '°C' ) ),
				'cualitativo'          => array( sprintf( 'La tendencia del periodo es %s. Un cambio de pocas décimas en meses es normal; tendencias climáticas requieren décadas de datos.', SAN_Analisis::frase_tendencia( $tend['pendiente'] * 30, $est['desviacion'] ?: 1, '°C', 'mes' ) ) ),
				'recomendaciones'      => array(),
				'cuantitativo'         => array(
					self::cifra( 'Media', SAN_Analisis::con_unidad( $est['media'], '°C' ) ),
					self::cifra( 'Mínima media diaria', SAN_Analisis::con_unidad( $est['min'], '°C' ) ),
					self::cifra( 'Máxima media diaria', SAN_Analisis::con_unidad( $est['max'], '°C' ) ),
					self::cifra( 'Tendencia', ( $tend['pendiente'] >= 0 ? '+' : '−' ) . SAN_Analisis::con_unidad( abs( $tend['pendiente'] * 30 ), '°C/mes', 2 ) ),
				),
				'resumen_cuantitativo' => sprintf( '%d días; desviación estándar %s.', $est['n'], SAN_Analisis::con_unidad( $est['desviacion'], '°C' ) ),
				'metodo'               => 'T2M, T2M_MAX y T2M_MIN de NASA POWER (MERRA-2) en la cabecera municipal.',
			),
			$r,
			'NASA POWER: rezago de ~3 días.'
		);
	}

	/**
	 * Lluvia mensual.
	 *
	 * @param array $p Parámetros.
	 * @return array
	 */
	public static function lluvia_mensual( array $p ) {
		$r = SAN_Fuentes::obtener( self::F )->serie( $p['municipio'] );
		if ( ! $r['ok'] ) {
			return SAN_Catalogo::fallo( $r );
		}
		$mes = array();
		$dias = array();
		foreach ( (array) $r['datos'] as $f ) {
			if ( null === $f['PRECTOTCORR'] ) {
				continue;
			}
			$k          = substr( $f['fecha'], 0, 7 );
			$mes[ $k ]  = ( $mes[ $k ] ?? 0 ) + $f['PRECTOTCORR'];
			$dias[ $k ] = ( $dias[ $k ] ?? 0 ) + 1;
		}
		$datos = array();
		foreach ( $mes as $k => $v ) {
			$datos[] = array(
				'fecha'    => $k . '-01',
				'lluvia'   => round( $v, 1 ),
				'dias'     => $dias[ $k ],
				'completo' => $dias[ $k ] >= 28 ? 'sí' : 'parcial',
			);
		}
		$comp = array_filter(
			$datos,
			function ( $d ) {
				return 'sí' === $d['completo'];
			}
		);
		$est  = SAN_Analisis::estadisticos( array_column( $comp, 'lluvia' ) );
		$max  = SAN_Analisis::extremo( array_values( $comp ), 'lluvia', true );
		$min  = SAN_Analisis::extremo( array_values( $comp ), 'lluvia', false );
		return self::ok(
			$datos,
			array(
				'x'          => 'fecha',
				'y'          => 'lluvia',
				'tiempo'     => true,
				'unidad'     => 'mm',
				'decimales'  => 0,
				'etiqueta_x' => 'Mes',
				'periodo'    => 'mes',
				'etiqueta_y' => 'Lluvia mensual (mm)',
				'tooltip'    => array( array( 'Lluvia', 'lluvia', 'mm', 0 ), array( 'Días con dato', 'dias', '', 0 ), array( 'Mes completo', 'completo' ) ),
			),
			array(
				'nivel'                => 'info',
				'titular'              => $max ? sprintf( 'El mes más lluvioso del periodo fue %s (%s) y el más seco %s (%s).', substr( $max['fecha'], 0, 7 ), SAN_Analisis::con_unidad( $max['lluvia'], 'mm', 0 ), substr( $min['fecha'], 0, 7 ), SAN_Analisis::con_unidad( $min['lluvia'], 'mm', 0 ) ) : 'Datos insuficientes para comparar meses completos.',
				'cualitativo'          => array( 'La estimación satelital suaviza los extremos locales; para balances hídricos finos use las estaciones del IDEAM.' ),
				'recomendaciones'      => array(),
				'cuantitativo'         => array(
					self::cifra( 'Media mensual', SAN_Analisis::con_unidad( $est['media'], 'mm', 0 ) ),
					self::cifra( 'Meses completos', (string) $est['n'] ),
					self::cifra( 'Total del periodo', SAN_Analisis::con_unidad( array_sum( array_column( $datos, 'lluvia' ) ), 'mm', 0 ) ),
				),
				'resumen_cuantitativo' => sprintf( 'Desviación entre meses: %s.', SAN_Analisis::con_unidad( $est['desviacion'], 'mm', 0 ) ),
				'metodo'               => 'Suma mensual de PRECTOTCORR (NASA POWER, corregida con GPM/IMERG).',
			),
			$r
		);
	}

	/**
	 * Cajas por mes.
	 *
	 * @param array $p Parámetros.
	 * @return array
	 */
	public static function box_mensual( array $p ) {
		$r = SAN_Fuentes::obtener( self::F )->serie( $p['municipio'] );
		if ( ! $r['ok'] ) {
			return SAN_Catalogo::fallo( $r );
		}
		$meses = array( 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic' );
		$datos = array();
		$por   = array();
		foreach ( (array) $r['datos'] as $f ) {
			if ( null === $f['T2M'] ) {
				continue;
			}
			$m       = $meses[ (int) substr( $f['fecha'], 5, 2 ) - 1 ] . ' ' . substr( $f['fecha'], 0, 4 );
			$datos[] = array(
				'id'    => $f['fecha'],
				'mes'   => $m,
				'valor' => $f['T2M'],
			);
			$por[ $m ][] = $f['T2M'];
		}
		$rangos = array();
		foreach ( $por as $m => $v ) {
			$rangos[ $m ] = max( $v ) - min( $v );
		}
		arsort( $rangos );
		$var = (string) array_key_first( $rangos );
		return self::ok(
			$datos,
			array(
				'x'          => 'mes',
				'y'          => 'valor',
				'id'         => 'id',
				'unidad'     => '°C',
				'decimales'  => 1,
				'etiqueta_x' => 'Mes',
				'etiqueta_y' => 'Temperatura media diaria (°C)',
			),
			array(
				'nivel'                => 'info',
				'titular'              => sprintf( 'El mes más variable fue %s, con %s entre su día más frío y el más cálido.', $var, SAN_Analisis::con_unidad( $rangos[ $var ] ?? 0, '°C' ) ),
				'cualitativo'          => array( 'En la zona ecuatorial la variación entre meses es pequeña; la diferencia entre el día y la noche suele ser mayor que entre estaciones.' ),
				'recomendaciones'      => array(),
				'cuantitativo'         => array(
					self::cifra( 'Meses', (string) count( $por ) ),
					self::cifra( 'Días', (string) count( $datos ) ),
					self::cifra( 'Mes más variable', $var ),
				),
				'resumen_cuantitativo' => 'Rango intramensual por mes: ' . implode( '; ', array_map( function ( $k, $v ) { return $k . ' ' . SAN_Analisis::con_unidad( $v, '°C' ); }, array_keys( $rangos ), $rangos ) ) . '.', // phpcs:ignore
				'metodo'               => 'T2M diaria agrupada por mes; cajas de D3plus BoxWhisker.',
			),
			$r
		);
	}
}
