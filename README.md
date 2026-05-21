# AI Image Quest

A benchmarking platform that evaluates AI vision models on their ability to correctly answer questions about images. Models are tested via [OpenRouter](https://openrouter.ai) and results are displayed on a public leaderboard.

## Architecture

```
aiimagequest/
├── config.php              # DB credentials & path constants (gitignored — copy from config.example.php)
├── public_html/            # PHP web frontend (point web server here)
│   ├── index.php           # Main leaderboard
│   ├── images.php          # Image browser with category filter
│   ├── image.php           # Per-image detail & model breakdown
│   ├── db_connect.php      # mysqli connection factory
│   └── admin/              # Image upload & management tools
├── scripts/                # Python backend
│   ├── benchmark.py        # Run evaluations against all pending model/question pairs
│   ├── generate_descriptions.py  # Bulk-generate image descriptions via AI
│   ├── generate_single_desc.py   # Single image description (called by PHP admin)
│   ├── api_client.py       # OpenRouter API wrapper
│   ├── data_loader.py      # DB read helpers
│   ├── db_utils.py         # DB write helpers
│   ├── scoring.py          # 4-tier scoring logic
│   ├── config.py           # Prompt templates, scoring threshold, BASE_URL
│   ├── pyproject.toml      # Dependencies (Python 3.13+, managed by uv)
│   └── .env                # API keys & DB credentials (gitignored — copy from .env.example)
└── temp_uploads/           # Transient image uploads (content gitignored)
```

## Setup

### Prerequisites

- PHP 8.0+ with `mysqli` extension
- MySQL 8.0+
- [uv](https://docs.astral.sh/uv/) (Python package manager)

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

Paste the output as the value of `ADMIN_PASSWORD_HASH` in `config.php`. Login at `/admin/login.php`.

### 5. Web server

Point your document root at `public_html/`. The `config.php` in the project root must be readable by PHP but **must not be web-accessible**.

```apache
# Apache example
DocumentRoot /path/to/aiimagequest/public_html
```

The `temp_uploads/` directory must be writable by the web server user.

> **`PYTHON_BIN` path**: Linux/macOS use `scripts/.venv/bin/python`; Windows uses `scripts/.venv/Scripts/python.exe`.

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

| File | Purpose |
|------|---------|
| `config.php` | PHP: DB credentials, derived path constants (`BASE_DIR`, `TEMP_UPLOADS_DIR`, etc.) |
| `scripts/.env` | Python: `OPENROUTER_API_KEY`, DB credentials, `SITE_BASE_URL` |
| `scripts/config.py` | Prompt templates, fuzzy match threshold, `BASE_URL` default |

## Scoring

Each model response scores as one of four outcomes:

| Score | Meaning |
|-------|---------|
| **Correct** | Exact match or fuzzy similarity ≥ 90% |
| **Incorrect** | Answer present but wrong |
| **Not Found** | Model responded "Information not available" |
| **Refusal** | Model refused to answer |
