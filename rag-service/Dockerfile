FROM ghcr.io/astral-sh/uv:python3.13-bookworm-slim AS base

# Keep the virtualenv outside /app so the dev source bind mount doesn't hide it.
ENV UV_PROJECT_ENVIRONMENT=/opt/venv \
    UV_COMPILE_BYTECODE=1 \
    UV_LINK_MODE=copy \
    PATH="/opt/venv/bin:$PATH"

WORKDIR /app
COPY pyproject.toml uv.lock .python-version ./

# ---- dev: includes test/lint tools, source is bind-mounted ----
FROM base AS dev
RUN uv sync --frozen
COPY . .
EXPOSE 8001
CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8001", "--reload"]

# ---- prod ----
FROM base AS prod
RUN uv sync --frozen --no-dev
COPY . .
EXPOSE 8001
CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8001", "--workers", "2"]
