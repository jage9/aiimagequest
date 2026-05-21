# AI Image Quest

Benchmarking platform that tests how accurately AI vision models can read and interpret real-world images — text on signs, product labels, safety information, and similar content relevant to accessibility use cases. Models are evaluated via [OpenRouter](https://openrouter.ai) and ranked on a public leaderboard.

> **This is an early prototype. All feedback is welcome.**

## How it works

1. Images are uploaded through the admin panel, each paired with a specific question and correct answer
2. The Python benchmark engine queries every configured model with each image + question
3. Responses are scored using exact match, fuzzy match, and pattern detection for refusals
4. Results are stored in MySQL and displayed on the public leaderboard at `public_html/index.php`

## Architecture

```
aiimagequest/
├── config.php              # DB credentials & path constants (gitignored — copy from config.example.php)
├── config.example.php      # Safe-to-commit template
├── public_html/            # PHP web frontend (document root)
│   ├── index.php           # Leaderboard
│   ├── images.php          # Image browser with category filter
│   ├── image.php           # Per-image model breakdown
│   └── admin/              # Image upload & management (session-protected)
├── scripts/                # Python benchmark engine
│   ├── benchmark.py        # Run evaluations against all pending model/question pairs
│   ├── generate_descriptions.py  # Bulk AI description generation
│   ├── api_client.py       # OpenRouter API wrapper
│   ├── scoring.py          # 4-tier scoring logic
│   ├── pyproject.toml      # Dependencies (Python 3.13+, managed by uv)
│   └── .env                # API keys & DB credentials (gitignored — copy from .env.example)
├── database/
│   └── schema.sql          # Full MySQL schema
└── temp_uploads/           # Transient image uploads (content gitignored)
```

## Setup

### Prerequisites

- PHP 8.0+ with `mysqli` extension
- MySQL 8.0+
- [uv](https://docs.astral.sh/uv/) (Python package manager)
- An [OpenRouter](https://openrouter.ai) API key

### 1. Clone & configure

```bash
git clone https://github.com/Jage9/aiimagequest.git
cd aiimagequest
cp config.example.php config.php        # fill in DB credentials and verify paths
cp scripts/.env.example scripts/.env    # fill in OPENROUTER_API_KEY, DB creds, SITE_BASE_URL
```

### 2. Database

Create a MySQL database and import the schema:

```bash
mysql -u youruser -p yourdbname < database/schema.sql
```

### 3. Python environment

```bash
cd scripts
uv sync    # creates .venv and installs all dependencies
```

### 4. Admin password

Generate a bcrypt hash and set it in `config.php`:

```bash
php -r "echo password_hash('yourpassword', PASSWORD_DEFAULT);"
```

Paste the output as the value of `ADMIN_PASSWORD_HASH` in `config.php`. Log in at `/admin/login.php`.

### 5. Web server

Point your document root at `public_html/`. The `config.php` at the project root must be readable by PHP but **must not be web-accessible**.

```apache
# Apache example
DocumentRoot /path/to/aiimagequest/public_html
```

Make `temp_uploads/` writable by the web server user.

## Usage

### Run benchmarks

```bash
cd scripts
uv run python benchmark.py
```

Resumes automatically — already-evaluated model/question pairs are skipped.

### Generate image descriptions

```bash
cd scripts
uv run python generate_descriptions.py
```

Finds all images with missing descriptions and generates them via AI.

### Add images

Log in at `/admin/login.php`, then visit `/admin/add_image.php`.

## Configuration

| File | Key setting | Notes |
|------|-------------|-------|
| `config.php` | `DB_*` credentials | Also defines path constants (`BASE_DIR`, `TEMP_UPLOADS_DIR`, etc.) |
| `config.php` | `ADMIN_PASSWORD_HASH` | Output of `password_hash()` — see setup step 4 |
| `config.php` | `PYTHON_BIN` | Path to venv Python: `.venv/bin/python` (Linux/macOS) or `.venv/Scripts/python.exe` (Windows) |
| `scripts/.env` | `OPENROUTER_API_KEY` | Required for benchmarks and description generation |
| `scripts/.env` | `SITE_BASE_URL` | Public URL of the site, used to build image URLs for API calls |
| `scripts/config.py` | `FUZZY_MATCH_THRESHOLD` | Similarity score (0–100) for fuzzy correct matches, default 90 |

## Scoring

Each model response is classified as one of four outcomes:

| Score | Meaning |
|-------|---------|
| **Correct** | Answer matches exactly, or fuzzy similarity ≥ 90% |
| **Incorrect** | Model gave a definite answer but it was wrong |
| **Not Found** | Model indicated the information wasn't visible in the image |
| **Refusal** | Model declined to answer (content policy, safety filter, etc.) |

## License

MIT — see [LICENSE](LICENSE).
