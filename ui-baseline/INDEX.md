# CoachNow UI Baseline

Frozen visual reference of all screens **before functional requirements development**.

- **Captured:** 2026-09-08
- **Git tag:** `ui-prototype`
- **Viewport:** 1440×900 (full page)

Use these screenshots to compare later UI changes. If something drifts, open the matching image and restore the layout to match.

## How to re-capture

1. `php artisan serve`
2. `php artisan db:seed` (demo logins)
3. `npx playwright install chromium` (once)
4. `node scripts/capture-ui-baseline.js`

## Demo logins

| Role | Email | Password |
|------|-------|----------|
| Player | player@coachnow.test | password |
| Coach | coach@coachnow.test | password |
| Admin | admin@coachnow.test | password |

## Screens

| # | Screen | URL | File |
|---|--------|-----|------|
| 1 | 01-home | `/` | [01-home.png](screenshots/01-home.png) |
| 2 | 02-find-a-coach | `/find-a-coach` | [02-find-a-coach.png](screenshots/02-find-a-coach.png) |
| 3 | 03-request-session | `/request-session` | [03-request-session.png](screenshots/03-request-session.png) |
| 4 | 04-become-a-coach | `/become-a-coach` | [04-become-a-coach.png](screenshots/04-become-a-coach.png) |
| 5 | 05-coach-profile | `/coach-profile` | [05-coach-profile.png](screenshots/05-coach-profile.png) |
| 6 | 06-player-dashboard | `/player-dashboard` | [06-player-dashboard.png](screenshots/06-player-dashboard.png) |
| 7 | 07-about | `/about` | [07-about.png](screenshots/07-about.png) |
| 8 | 08-faq | `/faq` | [08-faq.png](screenshots/08-faq.png) |
| 9 | 09-contact | `/contact` | [09-contact.png](screenshots/09-contact.png) |
| 10 | 10-login | `/login` | [10-login.png](screenshots/10-login.png) |
| 11 | 06b-player-dashboard-notifications | `/player-dashboard (notifications open)` | [06b-player-dashboard-notifications.png](screenshots/06b-player-dashboard-notifications.png) |
| 12 | 11-coach-dashboard | `/coach/dashboard` | [11-coach-dashboard.png](screenshots/11-coach-dashboard.png) |
| 13 | 12-coach-schedule | `/coach/schedule` | [12-coach-schedule.png](screenshots/12-coach-schedule.png) |
| 14 | 13-coach-player-overview | `/coach/player-overview` | [13-coach-player-overview.png](screenshots/13-coach-player-overview.png) |
| 15 | 14-coach-player-detail | `/coach/players/jamie-smith` | [14-coach-player-detail.png](screenshots/14-coach-player-detail.png) |
| 16 | 15-coach-add-report | `/coach/add-report` | [15-coach-add-report.png](screenshots/15-coach-add-report.png) |
| 17 | 16-admin-dashboard | `/admin` | [16-admin-dashboard.png](screenshots/16-admin-dashboard.png) |
| 18 | 17-admin-schedule | `/admin/schedule` | [17-admin-schedule.png](screenshots/17-admin-schedule.png) |
| 19 | 18-admin-coaches | `/admin/coaches` | [18-admin-coaches.png](screenshots/18-admin-coaches.png) |
| 20 | 19-admin-bookings | `/admin/bookings` | [19-admin-bookings.png](screenshots/19-admin-bookings.png) |
| 21 | 20-admin-locations | `/admin/locations` | [20-admin-locations.png](screenshots/20-admin-locations.png) |
| 22 | 21-admin-athletes | `/admin/athletes` | [21-admin-athletes.png](screenshots/21-admin-athletes.png) |
