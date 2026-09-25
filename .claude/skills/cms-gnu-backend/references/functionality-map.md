# Mapa de funcionalidades

Estado real del backend, verificado contra el código, las rutas y los tests. **Actualiza este archivo
cuando cierres un hueco** — si no, en dos fases vuelve a ser inútil, que es exactamente lo que le pasó
a la sección «Current phase» de `CLAUDE.md`.

Verificado con `php artisan route:list --path=api --except-vendor` (66 rutas) y
`php artisan test --compact` (209 tests, 888 aserciones, todos verdes).

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
| 13 | Estaciones (mapa) | ✅ | `Admin\StationController` (CRUD) + `StationRequest` · `Public\StationController@index` | `StationTest` — 10 tests |
| 14 | Biblioteca de media (listar / borrar) | ✅ **sin tabla** | `Admin\MediaController@index` / `@destroy`, sobre el disco `public` | `HomeContentTest` — «lists the uploaded images and deletes one», «404s when deleting an image that does not exist», «cannot reach the media delete with a path outside the media folder», «does not expose routes to show or update a media file», «blocks the media api without a token» |
| 15 | Notificación de lead nuevo al destinatario | ⬜ | — | ninguno |
| 16 | Blueprint `gnu` | ✅ **17 de 17 sembradas** | `GnuBlueprintSeeder` | los de arriba corren sobre él, + `ServicesAndSustainabilityTest` y `GncAndContactTest` |
| 17 | Blueprint `gnu-vehicular` | ✅ **5 de 5 columnas, 14 páginas** | `GnuVehicularBlueprintSeeder` | `BlogTest` — 2 tests, `VehicularHomeTest` — 7, `VehicularContactTest` — 7, `VehicularLocationsTest` — 9, `VehicularServicesTest` — 12 |
| 18 | Agenda de verificación | ✅ real | `appointments`, `Public\AppointmentController`, `Admin\AppointmentController`, `StoreAppointmentRequest` | `AppointmentTest` — 15 tests |
| 19 | Blueprint `traninn-logistic` | ✅ **5 de 5 columnas, 11 páginas** | `TraninnLogisticBlueprintSeeder` (hereda de `BlueprintSeeder`) | `TraninnHomeTest` — 5 tests · `TraninnAboutTest` — 6 tests · `TraninnServicesTest` — 6 tests · `TraninnContactTest` — 6 tests |
| 20 | Blueprint `tectuus` | ✅ **4 de 4 columnas, 4 páginas** | `TectuusBlueprintSeeder` (hereda de `BlueprintSeeder`) | `TectuusHomeTest` — 6 tests · `TectuusAboutTest` — 4 · `TectuusProductsTest` — 16 · `TectuusContactTest` — 5 |
| 21 | Catálogo de productos público (categorías, listado, filtro, búsqueda, detalle) | ✅ | `Public\ProductController` | `TectuusProductsTest` — 8 tests |
| 22 | Catálogo de productos admin (CRUD de categorías y productos) | ✅ | `Admin\ProductCategoryController`, `Admin\ProductController` + `ProductCategoryRequest`, `ProductRequest` | `TectuusProductsTest` — 8 tests |

El estado por tipo de sección (fila 4) no se repite aquí: vive en
`sitios-web-gnu/references/implementation-status.md`, al lado de la definición de cada tipo.

**`gnu` está completo**: las 17 páginas del blueprint, sus 47 secciones, las 4 globales, 4 artículos
y 2 estaciones de ejemplo. Verificado nodo por nodo contra la página 1 de `docs/mapa-sitios-pdf.pdf`,
columnas y «Requerimientos principales» incluidos. **Los cuatro sitios están completos**: ya no queda
blueprint pendiente, solo los huecos de abajo.

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

### 2. Blueprints de los otros tres sitios — **cerrado**

**Los 4 sitios están migrados y sembrados completos**: `gnu` (17 páginas), `gnu-vehicular` (14),
`traninn-logistic` (11) y `tectuus` (4, más las 3 categorías del catálogo). Lo que sigue es el
registro de lo que se aprendió migrándolos, no trabajo pendiente:
- Los **3 niveles** de anidamiento ya están demostrados por la columna de servicios de
  `gnu-vehicular`: `pages.parent_id` y `MenuController::tree()`, que ya era recursivo, los
  soportaron sin cambios.
- La portada de `traninn-logistic` no necesitó código nuevo: sus 5 tipos ya tenían reglas en
  `SCHEMAS` y los controladores son genéricos por `site_id`. Migrar una columna es sembrar datos.
- *Sobre nosotros* fue la excepción a lo anterior, y en la dosis esperada: **un tipo nuevo**
  (`image_gallery`) y **tres campos nullable** en `clients_showcase`. Ninguno necesitó migración —
  `sections.type` es un `string` libre y `content` es JSON—, así que el costo fue una entrada en
  `SCHEMAS` y su renglón en el catálogo.
- *Ubicaciones* de Traninn fue el primer nodo que el cliente entregó **vacío**: una cabeza de
  columna sin nada declarado adentro. Se resolvió con `stations_map` + filas de `stations`, o sea
  reusando lo de los otros dos sitios; la forma la decidió el blueprint y quedó anotado que así fue.
- **No queda ningún candidato de tipo sin auditar.** De los 5 del macro archivo, `billing_info`,
  `equipment_quote` y `financing` resultaron bloques genéricos, solo `service_scheduling` llegó al
  catálogo, y `product_catalog` resultó **dominio de datos**, no un tipo: el cliente pidió gestionar
  categorías y productos de forma dinámica, así que son tablas (`product_categories`, `products`) y
  `/productos` es una página con solo `page_hero`, calcada de `/blog`. La advertencia del spec se
  sostuvo entera: 4 de 5 eran páginas, bloques o datos disfrazados de tipo.
- *Productos* de Tectuus fue la primera columna desde `appointments` que **sí necesitó tablas
  nuevas**. El costo real: 2 migraciones, 2 modelos, 2 factories, 2 form requests, 3 controladores y
  13 rutas. Ninguna sección nueva y ninguna entrada en `SCHEMAS` — la estructura de la página no
  cambió.
- La renuncia que trae: **las categorías no salen en `GET {site}/menu`**, porque el menú se deriva
  de `pages`. Es la misma que ya se aceptó con los artículos del blog, y es la contrapartida de que
  el admin pueda crear categorías sin tocar estructura. El front las pide a `product-categories`.
- ~~Un lead no registra de qué formulario vino.~~ **Resuelto** con `leads.source`: el front manda el
  slug de la página del formulario, validado contra las páginas publicadas del sitio, y la bandeja
  filtra con `?source=`. Se difirió mientras eran dos formularios genéricos; se implementó al
  llegar a cuatro, dos de ellos comerciales («Cotiza tu equipo» y flotillas), donde no poder
  separar una cotización de una duda dejaba la bandeja inservible.

## Candidatos a borrar

Scaffolding que contradice el spec o cubre algo que nadie pidió. No los borres en la misma pasada en
que implementas algo — es una decisión aparte.

| Qué | Por qué sobra |
|---|---|
| `apiResource('menus')` + `Admin\MenuController` (5 rutas) | El menú es **derivado** del mapa de páginas, y `GET /api/public/{site}/menu` ya lo deriva. Un CRUD de menús contradice el spec y crea una segunda verdad sobre el orden de la navegación |
| `Admin\SiteController` `store`/`update`/`destroy` | 4 sitios fijos que siembra el blueprint. Un `index` read-only para poblar el selector de sitio del panel es todo lo que se necesita |
| `Admin\UserController` (5 rutas) | Un solo rol global y un solo usuario que siembra `AdminUserSeeder`. No se pidió gestión de usuarios |
| `prompt.md` | Sus 3 funcionalidades (catálogo de productos, captación de leads y «Quiénes somos» de Tectuus) ya están implementadas, igual que las de la pasada anterior. Sirve como log histórico; no es un spec |

Borrar los tres primeros son **13 rutas menos** y 2 controladores menos, sin perder ninguna
capacidad. `POST` y `DELETE` de `pages` ya se borraron al implementar `Admin\PageController`.

## Drift entre spec, docs y código

Verificado en esta pasada. Cada uno es un commit chico e independiente.

| Dónde | Qué dice | Qué es verdad |
|---|---|---|
| `CLAUDE.md`, «Current phase» | «scaffolding only», «todos los demás controladores son placeholders» | Falso: secciones, blog, leads, simulador, estaciones y media son reales, con 92 tests |
| `CLAUDE.md`, forma de respuesta | `{data, meta}` | Es `{status, data, meta}`. Los stubs sí devuelven dos claves, y así se distinguen |
| `CLAUDE.md`, `config/blueprints/{site}.php` | «la forma legible por máquina eventual» | **Descartado.** Los seeders ya son el blueprint y funcionan. Una capa de config intermedia con un solo consumidor no se paga. Se reconsidera si aparece un segundo consumidor |
| `sitios-web-gnu/references/gnu-vehicular.md` | árbol crudo, sin migrar | **Migrado completo**: las 5 columnas en formato blueprint; el árbol crudo queda solo como apéndice de trazabilidad |
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
