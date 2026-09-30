<?php
/**
 * IDEAM — observaciones de la red de estaciones automáticas publicadas en
 * datos.gov.co (Socrata SODA): precipitación, temperatura del aire y nivel
 * de los ríos en Nariño.
 *
 * Detalles verificados (2026-09-30):
 *  - Se cargan una vez al día (≈ 06:15 UTC) con datos hasta la medianoche.
 *  - El campo `departamento` cambió de "NARIÑO" a "Nariño": se filtra con
 *    upper(departamento) para no perder datos.
 *  - Sin filtro de fecha las consultas tardan > 40 s: siempre se acota y se
 *    agrega por día en el servidor de Socrata (SoQL).
 *  - Son datos crudos sin validar (advertencia del propio IDEAM).
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Fuente_Ideam extends SAN_Fuente {

	const BASE = 'https://www.datos.gov.co/resource/';

	/** Conjuntos de datos por variable. */
	const DATASETS = array(
		'precipitacion' => 's54a-sgyg',
		'temperatura'   => 'sbwg-7ju4',
		'humedad'       => 'uext-mhny',
		'nivel'         => 'bdmn-sqnh',
	);

	/** @return string */
	public function id() {
		return 'ideam';
	}

	/** @return string */
	public function nombre() {
		return 'IDEAM · Estaciones automáticas (datos.gov.co)';
	}

	/** @return string */
	public function nombre_corto() {
		return 'IDEAM';
	}

	/** @return string */
	public function categoria() {
		return 'ideam';
	}

	/** @return string */
	public function descripcion() {
		return 'Mediciones reales de las estaciones automáticas del IDEAM en Nariño: lluvia acumulada, temperatura del aire y nivel de los ríos, agregadas por día y estación.';
	}

	/** @return string[] */
	public function hosts() {
		return array( 'www.datos.gov.co' );
	}

	/** @return string */
	public function atribucion() {
		return 'Instituto de Hidrología, Meteorología y Estudios Ambientales — IDEAM (datos.gov.co)';
	}

	/** @return string */
	public function licencia() {
		return 'CC BY-SA 4.0';
	}

	/** @return string */
	public function url_doc() {
		return 'https://www.datos.gov.co/Ambiente-y-Desarrollo-Sostenible/Precipitaci-n/s54a-sgyg';
	}

	/** @return string */
	public function frecuencia() {
		return 'Diaria (carga ≈ 01:15 hora de Colombia, datos hasta el día anterior)';
	}

	/** @return array */
	public function config_defecto() {
		$c            = parent::config_defecto();
		$c['ttl']     = 180;
		$c['timeout'] = 45;
		return $c;
	}

	/** @return array */
	public function campos_params() {
		return array(
			'dias'      => array(
				'etiqueta' => 'Días consultados',
				'tipo'     => 'numero',
				'min'      => 2,
				'max'      => 30,
				'defecto'  => 7,
			),
			'app_token' => array(
				'etiqueta' => 'App token de Socrata (opcional)',
				'tipo'     => 'texto',
				'defecto'  => '',
				'ayuda'    => 'Evita el límite compartido por IP. Se obtiene gratis en datos.gov.co.',
			),
		);
	}

	/**
	 * Consulta SoQL agregada.
	 *
	 * @param string $variable Clave de DATASETS.
	 * @param string $select   Expresiones de agregación (además de estación y día).
	 * @return array 'datos' => filas.
	 */
	public function diario( $variable, $select ) {
		$ds    = self::DATASETS[ $variable ] ?? '';
		$dias  = (int) $this->param( 'dias', 7 );
		$desde = gmdate( 'Y-m-d', time() - $dias * DAY_IN_SECONDS ) . 'T00:00:00';
		$campos = 'codigoestacion,nombreestacion,municipio,latitud,longitud';
		$query = array(
			'$select' => $campos . ',date_trunc_ymd(fechaobservacion) as dia,' . $select . ',count(*) as n,max(fechaobservacion) as ultima',
			'$where'  => "upper(departamento)='NARIÑO' AND fechaobservacion>'" . $desde . "'",
			'$group'  => $campos . ',dia',
			'$order'  => 'dia ASC',
			'$limit'  => '5000',
		);
		$url   = self::BASE . $ds . '.json?' . http_build_query( $query, '', '&', PHP_QUERY_RFC3986 );
		$opc   = array();
		$tok   = (string) $this->param( 'app_token', '' );
		if ( '' !== $tok ) {
			$opc['headers'] = array( 'X-App-Token' => $tok );
		}
		return $this->get_procesado(
			$variable . ':' . $dias,
			$url,
			function ( $filas ) {
				if ( ! is_array( $filas ) ) {
					return null;
				}
				$out = array();
				foreach ( $filas as $f ) {
					$nombre = preg_replace( '/\s*\[\d+\]\s*$/', '', (string) ( $f['nombreestacion'] ?? '' ) );
					$nombre = trim( preg_replace( '/[\s-]*(AUT)?[\s-]*$/i', '', $nombre ) );
					$fila   = array(
						'estacion'  => mb_convert_case( mb_strtolower( $nombre, 'UTF-8' ), MB_CASE_TITLE, 'UTF-8' ),
						'codigo'    => (string) ( $f['codigoestacion'] ?? '' ),
						'municipio' => (string) ( $f['municipio'] ?? '' ),
						'lat'       => isset( $f['latitud'] ) ? (float) $f['latitud'] : null,
						'lon'       => isset( $f['longitud'] ) ? (float) $f['longitud'] : null,
						'fecha'     => substr( (string) ( $f['dia'] ?? '' ), 0, 10 ),
						'n'         => (int) ( $f['n'] ?? 0 ),
						'ultima'    => str_replace( 'T', ' ', substr( (string) ( $f['ultima'] ?? '' ), 0, 16 ) ),
					);
					foreach ( $f as $k => $v ) {
						if ( ! isset( $fila[ $k ] ) && ! in_array( $k, array( 'codigoestacion', 'nombreestacion', 'latitud', 'longitud', 'dia' ), true ) ) {
							$fila[ $k ] = is_numeric( $v ) ? round( (float) $v, 2 ) : $v;
						}
					}
					$out[] = $fila;
				}
				return $out;
			},
			$opc
		);
	}

	/** @return array Precipitación diaria por estación (mm). */
	public function precipitacion() {
		return $this->diario( 'precipitacion', 'sum(valorobservado::number) as total' );
	}

	/** @return array Temperatura diaria por estación (°C). */
	public function temperatura() {
		return $this->diario( 'temperatura', 'avg(valorobservado::number) as media,min(valorobservado::number) as minima,max(valorobservado::number) as maxima' );
	}

	/** @return array Nivel de río diario por estación (m). */
	public function nivel() {
		return $this->diario( 'nivel', 'avg(valorobservado::number) as media,max(valorobservado::number) as maxima,min(valorobservado::number) as minima' );
	}

	/** @return array */
	public function probar() {
		$query = array(
			'$select' => 'max(fechaobservacion) as ultima,count(*) as n',
			'$where'  => "upper(departamento)='NARIÑO' AND fechaobservacion>'" . gmdate( 'Y-m-d', time() - 3 * DAY_IN_SECONDS ) . "T00:00:00'",
		);
		$h     = SAN_Http::get( $this->id(), self::BASE . self::DATASETS['precipitacion'] . '.json?' . http_build_query( $query, '', '&', PHP_QUERY_RFC3986 ), array( 'timeout' => (int) $this->config()['timeout'] ) );
		$f     = is_array( $h['datos'] ) && isset( $h['datos'][0] ) ? $h['datos'][0] : array();
		$ok    = $h['ok'] && ! empty( $f['ultima'] );
		return $this->resultado_prueba(
			$h,
			$ok,
			$ok ? sprintf( '%s observaciones de precipitación en Nariño en 3 días.', SAN_Analisis::num( $f['n'] ?? 0, 0 ) ) : 'Sin observaciones recientes de Nariño (¿cambió el esquema o la carga diaria se retrasó?).',
			(string) ( $f['ultima'] ?? '' ),
			$f
		);
	}
}
