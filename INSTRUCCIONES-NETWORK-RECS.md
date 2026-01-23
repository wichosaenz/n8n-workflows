# WP Network Recommendations - Guía de Instalación y Uso

## 📦 Entregables

Este paquete incluye:

1. **`network-recs.php`** - Plugin de WordPress completo
2. **`network-recs-workflow.json`** - Subworkflow de n8n listo para importar
3. **Este archivo** - Instrucciones de instalación y uso

---

## 🔌 Parte 1: Instalación del Plugin en WordPress

### Opción A: Instalación mediante ZIP (Recomendada)

1. **Crear el archivo ZIP del plugin:**
   ```bash
   # En la carpeta donde está network-recs.php
   zip network-recs.zip network-recs.php
   ```

2. **Subir a WordPress:**
   - Ve a **Plugins > Añadir nuevo**
   - Haz clic en **Subir plugin**
   - Selecciona el archivo `network-recs.zip`
   - Haz clic en **Instalar ahora**
   - Activa el plugin

### Opción B: Instalación Manual vía FTP/SFTP

1. Sube el archivo `network-recs.php` a la carpeta:
   ```
   /wp-content/plugins/network-recs/network-recs.php
   ```

2. Ve a **Plugins** en el panel de WordPress y activa "WP Network Recommendations"

### Verificación de la Instalación

Una vez activado, verifica que el endpoint REST API esté disponible:

```bash
# Reemplaza example.com con tu dominio
curl https://example.com/wp-json/network-recs/v1/update
```

Deberías recibir un error 401 (no autorizado), lo cual confirma que el endpoint existe.

---

## 🎨 Parte 2: Uso del Plugin en WordPress

### Opción 1: Shortcode (Recomendada)

Agrega el shortcode en cualquier página, entrada o widget de texto:

```
[network_recs]
```

**Con parámetros personalizados:**

```
[network_recs limit="8" title="Lo Más Leído en la Red"]
```

**Parámetros disponibles:**
- `limit` - Cantidad máxima de artículos a mostrar (por defecto: 6)
- `title` - Título del bloque (por defecto: "Artículos Relacionados de la Red")

### Opción 2: Widget Clásico

1. Ve a **Apariencia > Widgets**
2. Busca "Artículos de la Red"
3. Arrástralo a la zona de widgets deseada (Sidebar, Footer, etc.)
4. Configura el título y la cantidad de artículos
5. Guarda los cambios

### Opción 3: Bloque de Gutenberg

1. En el editor de bloques, agrega un bloque de **Shortcode**
2. Pega: `[network_recs]`
3. El bloque se renderizará automáticamente

### Opción 4: Código PHP (para desarrolladores)

En archivos de tema:

```php
<?php echo do_shortcode('[network_recs limit="4"]'); ?>
```

---

## 🤖 Parte 3: Configuración del Workflow en n8n

### Importar el Workflow

1. Abre tu instancia de n8n
2. Haz clic en el menú **☰** (hamburguesa)
3. Selecciona **Import from File**
4. Carga el archivo `network-recs-workflow.json`
5. El workflow se importará con 3 nodos:
   - **Execute Workflow Trigger** (Trigger)
   - **Extraer Artículos del XML** (Code Node)
   - **Enviar a WordPress** (HTTP Request)

### Configuración de Credenciales

El workflow NO requiere configuración de credenciales separadas. La autenticación se construye dinámicamente usando los datos del input (`WP_User_API` y `WP_App_Password_API`).

**IMPORTANTE:** El nodo HTTP Request usa una expresión para generar el header de autenticación Basic Auth:

```
Authorization: Basic {{ $binary.toBuffer($json.tareaAgente.WP_User_API + ':' + $json.tareaAgente.WP_App_Password_API.replace(/\s/g, '')).toString('base64') }}
```

Esta expresión:
1. Toma el usuario y contraseña del input
2. Elimina espacios de la contraseña (las Application Passwords de WordPress usan espacios)
3. Codifica en Base64 para Basic Auth

### Crear Application Password en WordPress

Para que el workflow pueda autenticarse:

1. Ve a **Usuarios > Perfil**
2. Baja hasta **Contraseñas de Aplicación**
3. Ingresa un nombre: "n8n Network Sync"
4. Clic en **Añadir nueva contraseña de aplicación**
5. **COPIA LA CONTRASEÑA GENERADA** (aparece con espacios, ej: `RM0H M4g5 DTqX r0ii`)
6. Guarda esta contraseña para usarla en el campo `WP_App_Password_API` del input

### Probar el Workflow

1. Haz clic en **Execute Workflow** en n8n
2. Usa el siguiente JSON de prueba:

```json
[
  {
    "prompt": "\n<SystemPrompt>\n <articulos_hermanos>\n  <articulo_hermano>\n   <titulo>Prueba de Artículo 1</titulo>\n   <url>https://ejemplo.com/articulo-1</url>\n   <nombre_sitio>Sitio Prueba</nombre_sitio>\n   <cita_directa>Esta es una cita de prueba para verificar el funcionamiento del sistema.</cita_directa>\n   <dato_clave>Dato importante de prueba</dato_clave>\n  </articulo_hermano>\n  <articulo_hermano>\n   <titulo>Prueba de Artículo 2</titulo>\n   <url>https://ejemplo.com/articulo-2</url>\n   <nombre_sitio>Sitio Prueba</nombre_sitio>\n   <cita_directa>Otra cita de prueba.</cita_directa>\n   <dato_clave>Otro dato clave</dato_clave>\n  </articulo_hermano>\n </articulos_hermanos>\n</SystemPrompt>\n",
    "tareaAgente": {
      "Sitio_WordPress_URL_Destino": "https://tu-sitio.com/",
      "WP_User_API": "tu-usuario",
      "WP_App_Password_API": "RM0H M4g5 DTqX r0ii jTh1 12BI"
    }
  }
]
```

3. Verifica que el nodo "Enviar a WordPress" devuelva:
   ```json
   {
     "success": true,
     "message": "Artículos actualizados correctamente",
     "count": 2,
     "timestamp": "2026-01-23 10:30:45"
   }
   ```

---

## 🔄 Parte 4: Integración con el Workflow Principal

### Llamar al Subworkflow

Desde tu workflow principal, agrega un nodo **Execute Workflow**:

1. **Workflow** - Selecciona "Subworkflow: Enviar Artículos de Red a WordPress"
2. **Source** - "Database"
3. **Mode** - "Run once for all items" (si procesas múltiples sitios en lote)

### Flujo de Datos Completo

```
[Workflow Principal]
      ↓
[Generar Artículo con LLM]
      ↓
[Procesar Respuesta]
      ↓
[Execute Workflow] → [Subworkflow: Network Recs]
      ↓                     ↓
[Continuar...]      [Extraer XML]
                           ↓
                    [Enviar a WordPress]
```

---

## 🎯 Parte 5: Resultado Final en WordPress

Una vez que el workflow envíe los datos, el shortcode `[network_recs]` mostrará automáticamente:

### Características Visuales

✅ **Grid Responsivo** - Se adapta automáticamente a móviles, tablets y escritorio
✅ **Tarjetas con Sombra** - Diseño moderno tipo card con hover effects
✅ **Nombre del Sitio** - Badge azul en la parte superior de cada tarjeta
✅ **Título Clickeable** - Enlace directo al artículo original
✅ **Extracto** - Primeras 30 palabras de la cita directa
✅ **Dato Clave** - Destacado con icono verde
✅ **Botón "Leer más"** - Call-to-action claro
✅ **Links con target="_blank"** - Abre en nueva pestaña
✅ **CSS Inline** - No depende del tema, funciona en cualquier sitio

### Ejemplo Visual

```
╔══════════════════════════════════════════════════════════════╗
║  Artículos Relacionados de la Red                            ║
╠══════════════════════════════════════════════════════════════╣
║                                                              ║
║  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      ║
║  │ [Sitio]      │  │ [Sitio]      │  │ [Sitio]      │      ║
║  │ Título...    │  │ Título...    │  │ Título...    │      ║
║  │ Extracto...  │  │ Extracto...  │  │ Extracto...  │      ║
║  │ ✓ Dato clave │  │ ✓ Dato clave │  │ ✓ Dato clave │      ║
║  │ Leer más →   │  │ Leer más →   │  │ Leer más →   │      ║
║  └──────────────┘  └──────────────┘  └──────────────┘      ║
╚══════════════════════════════════════════════════════════════╝
```

---

## 🔐 Seguridad

### Plugin WordPress

- ✅ Validación de permisos con `manage_options`
- ✅ Autenticación mediante Application Passwords (nativo de WP 5.6+)
- ✅ Sanitización de todos los inputs (`sanitize_text_field`, `esc_url_raw`, etc.)
- ✅ Escapado de outputs (`esc_html`, `esc_url`, etc.)
- ✅ Validación de estructura de datos recibidos

### Workflow n8n

- ✅ No expone credenciales en el JSON exportado
- ✅ Autenticación Basic Auth estándar
- ✅ Validación de datos antes de envío
- ✅ Manejo de errores con try-catch
- ✅ Compatible con HTTPS (recomendado para producción)

---

## 🐛 Solución de Problemas

### El shortcode no muestra artículos

**Causa:** No se han recibido datos desde n8n
**Solución:**
1. Verifica que el workflow se haya ejecutado correctamente
2. Revisa los logs en n8n
3. Verifica manualmente en WordPress:
   ```php
   // En functions.php temporalmente para debug
   echo '<pre>';
   print_r(get_option('network_recs_data'));
   echo '</pre>';
   ```

### Error 401 en el HTTP Request

**Causa:** Credenciales incorrectas
**Solución:**
1. Verifica que el `WP_User_API` sea el nombre de usuario correcto
2. Verifica que el `WP_App_Password_API` sea la contraseña de aplicación (CON espacios)
3. Asegúrate de que el usuario tenga rol de Administrador

### Error 403 Forbidden

**Causa:** El usuario no tiene permisos `manage_options`
**Solución:** Asigna el rol de Administrador al usuario de la API

### Los artículos no se extraen del XML

**Causa:** Formato XML incorrecto
**Solución:**
1. Verifica que el XML tenga la estructura exacta:
   ```xml
   <articulos_hermanos>
     <articulo_hermano>
       <titulo>...</titulo>
       <url>...</url>
       <nombre_sitio>...</nombre_sitio>
       <cita_directa>...</cita_directa>
       <dato_clave>...</dato_clave>
     </articulo_hermano>
   </articulos_hermanos>
   ```
2. Revisa los logs del nodo Code en n8n

### El CSS no se aplica correctamente

**Causa:** Conflicto con el tema
**Solución:** El CSS inline tiene especificidad suficiente, pero si persiste, agrega `!important` a las reglas críticas en el archivo PHP

---

## 📊 Mantenimiento

### Actualizar Artículos

Los artículos se actualizan automáticamente cada vez que el workflow de n8n se ejecuta. El plugin sobrescribe completamente los datos anteriores.

### Ver Última Actualización

```php
// Agregar en cualquier template
$last_update = get_option('network_recs_last_update');
echo 'Última actualización: ' . $last_update;
```

### Limpiar Datos

Para eliminar todos los artículos almacenados:

```php
// Ejecutar una vez en functions.php o mediante WP-CLI
delete_option('network_recs_data');
delete_option('network_recs_last_update');
```

---

## 🚀 Despliegue a Producción

### Lista de Verificación

- [ ] Plugin activado en los 20 sitios WordPress
- [ ] Application Passwords creadas para cada sitio
- [ ] Workflow de n8n probado con datos reales
- [ ] Shortcode agregado a la página/sidebar deseada
- [ ] Verificación visual en diferentes dispositivos
- [ ] HTTPS habilitado en todos los sitios (requerido para Application Passwords)
- [ ] Backup de la base de datos antes del primer despliegue

### Recomendaciones

1. **Staging First:** Prueba en un sitio de staging antes de desplegar a los 20 sitios
2. **Monitoreo:** Configura alertas en n8n para errores en el workflow
3. **Logs:** Revisa los logs de WordPress regularmente
4. **Performance:** Con 20 sitios, considera ejecutar el workflow en lotes de 5 sitios
5. **Cache:** Si usas plugins de caché (WP Rocket, W3 Total Cache), limpia el caché después de cada actualización

---

## 📞 Soporte

Para reportar bugs o solicitar features:
- GitHub Issues: https://github.com/wichosaenz/n8n-workflows/issues

---

## 📝 Changelog

### v1.0.0 (2026-01-23)
- ✨ Release inicial
- ✅ Endpoint REST API con autenticación
- ✅ Shortcode con parámetros configurables
- ✅ Widget clásico de WordPress
- ✅ CSS inline responsivo
- ✅ Workflow de n8n compatible con Community Edition
- ✅ Extracción XML robusta con JavaScript puro
- ✅ Autenticación Basic Auth dinámica

---

## 📄 Licencia

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html
