/**
 * Capture a visual baseline of all CoachNow screens.
 * Waits for / forces past the site preloader so screenshots are clean.
 * Run: node scripts/capture-ui-baseline.js
 */
import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const BASE = process.env.BASE_URL || 'http://127.0.0.1:8000';
const OUT = path.join(__dirname, '..', 'ui-baseline');
const SHOTS = path.join(OUT, 'screenshots');

const publicScreens = [
  ['01-home', '/'],
  ['02-find-a-coach', '/find-a-coach'],
  ['03-request-session', '/request-session'],
  ['04-become-a-coach', '/become-a-coach'],
  ['05-coach-profile', '/coach-profile'],
  ['06-player-dashboard', '/player-dashboard'],
  ['07-about', '/about'],
  ['08-faq', '/faq'],
  ['09-contact', '/contact'],
  ['10-login', '/login'],
];

const coachScreens = [
  ['11-coach-dashboard', '/coach/dashboard'],
  ['12-coach-schedule', '/coach/schedule'],
  ['13-coach-player-overview', '/coach/player-overview'],
  ['14-coach-player-detail', '/coach/players/jamie-smith'],
  ['15-coach-add-report', '/coach/add-report'],
];

const adminScreens = [
  ['16-admin-dashboard', '/admin'],
  ['17-admin-schedule', '/admin/schedule'],
  ['18-admin-coaches', '/admin/coaches'],
  ['19-admin-bookings', '/admin/bookings'],
  ['20-admin-locations', '/admin/locations'],
  ['21-admin-athletes', '/admin/athletes'],
];

async function preparePage(page) {
  // Force past preloader + reveal motion content so screenshots are not mid-load.
  await page.evaluate(() => {
    const root = document.documentElement;
    const preloader = document.getElementById('coachnowPreloader');

    if (preloader) {
      preloader.classList.add('is-loaded', 'is-finished');
      preloader.style.display = 'none';
      preloader.setAttribute('aria-hidden', 'true');
    }

    root.classList.remove('preloader-active');
    root.classList.add('hero-motion-started');

    if (typeof window.startCoachNowPostLoaderMotion === 'function') {
      window.startCoachNowPostLoaderMotion();
    }

    document.querySelectorAll('.motion-section').forEach((section) => {
      section.classList.add('is-visible');
    });

    document.querySelectorAll('.hero-fade-target, .motion-item').forEach((el) => {
      el.style.opacity = '1';
      el.style.transform = 'none';
      el.style.visibility = 'visible';
      el.style.animation = 'none';
      el.style.transition = 'none';
    });

    // Stop any leftover fixed overlays from covering the page.
    document.querySelectorAll('#coachnowPreloader, .preloader-bars, .preloader-bar').forEach((el) => {
      el.style.display = 'none';
      el.style.visibility = 'hidden';
      el.style.opacity = '0';
      el.style.pointerEvents = 'none';
    });
  });

  // Let images settle after reveal.
  await page.waitForTimeout(700);

  try {
    await page.waitForFunction(
      () => {
        const preloader = document.getElementById('coachnowPreloader');
        if (!preloader) return true;
        const style = window.getComputedStyle(preloader);
        return (
          preloader.classList.contains('is-finished') ||
          style.display === 'none' ||
          style.visibility === 'hidden' ||
          Number(style.opacity) === 0
        );
      },
      { timeout: 8000 }
    );
  } catch {
    // Continue anyway — we already forced hide above.
  }

  // Scroll to bottom then top so lazy sections / sticky headers settle.
  await page.evaluate(async () => {
    const delay = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
    const height = Math.max(document.body.scrollHeight, document.documentElement.scrollHeight);
    window.scrollTo(0, height);
    await delay(250);
    window.scrollTo(0, 0);
    await delay(250);
  });
}

async function shot(page, name, urlPath) {
  const url = BASE + urlPath;
  console.log(`Capturing ${name} → ${url}`);
  await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });

  // Prefer network idle, but don't hang forever on long-polling assets.
  try {
    await page.waitForLoadState('networkidle', { timeout: 15000 });
  } catch {
    await page.waitForTimeout(1000);
  }

  await preparePage(page);

  const file = path.join(SHOTS, `${name}.png`);
  await page.screenshot({ path: file, fullPage: true, animations: 'disabled' });
  return { name, url: urlPath, file: `screenshots/${name}.png` };
}

async function login(page, email, password) {
  await page.goto(BASE + '/login', { waitUntil: 'domcontentloaded' });
  await preparePage(page);
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="password"]', password);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
    page.click('button[type="submit"]'),
  ]);
  try {
    await page.waitForLoadState('networkidle', { timeout: 15000 });
  } catch {
    await page.waitForTimeout(800);
  }
}

async function logout(page) {
  await page.context().clearCookies();
}

async function main() {
  fs.mkdirSync(SHOTS, { recursive: true });
  const results = [];

  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 1440, height: 900 },
    deviceScaleFactor: 1,
  });
  const page = await context.newPage();

  for (const [name, url] of publicScreens) {
    results.push(await shot(page, name, url));
  }

  // Player dashboard with notification panel open
  await page.goto(BASE + '/player-dashboard', { waitUntil: 'domcontentloaded' });
  try {
    await page.waitForLoadState('networkidle', { timeout: 15000 });
  } catch {
    await page.waitForTimeout(800);
  }
  await preparePage(page);

  const bell = await page.$('#playerNotifyBtn');
  if (bell) {
    await bell.click();
    await page.waitForTimeout(400);
    const file = path.join(SHOTS, '06b-player-dashboard-notifications.png');
    await page.screenshot({ path: file, fullPage: true, animations: 'disabled' });
    results.push({
      name: '06b-player-dashboard-notifications',
      url: '/player-dashboard (notifications open)',
      file: 'screenshots/06b-player-dashboard-notifications.png',
    });
    console.log('Capturing 06b-player-dashboard-notifications');
  }

  await logout(page);
  await login(page, 'coach@coachnow.test', 'password');
  for (const [name, url] of coachScreens) {
    results.push(await shot(page, name, url));
  }

  await logout(page);
  await login(page, 'admin@coachnow.test', 'password');
  for (const [name, url] of adminScreens) {
    results.push(await shot(page, name, url));
  }

  await browser.close();

  const index = `# CoachNow UI Baseline

Frozen visual reference of all screens **before functional requirements development**.

- **Captured:** ${new Date().toISOString().slice(0, 10)}
- **Git tag:** \`ui-screens\`
- **Viewport:** 1440×900 (full page)
- **Note:** Capture script waits for / force-hides the site preloader so screenshots are fully loaded.

Use these screenshots to compare later UI changes. If something drifts, open the matching image and restore the layout to match.

## How to re-capture

1. \`php artisan serve\`
2. \`php artisan db:seed\` (demo logins)
3. \`npx playwright install chromium\` (once)
4. \`node scripts/capture-ui-baseline.js\`

## Demo logins

| Role | Email | Password |
|------|-------|----------|
| Player | player@coachnow.test | password |
| Coach | coach@coachnow.test | password |
| Admin | admin@coachnow.test | password |

## Screens

| # | Screen | URL | File |
|---|--------|-----|------|
${results.map((r, i) => `| ${i + 1} | ${r.name} | \`${r.url}\` | [${r.name}.png](${r.file}) |`).join('\n')}
`;

  fs.writeFileSync(path.join(OUT, 'INDEX.md'), index);
  fs.writeFileSync(
    path.join(OUT, 'manifest.json'),
    JSON.stringify({ capturedAt: new Date().toISOString(), baseUrl: BASE, screens: results }, null, 2)
  );

  const gallery = `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CoachNow UI Baseline</title>
  <style>
    body { margin: 0; font-family: system-ui, sans-serif; background: #f4f4f5; color: #18181b; }
    header { padding: 24px 28px; background: #191615; color: #fff; }
    header h1 { margin: 0 0 6px; font-size: 22px; }
    header p { margin: 0; opacity: .7; font-size: 13px; }
    main { max-width: 1100px; margin: 0 auto; padding: 24px; display: grid; gap: 28px; }
    article { background: #fff; border: 1px solid #e4e4e7; border-radius: 14px; overflow: hidden; }
    .meta { padding: 14px 16px; border-bottom: 1px solid #f4f4f5; }
    .meta h2 { margin: 0; font-size: 15px; }
    .meta code { font-size: 12px; color: #71717a; }
    img { display: block; width: 100%; height: auto; background: #ddd; }
  </style>
</head>
<body>
  <header>
    <h1>CoachNow UI Baseline</h1>
    <p>Visual reference before functional development · ${new Date().toISOString().slice(0, 10)} · preloader cleared</p>
  </header>
  <main>
    ${results
      .map(
        (r) => `
      <article id="${r.name}">
        <div class="meta">
          <h2>${r.name}</h2>
          <code>${r.url}</code>
        </div>
        <img src="${r.file}?v=${Date.now()}" alt="${r.name}">
      </article>`
      )
      .join('')}
  </main>
</body>
</html>`;

  fs.writeFileSync(path.join(OUT, 'gallery.html'), gallery);
  console.log(`\nDone. ${results.length} screens saved to ui-baseline/`);
  console.log('Open ui-baseline/gallery.html to browse all screens.');
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
