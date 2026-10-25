#!/usr/bin/env bash
# ========================================================================
# PROYECTO TLATOA (FORK SOBERANO) — LANZADOR OFFLINE DIRECTO
# Cero Python. Cero PHP. Cero dependencias externas.
# Haz doble clic en este archivo para iniciar el observatorio cívico.
# ========================================================================

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null 2>&1 && pwd)"
cd "$DIR"

if [ -f "./tlatoa-desktop" ]; then
    chmod +x ./tlatoa-desktop
    ./tlatoa-desktop
else
    echo "❌ No se encontró el binario ./tlatoa-desktop."
    echo "Compílalo con: cargo build --release -p tlatoa-desktop"
    read -p "Presiona Enter para salir..."
fi
