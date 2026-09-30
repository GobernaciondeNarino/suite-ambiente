<?php
/**
 * Visualizaciones de biodiversidad (GBIF e iNaturalist).
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Viz_Biodiversidad extends SAN_Viz {

	/** Colores de la Lista Roja de la UICN (convención internacional). */
	const COLOR_UICN = array(
		'EX' => '#000000',
		'EW' => '#542344',
		'CR' => '#D81E05',
		'EN' => '#FC7F3F',
		'VU' => '#F9E814',
		'NT' => '#CCE226',
		'LC' => '#60C659',
		'DD' => '#D1D1C6',
	);

	/**
	 * Definiciones.
	 *
	 * @return array
	 */
	public static function definiciones() {
		$c = __CLASS__;
		return array(
			'bio_reinos'     => array(
				'titulo'      => 'Registros de biodiversidad por reino',
				'fuente'      => 'gbif',
				'subgrupo'    => 'Composición',
				'descripcion' => 'Número de registros de especies en Nariño publicados en GBIF según el reino biológico.',
				'lectura'     => 'Mide el esfuerzo de documentación, no la abundancia real de especies: grupos muy estudiados (aves, plantas) tienen más registros.',
				'tipo'        => 'treemap',
				'tipos'       => array( 'treemap', 'donut', 'pie', 'bar', 'pack' ),
				'procesador'  => array( $c, 'reinos' ),
			),
			'bio_clases'     => array(
				'titulo'      => 'Clases taxonómicas más registradas',
				'fuente'      => 'gbif',
				'subgrupo'    => 'Composición',
				'descripcion' => 'Las 12 clases con más registros de biodiversidad en Nariño.',
				'lectura'     => 'Aves y plantas con flor dominan por la ciencia ciudadana y las colecciones de herbario.',
				'tipo'        => 'barh',
				'tipos'       => array( 'barh', 'bar', 'treemap' ),
				'procesador'  => array( $c, 'clases' ),
			),
			'bio_amenaza'    => array(
				'titulo'      => 'Registros por categoría de amenaza (UICN)',
				'fuente'      => 'gbif',
				'subgrupo'    => 'Conservación',
				'descripcion' => 'Registros de especies evaluadas por la Lista Roja de la UICN según su categoría de amenaza.',
				'lectura'     => 'CR, EN y VU son especies amenazadas; NT, casi amenazadas; LC, de preocupación menor. Los colores siguen la convención de la UICN.',
				'tipo'        => 'donut',
				'tipos'       => array( 'donut', 'pie', 'bar', 'barh' ),
				'procesador'  => array( $c, 'amenaza' ),
			),
			'bio_anual'      => array(
				'titulo'      => 'Registros de biodiversidad por año',
				'fuente'      => 'gbif',
				'subgrupo'    => 'Tendencias',
				'descripcion' => 'Número de registros por año de observación en Nariño (últimos 30 años).',
				'lectura'     => 'Los picos corresponden a grandes expediciones o jornadas de ciencia ciudadana; los últimos años crecen por iNaturalist y eBird.',
				'tipo'        => 'bar',
				'tipos'       => array( 'bar', 'line', 'area' ),
				'procesador'  => array( $c, 'anual' ),
			),
			'bio_mapa'       => array(
				'titulo'      => 'Registros de biodiversidad por municipio',
				'fuente'      => 'gbif',
				'subgrupo'    => 'Territorio',
				'descripcion' => 'Número de registros de biodiversidad georreferenciados en cada municipio (escala logarítmica).',
				'lectura'     => 'Los municipios claros son vacíos de información: prioridades para inventarios biológicos.',
				'tipo'        => 'mapa',
				'tipos'       => array( 'mapa', 'barh' ),
				'variable_mapa' => 'biodiversidad',
				'procesador'  => array( $c, 'mapa' ),
			),
			'inat_grupos'    => array(
				'titulo'      => 'Observaciones ciudadanas recientes por grupo',
				'fuente'      => 'inaturalist',
				'subgrupo'    => 'Ciencia ciudadana',
				'descripcion' => 'Observaciones de iNaturalist en Nariño durante los últimos días, por grupo biológico.',
				'lectura'     => 'Refleja lo que la ciudadanía fotografía y comparte: una ventana en tiempo casi real a la biodiversidad del departamento.',
				'tipo'        => 'donut',
				'tipos'       => array( 'donut', 'treemap', 'bar', 'pie' ),
				'procesador'  => array( $c, 'inat_grupos' ),
			),
			'inat_diario'    => array(
				'titulo'      => 'Observaciones ciudadanas por día',
				'fuente'      => 'inaturalist',
				'subgrupo'    => 'Ciencia ciudadana',
				'descripcion' => 'Número de observaciones registradas en iNaturalist por día en Nariño.',
				'lectura'     => 'Los picos suelen coincidir con fines de semana, salidas de campo y eventos como el Global Big Day o el City Nature Challenge.',
				'tipo'        => 'bar',
				'tipos'       => array( 'bar', 'line', 'area' ),
				'procesador'  => array( $c, 'inat_diario' ),
			),
		);
	}

	/**
	 * Recursos.
	 *
	 * @return array
	 */
	public static function recursos() {
		return array(
			'biodiversidad_resumen' => array(
				'titulo'      => 'Resumen de registros de biodiversidad (GBIF)',
				'descripcion' => 'Conteos de registros en Nariño por reino, clase, categoría UICN, año y municipio.',
				'fuente'      => 'gbif',
				'formatos'    => array( 'json', 'csv' ),
				'campos'      => array(
					'dimension' => 'reino | clase | uicn | anio | municipio',
					'categoria' => 'Valor de la dimensión',
					'codigo'    => 'Clave GBIF, código UICN o DIVIPOLA',
					'registros' => 'Número de registros',
				),
				'generador'   => array( __CLASS__, 'gen_gbif' ),
			),
			'inat_recientes'        => array(
				'titulo'      => 'Observaciones ciudadanas recientes (iNaturalist)',
				'descripcion' => 'Últimas 30 observaciones de grado investigación en Nariño con especie, grupo, estado de conservación y ubicación.',
				'fuente'      => 'inaturalist',
				'formatos'    => array( 'json', 'csv', 'geojson' ),
				'campos'      => array(
					'fecha'    => 'Fecha de observación',
					'especie'  => 'Nombre científico',
					'comun'    => 'Nombre común',
					'grupo'    => 'Grupo biológico',
					'amenaza'  => 'Estado de conservación (si aplica)',
					'lat'      => 'Latitud (puede estar generalizada)',
					'lon'      => 'Longitud',
					'licencia' => 'Licencia de la observación',
					'enlace'   => 'Enlace a la observación',
				),
				'generador'   => array( __CLASS__, 'gen_inat' ),
			),
		);
	}

	/* ---------- GBIF ---------- */

	/**
	 * Facetas de GBIF.
	 *
	 * @return array
	 */
	private static function facetas() {
		return SAN_Fuentes::obtener( 'gbif' )->facetas();
	}

	/** @return array */
	public static function gen_gbif() {
		$r = self::facetas();
		if ( ! $r['ok'] ) {
			return array(
				'ok'    => false,
				'error' => $r['error'],
			);
		}
		$f     = SAN_Fuentes::obtener( 'gbif' );
		$gadm  = self::gadm();
		$filas = array();
		foreach ( $r['datos']['reinos'] as $k => $n ) {
			$filas[] = array( 'dimension' => 'reino', 'categoria' => SAN_Fuente_Gbif::REINOS[ $k ] ?? $k, 'codigo' => $k, 'registros' => $n );
		}
		foreach ( array_slice( $r['datos']['clases'], 0, 20, true ) as $k => $n ) {
			$filas[] = array( 'dimension' => 'clase', 'categoria' => $f->nombre_taxon( $k ), 'codigo' => $k, 'registros' => $n );
		}
		foreach ( $r['datos']['uicn'] as $k => $n ) {
			$filas[] = array( 'dimension' => 'uicn', 'categoria' => SAN_Fuente_Gbif::UICN[ $k ] ?? $k, 'codigo' => $k, 'registros' => $n );
		}
		foreach ( $r['datos']['anios'] as $k => $n ) {
			$filas[] = array( 'dimension' => 'anio', 'categoria' => $k, 'codigo' => $k, 'registros' => $n );
		}
		foreach ( $r['datos']['municipios'] as $k => $n ) {
			$div     = $gadm[ $k ] ?? '';
			$filas[] = array( 'dimension' => 'municipio', 'categoria' => $div ? SAN_Municipios::nombre( $div ) : $k, 'codigo' => $div ? $div : $k, 'registros' => $n );
		}
		return array(
			'ok'          => true,
			'filas'       => $filas,
			'actualizado' => $r['actualizado'],
			'vencido'     => $r['vencido'],
		);
	}

	/**
	 * Mapa GADM nivel 2 → DIVIPOLA.
	 *
	 * @return array
	 */
	private static function gadm() {
		static $mapa = null;
		if ( null === $mapa ) {
			$mapa = json_decode( (string) file_get_contents( SAN_DIR . 'data/gadm_narino.json' ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			$mapa = is_array( $mapa ) ? $mapa : array();
		}
		return $mapa;
	}

	/**
	 * Cifras UICN comunes.
	 *
	 * @param array $uicn Conteos por categoría.
	 * @param int   $total Total de registros.
	 * @return array
	 */
	private static function resumen_uicn( array $uicn, $total ) {
		$amen = ( $uicn['CR'] ?? 0 ) + ( $uicn['EN'] ?? 0 ) + ( $uicn['VU'] ?? 0 );
		$eval = array_sum( $uicn );
		return array(
			'amenazados' => $amen,
			'evaluados'  => $eval,
			'pct'        => $eval ? $amen / $eval * 100 : 0,
			'total'      => $total,
		);
	}

	/** @return array */
	public static function reinos() {
		$r = self::facetas();
		if ( ! $r['ok'] ) {
			return SAN_Catalogo::fallo( $r );
		}
		$datos = array();
		foreach ( $r['datos']['reinos'] as $k => $n ) {
			$datos[] = array(
				'reino'     => SAN_Fuente_Gbif::REINOS[ $k ] ?? $k,
				'registros' => $n,
			);
		}
		$datos = self::plegar_otros( $datos, 'reino', 'registros' );
		$tot   = (int) $r['datos']['total'];
		$an    = $datos[0] ?? null;
		$pl    = null;
		foreach ( $datos as $d ) {
			if ( 'Plantas' === $d['reino'] ) {
				$pl = $d;
			}
		}
		$u = self::resumen_uicn( $r['datos']['uicn'], $tot );
		return self::ok(
			$datos,
			array(
				'x'          => 'reino',
				'y'          => 'registros',
				'etiqueta'   => 'reino',
				'unidad'     => 'registros',
				'decimales'  => 0,
				'etiqueta_y' => 'Registros',
			),
			array(
				'nivel'                => 'info',
				'titular'              => sprintf( 'Nariño suma %s registros de biodiversidad en GBIF; el %s son %s.', SAN_Analisis::num( $tot, 0 ), $an ? SAN_Analisis::num( $an['registros'] / max( 1, $tot ) * 100, 0 ) . ' %' : '—', $an ? mb_strtolower( $an['reino'] ) : '' ),
				'cualitativo'          => array_filter( array(
					$pl ? sprintf( 'Las plantas aportan el %s de los registros, reflejo de la riqueza florística de los Andes y el Chocó biogeográfico.', SAN_Analisis::num( $pl['registros'] / max( 1, $tot ) * 100, 0 ) . ' %' ) : '',
					'Hongos, bacterias y protistas apenas están documentados: son grandes vacíos de conocimiento.',
					sprintf( 'Del total evaluado por la UICN, el %s corresponde a especies amenazadas (CR, EN o VU).', SAN_Analisis::num( $u['pct'], 1 ) . ' %' ),
				) ),
				'recomendaciones'      => array(),
				'cuantitativo'         => array_merge(
					array( self::cifra( 'Registros', SAN_Analisis::num( $tot, 0 ) ) ),
					array_map(
						function ( $d ) use ( $tot ) {
							return self::cifra( $d['reino'], SAN_Analisis::num( $d['registros'], 0 ), SAN_Analisis::num( $d['registros'] / max( 1, $tot ) * 100, 1 ) . ' %' );
						},
						array_slice( $datos, 0, 4 )
					)
				),
				'resumen_cuantitativo' => sprintf( '%d reinos con registros; amenazados: %s registros.', count( $r['datos']['reinos'] ), SAN_Analisis::num( $u['amenazados'], 0 ) ),
				'metodo'               => 'Facetas de la API de ocurrencias de GBIF con gadmGid=COL.22_2 (Nariño).',
			),
			$r
		);
	}

	/** @return array */
	public static function clases() {
		$r = self::facetas();
		if ( ! $r['ok'] ) {
			return SAN_Catalogo::fallo( $r );
		}
		$f     = SAN_Fuentes::obtener( 'gbif' );
		$datos = array();
		foreach ( array_slice( $r['datos']['clases'], 0, 12, true ) as $k => $n ) {
			$datos[] = array(
				'clase'     => $f->nombre_taxon( $k ),
				'registros' => $n,
			);
		}
		$tot = (int) $r['datos']['total'];
		$top = $datos[0] ?? array( 'clase' => '—', 'registros' => 0 );
		return self::ok(
			$datos,
			array(
				'x'          => 'clase',
				'y'          => 'registros',
				'etiqueta'   => 'clase',
				'unidad'     => 'registros',
				'decimales'  => 0,
				'etiqueta_y' => 'Registros',
			),
			array(
				'nivel'                => 'info',
				'titular'              => sprintf( '%s es la clase más documentada, con el %s de todos los registros.', $top['clase'], SAN_Analisis::num( $top['registros'] / max( 1, $tot ) * 100, 0 ) . ' %' ),
				'cualitativo'          => array( 'Nariño comparte el Chocó biogeográfico, los Andes y el piedemonte amazónico: una de las mayores diversidades de aves del mundo explica su peso en los datos.' ),
				'recomendaciones'      => array(),
				'cuantitativo'         => array_map(
					function ( $d ) {
						return self::cifra( $d['clase'], SAN_Analisis::num( $d['registros'], 0 ) );
					},
					array_slice( $datos, 0, 6 )
				),
				'resumen_cuantitativo' => sprintf( 'Las 12 clases principales suman el %s de los registros.', SAN_Analisis::num( array_sum( array_column( $datos, 'registros' ) ) / max( 1, $tot ) * 100, 1 ) . ' %' ),
				'metodo'               => 'Faceta classKey de GBIF; nombres desde /species/{key}.',
			),
			$r
		);
	}

	/** @return array */
	public static function amenaza() {
		$r = self::facetas();
		if ( ! $r['ok'] ) {
			return SAN_Catalogo::fallo( $r );
		}
		$datos = array();
		foreach ( array( 'CR', 'EN', 'VU', 'NT', 'LC', 'DD', 'EW', 'EX' ) as $k ) {
			if ( ! empty( $r['datos']['uicn'][ $k ] ) ) {
				$datos[] = array(
					'categoria' => SAN_Fuente_Gbif::UICN[ $k ] . ' (' . $k . ')',
					'codigo'    => $k,
					'registros' => $r['datos']['uicn'][ $k ],
					'color'     => self::COLOR_UICN[ $k ],
				);
			}
		}
		$u = self::resumen_uicn( $r['datos']['uicn'], (int) $r['datos']['total'] );
		return self::ok(
			$datos,
			array(
				'x'           => 'categoria',
				'y'           => 'registros',
				'etiqueta'    => 'categoria',
				'color_campo' => 'color',
				'unidad'      => 'registros',
				'decimales'   => 0,
				'etiqueta_y'  => 'Registros',
			),
			array(
				'nivel'                => 'moderado',
				'etiqueta'             => 'Conservación',
				'titular'              => sprintf( '%s registros corresponden a especies amenazadas (%s de los evaluados).', SAN_Analisis::num( $u['amenazados'], 0 ), SAN_Analisis::num( $u['pct'], 1 ) . ' %' ),
				'cualitativo'          => array(
					sprintf( 'En peligro crítico: %s registros; en peligro: %s; vulnerables: %s.', SAN_Analisis::num( $r['datos']['uicn']['CR'] ?? 0, 0 ), SAN_Analisis::num( $r['datos']['uicn']['EN'] ?? 0, 0 ), SAN_Analisis::num( $r['datos']['uicn']['VU'] ?? 0, 0 ) ),
					'La deforestación, la minería ilegal y la expansión agrícola son las principales presiones sobre estas especies en el departamento.',
				),
				'recomendaciones'      => array( 'Priorizar la protección de páramos, bosques andinos y manglares, hábitats de la mayoría de especies amenazadas.' ),
				'cuantitativo'         => array(
					self::cifra( 'Evaluados UICN', SAN_Analisis::num( $u['evaluados'], 0 ) ),
					self::cifra( 'Amenazados', SAN_Analisis::num( $u['amenazados'], 0 ), 'CR + EN + VU' ),
					self::cifra( '% amenazados', SAN_Analisis::num( $u['pct'], 1 ) . ' %' ),
					self::cifra( 'Casi amenazados', SAN_Analisis::num( $r['datos']['uicn']['NT'] ?? 0, 0 ) ),
				),
				'resumen_cuantitativo' => 'Son registros (observaciones), no número de especies.',
				'metodo'               => 'Faceta iucnRedListCategory de GBIF para Nariño.',
			),
			$r
		);
	}

	/** @return array */
	public static function anual() {
		$r = self::facetas();
		if ( ! $r['ok'] ) {
			return SAN_Catalogo::fallo( $r );
		}
		$anios = $r['datos']['anios'];
		ksort( $anios );
		$desde = (int) gmdate( 'Y' ) - 30;
		$datos = array();
		foreach ( $anios as $a => $n ) {
			if ( (int) $a >= $desde ) {
				$datos[] = array(
					'fecha'     => $a . '-01-01',
					'registros' => $n,
				);
			}
		}
		$max = SAN_Analisis::extremo( $datos, 'registros', true );
		$u10 = array_slice( array_column( $datos, 'registros' ), -10 );
		$tend = SAN_Analisis::tendencia( $u10 );
		return self::ok(
			$datos,
			array(
				'x'          => 'fecha',
				'y'          => 'registros',
				'tiempo'     => true,
				'unidad'     => 'registros',
				'decimales'  => 0,
				'etiqueta_x' => 'Año',
				'periodo'    => 'anio',
				'etiqueta_y' => 'Registros',
			),
			array(
				'nivel'                => 'info',
				'titular'              => $max ? sprintf( 'El año con más registros fue %s (%s).', substr( $max['fecha'], 0, 4 ), SAN_Analisis::num( $max['registros'], 0 ) ) : '',
				'cualitativo'          => array( sprintf( 'En la última década la tendencia es %s.', SAN_Analisis::frase_tendencia( $tend['pendiente'], SAN_Analisis::estadisticos( $u10 )['desviacion'] ?: 1, 'registros', 'año' ) ), 'El año en curso aparece incompleto: los registros se publican con meses de rezago.' ),
				'recomendaciones'      => array(),
				'cuantitativo'         => array(
					self::cifra( 'Años', (string) count( $datos ) ),
					self::cifra( 'Media anual', SAN_Analisis::num( SAN_Analisis::estadisticos( array_column( $datos, 'registros' ) )['media'], 0 ) ),
					self::cifra( 'Máximo', SAN_Analisis::num( $max['registros'] ?? 0, 0 ), $max ? substr( $max['fecha'], 0, 4 ) : '' ),
				),
				'resumen_cuantitativo' => 'Conteo por año de observación (eventDate).',
				'metodo'               => 'Faceta year de GBIF (Nariño), últimos 30 años.',
			),
			$r
		);
	}

	/** @return array */
	public static function mapa() {
		$r = self::facetas();
		if ( ! $r['ok'] ) {
			return SAN_Catalogo::fallo( $r );
		}
		$gadm  = self::gadm();
		$por   = array();
		foreach ( $r['datos']['municipios'] as $gid => $n ) {
			if ( isset( $gadm[ $gid ] ) ) {
				$por[ $gadm[ $gid ] ] = $n;
			}
		}
		$datos = array();
		foreach ( SAN_Municipios::todos() as $m ) {
			$n       = $por[ $m['divipola'] ] ?? 0;
			$datos[] = array(
				'divipola'    => $m['divipola'],
				'municipio'   => self::muni( $m['divipola'] ),
				'subregion'   => $m['subregion'],
				'registros'   => $n,
				'log'         => round( log10( $n + 1 ), 2 ),
				'por_km2'     => $m['area_km2'] ? round( $n / $m['area_km2'], 1 ) : null,
			);
		}
		$max   = SAN_Analisis::extremo( $datos, 'registros', true );
		$vacios = array_filter(
			$datos,
			function ( $d ) {
				return $d['registros'] < 100;
			}
		);
		$tot   = array_sum( array_column( $datos, 'registros' ) );
		$sorted = $datos;
		usort(
			$sorted,
			function ( $a, $b ) {
				return $b['registros'] <=> $a['registros'];
			}
		);
		$top3 = array_sum( array_column( array_slice( $sorted, 0, 3 ), 'registros' ) );
		return self::ok(
			$datos,
			array(
				'clave'      => 'divipola',
				'x'          => 'municipio',
				'y'          => 'log',
				'etiqueta'   => 'municipio',
				'unidad'     => 'log₁₀',
				'decimales'  => 1,
				'tono'       => 'verde',
				'etiqueta_y' => 'Registros (log₁₀)',
				'tooltip'    => array( array( 'Registros', 'registros', '', 0 ), array( 'Por km²', 'por_km2', '', 1 ), array( 'Subregión', 'subregion' ) ),
			),
			array(
				'nivel'                => 'info',
				'titular'              => sprintf( '%s concentra %s registros; los tres municipios más documentados suman el %s del total.', $max['municipio'], SAN_Analisis::num( $max['registros'], 0 ), SAN_Analisis::num( $tot ? $top3 / $tot * 100 : 0, 0 ) . ' %' ),
				'cualitativo'          => array(
					sprintf( '%d municipios tienen menos de 100 registros: son vacíos de información donde se recomiendan inventarios biológicos.', count( $vacios ) ),
					'La concentración responde a la cercanía de universidades, reservas y rutas de observación de aves, más que a una menor biodiversidad en el resto del territorio.',
				),
				'recomendaciones'      => array( 'Promover jornadas de ciencia ciudadana (iNaturalist, eBird) en los municipios con menos registros.' ),
				'cuantitativo'         => array(
					self::cifra( 'Municipio con más registros', SAN_Analisis::num( $max['registros'], 0 ), $max['municipio'] ),
					self::cifra( 'Con < 100 registros', (string) count( $vacios ), 'municipios' ),
					self::cifra( 'Concentración top 3', SAN_Analisis::num( $tot ? $top3 / $tot * 100 : 0, 0 ) . ' %' ),
				),
				'resumen_cuantitativo' => 'Color en escala logarítmica (log₁₀ de registros + 1) para distinguir municipios con pocos datos.',
				'metodo'               => 'Faceta gadmLevel2Gid de GBIF, equivalencia GADM→DIVIPOLA por nombre (64/64).',
			),
			$r
		);
	}

	/* ---------- iNaturalist ---------- */

	/** @return array */
	public static function gen_inat() {
		$r = SAN_Fuentes::obtener( 'inaturalist' )->resumen();
		return array(
			'ok'          => $r['ok'],
			'filas'       => $r['ok'] ? $r['datos']['recientes'] : array(),
			'actualizado' => $r['actualizado'],
			'vencido'     => $r['vencido'],
			'error'       => $r['error'],
		);
	}

	/**
	 * Análisis común de iNaturalist.
	 *
	 * @param array $d Datos del resumen.
	 * @return array
	 */
	private static function analisis_inat( array $d ) {
		$tot    = array_sum( $d['grupos'] );
		$top    = (string) array_key_first( $d['grupos'] );
		$amen   = array_filter(
			$d['recientes'],
			function ( $o ) {
				return in_array( strtoupper( $o['amenaza'] ), array( 'CR', 'EN', 'VU' ), true );
			}
		);
		$dias   = array_values( $d['dias'] );
		$ej     = $d['recientes'][0] ?? null;
		return array(
			'nivel'                => 'info',
			'titular'              => sprintf( '%s observaciones ciudadanas en Nariño en %d días; el grupo más observado es %s.', SAN_Analisis::num( $tot, 0 ), count( $dias ), mb_strtolower( $top ) ),
			'cualitativo'          => array_values(
				array_filter(
					array(
						$ej ? sprintf( 'Observación reciente destacada: %s%s (%s).', $ej['especie'], $ej['comun'] ? ' — ' . $ej['comun'] : '', $ej['fecha'] ) : '',
						$amen ? sprintf( 'Entre las observaciones recientes hay %d de especies amenazadas.', count( $amen ) ) : '',
						'Las observaciones con grado investigación son verificadas por la comunidad y se comparten con GBIF.',
					)
				)
			),
			'recomendaciones'      => array( 'Cualquier persona puede aportar datos con la aplicación iNaturalist: fotografías con fecha y lugar.' ),
			'cuantitativo'         => array(
				self::cifra( 'Observaciones', SAN_Analisis::num( $tot, 0 ) ),
				self::cifra( 'Grupos', (string) count( $d['grupos'] ) ),
				self::cifra( 'Promedio diario', SAN_Analisis::num( $dias ? array_sum( $dias ) / count( $dias ) : 0, 1 ) ),
				self::cifra( 'Día con más', SAN_Analisis::num( $dias ? max( $dias ) : 0, 0 ) ),
			),
			'resumen_cuantitativo' => 'Por grupo: ' . implode( '; ', array_map( function ( $k, $v ) { return $k . ' ' . $v; }, array_keys( array_slice( $d['grupos'], 0, 6, true ) ), array_slice( $d['grupos'], 0, 6, true ) ) ) . '.', // phpcs:ignore
			'metodo'               => 'API v1 de iNaturalist (place_id 12737, Nariño): iconic_taxa_counts e histogram.',
		);
	}

	/** @return array */
	public static function inat_grupos() {
		$r = SAN_Fuentes::obtener( 'inaturalist' )->resumen();
		if ( ! $r['ok'] ) {
			return SAN_Catalogo::fallo( $r );
		}
		$datos = array();
		foreach ( $r['datos']['grupos'] as $g => $n ) {
			$datos[] = array(
				'grupo'         => $g,
				'observaciones' => $n,
			);
		}
		return self::ok(
			self::plegar_otros( $datos, 'grupo', 'observaciones' ),
			array(
				'x'          => 'grupo',
				'y'          => 'observaciones',
				'etiqueta'   => 'grupo',
				'unidad'     => 'observaciones',
				'decimales'  => 0,
				'etiqueta_y' => 'Observaciones',
			),
			self::analisis_inat( $r['datos'] ),
			$r
		);
	}

	/** @return array */
	public static function inat_diario() {
		$r = SAN_Fuentes::obtener( 'inaturalist' )->resumen();
		if ( ! $r['ok'] ) {
			return SAN_Catalogo::fallo( $r );
		}
		$datos = array();
		foreach ( $r['datos']['dias'] as $d => $n ) {
			$datos[] = array(
				'fecha'         => $d,
				'observaciones' => $n,
			);
		}
		return self::ok(
			$datos,
			array(
				'x'          => 'fecha',
				'y'          => 'observaciones',
				'tiempo'     => true,
				'unidad'     => 'observaciones',
				'decimales'  => 0,
				'etiqueta_x' => 'Fecha',
				'etiqueta_y' => 'Observaciones',
			),
			self::analisis_inat( $r['datos'] ),
			$r
		);
	}
}
