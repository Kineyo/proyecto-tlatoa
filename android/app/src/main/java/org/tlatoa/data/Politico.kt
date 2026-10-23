package org.tlatoa.data

data class PoliticoSummary(
    val slug: String,
    val nombreCompleto: String,
    val partido: String,
    val partidoColor: String,
    val cargo: String,
    val camara: String,
    val entidad: String,
    val fotoUrl: String
)
