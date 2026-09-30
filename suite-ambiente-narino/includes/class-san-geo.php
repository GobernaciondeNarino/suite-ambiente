<?php
/**
 * Utilidades geográficas: ubicar un punto en el municipio de Nariño que lo
 * contiene (punto en polígono sobre el GeoJSON del MGN) y distancias.
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Geo {

	/** @var array|null [ { divipola, bbox, poligonos } ] */
	private static $municipios = null;

	/**
	 * Carga los polígonos municipales con su caja envolvente.
	 *
	 * @return array
	 */
	private static function cargar() {
		if ( null !== self::$municipios ) {
			return self::$municipios;
		}
		self::$municipios = array();
		$geo              = json_decode( (string) file_get_contents( SAN_DIR . 'data/narino_municipios.geojson' ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		foreach ( (array) ( $geo['features'] ?? array() ) as $f ) {
			$tipo  = $f['geometry']['type'] ?? '';
			$coord = $f['geometry']['coordinates'] ?? array();
			$polis = 'Polygon' === $tipo ? array( $coord ) : ( 'MultiPolygon' === $tipo ? $coord : array() );
			$bbox  = array( 180, 90, -180, -90 );
			foreach ( $polis as $p ) {
				foreach ( $p[0] as $pt ) {
					$bbox = array( min( $bbox[0], $pt[0] ), min( $bbox[1], $pt[1] ), max( $bbox[2], $pt[0] ), max( $bbox[3], $pt[1] ) );
				}
			}
			self::$municipios[] = array(
				'divipola'  => (string) ( $f['properties']['MPIO_CDPMP'] ?? '' ),
				'bbox'      => $bbox,
				'poligonos' => $polis,
			);
		}
		return self::$municipios;
	}

	/**
	 * Municipio de Nariño que contiene el punto, o '' si está fuera.
	 *
	 * @param float $lat Latitud.
	 * @param float $lon Longitud.
	 * @return string DIVIPOLA.
	 */
	public static function municipio_de( $lat, $lon ) {
		$lat = (float) $lat;
		$lon = (float) $lon;
		if ( $lat < SAN_Municipios::BBOX['sur'] || $lat > SAN_Municipios::BBOX['norte'] || $lon < SAN_Municipios::BBOX['oeste'] || $lon > SAN_Municipios::BBOX['este'] ) {
			return '';
		}
		foreach ( self::cargar() as $m ) {
			$b = $m['bbox'];
			if ( $lon < $b[0] || $lon > $b[2] || $lat < $b[1] || $lat > $b[3] ) {
				continue;
			}
			foreach ( $m['poligonos'] as $poli ) {
				if ( self::en_poligono( $lon, $lat, $poli ) ) {
					return $m['divipola'];
				}
			}
		}
		return '';
	}

	/**
	 * Punto en polígono con huecos (ray casting).
	 *
	 * @param float $x    Longitud.
	 * @param float $y    Latitud.
	 * @param array $poli Anillos: [exterior, hueco1, …].
	 * @return bool
	 */
	public static function en_poligono( $x, $y, array $poli ) {
		$dentro = false;
		foreach ( $poli as $k => $anillo ) {
			$en = false;
			$n  = count( $anillo );
			for ( $i = 0, $j = $n - 1; $i < $n; $j = $i++ ) {
				$xi = $anillo[ $i ][0];
				$yi = $anillo[ $i ][1];
				$xj = $anillo[ $j ][0];
				$yj = $anillo[ $j ][1];
				if ( ( ( $yi > $y ) !== ( $yj > $y ) ) && ( $x < ( $xj - $xi ) * ( $y - $yi ) / ( ( $yj - $yi ) ?: 1e-12 ) + $xi ) ) {
					$en = ! $en;
				}
			}
			if ( 0 === $k ) {
				$dentro = $en;
			} elseif ( $en ) {
				return false; // Dentro de un hueco.
			}
		}
		return $dentro;
	}

	/**
	 * Distancia de gran círculo en km (haversine).
	 *
	 * @return float
	 */
	public static function distancia_km( $lat1, $lon1, $lat2, $lon2 ) {
		$r    = 6371.0;
		$dlat = deg2rad( $lat2 - $lat1 );
		$dlon = deg2rad( $lon2 - $lon1 );
		$a    = sin( $dlat / 2 ) ** 2 + cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) * sin( $dlon / 2 ) ** 2;
		return 2 * $r * asin( min( 1, sqrt( $a ) ) );
	}

	/**
	 * ¿El punto está en la caja de Nariño ampliada en `$margen` grados?
	 *
	 * @return bool
	 */
	public static function en_region( $lat, $lon, $margen = 0.0 ) {
		$b = SAN_Municipios::BBOX;
		return $lat >= $b['sur'] - $margen && $lat <= $b['norte'] + $margen && $lon >= $b['oeste'] - $margen && $lon <= $b['este'] + $margen;
	}
}
