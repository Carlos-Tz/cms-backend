# Catálogo de tipos de sección

Esquema de cada `type` de la tabla `sections`. Es la **capa de esquema**: define qué campos
existe en cada tipo de bloque, no qué páginas lo usan (eso está en el archivo de cada sitio) ni
qué valor tiene (eso vive en el seeder y en la BD).

Identificadores de campo en inglés; los encabezados y las notas en español.

## Cómo leer las tablas

- **campo** — la clave dentro del JSON de `sections.content`.
- **tipo** — `string`, `text`, `html`, `url`, `media`, `decimal`, `bool`. `[]` marca una
  **colección repetible**: el admin agrega, quita y reordena ítems dentro de ella.
- **editable** — `sí` = el admin cambia el valor desde la API admin. `fijo` = solo el equipo de
  desarrollo lo cambia, en el blueprint.

**Casi todos los campos son `editable`, y eso es correcto.** `fijo` marca únicamente lo que está
atado a código o al esquema de la base de datos, donde un cambio del admin rompería algo en
silencio. No es una etiqueta para lo que preferiríamos que nadie tocara. En todo el catálogo hay
solo tres grupos `fijo`, y cada uno declara su motivo.

## Prohibido: overrides por página

Una página **no** puede redefinir los campos de un tipo. Si un campo aplica en una página y no en
otra, el campo es *nullable* en el tipo y esa instancia lo deja vacío. Si una página necesita una
forma genuinamente distinta, es **otro tipo**.

Un mecanismo de override obligaría a definir reglas de merge (profundo o superficial, si puede
*quitar* un campo, si puede voltear `fijo` → `editable`) y un resolver en PHP: un motor de herencia
de esquemas para un sitio de marketing de 17 páginas. Las celdas de **notas** de los mapas de sitio
son descriptivas, nunca normativas — la prueba es *«¿se puede borrar esta nota sin cambiar lo que
se construye?»*. Si la respuesta es no, está en el lugar equivocado.

---

## Tipos genéricos

Cubren la mayoría de las páginas de contenido por reuso. 11 de las 17 páginas de GNU se arman
únicamente con estos tres tipos.

### `page_hero`

Encabezado de página. Una sola instancia por página. La portada (`/`) **no** lo lleva: ahí el
`banner_carousel` es el hero.

| campo | tipo | editable | notas |
|---|---|---|---|
| `title` | string | sí | H1 de la página |
| `subtitle` | text | sí | nullable |
| `image` | media | sí | nullable; imagen de fondo |

### `rich_text`

Bloque de contenido libre. **Puede repetirse** en una misma página; cuando eso pasa, cada instancia
necesita su `key` en el mapa de la página.

| campo | tipo | editable | notas |
|---|---|---|---|
| `title` | string | sí | nullable |
| `body` | html | sí | |
| `video_url` | url | sí | nullable |

`video_url` absorbe el requisito transversal de «soporte para videos» de los 4 sitios: no existe un
tipo `video_embed`. Una sección de solo video es un `rich_text` con `body` vacío.

### `feature_grid`

Rejilla de tarjetas. Cubre valores, beneficios de un servicio, certificaciones e iniciativas.

| campo | tipo | editable | notas |
|---|---|---|---|
| `title` | string | sí | nullable |
| `items[]` | colección | sí | el admin agrega, quita y reordena tarjetas |
| `items[].icon` | media | sí | nullable |
| `items[].title` | string | sí | |
| `items[].text` | text | sí | |

---

## Tipos compartidos entre sitios

### `banner_carousel`

Presente en los 4 sitios. Una instancia por página.

| campo | tipo | editable | notas |
|---|---|---|---|
| `slides[]` | colección | sí | el admin agrega, quita y reordena slides |
| `slides[].image` | media | sí | |
| `slides[].title` | string | sí | nullable |
| `slides[].subtitle` | text | sí | nullable |
| `slides[].link_url` | url | sí | nullable |
| `slides[].link_label` | string | sí | nullable |

Sin `autoplay_seconds`: el ritmo del carrusel es decisión del front-end, no contenido.

### `savings_simulator`

GNU, GNU Vehicular y Traninn Logistic. No Tectuus.

| campo | tipo | editable | notas |
|---|---|---|---|
| `title` | string | sí | |
| `intro` | text | sí | nullable |
| `fuel_prices[]` | colección | sí | referencias de precio para el comparativo |
| `fuel_prices[].fuel` | string | sí | identificador en slug: `gas-natural`, `diesel`, `gasolina` |
| `fuel_prices[].label` | string | sí | nombre visible, ej. «Diésel» |
| `fuel_prices[].price_per_unit` | decimal | sí | |
| `fuel_prices[].unit` | string | sí | ej. litro, m³ |
| `fuel_prices[].equivalence_factor` | decimal | sí | unidades de gas natural equivalentes a 1 unidad de este combustible |

El cálculo del ahorro es código, no un campo. Lo que el admin mantiene son los precios de
referencia, que sí cambian seguido.

La fila con `fuel = gas-natural` es el precio del gas natural que vende la empresa y la base de
todo comparativo; su `equivalence_factor` es 1. El cálculo lo sirve
`GET /api/public/{site}/savings-simulator?fuel={slug}&quantity={n}`.

**Cada instancia guarda sus propios precios.** Un sitio puede llevar varias — `gnu` tiene una en `/`
y otra en `/nuestros-servicios/gnc` — y el endpoint elige cuál con `&page={slug}`; sin ese parámetro
devuelve la primera del sitio, que es la de la portada. Se resolvió así en vez de mover el bloque a
sección global porque un simulador ocupa una **posición dentro de la página**, y las secciones
globales no la tienen.

### `clients_showcase`

«Nuestros clientes». GNU, Traninn Logistic y Tectuus.

| campo | tipo | editable | notas |
|---|---|---|---|
| `title` | string | sí | nullable |
| `logos[]` | colección | sí | el admin agrega, quita y reordena logos |
| `logos[].image` | media | sí | |
| `logos[].name` | string | sí | |
| `logos[].url` | url | sí | nullable |

### `testimonials`

GNU y GNU Vehicular.

| campo | tipo | editable | notas |
|---|---|---|---|
| `title` | string | sí | nullable |
| `items[]` | colección | sí | el admin agrega, quita y reordena testimonios |
| `items[].quote` | text | sí | |
| `items[].author` | string | sí | |
| `items[].role` | string | sí | nullable |
| `items[].photo` | media | sí | nullable |

### `lead_capture_form`

Presente en los 4 sitios. Es la única escritura pública de la API
(`POST /api/public/{site}/leads`).

| campo | tipo | editable | notas |
|---|---|---|---|
| `title` | string | sí | |
| `intro` | text | sí | nullable |
| `fields[]` | colección | **fijo** | ver motivo abajo |
| `submit_label` | string | sí | texto del botón |

**`fields[]` es `fijo`** porque cada campo del formulario corresponde a una columna de la tabla
`leads`. Si el admin agregara un campo, el dato llegaría al endpoint y se descartaría en silencio.
Cambiar el formulario es un cambio de esquema, y pasa por desarrollo.

El destinatario de la notificación no vive aquí: es configuración del sitio, no contenido de la
sección.

### `stations_map`

GNU y GNU Vehicular.

| campo | tipo | editable | notas |
|---|---|---|---|
| `title` | string | sí | nullable |
| `center` | string | **fijo** | coordenadas del centro inicial |
| `zoom` | string | **fijo** | nivel de zoom inicial |

**`center` y `zoom` son `fijo`** porque son configuración de layout sin significado de marketing.

Las estaciones **no** son campos de esta sección: vienen de la tabla `stations`, que el admin
gestiona por separado (`StationController`). La sección solo dice *dónde se dibuja el mapa*.

### `social_icons`

Presente en los 4 sitios. En GNU es una sección global (todo el sitio), no de una página.

| campo | tipo | editable | notas |
|---|---|---|---|
| `networks[]` | colección | sí | el admin agrega y quita redes |
| `networks[].network` | string | **fijo** | identificador de la red |
| `networks[].url` | url | sí | perfil del sitio en esa red |

**`network` es `fijo`** porque está atado a los assets de ícono del front-end. Si el admin
escribiera `mastodon`, no habría ícono que renderizar. El admin sí controla la URL de cada red y
qué redes se muestran, eligiendo de la lista que el front soporta.

### `contact_info`

Datos de contacto de la empresa. Sección global (todo el sitio), igual que `social_icons`.
Las redes sociales **no** viven aquí: son `social_icons`, para no duplicar el mismo campo en dos
tipos.

| campo | tipo | editable | notas |
|---|---|---|---|
| `address` | text | sí | |
| `phone` | string | sí | |
| `email` | string | sí | |
| `schedule` | text | sí | nullable; horario de atención |
| `additional_info` | text | sí | nullable |

### `whatsapp_button`

Presente en los 4 sitios, siempre como contacto directo de marketing. Sección global.

| campo | tipo | editable | notas |
|---|---|---|---|
| `phone` | string | sí | número en formato internacional |
| `prefilled_message` | text | sí | nullable; texto precargado del chat |

Sin `position`: dónde flota el botón es layout.

---

## Tipos exclusivos de un sitio — sin verificar

Declarados en el macro archivo original pero **no auditados todavía**. Los cuatro tienen el mismo
olor a «página disfrazada de bloque» que tenían `certifications` y `esr_ecovadis`, que resultaron
ser páginas de GNU y se eliminaron de este catálogo. No agregarlos como tipos hasta revisar el
sitio correspondiente con el mismo criterio que se aplicó a GNU.

| candidato | sitio | qué es en el archivo original |
|---|---|---|
| `product_catalog` | Tectuus | EDRM, compresoras, valvulería |
| `equipment_quote` | GNU Vehicular | «cotiza tu equipo» |
| `financing` | GNU Vehicular | financiamiento |
| `service_scheduling` | GNU Vehicular | «agenda tu verificación» |

## Descartados, con motivo

Para que no vuelvan a proponerse:

| descartado | motivo |
|---|---|
| `video_embed` | El video es un requisito transversal, no una sección. Es el campo `video_url` de `rich_text`. |
| `blog` | `GET /api/public/{site}/blog` ya existe en `routes/public.php`. El blog es dominio de datos (`blog_posts`), no un tipo de sección. |
| `child_index` | Tendría cero campos editables: la lista de páginas hijas ya sale de `GET /api/public/{site}/menu` y su orden es derivable del mapa de páginas. Agregarlo solo si una página padre necesita esa lista en una posición que no sea la última. |
| `cta_band` | Ninguna página de GNU pide un llamado a la acción que no sea `lead_capture_form`. |
| `billing_info` | «Facturación» es hijo de *Contacto* en GNU Vehicular, así que es sección; y son instrucciones más una liga al portal, o sea contenido libre. Se resuelve con el `rich_text` de `/contacto`. |
| `certifications`, `esr_ecovadis` | Son páginas de GNU (`/sobre-nosotros/certificaciones`, `/desarrollo-sostenible/distintivo-esr`, `/desarrollo-sostenible/ecovadis`), no bloques. Se resuelven con `page_hero` + `feature_grid` / `rich_text`. |
