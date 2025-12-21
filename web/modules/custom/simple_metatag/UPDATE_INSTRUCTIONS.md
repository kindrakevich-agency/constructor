# Module Update Instructions

Since you cannot uninstall the module, follow these steps to update it properly:

## Method 1: Using the Install Script (Recommended)

### Step 1: Install Composer Dependencies

First, ensure the OpenAI PHP client is installed on your server:

```bash
# SSH into your server
ssh root@167.235.58.223

# Navigate to Drupal root
cd /www/wwwroot/new.polissya.today/drupal

# Install composer dependencies
composer require openai-php/client:^0.17
```

### Step 2: Deploy the Module

Simply run the install script:

```bash
./install.sh
```

This will automatically:
1. Delete the old module
2. Upload the new code to the server
3. Run database updates (drush updb -y)
4. Clear the Drupal cache (drush cr)

**No additional steps needed!** The script now handles everything.

## Method 2: Manual Update

If you prefer to update manually:

### 1. Install Composer Dependencies

```bash
ssh root@167.235.58.223
cd /www/wwwroot/new.polissya.today/drupal
composer require openai-php/client:^0.17
```

### 2. Upload the New Code

Use SFTP/rsync to upload to:
```
/www/wwwroot/new.polissya.today/drupal/web/modules/custom/simple_metatag
```

### 3. Run Database Updates

```bash
PATH="/www/server/php/84/bin:$PATH" /www/wwwroot/new.polissya.today/drupal/vendor/bin/drush updb -y
```

This will run the update hook `simple_metatag_update_9001()` which initializes the OpenAI settings.

### 4. Clear Cache

```bash
PATH="/www/server/php/84/bin:$PATH" /www/wwwroot/new.polissya.today/drupal/vendor/bin/drush cr
```

### 5. Verify the Update

```bash
# Check module version
PATH="/www/server/php/84/bin:$PATH" /www/wwwroot/new.polissya.today/drupal/vendor/bin/drush pml | grep simple_metatag

# Check if config was created
PATH="/www/server/php/84/bin:$PATH" /www/wwwroot/new.polissya.today/drupal/vendor/bin/drush config:get simple_metatag.openai_settings
```

## What's New in This Update

### New Features:
- **OpenAI SEO Generator** - New tab at `/admin/config/search/simple-metatag/openai`
- Automatic SEO text generation for taxonomy terms using OpenAI
- Batch processing for multiple terms
- Single term processing option
- Configurable OpenAI model selection

### New Files Added:
- `composer.json` - Declares dependency on openai-php/client
- `src/Form/OpenAISettingsForm.php` - OpenAI settings and generation form
- `simple_metatag.links.task.yml` - Tab navigation

### Updated Files:
- `simple_metatag.routing.yml` - Added OpenAI settings route
- `simple_metatag.install` - Added update hook 9001
- `config/schema/simple_metatag.schema.yml` - Added OpenAI settings schema
- `install.sh` - Now runs database updates before cache clear

### Dependencies Required:
- `openai-php/client:^0.17` - Official OpenAI PHP client library

## Accessing the New Feature

After updating, visit:
```
/admin/config/search/simple-metatag/openai
```

Or navigate to:
**Configuration > Search and metadata > Simple Metatag > OpenAI SEO Generator**

## Troubleshooting

### Cache Issues
If you don't see the new tab after updating:
```bash
PATH="/www/server/php/84/bin:$PATH" /www/wwwroot/new.polissya.today/drupal/vendor/bin/drush cr
PATH="/www/server/php/84/bin:$PATH" /www/wwwroot/new.polissya.today/drupal/vendor/bin/drush router:rebuild
```

### Config Not Found
If you get config errors:
```bash
PATH="/www/server/php/84/bin:$PATH" /www/wwwroot/new.polissya.today/drupal/vendor/bin/drush updb -y
```

### Permission Issues
Make sure the web server has proper permissions:
```bash
chown -R www:www /www/wwwroot/new.polissya.today/drupal/web/modules/custom/simple_metatag
```

## Quick Reference: Drush Commands

All commands should be prefixed with the PHP path:
```bash
PATH="/www/server/php/84/bin:$PATH"
```

Common commands:
- Clear cache: `drush cr`
- Database updates: `drush updb -y`
- Rebuild router: `drush router:rebuild`
- Check config: `drush config:get simple_metatag.openai_settings`
- Export config: `drush config:export`
