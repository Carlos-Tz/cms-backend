# Estado de implementación por tipo de sección

Qué tipos del catálogo ya son editables desde la API admin y cuáles todavía no. Vive aquí, junto a la
definición de los tipos, para que el estado no se separe de la forma. El mapa general del backend
está en la skill `cms-gnu-backend` y **referencia este archivo en vez de repetirlo**.

Columna **reglas**: si el tipo tiene entrada en `UpdateSectionRequest::SCHEMAS`
(`app/Http/Requests/Admin/UpdateSectionRequest.php`). Sin entrada, `PUT /api/admin/sections/{id}`
responde «El tipo de sección «X» aún no es editable desde el panel». Es cerrado por defecto, no un bug.

Columna **páginas en `gnu`**: en cuántas de las 17 páginas del mapa de `gnu.md` aparece el tipo. Da la
prioridad: el tipo que aparece en más páginas es el que más rinde implementar.

| tipo | reglas | sembrado | páginas en `gnu` | sitios (spec) | nota |
|---|---|---|---|---|---|
| `page_hero` | ✅ | ✅ `gnu` y `gnu-vehicular`, ambos en `/blog` | 16 de 17 | los 4 | la portada `/` no lo lleva |
| `banner_carousel` | ✅ | ✅ `gnu` y `gnu-vehicular`, ambos en `/` | 1 | los 4 | |
| `savings_simulator` | ✅ | ✅ `gnu` `/` y `/gnc`, `gnu-vehicular` `/` (3 combustibles) | 2 | `gnu`, `gnu-vehicular`, `traninn-logistic` | el cálculo lo sirve `GET savings-simulator`; **cada instancia guarda sus propios precios** y se elige con `?page={slug}` |
| `clients_showcase` | ✅ | ✅ `gnu` `/` | 2 | `gnu`, `traninn-logistic`, `tectuus` | |
| `lead_capture_form` | ✅ | ✅ `gnu` `/`, `gnu-vehicular` `/` | 5 | los 4 | `fields[]` es `fijo` y se sembró bloqueado |
| `contact_info` | ✅ | ✅ `gnu` y `gnu-vehicular`, global | global | los 4 | `page_id = null` |
| `social_icons` | ✅ | ✅ `gnu` y `gnu-vehicular`, global | global | los 4 | `network` es `fijo`; enum en las reglas: facebook, instagram, twitter, linkedin, youtube, tiktok |
| `whatsapp_button` | ✅ | ✅ `gnu` y `gnu-vehicular`, global | global | los 4 | |
| `rich_text` | ✅ | ✅ `gnu`, 12 páginas | **12 de 17** | `gnu` (+ los otros al migrar) | el de `/` es el bloque de video de la portada. Al sembrar una página con dos instancias hará falta la columna `key` en `sections` |
| `feature_grid` | ✅ | ✅ `gnu`, 7 páginas; `gnu-vehicular` `/` | **7 de 17** | `gnu`, `gnu-vehicular` (+ los otros al migrar) | el de la portada de `gnu-vehicular` es «Programa de recompensas» |
| `testimonials` | ✅ | ✅ `gnu` global, `gnu-vehicular` en `/` | global | `gnu` (global), `gnu-vehicular` (sección de *Inicio*) | la divergencia de ámbito entre los dos sitios es deliberada |
| `stations_map` | ✅ | ✅ `gnu` `/gnc` y `/contacto`, `gnu-vehicular` `/` | 2 | `gnu`, `gnu-vehicular` | solo `title` es editable; `center` y `zoom` son `fijo`. Los pines salen de la tabla `stations`, no de la sección |

## Qué se deduce de esto

- **12 de 12 tipos editables.** El catálogo entero tiene reglas en `SCHEMAS`; ya no hay tipos
  cerrados por defecto. Un tipo nuevo sí nace cerrado hasta que se le escriban las suyas.
- **`gnu` está completo: 17 de 17 páginas.** De `gnu-vehicular` faltan 3 de sus 5 columnas;
  `traninn-logistic` y `tectuus` no tienen nada.
- Los 4 tipos globales de `gnu` (`contact_info`, `social_icons`, `whatsapp_button` y `testimonials`)
  están sembrados. En `gnu-vehicular` los globales son 3: `testimonials` es sección de su portada.

## Qué está sembrado hoy

`GnuBlueprintSeeder` siembra **las 17 páginas** de `gnu` con sus 47 secciones, más 4 secciones
globales, 4 artículos y 2 estaciones de ejemplo. `GnuVehicularBlueprintSeeder` siembra **la portada
y el blog** de `gnu-vehicular`: `/` con sus 6 secciones, `/blog` con su hero, 3 secciones globales,
4 artículos y 2 estaciones (Morelia y Uruapan). `traninn-logistic` y `tectuus` no tienen seeder.

Las estaciones que siembra son placeholders, igual que todos los valores: las reales las carga el
admin por `POST /api/admin/stations`.

Que una página esté en el mapa del blueprint no significa que exista en la BD.
