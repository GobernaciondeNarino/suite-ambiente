# Catálogo de visualizaciones y shortcodes

El plugin trae **47 visualizaciones predefinidas** sobre 13 fuentes y **19 recursos de datos abiertos**. Este catálogo se generó desde el propio plugin (`SAN_Catalogo::por_fuente()` y `SAN_Datos_Abiertos::definiciones()`); si agrega o cambia visualizaciones, regenérelo con el comando del final.

En el panel, cada visualización es una tarjeta en **Suite Ambiente → Gráficos**, organizada en una pestaña por API y en subgrupos dentro de cada pestaña. La tarjeta trae cinco shortcodes listos para copiar y un botón de vista previa.

## Los cinco shortcodes de cada tarjeta

| Shortcode | Qué inserta |
|---|---|
| `[san_grafico id="…"]` | El gráfico, con su barra de herramientas: selector de municipio (si aplica), selector de tipo, tabla de datos y descargas CSV, JSON y PNG. |
| `[san_descripcion id="…"]` | Qué muestra el gráfico y cómo leerlo. |
| `[san_analisis_cualitativo id="…"]` | Lectura en lenguaje claro del estado actual, con recomendaciones cuando aplica (por ejemplo, ICA o índice UV altos). |
| `[san_analisis_cuantitativo id="…"]` | Cifras clave calculadas sobre los datos: promedio, mediana, mínimo, máximo, percentiles, tendencia y variación. |
| `[san_card id="…"]` | La tarjeta completa: título, gráfico, descripción y los dos análisis. |

Los análisis se calculan en el servidor a partir de los datos vigentes; no son textos fijos.

### Atributos de `san_grafico`, `san_card` y los análisis

| Atributo | Valores | Por defecto | Efecto |
|---|---|---|---|
| `id` | id de la tabla de abajo | — | Visualización (obligatorio). |
| `municipio` | código DIVIPOLA de 5 dígitos (`52001` = Pasto) | el de Configuración → General | Solo en visualizaciones con columna «Municipio = sí». |
| `tipo` | uno de los «Tipos» de la visualización | el primero de la lista | Tipo de gráfico inicial. |
| `alto` | 200 a 1200 | 420 | Alto en píxeles. |
| `selector` | `auto`, `si`, `no` | `auto` | Muestra el selector de municipio. |
| `herramientas` | `si`, `no` | `si` | Muestra la barra de herramientas. |
| `titulo` | `si`, `no` | `si` | Muestra el título. |
| `analisis` | `si`, `no` | `si` | En `san_card`, muestra los análisis. |
| `tema` | `claro`, `oscuro`, `auto` | el de Configuración → General | Paleta clara u oscura. |

### Shortcodes complementarios

| Shortcode | Qué inserta |
|---|---|
| `[san_tablero]` | El tablero configurado en Configuración → Tablero. Acepta `ids="a,b,c"`, `columnas`, `titulo` y `analisis`. |
| `[san_mapa variable="temperatura"]` | Atajo a un mapa coroplético. Variables: `temperatura`, `lluvia`, `uv`, `ica` y `biodiversidad`. Acepta `alto` (520). |
| `[san_datos recurso="…"]` | Botones de descarga de un recurso de datos abiertos. Acepta `formato="csv,json"`, `texto` y `municipio`. |
| `[san_datos_abiertos]` | Catálogo público de todos los recursos con sus descargas. |
| `[san_estado_apis]` | Estado público de las fuentes (en funcionamiento, con fallas, sin verificar). |

## Motores y tipos de gráfico

- **D3plus v4** (`@d3plus/core` 4.5.0) dibuja las formas estadísticas: líneas, áreas, barras, torta, dona, mapa de árbol, burbujas, radar, cajas y bigotes, dispersión, ranking, cuerdas (Chord), Sankey y matriz. Ver [d3plus-v4.md](d3plus-v4.md).
- **D3 v7** dibuja lo que D3plus no resuelve bien con la identidad institucional: mapas coropléticos con los polígonos oficiales de los 64 municipios, mapas de puntos, mapas de calor, medidores y rosas de viento u oleaje.

El tipo de gráfico se elige según la **forma de los datos**. En el plugin, cada visualización solo ofrece los tipos compatibles con su forma:

| Forma de los datos | Tipos compatibles |
|---|---|
| Serie temporal | líneas, área, barras, áreas apiladas, barras apiladas, cajas |
| Categorías | barras, barras horizontales, torta, dona, mapa de árbol, burbujas, radar |
| Flujos | cuerdas (Chord), Sankey |
| Geografía | mapa coroplético, mapa de puntos |
| Matriz | mapa de calor, matriz |
| Indicador | medidor |

## Visualizaciones por fuente

### Open-Meteo · Clima y pronóstico

| id | Visualización | Subgrupo | Motor | Tipos | Municipio |
|---|---|---|---|---|---|
| `clima_mapa_temperatura` | Temperatura actual en los 64 municipios | Condiciones actuales | D3 v7 | Mapa coroplético, Barras horizontales | — |
| `clima_mapa_lluvia` | Lluvia esperada hoy por municipio | Condiciones actuales | D3 v7 | Mapa coroplético, Barras horizontales | — |
| `clima_mapa_uv` | Índice UV máximo de hoy | Condiciones actuales | D3 v7 | Mapa coroplético, Barras horizontales | — |
| `clima_subregiones_lluvia` | Lluvia pronosticada a 7 días por subregión | Condiciones actuales | D3plus v4 | Barras horizontales, Barras, Mapa de árbol, Radar | — |
| `clima_box_subregiones` | Distribución de temperaturas máximas por subregión | Patrones | D3plus v4 | Cajas y bigotes | — |
| `clima_transiciones` | Persistencia y cambio del estado del tiempo | Patrones | D3plus v4 | Cuerdas (Chord), Matriz | — |
| `clima_pronostico_temperatura` | Pronóstico de temperatura | Pronóstico por municipio | D3plus v4 | Líneas, Área, Barras | sí |
| `clima_pronostico_lluvia` | Pronóstico de lluvia | Pronóstico por municipio | D3plus v4 | Barras, Líneas, Área | sí |
| `clima_calor_horario` | Temperatura hora a hora (7 días) | Pronóstico por municipio | D3 v7 | Mapa de calor | sí |
| `clima_rosa_vientos` | Rosa de vientos (7 días) | Pronóstico por municipio | D3 v7 | Rosa | sí |

### Open-Meteo · Calidad del aire (CAMS)

| id | Visualización | Subgrupo | Motor | Tipos | Municipio |
|---|---|---|---|---|---|
| `aire_ica_municipios` | Índice de Calidad del Aire (ICA) por municipio | Estado actual | D3 v7 | Mapa coroplético, Barras horizontales | — |
| `aire_ica_medidor` | ICA del municipio | Por municipio | D3 v7 | Medidor | sí |
| `aire_pronostico_pm` | Pronóstico de material particulado (5 días) | Por municipio | D3plus v4 | Líneas, Área | sí |
| `aire_contaminantes` | Contaminantes frente a las guías de la OMS | Por municipio | D3plus v4 | Radar, Barras, Barras horizontales | sí |

### Open-Meteo · Caudal de ríos (GloFAS)

| id | Visualización | Subgrupo | Motor | Tipos | Municipio |
|---|---|---|---|---|---|
| `hidro_caudal_rios` | Caudal de los ríos frente a su media histórica | Caudales | D3plus v4 | Líneas, Área | — |
| `hidro_estado_rios` | Estado actual de los ríos | Caudales | D3plus v4 | Barras horizontales, Barras, Radar | — |

### IDEAM · Estaciones automáticas (datos.gov.co)

| id | Visualización | Subgrupo | Motor | Tipos | Municipio |
|---|---|---|---|---|---|
| `ideam_lluvia_estaciones` | Lluvia observada por estación | Precipitación observada | D3plus v4 | Barras horizontales, Barras, Mapa de árbol | — |
| `ideam_lluvia_diaria` | Lluvia diaria en la red de estaciones | Precipitación observada | D3plus v4 | Barras, Líneas | — |
| `ideam_temperatura` | Temperatura observada por estación | Temperatura observada | D3plus v4 | Cajas y bigotes | — |
| `ideam_nivel_rios` | Nivel de los ríos en las estaciones hidrológicas | Nivel de ríos | D3 v7 | Mapa de calor | — |

### Open-Meteo · Océano Pacífico

| id | Visualización | Subgrupo | Motor | Tipos | Municipio |
|---|---|---|---|---|---|
| `oceano_oleaje` | Altura del oleaje (7 días) | Oleaje | D3plus v4 | Líneas, Área | — |
| `oceano_rosa_oleaje` | Dirección del oleaje en Tumaco | Oleaje | D3 v7 | Rosa | — |
| `oceano_temperatura` | Temperatura superficial del mar | Temperatura del mar | D3plus v4 | Líneas, Área | — |

### Servicio Geológico Colombiano · Sismos

| id | Visualización | Subgrupo | Motor | Tipos | Municipio |
|---|---|---|---|---|---|
| `sismos_mapa` | Sismos recientes en Nariño y su entorno | Sismicidad | D3 v7 | Mapa de puntos | — |
| `sismos_diarios` | Número de sismos por día | Sismicidad | D3plus v4 | Barras, Líneas, Área | — |
| `sismos_magnitud_prof` | Magnitud y profundidad | Sismicidad | D3plus v4 | Dispersión | — |
| `sismos_clases` | Sismos por rango de magnitud | Sismicidad | D3plus v4 | Dona, Torta, Barras | — |

### Servicio Geológico Colombiano · Volcanes

| id | Visualización | Subgrupo | Motor | Tipos | Municipio |
|---|---|---|---|---|---|
| `volcanes_alerta` | Nivel de alerta de los volcanes | Volcanes | D3 v7 | Mapa de puntos, Barras horizontales | — |

### USGS · Sismicidad regional

| id | Visualización | Subgrupo | Motor | Tipos | Municipio |
|---|---|---|---|---|---|
| `usgs_regional` | Sismicidad regional (último año) | Contexto regional | D3 v7 | Mapa de puntos | — |
| `usgs_magnitud_distancia` | Magnitud frente a distancia a Pasto | Contexto regional | D3plus v4 | Dispersión | — |

### GDACS · Alertas de desastres

| id | Visualización | Subgrupo | Motor | Tipos | Municipio |
|---|---|---|---|---|---|
| `eventos_tipos` | Eventos naturales del último año por tipo | Alertas GDACS | D3plus v4 | Dona, Torta, Barras, Mapa de árbol | — |
| `eventos_mapa` | Mapa de eventos con alerta | Alertas GDACS | D3 v7 | Mapa de puntos | — |
| `eventos_mensual` | Eventos por mes | Alertas GDACS | D3plus v4 | Barras apiladas, Barras, Áreas apiladas | — |

### NASA FIRMS · Focos de calor

| id | Visualización | Subgrupo | Motor | Tipos | Municipio |
|---|---|---|---|---|---|
| `incendios_mapa` | Focos de calor detectados por satélite | Focos de calor | D3 v7 | Mapa de puntos | — |
| `incendios_municipios` | Focos de calor por municipio | Focos de calor | D3plus v4 | Barras horizontales, Barras, Mapa de árbol | — |
| `incendios_diarios` | Focos de calor por día | Focos de calor | D3plus v4 | Barras, Líneas | — |

### NASA POWER · Radiación y agroclima

| id | Visualización | Subgrupo | Motor | Tipos | Municipio |
|---|---|---|---|---|---|
| `power_radiacion` | Radiación solar diaria | Energía solar | D3plus v4 | Área, Líneas, Barras | sí |
| `power_temperatura` | Temperatura diaria observada por satélite | Agroclima | D3plus v4 | Líneas, Área | sí |
| `power_lluvia_mensual` | Lluvia mensual estimada | Agroclima | D3plus v4 | Barras, Líneas, Área | sí |
| `power_box_mensual` | Variabilidad de la temperatura por mes | Agroclima | D3plus v4 | Cajas y bigotes | sí |

### GBIF · Biodiversidad (SiB Colombia)

| id | Visualización | Subgrupo | Motor | Tipos | Municipio |
|---|---|---|---|---|---|
| `bio_reinos` | Registros de biodiversidad por reino | Composición | D3plus v4 | Mapa de árbol, Dona, Torta, Barras, Burbujas | — |
| `bio_clases` | Clases taxonómicas más registradas | Composición | D3plus v4 | Barras horizontales, Barras, Mapa de árbol | — |
| `bio_amenaza` | Registros por categoría de amenaza (UICN) | Conservación | D3plus v4 | Dona, Torta, Barras, Barras horizontales | — |
| `bio_anual` | Registros de biodiversidad por año | Tendencias | D3plus v4 | Barras, Líneas, Área | — |
| `bio_mapa` | Registros de biodiversidad por municipio | Territorio | D3 v7 | Mapa coroplético, Barras horizontales | — |

### iNaturalist · Ciencia ciudadana

| id | Visualización | Subgrupo | Motor | Tipos | Municipio |
|---|---|---|---|---|---|
| `inat_grupos` | Observaciones ciudadanas recientes por grupo | Ciencia ciudadana | D3plus v4 | Dona, Mapa de árbol, Barras, Torta | — |
| `inat_diario` | Observaciones ciudadanas por día | Ciencia ciudadana | D3plus v4 | Barras, Líneas, Área | — |

## Recursos de datos abiertos

Cada recurso se publica en la API REST pública `/wp-json/suite-ambiente/v1/datos/{recurso}` con `?formato=json|csv|geojson`. Los recursos que admiten municipio aceptan `?municipio=52001`. También están en **Suite Ambiente → Datos abiertos**, con vista previa, URL y shortcode.

| Recurso | Título | Fuente | Formatos | Parámetros |
|---|---|---|---|---|
| `municipios` | Municipios de Nariño | DANE · Gobernación de Nariño | json, csv, geojson | — |
| `geo_municipios` | Límites municipales (GeoJSON) | DANE · Gobernación de Nariño | geojson | — |
| `geo_subregiones` | Límites de subregiones (GeoJSON) | DANE · Gobernación de Nariño | geojson | — |
| `clima_actual_municipios` | Condiciones meteorológicas actuales por municipio | Open-Meteo · Clima y pronóstico | json, csv, geojson | — |
| `clima_pronostico_diario` | Pronóstico diario por municipio | Open-Meteo · Clima y pronóstico | json, csv | municipio |
| `aire_ica_municipios` | Calidad del aire (ICA) por municipio | Open-Meteo · Calidad del aire (CAMS) | json, csv, geojson | — |
| `caudal_rios` | Caudal diario de los ríos (GloFAS) | Open-Meteo · Caudal de ríos (GloFAS) | json, csv | — |
| `ideam_precipitacion` | Precipitación diaria observada (IDEAM) | IDEAM · Estaciones automáticas (datos.gov.co) | json, csv, geojson | — |
| `ideam_temperatura` | Temperatura diaria observada (IDEAM) | IDEAM · Estaciones automáticas (datos.gov.co) | json, csv, geojson | — |
| `ideam_nivel` | Nivel diario de ríos (IDEAM) | IDEAM · Estaciones automáticas (datos.gov.co) | json, csv, geojson | — |
| `oceano_horario` | Oleaje y temperatura del mar (horario) | Open-Meteo · Océano Pacífico | json, csv | — |
| `sismos_sgc` | Sismos recientes (SGC) | Servicio Geológico Colombiano · Sismos | json, csv, geojson | — |
| `volcanes` | Nivel de actividad de los volcanes | Servicio Geológico Colombiano · Volcanes | json, csv, geojson | — |
| `sismos_usgs` | Sismicidad regional (USGS) | USGS · Sismicidad regional | json, csv, geojson | — |
| `eventos_gdacs` | Eventos naturales con alerta (GDACS) | GDACS · Alertas de desastres | json, csv, geojson | — |
| `focos_calor` | Focos de calor en Nariño (FIRMS) | NASA FIRMS · Focos de calor | json, csv, geojson | — |
| `power_diario` | Agroclima diario (NASA POWER) | NASA POWER · Radiación y agroclima | json, csv | municipio |
| `biodiversidad_resumen` | Resumen de registros de biodiversidad (GBIF) | GBIF · Biodiversidad (SiB Colombia) | json, csv | — |
| `inat_recientes` | Observaciones ciudadanas recientes (iNaturalist) | iNaturalist · Ciencia ciudadana | json, csv, geojson | — |


Además, cada visualización se puede descargar como CSV en `/wp-json/suite-ambiente/v1/visualizaciones/{id}?formato=csv`.

## Regenerar este catálogo

Con WP-CLI, desde la raíz de un WordPress con el plugin activo:

```bash
wp eval 'use GobernacionNarino\SuiteAmbiente as S;
foreach ( S\SAN_Catalogo::por_fuente() as $f => $subs )
  foreach ( $subs as $sg => $ids )
    foreach ( $ids as $id ) { $d = S\SAN_Catalogo::obtener( $id );
      echo "$f\t$sg\t$id\t{$d["titulo"]}\t" . implode( ",", $d["tipos"] ) . "\n"; }'
```
