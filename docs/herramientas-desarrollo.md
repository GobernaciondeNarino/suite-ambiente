# Herramientas de desarrollo

Estas herramientas se instalaron **en el repositorio** para el desarrollo con Claude Code. No forman parte del plugin de WordPress: el plugin (`suite-ambiente-narino/`) no necesita compilación ni dependencias de Node o Python.

| Herramienta | Repositorio | Qué aporta | Dónde está configurada |
|---|---|---|---|
| Repomix | [yamadashy/repomix](https://github.com/yamadashy/repomix) | Empaqueta el código en un solo archivo para análisis con IA; servidor MCP y comandos. | `package.json` (devDependency), `repomix.config.json`, plugins en `.claude/settings.json` |
| Skills de Anthropic | [anthropics/skills](https://github.com/anthropics/skills) | Skills de documentos (docx, pdf, pptx, xlsx) y de ejemplo. | Plugins en `.claude/settings.json` |
| Chrome DevTools MCP | [ChromeDevTools/chrome-devtools-mcp](https://github.com/ChromeDevTools/chrome-devtools-mcp) | Control de Chrome desde Claude Code: depuración, rendimiento y capturas. | Plugin en `.claude/settings.json` |
| Claude Code Security Review | [anthropics/claude-code-security-review](https://github.com/anthropics/claude-code-security-review) | Revisión de seguridad de cada *pull request* y comando `/security-review`. | `.github/workflows/claude-security-review.yml`, `.github/security/instrucciones-wordpress.txt`, `.claude/commands/security-review.md` |
| graphify | [Graphify-Labs/graphify](https://github.com/Graphify-Labs/graphify) | Grafo de conocimiento del código (`graphify-out/`) para consultas de arquitectura. | Skill en `.claude/skills/graphify/`, hooks en `.claude/settings.json`, sección en `CLAUDE.md` |
| Apple Design Skill | [dickwu/apple-design-skill](https://github.com/dickwu/apple-design-skill) | Criterios de diseño de interfaz (contraste, jerarquía, estados, modo oscuro) usados en el panel de administración. | Skill en `.claude/skills/apple-design/`, `skills-lock.json` |

## Puesta en marcha

Al abrir el proyecto en Claude Code, confíe en la carpeta: Claude Code ofrecerá instalar los *marketplaces* y plugins declarados en `.claude/settings.json`. Después ejecute:

```bash
bash scripts/setup-dev.sh
```

El script es idempotente. Instala Repomix con `npm ci` y el CLI de graphify (`graphifyy` en PyPI) con `uv`, `pipx` o `pip`, según lo que haya disponible. En las sesiones de Claude Code en la nube se ejecuta solo, con el hook `SessionStart`.

## Repomix

```bash
npm run pack:ai       # todo el repositorio → repomix-output.xml
npm run pack:plugin   # solo el plugin → repomix-plugin.xml
```

- La configuración está en `repomix.config.json`. Excluye las skills de terceros, las librerías minificadas (`assets/vendor/`), los datos geográficos (`data/`), el anexo de public-apis, `package-lock.json` y `graphify-out/`, y activa la revisión de secretos de Repomix.
- Los archivos generados están en `.gitignore`.
- Los plugins `repomix-mcp`, `repomix-commands` y `repomix-explorer` dan a Claude Code el servidor MCP de Repomix y comandos para explorar repositorios.

## Skills de Anthropic y Chrome DevTools MCP

Se declaran como *marketplaces* de plugins en `.claude/settings.json`:

- `anthropic-agent-skills` → `document-skills` y `example-skills`.
- `chrome-devtools-plugins` → `chrome-devtools-mcp`. Se descarga solo `.claude-plugin/` y `skills/` del repositorio (`sparsePaths`), porque el repositorio completo es grande.

Chrome DevTools MCP necesita Chrome o Chromium en la máquina. Para las pruebas automáticas del plugin se usa Playwright directamente (ver «Pruebas» abajo).

## Revisión de seguridad

**En GitHub.** El flujo `.github/workflows/claude-security-review.yml` revisa el diff de cada *pull request* y comenta los hallazgos. Para activarlo:

1. Cree el secreto `ANTHROPIC_API_KEY` en *Settings → Secrets and variables → Actions*.
2. Active *Require approval for all external contributors* en *Settings → Actions*. La acción no está protegida contra inyección de instrucciones en PR de terceros.

Las instrucciones adicionales para WordPress (`.github/security/instrucciones-wordpress.txt`) piden revisar nonces y capacidades, escape de salida, SQL preparado, SSRF en las llamadas a APIs, manejo de llaves e inyección de fórmulas en CSV. Se excluyen `.claude/skills` y `docs`.

**En local.** Con Claude Code, el comando `/security-review` revisa los cambios de la rama actual. Es el comando del mismo repositorio, con una sección añadida sobre las particularidades del plugin.

## graphify

```bash
graphify update .                       # (re)construye graphify-out/ desde el código, sin costo de API
graphify query "¿cómo se cachean las respuestas de las APIs?"
graphify path "SAN_Rest" "SAN_Cache"
graphify explain "SAN_Catalogo"
```

- `graphify-out/` está en `.gitignore`: cada persona genera su grafo.
- Los hooks `PreToolUse` de `.claude/settings.json` ejecutan `graphify hook-guard` antes de las búsquedas y lecturas de Claude Code para sugerir el grafo cuando existe. Si graphify no está instalado, el hook falla sin bloquear nada.
- Las reglas de uso para Claude están en `CLAUDE.md`.

## Apple Design Skill

La skill está copiada en `.claude/skills/apple-design/`. Su origen y hash quedan en `skills-lock.json`. Se revisó antes de incluirla: son instrucciones y referencias en Markdown. Su único script, `scripts/pull-hig.mjs`, se ejecuta solo a mano y vuelve a descargar las guías de interfaz de Apple desde developer.apple.com; Claude Code no lo ejecuta por su cuenta.

Se aplicó al panel de administración junto con el **Manual de Identidad Visual** y el **Manual de sitios web** de la Gobernación de Nariño:

- Contraste de texto de 4.5:1 o más.
- Áreas táctiles de 28 a 44 px.
- Modo claro y oscuro.
- Un color, un significado: el verde institucional para acciones, los colores de estado solo para estados, siempre con ícono y texto.
- Estados de carga, vacío y error en cada tarjeta.

## Pruebas del plugin

```bash
npm run lint:php      # php -l en todos los archivos PHP
npm run test:php      # pruebas unitarias sin WordPress (tests/run.php)
npm run test:e2e -- http://localhost:8080 ./capturas /tablero/ /graficos/   # páginas públicas con shortcodes
SAN_USUARIO=admin SAN_CLAVE=… npm run test:admin -- http://localhost:8080 ./capturas   # panel de administración
```

- **Unitarias:** `tests/php/bootstrap.php` simula las funciones de WordPress necesarias. `tests/run.php` prueba estadística descriptiva, ICA (Res. 2254/2017) y otras clasificaciones, municipios y geografía, seguridad (saneamiento y cifrado), redacción de llaves en los registros, CSV y GeoJSON, y limpieza de HTML en textos externos.
- **De extremo a extremo:** usan Playwright con Chromium y necesitan un WordPress con el plugin activo.
  - `tests/e2e/navegador.mjs` abre las rutas indicadas (páginas con shortcodes del plugin), espera a que cada gráfico termine de dibujarse y reporta gráficos fallidos y errores de consola. Con `SAN_TEMA=oscuro` emula el modo oscuro.
  - `tests/e2e/admin.mjs` recorre los tres módulos del panel.
  - Ambas guardan capturas en la carpeta de salida.
- **Cupo de Open-Meteo:** las pruebas de extremo a extremo consultan las APIs reales. No vacíe la caché de Open-Meteo entre ejecuciones seguidas; puede recibir HTTP 429 (ver [investigacion-apis.md](investigacion-apis.md)).
