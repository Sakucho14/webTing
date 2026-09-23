import re
import pdfplumber
from utils import (
    clean_url, normalize_subject, normalize_difficulty, normalize_age_range
)

URL_RE = re.compile(r"https?://[^\s\)\]\}\,\"]+")

# Patrones de metadatos (tolerantes)
META_PATTERNS = {
    "subject":      re.compile(r"(?:materia|asignatura|área|area)\s*[:\-]\s*(.+)", re.I),
    "difficulty":   re.compile(r"(?:dificultad|nivel)\s*[:\-]\s*(.+)", re.I),
    "age_range":    re.compile(r"(?:edad|edades|age)\s*[:\-]\s*(.+)", re.I),
    "competency":   re.compile(r"(?:competencia|habilidad|skill)\s*[:\-]\s*(.+)", re.I),
    "description":  re.compile(r"(?:descripci[oó]n|desc)\s*[:\-]\s*(.+)", re.I),
}


class GameRecord:
    def __init__(self, name, game_url, image_url="", subject=None,
                 difficulty=None, age_range=None, competency=None,
                 description=None, raw=""):
        self.name = name
        self.game_url = game_url
        self.image_url = image_url
        self.subject = subject
        self.difficulty = difficulty
        self.age_range = age_range
        self.competency = competency
        self.description = description
        self.raw = raw

    def to_dict(self):
        return {
            "name": self.name, "game_url": self.game_url, "image_url": self.image_url,
            "subject": self.subject, "difficulty": self.difficulty,
            "age_range": self.age_range, "competency": self.competency,
            "description": self.description,
        }

    def __repr__(self):
        return f"<GameRecord name={self.name!r} subj={self.subject} diff={self.difficulty}>"


def _find_urls(text):
    return [clean_url(u) for u in URL_RE.findall(text or "")]


def _is_image_url(url):
    if not url:
        return False
    u = url.lower()
    return any(x in u for x in [".png", ".jpg", ".jpeg", ".gif", ".webp", ".svg",
                                "unsplash.com", "imgur.com", "cloudinary", "logo"])


def _detect_meta(line):
    """Devuelve (campo, valor) si la línea es un metadato."""
    for field, pat in META_PATTERNS.items():
        m = pat.search(line)
        if m:
            val = m.group(1).strip()
            val = re.split(r"\s{2,}|\|", val)[0].strip()  # corta si hay ruido
            return field, val
    return None, None


def _finalize_record(current_name, meta, urls, raw):
    """Construye un GameRecord a partir de los datos acumulados."""
    if not current_name or not urls:
        return None
    game_url = next((u for u in urls if not _is_image_url(u)), "")
    img_url = next((u for u in urls if _is_image_url(u)), "")
    if not game_url:
        return None
    return GameRecord(
        name=current_name.strip(),
        game_url=game_url,
        image_url=img_url,
        subject=meta.get("subject"),
        difficulty=meta.get("difficulty"),
        age_range=meta.get("age_range"),
        competency=meta.get("competency"),
        description=meta.get("description"),
        raw=raw,
    )


def parse_pdf(pdf_path):
    games = []
    with pdfplumber.open(pdf_path) as pdf:
        for page in pdf.pages:
            # --- 1. Tablas ---
            for table in page.extract_tables() or []:
                for row in table:
                    cells = [c.strip() if c else "" for c in row]
                    joined = " | ".join(cells)
                    urls = [u for c in cells for u in _find_urls(c)]
                    if not urls:
                        continue
                    name = next((c for c in cells if c and not _find_urls(c)
                                 and not _detect_meta(c)[0]), "")
                    meta = {}
                    for c in cells:
                        f, v = _detect_meta(c)
                        if f:
                            meta[f] = v
                    rec = _finalize_record(name, meta, urls, joined)
                    if rec:
                        games.append(rec)

            # --- 2. Texto general ---
            text = page.extract_text() or ""
            lines = [l.strip() for l in text.split("\n") if l.strip()]

            current_name = None
            meta = {}
            pending_urls = []

            for line in lines:
                # Metadato explícito
                field, value = _detect_meta(line)
                if field:
                    meta[field] = value
                    continue

                urls = _find_urls(line)

                # Línea sin URL → posible nombre (reinicia meta y urls)
                if not urls:
                    if re.match(r"^(nombre|juego|link|url|imagen|logo)\b", line, re.I):
                        continue
                    if 3 <= len(line) <= 120:
                        # Si ya teníamos un name + urls pendientes, cerramos el registro anterior
                        if current_name and pending_urls:
                            rec = _finalize_record(current_name, meta, pending_urls, "")
                            if rec:
                                games.append(rec)
                        current_name = line
                        meta = {}
                        pending_urls = []
                    continue

                # Línea con URL(s)
                pending_urls.extend(urls)

                # Si aún no hay nombre, intenta extraerlo de la línea
                if not current_name:
                    cleaned = line
                    for u in urls:
                        cleaned = cleaned.replace(u, "")
                    cleaned = re.sub(r"(link|url|imagen|logo|juego)\s*:?", "", cleaned, flags=re.I)
                    cleaned = re.sub(r"[-–—:|]", " ", cleaned).strip()
                    if len(cleaned) >= 3:
                        current_name = cleaned

                # Cerramos si ya tenemos nombre + al menos 1 URL no-imagen
                if current_name and any(not _is_image_url(u) for u in pending_urls):
                    rec = _finalize_record(current_name, meta, pending_urls, line)
                    if rec:
                        games.append(rec)
                    current_name = None
                    meta = {}
                    pending_urls = []

            # Cerrar último registro de la página
            if current_name and pending_urls:
                rec = _finalize_record(current_name, meta, pending_urls, "")
                if rec:
                    games.append(rec)

    # Deduplicar por (game_url, name)
    seen, unique = set(), []
    for g in games:
        key = (g.game_url, g.name.lower())
        if key in seen:
            continue
        seen.add(key)
        unique.append(g)

    # Normalizar metadatos detectados
    for g in unique:
        if g.subject:    g.subject = normalize_subject(g.subject)
        if g.difficulty: g.difficulty = normalize_difficulty(g.difficulty)
        if g.age_range:  g.age_range = normalize_age_range(g.age_range)

    return unique