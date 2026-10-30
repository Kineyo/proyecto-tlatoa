# Cómo Crear un Scraper o Extractor de Datos

> **Proyecto Tlatoa** — Guía para extraer información parlamentaria y gubernamental oficial (.gob.mx).

---

## 1. Antes de Programar
1. **Verificar Fuente Oficial**: Consulta [FUENTES.md](FUENTES.md). Toda fuente debe ser gubernamental (`.gob.mx`, congreso estatal, POE, DOF).
2. **Revisar Términos y `robots.txt`**: Respeta intervalos de consulta (mínimo 1 segundo entre peticiones).
3. **Mapeo de Datos**: Revisa los contratos en [DATA_SCHEMA.md](DATA_SCHEMA.md) y [DICCIONARIO_DE_DATOS.md](DICCIONARIO_DE_DATOS.md).

---

## 2. Tecnologías Disponibles para Scraping

Proyecto Tlatoa soporta dos mecanismos de extracción:

### A. Motor Nativo Lua + Rust (`scrapers/`) — Recomendado
Los scrapers en Lua se ejecutan directamente con el motor Rust embebido (`tlatoa-cli`), con cero dependencias externas y ejecución ultrarrápida:

* **Ubicación:** `scrapers/<fuente>.lua` (Plantilla disponible: [`scrapers/template.lua`](../scrapers/template.lua)).
* **APIs globales en Lua (`tlatoa.*`):**
  * `tlatoa.http_get(url)`: Descarga HTTP/HTTPS nativo.
  * `tlatoa.read_file(path)` / `tlatoa.write_file(path, content)`: Lectura y escritura en disco.
  * `tlatoa.slugify(text)`: Normalización de cadenas y slugs.
  * `tlatoa.log(msg)`: Logging con formato de la plataforma.
* **Ejecución:**
  ```bash
  # Compilar tlatoa-cli (solo la primera vez)
  cargo build --release -p tlatoa-core
  
  # Ejecutar scraper
  ./crates/tlatoa-core/target/release/tlatoa-cli scrape scrapers/<fuente>.lua
  ```

### B. Scripts Python (`tools/`)
Para procesamiento que requiere librerías especializadas (PDFs, regex complejas o procesamiento de archivos masivos):
* **Ubicación:** `tools/<nombre_herramienta>.py` o `tools/core/`.
* **Convención:** Usar `urllib.request` o `requests` con manejo de codificación UTF-8 estricto.
* **Validación:** Comprobar con `python3 tools/audit_encoding.py`.

---

## 3. Formato y Salida de Datos

Todo dataset extraído debe guardarse en `storage/data/json/` con la siguiente nomenclatura canónica:
- **Congreso Federal:** `congreso_union_leg_<numero>.json`
- **Congresos Estatales:** `congreso_<estado_slug>_leg_<numero>.json`
- **Municipios y Cabildos:** `storage/data/json/municipios/<cvegeo>.json`

### Campos Obligatorios de Metadatos
Cada registro o archivo debe contener trazabilidad de evidencia:
```json
{
  "fuente_url": "https://congreso-ejemplo.gob.mx/diputados",
  "sha256_evidencia": "a1b2c3d4e5f6...",
  "fecha_extraccion": "2026-10-29"
}
```

---

## 4. Compilación e Integración a la Base de Datos

Una vez generado o actualizado el archivo JSON, actualiza la base de datos binaria SQLite (`storage/tlatoa.db`) utilizada por las apps de escritorio y móviles:

```bash
python3 tools/build_mobile_db.py
```

---

## 5. Checklist Obligatorio para Pull Requests (Directo a Upstream)

> [!IMPORTANT]
> **Aviso de Pull Requests:** En este repositorio soberano (`Proyecto Tlatoa`) **no se aceptan PRs**. Todo PR con nuevos extractores o datos debe enviarse directamente al repositorio upstream **`pulso-civico/pulso-civico/main`**.

- [ ] La fuente es oficial (`.gob.mx`).
- [ ] Registros con `fuente_url` y `sha256_evidencia`.
- [ ] Codificación UTF-8 limpia validada con `python3 tools/audit_encoding.py`.
- [ ] Sin datos personales privados (teléfonos, domicilios particulares).
- [ ] Base SQLite recompilada con `tools/build_mobile_db.py`.

---

## 🗺️ Navegación y Conexiones (Obsidian Graph)

* [[README|Índice Principal de Proyecto Tlatoa]]
* [[DATA_SCHEMA|Contratos de Datos y Esquemas JSON]]
* [[FUENTES|Catálogo de Fuentes Primarias Gubernamentales]]
* [[ESTADOS_COBERTURA|Mapa de Cobertura de las 32 Entidades]]
* [[ARQUITECTURA|Arquitectura del Sistema]]
