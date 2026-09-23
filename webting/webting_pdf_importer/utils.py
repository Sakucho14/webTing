import re
import unicodedata

# ----------------- Normalizadores -----------------

SUBJECT_ALIASES = {
    "matematicas": "Matemáticas", "matemática": "Matemáticas", "mates": "Matemáticas",
    "ciencias": "Ciencias", "naturales": "Ciencias", "biologia": "Ciencias",
    "lenguaje": "Lenguaje", "espanol": "Lenguaje", "español": "Lenguaje", "literatura": "Lenguaje",
    "historia": "Historia", "sociales": "Historia",
}


def slugify(text: str, max_len: int = 45) -> str:
    text = unicodedata.normalize("NFKD", text).encode("ascii", "ignore").decode("utf-8")
    text = re.sub(r"[^\w\s-]", "", text).strip().lower()
    text = re.sub(r"[-\s]+", "-", text)
    return text[:max_len].strip("-") or "game"


def normalize_difficulty(value: str) -> str:
    """ENUM: 'Facil' | 'Medio' | 'Dificil'"""
    if not value:
        return "Facil"
    v = unicodedata.normalize("NFKD", value).encode("ascii", "ignore").decode().lower()
    if "dificil" in v: return "Dificil"
    if "medio" in v or "intermedio" in v or "medium" in v: return "Medio"
    return "Facil"


def normalize_subject(value: str) -> str:
    """Debe coincidir con tus chips: Matemáticas, Ciencias, Lenguaje, Historia."""
    if not value:
        return "Matemáticas"
    v = unicodedata.normalize("NFKD", value).encode("ascii", "ignore").decode().lower().strip()
    for key, canonical in SUBJECT_ALIASES.items():
        if key in v:
            return canonical
    return value.strip().capitalize()


def normalize_age_range(value: str) -> str:
    """Limita a algo como '6-8', '9-12', '12+'. Si viene '6 a 8' lo arregla."""
    if not value:
        return "6-8"
    v = value.lower().replace("a", "-").replace("–", "-").replace("—", "-")
    v = re.sub(r"\s+", "", v)
    m = re.search(r"(\d{1,2})\s*-\s*(\d{1,2})", v)
    if m:
        return f"{m.group(1)}-{m.group(2)}"
    m = re.search(r"(\d{1,2})\s*\+", v)
    if m:
        return f"{m.group(1)}+"
    return "6-8"


def clean_url(url: str) -> str:
    if not url:
        return ""
    return url.strip().replace("\n", "").replace(" ", "")