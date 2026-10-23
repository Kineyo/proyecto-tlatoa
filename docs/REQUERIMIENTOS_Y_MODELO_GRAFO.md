# DOCUMENTO DE ANÁLISIS, REQUERIMIENTOS Y MODELO DE GRAFO CIUDADANO
## Proyecto Tlatoa — Plataforma de Transparencia Legislativa y Grafo de Poder
**Versión:** 1.0 | **Fecha:** Octubre 2026 | **Ámbito:** Nivel Federal (Cámara de Diputados y Senadores)

---

## 1. INTRODUCCIÓN Y VISIÓN DEL SISTEMA
**Proyecto Tlatoa** es un ecosistema ciudadano digital de alto rendimiento diseñado para transformar la opacidad de los datos parlamentarios de México en un **Grafo de Conocimiento Interconectado (Knowledge Graph)** navegable, verificable y antipartidista.

A diferencia de un directorio tradicional estático, la plataforma modela a los actores políticos y sus relaciones como **nodos y aristas dinámicas a través del tiempo**.

---

## 2. REGLA FUNDAMENTAL DE NEGOCIO: POLÍTICOS INVARIANTES VS. MANDATOS TEMPORALES
Uno de los descubrimientos y requisitos clave del sistema es que **la identidad de un político trasciende a una sola legislatura**:
1. Un político puede haber sido **Suplente** en la LXIV Legislatura (2018–2021).
2. Luego convertirse en **Diputado Propietario** por un partido en la LXV Legislatura (2021–2024).
3. Y finalmente ser **Senador Propietario** por otro partido en la LXVI Legislatura (2024–2027).

### Solución Arquitectónica: Desacoplamiento Persona vs. Mandato
* **Entidad `politicos` (Persona Invariante):** Representa a la persona física (nombre oficial, fecha de nacimiento, escolaridad, biografía, foto oficial principal, slug SEO permanente).
* **Entidad `mandatos_legislativos` (Cargo Temporal):** Representa la función específica de esa persona en una legislatura determinada:
  * Cámara (Diputados / Senadores).
  * Partido en esa legislatura (permite auditar el *Detector de Chapulines*).
  * Principio de elección (Mayoría Relativa, Plurinominal, Primera Minoría).
  * Entidad Federativa y Distrito.
  * Rol en el mandato: **Propietario** o **Suplente**.
  * Si es suplente: Vínculo directo al mandato titular que cubre (`titular_mandato_id`).

---

## 3. EL MODELO DE GRAFO DE NAVEGACIÓN (TODO ES UN HIPERVÍNCULO)

Todo elemento relevante en la plataforma constituye un nodo navegable:

```
[LEGISLATURA: LXVI] ── reúne a ──► [MANDATO LEGISLATIVO] ◄── desempeñado por ── [POLÍTICO / PERSONA]
                                          │
            ┌─────────────────────────────┼─────────────────────────────┐
            │                             │                             │
    adscrito a                    asignado a                     es suplente de
            ▼                             ▼                             ▼
   [PARTIDO POLÍTICO]            [COMISIÓN LEGISLATIVA]          [MANDATO PROPIETARIO]
    (/partido/morena)             (/comision/hacienda)           (/politico/titular-slug)
            │                             │                             │
    agrupa mandatos               reúne integrantes              revela dinastías,
    y mide disciplina             y dictamina leyes             pactos y relevos
```

### Rutas Canónicas del Grafo:
* `/politico/{slug}`: Radiografía del político, historial de todos sus mandatos (titular o suplente), comisiones, votaciones y noticias.
* `/comision/{slug}`: Ficha de la comisión, mesa directiva (presidente, secretarios), lista completa de integrantes y partidos participantes.
* `/legislatura/{slug}`: Vista panorámica de los 628 legisladores de esa asamblea, balance de bancadas y mapa interactivo.
* `/partido/{slug}`: Distribución de fuerzas, índice promedio de disciplina de bancada y deserciones históricas.
* `/estado/{slug}`: Senadores de la entidad y diputados clasificados por distrito federal.

---

## 4. REQUERIMIENTOS FUNCIONALES (RF)

* **RF-01 (Perfil Integral del Político):** Visualización de datos biográficos, contacto oficial, fotografía en alta resolución y semáforos de desempeño precalculados.
* **RF-02 (Red Bidireccional de Suplencias):** Si un perfil es propietario, mostrar enlace con foto y nombre de su suplente. Si es suplente, mostrar enlace al propietario y el periodo en que asumió funciones.
* **RF-03 (Navegación de Comisiones):** Cada comisión lista a sus integrantes con su cargo (Presidente, Secretario, Integrante) e hipervínculos bidireccionales.
* **RF-04 (Línea de Tiempo de Militancia / Chapulineo):** Registro cronológico de cambios de grupo parlamentario a lo largo de las legislaturas con citas de actas oficiales.
* **RF-05 (Auditoría de Ingesta):** Cada registro debe contar con su identificador oficial en el SIL (`referencia_sil`) y marca de tiempo de extracción.
* **RF-06 (Búsqueda Predictiva de Alta Velocidad):** Búsqueda instantánea por nombre, apodo, estado, distrito o comisión en menos de 50 ms.

---

## 5. REQUERIMIENTOS NO FUNCIONALES (RNF)

* **RNF-01 (Rendimiento Extremo):** Carga inicial de página (SSR) en menos de 500 ms en redes 3G móviles. Consultas a base de datos resueltas en memoria (<5 ms).
* **RNF-02 (Arquitectura Sin Dependencias Pesadas):** PHP 8.2 nativo con tipado estricto, conexión PDO Singleton y Tailwind CSS minificado.
* **RNF-03 (SEO y Rich Snippets):** Metadatos automáticos Open Graph (con tarjetas dinámicas para WhatsApp/Twitter) y marcado Schema.org (`Person`, `GovernmentOrganization`).
* **RNF-04 (Blindaje Legal Art. 6°):** Todo dato numérico o de filiación debe referenciar su fuente oficial gubernamental. Cero adjetivos difamatorios en el código o base de datos.
