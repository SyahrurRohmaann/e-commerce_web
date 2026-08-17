# Alagance CrewAI Service

Standalone CrewAI service for the Alagance e-commerce application.

## Setup

```bash
cd ai-service
cp .env.example .env
# Set CREW_API_KEY in .env
uv sync
set -a && . ./.env && set +a
uv run uvicorn app.main:app --host 0.0.0.0 --port 8001
```

The service tries `CREW_BASE_URL` first and then `CREW_FALLBACK_BASE_URL`. The API key stays server-side.

## Endpoints

```text
GET  /health
POST /api/crew
```

Request:

```json
{"prompt":"Your task for the crew"}
```

Response:

```json
{"result":"Crew output","model":"crew"}
```

## Test

```bash
uv run pytest -q
```
