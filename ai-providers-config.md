# Configuración de Proveedores de AI

Guía de configuración para diferentes proveedores de AI en el nodo "Anthropic Claude - Generate Insight" del workflow.

## Índice
1. [Anthropic Claude (Recomendado)](#1-anthropic-claude-recomendado)
2. [Groq (Gratis)](#2-groq-gratis)
3. [Google Gemini (Vertex AI)](#3-google-gemini-vertex-ai)
4. [OpenAI GPT-4o](#4-openai-gpt-4o)
5. [Comparativa de Costos](#5-comparativa-de-costos)

---

## 1. Anthropic Claude (Recomendado)

### Por qué es la mejor opción
- ✅ Excelente en extracción de datos numéricos
- ✅ Contexto de 200K tokens (procesa artículos largos)
- ✅ Seguimiento preciso de instrucciones
- ✅ $5 USD de créditos gratis al registrarse

### Obtener API Key

```bash
1. Visitar: https://console.anthropic.com/
2. Sign up / Login
3. Settings → API Keys
4. Create Key: "n8n-llms-insights"
5. Copiar key (empieza con sk-ant-)
```

### Configuración en n8n

**Credencial**:
```
Type: HTTP Header Auth
Name: Anthropic API
Header Name: x-api-key
Header Value: sk-ant-XXXXXXXXXXXXXXXXXXXXXXXXXX
```

**Nodo HTTP Request**:
```json
{
  "url": "https://api.anthropic.com/v1/messages",
  "method": "POST",
  "authentication": "predefinedCredentialType",
  "nodeCredentialType": "httpHeaderAuth",
  "sendHeaders": true,
  "headerParameters": {
    "parameters": [
      {
        "name": "anthropic-version",
        "value": "2023-06-01"
      },
      {
        "name": "Content-Type",
        "value": "application/json"
      }
    ]
  },
  "sendBody": true,
  "contentType": "json",
  "body": {
    "model": "claude-3-5-haiku-20241022",
    "max_tokens": 300,
    "temperature": 0.3,
    "system": "Actúa como un Analista de Inteligencia de Inversiones. Extrae métricas financieras, porcentajes y cifras críticas del texto. Genera un resumen ejecutivo en formato Markdown de máximo 250 caracteres que empiece directamente con el dato más impactante. No uses introducciones como 'Este artículo trata sobre...'. Inicia con números, porcentajes o hechos concretos.",
    "messages": [
      {
        "role": "user",
        "content": "Analiza este contenido y extrae el insight más impactante:\n\nTítulo: {{ $json.current_article.title }}\n\nContenido:\n{{ $json.current_article.excerpt_for_ai }}"
      }
    ]
  }
}
```

### Modelos Disponibles

| Modelo | Costo (por 1M tokens) | Velocidad | Mejor para |
|--------|----------------------|-----------|------------|
| `claude-3-5-sonnet-20241022` | $3 in / $15 out | ⚡⚡⚡ | Análisis complejo, alta calidad |
| `claude-3-5-haiku-20241022` | $0.80 in / $4 out | ⚡⚡⚡⚡⚡ | **Balance ideal** (recomendado) |
| `claude-3-haiku-20240307` | $0.25 in / $1.25 out | ⚡⚡⚡⚡⚡ | Alto volumen, bajo costo |

**Recomendación**: Usar `claude-3-5-haiku-20241022`

### Estimación de Costos

```
Escenario: 20 sitios × 20 artículos × 4 ejecuciones/mes

Input tokens: 400 artículos × 500 tokens = 200K tokens
Output tokens: 400 artículos × 100 tokens = 40K tokens

Costo Input: 0.2M × $0.80 = $0.16
Costo Output: 0.04M × $4.00 = $0.16
Total: ~$0.32/mes
```

### Procesamiento de Respuesta

```javascript
// En el nodo "Procesar Respuesta AI"
const response = $input.item.json;
let insight = 'Información relevante sobre infraestructura y logística.';

if (response.content && Array.isArray(response.content) && response.content.length > 0) {
  insight = response.content[0].text || insight;
  insight = insight.trim().substring(0, 250);
  if (insight.length === 250 && !insight.endsWith('.')) {
    insight = insight.substring(0, 247) + '...';
  }
}

return {
  json: {
    ...$json,
    insight: insight
  }
};
```

---

## 2. Groq (Gratis)

### Por qué elegir Groq
- ✅ **100% GRATIS** hasta 6,000 requests/día
- ✅ Velocidad extremadamente rápida (>300 tokens/s)
- ✅ Modelos open-source de alta calidad
- ⚠️ Rate limits más estrictos
- ⚠️ Calidad de extracción ligeramente inferior a Claude

### Obtener API Key

```bash
1. Visitar: https://console.groq.com/
2. Sign up (gratis, no requiere tarjeta)
3. API Keys → Create API Key
4. Copiar key (empieza con gsk_)
```

### Configuración en n8n

**Credencial**:
```
Type: HTTP Header Auth
Name: Groq API
Header Name: Authorization
Header Value: Bearer gsk_XXXXXXXXXXXXXXXXXXXXXXXXXX
```

**Nodo HTTP Request**:
```json
{
  "url": "https://api.groq.com/openai/v1/chat/completions",
  "method": "POST",
  "authentication": "predefinedCredentialType",
  "nodeCredentialType": "httpHeaderAuth",
  "sendHeaders": true,
  "headerParameters": {
    "parameters": [
      {
        "name": "Content-Type",
        "value": "application/json"
      }
    ]
  },
  "sendBody": true,
  "contentType": "json",
  "body": {
    "model": "llama-3.1-70b-versatile",
    "messages": [
      {
        "role": "system",
        "content": "Actúa como un Analista de Inteligencia de Inversiones. Extrae métricas financieras, porcentajes y cifras críticas del texto. Genera un resumen ejecutivo en formato Markdown de máximo 250 caracteres que empiece directamente con el dato más impactante. No uses introducciones como 'Este artículo trata sobre...'. Inicia con números, porcentajes o hechos concretos."
      },
      {
        "role": "user",
        "content": "Analiza este contenido y extrae el insight más impactante:\n\nTítulo: {{ $json.current_article.title }}\n\nContenido:\n{{ $json.current_article.excerpt_for_ai }}"
      }
    ],
    "max_tokens": 300,
    "temperature": 0.3
  }
}
```

### Modelos Disponibles (Todos GRATIS)

| Modelo | Tokens/segundo | Contexto | Mejor para |
|--------|----------------|----------|------------|
| `llama-3.1-70b-versatile` | 300+ | 128K | **Recomendado** - Balance ideal |
| `llama-3.1-8b-instant` | 500+ | 128K | Máxima velocidad |
| `mixtral-8x7b-32768` | 400+ | 32K | Alternativa robusta |

### Rate Limits (Tier Gratuito)

- **6,000 requests/día**
- **30 requests/minuto**
- Sin límite de tokens

Para 400 artículos/mes: **Totalmente gratis**

### Procesamiento de Respuesta

```javascript
// En el nodo "Procesar Respuesta AI"
const response = $input.item.json;
let insight = 'Información relevante sobre infraestructura y logística.';

if (response.choices && response.choices.length > 0) {
  insight = response.choices[0].message.content || insight;
  insight = insight.trim().substring(0, 250);
  if (insight.length === 250 && !insight.endsWith('.')) {
    insight = insight.substring(0, 247) + '...';
  }
}

return {
  json: {
    ...$json,
    insight: insight
  }
};
```

---

## 3. Google Gemini (Vertex AI)

### Por qué elegir Gemini
- ✅ Muy económico ($0.075 por 1M tokens)
- ✅ Contexto de 2M tokens
- ✅ Integración con Google Cloud
- ⚠️ Requiere configuración de GCP
- ⚠️ Latencia ligeramente mayor

### Setup en Google Cloud

```bash
# 1. Crear proyecto en GCP
gcloud projects create n8n-llms-updater

# 2. Habilitar Vertex AI API
gcloud services enable aiplatform.googleapis.com

# 3. Crear Service Account
gcloud iam service-accounts create n8n-gemini \
  --display-name="n8n Gemini Service Account"

# 4. Asignar rol
gcloud projects add-iam-policy-binding PROJECT_ID \
  --member="serviceAccount:n8n-gemini@PROJECT_ID.iam.gserviceaccount.com" \
  --role="roles/aiplatform.user"

# 5. Generar JSON key
gcloud iam service-accounts keys create key.json \
  --iam-account=n8n-gemini@PROJECT_ID.iam.gserviceaccount.com
```

### Configuración en n8n

**Credencial**:
```
Type: Google Service Account
Name: Vertex AI Gemini
Service Account Email: n8n-gemini@PROJECT_ID.iam.gserviceaccount.com
Private Key: [Contenido de key.json]
```

**Nodo HTTP Request**:
```json
{
  "url": "https://us-central1-aiplatform.googleapis.com/v1/projects/PROJECT_ID/locations/us-central1/publishers/google/models/gemini-1.5-flash-002:generateContent",
  "method": "POST",
  "authentication": "predefinedCredentialType",
  "nodeCredentialType": "googleServiceAccountOAuth2Api",
  "sendBody": true,
  "contentType": "json",
  "body": {
    "contents": [
      {
        "role": "user",
        "parts": [
          {
            "text": "Sistema: Actúa como un Analista de Inteligencia de Inversiones. Extrae métricas financieras, porcentajes y cifras críticas del texto. Genera un resumen ejecutivo en formato Markdown de máximo 250 caracteres que empiece directamente con el dato más impactante.\n\nAnaliza este contenido:\n\nTítulo: {{ $json.current_article.title }}\n\nContenido:\n{{ $json.current_article.excerpt_for_ai }}"
          }
        ]
      }
    ],
    "generationConfig": {
      "maxOutputTokens": 300,
      "temperature": 0.3,
      "topP": 0.95
    }
  }
}
```

### Modelos Disponibles

| Modelo | Costo (por 1M tokens) | Contexto | Mejor para |
|--------|----------------------|----------|------------|
| `gemini-1.5-flash-002` | $0.075 in / $0.30 out | 1M | **Recomendado** - Muy económico |
| `gemini-1.5-pro-002` | $1.25 in / $5.00 out | 2M | Análisis complejo |
| `gemini-2.0-flash-exp` | GRATIS (preview) | 1M | Experimentación |

### Procesamiento de Respuesta

```javascript
// En el nodo "Procesar Respuesta AI"
const response = $input.item.json;
let insight = 'Información relevante sobre infraestructura y logística.';

if (response.candidates && response.candidates.length > 0) {
  const candidate = response.candidates[0];
  if (candidate.content && candidate.content.parts && candidate.content.parts.length > 0) {
    insight = candidate.content.parts[0].text || insight;
    insight = insight.trim().substring(0, 250);
    if (insight.length === 250 && !insight.endsWith('.')) {
      insight = insight.substring(0, 247) + '...';
    }
  }
}

return {
  json: {
    ...$json,
    insight: insight
  }
};
```

---

## 4. OpenAI GPT-4o

### Por qué elegir OpenAI
- ✅ Muy conocido, documentación extensa
- ✅ Buena calidad de extracción
- ⚠️ Más caro que las alternativas
- ⚠️ Rate limits estrictos en tier gratuito

### Obtener API Key

```bash
1. Visitar: https://platform.openai.com/
2. Sign up / Login
3. API Keys → Create new secret key
4. Copiar key (empieza con sk-)
```

### Configuración en n8n

**Credencial**:
```
Type: HTTP Header Auth
Name: OpenAI API
Header Name: Authorization
Header Value: Bearer sk-XXXXXXXXXXXXXXXXXXXXXXXXXX
```

**Nodo HTTP Request**:
```json
{
  "url": "https://api.openai.com/v1/chat/completions",
  "method": "POST",
  "authentication": "predefinedCredentialType",
  "nodeCredentialType": "httpHeaderAuth",
  "sendHeaders": true,
  "headerParameters": {
    "parameters": [
      {
        "name": "Content-Type",
        "value": "application/json"
      }
    ]
  },
  "sendBody": true,
  "contentType": "json",
  "body": {
    "model": "gpt-4o-mini",
    "messages": [
      {
        "role": "system",
        "content": "Actúa como un Analista de Inteligencia de Inversiones. Extrae métricas financieras, porcentajes y cifras críticas del texto. Genera un resumen ejecutivo en formato Markdown de máximo 250 caracteres que empiece directamente con el dato más impactante."
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

### Modelos Disponibles

| Modelo | Costo (por 1M tokens) | Mejor para |
|--------|----------------------|------------|
| `gpt-4o-mini` | $0.15 in / $0.60 out | **Recomendado** - Balance |
| `gpt-4o` | $2.50 in / $10.00 out | Máxima calidad |
| `gpt-3.5-turbo` | $0.50 in / $1.50 out | Muy económico |

### Procesamiento de Respuesta

```javascript
// Igual que Groq (formato OpenAI compatible)
const response = $input.item.json;
let insight = 'Información relevante sobre infraestructura y logística.';

if (response.choices && response.choices.length > 0) {
  insight = response.choices[0].message.content || insight;
  insight = insight.trim().substring(0, 250);
  if (insight.length === 250 && !insight.endsWith('.')) {
    insight = insight.substring(0, 247) + '...';
  }
}

return {
  json: {
    ...$json,
    insight: insight
  }
};
```

---

## 5. Comparativa de Costos

### Escenario Real: 20 sitios × 20 artículos × 4 ejecuciones/mes = 1,600 artículos/mes

**Estimación de tokens**:
- Input: 1,600 × 500 tokens = 800K tokens (0.8M)
- Output: 1,600 × 100 tokens = 160K tokens (0.16M)

| Proveedor | Modelo | Costo Mensual | Velocidad | Calidad | **Recomendación** |
|-----------|--------|---------------|-----------|---------|-------------------|
| **Groq** | Llama 3.1 70B | $0.00 | ⚡⚡⚡⚡⚡ | ⭐⭐⭐⭐ | 🥇 **Desarrollo/MVP** |
| **Anthropic** | Claude 3.5 Haiku | $0.64 + $0.64 = $1.28 | ⚡⚡⚡⚡⚡ | ⭐⭐⭐⭐⭐ | 🥇 **Producción** |
| **Google** | Gemini Flash | $0.06 + $0.05 = $0.11 | ⚡⚡⚡⚡ | ⭐⭐⭐⭐ | 🥈 **Alto volumen** |
| **OpenAI** | GPT-4o mini | $0.12 + $0.10 = $0.22 | ⚡⚡⚡ | ⭐⭐⭐⭐ | 🥉 **Alternativa** |

### Recomendación por Caso de Uso

**🆓 Pruebas y Desarrollo**:
```
Groq (Llama 3.1 70B)
- Costo: $0
- Setup: 5 minutos
- Ideal para: Probar el workflow sin gastar
```

**🏆 Producción (Calidad)**:
```
Anthropic (Claude 3.5 Haiku)
- Costo: ~$1-2/mes
- Setup: 5 minutos
- Ideal para: Mejor extracción de métricas financieras
```

**💰 Producción (Economía)**:
```
Google (Gemini Flash)
- Costo: ~$0.10-0.20/mes
- Setup: 15 minutos (GCP setup)
- Ideal para: Máxima optimización de costos
```

---

## Optimización de Prompts por Proveedor

### Para Claude (mejor con instrucciones estructuradas)

```
Actúa como un Analista de Inteligencia de Inversiones.

TAREA:
1. Identifica cifras clave: inversión, ROI, porcentajes, plazos
2. Extrae beneficiarios: número de empresas, empleos, usuarios
3. Detecta métricas operativas: capacidad, volumen, tiempo

FORMATO DE SALIDA:
- Máximo 250 caracteres
- Inicia con la cifra más impactante
- Usa formato: [CIFRA] + [IMPACTO] + [CONTEXTO]

EJEMPLO:
"Inversión de $340M aumentará capacidad portuaria 40% para 2026. Reducción de tiempos de espera de 8 a 5 horas. Beneficia a 1,500 exportadores."

NO escribas introducciones genéricas.
```

### Para Groq/OpenAI (mejor con ejemplos)

```
Eres un analista financiero. Extrae datos de inversión del texto y genera un insight de máximo 250 caracteres.

BUENOS EJEMPLOS:
- "$450M reducirán entregas un 35%. ROI: 18 meses. 2,300 empresas beneficiadas."
- "87 km de autopista reducirán costos 28% ($12M/año). 45K vehículos/día. Q3 2025."

MALOS EJEMPLOS:
- "El artículo habla de mejoras en infraestructura..."
- "Se anuncian inversiones importantes..."

Analiza el siguiente contenido:
```

### Para Gemini (mejor con estructura JSON)

```
Analiza el texto y extrae:

{
  "inversion": "[monto y moneda]",
  "impacto": "[porcentaje o métrica principal]",
  "beneficiarios": "[número de afectados]",
  "plazo": "[fecha o periodo]"
}

Luego genera un insight de máximo 250 caracteres que combine estos datos iniciando con la cifra más impactante.
```

---

## Testing de Calidad

### Script de Validación

Agregar al final del nodo "Procesar Respuesta AI":

```javascript
// Validar calidad del insight
const insight = $json.insight;
let qualityScore = 0;

// Criterios de calidad
const hasNumbers = /\d+/.test(insight);
const hasPercentage = /%/.test(insight);
const hasCurrency = /\$|USD|EUR|MXN/.test(insight);
const hasDate = /\d{4}|Q[1-4]|202[4-9]/.test(insight);
const notGeneric = !/artículo|contenido|habla sobre|menciona que/i.test(insight);

if (hasNumbers) qualityScore += 20;
if (hasPercentage) qualityScore += 20;
if (hasCurrency) qualityScore += 20;
if (hasDate) qualityScore += 20;
if (notGeneric) qualityScore += 20;

return {
  json: {
    ...$json,
    insight_quality_score: qualityScore,
    insight_has_numbers: hasNumbers,
    insight_has_percentage: hasPercentage,
    insight_has_currency: hasCurrency
  }
};
```

Score mínimo recomendado: **60/100**

---

## Soporte

- **Anthropic Docs**: https://docs.anthropic.com/
- **Groq Docs**: https://console.groq.com/docs
- **Gemini Docs**: https://cloud.google.com/vertex-ai/docs
- **OpenAI Docs**: https://platform.openai.com/docs

**Última actualización**: Diciembre 2024
