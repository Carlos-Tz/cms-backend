# Mapa de funcionalidades

Estado real del backend, verificado contra el código, las rutas y los tests. **Actualiza este archivo
cuando cierres un hueco** — si no, en dos fases vuelve a ser inútil, que es exactamente lo que le pasó
a la sección «Current phase» de `CLAUDE.md`.

Verificado con `php artisan route:list --path=api --except-vendor` (48 rutas) y
`php artisan test --compact` (92 tests, 355 aserciones, todos verdes).

Leyenda: ✅ hecho y con tests · 🟡 parcial · 🔴 la ruta existe pero es stub · ⬜ no existe nada

## Estado por capacidad

| # | Capacidad | Estado | Dónde vive | Tests que la cubren |
|---|---|---|---|---|
| 1 | Login admin JWT (`login`/`logout`/`me`) | ✅ | `Admin\AuthController` | `AuthTest` — los 6 |
| 2 | Servir una página con sus secciones en orden | ✅ | `Public\PageController@show` | `HomeContentTest` — «returns the home page with its sections in blueprint order», «404s on an unknown site or page, in Spanish», «hides an unpublished page from the public API» |
| 3 | Servir las secciones globales del sitio | ✅ | `Public\PageController@globals` | `HomeContentTest` — «returns the site-wide sections keyed by type» |
| 4 | Editar el contenido de una sección | ✅ **12 de 12 tipos** | `Admin\SectionController` + `UpdateSectionRequest` | `HomeContentTest` — 8 tests, incluidos «replaces the testimonials through the admin API», «rejects a testimonial without an author» y «stores a video url on the home page rich text» · `AboutContentTest` — «replaces the feature grid items through the admin API», «rejects a feature grid item without a title», «updates a rich text section with a video», «rejects a rich text section with an invalid video url and no body» |
| 5 | Simulador de ahorro con cálculo en backend | ✅ | `Public\SavingsSimulatorController` | `HomeContentTest` — «computes the savings against diesel», «reflects an updated gas price in the simulation», «rejects an unknown fuel and a non positive quantity» |
| 6 | Blog público (listado + artículo) | ✅ | `Public\BlogController` | `BlogTest` — 8 tests |
| 7 | Blog admin (CRUD completo) | ✅ | `Admin\BlogPostController` + `BlogPostRequest` | `BlogTest` — 6 tests |
| 8 | Captación de leads con límite de tasa | ✅ | `Public\LeadController` + `StoreLeadRequest` | `LeadTest` — 6 tests |
| 9 | Bandeja de leads admin (ver / borrar) | ✅ | `Admin\LeadController` | `LeadTest` — 4 tests |
| 10 | Subir una imagen | ✅ | `Admin\MediaController@store` | `HomeContentTest` — «uploads an image and returns its url» |
| 11 | **Publicar / despublicar una página** | ✅ | `Admin\PageController` + `UpdatePageRequest` | `PageAndMenuTest` — «unpublishes and republishes a page through the admin API», «ignores blueprint fields sent alongside the publish flag», «does not expose routes to create or delete pages», «blocks page writes without a token» |
| 12 | Menú de navegación público | ✅ | `Public\MenuController@show` | `PageAndMenuTest` — «derives the public menu from the page map», «drops an unpublished page and its whole branch from the menu» |
| 12b | Listar páginas en el panel | ✅ | `Admin\PageController@index` / `@show` | `PageAndMenuTest` — «lists the pages of a site for the admin, including unpublished ones», «returns a page with its sections for the admin» |
| 13 | Estaciones (mapa) | ✅ | `Admin\StationController` (CRUD) + `StationRequest` · `Public\StationController@index` | `StationTest` — 9 tests |
| 14 | Biblioteca de media (listar / borrar) | ✅ **sin tabla** | `Admin\MediaController@index` / `@destroy`, sobre el disco `public` | `HomeContentTest` — «lists the uploaded images and deletes one», «404s when deleting an image that does not exist», «cannot reach the media delete with a path outside the media folder», «does not expose routes to show or update a media file», «blocks the media api without a token» |
| 15 | Notificación de lead nuevo al destinatario | ⬜ | — | ninguno |
| 16 | Blueprint `gnu` | ✅ **17 de 17 sembradas** | `GnuBlueprintSeeder` | los de arriba corren sobre él, + `ServicesAndSustainabilityTest` y `GncAndContactTest` |
| 17 | Blueprint `gnu-vehicular` | 🟡 portada + contacto + blog (3 de 5 columnas) | `GnuVehicularBlueprintSeeder` | `BlogTest` — 2 tests, `VehicularHomeTest` — 7, `VehicularContactTest` — 7 |
| 18 | Blueprint `traninn-logistic` | ⬜ | — | ninguno |
| 19 | Blueprint `tectuus` | ⬜ | — | ninguno |

El estado por tipo de sección (fila 4) no se repite aquí: vive en
`sitios-web-gnu/references/implementation-status.md`, al lado de la definición de cada tipo.

**`gnu` está completo**: las 17 páginas del blueprint, sus 47 secciones, las 4 globales, 4 artículos
y 2 estaciones de ejemplo. Verificado nodo por nodo contra la página 1 de `docs/mapa-sitios-pdf.pdf`,
columnas y «Requerimientos principales» incluidos. El trabajo pendiente ya no es de `gnu` sino de los
otros tres sitios.

## Huecos, con su pregunta abierta

Ninguno de estos está diseñado todavía. La pregunta abierta es la parte importante: contestarla es el
primer paso, no escribir la migración.

### 1. Notificación de lead nuevo

El spec dice que el destinatario «no vive aquí: es configuración del sitio, no contenido de la
sección».

*Abierto:* dónde vive esa configuración. Hay tres caminos y el spec no elige: una columna en `sites`,
un `config/` por sitio, o una variable de entorno única para todos. Tampoco está confirmado que la
notificación se quiera — el admin ya tiene la bandeja de leads. **Descartada explícitamente del
alcance de la pasada de `gnu`**: no aparece en los requisitos del PDF y la bandeja cubre la
necesidad.

### 2. Blueprints de los otros tres sitios

`gnu` está migrado al formato nuevo; `gnu-vehicular` lo está a medias — portada, contacto y blog
migrados y sembrados, sus otras dos columnas siguen en árbol crudo. `traninn-logistic` y `tectuus` siguen
enteros en árbol crudo.

*Abierto* al migrarlos:
- `gnu-vehicular` es el único árbol con **3 niveles** de anidamiento («Instala tu equipo de GNV» y sus
  hijos). `pages.parent_id` lo soporta; el mapa de `gnu` solo demuestra 2 niveles.
- Los 4 candidatos sin verificar que quedan (`product_catalog`, `equipment_quote`, `financing`,
  `service_scheduling`) hay que auditarlos: el spec advierte que huelen a página disfrazada de
  bloque, como pasó con `certifications` y `esr_ecovadis`. `billing_info` ya se descartó: es el
  `rich_text` de `/contacto` en `gnu-vehicular`.
- **Un lead no registra de qué formulario vino.** `gnu-vehicular` ya lleva dos `lead_capture_form`
  (`/` y `/contacto`) escribiendo en la misma tabla, y la bandeja admin no los distingue. Se
  resuelve con una columna `leads.source` el día que marketing pregunte qué formulario convierte;
  hasta entonces no se agrega.

## Candidatos a borrar

Scaffolding que contradice el spec o cubre algo que nadie pidió. No los borres en la misma pasada en
que implementas algo — es una decisión aparte.

| Qué | Por qué sobra |
|---|---|
| `apiResource('menus')` + `Admin\MenuController` (5 rutas) | El menú es **derivado** del mapa de páginas, y `GET /api/public/{site}/menu` ya lo deriva. Un CRUD de menús contradice el spec y crea una segunda verdad sobre el orden de la navegación |
| `Admin\SiteController` `store`/`update`/`destroy` | 4 sitios fijos que siembra el blueprint. Un `index` read-only para poblar el selector de sitio del panel es todo lo que se necesita |
| `Admin\UserController` (5 rutas) | Un solo rol global y un solo usuario que siembra `AdminUserSeeder`. No se pidió gestión de usuarios |
| `prompt.md` | Está 100% satisfecho por lo implementado (banners, simulador, cards de clientes, datos de contacto). Sirve como log histórico; no es un spec |

Borrar los tres primeros son **13 rutas menos** y 2 controladores menos, sin perder ninguna
capacidad. `POST` y `DELETE` de `pages` ya se borraron al implementar `Admin\PageController`.

## Drift entre spec, docs y código

Verificado en esta pasada. Cada uno es un commit chico e independiente.

| Dónde | Qué dice | Qué es verdad |
|---|---|---|
| `CLAUDE.md`, «Current phase» | «scaffolding only», «todos los demás controladores son placeholders» | Falso: secciones, blog, leads, simulador, estaciones y media son reales, con 92 tests |
| `CLAUDE.md`, forma de respuesta | `{data, meta}` | Es `{status, data, meta}`. Los stubs sí devuelven dos claves, y así se distinguen |
| `CLAUDE.md`, `config/blueprints/{site}.php` | «la forma legible por máquina eventual» | **Descartado.** Los seeders ya son el blueprint y funcionan. Una capa de config intermedia con un solo consumidor no se paga. Se reconsidera si aparece un segundo consumidor |
| `sitios-web-gnu/references/gnu-vehicular.md` | árbol crudo, sin migrar | Migrado a medias: portada y blog en formato blueprint, las otras tres columnas siguen como árbol crudo en el apéndice |
| `CLAUDE.md`, «Current phase» | «no hay migraciones ni modelos de dominio» | Falso: `sites`, `pages`, `sections`, `blog_posts`, `leads`, `stations` existen con sus modelos. La única tabla que no existe es `media`, y es a propósito |

## Fuera de alcance

No son huecos y no hace falta volver a preguntarlos: nada de esto aparece en el spec del cliente ni
en los requisitos de los 4 sitios.

- **Metadatos SEO** — sin meta title/description, Open Graph, canonical, sitemap ni robots
- **i18n** — contenido en un solo idioma (español)
- **Roles y permisos por sitio** — un rol global con acceso total
- **Versionado de contenido, borradores de secciones, previsualización** — la sección se edita y queda
  editada. El blog sí tiene borradores, vía `published_at`
- **Page builder** — el admin no crea páginas ni mueve secciones. Es el diseño, no una limitación
  temporal
- **Front-end** — este repo es API-only
