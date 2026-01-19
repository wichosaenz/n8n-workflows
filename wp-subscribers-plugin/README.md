# WP Subscribers Manager

Plugin de WordPress para gestionar una lista de suscriptores con base de datos PostgreSQL personalizada.

**Versión:** 1.5.0
**Autor:** Wicho Saenz
**Sitio Web:** [www.wichosaenz.com](https://www.wichosaenz.com)

---

## 📋 Descripción

WP Subscribers Manager es un plugin completo para WordPress que te permite crear y gestionar una lista de suscriptores utilizando una base de datos PostgreSQL personalizada. El plugin ofrece un formulario de suscripción totalmente personalizable que se adapta al estilo de tu tema.

### Características Principales

✅ **NUEVO v1.5.0:** 🐘 Migración completa a PostgreSQL (antes MySQL)
✅ **NUEVO v1.5.0:** 🔒 PDO con prepared statements para máxima seguridad
✅ **NUEVO v1.5.0:** ⚡ Optimización de rendimiento con triggers PostgreSQL
✅ **NUEVO v1.5.0:** 📁 Scripts SQL completos incluidos para instalación
✅ **NUEVO v1.5.0:** 🔧 Campo de puerto configurable (5432 por defecto)
✅ **NUEVO v1.5.0:** 📚 Documentación extensa de PostgreSQL incluida
✅ Conexión a base de datos PostgreSQL personalizada
✅ Configuración completa de parámetros de conexión
✅ Labels personalizables para soporte multiidioma (español/inglés)
✅ Formulario adaptable al CSS del tema activo
✅ Integración mediante shortcode o widget
✅ Validación de emails duplicados
✅ Mensajes de éxito y error personalizables
✅ Interfaz de administración intuitiva
✅ Envío de formulario con AJAX (sin recargar página)
✅ Responsive y compatible con dispositivos móviles
✅ v1.4.0: 📧 Notificaciones automáticas por email (SMTP)
✅ v1.4.0: 📬 Configuración SMTP completa (Gmail, Dreamhost, Office365, etc.)
✅ v1.4.0: 📨 Emails HTML profesionales con detalles del suscriptor
✅ v1.4.0: ✉️ Múltiples destinatarios de notificaciones (separados por comas)
✅ v1.4.0: ✅ Botón de "Enviar Email de Prueba" para verificar configuración
✅ v1.4.0: 🔔 Activar/desactivar notificaciones fácilmente
✅ v1.3.3: Simplificación de lista de suscriptores con carga instantánea
✅ v1.3.1: Internacionalización completa (i18n) con detección automática de idioma
✅ v1.3.1: Traducción al inglés incluida (en_US)
✅ v1.3.1: Archivos .pot, .po y .mo para traducciones personalizadas
✅ v1.3.0: Lista completa de suscriptores con filtros por sitio
✅ v1.3.0: Estadísticas de suscriptores en tiempo real
✅ v1.3.0: Cambio de estado de suscriptores desde el admin
✅ v1.3.0: Desuscripción inteligente con detección automática
✅ v1.3.0: Sistema de desuscripción de toda la red con razones
✅ v1.3.0: Registro de notas con timestamp para auditoría
✅ v1.2.0: Tracking automático de múltiples sitios web
✅ v1.2.0: Identificación del sitio de origen de cada suscriptor

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

### 1. Configurar Base de Datos PostgreSQL

**IMPORTANTE:** Antes de configurar el plugin en WordPress, debes preparar tu base de datos PostgreSQL.

#### Paso 1: Preparar PostgreSQL

Ejecuta los scripts SQL incluidos en la carpeta `/sql-postgresql/`:

```bash
# 1. Conectarse a PostgreSQL como superusuario
psql -U postgres

# 2. Ejecutar el script de creación de BD y usuario
\i /ruta/a/sql-postgresql/00-crear-base-datos.sql

# 3. Conectarse con el nuevo usuario
psql -U wp_subscribers_user -d wp_subscribers

# 4. Ejecutar el script de creación de tabla
\i /ruta/a/sql-postgresql/01-crear-tabla-subscribers.sql
```

Ver documentación completa en `/sql-postgresql/README-SQL.md`

#### Paso 2: Configurar Plugin en WordPress

Después de preparar la base de datos:

1. Ve a **Suscriptores** en el menú de administración de WordPress
2. Completa la sección **Configuración de Base de Datos PostgreSQL**:
   - **Host de Base de Datos**: El hostname de tu servidor PostgreSQL (ej: `localhost`, `192.168.1.100`, `postgres.ejemplo.com`)
   - **Puerto PostgreSQL**: Puerto de conexión (por defecto: `5432`)
   - **Nombre de Base de Datos**: El nombre de tu base de datos (ej: `wp_subscribers`)
   - **Usuario de Base de Datos**: Tu usuario de PostgreSQL (ej: `wp_subscribers_user`)
   - **Contraseña de Base de Datos**: La contraseña de tu usuario
   - **Nombre de Tabla**: El nombre de la tabla donde se guardarán los suscriptores (por defecto: `subscribers`)

3. Haz clic en **Probar Conexión** para verificar que los datos sean correctos
4. Haz clic en **Guardar Configuración**

> **Nota:** El plugin puede crear automáticamente la tabla si el usuario tiene permisos CREATE TABLE, pero se recomienda usar los scripts SQL incluidos.

> **Scripts SQL:** Ver carpeta `/sql-postgresql/` para todos los scripts de creación, datos de ejemplo, consultas útiles y mantenimiento.

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

### 3. Configurar Notificaciones SMTP (v1.4.0) 📧

Recibe un email automático cada vez que alguien se suscriba a tu sitio.

**Paso 1: Configurar Servidor SMTP**

Completa los siguientes campos con los datos de tu proveedor de email:

- **Servidor SMTP**: El hostname de tu servidor SMTP
  - Gmail: `smtp.gmail.com`
  - Dreamhost: `smtp.dreamhost.com`
  - Office 365: `smtp.office365.com`

- **Puerto SMTP**: Puerto de conexión
  - TLS (recomendado): `587`
  - SSL: `465`

- **Seguridad/Encriptación**: Selecciona `TLS` (recomendado)

- **Usuario / Email SMTP**: Tu email completo
  - Ejemplo: `tu-email@ejemplo.com`

- **Contraseña SMTP**: La contraseña de tu cuenta
  - **Nota para Gmail**: Debes usar una [Contraseña de Aplicación](https://support.google.com/accounts/answer/185833)

- **Nombre del Remitente**: Nombre que aparecerá como remitente
  - Por defecto: El nombre de tu sitio

**Paso 2: Probar Configuración**

1. Ingresa un email de prueba en el campo debajo de la configuración SMTP
2. Haz clic en **"Enviar Email de Prueba"**
3. Verifica que llegue el email de prueba a tu bandeja de entrada
4. Si hay errores, revisa tu configuración SMTP

**Paso 3: Configurar Destinatarios**

- **Activar Notificaciones**: Marca la casilla para activar
- **Destinatarios**: Ingresa los emails que recibirán notificaciones, separados por comas
  ```
  admin@ejemplo.com, ventas@ejemplo.com, marketing@ejemplo.com
  ```
- **Asunto del Email**: Personaliza el asunto (por defecto: "🎉 Nueva suscripción en TuSitio")

**Ejemplo de Email de Notificación:**

El email incluye:
- 👤 Nombre del suscriptor
- 📧 Email del suscriptor
- 📅 Fecha y hora de suscripción
- 🌐 Sitio web de origen
- 🖥️ Dirección IP
- 📍 Origen (shortcode, widget, etc.)
- Botón para ver la lista completa de suscriptores

**Proveedores SMTP Recomendados:**

| Proveedor | Servidor SMTP | Puerto | Seguridad |
|-----------|---------------|--------|-----------|
| Gmail | smtp.gmail.com | 587 | TLS |
| Dreamhost | smtp.dreamhost.com | 587 | TLS |
| Office 365 | smtp.office365.com | 587 | TLS |
| Outlook.com | smtp-mail.outlook.com | 587 | TLS |
| Yahoo | smtp.mail.yahoo.com | 587 | TLS |

**Notas Importantes:**

⚠️ **Gmail**: Necesitas generar una "Contraseña de Aplicación" en tu cuenta de Google
⚠️ **Verificación en 2 pasos**: Si tu email tiene verificación en 2 pasos, usa una contraseña de aplicación
⚠️ **Dreamhost**: Asegúrate de que tu cuenta de email esté creada en el panel de Dreamhost

---

## 🌍 Internacionalización (i18n)

El plugin **se adapta automáticamente al idioma de tu sitio WordPress** sin necesidad de configuración adicional.

### Idiomas Soportados

📌 **Español (es_ES)** - Idioma por defecto
🇺🇸 **Inglés (en_US)** - Incluido desde v1.3.1

### Cómo Funciona

El plugin detecta el idioma configurado en WordPress (`Settings > General > Site Language`) y muestra automáticamente los textos en ese idioma.

**Ejemplo:**
- Si tu sitio está en inglés → El plugin se muestra en inglés
- Si tu sitio está en español → El plugin se muestra en español

### Archivos de Traducción

Ubicación: `/languages/`

| Archivo | Descripción |
|---------|-------------|
| `wp-subscribers.pot` | Plantilla para nuevas traducciones (usar con Poedit) |
| `wp-subscribers-en_US.po` | Archivo de traducción al inglés (editable) |
| `wp-subscribers-en_US.mo` | Archivo binario de traducción al inglés |

### Agregar Nuevos Idiomas

1. **Descarga Poedit** (https://poedit.net/)
2. Abre el archivo `wp-subscribers.pot` con Poedit
3. Crea una nueva traducción seleccionando tu idioma (ej: francés `fr_FR`)
4. Traduce todas las strings
5. Guarda el archivo - Poedit generará automáticamente:
   - `wp-subscribers-fr_FR.po` (editable)
   - `wp-subscribers-fr_FR.mo` (binario)
6. Sube ambos archivos a `/wp-content/plugins/wp-subscribers-plugin/languages/`
7. Cambia el idioma de WordPress a francés → ¡Listo!

### Textos Traducibles

El plugin traduce **todos los elementos**:
- ✅ Formulario de suscripción (frontend)
- ✅ Formulario de desuscripción (frontend)
- ✅ Página de administración
- ✅ Lista de suscriptores
- ✅ Mensajes de éxito y error
- ✅ Botones y etiquetas
- ✅ Estadísticas y estados

---

## 📊 Scripts SQL

El plugin incluye un conjunto completo de scripts SQL para gestionar la base de datos de manera profesional. Todos los scripts están optimizados para **Dreamhost sin privilegios SUPER** y con binary logging habilitado.

### 📁 Scripts Disponibles

Ubicación: `/sql/`

| Archivo | Descripción | Obligatorio |
|---------|-------------|-------------|
| **00-instalacion-rapida.sql** | Script todo-en-uno para instalación rápida | ⚡ Recomendado |
| **01-crear-tabla-subscribers.sql** | Crea la tabla principal de suscriptores | ✅ Sí |
| **02-datos-de-ejemplo.sql** | Inserta datos de prueba (10 registros) | ⚠️ Opcional |
| **03-consultas-utiles.sql** | 25 consultas para gestión diaria | 📖 Referencia |
| **04-mantenimiento-optimizacion.sql** | Mantenimiento y optimización periódica | 🔧 Mensual |
| **README-SQL.md** | Documentación completa de SQL | 📚 Guía |

### 🚀 Instalación Rápida con SQL

Si prefieres crear la tabla manualmente antes de configurar el plugin:

1. **Accede a phpMyAdmin** en tu panel de Dreamhost
2. **Selecciona tu base de datos**
3. Haz clic en la pestaña **SQL**
4. **Copia y pega** el contenido de `00-instalacion-rapida.sql`
5. Haz clic en **Continuar**
6. ✅ ¡Tabla creada!

### 📋 Estructura de la Tabla

El plugin crea una tabla `subscribers` con la siguiente estructura:

```sql
CREATE TABLE subscribers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    subscribed_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    updated_date DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    source VARCHAR(100) NULL,
    website_url VARCHAR(255) NULL,
    notes TEXT NULL,
    -- Índices para optimización
    INDEX idx_status (status),
    INDEX idx_subscribed_date (subscribed_date),
    INDEX idx_status_date (status, subscribed_date),
    INDEX idx_website_url (website_url(100))
) ENGINE=InnoDB CHARSET=utf8mb4;
```

**Campos principales:**
- `id` - Identificador único autoincremental
- `name` - Nombre del suscriptor
- `email` - Email único del suscriptor
- `subscribed_date` - Fecha/hora de suscripción
- `ip_address` - IP del visitante (IPv4/IPv6)
- `status` - Estado: `active`, `inactive`, `unsubscribed`, `bounced`
- `source` - Origen: `shortcode`, `widget`, `manual`, `import`
- **`website_url`** - 🆕 **v1.2.0:** URL del sitio WordPress donde se registró (para múltiples sitios)

### 💡 Consultas Útiles Rápidas

```sql
-- Ver todos los suscriptores activos
SELECT name, email, subscribed_date
FROM subscribers
WHERE status = 'active'
ORDER BY subscribed_date DESC;

-- Contar suscriptores por estado
SELECT status, COUNT(*) as total
FROM subscribers
GROUP BY status;

-- Exportar emails para newsletter
SELECT email FROM subscribers
WHERE status = 'active'
ORDER BY email;

-- Buscar suscriptor
SELECT * FROM subscribers
WHERE email = 'ejemplo@email.com';

-- 🆕 v1.2.0: Suscriptores por sitio web
SELECT website_url, COUNT(*) as total
FROM subscribers
GROUP BY website_url
ORDER BY total DESC;

-- 🆕 v1.2.0: Ver suscriptores de un sitio específico
SELECT name, email, subscribed_date
FROM subscribers
WHERE website_url = 'https://tusitio.com'
ORDER BY subscribed_date DESC;
```

### 🔧 Mantenimiento Periódico

Ejecuta estas consultas mensualmente para mantener el rendimiento:

```sql
-- Analizar y optimizar tabla
ANALYZE TABLE subscribers;
OPTIMIZE TABLE subscribers;

-- Verificar integridad
CHECK TABLE subscribers;
```

### ⚠️ Consideraciones Importantes

**Error #1419 - Sin privilegios SUPER:**

Si ves este error en Dreamhost:
```
#1419 - You do not have the SUPER privilege and binary logging is enabled
```

✅ **Solución:** Usa solo los scripts SQL proporcionados. Están diseñados específicamente para funcionar sin privilegios SUPER.

🚫 **Evita crear:**
- Funciones (FUNCTION) con DETERMINISTIC
- Procedimientos almacenados (PROCEDURE)
- Triggers (TRIGGER)

✅ **Puedes usar sin problema:**
- CREATE TABLE, ALTER TABLE, DROP TABLE
- INSERT, UPDATE, DELETE, SELECT
- CREATE INDEX, DROP INDEX
- ANALYZE, OPTIMIZE, CHECK, REPAIR

### 📚 Documentación Completa

Para más detalles sobre el uso de SQL, consulta:
- **sql/README-SQL.md** - Guía completa de scripts SQL
- **sql/03-consultas-utiles.sql** - 25 consultas listas para usar
- **sql/04-mantenimiento-optimizacion.sql** - Guía de mantenimiento

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
│   ├── class-admin.php                    # Panel de administración
│   └── class-subscribers-list.php         # 🆕 v1.3.0: Lista de suscriptores
├── assets/
│   ├── css/
│   │   ├── admin.css                      # Estilos del admin
│   │   └── public.css                     # Estilos públicos (con unsubscribe UI)
│   └── js/
│       └── public.js                      # JavaScript público (con unsubscribe)
├── includes/
│   ├── class-database.php                 # Manejo de base de datos (con métodos v1.3.0)
│   ├── class-form-handler.php             # Procesamiento del formulario (con unsubscribe)
│   ├── class-shortcode.php                # Shortcode
│   └── class-widget.php                   # Widget
├── languages/                              # Archivos de traducción (i18n)
│   ├── wp-subscribers.pot                 # Plantilla de traducción
│   ├── wp-subscribers-en_US.po            # Traducción al inglés (editable)
│   └── wp-subscribers-en_US.mo            # Traducción al inglés (binario)
├── sql/                                    # Scripts SQL
│   ├── 00-instalacion-rapida.sql          # Instalación rápida todo-en-uno
│   ├── 01-crear-tabla-subscribers.sql     # Crear tabla principal
│   ├── 02-datos-de-ejemplo.sql            # Datos de prueba
│   ├── 03-consultas-utiles.sql            # 25 consultas útiles
│   ├── 04-mantenimiento-optimizacion.sql  # Mantenimiento periódico
│   └── README-SQL.md                      # Documentación de SQL
├── .gitignore                              # Archivos ignorados por git
├── INSTALACION.txt                         # Guía de instalación
├── README.md                               # Este archivo
└── wp-subscribers.php                      # Archivo principal del plugin
```

---

## 📞 Soporte y Contacto

**Desarrollador:** Wicho Saenz
**Sitio Web:** [www.wichosaenz.com](https://www.wichosaenz.com)
**Versión:** 1.5.0

---

## 📝 Licencia

Este plugin está licenciado bajo GPL v2 o posterior.

---

## 🔄 Changelog

### Versión 1.5.0 (2025) 🐘

**Migración completa a PostgreSQL** - Cambio de motor de base de datos de MySQL a PostgreSQL para mayor escalabilidad, rendimiento y características avanzadas.

#### Cambios Principales:
- 🐘 **PostgreSQL como motor de BD**: Migración completa de MySQL/MySQLi a PostgreSQL con PDO
- 🔒 **Prepared Statements**: Uso de PDO con prepared statements para máxima seguridad contra SQL injection
- 🔧 **Campo de puerto configurable**: Nuevo campo "Puerto PostgreSQL" en panel de administración (por defecto 5432)
- ⚡ **Triggers automáticos**: Sistema de triggers PostgreSQL para actualización automática de `updated_date`
- 🎯 **Funciones PL/pgSQL**: Funciones en PostgreSQL para automatización de timestamps
- 📊 **Tipos de datos optimizados**: Uso de `SERIAL` (autoincremento), `TIMESTAMP`, `VARCHAR`, `TEXT`
- 🔄 **Operador de concatenación**: Cambio de `CONCAT()` a `||` (operador nativo de PostgreSQL)
- 🚀 **Índices optimizados**: Recreación de todos los índices específicamente para PostgreSQL

#### Scripts SQL PostgreSQL:
- 📁 **Nueva carpeta**: `/sql-postgresql/` con todos los scripts adaptados
- 📄 `00-crear-base-datos.sql`: Creación de BD, usuario y permisos
- 📄 `01-crear-tabla-subscribers.sql`: Tabla principal con índices y triggers
- 📄 `02-datos-de-ejemplo.sql`: 20 registros de ejemplo para pruebas
- 📄 `03-consultas-utiles.sql`: +50 consultas útiles para administración
- 📄 `04-mantenimiento-optimizacion.sql`: VACUUM, ANALYZE, REINDEX, backups
- 📖 `README-SQL.md`: Documentación completa de instalación y configuración

#### Cambios Técnicos:
- 🔨 **class-database.php**: Reescritura completa con PDO en lugar de MySQLi
- 🔨 **class-admin.php**: Actualizado para agregar campo de puerto y textos de PostgreSQL
- 🔨 **wp-subscribers.php**: Versión actualizada a 1.5.0
- 📝 **README.md**: Documentación actualizada con instrucciones de PostgreSQL
- 🔐 **Conexión segura**: DSN con opciones de encoding UTF8
- ⚙️ **Error handling**: Manejo mejorado de excepciones PDO específicas de PostgreSQL

#### Compatibilidad:
- ✅ **PostgreSQL 12+**: Compatible con todas las versiones modernas de PostgreSQL
- ✅ **PHP 7.4+**: Requiere extensión PDO_PGSQL habilitada
- ✅ **WordPress 5.0+**: Mantiene compatibilidad con versiones recientes de WordPress
- ⚠️ **Migración desde MySQL**: Ver scripts de migración en `/sql-postgresql/`

#### Beneficios de PostgreSQL:
- 🎯 **ACID completo**: Transacciones más confiables
- 📈 **Mejor rendimiento**: Optimización para consultas complejas
- 🔍 **JSON nativo**: Soporte para tipos de datos avanzados
- 🌐 **Escalabilidad**: Mejor manejo de grandes volúmenes de datos
- 🛡️ **Seguridad robusta**: Sistema de permisos granular
- 🔄 **Replicación avanzada**: Soporte nativo para alta disponibilidad

### Versión 1.4.0 (2024) 📧
- 🆕 **Notificaciones automáticas por email**: Recibe un email cada vez que alguien se suscriba
- 🆕 **Configuración SMTP completa**: Soporta Gmail, Dreamhost, Office365, y cualquier servidor SMTP
- 🆕 **Emails HTML profesionales**: Plantilla elegante con gradientes morados y toda la información del suscriptor
- 🆕 **Múltiples destinatarios**: Configura varios emails separados por comas para recibir notificaciones
- 🆕 **Botón de prueba de email**: Verifica tu configuración SMTP antes de activar las notificaciones
- 🆕 **Activar/desactivar notificaciones**: Control fácil con un checkbox
- 🆕 **Asunto personalizable**: Configura el asunto del email de notificación
- 🆕 **Detalles completos en el email**: Nombre, email, fecha/hora, sitio web, IP y origen del suscriptor
- 🆕 **Integración automática**: Los emails se envían automáticamente al insertar un nuevo suscriptor
- 🆕 **No bloqueante**: El envío de email no afecta la experiencia del usuario en el frontend
- 📧 **Clase WP_Subscribers_Email_Notifications**: Nueva clase dedicada para manejo de emails
- 📁 **Archivo**: includes/class-email-notifications.php (300+ líneas)
- 🔧 **AJAX endpoint**: wp_ajax_wp_subscribers_test_email para probar configuración
- 🎨 **Diseño del email**: Gradiente morado (#667eea → #764ba2), responsive, formato HTML
- 🔒 **Seguridad SMTP**: Soporte para TLS, SSL y sin encriptación
- 📝 **Documentación completa**: Guía paso a paso para configurar SMTP en README.md

### Versión 1.3.3 (2024) ⚡
- 🎯 **Simplificación de Lista de Suscriptores**: Eliminada complejidad de AJAX - renderizado directo en PHP
- ⚡ **Rendimiento mejorado**: Carga inmediata de la lista sin esperas ni loading spinners
- 🐛 **Fix definitivo**: Resuelto problema de lista que se quedaba en "Loading subscribers..."
- 🔧 **HTML puro**: Eliminado JavaScript complejo - ahora usa formularios HTML simples
- 📊 **Estadísticas instantáneas**: Datos cargados directamente desde PHP sin AJAX
- 🚀 **Diagnóstico mejorado**: Mensaje de error claro si hay problemas de conexión a BD
- 💻 **Debugging simplificado**: Errores visibles inmediatamente en lugar de fallos silenciosos
- 🎨 **UI limpia**: Eliminados modales y scripts innecesarios para enfocarse en productividad
- ✅ **Filtros funcionales**: Sistema de filtros por sitio usando GET en lugar de AJAX
- 📱 **Más rápido y confiable**: Sin dependencias de jQuery ni problemas de nonce

### Versión 1.3.2 (2024) 🔧
- 🐛 **Fix crítico**: Corregido error en Lista de Suscriptores que quedaba en "Cargando..."
- 🔧 **AJAX corregido**: Agregado wpSubscribersListI18n.ajaxUrl y wpSubscribersListI18n.nonce
- ⚡ **Mejora de rendimiento**: wp_enqueue_script('jquery') para asegurar disponibilidad
- 📝 **Mensajes actualizados**: Cambio de "20 sitios de la red" a "newsletter mensual"
- ✍️ **Frontend mejorado**: Mensajes más claros sobre darse de baja del newsletter
- ✍️ **Backend mejorado**: Mensajes simplificados en panel de administración
- 🌐 **Traducciones actualizadas**: Archivo .po y .mo actualizados con nuevos mensajes
- 📱 **UX mejorada**: Confirmaciones más amigables y descriptivas
- 🎯 **Corrección JavaScript**: Uso correcto de variables localizadas en todos los AJAX calls

### Versión 1.3.1 (2024) 🌍
- 🆕 **Internacionalización completa (i18n)**: Detección automática del idioma de WordPress
- 🆕 **Traducción al inglés (en_US)**: Incluida y lista para usar
- 🆕 **Archivos .pot, .po y .mo**: Para traducciones personalizadas con Poedit
- 🆕 **Frontend traducible**: Formulario de suscripción y desuscripción en múltiples idiomas
- 🆕 **Backend traducible**: Panel de administración y lista de suscriptores en múltiples idiomas
- 🆕 **JavaScript localizado**: Todos los mensajes dinámicos traducibles
- 🆕 **Documentación i18n**: Guía completa para agregar nuevos idiomas
- ⚡ **Mejoras en class-subscribers-list.php**: wp_localize_script() para traducciones JavaScript
- 📚 **README actualizado**: Nueva sección de internacionalización con ejemplos

### Versión 1.3.0 (2024) 🎉
- 🆕 **Lista completa de suscriptores**: Nueva página de administración que muestra todos los suscriptores con tabla interactiva
- 🆕 **Estadísticas en tiempo real**: Dashboard con 5 tarjetas mostrando total, activos, inactivos, desuscritos y rebotados
- 🆕 **Filtros por sitio web**: Toggle para ver suscriptores solo del sitio actual o de toda la red (~20 sitios)
- 🆕 **Gestión de estados**: Cambiar estado de suscriptores (active, inactive, unsubscribed, bounced) desde el admin
- 🆕 **Modal de edición**: Interfaz moderna para cambiar estado con campo de notas
- 🆕 **Desuscripción inteligente**: Detecta automáticamente cuando un email ya existe al intentar suscribirse
- 🆕 **UI de desuscripción**: Formulario frontend que aparece automáticamente para suscriptores existentes
- 🆕 **Desuscripción de red completa**: Botón para desuscribir de TODOS los sitios de la red con advertencia clara
- 🆕 **Sistema de razones**: Campo de texto para que el usuario indique por qué se desuscribe
- 🆕 **Registro de notas con timestamp**: Todas las acciones quedan registradas con fecha y hora en el campo notes
- 🆕 **Confirmación de acciones críticas**: Diálogo de confirmación JavaScript para desuscripción de toda la red
- 🆕 **Botones de cancelar**: Opción de volver atrás en cualquier momento del proceso de desuscripción
- 🆕 **AJAX completo**: Todas las operaciones sin recargar página (lista, filtros, cambios de estado, desuscripción)
- 🆕 **CSS mejorado**: Estilos para tabla de suscriptores, modales, botones de peligro/secundarios, y formulario unsubscribe
- 🆕 **Responsive design**: Interfaz optimizada para móviles con botones apilados y formularios adaptables
- ⚡ **Métodos de base de datos nuevos**: get_subscribers(), get_statistics(), update_subscriber_status(), unsubscribe_from_all_sites(), get_subscriber_by_email()
- 📱 **UX optimizada**: Mensajes claros, advertencias visuales, y flujo intuitivo para el usuario final
- 🔒 **Seguridad mejorada**: Verificación de nonce en todas las operaciones AJAX públicas y privadas

### Versión 1.2.1 (2024)
- 🆕 **Sistema de debug por capas**: Validación detallada con 6 capas de diagnóstico de conexión
- 🆕 **Mensajes amigables y técnicos**: user_message para el usuario y debug_message para soporte técnico
- 🆕 **Identificación de errores MySQL**: Análisis específico de errores 2002, 2003, 2005, 2006, 1045, 1049, 1044, 2013
- 🆕 **Guías contextuales**: Cada error incluye pasos específicos para solucionarlo
- 🆕 **UI mejorada en admin**: Muestra mensajes de debug en panel amarillo con formato código
- 📚 **Documentación de troubleshooting**: Explicación detallada del sistema de capas

### Versión 1.2.0 (2024)
- 🆕 **Tracking de múltiples sitios web**: Ahora registra automáticamente la URL del sitio donde se suscribe cada usuario
- 🆕 **Campo `website_url`**: Nuevo campo en la base de datos para identificar el sitio de origen
- 🆕 **Índice optimizado**: Nuevo índice en `website_url` para búsquedas rápidas
- 🆕 **Consultas SQL nuevas**: 4 consultas adicionales para análisis por sitio web
- ✨ **Perfecto para múltiples sitios**: Ideal cuando usas la misma base de datos para varios sitios WordPress
- 📊 **Scripts SQL actualizados**: Todos los scripts incluyen el nuevo campo
- 📚 **Documentación mejorada**: Guías específicas para uso con múltiples sitios

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
