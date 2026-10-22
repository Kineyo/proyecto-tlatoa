# Cómo contribuir a Pulso Cívico

¡Gracias por sumarte! No necesitas ser programador para aportar.

## Formas de aportar
| Perfil | Qué puedes hacer |
|---|---|
| 💻 Desarrollo | Scrapers de nuevas fuentes, mejoras de vistas, rendimiento, tests |
| 📊 Datos / investigación | Encontrar portales oficiales, validar datos contra fuentes, reportar errores |
| 📰 Periodismo | Vincular notas a políticos y votaciones, verificar contexto |
| 🎨 Diseño / UX | Mejorar experiencia móvil, accesibilidad, visualizaciones |
| 📣 Difusión | Comunicar hallazgos, traer más voluntarios |

## Flujo de trabajo (código o datos)
1. **Busca o abre un Issue** antes de trabajar (evita duplicar esfuerzos). Comenta "Lo tomo".
2. Haz **fork** del repositorio y crea una rama:
   - `feat/descripcion-corta`, `fix/...`, `scraper/congreso-jalisco`, `docs/...`
3. **Levanta tu entorno y verifica tus cambios según tu sistema operativo:**

<details open>
<summary><strong>🛠️ ¿Cómo compilar / ejecutar según tu OS?</strong></summary>
<br>

<details>
<summary><strong>🐧 Linux (Bash / Zsh)</strong></summary>

```bash
# Levantar servidor activo en Python (FastAPI + Jinja2)
cp .env.example .env
python3 -m venv .venv && source .venv/bin/activate
pip install -r requirements.txt
uvicorn app.main:app --reload --port 8000

# Verificar sintaxis antes de hacer commit
python3 -m py_compile app/main.py app/routers/web.py
php tools/gateway.php lint "archivos,modificados.php"
php tools/gateway.php health
```

<details>
<summary><strong>🤖 Agentes (Prompt adaptado a rutas de Linux)</strong></summary>

```text
Trabajas en Pulso Cívico sobre Linux. Lee primero `AGENTS.md`.
- Entorno web activo: Python / FastAPI (`app/main.py`), routers en `app/routers/`, servicios en `app/services/` y plantillas Jinja2 en `app/templates/`.
- Datasets JSON en `storage/data/json/`.
- Antes de entregar cambios valida en terminal con:
  `python3 -m py_compile <archivos.py>` y `php tools/gateway.php lint "<archivos.php>"`
- Nunca uses `git add .`; agrega solo los archivos modificados.
```
</details>
</details>

<details>
<summary><strong>🪟 Windows (PowerShell)</strong></summary>

```powershell
# Levantar servidor activo en Python (FastAPI + Jinja2)
Copy-Item .env.example .env
python -m venv .venv
.\.venv\Scripts\Activate.ps1
pip install -r requirements.txt
uvicorn app.main:app --reload --port 8000

# Verificar sintaxis con el runner centralizado antes de hacer commit
python -m py_compile app\main.py app\routers\web.py
powershell -ExecutionPolicy Bypass -File .\tools\run.ps1 lint "archivos,modificados.php"
powershell -ExecutionPolicy Bypass -File .\tools\run.ps1 health
```

<details>
<summary><strong>🤖 Agentes (Prompt adaptado a rutas de Windows)</strong></summary>

```text
Trabajas en Pulso Cívico sobre Windows (PowerShell). Lee primero `AGENTS.md`.
- Entorno web activo: Python / FastAPI (`app\main.py`), routers en `app\routers\`, servicios en `app\services\` y plantillas Jinja2 en `app\templates\`.
- Datasets JSON en `storage\data\json\`.
- Ejecuta diagnósticos y validaciones mediante el runner centralizado:
  `powershell -ExecutionPolicy Bypass -File .\tools\run.ps1 lint "<archivos.php>"`
  `powershell -ExecutionPolicy Bypass -File .\tools\run.ps1 health`
- Nunca uses `git add .`; agrega solo los archivos modificados.
```
</details>
</details>

<details>
<summary><strong>🍎 macOS (Zsh / Bash)</strong></summary>

```zsh
# Levantar servidor activo en Python (FastAPI + Jinja2)
cp .env.example .env
python3 -m venv .venv && source .venv/bin/activate
pip install -r requirements.txt
uvicorn app.main:app --reload --port 8000

# Verificar sintaxis antes de hacer commit
python3 -m py_compile app/main.py app/routers/web.py
php tools/gateway.php lint "archivos,modificados.php"
php tools/gateway.php health
```

<details>
<summary><strong>🤖 Agentes (Prompt adaptado a rutas de macOS)</strong></summary>

```text
Trabajas en Pulso Cívico sobre macOS. Lee primero `AGENTS.md`.
- Entorno web activo: Python / FastAPI (`app/main.py`), routers en `app/routers/`, servicios en `app/services/` y plantillas Jinja2 en `app/templates/`.
- Datasets JSON en `storage/data/json/`.
- Antes de entregar cambios valida en terminal con:
  `python3 -m py_compile <archivos.py>` y `php tools/gateway.php lint "<archivos.php>"`
- Nunca uses `git add .`; agrega solo los archivos modificados.
```
</details>
</details>

</details>

4. Abre un **Pull Request** llenando la plantilla. Un mantenedor lo revisará.

## Reglas de oro
- **Fuente o no existe**: todo dato nuevo debe incluir `fuente_url` oficial.
- **Neutralidad**: sin opiniones, adjetivos ni sesgo partidista en datos, código o textos.
- **PRs pequeños**: un tema por PR. Más fácil de revisar = se acepta más rápido.
- **Sin secretos**: nunca subas `.env`, cookies, contraseñas ni datos personales.
- **Sin dependencias nuevas** sin discutirlo en un Issue.

## Mensajes de commit
```
feat: agrega vista de asistencias por diputado
fix: corrige conteo de votos en abstención
scraper: agrega Congreso de Nuevo León LXXVII
data: actualiza legisladores LXVI al 2026-10-12
docs: explica formato de votaciones
```

## Reportar un dato erróneo (sin programar)
Abre un Issue con la plantilla **"Dato erróneo"** indicando: político, dato incorrecto, dato correcto y enlace a la fuente oficial.

## Etiquetas de Issues
- `good first issue` — ideal para empezar.
- `fuente-nueva` — falta un scraper.
- `datos` — recolección o validación.
- `ayuda-ia-ok` — tarea bien acotada para resolver con asistente de IA.
- `estado:<nombre>` — relacionado con un estado específico.

## Licencias de tus aportes
Al contribuir aceptas que tu código se publique bajo **AGPL-3.0** y los datos bajo **CC BY 4.0**.
