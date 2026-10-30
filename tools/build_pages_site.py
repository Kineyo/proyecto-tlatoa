#!/usr/bin/env python3
"""
Compilador del sitio estático para GitHub Pages (Proyecto Tlatoa)
Genera el índice de búsqueda search_index.json y sincroniza assets en pages/
"""

import sqlite3
import json
import shutil
from pathlib import Path

ROOT_DIR = Path(__file__).resolve().parent.parent
PAGES_DIR = ROOT_DIR / "pages"
DATA_DIR = PAGES_DIR / "data"
ASSETS_DIR = PAGES_DIR / "assets"
DB_PATH = ROOT_DIR / "storage" / "tlatoa.db"

def build():
    print("🚀 Compilando sitio estático para GitHub Pages...")
    DATA_DIR.mkdir(parents=True, exist_ok=True)
    ASSETS_DIR.mkdir(parents=True, exist_ok=True)

    # 1. Copiar assets esenciales
    src_assets = ROOT_DIR / "assets"
    for filename in ["banner.png", "tecolote.png", "favicon.svg", "favicon.ico"]:
        src_file = src_assets / filename
        if src_file.exists():
            shutil.copy2(src_file, ASSETS_DIR / filename)
            print(f"  [+] Copiado asset: {filename}")

    # Copiar también tecolote.svg si existe en img
    src_svg = src_assets / "img" / "tecolote.svg"
    if src_svg.exists():
        (ASSETS_DIR / "img").mkdir(exist_ok=True)
        shutil.copy2(src_svg, ASSETS_DIR / "img" / "tecolote.svg")

    # 2. Extraer datos compactos de SQLite
    if not DB_PATH.exists():
        raise FileNotFoundError(f"No se encontró la base de datos en {DB_PATH}")

    conn = sqlite3.connect(DB_PATH)
    cursor = conn.cursor()
    cursor.execute("""
        SELECT slug, nombre_completo, partido, partido_color, cargo, camara, entidad, es_activo, foto_url, datos_json 
        FROM politicos
        ORDER BY es_activo DESC, nombre_completo ASC
    """)

    items = []
    for row in cursor.fetchall():
        slug, nombre, partido, color, cargo, camara, entidad, es_activo, foto, dj_str = row
        dj = json.loads(dj_str) if dj_str else {}
        
        fuente = dj.get("fuente_url") or (dj.get("metadata") or {}).get("fuente_url")
        if not fuente and dj.get("referencia_sil"):
            fuente = f"http://sil.gobernacion.gob.mx/Librerias/pp_PerfilLegislador.php?Referencia={dj['referencia_sil']}"

        # Clasificación simplificada para filtros rápidos
        c_lower = (cargo or "").lower()
        if "senador" in c_lower:
            categoria = "senador"
        elif "diputado(a) federal" in c_lower or "diputados" in (camara or "").lower():
            categoria = "diputado_federal"
        elif "gobernador" in c_lower or "jefe de gobierno" in c_lower or "regente" in c_lower:
            categoria = "gobernador"
        elif "municipal" in c_lower or "alcalde" in c_lower or "alcaldesa" in c_lower:
            categoria = "alcalde"
        elif "local" in c_lower:
            categoria = "diputado_local"
        else:
            categoria = "otros"

        items.append({
            "s": slug,
            "n": nombre,
            "p": partido or "Ind.",
            "c": color or "#4B5563",
            "r": cargo or "Servidor Público",
            "e": entidad or "México",
            "k": categoria,
            "a": es_activo or 0,
            "f": foto or "",
            "u": fuente or "https://www.gob.mx"
        })

    conn.close()

    output_path = DATA_DIR / "search_index.json"
    with open(output_path, "w", encoding="utf-8") as f:
        json.dump(items, f, ensure_ascii=False, separators=(",", ":"))

    size_mb = output_path.stat().st_size / (1024 * 1024)
    print(f"✅ Catálogo generado con éxito: {len(items):,} registros ({size_mb:.2f} MB)")
    print(f"📁 Salida: {output_path}")

if __name__ == "__main__":
    build()
