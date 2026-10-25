use mlua::{Lua, Result as LuaResult};
use std::fs;
use std::path::Path;
use std::time::Instant;

/// Motor de ejecución de Scrapers en Lua embebido dentro del Core de Rust.
/// Permite ejecutar scripts de extracción ligeros (.lua) sin depender de PHP ni Python.
pub struct LuaScraperEngine {
    lua: Lua,
}

impl LuaScraperEngine {
    pub fn new() -> LuaResult<Self> {
        let lua = Lua::new();
        Self::register_tlatoa_module(&lua)?;
        Ok(Self { lua })
    }

    fn register_tlatoa_module(lua: &Lua) -> LuaResult<()> {
        let globals = lua.globals();
        let tlatoa_mod = lua.create_table()?;

        // 1. tlatoa.log(mensaje)
        let log_fn = lua.create_function(|_, msg: String| {
            println!("🦉 [TLATOA-LUA] {}", msg);
            Ok(())
        })?;
        tlatoa_mod.set("log", log_fn)?;

        // 2. tlatoa.http_get(url) -> (body, status_code)
        let http_get_fn = lua.create_function(|_, url: String| {
            match ureq::get(&url).call() {
                Ok(mut res) => {
                    let status = res.status().as_u16() as i32;
                    let body = res.body_mut().read_to_string().unwrap_or_default();
                    Ok((body, status))
                }
                Err(err) => {
                    eprintln!("⚠️ [TLATOA-LUA] Error en GET {}: {}", url, err);
                    Ok((String::new(), 0i32))
                }
            }
        })?;
        tlatoa_mod.set("http_get", http_get_fn)?;

        // 3. tlatoa.read_file(path) -> string
        let read_file_fn = lua.create_function(|_, path: String| {
            match fs::read_to_string(&path) {
                Ok(content) => Ok(content),
                Err(_) => Ok(String::new()),
            }
        })?;
        tlatoa_mod.set("read_file", read_file_fn)?;

        // 4. tlatoa.write_file(path, content) -> bool
        let write_file_fn = lua.create_function(|_, (path, content): (String, String)| {
            let p = Path::new(&path);
            if let Some(parent) = p.parent() {
                let _ = fs::create_dir_all(parent);
            }
            match fs::write(p, content) {
                Ok(_) => Ok(true),
                Err(err) => {
                    eprintln!("❌ [TLATOA-LUA] Error guardando {}: {}", path, err);
                    Ok(false)
                }
            }
        })?;
        tlatoa_mod.set("write_file", write_file_fn)?;

        // 5. tlatoa.slugify(texto) -> string
        let slugify_fn = lua.create_function(|_, text: String| {
            let mut slug = String::new();
            let mut last_dash = true;
            for c in text.to_lowercase().chars() {
                let mapped = match c {
                    'á' | 'à' | 'ä' => 'a',
                    'é' | 'è' | 'ë' => 'e',
                    'í' | 'ì' | 'ï' => 'i',
                    'ó' | 'ò' | 'ö' => 'o',
                    'ú' | 'ù' | 'ü' => 'u',
                    'ñ' => 'n',
                    other => other,
                };
                if mapped.is_alphanumeric() {
                    slug.push(mapped);
                    last_dash = false;
                } else if !last_dash {
                    slug.push('-');
                    last_dash = true;
                }
            }
            if slug.ends_with('-') {
                slug.pop();
            }
            Ok(slug)
        })?;
        tlatoa_mod.set("slugify", slugify_fn)?;

        globals.set("tlatoa", tlatoa_mod)?;
        Ok(())
    }

    /// Ejecuta un archivo de script Lua
    pub fn run_script_file(&self, script_path: &Path) -> LuaResult<()> {
        let code = fs::read_to_string(script_path).map_err(|e| {
            mlua::Error::RuntimeError(format!("No fue posible leer {}: {}", script_path.display(), e))
        })?;

        println!("🦉 Ejecutando scraper Lua: {}", script_path.display());
        let t_start = Instant::now();
        self.lua.load(&code).set_name(script_path.to_string_lossy()).exec()?;
        println!("✨ Scraper completado con éxito en: {:.2?}", t_start.elapsed());
        Ok(())
    }
}
