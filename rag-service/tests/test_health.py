from fastapi import FastAPI
from fastapi.testclient import TestClient

from app.routers import health


def test_liveness_returns_ok():
    app = FastAPI()
    app.include_router(health.router)
    client = TestClient(app)

    response = client.get("/health")

    assert response.status_code == 200
    assert response.json() == {"status": "ok"}
