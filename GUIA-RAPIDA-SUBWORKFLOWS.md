# Guía Rápida: Implementación con Subworkflows

## Instalación en 15 Minutos

### Prerequisitos

✅ n8n Community Edition v1.0+ instalado
✅ Cuenta de Google (para Google Sheets)
✅ API Key de Anthropic o Groq (Groq es gratis)
✅ Acceso sFTP a tus servidores
✅ WordPress REST API habilitada en sitios

---

## Paso 1: Importar los 3 Workflows (3 min)

### 1.1 Importar MAIN Workflow

```bash
n8n UI → Workflows → Import from File
→ Seleccionar: llms-txt-MAIN-workflow.json
→ Click "Import"
```

**Resultado**: Workflow "LLMS.txt MAIN - Orchestrator" creado

### 1.2 Importar Process Site Subworkflow

```bash
n8n UI → Workflows → Import from File
→ Seleccionar: llms-txt-PROCESS-SITE-subworkflow.json
→ Click "Import"
```

**Resultado**: Workflow "LLMS.txt SUBWORKFLOW - Process Site" creado

### 1.3 Importar Generate Insights Subworkflow

```bash
n8n UI → Workflows → Import from File
→ Seleccionar: llms-txt-GENERATE-INSIGHTS-subworkflow.json
→ Click "Import"
```

**Resultado**: Workflow "LLMS.txt SUBWORKFLOW - Generate AI Insights" creado

---

## Paso 2: Obtener IDs de Subworkflows (2 min)

### 2.1 ID del Process Site Subworkflow

```bash
1. n8n UI → Abrir workflow "LLMS.txt SUBWORKFLOW - Process Site"
2. Copiar ID de la URL:
   https://tu-n8n.com/workflow/abc123...
                                ^^^^^^ COPIAR ESTE ID
3. Guardar como: PROCESS_SITE_WORKFLOW_ID
```

### 2.2 ID del Generate Insights Subworkflow

```bash
1. n8n UI → Abrir workflow "LLMS.txt SUBWORKFLOW - Generate AI Insights"
2. Copiar ID de la URL
3. Guardar como: GENERATE_INSIGHTS_WORKFLOW_ID
```

---

## Paso 3: Configurar IDs en Workflows (2 min)

### 3.1 En MAIN Workflow

```bash
1. Abrir "LLMS.txt MAIN - Orchestrator"
2. Click en nodo "Set Subworkflow IDs"
3. Editar valores:
   - process_site_workflow_id: PEGAR_ID_DE_PROCESS_SITE
   - generate_insights_workflow_id: PEGAR_ID_DE_GENERATE_INSIGHTS
4. Guardar
```

### 3.2 En Process Site Subworkflow

```bash
1. Abrir "LLMS.txt SUBWORKFLOW - Process Site"
2. Click en nodo "Get AI Workflow ID"
3. Editar valor:
   - ai_workflow_id: PEGAR_ID_DE_GENERATE_INSIGHTS
4. Guardar
```

---

## Paso 4: Configurar Credenciales (5 min)

### 4.1 Google Sheets OAuth2

```bash
1. n8n UI → Credentials → New Credential → Google Sheets OAuth2
2. Seguir instrucciones para autenticar
3. Nombre: "Google Sheets - Sitios Config"
```

[Ver guía detallada](./GUIA-IMPLEMENTACION.md#1-google-sheets-oauth2)

### 4.2 Anthropic API (o Groq GRATIS)

**Opción A: Anthropic (Recomendado)**
```bash
1. Obtener API key: https://console.anthropic.com/
2. n8n UI → Credentials → New → HTTP Header Auth
3. Header Name: x-api-key
4. Header Value: sk-ant-XXXXXXXXX
5. Nombre: "Anthropic Claude API"
```

**Opción B: Groq (Gratis)**
```bash
1. Obtener API key: https://console.groq.com/
2. Seguir mismo proceso
3. Header Name: Authorization
4. Header Value: Bearer gsk_XXXXXXXXX
5. En el subworkflow AI, cambiar URL a:
   https://api.groq.com/openai/v1/chat/completions
```

[Ver configuración de Groq](./ai-providers-config.md#2-groq-gratis)

### 4.3 Conectar Credenciales en Workflows

**En "Generate AI Insights" Subworkflow**:
```bash
1. Abrir el subworkflow
2. Click en nodo "Anthropic - Generate Insight"
3. En "Credentials" → Seleccionar tu credencial de Anthropic
4. Guardar
```

**En "MAIN" Workflow**:
```bash
1. Abrir MAIN workflow
2. Click en nodo "Google Sheets - Config"
3. En "Credentials" → Seleccionar tu credencial de Google Sheets
4. Guardar
```

---

## Paso 5: Crear Google Sheet (2 min)

### 5.1 Crear Hoja de Cálculo

```bash
1. Google Sheets → Nueva hoja de cálculo
2. Nombrar: "LLMS.txt - Sitios Config"
3. Copiar estructura de google-sheets-template.csv
```

### 5.2 Estructura Requerida

| Sitio_URL | WP_API_Key | sFTP_Host | sFTP_User | sFTP_Pass | Lookback_Days |
|-----------|------------|-----------|-----------|-----------|---------------|
| https://tu-sitio.com | wp_xxx | ftp.sitio.com | user | pass | 30 |

**Campos obligatorios**:
- `Sitio_URL`: URL completa del sitio WordPress
- `WP_API_Key`: Application Password de WordPress
- `sFTP_Host`: Hostname o IP del servidor sFTP
- `sFTP_User`: Usuario sFTP
- `sFTP_Pass`: Contraseña sFTP
- `Lookback_Days`: Días hacia atrás para obtener artículos (30-90 recomendado)

### 5.3 Obtener WordPress Application Password

```bash
1. WordPress Admin → Usuarios → Tu Perfil
2. Scroll hasta "Application Passwords"
3. Nombre: "n8n LLMS Updater"
4. Click "Add New Application Password"
5. Copiar el password generado (formato: xxxx xxxx xxxx xxxx)
6. Pegar en columna WP_API_Key (sin espacios)
```

---

## Paso 6: Configurar sFTP Dinámico (1 min)

### Instalar ssh2-sftp-client

**Si usas Docker**:
```bash
docker exec -it n8n npm install ssh2-sftp-client
```

**Si usas instalación directa**:
```bash
cd /ruta/a/n8n
npm install ssh2-sftp-client
```

**Nota**: Esto es necesario para el nodo "sFTP Upload (Dynamic)" en el subworkflow Process Site.

---

## Paso 7: Primera Ejecución de Prueba (2 min)

### 7.1 Configurar 1 Sitio de Prueba

```bash
1. En Google Sheet, agregar SOLO 1 sitio en la primera fila (más header)
2. Verificar que todos los datos sean correctos
```

### 7.2 Conectar Google Sheet en MAIN Workflow

```bash
1. Abrir "LLMS.txt MAIN - Orchestrator"
2. Click en nodo "Google Sheets - Config"
3. Pegar URL de tu Google Sheet
4. Verificar que Sheet Name = "Sheet1" (o el nombre correcto)
5. Guardar
```

### 7.3 Ejecutar

```bash
1. En el MAIN workflow, click "Execute Workflow"
2. Observar la ejecución:
   ✅ Google Sheets lee el sitio
   ✅ Valida datos
   ✅ Llama a subworkflow "Process Site"
   ✅ (En Process Site) Llama a WordPress API
   ✅ (En Process Site) Llama a subworkflow "Generate AI Insights"
   ✅ (En AI) Procesa artículos y genera insights
   ✅ (En Process Site) Construye llms.txt y sube vía sFTP
   ✅ (En MAIN) Log Success
   ✅ Reporte Final
```

### 7.4 Verificar Resultado

```bash
# Verificar que el archivo se subió
curl https://tu-sitio.com/llms.txt

# Deberías ver un archivo Markdown con:
# - Header del sitio
# - Lista de artículos con insights
# - Footer con metadata
```

---

## Paso 8: Escalar a Múltiples Sitios (1 min)

### 8.1 Agregar Más Sitios

```bash
1. En Google Sheet, agregar filas con tus 20 sitios
2. Verificar datos de cada sitio
3. Guardar
```

### 8.2 Ejecutar Producción

```bash
1. En MAIN workflow, click "Execute Workflow"
2. Observar logs en tiempo real
3. Esperar a que termine (aprox. 1-2 min por sitio)
4. Revisar reporte final
```

---

## Troubleshooting Rápido

### Error: "Workflow not found"

**Causa**: IDs de subworkflows no configurados correctamente

**Solución**:
```bash
1. Verificar que copiaste los IDs completos
2. Verificar que pegaste en los nodos correctos:
   - MAIN → "Set Subworkflow IDs"
   - Process Site → "Get AI Workflow ID"
3. Guardar y reintentar
```

### Error: "WP_API_ERROR" en logs

**Causa**: WordPress API no accesible o API Key inválida

**Solución**:
```bash
1. Verificar que el sitio esté online: curl https://tu-sitio.com
2. Verificar API REST: curl https://tu-sitio.com/wp-json/wp/v2/posts
3. Regenerar Application Password en WordPress
4. Actualizar en Google Sheet
```

### Error: "SFTP_ERROR" en logs

**Causa**: Credenciales sFTP incorrectas o servidor no accesible

**Solución**:
```bash
1. Verificar conectividad: sftp usuario@host
2. Verificar credenciales
3. Verificar que ssh2-sftp-client esté instalado
4. Verificar logs detallados en el nodo "sFTP Upload (Dynamic)"
```

### Error: AI retorna insights genéricos

**Causa**: AI API Key inválida o límite de rate excedido

**Solución**:
```bash
1. Verificar credencial de Anthropic/Groq
2. Verificar límites de API:
   - Anthropic: https://console.anthropic.com/
   - Groq: https://console.groq.com/
3. Si es Groq y excediste 6K req/día, esperar 24h o cambiar a Anthropic
```

### Ningún sitio se procesa

**Causa**: Google Sheet no conectado o vacío

**Solución**:
```bash
1. Verificar que el nodo "Google Sheets - Config" tenga:
   - Credential seleccionada
   - Document ID correcto
   - Sheet Name correcto
2. Ejecutar solo ese nodo para verificar que retorna datos
```

---

## Monitoreo de Ejecución

### Ver Logs en Tiempo Real

```bash
1. Durante ejecución, click en cada nodo para ver output
2. Los nodos "Log Success" y "Log Error" muestran resumen
3. El nodo "Generar Reporte Final" muestra estadísticas completas
```

### Logs de Consola (servidor n8n)

```bash
# Si ejecutas n8n en terminal
# Verás logs con emojis:
✅ Sitio procesado exitosamente: infraestructura1.com
❌ Error procesando sitio: logistica2.com - WP_API_ERROR
⚠️ Validación fallida: transporte3.com - Missing WP_API_Key
📊 REPORTE FINAL DE EJECUCIÓN...
```

---

## Programar Ejecución Automática

### Agregar Trigger de Cron

```bash
1. En MAIN workflow, agregar nodo al inicio:
   Schedule Trigger
   → Mode: Every Week
   → Weekday: Monday
   → Hour: 03:00
   → Timezone: America/Mexico_City

2. Conectar Schedule Trigger → "Google Sheets - Config"

3. Activar workflow (switch "Active" en ON)

4. Guardar
```

**Resultado**: El workflow se ejecutará automáticamente todos los lunes a las 3 AM.

---

## Resumen de Archivos

| Archivo | Propósito |
|---------|-----------|
| `llms-txt-MAIN-workflow.json` | Workflow principal - Orquestador |
| `llms-txt-PROCESS-SITE-subworkflow.json` | Subworkflow - Procesa 1 sitio |
| `llms-txt-GENERATE-INSIGHTS-subworkflow.json` | Subworkflow - Genera insights AI |
| `ARQUITECTURA-SUBWORKFLOWS.md` | Documentación técnica detallada |
| `GUIA-IMPLEMENTACION.md` | Guía completa paso a paso |
| `ai-providers-config.md` | Configuración de AI providers |
| `sftp-dynamic-upload-code.js` | Código auxiliar para sFTP |
| `google-sheets-template.csv` | Template de hoja de cálculo |

---

## Próximos Pasos

### Optimizaciones Opcionales

- [ ] Configurar notificaciones (Email/Slack) al terminar
- [ ] Exportar reporte a Google Sheets
- [ ] Agregar más providers AI como fallback
- [ ] Implementar caché de insights
- [ ] Configurar backup de llms.txt antes de sobrescribir

### Recursos

- **Documentación Completa**: [GUIA-IMPLEMENTACION.md](./GUIA-IMPLEMENTACION.md)
- **Arquitectura Detallada**: [ARQUITECTURA-SUBWORKFLOWS.md](./ARQUITECTURA-SUBWORKFLOWS.md)
- **Configuración AI**: [ai-providers-config.md](./ai-providers-config.md)
- **Comunidad n8n**: https://community.n8n.io/

---

**¿Dudas?** Revisa la [documentación completa](./GUIA-IMPLEMENTACION.md) o abre un issue en el repositorio.

**Versión**: 2.0 con Subworkflows
**Tiempo estimado de setup**: 15 minutos
**Compatibilidad**: n8n Community Edition v1.0+
