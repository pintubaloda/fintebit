# Fintebit

PHP + MySQL learning platform, prepared for GitHub and Railway deployment.

## 1) Run locally

1. Copy env template:
   ```bash
   cp .env.example .env
   ```
2. Set DB values in your shell or hosting env (this app reads OS env vars).
3. Start with any PHP+Apache stack and open:
   - `http://localhost:8080/setup.php` (first run only)
   - `http://localhost:8080/`

Default demo logins after setup:
- Admin: `admin@fintebit.com` / `admin123`
- User: `user@fintebit.com` / `user123`

## 2) Deploy on Railway

This repo includes:
- `Dockerfile`
- `railway.json`
- `docker-entrypoint.sh` (binds Apache to Railway `PORT`)

### Steps

1. In Railway, create a new project from your GitHub repo.
2. Add a **MySQL** service/plugin inside the same Railway project.
3. In your app service, set these environment variables:
   - `DB_HOST` = MySQL host from Railway
   - `DB_PORT` = `3306`
   - `DB_USER` = MySQL user from Railway
   - `DB_PASS` = MySQL password from Railway
   - `DB_NAME` = `fintebit`
   - `SITE_NAME` = `Fintebit`
   - `SITE_URL` = your Railway public URL
4. Deploy the app service.
5. After first deploy, open `https://<your-railway-domain>/setup.php` once to create tables and seed data.

## Notes

- App config uses environment variables with local defaults.
- `setup.php` seeding bind types were corrected for clean initialization.
- `.gitignore` and `.env.example` are included for GitHub-safe commits.
