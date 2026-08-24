# GNU Gas Natural Vehicular — `gnu-vehicular`

Blueprint de contenido predefinido. **Migrado completo**: las 5 columnas y sus 14 páginas están en
el mapa de abajo y sembradas por `GnuVehicularBlueprintSeeder`.

Los campos de cada tipo de sección están en [`section-types.md`](section-types.md); los valores
viven en `GnuVehicularBlueprintSeeder` y en la BD.

## Requisitos principales
- Carrusel de banners.
- Simulador de ahorro.
- Soporte para videos.
- Botón de WhatsApp — contacto directo de marketing.

## Mapa de páginas

Las columnas de navegación son 5: Inicio (1), Nuestros servicios (2), Ubicaciones (3), Contacto (4),
Blog (5) — una menos que el sitio institucional.

| # | ruta | título | padre | pos | secciones (en orden) |
|---|---|---|---|---|---|
| 1 | `/` | Inicio | — | 1 | `banner_carousel`, `savings_simulator`, `feature_grid`, `testimonials`, `stations_map`, `lead_capture_form` |
| 2 | `/nuestros-servicios` | Nuestros servicios | — | 2 | `page_hero`, `rich_text` |
| 3 | `/nuestros-servicios/instala-tu-equipo-de-gnv` | Instala tu equipo de GNV | `/nuestros-servicios` | 1 | `page_hero`, `rich_text`, `feature_grid` |
| 4 | `…/instala-tu-equipo-de-gnv/cotiza-tu-equipo` | Cotiza tu equipo | `…/instala-tu-equipo-de-gnv` | 1 | `page_hero`, `rich_text`, `lead_capture_form` |
| 5 | `…/instala-tu-equipo-de-gnv/financiamiento` | Financiamiento | `…/instala-tu-equipo-de-gnv` | 2 | `page_hero`, `rich_text`, `feature_grid` |
| 6 | `/nuestros-servicios/flotillas-empresariales` | Flotillas empresariales | `/nuestros-servicios` | 2 | `page_hero`, `rich_text`, `feature_grid`, `lead_capture_form` |
| 7 | `/nuestros-servicios/estaciones-moviles` | Estaciones móviles | `/nuestros-servicios` | 3 | `page_hero`, `rich_text`, `feature_grid` |
| 8 | `/ubicaciones` | Ubicaciones | — | 3 | `page_hero`, `stations_map` |
| 9 | `/ubicaciones/estaciones-morelia` | Estaciones Morelia | `/ubicaciones` | 1 | `page_hero`, `rich_text` |
| 10 | `/ubicaciones/estacion-uruapan` | Estación Uruapan | `/ubicaciones` | 2 | `page_hero`, `rich_text` |
| 11 | `/ubicaciones/agenda-tu-verificacion` | Agenda tu verificación | `/ubicaciones` | 3 | `page_hero`, `service_scheduling` |
| 12 | `/ubicaciones/talleres` | Talleres | `/ubicaciones` | 4 | `page_hero`, `rich_text` |
| 13 | `/contacto` | Contacto | — | 4 | `page_hero`, `lead_capture_form`, `rich_text`, `stations_map` |
| 14 | `/blog` | Blog | — | 5 | `page_hero` |

**Sembradas: 5 de 5 columnas, 14 páginas. El sitio está completo.** El estado por tipo vive en
[`implementation-status.md`](implementation-status.md).

Notas de lectura:

- **`/` no lleva `page_hero`**: el `banner_carousel` es el hero de la portada, igual que en `gnu`.
- **«Programa de recompensas» es el `feature_grid` de la portada.** Es hijo de *Inicio*, así que por
  la regla de clasificación de [`gnu.md`](gnu.md) es sección, no página; y es una rejilla de
  tarjetas (acumulas, canjeas, beneficios de flotilla), que es justo lo que ese tipo genérico cubre.
  No se promovió a tipo propio: el cliente no declaró campos que `feature_grid` no tenga.
- **`testimonials` es sección de la portada aquí, y global en `gnu`.** El árbol de cada sitio se lee
  tal como lo entregó el cliente en vez de armonizarlos en silencio. Ver `gnu.md`, «Secciones
  globales».
- **«Facturación» es el `rich_text` de `/contacto`.** Instrucciones de facturación y liga al portal
  son contenido libre; el candidato `billing_info` no se promovió a tipo. Mismo criterio que con el
  programa de recompensas: un candidato sin auditar se queda fuera del catálogo mientras un tipo
  genérico lo cubra.
- **El sitio lleva dos `lead_capture_form` y dos `stations_map`**, uno de cada uno en `/` y en
  `/contacto`. Ninguna página repite tipo dentro de sí misma, así que sigue sin hacer falta `key`.
  Consecuencia conocida: la bandeja admin no distingue de qué formulario vino cada lead.
- **«Nuestros servicios» es el único árbol de 3 niveles del proyecto.** *Cotiza tu equipo* y
  *Financiamiento* son páginas nietas, no secciones de *Instala tu equipo de GNV*. `pages.parent_id`
  siempre lo soportó; esta columna es la primera que lo demuestra, y `MenuController::tree()` ya era
  recursivo, así que no hizo falta código nuevo.
- **«Cotiza tu equipo» es un `lead_capture_form`, no un cotizador.** No hay cálculo de precio: el
  visitante deja sus datos y un asesor cotiza. Mismo patrón que «Cotiza tu suministro» en `gnu`.
  Descarta el candidato `equipment_quote`.
- **«Financiamiento» es un `feature_grid`**: los planes son tarjetas estructuradas. Descarta el
  candidato `financing`.
- **Solo *Flotillas empresariales* capta leads** dentro de esa columna, además de *Cotiza tu
  equipo*: es la página con intención comercial B2B; *Estaciones móviles* es informativa.
- **La columna no lleva `clients_showcase`**, aunque la equivalente de `gnu` sí: ese tipo está
  declarado para `gnu`, `traninn-logistic` y `tectuus`, no para este sitio.
- **Los hijos de *Ubicaciones* son páginas, no secciones.** La regla de clasificación solo hace
  secciones a los hijos de *Inicio* y *Contacto*. Es el primer nivel de anidamiento de este sitio.
- **Morelia, Uruapan y Talleres son `rich_text` abierto**: el cliente no definió una forma para
  ellos y el contenido libre no necesita una. Ojo: las páginas de Morelia y Uruapan son *contenido*,
  distintas de las filas de `stations` que dibujan los pines del mapa.
- **«Agenda tu verificación» lleva `service_scheduling`**, el único tipo del catálogo exclusivo de
  este sitio. Los horarios no son contenido: los sirve `GET {site}/appointments/availability`.
- **El `savings_simulator` de esta portada guarda sus propios precios**, distintos a los de `gnu`.
  El endpoint `GET /api/public/gnu-vehicular/savings-simulator` los usa sin parámetro `page`, porque
  es la única instancia del sitio.
- **Los pines del mapa no son campos de `stations_map`**: salen de la tabla `stations`. El seeder
  siembra dos placeholders, Morelia y Uruapan, que son las estaciones que nombra *Ubicaciones*.
- **`/blog` solo lleva `page_hero`**: el listado lo sirve `GET /api/public/gnu-vehicular/blog`, y
  los 4 artículos son filas de `blog_posts`.

## Secciones globales

Filas de `sections` con `page_id = null`. Son los tres requisitos transversales declarados para los
4 sitios. `testimonials` **no** está aquí — en este sitio vive en la portada.

| tipo | ámbito | notas |
|---|---|---|
| `contact_info` | todo el sitio | No está en el árbol original; datos de contacto de la empresa. |
| `social_icons` | todo el sitio | En el árbol original está fuera del flujo principal. |
| `whatsapp_button` | todo el sitio | Solo en «Requisitos principales», no en el árbol. |

## Candidatos de tipo, resueltos

Los cuatro candidatos «sin verificar» que el spec listaba para este sitio ya están auditados. Tres
se descartaron porque un tipo genérico los cubre, y solo uno llegó al catálogo:

| candidato | resultado |
|---|---|
| `billing_info` | descartado — es el `rich_text` de `/contacto` |
| `equipment_quote` | descartado — es el `lead_capture_form` de `cotiza-tu-equipo` |
| `financing` | descartado — es el `feature_grid` de `financiamiento` |
| `service_scheduling` | **tipo real** — único del catálogo exclusivo de un sitio |

Tres de cuatro resultaron bloques genéricos disfrazados de tipo, la misma proporción que en `gnu`
con `certifications` y `esr_ecovadis`. La advertencia del spec era correcta.

**Pendiente conocido:** el sitio tiene 4 `lead_capture_form` (`/`, `/contacto`, `cotiza-tu-equipo` y
`flotillas-empresariales`) escribiendo en la misma bandeja, y sigue sin registrarse de cuál vino
cada lead. La decisión sobre `leads.source` sigue siendo YAGNI, pero vale más que cuando eran dos.

## Apéndice — árbol de navegación original

Tal como fue entregado en el macro archivo de sitios web (GNU Gas Natural 2025). **No editar**: es
el único vínculo de trazabilidad con el documento del cliente.

**Íconos de redes sociales** (columna global, fuera del flujo principal)

**Inicio**
- Carrusel de banners
- Simulador de ahorro
- Programa de recompensas
- Testimoniales
- Mapa de estaciones
- Contacto

**Nuestros servicios**
- Instala tu equipo de GNV
  - Cotiza tu equipo
  - Financiamiento
- Flotillas empresariales
- Estaciones móviles

**Ubicaciones**
- Estaciones Morelia
- Estación Uruapan
- Agenda tu verificación
- Talleres

**Contacto**
- Captación de leads
- Facturación
- Mapa de estaciones

**Blog**
- Verificación anual
- Transporte sostenible
- Seguridad del GNV
- GN vs otros combustibles
