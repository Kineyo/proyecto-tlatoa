use std::env;
use std::path::PathBuf;
use std::sync::Arc;
use std::thread;
use std::time::Duration;
use tiny_http::{Header, Response, Server, StatusCode};
use tlatoa_core::TlatoaEngine;

const UI_HTML: &str = include_str!("ui.html");

fn find_database_path() -> PathBuf {
    // 1. Variable de entorno explícita
    if let Ok(env_path) = env::var("TLATOA_DB_PATH") {
        let p = PathBuf::from(env_path);
        if p.exists() {
            return p;
        }
    }
    // 2. Ruta relativa al directorio de trabajo
    let candidates = [
        PathBuf::from("storage/tlatoa.db"),
        PathBuf::from("../storage/tlatoa.db"),
        PathBuf::from("../../storage/tlatoa.db"),
        PathBuf::from("tlatoa.db"),
    ];
    for c in &candidates {
        if c.exists() {
            return c.clone();
        }
    }
    PathBuf::from("storage/tlatoa.db")
}

fn parse_query_param<'a>(url: &'a str, param: &str) -> Option<String> {
    let query_start = url.find('?')?;
    let query_str = &url[query_start + 1..];
    for pair in query_str.split('&') {
        let mut parts = pair.splitn(2, '=');
        if let (Some(k), Some(v)) = (parts.next(), parts.next()) {
            if k == param {
                let decoded = urlencoding_decode(v);
                return Some(decoded);
            }
        }
    }
    None
}

fn urlencoding_decode(s: &str) -> String {
    let mut result = String::with_capacity(s.len());
    let mut bytes = s.bytes();
    while let Some(b) = bytes.next() {
        if b == b'%' {
            let h1 = bytes.next().unwrap_or(b'0') as char;
            let h2 = bytes.next().unwrap_or(b'0') as char;
            if let Ok(byte_val) = u8::from_str_radix(&format!("{}{}", h1, h2), 16) {
                result.push(byte_val as char);
            }
        } else if b == b'+' {
            result.push(' ');
        } else {
            result.push(b as char);
        }
    }
    result
}

fn main() {
    let db_path = find_database_path();
    if !db_path.exists() {
        eprintln!("❌ No se encontró la base de datos en: {}", db_path.display());
        eprintln!("Por favor compila primero los datos con: python3 tools/build_mobile_db.py");
        std::process::exit(1);
    }

    println!(">>> Inicializando Proyecto Tlatoa (Escritorio Offline)...");
    let engine = match TlatoaEngine::open(db_path.to_str().unwrap_or("storage/tlatoa.db")) {
        Ok(e) => Arc::new(e),
        Err(err) => {
            eprintln!("❌ Error al cargar motor SQLite: {}", err);
            std::process::exit(1);
        }
    };

    let bind_addr = std::env::var("TLATOA_PORT")
        .map(|p| format!("127.0.0.1:{}", p))
        .unwrap_or_else(|_| "127.0.0.1:0".to_string());
    let server = Server::http(&bind_addr).expect("Fallo al iniciar servidor loopback");
    let port = server.server_addr().to_ip().map(|ip| ip.port()).unwrap_or(8080);
    let app_url = format!("http://127.0.0.1:{}", port);

    println!("========================================================");
    println!(" 🇲🇽 PROYECTO TLATOA — APLICACIÓN DE ESCRITORIO OFFLINE");
    println!("========================================================");
    println!(" ⚡ Motor: Rust Core Engine + SQLite FTS5 (< 1ms latencia)");
    println!(" 🌐 Servidor Nativo: {}", app_url);
    println!(" 🔒 100% Desconectado: Cero dependencias externas");
    println!("========================================================");
    println!(" Presiona Ctrl+C en cualquier momento para salir.");

    // Abrir ventana en el sistema operativo
    let open_url = app_url.clone();
    thread::spawn(move || {
        thread::sleep(Duration::from_millis(250));
        let _ = webbrowser::open(&open_url);
    });

    let header_html = Header::from_bytes(&b"Content-Type"[..], &b"text/html; charset=utf-8"[..]).unwrap();
    let header_json = Header::from_bytes(&b"Content-Type"[..], &b"application/json; charset=utf-8"[..]).unwrap();

    for request in server.incoming_requests() {
        let url = request.url().to_string();

        if url == "/" || url.starts_with("/index") {
            let response = Response::from_string(UI_HTML).with_header(header_html.clone());
            let _ = request.respond(response);
            continue;
        }

        if url.starts_with("/api/buscar") {
            let query = parse_query_param(&url, "q").unwrap_or_default();
            let limit = parse_query_param(&url, "limit")
                .and_then(|l| l.parse::<usize>().ok())
                .unwrap_or(20);

            let results = engine.search(&query, limit).unwrap_or_default();
            let json_body = serde_json::json!({
                "query": query,
                "total": results.len(),
                "resultados": results
            });
            let response = Response::from_string(json_body.to_string())
                .with_header(header_json.clone());
            let _ = request.respond(response);
            continue;
        }

        if url.starts_with("/api/politico") {
            let slug = parse_query_param(&url, "slug").unwrap_or_default();
            let detail = engine.get_by_slug(&slug).unwrap_or(None);
            let response_str = detail.unwrap_or_else(|| "null".to_string());
            let response = Response::from_string(response_str)
                .with_header(header_json.clone());
            let _ = request.respond(response);
            continue;
        }

        let response = Response::from_string("404 Not Found").with_status_code(StatusCode(404));
        let _ = request.respond(response);
    }
}
