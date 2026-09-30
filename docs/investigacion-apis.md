# Investigación de APIs ambientales para Nariño

Este documento reúne la investigación de fuentes de datos ambientales para el plugin **Suite Ambiente Nariño**. Tiene dos partes:

- **Parte I:** qué fuentes se implementaron en el plugin, con qué parámetros y qué limitaciones se encontraron durante el desarrollo.
- **Parte II:** el informe de pruebas en vivo de las APIs candidatas, hecho el 2026-09-30.

El punto de partida fue el repositorio [public-apis/public-apis](https://github.com/public-apis/public-apis). La tabla completa de candidatos revisados está en el [Anexo A](anexo-public-apis.md). A esa lista se sumaron fuentes oficiales colombianas que no figuran en public-apis (SGC, IDEAM en datos.gov.co) y servicios científicos de uso común (NASA FIRMS, NASA POWER, GBIF, iNaturalist, GDACS).

El criterio principal fue el que pidió la Gobernación: **datos que se actualicen de forma constante**, que cubran Nariño y que se puedan consultar sin llave o con una llave gratuita.

## Parte I — Fuentes implementadas en el plugin

### Las 13 fuentes

Cada fuente es una clase `SAN_Fuente_*` en `suite-ambiente-narino/includes/fuentes/`. Todas se configuran en **Suite Ambiente → Configuración → APIs**: activar o desactivar, tiempo de caché (TTL), tiempo de espera, parámetros y, cuando aplica, la llave. Desde la misma pestaña se prueban en vivo con «Probar ahora».

| id | Fuente | Categoría | Host permitido | Frecuencia de la fuente | TTL por defecto | Llave | Licencia |
|---|---|---|---|---|---|---|---|
| `openmeteo_clima` | Open-Meteo · Clima y pronóstico | Clima y tiempo | api.open-meteo.com | Cada hora (actual: 15 min) | 60 min | no | CC BY 4.0 |
| `openmeteo_aire` | Open-Meteo · Calidad del aire (CAMS) | Calidad del aire | air-quality-api.open-meteo.com | Cada hora (modelo CAMS) | 60 min | no | CC BY 4.0 |
| `openmeteo_hidro` | Open-Meteo · Caudal de ríos (GloFAS) | Agua y ríos | flood-api.open-meteo.com | Diaria (pronóstico a 30 días) | 360 min | no | CC BY 4.0 |
| `ideam` | IDEAM · Estaciones automáticas (datos.gov.co) | Red de estaciones IDEAM | www.datos.gov.co | Diaria (carga ≈ 01:15 hora de Colombia) | 180 min | no (app token opcional) | CC BY-SA 4.0 |
| `openmeteo_marino` | Open-Meteo · Océano Pacífico | Océano Pacífico | marine-api.open-meteo.com | Cada hora (oleaje cada 6–12 h) | 120 min | no | CC BY 4.0 |
| `sgc_sismos` | Servicio Geológico Colombiano · Sismos | Sismos y volcanes | api.sgc.gov.co | Continua (≈ 30 min de retraso) | 15 min | no | Información pública (Ley 1712 de 2014) |
| `sgc_volcanes` | Servicio Geológico Colombiano · Volcanes | Sismos y volcanes | archive.sgc.gov.co | Al cambiar el nivel y con cada boletín semanal | 60 min | no | Información pública (Ley 1712 de 2014) |
| `usgs` | USGS · Sismicidad regional | Sismos y volcanes | earthquake.usgs.gov | Continua (≈ 1 min) | 30 min | no | Dominio público (USGS) |
| `gdacs` | GDACS · Alertas de desastres | Eventos naturales | www.gdacs.org | Continua | 120 min | no | Uso con atribución |
| `firms` | NASA FIRMS · Focos de calor | Incendios y focos de calor | firms.modaps.eosdis.nasa.gov | Cada paso de satélite (≈ 3 h de latencia) | 60 min | opcional (MAP_KEY) | Datos abiertos NASA (con cita) |
| `nasa_power` | NASA POWER · Radiación y agroclima | Radiación solar y clima de superficie | power.larc.nasa.gov | Diaria (rezago de 3 a 5 días) | 720 min | no | Datos abiertos NASA (con cita) |
| `gbif` | GBIF · Biodiversidad (SiB Colombia) | Biodiversidad | api.gbif.org | Diaria | 1 440 min | no | CC0 / CC BY / CC BY-NC (según registro) |
| `inaturalist` | iNaturalist · Ciencia ciudadana | Biodiversidad | api.inaturalist.org | Continua | 180 min | no | CC0 / CC BY / CC BY-NC (según observación) |

### Cómo se consulta cada fuente

| Fuente | Consulta que hace el plugin | Parámetros configurables (valor por defecto) |
|---|---|---|
| Open-Meteo Clima | Una sola llamada con los **64 municipios** (centroides) para `current` y `daily` a 7 días; una llamada por municipio para el pronóstico detallado (`hourly` y `daily`). | `dias` de pronóstico (10) |
| Open-Meteo Aire | Una llamada con los 64 municipios (contaminantes actuales y PM horario); una por municipio para la serie de 5 días. El ICA se calcula con los cortes de la **Resolución 2254 de 2017** del MADS. | — |
| Open-Meteo Caudal | Cuatro puntos de río: Patía bajo (2.0, −78.5), Mira (1.4, −78.7), Patía medio (1.9, −77.8) y Guáitara (1.0, −77.5). Los puntos fueron validados en la Parte II: otros puntos devolvían `null` o un afluente menor. | — |
| IDEAM | SoQL agregado por día y estación sobre cuatro conjuntos: precipitación `s54a-sgyg`, temperatura `sbwg-7ju4`, humedad `uext-mhny` y nivel de río `bdmn-sqnh`. Filtra con `upper(departamento)='NARIÑO'` (ver Parte II). | `dias` (7), `app_token` (vacío) |
| Open-Meteo Océano | Punto frente a Tumaco (1.85, −78.85) o frente a Sanquianga (2.55, −78.55). | `punto` (tumaco) |
| SGC Sismos | Catálogo quincenal nacional; el plugin recorta al polígono de Nariño más un margen y asigna el municipio por punto en polígono. | `dias` (15), `margen` en grados (0.3) |
| SGC Volcanes | Archivo `volcanos.json` con el nivel de actividad y los boletines del OVS Pasto. Ver la nota de acceso más abajo. | `acceso` (estandar) |
| USGS | FDSN `query` con radio desde Pasto. | `radio_km` (400), `min_mag` (2.5), `dias` (365) |
| GDACS | Eventos por país. | `paises` (Colombia;Ecuador) |
| NASA FIRMS | Sin llave: CSV público de Sudamérica por sensor y ventana, recortado al polígono de Nariño. Con MAP_KEY: API por área. La llave se guarda cifrada (AES-256-GCM). | `sensor` (noaa20), `ventana` (7d) |
| NASA POWER | Serie diaria en Pasto de T2M, T2M_MAX, T2M_MIN, PRECTOTCORR, ALLSKY_SFC_SW_DWN, RH2M y WS2M. El valor −999 se trata como dato faltante. | `dias` (180) |
| GBIF | `occurrence/search` con `gadmGid=COL.22_2` (Nariño) y facetas por reino, clase, categoría UICN, año y municipio (GADM nivel 2). Los nombres de taxón se guardan 30 días. | — |
| iNaturalist | `place_id=12737` (Nariño): conteo por grupo icónico, histograma diario y observaciones recientes. | `dias` (30) |

### Candidatas verificadas que no entraron en la versión 1.0

La Parte II marca como «ADOPT» varias fuentes que no se implementaron. Para mantener el alcance de la v1.0 y el cupo de Open-Meteo, se dejó **una fuente principal por categoría** y se priorizó lo que se actualiza con más frecuencia. Estas quedan como ampliaciones posibles:

| Fuente | Qué aportaría |
|---|---|
| Open-Meteo Archive | Historial de 30 días por municipio (hoy lo cubre en parte NASA POWER para Pasto). |
| Open-Meteo Climate (CMIP6) | Proyecciones climáticas 2025–2050. Es estático: bastaría una consulta al mes. |
| INPE Queimadas | Segunda fuente de focos de calor, cada 10 minutos, con municipio ya asignado. Sería el respaldo de FIRMS. |
| NASA EONET | Eventos naturales (complemento de GDACS). |
| MET Norway | Respaldo del pronóstico si Open-Meteo falla. Exige un User-Agent identificable. |
| NOAA PTWC | Boletines de tsunami para la costa Pacífica. |
| IDEAM `57sv-p2fu` | Ventana móvil de ≈ 12 h, más reciente que las tablas por variable. |

Una fuente nueva se agrega con una clase `SAN_Fuente_*` registrada con el filtro `san_clases_fuentes` y sus visualizaciones con el filtro `san_clases_visualizaciones` (ver [arquitectura](arquitectura.md)).

### Notas operativas encontradas durante el desarrollo

**SGC · Volcanes: el acceso automatizado está bloqueado.**

- En las pruebas de la Parte II el archivo respondió 200. Durante el desarrollo del plugin empezó a responder **HTTP 403** a los clientes que no se identifican como navegador web. El bloqueo depende del User-Agent, no del Referer.
- El plugin se identifica con su propio User-Agent (`SuiteAmbienteNarino/1.0 (+sitio)`) y **no evade el bloqueo por defecto**. Mientras el SGC mantenga la restricción, la visualización `volcanes_alerta` muestra un mensaje de fuente no disponible.
- El parámetro `acceso` tiene una opción «Como navegador web (solo con autorización del SGC)». Debe activarse **solo después de acordarlo formalmente con el SGC**. Lo recomendable es que la Gobernación solicite al SGC un servicio de datos abiertos o una autorización expresa para este uso.

**Open-Meteo: cupo gratuito y uso no comercial.**

- El plan gratuito permite 600 llamadas por minuto, 5 000 por hora y 10 000 por día, y es para **uso no comercial**. Un portal institucional sin publicidad parece encajar, pero conviene confirmarlo con Open-Meteo. Si no encaja, existe un plan comercial con llave.
- Cada ubicación de una consulta por lotes cuenta como una llamada, y las consultas con más de 10 variables cuentan como fracciones adicionales. Con la configuración por defecto (caché de 60 min y sincronización horaria), el consumo estimado es de **4 000 a 4 500 llamadas al día**: ≈ 2 500 del clima de 64 municipios, ≈ 1 500 del aire, y el resto en pronósticos por municipio, caudales y océano. Es una estimación, no una medición.
- Durante las pruebas se recibió **HTTP 429** después de vaciar la caché varias veces seguidas (cada recarga de los 64 municipios son 64 llamadas). En producción esto se mitiga porque el plugin sirve la última copia en caché cuando la fuente falla (*stale-if-error*) y marca el dato como vencido. **No vacíe la caché de Open-Meteo repetidamente.**

**IDEAM.** La carga en datos.gov.co es diaria (≈ 01:15 hora de Colombia) con datos hasta el día anterior. El nombre del departamento cambió de `NARIÑO` a `Nariño` en julio de 2026; por eso el filtro usa `upper()`. El *app token* de Socrata es opcional y solo sube el límite de peticiones.

**NASA FIRMS.** Sin llave se usa el CSV público continental (unos cientos de KB), que el plugin recorta al polígono de Nariño y guarda ya procesado en caché. Con MAP_KEY gratuita se consulta solo el área del departamento.

**GBIF.** Hubo un tiempo de espera de conexión transitorio. El tiempo de espera por defecto es de 25 s y el cliente HTTP reintenta una vez ante errores de red.

**Atribución.** Cada gráfico muestra al pie la fuente, su licencia y la hora de actualización. La atribución se puede desactivar en Configuración → General, pero las licencias CC BY y CC BY-SA la exigen.

## Parte II — Informe de pruebas en vivo (2026-09-30)

Esta parte registra lo observado el día de las pruebas. Las decisiones posteriores están en la Parte I.

- **Fecha de las pruebas:** 2026-09-30, entre las 12:55 y las 13:20 UTC (07:55 a 08:20, hora de Colombia).
- **Método:** cada API se probó con `curl` desde un contenedor que sale a internet por un proxy HTTPS. La latencia medida incluye el proxy y sirve para comparar APIs entre sí, no como cifra absoluta.
- **Área:** departamento de Nariño, 64 municipios, bbox lat 0.35..2.70 y lon −79.05..−76.80. Puntos de referencia: Pasto 1.2136,−77.2811; Tumaco 1.8067,−78.7647; Ipiales 0.8303,−77.6445.
- **Muestras:** se guardó una respuesta real recortada de cada API (47 archivos). No se incluyen en el repositorio; se pueden regenerar con las URL de la sección 4.
- **Convenciones:**
  - «Verificado» significa que se observó en la respuesta real o que figura en la documentación oficial descargada en esta sesión.
  - Lo marcado **[no verificado]** viene de conocimiento previo o de una deducción, y hay que confirmarlo antes de usarlo en producción.

### 1. Resumen ejecutivo

Con ocho fuentes sin llave, todas verificadas y de actualización continua, se cubre casi todo lo que necesita el plugin:

1. **Open-Meteo (Forecast, Air Quality, Flood, Marine, Archive):**
   - Cubre clima, aire, caudales, océano e historial de 30 días.
   - Admite **lotes de varias ubicaciones en una sola llamada**: se probaron los 64 municipios juntos (200, 0.65 s, 53 KB).
   - Envía CORS `*`.
   - Licencia CC BY 4.0. El uso gratuito debe ser **no comercial** y no superar 10 000 llamadas al día.
2. **Servicio Geológico Colombiano (SGC)**, con dos endpoints **no documentados** pero públicos y vivos:
   - `api.sgc.gov.co/biweekly/biweekly_earthquakes`: sismos en GeoJSON, CORS `*`, con unos 30 minutos de retraso.
   - `archive.sgc.gov.co/volcanos/volcanos.json`: nivel de alerta y boletines semanales de Galeras, Cumbal, Chiles-Cerro Negro, Azufral, Doña Juana y Las Ánimas.
3. **IDEAM en datos.gov.co (Socrata SODA):** datos observados de estaciones de Nariño (precipitación, temperatura, humedad, presión, viento y nivel de río).
   - Se cargan una vez al día.
   - El dato más reciente en Nariño era de 2026-09-29T23:58 en las tablas por variable y de 2026-09-30T01:20 en la tabla de ventana móvil `57sv-p2fu`.
   - Hay una trampa: el campo `departamento` cambió de `NARIÑO` a `Nariño`. Por eso hay que filtrar con `upper(departamento)='NARIÑO'`; si no, los datos se ven congelados en 2026-07-24.
4. **Incendios:**
   - Los archivos públicos de NASA FIRMS (CSV de Sudamérica, sin llave) y del INPE Queimadas (CSV cada 10 minutos, sin llave, con campos `municipio` y `estado`) muestran focos en Nariño.
   - La API por área de FIRMS **sí exige** MAP_KEY.
5. **Biodiversidad:**
   - GBIF: se usa `gadmGid=COL.22_2`, que corresponde a Nariño (978 367 registros y facetas IUCN).
   - iNaturalist: se usa `place_id=12737`.
6. **Eventos y sismos complementarios:**
   - USGS FDSN y sus feeds: solo registran eventos de magnitud aproximada ≥ 4 en la región; hubo 0 dentro del bbox de Nariño en 365 días.
   - NASA EONET y GDACS.
7. **Radiación y agroclima:** NASA POWER, que va unos 3 días atrasado en meteorología y unos 5 días en radiación.

**Se rechazan para Nariño**, por evidencia directa:

- **RainViewer:** su mapa de cobertura no muestra radar sobre Nariño.
- **openSenseMap:** tiene 0 estaciones en el bbox.
- **IDEAM «Calidad del Aire en Colombia»:** no tiene registros de Nariño y su último dato nacional es de 2025-01-01.
- **Capa ArcGIS del SGC:** es histórica; el último evento de Nariño es de 1953.
- **7Timer!, wttr.in y Sunrise-Sunset:** repiten lo que ya da Open-Meteo.
- **kanari:** la respuesta viene truncada.

**Requieren llave (opcionales):** FIRMS Area, OpenAQ v3, WAQI, Global Forest Watch, OpenUV, eBird, IUCN, xeno-canto v3 y PurpleAir.

### 2. Tabla resumen de APIs probadas

Leyenda:

- **CORS:** el valor de `Access-Control-Allow-Origin` observado al enviar `Origin: https://example.org`.
- **Lote:** si admite varias ubicaciones o un área en una sola llamada.

| id | API | Categoría | Auth | HTTP · latencia | Dato más reciente observado | CORS | Lote | Veredicto |
|---|---|---|---|---|---|---|---|---|
| om_forecast | Open-Meteo Forecast | clima | ninguna | 200 · 0.53 s (64 ubic.: 0.65 s) | `current.time` 2026-09-30T07:45 local (paso de 15 min) | `*` | Sí | **ADOPT** |
| om_air | Open-Meteo Air Quality | aire | ninguna | 200 · 0.72 s | 07:00 local (horario) | `*` | Sí | **ADOPT** |
| om_flood | Open-Meteo Flood (GloFAS v4) | hidrología | ninguna | 200 · 0.84 s | valor diario del 2026-09-30 más 30 días | `*` | Sí | **ADOPT** |
| om_marine | Open-Meteo Marine | océano | ninguna | 200 · 0.56 s | 07:45 local | `*` | Sí | **ADOPT** |
| om_archive | Open-Meteo Historical | clima histórico | ninguna | 200 · 0.69 s | best_match hasta 09-30; ERA5 puro hasta 09-23 | `*` | Sí | **ADOPT** |
| om_climate | Open-Meteo Climate (CMIP6) | proyecciones | ninguna | 200 · 1.13 s | estático, 2025–2050 | `*` | Sí | **ADOPT (complementario)** |
| om_elev | Open-Meteo Elevation | auxiliar | ninguna | 200 · 0.79 s | estático | `*` | Sí | **ADOPT (auxiliar)** |
| usgs | USGS FDSN y feeds | sismos | ninguna | 200 · 0.2–0.9 s | feed de las 12:59:03Z; evento a las 12:57:22Z; 0 en Nariño en 365 días | `*` | bbox/radio | **ADOPT (complementario)** |
| sgc_sismos | SGC api biweekly | sismos | ninguna | 200 · 0.4 s (1 día) a 6.6 s (15 días, 1.3 MB) | nacional 12:31:09Z; Nariño 2026-09-25 11:17Z | `*` | nacional | **ADOPT (no documentada)** |
| sgc_volcanes | SGC volcanos.json | volcanes | ninguna | 200 · 0.52 s | Last-Modified 01:53 GMT; boletín de Galeras 09-29 | sin cabecera | 24 volcanes | **ADOPT (no documentada)** |
| eonet | NASA EONET v3 | eventos | ninguna | 200 · 0.81 s | en el bbox: inundación del 2026-07-20 | `*` | bbox | **ADOPT (complementario)** |
| gdacs | GDACS | eventos | ninguna | 200 · 3.0 s | Colombia, 2026-09-29 | `*` | país | **ADOPT (complementario)** |
| nasa_power | NASA POWER | radiación / agroclima | ninguna | 200 · 1.04 s | T2M 09-27; ALLSKY 09-25 | `*` | punto | **ADOPT** |
| firms_public | FIRMS CSV Sudamérica | incendios | ninguna | 200 · 0.3–0.7 s | adquisición 07:56Z; 13 focos en Nariño en 24 h | no | continental | **ADOPT** |
| firms_area | FIRMS Area API | incendios | MAP_KEY | 400 sin llave | — | — | bbox | **OPTIONAL-with-key** |
| inpe | INPE Queimadas | incendios | ninguna | 200 · 1.2–1.4 s | archivo de las 13:10Z; foco de Nariño a las 06:31Z | `*` | continental | **ADOPT** |
| gbif | GBIF | biodiversidad | ninguna | 200 · 0.64 s | lastInterpreted 09-30 03Z | `*` | GADM | **ADOPT** |
| inat | iNaturalist v1 | biodiversidad | ninguna | 200 · 0.77 s | creada 09-30T07:49-05 | `*` | place_id | **ADOPT** |
| ideam_vars | IDEAM, 11 tablas por variable | clima e hidrología observados | ninguna (app token opcional) | 200 · 0.4–2.6 s | 2026-09-29T23:58 | `*` | filtro | **ADOPT** |
| ideam_rt | IDEAM 57sv-p2fu | clima e hidrología observados | ninguna | 200 · 0.49 s | 2026-09-30T01:20 | `*` | filtro | **ADOPT (ventana ~12 h)** |
| ideam_cat | Catálogo de estaciones hp9r-jxuu | auxiliar | ninguna | 200 · 1.06 s | 09-30 06:16Z | `*` | filtro | **ADOPT (auxiliar)** |
| divipola | DIVIPOLA gdxc-w37w | auxiliar | ninguna | 200 · 0.67 s | estático | `*` | filtro | **ADOPT (auxiliar)** |
| metno | MET Norway | clima | User-Agent | 200 · 1.20 s (403 sin UA) | updated_at 11:20:58Z | `*` | No | **ADOPT (respaldo)** |
| tsunami | NOAA PTWC Atom | océano / amenazas | ninguna | 200 · 0.75 s | 09-27 (Caribe) | no | global | **ADOPT (complementario)** |
| gfw | Global Forest Watch | bosques | API key | metadatos 200; consultas 403 | v20260930 | `*` | geometría | **OPTIONAL-with-key** |
| openaq | OpenAQ v3 | aire | X-API-Key | 401 (v2 410) | — | — | bbox | **OPTIONAL-with-key** |
| waqi | WAQI/AQICN | aire | token | `demo` devuelve Shanghái | — | `*` | bounds | **OPTIONAL-with-key** |
| openuv | OpenUV | UV | llave | 403 | — | `*` | No | **OPTIONAL (baja prioridad)** |
| ebird | eBird CO-NAR | biodiversidad | token | 403 | — | — | región | **OPTIONAL-with-key** |
| iucn | IUCN v4 | biodiversidad | token | 403 | — | — | — | **OPTIONAL-with-key** |
| xenocanto | xeno-canto v3 | sonidos | key | 401 (v2 404) | — | `*` | — | **OPTIONAL-with-key** |
| purpleair | PurpleAir | aire | llave | 403 | — | `*` | bbox | **OPTIONAL-with-key** |
| 7timer | 7Timer! | clima | ninguna | 302 → 200 · 1.4 s | init 2026093006 | no | No | **REJECT** |
| rainviewer | RainViewer | radar | ninguna | 200 · 0.43 s | cuadro de las 13:10Z | `*` | teselas | **REJECT (sin cobertura)** |
| opensensemap | openSenseMap | sensores | ninguna | 200 · 12.4 s | 0 estaciones | `*` | bbox | **REJECT** |
| sunrise | Sunrise-Sunset | astronomía | ninguna | 200 · 0.23 s | — | `*` | No | **REJECT (redundante)** |
| ideam_aire | IDEAM g4t8-zkc3 | aire | ninguna | 200 · 0.68 s | 0 filas de Nariño; 2025-01-01 | `*` | filtro | **REJECT** |
| sgc_arcgis | SGC ArcGIS catálogo | sismos históricos | ninguna | 200 · 0.99 s | 1953 | eco del Origin | filtro | **REJECT** |
| ungrd | UNGRD 2343-nuqp | riesgo | ninguna | 200 · 0.33 s | 2025-12-27 (anual) | `*` | filtro | **REJECT (solo histórico)** |
| kanari | kanari.io | incendios | ninguna | 200 · 20.6 s | 0 en Sudamérica | `*` | No | **REJECT** |
| wttr | wttr.in | clima | ninguna | 200 · 0.90 s | — | `*` | No | **REJECT** |
| pirate | Pirate Weather | clima | llave | 401 | — | `*` | No | **REJECT** |
| meltema | Meltema | clima | — | reinicio del proxy | — | — | — | **no evaluado** |

### 3. Conjunto recomendado por categoría

| Categoría | Fuente principal (sin llave) | Complementos | Frecuencia sugerida |
|---|---|---|---|
| Clima (pronóstico y actual) | Open-Meteo Forecast (64 municipios en 1 llamada) | MET Norway | cada 1 h |
| Clima observado | IDEAM (tablas por variable y 57sv-p2fu) | catálogo hp9r-jxuu | 1 vez al día, después de las 09:00 UTC; guardar en BD propia |
| Historial reciente | Open-Meteo Archive | NASA POWER | 1 vez al día |
| Proyecciones | Open-Meteo Climate | — | estático, en caché |
| Calidad del aire | Open-Meteo Air Quality (modelo) | OpenAQ o WAQI con llave [cobertura no verificada] | cada 1 h |
| Hidrología | Open-Meteo Flood (Patía, Mira, Guáitara) | niveles de río del IDEAM | 1 vez al día |
| Océano | Open-Meteo Marine | NOAA PTWC | cada 1–3 h; tsunami cada 5–10 min |
| Sismos | SGC biweekly | USGS | cada 5–10 min, ventana de 1–2 días |
| Volcanes | SGC volcanos.json | EONET | cada 1–6 h |
| Incendios | INPE (10 min y diario) y FIRMS CSV | FIRMS Area (con llave) | cada 10–30 min |
| Eventos | GDACS y EONET | UNGRD (histórico) | cada 1–6 h |
| Biodiversidad | GBIF COL.22_2 e iNat 12737 | eBird, IUCN, xeno-canto (con llave) | GBIF diario; iNat cada 1–6 h |
| Radiación | Open-Meteo (`shortwave_radiation`, `uv_index`) y POWER | OpenUV | cada 1 h / 1 vez al día |
| Auxiliares | DIVIPOLA y Elevation | Open-Meteo Geocoding | estático |

**Presupuesto de llamadas a Open-Meteo**

- El límite gratuito es de 600 llamadas por minuto, 5 000 por hora y 10 000 por día (verificado en /en/terms).
- Según la página de precios, una petición de más de 10 variables o de más de 2 semanas cuenta como varias llamadas, con cálculo fraccionario.
- La calculadora de esa página incluye «Locations» como factor, así que **cada ubicación de un lote cuenta como una llamada**. Esto es una deducción, no una medición.
- Estimación:
  - Forecast horario para 64 municipios: unas 1 536 llamadas al día.
  - Air Quality horario para 64 municipios: otras 1 536.
  - Flood, Marine y Archive una vez al día: menos de 100.
  - Total: unas 3 200 llamadas al día, dentro del límite.

**Uso no comercial:** los términos citan como ejemplos «non-profit websites or apps that do not have subscriptions or advertising». Un portal institucional sin publicidad encajaría, pero es **[no verificado]**; conviene confirmarlo con Open-Meteo.

### 4. Detalle por API

#### 4.1 Open-Meteo Forecast — ADOPT
- **URL base:** `https://api.open-meteo.com/v1/forecast`
- **Ejemplo verificado (Pasto):**
  `https://api.open-meteo.com/v1/forecast?latitude=1.2136&longitude=-77.2811&current=temperature_2m,relative_humidity_2m,apparent_temperature,precipitation,rain,weather_code,cloud_cover,pressure_msl,wind_speed_10m,wind_direction_10m,wind_gusts_10m,is_day&hourly=temperature_2m,precipitation_probability,precipitation,uv_index&daily=temperature_2m_max,temperature_2m_min,precipitation_sum,precipitation_probability_max,uv_index_max,sunrise,sunset,et0_fao_evapotranspiration&timezone=America%2FBogota&forecast_days=7`
  - 200, 0.53 s.
- **Lote verificado:** `latitude=1.2136,1.8067,0.8303&longitude=-77.2811,-78.7647,-77.6445` responde 200 en 0.77 s.
  - Con los 64 municipios de DIVIPOLA (URL de 1 511 caracteres): 200, 0.65 s, 53 KB, 64 elementos en el mismo orden.
- **Forma de la respuesta con varias ubicaciones:**
  - Es un arreglo en el orden pedido.
  - Desde el segundo elemento cada objeto trae `location_id` (1, 2, …). **El primero no lo trae**, así que hay que indexar por posición.
  - La documentación dice: «To return data for multiple locations the JSON output changes to a list of structures. CSV and XLSX formats add a column location_id».
- **Campos:**
  - `current.time` (ISO local) y `current.interval` (900 s).
  - `current.temperature_2m` (°C), `relative_humidity_2m` (%), `precipitation` (mm), `weather_code` (WMO), `wind_speed_10m` (km/h), `wind_direction_10m` (°), `pressure_msl` (hPa).
  - `hourly.time[]` y las series `hourly.*[]`: 168 h.
  - `daily.temperature_2m_max[]`, `precipitation_sum[]`, `uv_index_max[]`, `sunrise[]`, `sunset[]`, `et0_fao_evapotranspiration[]`.
  - Las unidades vienen en `*_units`.
- **Selección de celda (verificado):**
  - Con `cell_selection=land` (por defecto), Pasto cae en 1.3005,−77.3460, a unos 10 km, con 15.0 °C.
  - Con `nearest` cae en 1.2302,−77.2849, con 17.4 °C.
  - Recomendación: evaluar `nearest` o pasar `elevation=` explícito.
- **Licencia (verificado en /en/licence):** CC BY 4.0. «You must include a link next to any location Open-Meteo data are displayed», por ejemplo `<a href="https://open-meteo.com/">Weather data by Open-Meteo.com</a>`.
- **Límites:** menos de 10 000 llamadas por día, 5 000 por hora y 600 por minuto, uso no comercial.
- **CORS:** `*`.
- **Incidencias de conexión:**
  - `api.open-meteo.com` falló por TLS (SSL_ERROR_SYSCALL) o timeout de 30 s de forma continua durante unos 3 minutos al principio de la sesión, y otra vez más tarde.
  - `flood-api` falló 1 vez de 8.
  - Puede deberse al proxy, pero igual conviene reintentar con backoff y guardar en caché en el servidor.

#### 4.2 Open-Meteo Air Quality — ADOPT
- **Ejemplo verificado:**
  `https://air-quality-api.open-meteo.com/v1/air-quality?latitude=1.2136,1.8067,0.8303&longitude=-77.2811,-78.7647,-77.6445&current=pm2_5,pm10,ozone,nitrogen_dioxide,carbon_monoxide,sulphur_dioxide,uv_index,us_aqi,european_aqi,dust,aerosol_optical_depth&hourly=pm2_5,pm10,ozone,nitrogen_dioxide,carbon_monoxide,sulphur_dioxide,uv_index,us_aqi,european_aqi&timezone=America%2FBogota&forecast_days=2`
  - 200, 0.72 s. Devuelve un arreglo de 3.
- **Campos:**
  - `[i].current.{pm2_5, pm10, ozone, nitrogen_dioxide, carbon_monoxide, sulphur_dioxide, dust}` en μg/m³.
  - `uv_index`, `aerosol_optical_depth`, `us_aqi` y `european_aqi`.
  - Valores en Pasto: PM2.5 4.6 y US AQI 25.
- **Frecuencia (documentada):** CAMS Global, 0.4° (unos 45 km), cada 3 horas, «Every 12 hours, 5 days forecast».
- **Advertencia:** son datos de **modelo**, no de estaciones. La UI debe indicarlo.

#### 4.3 Open-Meteo Flood (GloFAS v4) — ADOPT
- **Ejemplo verificado:**
  `https://flood-api.open-meteo.com/v1/flood?latitude=2.0,1.4,1.9,1.0&longitude=-78.5,-78.7,-77.8,-77.5&daily=river_discharge,river_discharge_mean,river_discharge_max,river_discharge_p75&past_days=7&forecast_days=30&timezone=America%2FBogota`
  - 200, 0.84 s.
- **Celdas identificadas** (búsqueda en rejilla de 0.1°, 234 puntos):

  | Punto pedido | Celda devuelta | Río (interpretación) | Caudal medio | Caudal hoy |
  |---|---|---|---|---|
  | 2.0,−78.5 | 2.025,−78.525 | Patía bajo | 2 845 m³/s | 2 703 m³/s |
  | 1.4,−78.7 | 1.425,−78.725 | Mira | 2 521 m³/s | 2 505 m³/s |
  | 1.9,−77.8 | — | Patía medio | 956 m³/s | 976 m³/s |
  | 1.0,−77.5 | — | Guáitara | 43 m³/s | 41.8 m³/s |

  - Puntos sugeridos en el encargo: 1.95,−78.3 da 7.2 m³/s (un afluente) y 1.55,−78.95 da todo `null`.
  - Los nombres de río son una interpretación y hay que validarlos.
- **Campos:** `[i].daily.time[]` y `river_discharge`, `_mean`, `_median`, `_max`, `_min`, `_p25`, `_p75[]` en m³/s.
- **Frecuencia (documentada):** 0.05°, diaria, con 30 días de pronóstico.
- **Índice de alerta sugerido:** `river_discharge / river_discharge_p75`.

#### 4.4 Open-Meteo Marine — ADOPT
- **Ejemplo verificado:**
  `https://marine-api.open-meteo.com/v1/marine?latitude=1.85&longitude=-78.85&current=wave_height,wave_direction,wave_period,swell_wave_height,sea_surface_temperature,ocean_current_velocity,sea_level_height_msl&hourly=wave_height,sea_surface_temperature,sea_level_height_msl&daily=wave_height_max,wave_period_max&timezone=America%2FBogota&forecast_days=3`
  - 200, 0.56 s. Celda devuelta: 1.875,−78.875.
- **Campos:**
  - `current.wave_height` (m; 0.62), `wave_direction` (°), `wave_period` (s), `swell_wave_height` (m).
  - `sea_surface_temperature` (°C; 29.8), `ocean_current_velocity` (km/h).
  - `sea_level_height_msl` (m; marea de −0.66 a 1.99).
  - `daily.wave_height_max[]`.
- **Frecuencia (documentada):** MFWAM cada 12 h, SMOC cada 24 h, ECMWF WAM cada 6 h.
- **Nota:** el SST de 29.8 °C parece alto y conviene contrastarlo [no verificado].

#### 4.5 Open-Meteo Archive — ADOPT
- **Ejemplo verificado:**
  `https://archive-api.open-meteo.com/v1/archive?latitude=1.2136&longitude=-77.2811&start_date=2026-08-31&end_date=2026-09-30&daily=temperature_2m_max,temperature_2m_min,temperature_2m_mean,precipitation_sum,rain_sum,shortwave_radiation_sum,et0_fao_evapotranspiration,wind_speed_10m_max&timezone=America%2FBogota`
  - 200, 0.69 s.
- **Frescura:**
  - En best_match hay datos hasta el 2026-09-30. La documentación incluye «ECMWF IFS … Every 6 hours with no delay».
  - Con `&models=era5` el último dato es del 2026-09-23. La documentación dice 5 días de retraso; se observaron unos 7.

#### 4.6 Open-Meteo Climate — ADOPT (complementario, estático)
- **Ejemplo verificado:**
  `https://climate-api.open-meteo.com/v1/climate?latitude=1.2136&longitude=-77.2811&start_date=2025-01-01&end_date=2050-12-31&models=MRI_AGCM3_2_S,EC_Earth3P_HR&daily=temperature_2m_mean,precipitation_sum`
  - 200, 1.13 s, 317 KB.
- **Campos:** `daily.<variable>_<MODELO>[]`.

#### 4.7 Open-Meteo Elevation — ADOPT (auxiliar)
- `https://api.open-meteo.com/v1/elevation?latitude=1.2136,1.8067,0.8303&longitude=-77.2811,-78.7647,-77.6445` responde `{"elevation":[2546.0, 8.0, 2904.0]}`.

#### 4.8 USGS Earthquake — ADOPT (complementario)
- **Ejemplos verificados:**
  - bbox de Nariño, 365 días: `https://earthquake.usgs.gov/fdsnws/event/1/query?format=geojson&starttime=2025-09-30&minlatitude=0.35&maxlatitude=2.70&minlongitude=-79.05&maxlongitude=-76.80&orderby=time` da **0 eventos**.
  - Mismo bbox, 5 años: sí hay eventos, por ejemplo M4.6 cerca de Gualmatán el 2023-12-11 y M5.0 frente a Mosquera el 2024-05-22.
  - Radio de 300 km alrededor de Pasto, M ≥ 2.5: `…?format=geojson&starttime=2025-09-30&latitude=1.2136&longitude=-77.2811&maxradiuskm=300&minmagnitude=2.5&orderby=time` da **7 eventos**.
  - `/count` responde `{"count":7,"maxAllowed":20000}`.
  - Feeds: `https://earthquake.usgs.gov/earthquakes/feed/v1.0/summary/all_day.geojson` (0.18 s) y `2.5_week.geojson`.
- **Campos:**
  - `metadata.count` y `metadata.generated`.
  - `features[].properties.{mag, magType, place, time, updated (epoch ms UTC), status, net, url}`.
  - `geometry.coordinates` = `[lon, lat, prof_km]`.
- **Frecuencia:** `max-age=60`; el feed se generó a las 12:59:03Z con un evento de las 12:57:22Z.
- **Licencia:** dominio público [no verificado]. Citar «U.S. Geological Survey».
- **CORS:** `*`.

#### 4.9 SGC sismos — ADOPT (API no documentada)
- **Cómo se encontró:** en el bundle `https://www.sgc.gov.co/static/js/main.64da3c0e.js`, que declara `baseURL "https://api.sgc.gov.co/"` y las rutas `/biweekly/biweekly_earthquakes` y `/biweeklycount/biweekly_earthquakes` con los parámetros `startdate` y `enddate`.
  - La web www.sgc.gov.co devuelve 403 a curl sin User-Agent de navegador; la API no.
- **Ejemplos verificados:**
  - `https://api.sgc.gov.co/biweekly/biweekly_earthquakes?startdate=2026-09-28&enddate=2026-09-30`: 200, 1.12 s, 185 eventos.
  - `…?startdate=2026-09-30&enddate=2026-10-01`: 200, 0.42 s, 18 eventos. El último fue a las **12:31:09Z**, consultado a las 13:03Z.
  - `https://api.sgc.gov.co/biweeklycount/biweekly_earthquakes?startdate=2026-09-15&enddate=2026-09-30` → `{"metadata": {"count": 2258}}`
- **Semántica observada:**
  - Los límites son días en hora local y `enddate` es exclusivo.
  - La ventana del 2026-09-01 al 10 devolvió 897 eventos; la del 2025-09 devolvió 0. La retención es limitada, pero el límite exacto es [no verificado].
  - Los parámetros de bbox se ignoran; hay que filtrar en local.
- **Campos:**
  - `features[].id`, por ejemplo `OVSP4921632` (OVSP = observatorio de Pasto).
  - `properties.{utcTime, localTime ("YYYY-MM-DD HH:MM:SS"), mag, magType, depth (km), place, closerTowns, status, felt, cdi, mmi, rms, gap, nst, agency, updated}`.
  - `geometry.coordinates` = `[lon, lat, prof]`.
- **Nariño:** 8 eventos en el bbox en 15 días. El último fue M2.6 el 2026-09-25 a las 11:17Z, a 18 km de Cumbal.
- **Riesgos:** no hay documentación ni términos publicados [no verificado]. Hay que validar el esquema y mantener USGS de respaldo.

#### 4.10 SGC volcanes — ADOPT (archivo no documentado)
- `https://archive.sgc.gov.co/volcanos/volcanos.json` responde 200 en 0.52 s, 181 KB (bucket S3), con `last-modified` 2026-09-30 01:53:50 GMT.
- **Nariño:**
  - Galeras: 3, «Alerta amarilla».
  - Cumbal: 3.
  - Chiles-Cerro Negro: 3.
  - Azufral: 4, «Volcán activo en reposo».
  - Doña Juana: 4.
  - Las Ánimas: 4.
  - Guamuez-Sibundoy: 4.
- **Campos:**
  - `properties.{VolcanoName, department, latitude, longitude, msnm}`.
  - `activityLevel`: se observaron 4 (reposo/verde) y 3 (amarilla). Que 2 sea naranja y 1 roja [no está verificado].
  - `activityLevelTitle`, `activityLevelShortDescription`, `activityLevelIcon`.
  - `bulletins[]{name, type, update, publication_date, file(PDF)}`: el último boletín de Galeras es del 2026-09-29.
  - `news[]`, `link`, `threat_map`.
- **Advertencia:** en `geometry.coordinates` el orden es `[lat, lon]`, invertido. No envía CORS.
- **Rutas que fallan:** `feed/v1.0.1/summary/*.json` y `volcanos/alert_leves.json` responden 403.
- **Actualización posterior:** horas después, el propio `volcanos.json` empezó a responder 403 a clientes que no se identifican como navegador. Ver «Notas operativas» en la Parte I.

#### 4.11 NASA EONET — ADOPT (complementario)
- `https://eonet.gsfc.nasa.gov/api/v3/events?bbox=-79.05,2.70,-76.80,0.35&status=all&days=3650` responde 200 en 0.81 s con 2 inundaciones de fuente GDACS.
- Con un bbox más amplio y 365 días hay 12 eventos, entre ellos Puracé (SIVolcano).
- **Parámetros:** el bbox va como `minLon,maxLat,maxLon,minLat`.
- **Campos:** `events[].{id, title, closed, categories[].id, sources[], geometry[].{date, type, coordinates}}`.
- **Advertencia:** un polígono de GDACS venía con coordenadas `[lat, lon]`.

#### 4.12 GDACS — ADOPT (complementario)
- `https://www.gdacs.org/gdacsapi/api/events/geteventlist/SEARCH?country=Colombia&fromdate=2025-09-30&todate=2026-09-30&alertlevel=green;orange;red` responde 200 en 3.0 s con 82 eventos. Solo 1 está en Nariño.
- **Campos:** `features[].properties.{eventtype, eventid, name, alertlevel, fromdate, todate}`; `geometry` va como `[lon, lat]`.
- **Licencia:** [no verificada].

#### 4.13 NASA POWER — ADOPT
- `https://power.larc.nasa.gov/api/temporal/daily/point?parameters=T2M,T2M_MAX,T2M_MIN,PRECTOTCORR,ALLSKY_SFC_SW_DWN,RH2M,WS2M&community=AG&longitude=-77.2811&latitude=1.2136&start=20260901&end=20260930&format=JSON` responde 200 en 1.04 s.
- **Campos:** `properties.parameter.<P>["YYYYMMDD"]`.
  - Unidades: T2M en °C; PRECTOTCORR en mm/day; ALLSKY en MJ/m²/day; RH2M en %; WS2M en m/s.
  - Valor de relleno −999.
- **Frescura:** meteorología hasta el 09-27; radiación hasta el 09-25.
- **Cita (verificada):** «The data was obtained from National Aeronautics and Space Administration (NASA) Langley Research Center's Prediction Of Worldwide Energy Resources (POWER) project funded through the NASA Earth Science Division», con versión y fecha.
- **Límites:** [no verificados].

#### 4.14 NASA FIRMS — ADOPT (CSV público) / OPTIONAL-with-key (API por área)
- **CSV públicos:**
  - `https://firms.modaps.eosdis.nasa.gov/data/active_fire/noaa-20-viirs-c2/csv/J1_VIIRS_C2_South_America_24h.csv`: 200, 1 MB, `last-modified` 12:41:30Z.
  - Variantes: `suomi-npp-viirs-c2/csv/SUOMI_VIIRS_C2_South_America_24h.csv`, `noaa-21-viirs-c2/csv/J2_…_24h.csv`, `modis-c6.1/csv/MODIS_C6_1_South_America_24h.csv` y `…_7d.csv`.
  - Focos en el bbox de Nariño:

    | Sensor | Ventana | Focos |
    |---|---|---|
    | NOAA-20 | 24 h | 13 |
    | NOAA-21 | 24 h | 15 |
    | S-NPP | 24 h | 7 |
    | MODIS | 24 h | 5 |
    | NOAA-20 | 7 días | 35 |

  - Columnas VIIRS: `latitude, longitude, bright_ti4, bright_ti5` (K), `scan, track, acq_date, acq_time` (HHMM UTC), `satellite, confidence, version, frp` (MW), `daynight`.
  - Sin CORS. Hay que recortar al polígono del departamento.
- **API por área:**
  - Formato: `/api/area/csv/[MAP_KEY]/[SOURCE]/[oeste,sur,este,norte]/[DAY_RANGE 1-5]` con `/[DATE]` opcional.
  - Sin llave o con llave inválida responde 400 «Invalid MAP_KEY.».
  - Límite documentado: «5000 transactions / 10-minute interval».

#### 4.15 INPE Queimadas — ADOPT
- **Archivos:**
  - `https://dataserver-coids.inpe.br/queimadas/queimadas/focos/csv/10min/`: listado de `focos_10min_YYYYMMDD_HHMM.csv`; el último era de las 13:10Z.
  - `…/focos/csv/diario/America_Sul/focos_diario_20260930.csv`: 200, 1 MB, 6 877 filas.
  - Ambos con CORS `*`.
- **Columnas:**
  - Archivo de 10 minutos: `lat, lon, satelite, data`.
  - Archivo diario: `id, lat, lon, data_hora_gmt, satelite, municipio, estado, pais, …, frp`.
- **Nariño:** 5 focos en 6 h, por ejemplo El Tablón de Gómez a las 06:31Z. En el diario se puede filtrar con `estado='Nariño'`.
- **Formato:** hay coordenadas como `"    .447450"`, sin el cero inicial.
- **Licencia:** [no verificada].

#### 4.16 GBIF — ADOPT
- **Identificador:** `gadmGid=COL.22_2` (verificado con `/v1/geocode/gadm/search?q=Nariño`). `COL.21_1` devuelve 0 y `stateProvince` trae ruido.
- **Consulta principal:** `https://api.gbif.org/v1/occurrence/search?gadmGid=COL.22_2&limit=3&facet=kingdomKey&facet=classKey&facet=iucnRedListCategory&facetLimit=8`
  - Da 978 367 registros.
  - Facetas IUCN: LC 688 393, VU 12 352, NT 7 653, EN 3 288, CR 240.
  - La clase 212 es Aves, con 497 206.
- **Registros de sep-2026:** `&eventDate=2026-09-01,2026-09-30` da 37.
- **Amenazadas:** con los filtros CR, EN y VU hay 15 880 ocurrencias.
- **Campos:** `count`, `facets[].counts[]`, `results[].{scientificName, eventDate, decimalLatitude, decimalLongitude, basisOfRecord, datasetName, license, iucnRedListCategory, gadm.level2.name, lastInterpreted}`.
- **Licencia:** por registro; por ejemplo, los de iNaturalist son CC BY-NC 4.0.
- **Cache:** `max-age=600`.

#### 4.17 iNaturalist — ADOPT
- **place_id:** 12737 («Nariño, CO»).
- **Consultas verificadas:**
  - `https://api.inaturalist.org/v1/observations?place_id=12737&order_by=observed_on&order=desc&per_page=3&quality_grade=research&d1=2026-09-01` da 92 observaciones.
  - Ordenando por `order_by=created_at`, la más reciente se creó el 2026-09-30T07:49-05:00.
  - `/v1/observations/species_counts?place_id=12737&threatened=true` da 186 especies.
- **Campos:** `results[].{observed_on, time_observed_at, created_at, quality_grade, license_code, location ("lat,lon"), taxon.{name, preferred_common_name, conservation_status.status}, photos[0].url}`.
- **Límites (verificado en swagger):** «max of 100 requests per minute … keep it to 60 … under 10,000 requests per day».
- **Advertencia:** algunas coordenadas vienen ocultadas (`geoprivacy`).

#### 4.18 IDEAM en datos.gov.co — ADOPT
**Descubrimiento:** `https://api.us.socrata.com/api/catalog/v1?domains=www.datos.gov.co&search_context=www.datos.gov.co&q=IDEAM`, junto con las búsquedas `precipitacion`, `calidad del aire`, `nivel rio`, `humedad`, `viento`, `crudos` y `SISAIRE`.

**Frescura en Nariño** (filtro `upper(departamento)='NARIÑO' AND fechaobservacion>'2026-09-23'`):

| id | Nombre | rowsUpdatedAt (UTC) | Último dato | Estaciones |
|---|---|---|---|---|
| s54a-sgyg | Precipitación | 06:17 | 2026-09-29T23:58 | 22 |
| sbwg-7ju4 | Temperatura Ambiente del Aire (antes «Datos Hidrometeorológicos Crudos») | 06:18 | 23:58 | 15 |
| uext-mhny | Humedad del Aire | 06:19 | 23:58 | 15 |
| 62tk-nxj5 | Presión Atmosférica | 06:15 | 23:58 | 15 |
| sgfv-3yp8 | Velocidad del Viento | 06:19 | 23:58 | 15 |
| kiw7-v9ta | Dirección del Viento | 06:16 | 23:50 | 15 |
| ccvq-rp9s | Temperatura Máxima | 06:16 | 23:14 | 15 |
| afdg-3zpb | Temperatura Mínima | 06:15 | 23:49 | 15 |
| bdmn-sqnh | Nivel Instantáneo del Río (m) | 06:15 | 23:00 | 9 |
| vfth-yucv | Nivel Máximo | 06:19 | 23:25 | 9 |
| pt9a-aamx | Nivel Mínimo | 06:16 | 23:00 | 9 |
| **57sv-p2fu** | Datos de Estaciones de IDEAM y de Terceros | 08:43 | **2026-09-30T01:20** | 25 |
| hp9r-jxuu | Catálogo Nacional de Estaciones | 06:16 | — | en Nariño: 129 automáticas activas y 189 convencionales activas |

- **Ejemplo verificado:**
  `https://www.datos.gov.co/resource/s54a-sgyg.json?$where=upper(departamento)%3D%27NARI%C3%91O%27%20AND%20fechaobservacion%3E%272026-09-28T00:00:00%27&$order=fechaobservacion%20DESC&$limit=3`
  - 0.71 s. Sin el filtro de fecha tardó **41 s**.
- **Ejemplo verificado (ventana móvil):**
  `https://www.datos.gov.co/resource/57sv-p2fu.json?$where=departamento%3D%27Nari%C3%B1o%27&$order=fechaobservacion%20DESC&$limit=5`
  - 0.49 s.
- **Hallazgos críticos:**
  1. **Cambio de mayúsculas:** `departamento` pasó de 'NARIÑO' a 'Nariño'. Filtrando con 'NARIÑO' el último dato aparente es el 2026-07-24.
  2. **57sv-p2fu es una ventana móvil nacional de unas 12 h:** min 2026-09-29T13:59, max 2026-09-30T01:52, 154 010 filas. Hay que cosecharla todos los días. Tiene variables exclusivas: humedad del suelo a 30 y 50 cm, temperatura del suelo a 10, 30 y 50 cm, evaporación horaria y nivel horario (cm).
  3. **Zona horaria de `fechaobservacion`:** no está documentada. Parece hora local, pero es [no verificado].
  4. `valorobservado` llega como string.
  5. En `hp9r-jxuu` el campo `ubicaci_n` tiene latitud y longitud intercambiadas; hay que usar `latitud` y `longitud`.
- **Campos:** `codigoestacion, codigosensor, fechaobservacion, valorobservado, nombreestacion, departamento, municipio, zonahidrografica, latitud, longitud, descripcionsensor, unidadmedida`. `57sv-p2fu` agrega `entidad`.
- **Frecuencia documentada:** «Diaria».
- **Licencia (verificada):** CC BY-SA 4.0. Atribución: «Instituto de Hidrología, Meteorología y Estudios Ambientales – IDEAM». Los datos **no están validados**.
- **Límites (verificado en dev.socrata.com):** sin app token el throttling es por IP desde un «shared pool»; se recomienda registrar un app token gratuito.
- **CORS:** `*`.

#### 4.19 DIVIPOLA — ADOPT (auxiliar)
- `https://www.datos.gov.co/resource/gdxc-w37w.json?$where=cod_dpto%3D%2752%27&$order=cod_mpio&$limit=100` devuelve **64** municipios.
- **Campos:** `cod_mpio, nom_mpio, latitud, longitud`. Las coordenadas usan coma decimal, por ejemplo `"1,212352"`.

#### 4.20 MET Norway — ADOPT (respaldo)
- `https://api.met.no/weatherapi/locationforecast/2.0/compact?lat=1.2136&lon=-77.2811&altitude=2527` con `User-Agent: <app>/<versión> <contacto>` responde 200 en 1.20 s. Sin UA responde 403.
- **Campos:** `properties.meta.updated_at` y `properties.timeseries[].{time, data.instant.details.{air_temperature, relative_humidity, wind_speed, air_pressure_at_sea_level}, data.next_1_hours.{summary.symbol_code, details.precipitation_amount}}`.
- **Términos (verificados):**
  - Más de 20 peticiones por segundo por aplicación requiere acuerdo.
  - Hay que respetar `Expires` (se observaron unos 32 min) y usar `If-Modified-Since`.
  - Licencia NLOD 2.0 y CC BY 4.0, con crédito «Data from MET Norway».

#### 4.21 NOAA PTWC — ADOPT (complementario)
- `https://www.tsunami.gov/events/xml/PHEBAtom.xml` responde 200 en 0.75 s. Es Atom con `<updated>2026-09-27T21:56:18Z</updated>`.
- Sin CORS.
- Que cubra la costa de Tumaco es [no verificado con un evento real].

#### 4.22 Con llave (OPTIONAL-with-key)
- **OpenAQ v3:** `https://api.openaq.org/v3/locations?bbox=-79.05,0.35,-76.80,2.70` responde 401 («X-API-Key»). v2 responde 410 («retired»).
- **WAQI:** `https://api.waqi.info/feed/geo:1.2136;-77.2811/?token=demo` devuelve Shanghái (idx 1437). `map/bounds` responde «Invalid key».
- **Global Forest Watch:**
  - `…/dataset/gfw_integrated_alerts/latest`, siguiendo el 307, responde 200 con `v20260930`.
  - Las consultas `…/query?sql=…`, por GET o POST, responden 403 «missing valid API key».
- **OpenUV:** 403.
- **eBird** (`https://api.ebird.org/v2/data/obs/CO-NAR/recent`): 403.
- **IUCN v4:** 403. Mientras tanto se puede usar la faceta IUCN de GBIF.
- **xeno-canto:** v3 responde 401 y v2 fue retirada.
- **PurpleAir:** 403.

### 5. Rechazadas

| API | Motivo |
|---|---|
| **RainViewer** | La API responde bien (200, cuadros cada 10 min). Pero la capa de cobertura `/v2/coverage/0/256/{z}/{x}/{y}/0/0_0.png` es opaca sobre Pasto, Tumaco, Ipiales, Bogotá y Quito, y transparente sobre Miami. Las teselas de radar sobre Nariño venían vacías y no hay canal satelital. La licencia es solo personal o educativa, con atribución. |
| **openSenseMap** | 0 estaciones en el bbox, 12.4 s de latencia. En un área más amplia solo hay 4, en Bogotá. |
| **IDEAM g4t8-zkc3** | 0 filas de Nariño. El último dato nacional es de 2025-01-01 y la frecuencia es «Anual». |
| **SGC ArcGIS catalogo_de_sismos_2** | 83 eventos en Nariño, el último de 1953. Las consultas que devuelven registros fallan en el FeatureServer (400); el MapServer sí responde. |
| **Feeds S3 del SGC** | 403 AccessDenied. |
| **7Timer!** | Sin CORS, `content-type` text/html, basado en GFS de baja resolución. Redundante. |
| **wttr.in** | No oficial y redundante. |
| **Sunrise-Sunset** | Exige atribución y Open-Meteo ya trae `sunrise` y `sunset`. |
| **kanari.io** | 20.6 s y 548 KB, truncado a 2 000 eventos, 0 en Sudamérica. |
| **UNGRD 2343-nuqp** | Frecuencia anual; último evento de Nariño el 2025-12-27; filas duplicadas. |
| **Pirate Weather** | 401 aunque el listado dice «sin auth». |
| **Meltema** | No se pudo evaluar por un reinicio del proxy. |
| **USGS Water, AirNow, US Weather** | Solo EE. UU. |

### 6. Riesgos y recomendaciones

1. Hacer todas las consultas desde el servidor (WP-Cron o Action Scheduler) y guardar en caché. Hay fuentes sin CORS y cupos que proteger.
2. Tener dos fuentes por categoría crítica: SGC con USGS, Open-Meteo con MET Norway, INPE con FIRMS.
3. Validar el esquema de las APIs del SGC en cada lectura y alertar al administrador si cambia.
4. Cuidar el orden de las coordenadas: `[lat, lon]` en SGC volcanes, en un polígono de EONET y en `ubicaci_n` del catálogo IDEAM. DIVIPOLA usa coma decimal y el INPE trae ceros iniciales faltantes.
5. Recortar al polígono de Nariño con GADM `COL.22_2`; los bbox incluyen partes de Cauca, Putumayo y Ecuador.
6. Mostrar la atribución de cada fuente: Open-Meteo con enlace, MET Norway, IDEAM (CC BY-SA 4.0), NASA POWER, FIRMS, INPE, GBIF e iNat (licencia por registro), SGC, USGS y GDACS.
7. Indicar la antigüedad de cada dato: Open-Meteo 15 min; IDEAM de 6 a 30 h; POWER de 3 a 5 días; GBIF de días a semanas.

### Anexo A

La tabla completa de candidatos de public-apis (334 entradas en 7 secciones) está en [anexo-public-apis.md](anexo-public-apis.md).
