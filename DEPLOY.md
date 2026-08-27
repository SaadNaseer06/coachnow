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

**Option B — app in subfolder**

Use a subdomain like `app.yourdomain.com` with document root `coachnow/public`.

### 4. First deploy (install dependencies)

```bash
cd ~/coachnow
composer install --no-dev --optimize-autoloader
bash deploy/cpanel-deploy.sh
```

Or set `APP_DIR=~/coachnow` and run the script.

### 5. Enable auto-deploy from GitHub

1. Edit **`.cpanel.yml`** in the repo: replace `YOUR_CPANEL_USER` with your cPanel username, commit, and push.
2. In cPanel → **Git Version Control** → your repo → **Manage**
3. Copy the **Webhook URL** (if shown)
4. On GitHub → repo **Settings** → **Webhooks** → **Add webhook**
   - Payload URL: paste cPanel webhook URL
   - Content type: `application/json`
   - Events: **Just the push event**
5. Save

After this, each `git push` to `main` can trigger pull + deploy (depending on your host’s Git UI — some hosts use **Pull or Deploy** manually instead).

If webhooks are not available, use **Git Version Control → Manage → Pull or Deploy** after each push.

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
| 500 error | Check `storage/logs/laravel.log`; fix `.env`; `chmod -R 775 storage bootstrap/cache` |
| Composer not found | Use cPanel Terminal path from host docs, or install Composer locally in home dir |
| Old config after deploy | Run `php artisan config:clear` then `config:cache` |
| CSS/JS 404 | Confirm document root is `public/`, not project root |
| SQLite on server | Prefer MySQL on cPanel; update `.env` `DB_*` |

## Security

- Never commit `.env` (already in `.gitignore`)
- Set `APP_DEBUG=false` in production
- Use a **private** GitHub repo if the code is not public
