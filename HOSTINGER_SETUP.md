# Hostinger Server Configuration Guide

## Session & Token Issues Fix

After deploying to Hostinger, if you're experiencing session/token issues where you get logged out after closing the browser, follow these steps:

### 1. Run the Sessions Migration

The application uses database sessions, so you need to create the sessions table:

```bash
php artisan migrate
```

### 2. Update Your .env File

Add or update these settings in your `.env` file on Hostinger:

```env
# Application URL (use your actual domain)
APP_URL=https://yourdomain.com

# Session Configuration
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
SESSION_DOMAIN=null

# Make sure these are set correctly
APP_ENV=production
APP_DEBUG=false
```

### 3. Set Proper File Permissions

Make sure these directories are writable:

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### 4. Clear Configuration Cache

After updating .env, clear the config cache:

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 5. Verify Sessions Table Exists

Check that the sessions table was created:

```bash
php artisan migrate:status
```

### Common Issues & Solutions

#### Issue: Sessions not persisting
- **Solution**: Make sure `SESSION_DRIVER=database` and the sessions table exists
- **Solution**: Verify `SESSION_SECURE_COOKIE=true` if using HTTPS
- **Solution**: Check that `APP_URL` matches your actual domain

#### Issue: CSRF token mismatch (or being asked to delete cookies)
- **Solution**: Ensure `APP_URL` in .env matches **exactly** the URL you use in the browser (e.g. `https://yourdomain.com`). If you use `http://localhost`, open `http://localhost`, not `http://127.0.0.1`.
- **Solution**: On production over HTTPS, set `SESSION_SECURE_COOKIE=true` in .env.
- **Solution**: Clear all caches after deployment: `php artisan config:clear && php artisan cache:clear`
- **Solution**: The app now auto-reloads on 419 (CSRF mismatch) to get a fresh token—you should no longer need to delete cookies manually. If it keeps happening, fix `APP_URL` and session settings above.

#### Issue: Getting logged out immediately
- **Solution**: Increase `SESSION_LIFETIME` (in minutes, default is 120)
- **Solution**: Set `SESSION_EXPIRE_ON_CLOSE=false` in .env
- **Solution**: Verify session table has proper permissions

### Additional Hostinger-Specific Settings

If you're using a subdomain or specific path, you may need:

```env
SESSION_DOMAIN=.yourdomain.com  # Note the leading dot for subdomains
SESSION_PATH=/  # Or your specific path
```

### Testing

After making changes:
1. Clear all caches
2. Log in to your application
3. Close the browser completely
4. Reopen and navigate to your site
5. You should remain logged in (if within SESSION_LIFETIME)
