from functools import lru_cache

from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_file=".env", extra="ignore")

    database_url: str = "postgresql://lara_rag:secret@postgres:5432/lara_rag"
    # Laravel's storage/app, mounted read-only so ingestion can read uploaded files.
    laravel_storage_path: str = "/data/laravel-storage"


@lru_cache
def get_settings() -> Settings:
    return Settings()
