# GNU Gas Natural Vehicular — `gnu-vehicular`

Blueprint de contenido predefinido. **Migrado parcialmente**: solo las columnas *Inicio* y *Blog*,
que son las que están sembradas. *Nuestros servicios*, *Ubicaciones* y *Contacto* siguen siendo el
árbol crudo del cliente, en el apéndice.

Los campos de cada tipo de sección están en [`section-types.md`](section-types.md); los valores
viven en `GnuVehicularBlueprintSeeder` y en la BD.

## Requisitos principales
- Carrusel de banners.
- Simulador de ahorro.
- Soporte para videos.
- Botón de WhatsApp — contacto directo de marketing.

## Mapa de páginas

Las columnas de navegación son 5: Inicio (1), Nuestros servicios (2), Ubicaciones (3), Contacto (4),
Blog (5) — una menos que el sitio institucional. Solo las dos migradas aparecen abajo.

| # | ruta | título | padre | pos | secciones (en orden) |
|---|---|---|---|---|---|
| 1 | `/` | Inicio | — | 1 | `banner_carousel`, `savings_simulator`, `feature_grid`, `testimonials`, `stations_map`, `lead_capture_form` |
| 5 | `/blog` | Blog | — | 5 | `page_hero` |

**Sembradas: 2 de 5 columnas.** El estado por tipo vive en
[`implementation-status.md`](implementation-status.md).

Notas de lectura:

- **`/` no lleva `page_hero`**: el `banner_carousel` es el hero de la portada, igual que en `gnu`.
- **«Programa de recompensas» es el `feature_grid` de la portada.** Es hijo de *Inicio*, así que por
  la regla de clasificación de [`gnu.md`](gnu.md) es sección, no página; y es una rejilla de
  tarjetas (acumulas, canjeas, beneficios de flotilla), que es justo lo que ese tipo genérico cubre.
  No se promovió a tipo propio: el cliente no declaró campos que `feature_grid` no tenga.
- **`testimonials` es sección de la portada aquí, y global en `gnu`.** El árbol de cada sitio se lee
  tal como lo entregó el cliente en vez de armonizarlos en silencio. Ver `gnu.md`, «Secciones
  globales».
- **El `savings_simulator` de esta portada guarda sus propios precios**, distintos a los de `gnu`.
  El endpoint `GET /api/public/gnu-vehicular/savings-simulator` los usa sin parámetro `page`, porque
  es la única instancia del sitio.
- **Los pines del mapa no son campos de `stations_map`**: salen de la tabla `stations`. El seeder
  siembra dos placeholders, Morelia y Uruapan, que son las estaciones que nombra *Ubicaciones*.
- **`/blog` solo lleva `page_hero`**: el listado lo sirve `GET /api/public/gnu-vehicular/blog`, y
  los 4 artículos son filas de `blog_posts`.

## Secciones globales

Filas de `sections` con `page_id = null`. Son los tres requisitos transversales declarados para los
4 sitios. `testimonials` **no** está aquí — en este sitio vive en la portada.

| tipo | ámbito | notas |
|---|---|---|
| `contact_info` | todo el sitio | No está en el árbol original; datos de contacto de la empresa. |
| `social_icons` | todo el sitio | En el árbol original está fuera del flujo principal. |
| `whatsapp_button` | todo el sitio | Solo en «Requisitos principales», no en el árbol. |

## Pendiente de migrar

Las tres columnas restantes. Arrastran dos cosas que `gnu` no tuvo:

- Los 4 candidatos de tipo **sin verificar** de este sitio — `equipment_quote`, `financing`,
  `service_scheduling`, `billing_info` — hay que auditarlos con el mismo criterio que se aplicó a
  `certifications` y `esr_ecovadis` en `gnu`, que resultaron ser páginas.
- Es el único árbol con **3 niveles** («Instala tu equipo de GNV» y sus hijos). `pages.parent_id` lo
  soporta; el mapa de `gnu` solo demuestra 2.

## Apéndice — árbol de navegación original

Tal como fue entregado en el macro archivo de sitios web (GNU Gas Natural 2025). **No editar**: es
el único vínculo de trazabilidad con el documento del cliente.

**Íconos de redes sociales** (columna global, fuera del flujo principal)

**Inicio**
- Carrusel de banners
- Simulador de ahorro
- Programa de recompensas
- Testimoniales
- Mapa de estaciones
- Contacto

**Nuestros servicios**
- Instala tu equipo de GNV
  - Cotiza tu equipo
  - Financiamiento
- Flotillas empresariales
- Estaciones móviles

**Ubicaciones**
- Estaciones Morelia
- Estación Uruapan
- Agenda tu verificación
- Talleres

**Contacto**
- Captación de leads
- Facturación
- Mapa de estaciones

**Blog**
- Verificación anual
- Transporte sostenible
- Seguridad del GNV
- GN vs otros combustibles
