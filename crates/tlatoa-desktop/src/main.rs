use std::path::PathBuf;
use std::sync::Arc;
use std::thread;
use std::time::Duration;
use tiny_http::{Header, Response, Server, StatusCode};
use tlatoa_core::TlatoaEngine;

const UI_HTML: &str = include_str!("ui.html");

fn find_database_path() -> PathBuf {
    // 1. Argumento CLI --db <ruta>
    let args: Vec<String> = std::env::args().collect();
    for i in 0..args.len() {
        if args[i] == "--db" && i + 1 < args.len() {
            let p = PathBuf::from(&args[i + 1]);
            if p.exists() {
                return p;
            }
        }
    }

    // 2. Variable de entorno TLATOA_DB_PATH
    if let Ok(env_db) = std::env::var("TLATOA_DB_PATH") {
        let p = PathBuf::from(env_db);
        if p.exists() {
            return p;
        }
    }

    // 3. Junto al ejecutable o en sus carpetas padre (distribución portable)
    if let Ok(exe_path) = std::env::current_exe() {
        if let Some(exe_dir) = exe_path.parent() {
            let exe_candidates = [
                exe_dir.join("tlatoa.db"),
                exe_dir.join("storage").join("tlatoa.db"),
                exe_dir.join("..").join("tlatoa.db"),
                exe_dir.join("..").join("storage").join("tlatoa.db"),
                exe_dir.join("../..").join("storage").join("tlatoa.db"),
                exe_dir.join("../../..").join("storage").join("tlatoa.db"),
            ];
            for candidate in &exe_candidates {
                if candidate.exists() {
                    return candidate.clone();
                }
            }
        }
    }

    // 4. Rutas relativas al directorio actual de trabajo
    let cwd_candidates = [
        PathBuf::from("storage/tlatoa.db"),
        PathBuf::from("tlatoa.db"),
        PathBuf::from("../storage/tlatoa.db"),
        PathBuf::from("../../storage/tlatoa.db"),
    ];
    for c in &cwd_candidates {
        if c.exists() {
            return c.clone();
        }
    }

    // 5. Directorio de datos del usuario (Linux/macOS HOME o Windows APPDATA)
    if let Ok(home) = std::env::var("HOME") {
        let user_db = PathBuf::from(home).join(".local/share/tlatoa/tlatoa.db");
        if user_db.exists() {
            return user_db;
        }
    }
    if let Ok(appdata) = std::env::var("APPDATA") {
        let win_db = PathBuf::from(appdata).join("tlatoa").join("tlatoa.db");
        if win_db.exists() {
            return win_db;
        }
    }

    PathBuf::from("storage/tlatoa.db")
}

fn parse_query_param(url: &str, param: &str) -> Option<String> {
    let query_start = url.find('?')?;
    let query_str = &url[query_start + 1..];
    for pair in query_str.split('&') {
        let mut parts = pair.splitn(2, '=');
        if let (Some(k), Some(v)) = (parts.next(), parts.next()) {
            if k == param {
                return Some(urlencoding_decode(v));
            }
        }
    }
    None
}

/// Decodificador URL seguro para secuencias UTF-8 multibyte (ñ, á, é, í, ó, ú, ü).
fn urlencoding_decode(s: &str) -> String {
    let mut raw_bytes = Vec::with_capacity(s.len());
    let mut bytes = s.bytes();
    while let Some(b) = bytes.next() {
        if b == b'%' {
            let h1 = bytes.next();
            let h2 = bytes.next();
            if let (Some(c1), Some(c2)) = (h1, h2) {
                let hex = [c1, c2];
                if let Ok(hex_str) = std::str::from_utf8(&hex) {
                    if let Ok(byte_val) = u8::from_str_radix(hex_str, 16) {
                        raw_bytes.push(byte_val);
                        continue;
                    }
                }
            }
            raw_bytes.push(b'%');
        } else if b == b'+' {
            raw_bytes.push(b' ');
        } else {
            raw_bytes.push(b);
        }
    }
    String::from_utf8_lossy(&raw_bytes).into_owned()
}

fn bind_server_with_fallback() -> Server {
    if let Ok(port_str) = std::env::var("TLATOA_PORT") {
        let addr = format!("127.0.0.1:{}", port_str.trim());
        if let Ok(srv) = Server::http(&addr) {
            return srv;
        }
        eprintln!(
            "⚠️ El puerto solicitado ({}) está ocupado o no es válido. Activando puerto dinámico automático...",
            addr
        );
    }

    if let Ok(srv) = Server::http("127.0.0.1:0") {
        return srv;
    }

    for fallback_port in 8080..=8095 {
        let addr = format!("127.0.0.1:{}", fallback_port);
        if let Ok(srv) = Server::http(&addr) {
            return srv;
        }
    }

    panic!("No fue posible abrir un socket local en 127.0.0.1. Verifica tu firewall local.");
}

fn main() {
    let db_path = find_database_path();
    println!(">>> Inicializando Proyecto Tlatoa (Escritorio Offline)...");

    let engine = match TlatoaEngine::open_or_fallback(db_path.to_str().unwrap_or("storage/tlatoa.db")) {
        Ok(e) => {
            if e.is_fallback() {
                eprintln!(
                    "⚠️ Aviso: No se encontró '{}' en disco. Iniciando en modo diagnóstico de respaldo.",
                    db_path.display()
                );
                eprintln!("   Para cargar todos los datos coloca 'tlatoa.db' junto al ejecutable o ejecuta:");
                eprintln!("   python3 tools/build_mobile_db.py");
            }
            Arc::new(e)
        }
        Err(err) => {
            eprintln!("❌ Error crítico al inicializar motor SQLite: {}", err);
            std::process::exit(1);
        }
    };

    let server = bind_server_with_fallback();
    let port = server.server_addr().to_ip().map(|ip| ip.port()).unwrap_or(8080);
    let app_url = format!("http://127.0.0.1:{}", port);

    println!("========================================================");
    println!(" 🇲🇽 PROYECTO TLATOA — APLICACIÓN DE ESCRITORIO OFFLINE");
    println!("========================================================");
    println!(" ⚡ Motor: Rust Core Engine + SQLite FTS5 (< 1ms latencia)");
    println!(" 📊 Registros cargados: {}", engine.total_records());
    println!(" 🌐 Servidor Nativo: {}", app_url);
    println!(" 🔒 100% Desconectado: Cero dependencias externas");
    println!("========================================================");
    println!(" Presiona Ctrl+C en cualquier momento para salir.");

    let no_browser = std::env::var("TLATOA_NO_BROWSER").unwrap_or_default() == "1"
        || std::env::args().any(|a| a == "--no-browser");
    if !no_browser {
        let open_url = app_url.clone();
        thread::spawn(move || {
            thread::sleep(Duration::from_millis(250));
            if webbrowser::open(&open_url).is_err() {
                eprintln!(
                    "ℹ️ Abre manualmente en tu navegador la dirección: {}",
                    open_url
                );
            }
        });
    }

    let header_html =
        Header::from_bytes(&b"Content-Type"[..], &b"text/html; charset=utf-8"[..]).unwrap();
    let header_json =
        Header::from_bytes(&b"Content-Type"[..], &b"application/json; charset=utf-8"[..]).unwrap();

    for request in server.incoming_requests() {
        let url = request.url().to_string();

        if url == "/" || url.starts_with("/index") {
            let response = Response::from_string(UI_HTML).with_header(header_html.clone());
            let _ = request.respond(response);
            continue;
        }

        if url.starts_with("/api/status") {
            let status_body = serde_json::json!({
                "ok": !engine.is_fallback(),
                "fallback_mode": engine.is_fallback(),
                "total_records": engine.total_records(),
                "db_path": db_path.display().to_string()
            });
            let response =
                Response::from_string(status_body.to_string()).with_header(header_json.clone());
            let _ = request.respond(response);
            continue;
        }

        if url.starts_with("/api/buscar") {
            let query = parse_query_param(&url, "q").unwrap_or_default();
            let limit = parse_query_param(&url, "limit")
                .and_then(|l| l.parse::<usize>().ok())
                .unwrap_or(40);

            let results = engine.search(&query, limit).unwrap_or_default();
            let json_body = serde_json::json!({
                "query": query,
                "total": results.len(),
                "fallback_mode": engine.is_fallback(),
                "resultados": results
            });
            let response =
                Response::from_string(json_body.to_string()).with_header(header_json.clone());
            let _ = request.respond(response);
            continue;
        }

        if url.starts_with("/api/politico") {
            let slug = parse_query_param(&url, "slug").unwrap_or_default();
            let detail = engine.get_by_slug(&slug).unwrap_or(None);
            let response_str = detail.unwrap_or_else(|| "null".to_string());
            let response = Response::from_string(response_str).with_header(header_json.clone());
            let _ = request.respond(response);
            continue;
        }

        let response = Response::from_string("404 Not Found").with_status_code(StatusCode(404));
        let _ = request.respond(response);
    }
}
