-- ========================================================================
-- PROYECTO TLATOA (FORK SOBERANO) — SCRAPER TEMPLATE EN LUA 5.4
-- Motor: Rust Core + LuaJIT/Lua 5.4 nativo (Sin PHP, Sin Python)
-- ========================================================================

tlatoa.log("Iniciando scraper plantilla...")

local entidad = "Ciudad de México"
local legislatura = 66
local slug_entidad = tlatoa.slugify(entidad)

tlatoa.log("Entidad normalizada: " .. slug_entidad .. " (Legislatura " .. legislatura .. ")")

-- Estructura de salida JSON estándar
local resultado = {
    metadata = {
        entidad = entidad,
        slug = slug_entidad,
        legislatura = legislatura,
        fuente_oficial = "https://www.congresocdmx.gob.mx",
        generado_por = "tlatoa-core (Rust + Lua)",
        timestamp = os.time()
    },
    diputados = {}
}

-- Ejemplo de extracción y normalización
local ejemplo_diputados = {
    { nombre = "Ana Karen López García", partido = "MORENA", distrito = "Distrito 1" },
    { nombre = "Carlos Mendoza Rivera", partido = "PAN", distrito = "Distrito 2" }
}

for i, dip in ipairs(ejemplo_diputados) do
    local slug = tlatoa.slugify(dip.nombre)
    tlatoa.log("Procesando diputado [" .. i .. "]: " .. dip.nombre .. " -> " .. slug)
    
    table.insert(resultado.diputados, {
        nombre_completo = dip.nombre,
        slug = slug,
        partido = dip.partido,
        cargo = "Diputado(a) Local",
        entidad = entidad,
        distrito = dip.distrito
    })
end

-- Serializar manualmente o escribir
local json_str = string.format([[
{
  "metadata": {
    "entidad": "%s",
    "legislatura": %d,
    "fuente_oficial": "%s",
    "total": %d
  },
  "diputados": [
]], resultado.metadata.entidad, resultado.metadata.legislatura, resultado.metadata.fuente_oficial, #resultado.diputados)

for i, d in ipairs(resultado.diputados) do
    json_str = json_str .. string.format([[
    {
      "nombre_completo": "%s",
      "slug": "%s",
      "partido": "%s",
      "cargo": "%s",
      "entidad": "%s"
    }%s
]], d.nombre_completo, d.slug, d.partido, d.cargo, d.entidad, (i == #resultado.diputados and "" or ","))
end

json_str = json_str .. "  ]\n}"

local output_path = "storage/data/json/congreso_" .. slug_entidad .. "_leg_" .. legislatura .. ".json"
local ok = tlatoa.write_file(output_path, json_str)

if ok then
    tlatoa.log("✅ Dataset guardado exitosamente en: " .. output_path)
else
    tlatoa.log("❌ Error guardando dataset en disco.")
end
