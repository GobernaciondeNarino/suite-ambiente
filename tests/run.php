<?php
/**
 * Pruebas unitarias de las clases puras de Suite Ambiente Nariño.
 * Uso: php tests/run.php
 */

require __DIR__ . '/php/bootstrap.php';

use GobernacionNarino\SuiteAmbiente\SAN_Analisis as A;
use GobernacionNarino\SuiteAmbiente\SAN_Umbrales as U;
use GobernacionNarino\SuiteAmbiente\SAN_Geo as G;
use GobernacionNarino\SuiteAmbiente\SAN_Municipios as M;
use GobernacionNarino\SuiteAmbiente\SAN_Seguridad as S;
use GobernacionNarino\SuiteAmbiente\SAN_Logger as L;
use GobernacionNarino\SuiteAmbiente\SAN_Datos_Abiertos as D;
use GobernacionNarino\SuiteAmbiente\SAN_Catalogo as C;
use GobernacionNarino\SuiteAmbiente\SAN_Consumo as K;

$fallos = 0;
$total  = 0;
function prueba( $nombre, $cond ) {
	global $fallos, $total;
	++$total;
	if ( ! $cond ) {
		++$fallos;
	}
	echo ( $cond ? '✓ ' : '✗ ' ) . $nombre . PHP_EOL;
}
function casi( $a, $b, $tol = 0.01 ) {
	return null !== $a && abs( $a - $b ) <= $tol;
}

// Estadística descriptiva.
$e = A::estadisticos( array( 1, 2, 3, 4, null, '5', 'x' ) );
prueba( 'estadisticos: n ignora nulos y no numéricos', 5 === $e['n'] );
prueba( 'estadisticos: media 3', casi( $e['media'], 3 ) );
prueba( 'estadisticos: mediana 3', casi( $e['mediana'], 3 ) );
prueba( 'estadisticos: desviación muestral 1,58', casi( $e['desviacion'], 1.5811 ) );
prueba( 'estadisticos: serie vacía', 0 === A::estadisticos( array() )['n'] );
$t = A::tendencia( array( 1, 2, 3, 4, 5 ) );
prueba( 'tendencia: pendiente 1 y R² 1', casi( $t['pendiente'], 1 ) && casi( $t['r2'], 1 ) );
prueba( 'variación porcentual +50 %', casi( A::variacion_pct( 10, 15 ), 50 ) );
prueba( 'formato colombiano 1.234,5', '1.234,5' === A::num( 1234.5 ) );
prueba( 'formato sin "-0"', '0,0' === A::num( -0.01 ) );
prueba( 'fecha en español', 'mié 30 sep' === A::fecha( '2026-09-30' ) );

// ICA (Res. 2254/2017).
prueba( 'ICA PM2.5 = 12 → 50', casi( U::ica( 'pm2_5', 12 ), 50 ) );
prueba( 'ICA PM2.5 = 37 → 100', casi( U::ica( 'pm2_5', 37 ), 100 ) );
prueba( 'ICA PM2.5 = 0 → 0', casi( U::ica( 'pm2_5', 0 ), 0 ) );
prueba( 'ICA PM10 = 154 → 100', casi( U::ica( 'pm10', 154 ), 100 ) );
prueba( 'ICA monótono', U::ica( 'pm2_5', 20 ) < U::ica( 'pm2_5', 40 ) );
prueba( 'Categoría ICA 75 = Aceptable', 'Aceptable' === U::categoria_ica( 75 )['nombre'] );
prueba( 'Categoría ICA 180 = Dañina a la salud', 'Dañina a la salud' === U::categoria_ica( 180 )['nombre'] );

// Otras clasificaciones.
prueba( 'UV 11 = Extremo', 'Extremo' === U::uv( 11 )['nombre'] );
prueba( 'Lluvia 25 mm = fuerte', 'lluvia fuerte' === U::lluvia_diaria( 25 )['nombre'] );
prueba( 'Sismo M5,2 = moderado', 'moderado' === U::sismo( 5.2 )['nombre'] );
prueba( 'Profundidad 120 km = intermedio', 'intermedio' === U::profundidad( 120 ) );
prueba( 'WMO 95 = Tormenta', 'Tormenta' === U::wmo( 95 )['grupo'] );

// Municipios y geografía.
prueba( '64 municipios', 64 === count( M::todos() ) );
prueba( 'buscar por nombre sin tilde', '52835' === ( M::buscar( 'tumaco' )['divipola'] ?? '' ) );
prueba( 'buscar por código', 'PASTO' === ( M::buscar( '52001' )['nombre'] ?? '' ) );
prueba( 'nombre en formato título', 'San Andrés de Tumaco' === M::nombre( '52835' ) );
prueba( '14 subregiones', 14 === count( M::subregiones() ) );
prueba( 'Pasto (cabecera) cae en Pasto', '52001' === G::municipio_de( 1.2124, -77.2788 ) );
prueba( 'Ipiales (cabecera) cae en Ipiales', '52356' === G::municipio_de( 0.8277, -77.6464 ) );
prueba( 'Quito queda fuera de Nariño', '' === G::municipio_de( -0.18, -78.47 ) );
prueba( 'Distancia Pasto–Ipiales ≈ 58 km', casi( G::distancia_km( 1.2124, -77.2788, 0.8277, -77.6464 ), 59, 5 ) );

// Seguridad.
prueba( 'DIVIPOLA inválido se rechaza', '' === S::sanear_divipola( '99999' ) );
prueba( 'id saneado', 'clima_mapa' === S::sanear_id( 'Clima_Mapa<script>' ) || 'clima_mapascript' === S::sanear_id( 'Clima_Mapa<script>' ) );
$cif = S::cifrar( 'clave-secreta-123' );
prueba( 'cifrado AES-GCM no deja la clave en claro', false === strpos( $cif, 'clave-secreta' ) );
prueba( 'descifrado recupera la clave', 'clave-secreta-123' === S::descifrar( $cif ) );
prueba( 'paquete manipulado no se descifra', '' === S::descifrar( substr( $cif, 0, -4 ) . 'AAAA' ) );
prueba( 'enmascarar clave', 'abcd…wxyz' === S::enmascarar( 'abcdefghijklmnopqrstuvwxyz' ) );

// Registros: redacción de datos sensibles.
prueba( 'redacta parámetro key en URL', false === strpos( L::redactar_url( 'https://x.org/a?key=SECRETO&b=1' ), 'SECRETO' ) );
prueba( 'redacta MAP_KEY en la ruta (FIRMS)', false === strpos( L::redactar_url( 'https://firms.modaps.eosdis.nasa.gov/api/area/csv/0123456789abcdef0123456789abcdef/VIIRS/1' ), '0123456789abcdef' ) );
prueba( 'redacta claves del contexto', '***' === L::redactar( array( 'token' => 'x' ) )['token'] );

// Datos abiertos y saneamiento.
$csv = D::a_csv( array( array( 'a' => '=CMD()', 'b' => 'hola, mundo' ), array( 'a' => -5, 'b' => 'x' ) ) );
prueba( 'CSV neutraliza fórmulas', false !== strpos( $csv, "'=CMD()" ) );
prueba( 'CSV conserva negativos numéricos', false !== strpos( $csv, "\n-5," ) );
prueba( 'CSV entrecomilla comas', false !== strpos( $csv, '"hola, mundo"' ) );
$geo = D::a_geojson( array( array( 'lat' => 1, 'lon' => -77, 'n' => 1 ), array( 'x' => 1 ) ) );
prueba( 'GeoJSON solo con filas georreferenciadas', 1 === count( $geo['features'] ) );
$s = C::sanear_textos( array( array( 'nombre' => '<img src=x onerror=alert(1)>Galeras', 'n' => 3 ) ) );
prueba( 'textos externos sin HTML', 'Galeras' === $s[0]['nombre'] && 3 === $s[0]['n'] );

// Cupo de Open-Meteo.
prueba( 'Open-Meteo: host gratuito y comercial', K::es_openmeteo( 'https://api.open-meteo.com/v1/forecast' ) && K::es_openmeteo( 'https://customer-air-quality-api.open-meteo.com/v1/air-quality' ) );
prueba( 'Open-Meteo: rechaza hosts parecidos', ! K::es_openmeteo( 'https://evil-open-meteo.com/v1' ) && ! K::es_openmeteo( 'https://open-meteo.com.ejemplo.co/v1' ) && ! K::es_openmeteo( 'https://api.gbif.org/v1' ) );
$lat64 = implode( ',', array_fill( 0, 64, '1.2' ) );
prueba( 'Peso: 64 coordenadas × 16 variables = 102,4', casi( K::peso( 'https://api.open-meteo.com/v1/forecast?latitude=' . $lat64 . '&longitude=' . $lat64 . '&current=a,b,c,d,e,f,g,h,i,j&daily=k,l,m,n,o,p&forecast_days=7' ), 102.4 ) );
prueba( 'Peso: 60 días de caudal = 4,29', casi( K::peso( 'https://flood-api.open-meteo.com/v1/flood?latitude=2&longitude=-78&daily=a,b,c,d&past_days=30&forecast_days=30' ), 60 / 14 ) );
prueba( 'Peso: días por defecto del API de caudal (92)', casi( K::peso( 'https://flood-api.open-meteo.com/v1/flood?latitude=2&longitude=-78&daily=a' ), 92 / 14 ) );
prueba( 'Peso mínimo de una consulta = 1', casi( K::peso( 'https://api.open-meteo.com/v1/forecast?latitude=1&longitude=-77&current=temperature_2m' ), 1 ) );
K::registrar( 'openmeteo_clima', 'https://api.open-meteo.com/v1/forecast?latitude=' . $lat64 . '&longitude=' . $lat64 . '&current=a,b,c,d,e,f,g,h,i,j&daily=k,l,m,n,o,p&forecast_days=7' );
K::registrar( 'openmeteo_aire', 'https://air-quality-api.open-meteo.com/v1/air-quality?latitude=1&longitude=-77&current=pm2_5' );
K::registrar( 'gbif', 'https://api.gbif.org/v1/occurrence/search' );
$r = K::resumen();
prueba( 'Consumo del día suma solo Open-Meteo', casi( $r['dia'], 103.4, 0.1 ) && 2 === $r['peticiones'] );
prueba( 'Consumo por fuente', casi( $r['fuentes']['openmeteo_clima'] ?? 0, 102.4, 0.1 ) && ! isset( $r['fuentes']['gbif'] ) );
prueba( 'Porcentaje del límite diario', casi( $r['pct_dia'], 1.0, 0.05 ) );
K::registrar_rechazo( 'https://api.open-meteo.com/v1/forecast?latitude=1' );
prueba( 'Cuenta respuestas 429', 1 === K::resumen()['rechazos'] );

// Llave comercial de Open-Meteo: host customer-… y parámetro apikey, redactado en registros.
update_option( 'san_fuentes', array( 'openmeteo_clima' => array( 'clave' => S::cifrar( 'llave-comercial-123' ) ) ) );
$om  = new \GobernacionNarino\SuiteAmbiente\SAN_Fuente_Openmeteo_Clima();
$url = ( new \ReflectionMethod( $om, 'url' ) );
$url->setAccessible( true );
$u = $url->invoke( $om, 'https://api.open-meteo.com/v1/forecast', array( 'latitude' => '1.2', 'longitude' => '-77.3' ) );
prueba( 'Con llave usa customer-api.open-meteo.com', 0 === strpos( $u, 'https://customer-api.open-meteo.com/v1/forecast?' ) && false !== strpos( $u, 'apikey=llave-comercial-123' ) );
prueba( 'El host comercial está en la lista blanca', in_array( 'customer-api.open-meteo.com', $om->hosts(), true ) && in_array( 'api.open-meteo.com', $om->hosts(), true ) );
prueba( 'La llave no aparece en los registros', false === strpos( L::redactar_url( $u ), 'llave-comercial-123' ) );
update_option( 'san_fuentes', array() );
$u = $url->invoke( $om, 'https://api.open-meteo.com/v1/forecast', array( 'latitude' => '1.2' ) );
prueba( 'Sin llave usa el plan gratuito', 0 === strpos( $u, 'https://api.open-meteo.com/' ) && false === strpos( $u, 'apikey' ) );

echo PHP_EOL . ( $total - $fallos ) . '/' . $total . ' pruebas correctas' . PHP_EOL;
exit( $fallos ? 1 : 0 );
