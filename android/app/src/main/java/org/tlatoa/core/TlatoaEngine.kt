package org.tlatoa.core

import android.content.Context
import android.database.sqlite.SQLiteDatabase
import org.json.JSONArray
import org.tlatoa.data.PoliticoSummary
import java.io.File
import java.io.FileOutputStream

object TlatoaEngine {
    private var nativeLoaded = false
    private var jniInitialized = false
    private var fallbackDb: SQLiteDatabase? = null

    init {
        try {
            System.loadLibrary("tlatoa_core")
            nativeLoaded = true
        } catch (_: UnsatisfiedLinkError) {
            nativeLoaded = false
        } catch (_: Throwable) {
            nativeLoaded = false
        }
    }

    // Funciones JNI implementadas en Rust (crates/tlatoa-core/src/lib.rs)
    private external fun nativeInit(dbPath: String): Boolean
    private external fun nativeSearch(query: String, limit: Int): String
    private external fun nativeGetPolitico(slug: String): String

    @Synchronized
    fun init(context: Context) {
        if (jniInitialized || fallbackDb != null) return

        val dbFile = File(context.filesDir, "tlatoa.db")
        if (!dbFile.exists() || dbFile.length() < 1024L) {
            val tmpFile = File(context.filesDir, "tlatoa.db.tmp")
            try {
                context.assets.open("tlatoa.db").use { input ->
                    FileOutputStream(tmpFile).use { output ->
                        input.copyTo(output)
                        output.flush()
                    }
                }
                if (dbFile.exists()) dbFile.delete()
                tmpFile.renameTo(dbFile)
            } catch (e: Exception) {
                tmpFile.delete()
                e.printStackTrace()
                return
            }
        }

        // 1. Intentar motor nativo Rust vía JNI
        if (nativeLoaded) {
            try {
                jniInitialized = nativeInit(dbFile.absolutePath)
                if (jniInitialized) return
            } catch (_: UnsatisfiedLinkError) {
                jniInitialized = false
            } catch (_: Throwable) {
                jniInitialized = false
            }
        }

        // 2. Fallback automático al motor SQLite nativo de Android si JNI no está disponible
        try {
            fallbackDb = SQLiteDatabase.openDatabase(
                dbFile.absolutePath,
                null,
                SQLiteDatabase.OPEN_READONLY
            )
        } catch (e: Exception) {
            e.printStackTrace()
        }
    }

    fun isUsingNativeRust(): Boolean = jniInitialized

    fun search(query: String, limit: Int = 30): List<PoliticoSummary> {
        val cleanQuery = query.trim()
        if (cleanQuery.isEmpty()) return emptyList()

        if (jniInitialized) {
            try {
                val jsonStr = nativeSearch(cleanQuery, limit)
                return parseSummaryJson(jsonStr)
            } catch (_: Throwable) {
                // Continuar al fallback SQLite si ocurre error JNI
            }
        }

        return searchFallbackSqlite(cleanQuery, limit)
    }

    fun getPoliticoDetails(slug: String): String {
        val cleanSlug = slug.trim()
        if (cleanSlug.isEmpty()) return ""

        if (jniInitialized) {
            try {
                val res = nativeGetPolitico(cleanSlug)
                if (res.isNotEmpty() && res != "null") return res
            } catch (_: Throwable) {
                // Continuar al fallback SQLite
            }
        }

        val db = fallbackDb ?: return ""
        return try {
            db.rawQuery(
                "SELECT datos_json FROM politicos WHERE slug = ? LIMIT 1",
                arrayOf(cleanSlug)
            ).use { cursor ->
                if (cursor.moveToFirst()) cursor.getString(0) ?: "" else ""
            }
        } catch (_: Exception) {
            ""
        }
    }

    private fun parseSummaryJson(jsonStr: String): List<PoliticoSummary> {
        val list = mutableListOf<PoliticoSummary>()
        try {
            val array = JSONArray(jsonStr)
            for (i in 0 until array.length()) {
                val obj = array.getJSONObject(i)
                list.add(
                    PoliticoSummary(
                        slug = obj.optString("slug"),
                        nombreCompleto = obj.optString("nombre_completo"),
                        partido = obj.optString("partido"),
                        partidoColor = obj.optString("partido_color", "#6C757D"),
                        cargo = obj.optString("cargo"),
                        camara = obj.optString("camara"),
                        entidad = obj.optString("entidad"),
                        fotoUrl = obj.optString("foto_url")
                    )
                )
            }
        } catch (_: Exception) {
        }
        return list
    }

    private fun searchFallbackSqlite(query: String, limit: Int): List<PoliticoSummary> {
        val db = fallbackDb ?: return emptyList()
        val list = mutableListOf<PoliticoSummary>()
        val safeLimit = limit.coerceIn(1, 100).toString()

        // Intento FTS5 en SQLite Android
        val tokens = query.split(Regex("\\s+"))
            .map { it.replace(Regex("[^\\p{L}\\p{N}]"), "") }
            .filter { it.isNotEmpty() && it.uppercase() !in setOf("AND", "OR", "NOT", "NEAR") }
            .map { "\"$it\"*" }

        if (tokens.isNotEmpty()) {
            val ftsQuery = tokens.joinToString(" ")
            try {
                db.rawQuery(
                    """SELECT p.slug, p.nombre_completo, p.partido, p.partido_color, p.cargo, p.camara, p.entidad, p.foto_url
                       FROM politicos_fts f
                       JOIN politicos p ON f.slug = p.slug
                       WHERE politicos_fts MATCH ?
                       LIMIT ?""",
                    arrayOf(ftsQuery, safeLimit)
                ).use { c ->
                    while (c.moveToNext()) {
                        list.add(
                            PoliticoSummary(
                                slug = c.getString(0) ?: "",
                                nombreCompleto = c.getString(1) ?: "",
                                partido = c.getString(2) ?: "",
                                partidoColor = c.getString(3) ?: "#6C757D",
                                cargo = c.getString(4) ?: "",
                                camara = c.getString(5) ?: "",
                                entidad = c.getString(6) ?: "",
                                fotoUrl = c.getString(7) ?: ""
                            )
                        )
                    }
                }
                if (list.isNotEmpty()) return list
            } catch (_: Exception) {
            }
        }

        // Fallback LIKE
        val pattern = "%${query.replace("%", "").replace("_", "")}%"
        try {
            db.rawQuery(
                """SELECT slug, nombre_completo, partido, partido_color, cargo, camara, entidad, foto_url
                   FROM politicos
                   WHERE nombre_completo LIKE ? OR partido LIKE ? OR entidad LIKE ? OR cargo LIKE ?
                   ORDER BY es_activo DESC, nombre_completo ASC
                   LIMIT ?""",
                arrayOf(pattern, pattern, pattern, pattern, safeLimit)
            ).use { c ->
                while (c.moveToNext()) {
                    list.add(
                        PoliticoSummary(
                            slug = c.getString(0) ?: "",
                            nombreCompleto = c.getString(1) ?: "",
                            partido = c.getString(2) ?: "",
                            partidoColor = c.getString(3) ?: "#6C757D",
                            cargo = c.getString(4) ?: "",
                            camara = c.getString(5) ?: "",
                            entidad = c.getString(6) ?: "",
                            fotoUrl = c.getString(7) ?: ""
                        )
                    )
                }
            }
        } catch (_: Exception) {
        }
        return list
    }
}
