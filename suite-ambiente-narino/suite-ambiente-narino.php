<?php
/**
 * Plugin Name:       Suite Ambiente Nariño
 * Plugin URI:        https://gobiernoabierto.narino.gov.co/
 * Description:       Observatorio ambiental del departamento de Nariño: consume APIs abiertas con datos actualizados (clima, calidad del aire, ríos, océano, sismos, eventos naturales, radiación, biodiversidad e IDEAM), genera gráficos D3.js y D3plus v4 con análisis cualitativo y cuantitativo, y publica los datos como datos abiertos.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Gobernación de Nariño · Secretaría TIC, Innovación y Gobierno Abierto
 * Author URI:        https://narino.gov.co
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       suite-ambiente-narino
 * Domain Path:       /languages
 *
 * Fuentes de datos (atribución obligatoria en cada visualización): Open-Meteo
 * (CC BY 4.0), IDEAM (datos.gov.co, CC BY-SA 4.0), Servicio Geológico
 * Colombiano, USGS, GDACS, NASA FIRMS, NASA POWER, GBIF, iNaturalist y DANE.
 *
 * @package SuiteAmbienteNarino
 */

// Sin acceso directo: el plugin solo se ejecuta dentro de WordPress.
defined( 'ABSPATH' ) || exit;

define( 'SAN_VERSION', '1.0.0' );
define( 'SAN_DB_VERSION', '1' );
define( 'SAN_FILE', __FILE__ );
define( 'SAN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SAN_URL', plugin_dir_url( __FILE__ ) );
define( 'SAN_BASENAME', plugin_basename( __FILE__ ) );
define( 'SAN_REST_NS', 'suite-ambiente/v1' );

require_once SAN_DIR . 'includes/class-san-autoload.php';
\GobernacionNarino\SuiteAmbiente\SAN_Autoload::registrar();

register_activation_hook( __FILE__, array( \GobernacionNarino\SuiteAmbiente\SAN_Activador::class, 'activar' ) );
register_deactivation_hook( __FILE__, array( \GobernacionNarino\SuiteAmbiente\SAN_Activador::class, 'desactivar' ) );

add_action( 'plugins_loaded', array( \GobernacionNarino\SuiteAmbiente\SAN_Plugin::class, 'instancia' ) );
