#!/usr/bin/env python3
"""
Script de verificación de credenciales para el workflow de Newsletter

Este script verifica que todas las credenciales y servicios estén
configurados correctamente ANTES de ejecutar el workflow en n8n.

Uso:
    1. Crea un archivo .env con todas tus credenciales
    2. Ejecuta: python verify-credentials.py
    3. Revisa los resultados y corrige errores antes de usar n8n

Requisitos:
    pip install python-dotenv pinecone-client mysql-connector-python google-auth google-auth-oauthlib google-auth-httplib2 google-api-python-client anthropic openai
"""

import os
import sys
from datetime import datetime
from dotenv import load_dotenv

# Colores para output
class Colors:
    GREEN = '\033[92m'
    RED = '\033[91m'
    YELLOW = '\033[93m'
    BLUE = '\033[94m'
    END = '\033[0m'

def print_success(msg):
    print(f"{Colors.GREEN}✅ {msg}{Colors.END}")

def print_error(msg):
    print(f"{Colors.RED}❌ {msg}{Colors.END}")

def print_warning(msg):
    print(f"{Colors.YELLOW}⚠️  {msg}{Colors.END}")

def print_info(msg):
    print(f"{Colors.BLUE}ℹ️  {msg}{Colors.END}")

def print_section(title):
    print(f"\n{Colors.BLUE}{'='*60}\n{title}\n{'='*60}{Colors.END}")


# Cargar variables de entorno
load_dotenv()

# Variables globales para tracking
errors = []
warnings = []


def verificar_pinecone():
    """Verifica conexión y configuración de Pinecone"""
    print_section("1. PINECONE")

    api_key = os.getenv("PINECONE_API_KEY")
    environment = os.getenv("PINECONE_ENVIRONMENT")

    if not api_key or not environment:
        print_error("Faltan variables: PINECONE_API_KEY, PINECONE_ENVIRONMENT")
        errors.append("Pinecone: Credenciales faltantes")
        return

    try:
        import pinecone

        print_info(f"Conectando a Pinecone (env: {environment})...")
        pinecone.init(api_key=api_key, environment=environment)

        # Listar índices
        indexes = pinecone.list_indexes()
        print_success(f"Conexión exitosa. Índices disponibles: {len(indexes)}")

        # Verificar índice específico
        if "everest-bothers-01" in indexes:
            print_success("Índice 'everest-bothers-01' encontrado")

            index = pinecone.Index("everest-bothers-01")
            stats = index.describe_index_stats()

            print_info(f"   Vectores en índice: {stats.total_vector_count}")
            print_info(f"   Dimensión: {stats.dimension}")

            if stats.total_vector_count == 0:
                print_warning("El índice está vacío. Ejecuta pinecone-upload-example.py")
                warnings.append("Pinecone: Índice vacío")
            elif stats.total_vector_count < 10:
                print_warning(f"Solo {stats.total_vector_count} vectores. Considera añadir más datos.")
        else:
            print_error("Índice 'everest-bothers-01' NO encontrado")
            print_info(f"   Índices disponibles: {', '.join(indexes) if indexes else 'Ninguno'}")
            errors.append("Pinecone: Índice 'everest-bothers-01' no existe")

    except Exception as e:
        print_error(f"Error de Pinecone: {str(e)}")
        errors.append(f"Pinecone: {str(e)}")


def verificar_anthropic():
    """Verifica API de Anthropic Claude"""
    print_section("2. ANTHROPIC CLAUDE")

    api_key = os.getenv("ANTHROPIC_API_KEY")

    if not api_key:
        print_error("Falta variable: ANTHROPIC_API_KEY")
        errors.append("Anthropic: API Key faltante")
        return

    try:
        import anthropic

        print_info("Probando API de Anthropic...")
        client = anthropic.Anthropic(api_key=api_key)

        # Hacer una llamada de prueba simple
        message = client.messages.create(
            model="claude-sonnet-4-5-20250929",
            max_tokens=50,
            messages=[
                {"role": "user", "content": "Di solo 'OK' si me recibes."}
            ]
        )

        print_success("Conexión a Claude 4.0 Sonnet exitosa")
        print_info(f"   Respuesta: {message.content[0].text}")
        print_info(f"   Tokens usados: input={message.usage.input_tokens}, output={message.usage.output_tokens}")

    except Exception as e:
        print_error(f"Error de Anthropic: {str(e)}")
        errors.append(f"Anthropic: {str(e)}")


def verificar_openai():
    """Verifica API de OpenAI (para embeddings)"""
    print_section("3. OPENAI (Embeddings)")

    api_key = os.getenv("OPENAI_API_KEY")

    if not api_key:
        print_warning("Variable OPENAI_API_KEY no encontrada")
        print_info("   Si usas otro proveedor de embeddings (Voyage, Cohere), ignora esto")
        warnings.append("OpenAI: API Key no configurada (opcional si usas otro proveedor)")
        return

    try:
        from openai import OpenAI

        print_info("Probando API de OpenAI...")
        client = OpenAI(api_key=api_key)

        # Generar un embedding de prueba
        response = client.embeddings.create(
            model="text-embedding-3-small",
            input="Test de verificación"
        )

        embedding = response.data[0].embedding
        print_success("Generación de embeddings exitosa")
        print_info(f"   Modelo: text-embedding-3-small")
        print_info(f"   Dimensión del vector: {len(embedding)}")
        print_info(f"   Tokens usados: {response.usage.total_tokens}")

    except Exception as e:
        print_error(f"Error de OpenAI: {str(e)}")
        errors.append(f"OpenAI: {str(e)}")


def verificar_gemini():
    """Verifica API de Google Gemini"""
    print_section("4. GOOGLE GEMINI")

    api_key = os.getenv("GEMINI_API_KEY")

    if not api_key:
        print_warning("Variable GEMINI_API_KEY no encontrada")
        print_info("   Si usas OAuth2, configúralo directamente en n8n")
        warnings.append("Gemini: API Key no configurada (puede usar OAuth2)")
        return

    try:
        import google.generativeai as genai

        print_info("Probando API de Gemini...")
        genai.configure(api_key=api_key)

        # Listar modelos disponibles
        models = [m.name for m in genai.list_models() if 'generateContent' in m.supported_generation_methods]

        print_success(f"Conexión a Gemini exitosa. Modelos disponibles: {len(models)}")

        # Verificar modelo específico
        if "models/gemini-2.0-flash-exp" in models or "models/gemini-1.5-pro" in models:
            print_success("Modelo gemini-2.0-flash-exp o 1.5-pro disponible")
        else:
            print_warning("Modelo gemini-2.0-flash-exp no encontrado. Modelos disponibles:")
            for model in models[:5]:
                print_info(f"      {model}")

        # Prueba simple
        model = genai.GenerativeModel('gemini-pro')
        response = model.generate_content("Di OK")
        print_info(f"   Respuesta de prueba: {response.text[:50]}")

    except Exception as e:
        print_error(f"Error de Gemini: {str(e)}")
        errors.append(f"Gemini: {str(e)}")


def verificar_mysql():
    """Verifica conexión a MySQL"""
    print_section("5. MYSQL")

    host = os.getenv("MYSQL_HOST")
    database = os.getenv("MYSQL_DATABASE")
    user = os.getenv("MYSQL_USER")
    password = os.getenv("MYSQL_PASSWORD")
    port = os.getenv("MYSQL_PORT", "3306")

    if not all([host, database, user, password]):
        print_error("Faltan variables: MYSQL_HOST, MYSQL_DATABASE, MYSQL_USER, MYSQL_PASSWORD")
        errors.append("MySQL: Credenciales incompletas")
        return

    try:
        import mysql.connector

        print_info(f"Conectando a MySQL ({host}:{port}/{database})...")
        conn = mysql.connector.connect(
            host=host,
            port=int(port),
            database=database,
            user=user,
            password=password
        )

        cursor = conn.cursor()

        # Verificar tabla de suscriptores
        cursor.execute("SHOW TABLES LIKE 'newsletter_subscribers'")
        if cursor.fetchone():
            print_success("Tabla 'newsletter_subscribers' encontrada")

            # Contar suscriptores activos
            cursor.execute("SELECT COUNT(*) FROM newsletter_subscribers WHERE activo = 1")
            count = cursor.fetchone()[0]
            print_info(f"   Suscriptores activos: {count}")

            if count == 0:
                print_warning("No hay suscriptores activos. Ejecuta setup-database.sql")
                warnings.append("MySQL: Sin suscriptores activos")
            elif count < 3:
                print_warning(f"Solo {count} suscriptor(es). Considera añadir más para pruebas.")

            # Verificar columnas requeridas
            cursor.execute("DESCRIBE newsletter_subscribers")
            columns = [row[0] for row in cursor.fetchall()]
            required_cols = ['email', 'nombre', 'grupo_investigador', 'activo']

            missing = [col for col in required_cols if col not in columns]
            if missing:
                print_error(f"Faltan columnas: {', '.join(missing)}")
                errors.append(f"MySQL: Faltan columnas {missing}")
            else:
                print_success("Todas las columnas requeridas presentes")

        else:
            print_error("Tabla 'newsletter_subscribers' NO encontrada")
            print_info("   Ejecuta setup-database.sql para crear la tabla")
            errors.append("MySQL: Tabla newsletter_subscribers no existe")

        conn.close()

    except Exception as e:
        print_error(f"Error de MySQL: {str(e)}")
        errors.append(f"MySQL: {str(e)}")


def verificar_google_sheets():
    """Verifica acceso a Google Sheets"""
    print_section("6. GOOGLE SHEETS")

    # Para OAuth2, la verificación es compleja
    # Aquí verificamos si hay un token guardado o credenciales

    creds_file = os.getenv("GOOGLE_APPLICATION_CREDENTIALS")
    spreadsheet_id = os.getenv("GOOGLE_SHEETS_SPREADSHEET_ID")

    if not creds_file:
        print_warning("Variable GOOGLE_APPLICATION_CREDENTIALS no configurada")
        print_info("   Configura OAuth2 directamente en n8n o usa service account")
        warnings.append("Google Sheets: Credenciales no configuradas (configurar en n8n)")
        return

    if not spreadsheet_id:
        print_warning("Variable GOOGLE_SHEETS_SPREADSHEET_ID no configurada")
        print_info("   Recuerda reemplazar 'INSERTAR_SPREADSHEET_ID_AQUI' en el workflow")
        warnings.append("Google Sheets: Spreadsheet ID no configurado")

    try:
        from google.oauth2 import service_account
        from googleapiclient.discovery import build

        print_info("Verificando credenciales de Google Sheets...")

        SCOPES = ['https://www.googleapis.com/auth/spreadsheets']
        creds = service_account.Credentials.from_service_account_file(
            creds_file, scopes=SCOPES
        )

        service = build('sheets', 'v4', credentials=creds)

        if spreadsheet_id:
            # Intentar leer la hoja
            result = service.spreadsheets().get(
                spreadsheetId=spreadsheet_id
            ).execute()

            print_success(f"Acceso a spreadsheet exitoso: {result.get('properties', {}).get('title')}")

            # Verificar hoja "Logs"
            sheets = [sheet['properties']['title'] for sheet in result.get('sheets', [])]
            if 'Logs' in sheets:
                print_success("Hoja 'Logs' encontrada")
            else:
                print_warning("Hoja 'Logs' no encontrada. Créala manualmente.")
                print_info(f"   Hojas disponibles: {', '.join(sheets)}")
                warnings.append("Google Sheets: Falta hoja 'Logs'")
        else:
            print_success("Credenciales válidas (spreadsheet ID no configurado para testear)")

    except Exception as e:
        print_error(f"Error de Google Sheets: {str(e)}")
        errors.append(f"Google Sheets: {str(e)}")


def verificar_gmail():
    """Verifica acceso a Gmail"""
    print_section("7. GMAIL")

    print_warning("Verificación de Gmail requiere OAuth2 interactivo")
    print_info("   Configura las credenciales directamente en n8n usando OAuth2")
    print_info("   Asegúrate de tener permisos de 'gmail.send'")

    creds_file = os.getenv("GOOGLE_APPLICATION_CREDENTIALS")

    if creds_file:
        print_info("   Service account detectada (no recomendado para Gmail)")
        print_info("   Gmail requiere OAuth2 de usuario, no service account")

    warnings.append("Gmail: Verificación manual requerida en n8n")


def resumen_final():
    """Muestra resumen de la verificación"""
    print_section("RESUMEN DE VERIFICACIÓN")

    total_checks = 7
    total_errors = len(errors)
    total_warnings = len(warnings)

    if total_errors == 0 and total_warnings == 0:
        print_success("🎉 ¡Todas las verificaciones pasaron! Estás listo para ejecutar el workflow.")
    elif total_errors == 0:
        print_warning(f"✅ Sin errores críticos, pero hay {total_warnings} advertencia(s):")
        for w in warnings:
            print(f"   • {w}")
        print_info("\n👉 Puedes ejecutar el workflow, pero revisa las advertencias.")
    else:
        print_error(f"❌ Se encontraron {total_errors} error(es) crítico(s):")
        for e in errors:
            print(f"   • {e}")

        if total_warnings > 0:
            print_warning(f"\n⚠️  También hay {total_warnings} advertencia(s):")
            for w in warnings:
                print(f"   • {w}")

        print_info("\n👉 Corrige los errores antes de ejecutar el workflow.")

    print(f"\n{Colors.BLUE}{'='*60}{Colors.END}\n")


def main():
    print(f"\n{Colors.BLUE}{'='*60}")
    print("  VERIFICADOR DE CREDENCIALES - WORKFLOW NEWSLETTER")
    print(f"  {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print(f"{'='*60}{Colors.END}\n")

    print_info("Este script verificará todas las credenciales necesarias")
    print_info("para el workflow de newsletter mensual automatizado.\n")

    # Ejecutar verificaciones
    verificar_pinecone()
    verificar_anthropic()
    verificar_openai()
    verificar_gemini()
    verificar_mysql()
    verificar_google_sheets()
    verificar_gmail()

    # Mostrar resumen
    resumen_final()

    # Exit code
    sys.exit(1 if len(errors) > 0 else 0)


if __name__ == "__main__":
    main()


# ==============================================================
# EJEMPLO DE ARCHIVO .env
# ==============================================================
#
# Crea un archivo .env en este mismo directorio con:
#
# # Pinecone
# PINECONE_API_KEY=your-pinecone-api-key
# PINECONE_ENVIRONMENT=us-east-1-aws
#
# # Anthropic
# ANTHROPIC_API_KEY=sk-ant-api03-xxxxx
#
# # OpenAI (para embeddings)
# OPENAI_API_KEY=sk-xxxxx
#
# # Google Gemini
# GEMINI_API_KEY=xxxxx
#
# # MySQL
# MYSQL_HOST=mysql.dreamhost.com
# MYSQL_PORT=3306
# MYSQL_DATABASE=nombre_base_datos
# MYSQL_USER=usuario
# MYSQL_PASSWORD=password
#
# # Google Sheets (Service Account)
# GOOGLE_APPLICATION_CREDENTIALS=/path/to/service-account.json
# GOOGLE_SHEETS_SPREADSHEET_ID=1abc123def456...
#
# ==============================================================
