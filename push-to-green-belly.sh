#!/bin/bash

# Script para push al repositorio green-belly/claude
# Ejecuta este script desde tu terminal local

echo "🚀 Push de WP Subscribers Manager v1.4.0 a green-belly/claude"
echo "================================================================"
echo ""

# Verificar que estamos en el directorio correcto
if [ ! -d ".git" ]; then
    echo "❌ Error: Este script debe ejecutarse desde el directorio raíz del repositorio"
    exit 1
fi

# Agregar remote de green-belly si no existe
if ! git remote | grep -q "^green-belly$"; then
    echo "📌 Agregando remote green-belly..."
    git remote add green-belly https://github.com/green-belly/claude.git
else
    echo "✓ Remote green-belly ya existe"
fi

# Mostrar remotes configurados
echo ""
echo "📋 Remotes configurados:"
git remote -v | grep green-belly
echo ""

# Verificar branch actual
current_branch=$(git branch --show-current)
echo "📍 Branch actual: $current_branch"
echo ""

# Si no estamos en la branch correcta, cambiar a ella
if [ "$current_branch" != "claude/wp-subscribers-manager-v1.4.0-C29CC" ]; then
    echo "🔄 Cambiando a branch claude/wp-subscribers-manager-v1.4.0-C29CC..."
    git checkout claude/wp-subscribers-manager-v1.4.0-C29CC
    if [ $? -ne 0 ]; then
        echo "❌ Error al cambiar de branch"
        exit 1
    fi
fi

# Mostrar últimos commits
echo ""
echo "📝 Últimos commits en esta branch:"
git log --oneline -5
echo ""

# Preguntar confirmación
read -p "¿Deseas hacer push de esta branch a green-belly/claude? (s/n): " confirm

if [ "$confirm" != "s" ] && [ "$confirm" != "S" ]; then
    echo "❌ Operación cancelada"
    exit 0
fi

# Hacer push
echo ""
echo "⬆️  Haciendo push a green-belly/claude..."
git push -u green-belly claude/wp-subscribers-manager-v1.4.0-C29CC

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ ¡Push completado exitosamente!"
    echo ""
    echo "🔗 Tu repositorio: https://github.com/green-belly/claude"
    echo "🌿 Branch: claude/wp-subscribers-manager-v1.4.0-C29CC"
    echo ""
    echo "📦 Contenido del push:"
    echo "   - WP Subscribers Manager v1.4.0"
    echo "   - Sistema de notificaciones SMTP"
    echo "   - Emails HTML profesionales"
    echo "   - Lista de suscriptores simplificada"
    echo "   - Todos los archivos con cambios del linter"
    echo "   - ZIP listo para distribución (127 KB)"
    echo ""
else
    echo ""
    echo "❌ Error al hacer push. Verifica tus credenciales de GitHub."
    echo ""
    echo "💡 Si tienes autenticación de 2 factores activada:"
    echo "   1. Ve a GitHub Settings > Developer settings > Personal access tokens"
    echo "   2. Genera un nuevo token con permisos 'repo'"
    echo "   3. Usa el token como contraseña cuando Git te lo pida"
    exit 1
fi
