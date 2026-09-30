<?php
/**
 * Visualizaciones de calidad del aire (Open-Meteo / CAMS).
 *
 * El ICA se calcula con los puntos de corte de la Resolución 2254 de 2017
 * sobre promedios móviles de 24 h de PM2.5 y PM10. Las guías de la OMS
 * (2021) se usan como referencia sanitaria más exigente.
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Viz_Aire extends SAN_Viz {

	const F = 'openmeteo_aire';

	/** Guías de calidad del aire OMS 2021 (µg/m³) y periodo. */
	const OMS = array(
		'pm2_5'            => array( 15, 'PM2.5', '24 h' ),
		'pm10'             => array( 45, 'PM10', '24 h' ),
		'ozone'            => array( 100, 'Ozono (O₃)', '8 h' ),
		'nitrogen_dioxide' => array( 25, 'Dióxido de nitrógeno (NO₂)', '24 h' ),
		'sulphur_dioxide'  => array( 40, 'Dióxido de azufre (SO₂)', '24 h' ),
		'carbon_monoxide'  => array( 4000, 'Monóxido de carbono (CO)', '24 h' ),
	);

	/**
	 * Definiciones.
	 *
	 * @return array
	 */
	public static function definiciones() {
		$c = __CLASS__;
		return array(
			'aire_ica_municipios'  => array(
				'titulo'        => 'Índice de Calidad del Aire (ICA) por municipio',
				'fuente'        => self::F,
				'subgrupo'      => 'Estado actual',
				'descripcion'   => 'ICA de las últimas 24 horas para los 64 municipios, calculado con los puntos de corte de la Resolución 2254 de 2017 a partir de PM2.5 y PM10 estimados por el modelo CAMS.',
				'lectura'       => 'Los colores son los oficiales del ICA: verde buena, amarillo aceptable, naranja dañina para grupos sensibles, rojo dañina, púrpura muy dañina y marrón peligrosa.',
				'tipo'          => 'mapa',
				'tipos'         => array( 'mapa', 'barh' ),
				'variable_mapa' => 'ica',
				'procesador'    => array( $c, 'ica_municipios' ),
			),
			'aire_ica_medidor'     => array(
				'titulo'      => 'ICA del municipio',
				'fuente'      => self::F,
				'subgrupo'    => 'Por municipio',
				'descripcion' => 'Medidor del Índice de Calidad del Aire (24 h) del municipio seleccionado, con sus bandas oficiales.',
				'lectura'     => 'La aguja marca el ICA actual; cada franja de color es una categoría de la Resolución 2254 de 2017.',
				'tipo'        => 'medidor',
				'tipos'       => array( 'medidor' ),
				'params'      => array( 'municipio' ),
				'procesador'  => array( $c, 'ica_medidor' ),
			),
			'aire_pronostico_pm'   => array(
				'titulo'      => 'Pronóstico de material particulado (5 días)',
				'fuente'      => self::F,
				'subgrupo'    => 'Por municipio',
				'descripcion' => 'Concentraciones horarias de PM2.5 y PM10 estimadas desde ayer y pronosticadas para los próximos 5 días.',
				'lectura'     => 'Compare las curvas con las guías de la OMS: 15 µg/m³ (PM2.5) y 45 µg/m³ (PM10) en promedio diario.',
				'tipo'        => 'line',
				'tipos'       => array( 'line', 'area' ),
				'params'      => array( 'municipio' ),
				'procesador'  => array( $c, 'pronostico_pm' ),
			),
			'aire_contaminantes'   => array(
				'titulo'      => 'Contaminantes frente a las guías de la OMS',
				'fuente'      => self::F,
				'subgrupo'    => 'Por municipio',
				'descripcion' => 'Concentración actual de cada contaminante expresada como porcentaje de su valor guía de la OMS (2021).',
				'lectura'     => 'El 100 % es el valor guía: por encima, el contaminante supera lo recomendado para proteger la salud.',
				'tipo'        => 'radar',
				'tipos'       => array( 'radar', 'bar', 'barh' ),
				'params'      => array( 'municipio' ),
				'procesador'  => array( $c, 'contaminantes' ),
			),
		);
	}

	/**
	 * Recursos de datos abiertos.
	 *
	 * @return array
	 */
	public static function recursos() {
		return array(
			'aire_ica_municipios' => array(
				'titulo'      => 'Calidad del aire (ICA) por municipio',
				'descripcion' => 'Una fila por municipio con PM2.5 y PM10 promedio de 24 h, ICA por contaminante, ICA final y categoría (Res. 2254 de 2017), más los contaminantes actuales.',
				'fuente'      => self::F,
				'formatos'    => array( 'json', 'csv', 'geojson' ),
				'campos'      => array(
					'divipola'   => 'Código DIVIPOLA',
					'municipio'  => 'Nombre',
					'subregion'  => 'Subregión',
					'lat'        => 'Latitud',
					'lon'        => 'Longitud',
					'pm25_24h'   => 'PM2.5 promedio 24 h (µg/m³)',
					'pm10_24h'   => 'PM10 promedio 24 h (µg/m³)',
					'ica_pm25'   => 'ICA por PM2.5',
					'ica_pm10'   => 'ICA por PM10',
					'ica'        => 'ICA final (máximo)',
					'categoria'  => 'Categoría del ICA',
					'ozono'      => 'Ozono actual (µg/m³)',
					'no2'        => 'NO₂ actual (µg/m³)',
					'so2'        => 'SO₂ actual (µg/m³)',
					'co'         => 'CO actual (µg/m³)',
					'uv'         => 'Índice UV actual',
				),
				'generador'   => array( __CLASS__, 'gen_ica' ),
			),
		);
	}

	/**
	 * Promedio de las últimas 24 horas hasta la hora actual.
	 *
	 * @param array  $obj   Objeto Open-Meteo de un punto.
	 * @param string $campo Variable horaria.
	 * @return float|null
	 */
	private static function media_24h( array $obj, $campo ) {
		$h     = $obj['hourly'] ?? array();
		$ahora = $obj['current']['time'] ?? '';
		$vals  = array();
		foreach ( (array) ( $h['time'] ?? array() ) as $i => $t ) {
			if ( $ahora && $t > $ahora ) {
				break;
			}
			$vals[] = $h[ $campo ][ $i ] ?? null;
		}
		$vals = array_slice( array_filter( $vals, 'is_numeric' ), -24 );
		return $vals ? array_sum( $vals ) / count( $vals ) : null;
	}

	/**
	 * Filas de ICA por municipio.
	 *
	 * @return array { resp, filas }
	 */
	private static function filas_ica() {
		$resp = SAN_Fuentes::obtener( self::F )->municipios();
		if ( ! $resp['ok'] ) {
			return array(
				'resp'  => $resp,
				'filas' => array(),
			);
		}
		$filas = array();
		foreach ( SAN_Municipios::todos() as $m ) {
			$o = $resp['datos'][ $m['divipola'] ] ?? null;
			if ( ! $o ) {
				continue;
			}
			$pm25  = self::media_24h( $o, 'pm2_5' );
			$pm10  = self::media_24h( $o, 'pm10' );
			$i25   = SAN_Umbrales::ica( 'pm2_5', $pm25 );
			$i10   = SAN_Umbrales::ica( 'pm10', $pm10 );
			$ica   = max( (float) $i25, (float) $i10 );
			$cat   = SAN_Umbrales::categoria_ica( $ica );
			$orden = array_search( $cat, SAN_Umbrales::ICA_CATEGORIAS, true );
			$cur   = $o['current'] ?? array();
			$filas[] = array(
				'divipola'   => $m['divipola'],
				'municipio'  => self::muni( $m['divipola'] ),
				'subregion'  => $m['subregion'],
				'lat'        => $m['lat'],
				'lon'        => $m['lon'],
				'pm25_24h'   => null === $pm25 ? null : round( $pm25, 1 ),
				'pm10_24h'   => null === $pm10 ? null : round( $pm10, 1 ),
				'ica_pm25'   => null === $i25 ? null : round( $i25 ),
				'ica_pm10'   => null === $i10 ? null : round( $i10 ),
				'ica'        => round( $ica ),
				'categoria'  => $cat['nombre'],
				'color'      => $cat['color'],
				'ica_orden'  => (int) $orden,
				'ozono'      => $cur['ozone'] ?? null,
				'no2'        => $cur['nitrogen_dioxide'] ?? null,
				'so2'        => $cur['sulphur_dioxide'] ?? null,
				'co'         => $cur['carbon_monoxide'] ?? null,
				'uv'         => $cur['uv_index'] ?? null,
			);
		}
		return array(
			'resp'  => $resp,
			'filas' => $filas,
		);
	}

	/**
	 * Generador de datos abiertos.
	 *
	 * @return array
	 */
	public static function gen_ica() {
		$a = self::filas_ica();
		return array(
			'ok'          => $a['resp']['ok'],
			'filas'       => array_map(
				function ( $f ) {
					unset( $f['color'], $f['ica_orden'] );
					return $f;
				},
				$a['filas']
			),
			'actualizado' => $a['resp']['actualizado'],
			'vencido'     => $a['resp']['vencido'],
			'error'       => $a['resp']['error'],
		);
	}

	/**
	 * ICA por municipio.
	 *
	 * @return array
	 */
	public static function ica_municipios() {
		$a = self::filas_ica();
		if ( ! $a['resp']['ok'] ) {
			return SAN_Catalogo::fallo( $a['resp'] );
		}
		$filas  = $a['filas'];
		$conteo = array_fill_keys( array_column( SAN_Umbrales::ICA_CATEGORIAS, 'nombre' ), 0 );
		foreach ( $filas as $f ) {
			++$conteo[ $f['categoria'] ];
		}
		$peor  = SAN_Analisis::extremo( $filas, 'ica', true );
		$cat   = SAN_Umbrales::categoria_ica( $peor['ica'] );
		$est   = SAN_Analisis::estadisticos( array_column( $filas, 'pm25_24h' ) );
		$sobre = count(
			array_filter(
				$filas,
				function ( $f ) {
					return (float) $f['pm25_24h'] > 15;
				}
			)
		);
		$dist  = array();
		foreach ( $conteo as $k => $v ) {
			if ( $v ) {
				$dist[] = $k . ': ' . $v;
			}
		}

		return self::ok(
			$filas,
			array(
				'clave'           => 'divipola',
				'x'               => 'municipio',
				'y'               => 'ica',
				'etiqueta'        => 'municipio',
				'unidad'          => '',
				'decimales'       => 0,
				'color_campo'     => 'color',
				'categoria_campo' => 'categoria',
				'orden_campo'     => 'ica_orden',
				'etiqueta_y'      => 'ICA',
				'tooltip'         => array( array( 'ICA', 'ica', '', 0 ), array( 'Categoría', 'categoria' ), array( 'PM2.5 (24 h)', 'pm25_24h', 'µg/m³', 1 ), array( 'PM10 (24 h)', 'pm10_24h', 'µg/m³', 1 ) ),
			),
			array(
				'nivel'                => $cat['nivel'],
				'etiqueta'             => 'ICA ' . $cat['nombre'],
				'titular'              => sprintf( 'El peor ICA del departamento está en %s (%d, %s). %d de 64 municipios tienen calidad del aire buena.', $peor['municipio'], $peor['ica'], mb_strtolower( $cat['nombre'] ), $conteo['Buena'] ),
				'cualitativo'          => array(
					$cat['mensaje'],
					sprintf( 'En %d municipio%s el PM2.5 de 24 h supera la guía de la OMS (15 µg/m³), aunque cumpla la norma nacional.', $sobre, 1 === $sobre ? '' : 's' ),
					'Son estimaciones del modelo CAMS (~40 km): captan quemas, polvo y transporte regional, pero no la contaminación local de una calle o una fábrica. Para decisiones regulatorias use las estaciones de vigilancia (SISAIRE).',
				),
				'recomendaciones'      => 'bueno' === $cat['nivel'] ? array() : array( 'Grupos sensibles: reducir la actividad física intensa al aire libre.', 'Evitar quemas agrícolas y de residuos.' ),
				'cuantitativo'         => array(
					self::cifra( 'ICA máximo', (string) $peor['ica'], $peor['municipio'] ),
					self::cifra( 'PM2.5 medio', SAN_Analisis::con_unidad( $est['media'], 'µg/m³' ), '24 h' ),
					self::cifra( 'PM2.5 máximo', SAN_Analisis::con_unidad( $est['max'], 'µg/m³' ) ),
					self::cifra( 'Sobre guía OMS', (string) $sobre, 'municipios PM2.5 > 15' ),
				),
				'resumen_cuantitativo' => 'Municipios por categoría — ' . implode( '; ', $dist ) . '.',
				'metodo'               => 'Promedio móvil de las últimas 24 h de PM2.5 y PM10 horarios; ICA por interpolación lineal con los puntos de corte de la Res. 2254/2017; el ICA final es el máximo de ambos.',
			),
			$a['resp'],
			'Estimación de modelo (CAMS), no medición de estación.'
		);
	}

	/**
	 * Medidor del ICA de un municipio.
	 *
	 * @param array $p Parámetros.
	 * @return array
	 */
	public static function ica_medidor( array $p ) {
		$a = self::filas_ica();
		if ( ! $a['resp']['ok'] ) {
			return SAN_Catalogo::fallo( $a['resp'] );
		}
		$fila = null;
		foreach ( $a['filas'] as $f ) {
			if ( $f['divipola'] === $p['municipio'] ) {
				$fila = $f;
			}
		}
		if ( ! $fila ) {
			return array(
				'ok'    => false,
				'error' => 'Sin datos para el municipio.',
			);
		}
		$cat    = SAN_Umbrales::categoria_ica( $fila['ica'] );
		$bandas = array();
		foreach ( SAN_Umbrales::ICA_CATEGORIAS as $c ) {
			$bandas[] = array(
				'min'    => $c['min'],
				'max'    => $c['max'],
				'color'  => $c['color'],
				'nombre' => $c['nombre'],
			);
		}
		$fila['etiqueta'] = $cat['nombre'];
		$dominante        = $fila['ica_pm25'] >= $fila['ica_pm10'] ? 'PM2.5' : 'PM10';

		return self::ok(
			array( $fila ),
			array(
				'valor'     => 'ica',
				'etiqueta'  => 'etiqueta',
				'max'       => 300,
				'bandas'    => $bandas,
				'decimales' => 0,
			),
			array(
				'nivel'                => $cat['nivel'],
				'etiqueta'             => 'ICA ' . $cat['nombre'],
				'titular'              => sprintf( 'Calidad del aire %s en %s (ICA %d), determinada por %s.', mb_strtolower( $cat['nombre'] ), $fila['municipio'], $fila['ica'], $dominante ),
				'cualitativo'          => array( $cat['mensaje'] ),
				'recomendaciones'      => 'bueno' === $cat['nivel'] ? array( 'Condiciones adecuadas para actividades al aire libre.' ) : array( 'Personas con asma o enfermedades cardiovasculares: tener a mano su medicación y limitar el esfuerzo al aire libre.' ),
				'cuantitativo'         => array(
					self::cifra( 'ICA', (string) $fila['ica'], $cat['nombre'] ),
					self::cifra( 'PM2.5 (24 h)', SAN_Analisis::con_unidad( $fila['pm25_24h'], 'µg/m³' ), 'ICA ' . $fila['ica_pm25'] ),
					self::cifra( 'PM10 (24 h)', SAN_Analisis::con_unidad( $fila['pm10_24h'], 'µg/m³' ), 'ICA ' . $fila['ica_pm10'] ),
					self::cifra( 'Ozono', SAN_Analisis::con_unidad( $fila['ozono'], 'µg/m³' ), 'actual' ),
				),
				'resumen_cuantitativo' => sprintf( 'El PM2.5 equivale al %s de la guía diaria de la OMS.', SAN_Analisis::num( (float) $fila['pm25_24h'] / 15 * 100, 0 ) . ' %' ),
				'metodo'               => 'Res. 2254/2017 (ICA) sobre promedios de 24 h del modelo CAMS.',
			),
			$a['resp'],
			'Estimación de modelo (CAMS).'
		);
	}

	/**
	 * Serie horaria de PM2.5 y PM10.
	 *
	 * @param array $p Parámetros.
	 * @return array
	 */
	public static function pronostico_pm( array $p ) {
		$r = SAN_Fuentes::obtener( self::F )->serie( $p['municipio'] );
		if ( ! $r['ok'] ) {
			return SAN_Catalogo::fallo( $r );
		}
		$horas = SAN_Fuente_Openmeteo::filas( $r['datos']['hourly'] ?? array() );
		$filas = array();
		foreach ( $horas as $h ) {
			foreach ( array( 'PM2.5' => 'pm2_5', 'PM10' => 'pm10' ) as $serie => $k ) {
				if ( null !== $h[ $k ] ) {
					$filas[] = array(
						'fecha' => $h['time'],
						'serie' => $serie,
						'valor' => $h[ $k ],
					);
				}
			}
		}
		$pm25   = array_column( $horas, 'pm2_5' );
		$e25    = SAN_Analisis::estadisticos( $pm25 );
		$e10    = SAN_Analisis::estadisticos( array_column( $horas, 'pm10' ) );
		$pico   = SAN_Analisis::extremo( $horas, 'pm2_5', true );
		$tend   = SAN_Analisis::tendencia( $pm25 );
		$sobre  = count(
			array_filter(
				$pm25,
				function ( $v ) {
					return (float) $v > 15;
				}
			)
		);
		$nom    = self::muni( $p['municipio'] );

		return self::ok(
			$filas,
			array(
				'x'          => 'fecha',
				'y'          => 'valor',
				'grupo'      => 'serie',
				'tiempo'     => true,
				'hora'       => true,
				'unidad'     => 'µg/m³',
				'decimales'  => 1,
				'etiqueta_x' => 'Fecha y hora',
				'etiqueta_y' => 'Concentración (µg/m³)',
			),
			array(
				'nivel'                => $e25['max'] > 37 ? 'alerta' : ( $e25['max'] > 15 ? 'moderado' : 'bueno' ),
				'titular'              => sprintf( 'En %s el PM2.5 alcanzaría su pico el %s (%s); la tendencia de los próximos días es %s.', $nom, SAN_Analisis::fecha( $pico['time'], true ), SAN_Analisis::con_unidad( $pico['pm2_5'], 'µg/m³' ), SAN_Analisis::frase_tendencia( $tend['pendiente'] * 24, $e25['desviacion'] ?: 1, 'µg/m³', 'día' ) ),
				'cualitativo'          => array(
					sprintf( '%d de %d horas superan 15 µg/m³ de PM2.5 (referencia de la guía diaria de la OMS).', $sobre, $e25['n'] ),
					'Los picos suelen coincidir con horas de poca mezcla vertical (madrugada y noche) y con quemas o polvo transportado.',
				),
				'recomendaciones'      => $e25['max'] > 37 ? array( 'Evitar actividades físicas intensas al aire libre en las horas pico.' ) : array(),
				'cuantitativo'         => array(
					self::cifra( 'PM2.5 medio', SAN_Analisis::con_unidad( $e25['media'], 'µg/m³' ) ),
					self::cifra( 'PM2.5 máximo', SAN_Analisis::con_unidad( $e25['max'], 'µg/m³' ) ),
					self::cifra( 'PM10 medio', SAN_Analisis::con_unidad( $e10['media'], 'µg/m³' ) ),
					self::cifra( 'Horas > 15 µg/m³', (string) $sobre ),
				),
				'resumen_cuantitativo' => sprintf( 'Relación PM2.5/PM10 media: %s (valores altos indican partículas finas de combustión; bajos, polvo grueso).', SAN_Analisis::num( $e10['media'] > 0 ? $e25['media'] / $e10['media'] : null, 2 ) ),
				'metodo'               => 'Serie horaria CAMS (ayer + 5 días). Tendencia lineal por hora expresada por día.',
			),
			$r,
			'Estimación de modelo (CAMS).'
		);
	}

	/**
	 * Contaminantes frente a la OMS.
	 *
	 * @param array $p Parámetros.
	 * @return array
	 */
	public static function contaminantes( array $p ) {
		$r = SAN_Fuentes::obtener( self::F )->serie( $p['municipio'] );
		if ( ! $r['ok'] ) {
			return SAN_Catalogo::fallo( $r );
		}
		$cur   = $r['datos']['current'] ?? array();
		$filas = array();
		$sobre = array();
		foreach ( self::OMS as $k => $g ) {
			if ( ! isset( $cur[ $k ] ) ) {
				continue;
			}
			$pct     = $cur[ $k ] / $g[0] * 100;
			$filas[] = array(
				'contaminante'  => $g[1],
				'porcentaje'    => round( $pct, 1 ),
				'concentracion' => $cur[ $k ],
				'guia'          => $g[0],
				'periodo'       => $g[2],
				'serie'         => 'Actual',
			);
			if ( $pct > 100 ) {
				$sobre[] = $g[1];
			}
		}
		usort(
			$filas,
			function ( $a, $b ) {
				return $b['porcentaje'] <=> $a['porcentaje'];
			}
		);
		$top = $filas[0] ?? null;

		return self::ok(
			$filas,
			array(
				'x'          => 'contaminante',
				'y'          => 'porcentaje',
				'grupo'      => 'serie',
				'etiqueta'   => 'contaminante',
				'unidad'     => '% de la guía',
				'decimales'  => 0,
				'etiqueta_y' => '% del valor guía OMS',
				'tooltip'    => array( array( '% de la guía', 'porcentaje', '%', 0 ), array( 'Concentración', 'concentracion', 'µg/m³', 1 ), array( 'Guía OMS', 'guia', 'µg/m³', 0 ), array( 'Periodo', 'periodo' ) ),
			),
			array(
				'nivel'                => $sobre ? 'moderado' : 'bueno',
				'titular'              => $top ? sprintf( 'El contaminante más cercano a su límite en %s es %s (%s de la guía OMS).', self::muni( $p['municipio'] ), $top['contaminante'], SAN_Analisis::num( $top['porcentaje'], 0 ) . ' %' ) : '',
				'cualitativo'          => array(
					$sobre ? 'Superan su guía: ' . implode( ', ', $sobre ) . '. La comparación es orientativa: el valor actual es horario y las guías son promedios de 8 o 24 h.' : 'Todos los contaminantes están por debajo de sus valores guía de la OMS.',
				),
				'recomendaciones'      => array(),
				'cuantitativo'         => array_map(
					function ( $f ) {
						return self::cifra( $f['contaminante'], SAN_Analisis::num( $f['porcentaje'], 0 ) . ' %', SAN_Analisis::con_unidad( $f['concentracion'], 'µg/m³' ) );
					},
					array_slice( $filas, 0, 6 )
				),
				'resumen_cuantitativo' => sprintf( '%d de %d contaminantes por encima del 100 %% de su guía.', count( $sobre ), count( $filas ) ),
				'metodo'               => 'Concentración actual / valor guía OMS 2021 × 100.',
			),
			$r,
			'Estimación de modelo (CAMS).'
		);
	}
}
