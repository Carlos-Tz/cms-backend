---
name: sitios-web-gnu
description: Blueprint de contenido predefinido de los 4 sitios web publicitarios que administra este Headless CMS (GNU institucional, GNU Gas Natural Vehicular, Traninn Logistic, Tectuus). Define qué páginas existe cada sitio, qué secciones lleva cada página en qué orden, qué campos tiene cada tipo de sección y cuáles de esos campos puede editar el administrador. Usa esta skill SIEMPRE que necesites crear o revisar migraciones, seeders, blueprints, rutas, menús de navegación, tipos de bloques de contenido (sections), endpoints de páginas o secciones, o cualquier estructura que dependa de qué contenido tiene un sitio. Consúltala antes de decidir si algo es una página o una sección, si un bloque es compartido o exclusivo de un sitio, y si un campo lo edita el admin o solo desarrollo.
---

# Blueprint de contenido — GNU Gas Natural 2025

Fuente de verdad del **contenido predefinido** de los 4 sitios que administra el CMS: qué páginas
existen, qué secciones lleva cada una y qué campos son editables.

Este CMS **no es un page-builder**. El equipo de desarrollo predefine toda la estructura; el
administrador solo actualiza valores. Esa restricción es el diseño, no una limitación temporal —
ver «Contrato de editabilidad» abajo.

## Las tres capas

Separadas a propósito, para no repetir la definición de campos en cada página:

| Capa | Qué define | Dónde vive | Quién la cambia |
|---|---|---|---|
| **Esquema** | Qué campos tiene cada tipo de sección | `references/section-types.md` | Desarrollo |
| **Instancias** | Qué páginas hay y qué secciones lleva cada una, en orden | `references/{sitio}.md` | Desarrollo |
| **Valores** | El contenido real | seeder → BD | Admin, solo campos `editable` |

## Contrato de editabilidad

**Define el equipo de desarrollo** (en el blueprint; inmutable desde la API admin):

- qué páginas existen y cuál es su ruta
- qué secciones tiene cada página y en qué orden
- qué campos tiene cada tipo de sección, con su tipo de dato
- qué campos son `fijo`

**Puede hacer el administrador** (API admin):

- editar el valor de los campos marcados `editable`
- agregar, quitar y reordenar **ítems dentro de una colección** — un slide del carrusel, un
  testimonio, un logo de cliente, un precio de referencia del simulador
- publicar y despublicar una página
- gestionar el contenido de dominio que vive en sus propias tablas: `blog_posts`, `stations`,
  `leads`, `media`

**No puede hacer el administrador:**

- crear ni borrar páginas
- agregar, quitar ni reordenar **secciones** de una página
- cambiar el `type` de una sección
- editar campos `fijo`

La línea está entre **datos** y **estructura**: los ítems de una colección son datos y el admin los
gestiona; las secciones de una página son estructura y no las toca.

Un detalle que hay que decir en voz alta: **casi todos los campos son `editable`, y eso es
correcto.** `fijo` marca solo lo atado a código o al esquema de la BD, donde un cambio del admin
rompería algo en silencio. En todo el catálogo hay dos grupos `fijo`, cada uno con su motivo
documentado. Si aparece un tercero sin justificación, sobra.

## Resumen comparativo

| Sitio | Slug | Columnas de nav. principal | Blog | Simulador de ahorro | Mapa/ubicaciones |
|---|---|---|---|---|---|
| GNU (institucional) | `gnu` | Inicio, Nuestros servicios, Sobre nosotros, Desarrollo sostenible, Contacto, Blog | Sí | Sí | Sí (mapa de estaciones) |
| GNU Gas Natural Vehicular | `gnu-vehicular` | Inicio, Nuestros servicios, Ubicaciones, Contacto, Blog | Sí | Sí | Sí (mapa de estaciones + ubicaciones específicas) |
| Traninn Logistic | `traninn-logistic` | Inicio, Nuestros servicios, Sobre nosotros, Contacto, Ubicaciones | No | Sí | Sí (mapa de bases en Ubicaciones) |
| Tectuus | `tectuus` | Inicio, Sobre nosotros, Productos, Contacto | No | No | No |

## Catálogo de tipos de sección

Los `type` válidos de la tabla `sections`. **Las tablas de campos de cada uno están en
`references/section-types.md`** — ese archivo es la definición; esta lista es solo el índice.

Genéricos, cubren la mayoría de las páginas de contenido por reuso:

- `page_hero` — encabezado de página. Una instancia por página.
- `rich_text` — contenido libre; incluye `video_url`, que absorbe el requisito transversal de
  video. Puede repetirse en una misma página.
- `feature_grid` — rejilla de tarjetas (valores, beneficios, certificaciones, iniciativas).
- `image_gallery` — rejilla de fotografías con pie de foto y texto alternativo.

Compartidos entre sitios:

- `banner_carousel` — los 4 sitios.
- `whatsapp_button` — los 4 sitios, siempre como contacto directo de marketing.
- `social_icons` — los 4 sitios.
- `contact_info` — sección global con los datos de contacto de la empresa.
- `lead_capture_form` — los 4 sitios («captación de leads»).
- `savings_simulator` — GNU, GNU Vehicular, Traninn Logistic. No Tectuus.
- `testimonials` — GNU, GNU Vehicular.
- `clients_showcase` («nuestros clientes») — GNU, Traninn Logistic, Tectuus. Cada logo puede
  llevar descripción y un testimonio propio, ambos nullable.
- `stations_map` — GNU, GNU Vehicular y Traninn Logistic.

- `service_scheduling` — solo GNU Vehicular. Único tipo exclusivo de un sitio.

**Ya no quedan candidatos sin auditar.** De los 5 que declaraba el macro archivo, `equipment_quote`,
`financing` y `billing_info` resultaron bloques genéricos, `product_catalog` resultó dominio de
datos, y solo `service_scheduling` llegó al catálogo.

`video_support`, `blog` y `product_catalog` **no son tipos de sección**: el primero es el campo
`video_url` de `rich_text`; los otros dos son dominio de datos (`blog_posts`, y
`product_categories` + `products`) con sus rutas públicas ya definidas en `routes/public.php`. Los
motivos completos, y los tipos descartados, están al final de `references/section-types.md`.

## Requisitos técnicos transversales (los 4 sitios)

- Carrusel de banners.
- Soporte para videos embebidos.
- Botón de WhatsApp para contacto directo de marketing.
- Íconos de redes sociales.
- Captación de leads mediante formularios.

Requisito adicional en GNU, GNU Vehicular y Traninn Logistic (no Tectuus):
- Simulador de ahorro (comparativo de costos frente a otros combustibles).

## Archivos de referencia

- `references/section-types.md` — **el catálogo de campos**. Leerlo antes de tocar cualquier
  estructura de secciones.
- `references/implementation-status.md` — qué tipos del catálogo ya son editables desde la API y
  cuáles no, y qué páginas están realmente sembradas. Leerlo antes de asumir que un tipo funciona.
- `references/gnu.md` — sitio institucional GNU. **Ya migrado al formato de blueprint**: mapa de 17
  páginas, secciones globales, y la clasificación de los nodos del árbol original.
- `references/gnu-vehicular.md` — GNU Gas Natural Vehicular.
- `references/traninn-logistic.md` — Traninn Logistic. **Migrado completo**: las 5 columnas, 11 páginas.
- `references/tectuus.md` — Tectuus. **Migrado completo**: las 4 columnas, 4 páginas, más las 3
  categorías del catálogo de productos.

Para todo lo que sea backend —endpoints, entidades que no son secciones, forma de las respuestas, y
qué está implementado— la skill es **`cms-gnu-backend`**.

**Los 4 sitios están migrados y sembrados completos.** El árbol crudo del cliente queda en cada
archivo solo como apéndice de trazabilidad.

La única decisión de diseño que quedaba abierta —la columna *Productos* de `tectuus`— está resuelta:
EDRM, Compresoras y Valvulería **no son páginas ni un `product_catalog`**, son filas de
`product_categories`, porque el cliente pidió gestionarlas de forma dinámica. `/productos` es una
página con solo `page_hero`, calcada de `/blog`. Ver `references/tectuus.md`.
