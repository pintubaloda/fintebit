# Fintebit

PHP + MySQL learning platform, prepared for GitHub and Render deployment.

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

## 2) Push to GitHub

Run from project folder:

```bash
git init
git add .
git commit -m "chore: prepare fintebit for github and render"
git branch -M main
git remote add origin <YOUR_GITHUB_REPO_URL>
git push -u origin main
```

## 3) Deploy on Render

This repo includes:
- `Dockerfile`
- `render.yaml`

### Steps

1. Create a new **Web Service** on Render from your GitHub repo.
2. Render auto-detects `render.yaml` and Docker build.
3. Add env vars in Render dashboard (or keep from `render.yaml`):
   - `DB_HOST`
   - `DB_PORT` (default `3306`)
   - `DB_USER`
   - `DB_PASS`
   - `DB_NAME` (default `fintebit`)
   - `SITE_NAME` (default `Fintebit`)
   - `SITE_URL` (your Render service URL)
4. Deploy.
5. After first deploy, open `https://<your-service>/setup.php` once to create tables and seed data.

## Notes

- App config now uses environment variables with safe local defaults.
- `setup.php` seeding bind types were corrected for clean initialization.
- `.gitignore` and `.env.example` are included for GitHub-safe commits.
