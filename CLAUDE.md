# Suite Ambiente Nariño

Plugin de WordPress (PHP 7.4+, JavaScript sin build) de observación ambiental para la Gobernación de Nariño. El código del plugin está en `suite-ambiente-narino/`; la documentación, en `docs/` (empiece por `docs/arquitectura.md`).

## Convenciones

- Idioma: español en código (clases, métodos, variables), comentarios, textos de interfaz, documentación y mensajes de commit.
- Espacio de nombres `GobernacionNarino\SuiteAmbiente`; clases `SAN_Foo_Bar` en `class-san-foo-bar.php` (autocarga en `includes/class-san-autoload.php`).
- Dominio de traducción `suite-ambiente-narino`; API REST `suite-ambiente/v1`.
- Estilo WordPress (tabulaciones, espacios dentro de paréntesis, `array()`), también en JavaScript.
- Documentos nuevos en Markdown (`.md`).
- Diseño: seguir el Manual de Identidad Visual y el Manual de sitios web de la Gobernación (verde `#10A13B`, amarillo `#FFD500`, azul `#003366`, texto `#4A4A4A`; Hind Madurai y Nunito Sans). La paleta de gráficos está validada para daltonismo: no agregue colores sin validarlos.

## Reglas del proyecto

- Fuentes de datos: una clase `SAN_Fuente_*` por API en `includes/fuentes/`, registrada en `SAN_Fuentes::clases()`. Toda llamada HTTP pasa por `SAN_Http` y solo a los hosts declarados en `hosts()`.
- Visualizaciones: se declaran en las clases `SAN_Viz_*` de `includes/visualizaciones/`. El procesador devuelve `ok`, `datos`, `config` y `analisis`. Los tipos de gráfico deben ser compatibles con la forma de los datos (`SAN_Catalogo::FORMAS`).
- Seguridad: escapar toda salida, validar ids contra el catálogo, `manage_options` + nonce en el panel y en las rutas `admin/*`, llaves de API cifradas y nunca enviadas al navegador ni a los registros.
- D3plus v4: ver `docs/d3plus-v4.md` antes de tocar `assets/js/san-graficos.js`. Las propiedades de texto van como funciones, los objetos de ejes se crean nuevos para cada eje y la fuente se pasa como `"Nunito Sans", sans-serif` (nunca la pila completa con `-apple-system`).
- No evadir bloqueos de las fuentes: el modo «navegador» de SGC Volcanes solo se activa con autorización del SGC.
- Open-Meteo tiene cupo (10 000 llamadas al día, uso no comercial). No vacíe su caché en bucle durante las pruebas.

## Comprobaciones

- `npm test`: `php -l` y pruebas unitarias (`tests/run.php`).
- `node tests/e2e/navegador.mjs <url> <salida> <rutas…>` y `node tests/e2e/admin.mjs <url> <salida>`: pruebas en Chromium contra un WordPress con el plugin activo.
- Si cambia el catálogo de visualizaciones o de datos abiertos, actualice `docs/catalogo-visualizaciones.md`.

## graphify

This project has a knowledge graph at graphify-out/ with god nodes, community structure, and cross-file relationships.

Rules:
- For codebase questions, first run `graphify query "<question>"` when graphify-out/graph.json exists. Use `graphify path "<A>" "<B>"` for relationships and `graphify explain "<concept>"` for focused concepts. These return a scoped subgraph, usually much smaller than GRAPH_REPORT.md or raw grep output.
- If graphify-out/wiki/index.md exists, use it for broad navigation instead of raw source browsing.
- Read graphify-out/GRAPH_REPORT.md only for broad architecture review or when query/path/explain do not surface enough context.
- After modifying code, run `graphify update .` to keep the graph current (AST-only, no API cost).
