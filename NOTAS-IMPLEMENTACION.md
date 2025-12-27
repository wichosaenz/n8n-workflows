# Notas de Implementación - Workflow LLMS.txt Auto-Updater

## Archivos Generados

### 1. Workflow Principal
- **Archivo**: `llms-txt-auto-updater-workflow.json`
- **Descripción**: Workflow completo de n8n con 17 nodos configurados
- **Formato**: JSON importable directamente en n8n

### 2. Documentación

#### a) Guía de Implementación Completa
- **Archivo**: `GUIA-IMPLEMENTACION.md`
- **Contenido**:
  - Requisitos previos y estimación de costos
  - Configuración paso a paso de credenciales (Google Sheets, Anthropic, sFTP)
  - Alternativas de AI providers (Groq, Gemini, OpenAI)
  - Preparación de Google Sheets con estructura de datos
  - Testing y validación
  - Troubleshooting de errores comunes
  - Optimizaciones avanzadas

#### b) README del Usuario
- **Archivo**: `LLMS-TXT-UPDATER-README.md`
- **Contenido**:
  - Introducción al workflow con ejemplos de transformación
  - Características principales
  - Instalación rápida (5 minutos)
  - Uso (manual y programado)
  - Arquitectura del workflow
  - Personalización de AI providers y prompts
  - Ejemplos de resultados reales
  - Integración con Pinecone

### 3. Archivos Complementarios

#### a) Template de Google Sheets
- **Archivo**: `google-sheets-template.csv`
- **Uso**: Estructura base para la hoja de cálculo con ejemplos
- **Columnas**: Sitio_URL, WP_API_Key, sFTP_Host, sFTP_User, sFTP_Pass, Lookback_Days

#### b) Código sFTP Dinámico
- **Archivo**: `sftp-dynamic-upload-code.js`
- **Uso**: Código para nodo Code que reemplaza el nodo sFTP estándar
- **Beneficio**: Permite usar credenciales dinámicas desde Google Sheets sin crear 20+ credenciales en n8n
- **Requiere**: `npm install ssh2-sftp-client` en servidor n8n

#### c) Configuración de AI Providers
- **Archivo**: `ai-providers-config.md`
- **Contenido**:
  - Configuración detallada para Anthropic, Groq, Gemini y OpenAI
  - Comparativa de costos y calidad
  - Ejemplos de código para cada provider
  - Procesamiento de respuestas específico por proveedor
  - Optimización de prompts según el modelo
  - Script de validación de calidad de insights

## Estructura del Workflow

### Flujo Principal

```
1. Google Sheets - Sitios Config
   ↓
2. Split Sitios - Batch Processor (procesa 1 sitio a la vez)
   ↓
3. Preparar Consulta WP (calcula fecha lookback, construye URL de API)
   ↓
4. WordPress API - Fetch Posts (obtiene artículos del sitio)
   ↓
5. Extraer y Limpiar Contenido (limpia HTML, extrae primeros 2-3 párrafos)
   ↓
6. Verificar si hay contenido
   ├─ [SÍ] → 7. Split Artículos - AI Processing
   └─ [NO] → 16. Log Sin Contenido → Loop back a 2
```

### Procesamiento de Artículos (cuando hay contenido)

```
7. Split Artículos - AI Processing (procesa 5 artículos a la vez)
   ├─ [Batch] → 8. Preparar Batch para AI
   │              ↓
   │           9. Anthropic Claude - Generate Insight (AI)
   │              ↓
   │           10. Procesar Respuesta AI (extrae insight)
   │              ↓
   │           Loop back a 7 (siguiente batch)
   │
   └─ [Done] → 12. Construir Archivo llms.txt
```

### Finalización y Upload

```
12. Construir Archivo llms.txt (compila header + artículos + footer)
    ↓
13. sFTP - Upload llms.txt (sube archivo a servidor)
    ↓
14. Wait - Rate Limit Protection (espera 5 segundos)
    ↓
15. Log Resultado (registra éxito)
    ↓
Loop back a 2 (siguiente sitio)
```

### Merge y Reporte Final

```
Cuando todos los sitios terminan:

Log Resultado + Log Sin Contenido
    ↓
17. Merge Resultados Finales
    ↓
18. Generar Reporte Final
```

## Nodos Clave y su Función

### Nodo 1: Google Sheets - Sitios Config
**Tipo**: `n8n-nodes-base.googleSheets`
**Función**: Lee la configuración de todos los sitios desde una hoja de Google Sheets
**Salida**: Array de objetos con datos de cada sitio

### Nodo 5: Extraer y Limpiar Contenido
**Tipo**: `n8n-nodes-base.code`
**Función**:
- Limpia HTML (remueve scripts, styles, tags)
- Extrae primeros 2-3 párrafos de cada artículo
- Limita a 1000 caracteres para optimizar costos de AI
- Filtra artículos muy cortos (<20 palabras)

### Nodo 9: Anthropic Claude - Generate Insight
**Tipo**: `n8n-nodes-base.httpRequest`
**Función**:
- Envía contenido extraído a Claude Haiku
- Usa System Prompt específico para Analista de Inversiones
- Extrae métricas financieras, porcentajes, cifras
- Genera resumen ejecutivo de máximo 250 caracteres

**System Prompt**:
```
Actúa como un Analista de Inteligencia de Inversiones. Extrae métricas financieras, porcentajes y cifras críticas del texto. Genera un resumen ejecutivo en formato Markdown de máximo 250 caracteres que empiece directamente con el dato más impactante. No uses introducciones como 'Este artículo trata sobre...'. Inicia con números, porcentajes o hechos concretos.
```

### Nodo 12: Construir Archivo llms.txt
**Tipo**: `n8n-nodes-base.code`
**Función**:
- Compila header con metadatos del sitio
- Lista artículos con sus insights
- Agrega footer con metodología
- Formatea todo en Markdown

**Estructura del archivo generado**:
```markdown
# nombre-sitio.com

> Portal de Infraestructura y Logística - Insights de Impacto para Deep Research
> Actualizado: 2024-12-15
> Artículos procesados: 18

## Recursos principales

- Sitio web: https://nombre-sitio.com
- RSS Feed: https://nombre-sitio.com/feed/
- API: https://nombre-sitio.com/wp-json/wp/v2/

---

## Insights de Impacto Recientes

### 1. [Título del Artículo](https://...)

**Insight:** Inversión de $450M reducirá tiempos de entrega un 35%...

**Fecha:** 2024-12-10

---

### 2. [Otro Artículo](https://...)

...

## Sobre este archivo

Este archivo llms.txt ha sido generado automáticamente para optimizar la recuperación de información por modelos de lenguaje de gran escala (LLMs)...
```

## Consideraciones Técnicas

### Manejo de Loops en Split in Batches

El workflow usa dos niveles de `Split in Batches`:

1. **Primer nivel** (Sitios): Procesa 1 sitio a la vez
   - Batch size: 1
   - Loop back desde: "Log Resultado" o "Log Sin Contenido"
   - Termina cuando se procesaron todos los sitios

2. **Segundo nivel** (Artículos): Procesa 5 artículos a la vez
   - Batch size: 5
   - Loop back desde: "Procesar Respuesta AI"
   - Termina cuando se procesaron todos los artículos del sitio actual
   - Output final va a: "Construir Archivo llms.txt"

### Rate Limiting

- **Wait entre sitios**: 5 segundos (configurable en nodo "Wait")
- **Propósito**: Evitar bloqueos por Rate Limit en:
  - WordPress REST API
  - AI Provider API
  - Servidor sFTP

### Optimización de Costos de AI

- **Límite de contenido**: 1000 caracteres por artículo
- **Batch processing**: 5 artículos por llamada (reduce overhead)
- **Modelo económico**: Claude Haiku ($0.80 input / $4 output por 1M tokens)
- **Estimación**: ~$0.30-0.50 por 1,000 artículos procesados

### Manejo de Errores

El workflow está diseñado para ser robusto:

1. **Sitios sin contenido**: Se marcan como "SKIPPED" y continúan con el siguiente
2. **API WordPress fallida**: El error se loguea pero no detiene el workflow
3. **AI API timeout**: Se puede configurar timeout de 30 segundos
4. **sFTP fallido**: El error se registra en el log pero continúa procesando otros sitios

## Limitaciones y Consideraciones

### Credenciales sFTP

El nodo sFTP estándar de n8n requiere credenciales pre-configuradas, lo que significa:

- **Opción 1 (Simple pero limitada)**: Crear una credencial por cada sitio en n8n (20 credenciales para 20 sitios)
- **Opción 2 (Recomendada)**: Usar el Code node con `ssh2-sftp-client` que permite credenciales dinámicas desde Google Sheets

**Implementación recomendada**: Ver archivo `sftp-dynamic-upload-code.js`

### Límites de WordPress REST API

- **Posts por request**: 20 (configurado en el nodo "Preparar Consulta WP")
- **Paginación**: El workflow actual NO maneja paginación
- **Solución**: Si un sitio tiene >20 artículos en el periodo lookback, solo se procesarán los 20 más recientes
- **Mejora futura**: Agregar loop de paginación para obtener todos los artículos

### Procesamiento de HTML

La función `extractFirstParagraphs()` es básica y puede no funcionar perfectamente con todos los formatos de HTML:

- **Mejora recomendada**: Usar librería como `turndown` para conversión HTML → Markdown más robusta
- **Instalación**: `npm install turndown` en servidor n8n

## Próximos Pasos de Implementación

### 1. Instalación Inmediata (30 minutos)

```bash
# 1. Importar workflow en n8n
# 2. Configurar credencial de Google Sheets
# 3. Crear Google Sheet con 1 sitio de prueba
# 4. Configurar credencial de Anthropic (o Groq gratis)
# 5. Ejecutar workflow con 1 sitio
# 6. Verificar llms.txt generado
```

### 2. Configuración para Producción (2-3 horas)

```bash
# 1. Agregar todos los 20 sitios al Google Sheet
# 2. Generar Application Passwords en cada WordPress
# 3. Verificar acceso sFTP a cada servidor
# 4. Implementar Code node para sFTP dinámico (si aplica)
# 5. Ejecutar workflow completo con logging activado
# 6. Configurar Cron para ejecución semanal
# 7. Agregar nodo de notificaciones (Slack/Email) al final
```

### 3. Optimizaciones Opcionales

```bash
# 1. Implementar caché de artículos procesados
# 2. Agregar paginación en WordPress API
# 3. Configurar backup de llms.txt antes de sobrescribir
# 4. Implementar validación de calidad de insights
# 5. Agregar exportación de insights a Pinecone
# 6. Generar métricas de ejecución en Google Sheets
```

## Soporte y Recursos

### Documentación Oficial

- **n8n Docs**: https://docs.n8n.io/
- **WordPress REST API**: https://developer.wordpress.org/rest-api/reference/posts/
- **Anthropic API**: https://docs.anthropic.com/en/api/messages
- **llms.txt Spec**: https://llmstxt.org/

### Comunidad

- **n8n Community Forum**: https://community.n8n.io/
- **n8n Discord**: https://discord.gg/n8n

### Archivos de Referencia

1. `GUIA-IMPLEMENTACION.md`: Guía completa paso a paso
2. `LLMS-TXT-UPDATER-README.md`: Documentación del usuario
3. `ai-providers-config.md`: Configuración de AI providers
4. `sftp-dynamic-upload-code.js`: Código para sFTP dinámico
5. `google-sheets-template.csv`: Template de hoja de cálculo

---

**Versión del Workflow**: 1.0.0
**Fecha**: Diciembre 2024
**Compatibilidad**: n8n Community Edition v1.0+
**Autor**: Claude Code
**Licencia**: MIT
