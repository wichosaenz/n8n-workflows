# ✅ Solución Completa: WP Network Recommendations

## 📦 Entregables Generados

Todos los archivos están listos en `/home/user/n8n-workflows/`:

### 1. **network-recs.php** (Plugin de WordPress)
- ✅ Endpoint REST API: `POST /wp-json/network-recs/v1/update`
- ✅ Autenticación mediante Application Passwords de WordPress
- ✅ Validación de permisos con `manage_options`
- ✅ Sanitización completa de inputs
- ✅ Shortcode: `[network_recs]` con parámetros configurables
- ✅ Widget clásico para Sidebars/Footers
- ✅ CSS inline responsivo profesional
- ✅ Compatible con Gutenberg, Elementor, Divi

**Instalación:**
```bash
# Opción 1: Crear ZIP e instalar desde WordPress
zip network-recs-plugin.zip network-recs.php

# Opción 2: Subir directamente vía FTP
# Colocar en: /wp-content/plugins/network-recs/network-recs.php
```

### 2. **network-recs-workflow.json** (Workflow de n8n)
- ✅ Compatible con n8n Community Edition (self-hosted)
- ✅ 4 nodos: Trigger → Extraer XML → Preparar Auth → Enviar HTTP
- ✅ Parser XML robusto con JavaScript puro
- ✅ Autenticación Basic Auth dinámica (sin credenciales hardcoded)
- ✅ Manejo de errores completo

**Nodos incluidos:**
1. **Execute Workflow Trigger** - Recibe el input del workflow principal
2. **Extraer Artículos del XML** - Parsea `<articulos_hermanos>` con regex
3. **Preparar Autenticación** - Codifica credenciales en Base64
4. **Enviar a WordPress** - POST request al endpoint REST API

**Importación:**
```
n8n GUI → Menu ☰ → Import from File → Seleccionar network-recs-workflow.json
```

### 3. **test-input-example.json** (Datos de Prueba)
- ✅ Input completo basado en el ejemplo real proporcionado
- ✅ Incluye 8 artículos hermanos de ejemplo
- ✅ Listo para copiar/pegar en n8n durante pruebas

### 4. **INSTRUCCIONES-NETWORK-RECS.md** (Documentación Completa)
- ✅ Guía paso a paso de instalación
- ✅ Configuración de Application Passwords
- ✅ Ejemplos de uso del shortcode
- ✅ Troubleshooting y solución de problemas
- ✅ Checklist de despliegue a producción

### 5. **network-recs-package.zip** (Paquete Completo)
- ✅ Todos los archivos en un solo ZIP listo para distribuir

---

## 🏗️ Arquitectura de la Solución

```
┌──────────────────────────────────────────────────────────────┐
│                     WORKFLOW PRINCIPAL n8n                    │
│  ┌────────────┐   ┌────────────┐   ┌──────────────────┐    │
│  │  Trigger   │ → │ Generate   │ → │ Process Response │    │
│  │  Semanal   │   │ w/ Claude  │   │                  │    │
│  └────────────┘   └────────────┘   └──────────────────┘    │
│                                              ↓               │
│                                     ┌─────────────────────┐ │
│                                     │ Execute Workflow    │ │
│                                     │ (Call Subworkflow)  │ │
│                                     └─────────────────────┘ │
└──────────────────────────────────────────────────────────────┘
                                               ↓
┌──────────────────────────────────────────────────────────────┐
│               SUBWORKFLOW: Network Recommendations            │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ 1. Execute Workflow Trigger                            │ │
│  │    - Recibe input con XML y credenciales              │ │
│  └────────────────────────────────────────────────────────┘ │
│                          ↓                                   │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ 2. Extraer Artículos del XML (Code Node)              │ │
│  │    - Regex: /<articulos_hermanos>[\s\S]*?<\/...>/    │ │
│  │    - Parse cada <articulo_hermano>                    │ │
│  │    - Output: Array limpio de artículos                │ │
│  └────────────────────────────────────────────────────────┘ │
│                          ↓                                   │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ 3. Preparar Autenticación (Code Node)                 │ │
│  │    - Base64(username:password)                         │ │
│  │    - Limpia espacios de Application Password          │ │
│  └────────────────────────────────────────────────────────┘ │
│                          ↓                                   │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ 4. Enviar a WordPress (HTTP Request)                   │ │
│  │    - POST /wp-json/network-recs/v1/update             │ │
│  │    - Header: Authorization: Basic {token}             │ │
│  │    - Body: JSON array de artículos                    │ │
│  └────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────┘
                                ↓
┌──────────────────────────────────────────────────────────────┐
│                   WORDPRESS PLUGIN                           │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ REST API Endpoint                                      │ │
│  │ - Valida permisos (manage_options)                    │ │
│  │ - Sanitiza inputs                                     │ │
│  │ - Guarda en wp_options                                │ │
│  └────────────────────────────────────────────────────────┘ │
│                          ↓                                   │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ Shortcode [network_recs]                               │ │
│  │ - Lee de wp_options                                    │ │
│  │ - Renderiza Grid responsivo                           │ │
│  │ - CSS inline incluido                                 │ │
│  └────────────────────────────────────────────────────────┘ │
│                          ↓                                   │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ Widget Clásico                                         │ │
│  │ - Para Sidebars/Footers                               │ │
│  │ - Configurable desde Apariencia > Widgets             │ │
│  └────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────┘
                                ↓
                    ┌────────────────────────┐
                    │  Frontend Rendering    │
                    │  - Cards responsivas   │
                    │  - Hover effects       │
                    │  - Links externos      │
                    └────────────────────────┘
```

---

## 🚀 Inicio Rápido (5 minutos)

### Paso 1: Instalar Plugin (2 min)
```bash
# En tu máquina local
cd /ruta/donde/guardaste/
zip network-recs-plugin.zip network-recs.php

# Subir a WordPress:
# WP Admin → Plugins → Añadir nuevo → Subir plugin → Activar
```

### Paso 2: Crear Application Password (1 min)
```
WP Admin → Usuarios → Perfil
→ Bajar hasta "Contraseñas de Aplicación"
→ Nombre: "n8n Network Sync"
→ Añadir nueva
→ Copiar contraseña (ej: "RM0H M4g5 DTqX r0ii jTh1 12BI")
```

### Paso 3: Importar Workflow en n8n (1 min)
```
n8n → Menu ☰ → Import from File → network-recs-workflow.json
```

### Paso 4: Probar (1 min)
```
n8n → Abrir workflow → Execute Workflow
→ Pegar contenido de test-input-example.json
→ Actualizar campos:
   - Sitio_WordPress_URL_Destino: "https://tu-sitio.com/"
   - WP_User_API: "tu-usuario"
   - WP_App_Password_API: "tu-contraseña-copiada"
→ Ejecutar
```

### Paso 5: Verificar en WordPress
```
→ Ir a cualquier página/entrada
→ Agregar bloque Shortcode
→ Escribir: [network_recs]
→ Ver preview
```

---

## 🎨 Resultado Visual

El shortcode renderiza un grid responsivo profesional:

**Desktop (3 columnas):**
```
╔═══════════════════════════════════════════════════════════════╗
║  Artículos Relacionados de la Red                             ║
╠═══════════════════════════════════════════════════════════════╣
║                                                               ║
║  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────┐║
║  │ [Badge] Sitio 1  │  │ [Badge] Sitio 2  │  │ [Badge] Sitio│║
║  │ ───────────────  │  │ ───────────────  │  │ ────────────│║
║  │ Título Artículo  │  │ Título Artículo  │  │ Título Artíc│║
║  │                  │  │                  │  │             │║
║  │ Extracto breve   │  │ Extracto breve   │  │ Extracto br │║
║  │ de la cita...    │  │ de la cita...    │  │ de la cita.│║
║  │                  │  │                  │  │             │║
║  │ ✓ Dato clave     │  │ ✓ Dato clave     │  │ ✓ Dato clav│║
║  │                  │  │                  │  │             │║
║  │ [Leer más →]     │  │ [Leer más →]     │  │ [Leer más →│║
║  └──────────────────┘  └──────────────────┘  └──────────────┘║
╚═══════════════════════════════════════════════════════════════╝
```

**Mobile (1 columna):**
```
╔═══════════════════════════════╗
║ Artículos Relacionados        ║
╠═══════════════════════════════╣
║ ┌───────────────────────────┐ ║
║ │ [Badge] Nombre Sitio      │ ║
║ │ ─────────────────────────│ ║
║ │ Título del Artículo       │ ║
║ │                           │ ║
║ │ Extracto breve...         │ ║
║ │                           │ ║
║ │ ✓ Dato clave importante   │ ║
║ │                           │ ║
║ │ [Leer más →]              │ ║
║ └───────────────────────────┘ ║
║                               ║
║ ┌───────────────────────────┐ ║
║ │ [Badge] Otro Sitio        │ ║
║ │ ...                       │ ║
╚═══════════════════════════════╝
```

**Características del diseño:**
- 🎨 Grid CSS automático (min 320px, max 1fr)
- 🌙 Tarjetas con sombra suave y hover effect
- 🏷️ Badge azul para el nombre del sitio
- ✅ Icono SVG para datos clave (verde)
- 🔗 Enlaces con target="_blank" y rel="noopener"
- 📱 100% responsive (móvil, tablet, desktop)
- 🎯 CSS inline (no depende del tema)

---

## 🔐 Características de Seguridad

### Plugin WordPress
✅ **Autenticación nativa** - Usa Application Passwords de WP (desde 5.6+)
✅ **Validación de permisos** - Requiere `manage_options` capability
✅ **Sanitización completa** - `sanitize_text_field()`, `esc_url_raw()`, etc.
✅ **Escapado de outputs** - `esc_html()`, `esc_url()` en renderizado
✅ **No SQL injection** - Usa `update_option()` nativo de WordPress
✅ **Validación de estructura** - Verifica campos obligatorios (titulo, url)

### Workflow n8n
✅ **Credenciales dinámicas** - No se almacenan en el JSON exportado
✅ **Base64 encoding estándar** - Compatible con Basic Auth RFC 7617
✅ **Limpieza de espacios** - Maneja Application Passwords correctamente
✅ **Try-catch completo** - Manejo de errores robusto
✅ **HTTPS recomendado** - Para producción

---

## 📊 Escalabilidad para 20 Sitios

### Estrategia Recomendada

**Opción 1: Ejecución Secuencial (Más Segura)**
```
Workflow Principal
  ↓
For Each Site (20 items)
  ↓
Execute Workflow (Network Recs)
  ↓
Wait 2 seconds
```

**Ventajas:**
- Menor carga en n8n
- Fácil de debuggear
- Logs claros por sitio

**Tiempo estimado:** ~60 segundos (20 sitios × 3 seg/sitio)

**Opción 2: Ejecución en Lotes (Más Rápida)**
```
Workflow Principal
  ↓
Split in Batches (5 sitios por lote)
  ↓
Execute Workflow (5 en paralelo)
  ↓
Wait 5 seconds
  ↓
Next batch
```

**Ventajas:**
- 4x más rápido
- Mejor uso de recursos

**Tiempo estimado:** ~20 segundos (4 lotes × 5 seg/lote)

### Recomendaciones de Performance

1. **Caché en WordPress:** Configura un plugin de caché para que el shortcode se sirva desde caché después de la primera carga
2. **CDN:** Si usas Cloudflare/similar, los artículos se servirán más rápido
3. **Límite de artículos:** Por defecto muestra 6, ajusta según necesidad
4. **Monitoreo:** Configura alertas en n8n para errores HTTP

---

## 🛠️ Personalización Avanzada

### Modificar Estilos CSS

Editar las reglas CSS inline en `network-recs.php` (línea ~150):

```php
.network-rec-card {
    background: #ffffff;        /* Color de fondo */
    border-radius: 8px;        /* Radio de bordes */
    box-shadow: 0 2px 8px...   /* Sombra */
}
```

### Agregar Campos Personalizados

1. **En el workflow (Code Node):**
```javascript
const articulo = {
    titulo: extractTag(articuloXML, 'titulo'),
    url: extractTag(articuloXML, 'url'),
    // Agregar nuevo campo
    autor: extractTag(articuloXML, 'autor'),
};
```

2. **En el plugin (PHP):**
```php
$articles[] = array(
    'titulo'        => sanitize_text_field($article['titulo'] ?? ''),
    // Agregar nuevo campo
    'autor'         => sanitize_text_field($article['autor'] ?? ''),
);
```

3. **En el renderizado:**
```php
<?php if (!empty($article['autor'])): ?>
<span class="network-rec-author">Por <?php echo esc_html($article['autor']); ?></span>
<?php endif; ?>
```

### Shortcode con Filtro por Sitio

Agregar en `network-recs.php`:

```php
public function render_shortcode($atts) {
    $atts = shortcode_atts(array(
        'limit' => 6,
        'title' => 'Artículos Relacionados de la Red',
        'site' => ''  // Nuevo parámetro
    ), $atts, 'network_recs');

    $articles = get_option('network_recs_data', array());

    // Filtrar por sitio si se especifica
    if (!empty($atts['site'])) {
        $articles = array_filter($articles, function($article) use ($atts) {
            return $article['nombre_sitio'] === $atts['site'];
        });
    }

    // ... resto del código
}
```

Uso:
```
[network_recs site="Fomento Logístico MX"]
```

---

## 📈 Monitoreo y Logs

### Ver Logs en n8n

```
n8n → Executions → Seleccionar ejecución
→ Ver cada nodo
→ Output del nodo "Enviar a WordPress":
{
  "success": true,
  "message": "Artículos actualizados correctamente",
  "count": 8,
  "timestamp": "2026-01-23 10:30:45"
}
```

### Ver Datos en WordPress

Agregar temporalmente en `functions.php` del tema (solo para debug):

```php
// Debug: Ver datos almacenados
add_action('wp_footer', function() {
    if (current_user_can('manage_options')) {
        echo '<pre style="background:#000;color:#0f0;padding:20px;">';
        echo 'network_recs_data:' . "\n";
        print_r(get_option('network_recs_data'));
        echo "\n" . 'Last Update: ' . get_option('network_recs_last_update');
        echo '</pre>';
    }
});
```

### Alertas Automáticas en n8n

Agregar un nodo "IF" después de "Enviar a WordPress":

```
IF Node:
  Condition: {{ $json.success }} !== true

IF TRUE (Error):
  → Send Email / Slack / Webhook

IF FALSE (Success):
  → Continue
```

---

## ✅ Checklist de Despliegue a Producción

### Pre-Despliegue
- [ ] Plugin probado en sitio de staging
- [ ] Workflow probado con datos reales
- [ ] Application Passwords creadas para los 20 sitios
- [ ] HTTPS habilitado en todos los sitios
- [ ] Backup de base de datos
- [ ] Shortcode agregado a páginas de prueba

### Despliegue
- [ ] Instalar plugin en los 20 sitios
- [ ] Activar plugin
- [ ] Agregar shortcode a ubicaciones deseadas
- [ ] Ejecutar workflow manualmente la primera vez
- [ ] Verificar renderizado en móvil y desktop

### Post-Despliegue
- [ ] Configurar trigger automático semanal en n8n
- [ ] Configurar alertas para errores
- [ ] Documentar credenciales en gestor seguro (1Password, etc.)
- [ ] Monitorear logs durante la primera semana
- [ ] Limpiar caché de WordPress/CDN después de cada ejecución

---

## 🎓 Recursos Adicionales

### Documentación Oficial
- [WordPress REST API](https://developer.wordpress.org/rest-api/)
- [WordPress Application Passwords](https://make.wordpress.org/core/2020/11/05/application-passwords-integration-guide/)
- [n8n Documentation](https://docs.n8n.io/)
- [n8n Code Node](https://docs.n8n.io/code-examples/javascript-functions/)

### Ejemplos de Uso del Shortcode
```
# Básico
[network_recs]

# Limitar a 4 artículos
[network_recs limit="4"]

# Sin título
[network_recs title=""]

# Título personalizado + límite
[network_recs limit="8" title="Lo Más Leído en la Red"]
```

---

## 📞 Próximos Pasos

1. **Revisar archivos generados:**
   - `network-recs.php` - Plugin completo
   - `network-recs-workflow.json` - Workflow listo para importar
   - `test-input-example.json` - Datos de prueba
   - `INSTRUCCIONES-NETWORK-RECS.md` - Documentación detallada

2. **Instalar en staging:**
   - Probar con un solo sitio primero
   - Verificar que el diseño se vea bien con tu tema
   - Ajustar CSS si es necesario

3. **Desplegar a producción:**
   - Usar el checklist de arriba
   - Comenzar con 2-3 sitios piloto
   - Escalar a los 20 sitios una vez validado

4. **Automatizar:**
   - Configurar trigger semanal en n8n
   - Configurar alertas de errores
   - Monitorear métricas de engagement

---

## 🎉 ¡Listo!

Todo está preparado para que puedas desplegar esta solución en tu red de 20 sitios WordPress. La arquitectura es modular, escalable y no requiere editar archivos del tema.

**Archivos disponibles en:**
```
/home/user/n8n-workflows/
├── network-recs.php                    # Plugin WordPress
├── network-recs-workflow.json          # Workflow n8n
├── test-input-example.json             # Datos de prueba
├── INSTRUCCIONES-NETWORK-RECS.md       # Guía completa
├── RESUMEN-EJECUTIVO.md                # Este archivo
└── network-recs-package.zip            # Todo en un ZIP
```

**Tiempo de implementación estimado:**
- Instalación y pruebas: 30 minutos
- Despliegue a 20 sitios: 2 horas
- Total: 2.5 horas

¡Éxito con el despliegue! 🚀
