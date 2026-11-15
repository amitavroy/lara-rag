from fastapi import FastAPI

from app.db import lifespan
from app.routers import health

app = FastAPI(title="lara-rag service", lifespan=lifespan)

app.include_router(health.router)
