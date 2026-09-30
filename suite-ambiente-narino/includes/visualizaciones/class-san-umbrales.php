<?php
/**
 * Umbrales y clasificaciones usadas en el análisis cualitativo.
 *
 * Cada clasificación indica su referencia normativa o técnica. Donde no hay
 * una norma colombiana aplicable se declara como clasificación operativa del
 * plugin, para que el lector sepa de dónde viene cada juicio.
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Umbrales {

	/**
	 * Categorías del Índice de Calidad del Aire (ICA), Resolución 2254 de 2017
	 * (MinAmbiente), con sus colores oficiales y mensajes de salud.
	 */
	const ICA_CATEGORIAS = array(
		array(
			'min'     => 0,
			'max'     => 50,
			'nombre'  => 'Buena',
			'color'   => '#00E400',
			'nivel'   => 'bueno',
			'mensaje' => 'La calidad del aire es satisfactoria y no representa riesgo para la salud.',
		),
		array(
			'min'     => 51,
			'max'     => 100,
			'nombre'  => 'Aceptable',
			'color'   => '#FFFF00',
			'nivel'   => 'moderado',
			'mensaje' => 'Aceptable; personas excepcionalmente sensibles podrían presentar síntomas leves.',
		),
		array(
			'min'     => 101,
			'max'     => 150,
			'nombre'  => 'Dañina a la salud de grupos sensibles',
			'color'   => '#FF7E00',
			'nivel'   => 'alerta',
			'mensaje' => 'Niños, adultos mayores y personas con enfermedades respiratorias o cardiovasculares deben reducir la actividad física prolongada al aire libre.',
		),
		array(
			'min'     => 151,
			'max'     => 200,
			'nombre'  => 'Dañina a la salud',
			'color'   => '#FF0000',
			'nivel'   => 'critico',
			'mensaje' => 'Toda la población puede sufrir efectos; los grupos sensibles, efectos más graves.',
		),
		array(
			'min'     => 201,
			'max'     => 300,
			'nombre'  => 'Muy dañina a la salud',
			'color'   => '#8F3F97',
			'nivel'   => 'critico',
			'mensaje' => 'Alerta sanitaria: toda la población puede presentar efectos graves.',
		),
		array(
			'min'     => 301,
			'max'     => 500,
			'nombre'  => 'Peligrosa',
			'color'   => '#7E0023',
			'nivel'   => 'critico',
			'mensaje' => 'Emergencia sanitaria.',
		),
	);

	/**
	 * Puntos de corte de concentración (µg/m³) por contaminante para cada
	 * categoría del ICA (Res. 2254/2017, tabla de puntos de corte).
	 * PM: promedio 24 h; O3 y CO: 8 h; NO2 y SO2: 1 h.
	 */
	const ICA_CORTES = array(
		'pm2_5'            => array( 12, 37, 55, 150, 250, 500 ),
		'pm10'             => array( 54, 154, 254, 354, 424, 604 ),
		'ozone'            => array( 106, 138, 167, 207, 393, 600 ),
		'nitrogen_dioxide' => array( 100, 189, 677, 1221, 2349, 3853 ),
		'sulphur_dioxide'  => array( 93, 197, 486, 797, 1583, 2629 ),
		'carbon_monoxide'  => array( 5094, 10819, 14254, 17688, 34862, 57703 ),
	);

	/** Límites de los índices del ICA por categoría. */
	const ICA_INDICES = array( array( 0, 50 ), array( 51, 100 ), array( 101, 150 ), array( 151, 200 ), array( 201, 300 ), array( 301, 500 ) );

	/**
	 * Calcula el ICA de un contaminante por interpolación lineal.
	 *
	 * @param string $contaminante Clave (pm2_5, pm10, ozone…).
	 * @param float  $c            Concentración (µg/m³).
	 * @return float|null
	 */
	public static function ica( $contaminante, $c ) {
		$c = SAN_Analisis::numero( $c );
		if ( null === $c || ! isset( self::ICA_CORTES[ $contaminante ] ) ) {
			return null;
		}
		$c      = max( 0.0, $c );
		$cortes = self::ICA_CORTES[ $contaminante ];
		$ultimo = count( $cortes ) - 1;
		$c_bajo = 0.0;
		foreach ( $cortes as $i => $c_alto ) {
			if ( $c <= $c_alto || $i === $ultimo ) {
				list( $i_bajo, $i_alto ) = self::ICA_INDICES[ $i ];
				$c                       = min( $c, (float) $c_alto );
				return $i_bajo + ( $i_alto - $i_bajo ) * ( $c - $c_bajo ) / ( $c_alto - $c_bajo );
			}
			$c_bajo = (float) $c_alto;
		}
		return null;
	}

	/**
	 * Categoría del ICA para un valor de índice.
	 *
	 * @param float $indice Valor del ICA.
	 * @return array
	 */
	public static function categoria_ica( $indice ) {
		$indice = (float) $indice;
		foreach ( self::ICA_CATEGORIAS as $cat ) {
			if ( $indice <= $cat['max'] + 0.5 ) {
				return $cat;
			}
		}
		return self::ICA_CATEGORIAS[ count( self::ICA_CATEGORIAS ) - 1 ];
	}

	/**
	 * Índice UV (OMS, Global Solar UV Index).
	 *
	 * @param float $uv Índice UV.
	 * @return array { nombre, nivel, color, mensaje }
	 */
	public static function uv( $uv ) {
		$uv = (float) $uv;
		if ( $uv < 3 ) {
			return array( 'nombre' => 'Bajo', 'nivel' => 'bueno', 'color' => '#3EA72D', 'mensaje' => 'No se requiere protección especial.' );
		}
		if ( $uv < 6 ) {
			return array( 'nombre' => 'Moderado', 'nivel' => 'moderado', 'color' => '#FFF300', 'mensaje' => 'Use sombrero y protector solar al mediodía.' );
		}
		if ( $uv < 8 ) {
			return array( 'nombre' => 'Alto', 'nivel' => 'alerta', 'color' => '#F18B00', 'mensaje' => 'Protección necesaria: sombra, gafas y protector solar FPS 30+.' );
		}
		if ( $uv < 11 ) {
			return array( 'nombre' => 'Muy alto', 'nivel' => 'critico', 'color' => '#E53210', 'mensaje' => 'Protección extra: evite la exposición entre las 10:00 y las 15:00.' );
		}
		return array( 'nombre' => 'Extremo', 'nivel' => 'critico', 'color' => '#B567A4', 'mensaje' => 'Evite salir al sol en horas centrales; la piel sin protección se quema en minutos.' );
	}

	/**
	 * Precipitación diaria (clasificación operativa del plugin).
	 *
	 * @param float $mm Milímetros en 24 h.
	 * @return array { nombre, nivel }
	 */
	public static function lluvia_diaria( $mm ) {
		$mm = (float) $mm;
		if ( $mm < 0.1 ) {
			return array( 'nombre' => 'sin lluvia', 'nivel' => 'bueno' );
		}
		if ( $mm < 5 ) {
			return array( 'nombre' => 'lluvia débil', 'nivel' => 'bueno' );
		}
		if ( $mm < 20 ) {
			return array( 'nombre' => 'lluvia moderada', 'nivel' => 'moderado' );
		}
		if ( $mm < 50 ) {
			return array( 'nombre' => 'lluvia fuerte', 'nivel' => 'alerta' );
		}
		return array( 'nombre' => 'lluvia muy fuerte', 'nivel' => 'critico' );
	}

	/**
	 * Magnitud sísmica (escala descriptiva usual del Servicio Geológico).
	 *
	 * @param float $m Magnitud.
	 * @return array { nombre, nivel }
	 */
	public static function sismo( $m ) {
		$m = (float) $m;
		if ( $m < 3 ) {
			return array( 'nombre' => 'micro', 'nivel' => 'bueno' );
		}
		if ( $m < 4 ) {
			return array( 'nombre' => 'menor', 'nivel' => 'bueno' );
		}
		if ( $m < 5 ) {
			return array( 'nombre' => 'ligero', 'nivel' => 'moderado' );
		}
		if ( $m < 6 ) {
			return array( 'nombre' => 'moderado', 'nivel' => 'alerta' );
		}
		if ( $m < 7 ) {
			return array( 'nombre' => 'fuerte', 'nivel' => 'critico' );
		}
		return array( 'nombre' => 'mayor', 'nivel' => 'critico' );
	}

	/**
	 * Profundidad hipocentral.
	 *
	 * @param float $km Profundidad en km.
	 * @return string
	 */
	public static function profundidad( $km ) {
		$km = (float) $km;
		if ( $km < 70 ) {
			return 'superficial';
		}
		return $km < 300 ? 'intermedio' : 'profundo';
	}

	/**
	 * Estado del mar por altura significativa de ola (escala Douglas simplificada).
	 *
	 * @param float $m Metros.
	 * @return array { nombre, nivel }
	 */
	public static function oleaje( $m ) {
		$m = (float) $m;
		if ( $m < 0.5 ) {
			return array( 'nombre' => 'mar en calma o rizada', 'nivel' => 'bueno' );
		}
		if ( $m < 1.25 ) {
			return array( 'nombre' => 'marejadilla', 'nivel' => 'bueno' );
		}
		if ( $m < 2.5 ) {
			return array( 'nombre' => 'marejada', 'nivel' => 'moderado' );
		}
		if ( $m < 4 ) {
			return array( 'nombre' => 'fuerte marejada', 'nivel' => 'alerta' );
		}
		return array( 'nombre' => 'mar gruesa', 'nivel' => 'critico' );
	}

	/**
	 * Viento (escala de Beaufort, km/h).
	 *
	 * @param float $kmh Velocidad.
	 * @return array { nombre, nivel }
	 */
	public static function viento( $kmh ) {
		$kmh    = (float) $kmh;
		$escala = array(
			array( 1, 'calma', 'bueno' ),
			array( 6, 'ventolina', 'bueno' ),
			array( 12, 'brisa muy débil', 'bueno' ),
			array( 20, 'brisa débil', 'bueno' ),
			array( 29, 'brisa moderada', 'bueno' ),
			array( 39, 'brisa fresca', 'moderado' ),
			array( 50, 'brisa fuerte', 'moderado' ),
			array( 62, 'viento fuerte', 'alerta' ),
			array( 75, 'temporal', 'critico' ),
		);
		foreach ( $escala as $e ) {
			if ( $kmh < $e[0] ) {
				return array( 'nombre' => $e[1], 'nivel' => $e[2] );
			}
		}
		return array( 'nombre' => 'temporal fuerte o superior', 'nivel' => 'critico' );
	}

	/**
	 * Estado de un río respecto a su mediana del periodo consultado.
	 *
	 * @param float $ratio Caudal actual / mediana.
	 * @return array { nombre, nivel }
	 */
	public static function caudal( $ratio ) {
		$ratio = (float) $ratio;
		if ( $ratio < 0.5 ) {
			return array( 'nombre' => 'muy por debajo de lo habitual', 'nivel' => 'alerta' );
		}
		if ( $ratio < 0.8 ) {
			return array( 'nombre' => 'por debajo de lo habitual', 'nivel' => 'moderado' );
		}
		if ( $ratio <= 1.25 ) {
			return array( 'nombre' => 'dentro de lo habitual', 'nivel' => 'bueno' );
		}
		if ( $ratio <= 2 ) {
			return array( 'nombre' => 'por encima de lo habitual', 'nivel' => 'moderado' );
		}
		return array( 'nombre' => 'muy por encima de lo habitual (posible creciente)', 'nivel' => 'alerta' );
	}

	/**
	 * Etiqueta y color de un nivel genérico (para insignias del análisis).
	 * Los colores se acompañan siempre de texto e icono.
	 *
	 * @param string $nivel bueno | moderado | alerta | critico | info.
	 * @return array { etiqueta, color, icono }
	 */
	public static function nivel( $nivel ) {
		$mapa = array(
			'bueno'    => array( 'etiqueta' => 'Favorable', 'color' => '#0ca30c', 'icono' => '●' ),
			'moderado' => array( 'etiqueta' => 'Moderado', 'color' => '#fab219', 'icono' => '▲' ),
			'alerta'   => array( 'etiqueta' => 'Atención', 'color' => '#ec835a', 'icono' => '◆' ),
			'critico'  => array( 'etiqueta' => 'Crítico', 'color' => '#d03b3b', 'icono' => '■' ),
			'info'     => array( 'etiqueta' => 'Informativo', 'color' => '#2B93D2', 'icono' => 'ℹ' ),
		);
		return $mapa[ $nivel ] ?? $mapa['info'];
	}

	/**
	 * Descripción del código de tiempo WMO (Open-Meteo `weather_code`).
	 *
	 * @param int $code Código WMO 4677.
	 * @return array { nombre, grupo }
	 */
	public static function wmo( $code ) {
		$code  = (int) $code;
		$tabla = array(
			0  => array( 'Despejado', 'Despejado' ),
			1  => array( 'Mayormente despejado', 'Despejado' ),
			2  => array( 'Parcialmente nublado', 'Nublado' ),
			3  => array( 'Cubierto', 'Nublado' ),
			45 => array( 'Niebla', 'Niebla' ),
			48 => array( 'Niebla con escarcha', 'Niebla' ),
			51 => array( 'Llovizna ligera', 'Llovizna' ),
			53 => array( 'Llovizna moderada', 'Llovizna' ),
			55 => array( 'Llovizna densa', 'Llovizna' ),
			56 => array( 'Llovizna helada', 'Llovizna' ),
			57 => array( 'Llovizna helada densa', 'Llovizna' ),
			61 => array( 'Lluvia ligera', 'Lluvia' ),
			63 => array( 'Lluvia moderada', 'Lluvia' ),
			65 => array( 'Lluvia fuerte', 'Lluvia' ),
			66 => array( 'Lluvia helada', 'Lluvia' ),
			67 => array( 'Lluvia helada fuerte', 'Lluvia' ),
			71 => array( 'Nevada ligera', 'Nieve' ),
			73 => array( 'Nevada moderada', 'Nieve' ),
			75 => array( 'Nevada fuerte', 'Nieve' ),
			77 => array( 'Granizo fino', 'Nieve' ),
			80 => array( 'Chubascos ligeros', 'Chubascos' ),
			81 => array( 'Chubascos moderados', 'Chubascos' ),
			82 => array( 'Chubascos violentos', 'Chubascos' ),
			85 => array( 'Chubascos de nieve', 'Nieve' ),
			86 => array( 'Chubascos de nieve fuertes', 'Nieve' ),
			95 => array( 'Tormenta', 'Tormenta' ),
			96 => array( 'Tormenta con granizo', 'Tormenta' ),
			99 => array( 'Tormenta con granizo fuerte', 'Tormenta' ),
		);
		$fila  = $tabla[ $code ] ?? array( 'Sin dato', 'Sin dato' );
		return array(
			'nombre' => $fila[0],
			'grupo'  => $fila[1],
		);
	}
}
