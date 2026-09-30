<?php
/**
 * Visualizaciones de clima y tiempo (Open-Meteo Forecast).
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Viz_Clima extends SAN_Viz {

	const F = 'openmeteo_clima';

	/**
	 * Definiciones.
	 *
	 * @return array
	 */
	public static function definiciones() {
		$c = __CLASS__;
		return array(
			'clima_mapa_temperatura'       => array(
				'titulo'        => 'Temperatura actual en los 64 municipios',
				'fuente'        => self::F,
				'subgrupo'      => 'Condiciones actuales',
				'descripcion'   => 'Temperatura del aire a 2 m en la cabecera de cada municipio, actualizada cada 15 minutos. El contorno grueso separa las subregiones.',
				'lectura'       => 'Tonos más oscuros indican más calor. El gradiente sigue la altitud: el litoral Pacífico y el piedemonte son cálidos; el altiplano de Túquerres–Ipiales y los páramos, fríos.',
				'tipo'          => 'mapa',
				'tipos'         => array( 'mapa', 'barh' ),
				'variable_mapa' => 'temperatura',
				'procesador'    => array( $c, 'mapa_temperatura' ),
			),
			'clima_mapa_lluvia'            => array(
				'titulo'        => 'Lluvia esperada hoy por municipio',
				'fuente'        => self::F,
				'subgrupo'      => 'Condiciones actuales',
				'descripcion'   => 'Precipitación acumulada pronosticada para el día de hoy (mm) en cada cabecera municipal, con su probabilidad de ocurrencia.',
				'lectura'       => 'Azul más intenso = más lluvia. Más de 20 mm en un día es lluvia fuerte y más de 50 mm, muy fuerte: aumentan el riesgo de crecientes súbitas y deslizamientos.',
				'tipo'          => 'mapa',
				'tipos'         => array( 'mapa', 'barh' ),
				'variable_mapa' => 'lluvia',
				'procesador'    => array( $c, 'mapa_lluvia' ),
			),
			'clima_mapa_uv'                => array(
				'titulo'        => 'Índice UV máximo de hoy',
				'fuente'        => self::F,
				'subgrupo'      => 'Condiciones actuales',
				'descripcion'   => 'Índice ultravioleta máximo pronosticado para hoy en cada cabecera (escala de la OMS).',
				'lectura'       => 'En la zona ecuatorial andina el índice UV suele ser muy alto o extremo al mediodía aun con nubosidad parcial.',
				'tipo'          => 'mapa',
				'tipos'         => array( 'mapa', 'barh' ),
				'variable_mapa' => 'uv',
				'procesador'    => array( $c, 'mapa_uv' ),
			),
			'clima_subregiones_lluvia'     => array(
				'titulo'      => 'Lluvia pronosticada a 7 días por subregión',
				'fuente'      => self::F,
				'subgrupo'    => 'Condiciones actuales',
				'descripcion' => 'Promedio, entre los municipios de cada subregión, de la lluvia acumulada pronosticada para los próximos 7 días.',
				'lectura'     => 'Compara qué subregiones recibirán más agua esta semana; útil para priorizar vigilancia de cuencas y vías.',
				'tipo'        => 'barh',
				'tipos'       => array( 'barh', 'bar', 'treemap', 'radar' ),
				'procesador'  => array( $c, 'subregiones_lluvia' ),
			),
			'clima_box_subregiones'        => array(
				'titulo'      => 'Distribución de temperaturas máximas por subregión',
				'fuente'      => self::F,
				'subgrupo'    => 'Patrones',
				'descripcion' => 'Diagrama de cajas con las temperaturas máximas diarias pronosticadas a 7 días para todos los municipios de cada subregión.',
				'lectura'     => 'La caja abarca el 50 % central de los valores y la línea interior es la mediana; los bigotes muestran el rango. Cajas altas y estrechas indican calor homogéneo.',
				'tipo'        => 'box',
				'tipos'       => array( 'box' ),
				'procesador'  => array( $c, 'box_subregiones' ),
			),
			'clima_transiciones'           => array(
				'titulo'      => 'Persistencia y cambio del estado del tiempo',
				'fuente'      => self::F,
				'subgrupo'    => 'Patrones',
				'descripcion' => 'Transiciones del estado del tiempo de un día al siguiente en los 64 municipios durante la semana pronosticada. Usa el nuevo diagrama de cuerdas (Chord) de D3plus v4.',
				'lectura'     => 'Cada arco es un estado (despejado, nublado, lluvia…); las cintas unen el estado de un día con el del día siguiente y su grosor es el número de casos. Las cintas que vuelven al mismo arco indican persistencia.',
				'tipo'        => 'chord',
				'tipos'       => array( 'chord', 'matrix' ),
				'procesador'  => array( $c, 'transiciones' ),
			),
			'clima_pronostico_temperatura' => array(
				'titulo'      => 'Pronóstico de temperatura',
				'fuente'      => self::F,
				'subgrupo'    => 'Pronóstico por municipio',
				'descripcion' => 'Temperaturas máxima y mínima diarias y sensación térmica máxima pronosticadas para el municipio seleccionado.',
				'lectura'     => 'La distancia entre las líneas es la amplitud térmica diaria. La sensación térmica combina temperatura, humedad y viento.',
				'tipo'        => 'line',
				'tipos'       => array( 'line', 'area', 'bar' ),
				'params'      => array( 'municipio' ),
				'procesador'  => array( $c, 'pronostico_temperatura' ),
			),
			'clima_pronostico_lluvia'      => array(
				'titulo'      => 'Pronóstico de lluvia',
				'fuente'      => self::F,
				'subgrupo'    => 'Pronóstico por municipio',
				'descripcion' => 'Lluvia diaria acumulada (mm) y probabilidad de precipitación pronosticadas para el municipio seleccionado.',
				'lectura'     => 'Barras más altas = más lluvia. Pase el cursor para ver la probabilidad de cada día.',
				'tipo'        => 'bar',
				'tipos'       => array( 'bar', 'line', 'area' ),
				'params'      => array( 'municipio' ),
				'procesador'  => array( $c, 'pronostico_lluvia' ),
			),
			'clima_calor_horario'          => array(
				'titulo'      => 'Temperatura hora a hora (7 días)',
				'fuente'      => self::F,
				'subgrupo'    => 'Pronóstico por municipio',
				'descripcion' => 'Mapa de calor con la temperatura pronosticada para cada hora de los próximos 7 días.',
				'lectura'     => 'Cada fila es un día y cada columna una hora. Permite ver a qué horas se concentra el calor y las madrugadas más frías (riesgo de heladas en zonas altas).',
				'tipo'        => 'calor',
				'tipos'       => array( 'calor' ),
				'params'      => array( 'municipio' ),
				'procesador'  => array( $c, 'calor_horario' ),
			),
			'clima_rosa_vientos'           => array(
				'titulo'      => 'Rosa de vientos (7 días)',
				'fuente'      => self::F,
				'subgrupo'    => 'Pronóstico por municipio',
				'descripcion' => 'Frecuencia de la dirección de donde sopla el viento y su intensidad, a partir del pronóstico horario de 7 días.',
				'lectura'     => 'Los pétalos más largos señalan las direcciones dominantes; los colores, la intensidad en km/h.',
				'tipo'        => 'rosa',
				'tipos'       => array( 'rosa' ),
				'params'      => array( 'municipio' ),
				'procesador'  => array( $c, 'rosa_vientos' ),
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
			'clima_actual_municipios'   => array(
				'titulo'      => 'Condiciones meteorológicas actuales por municipio',
				'descripcion' => 'Una fila por municipio con temperatura, sensación térmica, humedad, lluvia, nubosidad, viento, índice UV y estado del tiempo actuales.',
				'fuente'      => self::F,
				'formatos'    => array( 'json', 'csv', 'geojson' ),
				'campos'      => array(
					'divipola'       => 'Código DIVIPOLA',
					'municipio'      => 'Nombre',
					'subregion'      => 'Subregión',
					'lat'            => 'Latitud de la cabecera',
					'lon'            => 'Longitud de la cabecera',
					'hora'           => 'Hora local de la observación del modelo',
					'temperatura'    => 'Temperatura a 2 m (°C)',
					'sensacion'      => 'Sensación térmica (°C)',
					'humedad'        => 'Humedad relativa (%)',
					'lluvia'         => 'Precipitación del intervalo (mm)',
					'nubosidad'      => 'Cobertura de nubes (%)',
					'viento'         => 'Velocidad del viento a 10 m (km/h)',
					'viento_dir'     => 'Dirección del viento (grados)',
					'uv'             => 'Índice UV',
					'tiempo'         => 'Estado del tiempo (código WMO en texto)',
					'lluvia_hoy'     => 'Lluvia pronosticada para hoy (mm)',
					'prob_lluvia'    => 'Probabilidad máxima de lluvia hoy (%)',
					'uv_max'         => 'Índice UV máximo de hoy',
				),
				'generador'   => array( __CLASS__, 'gen_actual' ),
			),
			'clima_pronostico_diario'   => array(
				'titulo'      => 'Pronóstico diario por municipio',
				'descripcion' => 'Pronóstico diario (hasta 16 días) de un municipio: temperaturas, lluvia, probabilidad, UV, viento, horas de sol y evapotranspiración.',
				'fuente'      => self::F,
				'formatos'    => array( 'json', 'csv' ),
				'params'      => array( 'municipio' ),
				'campos'      => array(
					'fecha'            => 'Fecha',
					'tiempo'           => 'Estado del tiempo',
					'temp_max'         => 'Temperatura máxima (°C)',
					'temp_min'         => 'Temperatura mínima (°C)',
					'sensacion_max'    => 'Sensación térmica máxima (°C)',
					'lluvia'           => 'Lluvia (mm)',
					'prob_lluvia'      => 'Probabilidad de lluvia (%)',
					'uv_max'           => 'Índice UV máximo',
					'viento_max'       => 'Viento máximo (km/h)',
					'viento_dir'       => 'Dirección dominante (grados)',
					'horas_sol'        => 'Horas de sol',
					'evapotranspiracion' => 'ET0 FAO (mm)',
				),
				'generador'   => array( __CLASS__, 'gen_pronostico' ),
			),
		);
	}

	/* ------------------------------------------------------------------
	 * Datos base
	 * ---------------------------------------------------------------- */

	/**
	 * Filas actuales por municipio.
	 *
	 * @return array { resp, filas }
	 */
	private static function actuales() {
		$f    = SAN_Fuentes::obtener( self::F );
		$resp = $f->municipios();
		if ( ! $resp['ok'] ) {
			return array(
				'resp'  => $resp,
				'filas' => array(),
			);
		}
		$filas = array();
		foreach ( SAN_Municipios::todos() as $m ) {
			$o = $resp['datos'][ $m['divipola'] ] ?? null;
			if ( ! $o || empty( $o['current'] ) ) {
				continue;
			}
			$cur     = $o['current'];
			$day     = $o['daily'] ?? array();
			$wmo     = SAN_Umbrales::wmo( $cur['weather_code'] ?? -1 );
			$filas[] = array(
				'divipola'    => $m['divipola'],
				'municipio'   => self::muni( $m['divipola'] ),
				'subregion'   => $m['subregion'],
				'lat'         => $m['lat'],
				'lon'         => $m['lon'],
				'elevacion'   => $o['elevation'] ?? null,
				'hora'        => $cur['time'] ?? '',
				'temperatura' => $cur['temperature_2m'] ?? null,
				'sensacion'   => $cur['apparent_temperature'] ?? null,
				'humedad'     => $cur['relative_humidity_2m'] ?? null,
				'lluvia'      => $cur['precipitation'] ?? null,
				'nubosidad'   => $cur['cloud_cover'] ?? null,
				'viento'      => $cur['wind_speed_10m'] ?? null,
				'viento_dir'  => $cur['wind_direction_10m'] ?? null,
				'uv'          => $cur['uv_index'] ?? null,
				'tiempo'      => $wmo['nombre'],
				'tiempo_grupo' => $wmo['grupo'],
				'lluvia_hoy'  => $day['precipitation_sum'][0] ?? null,
				'prob_lluvia' => $day['precipitation_probability_max'][0] ?? null,
				'uv_max'      => $day['uv_index_max'][0] ?? null,
				'_diario'     => $day,
			);
		}
		return array(
			'resp'  => $resp,
			'filas' => $filas,
		);
	}

	/**
	 * Generador de datos abiertos: condiciones actuales.
	 *
	 * @return array
	 */
	public static function gen_actual() {
		$a = self::actuales();
		foreach ( $a['filas'] as &$f ) {
			unset( $f['_diario'], $f['tiempo_grupo'] );
		}
		return array(
			'ok'          => $a['resp']['ok'],
			'filas'       => $a['filas'],
			'actualizado' => $a['resp']['actualizado'],
			'vencido'     => $a['resp']['vencido'],
			'error'       => $a['resp']['error'],
		);
	}

	/**
	 * Generador de datos abiertos: pronóstico diario de un municipio.
	 *
	 * @param array $p Parámetros.
	 * @return array
	 */
	public static function gen_pronostico( array $p ) {
		$r = SAN_Fuentes::obtener( self::F )->pronostico( $p['municipio'] );
		if ( ! $r['ok'] ) {
			return array(
				'ok'    => false,
				'error' => $r['error'],
			);
		}
		$filas = array();
		foreach ( SAN_Fuente_Openmeteo::filas( $r['datos']['daily'] ?? array() ) as $d ) {
			$filas[] = array(
				'fecha'              => $d['time'],
				'tiempo'             => SAN_Umbrales::wmo( $d['weather_code'] ?? -1 )['nombre'],
				'temp_max'           => $d['temperature_2m_max'],
				'temp_min'           => $d['temperature_2m_min'],
				'sensacion_max'      => $d['apparent_temperature_max'],
				'lluvia'             => $d['precipitation_sum'],
				'prob_lluvia'        => $d['precipitation_probability_max'],
				'uv_max'             => $d['uv_index_max'],
				'viento_max'         => $d['wind_speed_10m_max'],
				'viento_dir'         => $d['wind_direction_10m_dominant'],
				'horas_sol'          => null === $d['sunshine_duration'] ? null : round( $d['sunshine_duration'] / 3600, 1 ),
				'evapotranspiracion' => $d['et0_fao_evapotranspiration'],
			);
		}
		return array(
			'ok'          => true,
			'filas'       => $filas,
			'actualizado' => $r['actualizado'],
			'vencido'     => $r['vencido'],
		);
	}

	/**
	 * Filas sin campos internos.
	 *
	 * @param array $filas Filas.
	 * @return array
	 */
	private static function limpiar( array $filas ) {
		return array_map(
			function ( $f ) {
				unset( $f['_diario'] );
				return $f;
			},
			$filas
		);
	}

	/* ------------------------------------------------------------------
	 * Procesadores
	 * ---------------------------------------------------------------- */

	/**
	 * Mapa de temperatura actual.
	 *
	 * @return array
	 */
	public static function mapa_temperatura() {
		$a = self::actuales();
		if ( ! $a['resp']['ok'] ) {
			return SAN_Catalogo::fallo( $a['resp'] );
		}
		$filas = self::limpiar( $a['filas'] );
		$est   = SAN_Analisis::estadisticos( array_column( $filas, 'temperatura' ) );
		$max   = SAN_Analisis::extremo( $filas, 'temperatura', true );
		$min   = SAN_Analisis::extremo( $filas, 'temperatura', false );
		$sen   = SAN_Analisis::extremo( $filas, 'sensacion', true );

		$lit = array();
		$and = array();
		foreach ( $filas as $f ) {
			if ( SAN_Municipios::es_litoral( $f['divipola'] ) ) {
				$lit[] = $f['temperatura'];
			} else {
				$and[] = $f['temperatura'];
			}
		}
		$m_lit    = SAN_Analisis::estadisticos( $lit )['media'];
		$m_and    = SAN_Analisis::estadisticos( $and )['media'];
		$lloviendo = count(
			array_filter(
				$filas,
				function ( $f ) {
					return (float) $f['lluvia'] > 0;
				}
			)
		);
		$grupos    = SAN_Analisis::agrupar( $filas, 'tiempo_grupo' );
		$predomina = (string) array_key_first( $grupos );

		$nivel = 'bueno';
		$recs  = array();
		if ( $sen && $sen['sensacion'] >= 32 ) {
			$nivel  = $sen['sensacion'] >= 38 ? 'alerta' : 'moderado';
			$recs[] = sprintf( 'En %s la sensación térmica alcanza %s: hidratación frecuente y evitar esfuerzo físico al sol entre las 11:00 y las 15:00.', $sen['municipio'], SAN_Analisis::con_unidad( $sen['sensacion'], '°C' ) );
		}
		if ( $min && $min['temperatura'] <= 6 ) {
			$nivel  = self::peor( array( $nivel, 'moderado' ) );
			$recs[] = sprintf( 'Temperaturas bajas en %s (%s): proteger a niños y adultos mayores del frío y vigilar cultivos sensibles a heladas.', $min['municipio'], SAN_Analisis::con_unidad( $min['temperatura'], '°C' ) );
		}

		$cual = array(
			sprintf( 'La temperatura en el departamento oscila entre %s en %s y %s en %s, con una media de %s entre las 64 cabeceras.', SAN_Analisis::con_unidad( $min['temperatura'], '°C' ), $min['municipio'], SAN_Analisis::con_unidad( $max['temperatura'], '°C' ), $max['municipio'], SAN_Analisis::con_unidad( $est['media'], '°C' ) ),
			null !== $m_lit && null !== $m_and ? sprintf( 'El litoral Pacífico promedia %s, %s más que la zona andina (%s): la diferencia refleja el gradiente altitudinal de unos 6 °C por cada 1.000 m.', SAN_Analisis::con_unidad( $m_lit, '°C' ), SAN_Analisis::con_unidad( $m_lit - $m_and, '°C' ), SAN_Analisis::con_unidad( $m_and, '°C' ) ) : '',
			sprintf( 'Predomina el estado "%s" y en este momento se registra lluvia en %d municipio%s.', mb_strtolower( $predomina ), $lloviendo, 1 === $lloviendo ? '' : 's' ),
		);

		return self::ok(
			$filas,
			array(
				'clave'     => 'divipola',
				'x'         => 'municipio',
				'y'         => 'temperatura',
				'etiqueta'  => 'municipio',
				'unidad'    => '°C',
				'decimales' => 1,
				'tono'      => 'naranja',
				'etiqueta_y' => 'Temperatura (°C)',
				'tooltip'   => array( array( 'Temperatura', 'temperatura', '°C', 1 ), array( 'Sensación', 'sensacion', '°C', 1 ), array( 'Humedad', 'humedad', '%', 0 ), array( 'Tiempo', 'tiempo' ), array( 'Subregión', 'subregion' ) ),
			),
			array(
				'nivel'                => $nivel,
				'titular'              => sprintf( 'Temperatura media departamental de %s, con %s de diferencia entre el municipio más cálido y el más frío.', SAN_Analisis::con_unidad( $est['media'], '°C' ), SAN_Analisis::con_unidad( $est['rango'], '°C' ) ),
				'cualitativo'          => array_values( array_filter( $cual ) ),
				'recomendaciones'      => $recs,
				'cuantitativo'         => array(
					self::cifra( 'Media', SAN_Analisis::con_unidad( $est['media'], '°C' ), 'n = ' . $est['n'] . ' municipios' ),
					self::cifra( 'Máxima', SAN_Analisis::con_unidad( $max['temperatura'], '°C' ), $max['municipio'] ),
					self::cifra( 'Mínima', SAN_Analisis::con_unidad( $min['temperatura'], '°C' ), $min['municipio'] ),
					self::cifra( 'Desviación estándar', SAN_Analisis::con_unidad( $est['desviacion'], '°C' ) ),
					self::cifra( 'Mediana', SAN_Analisis::con_unidad( $est['mediana'], '°C' ) ),
					self::cifra( 'Con lluvia ahora', (string) $lloviendo, 'municipios' ),
				),
				'resumen_cuantitativo' => sprintf( 'El 80 %% central de los municipios está entre %s y %s (percentiles 10 y 90); el coeficiente de variación es %s.', SAN_Analisis::con_unidad( $est['p10'], '°C' ), SAN_Analisis::con_unidad( $est['p90'], '°C' ), SAN_Analisis::pct( null === $est['cv'] ? null : $est['cv'] * 100 ) ),
				'metodo'               => 'Valores "current" del modelo en la coordenada de cada cabecera (DANE). Estadística descriptiva sobre los 64 municipios; litoral = subregiones Sanquianga, Pacífico Sur, Telembí y Pie de Monte Costero.',
			),
			$a['resp']
		);
	}

	/**
	 * Mapa de lluvia esperada hoy.
	 *
	 * @return array
	 */
	public static function mapa_lluvia() {
		$a = self::actuales();
		if ( ! $a['resp']['ok'] ) {
			return SAN_Catalogo::fallo( $a['resp'] );
		}
		$filas = self::limpiar( $a['filas'] );
		foreach ( $filas as &$f ) {
			$f['clase_lluvia'] = SAN_Umbrales::lluvia_diaria( $f['lluvia_hoy'] )['nombre'];
		}
		unset( $f );
		$est    = SAN_Analisis::estadisticos( array_column( $filas, 'lluvia_hoy' ) );
		$max    = SAN_Analisis::extremo( $filas, 'lluvia_hoy', true );
		$fuerte = array_filter(
			$filas,
			function ( $f ) {
				return (float) $f['lluvia_hoy'] >= 20;
			}
		);
		$muy    = array_filter(
			$filas,
			function ( $f ) {
				return (float) $f['lluvia_hoy'] >= 50;
			}
		);
		$secos  = array_filter(
			$filas,
			function ( $f ) {
				return (float) $f['lluvia_hoy'] < 0.1;
			}
		);
		usort(
			$fuerte,
			function ( $a, $b ) {
				return $b['lluvia_hoy'] <=> $a['lluvia_hoy'];
			}
		);
		$nivel = $muy ? 'critico' : ( $fuerte ? 'alerta' : ( $est['max'] >= 5 ? 'moderado' : 'bueno' ) );
		$recs  = array();
		if ( $fuerte ) {
			$recs[] = 'Activar la vigilancia de quebradas y taludes en los municipios con lluvia fuerte; evitar cruzar cauces crecidos.';
			$recs[] = 'Consultar los boletines del IDEAM y los consejos municipales de gestión del riesgo (Ley 1523 de 2012).';
		}
		$lista = implode(
			', ',
			array_map(
				function ( $f ) {
					return $f['municipio'] . ' (' . SAN_Analisis::con_unidad( $f['lluvia_hoy'], 'mm' ) . ')';
				},
				array_slice( $fuerte, 0, 5 )
			)
		);

		return self::ok(
			$filas,
			array(
				'clave'      => 'divipola',
				'x'          => 'municipio',
				'y'          => 'lluvia_hoy',
				'etiqueta'   => 'municipio',
				'unidad'     => 'mm',
				'decimales'  => 1,
				'tono'       => 'azul',
				'etiqueta_y' => 'Lluvia hoy (mm)',
				'tooltip'    => array( array( 'Lluvia hoy', 'lluvia_hoy', 'mm', 1 ), array( 'Probabilidad', 'prob_lluvia', '%', 0 ), array( 'Clase', 'clase_lluvia' ), array( 'Subregión', 'subregion' ) ),
			),
			array(
				'nivel'                => $nivel,
				'titular'              => $fuerte
					? sprintf( '%d municipio%s con lluvia fuerte o muy fuerte prevista hoy; el mayor acumulado se espera en %s (%s).', count( $fuerte ), 1 === count( $fuerte ) ? '' : 's', $max['municipio'], SAN_Analisis::con_unidad( $max['lluvia_hoy'], 'mm' ) )
					: sprintf( 'No se prevén lluvias fuertes hoy; el mayor acumulado sería en %s (%s).', $max['municipio'], SAN_Analisis::con_unidad( $max['lluvia_hoy'], 'mm' ) ),
				'cualitativo'          => array_values(
					array_filter(
						array(
							$fuerte ? 'Municipios con lluvia fuerte (≥ 20 mm): ' . $lista . '.' : '',
							sprintf( '%d municipios tendrían un día seco (< 0,1 mm).', count( $secos ) ),
							'La lluvia intensa en suelos ya saturados eleva el riesgo de movimientos en masa en la cordillera y de inundaciones en las partes bajas del Patía, el Mira y el Telembí.',
						)
					)
				),
				'recomendaciones'      => $recs,
				'cuantitativo'         => array(
					self::cifra( 'Media', SAN_Analisis::con_unidad( $est['media'], 'mm' ) ),
					self::cifra( 'Máximo', SAN_Analisis::con_unidad( $max['lluvia_hoy'], 'mm' ), $max['municipio'] ),
					self::cifra( 'Lluvia fuerte', (string) count( $fuerte ), 'municipios ≥ 20 mm' ),
					self::cifra( 'Muy fuerte', (string) count( $muy ), 'municipios ≥ 50 mm' ),
					self::cifra( 'Secos', (string) count( $secos ), 'municipios < 0,1 mm' ),
				),
				'resumen_cuantitativo' => sprintf( 'Suma de los acumulados municipales: %s; mediana %s; percentil 90: %s.', SAN_Analisis::con_unidad( $est['suma'], 'mm', 0 ), SAN_Analisis::con_unidad( $est['mediana'], 'mm' ), SAN_Analisis::con_unidad( $est['p90'], 'mm' ) ),
				'metodo'               => 'precipitation_sum diario del día en curso (pronóstico). Clases: < 5 mm débil, 5–20 moderada, 20–50 fuerte, ≥ 50 muy fuerte (clasificación operativa del plugin).',
			),
			$a['resp']
		);
	}

	/**
	 * Mapa de índice UV máximo.
	 *
	 * @return array
	 */
	public static function mapa_uv() {
		$a = self::actuales();
		if ( ! $a['resp']['ok'] ) {
			return SAN_Catalogo::fallo( $a['resp'] );
		}
		$filas  = self::limpiar( $a['filas'] );
		$conteo = array();
		foreach ( $filas as &$f ) {
			$cat             = SAN_Umbrales::uv( $f['uv_max'] );
			$f['categoria']  = $cat['nombre'];
			$conteo[ $cat['nombre'] ] = ( $conteo[ $cat['nombre'] ] ?? 0 ) + 1;
		}
		unset( $f );
		$est   = SAN_Analisis::estadisticos( array_column( $filas, 'uv_max' ) );
		$max   = SAN_Analisis::extremo( $filas, 'uv_max', true );
		$extr  = ( $conteo['Muy alto'] ?? 0 ) + ( $conteo['Extremo'] ?? 0 );
		$cat_m = SAN_Umbrales::uv( $est['media'] );

		return self::ok(
			$filas,
			array(
				'clave'      => 'divipola',
				'x'          => 'municipio',
				'y'          => 'uv_max',
				'etiqueta'   => 'municipio',
				'unidad'     => '',
				'decimales'  => 1,
				'tono'       => 'violeta',
				'etiqueta_y' => 'Índice UV máximo',
				'tooltip'    => array( array( 'UV máximo', 'uv_max', '', 1 ), array( 'Categoría', 'categoria' ), array( 'Subregión', 'subregion' ) ),
			),
			array(
				'nivel'                => $cat_m['nivel'],
				'titular'              => sprintf( 'Índice UV medio de %s (%s); %d municipios alcanzan niveles muy altos o extremos.', SAN_Analisis::num( $est['media'] ), mb_strtolower( $cat_m['nombre'] ), $extr ),
				'cualitativo'          => array(
					sprintf( 'El valor más alto se espera en %s (%s, %s).', $max['municipio'], SAN_Analisis::num( $max['uv_max'] ), mb_strtolower( SAN_Umbrales::uv( $max['uv_max'] )['nombre'] ) ),
					'Cerca del ecuador y en altura la radiación ultravioleta es intensa todo el año: la nubosidad la reduce, pero no la elimina.',
				),
				'recomendaciones'      => array( $cat_m['mensaje'], 'Reforzar la protección en colegios y trabajos al aire libre entre las 10:00 y las 15:00.' ),
				'cuantitativo'         => array(
					self::cifra( 'Media', SAN_Analisis::num( $est['media'] ) ),
					self::cifra( 'Máximo', SAN_Analisis::num( $max['uv_max'] ), $max['municipio'] ),
					self::cifra( 'Muy alto o extremo', (string) $extr, 'municipios' ),
					self::cifra( 'Mínimo', SAN_Analisis::num( $est['min'] ) ),
				),
				'resumen_cuantitativo' => 'Distribución por categoría OMS: ' . implode(
					'; ',
					array_map(
						function ( $k, $v ) {
							return $k . ' ' . $v;
						},
						array_keys( $conteo ),
						$conteo
					)
				) . '.',
				'metodo'               => 'uv_index_max diario (cielo real) del modelo; categorías del Índice UV Solar Mundial (OMS).',
			),
			$a['resp']
		);
	}

	/**
	 * Lluvia a 7 días por subregión.
	 *
	 * @return array
	 */
	public static function subregiones_lluvia() {
		$a = self::actuales();
		if ( ! $a['resp']['ok'] ) {
			return SAN_Catalogo::fallo( $a['resp'] );
		}
		$acum = array();
		foreach ( $a['filas'] as $f ) {
			$suma                         = array_sum( array_map( 'floatval', $f['_diario']['precipitation_sum'] ?? array() ) );
			$acum[ $f['subregion'] ][]    = $suma;
		}
		$filas = array();
		foreach ( $acum as $sub => $vals ) {
			$filas[] = array(
				'subregion'  => $sub,
				'lluvia_7d'  => round( array_sum( $vals ) / count( $vals ), 1 ),
				'municipios' => count( $vals ),
				'serie'      => 'Lluvia 7 días',
			);
		}
		usort(
			$filas,
			function ( $x, $y ) {
				return $y['lluvia_7d'] <=> $x['lluvia_7d'];
			}
		);
		$est = SAN_Analisis::estadisticos( array_column( $filas, 'lluvia_7d' ) );
		$top = $filas[0];
		$min = $filas[ count( $filas ) - 1 ];

		return self::ok(
			$filas,
			array(
				'x'          => 'subregion',
				'y'          => 'lluvia_7d',
				'etiqueta'   => 'subregion',
				'unidad'     => 'mm',
				'decimales'  => 1,
				'etiqueta_x' => 'Subregión',
				'etiqueta_y' => 'Lluvia acumulada 7 días (mm)',
				'tooltip'    => array( array( 'Lluvia 7 días', 'lluvia_7d', 'mm', 1 ), array( 'Municipios', 'municipios', '', 0 ) ),
			),
			array(
				'nivel'                => $top['lluvia_7d'] >= 150 ? 'alerta' : ( $top['lluvia_7d'] >= 70 ? 'moderado' : 'bueno' ),
				'titular'              => sprintf( '%s será la subregión más lluviosa de la semana (%s en promedio) y %s la más seca (%s).', $top['subregion'], SAN_Analisis::con_unidad( $top['lluvia_7d'], 'mm' ), $min['subregion'], SAN_Analisis::con_unidad( $min['lluvia_7d'], 'mm' ) ),
				'cualitativo'          => array(
					sprintf( 'La subregión más lluviosa recibiría %s veces el agua de la más seca, un contraste típico entre la vertiente del Pacífico y los valles interandinos.', SAN_Analisis::num( $min['lluvia_7d'] > 0 ? $top['lluvia_7d'] / $min['lluvia_7d'] : null ) ),
					'Acumulados semanales superiores a 150 mm en suelos saturados justifican vigilancia de cuencas y vías secundarias.',
				),
				'recomendaciones'      => array(),
				'cuantitativo'         => array(
					self::cifra( 'Más lluviosa', SAN_Analisis::con_unidad( $top['lluvia_7d'], 'mm' ), $top['subregion'] ),
					self::cifra( 'Más seca', SAN_Analisis::con_unidad( $min['lluvia_7d'], 'mm' ), $min['subregion'] ),
					self::cifra( 'Media subregional', SAN_Analisis::con_unidad( $est['media'], 'mm' ) ),
					self::cifra( 'Subregiones', (string) $est['n'] ),
				),
				'resumen_cuantitativo' => sprintf( 'Mediana %s; desviación estándar %s.', SAN_Analisis::con_unidad( $est['mediana'], 'mm' ), SAN_Analisis::con_unidad( $est['desviacion'], 'mm' ) ),
				'metodo'               => 'Suma de precipitation_sum de los 7 días pronosticados en cada cabecera, promediada entre los municipios de la subregión.',
			),
			$a['resp']
		);
	}

	/**
	 * Cajas de temperatura máxima por subregión.
	 *
	 * @return array
	 */
	public static function box_subregiones() {
		$a = self::actuales();
		if ( ! $a['resp']['ok'] ) {
			return SAN_Catalogo::fallo( $a['resp'] );
		}
		$filas = array();
		$por   = array();
		foreach ( $a['filas'] as $f ) {
			foreach ( (array) ( $f['_diario']['temperature_2m_max'] ?? array() ) as $i => $t ) {
				if ( null === $t ) {
					continue;
				}
				$filas[]                 = array(
					'id'        => $f['divipola'] . '-' . $i,
					'subregion' => SAN_Municipios::subregion_corta( $f['subregion'] ),
					'municipio' => $f['municipio'],
					'fecha'     => $f['_diario']['time'][ $i ] ?? '',
					'temp_max'  => $t,
				);
				$por[ SAN_Municipios::subregion_corta( $f['subregion'] ) ][] = $t;
			}
		}
		$medianas = array();
		foreach ( $por as $s => $v ) {
			$medianas[ $s ] = SAN_Analisis::estadisticos( $v )['mediana'];
		}
		arsort( $medianas );
		$calida = (string) array_key_first( $medianas );
		end( $medianas );
		$fria   = (string) key( $medianas );
		$global = SAN_Analisis::estadisticos( array_column( $filas, 'temp_max' ) );

		return self::ok(
			$filas,
			array(
				'x'          => 'subregion',
				'y'          => 'temp_max',
				'id'         => 'id',
				'unidad'     => '°C',
				'decimales'  => 1,
				'etiqueta_x' => 'Subregión',
				'etiqueta_y' => 'Temperatura máxima (°C)',
			),
			array(
				'nivel'                => 'info',
				'titular'              => sprintf( 'Las máximas de la semana van de %s a %s; %s es la subregión más cálida (mediana %s) y %s la más fresca (%s).', SAN_Analisis::con_unidad( $global['min'], '°C' ), SAN_Analisis::con_unidad( $global['max'], '°C' ), $calida, SAN_Analisis::con_unidad( $medianas[ $calida ], '°C' ), $fria, SAN_Analisis::con_unidad( $medianas[ $fria ], '°C' ) ),
				'cualitativo'          => array( 'Las subregiones con cajas más largas combinan municipios de distintos pisos térmicos; las cajas compactas indican condiciones homogéneas.' ),
				'recomendaciones'      => array(),
				'cuantitativo'         => array(
					self::cifra( 'Observaciones', (string) $global['n'], 'municipio × día' ),
					self::cifra( 'Mediana global', SAN_Analisis::con_unidad( $global['mediana'], '°C' ) ),
					self::cifra( 'Rango', SAN_Analisis::con_unidad( $global['rango'], '°C' ) ),
					self::cifra( 'Rango intercuartil aprox.', SAN_Analisis::con_unidad( $global['p90'] - $global['p10'], '°C' ), 'P10–P90' ),
				),
				'resumen_cuantitativo' => 'Medianas por subregión: ' . implode(
					'; ',
					array_map(
						function ( $s, $v ) {
							return $s . ' ' . SAN_Analisis::con_unidad( $v, '°C' );
						},
						array_keys( $medianas ),
						$medianas
					)
				) . '.',
				'metodo'               => 'temperature_2m_max diaria (7 días) de cada cabecera; cajas de D3plus BoxWhisker (cuartiles y rango).',
			),
			$a['resp']
		);
	}

	/**
	 * Transiciones del estado del tiempo (Chord).
	 *
	 * @return array
	 */
	public static function transiciones() {
		$a = self::actuales();
		if ( ! $a['resp']['ok'] ) {
			return SAN_Catalogo::fallo( $a['resp'] );
		}
		$cuenta = array();
		$total  = 0;
		$igual  = 0;
		$estado = array();
		foreach ( $a['filas'] as $f ) {
			$codigos = array_values( (array) ( $f['_diario']['weather_code'] ?? array() ) );
			for ( $i = 0; $i + 1 < count( $codigos ); $i++ ) {
				if ( null === $codigos[ $i ] || null === $codigos[ $i + 1 ] ) {
					continue;
				}
				$o = SAN_Umbrales::wmo( $codigos[ $i ] )['grupo'];
				$d = SAN_Umbrales::wmo( $codigos[ $i + 1 ] )['grupo'];
				$cuenta[ $o ][ $d ] = ( $cuenta[ $o ][ $d ] ?? 0 ) + 1;
				$estado[ $o ]       = ( $estado[ $o ] ?? 0 ) + 1;
				++$total;
				if ( $o === $d ) {
					++$igual;
				}
			}
		}
		$filas = array();
		$top   = null;
		foreach ( $cuenta as $o => $dest ) {
			foreach ( $dest as $d => $n ) {
				$filas[] = array(
					'origen'  => $o,
					'destino' => $d,
					'valor'   => $n,
				);
				if ( $o !== $d && ( ! $top || $n > $top['valor'] ) ) {
					$top = end( $filas );
				}
			}
		}
		arsort( $estado );
		$dom        = (string) array_key_first( $estado );
		$pers       = $total ? $igual / $total * 100 : 0;
		$lluv_total = 0;
		$lluv_sigue = 0;
		foreach ( array( 'Lluvia', 'Chubascos', 'Tormenta', 'Llovizna' ) as $g ) {
			foreach ( ( $cuenta[ $g ] ?? array() ) as $d => $n ) {
				$lluv_total += $n;
				if ( in_array( $d, array( 'Lluvia', 'Chubascos', 'Tormenta', 'Llovizna' ), true ) ) {
					$lluv_sigue += $n;
				}
			}
		}

		return self::ok(
			$filas,
			array(
				'origen'     => 'origen',
				'destino'    => 'destino',
				'valor'      => 'valor',
				'x'          => 'destino',
				'y'          => 'origen',
				'unidad'     => 'casos',
				'decimales'  => 0,
				'etiqueta_y' => 'Transiciones',
			),
			array(
				'nivel'                => 'info',
				'titular'              => sprintf( 'El tiempo persiste de un día a otro en el %s de los casos; el estado dominante de la semana es "%s".', SAN_Analisis::num( $pers, 0 ) . ' %', mb_strtolower( $dom ) ),
				'cualitativo'          => array_values(
					array_filter(
						array(
							$top ? sprintf( 'El cambio más frecuente es de "%s" a "%s" (%d casos).', mb_strtolower( $top['origen'] ), mb_strtolower( $top['destino'] ), $top['valor'] ) : '',
							$lluv_total ? sprintf( 'Después de un día con precipitación, en el %s de los casos el día siguiente también llueve: una señal de la persistencia de los sistemas húmedos.', SAN_Analisis::num( $lluv_sigue / $lluv_total * 100, 0 ) . ' %' ) : '',
						)
					)
				),
				'recomendaciones'      => array(),
				'cuantitativo'         => array(
					self::cifra( 'Transiciones', (string) $total, '64 municipios × 6 pasos' ),
					self::cifra( 'Persistencia', SAN_Analisis::num( $pers, 1 ) . ' %' ),
					self::cifra( 'Estados distintos', (string) count( $estado ) ),
					self::cifra( 'Estado dominante', $dom, $estado[ $dom ] . ' días' ),
				),
				'resumen_cuantitativo' => 'Frecuencia de estados de origen: ' . implode(
					'; ',
					array_map(
						function ( $k, $v ) {
							return $k . ' ' . $v;
						},
						array_keys( $estado ),
						$estado
					)
				) . '.',
				'metodo'               => 'Códigos WMO diarios agrupados en 8 estados; se cuenta cada par (día d → día d+1) por municipio. Matriz de transición representada con D3plus Chord (v4).',
			),
			$a['resp']
		);
	}

	/**
	 * Pronóstico de temperatura de un municipio.
	 *
	 * @param array $p Parámetros.
	 * @return array
	 */
	public static function pronostico_temperatura( array $p ) {
		$r = SAN_Fuentes::obtener( self::F )->pronostico( $p['municipio'] );
		if ( ! $r['ok'] ) {
			return SAN_Catalogo::fallo( $r );
		}
		$dias  = SAN_Fuente_Openmeteo::filas( $r['datos']['daily'] ?? array() );
		$filas = array();
		foreach ( $dias as $d ) {
			foreach ( array( 'Máxima' => 'temperature_2m_max', 'Mínima' => 'temperature_2m_min', 'Sensación máx.' => 'apparent_temperature_max' ) as $serie => $k ) {
				if ( null !== $d[ $k ] ) {
					$filas[] = array(
						'fecha' => $d['time'],
						'serie' => $serie,
						'valor' => $d[ $k ],
					);
				}
			}
		}
		$maxs  = array_column( $dias, 'temperature_2m_max' );
		$mins  = array_column( $dias, 'temperature_2m_min' );
		$em    = SAN_Analisis::estadisticos( $maxs );
		$en    = SAN_Analisis::estadisticos( $mins );
		$tend  = SAN_Analisis::tendencia( $maxs );
		$amp   = array();
		foreach ( $dias as $d ) {
			if ( null !== $d['temperature_2m_max'] && null !== $d['temperature_2m_min'] ) {
				$amp[] = $d['temperature_2m_max'] - $d['temperature_2m_min'];
			}
		}
		$ea    = SAN_Analisis::estadisticos( $amp );
		$dmax  = SAN_Analisis::extremo( $dias, 'temperature_2m_max', true );
		$dmin  = SAN_Analisis::extremo( $dias, 'temperature_2m_min', false );
		$nom   = self::muni( $p['municipio'] );
		$frase = SAN_Analisis::frase_tendencia( $tend['pendiente'], $em['desviacion'] ?: 1, '°C', 'día' );

		$recs = array();
		if ( $en['min'] !== null && $en['min'] <= 3 ) {
			$recs[] = 'Posibles heladas en las madrugadas: cubrir cultivos sensibles y abrigar a la población vulnerable.';
		}
		if ( $em['max'] !== null && $em['max'] >= 32 ) {
			$recs[] = 'Días calurosos: hidratación, sombra y pausas en trabajos al aire libre.';
		}

		return self::ok(
			$filas,
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
				'nivel'                => $recs ? 'moderado' : 'bueno',
				'titular'              => sprintf( 'En %s las máximas rondarán %s y las mínimas %s; la tendencia de las máximas es %s.', $nom, SAN_Analisis::con_unidad( $em['media'], '°C' ), SAN_Analisis::con_unidad( $en['media'], '°C' ), $frase ),
				'cualitativo'          => array(
					sprintf( 'El día más cálido sería el %s (%s) y la madrugada más fría el %s (%s).', SAN_Analisis::fecha( $dmax['time'] ), SAN_Analisis::con_unidad( $dmax['temperature_2m_max'], '°C' ), SAN_Analisis::fecha( $dmin['time'] ), SAN_Analisis::con_unidad( $dmin['temperature_2m_min'], '°C' ) ),
					sprintf( 'La amplitud térmica diaria promedio es de %s, %s.', SAN_Analisis::con_unidad( $ea['media'], '°C' ), $ea['media'] >= 10 ? 'propia de cielos despejados y aire seco en altura' : 'moderada por la nubosidad y la humedad' ),
				),
				'recomendaciones'      => $recs,
				'cuantitativo'         => array(
					self::cifra( 'Máxima media', SAN_Analisis::con_unidad( $em['media'], '°C' ) ),
					self::cifra( 'Mínima media', SAN_Analisis::con_unidad( $en['media'], '°C' ) ),
					self::cifra( 'Pico', SAN_Analisis::con_unidad( $em['max'], '°C' ), SAN_Analisis::fecha( $dmax['time'] ) ),
					self::cifra( 'Mínima absoluta', SAN_Analisis::con_unidad( $en['min'], '°C' ), SAN_Analisis::fecha( $dmin['time'] ) ),
					self::cifra( 'Amplitud media', SAN_Analisis::con_unidad( $ea['media'], '°C' ) ),
					self::cifra( 'Tendencia', ( $tend['pendiente'] >= 0 ? '+' : '−' ) . SAN_Analisis::con_unidad( abs( $tend['pendiente'] ), '°C/día', 2 ), 'R² ' . SAN_Analisis::num( $tend['r2'], 2 ) ),
				),
				'resumen_cuantitativo' => sprintf( 'Horizonte de %d días. Cambio total estimado de las máximas: %s (regresión lineal).', count( $dias ), ( $tend['cambio_total'] >= 0 ? '+' : '−' ) . SAN_Analisis::con_unidad( abs( $tend['cambio_total'] ), '°C' ) ),
				'metodo'               => 'Pronóstico diario Open-Meteo en la cabecera municipal; tendencia por mínimos cuadrados sobre las máximas.',
			),
			$r
		);
	}

	/**
	 * Pronóstico de lluvia de un municipio.
	 *
	 * @param array $p Parámetros.
	 * @return array
	 */
	public static function pronostico_lluvia( array $p ) {
		$r = SAN_Fuentes::obtener( self::F )->pronostico( $p['municipio'] );
		if ( ! $r['ok'] ) {
			return SAN_Catalogo::fallo( $r );
		}
		$dias  = SAN_Fuente_Openmeteo::filas( $r['datos']['daily'] ?? array() );
		$filas = array();
		foreach ( $dias as $d ) {
			$filas[] = array(
				'fecha'        => $d['time'],
				'lluvia'       => $d['precipitation_sum'],
				'probabilidad' => $d['precipitation_probability_max'],
				'clase'        => SAN_Umbrales::lluvia_diaria( $d['precipitation_sum'] )['nombre'],
			);
		}
		$vals    = array_column( $filas, 'lluvia' );
		$est     = SAN_Analisis::estadisticos( $vals );
		$lluvios = count(
			array_filter(
				$vals,
				function ( $v ) {
					return (float) $v >= 1;
				}
			)
		);
		$fuertes = array_filter(
			$filas,
			function ( $f ) {
				return (float) $f['lluvia'] >= 20;
			}
		);
		$pico    = SAN_Analisis::extremo( $filas, 'lluvia', true );
		$clase   = SAN_Umbrales::lluvia_diaria( $pico['lluvia'] );
		$nom     = self::muni( $p['municipio'] );
		$recs    = array();
		if ( $fuertes ) {
			$recs[] = 'Programar la limpieza de cunetas y alcantarillas antes de los días de lluvia fuerte.';
			$recs[] = 'Atender los avisos del consejo municipal de gestión del riesgo.';
		}
		if ( $lluvios <= 1 && count( $dias ) >= 7 ) {
			$recs[] = 'Periodo seco: uso eficiente del agua y prevención de quemas.';
		}

		return self::ok(
			$filas,
			array(
				'x'          => 'fecha',
				'y'          => 'lluvia',
				'tiempo'     => true,
				'unidad'     => 'mm',
				'decimales'  => 1,
				'etiqueta_x' => 'Fecha',
				'etiqueta_y' => 'Lluvia (mm)',
				'tooltip'    => array( array( 'Lluvia', 'lluvia', 'mm', 1 ), array( 'Probabilidad', 'probabilidad', '%', 0 ), array( 'Clase', 'clase' ) ),
			),
			array(
				'nivel'                => $clase['nivel'],
				'titular'              => sprintf( 'Se esperan %s en %d días para %s, con %d día%s de lluvia (≥ 1 mm).', SAN_Analisis::con_unidad( $est['suma'], 'mm', 0 ), count( $dias ), $nom, $lluvios, 1 === $lluvios ? '' : 's' ),
				'cualitativo'          => array(
					sprintf( 'El día más lluvioso sería el %s con %s (%s) y probabilidad de %s.', SAN_Analisis::fecha( $pico['fecha'] ), SAN_Analisis::con_unidad( $pico['lluvia'], 'mm' ), $clase['nombre'], SAN_Analisis::num( $pico['probabilidad'], 0 ) . ' %' ),
					$fuertes ? sprintf( 'Hay %d día%s con lluvia fuerte (≥ 20 mm).', count( $fuertes ), 1 === count( $fuertes ) ? '' : 's' ) : 'No se prevén días de lluvia fuerte.',
				),
				'recomendaciones'      => $recs,
				'cuantitativo'         => array(
					self::cifra( 'Acumulado', SAN_Analisis::con_unidad( $est['suma'], 'mm', 0 ) ),
					self::cifra( 'Días con lluvia', (string) $lluvios, 'de ' . count( $dias ) ),
					self::cifra( 'Máximo diario', SAN_Analisis::con_unidad( $pico['lluvia'], 'mm' ), SAN_Analisis::fecha( $pico['fecha'] ) ),
					self::cifra( 'Media diaria', SAN_Analisis::con_unidad( $est['media'], 'mm' ) ),
				),
				'resumen_cuantitativo' => sprintf( 'Mediana diaria %s; el día más lluvioso concentra el %s del total.', SAN_Analisis::con_unidad( $est['mediana'], 'mm' ), SAN_Analisis::num( $est['suma'] > 0 ? $pico['lluvia'] / $est['suma'] * 100 : 0, 0 ) . ' %' ),
				'metodo'               => 'precipitation_sum y precipitation_probability_max diarios (Open-Meteo).',
			),
			$r
		);
	}

	/**
	 * Mapa de calor horario de temperatura.
	 *
	 * @param array $p Parámetros.
	 * @return array
	 */
	public static function calor_horario( array $p ) {
		$r = SAN_Fuentes::obtener( self::F )->pronostico( $p['municipio'] );
		if ( ! $r['ok'] ) {
			return SAN_Catalogo::fallo( $r );
		}
		$horas = SAN_Fuente_Openmeteo::filas( $r['datos']['hourly'] ?? array() );
		$filas = array();
		$porh  = array();
		$pord  = array();
		foreach ( $horas as $h ) {
			if ( null === $h['temperature_2m'] ) {
				continue;
			}
			$t                 = strtotime( $h['time'] );
			$hora              = gmdate( 'H', $t ) . ':00';
			$dia               = SAN_Analisis::fecha( $h['time'] );
			$filas[]           = array(
				'dia'         => $dia,
				'hora'        => $hora,
				'temperatura' => $h['temperature_2m'],
				'humedad'     => $h['relative_humidity_2m'],
			);
			$porh[ $hora ][]   = $h['temperature_2m'];
			$pord[ $dia ][]    = $h['temperature_2m'];
		}
		$media_h = array();
		foreach ( $porh as $k => $v ) {
			$media_h[ $k ] = array_sum( $v ) / count( $v );
		}
		arsort( $media_h );
		$hc   = (string) array_key_first( $media_h );
		$hf   = (string) array_key_last( $media_h );
		$amp  = array();
		foreach ( $pord as $v ) {
			$amp[] = max( $v ) - min( $v );
		}
		$est  = SAN_Analisis::estadisticos( array_column( $filas, 'temperatura' ) );
		$ea   = SAN_Analisis::estadisticos( $amp );
		$nom  = self::muni( $p['municipio'] );

		return self::ok(
			$filas,
			array(
				'x'       => 'hora',
				'y'       => 'dia',
				'valor'   => 'temperatura',
				'unidad'  => '°C',
				'tono'    => 'naranja',
				'tooltip' => array( array( 'Temperatura', 'temperatura', '°C', 1 ), array( 'Humedad', 'humedad', '%', 0 ) ),
			),
			array(
				'nivel'                => $est['min'] <= 2 ? 'moderado' : 'info',
				'titular'              => sprintf( 'En %s la hora más cálida suele ser las %s (%s en promedio) y la más fría las %s (%s).', $nom, $hc, SAN_Analisis::con_unidad( $media_h[ $hc ], '°C' ), $hf, SAN_Analisis::con_unidad( $media_h[ $hf ], '°C' ) ),
				'cualitativo'          => array(
					sprintf( 'La oscilación diaria media es de %s entre la madrugada y la tarde.', SAN_Analisis::con_unidad( $ea['media'], '°C' ) ),
					$est['min'] <= 2 ? 'Algunas madrugadas bajan de 2 °C: condiciones favorables para heladas en zonas abiertas.' : 'No se esperan temperaturas de helada.',
				),
				'recomendaciones'      => array(),
				'cuantitativo'         => array(
					self::cifra( 'Horas analizadas', (string) $est['n'] ),
					self::cifra( 'Máxima horaria', SAN_Analisis::con_unidad( $est['max'], '°C' ) ),
					self::cifra( 'Mínima horaria', SAN_Analisis::con_unidad( $est['min'], '°C' ) ),
					self::cifra( 'Oscilación diaria media', SAN_Analisis::con_unidad( $ea['media'], '°C' ) ),
				),
				'resumen_cuantitativo' => sprintf( 'Media de las %d horas: %s; desviación estándar %s.', $est['n'], SAN_Analisis::con_unidad( $est['media'], '°C' ), SAN_Analisis::con_unidad( $est['desviacion'], '°C' ) ),
				'metodo'               => 'temperature_2m horaria (168 h). Hora local de Colombia (UTC−5).',
			),
			$r
		);
	}

	/**
	 * Rosa de vientos a partir del pronóstico horario.
	 *
	 * @param array $p Parámetros.
	 * @return array
	 */
	public static function rosa_vientos( array $p ) {
		$r = SAN_Fuentes::obtener( self::F )->pronostico( $p['municipio'] );
		if ( ! $r['ok'] ) {
			return SAN_Catalogo::fallo( $r );
		}
		$horas    = SAN_Fuente_Openmeteo::filas( $r['datos']['hourly'] ?? array() );
		$sectores = array( 'N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE', 'S', 'SSO', 'SO', 'OSO', 'O', 'ONO', 'NO', 'NNO' );
		$rangos   = array(
			array( 0, 5, '0–5 km/h' ),
			array( 5, 10, '5–10 km/h' ),
			array( 10, 20, '10–20 km/h' ),
			array( 20, 999, 'Más de 20 km/h' ),
		);
		$cuenta   = array();
		$n        = 0;
		$vel      = array();
		$por_sec  = array();
		foreach ( $horas as $h ) {
			if ( null === $h['wind_direction_10m'] || null === $h['wind_speed_10m'] ) {
				continue;
			}
			$s = $sectores[ (int) round( fmod( (float) $h['wind_direction_10m'] + 360, 360 ) / 22.5 ) % 16 ];
			foreach ( $rangos as $rg ) {
				if ( $h['wind_speed_10m'] >= $rg[0] && $h['wind_speed_10m'] < $rg[1] ) {
					$cuenta[ $s ][ $rg[2] ] = ( $cuenta[ $s ][ $rg[2] ] ?? 0 ) + 1;
					break;
				}
			}
			$por_sec[ $s ] = ( $por_sec[ $s ] ?? 0 ) + 1;
			$vel[]         = $h['wind_speed_10m'];
			++$n;
		}
		if ( ! $n ) {
			return array(
				'ok'    => false,
				'error' => 'Sin datos de viento.',
			);
		}
		$filas = array();
		foreach ( $sectores as $s ) {
			foreach ( $rangos as $rg ) {
				if ( isset( $cuenta[ $s ][ $rg[2] ] ) ) {
					$filas[] = array(
						'sector'     => $s,
						'intensidad' => $rg[2],
						'frecuencia' => round( $cuenta[ $s ][ $rg[2] ] / $n * 100, 1 ),
					);
				}
			}
		}
		arsort( $por_sec );
		$dom   = (string) array_key_first( $por_sec );
		$est   = SAN_Analisis::estadisticos( $vel );
		$bf    = SAN_Umbrales::viento( $est['max'] );
		$calma = count(
			array_filter(
				$vel,
				function ( $v ) {
					return $v < 2;
				}
			)
		);

		return self::ok(
			$filas,
			array(
				'direccion' => 'sector',
				'grupo'     => 'intensidad',
				'valor'     => 'frecuencia',
				'sectores'  => $sectores,
				'unidad'    => '%',
			),
			array(
				'nivel'                => $bf['nivel'],
				'titular'              => sprintf( 'En %s el viento sopla sobre todo del %s (%s del tiempo), con velocidad media de %s.', self::muni( $p['municipio'] ), $dom, SAN_Analisis::num( $por_sec[ $dom ] / $n * 100, 0 ) . ' %', SAN_Analisis::con_unidad( $est['media'], 'km/h' ) ),
				'cualitativo'          => array(
					sprintf( 'La ráfaga horaria máxima prevista es de %s (%s en la escala de Beaufort).', SAN_Analisis::con_unidad( $est['max'], 'km/h' ), $bf['nombre'] ),
					sprintf( 'Las calmas (< 2 km/h) ocupan el %s de las horas; con calma y cielo despejado aumenta la inversión térmica nocturna.', SAN_Analisis::num( $calma / $n * 100, 0 ) . ' %' ),
				),
				'recomendaciones'      => 'alerta' === $bf['nivel'] || 'critico' === $bf['nivel'] ? array( 'Asegurar techos livianos y estructuras temporales; precaución en vías de montaña.' ) : array(),
				'cuantitativo'         => array(
					self::cifra( 'Dirección dominante', $dom ),
					self::cifra( 'Velocidad media', SAN_Analisis::con_unidad( $est['media'], 'km/h' ) ),
					self::cifra( 'Máxima horaria', SAN_Analisis::con_unidad( $est['max'], 'km/h' ) ),
					self::cifra( 'Horas analizadas', (string) $n ),
				),
				'resumen_cuantitativo' => sprintf( 'Percentil 90 de la velocidad: %s. Sectores de 22,5°; frecuencias en %% de las horas.', SAN_Analisis::con_unidad( $est['p90'], 'km/h' ) ),
				'metodo'               => 'wind_direction_10m y wind_speed_10m horarios (168 h), agrupados en 16 sectores y 4 rangos de intensidad.',
			),
			$r
		);
	}
}
