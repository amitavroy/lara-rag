from collections.abc import AsyncIterator
from contextlib import asynccontextmanager

from psycopg_pool import AsyncConnectionPool

from app.config import get_settings

pool = AsyncConnectionPool(get_settings().database_url, open=False)


@asynccontextmanager
async def lifespan(_app) -> AsyncIterator[None]:
    await pool.open()
    yield
    await pool.close()
