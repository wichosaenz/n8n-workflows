#!/usr/bin/env python3
"""
Script de ejemplo para subir artículos a Pinecone
Úsalo para poblar tu índice 'everest-bothers-01' con datos de prueba

Requisitos:
    pip install pinecone-client openai python-dotenv

Uso:
    1. Crea un archivo .env con:
       PINECONE_API_KEY=tu-api-key
       PINECONE_ENVIRONMENT=tu-environment
       OPENAI_API_KEY=tu-openai-key (para embeddings)

    2. Ejecuta: python pinecone-upload-example.py
"""

import os
from datetime import datetime, timedelta
from dotenv import load_dotenv
import pinecone
from openai import OpenAI

# Cargar variables de entorno
load_dotenv()

# Configuración
PINECONE_API_KEY = os.getenv("PINECONE_API_KEY")
PINECONE_ENV = os.getenv("PINECONE_ENVIRONMENT")
OPENAI_API_KEY = os.getenv("OPENAI_API_KEY")
INDEX_NAME = "everest-bothers-01"

# Datos de ejemplo
ARTICULOS_EJEMPLO = [
    {
        "id": "art-2025-10-001",
        "titulo": "Tendencias en la Distribución Minorista Post-Pandemia en América Latina",
        "autor": "Dr. María González",
        "grupo_investigador": "Distribución Minorista",
        "fecha_publicacion": "2025-10-15",
        "contenido": """
        El sector minorista en América Latina ha experimentado transformaciones significativas desde 2020.
        Este estudio analiza 500 establecimientos en México, Colombia y Chile, identificando tres tendencias
        principales: (1) digitalización acelerada del punto de venta, (2) implementación de sistemas omnicanal,
        y (3) optimización de la cadena de suministro mediante IA.

        Los resultados muestran que el 68% de los minoristas medianos han adoptado al menos una tecnología
        digital en los últimos 18 meses, con un incremento promedio del 23% en eficiencia operativa.
        El análisis de 1,200 transacciones diarias revela que los sistemas omnicanal generan un 34% más
        de valor por cliente comparado con canales únicos.

        Las principales barreras identificadas incluyen: falta de capacitación técnica (45% de encuestados),
        resistencia al cambio organizacional (38%), y limitaciones de capital (32%). Sin embargo, el ROI
        promedio de estas inversiones alcanza el 180% en un periodo de 24 meses.
        """,
        "keywords": ["retail", "distribución", "omnicanalidad", "e-commerce", "América Latina"],
        "idioma": "es"
    },
    {
        "id": "art-2025-10-002",
        "titulo": "Electric Vehicle Adoption in Latin American Transport Industry",
        "autor": "Dr. John Smith",
        "grupo_investigador": "Industria de Transporte",
        "fecha_publicacion": "2025-10-20",
        "contenido": """
        This research examines the adoption rate of electric vehicles (EVs) in the commercial transport
        sector across five Latin American countries: Mexico, Brazil, Chile, Colombia, and Argentina.

        Using data from 1,200 transport companies collected over 18 months, we identify key barriers
        including: limited charging infrastructure (cited by 78% of respondents), high initial capital
        costs (65%), and range anxiety for long-haul routes (52%).

        However, early adopters report significant benefits: 40% reduction in operational costs over
        3 years, 65% decrease in maintenance expenses, and improved brand perception among eco-conscious
        clients. Government incentives in Chile and Mexico show promising results with 15% YoY growth
        in EV fleet adoption.

        The study also projects that by 2030, EVs will represent 28% of commercial fleets in urban
        areas, driven by regulatory pressure and total cost of ownership advantages.
        """,
        "keywords": ["electric vehicles", "transport", "logistics", "sustainability", "Latin America"],
        "idioma": "en"
    },
    {
        "id": "art-2025-10-003",
        "titulo": "Inversión China en Infraestructura Mexicana: Análisis 2020-2025",
        "autor": "Dr. Carlos Hernández",
        "grupo_investigador": "Inversiones Chinas en México",
        "fecha_publicacion": "2025-10-22",
        "contenido": """
        Durante el quinquenio 2020-2025, la inversión china en proyectos de infraestructura en México
        ha alcanzado los $12.3 mil millones USD, concentrándose principalmente en tres sectores:
        energía renovable (45%), transporte ferroviario (30%), y telecomunicaciones (25%).

        Este análisis examina 47 proyectos activos, identificando patrones de inversión, impacto
        económico regional, y consideraciones geopolíticas. Los estados del norte de México han
        capturado el 62% de estas inversiones, correlacionado con la proximidad al mercado
        estadounidense y la existencia de corredores industriales establecidos.

        Los proyectos de energía solar en Sonora y Chihuahua representan la mayor concentración
        de capital chino ($5.4B USD), seguidos por el desarrollo del tren interurbano Guadalajara-León
        ($2.1B USD). El análisis de riesgo identifica dependencias críticas en cadenas de suministro
        y consideraciones de seguridad nacional que requieren marcos regulatorios actualizados.
        """,
        "keywords": ["China", "inversión extranjera", "infraestructura", "México", "geopolítica"],
        "idioma": "es"
    },
    {
        "id": "art-2025-10-004",
        "titulo": "Supply Chain Resilience in the Mexican Auto Parts Industry",
        "autor": "Dr. Sarah Johnson",
        "grupo_investigador": "Sector Autopartes",
        "fecha_publicacion": "2025-10-25",
        "contenido": """
        The Mexican auto parts industry, valued at $115 billion annually, faced unprecedented
        disruptions during 2020-2023. This study surveys 320 tier-1 and tier-2 suppliers to
        assess supply chain resilience strategies implemented post-pandemic.

        Key findings include: 73% have diversified their supplier base geographically (up from
        31% in 2019), 58% maintain strategic inventory reserves averaging 45 days of production
        (up from 23% maintaining 15 days in 2019), and 45% have implemented predictive analytics
        for demand forecasting.

        The research also examines the impact of nearshoring trends, with 34% of respondents
        planning capacity expansion to serve relocated OEM production. Investment in Mexico's
        auto parts sector reached $8.2B in 2024, with particular growth in Nuevo León, Guanajuato,
        and San Luis Potosí.

        Emerging challenges include skilled labor shortages (67% cite this as critical) and
        the need to integrate sustainable practices while maintaining cost competitiveness.
        """,
        "keywords": ["automotive", "supply chain", "resilience", "nearshoring", "Mexico"],
        "idioma": "en"
    },
    {
        "id": "art-2025-10-005",
        "titulo": "Impacto de la IA Generativa en el Retail: Casos de Uso en México",
        "autor": "Dr. María González",
        "grupo_investigador": "Distribución Minorista",
        "fecha_publicacion": "2025-10-28",
        "contenido": """
        La inteligencia artificial generativa está revolucionando la experiencia del cliente en
        el sector retail mexicano. Este estudio de caso múltiple analiza 25 implementaciones
        exitosas de IA generativa en grandes cadenas minoristas durante 2024-2025.

        Se identifican cuatro aplicaciones principales: (1) personalización de recomendaciones
        de producto basada en comportamiento histórico y contexto conversacional (84% de adopción),
        (2) generación automática de contenido para marketing y redes sociales (67%),
        (3) chatbots conversacionales para atención al cliente 24/7 (92%), y (4) optimización
        de inventario con predicción de demanda mediante modelos generativos (45%).

        Los resultados muestran un incremento promedio del 31% en tasa de conversión, una
        reducción del 24% en costos de atención al cliente, y un aumento del 18% en el ticket
        promedio. Sin embargo, se identifican desafíos críticos en privacidad de datos,
        sesgo algorítmico, y la necesidad de mantener el toque humano en interacciones complejas.

        Las empresas más exitosas implementan un modelo híbrido que combina IA generativa para
        tareas rutinarias con escalación a humanos para casos complejos, logrando un balance
        óptimo entre eficiencia y satisfacción del cliente.
        """,
        "keywords": ["IA generativa", "retail", "personalización", "customer experience", "México"],
        "idioma": "es"
    }
]


def generar_embedding(texto: str, client: OpenAI) -> list[float]:
    """
    Genera un embedding usando OpenAI text-embedding-3-small
    """
    response = client.embeddings.create(
        model="text-embedding-3-small",
        input=texto,
        encoding_format="float"
    )
    return response.data[0].embedding


def main():
    print("🚀 Iniciando carga de datos a Pinecone...")

    # Validar variables de entorno
    if not all([PINECONE_API_KEY, PINECONE_ENV, OPENAI_API_KEY]):
        print("❌ Error: Faltan variables de entorno")
        print("   Crea un archivo .env con:")
        print("   PINECONE_API_KEY=tu-api-key")
        print("   PINECONE_ENVIRONMENT=tu-environment")
        print("   OPENAI_API_KEY=tu-openai-key")
        return

    # Inicializar Pinecone
    print(f"📌 Conectando a Pinecone (environment: {PINECONE_ENV})...")
    pinecone.init(api_key=PINECONE_API_KEY, environment=PINECONE_ENV)

    # Verificar/Crear índice
    if INDEX_NAME not in pinecone.list_indexes():
        print(f"📝 Creando índice '{INDEX_NAME}'...")
        pinecone.create_index(
            name=INDEX_NAME,
            dimension=1536,  # Dimensión de text-embedding-3-small
            metric="cosine"
        )
        print("✅ Índice creado")
    else:
        print(f"✅ Índice '{INDEX_NAME}' ya existe")

    # Conectar al índice
    index = pinecone.Index(INDEX_NAME)
    print(f"📊 Estadísticas del índice: {index.describe_index_stats()}")

    # Inicializar OpenAI
    client = OpenAI(api_key=OPENAI_API_KEY)

    # Procesar y subir artículos
    vectores = []
    print(f"\n🔄 Generando embeddings para {len(ARTICULOS_EJEMPLO)} artículos...")

    for i, articulo in enumerate(ARTICULOS_EJEMPLO, 1):
        print(f"   [{i}/{len(ARTICULOS_EJEMPLO)}] {articulo['titulo'][:60]}...")

        # Generar embedding del contenido
        embedding = generar_embedding(articulo["contenido"], client)

        # Preparar metadata (sin el contenido en sí para ahorrar espacio)
        metadata = {
            "titulo": articulo["titulo"],
            "autor": articulo["autor"],
            "grupo_investigador": articulo["grupo_investigador"],
            "fecha_publicacion": articulo["fecha_publicacion"],
            "contenido": articulo["contenido"].strip(),  # Incluir para RAG
            "keywords": ", ".join(articulo.get("keywords", [])),
            "idioma": articulo["idioma"]
        }

        vectores.append({
            "id": articulo["id"],
            "values": embedding,
            "metadata": metadata
        })

    # Upsert en Pinecone
    print(f"\n📤 Subiendo {len(vectores)} vectores a Pinecone...")
    index.upsert(vectors=vectores)

    print(f"✅ ¡Carga completada!\n")
    print(f"📊 Nuevas estadísticas del índice:")
    print(index.describe_index_stats())

    # Prueba de query
    print("\n🔍 Prueba de búsqueda:")
    test_query = "¿Cuáles son las tendencias en retail?"
    print(f"   Query: '{test_query}'")

    query_embedding = generar_embedding(test_query, client)
    resultados = index.query(
        vector=query_embedding,
        top_k=3,
        include_metadata=True
    )

    print(f"\n   Top 3 resultados más relevantes:")
    for i, match in enumerate(resultados.matches, 1):
        print(f"   {i}. [{match.score:.3f}] {match.metadata['titulo']}")
        print(f"      Autor: {match.metadata['autor']} | Grupo: {match.metadata['grupo_investigador']}")

    print("\n✨ ¡Todo listo! Ahora puedes ejecutar tu workflow de n8n.")


def cargar_desde_csv(csv_path: str):
    """
    Función alternativa para cargar artículos desde un CSV

    El CSV debe tener las columnas:
    id,titulo,autor,grupo_investigador,fecha_publicacion,contenido,keywords,idioma
    """
    import csv

    articulos = []
    with open(csv_path, 'r', encoding='utf-8') as f:
        reader = csv.DictReader(f)
        for row in reader:
            articulos.append({
                "id": row["id"],
                "titulo": row["titulo"],
                "autor": row["autor"],
                "grupo_investigador": row["grupo_investigador"],
                "fecha_publicacion": row["fecha_publicacion"],
                "contenido": row["contenido"],
                "keywords": row.get("keywords", "").split(","),
                "idioma": row.get("idioma", "es")
            })

    return articulos


if __name__ == "__main__":
    main()


# ==============================================================
# NOTAS ADICIONALES
# ==============================================================
#
# 1. ACTUALIZACIÓN MENSUAL:
#    Ejecuta este script mensualmente para añadir los nuevos
#    artículos del mes. Los IDs deben ser únicos.
#
# 2. METADATOS PERSONALIZADOS:
#    Añade campos adicionales según tus necesidades:
#    - "doi": DOI del paper
#    - "url": URL del artículo completo
#    - "tags": Tags adicionales
#    - "revista": Nombre de la publicación
#
# 3. FILTRADO POR FECHA:
#    El workflow de n8n filtra por fecha_publicacion del mes
#    anterior. Asegúrate de usar formato YYYY-MM-DD.
#
# 4. EMBEDDINGS ALTERNATIVOS:
#    Si no usas OpenAI, puedes usar:
#    - Voyage AI: voyage-large-2-instruct
#    - Cohere: embed-multilingual-v3.0
#    - Anthropic: No tienen embeddings nativos (usa OpenAI/Voyage)
#
# 5. LÍMITES DE PINECONE:
#    - Free tier: 1M vectores, 1 índice
#    - Metadata max: 40KB por vector
#    - Si tus artículos son muy largos, usa solo resúmenes
#
# ==============================================================
