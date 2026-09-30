<?php
/**
 * NASA POWER (Prediction Of Worldwide Energy Resources): series diarias
 * de radiación solar, temperatura, humedad, viento y precipitación
 * derivadas de satélite y reanálisis (MERRA-2, CERES). Útil para
 * agroclima y energía solar. Rezago: ~3 días (meteorología) y ~5 días
 * (radiación); los días sin dato vienen con −999.
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Fuente_Nasa_Power extends SAN_Fuente {

	const BASE = 'https://power.larc.nasa.gov/api/temporal/daily/point';

	/** Parámetros consultados. */
	const PARAMS = 'T2M,T2M_MAX,T2M_MIN,PRECTOTCORR,ALLSKY_SFC_SW_DWN,RH2M,WS2M';

	/** @return string */
	public function id() {
		return 'nasa_power';
	}

	/** @return string */
	public function nombre() {
		return 'NASA POWER · Radiación y agroclima';
	}

	/** @return string */
	public function nombre_corto() {
		return 'Radiación';
	}

	/** @return string */
	public function categoria() {
		return 'radiacion';
	}

	/** @return string */
	public function descripcion() {
		return 'Radiación solar en superficie, temperatura, humedad relativa, viento y precipitación diarios de los últimos meses para el municipio seleccionado.';
	}

	/** @return string[] */
	public function hosts() {
		return array( 'power.larc.nasa.gov' );
	}

	/** @return string */
	public function atribucion() {
		return 'NASA Langley Research Center, proyecto POWER (NASA Earth Science)';
	}

	/** @return string */
	public function licencia() {
		return 'Datos abiertos NASA (con cita)';
	}

	/** @return string */
	public function url_doc() {
		return 'https://power.larc.nasa.gov/docs/services/api/temporal/daily/';
	}

	/** @return string */
	public function frecuencia() {
		return 'Diaria (rezago de 3 a 5 días)';
	}

	/** @return array */
	public function config_defecto() {
		$c            = parent::config_defecto();
		$c['ttl']     = 720;
		$c['timeout'] = 30;
		return $c;
	}

	/** @return array */
	public function campos_params() {
		return array(
			'dias' => array(
				'etiqueta' => 'Días de historia',
				'tipo'     => 'numero',
				'min'      => 30,
				'max'      => 730,
				'defecto'  => 180,
			),
		);
	}

	/**
	 * Serie diaria de un municipio.
	 *
	 * @param string $divipola Código.
	 * @return array 'datos' => filas {fecha, T2M, …} sin días de relleno.
	 */
	public function serie( $divipola ) {
		$m = SAN_Municipios::por_divipola( $divipola );
		if ( ! $m ) {
			return $this->respuesta( false, null, '', false, 'Municipio desconocido', false );
		}
		$dias = (int) $this->param( 'dias', 180 );
		$url  = add_query_arg(
			array(
				'parameters' => self::PARAMS,
				'community'  => 'AG',
				'longitude'  => $m['lon'],
				'latitude'   => $m['lat'],
				'start'      => gmdate( 'Ymd', time() - $dias * DAY_IN_SECONDS ),
				'end'        => gmdate( 'Ymd' ),
				'format'     => 'JSON',
			),
			self::BASE
		);
		return $this->get_procesado(
			'serie:' . $divipola . ':' . $dias,
			$url,
			function ( $j ) {
				$par = $j['properties']['parameter'] ?? null;
				if ( ! is_array( $par ) || empty( $par['T2M'] ) ) {
					return null;
				}
				$out = array();
				foreach ( array_keys( $par['T2M'] ) as $f ) {
					$fila = array( 'fecha' => substr( $f, 0, 4 ) . '-' . substr( $f, 4, 2 ) . '-' . substr( $f, 6, 2 ) );
					$util = false;
					foreach ( $par as $k => $serie ) {
						$v          = $serie[ $f ] ?? null;
						$fila[ $k ] = ( null === $v || $v <= -999 ) ? null : $v;
						$util       = $util || null !== $fila[ $k ];
					}
					if ( $util ) {
						$out[] = $fila;
					}
				}
				return $out;
			}
		);
	}

	/** @return array */
	public function probar() {
		$url = add_query_arg(
			array(
				'parameters' => 'T2M,ALLSKY_SFC_SW_DWN',
				'community'  => 'AG',
				'longitude'  => '-77.2788',
				'latitude'   => '1.2124',
				'start'      => gmdate( 'Ymd', time() - 10 * DAY_IN_SECONDS ),
				'end'        => gmdate( 'Ymd' ),
				'format'     => 'JSON',
			),
			self::BASE
		);
		$h   = SAN_Http::get( $this->id(), $url, array( 'timeout' => (int) $this->config()['timeout'] ) );
		$t   = $h['datos']['properties']['parameter']['T2M'] ?? null;
		$ok  = $h['ok'] && is_array( $t );
		$ult = '';
		$val = null;
		foreach ( (array) $t as $f => $v ) {
			if ( $v > -999 ) {
				$ult = $f;
				$val = $v;
			}
		}
		return $this->resultado_prueba(
			$h,
			$ok,
			$ok ? sprintf( 'Pasto: T2M %s °C el %s.', $val ?? '—', $ult ) : 'Respuesta sin properties.parameter.T2M.',
			$ult,
			$ok ? array( 'T2M' => $t ) : array()
		);
	}
}
