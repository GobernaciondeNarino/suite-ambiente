# D3plus v4 en Suite Ambiente Nariño

La solicitud pedía «gráficos en 3d.js y 3dplus.js». Se interpretó como **D3.js** y **D3plus**, las dos librerías de visualización que usa el plugin, y se investigó la versión 4 de D3plus que se pidió revisar en [d3plus.org](https://d3plus.org/?path=/docs/core-charts-chord--d3plus).

Este documento resume qué cambió en D3plus v4, qué partes usa el plugin y los problemas que aparecieron al integrarlo, con su solución. Las fuentes son el `CHANGELOG.md` y el `MIGRATION.md` del repositorio [d3plus/d3plus](https://github.com/d3plus/d3plus), su código fuente y las pruebas de renderizado hechas durante el desarrollo.

## Versiones incluidas

| Librería | Versión | Licencia | Archivo en el plugin |
|---|---|---|---|
| D3 | 7.9.0 | ISC | `assets/vendor/d3/d3.min.js` |
| D3plus | `@d3plus/core` 4.5.0 (paquete UMD completo, global `d3plus`) | MIT | `assets/vendor/d3plus/d3plus-core.full.min.js` |

Por defecto las librerías se sirven desde el propio plugin. En **Configuración → General** se puede cambiar a jsDelivr; en ese caso el plugin agrega la verificación de integridad (SRI) a las etiquetas `<script>`:

| Archivo en jsDelivr | SRI |
|---|---|
| `d3@7.9.0/dist/d3.min.js` | `sha384-CjloA8y00+1SDAUkjs099PVfnY2KmDC2BZnws9kh8D/lX1s46w6EPhpXdqMfjK6i` |
| `@d3plus/core@4.5.0/umd/d3plus-core.full.min.js` | `sha384-qlYEmkF5QB9ZKXpZGpQcL8l+2rfieTsBf4yuyAZSXpxesgG338iXZ8s9jnEBDwJJ` |

Para actualizar una librería: reemplace el archivo en `assets/vendor/`, actualice `assets/vendor/VERSIONES.txt`, recalcule el SRI (`openssl dgst -sha384 -binary archivo | openssl base64 -A`) y cambie las constantes en `includes/class-san-assets.php`.

## Qué cambió en D3plus v4

### El motor de dibujo (4.0.0)

- **Grafo de escena.** En v3 cada gráfico modificaba el DOM mientras dibujaba. En v4 el gráfico se compila a un grafo de escena serializable, y un *backend* lo pinta. El resultado en SVG pretende ser idéntico al de v3.
- **La API pública no cambió.** Las clases de gráficos, los métodos encadenados (`.data()`, `.groupBy()`…), `config()` y los eventos funcionan igual. Para la mayoría de proyectos, migrar es cambiar la versión.
- **Dos backends:** `.renderer("svg")` (por defecto) o `.renderer("canvas")`. Canvas rinde mejor con miles de formas. El plugin usa SVG por defecto porque permite exportar PNG, es accesible e inspeccionable; se puede cambiar a Canvas en Configuración → General.
- **`viz.destroy()`** desconecta el `ResizeObserver` y los eventos del gráfico. El plugin lo llama cada vez que redibuja una figura (por ejemplo, al cambiar de tipo o de municipio) para no acumular observadores.
- **Paquetes por separado:** `@d3plus/core`, `@d3plus/data`, `@d3plus/format`, `@d3plus/locales`, `@d3plus/render`, `@d3plus/types` y otros. El plugin usa el UMD completo de `@d3plus/core`, que los incluye.
- Se eliminó la dependencia de `d3-collection`.

### Nuevos tipos y funciones (4.1 a 4.4)

- **4.1.0:** texto ajustado dentro de círculos; campos de configuración con tipos. Requiere Node 22 o superior solo para compilar; en el navegador no cambia nada.
- **4.2.0:** orden de apilado por valor (`stackOrder`). **Cambio de comportamiento:** el orden por defecto de las áreas y barras apiladas pasó de alfabético a «mayor total en la base».
- **4.3.0:** envoltorios para Vue, Svelte, Angular y Web Components; renderizado en el servidor (`@d3plus/ssr`); los valores `NaN` o `null` ya no se dibujan como ceros.
- **4.4.0:** **gráfico de cuerdas (`Chord`)**, dirigido o no dirigido, con el mismo modelo de nodos y enlaces que Network, Rings y Sankey. Flechas de dirección en Network, Rings y Sankey.

### Controles interactivos (4.5.0)

La versión 4.5.0 cambió varios valores por defecto: sin tocar la configuración, los gráficos se ven y se comportan distinto.

| Función | Valor por defecto en 4.5.0 | Cómo desactivarla | Valor en el plugin |
|---|---|---|---|
| Zoom y desplazamiento en todos los gráficos, con botones arriba a la derecha | activado | `zoom: false` | desactivado (se puede activar en Configuración → General) |
| Búsqueda y resaltado, arriba a la izquierda | activada | `search: false` | desactivada (configurable) |
| Vista de tabla con descarga CSV, arriba a la izquierda | activada | `tableView: false` | desactivada (configurable); el plugin trae su propio botón «Tabla» |
| Minimapa al hacer zoom | activado | `minimap: false` | desactivado (configurable) |
| Rueda del ratón | `zoomScroll: "modifier"` (Ctrl/⌘ + rueda) | — | sin cambios |

También cambiaron el estilo de la atribución y el mapa base de `Geomap` (el plugin no usa `Geomap`: dibuja sus mapas con D3 y los polígonos oficiales).

**Traducciones:** los botones nuevos (zoom, búsqueda) tienen traducción `es-ES`. El plugin configura `locale: "es-ES"` en todos los gráficos.

### Carga diferida de gráficos fuera de pantalla

En v4, `detectVisible` y `detectVisibleUnload` están activados por defecto. D3plus espera a que el gráfico sea visible para dibujarlo y **lo descarga cuando sale de la pantalla**. El plugin ya difiere la carga con su propio `IntersectionObserver`, así que desactiva ambas opciones. Con ellas activas aparecían gráficos vacíos de forma intermitente y la exportación a PNG fallaba en figuras fuera de pantalla.

## Cómo usa el plugin D3plus

El código está en `assets/js/san-graficos.js`, función `configD3plus()`.

| Tipo del plugin | Clase de D3plus |
|---|---|
| `line` | `LinePlot` |
| `area` | `AreaPlot` |
| `stacked_area` | `StackedArea` |
| `bar`, `barh`, `stacked_bar` | `BarChart` |
| `pie` | `Pie` |
| `donut` | `Donut` |
| `treemap` | `Treemap` |
| `pack` | `Pack` |
| `radar` | `Radar` |
| `box` | `BoxWhisker` |
| `scatter` | `Plot` |
| `bump` | `BumpChart` |
| `chord` | `Chord` |
| `sankey` | `Sankey` |
| `matrix` | `Matrix` |

Los mapas coropléticos, mapas de puntos, mapas de calor, medidores y rosas se dibujan con D3 v7 (`assets/js/san-d3.js`).

La visualización `clima_transiciones` usa el nuevo `Chord`: muestra cuántas veces el estado del tiempo de un día pasa a otro estado al día siguiente (por ejemplo, de «nublado» a «lluvia») en la semana pronosticada para los 64 municipios. Las cintas que vuelven al mismo arco indican persistencia. Se puede ver también como `Matrix`.

**Identidad visual:** los colores vienen de la paleta institucional validada para daltonismo, en modo claro y oscuro (`SAN.paleta()` en `san-core.js`). Cada entidad recibe su color en orden fijo; nunca por rango. Las fuentes son Hind Madurai y Nunito Sans, según el Manual de Identidad Visual de la Gobernación.

## Problemas encontrados y soluciones

| Síntoma | Causa | Solución en el plugin |
|---|---|---|
| Error `labelConfig.fontSize is not a function` | D3plus evalúa las propiedades de texto como funciones de acceso (*accessors*). | Se pasan funciones constantes: `fontSize: () => 12` (helper `cte()`). |
| Gráfico de dispersión en blanco | `shapeConfig.Circle.r` también debe ser función. | Se quitó el radio fijo y se usa el de D3plus. |
| Gráficos vacíos intermitentes | `detectVisible` y `detectVisibleUnload` activos por defecto. | Se desactivan; el plugin ya carga los gráficos al entrar en pantalla. |
| Eje Y aplastado o invertido en gráficos de líneas | D3plus **modifica** los objetos de configuración de los ejes; compartir el mismo objeto entre `xConfig` y `yConfig` corrompe la escala. | Una función `ejes()` crea objetos nuevos para cada eje. |
| Espacio vacío excesivo arriba del gráfico | Los botones nativos de la vista de tabla (`<button>`) se miden con el CSS del tema de WordPress, que les da más alto. | `tableView` desactivado por defecto y botón «Tabla» propio en la barra de herramientas. |
| Fechas del eje en inglés | Las marcas de fecha del eje no tomaban la configuración `locale: "es-ES"`. | Formateador propio con `toLocaleDateString('es-CO')`, por día, mes o año según la serie. |
| Etiquetas «undefined» sobre las barras | Una sola serie sin `groupBy` y etiquetas automáticas. | `label: false` en gráficos con ejes y `groupBy` constante en series únicas. |
| Errores CORS al cargar imágenes | D3plus trata los campos llamados `url` como imágenes y las descarga. | Los enlaces de los datos se llaman `enlace`. |
| Cajas y bigotes horizontales desalineadas | Con `discrete: "y"` y muchas categorías, las cajas no quedan alineadas con sus etiquetas. | Cajas verticales con nombres cortos de subregión. |
| Leyendas encima de la figura siguiente | CSS que forzaba `height: 100%` en el `svg` y su contenedor. | Se quitó; el alto lo fija el lienzo. |
| Posible inyección de HTML en tooltips | Los tooltips de D3plus insertan texto como HTML. | Los textos de las APIs se limpian en el servidor (`SAN_Catalogo::sanear_textos`) y se escapan en JavaScript (`esc()`). |
| Leyendas y ejes cortados de forma intermitente («Frente a Sanquianga...», «Inun-...») | `fontExists()` de D3plus elige la primera fuente de la lista que cree instalada y **guarda esa decisión para toda la página**. Si mide antes de que llegue la fuente web, la descarta. Además da `-apple-system` por instalada siempre, así que en Windows y Linux mide con una fuente y el navegador pinta con otra. | A D3plus solo se le pasa `"Nunito Sans", sans-serif`. Además, el plugin espera a que cargue la fuente (máximo 3 s) antes de dibujar y redibuja si llega más tarde. |
| Fechas cortadas («1…», «30de...») en columnas angostas y móvil | D3plus rotula todas las fechas y las trunca si no caben. | Se rotula una de cada *k* fechas según el ancho del lienzo (`labels`); en series de más de 31 puntos la cuadrícula sigue a las etiquetas (`ticks`). En series horarias solo se rotulan horas redondas, en formato de 24 h. |
| Casi todas las etiquetas de meses ocultas en barras apiladas | Los ejes discretos comparan las fechas por identidad del objeto `Date`, y cada fila creaba el suyo. | Todas las filas con la misma fecha comparten una única instancia de `Date`. |
| Nombres largos cortados en la leyenda en móvil | La leyenda en fila reparte el ancho entre los elementos. | En lienzos de menos de 480 px la leyenda va en columna (`legendConfig.direction: "column"`). |

## Referencias

- Documentación y ejemplos: <https://d3plus.org/>
- Gráfico de cuerdas: <https://d3plus.org/?path=/docs/core-charts-chord--d3plus>
- Repositorio: <https://github.com/d3plus/d3plus> (`CHANGELOG.md`, `MIGRATION.md`)
- D3: <https://d3js.org/>
