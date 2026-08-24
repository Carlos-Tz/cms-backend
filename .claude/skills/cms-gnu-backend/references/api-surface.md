# Superficie de API — los 53 endpoints

Verificado con `php artisan route:list --path=api --except-vendor`. Si el conteo no da 53, este
archivo quedó atrás.

`real` = implementado y con tests. `stub` = devuelve `{data, meta}` sin `status`, sin lógica y sin
tabla detrás en varios casos.

## Admin — `/api/admin` (43 rutas)

Todo dentro de `Route::middleware('auth:api')` menos `auth/login`.

### Autenticación — 3, real

| Método | URI | Acción | Devuelve |
|---|---|---|---|
| POST | `auth/login` | `AuthController@login` | `access_token`, `token_type: bearer`, `expires_in`. 401 «Credenciales inválidas» |
| POST | `auth/logout` | `AuthController@logout` | mensaje de salida |
| GET | `auth/me` | `AuthController@me` | el usuario autenticado |

### Secciones — 3, real

| Método | URI | Acción | Devuelve |
|---|---|---|---|
| GET | `sections` | `SectionController@index` | filtros `?site={slug}`, `?page={slug}`, `?global=1`; orden `page_id`, `position`; `meta.total` |
| GET | `sections/{section}` | `SectionController@show` | la sección completa |
| PUT/PATCH | `sections/{section}` | `SectionController@update` | la sección fresca. Valida `content` contra el esquema del `type`; los campos `fijo` se preservan |

**No existen `POST sections` ni `DELETE sections/{id}`, y no deben existir**: las secciones son
estructura del blueprint. Hay un test que lo verifica (`does not expose routes to create or delete
sections`).

### Páginas — 3, real

| Método | URI | Acción | Devuelve |
|---|---|---|---|
| GET | `pages` | `PageController@index` | `?site={slug}`; orden `parent_id`, `position`; **incluye despublicadas** y `sections_count`; `meta.total` |
| GET | `pages/{page}` | `PageController@show` | la página con sus secciones (`id`, `type`, `position`, `content`), en orden |
| PUT/PATCH | `pages/{page}` | `PageController@update` | la página fresca. Acepta **solo `is_published`**; `title`, `path` y `parent_id` se caen del payload |

**No existen `POST pages` ni `DELETE pages/{id}`, y no deben existir**: las páginas son estructura del
blueprint. Test: `does not expose routes to create or delete pages`.

### Blog — 5, real

| Método | URI | Acción | Devuelve |
|---|---|---|---|
| GET | `blog-posts` | `BlogPostController@index` | `?site={slug}`, `orderByDesc('published_at')`, `paginate(20)`. **Incluye borradores** |
| POST | `blog-posts` | `BlogPostController@store` | 201. Deriva `slug` del `title` si no viene |
| GET | `blog-posts/{blog_post}` | `BlogPostController@show` | el artículo |
| PUT/PATCH | `blog-posts/{blog_post}` | `BlogPostController@update` | el artículo fresco. `site_id` `prohibited` |
| DELETE | `blog-posts/{blog_post}` | `BlogPostController@destroy` | mensaje |

### Leads — 3, real

| Método | URI | Acción | Devuelve |
|---|---|---|---|
| GET | `leads` | `LeadController@index` | `?site={slug}`, `?source={pageSlug}`, `latest()`, `paginate(20)` |
| GET | `leads/{lead}` | `LeadController@show` | el lead con su sitio |
| DELETE | `leads/{lead}` | `LeadController@destroy` | mensaje |

**No hay `POST` ni `PUT` de leads en admin, a propósito**: un lead lo genera el visitante, el admin
solo lo lee y lo borra. Test: `does not let the admin create or edit a lead`.

`?source` es cómo el panel separa las cotizaciones del resto: el front manda en `source` el slug de
la página donde se renderizó el formulario, y el request lo valida contra las páginas publicadas de
ese sitio. Sin él, los 4 formularios de `gnu-vehicular` caen indistinguibles en la misma bandeja.

`source` hace un segundo trabajo en el `POST`: resuelve qué sección `lead_capture_form` originó el
lead, y su `fields[]` es la lista blanca contra la que se valida `details`. Una clave no declarada
por esa sección es un 422 — así `cotiza-tu-equipo` acepta `vehicle_description` y no `fleet_size`,
aunque el segundo exista en otro formulario del mismo sitio.

### Media — 3, reales

| Método | URI | Acción | Estado |
|---|---|---|---|
| POST | `media` | `MediaController@store` | **real** — valida `file` `image\|max:5120`, guarda en `media` del disco `public`, devuelve `{path, url}` 201 |
| GET | `media` | `MediaController@index` | **real** — lista `media` del disco `public`, más reciente primero: `[{path, name, url}]` + `meta.total` |
| DELETE | `media/{medium}` | `MediaController@destroy` | **real** — `{medium}` es el nombre del archivo; 404 si no existe |

**Sin `show` ni `update`, y sin tabla `media`**: un archivo no tiene detalle que mostrar ni nada que
parchear, y los únicos metadatos que hacen falta hoy (nombre y URL) salen del disco. La tabla se
justifica cuando pidan alt text o trazabilidad de quién subió qué.

Un traversal en `destroy` lo detiene el **router**: `{medium}` matchea un solo segmento de ruta, así
que `media/..%2F.env` da 404 sin llegar a la acción. El `basename()` del controlador es la segunda
línea de defensa, para el día que cambie esa restricción de ruta. Test:
`cannot reach the media delete with a path outside the media folder`.

### Estaciones — 5, real

| Método | URI | Acción | Devuelve |
|---|---|---|---|
| GET | `stations` | `StationController@index` | `?site={slug}`, `orderBy('name')`, `paginate(20)`. **Incluye inactivas** |
| POST | `stations` | `StationController@store` | 201. `site_id` requerido |
| GET | `stations/{station}` | `StationController@show` | la estación |
| PUT/PATCH | `stations/{station}` | `StationController@update` | la estación fresca. `site_id` `prohibited` |
| DELETE | `stations/{station}` | `StationController@destroy` | mensaje |

### Citas — 3, real

| Método | URI | Acción | Devuelve |
|---|---|---|---|
| GET | `appointments` | `AppointmentController@index` | `?site={slug}`, `orderBy('date')`+`orderBy('slot')` **ascendente**, `paginate(20)`, con `station:id,name` |
| GET | `appointments/{appointment}` | `AppointmentController@show` | la cita con su sitio y su estación |
| DELETE | `appointments/{appointment}` | `AppointmentController@destroy` | mensaje. Cancelar es borrar: no hay columna de estado |

**No hay `POST` ni `PUT` de citas en admin, a propósito**, igual que con los leads: una cita la
genera el visitante. Orden ascendente y no `latest()` porque en una agenda importa lo que viene.

### Stubs completos — 15 rutas

`apiResource` de 5 métodos cada uno, todos devolviendo `{data, meta:{resource, id}}`:

| Recurso | Controlador | Nota |
|---|---|---|
| `sites` | `SiteController` | 4 sitios fijos que siembra el blueprint. Candidato a quedar read-only |
| `users` | `UserController` | un solo rol global; no se pidió gestión de usuarios |
| `menus` | `Admin\MenuController` | el menú es **derivado** y `GET /api/public/{site}/menu` ya lo deriva. Candidato a borrar |

## Público — `/api/public/{site:slug}` (10 rutas)

| Método | URI | Acción | Estado / devuelve |
|---|---|---|---|
| GET | `pages/{pageSlug}` | `PageController@show` | **real** — solo si `is_published`; `{slug, path, title, sections:[{type, content}]}` en orden de blueprint |
| GET | `globals` | `PageController@globals` | **real** — secciones con `page_id = null`, mapeadas `type => content` |
| GET | `blog` | `BlogController@index` | **real** — `published()`, `latest('published_at')`, `paginate(10)`. **Sin `body`** |
| GET | `blog/{postSlug}` | `BlogController@show` | **real** — el artículo con `body` |
| GET | `savings-simulator` | `SavingsSimulatorController` | **real** — `?fuel={slug}&quantity={n}&page={slug}`. Ver abajo |
| POST | `leads` | `LeadController@store` | **real** — `throttle:10,1`. `data: null` + agradecimiento, 201. Acepta `source` (slug de la página del formulario) y `details` (los campos extra que esa sección declare) |
| GET | `menu` | `MenuController@show` | **real** — árbol derivado de `pages`: raíces por `position`, hijos anidados en `children`. Solo `is_published`; una página oculta arrastra su rama entera. `{slug, path, title, children[]}` |
| GET | `stations` | `StationController@index` | **real** — solo `is_active`, sin paginar (un mapa dibuja todos los pines). `{name, address, latitude, longitude, phone, schedule}` |
| GET | `appointments/availability` | `AppointmentController@availability` | **real** — `?station={id}&date=Y-m-d`. Ver abajo |
| POST | `appointments` | `AppointmentController@store` | **real** — `throttle:10,1`. 201 con `{date, slot, station}` confirmados. Segunda escritura pública |

### `GET appointments/availability`

Los horarios no son contenido: viven en `Appointment::SLOTS` — tres bloques de dos horas, de lunes a
sábado, **una cita cada uno** — y este endpoint es como los conoce el front.

```
station        estación del sitio, activa; si no, 404
día cerrado    domingo o fecha pasada  →  slots: []   (no un 422)
día abierto    los 3 slots con available = ! existe cita (station, date, slot)
```

Devolver una lista vacía y no un error es deliberado: el widget consulta la semana completa y no
debe romperse el día que el taller está cerrado.

El `POST` valida además que la estación sea del sitio y esté activa, que la fecha no sea pasada ni
domingo, y que el slot esté libre. El candado real contra el doble booking es el índice único
`[station_id, date, slot]`; si dos visitantes chocan entre validar e insertar, el controlador
convierte la violación en un 422 en español.

### `GET savings-simulator`

Único endpoint público con cálculo. Lee los precios de la primera sección `savings_simulator` del
sitio; la fila con `fuel = gas-natural` es la base y su `equivalence_factor` es 1.

```
currentCost  = quantity * price_per_unit del combustible pedido
gasQuantity  = quantity * equivalence_factor
gasCost      = gasQuantity * precio del gas natural
savings      = currentCost - gasCost        (todo redondeado a 2 decimales)
```

- 503 «El precio del gas natural no está configurado.» si falta la fila base
- `ValidationException` si el `fuel` no existe o es el propio `gas-natural`
