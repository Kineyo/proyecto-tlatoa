# Cómo crear un scraper (nueva legislatura o fuente)

## 1. Antes de programar
1. Revisa [FUENTES.md](FUENTES.md) y abre un Issue **"Nueva fuente"** para reservarla.
2. Revisa `robots.txt` del portal.
3. Guarda 2-3 páginas de ejemplo (HTML) para probar sin golpear el servidor.

## 2. Estructura
Los scrapers actuales viven en `src/Services/`:
- `SilIngestService.php` — legisladores (SIL-SEGOB).
- `ParliamentScraperService.php` — votaciones y asistencias (SITL).

Se registran en `tools/core/ScraperRouter.php` (fuentes `sil`, `votaciones`, `asistencias`).

Para una fuente nueva:
1. Crea `src/Services/<Fuente>ScraperService.php` (< 400 líneas; divide en helpers si crece).
2. Usa **siempre** `Tlatoa\Core\HttpClient` (ya trae reintentos, rate limit y logs). No uses `file_get_contents` sobre URLs.
3. Mapea los resultados a los DTOs de `src/DTO/` (ver [DATA_SCHEMA.md](DATA_SCHEMA.md)).
4. Guarda el resultado en `storage/data/json/<camara>_leg_<numero>.json`.
5. Registra un nuevo `case` en `ScraperRouter::run()`.

## 3. Esqueleto
```php
<?php
declare(strict_types=1);

namespace Tlatoa\Services;

use Tlatoa\Core\HttpClient;
use Tlatoa\Core\Logger;
use Tlatoa\DTO\LegisladorDTO;

final class CongresoEjemploScraperService
{
    public function __construct(
        private readonly array $config,
        private readonly HttpClient $http,
        private readonly Logger $logger
    ) {}

    /** @return LegisladorDTO[] */
    public function ingest(int $legislatura): array
    {
        $url = 'https://congreso-ejemplo.gob.mx/diputados';
        $response = $this->http->request('GET', $url);
        // 1. Parsear HTML con DOMDocument + DOMXPath (nativo)
        // 2. Construir LegisladorDTO por cada registro
        // 3. metadata: ['fuente_url' => $url, 'fecha_extraccion' => date('Y-m-d')]
        return [];
    }
}
```

## 4. Checklist del PR
- [ ] Validación de sintaxis sin errores según tu OS.
- [ ] Cada registro tiene `metadata.fuente_url` y `metadata.fecha_extraccion`.
- [ ] Rate limit ≥ 1000 ms; sin peticiones en paralelo.
- [ ] Sin datos personales no públicos.
- [ ] Fila actualizada en `docs/FUENTES.md`.

## 5. Compilación, Ejecución y Prompts de Agentes según tu OS

<details open>
<summary><strong>🛠️ ¿Cómo compilar / ejecutar tu scraper según tu OS?</strong></summary>
<br>

<details>
<summary><strong>🐧 Linux (Bash / Zsh)</strong></summary>

```bash
# Validar sintaxis del scraper PHP
php tools/gateway.php lint "src/Services/CongresoEjemploScraperService.php,tools/core/ScraperRouter.php"

# Ejecutar el scraper desde el gateway CLI
php tools/gateway.php scrape congreso-ejemplo

# Si integras el nuevo dataset en el servicio activo Python (FastAPI), verifica su compilación:
python3 -m py_compile app/services/data_service.py
```

<details>
<summary><strong>🤖 Agentes (Prompt adaptado para Linux)</strong></summary>

```text
Lee `AGENTS.md`, `docs/DATA_SCHEMA.md` y `src/Services/SilIngestService.php` (entorno Linux, rutas POSIX `/`).
1. Crea un scraper en `src/Services/<Fuente>ScraperService.php` (< 400 líneas) para <URL del portal> que extraiga <datos> de la legislatura <N>.
2. Usa `Tlatoa\Core\HttpClient` y `LegisladorDTO`, PHP 8.1+ con `declare(strict_types=1);`, sin dependencias externas, parseando con `DOMDocument`/`DOMXPath`.
3. Guarda el dataset en `storage/data/json/<camara>_leg_<numero>.json` incluyendo `metadata.fuente_url` y `fecha_extraccion`.
4. Registra la fuente en `tools/core/ScraperRouter.php` y, si requiere visualización en la web activa, intégralo en Python dentro de `app/services/data_service.py`.
5. Valida antes de finalizar con:
   `php tools/gateway.php lint "src/Services/<Fuente>ScraperService.php,tools/core/ScraperRouter.php"`
```
</details>
</details>

<details>
<summary><strong>🪟 Windows (PowerShell)</strong></summary>

```powershell
# Validar sintaxis del scraper con el runner centralizado de Windows
powershell -ExecutionPolicy Bypass -File .\tools\run.ps1 lint "src\Services\CongresoEjemploScraperService.php,tools\core\ScraperRouter.php"

# Ejecutar el scraper desde el runner centralizado
powershell -ExecutionPolicy Bypass -File .\tools\run.ps1 scrape congreso-ejemplo

# Si integras el nuevo dataset en el servicio activo Python (FastAPI), verifica su compilación:
python -m py_compile app\services\data_service.py
```

<details>
<summary><strong>🤖 Agentes (Prompt adaptado para Windows)</strong></summary>

```text
Lee `AGENTS.md`, `docs\DATA_SCHEMA.md` y `src\Services\SilIngestService.php` (entorno Windows PowerShell, rutas con `\`).
1. Crea un scraper en `src\Services\<Fuente>ScraperService.php` (< 400 líneas) para <URL del portal> que extraiga <datos> de la legislatura <N>.
2. Usa `Tlatoa\Core\HttpClient` y `LegisladorDTO`, PHP 8.1+ con `declare(strict_types=1);`, sin dependencias externas, parseando con `DOMDocument`/`DOMXPath`.
3. Guarda el dataset en `storage\data\json\<camara>_leg_<numero>.json` incluyendo `metadata.fuente_url` y `fecha_extraccion`.
4. Registra la fuente en `tools\core\ScraperRouter.php` y, si requiere visualización en la web activa, intégralo en Python dentro de `app\services\data_service.py`.
5. Valida antes de finalizar con el runner centralizado:
   `powershell -ExecutionPolicy Bypass -File .\tools\run.ps1 lint "src\Services\<Fuente>ScraperService.php,tools\core\ScraperRouter.php"`
```
</details>
</details>

<details>
<summary><strong>🍎 macOS (Zsh / Bash)</strong></summary>

```zsh
# Validar sintaxis del scraper PHP
php tools/gateway.php lint "src/Services/CongresoEjemploScraperService.php,tools/core/ScraperRouter.php"

# Ejecutar el scraper desde el gateway CLI
php tools/gateway.php scrape congreso-ejemplo

# Si integras el nuevo dataset en el servicio activo Python (FastAPI), verifica su compilación:
python3 -m py_compile app/services/data_service.py
```

<details>
<summary><strong>🤖 Agentes (Prompt adaptado para macOS)</strong></summary>

```text
Lee `AGENTS.md`, `docs/DATA_SCHEMA.md` y `src/Services/SilIngestService.php` (entorno macOS, rutas POSIX `/`).
1. Crea un scraper en `src/Services/<Fuente>ScraperService.php` (< 400 líneas) para <URL del portal> que extraiga <datos> de la legislatura <N>.
2. Usa `Tlatoa\Core\HttpClient` y `LegisladorDTO`, PHP 8.1+ con `declare(strict_types=1);`, sin dependencias externas, parseando con `DOMDocument`/`DOMXPath`.
3. Guarda el dataset en `storage/data/json/<camara>_leg_<numero>.json` incluyendo `metadata.fuente_url` y `fecha_extraccion`.
4. Registra la fuente en `tools/core/ScraperRouter.php` y, si requiere visualización en la web activa, intégralo en Python dentro de `app/services/data_service.py`.
5. Valida antes de finalizar con:
   `php tools/gateway.php lint "src/Services/<Fuente>ScraperService.php,tools/core/ScraperRouter.php"`
```
</details>
</details>

</details>

