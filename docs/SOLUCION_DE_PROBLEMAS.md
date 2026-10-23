# 🛠️ Guía de Solución de Problemas (Troubleshooting) — Proyecto Tlatoa

Esta guía está diseñada para ayudar a **ciudadanos, periodistas y usuarios finales** (sin necesidad de conocimientos técnicos) así como a **colaboradores** a resolver cualquier falla al instalar o usar **Proyecto Tlatoa** en teléfonos Android, computadoras de escritorio o entorno web.

---

## 📑 Índice Rápido de Soluciones

1. [📱 Problemas en la App de Android (APK)](#1--problemas-en-la-app-de-android-apk)
2. [💻 Problemas en la App de Escritorio (Windows, Linux y macOS)](#2--problemas-en-la-app-de-escritorio-windows-linux-y-macos)
3. [🔍 Problemas con el Buscador o Datos Faltantes (`tlatoa.db`)](#3--problemas-con-el-buscador-o-datos-faltantes-tlatoadb)
4. [🌐 Problemas en el Servidor Web Local (Python / FastAPI)](#4--problemas-en-el-servidor-web-local-python--fastapi)

---

## 1. 📱 Problemas en la App de Android (APK)

### 1.1. "Por motivos de seguridad, tu teléfono no puede instalar aplicaciones desconocidas"
* **Causa:** Android bloquea por defecto las aplicaciones descargadas fuera de Google Play Store.
* **Solución paso a paso:**
  1. Al tocar el archivo `tlatoa-android.apk` descargado, presiona **"Configuración"** en el aviso emergente.
  2. Activa la casilla **"Confiar en esta fuente"** o **"Permitir desde esta fuente"**.
  3. Regresa atrás y presiona **"Instalar"**.

### 1.2. "Google Play Protect bloqueó la instalación" o "App no reconocida"
* **Causa:** Como Proyecto Tlatoa se distribuye de forma libre y gratuita desde GitHub Releases (sin pagar cuentas corporativas de tiendas), Play Protect muestra una advertencia estándar para apps independientes.
* **Solución:**
  1. En la ventana del aviso, toca en **"Más detalles"** (flecha hacia abajo).
  2. Toca en **"Instalar de todas formas"** (*Install anyway*).

### 1.3. "No se pudo instalar la aplicación / El paquete parece no ser válido"
* **Causa:** La descarga del archivo `.apk` quedó incompleta o tienes instalada una versión previa con firma distinta.
* **Solución:**
  1. Desinstala cualquier versión anterior de **Proyecto Tlatoa** de tu teléfono.
  2. Verifica que tengas al menos **100 MB de espacio libre** en tu almacenamiento interno.
  3. Vuelve a descargar el archivo `.apk` desde la sección de [Releases en GitHub](https://github.com/Kineyo/proyecto-tlatoa/releases).

### 1.4. La app abre pero no muestra resultados al buscar
* **Mecanismo de autorreparación integrado:**
  - En el primer arranque, la aplicación desempaqueta automáticamente la base de datos desconectada (`tlatoa.db`). Si la app se cerró accidentalmente a mitad de ese primer arranque, simplemente **ciérrala y vuélvela a abrir**: el sistema detecta archivos incompletos (`< 1 KB`) y los reconstruye automáticamente.
  - Si tu teléfono tiene un procesador poco común donde la librería ultrarrápida en Rust (`libtlatoa_core.so`) no pueda cargarse, la app **activa automáticamente el motor SQLite nativo de Android** como respaldo (*fallback*) sin cerrarse.

---

## 2. 💻 Problemas en la App de Escritorio (Windows, Linux y macOS)

### 2.1. En Windows: "Windows protegió su PC (Microsoft Defender SmartScreen)"
* **Causa:** El ejecutable `tlatoa-desktop-windows-x86_64.exe` es compilado de forma abierta en GitHub Actions sin certificados comerciales de pago.
* **Solución:**
  1. Haz clic en el texto subrayado **"Más información"** (*More info*).
  2. Haz clic en el botón **"Ejecutar de todas formas"** (*Run anyway*).

### 2.2. Aparece el aviso: `⚠️ Base de datos local (tlatoa.db) no encontrada`
* **Causa:** Ejecutaste el programa suelto sin tener el archivo `tlatoa.db` en la misma carpeta.
* **Cómo funciona el modo de respaldo:** En lugar de cerrarse o fallar, la aplicación inicia en **Modo Diagnóstico de Respaldo** y te muestra las instrucciones en pantalla.
* **Solución para usuarios finales:**
  1. Descarga el paquete **`portable.zip`** desde [GitHub Releases](https://github.com/Kineyo/proyecto-tlatoa/releases) y extrae **ambos archivos** (`tlatoa-desktop` y `tlatoa.db`) dentro de la misma carpeta.
  2. También puedes indicarle manualmente dónde está la base de datos ejecutando:
     ```bash
     ./tlatoa-desktop --db /ruta/a/tu/tlatoa.db
     ```
* **Solución para desarrolladores (desde el código fuente):**
  Genera la base de datos ejecutando en la raíz del proyecto:
  ```bash
  python3 tools/build_mobile_db.py
  ```

### 2.3. En Linux o macOS: "Permiso denegado" (`Permission denied`) al abrir el binario
* **Causa:** Los sistemas UNIX quitan el permiso de ejecución a los archivos recién descargados de internet.
* **Solución:** Abre una terminal en la carpeta donde descargaste el archivo y ejecuta:
  ```bash
  chmod +x tlatoa-desktop-linux-x86_64
  ./tlatoa-desktop-linux-x86_64
  ```

### 2.4. El programa inicia pero no se abre la ventana del navegador automáticamente
* **Causa:** En algunos entornos de escritorio (como Linux sin `xdg-open` o sesiones remotas), el sistema operativo no lanza el navegador por sí solo.
* **Solución:**
  1. Observa la ventana de terminal del programa; verás una línea como:
     `🌐 Servidor Nativo: http://127.0.0.1:44805`
  2. Copia esa dirección `http://127.0.0.1:...` y pégala en cualquier navegador web instalado en tu equipo (Firefox, Chrome, Brave, Edge). No requiere conexión a internet.
  3. Si deseas fijar un puerto específico (por ejemplo, `8080`), puedes definir la variable `TLATOA_PORT=8080`:
     ```bash
     TLATOA_PORT=8080 ./tlatoa-desktop
     ```
     *(Si el puerto elegido está ocupado por otro programa, Proyecto Tlatoa cambiará automáticamente a un puerto libre sin fallar).*

---

## 3. 🔍 Problemas con el Buscador o Datos Faltantes (`tlatoa.db`)

### 3.1. Busco con acentos, letra "ñ" o signos especiales (guiones, paréntesis) y no falla
* **Resiliencia integrada:**
  - El motor decodifica secuencias UTF-8 completas (`ñ`, `á`, `é`, `í`, `ó`, `ú`, `ü`) y normaliza diacríticos (`unicode61 remove_diacritics 2`), por lo que buscar `"Noroña"` o `"norona"` arroja exactamente los mismos resultados.
  - Si escribes símbolos especiales como `"Morena - (CDMX)"`, el sanitizador FTS5 limpia automáticamente los operadores reservados y, si la búsqueda por prefijo no encuentra coincidencias, **activa automáticamente una búsqueda de respaldo por subcadenas (`SQL LIKE`)**.

### 3.2. Agregué nuevos archivos JSON pero no aparecen en la app de escritorio o móvil
* **Causa:** Las aplicaciones nativas leen el archivo compilado `storage/tlatoa.db`, no los miles de JSON sueltos directamente.
* **Solución:** Vuelve a compilar la base de datos SQLite corriendo:
  ```bash
  python3 tools/build_mobile_db.py
  ```

---

## 4. 🌐 Problemas en el Servidor Web Local (Python / FastAPI)

### 4.1. Error `ModuleNotFoundError: No module named 'orjson'`, `'pydantic_settings'` o `'PIL'`
* **Fallbacks automáticos integrados:**
  - Si tu entorno de Python no cuenta con el paquete binario `orjson`, el servicio [`app/services/data_service.py`](../app/services/data_service.py) cambia automáticamente a la librería estándar `json` de Python.
  - Si no tienes `pydantic-settings`, [`app/core/config.py`](../app/core/config.py) carga automáticamente las variables desde `.env` y el sistema operativo.
  - Si `Pillow` (`PIL`) o la librería del sistema `libwebp` no están instalados al subir un acta electoral, [`app/services/image_service.py`](../app/services/image_service.py) degrada con elegancia a formato `JPEG` o resguardo binario con hash SHA-256 sin detener el servidor.

### 4.2. El comando `php tools/gateway.php health` muestra `healthy: false`
* **Causa:** El chequeo de salud audita tanto la sintaxis del código como que todos los directorios de `storage/` existan y que los 33,000+ registros históricos tengan `fuente_url`.
* **Solución:**
  1. Revisa que `"syntax_errors_count": 0` esté en cero.
  2. Si algún directorio como `storage/raw/html` aparece con `"exists": false`, créalo con:
     ```bash
     mkdir -p storage/logs storage/data/json storage/raw/html storage/raw/photos
     ```

---

## 🆘 ¿Tu problema no aparece aquí?

Abre un reporte en la sección de **[Issues de GitHub](https://github.com/Kineyo/proyecto-tlatoa/issues)** indicando:
1. En qué dispositivo estás (Android, Windows, Linux o macOS).
2. Qué estabas buscando o haciendo cuando ocurrió el problema.
3. Una captura de pantalla o el mensaje que apareció en pantalla.
