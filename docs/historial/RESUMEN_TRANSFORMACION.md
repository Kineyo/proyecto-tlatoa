# Resumen Maestro de la Transformación Radical (Proyecto Tlatoa — Fork Soberano)

> **Estado**: Transformación de Fase 1, 2 y 3 ejecutada con éxito.
> **Fecha**: 23 de Octubre de 2026.
> **Mascota Oficial**: Tecolote de la Transparencia (Pixel Art de alta fidelidad).

---

## 1. ¿Qué se Eliminó? (Demolición de Candados y PHP Legacy)

| Elemento Eliminado | Ubicación Original | Motivo de la Eliminación |
| :--- | :--- | :--- |
| **Página de Baneo** | `app/templates/baneado.html` y `views/baneado.php` | Punitivo y contrario a la filosofía abierta. Castigaba a usuarios con 24 horas de suspensión tras 5 clics rápidos. |
| **Candados de Baneo en Backend** | `app/services/audit_service.py` | Se eliminaron `MAX_REPORTS_PER_MINUTE = 5`, `BAN_DURATION_SECONDS = 86400`, honeypots bloqueantes y las listas negras `bans.json` y `rate_limits.json`. |
| **Middleware Freno de Mano** | `app/core/security.py` y `app/main.py` | El `RateLimitMiddleware` bloqueaba consultas ciudadanas legítimas y scrapers locales. Se reemplazó por acceso 100% libre. |
| **Restricciones de CORS y Docs** | `app/main.py` | Se abrió CORS a `*` y se habilitó la documentación pública `/docs` y `/openapi.json` sin restricciones. |
| **Todo el Stack PHP Legacy (53 clases)** | `src/` (`src/Services/`, `src/Controllers/`, `src/Core/`, `src/DTO/`) | Código pesado y dependiente de un runtime PHP. Ahora sustituido por el motor híbrido Rust + Lua. |
| **Vistas PHP Duplicadas (20 vistas)** | `views/` | Vistas legacy obsoletas eliminadas. |
| **Scripts y Runners PHP** | `tools/gateway.php`, `tools/core/*.php`, `index.php`, `.htaccess`, `config/config.php`, `bin/console.php` | Eliminados al 100%. **Cero archivos PHP en todo el repositorio**. |

---

## 2. ¿Qué se Conservó? (El Corazón de Datos Soberano)

1. **La Base de Datos SQLite Indexada ([`storage/tlatoa.db`](file:///home/alex/Documentos/El pulso de la politica/storage/tlatoa.db))**:
   - 40 MB con búsqueda de texto completo **SQLite FTS5** con respuestas en < 1 ms.
2. **Los 3,221 Archivos JSON Históricos ([`storage/data/json/`](file:///home/alex/Documentos/El pulso de la politica/storage/data/json/))**:
   - Cobertura de las 66 legislaturas federales y congresos locales de los 32 estados.
   - Datos de 2,474 municipios y cabildos en `storage/data/json/alcaldes/` y `storage/data/json/municipios/`.
   - Mandatarios históricos en `storage/data/json/ejecutivo/`.
   - Matriz de comisiones (8.4 MB) y asistencias nominales de pleno.
3. **El Archivo de Fotografías Locales ([`storage/raw/photos/`](file:///home/alex/Documentos/El pulso de la politica/storage/raw/photos/))**:
   - 583 retratos oficiales locales (18 MB) servidos directamente de manera desconectada.
4. **Cartografía Vectorial Local ([`storage/data/mexico_paths.json`](file:///home/alex/Documentos/El pulso de la politica/storage/data/mexico_paths.json))**:
   - Geometrías SVG de las 32 entidades federativas para renderizar el mapa de México sin conexiones a internet ni APIs remotas.
5. **El Principio de Neutralidad y Trazabilidad Oficial**:
   - Cero adjetivos ni sesgos hacia partidos; cada dato mantiene su enlace oficial `.gob.mx`, SIL o Gaceta.

---

## 3. ¿Qué se Añadió y Transformó?

### A. La Mascota Oficial: El Tecolote de la Transparencia
- **Concepto**: Un búho/tecolote sabio mexicano en Pixel Art de alta fidelidad, vigilante incorruptible de la información pública.
- **Archivos generados**:
  - [`assets/img/tecolote.svg`](file:///home/alex/Documentos/El pulso de la politica/assets/img/tecolote.svg): Vectorial nítido con `shape-rendering="crispEdges"`.
  - [`assets/img/tecolote.png`](file:///home/alex/Documentos/El pulso de la politica/assets/img/tecolote.png): Render de 1024x1024 píxeles perfecto con interpolación de vecino más cercano.
  - [`assets/favicon.svg`](file:///home/alex/Documentos/El pulso de la politica/assets/favicon.svg): Favicon oficial actualizado.
- **Integración visual**:
  - Presente en el encabezado (`navbar`) y pie de página de la web.
  - Integrado en la aplicación de escritorio nativa ([`crates/tlatoa-desktop/src/ui.html`](file:///home/alex/Documentos/El pulso de la politica/crates/tlatoa-desktop/src/ui.html)) tanto en la barra superior como en el estado vacío interactivo ("El Tecolote de la Transparencia no encontró coincidencias...").

### B. Motor Híbrido de Scraping (Rust Core + Lua 5.4)
- **Integración**: [`crates/tlatoa-core/src/scraper.rs`](file:///home/alex/Documentos/El pulso de la politica/crates/tlatoa-core/src/scraper.rs) incorpora el runtime de Lua 5.4 compilado estáticamente dentro del binario Rust vía `mlua` y peticiones HTTP vía `ureq`.
- **Nuevo CLI Multifunción ([`crates/tlatoa-core/src/main.rs`](file:///home/alex/Documentos/El pulso de la politica/crates/tlatoa-core/src/main.rs))**:
  - `tlatoa-cli scrape <script.lua>`: Ejecuta scrapers en Lua con velocidad sub-milisegundo.
  - `tlatoa-cli search <query>`: Consultas ultrarrápidas con FTS5.
  - `tlatoa-cli status`: Diagnóstico del estado de la base de datos local.
- **Scraper de Ejemplo y Guía**:
  - [`scrapers/template.lua`](file:///home/alex/Documentos/El pulso de la politica/scrapers/template.lua): Script base que se ejecuta en 737 microsegundos.
  - [`scrapers/README.md`](file:///home/alex/Documentos/El pulso de la politica/scrapers/README.md): Documentación del entorno.

### C. Experiencia 100% Autónoma sin Python ni Servidores
- **Página de Inicio Autónoma ([`index.html`](file:///home/alex/Documentos/El pulso de la politica/index.html))**: Archivo HTML5 puro en la raíz del repositorio, pre-renderizado, con CSS local, mascota Tecolote integrada y **cero etiquetas Jinja rotas**. Se puede abrir con doble clic directamente en cualquier navegador (`file://`). Incluye un motor de búsqueda en JavaScript con catálogo precargado y detección automática del binario nativo.
- **Lanzador de Escritorio Nativo ([`tlatoa-desktop`](file:///home/alex/Documentos/El pulso de la politica/tlatoa-desktop)) y ([`iniciar_tlatoa.sh`](file:///home/alex/Documentos/El pulso de la politica/iniciar_tlatoa.sh))**:
  - Binario único de 3.7 MB ubicado en la raíz del proyecto.
  - Con un solo doble clic arranca el servidor local en Rust (SQLite FTS5 + 16,100 registros) y abre el navegador por defecto del usuario.
  - Cero dependencias: no necesita Python, ni pip, ni node, ni internet.

### D. Servidor Nativo de Fotos y Assets en Rust
- [`crates/tlatoa-desktop/src/main.rs`](file:///home/alex/Documentos/El pulso de la politica/crates/tlatoa-desktop/src/main.rs) ahora sirve `/photos/*` y `/assets/*` directamente desde disco, permitiendo una experiencia de escritorio 100% offline con fotos de los representantes.

---

## 4. ¿Qué Falta para Siguientes Iteraciones?

1. **Portar scrapers restantes a Lua**:
   - Crear extractores `.lua` adicionales en `scrapers/` para los congresos locales que requieran actualización periódica.
2. **Compilador Nativo de Base de Datos (`build-db` en Rust)**:
   - Crear un subcomando `tlatoa-cli build-db` para compilar los 3,221 JSONs a SQLite sin necesidad de usar Python ni `build_mobile_db.py`.
3. **Empaquetado y Distribución "Zero-Install"**:
   - Generar binarios precompilados estáticos (un solo archivo ejecutable para Linux y `.exe` para Windows) que incluya la base de datos y la interfaz gráfica embebida para que cualquier usuario pueda abrirlo con doble clic.
