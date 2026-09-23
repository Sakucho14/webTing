import os, csv
from datetime import datetime
from colorama import Fore, init
from config import Config
from pdf_parser import parse_pdf
from db_manager import DBManager, build_game_payload

init(autoreset=True)
LOG_DIR = "logs"
os.makedirs(LOG_DIR, exist_ok=True)


def log_report(records, results):
    ts = datetime.now().strftime("%Y%m%d_%H%M%S")
    path = os.path.join(LOG_DIR, f"import_{ts}.csv")
    with open(path, "w", newline="", encoding="utf-8") as f:
        w = csv.writer(f)
        w.writerow(["name", "subject", "difficulty", "age_range", "competency",
                    "game_url", "image_url", "game_id", "status"])
        for rec, res in zip(records, results):
            w.writerow([
                rec.name, rec.subject or "", rec.difficulty or "", rec.age_range or "",
                rec.competency or "", rec.game_url, rec.image_url,
                res.get("id", ""), res.get("status", "")
            ])
    print(f"{Fore.CYAN}[LOG] Reporte guardado en: {path}")


def _print_preview(records):
    print(f"{Fore.YELLOW}   Vista previa:")
    for r in records:
        meta = " | ".join(filter(None, [
            r.subject, r.difficulty, r.age_range,
            (r.competency[:25] + "…") if r.competency and len(r.competency) > 25 else r.competency
        ]))
        print(f"   • {r.name}")
        print(f"       URL: {r.game_url}")
        print(f"       IMG: {r.image_url or '—'}")
        print(f"       META: {meta or '— (usará defaults)'}")


def run(pdf_path):
    if not os.path.isfile(pdf_path):
        print(f"{Fore.RED}[!] No existe el archivo: {pdf_path}")
        return

    print(f"{Fore.YELLOW}[1/3] Parseando PDF...")
    records = parse_pdf(pdf_path)
    print(f"{Fore.GREEN}    → {len(records)} juegos detectados.")

    if not records:
        print(f"{Fore.RED}[!] No se encontraron juegos.")
        return

    if Config.DRY_RUN:
        print(f"{Fore.YELLOW}[DRY-RUN] No se escribirá en la BD.")
        _print_preview(records)
        return

    print(f"{Fore.YELLOW}[2/3] Conectando a BD y aplicando cambios...")
    db = DBManager()
    results = []
    counts = {"inserted": 0, "updated": 0, "skipped": 0, "error": 0}

    for i, rec in enumerate(records, 1):
        payload = build_game_payload(rec, i)

        if db.game_exists(payload["id"]) and not Config.UPDATE_EXISTING:
            new_id = db.ensure_unique_id(payload["id"])
            print(f"{Fore.MAGENTA}    [i] ID '{payload['id']}' ocupado → '{new_id}'")
            payload["id"] = new_id

        status = db.upsert_game(payload)
        counts[status] = counts.get(status, 0) + 1

        color = {"inserted": Fore.GREEN, "updated": Fore.CYAN,
                 "skipped": Fore.YELLOW, "error": Fore.RED}.get(status, Fore.WHITE)
        icon = {"inserted": "＋", "updated": "↻", "skipped": "•", "error": "✗"}.get(status, "?")

        meta_bits = " ".join(filter(None, [
            f"[{payload['subject']}]" if payload['subject'] else "",
            f"({payload['difficulty']})" if payload['difficulty'] else "",
        ]))
        print(f"    {color}{icon} [{i}/{len(records)}] {payload['title']} {meta_bits} → {status}")

        results.append({"id": payload["id"], "status": status})

    db.close()

    print(f"\n{Fore.YELLOW}[3/3] Resumen:")
    print(f"   {Fore.GREEN}Insertados:   {counts['inserted']}")
    print(f"   {Fore.CYAN}Actualizados: {counts['updated']}")
    print(f"   {Fore.YELLOW}Omitidos:     {counts['skipped']}")
    print(f"   {Fore.RED}Errores:      {counts['error']}")
    log_report(records, results)