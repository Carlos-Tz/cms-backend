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
| `page_hero` | ✅ | ✅ `gnu` 16 páginas; `gnu-vehicular` 13 páginas; `traninn-logistic` 10 páginas; `tectuus` 3 páginas | 16 de 17 | los 4 | la portada `/` no lo lleva |
| `banner_carousel` | ✅ | ✅ los 4 sitios sembrados, en `/` | 1 | los 4 | |
| `savings_simulator` | ✅ | ✅ `gnu` `/` y `/gnc`, `gnu-vehicular` `/`, `traninn-logistic` `/` (3 combustibles) | 2 | `gnu`, `gnu-vehicular`, `traninn-logistic` | el cálculo lo sirve `GET savings-simulator`; **cada instancia guarda sus propios precios** y se elige con `?page={slug}` |
| `clients_showcase` | ✅ | ✅ `gnu` `/` y `/nuestros-servicios`, `traninn-logistic` `/` y `/sobre-nosotros`, `tectuus` `/` | 2 | `gnu`, `traninn-logistic`, `tectuus` | `description`, `quote` y `quote_author` por logo son nullable: la instancia de `/sobre-nosotros` los llena, las demás los dejan vacíos |
| `lead_capture_form` | ✅ | ✅ `gnu` `/`, `gnu-vehicular` 4 páginas, `traninn-logistic` `/` y `/contacto`, `tectuus` `/` y `/contacto` | 5 | los 4 | `fields[]` es `fijo` y se sembró bloqueado |
| `contact_info` | ✅ | ✅ los 4 sitios sembrados, global | global | los 4 | `page_id = null` |
| `social_icons` | ✅ | ✅ los 4 sitios sembrados, global | global | los 4 | `network` es `fijo`; enum en las reglas: facebook, instagram, twitter, linkedin, youtube, tiktok |
| `whatsapp_button` | ✅ | ✅ los 4 sitios sembrados, global | global | los 4 | |
| `rich_text` | ✅ | ✅ `gnu`, 12 páginas; `gnu-vehicular` 10 páginas; `traninn-logistic` 6 páginas; `tectuus` `/` y `/sobre-nosotros` | **12 de 17** | `gnu` (+ los otros al migrar) | el de `/` es el bloque de video de la portada; el de `gnu-vehicular` `/contacto` es «Facturación». Al sembrar una página con dos instancias hará falta la columna `key` en `sections` |
| `image_gallery` | ✅ | ✅ `traninn-logistic`, 3 páginas de *Sobre nosotros*; `tectuus` `/sobre-nosotros` | 0 | genérico, cualquier sitio | nació con la columna «Sobre nosotros» de Traninn; sin `layout` ni `columns`, la rejilla la decide el front |
| `feature_grid` | ✅ | ✅ `gnu`, 7 páginas; `gnu-vehicular` 5 páginas; `traninn-logistic` 3 páginas | **7 de 17** | `gnu`, `gnu-vehicular` (+ los otros al migrar) | el de la portada de `gnu-vehicular` es «Programa de recompensas» |
| `service_scheduling` | ✅ | ✅ `gnu-vehicular` `/ubicaciones/agenda-tu-verificacion` | 0 | solo `gnu-vehicular` | único tipo exclusivo de un sitio. Los horarios no son campos: los sirve `GET appointments/availability` |
| `testimonials` | ✅ | ✅ `gnu` global, `gnu-vehicular` en `/` | global | `gnu` (global), `gnu-vehicular` (sección de *Inicio*) | la divergencia de ámbito entre los dos sitios es deliberada |
| `stations_map` | ✅ | ✅ `gnu` `/gnc` y `/contacto`, `gnu-vehicular` `/`, `/ubicaciones` y `/contacto`, `traninn-logistic` `/ubicaciones` | 2 | `gnu`, `gnu-vehicular`, `traninn-logistic` | solo `title` es editable; `center` y `zoom` son `fijo`. Los pines salen de la tabla `stations`, no de la sección |

## Qué se deduce de esto

- **14 de 14 tipos editables.** El catálogo entero tiene reglas en `SCHEMAS`; ya no hay tipos
  cerrados por defecto. Un tipo nuevo sí nace cerrado hasta que se le escriban las suyas.
- **Los 4 sitios están completos**: 17, 14, 11 y 4 páginas. `tectuus` es el más chico porque
  ninguna de sus 4 columnas tiene hijas — sus tres nodos de *Productos* son categorías, filas de
  `product_categories`, no páginas.
- Los 4 tipos globales de `gnu` (`contact_info`, `social_icons`, `whatsapp_button` y `testimonials`)
  están sembrados. En `gnu-vehicular` los globales son 3: `testimonials` es sección de su portada.

## Qué está sembrado hoy

`GnuBlueprintSeeder` siembra **las 17 páginas** de `gnu` con sus 47 secciones, más 4 secciones
globales, 4 artículos y 2 estaciones de ejemplo. `GnuVehicularBlueprintSeeder` siembra **las 14 páginas** de
`gnu-vehicular`: `/` con sus 6 secciones, la columna de servicios con sus 6 páginas en 3 niveles,
`/ubicaciones` y sus 4 hijas, `/contacto` con 4 y `/blog` con su hero; más 3 secciones globales,
4 artículos y 2 estaciones (Morelia y Uruapan). `TraninnLogisticBlueprintSeeder` siembra **las 11 páginas**
de `traninn-logistic`: `/` con sus 5 secciones, `/nuestros-servicios` y sus 3 hijas,
`/sobre-nosotros` y sus 3 hijas, `/contacto` con hero y formulario, y `/ubicaciones` con su mapa;
más 3 secciones globales y 2 bases de ejemplo en `stations`. Sin blog, porque el sitio no declara ninguno.
`TectuusBlueprintSeeder` siembra **las 4 páginas** de `tectuus`: `/` con sus 4 secciones,
`/sobre-nosotros` con 3, `/productos` con su hero y `/contacto` con 2; más 3 secciones globales y las
**3 categorías de producto** (`edrm`, `compresoras`, `valvuleria`). Sin blog, sin simulador y sin
estaciones, porque el sitio no declara ninguno. **No siembra productos**: los carga el admin por
`POST /api/admin/products`, igual que las estaciones.

Las estaciones que siembra son placeholders, igual que todos los valores: las reales las carga el
admin por `POST /api/admin/stations`.

Que una página esté en el mapa del blueprint no significa que exista en la BD.
