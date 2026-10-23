package org.tlatoa.core

import android.content.Context
import org.json.JSONArray
import org.tlatoa.data.PoliticoSummary
import java.io.File
import java.io.FileOutputStream

object TlatoaEngine {
    private var isInitialized = false

    init {
        try {
            System.loadLibrary("tlatoa_core")
        } catch (e: UnsatisfiedLinkError) {
            e.printStackTrace()
        }
    }

    // Funciones JNI implementadas en Rust (crates/tlatoa-core/src/lib.rs)
    private external fun nativeInit(dbPath: String): Boolean
    private external fun nativeSearch(query: String, limit: Int): String
    private external fun nativeGetPolitico(slug: String): String

    fun init(context: Context) {
        if (isInitialized) return

        val dbFile = File(context.filesDir, "tlatoa.db")
        if (!dbFile.exists()) {
            // Desempaquetar base de datos SQLite precompilada desde assets
            context.assets.open("tlatoa.db").use { input ->
                FileOutputStream(dbFile).use { output ->
                    input.copyTo(output)
                }
            }
        }

        isInitialized = nativeInit(dbFile.absolutePath)
    }

    fun search(query: String, limit: Int = 25): List<PoliticoSummary> {
        if (!isInitialized) return emptyList()

        val jsonStr = nativeSearch(query, limit)
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
        } catch (e: Exception) {
            e.printStackTrace()
        }
        return list
    }

    fun getPoliticoDetails(slug: String): String {
        if (!isInitialized) return ""
        return nativeGetPolitico(slug)
    }
}
