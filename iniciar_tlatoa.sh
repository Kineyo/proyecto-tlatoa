#!/usr/bin/env bash
# ========================================================================
# PROYECTO TLATOA (FORK SOBERANO) — LANZADOR OFFLINE DIRECTO
# Cero Python. Cero PHP. Cero dependencias externas.
# Haz doble clic en este archivo para iniciar el observatorio cívico.
# ========================================================================

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null 2>&1 && pwd)"
cd "$DIR"

if [ -f "./bin/tlatoa-desktop" ]; then
    chmod +x ./bin/tlatoa-desktop
    ./bin/tlatoa-desktop
elif [ -f "./tlatoa-desktop" ]; then
    chmod +x ./tlatoa-desktop
    ./tlatoa-desktop
else
    echo "❌ No se encontró el binario ./bin/tlatoa-desktop."
    echo "Compílalo con: cargo build --release -p tlatoa-desktop && cp crates/tlatoa-desktop/target/release/tlatoa-desktop bin/"
    read -p "Presiona Enter para salir..."
fi
