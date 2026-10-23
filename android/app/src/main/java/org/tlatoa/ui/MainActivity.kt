package org.tlatoa.ui

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Clear
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.WifiOff
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import org.json.JSONObject
import org.tlatoa.core.TlatoaEngine
import org.tlatoa.data.PoliticoSummary
import org.tlatoa.ui.theme.TlatoaTheme

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        TlatoaEngine.init(this)

        setContent {
            TlatoaTheme {
                Surface(
                    modifier = Modifier.fillMaxSize(),
                    color = MaterialTheme.colorScheme.background
                ) {
                    TlatoaSearchScreen()
                }
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun TlatoaSearchScreen() {
    var searchQuery by remember { mutableStateOf("") }
    var results by remember { mutableStateOf<List<PoliticoSummary>>(emptyList()) }
    var isSearching by remember { mutableStateOf(false) }
    var selectedPolitico by remember { mutableStateOf<PoliticoSummary?>(null) }
    var selectedJson by remember { mutableStateOf("") }

    LaunchedEffect(searchQuery) {
        if (searchQuery.trim().length >= 2) {
            isSearching = true
            results = withContext(Dispatchers.Default) {
                TlatoaEngine.search(searchQuery.trim(), limit = 40)
            }
            isSearching = false
        } else {
            results = emptyList()
        }
    }

    LaunchedEffect(selectedPolitico) {
        val current = selectedPolitico
        if (current != null) {
            selectedJson = withContext(Dispatchers.Default) {
                TlatoaEngine.getPoliticoDetails(current.slug)
            }
        } else {
            selectedJson = ""
        }
    }

    if (selectedPolitico != null) {
        PoliticoDetailDialog(
            summary = selectedPolitico!!,
            rawJson = selectedJson,
            onDismiss = { selectedPolitico = null }
        )
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        Box(
                            modifier = Modifier
                                .size(28.dp)
                                .clip(CircleShape)
                                .background(MaterialTheme.colorScheme.primary),
                            contentAlignment = Alignment.Center
                        ) {
                            Text(
                                text = "T",
                                color = Color.White,
                                fontWeight = FontWeight.Bold,
                                fontSize = 16.sp
                            )
                        }
                        Text(
                            text = "Proyecto Tlatoa",
                            fontWeight = FontWeight.Bold,
                            fontSize = 18.sp
                        )
                    }
                },
                actions = {
                    Surface(
                        color = Color(0xFFE8F5E9),
                        shape = RoundedCornerShape(12.dp),
                        modifier = Modifier.padding(end = 12.dp)
                    ) {
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp),
                            horizontalArrangement = Arrangement.spacedBy(4.dp)
                        ) {
                            Icon(
                                imageVector = Icons.Default.WifiOff,
                                contentDescription = "Offline",
                                modifier = Modifier.size(14.dp),
                                tint = Color(0xFF2E7D32)
                            )
                            Text(
                                text = "100% Offline",
                                color = Color(0xFF2E7D32),
                                fontSize = 11.sp,
                                fontWeight = FontWeight.SemiBold
                            )
                        }
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.surface
                )
            )
        }
    ) { padding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(padding)
                .padding(horizontal = 16.dp)
        ) {
            Spacer(modifier = Modifier.height(8.dp))

            OutlinedTextField(
                value = searchQuery,
                onValueChange = { searchQuery = it },
                modifier = Modifier.fillMaxWidth(),
                placeholder = { Text("Busca por nombre, estado o partido (ej. Noroña)...") },
                leadingIcon = {
                    Icon(imageVector = Icons.Default.Search, contentDescription = "Buscar")
                },
                trailingIcon = {
                    if (searchQuery.isNotEmpty()) {
                        IconButton(onClick = { searchQuery = "" }) {
                            Icon(imageVector = Icons.Default.Clear, contentDescription = "Borrar")
                        }
                    }
                },
                singleLine = true,
                shape = RoundedCornerShape(24.dp),
                colors = OutlinedTextFieldDefaults.colors(
                    focusedContainerColor = MaterialTheme.colorScheme.surface,
                    unfocusedContainerColor = MaterialTheme.colorScheme.surface
                )
            )

            Spacer(modifier = Modifier.height(12.dp))

            if (results.isEmpty() && searchQuery.trim().length >= 2 && !isSearching) {
                Box(
                    modifier = Modifier.fillMaxSize(),
                    contentAlignment = Alignment.Center
                ) {
                    Text(
                        text = "No se encontraron políticos con \"$searchQuery\"",
                        color = Color.Gray,
                        fontSize = 14.sp
                    )
                }
            } else if (searchQuery.trim().length < 2) {
                Box(
                    modifier = Modifier.fillMaxSize(),
                    contentAlignment = Alignment.Center
                ) {
                    Text(
                        text = "Teclea al menos 2 letras para buscar sin internet...",
                        color = Color.Gray,
                        fontSize = 14.sp
                    )
                }
            } else {
                LazyColumn(
                    verticalArrangement = Arrangement.spacedBy(8.dp),
                    contentPadding = PaddingValues(bottom = 24.dp)
                ) {
                    items(results, key = { it.slug }) { politico ->
                        PoliticoCard(
                            politico = politico,
                            onClick = { selectedPolitico = politico }
                        )
                    }
                }
            }
        }
    }
}

@Composable
fun PoliticoCard(politico: PoliticoSummary, onClick: () -> Unit) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.surface
        ),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
    ) {
        Column(
            modifier = Modifier.padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(6.dp)
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                val parsedColor = try {
                    Color(android.graphics.Color.parseColor(politico.partidoColor))
                } catch (_: Exception) {
                    Color(0xFF6C757D)
                }

                Surface(
                    color = parsedColor.copy(alpha = 0.12f),
                    shape = RoundedCornerShape(8.dp)
                ) {
                    Text(
                        text = politico.partido,
                        color = parsedColor,
                        fontWeight = FontWeight.Bold,
                        fontSize = 12.sp,
                        modifier = Modifier.padding(horizontal = 8.dp, vertical = 3.dp)
                    )
                }

                Text(
                    text = politico.entidad,
                    fontSize = 12.sp,
                    color = Color.Gray,
                    fontWeight = FontWeight.Medium
                )
            }

            Text(
                text = politico.nombreCompleto,
                fontWeight = FontWeight.Bold,
                fontSize = 16.sp,
                maxLines = 2,
                overflow = TextOverflow.Ellipsis
            )

            Text(
                text = politico.cargo,
                fontSize = 13.sp,
                color = Color(0xFF495057),
                maxLines = 1,
                overflow = TextOverflow.Ellipsis
            )
        }
    }
}

@Composable
fun PoliticoDetailDialog(
    summary: PoliticoSummary,
    rawJson: String,
    onDismiss: () -> Unit
) {
    val details = remember(rawJson) {
        try {
            if (rawJson.isNotEmpty()) JSONObject(rawJson) else null
        } catch (_: Exception) {
            null
        }
    }

    AlertDialog(
        onDismissRequest = onDismiss,
        confirmButton = {
            TextButton(onClick = onDismiss) {
                Text("Cerrar")
            }
        },
        title = {
            Text(
                text = summary.nombreCompleto,
                fontWeight = FontWeight.Bold,
                fontSize = 18.sp
            )
        },
        text = {
            Column(
                modifier = Modifier.verticalScroll(rememberScrollState()),
                verticalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                Text("Partido: ${summary.partido}", fontWeight = FontWeight.SemiBold)
                Text("Cargo: ${summary.cargo}")
                Text("Entidad: ${summary.entidad}")

                if (details != null) {
                    val principio = details.optString("principio_eleccion")
                    if (principio.isNotEmpty()) Text("Principio: $principio")

                    val periodo = details.optString("periodo")
                        .ifEmpty { details.optString("fecha_toma_protesta") }
                        .ifEmpty { details.optString("fecha_inicio") }
                    if (periodo.isNotEmpty()) Text("Periodo / Inicio: $periodo")

                    val suplente = details.optString("suplente_nombre")
                    if (suplente.isNotEmpty() && suplente != "null") Text("Suplente: $suplente")

                    val estudios = details.optString("ultimo_grado_estudios")
                        .ifEmpty { details.optString("preparacion_academica") }
                    if (estudios.isNotEmpty() && estudios != "null") Text("Escolaridad: $estudios")

                    val meta = details.optJSONObject("metadata")
                    val fuente = meta?.optString("fuente_url")
                        ?.ifEmpty { details.optString("fuente_url") }
                        ?: "Portal Oficial (.gob.mx)"
                    Spacer(modifier = Modifier.height(4.dp))
                    Text(
                        text = "Fuente Oficial: $fuente",
                        fontSize = 12.sp,
                        color = Color.Gray
                    )
                }
            }
        }
    )
}
