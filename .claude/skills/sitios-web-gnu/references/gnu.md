# GNU (institucional) — `gnu`

Blueprint de contenido predefinido. Es la **capa de instancias**: qué páginas existen, cuáles son
sus rutas y qué secciones lleva cada una, en orden. Los campos de cada tipo de sección están en
[`section-types.md`](section-types.md); los valores viven en el seeder y en la BD.

El administrador no crea páginas ni cambia esta estructura — solo edita los campos marcados
`editable` en el catálogo. Ver «Contrato de editabilidad» en `SKILL.md`.

## Requisitos principales
- Carrusel de banners.
- Simulador de ahorro.
- Soporte para videos.
- Botón de WhatsApp — marketing.
- Enfoque prioritario a GNC, destacando ahorro y mapa de sucursales.
- Después de la columna "Inicio", presentar "Nuestros servicios" para que el cliente vea la oferta
  de forma inmediata (orden de navegación con intención comercial).

El enfoque prioritario a GNC aterriza en `/nuestros-servicios/gnc`, la única página con
`savings_simulator` y `stations_map` juntos. La intención comercial del orden de navegación aterriza
en `pos = 2` de `/nuestros-servicios`.

## Mapa de páginas

17 páginas. `padre` vacío = página raíz, y su `pos` ordena las columnas de la navegación
principal; en una página hija la columna *es* el padre. El orden de las secciones es el orden de la
lista.

| # | ruta | título | padre | pos | secciones (en orden) |
|---|---|---|---|---|---|
| 1 | `/` | Inicio | — | 1 | `banner_carousel`, `savings_simulator`, `rich_text`, `clients_showcase`, `lead_capture_form` |
| 2 | `/nuestros-servicios` | Nuestros servicios | — | 2 | `page_hero`, `rich_text`, `clients_showcase` |
| 3 | `/nuestros-servicios/gnc` | GNC | `/nuestros-servicios` | 1 | `page_hero`, `rich_text`, `feature_grid`, `savings_simulator`, `stations_map`, `lead_capture_form` |
| 4 | `/nuestros-servicios/gnd` | GND | `/nuestros-servicios` | 2 | `page_hero`, `rich_text`, `feature_grid`, `lead_capture_form` |
| 5 | `/nuestros-servicios/gnv` | GNV | `/nuestros-servicios` | 3 | `page_hero`, `rich_text`, `feature_grid`, `lead_capture_form` |
| 6 | `/sobre-nosotros` | Sobre nosotros | — | 3 | `page_hero`, `rich_text` |
| 7 | `/sobre-nosotros/proposito-empresarial` | Propósito empresarial | `/sobre-nosotros` | 1 | `page_hero`, `rich_text`, `feature_grid` |
| 8 | `/sobre-nosotros/valores` | Valores | `/sobre-nosotros` | 2 | `page_hero`, `feature_grid` |
| 9 | `/sobre-nosotros/certificaciones` | Certificaciones | `/sobre-nosotros` | 3 | `page_hero`, `feature_grid` |
| 10 | `/sobre-nosotros/nuestra-historia` | Nuestra historia | `/sobre-nosotros` | 4 | `page_hero`, `rich_text` |
| 11 | `/desarrollo-sostenible` | Desarrollo sostenible | — | 4 | `page_hero`, `rich_text` |
| 12 | `/desarrollo-sostenible/distintivo-esr` | Distintivo ESR | `/desarrollo-sostenible` | 1 | `page_hero`, `rich_text` |
| 13 | `/desarrollo-sostenible/ecovadis` | EcoVadis | `/desarrollo-sostenible` | 2 | `page_hero`, `rich_text` |
| 14 | `/desarrollo-sostenible/voluntariado` | Voluntariado | `/desarrollo-sostenible` | 3 | `page_hero`, `rich_text` |
| 15 | `/desarrollo-sostenible/iniciativas-rse` | Iniciativas de RSE | `/desarrollo-sostenible` | 4 | `page_hero`, `feature_grid` |
| 16 | `/contacto` | Contacto | — | 5 | `page_hero`, `lead_capture_form`, `stations_map` |
| 17 | `/blog` | Blog | — | 6 | `page_hero` |

**Sembradas: 17 de 17.** El estado por tipo vive en
[`implementation-status.md`](implementation-status.md).

Notas de lectura:

- **`/` no lleva `page_hero`**: el `banner_carousel` es el hero de la portada.
- **El `rich_text` de `/` es el bloque de video de la portada.** «Soporte para videos» es un
  requisito transversal que aterriza en `rich_text.video_url`, y sin este bloque la página más
  visitada del sitio no tendría dónde poner uno. Su `body` sirve de contenido introductorio.
- **`/blog` solo lleva `page_hero`**: el listado de artículos lo sirve
  `GET /api/public/gnu/blog`, y cada artículo `GET /api/public/gnu/blog/{postSlug}`. Los artículos
  son filas de `blog_posts`, no páginas de este blueprint.
- **Ninguna página de GNU repite un tipo de sección**, así que ninguna instancia necesita `key`
  todavía. Cuando una página lleve dos `rich_text`, cada uno necesita su `key` para distinguirlos.
- 11 de las 17 páginas se arman solo con tipos genéricos (`page_hero`, `rich_text`,
  `feature_grid`). Eso es la señal de que conviene
  mantener el catálogo de tipos chico y reutilizarlo.
- **`/sobre-nosotros/proposito-empresarial` lleva `feature_grid` además del `rich_text`**: Misión,
  Visión y Propósito son tarjetas estructuradas, no encabezados dentro del HTML. Decisión del
  cliente al sembrar la columna «Sobre nosotros»; el `rich_text` queda como intro.

## Secciones globales

Presentes en todo el sitio, no en una página. Al sembrar son filas de `sections` con
`page_id = null`.

| tipo | ámbito | notas |
|---|---|---|
| `contact_info` | todo el sitio | No está en el árbol original: los datos de contacto de la empresa (dirección, teléfono, correo, horario) se pidieron aparte y son transversales al sitio. |
| `social_icons` | todo el sitio | En el árbol original está en el grupo «Global». |
| `testimonials` | todo el sitio | En el árbol original está en el grupo «Global». En `gnu-vehicular.md` el mismo bloque aparece como sección de *Inicio*; se respeta la lectura de cada archivo fuente en vez de armonizarlos en silencio. |
| `whatsapp_button` | todo el sitio | **No aparece en el árbol de navegación** — solo en «Requisitos principales». Se incorpora aquí porque es un requisito transversal declarado para los 4 sitios. |

## Clasificación de los nodos del árbol original

El árbol entregado por el cliente mezcla páginas, bloques de contenido y títulos de artículos sin
distinguirlos. Regla aplicada, en este orden:

1. Los hijos de **Inicio** y **Contacto** son **secciones** — bajo esas dos columnas el cliente
   listó el contenido de la página, no subpáginas.
2. Los hijos de **Blog** son filas de `blog_posts`.
3. Todo lo demás es **página**: cada cabeza de columna, y cada hijo de *Nuestros servicios*,
   *Sobre nosotros* y *Desarrollo sostenible*. Excepción: un hijo cuyo nombre coincide con un tipo
   del catálogo es una **sección** — esto captura *Nuestros clientes* bajo *Nuestros servicios*, el
   único nodo que se lee como nombre de página sin serlo.

La estructura manda y el nombre es solo la excepción, no la regla: clasificar por nombre falla en
`Contacto`, `Certificaciones`, `Distintivo ESR` y `EcoVadis`.

Los 24 nodos hijos del árbol quedan así, sin sobrantes:

| resultado | cantidad | nodos |
|---|---|---|
| Página | 11 | GNC, GND, GNV, Propósito empresarial, Valores, Certificaciones, Nuestra historia, Distintivo ESR, EcoVadis, Voluntariado, Iniciativas de RSE |
| Sección de página | 7 | Carrusel de banners, Simulador de ahorro, Nuestros clientes ×2, Contacto, Captación de leads, Mapa de estaciones |
| Fila de `blog_posts` | 4 | los 4 títulos bajo Blog |
| Sección global | 2 | Íconos de redes sociales, Testimoniales |

Las 6 cabezas de columna se suman como páginas raíz: 11 + 6 = 17.

Traducción de nombre a tipo, para las secciones: Carrusel de banners → `banner_carousel`,
Simulador de ahorro → `savings_simulator`, Nuestros clientes → `clients_showcase`, Contacto y
Captación de leads → `lead_capture_form`, Mapa de estaciones → `stations_map`, Íconos de redes
sociales → `social_icons`, Testimoniales → `testimonials`.

## Apéndice — árbol de navegación original

Tal como fue entregado en el macro archivo de sitios web (GNU Gas Natural 2025). **No editar**: es
el único vínculo de trazabilidad con el documento del cliente. Cualquier cambio de estructura va en
el mapa de páginas de arriba, no aquí.

**Inicio**
- Carrusel de banners
- Simulador de ahorro
- Nuestros clientes
- Contacto

**Nuestros servicios**
- GNC
- GND
- GNV
- Nuestros clientes

**Sobre nosotros**
- Propósito empresarial
- Valores
- Certificaciones
- Nuestra historia

**Desarrollo sostenible**
- Distintivo ESR
- EcoVadis
- Voluntariado
- Iniciativas de RSE

**Contacto**
- Captación de leads
- Mapa de estaciones

**Blog**
- ¿Por qué elegir gas natural?
- Beneficios del GN
- Impacto del GN en el medio ambiente
- GN vs otros combustibles

**Global (fuera de columnas, presentes en todo el sitio)**
- Íconos de redes sociales
- Testimoniales
