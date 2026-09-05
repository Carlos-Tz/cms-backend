# Entidades de dominio

Las tablas y modelos del CMS, con las decisiones de diseño que no se leen solas en la migración.
Base de datos única SQLite (`database/database.sqlite`). Todo el aislamiento entre sitios es la
columna `site_id`, presente en toda tabla de dominio menos `sites` y `users`.

Los modelos usan el estilo de atributos de Laravel 13 (`#[Fillable]`, `#[Hidden]`, `#[Scope]`), no
las propiedades `$fillable` / `$casts`. Si agregas un modelo, sigue ese estilo.

## Existentes

### `sites`

`id`, `slug` (unique), `name`, timestamps.

Las 4 filas son fijas: `gnu`, `gnu-vehicular`, `traninn-logistic`, `tectuus`. Las siembra el
blueprint, no el admin. `Site` expone `pages()`, `sections()`, `blogPosts()`, `leads()` y
**`globalSections()`** — el atajo a `sections()->whereNull('page_id')->orderBy('position')`, que es
lo que consume `GET /api/public/{site}/globals`.

### `pages`

`id`, `site_id` FK cascade, `parent_id` FK self nullable cascade, `slug`, `path`, `title`,
`position` (unsignedSmallInteger, default 1), `is_published` (bool, default true), timestamps.
Unique `[site_id, slug]` y `[site_id, path]`.

Dos columnas para la ruta, a propósito:

- **`slug`** — el segmento único con el que la API busca la página (`pages/{pageSlug}`)
- **`path`** — la ruta completa del front-end tal como la declara el blueprint
  (`/nuestros-servicios/gnc`)

`parent_id` da la jerarquía padre/hijo del árbol de navegación; `position` ordena los hermanos y, en
las páginas raíz, ordena las columnas del menú principal. De aquí sale el menú público **derivado**,
que aún no está implementado.

De todo esto el admin solo puede tocar `is_published`.

### `sections`

`id`, `site_id` FK cascade, `page_id` FK nullable cascade, `type`, `position`
(unsignedSmallInteger, default 1), `content` (json), timestamps. Index `[site_id, page_id, position]`.

- **`page_id = null` marca una sección global** (de todo el sitio, no de una página): `contact_info`,
  `social_icons`, `whatsapp_button`, y en GNU también `testimonials`.
- **`type`** es uno de los 12 del catálogo de `sitios-web-gnu/references/section-types.md`. No hay
  enum en la BD ni check constraint: la validación vive en `UpdateSectionRequest::SCHEMAS` y el valor
  lo pone el seeder.
- **`content`** es el JSON con los campos del tipo, casteado a `array`. Los campos `fijo` viven aquí
  igual que los editables; lo que los protege es que están ausentes de las reglas de validación.
- **Sin columna `key`.** El catálogo dice que hace falta cuando una página repita un tipo (dos
  `rich_text`, por ejemplo). Ninguna página de `gnu` lo hace todavía, así que la columna no existe.
  Agregarla es el paso previo a servir una página con tipos repetidos.

### `blog_posts`

`id`, `site_id` FK cascade, `slug`, `title`, `excerpt` (text nullable), `body` (text),
`cover_image` (string nullable), `published_at` (timestamp nullable), timestamps.
Unique `[site_id, slug]`, index `[site_id, published_at]`.

**`published_at` es el único estado**, sin flag `is_published`: `null` = borrador,
fecha futura = programado, fecha pasada = publicado. El scope `published()` del modelo es
`whereNotNull('published_at')->where('published_at', '<=', now())` y es lo que aplica la API pública;
la admin no lo aplica, por eso lista borradores.

`cover_image` guarda el `path` que devuelve `MediaController@store`, la misma convención que las
imágenes de las secciones.

Los artículos **no son páginas del blueprint**. La página `/blog` existe y lleva solo `page_hero`; el
listado lo sirve `GET /api/public/{site}/blog`.

### `leads`

`id`, `site_id` FK cascade, `source` (nullable), `name`, `email`, `phone` (nullable), `message`
(text nullable), `details` (json nullable), timestamps. Index `[site_id, created_at]`.

**Una columna por cada entrada core del `fields[]` de `lead_capture_form`** — `name`, `email`,
`phone`, `message`, las que lleva todo formulario. `Lead::CORE_FIELDS` es esa lista.

**`details` guarda los campos que un formulario pide de más.** Un formulario comercial pregunta lo
suyo —el vehículo a convertir, el tamaño de una flotilla— y sin esta columna el dato terminaba
enterrado en el texto libre del mensaje. Es JSON y no una columna por campo porque el conjunto se
declara por sección en `fields[]`, así que un formulario nuevo no debe necesitar una migración.

Lo que impide que se vuelva un basurero es que `StoreLeadRequest` **valida el payload contra el
`fields[]` de la sección de la que vino el lead**, resuelta por `source`: una clave no declarada es
un 422, y sin `source` no se acepta ningún extra. Ese request es lo que convierte `fields[]` de
pista para el front en contrato real.

`created_at` es la fecha de captación; no hay columna extra para eso. `site_id` **no** es fillable a
propósito: sale del route binding.

**`source` es el slug de la página cuyo formulario produjo el lead.** Un sitio lleva varios
`lead_capture_form` — `gnu-vehicular` tiene cuatro — y sin esta columna la bandeja no distingue una
solicitud de cotización de una duda general. Sí es fillable, a diferencia de `site_id`: no es una
frontera de confianza, un valor falsificado solo etiquetea mal una fila. Aun así el form request lo
valida contra las páginas **publicadas de ese sitio**, para que la bandeja no se llene de basura.
Nullable, para que un front que no lo mande siga funcionando.

La bandeja filtra con `?source={slug}`. No lleva índice: al volumen de leads de un sitio de
marketing no se paga.

### `stations`

`id`, `site_id` FK cascade, `name`, `address`, `latitude` / `longitude` (`decimal(10,7)`), `phone`
(nullable), `schedule` (nullable), `is_active`, timestamps. Index `[site_id, is_active]`.

Las estaciones que dibuja el tipo `stations_map`, en `gnu` y `gnu-vehicular`. El spec es explícito:
**no son campos de la sección**, la sección solo dice dónde se dibuja el mapa. Decimal y no float
porque las coordenadas se comparan y se muestran, nunca se acumulan; 7 decimales son ~1 cm.

`is_active` deja sacar una estación del mapa sin perder sus datos: el `GET` público filtra por el
scope `active()`, el admin no. `site_id` es `prohibited` en update — una estación no cambia de sitio.

### `appointments`

`id`, `site_id` FK cascade, `station_id` FK cascade, `date`, `slot`, `name`, `email`, `phone`,
`vehicle_plate`, `vehicle_description` (nullable), timestamps.
**Unique `[station_id, date, slot]`**, index `[site_id, date]`.

Las verificaciones que se agendan desde la sección `service_scheduling` de `gnu-vehicular`. Dominio
de datos, como los leads: nacen en el sitio público, el admin las lee y las cancela borrándolas.

Cuatro decisiones que no son obvias:

- **Una cita por slot**, y el candado es el índice único, no la validación. El `Rule::unique` del
  form request solo existe para que el 422 sea legible; la carrera entre validar e insertar la
  atrapa un `catch (UniqueConstraintViolationException)` en el controlador.
- **Los slots viven en `Appointment::SLOTS`**, no en una tabla ni en la sección: tres bloques de dos
  horas de lunes a sábado. Cambiarlos es cambio de código, a propósito.
- **`date` NO tiene cast a fecha.** Con el cast, Eloquent lo guardaría como `Y-m-d H:i:s` y ninguna
  búsqueda de disponibilidad ni el índice único casarían con el `Y-m-d` que toma y devuelve la API.
  Es un valor date-only en el contrato; se guarda y se compara como string.
- **`site_id` es derivable de `station_id`** pero se guarda igual, por la convención de que toda
  tabla de dominio lo lleve. Sale del route binding, nunca del payload.

### `product_categories`

`id`, `site_id` FK cascade, `slug`, `name`, `description` (text nullable), `image` (string nullable),
`position` (unsignedSmallInteger, default 1), timestamps. Unique `[site_id, slug]`.

Las categorías del catálogo de Tectuus: EDRM, Compresoras y Valvulería, sembradas por
`TectuusBlueprintSeeder`. **No son páginas**, así que no aparecen en `GET {site}/menu`; el front arma
ese submenú con `GET {site}/product-categories`. El motivo completo está en
`sitios-web-gnu/references/tectuus.md`.

**Sin `is_active`**, a diferencia de `stations` y `products`: el cliente pidió estado a nivel
producto, no de categoría. Una categoría sin productos activos simplemente devuelve
`products_count = 0`.

`position` ordena la lista; `image` guarda el `path` que devuelve `MediaController@store`, la misma
convención que `blog_posts.cover_image`.

### `products`

`id`, `site_id` FK cascade, `product_category_id` FK **restrict**, `slug`, `name`, `description`
(text), `price` (`decimal(12,2)` nullable), `images` (json), `is_active` (bool default true),
timestamps. Unique `[site_id, slug]`, index `[site_id, product_category_id, is_active]`.

Cuatro decisiones que no son obvias:

- **`price` es nullable y significa «precio a cotizar».** El equipo industrial se cotiza más seguido
  de lo que se lista. Sin columna de moneda: todo es MXN, y se agrega si aparece equipo importado.
  Casteado a `decimal:2`, no a float: el precio se muestra y se compara, nunca se acumula en PHP.
- **`images` es JSON**, una lista ordenada de paths de media — la misma forma que las colecciones de
  `sections.content`. Una tabla `product_images` se paga cuando cada imagen necesite su `alt` y su
  `caption`; hoy no los necesita.
- **La FK de la categoría es `restrictOnDelete`, no cascade.** Borrar una categoría no debe llevarse
  sus productos en silencio. `Admin\ProductCategoryController@destroy` comprueba
  `products()->exists()` antes de borrar y responde **409** con mensaje en español; el candado real
  es la FK, la comprobación solo lo hace legible.
- **`site_id` es derivable de `product_category_id`** pero se guarda igual, por la convención de que
  toda tabla de dominio lo lleve — misma decisión que `appointments.site_id`. Lo que impide un
  producto cruzado es `ProductRequest`: valida `product_category_id` con
  `Rule::exists(...)->where('site_id', $siteId)`, así que una categoría de otro sitio es un 422.

El scope `active()` del modelo es lo que aplica la API pública; la admin no lo aplica, por eso lista
inactivos — igual que el blog lista borradores.

### `users`

Tabla estándar de Laravel. Único login del sistema, guard JWT `api`. `User` implementa `JWTSubject`.
La siembra `AdminUserSeeder` de forma idempotente con las credenciales de `config/admin.php`.

Un solo rol global con acceso total. **No hay tabla de roles ni permisos** y no está pedida. El
diagrama DBML de las tablas de autenticación está en `docs/database/db-diagram-structure.md`.

## Declaradas por el spec, sin tabla todavía

### `media`

El upload ya funciona sin tabla: `POST /api/admin/media` guarda el archivo y devuelve `{path, url}`,
y ese `path` es lo que se guarda en los campos `media` de las secciones y en
`blog_posts.cover_image`. Los otros cuatro métodos del recurso son stubs.

Que la tabla haga falta o no es una **pregunta abierta**, no un pendiente dado: para un catálogo de
imágenes de marketing puede alcanzar con listar el disco.

### `menus` — probablemente nunca

El spec dice que el menú se **deriva** del mapa de páginas («su orden es derivable del mapa de
páginas»). Una tabla de menús y su CRUD admin contradicen eso. El `apiResource('menus')` de
`routes/admin.php` es scaffolding: ver «Candidatos a borrar» en `functionality-map.md`.

## Factories

- `BlogPostFactory` — sin `site_id` por default (se usa `->for($site)`); estados `draft()` y
  `scheduled()`
- `LeadFactory` — igual, sin `site_id`
- `StationFactory` — sin `site_id`; estado `inactive()`
- `AppointmentFactory` — sin `site_id` ni `station_id`: se pasan con `->for($site)->for($station)`
- `ProductCategoryFactory` — sin `site_id`
- `ProductFactory` — sin `site_id` ni `product_category_id`: se pasan con
  `->for($site)->for($category, 'category')`, con el nombre de relación explícito porque la relación
  se llama `category` y no `productCategory`. Estados `inactive()` y `quoted()` (`price` = null)
- `UserFactory` — estándar

**No hay `SiteFactory`, `PageFactory` ni `SectionFactory`, y es deliberado**: sitios, páginas y
secciones solo se crean desde los seeders de blueprint, así los tests corren contra la estructura
real y no contra una inventada.
