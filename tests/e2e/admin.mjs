/**
 * Prueba del panel de administración de Suite Ambiente en Chromium.
 *
 * Inicia sesión, recorre Gráficos (pestañas por API, vista previa de una
 * tarjeta, personalización de shortcodes), Datos abiertos (vista previa de
 * un recurso) y Configuración (Tablero, APIs con "Probar ahora", Registros,
 * General) y guarda capturas.
 *
 * Uso: SAN_USUARIO=admin SAN_CLAVE=… node tests/e2e/admin.mjs <url-base> <carpeta-salida>
 */
import { createRequire } from 'node:module';

const require = createRequire( import.meta.url );
let pw;
try {
	pw = require( 'playwright' );
} catch {
	pw = await import( '/opt/node22/lib/node_modules/playwright/index.mjs' );
}
const [ base, salida ] = process.argv.slice( 2 );
const navegador = await pw.chromium
	.launch( { executablePath: process.env.CHROMIUM || '/opt/pw-browsers/chromium' } )
	.catch( () => pw.chromium.launch() );
const pagina = await ( await navegador.newContext( { viewport: { width: 1440, height: 1000 }, ignoreHTTPSErrors: true } ) ).newPage();
const errores = [];
pagina.on( 'pageerror', ( e ) => errores.push( e.message ) );

let fallos = 0;
const verificar = ( cond, msg ) => {
	console.log( ( cond ? '✓ ' : '✗ ' ) + msg );
	if ( ! cond ) {
		fallos++;
	}
};

await pagina.goto( base + '/wp-login.php' );
await pagina.fill( '#user_login', process.env.SAN_USUARIO || 'admin' );
await pagina.fill( '#user_pass', process.env.SAN_CLAVE || '' );
await pagina.click( '#wp-submit' );
await pagina.waitForLoadState( 'networkidle' );

// Gráficos.
await pagina.goto( base + '/wp-admin/admin.php?page=san-graficos' );
const pestanas = await pagina.locator( '.san-pestanas .nav-tab' ).count();
verificar( pestanas >= 10, `Gráficos: ${ pestanas } pestañas (una por API + complementos)` );
const tarjetas = await pagina.locator( '.san-tarjeta[data-viz]' ).count();
verificar( tarjetas > 0, `Gráficos: ${ tarjetas } tarjetas en la primera pestaña` );
const sc = await pagina.locator( '.san-tarjeta[data-viz]' ).first().locator( '.san-sc__codigo' ).count();
verificar( sc === 5, `Tarjeta con ${ sc } shortcodes (gráfico, descripción, cualitativo, cuantitativo, tarjeta)` );
const tarjeta = pagina.locator( '.san-tarjeta[data-viz="clima_pronostico_temperatura"]' );
await tarjeta.locator( 'select[data-attr="municipio"]' ).selectOption( '52835' );
const codigo = await tarjeta.locator( '.san-sc__codigo[data-rol="grafico"]' ).inputValue();
verificar( codigo.includes( 'municipio="52835"' ), 'Personalizar actualiza el shortcode: ' + codigo );
await tarjeta.locator( '.san-previa' ).click();
await pagina.waitForSelector( '.san-tarjeta[data-viz="clima_pronostico_temperatura"] .san-grafico[data-estado="listo"]', { timeout: 60000 } ).catch( () => {} );
await pagina.waitForTimeout( 1500 );
const previa = await tarjeta.locator( '.san-previa-analisis .san-cifra' ).count();
verificar( previa > 0, `Vista previa con gráfico y ${ previa } cifras de análisis` );
await tarjeta.screenshot( { path: `${ salida }/admin-tarjeta.png` } );
await pagina.screenshot( { path: `${ salida }/admin-graficos.png`, fullPage: false } );

await pagina.goto( base + '/wp-admin/admin.php?page=san-graficos&pestana=complementos' );
verificar( ( await pagina.locator( '.san-tabla-atributos tbody tr' ).count() ) >= 8, 'Complementos: tabla de atributos' );

// Datos abiertos.
await pagina.goto( base + '/wp-admin/admin.php?page=san-datos' );
const recursos = await pagina.locator( '.san-tarjeta[data-recurso]' ).count();
verificar( recursos >= 15, `Datos abiertos: ${ recursos } recursos` );
await pagina.locator( '.san-tarjeta[data-recurso="municipios"] .san-previa-datos' ).click();
await pagina.waitForSelector( '.san-tarjeta[data-recurso="municipios"] .san-previa-tabla table', { timeout: 30000 } ).catch( () => {} );
verificar( ( await pagina.locator( '.san-tarjeta[data-recurso="municipios"] .san-previa-tabla tbody tr' ).count() ) === 10, 'Vista previa de 10 filas del recurso municipios' );
await pagina.screenshot( { path: `${ salida }/admin-datos.png` } );

// Configuración.
await pagina.goto( base + '/wp-admin/admin.php?page=san-config&pestana=tablero' );
verificar( ( await pagina.locator( '.san-kpi' ).count() ) === 6, 'Tablero: 6 indicadores de estado' );
await pagina.screenshot( { path: `${ salida }/admin-config-tablero.png` } );

await pagina.goto( base + '/wp-admin/admin.php?page=san-config&pestana=apis' );
const fuentes = await pagina.locator( '.san-fuente' ).count();
verificar( fuentes >= 12, `APIs: ${ fuentes } fuentes configurables` );
await pagina.locator( '#fuente-openmeteo_clima .san-probar' ).click();
await pagina.waitForSelector( '#fuente-openmeteo_clima .san-resultado-prueba:not([hidden])', { timeout: 60000 } ).catch( () => {} );
const resultado = await pagina.locator( '#fuente-openmeteo_clima .san-resultado-prueba' ).innerText().catch( () => '' );
verificar( /HTTP 200/.test( resultado ), 'Probar ahora (Open-Meteo): ' + resultado.split( '\n' )[ 0 ] );
await pagina.locator( '#fuente-openmeteo_clima' ).screenshot( { path: `${ salida }/admin-config-api.png` } );

await pagina.goto( base + '/wp-admin/admin.php?page=san-config&pestana=registros' );
verificar( ( await pagina.locator( '.san-logs tbody tr' ).count() ) > 0, 'Registros: tabla con eventos' );
await pagina.screenshot( { path: `${ salida }/admin-config-registros.png` } );

await pagina.goto( base + '/wp-admin/admin.php?page=san-config&pestana=general' );
verificar( ( await pagina.locator( 'select[name="san[libreria]"]' ).count() ) === 1, 'General: ajustes de librerías' );
await pagina.screenshot( { path: `${ salida }/admin-config-general.png` } );

if ( errores.length ) {
	console.log( 'Errores JS:\n' + errores.join( '\n' ) );
}
await navegador.close();
process.exit( fallos || errores.length ? 1 : 0 );
