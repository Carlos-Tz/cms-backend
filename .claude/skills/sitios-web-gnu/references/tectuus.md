# Tectuus — `tectuus`

Sitio más simple de los 4: sin blog, sin simulador de ahorro, enfocado en catálogo de producto
industrial.

Blueprint de contenido predefinido. **Migrado completo**: las 4 columnas, sus 4 páginas y las
secciones globales están en formato blueprint y sembradas por `TectuusBlueprintSeeder`, más las 3
categorías de producto. El árbol crudo del apéndice queda solo como trazabilidad.

Los campos de cada tipo de sección están en [`section-types.md`](section-types.md); los valores viven
en el seeder y en la BD.

## Requisitos principales
- Carrusel de banners.
- Soporte para videos.
- Botón de WhatsApp — contacto directo de marketing.

## Mapa de páginas

Las columnas de navegación son 4: Inicio (1), Sobre nosotros (2), Productos (3), Contacto (4). El
sitio **no tiene blog**.

| # | ruta | título | padre | pos | secciones (en orden) |
|---|---|---|---|---|---|
| 1 | `/` | Inicio | — | 1 | `banner_carousel`, `trust_strip`, `rich_text`, `clients_showcase`, `stats_band`, `image_text`, `lead_capture_form` |
| 2 | `/sobre-nosotros` | Sobre nosotros | — | 2 | `page_hero`, `rich_text`, `image_gallery` |
| 3 | `/productos` | Productos | — | 3 | `page_hero` |
| 4 | `/contacto` | Contacto | — | 4 | `page_hero`, `lead_capture_form` |

**Sembradas: 4 columnas de 4, 4 páginas, sin hijas.** El estado por tipo vive en
[`implementation-status.md`](implementation-status.md).

## Catálogo de productos — dominio de datos, no secciones

La columna *Productos* es la única del blueprint que no se resuelve solo con secciones.

EDRM, Compresoras y Valvulería **no son páginas hijas ni un tipo `product_catalog`**: son filas de
`product_categories`, y los productos filas de `products`. El criterio es el del contrato de
editabilidad — el cliente pidió agregarlas, editarlas y eliminarlas, o sea son **datos**, y la
estructura no se toca. Precedente literal: `/blog` lleva solo `page_hero` y los artículos son filas
de `blog_posts`.

| capa | dónde vive |
|---|---|
| la página `/productos` | `pages`, con su único `page_hero` |
| las 3 categorías | `product_categories`, sembradas: `edrm` (1), `compresoras` (2), `valvuleria` (3) |
| los productos | `products`, cargados por el admin — el seeder no siembra ninguno |
| las rutas de categoría y de detalle | del front, servidas por la API pública; no son filas de `pages` |

Endpoints: `GET /api/public/tectuus/product-categories`,
`GET …/products?category={slug}&q={búsqueda}` y `GET …/products/{productSlug}`. En la API admin son
dos `apiResource` con CRUD completo, como el blog.

**Las categorías no salen en `GET /api/public/tectuus/menu`**, porque el menú se deriva de `pages` y
ellas no son páginas. El front arma ese submenú con `product-categories`. Es la misma renuncia que
ya se aceptó con los artículos del blog, y la contrapartida de que el admin pueda crear categorías
sin tocar estructura.

Los campos y las decisiones de esquema de las dos tablas están en
`cms-gnu-backend/references/domain-entities.md`.

Notas de lectura:

- **`/` no lleva `page_hero`**: el `banner_carousel` es el hero de la portada, igual que en los otros
  tres sitios.
- **Los tres hijos de *Inicio* son secciones, no páginas.** Es la regla 1 de clasificación de
  [`gnu.md`](gnu.md): bajo *Inicio* el cliente listó el contenido de la página, no hijas. «Contacto»
  ahí es el `lead_capture_form` de la portada, distinto de la columna *Contacto*.
- **El `rich_text` de la portada no está en el árbol**: absorbe el requisito «soporte para videos»,
  que el cliente declaró en «Requisitos principales» y no en el árbol. `video_url` de `rich_text` es
  el único lugar del catálogo donde ese requisito aterriza. Mismo criterio que en los otros tres
  sitios.
- **Sin `savings_simulator`**: es el único de los 4 sitios que no declara simulador de ahorro, así
  que `GET /api/public/tectuus/savings-simulator` responde 404. No es un hueco, es el blueprint.
- **Sin `stations_map`**: el sitio no tiene ubicaciones ni mapa; tampoco filas de `stations`.
- **Sin `testimonials`**: ese tipo está declarado para `gnu` y `gnu-vehicular`, no para este sitio.
  Si aparecen testimonios, van dentro de `clients_showcase`, atados al cliente que los dijo.
- **El `clients_showcase` de la portada es un muro de logos**: deja vacíos `description`, `quote` y
  `quote_author`, los tres campos nullable del tipo.
- **Ningún `lead_capture_form` declara extras**: los cuatro campos de siempre, que son columnas de
  `leads`. El sitio tiene **dos** formularios, el de `/` y el de `/contacto`, y `leads.source` es lo
  que los separa en la bandeja.
- **Ninguna de las 4 columnas tiene páginas hijas.** *Sobre nosotros* y *Contacto* cuelgan un solo
  nodo en el árbol del cliente —«¿Quiénes somos?» y «Captación de leads»— y en ambos casos ese nodo
  es el contenido de la cabeza de columna, no una hija: es la regla 1 de [`gnu.md`](gnu.md), la
  misma lectura que *Contacto* en [`traninn-logistic.md`](traninn-logistic.md). Los tres nodos de
  *Productos* son categorías, no páginas.
- **`/sobre-nosotros` lleva el video y las fotos de la empresa**: el requisito de «imágenes y
  videos» de esa columna aterriza en `video_url` de su `rich_text` y en su `image_gallery`, no en
  tipos nuevos.

## Secciones globales

Filas de `sections` con `page_id = null`. Son los tres requisitos transversales declarados para los
4 sitios.

| tipo | ámbito | notas |
|---|---|---|
| `contact_info` | todo el sitio | No está en el árbol original; datos de contacto de la empresa. |
| `social_icons` | todo el sitio | En el árbol original está fuera del flujo principal. |
| `whatsapp_button` | todo el sitio | Solo en «Requisitos principales», no en el árbol. |

## Apéndice — árbol de navegación original

Tal como fue entregado en el macro archivo de sitios web (GNU Gas Natural 2025). **No editar**: es el
único vínculo de trazabilidad con el documento del cliente.

**Íconos de redes sociales** (columna global, fuera del flujo principal)

**Inicio**
- Carrusel de banners
- Nuestros clientes
- Contacto

**Sobre nosotros**
- ¿Quiénes somos?

**Productos**
- EDRM
- Compresoras
- Valvulería

**Contacto**
- Captación de leads
