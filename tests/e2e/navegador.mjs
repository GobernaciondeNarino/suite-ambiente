/**
 * Prueba de extremo a extremo en Chromium (Playwright): abre páginas con
 * shortcodes del plugin, espera a que cada gráfico termine de dibujarse y
 * reporta errores de consola, gráficos fallidos y capturas de pantalla.
 *
 * Uso: node tests/e2e/navegador.mjs <url-base> <carpeta-salida> <ruta1> [ruta2 …]
 * Variables opcionales:
 *   SAN_USUARIO / SAN_CLAVE  iniciar sesión antes (páginas de administración).
 *   SAN_TEMA=oscuro          emular prefers-color-scheme: dark.
 *   CHROMIUM                 ruta del ejecutable de Chromium.
 */
import { createRequire } from 'node:module';

const require = createRequire( import.meta.url );
let pw;
try {
	pw = require( 'playwright' );
} catch {
	pw = await import( '/opt/node22/lib/node_modules/playwright/index.mjs' );
}
const { chromium } = pw;

const [ base, salida, ...rutas ] = process.argv.slice( 2 );
const navegador = await chromium
	.launch( { executablePath: process.env.CHROMIUM || '/opt/pw-browsers/chromium' } )
	.catch( () => chromium.launch() );
const contexto = await navegador.newContext( {
	viewport: { width: 1280, height: 900 },
	ignoreHTTPSErrors: true,
	colorScheme: process.env.SAN_TEMA === 'oscuro' ? 'dark' : 'light',
} );
const pagina = await contexto.newPage();
const errores = [];
pagina.on( 'console', ( m ) => {
	if ( m.type() === 'error' ) {
		errores.push( m.text() );
	}
} );
pagina.on( 'pageerror', ( e ) => errores.push( 'pageerror: ' + e.message ) );

if ( process.env.SAN_USUARIO ) {
	await pagina.goto( base + '/wp-login.php' );
	await pagina.fill( '#user_login', process.env.SAN_USUARIO );
	await pagina.fill( '#user_pass', process.env.SAN_CLAVE );
	await pagina.click( '#wp-submit' );
	await pagina.waitForLoadState( 'networkidle' );
}

let fallos = 0;
for ( const [ i, ruta ] of rutas.entries() ) {
	await pagina.goto( base + ruta, { waitUntil: 'domcontentloaded' } );
	// Desplaza la página para activar la carga diferida (IntersectionObserver).
	await pagina.evaluate( async () => {
		for ( let y = 0; y < document.body.scrollHeight; y += 600 ) {
			window.scrollTo( 0, y );
			await new Promise( ( r ) => setTimeout( r, 120 ) );
		}
		window.scrollTo( 0, 0 );
	} );
	await pagina
		.waitForFunction(
			() => [ ...document.querySelectorAll( '.san-grafico[data-viz]' ) ].every( ( f ) => [ 'listo', 'error' ].includes( f.getAttribute( 'data-estado' ) ) ),
			null,
			{ timeout: 180000 }
		)
		.catch( () => {} );
	await pagina.waitForTimeout( 1500 );
	const estado = await pagina.evaluate( () =>
		[ ...document.querySelectorAll( '.san-grafico[data-viz]' ) ].map( ( f ) => ( {
			viz: f.getAttribute( 'data-viz' ),
			tipo: f.getAttribute( 'data-tipo' ),
			estado: f.getAttribute( 'data-estado' ),
			marcas: f.querySelectorAll( '.san-grafico__lienzo svg path, .san-grafico__lienzo svg rect, .san-grafico__lienzo svg circle, .san-grafico__lienzo canvas' ).length,
			error: ( f.querySelector( '.san-error' ) || {} ).textContent || '',
		} ) )
	);
	for ( const e of estado ) {
		const mal = e.estado !== 'listo' || e.marcas < 3;
		if ( mal ) {
			fallos++;
		}
		console.log( `${ mal ? '✗' : '✓' } ${ ruta } ${ e.viz } [${ e.tipo }] estado=${ e.estado } marcas=${ e.marcas } ${ e.error }` );
	}
	await pagina.screenshot( { path: `${ salida }/pagina-${ i + 1 }.png`, fullPage: true } );
}
if ( errores.length ) {
	console.log( 'Errores de consola:\n' + [ ...new Set( errores ) ].join( '\n' ) );
}
console.log( fallos ? `${ fallos } gráfico(s) con problemas` : 'Todos los gráficos se dibujaron.' );
await navegador.close();
process.exit( fallos || errores.length ? 1 : 0 );
