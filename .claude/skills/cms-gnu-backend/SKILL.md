---
name: cms-gnu-backend
description: Alcance y estado del backend de este Headless CMS — qué debe cubrir, qué endpoints existen y cuáles son todavía stubs, qué entidades viven fuera de la tabla `sections`, y cómo el código hace cumplir el contrato de editabilidad. Usa esta skill SIEMPRE que toques rutas, controladores, Form Requests, migraciones o la forma de las respuestas de API; y ANTES de implementar cualquier cosa, para no reimplementar algo que ya está hecho ni construir algo que está fuera de alcance. Consúltala cuando la pregunta sea «¿esto ya está?», «¿qué falta?», «¿dónde vive esto?» o «¿esto le toca al admin o al front?». Para el contenido en sí (qué páginas hay, qué campos tiene cada tipo de sección) la fuente es la skill `sitios-web-gnu`.
---

# Backend del Headless CMS — alcance y estado

Esta skill cubre el CMS **como backend**. El contenido que administra —qué páginas existen, qué
secciones lleva cada una, qué campos tiene cada tipo— vive en la skill **`sitios-web-gnu`**, que es
su fuente de verdad. No dupliques nada de allá aquí.

## Qué debe cubrir este CMS

Un backend API-only que sirve el contenido predefinido de 4 sitios de marketing y capta sus leads.
Nada más. En una línea: **servir contenido predefinido + captar leads.**

Sí cubre:

- servir al front-end las páginas de cada sitio con sus secciones en orden, y las secciones globales
- dejar que el administrador edite el **valor** de los campos editables de esas secciones
- gestionar el contenido de dominio que tiene su propia tabla: `blog_posts`, `stations`, `leads`,
  `media`
- calcular el ahorro del simulador en backend, no en el front
- recibir leads del formulario público, con límite de tasa

No cubre, y no es un pendiente sino una decisión — ver «Fuera de alcance» en
`references/functionality-map.md`:

- no es un page-builder: el admin no crea páginas ni mueve secciones
- no hay metadatos SEO, i18n, versionado de contenido, previsualización ni roles por sitio
- no renderiza nada: no hay front-end en este repo

## Las dos superficies de API

Ambas se registran en el `then:` de `withRouting()` en `bootstrap/app.php`. **No hay
`routes/api.php`**; si lo creas, vas a duplicar prefijos.

| Superficie | Prefijo | Archivo | Auth | Escritura |
|---|---|---|---|---|
| Admin | `/api/admin` | `routes/admin.php` | JWT, guard `api` (`tymon/jwt-auth`) | sí |
| Público | `/api/public/{site:slug}` | `routes/public.php` | ninguna | solo `POST leads` |

`POST /api/admin/auth/login` es la única ruta admin fuera de `auth:api`. El sitio en la API pública
se resuelve por **route binding** (`{site:slug}` → modelo `Site`), no por parámetro. El inventario
completo, endpoint por endpoint y con su estado, está en `references/api-surface.md`.

## Envelope de respuesta

Toda respuesta implementada devuelve tres claves:

```json
{ "status": "success", "data": …, "meta": { "message": "…" } }
```

`status` es `success` o `error`. `meta` lleva paginación (`page`, `per_page`, `total`, `last_page`),
`message` en español, o `site`. `data` es `null` cuando no hay cuerpo que devolver — así responde
`POST leads`. Los errores 401 y 404 los formatea `withExceptions` en `bootstrap/app.php`, ya en
español.

Dos divergencias conocidas, registradas a propósito:

- los controladores stub devuelven `{data, meta}` **sin** `status`. Es la forma rápida de detectar
  un stub: si falta `status`, no está implementado.
- `CLAUDE.md` documenta la forma vieja de dos claves. La forma real es de tres.

## Aislamiento por sitio

Toda tabla de dominio lleva `site_id` (menos `sites` y `users`). El filtro es **siempre**
backend-side:

- público: sale del route binding `{site:slug}`
- admin: del query param `?site={slug}`, resuelto vía `whereHas('site')`

**Nunca se acepta `site_id` del cliente.** Dos puntos donde eso está forzado en código, y hay un
test por cada uno:

- `Lead::$fillable` omite `site_id` a propósito; el lead se crea con `$site->leads()->create(...)`
- `BlogPostRequest` marca `site_id` como `prohibited` en update, para que un artículo no se pueda
  mover de sitio

## Cómo el código hace cumplir el contrato de editabilidad

El contrato está en `sitios-web-gnu/SKILL.md`. Aquí están sus cuatro prohibiciones y el punto exacto
donde cada una se aplica. Si cambias uno de estos archivos, estás cambiando el contrato:

| Prohibición | Dónde se aplica |
|---|---|
| El admin no agrega ni borra secciones | `routes/admin.php`: `sections` es `only(['index','show','update'])`, sin `store` ni `destroy` |
| El admin no cambia el `type` de una sección | `UpdateSectionRequest` nunca incluye `type` en las reglas; el `type` se lee del modelo ya cargado |
| El admin no edita campos `fijo` | Los campos `fijo` están ausentes de `UpdateSectionRequest::SCHEMAS`, así que se caen del payload validado; el `array_merge($section->content, …)` de `SectionController@update` conserva el valor guardado |
| El admin no crea ni borra páginas | `routes/admin.php`: `pages` es `only(['index','show','update'])`, sin `store` ni `destroy`; `UpdatePageRequest` solo admite `is_published`, así que `title`, `path` y `parent_id` se caen del payload |

Un tipo de sección sin entrada en `SCHEMAS` se rechaza con `ValidationException` («El tipo de
sección «X» aún no es editable desde el panel»). Eso es intencional: cerrado por defecto.

## Convenciones

- **Validación siempre en Form Request.** La única excepción es `AuthController@login`, que valida
  inline dos campos. No la extiendas: si el login crece, sale a su propio request.
- **Sin API Resources todavía.** Los controladores arman el array a mano en un método privado
  (`presentSection()`, `present()`). Es deliberado mientras las formas sean de 3-4 claves; cuando una
  forma se repita en dos controladores, ahí sí toca un Resource.
- **Sin Policies.** Un solo rol global; `authorize()` devuelve `true`. La autorización real es el
  guard `auth:api` en el grupo de rutas.
- Código, identificadores y comentarios en inglés. Mensajes al usuario en español neutro.
- Los blueprints son **seeders**, no config. `config/blueprints/{site}.php` está descartado; ver el
  mapa.

## Archivos de referencia

- `references/functionality-map.md` — **el mapa de lo hecho, lo stub y lo que falta.** Léelo antes de
  implementar cualquier cosa, y actualízalo cuando cierres un hueco.
- `references/api-surface.md` — los 48 endpoints, con su estado y lo que devuelven.
- `references/domain-entities.md` — las tablas que no son `sections`, con sus columnas y las
  decisiones de diseño que no son obvias.

Para contenido: `sitios-web-gnu/references/section-types.md` (campos de cada tipo),
`references/{sitio}.md` (qué páginas y secciones) y
`sitios-web-gnu/references/implementation-status.md` (qué tipo ya es editable).
