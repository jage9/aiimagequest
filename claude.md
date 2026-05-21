# AI Image Quest - Dev Context

## Project
Benchmarking platform for AI vision models via OpenRouter. PHP frontend + Python backend.
Full setup/usage docs in `README.md`.

## Key Files
- `config.php` — gitignored; DB creds + `BASE_DIR`, `TEMP_UPLOADS_DIR`, `SCRIPTS_DIR`, `PYTHON_BIN`, `IMAGES_DIR`
- `public_html/db_connect.php` — loads config via `dirname(__DIR__) . '/config.php'`
- `public_html/admin/` — image upload/management; loads config via `dirname(__DIR__, 2) . '/config.php'`
- `scripts/.env` — gitignored; `OPENROUTER_API_KEY`, DB vars, `SITE_BASE_URL`
- `scripts/config.py` — prompt templates, `FUZZY_MATCH_THRESHOLD`, `BASE_URL` (from `SITE_BASE_URL` env)

## Conventions

### PHP
- Raw `mysqli`, procedural, mixed HTML/PHP templates
- Never hardcode server paths — use constants from `config.php`
- `admin/` scripts: `require_once dirname(__DIR__, 2) . '/config.php'` (2 levels up to project root)

### Python
- Modular flat structure: `api_client`, `db_utils`, `data_loader`, `scoring`, `config`
- Run from `scripts/` dir: `uv run python <script>.py`
- Venv at `scripts/.venv/` (managed by uv, Python 3.13+)
- DB creds via `.env` / `python-dotenv`
- Image URLs via `config.BASE_URL` (never hardcoded)

## Common Commands
- Run benchmark: `cd scripts && uv run python benchmark.py`
- Generate descriptions: `cd scripts && uv run python generate_descriptions.py`
- Add image: `/admin/add_image.php`
