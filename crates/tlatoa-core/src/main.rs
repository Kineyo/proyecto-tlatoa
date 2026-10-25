use std::env;
use std::path::Path;
use std::time::Instant;
use tlatoa_core::scraper::LuaScraperEngine;
use tlatoa_core::TlatoaEngine;

fn print_banner() {
    println!("🦉 ========================================================");
    println!("   PROYECTO TLATOA — MOTOR HÍBRIDO RUST + LUA (CLI)");
    println!("   Mascota: Tecolote de la Transparencia | Fork Soberano");
    println!("============================================================");
}

fn print_help() {
    print_banner();
    println!("Uso: tlatoa-cli [COMANDO] [OPCIONES]");
    println!();
    println!("Comandos disponibles:");
    println!("  scrape <script.lua>    Ejecuta un scraper en Lua con el runtime nativo");
    println!("  search <termino>       Busca políticos en SQLite FTS5 (<1ms)");
    println!("  status                 Verifica el estado y registros en tlatoa.db");
    println!("  help                   Muestra esta ayuda");
}

fn main() {
    let args: Vec<String> = env::args().collect();
    let command = if args.len() > 1 {
        args[1].as_str()
    } else {
        "help"
    };

    match command {
        "scrape" => {
            if args.len() < 3 {
                eprintln!("❌ Error: Debes especificar la ruta del script Lua.");
                eprintln!("Ejemplo: tlatoa-cli scrape scrapers/template.lua");
                std::process::exit(1);
            }
            let script_path = Path::new(&args[2]);
            if !script_path.exists() {
                eprintln!("❌ El archivo no existe: {}", script_path.display());
                std::process::exit(1);
            }

            match LuaScraperEngine::new() {
                Ok(engine) => {
                    if let Err(e) = engine.run_script_file(script_path) {
                        eprintln!("❌ Error en ejecución de script Lua: {}", e);
                        std::process::exit(1);
                    }
                }
                Err(e) => {
                    eprintln!("❌ No se pudo inicializar el motor de Lua: {}", e);
                    std::process::exit(1);
                }
            }
        }
        "status" => {
            print_banner();
            let db_path = find_db();
            match TlatoaEngine::open(&db_path) {
                Ok(engine) => {
                    println!("📁 Base de datos: {}", db_path);
                    println!("📊 Registros indexados: {}", engine.total_records());
                    println!("⚡ Estado: Listo y funcional (SQLite FTS5 + Rust)");
                }
                Err(err) => {
                    eprintln!("❌ Error abriendo SQLite en {}: {}", db_path, err);
                }
            }
        }
        "search" => {
            let query = if args.len() > 2 {
                args[2..].join(" ")
            } else {
                "claudia".to_string()
            };

            let db_path = find_db();
            let t_init = Instant::now();
            let engine = match TlatoaEngine::open(&db_path) {
                Ok(e) => e,
                Err(err) => {
                    eprintln!("❌ Error al abrir SQLite: {}", err);
                    std::process::exit(1);
                }
            };

            println!("⚡ Engine listo en: {:.2?}", t_init.elapsed());
            println!("🔍 Buscando: \"{}\"", query);
            let t_search = Instant::now();
            match engine.search(&query, 15) {
                Ok(results) => {
                    let elapsed = t_search.elapsed();
                    println!(
                        "⚡ Búsqueda completada en: {:.2?} ({} resultados)",
                        elapsed,
                        results.len()
                    );
                    for (i, p) in results.iter().enumerate() {
                        println!(
                            "   [{}] {} ({}) - {} | {}",
                            i + 1,
                            p.nombre_completo,
                            p.partido,
                            p.cargo,
                            p.entidad
                        );
                    }
                }
                Err(err) => {
                    eprintln!("❌ Error durante la búsqueda: {}", err);
                }
            }
        }
        "help" | "--help" | "-h" => {
            print_help();
        }
        _other => {
            // Si el primer argumento no es un comando reconocido, interpretarlo como búsqueda directa
            let query = args[1..].join(" ");
            let db_path = find_db();
            let engine = match TlatoaEngine::open(&db_path) {
                Ok(e) => e,
                Err(err) => {
                    eprintln!("❌ Error al abrir SQLite: {}", err);
                    std::process::exit(1);
                }
            };
            println!("🔍 Buscando: \"{}\"", query);
            match engine.search(&query, 10) {
                Ok(results) => {
                    for (i, p) in results.iter().enumerate() {
                        println!(
                            "   [{}] {} ({}) - {} | {}",
                            i + 1,
                            p.nombre_completo,
                            p.partido,
                            p.cargo,
                            p.entidad
                        );
                    }
                }
                Err(err) => {
                    eprintln!("❌ Error durante la búsqueda: {}", err);
                }
            }
        }
    }
}

fn find_db() -> String {
    let env_db = env::var("TLATOA_DB_PATH").unwrap_or_default();
    if !env_db.is_empty() && Path::new(&env_db).exists() {
        return env_db;
    }
    let candidates = [
        "storage/tlatoa.db",
        "../../storage/tlatoa.db",
        "../storage/tlatoa.db",
        "tlatoa.db",
    ];
    for c in candidates {
        if Path::new(c).exists() {
            return c.to_string();
        }
    }
    "storage/tlatoa.db".to_string()
}
