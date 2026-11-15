# rag-service

FastAPI service that handles the retrieval side of lara-rag: chunking, embedding, retrieval, reranking.

## Layout

- `app/main.py`: FastAPI app; registers routers.
- `app/config.py`: `Settings` (pydantic-settings), read from env vars. Access via `get_settings()`.
- `app/db.py`: shared async `psycopg` connection pool, opened in the app lifespan.
- `app/routers/`: one module per resource, each exposing `router`.
- `tests/`: pytest.

## Conventions

- Add dependencies with `make rag-add ARGS="<pkg>"` (or `ARGS="--dev <pkg>"`). Commit `uv.lock`.
- Request and response bodies are Pydantic models, not raw dicts, so the contract with Laravel is explicit.
- This service owns only its own tables (chunks, embeddings). Never write to Laravel's tables.
- Run commands via make from the repo root: `make rag-test`, `make rag-lint`, `make rag-shell`.
