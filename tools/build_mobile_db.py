#!/usr/bin/env python3
"""
tools/build_mobile_db.py — Compilador de Base de Datos SQLite Ultracompacta para Móvil (Tlatoa)
Convierte los 3,200+ datasets JSON en un archivo SQLite 'storage/tlatoa.db' optimizado con FTS5.
"""

import sys
import json
import sqlite3
import unicodedata
import re
from pathlib import Path

BASE_DIR = Path(__file__).resolve().parent.parent
DATA_DIR = BASE_DIR / "storage" / "data" / "json"
OUTPUT_DB = BASE_DIR / "storage" / "tlatoa.db"

def slugify(text: str) -> str:
    if not text:
        return ""
    text = unicodedata.normalize("NFKD", str(text)).encode("ascii", "ignore").decode("utf-8")
    text = re.sub(r"[^\w\s-]", "", text.lower()).strip()
    return re.sub(r"[-\s]+", "-", text)

def get_party_color(partido: str) -> str:
    if not partido:
        return "#6C757D"
    p = partido.upper()
    if "MORENA" in p: return "#8B1C3F"
    if "PAN" in p and "PANAL" not in p: return "#003893"
    if "VERDE" in p or "PVEM" in p: return "#009E49"
    if "PRI" in p: return "#C8102E"
    if "PT" in p or "TRABAJO" in p: return "#DA291C"
    if "MC" in p or "CIUDADANO" in p: return "#FF8300"
    if "PRD" in p: return "#FFD100"
    return "#6C757D"

def init_database(conn: sqlite3.Connection):
    cursor = conn.cursor()
    cursor.execute("PRAGMA journal_mode = OFF;")
    cursor.execute("PRAGMA synchronous = 0;")
    cursor.execute("PRAGMA page_size = 4096;")

    # Tabla maestra relacional
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS politicos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            slug TEXT UNIQUE NOT NULL,
            nombre_completo TEXT NOT NULL,
            partido TEXT NOT NULL,
            partido_color TEXT NOT NULL,
            cargo TEXT NOT NULL,
            camara TEXT NOT NULL,
            entidad TEXT NOT NULL,
            es_activo INTEGER NOT NULL DEFAULT 1,
            foto_url TEXT,
            datos_json TEXT NOT NULL
        );
    """)

    # Tabla de búsqueda FTS5 de texto completo con remoción de acentos
    cursor.execute("""
        CREATE VIRTUAL TABLE IF NOT EXISTS politicos_fts USING fts5(
            slug UNINDEXED,
            nombre,
            partido,
            cargo,
            entidad,
            tokenize = 'unicode61 remove_diacritics 2'
        );
    """)
    conn.commit()

def build_database():
    print(f">>> Generando base de datos SQLite móvil en: {OUTPUT_DB}")
    if OUTPUT_DB.exists():
        OUTPUT_DB.unlink()

    conn = sqlite3.connect(str(OUTPUT_DB))
    init_database(conn)
    cursor = conn.cursor()

    politicos_vistos = set()
    total_insertados = 0

    # 1. Procesar Congreso Federal (Diputados y Senadores LXVI)
    fed_file = DATA_DIR / "congreso_union_leg_66.json"
    if fed_file.exists():
        with open(fed_file, encoding="utf-8") as f:
            data = json.load(f)
            for camara, lista in [("Diputados", data.get("diputados", [])), ("Senadores", data.get("senadores", []))]:
                for p in lista:
                    nombre = p.get("nombre_completo", "").strip()
                    if not nombre:
                        continue
                    slug = p.get("slug") or slugify(nombre)
                    if slug in politicos_vistos:
                        continue
                    politicos_vistos.add(slug)

                    partido = p.get("partido") or "Sin Partido"
                    color = get_party_color(partido)
                    entidad = p.get("entidad") or "Nacional"
                    cargo = "Senador de la República" if camara == "Senadores" else "Diputado Federal"
                    foto = p.get("foto_oficial_url") or ""
                    
                    cursor.execute("""
                        INSERT INTO politicos (slug, nombre_completo, partido, partido_color, cargo, camara, entidad, es_activo, foto_url, datos_json)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?)
                    """, (slug, nombre, partido, color, cargo, camara, entidad, foto, json.dumps(p, ensure_ascii=False)))

                    cursor.execute("""
                        INSERT INTO politicos_fts (slug, nombre, partido, cargo, entidad)
                        VALUES (?, ?, ?, ?, ?)
                    """, (slug, nombre, partido, cargo, entidad))
                    total_insertados += 1

    # 2. Procesar Gobernadores de las 32 Entidades
    dir_ejecutivo = DATA_DIR / "ejecutivo"
    if dir_ejecutivo.exists():
        for gob_file in dir_ejecutivo.glob("gobernadores_*.json"):
            try:
                with open(gob_file, encoding="utf-8") as f:
                    data = json.load(f)
                    entidad = data.get("entidad", "")
                    mandatarios = data.get("mandatarios", [])
                    if mandatarios:
                        actual = mandatarios[-1] # Mandatario en funciones
                        nombre = actual.get("nombre", "").strip()
                        if nombre:
                            slug = actual.get("slug") or slugify(nombre)
                            if slug not in politicos_vistos:
                                politicos_vistos.add(slug)
                                partido = actual.get("partido") or "Sin Partido"
                                color = actual.get("partido_color") or get_party_color(partido)
                                cargo = f"Gobernador(a) de {entidad}"
                                foto = actual.get("foto_url") or ""

                                cursor.execute("""
                                    INSERT INTO politicos (slug, nombre_completo, partido, partido_color, cargo, camara, entidad, es_activo, foto_url, datos_json)
                                    VALUES (?, ?, ?, ?, ?, 'Ejecutivo Estatal', ?, 1, ?, ?)
                                """, (slug, nombre, partido, color, cargo, entidad, foto, json.dumps(actual, ensure_ascii=False)))

                                cursor.execute("""
                                    INSERT INTO politicos_fts (slug, nombre, partido, cargo, entidad)
                                    VALUES (?, ?, ?, ?, ?)
                                """, (slug, nombre, partido, cargo, entidad))
                                total_insertados += 1
            except Exception:
                continue

    # 3. Procesar Presidentes Municipales / Alcaldes
    dir_alcaldes = DATA_DIR / "alcaldes"
    if dir_alcaldes.exists():
        for alc_file in dir_alcaldes.glob("*.json"):
            try:
                with open(alc_file, encoding="utf-8") as f:
                    data = json.load(f)
                    items = data if isinstance(data, list) else data.get("alcaldes", [])
                    for a in items:
                        nombre = a.get("nombre", "") or a.get("alcalde", "")
                        nombre = nombre.strip()
                        if not nombre or len(nombre) < 3:
                            continue
                        slug = a.get("slug") or slugify(f"{nombre}-{a.get('municipio', '')}")
                        if slug in politicos_vistos:
                            continue
                        politicos_vistos.add(slug)

                        partido = a.get("partido") or "Sin Partido"
                        color = get_party_color(partido)
                        entidad = a.get("entidad") or ""
                        mun = a.get("municipio") or ""
                        cargo = f"Presidente(a) Municipal ({mun})" if mun else "Presidente(a) Municipal"

                        cursor.execute("""
                            INSERT INTO politicos (slug, nombre_completo, partido, partido_color, cargo, camara, entidad, es_activo, foto_url, datos_json)
                            VALUES (?, ?, ?, ?, ?, 'Ayuntamiento', ?, 1, '', ?)
                        """, (slug, nombre, partido, color, cargo, entidad, json.dumps(a, ensure_ascii=False)))

                        cursor.execute("""
                            INSERT INTO politicos_fts (slug, nombre, partido, cargo, entidad)
                            VALUES (?, ?, ?, ?, ?)
                        """, (slug, nombre, partido, cargo, entidad))
                        total_insertados += 1
            except Exception:
                continue

    # 4. Procesar Congresos Locales (Diputados Estatales activos)
    for c_file in DATA_DIR.glob("congreso_*_leg_*.json"):
        try:
            with open(c_file, encoding="utf-8") as f:
                c_data = json.load(f)
                dips = c_data.get("diputados", []) if isinstance(c_data, dict) else (c_data if isinstance(c_data, list) else [])
                for d in dips:
                    nombre = d.get("nombre_completo", "") or d.get("nombre", "")
                    nombre = nombre.strip()
                    if not nombre:
                        continue
                    slug = d.get("slug") or slugify(nombre)
                    if slug in politicos_vistos:
                        continue
                    politicos_vistos.add(slug)

                    partido = d.get("partido") or "Sin Partido"
                    color = get_party_color(partido)
                    entidad = d.get("entidad") or c_data.get("metadata", {}).get("entidad", "Congreso Local")
                    cargo = "Diputado(a) Local"
                    foto = d.get("foto_oficial_url") or ""

                    cursor.execute("""
                        INSERT INTO politicos (slug, nombre_completo, partido, partido_color, cargo, camara, entidad, es_activo, foto_url, datos_json)
                        VALUES (?, ?, ?, ?, ?, 'Congreso Local', ?, 1, ?, ?)
                    """, (slug, nombre, partido, color, cargo, entidad, foto, json.dumps(d, ensure_ascii=False)))

                    cursor.execute("""
                        INSERT INTO politicos_fts (slug, nombre, partido, cargo, entidad)
                        VALUES (?, ?, ?, ?, ?)
                    """, (slug, nombre, partido, cargo, entidad))
                    total_insertados += 1
        except Exception:
            continue

    conn.commit()
    cursor.execute("VACUUM;")
    cursor.execute("ANALYZE;")
    conn.commit()
    conn.close()

    size_mb = OUTPUT_DB.stat().st_size / (1024 * 1024)
    print(f"✅ Base de datos SQLite compilada con éxito!")
    print(f"   - Total de políticos indexados: {total_insertados}")
    print(f"   - Tamaño final del archivo SQLite: {size_mb:.2f} MB")

if __name__ == "__main__":
    build_database()
