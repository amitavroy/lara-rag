# lara-rag

A RAG application split into two services that run side by side under Docker Compose.

| Path           | Service      | Responsibility                                                                 |
| -------------- | ------------ | ------------------------------------------------------------------------------ |
| `backend/`     | Laravel 13   | Users, auth, document upload, job orchestration, Inertia/React UI, LLM calls via `laravel/ai` |
| `rag-service/` | Python/FastAPI | Parsing, chunking, embedding, retrieval, reranking                            |

Supporting containers: `postgres` (pgvector), `redis` (Laravel queue + cache), `queue` (Laravel worker), `vite` (frontend dev server).

## How the services talk

- Laravel calls the RAG service over HTTP at `config('services.rag.url')` (`http://rag:8001` inside the Compose network).
- Laravel's `storage/app` is mounted read-only into the `rag` container at `/data/laravel-storage`, so ingestion can read uploaded files by path.
- Both services share one Postgres database. **Each service migrates only the tables it owns**: Laravel owns documents, users and job status; the RAG service owns chunks and embeddings.

## Running things

All interaction goes through `make`. Run `make` alone to list every command. Do not call `docker`/`docker compose` directly, and do not run `php`, `composer`, `npm` or `uv` on the host. If a needed command is missing, add a target to the `Makefile`.

```bash
make setup                          # first-time dev setup (build, start, migrate)
make up / make down
make artisan ARGS="make:model Foo"
make test ARGS="--filter=Foo"       # Laravel tests
make lint                           # Pint
make rag-test                       # Python tests
make rag-lint                       # ruff fix + format
make rag-add ARGS="pgvector"        # add a Python dependency
make check                          # all tests + static analysis
make logs ARGS=queue
```

Laravel: http://localhost:8000 · RAG API docs: http://localhost:8001/docs

Production: `make prod` builds the `prod` image targets and runs `docker-compose.prod.yml`. Laravel runs on FrankenPHP with a queue worker and scheduler. The RAG service runs without reload and is not exposed to the host. Configuration lives in `backend/.env.production` (gitignored), which also supplies the Postgres credentials.

## Configuration

- Root `.env`: dev Compose ports and Postgres credentials.
- `backend/.env`: Laravel config. Its `DB_*` values must match the root `.env`. Don't inject `DB_*` into the Laravel containers via Compose `environment:`. Real env vars override phpunit's `<env>` values, so tests would run against the dev database instead of in-memory SQLite.
- `rag-service`: settings come from env vars in `docker-compose.yml`, read by `app/config.py`.

## Conventions

- Laravel architecture rules: `docs/architecture/index.md`.
- Python conventions: `rag-service/CLAUDE.md`.
- When changing the HTTP contract between the services, update both sides in the same change.
