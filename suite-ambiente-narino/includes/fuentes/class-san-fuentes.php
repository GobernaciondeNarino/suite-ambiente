<?php
/**
 * Registro de fuentes de datos.
 *
 * @package SuiteAmbienteNarino
 */

namespace GobernacionNarino\SuiteAmbiente;

defined( 'ABSPATH' ) || exit;

final class SAN_Fuentes {

	/** @var SAN_Fuente[]|null */
	private static $fuentes = null;

	/**
	 * Clases registradas (filtrable para extender con nuevas fuentes).
	 *
	 * @return string[]
	 */
	public static function clases() {
		return apply_filters(
			'san_clases_fuentes',
			array(
				SAN_Fuente_Openmeteo_Clima::class,
				SAN_Fuente_Openmeteo_Aire::class,
				SAN_Fuente_Openmeteo_Hidro::class,
				SAN_Fuente_Ideam::class,
				SAN_Fuente_Openmeteo_Marino::class,
				SAN_Fuente_Sgc_Sismos::class,
				SAN_Fuente_Sgc_Volcanes::class,
				SAN_Fuente_Usgs::class,
				SAN_Fuente_Gdacs::class,
				SAN_Fuente_Firms::class,
				SAN_Fuente_Nasa_Power::class,
				SAN_Fuente_Gbif::class,
				SAN_Fuente_Inaturalist::class,
			)
		);
	}

	/**
	 * Todas las fuentes, indexadas por id.
	 *
	 * @return SAN_Fuente[]
	 */
	public static function todas() {
		if ( null === self::$fuentes ) {
			self::$fuentes = array();
			foreach ( self::clases() as $clase ) {
				if ( class_exists( $clase ) ) {
					$f                          = new $clase();
					self::$fuentes[ $f->id() ] = $f;
				}
			}
		}
		return self::$fuentes;
	}

	/**
	 * Fuente por id.
	 *
	 * @param string $id Id.
	 * @return SAN_Fuente|null
	 */
	public static function obtener( $id ) {
		$t = self::todas();
		return $t[ (string) $id ] ?? null;
	}

	/**
	 * Nombre legible de una categoría.
	 *
	 * @param string $cat Categoría.
	 * @return string
	 */
	public static function nombre_categoria( $cat ) {
		$n = array(
			'clima'         => __( 'Clima y tiempo', 'suite-ambiente-narino' ),
			'aire'          => __( 'Calidad del aire', 'suite-ambiente-narino' ),
			'agua'          => __( 'Agua y ríos', 'suite-ambiente-narino' ),
			'oceano'        => __( 'Océano Pacífico', 'suite-ambiente-narino' ),
			'sismos'        => __( 'Sismos y volcanes', 'suite-ambiente-narino' ),
			'eventos'       => __( 'Eventos naturales', 'suite-ambiente-narino' ),
			'radiacion'     => __( 'Radiación solar y clima de superficie', 'suite-ambiente-narino' ),
			'biodiversidad' => __( 'Biodiversidad', 'suite-ambiente-narino' ),
			'ideam'         => __( 'Red de estaciones IDEAM', 'suite-ambiente-narino' ),
			'incendios'     => __( 'Incendios y focos de calor', 'suite-ambiente-narino' ),
		);
		return $n[ $cat ] ?? ucfirst( $cat );
	}
}
