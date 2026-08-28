# Auto-deploy: GitHub → cPanel

After **one-time setup**, every `git push origin main` updates the live site automatically.

```
Your PC  →  git push  →  GitHub Actions  →  SSH into cPanel  →  git pull + deploy  →  live
```

---

## One-time setup

### A — cPanel (server)

#### 1. Enable SSH
cPanel → **Security** → **SSH Access** → enable / manage keys.

Generate a key pair (or use Terminal):

```bash
ssh-keygen -t ed25519 -C "github-deploy" -f ~/.ssh/github_deploy -N ""
cat ~/.ssh/github_deploy.pub >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
```

Copy the **private** key — you will paste it into GitHub:

```bash
cat ~/.ssh/github_deploy
```

#### 2. Create `.env` on the server

cPanel → **File Manager** → site folder → copy `.env.example` to `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://coachnow.serverlinktestwebsites.com

DB_CONNECTION=sqlite
SESSION_DRIVER=database
CACHE_STORE=database
```

#### 3. First deploy (Terminal, one time)

```bash
cd ~/public_html/coachnow.serverlinktestwebsites.com
bash deploy/cpanel-deploy.sh
```

---

### B — GitHub (repository secrets)

Open: **https://github.com/SaadNaseer06/coachnow/settings/secrets/actions**

Add these **6 repository secrets**:

| Secret name | Value |
|-------------|--------|
| `CPANEL_SSH_HOST` | `serverlinktestwebsites.com` (or your server IP) |
| `CPANEL_SSH_USER` | `serverlinktestwe` |
| `CPANEL_SSH_KEY` | Full private key from step A (`-----BEGIN ... END-----`) |
| `CPANEL_DEPLOY_PATH` | `/home/serverlinktestwe/public_html/coachnow.serverlinktestwebsites.com` |
| `GITHUB_DEPLOY_TOKEN` | GitHub Personal Access Token (repo read access) |
| `CPANEL_SSH_PORT` | `22` (optional — omit if default) |

**GitHub token** (for private repo pull on server):

1. GitHub → **Settings → Developer settings → Personal access tokens**
2. Generate token with **Contents: Read** on the `coachnow` repo
3. Paste as `GITHUB_DEPLOY_TOKEN`

**Done.** Push to test:

```bash
git push origin main
```

Check **GitHub → Actions → Deploy to cPanel** — should show green.

---

## Daily workflow

```bash
git add .
git commit -m "Your change"
git push origin main
```

Site updates in ~30–60 seconds. No cPanel clicks, no manual commands.

---

## What runs on each deploy

`deploy/remote-deploy.sh` (via SSH):

1. `git fetch` + `git reset --hard origin/main`
2. `deploy/cpanel-deploy.sh` → composer install, migrate, cache rebuild

---

## Alternative: cPanel webhook only (no SSH)

If SSH is not available on your host, use one secret instead:

| Secret | Value |
|--------|--------|
| `CPANEL_DEPLOY_URL` | cPanel → Git Version Control → Manage → Pull or Deploy URL |

Also click **Update from Remote** once in cPanel so `.cpanel.yml` exists on the server.

Do **not** configure both SSH secrets and `CPANEL_DEPLOY_URL`.

---

## Troubleshooting

| Issue | Fix |
|-------|-----|
| Actions fails: permission denied | Check `CPANEL_SSH_KEY` is the full private key; public key is in `authorized_keys` |
| Actions fails: git fetch | Set `GITHUB_DEPLOY_TOKEN` with repo read access |
| Actions skipped / error "No deploy secrets" | Add secrets from table above |
| 500 on site | Ensure `.env` exists on server with `APP_KEY=` set |
| CSS/images 404 | Confirm root `.htaccess` and `index.php` exist after deploy |
| Check server | SSH in and run: `bash deploy/check-server.sh` |

---

## Requirements

- PHP **8.2+** on cPanel
- SSH access enabled
- Writable `storage/` and `bootstrap/cache/`

## Security

- Never commit `.env` or SSH private keys to GitHub
- Use `APP_DEBUG=false` in production
- Rotate `GITHUB_DEPLOY_TOKEN` if exposed
