# Consulta al Servicio Geológico Colombiano sobre los boletines volcánicos

El plugin toma el nivel de actividad de los volcanes de Nariño de la lista pública de boletines del SGC, la misma que alimenta su página de boletines:

```text
https://www2.sgc.gov.co/Noticias/_api/web/lists/getbytitle('Boletines-Comunicados')/items
```

La consulta cumple estas condiciones:

- Responde sin autenticación a un cliente que se identifica honestamente.
- `robots.txt` no restringe la ruta.
- No se evade ningún bloqueo.

Sin embargo, **no está publicada como datos abiertos** y el SGC está migrando su portal (`www.sgc.gov.co` bloquea el acceso automatizado). Por eso conviene hacer dos cosas:

1. Informar al SGC del uso.
2. Pedir su aval o, mejor, un servicio de datos abiertos con el nivel de actividad de cada volcán.

- **Destinatario sugerido:** el canal de atención al ciudadano del SGC (PQRSD en <https://www.sgc.gov.co>) o el Observatorio Vulcanológico y Sismológico de Pasto.
- **Antes de enviar:** complete los campos entre corchetes.

## Correo

> **Asunto:** Uso de la lista pública de boletines volcánicos en el observatorio ambiental de la Gobernación de Nariño
>
> Señores
> Servicio Geológico Colombiano — Observatorio Vulcanológico y Sismológico de Pasto
>
> Cordial saludo.
>
> La Gobernación de Nariño, a través de la Secretaría TIC, Innovación y Gobierno Abierto, está publicando un observatorio ambiental gratuito y sin publicidad [URL del observatorio]. Entre otros datos, muestra el nivel de actividad de los volcanes Galeras, Cumbal, Chiles, Cerro Negro, Azufral, Doña Juana y Las Ánimas, con enlace a los boletines oficiales del SGC.
>
> Para mantener esa información al día sin intervención manual, nuestro servidor consulta la lista pública «Boletines-Comunicados» de www2.sgc.gov.co, la misma que alimenta la página de boletines del SGC. Lo hace en estas condiciones:
>
> - Una consulta por hora, con caché, identificada como «SuiteAmbienteNarino/1.0» e incluyendo la dirección del sitio.
> - Solo se piden los campos título, fecha de emisión, tipo de boletín, observatorio, nivel de actividad, volcán y ruta del PDF. No se piden ni se guardan datos de las personas que publican.
> - El sitio muestra al SGC y al OVSP como fuente, con enlace a cada boletín.
>
> El archivo volcanos.json del portal de volcanes y el nuevo sitio www.sgc.gov.co rechazan el acceso automatizado, así que no los usamos.
>
> Les solicitamos, respetuosamente:
>
> 1. Confirmar si están de acuerdo con este uso de la lista de boletines o si prefieren otras condiciones (frecuencia, identificación, atribución).
> 2. Informarnos si el SGC ofrece o prevé ofrecer un servicio de datos abiertos con el nivel de actividad vigente de cada volcán, para usarlo en su lugar.
> 3. Indicarnos, si es posible, cómo publican el nivel de Azufral, Doña Juana y Las Ánimas, que solo figura en el texto del boletín mensual.
>
> Agradecemos de antemano su atención y el trabajo del SGC en la vigilancia volcánica del departamento.
>
> Atentamente,
>
> [Nombre completo]
> [Cargo] — Secretaría TIC, Innovación y Gobierno Abierto
> Gobernación de Nariño
> [Correo institucional] · [Teléfono]

## Después de la respuesta

| Respuesta | Qué hacer |
|---|---|
| Están de acuerdo | Guarde la respuesta como soporte y regístrela abajo. |
| Ofrecen un servicio de datos abiertos | Cambie `SAN_Fuente_Sgc_Volcanes` para que use ese servicio. Solo cambian la URL y el procesamiento: la visualización y los datos abiertos conservan su forma. |
| Piden no usar la lista | Desactive la fuente en **Configuración → APIs**. La visualización mostrará «fuente no disponible». |

## Registro de la consulta

| Fecha de envío | Radicado | Fecha de respuesta | Resultado |
|---|---|---|---|
| | | | |
