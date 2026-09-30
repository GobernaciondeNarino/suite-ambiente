# Suite Ambiente — Gobernación de Nariño

Repositorio del plugin de WordPress **Suite Ambiente Nariño**, un observatorio ambiental del departamento de Nariño. Consulta 13 APIs abiertas que se actualizan de forma continua, dibuja 47 visualizaciones con D3.js v7 y D3plus v4 (cada una con análisis cualitativo y cuantitativo) y publica 19 conjuntos de datos abiertos.

| Carpeta | Contenido |
|---|---|
| [`suite-ambiente-narino/`](suite-ambiente-narino/) | **El plugin.** Se instala copiando esta carpeta en `wp-content/plugins/`. Ver su [README](suite-ambiente-narino/README.md). |
| [`docs/`](docs/) | Documentación técnica e investigación. |
| [`tests/`](tests/) | Pruebas unitarias en PHP y pruebas de extremo a extremo con Playwright. |
| [`scripts/`](scripts/) | Preparación del entorno de desarrollo. |
| `.claude/`, `.github/` | Configuración de Claude Code (plugins, skills, hooks) y revisión de seguridad automática. |

## Documentación

| Documento | Contenido |
|---|---|
| [Investigación de APIs](docs/investigacion-apis.md) | Fuentes implementadas y por qué; pruebas en vivo de más de 40 APIs; riesgos y cupos. |
| [Anexo: public-apis](docs/anexo-public-apis.md) | Evaluación de 334 entradas del repositorio public-apis. |
| [Catálogo de visualizaciones](docs/catalogo-visualizaciones.md) | Las 47 visualizaciones, sus tipos de gráfico, los shortcodes y los recursos de datos abiertos. |
| [D3plus v4](docs/d3plus-v4.md) | Qué cambió en la versión 4, cómo la usa el plugin y problemas resueltos. |
| [Arquitectura](docs/arquitectura.md) | Flujo de datos, almacenamiento, API REST, seguridad y puntos de extensión. |
| [Herramientas de desarrollo](docs/herramientas-desarrollo.md) | Repomix, skills de Anthropic, Chrome DevTools MCP, revisión de seguridad, graphify y Apple Design Skill. |

## Desarrollo

El plugin no necesita compilación. Las herramientas de desarrollo son opcionales:

```bash
bash scripts/setup-dev.sh   # instala Repomix (npm) y graphify (Python)
npm test                    # php -l + pruebas unitarias (45)
npm run pack:plugin         # empaqueta el plugin para análisis con IA (Repomix)
npm run graph               # actualiza el grafo de conocimiento (graphify)
```

Para probar en un WordPress local:

```bash
# Con WP-CLI, desde la raíz de WordPress
ln -s "$PWD/suite-ambiente-narino" wp-content/plugins/suite-ambiente-narino
wp plugin activate suite-ambiente-narino
```

Las pruebas de extremo a extremo (`npm run test:e2e`, `npm run test:admin`) necesitan ese WordPress en marcha; ver [herramientas de desarrollo](docs/herramientas-desarrollo.md#pruebas-del-plugin).

## Licencia

GPL-2.0-or-later. D3 (ISC) y D3plus (MIT) se incluyen con sus licencias en `suite-ambiente-narino/assets/vendor/`. Los datos pertenecen a sus fuentes y se publican con la atribución y la licencia de cada una.
