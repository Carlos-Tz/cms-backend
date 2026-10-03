# Traninn Logistic — `traninn-logistic`

Blueprint de contenido predefinido. **Migrado completo**: las cinco columnas —*Inicio*,
*Nuestros servicios*, *Sobre nosotros*, *Contacto* y *Ubicaciones*— más las secciones globales están
en formato blueprint y sembradas por `TraninnLogisticBlueprintSeeder`.

Los campos de cada tipo de sección están en [`section-types.md`](section-types.md); los valores viven
en el seeder y en la BD.

## Requisitos principales
- Carrusel de banners.
- Simulador de ahorro.
- Soporte para videos.
- Botón de WhatsApp — contacto directo de marketing.

## Mapa de páginas

Las columnas de navegación son 5: Inicio (1), Nuestros servicios (2), Sobre nosotros (3),
Contacto (4), Ubicaciones (5). El sitio **no tiene blog**.

| # | ruta | título | padre | pos | secciones (en orden) |
|---|---|---|---|---|---|
| 1 | `/` | Inicio | — | 1 | `banner_carousel`, `trust_strip`, `savings_simulator`, `rich_text`, `clients_showcase`, `stats_band`, `image_text`, `lead_capture_form` |
| 2 | `/nuestros-servicios` | Nuestros servicios | — | 2 | `page_hero`, `rich_text` |
| 3 | `/nuestros-servicios/transporte-de-gnc` | Transporte de GNC | `/nuestros-servicios` | 1 | `page_hero`, `rich_text`, `feature_grid` |
| 4 | `/nuestros-servicios/logistica` | Logística | `/nuestros-servicios` | 2 | `page_hero`, `rich_text`, `feature_grid` |
| 5 | `/nuestros-servicios/carga-pesada` | Carga pesada | `/nuestros-servicios` | 3 | `page_hero`, `rich_text`, `feature_grid` |
| 6 | `/sobre-nosotros` | Sobre nosotros | — | 3 | `page_hero`, `rich_text`, `clients_showcase` |
| 7 | `/sobre-nosotros/que-hacemos` | ¿Qué hacemos? | `/sobre-nosotros` | 1 | `page_hero`, `banner_carousel`, `rich_text`, `image_gallery` |
| 8 | `/sobre-nosotros/proposito-empresarial` | Propósito empresarial | `/sobre-nosotros` | 2 | `page_hero`, `rich_text`, `image_gallery` |
| 9 | `/sobre-nosotros/nuestra-historia` | Nuestra historia | `/sobre-nosotros` | 3 | `page_hero`, `banner_carousel`, `rich_text`, `image_gallery` |
| 10 | `/contacto` | Contacto | — | 4 | `page_hero`, `lead_capture_form` |
| 11 | `/ubicaciones` | Ubicaciones | — | 5 | `page_hero`, `stations_map` |

**Sembradas: las 5 columnas, 11 páginas.** El estado por tipo vive en
[`implementation-status.md`](implementation-status.md).

Notas de lectura:

- **`/` no lleva `page_hero`**: el `banner_carousel` es el hero de la portada, igual que en `gnu` y
  en `gnu-vehicular`.
- **Los cuatro hijos de *Inicio* son secciones, no páginas.** Es la regla de clasificación de
  [`gnu.md`](gnu.md): bajo *Inicio* y *Contacto* el cliente listó contenido de la página, no hijas.
  «Contacto» ahí es el `lead_capture_form` de la portada, distinto de la columna *Contacto*.
- **El `rich_text` de la portada no está en el árbol**: absorbe el requisito «soporte para videos»,
  que el cliente declaró en «Requisitos principales» y no en el árbol. `video_url` de `rich_text` es
  el único lugar del catálogo donde ese requisito aterriza. Mismo criterio que en `gnu`.
- **El `savings_simulator` guarda sus propios precios**, cotizados por m³ de gas transportado: este
  sitio vende flete, no conversiones. Como es la única instancia del sitio,
  `GET /api/public/traninn-logistic/savings-simulator` la resuelve sin el parámetro `page`.
- ***Ubicaciones* lleva mapa, y esa forma la decidió el blueprint, no el cliente.** Es la única
  columna que el árbol entregó sin declarar nada adentro, así que se resolvió como en `gnu` y
  `gnu-vehicular`: `page_hero` + `stations_map`, con los pines saliendo de la tabla `stations`. El
  seeder deja dos bases placeholder (Morelia y Uruapan); las reales las carga el admin por
  `POST /api/admin/stations`. Son bases de operación, no estaciones de carga: la tabla es la misma,
  el significado lo pone el sitio.
- **Las tres páginas de servicios son informativas: ninguna capta leads.** El formulario vive en la
  portada y en la columna *Contacto*. Mismo criterio que *Estaciones móviles* en
  `gnu-vehicular`. La columna tampoco lleva `clients_showcase`, a diferencia de la equivalente de
  `gnu`: en este árbol *Nuestros clientes* cuelga de *Inicio* y de *Sobre nosotros*.
- **Sin `testimonials`**: ese tipo está declarado para `gnu` y `gnu-vehicular`, no para este sitio.
- **Sin blog**: no hay `/blog` ni filas de `blog_posts`.
- **«Nuestros clientes» bajo *Sobre nosotros* es una sección, no una cuarta página hija.** Es la
  excepción de la regla 3 de clasificación de [`gnu.md`](gnu.md): su nombre coincide con un tipo del
  catálogo. Queda como `clients_showcase` de la cabeza de columna, igual que el nodo homónimo bajo
  *Nuestros servicios* en `gnu`.
- **El sitio lleva dos `clients_showcase`**, en `/` y en `/sobre-nosotros`, y cada instancia guarda
  sus propios valores. El de la portada es un muro de logos; el de *Sobre nosotros* llena además
  `description`, `quote` y `quote_author`, los tres campos nullable que se agregaron al tipo.
- **Las páginas hijas llevan `page_hero` junto con `banner_carousel`.** No se contradicen: el `page_hero`
  es el H1 de la página y el carrusel es un bloque de contenido. La regla «el carrusel es el hero»
  aplica solo a `/`, que por eso no lleva `page_hero`.
- **`image_gallery` nació con esta columna.** El cliente pidió «imágenes» aparte de los banners en
  tres de sus páginas, y no había tipo que lo cubriera sin incrustar HTML a mano.
- **Ninguno de los dos `lead_capture_form` declara extras**: los cuatro campos de siempre, que son
  columnas de `leads`. El sitio tiene dos formularios —`/` y `/contacto`—, así que `leads.source`
  (`inicio` o `contacto`) es lo que los separa en la bandeja.
- **`/contacto` no lleva `stations_map`**, a diferencia de las páginas de contacto de `gnu` y
  `gnu-vehicular`: en este sitio el mapa es exclusivo de `/ubicaciones`. La columna cubre el único
  nodo que declaró el cliente —«Captación de leads»— más el hero que llevan todas las páginas.

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
- Simulador de ahorro
- Nuestros clientes
- Contacto

**Nuestros servicios**
- Transporte de GNC
- Logística
- Carga pesada

**Sobre nosotros**
- ¿Qué hacemos?
- Propósito empresarial
- Nuestra historia
- Nuestros clientes

**Contacto**
- Captación de leads

**Ubicaciones** (columna final, sin subsecciones declaradas en el macro archivo original)
