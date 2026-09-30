<?php
/**
 * NASA FIRMS — focos de calor (incendios activos) detectados por los
 * sensores VIIRS y MODIS.
 *
 * Sin clave se usa el CSV público de Sudamérica (≈ 1 MB, actualizado con
 * cada paso del satélite) y se recorta al polígono de Nariño. Con una
 * MAP_KEY gratuita se usa la API por área, que descarga solo la caja del
 * departamento. La clave nunca sale del servidor ni aparece en los logs.
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Fuente_Firms extends SAN_Fuente {

	const HOST = 'https://firms.modaps.eosdis.nasa.gov/';

	/** Sensores: archivo público / fuente de la API por área. */
	const SENSORES = array(
		'noaa20' => array(
			'nombre'  => 'VIIRS NOAA-20',
			'publico' => 'data/active_fire/noaa-20-viirs-c2/csv/J1_VIIRS_C2_South_America_%s.csv',
			'api'     => 'VIIRS_NOAA20_NRT',
		),
		'noaa21' => array(
			'nombre'  => 'VIIRS NOAA-21',
			'publico' => 'data/active_fire/noaa-21-viirs-c2/csv/J2_VIIRS_C2_South_America_%s.csv',
			'api'     => 'VIIRS_NOAA21_NRT',
		),
		'snpp'   => array(
			'nombre'  => 'VIIRS Suomi NPP',
			'publico' => 'data/active_fire/suomi-npp-viirs-c2/csv/SUOMI_VIIRS_C2_South_America_%s.csv',
			'api'     => 'VIIRS_SNPP_NRT',
		),
		'modis'  => array(
			'nombre'  => 'MODIS (Terra/Aqua)',
			'publico' => 'data/active_fire/modis-c6.1/csv/MODIS_C6_1_South_America_%s.csv',
			'api'     => 'MODIS_NRT',
		),
	);

	/** @return string */
	public function id() {
		return 'firms';
	}

	/** @return string */
	public function nombre() {
		return 'NASA FIRMS · Focos de calor';
	}

	/** @return string */
	public function nombre_corto() {
		return 'Incendios';
	}

	/** @return string */
	public function categoria() {
		return 'incendios';
	}

	/** @return string */
	public function descripcion() {
		return 'Focos de calor (posibles incendios o quemas) detectados por satélite en Nariño, con su potencia radiativa (FRP), confianza y municipio.';
	}

	/** @return string[] */
	public function hosts() {
		return array( 'firms.modaps.eosdis.nasa.gov' );
	}

	/** @return string */
	public function atribucion() {
		return 'NASA FIRMS (Fire Information for Resource Management System), LANCE / EOSDIS';
	}

	/** @return string */
	public function licencia() {
		return 'Datos abiertos NASA (con cita)';
	}

	/** @return string */
	public function url_doc() {
		return 'https://firms.modaps.eosdis.nasa.gov/api/area/';
	}

	/** @return string */
	public function url_clave() {
		return 'https://firms.modaps.eosdis.nasa.gov/api/map_key/';
	}

	/** @return string */
	public function frecuencia() {
		return 'Cada paso de satélite (≈ 3 h de latencia)';
	}

	/**
	 * La clave es opcional: el CSV público funciona sin ella.
	 *
	 * @return bool
	 */
	public function requiere_clave() {
		return false;
	}

	/**
	 * Admite MAP_KEY opcional (se guarda cifrada).
	 *
	 * @return bool
	 */
	public function admite_clave() {
		return true;
	}

	/** @return array */
	public function config_defecto() {
		$c            = parent::config_defecto();
		$c['ttl']     = 60;
		$c['timeout'] = 30;
		return $c;
	}

	/** @return array */
	public function campos_params() {
		$op = array();
		foreach ( self::SENSORES as $k => $s ) {
			$op[ $k ] = $s['nombre'];
		}
		return array(
			'sensor'  => array(
				'etiqueta' => 'Sensor',
				'tipo'     => 'select',
				'opciones' => $op,
				'defecto'  => 'noaa20',
			),
			'ventana' => array(
				'etiqueta' => 'Ventana',
				'tipo'     => 'select',
				'opciones' => array(
					'24h' => 'Últimas 24 horas',
					'7d'  => 'Últimos 7 días',
				),
				'defecto'  => '7d',
			),
		);
	}

	/**
	 * Focos de calor dentro de Nariño.
	 *
	 * @return array 'datos' => filas.
	 */
	public function focos() {
		$sensor  = (string) $this->param( 'sensor', 'noaa20' );
		$ventana = (string) $this->param( 'ventana', '7d' );
		$s       = self::SENSORES[ $sensor ] ?? self::SENSORES['noaa20'];
		$clave   = (string) $this->config()['clave'];
		$b = SAN_Municipios::BBOX;
		if ( '' !== $clave ) {
			$url = self::HOST . 'api/area/csv/' . rawurlencode( $clave ) . '/' . $s['api'] . '/' . $b['oeste'] . ',' . $b['sur'] . ',' . $b['este'] . ',' . $b['norte'] . '/' . ( '24h' === $ventana ? 1 : 5 );
		} else {
			$url = self::HOST . sprintf( $s['publico'], $ventana );
		}
		return $this->get_procesado(
			'focos:' . $sensor . ':' . $ventana . ':' . ( '' !== $clave ? 'api' : 'pub' ),
			$url,
			function ( $csv ) use ( $s ) {
				if ( ! is_string( $csv ) || false === strpos( $csv, 'latitude' ) ) {
					return null;
				}
				$out = array();
				foreach ( SAN_Fuente::csv_a_filas( $csv ) as $f ) {
					$lat = (float) ( $f['latitude'] ?? 0 );
					$lon = (float) ( $f['longitude'] ?? 0 );
					$div = SAN_Geo::municipio_de( $lat, $lon );
					if ( '' === $div ) {
						continue;
					}
					$hhmm  = str_pad( (string) ( $f['acq_time'] ?? '0' ), 4, '0', STR_PAD_LEFT );
					$out[] = array(
						'fecha'      => (string) ( $f['acq_date'] ?? '' ),
						'hora_utc'   => substr( $hhmm, 0, 2 ) . ':' . substr( $hhmm, 2, 2 ),
						'lat'        => round( $lat, 4 ),
						'lon'        => round( $lon, 4 ),
						'frp'        => isset( $f['frp'] ) ? (float) $f['frp'] : null,
						'confianza'  => (string) ( $f['confidence'] ?? '' ),
						'dia_noche'  => 'N' === ( $f['daynight'] ?? '' ) ? 'Noche' : 'Día',
						'satelite'   => $s['nombre'],
						'divipola'   => $div,
						'municipio'  => SAN_Municipios::nombre( $div ),
						'subregion'  => SAN_Municipios::por_divipola( $div )['subregion'] ?? '',
					);
				}
				return $out;
			},
			array( 'formato' => 'texto' )
		);
	}

	/** @return array */
	public function probar() {
		$h  = SAN_Http::get( $this->id(), self::HOST . sprintf( self::SENSORES['noaa20']['publico'], '24h' ), array( 'formato' => 'texto', 'timeout' => (int) $this->config()['timeout'] ) );
		$ok = $h['ok'] && is_string( $h['datos'] ) && false !== strpos( $h['datos'], 'latitude' );
		$n  = 0;
		$ul = '';
		if ( $ok ) {
			foreach ( SAN_Fuente::csv_a_filas( $h['datos'] ) as $f ) {
				++$n;
				$t = ( $f['acq_date'] ?? '' ) . ' ' . ( $f['acq_time'] ?? '' );
				if ( $t > $ul ) {
					$ul = $t;
				}
			}
		}
		return $this->resultado_prueba(
			$h,
			$ok,
			$ok ? sprintf( '%s focos en Sudamérica (24 h, NOAA-20).', SAN_Analisis::num( $n, 0 ) ) : 'El CSV no tiene la cabecera esperada.',
			$ul ? $ul . ' UTC' : '',
			array()
		);
	}
}
