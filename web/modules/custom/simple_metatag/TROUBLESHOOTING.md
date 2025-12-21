# Troubleshooting Guide

## Quick Fix for Current Issues

If you're seeing "Class does not exist" errors or missing tabs, run these commands on your server:

```bash
# SSH into your server
ssh root@167.235.58.223

# Navigate to Drupal root
cd /www/wwwroot/new.polissya.today/drupal

# 1. Rebuild Composer autoloader
composer dump-autoload

# 2. Clear all Drupal caches
PATH="/www/server/php/84/bin:$PATH" vendor/bin/drush cr

# 3. Rebuild router
PATH="/www/server/php/84/bin:$PATH" vendor/bin/drush router:rebuild

# 4. Run database updates
PATH="/www/server/php/84/bin:$PATH" vendor/bin/drush updb -y

# 5. Clear cache again
PATH="/www/server/php/84/bin:$PATH" vendor/bin/drush cr
```

## Verify Files Were Uploaded

Check if the new files exist on the server:

```bash
ssh root@167.235.58.223

# Check if OpenAISettingsForm.php exists
ls -la /www/wwwroot/new.polissya.today/drupal/web/modules/custom/simple_metatag/src/Form/OpenAISettingsForm.php

# Check if links.task.yml exists
ls -la /www/wwwroot/new.polissya.today/drupal/web/modules/custom/simple_metatag/simple_metatag.links.task.yml

# Check if composer.json exists
ls -la /www/wwwroot/new.polissya.today/drupal/web/modules/custom/simple_metatag/composer.json

# Check routing.yml
grep -A 5 "openai_settings" /www/wwwroot/new.polissya.today/drupal/web/modules/custom/simple_metatag/simple_metatag.routing.yml
```

If any files are missing, run `./install.sh` again.

## Common Issues

### Issue 1: "Class does not exist" Error

**Cause**: Drupal's autoloader doesn't know about the new class.

**Fix**:
```bash
cd /www/wwwroot/new.polissya.today/drupal
composer dump-autoload
PATH="/www/server/php/84/bin:$PATH" vendor/bin/drush cr
```

### Issue 2: Tabs Not Showing

**Cause**: Router cache not rebuilt after adding new routes.

**Fix**:
```bash
PATH="/www/server/php/84/bin:$PATH" /www/wwwroot/new.polissya.today/drupal/vendor/bin/drush router:rebuild
PATH="/www/server/php/84/bin:$PATH" /www/wwwroot/new.polissya.today/drupal/vendor/bin/drush cr
```

### Issue 3: OpenAI Client Not Found

**Cause**: Composer dependency not installed.

**Fix**:
```bash
cd /www/wwwroot/new.polissya.today/drupal
composer require openai-php/client:^0.17
PATH="/www/server/php/84/bin:$PATH" vendor/bin/drush cr
```

### Issue 4: Permission Denied Errors

**Cause**: Wrong file permissions.

**Fix**:
```bash
chown -R www:www /www/wwwroot/new.polissya.today/drupal/web/modules/custom/simple_metatag
chmod -R 755 /www/wwwroot/new.polissya.today/drupal/web/modules/custom/simple_metatag
```

## Verify Everything Works

Run these commands to verify the module is working:

```bash
# 1. Check module is enabled
PATH="/www/server/php/84/bin:$PATH" /www/wwwroot/new.polissya.today/drupal/vendor/bin/drush pml | grep simple_metatag

# Expected output: simple_metatag (Simple Metatag) Enabled

# 2. Check routes exist
PATH="/www/server/php/84/bin:$PATH" /www/wwwroot/new.polissya.today/drupal/vendor/bin/drush route:debug | grep simple_metatag

# Expected output should include:
# simple_metatag.path_metatags_list
# simple_metatag.openai_settings

# 3. Check config exists
PATH="/www/server/php/84/bin:$PATH" /www/wwwroot/new.polissya.today/drupal/vendor/bin/drush config:get simple_metatag.openai_settings

# Expected: Configuration object exists (even if empty)

# 4. Test the OpenAI settings page directly
# Visit: /admin/config/search/simple-metatag/openai
```

## Manual Cache Clear (Nuclear Option)

If normal cache clear doesn't work, try this:

```bash
ssh root@167.235.58.223
cd /www/wwwroot/new.polissya.today/drupal

# Clear all cache tables directly
PATH="/www/server/php/84/bin:$PATH" vendor/bin/drush sqlq "TRUNCATE cache_bootstrap"
PATH="/www/server/php/84/bin:$PATH" vendor/bin/drush sqlq "TRUNCATE cache_config"
PATH="/www/server/php/84/bin:$PATH" vendor/bin/drush sqlq "TRUNCATE cache_container"
PATH="/www/server/php/84/bin:$PATH" vendor/bin/drush sqlq "TRUNCATE cache_data"
PATH="/www/server/php/84/bin:$PATH" vendor/bin/drush sqlq "TRUNCATE cache_default"
PATH="/www/server/php/84/bin:$PATH" vendor/bin/drush sqlq "TRUNCATE cache_discovery"
PATH="/www/server/php/84/bin:$PATH" vendor/bin/drush sqlq "TRUNCATE cache_entity"
PATH="/www/server/php/84/bin:$PATH" vendor/bin/drush sqlq "TRUNCATE cache_menu"
PATH="/www/server/php/84/bin:$PATH" vendor/bin/drush sqlq "TRUNCATE cache_render"
PATH="/www/server/php/84/bin:$PATH" vendor/bin/drush sqlq "TRUNCATE router"

# Rebuild everything
composer dump-autoload
PATH="/www/server/php/84/bin:$PATH" vendor/bin/drush cr
PATH="/www/server/php/84/bin:$PATH" vendor/bin/drush router:rebuild
```

## Check Drupal Logs

If you're still having issues, check the logs:

```bash
# View recent logs
PATH="/www/server/php/84/bin:$PATH" /www/wwwroot/new.polissya.today/drupal/vendor/bin/drush watchdog:show --count=20

# Or visit in browser:
# /admin/reports/dblog
```

## Re-deploy from Scratch

If nothing else works, re-run the install script:

```bash
# From your local machine
./install.sh
```

The updated script now:
1. Deletes old module
2. Copies new module
3. Rebuilds autoloader
4. Runs database updates
5. Clears cache and rebuilds router

## Still Not Working?

1. Check PHP error logs: `/www/wwwlogs/new.polissya.today.error.log`
2. Check web server is running: `systemctl status nginx`
3. Check PHP-FPM is running: `systemctl status php-fpm`
4. Verify file ownership: `ls -la /www/wwwroot/new.polissya.today/drupal/web/modules/custom/simple_metatag`

## Contact Support

If you're still stuck, provide:
- Output of: `drush pml | grep simple_metatag`
- Output of: `drush route:debug | grep simple_metatag`
- Recent watchdog logs: `drush watchdog:show --count=20`
- File list: `ls -laR /www/wwwroot/new.polissya.today/drupal/web/modules/custom/simple_metatag`
