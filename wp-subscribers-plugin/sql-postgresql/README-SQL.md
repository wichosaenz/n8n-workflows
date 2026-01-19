# Scripts SQL para PostgreSQL - WP Subscribers Manager v1.5.0

## 📋 Descripción

Esta carpeta contiene todos los scripts SQL necesarios para configurar y mantener la base de datos PostgreSQL para el plugin **WP Subscribers Manager v1.5.0**.

## 🗂️ Estructura de Archivos

| Archivo | Descripción | Obligatorio |
|---------|-------------|-------------|
| `00-crear-base-datos.sql` | Crea la base de datos, usuario y configura permisos | ✅ Sí |
| `01-crear-tabla-subscribers.sql` | Crea la tabla principal, índices y triggers | ✅ Sí |
| `02-datos-de-ejemplo.sql` | Inserta datos de prueba para desarrollo | ⚠️ Opcional |
| `03-consultas-utiles.sql` | Colección de consultas útiles para administración | 📚 Referencia |
| `04-mantenimiento-optimizacion.sql` | Comandos de mantenimiento y optimización | 📚 Referencia |
| `README-SQL.md` | Este archivo - Documentación de scripts | 📖 Info |

## 🚀 Guía de Instalación Rápida

### Paso 1: Crear Base de Datos y Usuario

```bash
# Conectarse a PostgreSQL como superusuario
psql -U postgres

# Ejecutar el script de creación
\i /ruta/a/sql-postgresql/00-crear-base-datos.sql

# O copiar y pegar el contenido del archivo
```

**IMPORTANTE:** Antes de ejecutar, edita el archivo `00-crear-base-datos.sql` y cambia:
```sql
PASSWORD 'tu_password_seguro';
```
Por una contraseña fuerte de tu elección.

### Paso 2: Crear Tabla e Índices

```bash
# Conectarse con el nuevo usuario
psql -U wp_subscribers_user -d wp_subscribers

# Ejecutar el script de creación de tabla
\i /ruta/a/sql-postgresql/01-crear-tabla-subscribers.sql
```

### Paso 3 (Opcional): Insertar Datos de Ejemplo

```bash
# Conectado a la base de datos wp_subscribers
\i /ruta/a/sql-postgresql/02-datos-de-ejemplo.sql
```

**NOTA:** Solo para entornos de desarrollo/pruebas.

## 🔧 Configuración en WordPress

Después de ejecutar los scripts, configura el plugin con estos datos:

```
Host:           localhost (o IP del servidor PostgreSQL)
Puerto:         5432
Base de Datos:  wp_subscribers
Usuario:        wp_subscribers_user
Contraseña:     [la que configuraste en el Paso 1]
Tabla:          subscribers
```

## 📊 Diferencias con MySQL

Si vienes de la versión anterior (MySQL), estas son las principales diferencias:

| Característica | MySQL | PostgreSQL |
|----------------|-------|------------|
| Autoincremento | `AUTO_INCREMENT` | `SERIAL` |
| Tipo fecha/hora | `DATETIME` | `TIMESTAMP` |
| Concatenación | `CONCAT()` | `\|\|` o `CONCAT()` |
| Actualización automática | `ON UPDATE CURRENT_TIMESTAMP` | Trigger + Función |
| Puerto por defecto | 3306 | 5432 |
| Comillas identificadores | `` ` `` o ninguna | `"` o ninguna |
| Insensible a mayúsculas | `LIKE` | `ILIKE` |

## 🔐 Configuración de Acceso Remoto

Si WordPress está en un servidor diferente al de PostgreSQL:

### 1. Editar `postgresql.conf`

```bash
# Encontrar el archivo
sudo find /etc -name postgresql.conf

# Editar
sudo nano /etc/postgresql/*/main/postgresql.conf
```

Cambiar:
```conf
#listen_addresses = 'localhost'
```

Por:
```conf
listen_addresses = '*'
```

O especificar IPs:
```conf
listen_addresses = '192.168.1.100,10.0.0.50'
```

### 2. Editar `pg_hba.conf`

```bash
sudo nano /etc/postgresql/*/main/pg_hba.conf
```

Agregar al final:
```conf
# Permitir conexión desde WordPress
host    wp_subscribers    wp_subscribers_user    0.0.0.0/0    md5
```

Para mayor seguridad, reemplaza `0.0.0.0/0` con la IP específica:
```conf
host    wp_subscribers    wp_subscribers_user    192.168.1.100/32    md5
```

### 3. Reiniciar PostgreSQL

```bash
# En Ubuntu/Debian
sudo systemctl restart postgresql

# O
sudo service postgresql restart
```

### 4. Verificar Puerto Abierto

```bash
# Verificar que PostgreSQL esté escuchando
sudo netstat -plunt | grep postgres

# O
sudo ss -lntp | grep postgres
```

## 🛠️ Mantenimiento

### Backup Diario

```bash
# Backup completo
pg_dump -U wp_subscribers_user -d wp_subscribers > backup_$(date +%Y%m%d).sql

# Backup comprimido
pg_dump -U wp_subscribers_user -d wp_subscribers | gzip > backup_$(date +%Y%m%d).sql.gz
```

### Restaurar Backup

```bash
# Desde archivo SQL
psql -U wp_subscribers_user -d wp_subscribers < backup_20250115.sql

# Desde archivo comprimido
gunzip -c backup_20250115.sql.gz | psql -U wp_subscribers_user -d wp_subscribers
```

### Mantenimiento Semanal

```bash
# Conectarse
psql -U wp_subscribers_user -d wp_subscribers

# Ejecutar VACUUM ANALYZE
VACUUM ANALYZE subscribers;
```

## 📈 Consultas Útiles Rápidas

```sql
-- Ver total de suscriptores
SELECT COUNT(*) FROM subscribers;

-- Últimos 10 suscriptores
SELECT name, email, subscribed_date
FROM subscribers
ORDER BY subscribed_date DESC
LIMIT 10;

-- Estadísticas por estado
SELECT status, COUNT(*)
FROM subscribers
GROUP BY status;

-- Suscriptores de hoy
SELECT COUNT(*)
FROM subscribers
WHERE DATE(subscribed_date) = CURRENT_DATE;
```

Ver más consultas en: `03-consultas-utiles.sql`

## ⚠️ Solución de Problemas

### Error: "could not connect to server"

**Causa:** PostgreSQL no está ejecutándose o no acepta conexiones.

**Solución:**
```bash
# Verificar estado
sudo systemctl status postgresql

# Iniciar si está detenido
sudo systemctl start postgresql
```

### Error: "password authentication failed"

**Causa:** Usuario o contraseña incorrectos.

**Solución:**
1. Verificar credenciales en WordPress
2. Resetear contraseña:
```sql
ALTER USER wp_subscribers_user WITH PASSWORD 'nueva_password';
```

### Error: "database does not exist"

**Causa:** La base de datos no fue creada.

**Solución:**
Ejecutar el script `00-crear-base-datos.sql` completo.

### Error: "permission denied"

**Causa:** El usuario no tiene permisos suficientes.

**Solución:**
```sql
-- Como superusuario
GRANT ALL PRIVILEGES ON DATABASE wp_subscribers TO wp_subscribers_user;
GRANT ALL PRIVILEGES ON TABLE subscribers TO wp_subscribers_user;
GRANT USAGE, SELECT ON SEQUENCE subscribers_id_seq TO wp_subscribers_user;
```

## 📚 Recursos Adicionales

- [Documentación oficial de PostgreSQL](https://www.postgresql.org/docs/)
- [PostgreSQL Tutorial](https://www.postgresqltutorial.com/)
- [Migración de MySQL a PostgreSQL](https://wiki.postgresql.org/wiki/Converting_from_other_Databases_to_PostgreSQL#MySQL)

## 🆘 Soporte

Si encuentras problemas:

1. Verifica los logs de PostgreSQL:
   ```bash
   sudo tail -f /var/log/postgresql/postgresql-*-main.log
   ```

2. Usa el botón "Probar Conexión" en el panel de WordPress

3. Revisa los logs de error de WordPress

4. Contacta al desarrollador: www.wichosaenz.com

## 📝 Changelog

### v1.5.0 (2025-01-19)
- ✨ Migración completa de MySQL a PostgreSQL
- 🔧 Uso de PDO en lugar de MySQLi
- 🛡️ Prepared statements para mayor seguridad
- ⚡ Optimización de índices para PostgreSQL
- 🔄 Triggers para actualización automática de timestamps
- 📊 Scripts de mantenimiento y consultas útiles

---

**Autor:** Wicho Saenz (www.wichosaenz.com)
**Versión del Plugin:** 1.5.0
**Versión de PostgreSQL:** 12+
**Licencia:** GPL v2 o posterior
