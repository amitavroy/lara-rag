from fastapi import APIRouter, HTTPException

from app.db import pool

router = APIRouter(prefix="/health", tags=["health"])


@router.get("")
async def liveness() -> dict[str, str]:
    return {"status": "ok"}


@router.get("/ready")
async def readiness() -> dict[str, str]:
    try:
        async with pool.connection(timeout=2) as conn:
            await conn.execute("SELECT 1")
    except Exception as exc:
        raise HTTPException(status_code=503, detail="database unavailable") from exc

    return {"status": "ok", "database": "ok"}
