import os
from dotenv import load_dotenv

load_dotenv()

class Config:
    DB_HOST = os.getenv("DB_HOST", "localhost")
    DB_PORT = int(os.getenv("DB_PORT", 3306))
    DB_USER = os.getenv("DB_USER", "root")
    DB_PASSWORD = os.getenv("DB_PASSWORD", "")
    DB_NAME = os.getenv("DB_NAME", "webting_db")

    DRY_RUN = os.getenv("DRY_RUN", "False").lower() == "true"
    UPDATE_EXISTING = os.getenv("UPDATE_EXISTING", "True").lower() == "true"

    DEFAULT_SUBJECT = os.getenv("DEFAULT_SUBJECT", "Matemáticas")
    DEFAULT_DIFFICULTY = os.getenv("DEFAULT_DIFFICULTY", "Fácil")
    DEFAULT_AGE_RANGE = os.getenv("DEFAULT_AGE_RANGE", "6-8")
    DEFAULT_COMPETENCY = os.getenv("DEFAULT_COMPETENCY", "Competencia general")

    DEFAULT_SCROLL = int(os.getenv("DEFAULT_SCROLL", 0))
    DEFAULT_FULLSCREEN = int(os.getenv("DEFAULT_FULLSCREEN", 1))
    DEFAULT_SCORING = int(os.getenv("DEFAULT_SCORING", 1))