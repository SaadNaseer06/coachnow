#!/bin/bash
# Called by GitHub Actions over SSH after each push to main.
set -e

APP_DIR="${CPANEL_DEPLOY_PATH:-/home/serverlinktestwe/public_html/coachnow.serverlinktestwebsites.com}"
REPO="SaadNaseer06/coachnow"
BRANCH="${DEPLOY_BRANCH:-main}"

cd "$APP_DIR"

if [ -n "$GITHUB_DEPLOY_TOKEN" ]; then
  git remote set-url origin "https://x-access-token:${GITHUB_DEPLOY_TOKEN}@github.com/${REPO}.git"
fi

git fetch origin "$BRANCH"
git reset --hard "origin/${BRANCH}"

bash deploy/cpanel-deploy.sh
