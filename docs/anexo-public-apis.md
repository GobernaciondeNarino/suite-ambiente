# Anexo A — Candidatos del repositorio public-apis

Fuente: `https://raw.githubusercontent.com/public-apis/public-apis/master/README.md`, descargado el 2026-09-30. Secciones Environment, Weather, Science & Math, Open Data, Government y Animals completas; de Geocoding solo las entradas relevantes. La columna «¿Cubre Colombia/Nariño?» marca con «P:» las APIs **probadas** en vivo; el resto es una evaluación por descripción, no verificada.


## Environment (Medio ambiente) — 25 entradas

| Nombre | URL | Auth | HTTPS | CORS | ¿Cubre Colombia/Nariño? | Nota |
|---|---|---|---|---|---|---|
| AirNow | https://docs.airnowapi.org/ | apiKey | Yes | Yes | No | Solo EE. UU. (EPA) |
| BreezoMeter Pollen | https://docs.breezometer.com/api-documentation/pollen-api/v2/ | apiKey | Yes | Unknown | Global (llave) | Servicio comercial; polen |
| Carbon Interface | https://docs.carboninterface.com/ | apiKey | Yes | Yes | N/A | Calculadora de emisiones, no monitoreo |
| Climatiq | https://docs.climatiq.io | apiKey | Yes | Yes | N/A | Calculadora de huella |
| Cloverly | https://www.cloverly.com/carbon-offset-documentation | apiKey | Yes | Unknown | N/A | Compensación de carbono |
| CO2 Offset | https://co2offset.io/api.html | No | Yes | Unknown | N/A | Calculadora |
| Danish data service Energi | https://www.energidataservice.dk/ | No | Yes | Unknown | No | Dinamarca |
| GridHub | https://grid-hub.app/developers | apiKey | Yes | Yes | No | Mercados eléctricos |
| GrünstromIndex | https://gruenstromindex.de/ | No | No | Yes | No | Alemania |
| IQAir | https://www.iqair.com/air-pollution-data-api | apiKey | Yes | Unknown | Global (llave) | No probado; cobertura en Nariño desconocida |
| kanari | https://kanari.io/en/api | No | Yes | Yes | P: Global (declarado) | Probado: sin eventos en Sudamérica en respuesta truncada |
| Luchtmeetnet | https://api-docs.luchtmeetnet.nl/ | No | Yes | Unknown | No | Países Bajos |
| National Grid ESO | https://data.nationalgrideso.com/ | No | Yes | Unknown | No | Reino Unido |
| OpenAQ | https://docs.openaq.org/ | apiKey | Yes | Unknown | P: Global (llave) | Probado: requiere X-API-Key |
| Open-Meteo | https://open-meteo.com/ | No | Yes | Yes | P: Sí (global) | Probado: ADOPT |
| PM2.5 Open Data Portal | https://pm25.lass-net.org/#apis | No | Yes | Unknown | Dudosa | Sensores LASS, foco Asia; no probado |
| PM25.in | http://www.pm25.in/api_doc | apiKey | No | Unknown | No | China |
| PVGIS | https://joint-research-centre.ec.europa.eu/pvgis-photovoltaic-geographical-information-system/getting-started-pvgis/api-non-interactive-service | No | Yes | No | Parcial | Radiación/FV; cobertura Sudamérica parcial; no probado |
| PVWatts | https://developer.nrel.gov/docs/solar/pvwatts/v6/ | apiKey | Yes | Unknown | Parcial (llave) | NREL; no probado |
| Solematica | https://www.solematica.it/sviluppatori | No | Yes | No | No | Italia |
| Srp Energy | https://srpenergy-api-client-python.readthedocs.io/en/latest/api.html | apiKey | Yes | No | No | Clientes SRP EE. UU. |
| SustainMetrics | https://www.sustainmetrics.net/api | apiKey | Yes | Yes | N/A | Factores de emisión |
| UK Carbon Intensity | https://carbon-intensity.github.io/api-definitions/#carbon-intensity-api-v1-0-0 | No | Yes | Unknown | No | Reino Unido |
| WattFigure | https://api.wattfigure.com/ | No | Yes | Yes | No | EE. UU. |
| Website Carbon | https://api.websitecarbon.com/ | No | Yes | Unknown | N/A | Huella web |

## Weather (Clima) — 43 entradas

| Nombre | URL | Auth | HTTPS | CORS | ¿Cubre Colombia/Nariño? | Nota |
|---|---|---|---|---|---|---|
| Weatherstack | https://weatherstack.com/?utm_source=Github&utm_medium=Referral&utm_campaign=Public-apis-repo-Best-sellers | apiKey | Yes | Unknown | Global (llave) | Comercial |
| 7Timer! | http://www.7timer.info/doc.php?lang=en | No | No | Unknown | P: Sí (global) | Probado: REJECT (redundante) |
| AccuWeather | https://developer.accuweather.com/apis | apiKey | No | Unknown | Global (llave) | Comercial |
| Aemet | https://opendata.aemet.es/centrodedescargas/inicio | apiKey | Yes | Unknown | No | España |
| APIXU | https://www.apixu.com/doc/request.aspx | apiKey | Yes | Unknown | Obsoleto | Servicio migrado a Weatherstack |
| AQICN | https://aqicn.org/api/ | apiKey | Yes | Unknown | P: Global (token) | Probado como WAQI: token demo solo Shanghai |
| AviationWeather | https://www.aviationweather.gov/dataserver | No | Yes | Unknown | Parcial | METAR/TAF; Pasto (SKPS), Ipiales (SKIP), Tumaco (SKCO) plausibles; no probado |
| ColorfulClouds | https://open.caiyunapp.com/ColorfulClouds_Weather_API | apiKey | Yes | Yes | Global (llave) | China principalmente |
| Euskalmet | https://opendata.euskadi.eus/api-euskalmet/-/api-de-euskalmet/ | apiKey | Yes | Unknown | No | País Vasco |
| Foreca | https://developer.foreca.com | OAuth | Yes | Unknown | Global (OAuth) | Comercial |
| Hail History | https://hail-history-noaa.netlify.app/api-docs.html | No | Yes | Yes | No | EE. UU. |
| HG Weather | https://hgbrasil.com/status/weather | apiKey | Yes | Yes | No | Brasil |
| Hong Kong Obervatory | https://www.hko.gov.hk/en/abouthko/opendata_intro.htm | No | Yes | Unknown | No | Hong Kong |
| IPMA | https://api.ipma.pt/open-data/ | No | Yes | Unknown | No | Portugal |
| KNMI | https://developer.dataplatform.knmi.nl/ | apiKey | Yes | Unknown | No | Países Bajos |
| Meltema | https://meltema.com/docs | No | Yes | No | Global (declarado) | No se pudo probar: conexión reiniciada vía proxy |
| Météo-France | https://portail-api.meteofrance.fr/ | apiKey | Yes | Unknown | No/Parcial (llave) | Francia |
| Meteorologisk Institutt | https://api.met.no/weatherapi/documentation | User-Agent | Yes | Unknown | P: Sí (global) | Probado: requiere User-Agent |
| Micro Weather | https://m3o.com/weather/api | apiKey | Yes | Unknown | Obsoleto | m3o descontinuado; no probado |
| NASA POWER | https://power.larc.nasa.gov/docs/ | No | Yes | Yes | P: Sí (global) | Probado: ADOPT |
| ODWeather | http://api.oceandrivers.com/static/docs.html | No | No | Unknown | No | España/costas; HTTP |
| Oikolab | https://docs.oikolab.com | apiKey | Yes | Yes | Global (llave) | No probado |
| Open-Meteo | https://open-meteo.com/ | No | Yes | Yes | P: Sí (global) | Probado: ADOPT |
| Open-Meteo Ensemble | https://open-meteo.com/en/docs/ensemble-api | No | Yes | Yes | Sí (global) | Misma plataforma Open-Meteo; no probado aparte |
| openSenseMap | https://api.opensensemap.org/ | No | Yes | Yes | P: Global (crowd) | Probado: 0 estaciones en Nariño |
| OpenUV | https://www.openuv.io | apiKey | Yes | Unknown | P: Global (llave) | Probado: requiere llave |
| OpenWeatherMap | https://openweathermap.org/api | apiKey | Yes | Unknown | Global (llave) | No probado |
| Pirate Weather | https://pirateweather.net/en/latest/ | No | Yes | Yes | P: Global (llave) | Probado: 401 sin llave (el listado dice "No") |
| QWeather | https://dev.qweather.com/en/ | apiKey | Yes | Yes | Global (llave) | No probado |
| Rainbow Weather | https://developer.rainbow.ai/ | apiKey | Yes | Unknown | Global (llave) | No probado |
| RainViewer | https://www.rainviewer.com/api.html | No | Yes | Unknown | P: No en Nariño | Probado: mapa de cobertura sin radar en Nariño |
| Storm Glass | https://stormglass.io/ | apiKey | Yes | Yes | Global (llave) | Marino; no probado |
| Terrace Weather | https://terrace.javiermateo.dev/swagger-ui.html | No | Yes | Yes | N/A | Veredicto terrazas |
| Tomorrow | https://docs.tomorrow.io | apiKey | Yes | Unknown | Global (llave) | No probado |
| US Weather | https://www.weather.gov/documentation/services-web-api | No | Yes | Yes | No | EE. UU. (NWS) |
| Visual Crossing | https://www.visualcrossing.com/weather-api | apiKey | Yes | Yes | Global (llave) | No probado |
| weather-api | https://github.com/robertoduessmann/weather-api | No | Yes | No | Global | Proyecto personal; no probado |
| WeatherAPI | https://www.weatherapi.com/ | apiKey | Yes | Yes | Global (llave) | No probado |
| Weatherbit | https://www.weatherbit.io/api | apiKey | Yes | Unknown | Global (llave) | No probado |
| WeatherTotals | https://weathertotals.com/api | No | Yes | Yes | No | EE. UU. |
| World Time & Weather | https://worldtimeweather.com/api.html | No | Yes | Yes | Parcial | 400 ciudades; no probado |
| wttr.in | https://wttr.in/:help | No | Yes | Yes | P: Sí (global) | Probado: REJECT (no oficial/redundante) |
| Yandex.Weather | https://yandex.com/dev/weather/ | apiKey | Yes | No | Global (llave) | No probado |

## Science & Math (Ciencia) — 47 entradas

| Nombre | URL | Auth | HTTPS | CORS | ¿Cubre Colombia/Nariño? | Nota |
|---|---|---|---|---|---|---|
| arcsecond.io | https://api.arcsecond.io/ | No | Yes | Unknown | N/A | Astronomía |
| arXiv | https://arxiv.org/help/api/user-manual | No | Yes | Unknown | N/A | Bibliografía |
| CodeCogs | https://editor.codecogs.com/docs/4-LaTeX_rendering.php | No | Yes | Unknown | N/A | Tema no ambiental |
| CORE | https://core.ac.uk/services#api | apiKey | Yes | Unknown | N/A | Bibliografía |
| CycleCalcs | https://www.cyclecalcs.com/api.html | No | Yes | Yes | N/A | Astronomía |
| DataCite | https://support.datacite.org/docs/rest-api | No | Yes | Yes | N/A | Metadatos de investigación |
| Europe PMC | https://europepmc.org/RestfulWebService | No | Yes | Yes | N/A | Bibliografía |
| GBIF | https://www.gbif.org/developer/summary | No | Yes | Yes | P: Sí | Probado: ADOPT |
| iDigBio | https://github.com/idigbio/idigbio-search-api/wiki | No | Yes | Unknown | Parcial | Especímenes de museo; no probado |
| inspirehep.net | https://github.com/inspirehep/rest-api-doc | No | Yes | Unknown | N/A | Física |
| isEven (humor) | https://isevenapi.xyz/ | No | Yes | Unknown | N/A | Tema no ambiental |
| ISRO | https://isro.vercel.app | No | Yes | No | N/A | Espacio |
| ITIS | https://www.itis.gov/ws_description.html | No | Yes | Unknown | N/A | Taxonomía, sin geografía |
| Launch Library 2 | https://thespacedevs.com/llapi | No | Yes | Yes | N/A | Lanzamientos espaciales |
| Materials Platform for Data Science | https://mpds.io | apiKey | Yes | No | N/A | Materiales |
| Minor Planet Center | http://www.asterank.com/mpc | No | No | Unknown | N/A | Astronomía |
| MyGene.info | https://docs.mygene.info/ | No | Yes | Yes | N/A | Genes |
| NASA | https://api.nasa.gov | No | Yes | No | P: Sí (global) | api.nasa.gov; se probaron EONET/POWER/FIRMS (no listados aquí) |
| NASA InSight | https://api.nasa.gov/ | apiKey | Yes | Yes | N/A | Marte |
| NASA ADS | https://ui.adsabs.harvard.edu/help/api/api-docs.html | OAuth | Yes | Yes | N/A | Astrofísica |
| Newton | https://newton.vercel.app | No | Yes | No | N/A | Tema no ambiental |
| Noctua | https://api.noctuasky.com/api/v1/swaggerdoc/ | No | Yes | Unknown | N/A | Astronomía |
| Numbers | https://math.tools/api/numbers/ | apiKey | Yes | No | N/A | Tema no ambiental |
| Ocean Facts | https://oceanfacts.herokuapp.com/ | No | Yes | Unknown | N/A | Datos curiosos |
| Open Notify | http://open-notify.org/Open-Notify-API/ | No | No | No | N/A | ISS |
| Open Science Framework | https://developer.osf.io | No | Yes | Unknown | N/A | Repositorio |
| OpenAlex | https://docs.openalex.org/ | No | Yes | Yes | N/A | Bibliografía |
| Open Ephemeris | https://openephemeris.com/docs | apiKey | Yes | No | N/A | Astronomía |
| OrbitalWiki | https://orbitalwiki.com/developers | apiKey | Yes | Yes | N/A | Satélites |
| Purple Air | https://www2.purpleair.com/ | No | Yes | Unknown | P: Global (llave) | Probado: requiere llave |
| RCSB PDB | https://data.rcsb.org/ | No | Yes | Yes | N/A | Proteínas |
| Remote Calc | https://github.com/elizabethadegbaju/remotecalc | No | Yes | Yes | N/A | Tema no ambiental |
| Semantic Scholar | https://api.semanticscholar.org/ | No | Yes | Unknown | N/A | Bibliografía |
| SHARE | https://share.osf.io/api/v2/ | No | Yes | No | N/A | Repositorio |
| Solar System OpenData | https://api.le-systeme-solaire.net | No | Yes | Yes | N/A | Astronomía |
| SpaceX | https://github.com/r-spacex/SpaceX-API | No | Yes | No | N/A | Espacio |
| SpaceX | https://api.spacex.land/graphql/ | No | Yes | Unknown | N/A | Espacio |
| Sunrise and Sunset | https://sunrise-sunset.org/api | No | Yes | No | P: Sí (global) | Probado: REJECT (redundante) |
| Tallytopia | https://tallytopia.com/api-docs | No | Yes | Yes | N/A | Tema no ambiental |
| Times Adder | https://github.com/FranP-code/API-Times-Adder | No | Yes | No | N/A | Tema no ambiental |
| TLE | https://tle.ivanstanojevic.me/#/docs | No | Yes | No | N/A | Satélites |
| Unpaywall | https://unpaywall.org/products/api | No | Yes | Yes | N/A | Bibliografía |
| USGS Earthquake Hazards Program | https://earthquake.usgs.gov/fdsnws/event/1/ | No | Yes | No | P: Sí (global) | Probado: ADOPT complementario |
| USGS Water Services | https://waterservices.usgs.gov/ | No | Yes | No | No | Solo EE. UU. |
| VedIntel™ AstroAPI | https://vedintelastroapi.com/docs | apiKey | Yes | Yes | N/A | Tema no ambiental |
| World Bank | https://datahelpdesk.worldbank.org/knowledgebase/topics/125589 | No | Yes | No | Nacional | Indicadores país, no tiempo real |
| xMath | https://x-math.herokuapp.com/ | No | Yes | Yes | N/A | Tema no ambiental |

## Open Data (Datos abiertos) — 63 entradas

| Nombre | URL | Auth | HTTPS | CORS | ¿Cubre Colombia/Nariño? | Nota |
|---|---|---|---|---|---|---|
| 49 Gallery Historical Data | https://api.181649.com/docs | No | Yes | Yes | N/A | Tema no ambiental o sin cobertura local |
| AcreLens | https://www.acrelens.com | apiKey | Yes | Unknown | No | EE. UU. |
| API Setu | https://www.apisetu.gov.in/ | No | Yes | Yes | No | India |
| APIllow | https://apillow.co/docs.html | apiKey | Yes | Yes | N/A | Tema no ambiental o sin cobertura local |
| Archive.org | https://archive.readme.io/docs | No | Yes | No | N/A |  |
| Big Data Explained | https://bigdataexplained.com/data | No | Yes | Yes | N/A | Tema no ambiental o sin cobertura local |
| Black History Facts | https://www.blackhistoryapi.io/docs | apiKey | Yes | Yes | N/A | Tema no ambiental o sin cobertura local |
| BotsArchive | https://botsarchive.com/docs.html | No | Yes | Unknown | N/A | Tema no ambiental o sin cobertura local |
| BTU Graph | https://btugraph.com/data/ | No | Yes | Yes | N/A | Tema no ambiental o sin cobertura local |
| Callook.info | https://callook.info | No | Yes | Unknown | No | EE. UU. |
| CARTO | https://carto.com/ | apiKey | Yes | Unknown | N/A | Tema no ambiental o sin cobertura local |
| CollegeScoreCard.ed.gov | https://collegescorecard.ed.gov/data/ | No | Yes | Unknown | No | EE. UU. |
| CoworkingView | https://coworkingview.com/openapi.json | No | Yes | No | No | Unión Europea |
| CuttingToolsAI | https://cuttingtoolsai.eu/api | No | Yes | Yes | N/A | Tema no ambiental o sin cobertura local |
| DevLifeCheck | https://devlifecheck.com/developers | No | Yes | No | N/A | Tema no ambiental o sin cobertura local |
| Enigma Public | https://developers.enigma.com/docs | apiKey | Yes | Yes | No | EE. UU. |
| EOSL | https://eosl.ai/api/ | No | Yes | Yes | N/A | Tema no ambiental o sin cobertura local |
| French Address Search | https://geo.api.gouv.fr/adresse | No | Yes | Unknown | No | Francia |
| GENESIS | https://www.destatis.de/EN/Service/OpenData/api-webservice.html | OAuth | Yes | Unknown | No | Alemania |
| HousingFeed | https://housingfeed.com/docs | No | Yes | Yes | No | EE. UU. |
| InfraNode | https://infranode.dev | apiKey | Yes | Unknown | No | Alemania |
| i6eal Open AI Data | https://i6eal.de/en/tools/data/ | No | Yes | Yes | No | Alemania |
| Joshua Project | https://api.joshuaproject.net/ | apiKey | Yes | Unknown | N/A | Tema no ambiental o sin cobertura local |
| K-Data Gate | https://kdata-gate.vercel.app/docs | apiKey | Yes | Unknown | No | Corea |
| Kaggle | https://www.kaggle.com/docs/api | apiKey | Yes | Unknown | Variable (llave) | Datasets estáticos |
| LinkPreview | https://www.linkpreview.net | apiKey | Yes | Yes | N/A | Tema no ambiental o sin cobertura local |
| LottoLens PH | https://remo65588-boop.github.io/lottolens-ph-public-data/api/ | No | Yes | Yes | No | Filipinas |
| Lowy Asia Power Index | https://github.com/0x0is1/lowy-index-api-docs | No | Yes | Unknown | No | Asia |
| Microburbs | https://www.microburbs.com.au/developers/api-docs | apiKey | Yes | No | No | Australia |
| Microlink.io | https://microlink.io | No | Yes | Yes | N/A | Tema no ambiental o sin cobertura local |
| MostExpensiveWatches | https://mostexpensivewatches.net/api | No | Yes | Yes | N/A | Tema no ambiental o sin cobertura local |
| ModelPartFinder Error Codes | https://modelpartfinder.com/docs/api | No | Yes | Yes | N/A | Tema no ambiental o sin cobertura local |
| Nasdaq Data Link | https://docs.data.nasdaq.com/ | apiKey | Yes | Unknown | N/A | Tema no ambiental o sin cobertura local |
| Nobel Prize | https://www.nobelprize.org/about/developer-zone-2/ | No | Yes | Yes | N/A | Tema no ambiental o sin cobertura local |
| Onyx Bazaar | https://onyx-actions.onrender.com/bazaar | No | Yes | Unknown | N/A | Tema no ambiental o sin cobertura local |
| Open Data Minneapolis | https://opendata.minneapolismn.gov/ | No | Yes | No | No | EE. UU. |
| Open Scholarships | https://scholarships.grudged.io | No | Yes | Yes | No | EE. UU. |
| openAFRICA | https://africaopendata.org/ | No | Yes | Unknown | No | África |
| OpenCorporates | http://api.opencorporates.com/documentation/API-Reference | apiKey | Yes | Unknown | N/A | Tema no ambiental o sin cobertura local |
| OpenSanctions | https://www.opensanctions.org/docs/api/ | No | Yes | Yes | N/A | Tema no ambiental o sin cobertura local |
| Pan Africa Data | https://panafricadata.com | apiKey | Yes | Unknown | No | África |
| PayCrunch | https://paycrunch.co/api.html | No | Yes | Yes | No | EE. UU. |
| PeakMetrics | https://rapidapi.com/peakmetrics-peakmetrics-default/api/peakmetrics-news | apiKey | Yes | Unknown | N/A | Tema no ambiental o sin cobertura local |
| PublicDataHub | https://publicdatahub.org/api | No | Yes | Yes | No | EE. UU. |
| Recreation Information Database | https://ridb.recreation.gov/ | apiKey | Yes | Unknown | No | EE. UU. |
| Registrum | https://api.registrum.co.uk/docs | apiKey | Yes | No | No | Reino Unido |
| Scoop.it | http://www.scoop.it/dev | apiKey | No | Unknown | N/A | Tema no ambiental o sin cobertura local |
| SlashYear | https://slashyear.com/api | No | Yes | Yes | N/A | Tema no ambiental o sin cobertura local |
| Socrata | https://dev.socrata.com/ | OAuth | Yes | Yes | P: Sí | datos.gov.co corre en Socrata; probado: ADOPT (IDEAM) |
| Statistics of the World | https://statisticsoftheworld.com/api-docs | No | Yes | Yes | Nacional | Indicadores país |
| StatOrigin | https://statorigin.org/docs/api | No | Yes | Yes | N/A | Tema no ambiental o sin cobertura local |
| Teleport | https://developers.teleport.org/ | No | Yes | Unknown | No | Calidad de vida ciudades |
| Tilth | https://www.tilth.uk/data | No | Yes | Yes | No | Reino Unido |
| Umeå Open Data | https://opendata.umea.se/api/ | No | Yes | Yes | No | Suecia |
| Universities List | https://github.com/Hipo/university-domains-list | No | Yes | Unknown | N/A | Tema no ambiental o sin cobertura local |
| University of Oslo | https://data.uio.no/ | No | Yes | Unknown | No | Noruega |
| UPC database | https://upcdatabase.org/api | apiKey | Yes | Unknown | N/A | Tema no ambiental o sin cobertura local |
| Urban Observatory | https://urbanobservatory.ac.uk | No | No | No | No | Reino Unido |
| Voidly | https://voidly.ai/api-docs | No | Yes | No | N/A | Tema no ambiental o sin cobertura local |
| Warnely | https://warnely.com/developers | No | Yes | Yes | N/A | Índices de seguridad de viaje por país |
| Wikidata | https://www.wikidata.org/w/api.php?action=help | OAuth | Yes | Unknown | Sí (no ambiental) | Referencia, no monitoreo |
| Wikipedia | https://www.mediawiki.org/wiki/API:Main_page | No | Yes | Unknown | Sí (no ambiental) | Referencia |
| Yelp | https://www.yelp.com/developers/documentation/v3 | OAuth | Yes | Unknown | N/A | Tema no ambiental o sin cobertura local |

## Government (Gobierno) — 115 entradas

| Nombre | URL | Auth | HTTPS | CORS | ¿Cubre Colombia/Nariño? | Nota |
|---|---|---|---|---|---|---|
| AI Law Tracker | https://ai-law-tracker.com/developers | apiKey | Yes | Unknown | N/A | No ambiental |
| Ayes and Noes | https://ayesandnoes.co.uk/developers | No | Yes | Yes | No | Reino Unido |
| Bank Negara Malaysia Open Data | https://apikijangportal.bnm.gov.my/ | No | Yes | Unknown | No | Malasia |
| BCLaws | https://www.bclaws.gov.bc.ca/civix/template/complete/api/index.html | No | No | Unknown | No | Canadá (BC) |
| Bidledger | https://jaydemks.github.io/bidledger/api.html | No | Yes | Yes | No | Unión Europea |
| Brazil | https://brasilapi.com.br/ | No | Yes | Yes | No | Brasil |
| Brazil Central Bank Open Data | https://dadosabertos.bcb.gov.br/ | No | Yes | Unknown | No | Brasil |
| Brazil Receita WS | https://www.receitaws.com.br/ | No | Yes | Unknown | No | Brasil |
| Brazilian Chamber of Deputies Open Data | https://dadosabertos.camara.leg.br/swagger/api.html | No | Yes | No | No | Brasil |
| Bureau of Labor Statistics | https://www.bls.gov/developers/ | No | Yes | Unknown | No | EE. UU. |
| CPFHub | https://cpfhub.io | apiKey | Yes | Yes | No | Brasil |
| Census.gov | https://www.census.gov/data/developers/data-sets.html | No | Yes | Unknown | No | EE. UU. |
| City, Berlin | https://daten.berlin.de/ | No | Yes | Unknown | No | Berlin |
| City, Gdańsk | https://ckan.multimediagdansk.pl/en | No | Yes | Unknown | No | Gdańsk |
| City, Gdynia | http://otwartedane.gdynia.pl/en/api_doc.html | No | No | Unknown | No | Gdynia |
| City, Helsinki | https://hri.fi/en_gb/ | No | Yes | Unknown | No | Helsinki |
| City, Lviv | https://opendata.city-adm.lviv.ua/ | No | Yes | Unknown | No | Lviv |
| City, Nantes Open Data | https://data.nantesmetropole.fr/pages/home/ | apiKey | Yes | Unknown | No | Nantes Open Data |
| City, New York Open Data | https://opendata.cityofnewyork.us/ | No | Yes | Unknown | No | New York Open Data |
| City, Prague Open Data | http://opendata.praha.eu/en | No | No | Unknown | No | Prague Open Data |
| City, Toronto Open Data | https://open.toronto.ca/ | No | Yes | Yes | No | Toronto Open Data |
| Code.gov | https://code.gov | apiKey | Yes | Unknown | No | EE. UU. |
| Colorado Information Marketplace | https://data.colorado.gov/ | No | Yes | Unknown | No | EE. UU. |
| Conversor IAE CNAE | https://www.conversoriaecnae.es/api/v1/docs | apiKey | Yes | No | No | España |
| Data USA | https://datausa.io/about/api/ | No | Yes | Unknown | No | EE. UU. |
| Data.gov | https://api.data.gov/ | apiKey | Yes | Unknown | No | EE. UU. |
| Data.parliament.uk | https://explore.data.parliament.uk/?learnmore=Members | No | No | Unknown | No | Reino Unido |
| Deutscher Bundestag DIP | https://dip.bundestag.de/documents/informationsblatt_zur_dip_api_v01.pdf | apiKey | Yes | Unknown | No | Alemania |
| Disclosed Capitol | https://www.disclosedcapitol.com/data-files/api | apiKey | Yes | No | No | EE. UU. |
| District of Columbia Open Data | http://opendata.dc.gov/pages/using-apis | No | Yes | Unknown | No | EE. UU. |
| DistrictAPI | https://districtapi.dev/docs | apiKey | Yes | Yes | No | EE. UU. |
| eCourtsIndia | https://ecourtsindia.com/api | apiKey | Yes | Yes | No | India |
| Edgrapi | https://edgrapi.com/docs | apiKey | Yes | Yes | No | EE. UU. |
| EditalMD | https://editalmd.com/api/ | No | Yes | Yes | No | Brasil |
| EPA | https://www.epa.gov/developers/data-data-products#apis | No | Yes | Unknown | No | EE. UU. |
| EU VAT Rates by Commodity Code | https://github.com/humora2504/eu-vat-by-commodity-code | No | Yes | Yes | No | Unión Europea |
| FastDOL | https://www.fastdol.com/docs | apiKey | Yes | Yes | No | EE. UU. |
| FBI Wanted | https://www.fbi.gov/wanted/api | No | Yes | Unknown | No | EE. UU. |
| FDA Import Alert Screening | https://aerviklabs.com/apis/fda-import-alerts/ | apiKey | Yes | Unknown | No | EE. UU. |
| FEC | https://api.open.fec.gov/developers/ | apiKey | Yes | Unknown | No | EE. UU. |
| Federal Register | https://www.federalregister.gov/reader-aids/developer-resources/rest-api | No | Yes | Unknown | No | EE. UU. |
| gankdat | https://gankdat.com/docs | apiKey | Yes | Yes | No | Reino Unido/UE |
| Gazette Data, UK | https://www.thegazette.co.uk/data | OAuth | Yes | Unknown | No | Reino Unido |
| Gun Policy | https://www.gunpolicy.org/api | apiKey | Yes | Unknown | N/A | No ambiental |
| Indian Mandi Prices | https://mandi-api.vercel.app/docs | No | Yes | Yes | No | India |
| Indian Pincode | https://indianpincode.com/ | No | Yes | Yes | No | India |
| INEI | http://iinei.inei.gob.pe/microdatos/ | No | No | Unknown | No | Perú |
| Interpol Red Notices | https://interpol.api.bund.dev/ | No | Yes | Unknown | N/A | No ambiental |
| Istanbul (İBB) Open Data | https://data.ibb.gov.tr | No | Yes | Unknown | No | Turquía |
| LocalGov.jp | https://localgov.jp/ | No | Yes | Yes | No | Japón |
| National Park Service, US | https://www.nps.gov/subjects/developer/ | apiKey | Yes | Yes | No | EE. UU. |
| Neotimo DGFiP Mirror | https://neotimo.com/annuaire-dgfip | No | Yes | Unknown | No | Francia |
| Open Government, ACT | https://www.data.act.gov.au/ | No | Yes | Unknown | No | ACT |
| Open Government, Argentina | https://datos.gob.ar/ | No | Yes | Unknown | No | Argentina |
| Open Government, Australia | https://www.data.gov.au/ | No | Yes | Unknown | No | Australia |
| Open Government, Austria | https://www.data.gv.at/ | No | Yes | Unknown | No | Austria |
| Open Government, Belgium | https://data.gov.be/ | No | Yes | Unknown | No | Belgium |
| Open Government, Canada | http://open.canada.ca/en | No | No | Unknown | No | Canada |
| Open Government, Colombia | https://www.dane.gov.co/ | No | No | Unknown | Sí (no API directa) | Enlace a dane.gov.co; el portal con API es datos.gov.co (Socrata) |
| Open Government, Cyprus | https://data.gov.cy/?language=en | No | Yes | Unknown | No | Cyprus |
| Open Government, Czech Republic | https://data.gov.cz/english/ | No | Yes | Unknown | No | Czech Republic |
| Open Government, Denmark | https://www.opendata.dk/ | No | Yes | Unknown | No | Denmark |
| Open Government, Estonia | https://avaandmed.eesti.ee/instructions/opendata-dataset-api | apiKey | Yes | Unknown | No | Estonia |
| Open Government, Finland | https://www.avoindata.fi/en | No | Yes | Unknown | No | Finland |
| Open Government, France | https://www.data.gouv.fr/ | apiKey | Yes | Unknown | No | France |
| Open Government, Germany | https://www.govdata.de/daten/-/details/govdata-metadatenkatalog | No | Yes | Unknown | No | Germany |
| Open Government, Greece | https://data.gov.gr/ | OAuth | Yes | Unknown | No | Greece |
| Open Government, India | https://data.gov.in/ | apiKey | Yes | Unknown | No | India |
| Open Government, Indonesia | https://data.go.id/ | No | Yes | Unknown | No | Indonesia |
| Open Government, Ireland | https://data.gov.ie/pages/developers | No | Yes | Unknown | No | Ireland |
| Open Government, Italy | https://www.dati.gov.it/ | No | Yes | Unknown | No | Italy |
| Open Government, Korea | https://www.data.go.kr/ | apiKey | Yes | Unknown | No | Korea |
| Open Government, Lithuania | https://data.gov.lt/public/api/1 | No | Yes | Unknown | No | Lithuania |
| Open Government, Luxembourg | https://data.public.lu | apiKey | Yes | Unknown | No | Luxembourg |
| Open Government, Mexico | https://www.inegi.org.mx/datos/ | No | Yes | Unknown | No | Mexico |
| Open Government, Mexico | https://datos.gob.mx/ | No | Yes | Unknown | No | Mexico |
| Open Government, Netherlands | https://data.overheid.nl/en/ondersteuning/data-publiceren/api | No | Yes | Unknown | No | Netherlands |
| Open Government, New South Wales | https://api.nsw.gov.au/ | apiKey | Yes | Unknown | No | New South Wales |
| Open Government, New Zealand | https://www.data.govt.nz/ | No | Yes | Unknown | No | New Zealand |
| Open Government, Norway | https://data.norge.no/dataservices | No | Yes | Yes | No | Norway |
| Open Government, Peru | https://www.datosabiertos.gob.pe/ | No | Yes | Unknown | No | Peru |
| Open Government, Poland | https://dane.gov.pl/en | No | Yes | Yes | No | Poland |
| Open Government, Portugal | https://dados.gov.pt/en/docapi/ | No | Yes | Yes | No | Portugal |
| Open Government, Queensland Government | https://www.data.qld.gov.au/ | No | Yes | Unknown | No | Queensland Government |
| Open Government, Romania | http://data.gov.ro/ | No | No | Unknown | No | Romania |
| Open Government, Saudi Arabia | https://data.gov.sa | No | Yes | Unknown | No | Saudi Arabia |
| Open Government, Singapore | https://data.gov.sg/developer | No | Yes | Unknown | No | Singapore |
| Open Government, Slovakia | https://data.gov.sk/en/ | No | Yes | Unknown | No | Slovakia |
| Open Government, Slovenia | https://podatki.gov.si/ | No | Yes | No | No | Slovenia |
| Open Government, South Australian Government | https://data.sa.gov.au/ | No | Yes | Unknown | No | South Australian Government |
| Open Government, Spain | https://datos.gob.es/en | No | Yes | Unknown | No | Spain |
| Open Government, Sweden | https://www.dataportal.se/en/dataservice/91_29789/api-for-the-statistical-database | No | Yes | Unknown | No | Sweden |
| Open Government, Switzerland | https://handbook.opendata.swiss/de/content/nutzen/api-nutzen.html | No | Yes | Unknown | No | Switzerland |
| Open Government, Taiwan | https://data.gov.tw/ | No | Yes | Unknown | No | Taiwan |
| Open Government, Thailand | https://data.go.th/ | apiKey | Yes | Unknown | No | Thailand |
| Open Government, UK | https://data.gov.uk/ | No | Yes | Unknown | No | UK |
| Open Government, USA | https://www.data.gov/ | No | Yes | Unknown | No | USA |
| Open Government, Victoria State Government | https://www.data.vic.gov.au/ | No | Yes | Unknown | No | Victoria State Government |
| Open Government, West Australia | https://data.wa.gov.au/ | No | Yes | Unknown | No | West Australia |
| OpenMercantil | https://openmercantil.es/api/documentacion | No | Yes | Yes | No | España |
| OpenRegistry | https://openregistry.sophymarine.com | OAuth | Yes | Unknown | N/A | Registros mercantiles |
| PRC Exam Schedule | https://api.whenisthenextboardexam.com/docs/ | No | Yes | Yes | No | Filipinas |
| Radar CNPJ | https://radar-cnpj.com/api/ | No | Yes | No | No | Brasil |
| Represent by Open North | https://represent.opennorth.ca/ | No | Yes | Unknown | No | Canadá |
| Right to Disconnect | https://righttodisconnect.jdries.nl/api/ | No | Yes | Yes | No | Unión Europea |
| Spatial India | https://api.spatialindia.com | No | Yes | Yes | No | India |
| Tollmint | https://api.tollmint.com | No | Yes | Yes | No | EE. UU. |
| UK Companies House | https://developer.company-information.service.gov.uk/ | OAuth | Yes | Unknown | No | Reino Unido |
| UK Legislation Changes | https://uk-legal-changes.pages.dev/docs | No | Yes | Yes | No | Reino Unido |
| US Presidential Election Data by TogaTech | https://uselection.togatech.org/api/ | No | Yes | No | No | EE. UU. |
| USA.gov | https://www.usa.gov/developer | apiKey | Yes | Unknown | No | EE. UU. |
| US Federal Contracts & Grants | https://government-data-api.onrender.com/docs | No | Yes | Yes | No | EE. UU. |
| USAspending.gov | https://api.usaspending.gov/ | No | Yes | Unknown | No | EE. UU. |
| Vett | https://wimberly.solutions/api/free-sanctions-check/ | No | Yes | Yes | N/A | Listas de sanciones |
| VotePredictor | https://votepredictor.com/developers | No | Yes | Yes | No | EE. UU. |

## Animals (Animales) — 24 entradas

| Nombre | URL | Auth | HTTPS | CORS | ¿Cubre Colombia/Nariño? | Nota |
|---|---|---|---|---|---|---|
| Axolotl | https://theaxolotlapi.netlify.app/ | No | Yes | No | N/A | Entretenimiento/mascotas, sin datos de campo |
| Breed Health Score | https://breedhealthscore.com/developers/ | No | Yes | No | N/A | Entretenimiento/mascotas, sin datos de campo |
| Cat Facts | https://alexwohlbruck.github.io/cat-facts/ | No | Yes | No | N/A | Entretenimiento/mascotas, sin datos de campo |
| Cat Facts | https://catfact.ninja/ | No | Yes | Yes | N/A | Entretenimiento/mascotas, sin datos de campo |
| Cats | https://docs.thecatapi.com/ | apiKey | Yes | No | N/A | Entretenimiento/mascotas, sin datos de campo |
| Dog Facts | https://dukengn.github.io/Dog-facts-API/ | No | Yes | Yes | N/A | Entretenimiento/mascotas, sin datos de campo |
| Dog Facts | https://kinduff.github.io/dog-api/ | No | Yes | Yes | N/A | Entretenimiento/mascotas, sin datos de campo |
| Dogs | https://dog.ceo/dog-api/ | No | Yes | Yes | N/A | Entretenimiento/mascotas, sin datos de campo |
| eBird | https://documenter.getpostman.com/view/664302/S1ENwy59 | apiKey | Yes | No | P: Sí (llave) | Probado: 403 sin token; región CO-NAR |
| FishWatch | https://www.fishwatch.gov/developers | No | Yes | Yes | No | Pesquerías EE. UU. |
| HTTP Cat | https://http.cat/ | No | Yes | Yes | N/A | Entretenimiento/mascotas, sin datos de campo |
| HTTP Dog | https://http.dog/ | No | Yes | Yes | N/A | Entretenimiento/mascotas, sin datos de campo |
| IUCN | https://www.iucnredlist.org/en | apiKey | Yes | No | P: Sí (token) | Probado: 403 sin token; categorías vía GBIF |
| MeowFacts | https://github.com/wh-iterabb-it/meowfacts | No | Yes | No | N/A | Entretenimiento/mascotas, sin datos de campo |
| Movebank | https://github.com/movebank/movebank-api-doc | No | Yes | Yes | Parcial | Telemetría animal; la mayoría de estudios requiere login; no probado |
| PlaceBear | https://placebear.com/ | No | Yes | Yes | N/A | Entretenimiento/mascotas, sin datos de campo |
| PlaceDog | https://place.dog | No | Yes | Yes | N/A | Entretenimiento/mascotas, sin datos de campo |
| RandomDog | https://random.dog/woof.json | No | Yes | Yes | N/A | Entretenimiento/mascotas, sin datos de campo |
| RandomDuck | https://random-d.uk/api | No | Yes | No | N/A | Entretenimiento/mascotas, sin datos de campo |
| RandomFox | https://randomfox.ca/floof/ | No | Yes | No | N/A | Entretenimiento/mascotas, sin datos de campo |
| RescueGroups | https://userguide.rescuegroups.org/display/APIDG/API+Developers+Guide+Home | No | Yes | Unknown | N/A | Entretenimiento/mascotas, sin datos de campo |
| Shibe.Online | http://shibe.online/ | No | Yes | Yes | N/A | Entretenimiento/mascotas, sin datos de campo |
| The Dog | https://thedogapi.com/ | apiKey | Yes | No | N/A | Entretenimiento/mascotas, sin datos de campo |
| xeno-canto | https://xeno-canto.org/explore/api | No | Yes | Unknown | P: Sí (llave) | Probado: API v3 requiere key |

## Geocoding (solo relevantes) — 17 entradas (de 103 en la sección)

| Nombre | URL | Auth | HTTPS | CORS | ¿Cubre Colombia/Nariño? | Nota |
|---|---|---|---|---|---|---|
| Actinia Grass GIS | https://actinia.mundialis.de/api_docs/ | apiKey | Yes | Unknown | Sí (llave) | Procesamiento GIS |
| administrative-divisons-db | https://github.com/kamikazechaser/administrative-divisions-db | No | Yes | Yes | Sí | Divisiones administrativas estáticas |
| Geoapify | https://www.geoapify.com/api/geocoding-api/ | apiKey | Yes | Yes | Sí (llave) | Geocodificación |
| Geocode.xyz | https://geocode.xyz/api | No | Yes | Unknown | Sí (global) | No probado |
| GeographQL | https://geographql.netlify.app | No | Yes | Yes | Sí | Países/estados/ciudades |
| GeoNames | http://www.geonames.org/export/web-services.html | No | No | Unknown | Sí (global) | Topónimos; HTTP; no probado |
| Google Earth Engine | https://developers.google.com/earth-engine/ | apiKey | Yes | Unknown | Sí (cuenta) | Plataforma de análisis; requiere cuenta |
| LocationIQ | https://locationiq.org/docs/ | apiKey | Yes | Yes | Sí (llave) | Geocodificación |
| Mapbox | https://docs.mapbox.com/ | apiKey | Yes | Unknown | Sí (llave) | Mapas base |
| Nominatim | https://nominatim.org/release-docs/latest/api/Overview/ | No | Yes | Yes | Sí (global) | Geocodificación OSM; política de uso estricta; no probado |
| OnWater | https://onwater.io/ | No | Yes | Unknown | Sí (global) | Tierra/agua; no probado |
| Open Topo Data | https://www.opentopodata.org | No | Yes | No | Sí (global) | Elevación; redundante con Open-Meteo Elevation |
| OpenCage | https://opencagedata.com | apiKey | Yes | Yes | Sí (llave) | Geocodificación |
| openrouteservice.org | https://openrouteservice.org/ | apiKey | Yes | Unknown | Sí (llave) | Rutas/elevación |
| OpenStreetMap | http://wiki.openstreetmap.org/wiki/API | OAuth | No | Unknown | Sí (global) | API de edición OSM; no para consultas masivas |
| Queimadas INPE | https://queimadas.dgi.inpe.br/queimadas/dados-abertos/ | No | Yes | Unknown | P: Sí (Sudamérica) | Probado: ADOPT (focos de calor) |
| REST Countries | https://restcountries.com | No | Yes | Yes | Sí (no ambiental) | Datos de países |
