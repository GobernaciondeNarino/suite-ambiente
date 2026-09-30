# Despliegue en producción

Esta guía sirve para instalar Suite Ambiente Nariño en el servidor de la Gobernación y dejarlo funcionando solo:

- La sincronización corre desde el cron del servidor.
- NASA FIRMS usa la MAP_KEY institucional.
- Hay un monitoreo de las fuentes.

## 1. Requisitos del servidor

| Requisito | Detalle |
|---|---|
| WordPress | 6.0 o superior |
| PHP | 7.4 o superior, con OpenSSL (cifra las llaves de API) y cURL |
| WP-CLI | Recomendado: lo usan el cron y el monitoreo de esta guía |
| Base de datos | MySQL o MariaDB; el plugin crea `{prefijo}san_cache` y `{prefijo}san_logs` |
| Salida HTTPS | El servidor debe poder conectarse por el puerto 443 a los hosts de la tabla siguiente |

**Hosts de salida.** Si el servidor está detrás de un *firewall* o un proxy de salida, permita estos hosts. El plugin no se conecta a ningún otro.

| Fuente | Hosts |
|---|---|
| Open-Meteo · Clima y pronóstico | `api.open-meteo.com`, `customer-api.open-meteo.com` |
| Open-Meteo · Calidad del aire (CAMS) | `air-quality-api.open-meteo.com`, `customer-air-quality-api.open-meteo.com` |
| Open-Meteo · Caudal de ríos (GloFAS) | `flood-api.open-meteo.com`, `customer-flood-api.open-meteo.com` |
| Open-Meteo · Océano Pacífico | `marine-api.open-meteo.com`, `customer-marine-api.open-meteo.com` |
| IDEAM · Estaciones automáticas (datos.gov.co) | `www.datos.gov.co` |
| Servicio Geológico Colombiano · Sismos | `api.sgc.gov.co` |
| Servicio Geológico Colombiano · Volcanes | `www2.sgc.gov.co` |
| USGS · Sismicidad regional | `earthquake.usgs.gov` |
| GDACS · Alertas de desastres | `www.gdacs.org` |
| NASA FIRMS · Focos de calor | `firms.modaps.eosdis.nasa.gov` |
| NASA POWER · Radiación y agroclima | `power.larc.nasa.gov` |
| GBIF · Biodiversidad (SiB Colombia) | `api.gbif.org` |
| iNaturalist · Ciencia ciudadana | `api.inaturalist.org` |

Los hosts `customer-…` solo se usan si se configura la llave de un plan comercial de Open-Meteo.

Los navegadores de los visitantes, además, cargan las tipografías de `fonts.googleapis.com` y `fonts.gstatic.com`. Esto se puede desactivar en **Configuración → General**. Si se elige jsDelivr como origen de las librerías, también cargan de `cdn.jsdelivr.net`.

## 2. Instalación y actualización

1. Copie la carpeta `suite-ambiente-narino/` en `wp-content/plugins/`, o súbala comprimida desde **Plugins → Añadir nuevo**.
2. Active el plugin: `wp plugin activate suite-ambiente-narino`.
3. Pruebe las fuentes: `wp suite-ambiente verificar`.
4. Para actualizar, reemplace la carpeta. Las tablas y los ajustes se conservan, y el esquema se migra solo.

> **Llaves y sales de WordPress.** Las llaves de API se cifran con una clave derivada de `AUTH_SALT`/`AUTH_KEY` de `wp-config.php`. Si cambia las sales, por ejemplo al rotarlas o al migrar el sitio sin copiar `wp-config.php`, las llaves guardadas dejan de poder leerse. En ese caso vuelva a pegarlas en **Configuración → APIs**.

## 3. Sincronización con el cron del servidor

WP-Cron solo se ejecuta cuando alguien visita el sitio. Si el sitio tiene pocas visitas de madrugada, los datos envejecen. En producción conviene que lo dispare el cron del sistema.

### 3.1 Desactivar el disparo por visitas

En `wp-config.php`, antes de la línea `/* That's all, stop editing! */`:

```php
define( 'DISABLE_WP_CRON', true );
```

### 3.2 Programar el cron (opción recomendada: WP-CLI)

Edite el crontab del usuario del servidor web (por ejemplo, `sudo crontab -u www-data -e`). Ajuste la ruta de WordPress y la ruta de `wp`:

```cron
MAILTO=""
# Ejecuta todas las tareas de WordPress que estén vencidas, incluidas las del plugin
# (sincronización cada hora y mantenimiento diario).
*/10 * * * * /usr/local/bin/wp --path=/var/www/html cron event run --due-now --quiet
```

Así se ejecutan las tareas del plugin y también las del núcleo de WordPress (actualizaciones, correo programado, etc.).

**Sin WP-CLI.** Si el servidor no tiene WP-CLI, use `curl` contra `wp-cron.php`:

```cron
*/10 * * * * curl -fsS "https://SITIO/wp-cron.php?doing_wp_cron" > /dev/null
```

**Horario propio para el plugin (opcional).** Si prefiere manejar las tareas del plugin por separado, use sus comandos:

```cron
15 * * * *  /usr/local/bin/wp --path=/var/www/html suite-ambiente sincronizar --quiet
40 3 * * *  /usr/local/bin/wp --path=/var/www/html suite-ambiente mantenimiento --quiet
```

No combine esta opción con la anterior: la sincronización se ejecutaría dos veces por hora y gastaría el doble del cupo de Open-Meteo.

### 3.3 Comprobar que funciona

```bash
wp suite-ambiente estado                                  # última y próxima sincronización, cupo de Open-Meteo, fuentes
wp cron event list --fields=hook,next_run_relative | grep san_
```

En el panel, **Configuración → Tablero** muestra el aviso «Sincronización atrasada» si pasan más de 2 horas sin una sincronización completa.

## 4. Monitoreo de las fuentes

`wp suite-ambiente verificar --estricto` termina con código 1 si alguna fuente activa falla. Con esta línea en el crontab, el servidor envía un correo solo cuando hay fallas: el comando `estado` únicamente se ejecuta si `verificar` falla, y cron envía por correo cualquier salida.

```cron
MAILTO="soporte-tic@ejemplo.gov.co"
5 7 * * * /usr/local/bin/wp --path=/var/www/html suite-ambiente verificar --estricto > /dev/null 2>&1 || /usr/local/bin/wp --path=/var/www/html suite-ambiente estado
```

Si una fuente queda bloqueada o fuera de servicio por un tiempo largo, desactívela en **Configuración → APIs** mientras siga así; si no, este monitoreo fallaría todos los días.

Para una página pública de estado, inserte el shortcode `[san_estado_apis]`.

## 5. NASA FIRMS: MAP_KEY institucional

Sin llave, el plugin descarga el CSV público de focos de toda Sudamérica y lo recorta a Nariño. Con una MAP_KEY consulta solo el área del departamento, con menos datos y más rápido.

1. Entre a <https://firms.modaps.eosdis.nasa.gov/api/map_key/> y pulse **Get MAP_KEY**.
2. Registre un correo institucional (por ejemplo, el de la Secretaría TIC), no uno personal. La llave es gratuita y llega a ese correo.
3. En WordPress, vaya a **Suite Ambiente → Configuración → APIs → NASA FIRMS · Focos de calor**.
4. Pegue la llave en **MAP_KEY de NASA FIRMS (opcional)** y guarde. Se guarda cifrada y nunca se envía al navegador ni aparece en los registros.
5. Pulse **Probar ahora**. Debe aparecer «MAP_KEY válida» con las transacciones usadas. Desde la terminal: `wp suite-ambiente verificar --fuente=firms`.

**Detalles de funcionamiento:**

- **Límite de FIRMS:** 5 000 transacciones cada 10 minutos. Con la caché por defecto (60 minutos), el plugin usa muy pocas.
- **Respaldo:** si la MAP_KEY deja de funcionar (vencida, inválida o sin transacciones), el plugin vuelve al CSV público y deja una advertencia en **Registros**.
- **Pérdida de la llave:** para reenviarla, cambiarla o borrarla, entre por <https://firms.modaps.eosdis.nasa.gov/download/> con el mismo correo.

## 6. Fuentes con condiciones especiales

| Fuente | Situación | Qué hacer |
|---|---|---|
| Open-Meteo | Plan gratuito solo para uso no comercial; menos de 10 000 llamadas al día. El plugin usa unas 4 200 al día y, en el peor caso, unas 6 000. | Enviar la consulta de [consulta-open-meteo.md](consulta-open-meteo.md). Vigilar el medidor de **Configuración → Tablero** o `wp suite-ambiente consumo`. Si contratan un plan, pegar la llave en las cuatro fuentes de Open-Meteo. |
| SGC · Volcanes | El plugin usa la lista pública de boletines de `www2.sgc.gov.co`. Funciona, pero no está documentada como datos abiertos y puede cambiar. | Enviar la consulta de [consulta-sgc.md](consulta-sgc.md). Si la lista cambia, «Probar ahora» lo muestra en rojo y la visualización queda en «fuente no disponible». |
| IDEAM | Los datos se cargan una vez al día en datos.gov.co. | Nada. Opcional: un *app token* de Socrata sube el límite de peticiones. |

## 7. Lista de verificación final

- [ ] `DISABLE_WP_CRON` activo y cron del servidor programado (sección 3).
- [ ] `wp suite-ambiente estado` muestra una sincronización de la última hora.
- [ ] MAP_KEY de FIRMS configurada y «Probar ahora» en verde.
- [ ] Monitoreo por correo programado (sección 4).
- [ ] Consulta a Open-Meteo enviada y registrada en [consulta-open-meteo.md](consulta-open-meteo.md).
- [ ] Consulta al SGC sobre la lista de boletines enviada y registrada en [consulta-sgc.md](consulta-sgc.md).
- [ ] Salida HTTPS a los hosts de la sección 1.
- [ ] Copia de seguridad de la base de datos y de `wp-config.php`, que contiene las sales que cifran las llaves.
