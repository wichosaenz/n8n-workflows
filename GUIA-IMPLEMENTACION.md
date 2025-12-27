# Guía de Implementación: LLMS.txt Auto-Updater

## Índice
1. [Requisitos Previos](#requisitos-previos)
2. [Configuración de Credenciales](#configuración-de-credenciales)
3. [Preparación de Google Sheets](#preparación-de-google-sheets)
4. [Importación del Workflow](#importación-del-workflow)
5. [Configuración de Nodos](#configuración-de-nodos)
6. [Alternativas de AI Provider](#alternativas-de-ai-provider)
7. [Testing y Validación](#testing-y-validación)
8. [Troubleshooting](#troubleshooting)

---

## Requisitos Previos

### Software
- **n8n Community Edition** v1.0+ instalado
- Node.js v18+ (para ejecución de Code nodes)
- Acceso a internet para llamadas API

### Servicios Externos
- **Google Sheets**: Para almacenar configuración de sitios
- **Anthropic API** o **Google Vertex AI**: Para generación de insights
- **WordPress REST API**: Habilitada en todos los sitios objetivo
- **Acceso sFTP**: Credenciales de cada servidor

### Costos Estimados (mensual para 20 sitios)
- **Anthropic Claude Haiku**: ~$2-5 USD (basado en 20 sitios × 20 artículos × 30 días)
- **Google Vertex AI Gemini Flash**: ~$1-3 USD
- n8n Community: **GRATIS**

---

## Configuración de Credenciales

### 1. Google Sheets OAuth2

**Paso 1: Crear credenciales en Google Cloud Console**

```bash
1. Ir a: https://console.cloud.google.com/apis/credentials
2. Crear nuevo proyecto: "n8n-llms-updater"
3. Habilitar API: "Google Sheets API"
4. Crear credenciales OAuth 2.0:
   - Tipo: Aplicación web
   - URIs de redirección: https://TU_DOMINIO_N8N/rest/oauth2-credential/callback
5. Descargar JSON de credenciales
```

**Paso 2: Configurar en n8n**

```
n8n UI → Credentials → New Credential → Google Sheets OAuth2
- Client ID: [Tu Client ID]
- Client Secret: [Tu Client Secret]
- Scopes: https://www.googleapis.com/auth/spreadsheets
- OAuth Callback URL: [Auto-generada por n8n]
- Clic en "Connect my account"
```

**Nombre sugerido**: `Google Sheets - Sitios Infraestructura`

---

### 2. Anthropic API (Opción Recomendada)

**Paso 1: Obtener API Key**

```bash
1. Visitar: https://console.anthropic.com/
2. Crear cuenta o iniciar sesión
3. Ir a: Settings → API Keys
4. Crear nueva API key: "n8n-llms-insights-generator"
5. Copiar la key (empieza con sk-ant-)
```

**Paso 2: Configurar en n8n**

```
n8n UI → Credentials → New Credential → HTTP Header Auth
- Name: Anthropic API Key
- Header Name: x-api-key
- Header Value: sk-ant-XXXXXXXXXXXXXXXXX
```

**Nombre sugerido**: `Anthropic Claude API`

**Modelo recomendado**: `claude-3-5-haiku-20241022` (balance costo/calidad)

---

### 3. Alternativa: Google Vertex AI (Gemini)

Si prefieres usar Gemini en lugar de Claude:

**Paso 1: Habilitar Vertex AI**

```bash
# En Google Cloud Console
1. Habilitar Vertex AI API
2. Crear Service Account con rol "Vertex AI User"
3. Generar JSON key
```

**Paso 2: Modificar nodo "Anthropic Claude - Generate Insight"**

Cambiar la configuración HTTP Request a:

```json
{
  "url": "https://REGION-aiplatform.googleapis.com/v1/projects/PROJECT_ID/locations/REGION/publishers/google/models/gemini-1.5-flash:generateContent",
  "method": "POST",
  "authentication": "serviceAccount",
  "body": {
    "contents": [{
      "role": "user",
      "parts": [{
        "text": "Sistema: Actúa como un Analista de Inteligencia de Inversiones...\n\nTítulo: {{ $json.current_article.title }}\n\nContenido:\n{{ $json.current_article.excerpt_for_ai }}"
      }]
    }],
    "generationConfig": {
      "maxOutputTokens": 300,
      "temperature": 0.3
    }
  }
}
```

---

### 4. Credenciales sFTP (Configuración Dinámica)

**Desafío**: Necesitas credenciales sFTP únicas para cada sitio (20 sitios = 20 credenciales).

**Solución 1: Credenciales Dinámicas via Code Node** (Recomendado para Community Edition)

Reemplaza el nodo "sFTP - Upload llms.txt" con un nodo Code que use la librería `ssh2-sftp-client`:

```javascript
// Instalar en el servidor n8n:
// npm install ssh2-sftp-client

const SftpClient = require('ssh2-sftp-client');
const sftp = new SftpClient();

const config = {
  host: $json.sFTP_Host,
  port: 22,
  username: $json.sFTP_User,
  password: $json.sFTP_Pass
};

const remotePath = '/public_html/llms.txt';
const content = Buffer.from($json.llms_txt_content, 'utf-8');

try {
  await sftp.connect(config);
  await sftp.put(content, remotePath);
  await sftp.end();

  return {
    json: {
      ...$json,
      upload_status: 'SUCCESS',
      uploaded_at: new Date().toISOString()
    }
  };
} catch (error) {
  console.error('sFTP Error:', error);
  return {
    json: {
      ...$json,
      upload_status: 'FAILED',
      error_message: error.message
    }
  };
}
```

**Solución 2: Credenciales Pre-configuradas** (Para pocos sitios)

Si tienes menos de 5 sitios, puedes crear credenciales individuales:

```
n8n UI → Credentials → New Credential → SFTP
- Host: sitio1.com
- Port: 22
- Username: user_sitio1
- Password: pass_sitio1
- Private Key: [Opcional]
```

Luego usa un nodo Switch para dirigir cada sitio a su credencial:

```
Switch Node (basado en $json.site_domain)
  → Route 1 (sitio1.com): sFTP with Credential "Sitio1 SFTP"
  → Route 2 (sitio2.com): sFTP with Credential "Sitio2 SFTP"
  ...
```

---

## Preparación de Google Sheets

### Estructura de la Hoja de Cálculo

**Nombre del archivo**: `Sitios Infraestructura - LLMS Config`

**Hoja 1**: `Sheet1` (o renombrar a "Sitios")

| Sitio_URL | WP_API_Key | sFTP_Host | sFTP_User | sFTP_Pass | Lookback_Days |
|-----------|------------|-----------|-----------|-----------|---------------|
| https://infraestructura1.com | wp_12345... | ftp.infraestructura1.com | ftpuser1 | pass123 | 30 |
| https://logistica2.com | wp_67890... | sftp.logistica2.com | deploy | secureP@ss | 45 |
| https://transporte3.com | wp_abcde... | 192.168.1.100 | admin | ftpPass456 | 60 |

**Descripción de Columnas**:

1. **Sitio_URL**: URL completa del sitio WordPress (con https://)
2. **WP_API_Key**: JWT token o Application Password de WordPress
   - Generar en: WP Admin → Usuarios → Tu perfil → Application Passwords
3. **sFTP_Host**: Hostname o IP del servidor sFTP
4. **sFTP_User**: Usuario sFTP con permisos de escritura en `/public_html/`
5. **sFTP_Pass**: Contraseña del usuario sFTP
6. **Lookback_Days**: Días hacia atrás para consultar artículos (30-90 recomendado)

**Permisos**: Compartir la hoja con el email de la Service Account de Google Sheets (con permiso de "Viewer" es suficiente).

---

## Importación del Workflow

### Paso 1: Importar JSON

```bash
1. Abrir n8n UI
2. Workflows → New Workflow → Import from File
3. Seleccionar: llms-txt-auto-updater-workflow.json
4. Click "Import"
```

### Paso 2: Verificar Nodos Importados

Deberías ver 17 nodos conectados:
- ✅ Google Sheets - Sitios Config
- ✅ Split Sitios - Batch Processor
- ✅ Preparar Consulta WP
- ✅ WordPress API - Fetch Posts
- ✅ Extraer y Limpiar Contenido
- ✅ Verificar si hay contenido
- ✅ Split Artículos - AI Processing
- ✅ Preparar Batch para AI
- ✅ Anthropic Claude - Generate Insight
- ✅ Procesar Respuesta AI
- ✅ Construir Archivo llms.txt
- ✅ sFTP - Upload llms.txt
- ✅ Wait - Rate Limit Protection
- ✅ Log Resultado
- ✅ Log Sin Contenido
- ✅ Merge Resultados Finales
- ✅ Generar Reporte Final

---

## Configuración de Nodos

### Nodo 1: Google Sheets - Sitios Config

```
Operation: Read
Document ID: [Pegar URL de tu Google Sheet]
Sheet Name: Sheet1
Range: A:F (todas las columnas necesarias)
Options:
  - RAW Data: OFF (para obtener valores formateados)
```

**Testing**:
```bash
Click "Execute Node" → Verificar que retorna las filas de tu Sheet
```

---

### Nodo 4: WordPress API - Fetch Posts

**Configuración dinámica** (ya configurada en JSON):

```
URL: ={{ $json.wp_api_url }}
Authentication: HTTP Header Auth
Headers:
  - Name: Authorization
  - Value: =Bearer {{ $json.WP_API_Key }}
```

**Validación**: Ejecutar nodo manualmente con un sitio de prueba.

**Troubleshooting común**:
- Error 401: API Key inválida → Regenerar Application Password en WP
- Error 404: Endpoint no existe → Verificar que WordPress REST API esté habilitada
- Error 403: IP bloqueada → Revisar firewall del servidor WP

---

### Nodo 9: Anthropic Claude - Generate Insight

**Configuración del System Prompt**:

```json
{
  "system": "Actúa como un Analista de Inteligencia de Inversiones. Extrae métricas financieras, porcentajes y cifras críticas del texto. Genera un resumen ejecutivo en formato Markdown de máximo 250 caracteres que empiece directamente con el dato más impactante. No uses introducciones como 'Este artículo trata sobre...'. Inicia con números, porcentajes o hechos concretos."
}
```

**Personalización del Prompt**:

Si necesitas insights más específicos, modifica el campo `system`:

```javascript
// Ejemplo para foco en ESG (Environmental, Social, Governance)
"Actúa como un Analista ESG. Extrae métricas de sostenibilidad, reducción de emisiones, y cumplimiento normativo. Resume en máximo 250 caracteres iniciando con el indicador ESG más relevante."

// Ejemplo para foco en ROI
"Actúa como un CFO. Extrae cifras de retorno de inversión, periodos de payback, y proyecciones financieras. Resume en máximo 250 caracteres iniciando con el ROI o ahorro más significativo."
```

---

### Nodo 12: sFTP - Upload llms.txt

**Si usas credenciales dinámicas** (Solución 1 con Code Node):

Reemplaza este nodo con el Code node del apartado de credenciales sFTP.

**Si usas credenciales pre-configuradas**:

```
Protocol: SFTP
Operation: Upload
Path: /public_html/llms.txt
Binary Data: OFF
File Content: ={{ $json.llms_txt_content }}
Credentials: [Seleccionar credencial sFTP correspondiente]
```

**Testing**:
```bash
# Verificar que el archivo se suba correctamente
curl https://tu-sitio.com/llms.txt
```

---

## Alternativas de AI Provider

### Comparativa de Modelos

| Proveedor | Modelo | Costo (1M tokens) | Velocidad | Calidad Insights | Community Compatible |
|-----------|--------|-------------------|-----------|------------------|---------------------|
| **Anthropic** | Claude 3.5 Haiku | $0.80 in / $4.00 out | ⚡⚡⚡ | ⭐⭐⭐⭐⭐ | ✅ Vía HTTP |
| **Google** | Gemini 1.5 Flash | $0.075 in / $0.30 out | ⚡⚡⚡⚡ | ⭐⭐⭐⭐ | ✅ Vía HTTP |
| **OpenAI** | GPT-4o-mini | $0.15 in / $0.60 out | ⚡⚡⚡ | ⭐⭐⭐⭐ | ✅ Vía HTTP |
| **Groq** | Llama 3.1 70B | GRATIS (límites) | ⚡⚡⚡⚡⚡ | ⭐⭐⭐ | ✅ Vía HTTP |

**Recomendación**:
- **Producción**: Claude 3.5 Haiku (mejor balance calidad/costo)
- **Desarrollo/Testing**: Groq Llama 3.1 (gratis hasta 6,000 req/día)
- **Alto volumen**: Gemini Flash (más económico)

---

### Configuración de Groq (Opción GRATIS)

**Obtener API Key**:
```bash
1. Visitar: https://console.groq.com/
2. Sign up (gratis)
3. API Keys → Create API Key
4. Copiar key (empieza con gsk_)
```

**Modificar nodo HTTP Request**:

```json
{
  "url": "https://api.groq.com/openai/v1/chat/completions",
  "method": "POST",
  "headers": {
    "Authorization": "Bearer gsk_XXXXXXXXXXXXX",
    "Content-Type": "application/json"
  },
  "body": {
    "model": "llama-3.1-70b-versatile",
    "messages": [
      {
        "role": "system",
        "content": "Actúa como un Analista de Inteligencia de Inversiones..."
      },
      {
        "role": "user",
        "content": "Analiza este contenido:\n\nTítulo: {{ $json.current_article.title }}\n\nContenido:\n{{ $json.current_article.excerpt_for_ai }}"
      }
    ],
    "max_tokens": 300,
    "temperature": 0.3
  }
}
```

**Nota**: Groq tiene límites de 6,000 requests/día en tier gratuito (suficiente para 300 artículos/día).

---

## Testing y Validación

### Test 1: Ejecución Manual de un Sitio

```bash
1. En Google Sheets, dejar solo 1 fila de datos (más el header)
2. En n8n, click en "Execute Workflow"
3. Observar la ejecución nodo por nodo
4. Verificar:
   - ✅ Google Sheets retorna 1 sitio
   - ✅ WordPress API retorna artículos
   - ✅ AI genera insights coherentes
   - ✅ Archivo llms.txt se construye correctamente
   - ✅ Upload sFTP exitoso
   - ✅ Archivo accesible en https://sitio.com/llms.txt
```

### Test 2: Validación de Insights

```bash
# Revisar calidad de un insight generado
1. Ir al nodo "Procesar Respuesta AI"
2. Ver output JSON
3. Verificar que el campo "insight" contiene:
   - ✅ Máximo 250 caracteres
   - ✅ Inicia con dato impactante (no introducción)
   - ✅ Contiene cifras, porcentajes o métricas
   - ✅ Lenguaje ejecutivo, no narrativo
```

**Ejemplo de Insight BUENO**:
```
📊 Inversión de $450M reducirá tiempos de entrega un 35% en corredores intermodales. ROI proyectado: 18 meses. Beneficia a 2,300 empresas logísticas en zona metropolitana.
```

**Ejemplo de Insight MALO**:
```
Este artículo trata sobre las mejoras en la infraestructura logística que se están implementando en la región.
```

Si obtienes insights malos, ajusta el System Prompt para ser más específico.

---

### Test 3: Rate Limiting

```bash
# Probar con 3 sitios para verificar delays
1. Configurar 3 sitios en Google Sheets
2. Ejecutar workflow
3. Verificar en logs que hay 5 segundos de espera entre sitios
4. Confirmar que no hay errores 429 (Too Many Requests)
```

---

### Test 4: Manejo de Errores

**Escenarios a probar**:

1. **Sitio sin artículos nuevos**:
   - Cambiar Lookback_Days a 1 en un sitio antiguo
   - Verificar que el workflow lo marca como "SKIPPED"

2. **API Key inválida**:
   - Poner una API Key falsa en 1 sitio
   - Verificar que el workflow continúa con los demás

3. **sFTP fallido**:
   - Poner credenciales incorrectas en 1 sitio
   - Verificar que el error se loguea pero no detiene el workflow

---

## Troubleshooting

### Problema: "Error 413 Request Entity Too Large" en AI API

**Causa**: Artículos muy largos superan límite de tokens.

**Solución**: En el nodo "Extraer y Limpiar Contenido", reducir límite:

```javascript
// Cambiar de 1000 a 500 caracteres
return selectedParagraphs.substring(0, 500) + ...
```

---

### Problema: sFTP timeout

**Causa**: Servidor sFTP lento o bloqueado por firewall.

**Solución**:

```javascript
// En el Code node de sFTP, aumentar timeout
const config = {
  host: $json.sFTP_Host,
  port: 22,
  username: $json.sFTP_User,
  password: $json.sFTP_Pass,
  readyTimeout: 30000, // Aumentar a 30 segundos
  retries: 3
};
```

---

### Problema: "Invalid credentials" en Google Sheets

**Causa**: Token OAuth2 expirado.

**Solución**:

```bash
1. n8n UI → Credentials → Google Sheets OAuth2
2. Click "Reconnect"
3. Autorizar nuevamente
```

---

### Problema: Insights genéricos sin datos numéricos

**Causa**: System Prompt no es lo suficientemente específico para tu dominio.

**Solución**: Personalizar el prompt con ejemplos:

```json
{
  "system": "Actúa como un Analista de Inteligencia de Inversiones especializado en infraestructura y logística.\n\nEXTRAE únicamente:\n- Montos de inversión (USD, millones)\n- Porcentajes de mejora/reducción\n- Plazos y fechas clave\n- Número de beneficiarios/usuarios\n- Métricas operativas (tiempo, capacidad, toneladas)\n\nFORMATO de salida (máximo 250 caracteres):\n[CIFRA PRINCIPAL] + [IMPACTO] + [CONTEXTO]\n\nEJEMPLO BUENO:\n'Inversión de $120M aumentará capacidad portuaria un 40% para 2026. Reducción de tiempos de espera de 8 a 5 horas. Impacto en 1,500 exportadores.'\n\nNO escribas introducciones como 'El artículo menciona que...'"
}
```

---

### Problema: Workflow se detiene en el primer sitio

**Causa**: El nodo "Split in Batches" no está configurado para hacer loop.

**Solución**: Verificar conexiones:

```
Log Resultado → (conectar de vuelta a) → Split Sitios - Batch Processor
Log Sin Contenido → (conectar de vuelta a) → Split Sitios - Batch Processor
```

---

## Monitoreo y Mantenimiento

### Logs a revisar

```bash
# Ver ejecuciones en n8n UI
Workflows → LLMS.txt Auto-Updater → Executions

# Filtrar por:
- ✅ Successful: Todas las subidas exitosas
- ⚠️ Warning: Sitios sin contenido
- ❌ Error: Fallos de API o sFTP
```

### Configurar Alertas (Opcional)

Agregar un nodo al final del workflow:

```
IF (Nodo condicional)
  Condición: {{ $json.successful_uploads < $json.total_sites_processed * 0.8 }}

  TRUE → Send Email / Slack / Discord
    Mensaje: "⚠️ Solo {{ $json.successful_uploads }}/{{ $json.total_sites_processed }} sitios actualizados"
```

---

## Optimizaciones Avanzadas

### 1. Caché de Artículos Procesados

Para evitar re-procesar artículos que ya tienen insights:

```javascript
// En el nodo "Extraer y Limpiar Contenido"
// Guardar hash de artículos procesados en n8n Static Data
const processedHashes = $static.processedArticles || [];
const newArticles = articles.filter(article => {
  const hash = require('crypto').createHash('md5').update(article.link).digest('hex');
  return !processedHashes.includes(hash);
});
```

### 2. Procesamiento Paralelo de Sitios

Para acelerar la ejecución (requiere más recursos):

```
Reemplazar "Split in Batches" por "Split Out"
  → Procesa todos los sitios en paralelo
  → Reducir Wait time a 1-2 segundos
  → Atención: Mayor carga en APIs
```

### 3. Compresión de llms.txt

Para sitios con muchos artículos:

```javascript
// Antes de subir, comprimir con gzip
const zlib = require('zlib');
const compressed = zlib.gzipSync(Buffer.from($json.llms_txt_content));

// Subir como llms.txt.gz
// Servir con header: Content-Encoding: gzip
```

---

## Recursos Adicionales

- **n8n Docs**: https://docs.n8n.io/
- **WordPress REST API**: https://developer.wordpress.org/rest-api/
- **Anthropic API**: https://docs.anthropic.com/
- **Especificación llms.txt**: https://llmstxt.org/
- **Soporte**: [Foro de n8n](https://community.n8n.io/)

---

## Preguntas Frecuentes

**Q: ¿Puedo usar este workflow con Webflow o sitios no-WordPress?**

A: Sí, reemplaza el nodo de WordPress API con un Web Scraper (n8n tiene nodo HTML Extract) y ajusta la extracción de contenido.

**Q: ¿Cómo programo la ejecución automática?**

A: Cambia el primer nodo de "Google Sheets" manual a un nodo "Cron":

```
Cron Node
  Mode: Every Week
  Day: Monday
  Hour: 3 AM
  → (conectar a) Google Sheets
```

**Q: ¿Funciona con n8n Cloud?**

A: Sí, pero los nodos Code pueden tener limitaciones. Verifica la documentación de n8n Cloud sobre librerías npm permitidas.

**Q: ¿Puedo exportar los insights a Pinecone también?**

A: Sí, agrega un nodo HTTP Request al final:

```
HTTP Request → Pinecone Upsert API
  Vectors: Embeddings de los insights (usar OpenAI Embeddings API)
  Metadata: { url, title, insight, date }
```

---

**Última actualización**: Diciembre 2024
**Versión del Workflow**: 1.0.0
**Compatibilidad**: n8n Community Edition v1.0+
