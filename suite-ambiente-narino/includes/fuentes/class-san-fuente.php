<?php
/**
 * Clase base de una fuente de datos (API externa).
 *
 * Cada fuente declara sus metadatos (nombre, categoría, licencia, hosts
 * permitidos, frecuencia de actualización), sus parámetros configurables,
 * los recursos que publica como datos abiertos y cómo se prueba su
 * funcionamiento. La consulta pasa siempre por la caché con respaldo
 * "stale-if-error".
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

abstract class SAN_Fuente {

	/** @return string Identificador único ([a-z0-9_]). */
	abstract public function id();

	/** @return string Nombre legible. */
	abstract public function nombre();

	/** @return string Categoría temática (clima, aire, agua, oceano, sismos, eventos, radiacion, biodiversidad, incendios, ideam). */
	abstract public function categoria();

	/** @return string Descripción corta de qué aporta la fuente. */
	abstract public function descripcion();

	/** @return string[] Hosts HTTPS permitidos. */
	abstract public function hosts();

	/** @return string Texto de atribución obligatorio. */
	abstract public function atribucion();

	/** @return string Licencia de los datos. */
	abstract public function licencia();

	/** @return string URL de la documentación oficial. */
	abstract public function url_doc();

	/** @return string Frecuencia de actualización declarada por la fuente. */
	abstract public function frecuencia();

	/**
	 * Prueba de funcionamiento: petición mínima y verificación de forma.
	 *
	 * @return array { ok, codigo, ms, mensaje, frescura (ISO|''), muestra (array) }
	 */
	abstract public function probar();

	/**
	 * Nombre corto para pestañas.
	 *
	 * @return string
	 */
	public function nombre_corto() {
		return $this->nombre();
	}

	/**
	 * ¿Necesita clave de API?
	 *
	 * @return bool
	 */
	public function requiere_clave() {
		return false;
	}

	/**
	 * ¿Acepta una clave de API opcional? (por defecto, solo si la requiere).
	 *
	 * @return bool
	 */
	public function admite_clave() {
		return $this->requiere_clave();
	}

	/**
	 * Textos del campo de clave en el panel.
	 *
	 * @return array { etiqueta, enlace, ayuda }
	 */
	public function texto_clave() {
		return array(
			'etiqueta' => $this->requiere_clave() ? 'Clave de API' : 'Clave de API (opcional)',
			'enlace'   => 'Obtener una clave gratuita',
			'ayuda'    => '',
		);
	}

	/**
	 * URL donde se obtiene la clave (si aplica).
	 *
	 * @return string
	 */
	public function url_clave() {
		return '';
	}

	/**
	 * Configuración por defecto.
	 *
	 * @return array
	 */
	public function config_defecto() {
		return array(
			'activa'  => ! $this->requiere_clave(),
			'ttl'     => 60,
			'timeout' => 15,
			'clave'   => '',
			'params'  => $this->params_defecto(),
		);
	}

	/**
	 * Definición de parámetros configurables en el panel.
	 *
	 * @return array clave => { etiqueta, tipo (numero|texto|select), min, max, opciones, ayuda, defecto }
	 */
	public function campos_params() {
		return array();
	}

	/**
	 * Valores por defecto de los parámetros.
	 *
	 * @return array
	 */
	public function params_defecto() {
		$out = array();
		foreach ( $this->campos_params() as $k => $c ) {
			$out[ $k ] = $c['defecto'] ?? '';
		}
		return $out;
	}

	/**
	 * Sanea los parámetros según su definición.
	 *
	 * @param array $crudos Valores crudos.
	 * @return array
	 */
	public function sanear_params( array $crudos ) {
		$out = array();
		foreach ( $this->campos_params() as $k => $c ) {
			$v = $crudos[ $k ] ?? ( $c['defecto'] ?? '' );
			switch ( $c['tipo'] ) {
				case 'numero':
					$v = is_numeric( $v ) ? $v + 0 : $c['defecto'];
					if ( isset( $c['min'] ) ) {
						$v = max( $c['min'], $v );
					}
					if ( isset( $c['max'] ) ) {
						$v = min( $c['max'], $v );
					}
					break;
				case 'select':
					$v = array_key_exists( (string) $v, $c['opciones'] ) ? (string) $v : $c['defecto'];
					break;
				default:
					$v = sanitize_text_field( wp_unslash( (string) $v ) );
			}
			$out[ $k ] = $v;
		}
		return $out;
	}

	/**
	 * Recursos que la fuente publica como datos abiertos.
	 *
	 * @return array id => { nombre, descripcion, campos: {campo: descripcion} }
	 */
	public function recursos() {
		return array();
	}

	/**
	 * Obtiene un recurso normalizado (filas planas) para datos abiertos.
	 *
	 * @param string $recurso Id del recurso.
	 * @param array  $params  Parámetros saneados (municipio, etc.).
	 * @return array { ok, filas, actualizado, vencido, error }
	 */
	public function recurso( $recurso, array $params ) {
		return array(
			'ok'          => false,
			'filas'       => array(),
			'actualizado' => '',
			'vencido'     => false,
			'error'       => 'Recurso no disponible',
		);
	}

	/**
	 * Configuración efectiva (incluye la clave descifrada: solo servidor).
	 *
	 * @return array
	 */
	public function config() {
		return SAN_Ajustes::fuente( $this->id() );
	}

	/**
	 * ¿Está activa y, si requiere clave, tiene una?
	 *
	 * @return bool
	 */
	public function disponible() {
		$c = $this->config();
		return ! empty( $c['activa'] ) && ( ! $this->requiere_clave() || '' !== (string) $c['clave'] );
	}

	/**
	 * Parámetro configurado.
	 *
	 * @param string $clave   Nombre.
	 * @param mixed  $defecto Por defecto.
	 * @return mixed
	 */
	public function param( $clave, $defecto = null ) {
		$c = $this->config();
		return $c['params'][ $clave ] ?? $defecto;
	}

	/**
	 * GET con caché y respaldo.
	 *
	 * @param string   $clave   Clave de caché (sin prefijo).
	 * @param string   $url     URL.
	 * @param array    $opc     Opciones para SAN_Http::get (formato, headers).
	 * @param int|null $ttl_min TTL en minutos (null = el de la fuente).
	 * @return array { ok, datos, actualizado, vencido, error, desde_cache }
	 */
	protected function get_cacheado( $clave, $url, array $opc = array(), $ttl_min = null ) {
		$cfg   = $this->config();
		$clave = $this->id() . ':' . $clave;

		if ( ! $this->disponible() ) {
			$viejo = SAN_Cache::get( $clave, true );
			return $this->respuesta( (bool) $viejo, $viejo ? $viejo['valor'] : null, $viejo ? $viejo['creado'] : '', (bool) $viejo, $this->requiere_clave() && empty( $cfg['clave'] ) ? 'Fuente sin clave de API configurada' : 'Fuente desactivada', true );
		}

		$fresco = SAN_Cache::get( $clave );
		if ( $fresco ) {
			return $this->respuesta( true, $fresco['valor'], $fresco['creado'], false, '', true );
		}

		$opc['timeout'] = $opc['timeout'] ?? (int) $cfg['timeout'];
		$r              = SAN_Http::get( $this->id(), $url, $opc );
		if ( $r['ok'] ) {
			$ttl = 60 * (int) ( null === $ttl_min ? $cfg['ttl'] : $ttl_min );
			SAN_Cache::set( $clave, $r['datos'], $ttl, $this->id() );
			return $this->respuesta( true, $r['datos'], gmdate( 'Y-m-d H:i:s' ), false, '', false );
		}

		$viejo = SAN_Cache::get( $clave, true );
		if ( $viejo ) {
			SAN_Logger::advertencia( $this->id(), 'respaldo', 'API no disponible; se sirve la última copia (' . $viejo['creado'] . ' UTC).', array( 'error' => $r['error'] ) );
			return $this->respuesta( true, $viejo['valor'], $viejo['creado'], true, $r['error'], true );
		}
		return $this->respuesta( false, null, '', false, $r['error'], false );
	}

	/**
	 * GET que guarda en caché el resultado ya procesado (útil cuando la
	 * respuesta cruda es grande —CSV continentales, catálogos nacionales— y
	 * solo interesa el recorte de Nariño).
	 *
	 * @param string   $clave    Clave de caché.
	 * @param string   $url      URL.
	 * @param callable $procesar function( mixed $crudo ): mixed.
	 * @param array    $opc      Opciones para SAN_Http::get.
	 * @param int|null $ttl_min  TTL en minutos.
	 * @return array
	 */
	protected function get_procesado( $clave, $url, callable $procesar, array $opc = array(), $ttl_min = null ) {
		$cfg   = $this->config();
		$clave = $this->id() . ':' . $clave;

		if ( ! $this->disponible() ) {
			$viejo = SAN_Cache::get( $clave, true );
			return $this->respuesta( (bool) $viejo, $viejo ? $viejo['valor'] : null, $viejo ? $viejo['creado'] : '', (bool) $viejo, 'Fuente desactivada', true );
		}
		$fresco = SAN_Cache::get( $clave );
		if ( $fresco ) {
			return $this->respuesta( true, $fresco['valor'], $fresco['creado'], false, '', true );
		}

		$opc['timeout'] = $opc['timeout'] ?? (int) $cfg['timeout'];
		$r              = SAN_Http::get( $this->id(), $url, $opc );
		if ( $r['ok'] ) {
			$datos = call_user_func( $procesar, $r['datos'] );
			if ( null !== $datos ) {
				SAN_Cache::set( $clave, $datos, 60 * (int) ( null === $ttl_min ? $cfg['ttl'] : $ttl_min ), $this->id() );
				return $this->respuesta( true, $datos, gmdate( 'Y-m-d H:i:s' ), false, '', false );
			}
			$r['error'] = 'Respuesta con formato inesperado';
			SAN_Logger::error( $this->id(), 'formato', 'La respuesta no tiene el formato esperado.', array( 'url' => $url ) );
		}

		$viejo = SAN_Cache::get( $clave, true );
		if ( $viejo ) {
			SAN_Logger::advertencia( $this->id(), 'respaldo', 'API no disponible; se sirve la última copia (' . $viejo['creado'] . ' UTC).', array( 'error' => $r['error'] ) );
			return $this->respuesta( true, $viejo['valor'], $viejo['creado'], true, $r['error'], true );
		}
		return $this->respuesta( false, null, '', false, $r['error'], false );
	}

	/**
	 * Convierte CSV (con cabecera) en filas asociativas.
	 *
	 * @param string $csv Texto CSV.
	 * @return array[]
	 */
	public static function csv_a_filas( $csv ) {
		$lineas = preg_split( '/\r\n|\n|\r/', trim( (string) $csv ) );
		if ( ! $lineas || count( $lineas ) < 2 ) {
			return array();
		}
		$cab   = array_map( 'trim', str_getcsv( array_shift( $lineas ), ',', '"', '\\' ) );
		$filas = array();
		foreach ( $lineas as $l ) {
			if ( '' === trim( $l ) ) {
				continue;
			}
			$v = str_getcsv( $l, ',', '"', '\\' );
			if ( count( $v ) === count( $cab ) ) {
				$filas[] = array_combine( $cab, array_map( 'trim', $v ) );
			}
		}
		return $filas;
	}

	/**
	 * Estructura uniforme.
	 *
	 * @return array
	 */
	protected function respuesta( $ok, $datos, $actualizado, $vencido, $error, $desde_cache ) {
		return array(
			'ok'          => (bool) $ok,
			'datos'       => $datos,
			'actualizado' => (string) $actualizado,
			'vencido'     => (bool) $vencido,
			'error'       => (string) $error,
			'desde_cache' => (bool) $desde_cache,
		);
	}

	/**
	 * Resultado de prueba uniforme.
	 *
	 * @param array  $http     Resultado de SAN_Http::get.
	 * @param bool   $forma_ok ¿La respuesta tiene la forma esperada?
	 * @param string $mensaje  Mensaje.
	 * @param string $frescura Fecha del dato más reciente (ISO) si se conoce.
	 * @param array  $muestra  Muestra pequeña para el panel.
	 * @return array
	 */
	protected function resultado_prueba( array $http, $forma_ok, $mensaje, $frescura = '', array $muestra = array() ) {
		return array(
			'ok'       => $http['ok'] && $forma_ok,
			'codigo'   => $http['codigo'],
			'ms'       => $http['ms'],
			'bytes'    => $http['bytes'],
			'mensaje'  => $http['ok'] ? $mensaje : ( 'Fallo: ' . $http['error'] ),
			'frescura' => $frescura,
			'muestra'  => $muestra,
		);
	}

	/**
	 * Metadatos públicos (sin configuración sensible).
	 *
	 * @return array
	 */
	public function meta() {
		return array(
			'id'          => $this->id(),
			'nombre'      => $this->nombre(),
			'categoria'   => $this->categoria(),
			'descripcion' => $this->descripcion(),
			'atribucion'  => $this->atribucion(),
			'licencia'    => $this->licencia(),
			'url_doc'     => $this->url_doc(),
			'frecuencia'  => $this->frecuencia(),
			'clave'       => $this->requiere_clave(),
		);
	}
}
