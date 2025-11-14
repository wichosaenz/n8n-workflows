# Guía de Scripts SQL - WP Subscribers Manager

**Versión:** 1.0.0
**Autor:** Wicho Saenz
**Sitio Web:** [www.wichosaenz.com](https://www.wichosaenz.com)

---

## 📋 Índice de Scripts SQL

Esta carpeta contiene todos los scripts SQL necesarios para configurar y gestionar la base de datos del plugin WP Subscribers Manager en Dreamhost.

### Scripts Disponibles

| Archivo | Descripción | Obligatorio | Orden |
|---------|-------------|-------------|-------|
| `01-crear-tabla-subscribers.sql` | Crea la tabla principal de suscriptores | ✅ Sí | 1° |
| `02-datos-de-ejemplo.sql` | Inserta datos de ejemplo para pruebas | ⚠️ Opcional | 2° |
| `03-consultas-utiles.sql` | Consultas útiles para gestión diaria | 📖 Referencia | - |
| `04-mantenimiento-optimizacion.sql` | Mantenimiento y optimización | 🔧 Periódico | - |
| `README-SQL.md` | Esta guía | 📚 Documentación | - |

---

## 🚀 Instalación Rápida (3 Pasos)

### Paso 1: Acceder a phpMyAdmin

1. Accede a tu panel de **Dreamhost**
2. Ve a **MySQL Databases** (Bases de datos MySQL)
3. Localiza tu base de datos y haz clic en **phpMyAdmin**
4. Ingresa tus credenciales

### Paso 2: Ejecutar Script Principal

1. En phpMyAdmin, selecciona tu base de datos en el panel izquierdo
2. Haz clic en la pestaña **SQL** en la parte superior
3. Abre el archivo `01-crear-tabla-subscribers.sql`
4. **Copia todo el contenido** del archivo
5. **Pégalo** en el editor SQL de phpMyAdmin
6. Haz clic en el botón **Continuar** (o Go)
7. Deberías ver el mensaje: "Tu consulta SQL ha sido ejecutada con éxito"

### Paso 3: Verificar Creación

1. En phpMyAdmin, haz clic en la pestaña **Estructura**
2. Deberías ver la tabla `subscribers` en la lista
3. Haz clic en la tabla para ver su estructura
4. ✅ ¡Listo! Tu tabla está creada

---

## 🗄️ Esquema de la Base de Datos

### Tabla: `subscribers`

Almacena toda la información de los suscriptores del sitio web.

#### Estructura de Columnas

| Columna | Tipo | Nulo | Clave | Default | Descripción |
|---------|------|------|-------|---------|-------------|
| `id` | INT UNSIGNED | NO | PRI | AUTO_INCREMENT | Identificador único |
| `name` | VARCHAR(255) | NO | | | Nombre del suscriptor |
| `email` | VARCHAR(255) | NO | UNI | | Email (único) |
| `subscribed_date` | DATETIME | NO | MUL | CURRENT_TIMESTAMP | Fecha de suscripción |
| `ip_address` | VARCHAR(45) | YES | | NULL | IP del suscriptor |
| `status` | VARCHAR(20) | NO | MUL | 'active' | Estado del suscriptor |
| `updated_date` | DATETIME | YES | | NULL | Última modificación |
| `source` | VARCHAR(100) | YES | | NULL | Origen de suscripción |
| `notes` | TEXT | YES | | NULL | Notas adicionales |

#### Índices

| Nombre | Tipo | Columnas | Propósito |
|--------|------|----------|-----------|
| PRIMARY | PRIMARY KEY | `id` | Clave primaria |
| idx_email_unique | UNIQUE | `email` | Evita duplicados |
| idx_status | INDEX | `status` | Búsquedas por estado |
| idx_subscribed_date | INDEX | `subscribed_date` | Ordenamiento por fecha |
| idx_status_date | INDEX | `status`, `subscribed_date` | Filtrado combinado |

#### Valores Válidos para `status`

- `active` - Suscriptor activo (default)
- `inactive` - Suscriptor inactivo
- `unsubscribed` - Dado de baja
- `bounced` - Email rebotado

#### Valores Posibles para `source`

- `shortcode` - Desde shortcode en página/entrada
- `widget` - Desde widget en sidebar
- `manual` - Agregado manualmente
- `import` - Importación masiva
- `ejemplo` - Datos de ejemplo (testing)

---

## 📊 Guía de Uso de Scripts

### Script 01: Crear Tabla (OBLIGATORIO)

**Archivo:** `01-crear-tabla-subscribers.sql`

**¿Cuándo usar?**
- Primera vez que instalas el plugin
- Si la tabla fue eliminada accidentalmente
- Para crear la tabla en una nueva base de datos

**Características:**
- ✅ Seguro ejecutar múltiples veces (usa `IF NOT EXISTS`)
- ✅ Compatible con Dreamhost (sin privilegios SUPER)
- ✅ Crea todos los índices necesarios
- ✅ Configura charset UTF-8 correctamente

**Qué hace:**
1. Configura charset UTF-8
2. Crea la tabla `subscribers` con toda su estructura
3. Crea índices para optimizar consultas
4. Muestra la estructura creada para verificación

---

### Script 02: Datos de Ejemplo (OPCIONAL)

**Archivo:** `02-datos-de-ejemplo.sql`

**¿Cuándo usar?**
- Para probar el sistema antes de usar datos reales
- Para aprender a usar las consultas SQL
- Para desarrollo y testing

**ADVERTENCIA:** No uses en producción con datos reales.

**Qué hace:**
1. Inserta 10 suscriptores de ejemplo
2. Incluye diferentes estados (active, inactive, unsubscribed, bounced)
3. Todos marcados con `source = 'ejemplo'` para fácil identificación
4. Muestra estadísticas de los datos insertados

**Cómo eliminar los datos de ejemplo:**
```sql
DELETE FROM subscribers WHERE source = 'ejemplo';
```

---

### Script 03: Consultas Útiles (REFERENCIA)

**Archivo:** `03-consultas-utiles.sql`

**¿Cuándo usar?**
- Para gestión diaria de suscriptores
- Para generar reportes
- Para exportar listas
- Para hacer modificaciones

**Contiene 25 consultas organizadas en categorías:**

#### 🔍 Listado (Consultas 1-4)
- Ver suscriptores activos
- Ver últimos suscriptores
- Buscar por email
- Buscar por nombre

#### 📊 Estadísticas (Consultas 5-9)
- Contar suscriptores totales
- Contar por estado
- Suscriptores por mes
- Suscriptores por origen
- Estadísticas generales

#### 📤 Exportación (Consultas 10-12)
- Exportar emails activos
- Exportar con nombre y email
- Exportar datos completos

#### ✏️ Modificación (Consultas 13-17)
- Cambiar estado a inactivo
- Marcar como dado de baja
- Reactivar suscriptor
- Agregar notas
- Cambios masivos

#### 🧹 Limpieza (Consultas 18-20)
- Eliminar duplicados
- Eliminar rebotados
- Eliminar por email

#### 🔬 Avanzadas (Consultas 21-25)
- Detectar duplicados por nombre
- Suscriptores recientes
- Dominios más comunes
- Tasa de crecimiento
- Verificar integridad

**Ejemplo de uso:**

```sql
-- 1. Copia la consulta que necesites del archivo
-- 2. Pégala en phpMyAdmin > pestaña SQL
-- 3. Ajusta los parámetros si es necesario
-- 4. Ejecuta

-- Ejemplo: Buscar un suscriptor
SELECT * FROM subscribers
WHERE email = 'juan@ejemplo.com';
```

---

### Script 04: Mantenimiento (PERIÓDICO)

**Archivo:** `04-mantenimiento-optimizacion.sql`

**¿Cuándo usar?**
- Mensualmente para optimización
- Cuando la tabla crece mucho
- Cuando notas lentitud en consultas
- Para limpieza de datos

**Operaciones principales:**

#### 🔧 Optimización (Ejecutar mensualmente)
```sql
ANALYZE TABLE subscribers;  -- Analiza distribución de datos
OPTIMIZE TABLE subscribers; -- Optimiza espacio y reorganiza
CHECK TABLE subscribers;    -- Verifica integridad
```

#### 📈 Estadísticas
- Ver tamaño de la tabla
- Ver información de índices
- Monitorear crecimiento

#### 🧹 Limpieza
- Normalizar datos (minúsculas en emails)
- Eliminar espacios
- Limpiar registros antiguos
- Eliminar duplicados

#### 💾 Backup
```sql
-- Crear backup con fecha
CREATE TABLE subscribers_backup_20240314 LIKE subscribers;
INSERT INTO subscribers_backup_20240314 SELECT * FROM subscribers;
```

**Calendario de mantenimiento recomendado:**

| Frecuencia | Tareas |
|------------|--------|
| **Semanal** | CHECK TABLE, verificar datos inválidos |
| **Quincenal** | ANALYZE TABLE, revisar índices |
| **Mensual** | OPTIMIZE TABLE, backup, normalizar datos |
| **Trimestral** | Limpiar datos antiguos, evaluar índices |
| **Anual** | Auditoría completa, revisar políticas |

---

## 🔒 Consideraciones de Seguridad y Privilegios

### ✅ Sin Privilegios SUPER (Dreamhost)

Todos los scripts están diseñados para funcionar sin privilegios SUPER y con binary logging habilitado.

**Operaciones SEGURAS (permitidas):**
- ✓ CREATE TABLE, ALTER TABLE, DROP TABLE
- ✓ INSERT, UPDATE, DELETE, SELECT
- ✓ CREATE INDEX, DROP INDEX
- ✓ ANALYZE TABLE, OPTIMIZE TABLE
- ✓ CHECK TABLE, REPAIR TABLE

**Operaciones BLOQUEADAS (requieren SUPER):**
- ✗ CREATE FUNCTION/PROCEDURE con DETERMINISTIC
- ✗ CREATE TRIGGER
- ✗ SET GLOBAL variables
- ✗ Binary log operations

### Error #1419 - Solución

Si ves este error:
```
#1419 - You do not have the SUPER privilege and binary logging is enabled
```

**Causa:** Intentaste crear una función, procedimiento o trigger.

**Solución:** Usa solo los scripts proporcionados, que evitan estas operaciones.

---

## 📤 Exportar e Importar Datos

### Exportar Suscriptores

#### Opción 1: phpMyAdmin
1. Selecciona la tabla `subscribers`
2. Clic en "Exportar"
3. Elige formato:
   - **CSV** - Para Excel/hojas de cálculo
   - **SQL** - Para backup completo
   - **JSON** - Para aplicaciones
4. Clic en "Continuar"

#### Opción 2: SQL Directo
```sql
-- Exportar solo emails activos
SELECT email FROM subscribers
WHERE status = 'active'
INTO OUTFILE '/tmp/emails.txt';
```

### Importar Suscriptores

```sql
-- Importar desde CSV (ajusta la ruta)
LOAD DATA INFILE '/ruta/archivo.csv'
INTO TABLE subscribers
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 ROWS
(name, email);
```

---

## 🔍 Consultas Comunes

### Ver todos los suscriptores activos
```sql
SELECT name, email, subscribed_date
FROM subscribers
WHERE status = 'active'
ORDER BY subscribed_date DESC;
```

### Contar suscriptores por estado
```sql
SELECT status, COUNT(*) as total
FROM subscribers
GROUP BY status;
```

### Buscar un suscriptor
```sql
SELECT * FROM subscribers
WHERE email = 'ejemplo@email.com';
```

### Últimos 10 suscriptores
```sql
SELECT name, email, subscribed_date
FROM subscribers
ORDER BY subscribed_date DESC
LIMIT 10;
```

### Suscriptores de hoy
```sql
SELECT name, email, DATE_FORMAT(subscribed_date, '%H:%i') as hora
FROM subscribers
WHERE DATE(subscribed_date) = CURDATE();
```

### Cambiar estado de un suscriptor
```sql
UPDATE subscribers
SET status = 'inactive'
WHERE email = 'ejemplo@email.com';
```

---

## 🐛 Solución de Problemas

### Problema: "Table already exists"
**Solución:** La tabla ya existe. Usa el script `03-consultas-utiles.sql` para trabajar con ella.

### Problema: "Access denied"
**Solución:** Verifica que estás usando el usuario correcto de la base de datos.

### Problema: "Duplicate entry for key 'idx_email_unique'"
**Solución:** Ya existe un suscriptor con ese email. Usa UPDATE en lugar de INSERT.

### Problema: La tabla está muy lenta
**Solución:** Ejecuta:
```sql
ANALYZE TABLE subscribers;
OPTIMIZE TABLE subscribers;
```

### Problema: Necesito eliminar todos los datos
**Solución:**
```sql
TRUNCATE TABLE subscribers;
```
**ADVERTENCIA:** Esto elimina TODO permanentemente.

---

## 📚 Recursos Adicionales

### Documentación de MySQL
- [CREATE TABLE](https://dev.mysql.com/doc/refman/8.0/en/create-table.html)
- [Optimización](https://dev.mysql.com/doc/refman/8.0/en/optimization.html)
- [Índices](https://dev.mysql.com/doc/refman/8.0/en/mysql-indexes.html)

### Documentación de Dreamhost
- [MySQL en Dreamhost](https://help.dreamhost.com/hc/en-us/sections/203242327-MySQL)
- [phpMyAdmin](https://help.dreamhost.com/hc/en-us/articles/214395638-phpMyAdmin-overview)

---

## 💡 Consejos y Mejores Prácticas

### 1. Backups Regulares
```sql
-- Crear backup cada mes
CREATE TABLE subscribers_backup_YYYYMMDD LIKE subscribers;
INSERT INTO subscribers_backup_YYYYMMDD SELECT * FROM subscribers;
```

### 2. Probar antes de ejecutar UPDATE/DELETE
```sql
-- Primero usa SELECT para verificar
SELECT * FROM subscribers WHERE status = 'bounced';

-- Si está correcto, entonces UPDATE/DELETE
DELETE FROM subscribers WHERE status = 'bounced';
```

### 3. Usar transacciones para operaciones críticas
```sql
START TRANSACTION;

-- Tus operaciones aquí
UPDATE subscribers SET status = 'inactive' WHERE ...;

-- Si todo está bien
COMMIT;

-- Si hay error
-- ROLLBACK;
```

### 4. Mantener índices optimizados
```sql
-- Ejecutar mensualmente
ANALYZE TABLE subscribers;
OPTIMIZE TABLE subscribers;
```

### 5. Normalizar datos regularmente
```sql
-- Convertir emails a minúsculas
UPDATE subscribers SET email = LOWER(email);

-- Eliminar espacios
UPDATE subscribers SET email = TRIM(email);
```

---

## 📞 Soporte

**Desarrollador:** Wicho Saenz
**Sitio Web:** [www.wichosaenz.com](https://www.wichosaenz.com)

Para soporte o consultas, visita www.wichosaenz.com

---

## 📝 Licencia

Este conjunto de scripts SQL es parte del plugin WP Subscribers Manager, licenciado bajo GPL v2 o posterior.

---

**© 2024 Wicho Saenz - Todos los derechos reservados**
