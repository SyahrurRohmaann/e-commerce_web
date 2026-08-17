import os
from functools import lru_cache

from crewai import Agent, Crew, LLM, Process, Task
from fastapi import Depends, FastAPI, HTTPException
from pydantic import BaseModel, Field, field_validator

MODEL = os.getenv("CREW_MODEL", "crew")
DEFAULT_BASE_URL = "http://9router:20128/v1"


class CrewRequest(BaseModel):
    prompt: str = Field(max_length=4000)

    @field_validator("prompt")
    @classmethod
    def prompt_must_not_be_blank(cls, value: str) -> str:
        value = value.strip()
        if not value:
            raise ValueError("prompt must not be blank")
        return value


class CrewResponse(BaseModel):
    result: str
    model: str


class CrewRunner:
    def __init__(self) -> None:
        api_key = os.getenv("CREW_API_KEY")
        if not api_key:
            raise RuntimeError("CREW_API_KEY is required")

        self.api_key = api_key
        self.base_urls = [
            os.getenv("CREW_BASE_URL", DEFAULT_BASE_URL),
            os.getenv("CREW_FALLBACK_BASE_URL", "http://16.79.198.38:20128/v1"),
        ]

    def _build_llm(self, base_url: str) -> LLM:
        return LLM(
            model=MODEL,
            provider="openai",
            api_key=self.api_key,
            base_url=base_url,
            timeout=float(os.getenv("CREW_TIMEOUT", "120")),
        )

    def _run_with_llm(self, prompt: str, llm: LLM) -> str:
        agent = Agent(
            role="E-commerce AI Assistant",
            goal="Answer the supplied e-commerce task accurately and concisely",
            backstory="You are an AI service embedded in the Alagance e-commerce platform.",
            llm=llm,
            verbose=False,
        )
        task = Task(
            description=prompt,
            expected_output="A direct, useful response to the supplied task.",
            agent=agent,
        )
        crew = Crew(agents=[agent], tasks=[task], process=Process.sequential, verbose=False)
        return str(crew.kickoff())

    def run(self, prompt: str) -> str:
        last_error: Exception | None = None
        for base_url in dict.fromkeys(self.base_urls):
            try:
                return self._run_with_llm(prompt, self._build_llm(base_url))
            except Exception as exc:
                last_error = exc

        raise RuntimeError("all CrewAI providers failed") from last_error


@lru_cache
def get_crew_runner() -> CrewRunner:
    return CrewRunner()


app = FastAPI(title="Alagance CrewAI Service", version="1.0.0")


@app.get("/health")
def health() -> dict[str, str]:
    return {"status": "ok", "model": MODEL}


@app.post("/api/crew", response_model=CrewResponse)
def run_crew(
    request: CrewRequest,
    runner: CrewRunner = Depends(get_crew_runner),
) -> CrewResponse:
    try:
        result = runner.run(request.prompt)
    except Exception as exc:
        raise HTTPException(status_code=502, detail="CrewAI provider unavailable") from exc

    return CrewResponse(result=result, model=MODEL)
