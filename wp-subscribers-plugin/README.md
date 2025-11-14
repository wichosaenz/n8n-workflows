# WP Subscribers Manager

Plugin de WordPress para gestionar una lista de suscriptores con base de datos MySQL personalizada en Dreamhost.

**Versión:** 1.0.0
**Autor:** Wicho Saenz
**Sitio Web:** [www.wichosaenz.com](https://www.wichosaenz.com)

---

## 📋 Descripción

WP Subscribers Manager es un plugin completo para WordPress que te permite crear y gestionar una lista de suscriptores utilizando una base de datos MySQL personalizada alojada en Dreamhost. El plugin ofrece un formulario de suscripción totalmente personalizable que se adapta al estilo de tu tema.

### Características Principales

✅ Conexión a base de datos MySQL personalizada en Dreamhost
✅ Configuración completa de parámetros de conexión
✅ Labels personalizables para soporte multiidioma (español/inglés)
✅ Formulario adaptable al CSS del tema activo
✅ Integración mediante shortcode o widget
✅ Validación de emails duplicados
✅ Mensajes de éxito y error personalizables
✅ Interfaz de administración intuitiva
✅ Envío de formulario con AJAX (sin recargar página)
✅ Responsive y compatible con dispositivos móviles

---

## 🚀 Instalación

### Método 1: Subir ZIP desde WordPress

1. Descarga el archivo `wp-subscribers-plugin.zip`
2. Ve a tu panel de WordPress: **Plugins > Añadir nuevo**
3. Haz clic en **Subir plugin**
4. Selecciona el archivo ZIP y haz clic en **Instalar ahora**
5. Activa el plugin después de la instalación

### Método 2: Instalación Manual por FTP

1. Descomprime el archivo `wp-subscribers-plugin.zip`
2. Sube la carpeta `wp-subscribers-plugin` a `/wp-content/plugins/`
3. Ve a **Plugins** en tu panel de WordPress
4. Activa **WP Subscribers Manager**

---

## ⚙️ Configuración

### 1. Configurar Base de Datos

Después de activar el plugin:

1. Ve a **Suscriptores** en el menú de administración de WordPress
2. Completa la sección **Configuración de Base de Datos**:
   - **Host de Base de Datos**: El hostname de tu servidor MySQL en Dreamhost (ej: `mysql.example.dreamhosters.com`)
   - **Nombre de Base de Datos**: El nombre de tu base de datos
   - **Usuario de Base de Datos**: Tu usuario de MySQL
   - **Contraseña de Base de Datos**: La contraseña de tu usuario
   - **Nombre de Tabla**: El nombre de la tabla donde se guardarán los suscriptores (por defecto: `subscribers`)

3. Haz clic en **Probar Conexión** para verificar que los datos sean correctos
4. Haz clic en **Guardar Configuración**

> **Nota:** El plugin creará automáticamente la tabla en tu base de datos si no existe.

### 2. Configurar Etiquetas (Labels)

Personaliza los textos del formulario en español o inglés:

- **Título del Formulario**: El título que aparece encima del formulario
- **Etiqueta de Nombre**: Texto para el campo de nombre
- **Etiqueta de Email**: Texto para el campo de correo electrónico
- **Texto del Botón**: Texto del botón de envío
- **Mensaje de Éxito**: Mensaje que aparece cuando la suscripción es exitosa
- **Mensaje de Error**: Mensaje que aparece cuando hay un error
- **Mensaje de Email Duplicado**: Mensaje cuando el email ya está registrado

**Ejemplo en Español (por defecto):**
```
Título: Suscríbete a nuestro boletín
Nombre: Nombre
Email: Correo electrónico
Botón: Suscribirse
Éxito: ¡Gracias por suscribirte!
Error: Hubo un error. Por favor, intenta de nuevo.
Duplicado: Este correo ya está suscrito.
```

**Ejemplo en Inglés:**
```
Title: Subscribe to our newsletter
Name: Name
Email: Email address
Button: Subscribe
Success: Thank you for subscribing!
Error: An error occurred. Please try again.
Duplicate: This email is already subscribed.
```

---

## 📝 Uso del Plugin

### Opción 1: Usar Shortcode

Puedes insertar el formulario en cualquier página o entrada usando el shortcode:

```
[wp_subscribers_form]
```

**Con opciones personalizadas:**

```
[wp_subscribers_form title="Únete a nuestra comunidad" show_title="yes"]
```

**Parámetros disponibles:**
- `title`: Título personalizado del formulario
- `show_title`: Mostrar u ocultar el título (`yes` o `no`)

**Ejemplos:**

```
[wp_subscribers_form]
[wp_subscribers_form title="Newsletter" show_title="yes"]
[wp_subscribers_form show_title="no"]
```

### Opción 2: Usar Widget

1. Ve a **Apariencia > Widgets** en tu panel de WordPress
2. Busca el widget **Formulario de Suscriptores**
3. Arrástralo al área de widgets donde quieres que aparezca
4. Configura el título y si quieres mostrarlo
5. Guarda los cambios

### Opción 3: Usar en Código PHP (para desarrolladores)

Si necesitas insertar el formulario directamente en tu tema:

```php
<?php
if (function_exists('WP_Subscribers_Form_Handler::render_form')) {
    echo WP_Subscribers_Form_Handler::render_form(array(
        'title' => 'Tu título',
        'show_title' => 'yes'
    ));
}
?>
```

---

## 🗄️ Estructura de la Base de Datos

El plugin crea automáticamente una tabla con la siguiente estructura:

```sql
CREATE TABLE subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    subscribed_date DATETIME NOT NULL,
    ip_address VARCHAR(45),
    status VARCHAR(20) DEFAULT 'active',
    INDEX idx_email (email),
    INDEX idx_status (status)
)
```

**Campos:**
- `id`: Identificador único del suscriptor
- `name`: Nombre del suscriptor
- `email`: Correo electrónico (único)
- `subscribed_date`: Fecha y hora de suscripción
- `ip_address`: Dirección IP del visitante
- `status`: Estado del suscriptor (por defecto: 'active')

---

## 🎨 Personalización de Estilos

El formulario está diseñado para adaptarse automáticamente al CSS de tu tema, pero puedes personalizarlo añadiendo CSS adicional:

### Clases CSS Disponibles:

- `.wp-subscribers-form-wrapper`: Contenedor principal
- `.wp-subscribers-form-title`: Título del formulario
- `.wp-subscribers-form`: Formulario
- `.wp-subscribers-field`: Contenedor de cada campo
- `.wp-subscribers-input`: Campos de entrada
- `.wp-subscribers-button`: Botón de envío
- `.wp-subscribers-message`: Contenedor de mensajes
- `.wp-subscribers-message.success`: Mensaje de éxito
- `.wp-subscribers-message.error`: Mensaje de error

### Ejemplo de Personalización:

```css
/* Personalizar el botón */
.wp-subscribers-button {
    background-color: #ff6b6b;
    border-radius: 25px;
    font-size: 18px;
}

.wp-subscribers-button:hover {
    background-color: #ff5252;
}

/* Personalizar los campos de entrada */
.wp-subscribers-input {
    border-radius: 10px;
    border: 2px solid #e0e0e0;
}

.wp-subscribers-input:focus {
    border-color: #ff6b6b;
    box-shadow: 0 0 0 2px rgba(255, 107, 107, 0.2);
}
```

Añade este CSS en **Apariencia > Personalizar > CSS Adicional** o en el archivo `style.css` de tu tema hijo.

---

## 🔧 Requisitos del Sistema

- WordPress 5.0 o superior
- PHP 7.2 o superior
- MySQL 5.6 o superior
- Extensión MySQLi de PHP habilitada
- Acceso a una base de datos MySQL en Dreamhost

---

## 🔒 Seguridad

El plugin implementa las siguientes medidas de seguridad:

✅ Validación y sanitización de todos los datos de entrada
✅ Protección contra inyección SQL usando MySQLi
✅ Verificación de nonces en todas las peticiones AJAX
✅ Validación de permisos de usuario
✅ Prevención de acceso directo a archivos PHP
✅ Registro de errores en el log de WordPress

---

## 📊 Gestión de Suscriptores

Para gestionar tus suscriptores, puedes acceder directamente a tu base de datos MySQL a través de:

1. **phpMyAdmin** en tu panel de Dreamhost
2. Cliente MySQL como MySQL Workbench
3. Consultas SQL directas

### Consultas Útiles:

**Ver todos los suscriptores:**
```sql
SELECT * FROM subscribers ORDER BY subscribed_date DESC;
```

**Contar suscriptores activos:**
```sql
SELECT COUNT(*) as total FROM subscribers WHERE status = 'active';
```

**Buscar suscriptor por email:**
```sql
SELECT * FROM subscribers WHERE email = 'ejemplo@email.com';
```

**Exportar todos los suscriptores:**
```sql
SELECT name, email, subscribed_date
FROM subscribers
WHERE status = 'active'
ORDER BY subscribed_date DESC;
```

---

## ❓ Preguntas Frecuentes

### ¿Puedo usar este plugin con mi propia base de datos fuera de Dreamhost?

Sí, el plugin funciona con cualquier servidor MySQL. Solo necesitas configurar correctamente el host, nombre de base de datos, usuario y contraseña.

### ¿El formulario es responsive?

Sí, el formulario está diseñado para adaptarse a cualquier tamaño de pantalla y dispositivo.

### ¿Puedo tener el formulario en varios idiomas?

Sí, puedes configurar los labels en el idioma que prefieras. Para sitios multiidioma, puedes usar plugins de traducción como WPML o Polylang.

### ¿Qué pasa si ya existe un suscriptor con el mismo email?

El plugin detecta emails duplicados y muestra un mensaje personalizable informando que el correo ya está suscrito.

### ¿Puedo personalizar el diseño del formulario?

Sí, el formulario usa clases CSS que puedes personalizar completamente. También se adapta automáticamente al estilo de tu tema.

### ¿El plugin envía emails de confirmación?

Esta versión 1.0 no incluye envío automático de emails. Los datos se guardan directamente en la base de datos. Puedes implementar esta funcionalidad usando hooks o extensiones adicionales.

### ¿Cómo exporto mi lista de suscriptores?

Puedes exportar tu lista directamente desde phpMyAdmin o usando consultas SQL. También puedes usar herramientas de exportación de MySQL.

---

## 🛠️ Solución de Problemas

### El formulario no se muestra

1. Verifica que el plugin esté activado
2. Asegúrate de estar usando el shortcode correcto: `[wp_subscribers_form]`
3. Revisa si hay conflictos con otros plugins desactivándolos temporalmente

### Error de conexión a la base de datos

1. Verifica que los datos de conexión sean correctos
2. Usa el botón "Probar Conexión" en la configuración
3. Asegúrate de que tu IP esté autorizada para conectarse al servidor MySQL de Dreamhost
4. Verifica que el usuario de MySQL tenga permisos para crear tablas

### El formulario no envía datos

1. Asegúrate de que JavaScript esté habilitado en tu navegador
2. Verifica que no haya errores en la consola del navegador (F12)
3. Comprueba que jQuery esté cargado en tu tema
4. Revisa los logs de error de WordPress

### Los estilos no se aplican correctamente

1. Limpia la caché de tu navegador y del sitio web
2. Verifica que los archivos CSS se estén cargando correctamente
3. Comprueba si hay conflictos CSS con tu tema usando las herramientas de desarrollo del navegador

---

## 📁 Estructura de Archivos

```
wp-subscribers-plugin/
├── admin/
│   └── class-admin.php          # Panel de administración
├── assets/
│   ├── css/
│   │   ├── admin.css            # Estilos del admin
│   │   └── public.css           # Estilos públicos
│   └── js/
│       └── public.js            # JavaScript público
├── includes/
│   ├── class-database.php       # Manejo de base de datos
│   ├── class-form-handler.php   # Procesamiento del formulario
│   ├── class-shortcode.php      # Shortcode
│   └── class-widget.php         # Widget
├── languages/                    # Carpeta para traducciones
├── README.md                     # Este archivo
└── wp-subscribers.php           # Archivo principal del plugin
```

---

## 📞 Soporte y Contacto

**Desarrollador:** Wicho Saenz
**Sitio Web:** [www.wichosaenz.com](https://www.wichosaenz.com)
**Versión:** 1.0.0

---

## 📝 Licencia

Este plugin está licenciado bajo GPL v2 o posterior.

---

## 🔄 Changelog

### Versión 1.0.0 (2024)
- ✨ Lanzamiento inicial
- ✅ Conexión a base de datos MySQL personalizada
- ✅ Formulario de suscripción con AJAX
- ✅ Configuración de labels multiidioma
- ✅ Shortcode y widget
- ✅ Panel de administración completo
- ✅ Estilos adaptables al tema
- ✅ Validación y sanitización de datos
- ✅ Detección de emails duplicados

---

## 🙏 Agradecimientos

Gracias por usar WP Subscribers Manager. Si tienes sugerencias o encuentras algún problema, no dudes en contactar a través de [www.wichosaenz.com](https://www.wichosaenz.com).

---

**© 2024 Wicho Saenz - Todos los derechos reservados**
