# Deploy CoachNow: GitHub → cPanel

Push code to GitHub from your PC; cPanel pulls and runs Laravel deploy steps automatically.

## Overview

```
Your PC  →  git push  →  GitHub  →  webhook / Deploy  →  cPanel server
```

## Part 1 — GitHub (one time, on your PC)

Already done if this repo is on GitHub. If not:

```bash
git init -b main
git add -A
git commit -m "Initial commit"
gh repo create coachnow --private --source=. --remote=origin --push
```

Day-to-day workflow:

```bash
git add .
git commit -m "Describe your change"
git push origin main
```

## Part 2 — cPanel setup (one time)

### 1. Clone the repo

1. Log in to **cPanel**
2. Open **Git Version Control**
3. Click **Create**
4. Clone URL: `https://github.com/YOUR_USERNAME/coachnow.git`
5. Repository Path: `/home/YOUR_CPANEL_USER/coachnow`  
   (keep it **outside** `public_html`)
6. Clone

### 2. Create `.env` on the server

In cPanel **Terminal** (or File Manager):

```bash
cd ~/coachnow
cp .env.example .env
php artisan key:generate
```

Edit `.env` for production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# MySQL (typical on cPanel — create DB in cPanel first)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=youruser_coachnow
DB_USERNAME=youruser_dbuser
DB_PASSWORD=your_db_password
```

Create the MySQL database and user in cPanel → **MySQL Databases**, then run:

```bash
php artisan migrate --force
```

### 3. Point the domain to Laravel `public/`

**Option A — subdomain or main domain (recommended)**

cPanel → **Domains** → your domain → **Document Root**  
Set to: `/home/YOUR_CPANEL_USER/coachnow/public`

**Option B — document root is the project folder (public_html = coachnow root)**

If cPanel will **not** let you set document root to `public/`, do **not** copy `public/index.php` by hand. Use the repo’s **root** files instead:

- `index.php` (project root) — paths already correct for this layout  
- `.htaccess` (project root) — routes requests and blocks `app/`, `.env`, etc.

Keep the originals in `public/` as well. After pull, your web root should contain root `index.php` + `.htaccess`.

**Option C — subdomain**

Use a subdomain like `app.yourdomain.com` with document root `coachnow/public`.

### 4. First deploy (install dependencies)

**Important:** `php artisan` will do nothing useful until `vendor/` exists. Run Composer first.

```bash
cd ~/coachnow   # or ~/public_html/your-site-folder

# Use PHP 8.2+ on cPanel (pick the path your host provides):
php -v
# If version is below 8.2, try:
# /usr/local/bin/ea-php82 $(which composer) install --no-dev --optimize-autoloader

composer install --no-dev --optimize-autoloader

# SQLite on server (if DB_CONNECTION=sqlite in .env):
touch database/database.sqlite
chmod 664 database/database.sqlite

cp .env.example .env   # skip if .env already exists
php artisan key:generate
php artisan migrate --force

bash deploy/cpanel-deploy.sh
```

**Artisan shows no output?** Run the diagnostic script:

```bash
bash deploy/check-server.sh
```

Common fixes:
- `composer install` not run → no `vendor/` folder
- Wrong PHP CLI (7.x) → use **Select PHP Version** in cPanel for CLI, or `/usr/local/bin/ea-php82 artisan ...`
- Missing SQLite file → `touch database/database.sqlite`
- Permissions → `chmod -R 775 storage bootstrap/cache database`

### 5. Auto-deploy pipeline (push → live on cPanel)

The repo includes:

- **`.cpanel.yml`** — runs on cPanel **Deploy HEAD Commit** (composer, migrate, cache)
- **`.github/workflows/deploy-cpanel.yml`** — triggers cPanel webhook on every push to `main`

**One-time setup:**

1. **cPanel → Git Version Control → Manage** your repo  
2. Copy the **Pull or Deploy webhook URL** (sometimes under “Deploy” or “Webhook”)
3. **GitHub → coachnow → Settings → Secrets and variables → Actions → New secret**
   - Name: `CPANEL_DEPLOY_URL`
   - Value: paste the cPanel webhook URL
4. In cPanel, click **Pull or Deploy** once to run the first deploy with `.cpanel.yml`

**Daily workflow:**

```bash
git add .
git commit -m "Your change"
git push origin main
```

GitHub Actions calls the cPanel webhook → server pulls code → `.cpanel.yml` runs deploy tasks.

**No webhook on your host?** After each push: cPanel → Git → **Manage → Pull or Deploy**, or Terminal:

```bash
cd ~/public_html/coachnow.serverlinktestwebsites.com
git pull origin main
bash deploy/cpanel-deploy.sh
```

### 6. Fix asset 404s (CSS / images / JS)

Assets live in **`public/assets/`**. If your domain points at the **project root** (not `public/`), the repo includes:

- Root **`index.php`** (correct Laravel paths)
- Root **`.htaccess`** (maps `/assets/*` → `public/assets/*`)

After pull, confirm these files exist in your cPanel folder. Then:

```bash
php artisan config:clear
php artisan view:clear
```

Hard-refresh the browser (Ctrl+Shift+R).

## Part 3 — Every time you ship a feature

On your PC:

```bash
git add .
git commit -m "Add booking flow"
git push origin main
```

On cPanel (if no webhook):

- **Git Version Control** → **Manage** → **Pull or Deploy**

Or Terminal:

```bash
cd ~/coachnow
git pull origin main
bash deploy/cpanel-deploy.sh
```

## Requirements on cPanel

- PHP **8.2+** (Select PHP Version)
- Extensions: `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`
- **Composer** (often `composer` or `/usr/local/bin/composer` in Terminal)
- Writable `storage/` and `bootstrap/cache/`

## Troubleshooting

| Issue | Fix |
|-------|-----|
| 500 after moving `index.php` | Root `index.php` must use `/vendor/` not `/../vendor/`. Use repo root `index.php` + `.htaccess`, or set document root to `public/` |
| 500 error (general) | Temporarily set `APP_DEBUG=true` in `.env` to see the error; check `storage/logs/laravel.log` |
| 500 error | Run `composer install --no-dev`; ensure `APP_KEY=` is set (`php artisan key:generate`); `chmod -R 775 storage bootstrap/cache` |
| Composer not found | Use cPanel Terminal path from host docs, or install Composer locally in home dir |
| Old config after deploy | Run `php artisan config:clear` then `config:cache` |
| CSS/JS 404 | Document root must be `public/`, **or** use root `.htaccess` which serves files from `public/assets/` |
| SQLite on server | Prefer MySQL on cPanel; update `.env` `DB_*` |

## Security

- Never commit `.env` (already in `.gitignore`)
- Set `APP_DEBUG=false` in production
- Use a **private** GitHub repo if the code is not public
