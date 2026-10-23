#!/usr/bin/env python3
"""
tools/build_mobile_db.py — Compilador de Base de Datos SQLite Ultracompacta (Proyecto Tlatoa)
Convierte los datasets JSON en un archivo SQLite 'storage/tlatoa.db' optimizado con FTS5.
"""

import json
import re
import sqlite3
import sys
import unicodedata
from pathlib import Path

if hasattr(sys.stdout, "reconfigure"):
    try:
        sys.stdout.reconfigure(encoding="utf-8", errors="replace")
        sys.stderr.reconfigure(encoding="utf-8", errors="replace")
    except Exception:
        pass

try:
    import orjson

    def load_json_file(path: Path):
        return orjson.loads(path.read_bytes())
except ImportError:
    def load_json_file(path: Path):
        with open(path, encoding="utf-8", errors="replace") as f:
            return json.load(f)

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
    if "MORENA" in p:
        return "#8B1C3F"
    if "PAN" in p and "PANAL" not in p:
        return "#003893"
    if "VERDE" in p or "PVEM" in p:
        return "#009E49"
    if "PRI" in p:
        return "#C8102E"
    if "PT" in p or "TRABAJO" in p:
        return "#DA291C"
    if "MC" in p or "CIUDADANO" in p or "CONVERGENCIA" in p:
        return "#FF8300"
    if "PES" in p:
        return "#582C83"
    if "PRD" in p:
        return "#FFD100"
    if "PANAL" in p or "NUEVA ALIANZA" in p:
        return "#00A896"
    return "#6C757D"


def extract_leg_num(path: Path) -> int:
    m = re.search(r"_leg_(\d+)\.json$", path.name)
    return int(m.group(1)) if m else 0


def init_database(conn: sqlite3.Connection):
    cursor = conn.cursor()
    cursor.execute("PRAGMA journal_mode = OFF;")
    cursor.execute("PRAGMA synchronous = 0;")
    cursor.execute("PRAGMA page_size = 4096;")

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

    cursor.execute("CREATE INDEX IF NOT EXISTS idx_politicos_nombre ON politicos(nombre_completo);")
    cursor.execute("CREATE INDEX IF NOT EXISTS idx_politicos_entidad ON politicos(entidad);")
    cursor.execute("CREATE INDEX IF NOT EXISTS idx_politicos_partido ON politicos(partido);")

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


def insert_record(cursor, politicos_vistos, slug, nombre, partido, color, cargo, camara, entidad, es_activo, foto, raw_dict) -> bool:
    if not nombre or not slug or slug in politicos_vistos:
        return False
    politicos_vistos.add(slug)
    json_str = json.dumps(raw_dict, ensure_ascii=False)
    cursor.execute("""
        INSERT INTO politicos (slug, nombre_completo, partido, partido_color, cargo, camara, entidad, es_activo, foto_url, datos_json)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    """, (slug, nombre, partido, color, cargo, camara, entidad, es_activo, foto or "", json_str))

    cursor.execute("""
        INSERT INTO politicos_fts (slug, nombre, partido, cargo, entidad)
        VALUES (?, ?, ?, ?, ?)
    """, (slug, nombre, partido, cargo, entidad))
    return True


def build_database():
    OUTPUT_DB.parent.mkdir(parents=True, exist_ok=True)
    tmp_db = OUTPUT_DB.with_suffix(".db.tmp")
    if tmp_db.exists():
        tmp_db.unlink()

    print(f">>> Generando base de datos SQLite en: {OUTPUT_DB}")
    conn = sqlite3.connect(str(tmp_db))
    init_database(conn)
    cursor = conn.cursor()

    politicos_vistos = set()
    counts = {"federales": 0, "ejecutivo": 0, "alcaldes": 0, "locales": 0}

    # 1. Procesar Congreso Federal (Diputados y Senadores, priorizando LXVI más reciente)
    fed_files = sorted(DATA_DIR.glob("congreso_union_leg_*.json"), key=extract_leg_num, reverse=True)
    for fed_file in fed_files:
        try:
            data = load_json_file(fed_file)
            leg_num = extract_leg_num(fed_file)
            es_activo = 1 if leg_num >= 66 else 0
            for camara, lista in [("Diputados", data.get("diputados", [])), ("Senadores", data.get("senadores", []))]:
                for p in lista:
                    if not isinstance(p, dict):
                        continue
                    nombre = (p.get("nombre_completo") or p.get("nombre") or "").strip()
                    slug = p.get("slug") or slugify(nombre)
                    partido = p.get("partido") or "Sin Partido"
                    color = get_party_color(partido)
                    entidad = p.get("entidad") or "Nacional"
                    cargo = "Senador(a) de la República" if camara == "Senadores" else "Diputado(a) Federal"
                    foto = p.get("foto_oficial_url") or p.get("foto_url") or ""
                    if insert_record(cursor, politicos_vistos, slug, nombre, partido, color, cargo, camara, entidad, es_activo, foto, p):
                        counts["federales"] += 1
        except Exception as e:
            print(f"  [Aviso] Omitiendo {fed_file.name}: {e}")

    # 2. Procesar Gobernadores y Jefes de Gobierno (32 Entidades)
    dir_ejecutivo = DATA_DIR / "ejecutivo"
    if dir_ejecutivo.exists():
        ejec_files = sorted(dir_ejecutivo.glob("*.json"))
        for gob_file in ejec_files:
            try:
                data = load_json_file(gob_file)
                if not isinstance(data, dict):
                    continue
                entidad = data.get("entidad") or "Nacional"
                mandatarios = data.get("mandatarios", [])
                if not isinstance(mandatarios, list):
                    continue
                # Recorrer del más reciente al más antiguo
                for idx, m in enumerate(reversed(mandatarios)):
                    if not isinstance(m, dict):
                        continue
                    nombre = (m.get("nombre_completo") or m.get("nombre") or "").strip()
                    slug = m.get("slug") or slugify(f"{nombre}-{entidad}")
                    partido = m.get("partido") or "Sin Partido"
                    color = m.get("partido_color") or get_party_color(partido)
                    es_activo = 1 if idx == 0 else 0
                    cargo_base = m.get("cargo_oficial") or f"Gobernador(a) de {entidad}"
                    cargo = cargo_base if es_activo else f"Ex {cargo_base}"
                    foto = m.get("foto_url") or m.get("foto_oficial_url") or ""
                    if insert_record(cursor, politicos_vistos, slug, nombre, partido, color, cargo, "Ejecutivo Estatal", entidad, es_activo, foto, m):
                        counts["ejecutivo"] += 1
            except Exception as e:
                print(f"  [Aviso] Omitiendo {gob_file.name}: {e}")

    # 3. Procesar Presidentes Municipales / Alcaldes (2,474 municipios)
    dir_alcaldes = DATA_DIR / "alcaldes"
    if dir_alcaldes.exists():
        for alc_file in sorted(dir_alcaldes.glob("*.json")):
            try:
                data = load_json_file(alc_file)
                raw_alcaldes = data.get("alcaldes", []) if isinstance(data, dict) else data
                if isinstance(raw_alcaldes, dict):
                    items = list(raw_alcaldes.values())
                elif isinstance(raw_alcaldes, list):
                    items = raw_alcaldes
                else:
                    items = []

                for a in items:
                    if not isinstance(a, dict):
                        continue
                    nombre = (a.get("nombre_completo") or a.get("nombre") or a.get("alcalde") or "").strip()
                    if not nombre or len(nombre) < 3:
                        continue
                    mun = (a.get("municipio") or "").strip()
                    entidad = (a.get("entidad") or "").strip()
                    slug = a.get("slug") or slugify(f"{nombre}-{mun}-{entidad}")
                    partido = a.get("partido") or "Sin Partido"
                    color = get_party_color(partido)
                    cargo = f"Presidente(a) Municipal de {mun}" if mun else "Presidente(a) Municipal"
                    foto = a.get("foto_url") or ""
                    if insert_record(cursor, politicos_vistos, slug, nombre, partido, color, cargo, "Municipal", entidad, 1, foto, a):
                        counts["alcaldes"] += 1
            except Exception as e:
                print(f"  [Aviso] Omitiendo {alc_file.name}: {e}")

    # 4. Procesar Congresos Locales (32 estados, priorizando legislaturas recientes)
    local_files = [
        p for p in DATA_DIR.glob("congreso_*_leg_*.json")
        if not p.name.startswith("congreso_union_")
    ]
    local_files.sort(key=extract_leg_num, reverse=True)
    for c_file in local_files:
        try:
            c_data = load_json_file(c_file)
            if isinstance(c_data, dict):
                dips = c_data.get("legisladores") or c_data.get("diputados") or c_data.get("integrantes") or []
                meta_entidad = c_data.get("metadata", {}).get("entidad") or c_data.get("entidad") or "Congreso Local"
            elif isinstance(c_data, list):
                dips = c_data
                meta_entidad = "Congreso Local"
            else:
                dips = []
                meta_entidad = "Congreso Local"
            for d in dips:
                if not isinstance(d, dict):
                    continue
                nombre = (d.get("nombre_completo") or d.get("nombre") or "").strip()
                slug = d.get("slug") or slugify(nombre)
                partido = d.get("partido") or "Sin Partido"
                color = get_party_color(partido)
                entidad = d.get("entidad") or meta_entidad
                cargo = f"Diputado(a) Local ({entidad})" if entidad and entidad != "Congreso Local" else "Diputado(a) Local"
                foto = d.get("foto_oficial_url") or d.get("foto_url") or ""
                if insert_record(cursor, politicos_vistos, slug, nombre, partido, color, cargo, "Congreso Local", entidad, 1, foto, d):
                    counts["locales"] += 1
        except Exception as e:
            print(f"  [Aviso] Omitiendo {c_file.name}: {e}")

    conn.commit()
    cursor.execute("VACUUM;")
    cursor.execute("ANALYZE;")
    conn.commit()
    conn.close()

    tmp_db.replace(OUTPUT_DB)

    size_mb = OUTPUT_DB.stat().st_size / (1024 * 1024)
    total = sum(counts.values())
    print("✅ Base de datos SQLite compilada con éxito!")
    print(f"   - Legisladores Federales: {counts['federales']}")
    print(f"   - Gobernadores / Ejecutivo: {counts['ejecutivo']}")
    print(f"   - Presidentes Municipales (Alcaldes): {counts['alcaldes']}")
    print(f"   - Diputados Locales: {counts['locales']}")
    print(f"   - Total de actores políticos indexados: {total}")
    print(f"   - Tamaño final del archivo SQLite: {size_mb:.2f} MB")


if __name__ == "__main__":
    build_database()
