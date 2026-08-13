# Instrucciones para Documentación con GitHub Copilot

## Directrices Generales
- Proporcionar ejemplos de código claros y concisos
- Incluir explicaciones detalladas y resumidas para cada ejemplo de código
- Redactar toda la documentación en español
- Documentar únicamente en el chat de GitHub Copilot (no crear archivos markdown separados)

## Estructura Obligatoria para cada Prompt

Seguir esta estructura en orden para cada funcionalidad documentada:

1. **Flujo Simple** - Diagrama de flujo de la funcionalidad o característica
2. **Decisiones Clave de Implementación** - Código y explicaciones técnicas principales
3. **Configuración** - Configuraciones necesarias (si aplica)
4. **Implementación** - Detalles técnicos y estructura de peticiones
5. **Endpoints** - Documentación de API (si aplica)
6. **Archivos involucrados** - Tabla de archivos describiendo capa y ruta de cada archivo involucrado en la funcionalidad. 

---

## Plantilla de Ejemplo

### [Nombre de la Funcionalidad]
*Descripción breve de qué hace la funcionalidad y su propósito principal.*

#### Flujo Simple
```
Paso 1: Acción Inicial
⬇️ 
Paso 2: Validación/Middleware
⬇️
Paso 3: Procesamiento Principal
⬇️ 
Paso 4: Acciones en Base de Datos
⬇️ 
Paso 5: Respuesta/Resultado
```

#### Decisiones Clave de Implementación

**[Nombre del Componente 1]**
```php
// Explicación del código
$ejemplo = new ClaseEjemplo();
$ejemplo->metodo();
```

**[Nombre del Componente 2]**
```php
// Otro ejemplo con explicación
class EjemploClase 
{
    public function metodoEjemplo()
    {
        // Lógica implementada
    }
}
```

#### Configuración
- Configuración requerida 1
- Configuración requerida 2
- Variables de entorno necesarias

#### Implementación

**Estructura de la Petición**
```
Headers requeridos:
Authorization: Bearer {token}
Content-Type: application/json
```

**Endpoint Principal**
```
METHOD /api/ruta/endpoint
```

**Ejemplo de Petición**
```json
{
    "campo1": "valor1",
    "campo2": "valor2"
}
```

**Respuesta Exitosa (HTTP 200)**
```json
{
    "success": true,
    "message": "Operación exitosa",
    "data": {
        "resultado": "datos_de_respuesta"
    }
}
```

#### Endpoints Adicionales (si aplica)

**Listar Elementos**
```
GET /api/ruta/elementos
```

**Actualizar Elemento**
```
PUT /api/ruta/elementos/{id}
```

**Respuestas de Error Comunes**

**Error 404 - No Encontrado**
```json
{
    "success": false,
    "message": "Elemento no encontrado",
    "error": "Descripción detallada del error"
}
```

**Error 401 - No Autorizado**
```json
{
    "success": false,
    "message": "Token inválido",
    "error": "Descripción detallada del error"
}
```