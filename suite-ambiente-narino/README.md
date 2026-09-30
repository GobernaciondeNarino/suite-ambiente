# Suite Ambiente Nariño

Plugin de WordPress para **observar y analizar datos ambientales del departamento de Nariño**. Consulta 13 APIs abiertas que se actualizan de forma continua (clima, calidad del aire, ríos, océano Pacífico, sismos, volcanes, eventos naturales, focos de calor, radiación solar y biodiversidad), las presenta en **47 visualizaciones** con D3.js y D3plus v4, calcula para cada una un análisis cualitativo y otro cuantitativo, y publica los datos como **datos abiertos**.

- **Versión:** 1.0.0
- **Requiere:** WordPress 6.0 o superior y PHP 7.4 o superior, con OpenSSL (para cifrar llaves de API).
- **Licencia:** GPL-2.0-or-later
- **Autor:** Gobernación de Nariño · Secretaría TIC, Innovación y Gobierno Abierto

## Instalación

1. Copie la carpeta `suite-ambiente-narino/` en `wp-content/plugins/`, o comprímala en un `.zip` y súbala desde **Plugins → Añadir nuevo → Subir plugin**.
2. Active el plugin. Se crean dos tablas (`san_cache` y `san_logs`) y dos tareas programadas: sincronización cada hora y mantenimiento diario.
3. Vaya a **Suite Ambiente → Configuración → APIs** y pulse **«Probar todas»** para verificar que el servidor llega a cada fuente.
4. Inserte una tarjeta en cualquier página, por ejemplo `[san_card id="clima_mapa_temperatura"]`, o el tablero completo con `[san_tablero]`.

El servidor debe poder hacer peticiones HTTPS salientes a los hosts de las fuentes (lista en [docs/investigacion-apis.md](../docs/investigacion-apis.md)). No se necesita compilar nada ni instalar dependencias.

## Módulos del panel

El menú **Suite Ambiente** tiene tres módulos.

### Gráficos

- Una **pestaña por API** y, dentro de cada una, **subgrupos** (por ejemplo, en Open-Meteo Clima: «Condiciones actuales», «Patrones» y «Pronóstico por municipio»).
- Cada visualización es una **tarjeta** con cinco shortcodes listos para copiar:

| Shortcode | Inserta |
|---|---|
| `[san_grafico id="…"]` | El gráfico |
| `[san_descripcion id="…"]` | La descripción y cómo leerlo |
| `[san_analisis_cualitativo id="…"]` | El análisis cualitativo |
| `[san_analisis_cuantitativo id="…"]` | El análisis cuantitativo |
| `[san_card id="…"]` | Todo lo anterior en una tarjeta |

- Desde la tarjeta se personalizan el municipio, el tipo de gráfico y el alto; los shortcodes se actualizan solos. El botón **«Vista previa»** dibuja el gráfico con datos reales.
- La pestaña **Complementos** documenta los atributos y los shortcodes de tablero, mapa, datos y estado.

### Datos abiertos

- **19 recursos** descargables en JSON, CSV o GeoJSON: condiciones actuales de los 64 municipios, ICA, caudales, sismos, focos de calor, biodiversidad, series del IDEAM, límites municipales y más.
- Cada recurso muestra su URL en la API REST, su shortcode `[san_datos recurso="…"]`, sus campos, su fuente y licencia, y una vista previa de 10 filas.
- `[san_datos_abiertos]` publica el catálogo completo en una página.

### Configuración

| Pestaña | Contenido |
|---|---|
| **Tablero** | Seis indicadores del estado: fuentes activas, fuentes funcionando, errores y advertencias de las últimas 24 h, entradas en caché y próxima sincronización y la configuración del tablero público `[san_tablero]`: título, visualizaciones, orden, columnas, análisis y frecuencia de actualización. |
| **APIs** | Una ficha por fuente: activar o desactivar, tiempo de caché, tiempo de espera, parámetros (por ejemplo, días de pronóstico o radio de búsqueda de sismos), llave cuando aplica, **«Probar ahora»**, **«Vaciar caché»** e historial de las últimas verificaciones. |
| **Registros** | Registro de llamadas, errores y tareas programadas, con filtros por nivel y fuente, búsqueda de texto, exportación a CSV y purga. Las llaves de API nunca aparecen en los registros. |
| **General** | Librerías locales o jsDelivr (con SRI), tipografía institucional, municipio por defecto, tema claro, oscuro o automático, renderizador SVG o Canvas, controles de D3plus v4, atribución, límite de la API pública, nivel y retención de registros. |

## Uso rápido

```text
[san_card id="aire_ica_municipios"]
[san_grafico id="clima_pronostico_temperatura" municipio="52835" tipo="area"]
[san_analisis_cualitativo id="hidro_caudal_rios"]
[san_mapa variable="lluvia"]
[san_tablero ids="clima_mapa_temperatura,sismos_mapa,bio_reinos" columnas="3"]
[san_datos recurso="sismos_sgc" formato="csv,geojson"]
[san_estado_apis]
```

- El catálogo completo de ids, tipos y atributos está en [docs/catalogo-visualizaciones.md](../docs/catalogo-visualizaciones.md).
- Los códigos de municipio son DIVIPOLA: `52001` Pasto, `52835` Tumaco, `52356` Ipiales, etc.

## Fuentes de datos

| Categoría | Fuente | Llave |
|---|---|---|
| Clima y pronóstico | Open-Meteo (64 municipios) | no |
| Calidad del aire | Open-Meteo Air Quality (CAMS); ICA según Res. 2254 de 2017 | no |
| Ríos | Open-Meteo Flood (GloFAS): Patía, Mira y Guáitara | no |
| Estaciones | IDEAM en datos.gov.co: lluvia, temperatura, humedad y nivel de ríos | no (token opcional) |
| Océano Pacífico | Open-Meteo Marine: Tumaco y Sanquianga | no |
| Sismos | Servicio Geológico Colombiano y USGS | no |
| Volcanes | Servicio Geológico Colombiano | no (ver aviso) |
| Eventos naturales | GDACS | no |
| Focos de calor | NASA FIRMS | opcional (MAP_KEY) |
| Radiación y agroclima | NASA POWER | no |
| Biodiversidad | GBIF (SiB Colombia) e iNaturalist | no |

Cada gráfico muestra al pie la fuente, su licencia y la hora de la última actualización.

## Avisos importantes

- **Volcanes (SGC):** el Servicio Geológico Colombiano bloquea el acceso automatizado a su archivo de volcanes (HTTP 403). El plugin no evade ese bloqueo por defecto, y la visualización de volcanes muestra «fuente no disponible». Existe un modo de acceso «como navegador» que **solo debe activarse con autorización del SGC**.
- **Open-Meteo:** el plan gratuito es para **uso no comercial** y tiene un límite de 10 000 llamadas al día; el plugin consume unas 4 000 a 4 500 con la configuración por defecto. **No vacíe la caché de Open-Meteo varias veces seguidas**: puede recibir HTTP 429. Si ocurre, el plugin sigue mostrando la última copia guardada.
- **IDEAM:** los datos se cargan en datos.gov.co una vez al día, con datos hasta el día anterior.
- **WP-Cron:** en producción conviene ejecutar WP-Cron desde el cron del servidor. Ver [docs/arquitectura.md](../docs/arquitectura.md).

## Identidad visual

La interfaz sigue el **Manual de Identidad Visual** y el **Manual de sitios web** de la Gobernación de Nariño:

- **Colores:** verde `#10A13B`, amarillo `#FFD500`, azul `#003366` y texto `#4A4A4A`.
- **Tipografías:** Hind Madurai para títulos y Nunito Sans para texto.
- **Paletas:** la paleta categórica de los gráficos se validó para daltonismo en modo claro y oscuro. Las escalas de magnitud usan un solo tono. Los colores del ICA son los oficiales de la Resolución 2254 de 2017.
- **Accesibilidad:** cada gráfico tiene una vista de tabla y los estados se indican con ícono y texto, no solo con color.

## Desinstalación

Al **eliminar** el plugin desde WordPress (no solo desactivarlo) se borran sus tablas, opciones, transitorios y tareas programadas.

## Documentación

- [Arquitectura y extensión](../docs/arquitectura.md)
- [Catálogo de visualizaciones y shortcodes](../docs/catalogo-visualizaciones.md)
- [Investigación de APIs](../docs/investigacion-apis.md) y [anexo de public-apis](../docs/anexo-public-apis.md)
- [D3plus v4](../docs/d3plus-v4.md)
