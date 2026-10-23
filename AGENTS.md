# AGENTS.md — Reglas para Asistentes de IA (Proyecto Tlatoa)

> Fuente única de verdad para agentes de IA y desarrolladores.

## 1. Stack Activo (Python / FastAPI)
- **Servidor local activo**: Python 3 + FastAPI (`app/main.py`) sirviendo Jinja2 en `app/templates/`. URL: `http://localhost:8000`.
- **Desarrollo activo**: Todo nuevo endpoint, vista o lógica va en `app/` (`app/routers/`, `app/services/`, `app/templates/`).
- **PROHIBIDO**: Editar o crear vistas/backend en PHP (`src/`, `views/`) para la web activa. PHP solo se mantiene para scrapers CLI y `tools/gateway.php`.
- **Datasets**: `storage/data/json/`.

## 2. Neutralidad y Datos
1. **Neutralidad total**: Cero juicios de valor, opiniones o adjetivos hacia actores políticos.
2. **Fuentes oficiales**: Todo dato debe incluir `fuente_url` oficial verificada (`.gob.mx`, Gaceta, DOF). Prohibido alucinar o inventar datos.
3. **Privacidad**: Solo datos públicos del cargo. Prohibido exponer domicilios, teléfonos personales o información de familiares.

## 3. Mapa del Código
| Ruta | Contenido | Acción |
|---|---|---|
| `app/routers/` | Endpoints y rutas FastAPI | ✅ Desarrollar aquí |
| `app/services/` | Lógica de datos y agregadores | ✅ Desarrollar aquí |
| `app/templates/` | Plantillas Jinja2 (HTML) | ✅ Desarrollar aquí |
| `app/main.py` | Configuración FastAPI | ✅ Editar con cuidado |
| `storage/data/json/` | Datasets procesados en JSON | ⚠️ Solo vía scrapers |
| `tools/` | Runners centralizados CLI | ✅ Agregar chequeos |
| `src/` | Scrapers y backend PHP legacy | ❌ No tocar para web activa |
| `views/` | Vistas PHP legacy | ❌ No tocar (usar `app/templates/`) |
| `storage/raw/` | Descargas crudas (html, fotos) | ❌ No tocar |

## 4. Reglas Obligatorias de Código
1. **Límite de tamaño**: Archivos < 400 líneas. Si excede, modularizar.
2. **Dependencias**: Cero dependencias nuevas en `requirements.txt` o `composer.json` sin autorización.
3. **Secretos**: Cero credenciales hardcodeadas; usar siempre `.env` (referencia: `.env.example`).
4. **URLs portables**: Nunca usar rutas absolutas hardcodeadas en plantillas ni enlaces.
5. **Código completo**: Prohibido entregar `// resto del código igual` o `pass # TODO`. Entregar código completo o bloques de reemplazo exactos.
6. **Lectura quirúrgica**: Leer bloques acotados (30–80 líneas con StartLine/EndLine) para ahorrar tokens.
7. **Git**: Commits convencionales (`feat:`, `fix:`, `data:`, `scraper:`, `docs:`, `refactor:`, `chore:`). **NUNCA usar `git add .`**; hacer stage archivo por archivo.

## 5. Runners y Validación (Zero-Prompt & Batching)
Valida en lote al terminar cada bloque de trabajo. Prohibido ejecutar comandos dispersos ad-hoc (`php -l`, `dir`, etc.).

### Linux / macOS
```bash
# Validar sintaxis Python y PHP
python3 -m py_compile app/main.py app/routers/*.py app/services/*.py
php tools/gateway.php lint "ruta/archivo1.php,ruta/archivo2.php"  # si modificaste PHP
php tools/gateway.php health
php tools/gateway.php git:status
```

### Windows (PowerShell)
```powershell
# Validar sintaxis Python y PHP
python -m py_compile app\main.py app\routers\web.py
powershell -ExecutionPolicy Bypass -File .\tools\run.ps1 lint "ruta\archivo1.php,ruta\archivo2.php"
powershell -ExecutionPolicy Bypass -File .\tools\run.ps1 health
powershell -ExecutionPolicy Bypass -File .\tools\run.ps1 git:status
```

## 6. Formato de Respuesta de la IA
1. **Diagnóstico**: 1-2 líneas sobre el problema o estado.
2. **Acción**: Resumen directo de cambios.
3. **Ubicación**: Enlaces a archivos y líneas (`[archivo](file:///ruta/al/archivo#L10-L30)`).

## 7. Prompts Específicos por Agente

### 🤖 Claude (Claude Code / Anthropic)
```text
Actúas como Claude Code en Proyecto Tlatoa.
- ENTORNO ACTIVO: Python 3 + FastAPI (`app/main.py`) y Jinja2 (`app/templates/`).
- REGLA SUPREMA: No edites PHP (`src/`, `views/`) para la web activa. Modifica solo `app/routers/`, `app/services/` y `app/templates/`.
- DATOS: `storage/data/json/`. Neutralidad absoluta, sin juicios y con `fuente_url` oficial (.gob.mx).
- REGLAS: Archivos < 400 líneas. Cero dependencias nuevas. Cero secretos (usar `.env`). Código completo sin truncar.
- VERIFICACIÓN:
  * Linux/macOS: `python3 -m py_compile <archivos>` y `php tools/gateway.php health`
  * Windows: `python -m py_compile <archivos>` y `powershell .\tools\run.ps1 health`
- GIT: Nunca uses `git add .`; stage archivo por archivo con prefijo convencional (`feat:`, `fix:`).
- RESPUESTA: Diagnóstico -> Acción -> Ubicación exacta.
```

### 🤖 OpenCode (OpenCode Interpreter / CLI)
```text
Actúas como OpenCode en Proyecto Tlatoa.
- ENTORNO ACTIVO: Python / FastAPI con Jinja2 en `app/`.
- DIRECTIVA: Edita solo en `app/routers/`, `app/services/` y `app/templates/`. No toques `src/` ni `views/`.
- RUNNER: Usa el gateway centralizado sin comandos sueltos:
  * Unix: `php tools/gateway.php health` | `php tools/gateway.php lint "<rutas>"`
  * Windows: `powershell -ExecutionPolicy Bypass -File .\tools\run.ps1 health`
- SINTAXIS: Valida con `python3 -m py_compile <archivos>`.
- REGLAS: Archivos < 400 líneas, sin secretos, código completo funcional, neutralidad total en datos. Nunca uses `git add .`.
```

### 🤖 OpenAI Codex (Codex CLI / ChatGPT / Copilot CLI)
```text
Eres un agente de desarrollo basado en OpenAI Codex en Proyecto Tlatoa.
- ENTORNO ACTIVO: Python (FastAPI + Jinja2) en `app/main.py`.
- ZONA PERMITIDA: `app/routers/`, `app/services/`, `app/templates/`, `storage/data/json/`.
- PROHIBIDO: Editar `src/` o `views/` para la aplicación web activa.
- DIRECTRICES: Neutralidad estricta (datos con `fuente_url` oficial), archivos < 400 líneas, cero dependencias nuevas, código completo sin abreviar.
- VALIDACIÓN: `python3 -m py_compile <archivos>` y runner `php tools/gateway.php health` (Unix) o `powershell .\tools\run.ps1 health` (Windows).
- GIT: Commits convencionales; nunca `git add .`.
```

### 🤖 Antigravity (Google DeepMind / Gemini CLI)
```text
Eres el agente Antigravity / Gemini en Proyecto Tlatoa.
- MODO: Ultra-eficiencia, bajo consumo de tokens y cero prompts innecesarios en terminal.
- ENTORNO ACTIVO: Python (FastAPI + Jinja2) en `app/main.py`. Todo desarrollo web va en `app/routers/`, `app/services/` o `app/templates/`. PROHIBIDO tocar PHP (`src/`, `views/`) para la web activa.
- LECTURA QUIRÚRGICA: Inspecciona bloques de 30-80 líneas con StartLine/EndLine. No leas archivos enteros sin necesidad.
- RUNNER ZERO-PROMPT: Valida en lote con `python3 -m py_compile` y el runner del OS (`php tools/gateway.php health` o `powershell .\tools\run.ps1 health`). Prohibido comandos ad-hoc dispersos.
- RESPUESTA: Diagnóstico rápido -> Acción tomada -> Ubicación exacta con enlace.
- REGLAS: Archivos < 400 líneas, neutralidad absoluta, nunca `git add .`.
```

### 🤖 Cursor / GitHub Copilot (Agentes de IDE)
```text
Contexto para Cursor / GitHub Copilot en Proyecto Tlatoa:
- ENTORNO ACTIVO: Python / FastAPI (`app/main.py`) con Jinja2 en `app/templates/`.
- DESARROLLO ACTIVO: `app/routers/` (APIs/rutas), `app/services/` (lógica/datos), `app/templates/` (UI).
- NO MODIFICAR: `src/` ni `views/` para funcionalidades activas.
- REGLAS: Archivos < 400 líneas, preservar tipos/comentarios, neutralidad estricta con `fuente_url`, sin dependencias nuevas sin aprobación.
- VALIDACIÓN: Ejecutar `python3 -m py_compile` antes de entregar cambios.
```

## 8. Documentación de Referencia
- Arquitectura: `docs/ARQUITECTURA.md`
- Esquema JSON: `docs/DATA_SCHEMA.md`
- Scrapers: `docs/COMO_CREAR_UN_SCRAPER.md`
- Fuentes: `docs/FUENTES.md`
- Diccionario: `docs/DICCIONARIO_DE_DATOS.md`
