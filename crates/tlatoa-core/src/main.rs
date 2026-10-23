use std::env;
use std::path::Path;
use std::time::Instant;
use tlatoa_core::TlatoaEngine;

fn main() {
    let args: Vec<String> = env::args().collect();
    let query = if args.len() > 1 {
        args[1].clone()
    } else {
        "claudia".to_string()
    };

    let db_path = env::var("TLATOA_DB_PATH")
        .unwrap_or_else(|_| "../../storage/tlatoa.db".to_string());

    if !Path::new(&db_path).exists() {
        eprintln!("❌ No se encontró la base de datos en: {}", db_path);
        eprintln!("Ejecuta primero: python3 tools/build_mobile_db.py");
        std::process::exit(1);
    }

    println!(">>> Inicializando Tlatoa Core Engine (Rust)...");
    let t_init = Instant::now();
    let engine = match TlatoaEngine::open(&db_path) {
        Ok(e) => e,
        Err(err) => {
            eprintln!("❌ Error al abrir SQLite: {}", err);
            std::process::exit(1);
        }
    };
    println!("⚡ Engine listo en: {:.2?}", t_init.elapsed());

    println!(">>> Ejecutando búsqueda para: \"{}\"", query);
    let t_search = Instant::now();
    match engine.search(&query, 10) {
        Ok(results) => {
            let elapsed = t_search.elapsed();
            println!("⚡ Búsqueda completada en: {:.2?} ({} resultados encontrados)", elapsed, results.len());
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
