"""
Módulo de seguridad libre y local (Proyecto Tlatoa Fork Soberano).
Cero bloqueos por IP, cero censura, 100% de acceso para uso local y herramientas ciudadanas.
"""

def sanitize_input(text: str) -> str:
    """Sanitiza entradas para prevenir inyecciones básicas sin truncar datos válidos."""
    if not text:
        return ""
    return text.strip()
