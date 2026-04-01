# Production .env – Issues & Fixes

## Critical (security & behavior)

| Variable | Current (wrong) | Use in production |
|----------|------------------|-------------------|
| **APP_ENV** | `local` | `production` |
| **APP_DEBUG** | `true` | `false` |
| **LOG_LEVEL** | `debug` | `warning` or `error` |

**Why it matters:**
- `APP_ENV=local` → `URL::forceScheme('https')` in `AppServiceProvider` never runs, so generated URLs may be `http` instead of `https`.
- `APP_DEBUG=true` → Full error pages with stack traces and env values are shown to users (security risk).
- `LOG_LEVEL=debug` → Logs get very large and can expose internal details.

---

## Recommended (stability / 419 fixes)

| Variable | Current | Recommended |
|----------|---------|-------------|
| **SESSION_DOMAIN** | (not set) | `.technest-agency.com` |

For `https://alarabiya.technest-agency.com`, setting `SESSION_DOMAIN=.technest-agency.com` helps session cookies work correctly and can reduce “Page Expired” (419) issues.

---

## Optional (branding / mail)

| Variable | Suggestion |
|----------|------------|
| **APP_NAME** | `Alarabiya Academy` (or your app name) |
| **MAIL_*** ** | Replace mailhog with real SMTP when you need to send email. |

---

## DB password with special characters

If `DB_PASSWORD` contains `#`, `$`, spaces, or other special characters, keep it in **double quotes** so the value is read correctly:

```env
DB_PASSWORD="7#4S]Om&"
```

---

## Minimal production snippet (copy into .env)

```env
APP_NAME="Alarabiya Academy"
APP_ENV=production
APP_KEY=base64:QYYpcg6R4fo1g1aYC9ginkJOwdRfYHn+8NQYMyL+V44=
APP_DEBUG=false
APP_URL=https://alarabiya.technest-agency.com

LOG_CHANNEL=stack
LOG_LEVEL=warning

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
SESSION_DOMAIN=.technest-agency.com
```

After changing `.env`, run on the server:

```bash
php artisan config:clear
php artisan cache:clear
```
