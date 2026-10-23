use rusqlite::{params, Connection};
use serde::{Deserialize, Serialize};
use std::sync::Mutex;

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
}

impl TlatoaEngine {
    pub fn open(db_path: &str) -> Result<Self, rusqlite::Error> {
        let conn = Connection::open(db_path)?;
        conn.execute_batch(
            "PRAGMA query_only = 1;
             PRAGMA mmap_size = 268435456;
             PRAGMA cache_size = -8000;"
        )?;
        Ok(Self { conn })
    }

    pub fn search(&self, query: &str, limit: usize) -> Result<Vec<PoliticoSummary>, rusqlite::Error> {
        let clean_q = query.trim();
        if clean_q.is_empty() {
            return Ok(Vec::new());
        }

        let fts_query = format!("{}*", clean_q.replace('"', "").replace('\'', ""));
        let mut stmt = self.conn.prepare(
            "SELECT p.slug, p.nombre_completo, p.partido, p.partido_color, p.cargo, p.camara, p.entidad, p.foto_url
             FROM politicos_fts f
             JOIN politicos p ON f.slug = p.slug
             WHERE politicos_fts MATCH ?1
             ORDER BY rank
             LIMIT ?2"
        )?;

        let rows = stmt.query_map(params![fts_query, limit as i64], |row| {
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
        })?;

        let mut results = Vec::new();
        for r in rows {
            results.push(r?);
        }
        Ok(results)
    }

    pub fn get_by_slug(&self, slug: &str) -> Result<Option<String>, rusqlite::Error> {
        let mut stmt = self.conn.prepare("SELECT datos_json FROM politicos WHERE slug = ?1 LIMIT 1")?;
        let mut rows = stmt.query(params![slug])?;
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
    let mut lock = GLOBAL_ENGINE.lock().map_err(|e| e.to_string())?;
    *lock = Some(engine);
    Ok(())
}

pub fn search_json(query: &str, limit: usize) -> Result<String, String> {
    let lock = GLOBAL_ENGINE.lock().map_err(|e| e.to_string())?;
    let engine = lock.as_ref().ok_or("TlatoaEngine no ha sido inicializado")?;
    let results = engine.search(query, limit).map_err(|e| e.to_string())?;
    serde_json::to_string(&results).map_err(|e| e.to_string())
}

pub fn get_politico_json(slug: &str) -> Result<String, String> {
    let lock = GLOBAL_ENGINE.lock().map_err(|e| e.to_string())?;
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
        let limit_val = if limit <= 0 { 20 } else { limit as usize };
        let json_result = search_json(&query_str, limit_val).unwrap_or_else(|e| format!("[] /* err: {} */", e));
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
