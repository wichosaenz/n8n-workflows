# Newsletter Mensual Automatizado - Guía de Configuración

## 📋 Descripción General

Este workflow automatiza completamente el envío de tu newsletter mensual usando:
- **RAG (Retrieval-Augmented Generation)** con Pinecone para contenido inteligente
- **Claude 4.0 Sonnet** para generación de artículos de alta calidad
- **Google Gemini** para imágenes de cabecera profesionales
- **Soporte bilingüe** (Inglés principal + Snippet español)
- **Envío masivo** vía Gmail con batching inteligente
- **Logging completo** en Google Sheets

---

## 🔧 Credenciales Requeridas

Antes de importar el workflow, configura las siguientes credenciales en n8n:

### 1. Pinecone (`pinecone-everest-bothers-01`)
```
Tipo: Pinecone API
- API Key: [Tu Pinecone API Key]
- Environment: [Tu Pinecone Environment, ej: us-east-1-aws]
- Index Name: everest-bothers-01
```

**Requisitos de Metadatos en Pinecone:**
Tus vectores deben incluir estos campos en metadata:
- `titulo` (string)
- `autor` (string)
- `grupo_investigador` (string: "Distribución Minorista", "Industria Transporte", etc.)
- `fecha_publicacion` (string: formato "YYYY-MM-DD")
- `contenido` o `resumen` (string: texto completo del artículo)

### 2. Anthropic Claude (`anthropic-claude-main`)
```
Tipo: Anthropic API
- API Key: [Tu Anthropic API Key]
```
**Nota:** El workflow usa `claude-sonnet-4-5-20250929`. Ajusta el modelo si usas otra versión.

### 3. Google Gemini (`gemini-main`)
```
Tipo: Google Gemini OAuth2 API
- OAuth2 Credentials configuradas para tu cuenta Google
```
**Alternativa:** Puedes usar API Key si prefieres:
- API Key: [Tu Google AI Studio API Key]

### 4. MySQL (`mysql-dreamhost-prod`)
```
Tipo: MySQL
- Host: [Tu host MySQL, ej: mysql.dreamhost.com]
- Database: [Nombre de tu base de datos]
- User: [Usuario MySQL]
- Password: [Contraseña]
- Port: 3306 (o el puerto que uses)
```

**Esquema de Tabla Requerido:**
```sql
CREATE TABLE newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    nombre VARCHAR(255) NOT NULL,
    grupo_investigador VARCHAR(255),
    activo TINYINT(1) DEFAULT 1,
    fecha_suscripcion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_activo (activo)
);
```

### 5. Gmail (`gmail-sender-oauth2`)
```
Tipo: Gmail OAuth2
- OAuth2 Credentials configuradas
```
**Importante:** La cuenta debe tener permisos para enviar emails.

### 6. Google Sheets (`google-sheets-oauth2`)
```
Tipo: Google Sheets OAuth2 API
- OAuth2 Credentials configuradas
```

**Configuración de la Hoja de Logs:**
1. Crea un nuevo Google Sheet
2. Nómbralo "Newsletter Logs" (o el nombre que prefieras)
3. Crea una hoja llamada "Logs" con estos headers en la fila 1:
   ```
   email | estado | fecha | error | mensaje_id
   ```
4. Copia el ID del spreadsheet (de la URL: `https://docs.google.com/spreadsheets/d/SPREADSHEET_ID/edit`)
5. Reemplaza `INSERTAR_SPREADSHEET_ID_AQUI` en los nodos:
   - `Google Sheets - Log Éxito` (línea 459)
   - `Google Sheets - Log Error` (línea 485)

---

## 📥 Importación del Workflow

1. En n8n, ve a **Workflows** → **Import from File**
2. Selecciona el archivo `newsletter-mensual-automatizado.json`
3. El workflow se importará con todas las configuraciones

---

## ⚙️ Configuraciones Finales

### 1. Nodo de Embeddings (IMPORTANTE)

El nodo **"Generar Embedding de Consulta"** usa OpenAI como placeholder. Necesitas:

**Opción A - Usar OpenAI Embeddings:**
1. Crea credencial `openAiApi`
2. En el nodo, configura:
   - Model: `text-embedding-3-small`
   - API Key en credenciales

**Opción B - Usar Anthropic/Voyage/Cohere:**
1. Reemplaza el nodo con el servicio de embeddings que uses
2. Asegúrate que el output sea compatible con Pinecone (1536 dimensiones para OpenAI)

### 2. Ajustar el Schedule Trigger

El nodo **"Schedule - Fin de Mes"** está configurado con `0 0 1 * *` (primer día del mes a las 00:00).

**Para ejecutar el último día del mes:**
```cron
0 23 L * *
```
(L = Last day of month, a las 23:00)

**Nota:** La sintaxis `L` depende de tu versión de n8n. Si no funciona, usa:
```cron
0 23 28-31 * *
```
Y añade un nodo IF para verificar si mañana es el día 1.

### 3. Personalizar URLs y Branding

Busca y reemplaza en el nodo **"Gmail - Enviar Newsletter"**:
- `https://nuestro-sitio.com/es/newsletter-mensual` → Tu URL de newsletter en español
- `https://nuestro-sitio.com/unsubscribe` → Tu URL de desuscripción
- `https://nuestro-sitio.com/preferences` → Tu URL de preferencias
- `Everest Brothers Research` → Nombre de tu organización

---

## 🚀 Ejecución y Pruebas

### Primera Ejecución (Manual)

1. **Prepara datos de prueba:**
   - Inserta 2-3 suscriptores de prueba en MySQL (usa emails tuyos)
   - Asegúrate de tener vectores en Pinecone del mes pasado

2. **Ejecuta manualmente:**
   - Click en "Execute Workflow" (botón de play)
   - El nodo **"Manual Trigger"** iniciará el proceso

3. **Monitorea la ejecución:**
   - Observa cada nodo conforme se ejecuta
   - Revisa los datos en cada paso (click en el nodo para ver output)

### Verificación de Outputs

**Después de "Claude 4.0 - Generar Artículo":**
- Verifica que el HTML del artículo se vea correcto
- Debe tener estructura HTML válida (h2, p, ul, etc.)

**Después de "Gemini - Generar Imagen Header":**
- Verifica que se generó una imagen (base64 o URL)
- Si falla, revisa los créditos de Gemini

**Después de "Gmail - Enviar Newsletter":**
- Revisa tu inbox (puede tardar 1-2 minutos)
- Verifica que el HTML se renderice correctamente

**En Google Sheets:**
- Confirma que se registraron los envíos en la hoja "Logs"

---

## 🔍 Troubleshooting

### Error: "Pinecone index not found"
- Verifica el nombre del índice en las credenciales
- Asegúrate de tener un índice llamado `everest-bothers-01`

### Error: "No matches found in Pinecone"
- Verifica que hay vectores con `fecha_publicacion` del mes pasado
- Ajusta el filtro de fechas en el nodo Pinecone Query
- Reduce `topK` a 10 para testing

### Error: "Gmail API rate limit exceeded"
- El nodo `Split in Batches` está configurado para 100 emails/lote
- Reduce a 50 o añade un nodo `Wait` entre lotes
- Configura: `Wait` → 1 segundo después de cada lote

### Error: "Claude API timeout"
- El artículo es muy largo (50 matches × contenido extenso)
- Reduce `topK` a 20-30 en el nodo Pinecone
- Aumenta `maxTokens` en Claude a 8000

### Imagen no se genera (Gemini)
- Verifica créditos de Google AI Studio
- Gemini a veces falla en generar imágenes por políticas de contenido
- **Alternativa:** Reemplaza con Stable Diffusion, DALL-E 3, o Midjourney

### Emails van a spam
- Configura SPF/DKIM/DMARC en tu dominio
- Usa un servicio profesional (SendGrid, Mailgun) en lugar de Gmail
- Añade un nodo de "warm-up" que envíe 10 emails/día antes del envío masivo

---

## 🎨 Personalizaciones Avanzadas

### A. Cambiar el Diseño del Email

Edita el HTML en el nodo **"Gmail - Enviar Newsletter"**:
- Los estilos están inline en `<style>`
- Usa herramientas como [Foundation for Emails](https://get.foundation/emails.html) para diseños responsive
- Testea en [Litmus](https://litmus.com/) o [Email on Acid](https://www.emailonacid.com/)

### B. Personalización por Grupo de Investigador

Añade un nodo **IF** después de "Preparar Datos para Envío":
```javascript
{{ $json.grupo_investigador === "Distribución Minorista" }}
```
Luego bifurca el flujo para enviar contenido específico por grupo.

### C. A/B Testing de Asuntos

1. Añade un nodo **"Code"** antes de Gmail:
```javascript
const subjects = [
  "Newsletter Mensual - Investigación de {{month}}",
  "Descubre los hallazgos de {{month}}",
  "Tu resumen mensual de investigación está aquí"
];

// Round-robin
const index = $item(0).$itemIndex % subjects.length;
return { subject: subjects[index].replace('{{month}}', $now.minus({months:1}).toFormat('MMMM')) };
```

2. Modifica el Subject del nodo Gmail a: `={{ $json.subject }}`

### D. Incluir Adjuntos PDF

1. Añade un nodo **"HTML to PDF"** (requiere n8n-nodes-pdf):
```
Input HTML: {{ $json.articulo_html }}
Output: binary (PDF)
```

2. En el nodo Gmail, configura:
```
Attachments: {{ $binary.data }}
Attachment Name: Newsletter-{{$now.minus({months:1}).toFormat('yyyy-MM')}}.pdf
```

---

## 📊 Monitoreo y Analítica

### Dashboards en Google Sheets

Crea una segunda hoja "Dashboard" en tu spreadsheet con estas fórmulas:

**Total Enviados:**
```
=COUNTIF(Logs!B:B,"Enviado")
```

**Total Fallidos:**
```
=COUNTIF(Logs!B:B,"Fallido")
```

**Tasa de Éxito:**
```
=COUNTIF(Logs!B:B,"Enviado")/COUNTA(Logs!B:B)
```

**Errores Más Comunes:**
```
=QUERY(Logs!A:E, "SELECT D, COUNT(D) WHERE B='Fallido' GROUP BY D ORDER BY COUNT(D) DESC LIMIT 5")
```

### Notificaciones de Errores

Añade un nodo **"Send Email"** (o Slack/Telegram) después de "Google Sheets - Log Error":
```
To: admin@tuempresa.com
Subject: 🚨 Errores en Newsletter Mensual
Body: Se detectaron {{ $('Google Sheets - Log Error').all().length }} errores al enviar el newsletter.
```

---

## 🔐 Seguridad y Mejores Prácticas

1. **Nunca compartas las credenciales**
   - Usa variables de entorno para API Keys
   - Configura permisos mínimos (principio de menor privilegio)

2. **Backup del workflow**
   - Exporta el JSON mensualmente
   - Versiona en Git (sin credenciales)

3. **Validación de emails**
   - Añade un nodo de validación antes de enviar
   - Usa regex: `/^[^\s@]+@[^\s@]+\.[^\s@]+$/`

4. **Rate limiting**
   - Gmail tiene límite de 500 emails/día (cuentas gratuitas)
   - G Suite: 2000 emails/día
   - Considera servicios transaccionales para >2000 suscriptores

5. **Compliance (GDPR/CAN-SPAM)**
   - El template incluye enlace de unsubscribe (OBLIGATORIO)
   - Añade dirección física de tu empresa
   - Registra consentimientos en MySQL

---

## 📈 Optimizaciones de Rendimiento

### Para >1000 suscriptores:

1. **Usa n8n en modo Queue:**
```bash
export EXECUTIONS_MODE=queue
export QUEUE_BULL_REDIS_HOST=localhost
```

2. **Paraleliza el envío:**
   - Usa `Split in Batches` con `batchSize: 50`
   - Añade múltiples workers de n8n

3. **Cachea el contenido:**
   - Usa un nodo **"Sticky Note"** con el artículo generado
   - Evita regenerar para cada email (ahorra tokens de Claude)

---

## 🆘 Soporte

Si encuentras problemas:

1. **Revisa los logs de n8n:**
   ```bash
   docker logs n8n
   ```

2. **Activa debug mode:**
   - En cada nodo, click en "Settings" → "Always Output Data"

3. **Comunidad n8n:**
   - [Forum oficial](https://community.n8n.io/)
   - [Discord](https://discord.gg/n8n)

---

## 📝 Changelog

**v1.0 (2025-11-16)**
- Lanzamiento inicial
- Soporte para RAG con Pinecone
- Generación de contenido con Claude 4.0
- Imágenes con Gemini
- Envío vía Gmail
- Logging en Google Sheets
- Soporte bilingüe (EN/ES)

---

## 🎯 Próximas Mejoras

- [ ] Integración con analytics (track opens/clicks)
- [ ] Personalización AI por suscriptor (contenido adaptado)
- [ ] Multi-idioma completo (no solo snippet)
- [ ] Preview mode (enviar a admin antes del envío masivo)
- [ ] Migración a SendGrid/Mailgun para escalabilidad
- [ ] Webhook para desuscripciones automáticas

---

**¡Listo para ejecutar!** 🚀

Si tienes preguntas, revisa la sección de Troubleshooting o contacta a tu equipo de DevOps.
