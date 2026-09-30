<?php
/**
 * Estadística descriptiva y utilidades de redacción para los análisis
 * cuantitativo y cualitativo.
 *
 * Todo el cálculo es determinista y auditable: no se usan modelos opacos.
 * Los números se formatean en convención colombiana (coma decimal).
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Analisis {

	/**
	 * Estadísticos de una serie numérica (ignora nulos).
	 *
	 * @param array $valores Valores (pueden incluir null).
	 * @return array { n, media, mediana, min, max, desviacion, rango, suma, p10, p90, cv }
	 */
	public static function estadisticos( array $valores ) {
		$v = array_values( array_filter( array_map( array( __CLASS__, 'numero' ), $valores ), 'is_float' ) );
		$n = count( $v );
		if ( 0 === $n ) {
			return array(
				'n'          => 0,
				'media'      => null,
				'mediana'    => null,
				'min'        => null,
				'max'        => null,
				'desviacion' => null,
				'rango'      => null,
				'suma'       => null,
				'p10'        => null,
				'p90'        => null,
				'cv'         => null,
			);
		}
		sort( $v );
		$suma  = array_sum( $v );
		$media = $suma / $n;
		$var   = 0.0;
		foreach ( $v as $x ) {
			$var += ( $x - $media ) ** 2;
		}
		$desv = $n > 1 ? sqrt( $var / ( $n - 1 ) ) : 0.0;
		return array(
			'n'          => $n,
			'media'      => $media,
			'mediana'    => self::percentil( $v, 50 ),
			'min'        => $v[0],
			'max'        => $v[ $n - 1 ],
			'desviacion' => $desv,
			'rango'      => $v[ $n - 1 ] - $v[0],
			'suma'       => $suma,
			'p10'        => self::percentil( $v, 10 ),
			'p90'        => self::percentil( $v, 90 ),
			'cv'         => abs( $media ) > 1e-9 ? $desv / abs( $media ) : null,
		);
	}

	/**
	 * Percentil por interpolación lineal sobre valores ordenados.
	 *
	 * @param float[] $ordenados Valores ordenados.
	 * @param float   $p         Percentil 0–100.
	 * @return float|null
	 */
	public static function percentil( array $ordenados, $p ) {
		$n = count( $ordenados );
		if ( 0 === $n ) {
			return null;
		}
		$pos = ( $n - 1 ) * $p / 100;
		$i   = (int) floor( $pos );
		$f   = $pos - $i;
		return $i + 1 < $n ? $ordenados[ $i ] + $f * ( $ordenados[ $i + 1 ] - $ordenados[ $i ] ) : $ordenados[ $i ];
	}

	/**
	 * Tendencia lineal por mínimos cuadrados sobre el índice (pasos iguales).
	 *
	 * @param array $valores Serie ordenada en el tiempo.
	 * @return array { pendiente (unidades por paso), r2, cambio_total }
	 */
	public static function tendencia( array $valores ) {
		$pts = array();
		foreach ( array_values( $valores ) as $i => $y ) {
			$y = self::numero( $y );
			if ( is_float( $y ) ) {
				$pts[] = array( (float) $i, $y );
			}
		}
		$n = count( $pts );
		if ( $n < 3 ) {
			return array(
				'pendiente'    => 0.0,
				'r2'           => 0.0,
				'cambio_total' => 0.0,
			);
		}
		$sx  = 0.0;
		$sy  = 0.0;
		$sxx = 0.0;
		$sxy = 0.0;
		foreach ( $pts as $p ) {
			$sx  += $p[0];
			$sy  += $p[1];
			$sxx += $p[0] * $p[0];
			$sxy += $p[0] * $p[1];
		}
		$den = $n * $sxx - $sx * $sx;
		if ( abs( $den ) < 1e-12 ) {
			return array(
				'pendiente'    => 0.0,
				'r2'           => 0.0,
				'cambio_total' => 0.0,
			);
		}
		$b     = ( $n * $sxy - $sx * $sy ) / $den;
		$a     = ( $sy - $b * $sx ) / $n;
		$media = $sy / $n;
		$sst   = 0.0;
		$sse   = 0.0;
		foreach ( $pts as $p ) {
			$sst += ( $p[1] - $media ) ** 2;
			$sse += ( $p[1] - ( $a + $b * $p[0] ) ) ** 2;
		}
		return array(
			'pendiente'    => $b,
			'r2'           => $sst > 1e-12 ? max( 0.0, 1 - $sse / $sst ) : 0.0,
			'cambio_total' => $b * ( $pts[ $n - 1 ][0] - $pts[0][0] ),
		);
	}

	/**
	 * Variación porcentual entre dos valores.
	 *
	 * @param float $desde Valor inicial.
	 * @param float $hasta Valor final.
	 * @return float|null
	 */
	public static function variacion_pct( $desde, $hasta ) {
		$desde = self::numero( $desde );
		$hasta = self::numero( $hasta );
		if ( ! is_float( $desde ) || ! is_float( $hasta ) || abs( $desde ) < 1e-9 ) {
			return null;
		}
		return ( $hasta - $desde ) / abs( $desde ) * 100;
	}

	/**
	 * Fila con el valor máximo o mínimo de un campo.
	 *
	 * @param array  $filas Filas.
	 * @param string $campo Campo numérico.
	 * @param bool   $max   true = máximo.
	 * @return array|null
	 */
	public static function extremo( array $filas, $campo, $max = true ) {
		$mejor = null;
		foreach ( $filas as $f ) {
			$v = self::numero( $f[ $campo ] ?? null );
			if ( ! is_float( $v ) ) {
				continue;
			}
			if ( null === $mejor || ( $max ? $v > $mejor[ $campo ] : $v < $mejor[ $campo ] ) ) {
				$mejor           = $f;
				$mejor[ $campo ] = $v;
			}
		}
		return $mejor;
	}

	/**
	 * Suma por grupo.
	 *
	 * @param array  $filas  Filas.
	 * @param string $grupo  Campo de agrupación.
	 * @param string $valor  Campo a sumar ('' = contar filas).
	 * @return array grupo => total (ordenado desc).
	 */
	public static function agrupar( array $filas, $grupo, $valor = '' ) {
		$out = array();
		foreach ( $filas as $f ) {
			$g = (string) ( $f[ $grupo ] ?? '' );
			if ( '' === $g ) {
				continue;
			}
			$out[ $g ] = ( $out[ $g ] ?? 0 ) + ( '' === $valor ? 1 : (float) ( $f[ $valor ] ?? 0 ) );
		}
		arsort( $out );
		return $out;
	}

	/**
	 * Convierte a float o devuelve null.
	 *
	 * @param mixed $v Valor.
	 * @return float|null
	 */
	public static function numero( $v ) {
		if ( null === $v || '' === $v || is_bool( $v ) || ! is_numeric( $v ) ) {
			return null;
		}
		$f = (float) $v;
		return is_finite( $f ) ? $f : null;
	}

	/* ------------------------------------------------------------------
	 * Redacción
	 * ---------------------------------------------------------------- */

	/**
	 * Número con coma decimal y punto de miles.
	 *
	 * @param float|null $v   Valor.
	 * @param int        $dec Decimales.
	 * @return string
	 */
	public static function num( $v, $dec = 1 ) {
		if ( null === $v || ! is_numeric( $v ) ) {
			return '—';
		}
		$s = number_format( (float) $v, $dec, ',', '.' );
		return '-0' === $s || ( $dec > 0 && '-0,' . str_repeat( '0', $dec ) === $s ) ? ltrim( $s, '-' ) : $s;
	}

	/**
	 * Número con unidad: "18,4 °C".
	 *
	 * @param float|null $v      Valor.
	 * @param string     $unidad Unidad.
	 * @param int        $dec    Decimales.
	 * @return string
	 */
	public static function con_unidad( $v, $unidad, $dec = 1 ) {
		$n = self::num( $v, $dec );
		return '—' === $n ? $n : trim( $n . ' ' . $unidad );
	}

	/**
	 * Porcentaje con signo: "+12,3 %".
	 *
	 * @param float|null $v   Valor.
	 * @param int        $dec Decimales.
	 * @return string
	 */
	public static function pct( $v, $dec = 1 ) {
		if ( null === $v ) {
			return '—';
		}
		return ( $v > 0 ? '+' : '' ) . self::num( $v, $dec ) . ' %';
	}

	/**
	 * Describe una tendencia en palabras.
	 *
	 * @param float  $pendiente Unidades por paso.
	 * @param float  $escala    Magnitud de referencia (p. ej. desviación) para decidir si es "estable".
	 * @param string $unidad    Unidad.
	 * @param string $paso      Nombre del paso ("día", "hora").
	 * @return string
	 */
	public static function frase_tendencia( $pendiente, $escala, $unidad, $paso ) {
		$umbral = max( 1e-6, 0.05 * abs( (float) $escala ) );
		if ( abs( $pendiente ) < $umbral ) {
			return 'estable';
		}
		$dir = $pendiente > 0 ? 'ascendente' : 'descendente';
		return sprintf( '%s (%s%s por %s)', $dir, $pendiente > 0 ? '+' : '−', self::con_unidad( abs( $pendiente ), $unidad, 2 ), $paso );
	}

	/**
	 * Fecha legible en español a partir de ISO (Y-m-d o Y-m-d\TH:i).
	 *
	 * @param string $iso  Fecha.
	 * @param bool   $hora Incluir hora.
	 * @return string
	 */
	public static function fecha( $iso, $hora = false ) {
		$t = strtotime( (string) $iso );
		if ( ! $t ) {
			return (string) $iso;
		}
		$meses = array( 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic' );
		$dias  = array( 'dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb' );
		$s     = $dias[ (int) gmdate( 'w', $t ) ] . ' ' . gmdate( 'j', $t ) . ' ' . $meses[ (int) gmdate( 'n', $t ) - 1 ];
		return $hora ? $s . ' ' . gmdate( 'H:i', $t ) : $s;
	}
}
