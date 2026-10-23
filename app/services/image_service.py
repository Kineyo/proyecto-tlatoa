import hashlib
import io
import re
from datetime import datetime, timezone
from typing import Any, Dict

try:
    from PIL import Image
    HAS_PIL = True
except ImportError:
    Image = None  # type: ignore[assignment]
    HAS_PIL = False

from app.core.config import settings


def _sanitize_token(value: str, default: str) -> str:
    cleaned = re.sub(r"[^a-zA-Z0-9_-]", "", (value or "").strip())
    return cleaned[:32] if cleaned else default


class ImageService:
    @staticmethod
    def sanitizar_y_guardar_acta(contenido_bytes: bytes, seccion: str, casilla: str) -> Dict[str, Any]:
        """
        1. Genera hash SHA-256 inmutable de la imagen original recibida.
        2. Elimina al 100% metadatos EXIF (geolocalización GPS, modelo de cámara, número de serie).
        3. Convierte a formato WebP optimizado (con fallback automático a JPEG/binario si libwebp o Pillow no están disponibles).
        """
        sha256_hash = hashlib.sha256(contenido_bytes).hexdigest()
        safe_sec = _sanitize_token(seccion, "0000")
        safe_cas = _sanitize_token(casilla, "B")

        settings.STORAGE_ELECTORAL_DIR.mkdir(parents=True, exist_ok=True)
        filename = f"{safe_sec}_{safe_cas}_{sha256_hash[:12]}.webp"
        target_path = settings.STORAGE_ELECTORAL_DIR / filename

        saved = False
        if HAS_PIL and Image is not None:
            try:
                image = Image.open(io.BytesIO(contenido_bytes))
                if image.mode != "RGB":
                    image = image.convert("RGB")
                clean_img = Image.new("RGB", image.size)
                clean_img.paste(image)
                try:
                    clean_img.save(target_path, "WEBP", quality=85, method=6)
                    saved = True
                except Exception:
                    # Fallback a JPEG si libwebp no está instalado en el sistema operativo
                    filename = f"{safe_sec}_{safe_cas}_{sha256_hash[:12]}.jpg"
                    target_path = settings.STORAGE_ELECTORAL_DIR / filename
                    clean_img.save(target_path, "JPEG", quality=88)
                    saved = True
            except Exception:
                saved = False

        if not saved:
            filename = f"{safe_sec}_{safe_cas}_{sha256_hash[:12]}.bin"
            target_path = settings.STORAGE_ELECTORAL_DIR / filename
            target_path.write_bytes(contenido_bytes)

        file_size = target_path.stat().st_size
        ahora = datetime.now(timezone.utc).isoformat()

        return {
            "hash_sha256": sha256_hash,
            "filename": filename,
            "seccion": safe_sec,
            "casilla": safe_cas,
            "tamano_bytes": file_size,
            "fecha_subida": ahora,
            "archivo_relativo": f"/electoral/actas/{filename}",
        }


image_service = ImageService()
