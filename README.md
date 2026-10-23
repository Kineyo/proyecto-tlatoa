# Proyecto Tlatoa

> **Plataforma ciudadana independiente, abierta y 100% desconectada (offline) para la fiscalización del poder público en México.**

<p align="center">
  <a href="https://www.rust-lang.org/"><img src="https://img.shields.io/badge/Rust-Core%20Engine-black?style=for-the-badge&logo=rust&logoColor=white" alt="Rust Core Engine"></a>
  <a href="https://kotlinlang.org/"><img src="https://img.shields.io/badge/Kotlin-Android%20Compose-7F52FF?style=for-the-badge&logo=kotlin&logoColor=white" alt="Kotlin Android"></a>
  <a href="https://www.sqlite.org/"><img src="https://img.shields.io/badge/SQLite-FTS5%20Offline-003B57?style=for-the-badge&logo=sqlite&logoColor=white" alt="SQLite FTS5"></a>
  <a href="https://fastapi.tiangolo.com/"><img src="https://img.shields.io/badge/FastAPI-0.110+-009688?style=for-the-badge&logo=fastapi&logoColor=white" alt="FastAPI"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/Licencia-AGPL--3.0-blue.svg?style=for-the-badge" alt="Licencia AGPL-3.0"></a>
  <a href="DATA_LICENSE.md"><img src="https://img.shields.io/badge/Datos-CC%20BY%204.0-lightgrey.svg?style=for-the-badge" alt="CC BY 4.0"></a>
</p>

---

## 📖 ¿Qué es Proyecto Tlatoa? (Explicación para Ciudadanos)

En la lengua náhuatl, *Tlatoa* significa **"el que habla"** o **"hacer escuchar la voz"** (de donde proviene el término histórico *Tlatoani*).

**Proyecto Tlatoa** es una herramienta de consulta pública diseñada para que cualquier persona —sin importar si sabe de computación o no— pueda conocer en **menos de 1 segundo** el historial real de sus representantes populares en México:
* **¿Quién es la persona que está hablando en un mitin o conferencia?**
* **¿A qué partido pertenecía antes y cuántas veces ha cambiado de bancada ("chapulineo")?**
* **¿Qué iniciativas ha presentado y cómo ha votado en las reformas cruciales?**
* **¿Cuál es su grado real de escolaridad y trayectoria en cargos públicos?**

### 🎯 Nuestro Objetivo
Transformar miles de páginas oficiales confusas, gacetas en PDF y acuerdos gubernamentales dispersos en una herramienta visual, clara, neutral y accesible para todos los ciudadanos. **Nuestro único compromiso es con la verdad de los documentos oficiales.**

### ⚙️ ¿Cómo Funciona?
1. **Extracción Oficial:** Recolectamos datos exclusivamente de portales gubernamentales (.gob.mx): Sistema de Información Legislativa (SIL-SEGOB), Cámara de Diputados, Senado, los 32 Congresos Estatales e INEGI.
2. **Cero Opiniones Subjetivas:** No calificamos si un político es "bueno" o "malo". Presentamos exclusivamente hechos verificables con enlace al documento o folio oficial correspondiente.
3. **100% Desconectado (Offline):** La base de datos completa de México viene integrada dentro de la aplicación. Puedes consultar expedientes en un avión, en una carretera sin señal o en un mitin político sin gastar tus datos móviles.

### 🚀 La Meta Final
Garantizar la **soberanía ciudadana de los datos**. Que la información pública sea indestructible, inmune a la censura o al borrado de páginas gubernamentales, y que cualquier ciudadano tenga una copia completa del historial político de México en su propio teléfono o computadora.

---

## 🧭 Índice Rápido de Navegación

* [📱 1. Cómo Descargar y Usar la Aplicación](#-1-cómo-descargar-y-usar-la-aplicación)
  * [Android (.APK directo sin tiendas)](#android-apk-directo-100-offline)
  * [Escritorio Nativo (Windows, Linux, Mac)](#escritorio-nativo-windows-linux-macos)
  * [Servidor Web Local (FastAPI / Python)](#servidor-web-local-python--fastapi)
* [🛠️ Solución de Problemas para el Usuario Final (Troubleshooting)](#️-solución-de-problemas-frecuentes-troubleshooting)
* [🤝 2. Cómo Apoyar al Proyecto](#-2-cómo-apoyar-al-proyecto)
* [📬 3. Cómo Enviar Información o Reportar Errores (Sin saber programar)](#-3-cómo-enviar-información-o-reportar-errores-sin-programar)
* [💻 4. Cómo Aportar con Código y Scrapers (Para desarrolladores)](#-4-cómo-aportar-con-código-y-scrapers-para-desarrolladores)
* [🏛️ 5. Cobertura de Datos y Arquitectura](#-5-cobertura-de-datos-y-arquitectura)
* [⚖️ 6. Licencias y Aviso Legal](#️-6-licencias-y-aviso-legal-agplv3)

> [!TIP]
> **¿Tienes algún problema al instalar la app en Android, Windows o Linux?**  
> Consulta nuestra **[Guía de Solución de Problemas para Usuarios (`docs/SOLUCION_DE_PROBLEMAS.md`)](docs/SOLUCION_DE_PROBLEMAS.md)** con respuestas paso a paso.

---

## 📱 1. Cómo Descargar y Usar la Aplicación

No necesitas instalar programas complejos para consultar la plataforma:

### Android (.APK directo, 100% Offline)
* **Descarga**: Ve a la sección de **[Releases](https://github.com/Kineyo/proyecto-tlatoa/releases)** y descarga el archivo `tlatoa-vX.X.X.apk`.
* **Sin Tiendas**: No requiere Google Play ni suscripciones. Se instala directamente en tu teléfono Android.
* **Velocidad**: Diseñado en **Kotlin + Jetpack Compose** con motor nativo en **Rust**. Búsquedas en menos de 1 milisegundo sin internet.

### Escritorio Nativo (Windows, Linux, macOS)
* Descarga el ejecutable portable para tu sistema operativo desde **[Releases](https://github.com/Kineyo/proyecto-tlatoa/releases)**:
  * **Windows**: `tlatoa-desktop-windows-x86_64.exe` (doble clic y listo).
  * **Linux**: `tlatoa-desktop-linux-x86_64` (dar permisos de ejecución `chmod +x` y ejecutar).
  * **macOS**: `tlatoa-desktop-macos-x86_64`.
* Abre una ventana gráfica instantánea con búsqueda rápida y base de datos local SQLite.

### Servidor Web Local (Python + FastAPI)
Si eres desarrollador y prefieres ejecutar la plataforma web en tu navegador:
```bash
# 1. Copiar configuración de entorno
cp .env.example .env

# 2. Instalar dependencias e iniciar servidor
pip install -r requirements.txt
uvicorn app.main:app --reload --port 8000
```
Abre en tu navegador: `http://localhost:8000`

---

## 🛠️ Solución de Problemas Frecuentes (Troubleshooting)

Si al abrir o instalar la aplicación encuentras algún aviso de seguridad o duda técnica, revisa nuestra guía completa: **[`docs/SOLUCION_DE_PROBLEMAS.md`](docs/SOLUCION_DE_PROBLEMAS.md)**.

* **Android ("Fuentes desconocidas" o "Play Protect"):** Cómo permitir la instalación directa del archivo `.apk` y cómo opera el modo de autorreparación y respaldo SQLite nativo.
* **Windows / Linux ("SmartScreen", permisos o falta de `tlatoa.db`):** Cómo ejecutar el paquete portable `.zip`, dar permisos con `chmod +x` o abrir el navegador manualmente si no se lanza solo.
* **Buscador y Base de Datos:** Cómo recompilar `storage/tlatoa.db` con `python3 tools/build_mobile_db.py` y cómo funciona la búsqueda tolerante a acentos y caracteres especiales.

---

## 🤝 2. Cómo Apoyar al Proyecto

Proyecto Tlatoa es una iniciativa ciudadana sin fines de lucro, sin patrocinio gubernamental ni vínculos con partidos políticos. Puedes apoyar de las siguientes formas:
1. **Difusión Ciudadana**: Comparte la aplicación con periodistas, estudiantes, colectivos vecinales e investigadores.
2. **Auditoría Comunitaria**: Comprueba los datos de tu diputado o presidente municipal contra la gaceta de tu estado. Si detectas un dato desactualizado, repórtalo con su fuente.
3. **Adopción de Congresos Estatales**: Si tienes conocimientos técnicos, ayúdanos a mantener actualizados los extractores de los congresos locales.

---

## 📬 3. Cómo Enviar Información o Reportar Errores (Sin programar)

Si encuentras un cargo incorrecto, una foto desactualizada o una votación faltante:
1. **Regla de Oro**: Toda corrección requiere un enlace oficial (`.gob.mx`, Diario Oficial, periódico acreditado o gaceta).
2. **Abre un Reporte en GitHub**:
   - Reportar dato erróneo: Utiliza la plantilla **[Fe de Erratas](https://github.com/Kineyo/proyecto-tlatoa/issues/new?template=fe_de_erratas.yml)**.
   - Proponer nueva fuente: Utiliza la plantilla **[Nueva Fuente Oficial](https://github.com/Kineyo/proyecto-tlatoa/issues/new?template=nueva_fuente.yml)**.
3. Para consultar el flujo detallado de validación y aportes externos, lee **[`docs/PIPELINE_STAGING.md`](docs/PIPELINE_STAGING.md)** y nuestro **[`docs/MANIFIESTO.md`](docs/MANIFIESTO.md)**.

---

## 💻 4. Cómo Aportar con Código y Scrapers (Para desarrolladores)

Para contribuir código al repositorio se sigue el flujo estándar de **Fork & Pull Request**:

```text
[Repositorio Oficial (upstream)] ──(Fork)──> [Tu Fork (origin)]
             │                                      │
             ▼ (git pull)                           ▼ (git push)
    [Tu Copia Local] ───────(git checkout -b)───> [Tu Rama de Trabajo]
```

### Reglas de Oro de Desarrollo:
1. **Neutralidad Total**: Cero juicios de valor, opiniones o adjetivos en código, comentarios o plantillas.
2. **Archivos Pequeños**: Menos de 400 líneas por archivo.
3. **Commits Convencionales**: Prefijos `feat:`, `fix:`, `scraper:`, `data:`, `docs:`, `chore:`. Nunca uses `git add .`; añade archivo por archivo.
4. **Validación antes de Commit**:
   ```bash
   # Validar sintaxis Python
   python3 -m py_compile app/main.py tools/*.py
   # Validar herramientas y datasets
   php tools/gateway.php health
   ```

### Guías Especializadas para Colaboradores:
* **[Guía para crear un Scraper](docs/COMO_CREAR_UN_SCRAPER.md)**: Estándares para extraer datos de congresos estatales.
* **[Reglas para Asistentes de IA (AGENTS.md)](AGENTS.md)**: Directrices para Cursor, Claude, ChatGPT, Gemini y Copilot.
* **[Esquema y Contratos de Datos (DATA_SCHEMA.md)](docs/DATA_SCHEMA.md)**: Formato canónico de los archivos JSON.
* **[Diccionario de Entidades y Campos](docs/DICCIONARIO_DE_DATOS.md)**: Explicación de campos de políticos y votaciones.

---

## 🏛️ 5. Cobertura de Datos y Arquitectura

* **Poder Legislativo Federal**: Cobertura de las Legislaturas 57 a 66 (1997–2027) de la Cámara de Diputados y el Senado de la República.
* **Congresos Estatales**: Extracción de las 32 legislaturas locales de México.
* **Presidentes Municipales**: Catálogo nacional de los 2,474 alcaldes y alcaldesas de México.
* **Poder Ejecutivo Estatal**: Cronologías de mandatarios de los 32 estados de 1929 a 2030 en [`storage/data/json/ejecutivo/`](storage/data/json/ejecutivo/).
* **Base de Datos Ultrarrápida SQLite**: Generada mediante [`tools/build_mobile_db.py`](tools/build_mobile_db.py) con motor de búsqueda en texto completo FTS5 (< 1 ms).

---

## ⚖️ Licencias y Aviso Legal (AGPLv3)

* **Código de la plataforma:** [GNU Affero General Public License v3.0 (AGPL-3.0)](LICENSE).
* **Datasets de datos abiertos:** [Creative Commons Atribución 4.0 Internacional (CC BY 4.0)](DATA_LICENSE.md).
* **Aviso Legal e Identidad:** [NOTICE.md](NOTICE.md).

<sub>Proyecto Tlatoa es una bifurcación independiente (Hard Fork) iniciada en octubre de 2026 por Kineyo. Código base derivado originalmente de Pulso Cívico (Copyright (c) 2024-2026 Autores Originales), distribuido bajo licencia GNU AGPLv3. Modificaciones y mantenimiento multiplataforma Copyright (c) 2026 Kineyo. Código fuente completo disponible en <a href="https://github.com/Kineyo/proyecto-tlatoa">https://github.com/Kineyo/proyecto-tlatoa</a>. Consulta <a href="NOTICE.md">NOTICE.md</a> y <a href="LICENSE">LICENSE</a>.</sub>
