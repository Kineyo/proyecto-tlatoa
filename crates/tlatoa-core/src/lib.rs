use rusqlite::{params, Connection};
use serde::{Deserialize, Serialize};
use std::sync::Mutex;

pub mod scraper;

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct PoliticoSummary {
    pub slug: String,
    pub nombre_completo: String,
    pub partido: String,
    pub partido_color: String,
    pub cargo: String,
    pub camara: String,
    pub entidad: String,
    pub foto_url: String,
}

pub struct TlatoaEngine {
    conn: Connection,
    fallback_mode: bool,
}

/// Sanitiza cualquier entrada del usuario para evitar errores de sintaxis en SQLite FTS5
/// (como guiones, paréntesis, dos puntos, comillas o palabras reservadas AND/OR/NOT/NEAR).
pub fn build_fts5_query(raw: &str) -> Option<String> {
    let mut tokens = Vec::new();
    for word in raw.split_whitespace() {
        let cleaned: String = word
            .chars()
            .filter(|c| c.is_alphanumeric())
            .collect();
        if cleaned.is_empty() {
            continue;
        }
        let upper = cleaned.to_uppercase();
        if matches!(upper.as_str(), "AND" | "OR" | "NOT" | "NEAR") {
            continue;
        }
        tokens.push(format!("\"{}\"*", cleaned));
    }
    if tokens.is_empty() {
        None
    } else {
        Some(tokens.join(" "))
    }
}

impl TlatoaEngine {
    pub fn open(db_path: &str) -> Result<Self, rusqlite::Error> {
        let conn = Connection::open(db_path)?;
        let _ = conn.execute_batch(
            "PRAGMA query_only = 1;
             PRAGMA mmap_size = 268435456;
             PRAGMA cache_size = -8000;",
        );
        // Verificar que la tabla principal exista
        let count: i64 = conn.query_row("SELECT count(*) FROM politicos", [], |r| r.get(0))?;
        if count == 0 {
            return Err(rusqlite::Error::QueryReturnedNoRows);
        }
        Ok(Self {
            conn,
            fallback_mode: false,
        })
    }

    /// Fallback resiliente en memoria cuando `tlatoa.db` no existe o está dañado,
    /// evitando que la aplicación de escritorio o móvil colapse al iniciar.
    pub fn open_in_memory_fallback() -> Result<Self, rusqlite::Error> {
        let conn = Connection::open_in_memory()?;
        conn.execute_batch(
            "CREATE TABLE IF NOT EXISTS politicos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                slug TEXT UNIQUE NOT NULL,
                nombre_completo TEXT NOT NULL,
                partido TEXT NOT NULL,
                partido_color TEXT NOT NULL,
                cargo TEXT NOT NULL,
                camara TEXT NOT NULL,
                entidad TEXT NOT NULL,
                es_activo INTEGER NOT NULL DEFAULT 1,
                foto_url TEXT,
                datos_json TEXT NOT NULL
            );
            CREATE VIRTUAL TABLE IF NOT EXISTS politicos_fts USING fts5(
                slug UNINDEXED,
                nombre,
                partido,
                cargo,
                entidad,
                tokenize = 'unicode61 remove_diacritics 2'
            );",
        )?;
        Ok(Self {
            conn,
            fallback_mode: true,
        })
    }

    pub fn open_or_fallback(db_path: &str) -> Result<Self, rusqlite::Error> {
        match Self::open(db_path) {
            Ok(engine) => Ok(engine),
            Err(_) => Self::open_in_memory_fallback(),
        }
    }

    pub fn is_fallback(&self) -> bool {
        self.fallback_mode
    }

    pub fn total_records(&self) -> usize {
        self.conn
            .query_row("SELECT count(*) FROM politicos", [], |r| r.get::<_, i64>(0))
            .unwrap_or(0) as usize
    }

    pub fn search(&self, query: &str, limit: usize) -> Result<Vec<PoliticoSummary>, rusqlite::Error> {
        let clean_q = query.trim();
        if clean_q.is_empty() {
            return Ok(Vec::new());
        }
        let safe_limit = limit.clamp(1, 200);

        // 1. Intento primario ultrarrápido con índice invertido FTS5
        if let Some(fts_query) = build_fts5_query(clean_q) {
            if let Ok(mut stmt) = self.conn.prepare(
                "SELECT p.slug, p.nombre_completo, p.partido, p.partido_color, p.cargo, p.camara, p.entidad, p.foto_url
                 FROM politicos_fts f
                 JOIN politicos p ON f.slug = p.slug
                 WHERE politicos_fts MATCH ?1
                 ORDER BY rank
                 LIMIT ?2",
            ) {
                if let Ok(rows) = stmt.query_map(params![fts_query, safe_limit as i64], Self::map_summary_row) {
                    let results: Vec<PoliticoSummary> = rows.filter_map(Result::ok).collect();
                    if !results.is_empty() {
                        return Ok(results);
                    }
                }
            }
        }

        // 2. Fallback automático con búsqueda SQL LIKE (subcadenas intermedias o caracteres especiales)
        self.search_like_fallback(clean_q, safe_limit)
    }

    fn search_like_fallback(
        &self,
        clean_q: &str,
        limit: usize,
    ) -> Result<Vec<PoliticoSummary>, rusqlite::Error> {
        let pattern = format!("%{}%", clean_q.replace('%', "").replace('_', ""));
        let mut stmt = self.conn.prepare(
            "SELECT slug, nombre_completo, partido, partido_color, cargo, camara, entidad, foto_url
             FROM politicos
             WHERE nombre_completo LIKE ?1
                OR partido LIKE ?1
                OR entidad LIKE ?1
                OR cargo LIKE ?1
             ORDER BY es_activo DESC, nombre_completo ASC
             LIMIT ?2",
        )?;

        let rows = stmt.query_map(params![pattern, limit as i64], Self::map_summary_row)?;
        Ok(rows.filter_map(Result::ok).collect())
    }

    fn map_summary_row(row: &rusqlite::Row<'_>) -> Result<PoliticoSummary, rusqlite::Error> {
        Ok(PoliticoSummary {
            slug: row.get(0)?,
            nombre_completo: row.get(1)?,
            partido: row.get(2)?,
            partido_color: row.get(3)?,
            cargo: row.get(4)?,
            camara: row.get(5)?,
            entidad: row.get(6)?,
            foto_url: row.get::<_, Option<String>>(7)?.unwrap_or_default(),
        })
    }

    pub fn get_by_slug(&self, slug: &str) -> Result<Option<String>, rusqlite::Error> {
        let mut stmt = self
            .conn
            .prepare("SELECT datos_json FROM politicos WHERE slug = ?1 LIMIT 1")?;
        let mut rows = stmt.query(params![slug.trim()])?;
        if let Some(row) = rows.next()? {
            let json_str: String = row.get(0)?;
            Ok(Some(json_str))
        } else {
            Ok(None)
        }
    }
}

// -----------------------------------------------------------------------------
// Singleton Thread-safe Engine instance for JNI & C-ABI
// -----------------------------------------------------------------------------
static GLOBAL_ENGINE: Mutex<Option<TlatoaEngine>> = Mutex::new(None);

pub fn init_engine(db_path: &str) -> Result<(), String> {
    let engine = TlatoaEngine::open(db_path).map_err(|e| e.to_string())?;
    let mut lock = GLOBAL_ENGINE
        .lock()
        .unwrap_or_else(|poisoned| poisoned.into_inner());
    *lock = Some(engine);
    Ok(())
}

pub fn search_json(query: &str, limit: usize) -> Result<String, String> {
    let lock = GLOBAL_ENGINE
        .lock()
        .unwrap_or_else(|poisoned| poisoned.into_inner());
    let engine = lock.as_ref().ok_or("TlatoaEngine no ha sido inicializado")?;
    let results = engine.search(query, limit).map_err(|e| e.to_string())?;
    serde_json::to_string(&results).map_err(|e| e.to_string())
}

pub fn get_politico_json(slug: &str) -> Result<String, String> {
    let lock = GLOBAL_ENGINE
        .lock()
        .unwrap_or_else(|poisoned| poisoned.into_inner());
    let engine = lock.as_ref().ok_or("TlatoaEngine no ha sido inicializado")?;
    match engine.get_by_slug(slug).map_err(|e| e.to_string())? {
        Some(json) => Ok(json),
        None => Ok("null".to_string()),
    }
}

// -----------------------------------------------------------------------------
// JNI Exports for Android Kotlin Bridge (org.tlatoa.core.TlatoaEngine)
// -----------------------------------------------------------------------------
pub mod jni_exports {
    use super::*;
    use jni::objects::{JClass, JString};
    use jni::sys::{jboolean, jint, jstring, JNI_FALSE, JNI_TRUE};
    use jni::JNIEnv;

    #[no_mangle]
    pub extern "C" fn Java_org_tlatoa_core_TlatoaEngine_nativeInit(
        mut env: JNIEnv,
        _class: JClass,
        path: JString,
    ) -> jboolean {
        let path_str: String = match env.get_string(&path) {
            Ok(s) => s.into(),
            Err(_) => return JNI_FALSE,
        };
        match init_engine(&path_str) {
            Ok(_) => JNI_TRUE,
            Err(_) => JNI_FALSE,
        }
    }

    #[no_mangle]
    pub extern "C" fn Java_org_tlatoa_core_TlatoaEngine_nativeSearch(
        mut env: JNIEnv,
        _class: JClass,
        query: JString,
        limit: jint,
    ) -> jstring {
        let query_str: String = match env.get_string(&query) {
            Ok(s) => s.into(),
            Err(_) => String::new(),
        };
        let limit_val = if limit <= 0 { 25 } else { limit as usize };
        let json_result = search_json(&query_str, limit_val).unwrap_or_else(|_| "[]".to_string());
        match env.new_string(json_result) {
            Ok(js) => js.into_raw(),
            Err(_) => std::ptr::null_mut(),
        }
    }

    #[no_mangle]
    pub extern "C" fn Java_org_tlatoa_core_TlatoaEngine_nativeGetPolitico(
        mut env: JNIEnv,
        _class: JClass,
        slug: JString,
    ) -> jstring {
        let slug_str: String = match env.get_string(&slug) {
            Ok(s) => s.into(),
            Err(_) => String::new(),
        };
        let json_result = get_politico_json(&slug_str).unwrap_or_else(|_| "null".to_string());
        match env.new_string(json_result) {
            Ok(js) => js.into_raw(),
            Err(_) => std::ptr::null_mut(),
        }
    }
}
