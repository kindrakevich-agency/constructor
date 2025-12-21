<?php

/**
 * @file
 * Local development settings.
 *
 * Copy this file to settings.local.php and uncomment the sections you need.
 */

// Database settings for Lando.
$databases['default']['default'] = [
  'database' => 'drupal',
  'username' => 'drupal',
  'password' => 'drupal',
  'host' => 'database',
  'port' => '3306',
  'driver' => 'mysql',
  'prefix' => '',
  'collation' => 'utf8mb4_general_ci',
];

// Trusted host patterns for Lando.
$settings['trusted_host_patterns'] = [
  '^constructor\.lndo\.site$',
  '^localhost$',
  '^127\.0\.0\.1$',
];

// Hash salt - generate a unique one for production.
$settings['hash_salt'] = 'constructor-development-hash-salt-change-in-production';

// Config sync directory.
$settings['config_sync_directory'] = '../config/sync';

// File paths.
$settings['file_public_path'] = 'sites/default/files';
$settings['file_private_path'] = 'sites/default/files/private';
$settings['file_temp_path'] = '/tmp';

// Development settings.
$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/development.services.yml';
$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;
$settings['cache']['bins']['render'] = 'cache.backend.null';
$settings['cache']['bins']['page'] = 'cache.backend.null';
$settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';

// Error reporting.
$config['system.logging']['error_level'] = 'verbose';

// Skip permissions hardening for development.
$settings['skip_permissions_hardening'] = TRUE;
