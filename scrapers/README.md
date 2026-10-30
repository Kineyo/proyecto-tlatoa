# Motor de Scrapers en Lua — Proyecto Tlatoa (Fork Soberano)

> Sistema híbrido ultraligero: **Rust Core + Lua 5.4**.
> Permite escribir y ejecutar extractores de datos legislativos sin requerir PHP, Composer ni Python.

## Características
- **Ejecución nativa**: Embebido directamente en el binario `tlatoa-cli` mediante `mlua`.
- **Cero dependencias externas**: El runtime de Lua 5.4 está compilado dentro del ejecutable.
- **Velocidad extrema**: Procesamiento y normalización en microsegundos (< 1 ms).
- **APIs disponibles en Lua (`tlatoa.*`)**:
  - `tlatoa.log(mensaje)`: Imprime registros formateados con el sello del Tecolote.
  - `tlatoa.http_get(url)`: Descarga contenido HTTP/HTTPS nativo. Devuelve `(body, status_code)`.
  - `tlatoa.read_file(path)`: Lee un archivo local (HTML, texto o JSON).
  - `tlatoa.write_file(path, content)`: Guarda archivos asegurando la creación de directorios.
  - `tlatoa.slugify(texto)`: Normaliza acentos, eñes y caracteres especiales a formato web limpio.

## Cómo ejecutar un Scraper
```bash
# Compilar el CLI (solo la primera vez)
cargo build --release -p tlatoa-core

# Ejecutar un script de scraping
./crates/tlatoa-core/target/release/tlatoa-cli scrape scrapers/template.lua
```

## Estructura de un Scraper en Lua
Consulta [`scrapers/template.lua`](template.lua) para ver una plantilla lista para usar.


---

## 🗺️ Navegación Rápida

* [Índice Principal de Proyecto Tlatoa](../README.md)
* [Guía para Crear un Scraper](../docs/COMO_CREAR_UN_SCRAPER.md)
* [Arquitectura del Sistema](../docs/ARQUITECTURA.md)
* [Guía Maestra para Desarrolladores](../index.md)
