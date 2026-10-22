# REGLAS DEL PROYECTO: PULSO CÍVICO
**Modo: Ultra-Eficiencia, Low-Cost y Cero Interrupciones de Terminal**

> Las reglas universales para cualquier IA y colaborador están en [`AGENTS.md`](AGENTS.md) (leer primero).
> Este archivo agrega solo las reglas operativas del entorno Gemini/Antigravity.

## 0. REGLA MAESTRA DE STACK: ENTORNO ACTIVO EN PYTHON (FASTAPI / JINJA2)
- **El entorno que corre localmente en esta PC es Python con FastAPI (`app/main.py`)** sirviendo plantillas Jinja2 desde `app/templates/`.
- **PROHIBIDO codear en PHP (`src/`, `views/`)** para características visuales o de backend que el usuario prueba en esta máquina. Todo endpoint, servicio y vista activa DEBE implementarse en Python dentro de `app/` (`app/routers/`, `app/services/`, `app/templates/`) para que los cambios se reflejen de inmediato en el navegador del usuario (`http://localhost:8000`).

---

## 1. RUNNER CENTRALIZADO Y COMANDO ÚNICO (ZERO-PROMPT)
Para evitar solicitudes repetitivas de permisos de consola y optimizar tokens, utiliza el punto único de ejecución adaptado a cada sistema operativo:

<details open>
<summary><strong>🛠️ ¿Cómo compilar y ejecutar el runner según tu OS?</strong></summary>
<br>

<details>
<summary><strong>🐧 Linux (Bash / Zsh)</strong></summary>

```bash
# Levantar entorno activo FastAPI
python3 -m venv .venv && source .venv/bin/activate
pip install -r requirements.txt
uvicorn app.main:app --reload --port 8000

# Gateway centralizado en Linux
php tools/gateway.php <accion> [args]
php tools/gateway.php lint "ruta/archivo1.php,ruta/archivo2.php"
php tools/gateway.php health
php tools/gateway.php git:status
```

<details>
<summary><strong>🤖 Agentes (Prompt adaptado para Linux)</strong></summary>

```text
Entorno activo: Linux (POSIX `/`) con Python FastAPI (`app/main.py`) y plantillas en `app/templates/`.
- Implementa todo endpoint, servicio y vista en `app/routers/`, `app/services/` y `app/templates/`.
- Ejecuta diagnósticos y validaciones en lote mediante `php tools/gateway.php <accion> [args]` y `python3 -m py_compile`.
- Usa lectura quirúrgica (`StartLine` y `EndLine` de 30–80 líneas) y evita comandos dispersos.
```
</details>
</details>

<details>
<summary><strong>🪟 Windows (PowerShell)</strong></summary>

```powershell
# Levantar entorno activo FastAPI
python -m venv .venv
.\.venv\Scripts\Activate.ps1
pip install -r requirements.txt
uvicorn app.main:app --reload --port 8000

# Runner centralizado en Windows
powershell -ExecutionPolicy Bypass -File .\tools\run.ps1 <accion> [args]
powershell -ExecutionPolicy Bypass -File .\tools\run.ps1 lint "ruta\archivo1.php,ruta\archivo2.php"
powershell -ExecutionPolicy Bypass -File .\tools\run.ps1 health
powershell -ExecutionPolicy Bypass -File .\tools\run.ps1 git:status
```

<details>
<summary><strong>🤖 Agentes (Prompt adaptado para Windows)</strong></summary>

```text
Entorno activo: Windows (PowerShell `\`) con Python FastAPI (`app\main.py`) y plantillas en `app\templates\`.
- Implementa todo endpoint, servicio y vista en `app\routers\`, `app\services\` y `app\templates\`.
- Ejecuta toda operación de consola mediante `powershell -ExecutionPolicy Bypass -File .\tools\run.ps1 <accion> [args]`.
- Usa lectura quirúrgica (`StartLine` y `EndLine` de 30–80 líneas) y evita comandos dispersos.
```
</details>
</details>

<details>
<summary><strong>🍎 macOS (Zsh / Bash)</strong></summary>

```zsh
# Levantar entorno activo FastAPI
python3 -m venv .venv && source .venv/bin/activate
pip install -r requirements.txt
uvicorn app.main:app --reload --port 8000

# Gateway centralizado en macOS
php tools/gateway.php <accion> [args]
php tools/gateway.php lint "ruta/archivo1.php,ruta/archivo2.php"
php tools/gateway.php health
php tools/gateway.php git:status
```

<details>
<summary><strong>🤖 Agentes (Prompt adaptado para macOS)</strong></summary>

```text
Entorno activo: macOS (POSIX `/`) con Python FastAPI (`app/main.py`) y plantillas en `app/templates/`.
- Implementa todo endpoint, servicio y vista en `app/routers/`, `app/services/` y `app/templates/`.
- Ejecuta diagnósticos y validaciones en lote mediante `php tools/gateway.php <accion> [args]` y `python3 -m py_compile`.
- Usa lectura quirúrgica (`StartLine` y `EndLine` de 30–80 líneas) y evita comandos dispersos.
```
</details>
</details>

</details>

- **Prohibición**: Queda prohibido ejecutar comandos de consola dispersos (`php -l`, `Get-ChildItem`, `dir`, `git status`, shells de base de datos o scripts temporales ad-hoc).
- **Acciones registradas en `tools/gateway.php` y `tools/run.ps1`**:
  - `lint "<archivo1,archivo2...>"`: Valida sintaxis en lote de uno o múltiples archivos PHP.
  - `lint:dir [directorio]`: Valida recursivamente la sintaxis de una carpeta completa.
  - `health`: Chequeo integral de salud (sintaxis general + verificación de carpetas `storage/`).
  - `git:status`: Consulta limpia de estado de control de versiones en JSON.
  - `git:diff`: Resumen conciso de cambios pendientes.

---

## 2. PROTOCOLO OBLIGATORIO DE BATCHING POST-EDICIÓN
- Al terminar un bloque de trabajo con múltiples archivos modificados, se valida la sintaxis **en una sola llamada agrupada** según el OS (`php tools/gateway.php lint "ruta/archivo1.php,ruta/archivo2.php"` o `.\tools\run.ps1 lint "ruta\archivo1.php,ruta\archivo2.php"`).
- Si se añaden nuevas tareas automatizables (migraciones, consultas analíticas o tests), se integran como clase en `tools/core/` y se exponen en `tools/gateway.php`.

---

## 3. LECTURA QUIRÚRGICA Y AHORRO DE TOKENS
- **Uso prioritario de Codebase Memory**: Para localizar funciones, clases o contratos, usar herramientas de grafo (`codebase-memory-mcp`) antes de buscar en disco.
- **Rango acotado de lectura**: Al inspeccionar código con `view_file`, especificar siempre `StartLine` y `EndLine` pertinentes (bloques de 30 a 80 líneas). Evitar volcar archivos enteros sin necesidad.
- **Respuestas Concisas**: Formato directo: **Diagnóstico rápido → Acción tomada → Ubicación (archivo/línea)** sin intros innecesarias.
