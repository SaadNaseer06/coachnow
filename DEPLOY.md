# Auto-deploy: GitHub → cPanel

After **one-time setup**, every `git push origin main` updates the live site automatically. No SSH, no manual pull, no terminal commands.

```
Your PC  →  git push  →  GitHub  →  webhook  →  cPanel pulls + deploys
```

---

## One-time setup (about 10 minutes)

Do these steps **once**. After that, only `git push` is needed.

### Step 1 — Connect GitHub repo in cPanel

1. Log in to **cPanel**
2. Open **Git Version Control**
3. Click **Create**
4. Fill in:
   - **Clone URL:** `https://github.com/SaadNaseer06/coachnow.git`
   - **Repository Path:** `/home/serverlinktestwe/public_html/coachnow.serverlinktestwebsites.com`
5. Click **Create**

> Use a **GitHub Personal Access Token** as the password if the repo is private  
> (GitHub → Settings → Developer settings → Personal access tokens).

### Step 2 — Create `.env` on the server (one time)

cPanel → **File Manager** → open your site folder:

1. Copy `.env.example` → rename to `.env`
2. Edit `.env` — minimum settings:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://coachnow.serverlinktestwebsites.com

DB_CONNECTION=sqlite
SESSION_DRIVER=database
CACHE_STORE=database
```

Save the file. Do **not** commit `.env` to GitHub.

### Step 3 — First deploy (one click)

1. cPanel → **Git Version Control** → **Manage** (your repo)
2. Click **Pull or Deploy**

cPanel will:
- Pull the latest code from GitHub
- Run `.cpanel.yml` → `deploy/cpanel-deploy.sh`
- Install Composer dependencies, migrate DB, rebuild caches

Wait until it finishes (may take 1–3 minutes the first time).

### Step 4 — Add GitHub repository secret (enables auto-deploy)

This is the **only GitHub-side config** you need. Do **not** add a GitHub Webhook if you use this method.

1. In cPanel **Git Version Control → Manage**, copy the **Pull or Deploy URL**  
   (a long `https://...` link — same URL used for manual deploy)
2. Open **GitHub** → [coachnow](https://github.com/SaadNaseer06/coachnow) → **Settings** → **Secrets and variables** → **Actions**
3. Click **New repository secret**
4. Set:

| Field | Value |
|-------|--------|
| **Name** | `CPANEL_DEPLOY_URL` |
| **Secret** | Paste the cPanel Pull or Deploy URL |

5. Click **Add secret**

**Done.** On every `git push origin main`, GitHub Actions calls that URL → cPanel pulls and deploys.

Verify: after pushing, open **Actions** tab on GitHub — the **Deploy to cPanel** workflow should show green.

---

## Daily workflow (after setup)

On your PC:

```bash
git add .
git commit -m "Describe your change"
git push origin main
```

Within ~30–60 seconds, cPanel pulls and deploys. Refresh the site to see changes.

---

## What runs on each deploy

File **`.cpanel.yml`** triggers **`deploy/cpanel-deploy.sh`**, which:

1. `composer install --no-dev`
2. Creates `database/database.sqlite` if missing
3. `php artisan migrate --force`
4. Clears and rebuilds config / route / view caches
5. `php artisan storage:link`
6. Fixes permissions on `storage/`, `bootstrap/cache/`, `database/`

---

## Alternative: GitHub Webhook (skip if you use the secret)

Only use this if you prefer not to use GitHub Actions:

1. GitHub → **Settings → Webhooks → Add webhook**
2. **Payload URL** = same cPanel Pull or Deploy URL
3. **Events:** Just the push event

Do **not** use both the secret **and** the webhook — that deploys twice per push.

---

## Site layout (document root = project root)

This project uses root **`index.php`** + **`.htaccess`** because cPanel points the domain at the project folder (not `public/`). Assets in `public/assets/` are served via rewrite rules — no extra config needed after deploy.

---

## Troubleshooting

| Issue | Fix |
|-------|-----|
| Webhook shows red ✗ | Confirm Payload URL matches cPanel exactly; repo path in Step 1 is correct |
| Push does nothing | cPanel → Git → Manage → check **Pull or Deploy** manually once; review deploy log |
| 500 error | File Manager → check `.env` exists with `APP_KEY=` set; re-run Pull or Deploy |
| CSS/images 404 | Confirm root `.htaccess` and `index.php` exist after pull; hard-refresh browser |
| Composer fails | cPanel → MultiPHP INI Editor → enable `allow_url_fopen` for PHP 8.2 |
| Check server state | One-time in Terminal: `bash deploy/check-server.sh` |

---

## Requirements on cPanel

- PHP **8.2+** (Select PHP Version)
- Extensions: `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`
- Writable `storage/` and `bootstrap/cache/`

## Security

- Never commit `.env` (in `.gitignore`)
- Keep `APP_DEBUG=false` in production
- Use a private GitHub repo
