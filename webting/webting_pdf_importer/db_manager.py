import mysql.connector
from mysql.connector import Error
from config import Config
from utils import (
    slugify, normalize_subject, normalize_difficulty, normalize_age_range
)


class DBManager:
    def __init__(self):
        self.conn = None
        self._connect()

    def _connect(self):
        try:
            self.conn = mysql.connector.connect(
                host=Config.DB_HOST, port=Config.DB_PORT,
                user=Config.DB_USER, password=Config.DB_PASSWORD,
                database=Config.DB_NAME, charset="utf8mb4", autocommit=False,
            )
            print(f"[DB] Conectado a {Config.DB_NAME}@{Config.DB_HOST}")
        except Error as e:
            raise SystemExit(f"[DB] Error de conexión: {e}")

    def game_exists(self, game_id):
        cur = self.conn.cursor()
        cur.execute("SELECT 1 FROM games WHERE id=%s", (game_id,))
        r = cur.fetchone()
        cur.close()
        return r is not None

    def insert_game(self, g):
        sql = """
        INSERT INTO games
        (id, title, subject, difficulty, age_range, competency,
         logo_url, hover_description, iframe_url, iframe_scroll,
         iframe_sandbox, iframe_fullscreen, scoring_enabled)
        VALUES (%(id)s, %(title)s, %(subject)s, %(difficulty)s, %(age_range)s, %(competency)s,
                %(logo_url)s, %(hover_description)s, %(iframe_url)s, %(iframe_scroll)s,
                %(iframe_sandbox)s, %(iframe_fullscreen)s, %(scoring_enabled)s)
        """
        try:
            cur = self.conn.cursor()
            cur.execute(sql, g)
            self.conn.commit()
            cur.close()
            return True
        except Error as e:
            self.conn.rollback()
            print(f"[DB] Error insertando '{g['title']}': {e}")
            return False

    def update_game(self, g):
        sql = """
        UPDATE games SET
            title=%(title)s, subject=%(subject)s, difficulty=%(difficulty)s,
            age_range=%(age_range)s, competency=%(competency)s, logo_url=%(logo_url)s,
            hover_description=%(hover_description)s, iframe_url=%(iframe_url)s,
            iframe_scroll=%(iframe_scroll)s, iframe_sandbox=%(iframe_sandbox)s,
            iframe_fullscreen=%(iframe_fullscreen)s, scoring_enabled=%(scoring_enabled)s
        WHERE id=%(id)s
        """
        try:
            cur = self.conn.cursor()
            cur.execute(sql, g)
            self.conn.commit()
            cur.close()
            return True
        except Error as e:
            self.conn.rollback()
            print(f"[DB] Error actualizando '{g['id']}': {e}")
            return False

    def upsert_game(self, g):
        if self.game_exists(g["id"]):
            if Config.UPDATE_EXISTING:
                return "updated" if self.update_game(g) else "error"
            return "skipped"
        return "inserted" if self.insert_game(g) else "error"

    def ensure_unique_id(self, base_id):
        if not self.game_exists(base_id):
            return base_id
        i = 2
        while self.game_exists(f"{base_id}-{i}") and i < 999:
            i += 1
        return f"{base_id}-{i}"

    def close(self):
        if self.conn and self.conn.is_connected():
            self.conn.close()


def build_game_payload(record, index):
    """
    Construye el dict final. Si el PDF trajo metadatos, se usan;
    si no, se aplican los defaults de Config.
    """
    base_id = slugify(record.name or f"juego-{index}")
    subject = record.subject or Config.DEFAULT_SUBJECT
    difficulty = record.difficulty or Config.DEFAULT_DIFFICULTY
    age_range = record.age_range or Config.DEFAULT_AGE_RANGE
    competency = record.competency or Config.DEFAULT_COMPETENCY

    desc = record.description or f"{record.name} — añadido automáticamente desde PDF."

    return {
        "id": base_id,
        "title": record.name.strip()[:150],
        "subject": normalize_subject(subject),
        "difficulty": normalize_difficulty(difficulty),
        "age_range": normalize_age_range(age_range),
        "competency": competency[:100],
        "logo_url": record.image_url or None,
        "hover_description": desc,
        "iframe_url": record.game_url,
        "iframe_scroll": Config.DEFAULT_SCROLL,
        "iframe_sandbox": "",
        "iframe_fullscreen": Config.DEFAULT_FULLSCREEN,
        "scoring_enabled": Config.DEFAULT_SCORING,
    }