import os

os.environ.setdefault("CREW_API_KEY", "test-key")

from fastapi.testclient import TestClient

from app.main import app, get_crew_runner


class FakeCrewRunner:
    def run(self, prompt: str) -> str:
        return f"crew:{prompt}"


def test_health_endpoint_reports_model():
    client = TestClient(app)

    response = client.get("/health")

    assert response.status_code == 200
    assert response.json() == {"status": "ok", "model": "crew"}


def test_crew_endpoint_runs_prompt():
    app.dependency_overrides[get_crew_runner] = lambda: FakeCrewRunner()
    client = TestClient(app)

    response = client.post("/api/crew", json={"prompt": "Rekomendasikan produk"})

    assert response.status_code == 200
    assert response.json() == {"result": "crew:Rekomendasikan produk", "model": "crew"}
    app.dependency_overrides.clear()


def test_crew_endpoint_rejects_blank_prompt():
    client = TestClient(app)

    response = client.post("/api/crew", json={"prompt": "   "})

    assert response.status_code == 422


def test_crew_failure_is_returned_as_bad_gateway():
    class FailedCrewRunner:
        def run(self, prompt: str) -> str:
            raise RuntimeError("provider unavailable")

    app.dependency_overrides[get_crew_runner] = lambda: FailedCrewRunner()
    client = TestClient(app)

    response = client.post("/api/crew", json={"prompt": "hello"})

    assert response.status_code == 502
    assert response.json() == {"detail": "CrewAI provider unavailable"}
    app.dependency_overrides.clear()
