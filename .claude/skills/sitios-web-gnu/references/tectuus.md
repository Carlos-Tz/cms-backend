# Tectuus — `tectuus`

Sitio más simple de los 4: sin blog, sin simulador de ahorro, enfocado en catálogo de producto
industrial.

Blueprint de contenido predefinido. **Migrado parcial**: solo la columna *Inicio* y las secciones
globales están en formato blueprint y sembradas por `TectuusBlueprintSeeder`. Las columnas *Sobre
nosotros*, *Productos* y *Contacto* siguen en el árbol crudo del apéndice.

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
| 1 | `/` | Inicio | — | 1 | `banner_carousel`, `rich_text`, `clients_showcase`, `lead_capture_form` |

**Sembrada: 1 columna de 4, 1 página.** El estado por tipo vive en
[`implementation-status.md`](implementation-status.md).

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
- **El `lead_capture_form` no declara extras**: los cuatro campos de siempre, que son columnas de
  `leads`. Cuando se migre la columna *Contacto*, su formulario será el segundo del sitio y
  `leads.source` es lo que los separa en la bandeja.
- **La columna *Productos* está pendiente y es la única decisión de diseño real que queda.** EDRM,
  Compresoras y Valvulería pueden ser tres páginas hijas o un `product_catalog`, el último candidato
  de tipo sin auditar del catálogo — ver [`section-types.md`](section-types.md), «Tipos exclusivos de
  un sitio». No promoverlo a tipo sin auditar la columna con el criterio de [`gnu.md`](gnu.md).

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
