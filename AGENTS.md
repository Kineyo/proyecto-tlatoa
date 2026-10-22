# AGENTS.md — Reglas para asistentes de IA

> Este archivo lo leen automáticamente Cursor, Claude Code, GitHub Copilot, Gemini/Antigravity, Codex y otros agentes.
> Si usas ChatGPT u otra IA de chat, **pégale este archivo al inicio de la conversación**.

## Contexto del proyecto y Entorno Activo
- **ENTORNO ACTIVO EN ESTA PC: PYTHON (FastAPI + Jinja2)**.
  - El servidor que se ejecuta y visualiza localmente en esta máquina corre en **Python / FastAPI (`app/main.py`)** con plantillas en `app/templates/`.
  - **REGLA ESTRICTA**: Todo desarrollo nuevo de backend, endpoints, vistas y lógica visual DEBE realizarse en **Python** dentro del directorio `app/` (`app/routers/`, `app/services/`, `app/templates/`), NO en PHP (`src/`, `views/`), para que los cambios se reflejen de inmediato en la aplicación en ejecución.
- Principio rector: **neutralidad**. Todo dato mostrado debe provenir de una fuente oficial o nota periodística **enlazada**. Nunca generar opiniones, calificativos ni datos inventados.

## Mapa del código
| Ruta | Qué contiene | ¿Tocar? |
|---|---|---|
| `index.php` | Front controller | Rara vez |
| `src/Core/` | Router, HttpClient, Logger, ViewHelper | Solo con issue aprobado |
| `src/Controllers/` | Controladores web | Sí |
| `src/Services/` | Lógica de datos y scrapers | Sí |
| `src/DTO/` | Contratos de datos tipados | **Solo con aprobación** (rompe datasets) |
| `views/` | Plantillas PHP/HTML | Sí |
| `config/config.php` | Configuración (lee `.env`) | Con cuidado |
| `tools/` | Runner y utilidades CLI | Sí |
| `storage/data/` | Datasets JSON generados | Solo vía scrapers |
| `storage/raw/` | Descargas crudas (ignorado en git salvo fotos) | No |

## Reglas obligatorias
1. **`declare(strict_types=1);`** en todo archivo PHP. No eliminar tipos.
2. **Sin dependencias nuevas** (Composer/npm) sin issue aprobado. Usar funciones nativas de PHP/JS.
3. **URLs portables**: nunca `href="/..."` ni `fetch('/...')`. Usar `Router::url('/ruta')`. En PHP, rutas con `__DIR__`.
4. **Escapar toda salida** en vistas con `ViewHelper::e()`.
5. **Cero secretos** en código. Configuración vía `.env` (ver `.env.example`).
6. **Archivos < 400 líneas**. Si una clase crece más, dividirla.
7. **Lógica reutilizable** → servicio/helper, nunca copiar-pegar.
8. **Firmas existentes**: parámetros nuevos siempre opcionales con valor por defecto.
9. **Prohibido** escribir `// ... resto del código igual` o resumir código existente. Entregar archivos completos o diffs exactos.
10. **Scrapers**: respetar `rate_limit_ms` (≥ 1000 ms), no saturar servidores de gobierno, guardar siempre `fuente_url` y `fecha_extraccion`.
11. **Datos personales**: solo información pública del cargo (nada de domicilios particulares, familiares ni datos sensibles).

## Verificación y Compilación según tu OS

<details open>
<summary><strong>🛠️ ¿Cómo compilar y verificar según tu OS?</strong></summary>
<br>

<details>
<summary><strong>🐧 Linux (Bash / Zsh)</strong></summary>

```bash
# Levantar servidor activo (FastAPI + Jinja2)
python3 -m venv .venv && source .venv/bin/activate
pip install -r requirements.txt
uvicorn app.main:app --reload --port 8000

# Validar sintaxis Python y PHP antes de entregar
python3 -m py_compile app/main.py app/routers/web.py
php tools/gateway.php lint "ruta/archivo1.php,ruta/archivo2.php"
php tools/gateway.php health
```

<details>
<summary><strong>🤖 Agentes (Prompt adaptado para Linux)</strong></summary>

```text
Estás en Pulso Cívico ejecutándose sobre Linux (rutas POSIX con `/`).
1. El entorno activo es Python (FastAPI + Jinja2) en `app/main.py`.
2. Desarrolla endpoints en `app/routers/`, lógica en `app/services/` y vistas en `app/templates/`. No edites `src/` ni `views/` para cambios de UI o backend web activo.
3. Los datasets residen en `storage/data/json/`.
4. Para verificar sintaxis y estado usa:
   - `python3 -m py_compile <archivos_python>`
   - `php tools/gateway.php lint "ruta/archivo1.php,ruta/archivo2.php"`
   - `php tools/gateway.php health`
   - `php tools/gateway.php git:status`
5. Cumple neutralidad absoluta, archivos < 400 líneas y commits granulares sin `git add .`.
```
</details>
</details>

<details>
<summary><strong>🪟 Windows (PowerShell / CMD)</strong></summary>

```powershell
# Levantar servidor activo (FastAPI + Jinja2)
python -m venv .venv
.\.venv\Scripts\Activate.ps1
pip install -r requirements.txt
uvicorn app.main:app --reload --port 8000

# Validar sintaxis Python y PHP con el runner centralizado
python -m py_compile app\main.py app\routers\web.py
powershell -ExecutionPolicy Bypass -File .\tools\run.ps1 lint "ruta\archivo1.php,ruta\archivo2.php"
powershell -ExecutionPolicy Bypass -File .\tools\run.ps1 health
```

<details>
<summary><strong>🤖 Agentes (Prompt adaptado para Windows)</strong></summary>

```text
Estás en Pulso Cívico ejecutándose sobre Windows (PowerShell, rutas con `\`).
1. El entorno activo es Python (FastAPI + Jinja2) en `app\main.py`.
2. Desarrolla endpoints en `app\routers\`, lógica en `app\services\` y vistas en `app\templates\`. No edites `src\` ni `views\` para cambios de UI o backend web activo.
3. Los datasets residen en `storage\data\json\`.
4. Ejecuta toda validación e inspección mediante el runner centralizado:
   - `python -m py_compile <archivos_python>`
   - `powershell -ExecutionPolicy Bypass -File .\tools\run.ps1 lint "ruta\archivo1.php,ruta\archivo2.php"`
   - `powershell -ExecutionPolicy Bypass -File .\tools\run.ps1 health`
   - `powershell -ExecutionPolicy Bypass -File .\tools\run.ps1 git:status`
5. Cumple neutralidad absoluta, archivos < 400 líneas y commits granulares sin `git add .`.
```
</details>
</details>

<details>
<summary><strong>🍎 macOS (Zsh / Bash)</strong></summary>

```zsh
# Levantar servidor activo (FastAPI + Jinja2)
python3 -m venv .venv && source .venv/bin/activate
pip install -r requirements.txt
uvicorn app.main:app --reload --port 8000

# Validar sintaxis Python y PHP antes de entregar
python3 -m py_compile app/main.py app/routers/web.py
php tools/gateway.php lint "ruta/archivo1.php,ruta/archivo2.php"
php tools/gateway.php health
```

<details>
<summary><strong>🤖 Agentes (Prompt adaptado para macOS)</strong></summary>

```text
Estás en Pulso Cívico ejecutándose sobre macOS (rutas POSIX con `/`).
1. El entorno activo es Python (FastAPI + Jinja2) en `app/main.py`.
2. Desarrolla endpoints en `app/routers/`, lógica en `app/services/` y vistas en `app/templates/`. No edites `src/` ni `views/` para cambios de UI o backend web activo.
3. Los datasets residen en `storage/data/json/`.
4. Para verificar sintaxis y estado usa:
   - `python3 -m py_compile <archivos_python>`
   - `php tools/gateway.php lint "ruta/archivo1.php,ruta/archivo2.php"`
   - `php tools/gateway.php health`
   - `php tools/gateway.php git:status`
5. Cumple neutralidad absoluta, archivos < 400 líneas y commits granulares sin `git add .`.
```
</details>
</details>

</details>

Si hay error de sintaxis, **corregirlo antes de proponer el cambio**.

## Commits
Prefijos: `feat:`, `fix:`, `data:`, `scraper:`, `docs:`, `refactor:`, `chore:`.
Nunca `git add .` — agregar solo archivos modificados.

## Documentación de referencia
- Arquitectura: `docs/ARQUITECTURA.md`
- Formato de datos: `docs/DATA_SCHEMA.md`
- Crear scraper: `docs/COMO_CREAR_UN_SCRAPER.md`
- Fuentes: `docs/FUENTES.md`
