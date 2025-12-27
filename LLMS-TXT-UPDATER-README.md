# LLMS.txt Auto-Updater para Redes de Sitios

Workflow de n8n Community Edition que automatiza la generación y actualización de archivos `llms.txt` con **Insights de Impacto** para modelos de Deep Research en 20+ sitios web de infraestructura y logística.

## ¿Qué hace este workflow?

Transforma esto (contenido genérico):
```
# mi-sitio-logistica.com

- Artículo 1: Nueva terminal portuaria
- Artículo 2: Mejoras en transporte
```

En esto (insights accionables):
```markdown
# mi-sitio-logistica.com

### 1. [Nueva terminal portuaria aumenta capacidad](https://...)

**Insight:** Inversión de $450M reducirá tiempos de entrega un 35% en corredores intermodales. ROI: 18 meses. Beneficia a 2,300 empresas logísticas.

**Fecha:** 2024-12-15

---

### 2. [Corredor logístico conecta puertos](https://...)

**Insight:** 127 km de infraestructura nueva transportarán 8,5M toneladas/año. Reducción de costos del 22% para exportadores. Operativo Q2 2025.

**Fecha:** 2024-12-10
```

## Características

- ✅ **Procesamiento automático** de 20+ sitios en una ejecución
- 🤖 **AI-powered insights** usando Claude, Gemini o Groq
- 📊 **Extracción inteligente** de cifras, porcentajes y métricas clave
- 🔄 **Actualización automática** vía sFTP
- 💰 **Bajo costo**: ~$2-5 USD/mes para 20 sitios
- 🆓 **100% Community Edition** compatible
- ⚡ **Rate limiting** automático para evitar bloqueos
- 📈 **Reportes detallados** de cada ejecución

## Requisitos

### Software
- n8n Community Edition v1.0+
- Node.js v18+ (si usas Docker, ya incluido)

### Servicios
- Google Sheets (gratis)
- Anthropic API ($5 créditos iniciales gratis) o Groq (gratis)
- Acceso sFTP a tus servidores
- WordPress REST API habilitada en los sitios

## Instalación Rápida

### 1. Importar Workflow

```bash
# Descargar el archivo JSON
wget https://github.com/TU_REPO/llms-txt-auto-updater-workflow.json

# O copiar manualmente el contenido de llms-txt-auto-updater-workflow.json
```

En n8n:
```
Workflows → Import from File → Seleccionar JSON → Import
```

### 2. Configurar Google Sheet

**Crear hoja de cálculo con esta estructura**:

| Sitio_URL | WP_API_Key | sFTP_Host | sFTP_User | sFTP_Pass | Lookback_Days |
|-----------|------------|-----------|-----------|-----------|---------------|
| https://sitio1.com | wp_abc123... | ftp.sitio1.com | deploy | pass123 | 30 |
| https://sitio2.com | wp_def456... | ftp.sitio2.com | admin | secure456 | 45 |

[📖 Ver guía detallada de configuración](./GUIA-IMPLEMENTACION.md#preparación-de-google-sheets)

### 3. Configurar Credenciales

**Mínimo necesario**:

1. **Google Sheets OAuth2**
   ```
   n8n → Credentials → New → Google Sheets OAuth2
   ```
   [Instrucciones paso a paso](./GUIA-IMPLEMENTACION.md#1-google-sheets-oauth2)

2. **Anthropic API** (o Groq gratis)
   ```
   n8n → Credentials → New → HTTP Header Auth
   Header: x-api-key
   Value: sk-ant-XXXXXXXXX
   ```
   [Obtener API key](https://console.anthropic.com/)

3. **sFTP** (dinámico o pre-configurado)

   Ver [Guía de sFTP](./GUIA-IMPLEMENTACION.md#4-credenciales-sftp-configuración-dinámica)

### 4. Conectar y Probar

```bash
# En n8n UI
1. Abrir el workflow importado
2. Click en "Google Sheets - Sitios Config"
3. Seleccionar tu credencial de Google Sheets
4. Pegar el URL de tu hoja
5. Click "Execute Workflow"

# Primera ejecución: usar solo 1 sitio para probar
```

## Uso

### Ejecución Manual

```bash
n8n UI → Workflow "LLMS.txt Auto-Updater" → Execute Workflow
```

Verás en tiempo real:
- ✅ Sitios procesados
- 📝 Artículos analizados
- 🤖 Insights generados
- ⬆️ Archivos subidos

### Ejecución Programada

Agregar al inicio del workflow un nodo **Cron**:

```
Cron → Configurar:
  Mode: Every Week
  Weekday: Monday
  Hour: 03:00
  Timezone: America/Mexico_City

→ Conectar a "Google Sheets - Sitios Config"
```

Guardar workflow y activar con el switch "Active".

### Monitoreo de Resultados

Cada ejecución genera un reporte final con:

```json
{
  "execution_timestamp": "2024-12-15T03:00:00Z",
  "total_sites_processed": 20,
  "successful_uploads": 19,
  "skipped_sites": 1,
  "total_articles_processed": 387,
  "total_file_size_bytes": 156420
}
```

Ver en:
```
n8n → Workflows → LLMS.txt Auto-Updater → Executions → [Última ejecución]
```

## Arquitectura del Workflow

```
Google Sheets (config)
    ↓
Split Sitios (uno por uno)
    ↓
WordPress API (obtener artículos)
    ↓
Extraer Contenido (primeros 2-3 párrafos)
    ↓
[SI hay contenido]
    ↓
Split Artículos (batch de 5)
    ↓
AI Processing (Claude/Gemini)
    ↓
Generar Insights
    ↓
Construir llms.txt
    ↓
Upload sFTP
    ↓
Wait 5s (rate limit)
    ↓
Loop siguiente sitio

[SI NO hay contenido]
    ↓
Log "Skipped"
    ↓
Loop siguiente sitio
```

## Personalización

### Cambiar el Modelo de AI

**Usar Groq (GRATIS)**:

1. Ir al nodo "Anthropic Claude - Generate Insight"
2. Cambiar URL a: `https://api.groq.com/openai/v1/chat/completions`
3. Modificar body según [esta guía](./GUIA-IMPLEMENTACION.md#configuración-de-groq-opción-gratis)

**Usar Gemini**:

Ver [Alternativa Google Vertex AI](./GUIA-IMPLEMENTACION.md#3-alternativa-google-vertex-ai-gemini)

### Personalizar el System Prompt

En el nodo "Anthropic Claude - Generate Insight", editar el campo `system`:

**Para foco en ESG**:
```
Actúa como un Analista ESG. Extrae métricas de sostenibilidad, reducción de emisiones, y cumplimiento normativo. Resume en máximo 250 caracteres iniciando con el indicador ESG más relevante.
```

**Para foco en ROI**:
```
Actúa como un CFO. Extrae cifras de retorno de inversión, periodos de payback, y proyecciones financieras. Resume en máximo 250 caracteres iniciando con el ROI o ahorro más significativo.
```

### Ajustar Lookback Days por Sitio

En Google Sheets, modificar la columna `Lookback_Days`:
- **30 días**: Sitios con publicaciones frecuentes
- **60 días**: Sitios con publicaciones semanales
- **90 días**: Sitios con publicaciones mensuales

## Costos Estimados

### Escenario: 20 sitios × 20 artículos/sitio × 1 ejecución/semana

| Servicio | Costo Mensual | Notas |
|----------|---------------|-------|
| **n8n Community** | $0 | Autohosteado gratis |
| **Anthropic (Claude Haiku)** | $2-5 | ~400 artículos/mes |
| **Groq (Llama 3.1)** | $0 | Tier gratis: 6K req/día |
| **Gemini Flash** | $1-3 | Más económico, buena calidad |
| **Google Sheets** | $0 | Uso básico gratis |

**Total**: $0-5 USD/mes (dependiendo del AI provider)

## Troubleshooting

### Error: "Invalid API Key" en WordPress

**Solución**:
```bash
1. WP Admin → Usuarios → Tu Perfil
2. Scroll hasta "Application Passwords"
3. Nombre: "n8n LLMS Updater"
4. Click "Add New Application Password"
5. Copiar el password generado (formato: xxxx xxxx xxxx xxxx)
6. Pegar en Google Sheet columna WP_API_Key
```

### Error: "Connection timeout" en sFTP

**Solución**:
```bash
# Verificar conectividad desde terminal
sftp usuario@host

# Si falla, revisar:
- Puerto correcto (22 por defecto)
- Firewall permite conexión desde IP de n8n
- Credenciales correctas
```

Ver [más soluciones](./GUIA-IMPLEMENTACION.md#troubleshooting)

### Insights genéricos sin datos

**Causa**: AI no encuentra números en el contenido.

**Solución**:
1. Aumentar párrafos extraídos de 3 a 5 en el nodo "Extraer y Limpiar Contenido"
2. Personalizar System Prompt con ejemplos específicos de tu industria
3. Cambiar modelo (Claude > Gemini > Groq en calidad de extracción)

## Ejemplos de Resultados

### Antes (descripción genérica)
```
Artículo sobre nueva autopista de peaje que conectará zona industrial
```

### Después (insight de impacto)
```
**Insight:** Autopista de 87 km reducirá costos logísticos un 28% ($12M anuales).
Inversión: $340M PPP. Transitarán 45K vehículos/día. Operativa Q3 2025.
```

---

### Antes (comunicado corporativo)
```
Empresa anuncia expansión de su red de almacenes frigoríficos
```

### Después (insight de impacto)
```
**Insight:** 15 nuevos centros frigoríficos (+120K m²) aumentarán capacidad 40%.
Inversión: $68M. Generará 890 empleos. Reducción de pérdidas del 15% al 6%.
```

## Integración con Pinecone

Si ya tienes embeddings en Pinecone, este workflow **complementa** esa base:

- **Pinecone**: Búsqueda semántica de artículos completos
- **llms.txt**: Resumen ejecutivo pre-procesado para LLMs

Puedes agregar un nodo al final para también indexar los insights:

```
HTTP Request → Pinecone Upsert
  URL: https://INDEX-NAME-PROJECT.svc.gcp-starter.pinecone.io/vectors/upsert
  Body: {
    "vectors": [{
      "id": "insight-{{ $json.url }}",
      "values": [0.1, 0.2, ...], // Embeddings del insight
      "metadata": {
        "type": "impact_insight",
        "url": "{{ $json.url }}",
        "insight": "{{ $json.insight }}",
        "date": "{{ $json.date }}"
      }
    }]
  }
```

## Próximas Mejoras

- [ ] Soporte para sitios no-WordPress (Webflow, Ghost)
- [ ] Generación de gráficos de tendencias
- [ ] Envío de notificaciones (Email/Slack) al completar
- [ ] Exportación de insights a CSV
- [ ] Integración con Google Analytics para priorizar artículos

## Contribuir

¿Mejoras o bugs? Abre un issue o PR en este repositorio.

## Licencia

MIT License - Uso libre para proyectos comerciales y personales.

## Soporte

- 📖 [Guía completa de implementación](./GUIA-IMPLEMENTACION.md)
- 💬 [Foro de n8n](https://community.n8n.io/)
- 📧 Email: tu-email@example.com

---

**Desarrollado para**: Redes de sitios de infraestructura y logística
**Compatible con**: n8n Community Edition v1.0+
**Última actualización**: Diciembre 2024
**Versión**: 1.0.0

---

## Inicio Rápido (5 minutos)

```bash
# 1. Importar workflow en n8n
#    Workflows → Import → Seleccionar llms-txt-auto-updater-workflow.json

# 2. Crear Google Sheet con tus sitios
#    Copiar template: https://docs.google.com/spreadsheets/d/TEMPLATE_ID

# 3. Configurar credencial Anthropic
#    Obtener API key: https://console.anthropic.com/

# 4. Conectar Google Sheet en el primer nodo

# 5. Execute Workflow (probar con 1 sitio primero)

# 6. Verificar resultado:
curl https://tu-sitio.com/llms.txt
```

**¡Listo!** Tu primer `llms.txt` con insights de impacto ha sido generado.
