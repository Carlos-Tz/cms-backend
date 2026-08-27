# Traninn Logistic — `traninn-logistic`

Blueprint de contenido predefinido. **Migrado parcial**: solo la columna *Inicio* y las secciones
globales están en formato blueprint y sembradas por `TraninnLogisticBlueprintSeeder`. Las otras
cuatro columnas siguen en el árbol crudo del apéndice.

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
| 1 | `/` | Inicio | — | 1 | `banner_carousel`, `savings_simulator`, `rich_text`, `clients_showcase`, `lead_capture_form` |
| — | `/nuestros-servicios` | Nuestros servicios | — | 2 | **sin migrar** |
| — | `/sobre-nosotros` | Sobre nosotros | — | 3 | **sin migrar** |
| — | `/contacto` | Contacto | — | 4 | **sin migrar** |
| — | `/ubicaciones` | Ubicaciones | — | 5 | **sin migrar** |

**Sembradas: 1 de 5 columnas.** El estado por tipo vive en
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
- **Sin `stations_map` ni `stations`.** *Ubicaciones* es una columna sin subsecciones declaradas y el
  spec no le atribuye mapa interactivo. Si al migrar esa columna aparece uno, es `stations_map` más
  filas de `stations`, no campos nuevos.
- **Sin `testimonials`**: ese tipo está declarado para `gnu` y `gnu-vehicular`, no para este sitio.
- **Sin blog**: no hay `/blog` ni filas de `blog_posts`.
- **El `lead_capture_form` de la portada no declara extras**: los cuatro campos de siempre, que son
  columnas de `leads`. Cuando se migre la columna *Contacto* el sitio tendrá dos formularios, y ahí
  `leads.source` empieza a importar para separarlos en la bandeja.

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
