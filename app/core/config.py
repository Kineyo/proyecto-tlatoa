import os
from pathlib import Path

BASE_DIR = Path(__file__).resolve().parent.parent.parent

try:
    from pydantic_settings import BaseSettings

    class Settings(BaseSettings):
        BASE_DIR: Path = BASE_DIR
        APP_NAME: str = "Proyecto Tlatoa"
        APP_SHORT_NAME: str = "Tlatoa"
        APP_TAGLINE: str = "Radiografía ciudadana de Gobernadores, Alcaldes, Diputados y Senadores"
        APP_ENV: str = "development"
        DEBUG: bool = True
        PORT: int = 8000
        HOST: str = "0.0.0.0"

        STORAGE_DATA_DIR: Path = BASE_DIR / "storage" / "data" / "json"
        STORAGE_RAW_PHOTOS: Path = BASE_DIR / "storage" / "raw" / "photos"
        STORAGE_ELECTORAL_DIR: Path = BASE_DIR / "storage" / "electoral" / "actas"
        TEMPLATES_DIR: Path = BASE_DIR / "app" / "templates"
        ASSETS_DIR: Path = BASE_DIR / "assets"

        MAX_UPLOAD_SIZE_BYTES: int = 15 * 1024 * 1024
        ENABLE_API_DOCS: bool = False
        API_RATE_LIMIT_PER_MINUTE: int = 60
        ALLOWED_ORIGINS: list[str] = [
            "http://localhost:8000",
            "http://127.0.0.1:8000",
            "http://localhost",
            "http://127.0.0.1",
        ]

        class Config:
            env_file = ".env"
            env_file_encoding = "utf-8"
            extra = "ignore"

except ImportError:
    class Settings:  # type: ignore[no-redef]
        """Fallback sin pydantic-settings: carga variables desde .env y os.environ."""

        def __init__(self) -> None:
            env_path = BASE_DIR / ".env"
            if env_path.exists():
                for line in env_path.read_text(encoding="utf-8", errors="ignore").splitlines():
                    line = line.strip()
                    if line and not line.startswith("#") and "=" in line:
                        k, v = line.split("=", 1)
                        os.environ.setdefault(k.strip(), v.strip().strip('"').strip("'"))

            self.BASE_DIR: Path = BASE_DIR
            self.APP_NAME: str = os.getenv("APP_NAME", "Proyecto Tlatoa")
            self.APP_SHORT_NAME: str = os.getenv("APP_SHORT_NAME", "Tlatoa")
            self.APP_TAGLINE: str = os.getenv(
                "APP_TAGLINE",
                "Radiografía ciudadana de Gobernadores, Alcaldes, Diputados y Senadores",
            )
            self.APP_ENV: str = os.getenv("APP_ENV", "development")
            self.DEBUG: bool = os.getenv("DEBUG", "true").lower() in ("1", "true", "yes")
            self.PORT: int = int(os.getenv("PORT", "8000"))
            self.HOST: str = os.getenv("HOST", "0.0.0.0")

            self.STORAGE_DATA_DIR: Path = BASE_DIR / "storage" / "data" / "json"
            self.STORAGE_RAW_PHOTOS: Path = BASE_DIR / "storage" / "raw" / "photos"
            self.STORAGE_ELECTORAL_DIR: Path = BASE_DIR / "storage" / "electoral" / "actas"
            self.TEMPLATES_DIR: Path = BASE_DIR / "app" / "templates"
            self.ASSETS_DIR: Path = BASE_DIR / "assets"

            self.MAX_UPLOAD_SIZE_BYTES: int = 15 * 1024 * 1024
            self.ENABLE_API_DOCS: bool = os.getenv("ENABLE_API_DOCS", "false").lower() in ("1", "true", "yes")
            self.API_RATE_LIMIT_PER_MINUTE: int = int(os.getenv("API_RATE_LIMIT_PER_MINUTE", "60"))
            self.ALLOWED_ORIGINS: list[str] = [
                "http://localhost:8000",
                "http://127.0.0.1:8000",
                "http://localhost",
                "http://127.0.0.1",
            ]

settings = Settings()
