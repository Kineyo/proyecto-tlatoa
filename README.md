# 🕷️ Suite Oficial de Scrapers e Ingesta Legislativa

> **Rama Huérfana (`scrapers`)** — Entorno aislado y autosuficiente para extractores, parsers y motores de ingesta parlamentaria y gubernamental de México.

---

> [!CAUTION]
> ### 🚫 POLÍTICA DE CONTRIBUCIÓN: NO SE ACEPTAN PULL REQUESTS EN ESTE REPOSITORIO
> Este repositorio (`Proyecto Tlatoa`) es un **Hard Fork soberano** enfocado exclusivamente en runtimes multiplataforma offline (Rust, Kotlin, SQLite FTS5 y FastAPI).
> 
> * **NO se aceptan Pull Requests ni propuestas en este repositorio ni en esta rama.**
> * Cualquier nuevo scraper, corrección de extractores existentes o aportación de datasets **debe enviarse mediante Pull Request directamente al repositorio base upstream:**
>   👉 **[`pulso-civico/pulso-civico/main`](https://github.com/elpulsocivico/pulso-civico)**

---

## 🧭 Propósito de esta Rama

Para preservar la pureza, ligereza y velocidad de la aplicación principal, la suite completa de scraping se mantiene aislada en esta **rama huérfana (`scrapers`)**. Aquí encontrarás:
* Extractores en PHP y Python para el Congreso de la Unión (SIL y SITL) y los 32 Congresos Estatales.
* Parsers especializados para diarios de debates, PDFs legislativos y gacetas parlamentarias.
* Plantillas de scrapers ultraligeros en Lua 5.4.
* Herramientas de auditoría de codificación y cálculo de evidencia criptográfica (SHA-256).

---

## 🚀 Cómo Usar los Scrapers

### 1. Preparación del Entorno
Copia el archivo de configuración de variables de entorno:
```bash
cp .env.example .env
```

### 2. Motor CLI en PHP (`bin/console.php`)
Ideal para ingestas masivas del Sistema de Información Legislativa (SIL):
```bash
# Ingestar las 32 entidades federativas completas (628 legisladores federales)
php bin/console.php --nacional --legislatura=66

# Ingestar una entidad específica (ejemplo: Jalisco, ID 14)
php bin/console.php --entidad=14 --legislatura=66

# Ingestar sin descargar fotos (modo ultra-rápido)
php bin/console.php --entidad=2 --no-photos
```

### 3. Gateway Unificado (`tools/gateway.php`)
Permite invocar extractores federales o estatales de manera parametrizada:
```bash
# Listar todos los scrapers y entidades disponibles
php tools/gateway.php list

# Extraer legisladores federales del SIL
php tools/gateway.php scrape sil "nacional=1,legislatura=66"

# Extraer votaciones nominales de la Cámara de Diputados (SITL)
php tools/gateway.php scrape votaciones "periodo=1,legislatura=66"

# Extraer congresos estatales específicos
php tools/gateway.php scrape cdmx
php tools/gateway.php scrape jalisco
php tools/gateway.php scrape nuevoleon
php tools/gateway.php scrape sonora
```

### 4. Parsers Especializados en Python (`tools/core/`)
Para procesar gacetas y diarios en formato PDF o actas no estructuradas:
```bash
# Parseo de actas de Coahuila
python3 tools/core/coahuila_parser.py

# Extracción de gacetas PDF de Nuevo León
python3 tools/core/nl_pdf_parser.py
```

### 5. Plantilla Ultraligera en Lua (`scrapers/template.lua`)
Ejemplo de extractor con cero dependencias en runtime:
```lua
-- scrapers/template.lua
local entidad = "Ciudad de México"
local slug = tlatoa.slugify(entidad)
tlatoa.log("Extrayendo " .. slug)
```

---

## 📜 Lineamientos Éticos y Cumplimiento de Servidor

Todo extractor debe operar con estricto apego a las normas éticas de acceso a servidores públicos:

1. **Inspección de `robots.txt`**: Antes de consultar cualquier dominio gubernamental (`.gob.mx`), inspecciona `https://<dominio>/robots.txt` y respeta rutas restringidas (`Disallow`) y directivas de crawl delay.
2. **Rate Limiting Obligatorio (Cortesía de Red)**:
   - Pausa mínima de **1,250 ms** entre peticiones consecutivas, sumando un jitter aleatorio de 100–250 ms.
   - Prohibido realizar peticiones concurrentes masivas o multihilo que puedan saturar servidores públicos.
3. **Caché Local de Respuestas Crudas**:
   - Guarda siempre el HTML/PDF obtenido en `storage/raw/html/`.
   - Si el archivo ya existe en disco, **no vuelvas a consultar el servidor gubernamental**.
4. **User-Agent Transparente e Identificable**: Declara un User-Agent claro que identifique el propósito cívico de la petición.
5. **Neutralidad Total y Respeto a la Privacidad**:
   - Cero adjetivos, opiniones o sesgos partidistas en el código, comentarios o datos extraídos.
   - Exclusivamente datos oficiales públicos del cargo. **Prohibido recopilar teléfonos privados, domicilios o información familiar.**

---

## 📦 Formato de Datos para el Upstream (`pulso-civico/pulso-civico/main`)

Para que un dataset o scraper sea aceptado en el repositorio base upstream `pulso-civico`, debes cumplir con los siguientes estándares canónicos:

### 1. Convenciones Estructurales
* **Codificación:** UTF-8 estricto sin BOM, normalización Unicode NFC. Valida siempre con:
  ```bash
  python3 tools/audit_encoding.py
  ```
* **Nombres de campos:** Formato `snake_case`.
* **Directorio de salida:** `storage/data/json/`.

### 2. Nomenclatura Canónica de Archivos
* **Congreso de la Unión:** `congreso_union_leg_<numero>.json`
* **Congresos Estatales:** `congreso_<estado_slug>_leg_<numero>.json` (ejemplo: `congreso_jalisco_leg_64.json`, `congreso_cdmx_leg_66.json`).
* **Municipios y Cabildos:** `storage/data/json/municipios/<cvegeo>.json`.

### 3. Trazabilidad y Fe Pública Digital (Metadatos Obligatorios)
Todo archivo o registro extraído debe incluir metadatos de evidencia criptográfica:
```json
{
  "metadata": {
    "entidad": "Jalisco",
    "slug": "jalisco",
    "legislatura": 64,
    "fuente_url": "https://www.congresojal.gob.mx/...",
    "sha256_evidencia": "a1b2c3d4e5f6...",
    "fecha_extraccion": "2026-10-30"
  },
  "diputados": [
    {
      "nombre_completo": "Nombre Oficial",
      "slug": "nombre-oficial",
      "partido": "MORENA",
      "tipo_eleccion": "Mayoría Relativa",
      "distrito": "Distrito 1",
      "cargo": "Diputado(a) Local"
    }
  ]
}
```

---

## 🔄 Flujo para Contribuir al Upstream

1. Haz un fork del repositorio principal: **`https://github.com/elpulsocivico/pulso-civico`**.
2. Clona tu fork y crea una rama descriptiva:
   ```bash
   git checkout -b scraper/congreso-colima
   ```
3. Desarrolla y prueba tu extractor respetando los lineamientos de `robots.txt` y rate-limiting.
4. Genera el dataset JSON y audita la codificación con `python3 tools/audit_encoding.py`.
5. Realiza tus commits con formato convencional (`scraper: agrega extractor de Colima LX`).
6. Envía un **Pull Request directamente a `pulso-civico/pulso-civico/main`**.
