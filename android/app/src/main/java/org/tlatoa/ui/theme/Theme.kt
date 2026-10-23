package org.tlatoa.ui.theme

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable

private val LightColorScheme = lightColorScheme(
    primary = TlatoaBlue,
    background = TlatoaBg,
    surface = TlatoaSurface,
    onPrimary = TlatoaSurface,
    onBackground = TlatoaText,
    onSurface = TlatoaText
)

@Composable
fun TlatoaTheme(content: @Composable () -> Unit) {
    MaterialTheme(
        colorScheme = LightColorScheme,
        content = content
    )
}
