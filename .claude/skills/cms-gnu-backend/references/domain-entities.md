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
(text nullable), timestamps. Index `[site_id, created_at]`.

**Una columna por cada entrada del `fields[]` bloqueado de `lead_capture_form`.** Esa es la razón de
que `fields[]` sea `fijo`: si el admin agregara un campo al formulario, el dato llegaría al endpoint
y se descartaría en silencio. Cambiar el formulario es un cambio de esquema.

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
- `UserFactory` — estándar

**No hay `SiteFactory`, `PageFactory` ni `SectionFactory`, y es deliberado**: sitios, páginas y
secciones solo se crean desde los seeders de blueprint, así los tests corren contra la estructura
real y no contra una inventada.
