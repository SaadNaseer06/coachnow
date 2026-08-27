# CoachNow (Laravel)

Approved marketing UI converted into a Laravel 12 app so we can implement functional requirements next.

## Stack

- Laravel 12 / PHP 8.2+
- Blade views (converted from approved HTML)
- SQLite by default (ready for MySQL)
- Static assets in `public/assets`

## Run locally

```bash
composer install
copy .env.example .env   # if needed
php artisan key:generate
php artisan migrate
php artisan serve
```

Then open:

- Website: http://127.0.0.1:8000
- Admin: http://127.0.0.1:8000/admin
- Login: http://127.0.0.1:8000/login

## Project layout

| Path | Purpose |
|------|---------|
| `resources/views/pages/` | Public website blades |
| `resources/views/admin/` | Admin dashboard blades |
| `public/assets/` | CSS, JS, images |
| `legacy-static/` | Original static HTML backup |
| `app/Http/Controllers/` | Page + Admin controllers |
| `routes/web.php` | All current routes |
| `deploy/` | cPanel deploy script + docs (`DEPLOY.md`) |
| `database/migrations/*coachnow*` | Core tables: locations, coaches, bookings, user roles |

## Routes

**Public:** `/`, `/find-a-coach`, `/become-a-coach`, `/player-dashboard`, `/about`, `/faq`, `/contact`, `/login`, `/coach-profile`

**Admin:** `/admin`, `/admin/schedule`, `/admin/coaches`, `/admin/bookings`, `/admin/locations`, `/admin/athletes`

## Next functional work

1. Auth (admin/coach/athlete roles)
2. Eloquent models + seeders for parks & coaches
3. Admin CRUD for locations/coaches/bookings
4. Homepage location → coaches driven by DB
5. Booking flow from coach profile
