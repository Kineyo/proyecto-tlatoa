# Historial de Migración y Auditoría de Datos

> **Registro de Auditoría Técnica** — Transición de PHP a Python (FastAPI) y corrección estructural de datasets parlamentarios.

---

## 1. Resumen Ejecutivo

La plataforma completó la transición desde una base legacy en PHP hacia una arquitectura moderna, asíncrona y modular basada en **Python 3.10+ + FastAPI + Jinja2 + orjson**, con motor offline complementario en **Rust** (SQLite FTS5) y frontend móvil nativo en **Kotlin (Android)**.

---

## 2. Anomalías Estructurales Auditadas y Resueltas

### A. Descontaminación de Legislaturas Federales
* **Problema Previo:** La búsqueda amplia `congreso_*_leg_*.json` incluía congresos locales que comparten numeración (como Chihuahua LXVI o Aguascalientes LXVI), inflando artificialmente el conteo de legisladores federales a más de 780 integrantes.
* **Solución:** Ingesta restringida a `congreso_union_leg_*.json` y `legisladores_entidad_*_leg_*.json` con validación estricta de cámara (`Diputados` o `Senadores`). Conteos federales normalizados al 100%.

### B. Mapeo de Entidades Históricas (DF / Ciudad de México)
* **Problema Previo:** Registros de las legislaturas LXI y LXII clasificaban a la capital como `"Distrito Federal"` o `"DF"`, dejando el mapa histórico de la CDMX en cero representantes.
* **Solución:** Función `normalizar_entidad()` que homologa variantes hacia `"ciudad-de-mexico"` con etiqueta legible `"Ciudad de México"`.

### C. Persistencia de Historiales Multi-Legislatura
* **Problema Previo:** La indexación basada únicamente en el slug sobreescribía la trayectoria previa de más de 470 legisladores con mandatos en múltiples asambleas.
* **Solución:** Estructura `mandatos_historicos` cronológica en `DataService` y visualizador de bancadas y comisiones por periodo en `app/templates/politico.html`.

### D. Optimización de Endpoints y Búsqueda en Memoria
* **Problema Previo:** Carga lenta de archivos JSON individuales por petición.
* **Solución:** Indexación en memoria RAM al inicio del servidor FastAPI mediante `orjson` (< 15 ms por consulta) y compilación periódica a SQLite FTS5 (`tools/build_mobile_db.py`).

---

## 🗺️ Navegación y Conexiones (Obsidian Graph)

* [[README|Índice Principal de Proyecto Tlatoa]]
* [[ARQUITECTURA|Arquitectura del Sistema y Grafo de Conocimiento]]
* [[DATA_SCHEMA|Contratos de Datos y Esquemas JSON]]
* [[PLAN_RESILIENCIA_Y_PRESERVACION|Protocolo de Resiliencia y Preservación Digital]]
