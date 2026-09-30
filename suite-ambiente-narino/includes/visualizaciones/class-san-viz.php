<?php
/**
 * Utilidades comunes para las clases de visualizaciones (SAN_Viz_*).
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

abstract class SAN_Viz {

	/**
	 * Resultado correcto de un procesador.
	 *
	 * @param array $datos    Filas.
	 * @param array $config   Configuración del gráfico.
	 * @param array $analisis Análisis.
	 * @param array $resp     Respuesta de la fuente (actualizado, vencido).
	 * @param string $nota    Nota al pie.
	 * @return array
	 */
	protected static function ok( array $datos, array $config, array $analisis, array $resp, $nota = '' ) {
		return array(
			'ok'          => true,
			'datos'       => $datos,
			'config'      => $config,
			'analisis'    => $analisis,
			'actualizado' => $resp['actualizado'] ?? '',
			'vencido'     => ! empty( $resp['vencido'] ),
			'error'       => '',
			'nota'        => $nota,
		);
	}

	/**
	 * Fecha de hoy en Colombia (las APIs responden en America/Bogota).
	 *
	 * @return string Y-m-d
	 */
	protected static function hoy() {
		return ( new \DateTime( 'now', new \DateTimeZone( 'America/Bogota' ) ) )->format( 'Y-m-d' );
	}

	/**
	 * Cifra del análisis cuantitativo.
	 *
	 * @param string $etiqueta Etiqueta.
	 * @param string $valor    Valor formateado.
	 * @param string $detalle  Detalle opcional.
	 * @return array
	 */
	protected static function cifra( $etiqueta, $valor, $detalle = '' ) {
		return array(
			'etiqueta' => $etiqueta,
			'valor'    => $valor,
			'detalle'  => $detalle,
		);
	}

	/**
	 * Nombre legible del municipio.
	 *
	 * @param string $divipola Código.
	 * @return string
	 */
	protected static function muni( $divipola ) {
		return SAN_Municipios::nombre( $divipola );
	}

	/**
	 * Nivel más grave de una lista.
	 *
	 * @param string[] $niveles Niveles.
	 * @return string
	 */
	protected static function peor( array $niveles ) {
		$orden = array( 'info' => 0, 'bueno' => 1, 'moderado' => 2, 'alerta' => 3, 'critico' => 4 );
		$peor  = 'info';
		foreach ( $niveles as $n ) {
			if ( ( $orden[ $n ] ?? 0 ) > $orden[ $peor ] ) {
				$peor = $n;
			}
		}
		return $peor;
	}

	/**
	 * Agrupa en "Otros" las categorías más allá de las primeras N
	 * (regla: nunca más de 6 colores categóricos).
	 *
	 * @param array  $filas  Filas ordenadas desc por valor.
	 * @param string $campo  Campo categoría.
	 * @param string $valor  Campo valor.
	 * @param int    $n      Máximo de categorías (incluye "Otros").
	 * @return array
	 */
	protected static function plegar_otros( array $filas, $campo, $valor, $n = 6 ) {
		if ( count( $filas ) <= $n ) {
			return $filas;
		}
		$top   = array_slice( $filas, 0, $n - 1 );
		$resto = 0;
		foreach ( array_slice( $filas, $n - 1 ) as $f ) {
			$resto += (float) $f[ $valor ];
		}
		$top[] = array(
			$campo => 'Otros',
			$valor => $resto,
		);
		return $top;
	}
}
