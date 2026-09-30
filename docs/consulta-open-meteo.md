# Consulta a Open-Meteo sobre el uso no comercial

El plugin usa el plan gratuito de Open-Meteo, que es solo para uso no comercial. Los [términos](https://open-meteo.com/en/terms) dan como ejemplos de uso no comercial:

- Los sitios privados o sin ánimo de lucro, sin suscripciones ni publicidad.
- La automatización doméstica.
- La investigación pública en instituciones públicas.
- El contenido educativo.

No mencionan los portales de gobierno. Por eso conviene confirmarlo por escrito antes de publicar el observatorio.

- **Destinatario:** `info@open-meteo.com` (contacto indicado en <https://open-meteo.com/en/pricing>).
- **Remitente:** un correo institucional de la Gobernación de Nariño.
- **Antes de enviar:** complete los campos entre corchetes.

## Cifras de uso que se citan

Las cifras se midieron con el contador del plugin (ver [investigacion-apis.md](investigacion-apis.md)):

| Concepto | Valor |
|---|---|
| Ubicaciones consultadas | 64 cabeceras municipales en un lote, más 4 puntos de río y 2 puntos en el océano Pacífico |
| Consumo con la configuración por defecto | ≈ 4 200 llamadas equivalentes al día |
| Peor caso (visitantes que consultan los 64 municipios) | ≈ 6 000 al día |
| Máximo en una hora | ≈ 420 |
| Caché en el servidor | 60 min para los lotes; 3 h por municipio; 6 h para caudales |

## Correo en inglés (para enviar)

> **Subject:** Non-commercial use confirmation — public environmental observatory, Government of Nariño (Colombia)
>
> Dear Open-Meteo team,
>
> I am writing on behalf of the Gobernación de Nariño, the departmental government of Nariño in southwestern Colombia. We are building a public environmental observatory: a free website with no advertising, no subscriptions and no paid features. It shows current weather, forecasts, air quality, river discharge and marine conditions for the 64 municipalities of the department, together with other open data sources (IDEAM, the Colombian Geological Service, NASA and GBIF).
>
> Before publishing it, we would like to confirm that this use qualifies as non-commercial under your terms and can use the free API.
>
> Some details of our usage:
>
> - The data is requested from our server, never from visitors' browsers. Responses are cached: 60 minutes for batch requests, 3 hours per municipality, and 6 hours for river discharge.
> - We send one batch request for the 64 municipal capitals, plus 4 river points and 2 ocean points.
> - We measured about 4,200 weighted API calls per day, following your counting rules (per location, per 10 variables, per 2 weeks). Our worst case is about 6,000 per day, and never more than about 420 per hour.
> - Every chart shows "Open-Meteo.com (CC BY 4.0)" with a link, next to the data timestamp.
> - The site will be available at [URL of the observatory].
>
> If our use does not qualify, could you please tell us which plan you would recommend? Our software already supports your commercial endpoints (customer-api.open-meteo.com and equivalents) with an API key.
>
> Thank you very much for Open-Meteo and for your open data work.
>
> Kind regards,
>
> [Full name]
> [Position] — Secretaría TIC, Innovación y Gobierno Abierto
> Gobernación de Nariño, Colombia
> [Institutional email] · [Phone]

## Versión en español (referencia)

> **Asunto:** Confirmación de uso no comercial — observatorio ambiental público, Gobernación de Nariño (Colombia)
>
> Estimado equipo de Open-Meteo:
>
> Les escribo en nombre de la Gobernación de Nariño, el gobierno departamental de Nariño, en el suroccidente de Colombia. Estamos construyendo un observatorio ambiental público: un sitio web gratuito, sin publicidad, sin suscripciones y sin funciones de pago. Muestra el tiempo actual, pronósticos, calidad del aire, caudal de ríos y condiciones del mar para los 64 municipios del departamento, junto con otras fuentes de datos abiertos (IDEAM, el Servicio Geológico Colombiano, la NASA y GBIF).
>
> Antes de publicarlo, queremos confirmar que este uso se considera no comercial según sus términos y que puede usar la API gratuita.
>
> Algunos detalles de nuestro uso:
>
> - Los datos se piden desde nuestro servidor, nunca desde el navegador de los visitantes. Las respuestas se guardan en caché: 60 minutos para las consultas por lotes, 3 horas por municipio y 6 horas para los caudales.
> - Enviamos una consulta por lotes para las 64 cabeceras municipales, más 4 puntos de río y 2 puntos en el océano.
> - Medimos unas 4 200 llamadas ponderadas al día, según sus reglas de conteo (por ubicación, por cada 10 variables, por cada 2 semanas). Nuestro peor caso es de unas 6 000 al día, y nunca más de unas 420 por hora.
> - Cada gráfico muestra «Open-Meteo.com (CC BY 4.0)» con un enlace, junto a la hora del dato.
> - El sitio estará en [URL del observatorio].
>
> Si nuestro uso no se considera no comercial, ¿podrían indicarnos qué plan recomiendan? Nuestro software ya admite sus servidores comerciales (customer-api.open-meteo.com y equivalentes) con una llave de API.
>
> Muchas gracias por Open-Meteo y por su trabajo con datos abiertos.
>
> Cordialmente,
>
> [Nombre completo]
> [Cargo] — Secretaría TIC, Innovación y Gobierno Abierto
> Gobernación de Nariño, Colombia
> [Correo institucional] · [Teléfono]

## Después de la respuesta

| Respuesta | Qué hacer |
|---|---|
| Confirman el uso no comercial | Guarde el correo como soporte y agregue la fecha y el remitente al final de este documento. No hay que cambiar nada en el plugin. |
| Piden un plan comercial | Contrate el plan. Luego pegue la llave en **Configuración → APIs**, en el campo «Llave de plan comercial» de las cuatro fuentes de Open-Meteo (clima, aire, caudales y océano). Pulse **Probar ahora** en cada una. El plugin pasa solo a los servidores `customer-…open-meteo.com`. |
| No responden | El uso actual es gratuito y queda por debajo de los límites. Vigile el medidor **Cupo gratuito de Open-Meteo** en **Configuración → Tablero** y reitere la consulta. |

## Registro de la consulta

| Fecha de envío | Remitente | Fecha de respuesta | Resultado |
|---|---|---|---|
| | | | |
