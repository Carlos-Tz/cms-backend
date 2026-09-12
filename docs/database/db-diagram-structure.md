# Diagrama de base de datos utilizado

Sintaxis [DBML](https://dbml.dbdiagram.io), listo para pegar en dbdiagram.io.
Diagrama completo de la base de datos única SQLite (`database/database.sqlite`).
Fuente: migraciones de `database/migrations/` y esquema vivo de la base.
Última actualización: 2026-09-11.

Notas de lectura:

- Toda tabla de dominio lleva `site_id` FK hacia `sites` (aislamiento
  multi-sitio), salvo `sites` y `users`. El filtro por sitio siempre ocurre en
  el backend, nunca con datos del cliente.
- `users` es la única tabla con la que se inicia sesión (guard JWT `api`).
  `password_reset_tokens` y `sessions` son tablas de soporte generadas por
  Laravel.
- `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs` y `migrations`
  son infraestructura de Laravel (caché, cola e historial de migraciones).

```dbml
// ---------------------------------------------------------------
// Multi-sitio: una fila por sitio (4 sitios fijos)
// ---------------------------------------------------------------

Table sites {
  id bigint [pk, increment]
  slug varchar [unique, not null, note: 'Identificador en la URL: /api/public/{site}']
  name varchar
  created_at timestamp
  updated_at timestamp

  Note: '4 filas fijas: gnu, gnu-vehicular, traninn-logistic, tectuus. Las siembra el blueprint, no el admin.'
}

// ---------------------------------------------------------------
// Estructura de páginas y secciones (blueprint)
// ---------------------------------------------------------------

Table pages {
  id bigint [pk, increment]
  site_id bigint [ref: > sites.id, not null, note: 'FK cascada']
  parent_id bigint [ref: > pages.id, null, note: 'Jerarquía del menú; cascada']
  slug varchar [not null, note: 'Segmento único con el que la API busca la página']
  path varchar [not null, note: 'Ruta completa del front (p. ej. /nuestros-servicios/gnc)']
  title varchar [not null]
  position int [default: 1, note: 'Ordena hermanos y columnas del menú']
  is_published boolean [default: true, note: 'Lo único que el admin puede editar']
  created_at timestamp
  updated_at timestamp

  Note: 'Estructura fija del blueprint: el admin solo publica y despublica.'

  indexes {
    (site_id, slug) [unique]
    (site_id, path) [unique]
  }
}

Table sections {
  id bigint [pk, increment]
  site_id bigint [ref: > sites.id, not null, note: 'FK cascada']
  page_id bigint [ref: > pages.id, null, note: 'Null = sección global del sitio']
  type varchar [not null, note: 'Uno de los 12 tipos del catálogo; sin enum en BD']
  position int [default: 1]
  content json [not null, note: 'Campos del tipo; los fijos se protegen en la validación']
  created_at timestamp
  updated_at timestamp

  indexes {
    (site_id, page_id, position)
  }
}

// ---------------------------------------------------------------
// Contenido de dominio con tabla propia
// ---------------------------------------------------------------

Table blog_posts {
  id bigint [pk, increment]
  site_id bigint [ref: > sites.id, not null, note: 'FK cascada']
  slug varchar [not null]
  title varchar [not null]
  excerpt text [null]
  body text [not null]
  cover_image varchar [null, note: 'Path devuelto por MediaController@store']
  published_at timestamp [null, note: 'Único estado: null = borrador, futura = programada, pasada = publicada']
  created_at timestamp
  updated_at timestamp

  indexes {
    (site_id, slug) [unique]
    (site_id, published_at)
  }
}

Table leads {
  id bigint [pk, increment]
  site_id bigint [ref: > sites.id, not null, note: 'Sale del route binding, nunca del cliente']
  source varchar [null, note: 'Slug de la página cuyo formulario produjo el lead']
  name varchar [not null]
  email varchar [not null]
  phone varchar [null]
  message text [null]
  details json [null, note: 'Campos extra validados contra el fields[] de la sección de origen']
  created_at timestamp [note: 'Fecha de captación']
  updated_at timestamp

  indexes {
    (site_id, created_at)
  }
}

Table stations {
  id bigint [pk, increment]
  site_id bigint [ref: > sites.id, not null, note: 'FK cascada']
  name varchar [not null]
  address varchar [not null]
  latitude decimal(10,7) [not null]
  longitude decimal(10,7) [not null]
  phone varchar [null]
  schedule varchar [null]
  is_active boolean [default: true, note: 'Saca una estación del mapa sin borrar sus datos']
  created_at timestamp
  updated_at timestamp

  indexes {
    (site_id, is_active)
  }
}

Table appointments {
  id bigint [pk, increment]
  site_id bigint [ref: > sites.id, not null, note: 'Por convención; sale del route binding']
  station_id bigint [ref: > stations.id, not null, note: 'FK cascada']
  date date [not null]
  slot varchar [not null, note: 'Uno de Appointment::SLOTS, guardado como su etiqueta']
  name varchar [not null]
  email varchar [not null]
  phone varchar [not null]
  vehicle_plate varchar [not null]
  vehicle_description varchar [null]
  created_at timestamp
  updated_at timestamp

  Note: 'El guard real contra doble reserva es el unique [station_id, date, slot].'

  indexes {
    (station_id, date, slot) [unique]
    (site_id, date)
  }
}

Table product_categories {
  id bigint [pk, increment]
  site_id bigint [ref: > sites.id, not null, note: 'FK cascada']
  slug varchar [not null]
  name varchar [not null]
  description text [null]
  image varchar [null, note: 'Path devuelto por MediaController@store']
  position int [default: 1]
  created_at timestamp
  updated_at timestamp

  indexes {
    (site_id, slug) [unique]
  }
}

Table products {
  id bigint [pk, increment]
  site_id bigint [ref: > sites.id, not null, note: 'Por convención: toda tabla de dominio lo lleva']
  product_category_id bigint [ref: > product_categories.id, not null, note: 'restrictOnDelete: el controlador lo convierte en 409']
  slug varchar [not null]
  name varchar [not null]
  description text [not null]
  price decimal(12,2) [null, note: 'Null = precio a cotizar; sin columna de moneda (todo MXN)']
  images json [not null, note: 'Lista ordenada de paths de media']
  is_active boolean [default: true]
  created_at timestamp
  updated_at timestamp

  indexes {
    (site_id, slug) [unique]
    (site_id, product_category_id, is_active)
  }
}

// ---------------------------------------------------------------
// Autenticación y sesiones
// ---------------------------------------------------------------

Table users {
  id bigint [pk, increment]
  name varchar
  email varchar [unique, not null, note: 'Credencial de login']
  email_verified_at timestamp [null]
  password varchar [not null, note: 'Hash bcrypt; oculto en respuestas']
  remember_token varchar [null]
  created_at timestamp
  updated_at timestamp

  Note: 'Único usuario del panel admin, creado por AdminUserSeeder. Autenticación vía JWT (guard api).'
}

Table password_reset_tokens {
  email varchar [pk]
  token varchar [not null]
  created_at timestamp [null]

  Note: 'Soporte de Laravel; sin uso en el flujo JWT actual.'
}

Table sessions {
  id varchar [pk]
  user_id bigint [ref: > users.id, null]
  ip_address varchar(45) [null]
  user_agent text [null]
  payload longtext [not null]
  last_activity integer [not null]

  indexes {
    user_id
    last_activity
  }
}

// ---------------------------------------------------------------
// Infraestructura de Laravel (caché, cola, historial de migraciones)
// ---------------------------------------------------------------

Table cache {
  key varchar [pk]
  value text [not null]
  expiration integer [not null]
}

Table cache_locks {
  key varchar [pk]
  owner varchar [not null]
  expiration integer [not null]
}

Table jobs {
  id bigint [pk, increment]
  queue varchar [not null]
  payload longtext [not null]
  attempts int [not null]
  reserved_at integer [null]
  available_at integer [not null]
  created_at integer [not null]

  indexes {
    queue
  }
}

Table job_batches {
  id varchar [pk]
  name varchar [not null]
  total_jobs integer [not null]
  pending_jobs integer [not null]
  failed_jobs integer [not null]
  failed_job_ids longtext [not null]
  options text [null]
  cancelled_at integer [null]
  created_at integer [not null]
  finished_at integer [null]
}

Table failed_jobs {
  id bigint [pk, increment]
  uuid varchar [unique, not null]
  connection text [not null]
  queue text [not null]
  payload longtext [not null]
  exception longtext [not null]
  failed_at timestamp [default: `now()`]
}

Table migrations {
  id bigint [pk, increment]
  migration varchar [not null]
  batch integer [not null]
}
```
