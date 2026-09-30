# Arquitectura del plugin

Suite Ambiente Nariño es un plugin de WordPress en PHP 7.4+ y JavaScript sin compilación. Consulta APIs ambientales desde el servidor, guarda las respuestas en caché, calcula los análisis y entrega al navegador datos listos para dibujar con D3 v7 y D3plus v4.

- **Espacio de nombres:** `GobernacionNarino\SuiteAmbiente`, clases con prefijo `SAN_`.
- **Dominio de traducción:** `suite-ambiente-narino`.
- **API REST:** `/wp-json/suite-ambiente/v1/`.

## Flujo de una visualización

```text
[san_card id="aire_ica_municipios"]            (shortcode en una página)
        │  SAN_Shortcodes → HTML con el contenedor, la descripción y los análisis
        ▼
Navegador: san-graficos.js (carga diferida al entrar en pantalla)
        │  GET /wp-json/suite-ambiente/v1/visualizaciones/aire_ica_municipios
        ▼
SAN_Rest ──► SAN_Catalogo::resolver()
                 │  valida el id contra el catálogo y sanea los parámetros
                 ▼
             SAN_Viz_Aire (procesador de la visualización)
                 │  pide datos a la fuente
                 ▼
             SAN_Fuente_Openmeteo_Aire::municipios()
                 │  get_cacheado(): tabla {prefix}san_cache
                 │    ├─ vigente → se usa
                 │    └─ vencida → SAN_Http (host permitido, 1 reintento)
                 │         └─ si falla → última copia (marcada «vencido») + registro en san_logs
                 ▼
             datos + config (ejes, unidad, colores) + análisis cualitativo y cuantitativo
        ▼
Navegador: D3plus v4 (formas estadísticas) o D3 v7 (mapas, calor, medidor, rosa)
```

Los mismos datos resueltos alimentan los shortcodes de análisis, la descarga CSV de la visualización y la vista previa del panel.

## Estructura de archivos

```text
suite-ambiente-narino/
├── suite-ambiente-narino.php      Cabecera, constantes y arranque
├── uninstall.php                  Borra tablas, opciones y tareas programadas
├── includes/
│   ├── class-san-plugin.php       Singleton que conecta los módulos
│   ├── class-san-autoload.php     Autocarga SAN_Foo_Bar → class-san-foo-bar.php
│   ├── class-san-activador.php    Tablas san_cache y san_logs (dbDelta)
│   ├── class-san-ajustes.php      Opciones san_ajustes y san_fuentes; llaves cifradas
│   ├── class-san-cache.php        Caché en tabla propia, con copia vencida ante errores
│   ├── class-san-logger.php       Registros con niveles y redacción de llaves
│   ├── class-san-seguridad.php    Saneamiento, cifrado AES-256-GCM, límite por IP
│   ├── class-san-http.php         Cliente HTTP: solo HTTPS y hosts de las fuentes
│   ├── class-san-municipios.php   64 municipios, 14 subregiones, DIVIPOLA y alias
│   ├── class-san-geo.php          Punto en polígono, distancias, recorte a Nariño
│   ├── class-san-cron.php         Sincronización horaria y mantenimiento diario
│   ├── class-san-assets.php       Registro de scripts y estilos; SRI para CDN
│   ├── class-san-rest.php         API REST pública y de administración
│   ├── class-san-shortcodes.php   Los diez shortcodes
│   ├── fuentes/                   Una clase por API (13) + base y registro
│   ├── visualizaciones/           Catálogo, análisis, umbrales y 10 clases SAN_Viz_*
│   └── admin/                     Gráficos, Datos abiertos y Configuración
├── assets/
│   ├── js/san-core.js             Utilidades: API, GeoJSON, formatos, paleta, descargas
│   ├── js/san-graficos.js         Controlador de figuras y configuración de D3plus
│   ├── js/san-d3.js               Motores D3: mapa, puntos, calor, medidor, rosa
│   ├── js/san-admin.js            Interacción del panel
│   ├── css/san-front.css          Estilos públicos con tokens claro/oscuro
│   ├── css/san-admin.css          Estilos del panel
│   └── vendor/                    D3 7.9.0 y @d3plus/core 4.5.0
└── data/
    ├── narino_municipios.geojson  Límites municipales (suite-oni)
    ├── narino_subregiones.geojson Límites de subregiones (suite-oni)
    ├── narino_departamento.geojson Contorno del departamento
    ├── narino_municipios.json     Tabla maestra: DIVIPOLA, nombre, subregión, centroide
    └── gadm_narino.json           Correspondencia GADM ↔ DIVIPOLA (GBIF)
```

## Datos base

Los límites de municipios y subregiones vienen del repositorio [GobernaciondeNarino/suite-oni](https://github.com/GobernaciondeNarino/suite-oni/tree/main/data). La tabla maestra `narino_municipios.json` une el código DIVIPOLA, el nombre oficial, la subregión (14, según la capa de subregiones de suite-oni), el área, la población del censo 2018 y el centroide, que se usa para consultar Open-Meteo en los 64 municipios.

Los GeoJSON siguen RFC 7946, que usa el sentido de giro contrario al que espera `d3-geo`. `SAN.reorientar()` corrige el sentido de los anillos antes de dibujar; sin esa corrección, los mapas salían como un rectángulo lleno.

## Almacenamiento

| Elemento | Tipo | Contenido |
|---|---|---|
| `{prefix}san_cache` | tabla | Respuestas de las APIs y subconjuntos procesados, con vencimiento y fuente. |
| `{prefix}san_logs` | tabla | Registros por fuente, nivel, evento, mensaje, duración y contexto. |
| `san_ajustes` | opción | Ajustes generales y del tablero. |
| `san_fuentes` | opción | Configuración por fuente: activa, TTL, tiempo de espera, parámetros y llave cifrada. |
| `san_salud` | opción | Últimas 20 verificaciones de cada fuente. |
| `san_db_version` | opción | Versión del esquema de tablas. |

## Tareas programadas

| Evento | Frecuencia | Qué hace |
|---|---|---|
| `san_sincronizar` | cada hora | Verifica todas las fuentes y precalienta la caché de las visualizaciones del tablero. |
| `san_mantenimiento` | diaria | Purga los registros más antiguos que la retención configurada (30 días por defecto) y la caché vencida hace más de 7 días (la reciente se conserva como respaldo ante fallos). |

WP-Cron depende de las visitas al sitio. En producción conviene desactivarlo en `wp-config.php` (`define( 'DISABLE_WP_CRON', true );`) y llamarlo desde el cron del servidor cada 5 a 15 minutos.

## API REST

| Ruta | Método | Acceso | Uso |
|---|---|---|---|
| `/visualizaciones` | GET | público | Catálogo con metadatos y shortcodes. |
| `/visualizaciones/{id}` | GET | público | Datos, configuración y análisis. `?municipio=`, `?formato=csv`. |
| `/datos` | GET | público | Catálogo de recursos de datos abiertos. |
| `/datos/{recurso}` | GET | público | Recurso en `json`, `csv` o `geojson`. |
| `/municipios` | GET | público | Municipios y subregiones. |
| `/estado` | GET | público | Estado de las fuentes (sin detalles internos). |
| `/admin/probar/{fuente}` | POST | administrador | Prueba en vivo de una fuente. |
| `/admin/probar` | POST | administrador | Prueba todas las fuentes. |
| `/admin/cache/vaciar` | POST | administrador | Vacía la caché (toda o de una fuente). |
| `/admin/logs` | GET, DELETE | administrador | Consulta y purga de registros. |

Las rutas públicas tienen un límite por IP (300 peticiones por minuto por defecto, configurable entre 10 y 1000) y responden 429 al superarlo. Una página con muchas tarjetas hace unas 50 peticiones; tenga en cuenta las oficinas que salen a internet con una sola IP. Los administradores con sesión iniciada no tienen límite. Las de administración exigen `manage_options` y nonce de la API REST.

## Seguridad

- **SSRF:** `SAN_Http` solo acepta HTTPS y los hosts declarados por cada fuente. Las URL nunca vienen del usuario.
- **Llaves de API:** se guardan cifradas con AES-256-GCM, derivando la clave de las sales de WordPress. Nunca se envían al navegador. En los registros se redactan los parámetros sensibles y los segmentos de ruta largos.
- **Validación:** los ids de visualización, recurso y fuente se validan contra el catálogo. Los municipios, contra la lista DIVIPOLA. El tipo de gráfico, contra los tipos de la visualización.
- **Salida:** todo se escapa en PHP. Los textos de las APIs se limpian de HTML en el servidor y se vuelven a escapar en JavaScript, porque los tooltips de D3plus insertan HTML.
- **CSV:** las celdas de texto que empiezan por `=`, `+`, `-`, `@`, tabulador o retorno se neutralizan con un apóstrofo contra la inyección de fórmulas; los números negativos se conservan.
- **Panel:** formularios con nonce y `manage_options`; el nonce REST solo se imprime para usuarios con sesión iniciada.

## Extensión

| Filtro | Recibe | Uso |
|---|---|---|
| `san_clases_fuentes` | `string[]` de clases | Agregar o quitar fuentes. Una fuente extiende `SAN_Fuente`. |
| `san_clases_visualizaciones` | `string[]` de clases | Agregar clases con visualizaciones (método estático `definiciones()`). |
| `san_visualizaciones` | definiciones | Modificar visualizaciones existentes (títulos, tipos, textos). |
| `san_datos_abiertos` | recursos | Agregar o modificar recursos de datos abiertos. |
| `san_ip_cliente` | IP detectada | Ajustar la IP del visitante detrás de un proxy o CDN, para el límite de peticiones. |

Ejemplo de una fuente nueva:

```php
add_filter( 'san_clases_fuentes', function ( $clases ) {
	$clases[] = Mi_Fuente::class; // extends GobernacionNarino\SuiteAmbiente\SAN_Fuente
	return $clases;
} );
```

Una clase de fuente declara `id()`, `nombre()`, `categoria()`, `hosts()`, `atribucion()`, `licencia()`, `frecuencia()` y `probar()`, y consulta con `get_cacheado()` o `get_procesado()`. Las visualizaciones se declaran con un procesador que devuelve `ok`, `datos`, `config` y `analisis` (ver la cabecera de `class-san-catalogo.php`).
