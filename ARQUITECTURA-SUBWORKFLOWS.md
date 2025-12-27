# Arquitectura de Subworkflows - LLMS.txt Auto-Updater v2.0

## Visión General

La versión 2.0 utiliza una **arquitectura modular con subworkflows** para mejorar la escalabilidad, mantenibilidad y robustez del sistema.

### Ventajas de esta Arquitectura

✅ **Aislamiento de errores**: Un fallo en un sitio no detiene el procesamiento de los demás
✅ **Mejor debugging**: Cada subworkflow se puede probar independientemente
✅ **Escalabilidad**: Procesa 20+ sitios sin sobrecargar el contexto de ejecución
✅ **Mantenimiento**: Cambios en la lógica AI o sFTP no afectan el workflow principal
✅ **Reutilización**: Los subworkflows pueden ser llamados desde otros workflows

---

## Estructura de Workflows

```
┌─────────────────────────────────────────────────────────────┐
│                  MAIN WORKFLOW                              │
│  (llms-txt-MAIN-workflow.json)                             │
│                                                              │
│  1. Google Sheets → Lee configuración de 20 sitios         │
│  2. Split Sitios → Procesa 1 sitio a la vez               │
│  3. Validar Datos → Verifica datos requeridos             │
│  4. ┌─────────────────────────────────────┐               │
│     │  Execute: Process Site (subworkflow) │               │
│     └─────────────────────────────────────┘               │
│  5. Verificar Resultado → Success/Error                    │
│  6. Log Resultado → Registra éxito o fallo               │
│  7. Wait 3s → Rate limiting                               │
│  8. Loop → Siguiente sitio                                 │
│  9. Merge & Reporte Final                                  │
└─────────────────────────────────────────────────────────────┘
                         │
                         │ llama a
                         ▼
┌─────────────────────────────────────────────────────────────┐
│              SUBWORKFLOW: Process Site                      │
│  (llms-txt-PROCESS-SITE-subworkflow.json)                  │
│                                                              │
│  1. Recibe datos del sitio                                 │
│  2. Preparar URL de WordPress API                          │
│  3. WordPress API Call → con reintentos                    │
│  4. Validar Respuesta → ¿Exitosa?                         │
│     ├─ Error → Return API Error                           │
│     └─ Success → Extraer contenido                        │
│  5. ¿Hay artículos válidos?                               │
│     ├─ No → Return "No Content"                           │
│     └─ Sí → ┌──────────────────────────────┐            │
│              │ Execute: Generate AI Insights │            │
│              │      (subworkflow)            │            │
│              └──────────────────────────────┘            │
│  6. Validar Respuesta AI → Success o Fallback            │
│  7. Construir archivo llms.txt                             │
│  8. sFTP Upload → con manejo de error                     │
│  9. Return Success/Failed al MAIN                          │
└─────────────────────────────────────────────────────────────┘
                         │
                         │ llama a
                         ▼
┌─────────────────────────────────────────────────────────────┐
│           SUBWORKFLOW: Generate AI Insights                 │
│  (llms-txt-GENERATE-INSIGHTS-subworkflow.json)             │
│                                                              │
│  1. Recibe array de artículos                              │
│  2. Parse Articles → Expandir array                        │
│  3. Split Articles → Procesa 1 por vez                     │
│  4. AI API Call (Anthropic/Groq) → con reintentos         │
│  5. Procesar Respuesta → Extraer insight                   │
│     └─ Error → Insight genérico (fallback)                │
│  6. Validar Calidad → Score de insight                     │
│  7. Wait 1s → AI rate limiting                             │
│  8. Loop → Siguiente artículo                              │
│  9. Acumular Resultados → Return al Process Site           │
└─────────────────────────────────────────────────────────────┘
```

---

## Flujo de Datos Detallado

### 1. MAIN Workflow → Process Site

**Input (desde Google Sheets)**:
```json
{
  "Sitio_URL": "https://infraestructura1.com",
  "WP_API_Key": "wp_abc123...",
  "sFTP_Host": "ftp.infraestructura1.com",
  "sFTP_User": "deploy",
  "sFTP_Pass": "pass123",
  "Lookback_Days": 30
}
```

**Validación y Preparación**:
```json
{
  ...input,
  "site_domain": "infraestructura1.com",
  "validation_error": false,
  "processing_timestamp": "2024-12-27T10:00:00Z"
}
```

**Llamada a Subworkflow**:
```javascript
Execute Workflow: "Process Site"
  Input: {
    siteData: JSON.stringify(validatedData)
  }
```

---

### 2. Process Site → Generate AI Insights

**Después de extraer contenido de WordPress**:
```json
{
  "articles": [
    {
      "title": "Nueva autopista de peaje...",
      "url": "https://...",
      "date": "2024-12-15",
      "excerpt_for_ai": "La nueva autopista de 87 km...",
      "word_count": 156
    },
    ...
  ],
  "total_articles": 15
}
```

**Llamada a Subworkflow AI**:
```javascript
Execute Workflow: "Generate AI Insights"
  Input: {
    articles: JSON.stringify(articles),
    siteDomain: "infraestructura1.com"
  }
```

---

### 3. Generate AI Insights → Retorno

**Procesamiento por Artículo**:
```
Artículo 1 → AI API → Insight → Quality Score → Acumular
Artículo 2 → AI API → Insight → Quality Score → Acumular
...
Artículo 15 → AI API → Insight → Quality Score → Acumular
```

**Output del Subworkflow AI**:
```json
{
  "processed_articles": [
    {
      "title": "Nueva autopista de peaje...",
      "url": "https://...",
      "date": "2024-12-15",
      "insight": "Autopista de 87 km reducirá costos logísticos un 28% ($12M anuales)...",
      "quality_score": 100
    },
    ...
  ],
  "insights_count": 15,
  "ai_errors_count": 0,
  "avg_quality_score": 92,
  "execution_time_ms": 23450,
  "status": "SUCCESS"
}
```

---

### 4. Process Site → Retorno a MAIN

**Después de construir llms.txt y subir vía sFTP**:
```json
{
  "site_domain": "infraestructura1.com",
  "Sitio_URL": "https://infraestructura1.com",
  "status": "SUCCESS",
  "articles_processed": 15,
  "file_size": 12456,
  "insights_count": 15,
  "upload_status": "SUCCESS",
  "ai_fallback_used": false,
  "execution_time_ms": 45230,
  "llms_txt_url": "https://infraestructura1.com/llms.txt"
}
```

---

## Manejo de Errores por Nivel

### Nivel 1: MAIN Workflow

**Errores capturados**:
- ❌ Datos inválidos en Google Sheets (ej: Sitio_URL vacío)
- ❌ Subworkflow "Process Site" no disponible
- ❌ Subworkflow retorna error

**Comportamiento**:
- ✅ Loguea el error con detalles
- ✅ **Continúa con el siguiente sitio**
- ✅ NO detiene la ejecución completa

**Bifurcaciones**:
```
Execute: Process Site
  ├─ Output Normal → Verificar status
  │   ├─ SUCCESS → Log Success → Wait → Next Site
  │   └─ FAILED → Log Error → Wait → Next Site
  └─ Error Output → Log Error → Wait → Next Site
```

---

### Nivel 2: Process Site Subworkflow

**Errores capturados**:
- ❌ WordPress API timeout/403/404/500
- ❌ WordPress API retorna datos inválidos
- ❌ sFTP conexión fallida
- ❌ sFTP upload fallido
- ❌ Subworkflow AI no disponible

**Comportamiento**:
- ✅ Detecta el tipo de error específico
- ✅ Retorna estructura con `status: "FAILED"` y `error_type`
- ✅ **NO lanza excepciones** que detendrían el MAIN workflow

**Bifurcaciones**:
```
WordPress API Call
  └─ Validar Respuesta
      ├─ Error → Return API Error {status: "FAILED", error_type: "WP_API_ERROR"}
      └─ Success → Extraer Contenido
          └─ ¿Hay artículos?
              ├─ No → Return No Content {status: "SKIPPED"}
              └─ Sí → Execute: AI Insights
                  └─ Validar Respuesta AI
                      ├─ Error → Usar fallback → Continuar
                      └─ Success → Continuar
                          └─ sFTP Upload
                              ├─ Error → Return {status: "FAILED", error_type: "SFTP_ERROR"}
                              └─ Success → Return {status: "SUCCESS"}
```

---

### Nivel 3: Generate AI Insights Subworkflow

**Errores capturados**:
- ❌ AI API timeout/429/500
- ❌ AI API retorna respuesta malformada
- ❌ AI retorna insight genérico (baja calidad)

**Comportamiento**:
- ✅ Procesa artículos 1 por 1 (no en batch)
- ✅ Si un artículo falla en AI → usa insight genérico
- ✅ **Nunca falla completamente** (siempre retorna algo)
- ✅ Registra errores AI pero continúa

**Ejemplo de Fallback**:
```javascript
// Si AI API falla para artículo #5:
if (aiError) {
  insight = `Contenido relevante sobre ${siteDomain}. Consulte el artículo completo.`;
  qualityScore = 25; // Bajo score indica fallback
}
```

---

## Estrategia de Reintentos

### WordPress API
```javascript
options: {
  timeout: 25000, // 25 segundos
  retry: {
    maxRetries: 2,
    retryInterval: 2000 // 2 segundos entre intentos
  }
}
// Total de intentos: 1 inicial + 2 reintentos = 3
```

### AI API (Anthropic/Groq)
```javascript
options: {
  timeout: 30000, // 30 segundos
  retry: {
    maxRetries: 2,
    retryInterval: 3000 // 3 segundos entre intentos
  }
}
// Si falla después de 3 intentos → fallback automático
```

### sFTP Upload
```javascript
// Dentro del código ssh2-sftp-client:
config: {
  readyTimeout: 20000,
  retries: 2,
  retry_factor: 2,
  retry_minTimeout: 2000
}
// Reintentos exponenciales: 2s, 4s
```

---

## Rate Limiting

### Entre Sitios (MAIN)
```
Wait: 3 segundos
Propósito: Evitar saturar WordPress APIs y sFTP servers
```

### Entre Artículos (AI Subworkflow)
```
Wait: 1 segundo
Propósito: Respetar rate limits de AI APIs
  - Anthropic: 5 req/min en tier básico
  - Groq: 30 req/min en tier gratis
```

---

## Logs y Trazabilidad

### MAIN Workflow
```javascript
// Log Success
{
  timestamp: "2024-12-27T10:05:23Z",
  site: "infraestructura1.com",
  status: "SUCCESS",
  articles_processed: 15,
  file_size: 12456,
  insights_generated: 15,
  upload_status: "SUCCESS",
  execution_time_ms: 45230
}

// Log Error
{
  timestamp: "2024-12-27T10:06:15Z",
  site: "logistica2.com",
  status: "FAILED",
  error_type: "WP_API_ERROR",
  error_message: "Connection timeout after 25s",
  error_details: {
    validation_error: false,
    api_error: true,
    sftp_error: false,
    ai_error: false
  },
  articles_attempted: 0
}

// Log Validation Failed
{
  timestamp: "2024-12-27T10:07:00Z",
  site: "transporte3.com",
  status: "VALIDATION_FAILED",
  error_type: "MISSING_REQUIRED_DATA",
  missing_fields: ["WP_API_Key", "sFTP_Pass"]
}
```

### Reporte Final Consolidado
```
╔════════════════════════════════════════════════════════════╗
║           REPORTE FINAL - LLMS.txt Auto-Updater           ║
╚════════════════════════════════════════════════════════════╝

📊 RESUMEN EJECUTIVO:
  • Total de sitios: 20
  • ✅ Exitosos: 17
  • ❌ Fallidos: 2
  • ⚠️ Validación fallida: 1
  • ⏭️ Omitidos: 0
  • 📈 Tasa de éxito: 85.00%

📝 PROCESAMIENTO:
  • Total artículos procesados: 312
  • Tamaño total archivos: 156.42 KB
  • Tiempo promedio por sitio: 38s

❌ ERRORES (si los hay):
  • WP_API_ERROR: 1 sitio(s)
  • SFTP_ERROR: 1 sitio(s)

⏰ Ejecutado: 12/27/2024, 10:15:30 AM
```

---

## Configuración de IDs de Subworkflows

### Paso 1: Importar los 3 workflows

```bash
1. Importar llms-txt-MAIN-workflow.json
2. Importar llms-txt-PROCESS-SITE-subworkflow.json
3. Importar llms-txt-GENERATE-INSIGHTS-subworkflow.json
```

### Paso 2: Obtener IDs de Subworkflows

```bash
# En n8n UI, para cada subworkflow:
1. Abrir el workflow
2. Copiar el ID de la URL:
   https://tu-n8n.com/workflow/abc123def456...
                                  ^^^^^^^^^^^^^ Este es el ID
```

### Paso 3: Configurar en MAIN Workflow

**Nodo: "Set Subworkflow IDs"**
```javascript
{
  process_site_workflow_id: "PEGAR_ID_DE_PROCESS_SITE",
  generate_insights_workflow_id: "PEGAR_ID_DE_GENERATE_INSIGHTS"
}
```

### Paso 4: Configurar en Process Site Subworkflow

**Nodo: "Get AI Workflow ID"**
```javascript
{
  ai_workflow_id: "PEGAR_ID_DE_GENERATE_INSIGHTS"
}
```

---

## Testing por Niveles

### Test 1: Subworkflow AI (Aislado)

```bash
1. Abrir "Generate AI Insights" subworkflow
2. Ejecutar manualmente con datos de prueba:
   {
     "articles": "[{\"title\":\"Test\",\"excerpt_for_ai\":\"Inversión de $50M...\"}]",
     "siteDomain": "test.com"
   }
3. Verificar que retorna insights
```

### Test 2: Subworkflow Process Site (Aislado)

```bash
1. Abrir "Process Site" subworkflow
2. Ejecutar manualmente con datos de prueba:
   {
     "siteData": "{\"Sitio_URL\":\"https://ejemplo.com\",\"WP_API_Key\":\"...\",\"sFTP_Host\":\"...\"}"
   }
3. Verificar que:
   - Llama a WordPress API
   - Llama al subworkflow AI
   - Construye llms.txt
   - Sube vía sFTP
```

### Test 3: MAIN Workflow (Completo)

```bash
1. Configurar Google Sheet con 1-2 sitios de prueba
2. Ejecutar MAIN workflow
3. Verificar logs de cada paso
4. Confirmar reporte final
```

### Test 4: Manejo de Errores

```bash
# Test 4.1: WordPress API inválida
1. En Google Sheet, usar un Sitio_URL inexistente
2. Ejecutar MAIN
3. Verificar que:
   - Se loguea el error WP_API_ERROR
   - El workflow continúa con el siguiente sitio
   - No se detiene la ejecución

# Test 4.2: AI API Key inválida
1. Configurar credencial Anthropic con key falsa
2. Ejecutar
3. Verificar que:
   - Se usan insights genéricos (fallback)
   - El archivo llms.txt se crea igualmente
   - Status final = SUCCESS (con ai_fallback_used: true)

# Test 4.3: sFTP credenciales incorrectas
1. En Google Sheet, usar sFTP_Pass incorrecta
2. Ejecutar
3. Verificar que:
   - Se loguea el error SFTP_ERROR
   - El workflow continúa con el siguiente sitio
   - Status = FAILED pero no detiene ejecución
```

---

## Ventajas vs. Arquitectura Monolítica

| Aspecto | Monolítica (v1.0) | Subworkflows (v2.0) |
|---------|-------------------|---------------------|
| **Aislamiento** | ❌ Un error detiene todo | ✅ Errores aislados por sitio |
| **Debugging** | ❌ Difícil identificar fallo | ✅ Logs granulares por nivel |
| **Escalabilidad** | ⚠️ 20 sitios = contexto pesado | ✅ Contexto limpio por subworkflow |
| **Mantenimiento** | ❌ Cambio afecta todo | ✅ Cambios modulares |
| **Testing** | ❌ Solo test end-to-end | ✅ Test por componente |
| **Reutilización** | ❌ No reutilizable | ✅ Subworkflows reutilizables |
| **Tasa de éxito** | ⚠️ Todo o nada | ✅ Éxito parcial (ej: 17/20) |

---

## Próximas Mejoras

### Nivel 1: MAIN Workflow
- [ ] Notificaciones por email/Slack al finalizar
- [ ] Exportar reporte a Google Sheets
- [ ] Configuración de threshold de éxito mínimo

### Nivel 2: Process Site
- [ ] Paginación de WordPress API (>50 posts)
- [ ] Backup de llms.txt antes de sobrescribir
- [ ] Validación de archivo subido (checksum)

### Nivel 3: Generate AI Insights
- [ ] Batch processing (5 artículos por llamada AI)
- [ ] Caché de insights ya generados
- [ ] Múltiples providers AI (fallback automático)

---

**Versión**: 2.0
**Fecha**: Diciembre 2024
**Compatibilidad**: n8n Community Edition v1.0+
