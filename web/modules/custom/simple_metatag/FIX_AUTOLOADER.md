# Fix Broken Drupal Autoloader

The error "Class Drupal not found" means Composer's autoloader is broken. Follow these steps carefully:

## Step 1: Verify Directory Structure

```bash
ssh root@167.235.58.223

# Check if vendor directory exists
ls -la /www/wwwroot/new.polissya.today/drupal/vendor

# Check if autoload.php exists
ls -la /www/wwwroot/new.polissya.today/drupal/vendor/autoload.php

# Check Drupal core
ls -la /www/wwwroot/new.polissya.today/drupal/web/core
```

## Step 2: Rebuild Composer Autoloader

```bash
cd /www/wwwroot/new.polissya.today/drupal

# Option A: Quick rebuild
composer dump-autoload --optimize

# Option B: If that fails, full reinstall
composer install --no-interaction --prefer-dist --optimize-autoloader
```

## Step 3: Verify Drupal is Working

```bash
# Test if PHP can find Drupal
php -r "require_once '/www/wwwroot/new.polissya.today/drupal/vendor/autoload.php'; echo class_exists('Drupal') ? 'OK' : 'FAIL';"

# Expected output: OK
```

## Step 4: Test Drush

```bash
cd /www/wwwroot/new.polissya.today/drupal
PATH="/www/server/php/84/bin:$PATH" vendor/bin/drush status
```

## If composer dump-autoload fails:

Try this more aggressive approach:

```bash
cd /www/wwwroot/new.polissya.today/drupal

# Remove autoloader files
rm -rf vendor/composer
rm vendor/autoload.php

# Regenerate autoloader
composer dump-autoload --optimize

# If still fails, reinstall all packages
composer install --no-dev --optimize-autoloader
```

## Alternative: Use Drush Launcher

If drush keeps failing, try using absolute paths:

```bash
# Instead of: vendor/bin/drush cr
# Use the full PHP command:
/www/server/php/84/bin/php /www/wwwroot/new.polissya.today/drupal/vendor/bin/drush cr
```

## Check PHP Configuration

Make sure PHP can find required extensions:

```bash
/www/server/php/84/bin/php -m | grep -E "pdo|json|xml|mbstring"
```

All these should show up. If not, install missing extensions.

## Nuclear Option: Rebuild Vendor Directory

**⚠️ WARNING: This will take time and bandwidth**

```bash
cd /www/wwwroot/new.polissya.today/drupal

# Backup composer files
cp composer.json composer.json.backup
cp composer.lock composer.lock.backup

# Remove vendor directory
rm -rf vendor

# Reinstall everything
composer install --no-interaction --optimize-autoloader

# Test
PATH="/www/server/php/84/bin:$PATH" vendor/bin/drush status
```

## Temporary Workaround: Manual Cache Clear

If drush doesn't work, clear cache manually through the UI:

1. Visit: `/admin/config/development/performance`
2. Click "Clear all caches"

Or delete cache files directly:

```bash
# Clear cache tables via MySQL
mysql -u root -p new_polissya_today_drupal << EOF
TRUNCATE cache_bootstrap;
TRUNCATE cache_config;
TRUNCATE cache_container;
TRUNCATE cache_data;
TRUNCATE cache_default;
TRUNCATE cache_discovery;
TRUNCATE cache_entity;
TRUNCATE cache_menu;
TRUNCATE cache_render;
TRUNCATE cache_page;
TRUNCATE router;
EOF
```

## Check for Disk Space

Sometimes this error happens due to lack of disk space:

```bash
df -h /www/wwwroot/new.polissya.today
```

Make sure you have at least 500MB free.

## Verify File Permissions

```bash
cd /www/wwwroot/new.polissya.today/drupal

# Check ownership
ls -la vendor/autoload.php

# Should be owned by www:www or similar web user
# If not, fix it:
chown -R www:www vendor/
```
