<?php

/**
 * @file
 * Enables modules and site configuration for the Constructor profile.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_theme().
 */
function constructor_theme($existing, $type, $theme, $path) {
  return [
    'constructor_example_page' => [
      'variables' => [],
      'template' => 'constructor-example-page',
      'path' => \Drupal::service('extension.list.theme')->getPath('constructor_theme') . '/templates/pages',
    ],
  ];
}

/**
 * Implements hook_preprocess_install_page().
 */
function constructor_preprocess_install_page(&$variables) {
  // Determine current step based on the active task.
  $install_state = $GLOBALS['install_state'] ?? [];
  $active_task = $install_state['active_task'] ?? '';

  // Map Drupal install tasks to our 7-step wizard.
  // Steps: 1-Language, 2-Database, 3-Site Basics, 4-Languages, 5-Content Types, 6-AI, 7-Finalize
  $step_mapping = [
    // Step 1: Language selection.
    'install_select_language' => 1,
    'install_select_profile' => 1,
    'install_load_profile' => 1,
    'install_verify_requirements' => 1,
    // Step 2: Database setup.
    'install_settings_form' => 2,
    'install_verify_database_ready' => 2,
    // Back to step 2 during installation process.
    'install_base_system' => 2,
    'install_bootstrap_full' => 2,
    'install_profile_modules' => 2,
    'install_profile_themes' => 2,
    'install_install_profile' => 2,
    'install_configure_form' => 2,
    // Constructor custom wizard steps (steps 3-7).
    'constructor_install_site_basics' => 3,
    'constructor_install_languages' => 4,
    'constructor_install_content_types' => 5,
    'constructor_install_ai_integration' => 6,
    'constructor_finalize_installation' => 7,
    'constructor_update_translations' => 7,
    'install_finished' => 7,
  ];

  $variables['current_step'] = $step_mapping[$active_task] ?? 1;
}

/**
 * Implements hook_install_tasks().
 */
function constructor_install_tasks(&$install_state) {
  $tasks = [];

  // Import profile translations early.
  $tasks['constructor_import_translations'] = [
    'display_name' => t('Importing translations'),
    'display' => FALSE,
    'type' => 'normal',
    'run' => INSTALL_TASK_RUN_IF_NOT_COMPLETED,
  ];

  // Detect if running in CLI mode (drush).
  $is_cli = PHP_SAPI === 'cli' || defined('STDIN');

  if (!$is_cli) {
    // Only show wizard forms for browser-based installation.

    // Step 5: Site Basics.
    $tasks['constructor_install_site_basics'] = [
      'display_name' => t('Site Basics'),
      'display' => TRUE,
      'type' => 'form',
      'function' => 'Drupal\constructor\Form\SiteBasicsForm',
    ];

    // Step 6: Languages.
    $tasks['constructor_install_languages'] = [
      'display_name' => t('Languages'),
      'display' => TRUE,
      'type' => 'form',
      'function' => 'Drupal\constructor\Form\LanguagesForm',
    ];

    // Step 7: Content Types.
    $tasks['constructor_install_content_types'] = [
      'display_name' => t('Content Types'),
      'display' => TRUE,
      'type' => 'form',
      'function' => 'Drupal\constructor\Form\ContentTypesForm',
    ];

    // Step 7: AI Integration.
    $tasks['constructor_install_ai_integration'] = [
      'display_name' => t('AI Integration'),
      'display' => TRUE,
      'type' => 'form',
      'function' => 'Drupal\constructor\Form\AIIntegrationForm',
    ];

    // Finalize: Apply all configurations.
    $tasks['constructor_finalize_installation'] = [
      'display_name' => t('Applying Configuration'),
      'display' => TRUE,
      'type' => 'batch',
      'function' => 'constructor_finalize_batch',
    ];

    // Install content_translation module.
    $tasks['constructor_install_translation_module'] = [
      'display_name' => t('Installing Translation Module'),
      'display' => TRUE,
      'type' => 'batch',
      'function' => 'constructor_install_translation_module_batch',
    ];

    // Translate content using AI.
    // This runs as a SEPARATE task (new HTTP request) after the module is installed.
    $tasks['constructor_translate_content'] = [
      'display_name' => t('Translating Content'),
      'display' => TRUE,
      'type' => 'batch',
      'function' => 'constructor_translate_content_batch',
    ];

    // Update interface translations step.
    $tasks['constructor_update_translations'] = [
      'display_name' => t('Updating Translations'),
      'display' => TRUE,
      'type' => 'batch',
      'function' => 'constructor_update_translations_batch',
    ];

    // Finalize installation.
    $tasks['constructor_setup_post_install'] = [
      'display_name' => t('Finalizing'),
      'display' => TRUE,
      'type' => 'normal',
      'function' => 'constructor_setup_post_install',
    ];
  }
  else {
    // In CLI mode, run batch operations with defaults.
    $tasks['constructor_cli_setup'] = [
      'display_name' => t('CLI Setup'),
      'display' => TRUE,
      'type' => 'batch',
    ];
  }

  return $tasks;
}

/**
 * Implements hook_install_tasks_alter().
 */
function constructor_install_tasks_alter(&$tasks, $install_state) {
  // Skip Drupal's default configure form - we handle this in SiteBasicsForm.
  $is_cli = PHP_SAPI === 'cli' || defined('STDIN');
  if (!$is_cli && isset($tasks['install_configure_form'])) {
    // Replace the core form with our own that just creates the account.
    $tasks['install_configure_form']['function'] = 'Drupal\constructor\Form\MinimalConfigureForm';
    $tasks['install_configure_form']['display'] = FALSE;
  }
}

/**
 * Implements hook_form_alter().
 */
function constructor_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
  // Set English as default language in installer.
  if ($form_id === 'install_select_language_form') {
    if (isset($form['langcode']['#default_value'])) {
      $form['langcode']['#default_value'] = 'en';
    }
  }
}

/**
 * Import profile translations.
 */
function constructor_import_translations() {
  // Get the current language.
  $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();

  // Only import for non-English languages.
  if ($langcode === 'en') {
    return;
  }

  // Check if locale module is available.
  if (!\Drupal::moduleHandler()->moduleExists('locale')) {
    return;
  }

  // Path to our translation file.
  $profile_path = \Drupal::service('extension.list.profile')->getPath('constructor');
  $translation_file = $profile_path . '/translations/constructor-1.0.0.' . $langcode . '.po';

  if (!file_exists($translation_file)) {
    return;
  }

  try {
    // Use the Gettext file loader to import translations.
    $reader = new \Drupal\Component\Gettext\PoStreamReader();
    $reader->setLangcode($langcode);
    $reader->setURI($translation_file);
    $reader->open();

    $header = $reader->getHeader();
    if (!$header) {
      $reader->close();
      return;
    }

    // Get the locale storage service.
    $locale_storage = \Drupal::service('locale.storage');

    // Read and import each translation.
    while ($item = $reader->readItem()) {
      if (empty($item->getSource()) || empty($item->getTranslation())) {
        continue;
      }

      // Find or create the source string.
      $source_string = $locale_storage->findString([
        'source' => $item->getSource(),
        'context' => $item->getContext(),
      ]);

      if (!$source_string) {
        // Create a new source string.
        $source_string = $locale_storage->createString([
          'source' => $item->getSource(),
          'context' => $item->getContext(),
        ]);
        $source_string->save();
      }

      // Save the translation.
      $locale_storage->createTranslation([
        'lid' => $source_string->lid,
        'language' => $langcode,
        'translation' => $item->getTranslation(),
      ])->save();
    }

    $reader->close();

    // Clear the locale cache.
    _locale_invalidate_js($langcode);
    \Drupal::cache()->delete('locale:' . $langcode);

  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->error('Failed to import translations: @message', ['@message' => $e->getMessage()]);
  }
}

/**
 * Batch callback: CLI setup with default configuration.
 */
function constructor_cli_setup(&$install_state) {
  $operations = [];

  // Apply default configurations for CLI installation.
  $operations[] = ['constructor_cli_apply_defaults', []];
  $operations[] = ['constructor_clear_caches', []];

  $batch = [
    'title' => t('Setting up Constructor'),
    'operations' => $operations,
    'finished' => 'constructor_finalize_finished',
  ];

  return $batch;
}

/**
 * Batch operation: Apply default configuration for CLI installation.
 */
function constructor_cli_apply_defaults(&$context) {
  $context['message'] = t('Applying default configuration...');

  // Set default site configuration.
  $config = \Drupal::configFactory()->getEditable('system.site');
  if (empty($config->get('name'))) {
    $config->set('name', 'Constructor Site');
  }
  $config->save();

  $context['results'][] = 'cli_defaults';
}

/**
 * Batch callback: Finalize installation with all configurations.
 */
function constructor_finalize_batch(&$install_state) {
  // Get constructor settings from key_value storage.
  $key_value = \Drupal::keyValue('constructor_install');
  $constructor_settings = [
    'site_basics' => $key_value->get('site_basics', []),
    'languages' => $key_value->get('languages', []),
    'content_types' => $key_value->get('content_types', []),
    'content_type_modules' => $key_value->get('content_type_modules', []),
    'modules' => $key_value->get('modules', []),
    'modules_to_enable' => $key_value->get('modules_to_enable', []),
    'layout' => $key_value->get('layout', []),
    'ai_settings' => $key_value->get('ai_settings', []),
  ];

  // Debug: Log all key_value data.
  \Drupal::logger('constructor')->notice('finalize_batch: languages from key_value = @data', [
    '@data' => print_r($constructor_settings['languages'], TRUE),
  ]);

  $operations = [];

  // ========== PHASE 0: Pre-requisites ==========
  // Create full_html text format BEFORE any modules are installed.
  // This ensures fields can reference the format during module installation.
  $operations[] = ['constructor_batch_create_full_html_format', []];

  // ========== PHASE 1: Module Installations ==========
  // Apply languages (installs language, content_translation, locale modules).
  $operations[] = ['constructor_batch_apply_languages', [$constructor_settings]];

  // Install language switcher module if multiple languages.
  $operations[] = ['constructor_batch_install_language_switcher', [$constructor_settings]];

  // Apply content type modules (like content_faq, content_team).
  $operations[] = ['constructor_batch_apply_content_type_modules', [$constructor_settings]];

  // Apply content types.
  $operations[] = ['constructor_batch_apply_content_types', [$constructor_settings]];

  // Apply modules.
  $operations[] = ['constructor_batch_apply_modules', [$constructor_settings]];

  // ========== PHASE 2: Container Rebuild ==========
  // CRITICAL: Rebuild container after all module installations.
  $operations[] = ['constructor_batch_rebuild_container', []];

  // ========== PHASE 3: Basic Configuration ==========
  // Apply layout.
  $operations[] = ['constructor_batch_apply_layout', [$constructor_settings]];

  // Apply AI settings.
  $operations[] = ['constructor_batch_apply_ai_settings', [$constructor_settings]];

  // Set default theme.
  $operations[] = ['constructor_batch_set_default_theme', []];

  // Configure theme settings (dark mode, color scheme).
  $operations[] = ['constructor_batch_configure_theme_settings', [$constructor_settings]];

  // Create main menu links (without block placement).
  $operations[] = ['constructor_batch_create_main_menu', [$constructor_settings]];

  // Configure frontpage.
  $operations[] = ['constructor_batch_configure_frontpage', [$constructor_settings]];

  // Configure development settings.
  $operations[] = ['constructor_batch_configure_development_settings', []];

  // ========== PHASE 4: Final Cache Clear ==========
  // Clear all caches to ensure entity types are fully registered.
  $operations[] = ['constructor_batch_final_cache_clear', []];

  // ========== PHASE 5: AI Content (WITHOUT translations) ==========
  // Generate AI content - each content type as a separate batch operation.
  $content_type_modules = $constructor_settings['content_type_modules'] ?? [];

  if (in_array('content_faq', $content_type_modules)) {
    $operations[] = ['constructor_batch_generate_faq', [$constructor_settings]];
  }
  if (in_array('content_team', $content_type_modules)) {
    $operations[] = ['constructor_batch_generate_team', [$constructor_settings]];
  }
  if (in_array('content_services', $content_type_modules)) {
    $operations[] = ['constructor_batch_generate_services', [$constructor_settings]];
  }
  if (in_array('content_article', $content_type_modules)) {
    $operations[] = ['constructor_batch_generate_articles', [$constructor_settings]];
  }
  if (in_array('content_commerce', $content_type_modules)) {
    $operations[] = ['constructor_batch_generate_products', [$constructor_settings]];
  }
  if (in_array('gallery', $content_type_modules)) {
    $operations[] = ['constructor_batch_generate_gallery', [$constructor_settings]];
  }

  // Place all blocks (FAQ, Team, Language Switcher, Main Menu, Branding).
  $operations[] = ['constructor_batch_place_all_blocks', [$constructor_settings]];

  // NOTE: Content translation is handled in a SEPARATE install task
  // (constructor_content_translation_batch) that runs as a new HTTP request.
  // This ensures entity types are fully registered before any translation ops.

  return [
    'title' => t('Applying Configuration'),
    'operations' => $operations,
    'finished' => 'constructor_finalize_batch_finished',
  ];
}

/**
 * Setup post-install redirect for content translation.
 *
 * This sets state variables that the CompleteSetupController will use
 * to run content translations in a fresh HTTP request.
 */
function constructor_setup_post_install(&$install_state) {
  // Get constructor settings from key_value storage.
  $key_value = \Drupal::keyValue('constructor_install');
  $constructor_settings = [
    'site_basics' => $key_value->get('site_basics', []),
    'languages' => $key_value->get('languages', []),
    'content_type_modules' => $key_value->get('content_type_modules', []),
    'ai_settings' => $key_value->get('ai_settings', []),
  ];

  $languages = $constructor_settings['languages'] ?? [];
  $additional_languages = $languages['additional_languages'] ?? [];

  // Filter additional_languages.
  $default_language = $languages['default_language'] ?? 'en';
  $additional_languages = array_filter($additional_languages, function ($langcode) use ($default_language) {
    return !empty($langcode) && $langcode !== $default_language;
  });

  // Translation is now handled in the batch process (constructor_translate_content_batch).
  // No post-install redirect needed.
  $ai_settings = $constructor_settings['ai_settings'] ?? [];
  if (!empty($additional_languages) && !empty($ai_settings['api_key'])) {
    \Drupal::logger('constructor')->notice('Content translation will be handled in batch process.');
  }
  else {
    \Drupal::logger('constructor')->notice('Content translation not needed (no additional languages or no API key).');
  }
}

/**
 * Batch callback: Install translation module.
 *
 * This runs as a SEPARATE install task - installs ONLY the module.
 * The actual translation happens in the NEXT task (new HTTP request).
 */
function constructor_install_translation_module_batch(&$install_state) {
  // Get constructor settings from key_value storage.
  $key_value = \Drupal::keyValue('constructor_install');
  $constructor_settings = [
    'languages' => $key_value->get('languages', []),
  ];

  $operations = [];

  // Install content_translation module ONLY.
  $operations[] = ['constructor_batch_install_content_translation', [$constructor_settings]];

  return [
    'title' => t('Installing Translation Module'),
    'operations' => $operations,
    'finished' => 'constructor_install_translation_finished',
  ];
}

/**
 * Batch finished callback for translation module installation.
 */
function constructor_install_translation_finished($success, $results, $operations) {
  if ($success) {
    \Drupal::messenger()->addStatus(t('Translation module installed.'));
  }
}

/**
 * Batch callback: Translate content.
 *
 * This runs as a SEPARATE install task AFTER the module is installed.
 * Running as a new HTTP request ensures entity types are fully registered.
 */
function constructor_translate_content_batch(&$install_state) {
  // Get constructor settings from key_value storage.
  $key_value = \Drupal::keyValue('constructor_install');
  $constructor_settings = [
    'site_basics' => $key_value->get('site_basics', []),
    'languages' => $key_value->get('languages', []),
    'content_type_modules' => $key_value->get('content_type_modules', []),
    'ai_settings' => $key_value->get('ai_settings', []),
  ];

  $operations = [];

  // Check if we have translations to do.
  $ai_settings = $constructor_settings['ai_settings'] ?? [];
  $languages = $constructor_settings['languages'] ?? [];
  $content_type_modules = $constructor_settings['content_type_modules'] ?? [];

  $default_language = $languages['default_language'] ?? 'en';
  $additional_languages = $languages['additional_languages'] ?? [];
  $additional_languages = array_filter($additional_languages, function ($langcode) use ($default_language) {
    return !empty($langcode) && $langcode !== $default_language;
  });

  // Translate menu items (doesn't require AI, just predefined translations).
  if (!empty($additional_languages)) {
    $operations[] = ['constructor_batch_translate_menus', [$constructor_settings]];
  }

  // Only add translation operations if we have API key and additional languages.
  if (!empty($ai_settings['api_key']) && !empty($additional_languages)) {
    // Each content type gets its own batch operation.
    if (in_array('content_faq', $content_type_modules)) {
      $operations[] = ['constructor_batch_translate_faq', [$constructor_settings]];
    }
    if (in_array('content_team', $content_type_modules)) {
      $operations[] = ['constructor_batch_translate_team', [$constructor_settings]];
    }
    if (in_array('content_services', $content_type_modules)) {
      $operations[] = ['constructor_batch_translate_services', [$constructor_settings]];
    }
    if (in_array('content_article', $content_type_modules)) {
      $operations[] = ['constructor_batch_translate_articles', [$constructor_settings]];
    }
    if (in_array('content_commerce', $content_type_modules)) {
      $operations[] = ['constructor_batch_translate_products', [$constructor_settings]];
      // Also translate product_category taxonomy terms.
      $operations[] = ['constructor_batch_translate_taxonomy', [$constructor_settings, 'product_category']];
    }
    if (in_array('pricing_plans', $content_type_modules)) {
      $operations[] = ['constructor_batch_translate_pricing_plans', [$constructor_settings]];
    }
  }

  // Add a placeholder if no operations (batch needs at least one).
  if (empty($operations)) {
    $operations[] = ['constructor_batch_translation_skipped', []];
  }

  return [
    'title' => t('Translating Content'),
    'operations' => $operations,
    'finished' => 'constructor_translate_content_finished',
  ];
}

/**
 * Batch operation: Translation skipped placeholder.
 */
function constructor_batch_translation_skipped(&$context) {
  $context['message'] = t('Translation skipped (no additional languages or API key).');
  $context['results'][] = 'translation_skipped';
}

/**
 * Batch finished callback for content translation.
 */
function constructor_translate_content_finished($success, $results, $operations) {
  if ($success) {
    \Drupal::messenger()->addStatus(t('Content translation completed.'));
  }
  else {
    \Drupal::messenger()->addWarning(t('Content translation had some issues.'));
  }
}

/**
 * Batch callback: Update translations.
 */
function constructor_update_translations_batch(&$install_state) {
  $operations = [];
  $operations[] = ['constructor_batch_update_translations', []];

  return [
    'title' => t('Updating Translations'),
    'operations' => $operations,
    'finished' => 'constructor_translations_batch_finished',
  ];
}

/**
 * Batch operation: Apply languages.
 */
function constructor_batch_apply_languages($constructor_settings, &$context) {
  $context['message'] = t('Configuring languages...');

  // Debug: Log what we received from key_value storage.
  $language_settings = $constructor_settings['languages'] ?? [];
  \Drupal::logger('constructor')->notice('batch_apply_languages received: @data', [
    '@data' => print_r($language_settings, TRUE),
  ]);

  constructor_apply_languages($context, $constructor_settings);
}

/**
 * Batch operation: Install language switcher module if multiple languages.
 */
function constructor_batch_install_language_switcher($constructor_settings, &$context) {
  $context['message'] = t('Installing language switcher...');

  $language_settings = $constructor_settings['languages'] ?? [];
  $additional_languages = $language_settings['additional_languages'] ?? [];
  $default_language = $language_settings['default_language'] ?? 'en';

  // Filter out empty values and the default language from additional languages.
  $additional_languages = array_filter($additional_languages, function ($langcode) use ($default_language) {
    return !empty($langcode) && $langcode !== $default_language;
  });

  // Check if we have at least one additional language (total > 1).
  $has_multiple_languages = !empty($additional_languages);

  \Drupal::logger('constructor')->notice('Language check: default=@default, additional=@additional, has_multiple=@multiple', [
    '@default' => $default_language,
    '@additional' => implode(', ', $additional_languages),
    '@multiple' => $has_multiple_languages ? 'yes' : 'no',
  ]);

  if ($has_multiple_languages) {
    try {
      /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
      $module_installer = \Drupal::service('module_installer');
      $module_handler = \Drupal::service('extension.list.module');

      // Check if language_switcher module exists before installing.
      if ($module_handler->exists('language_switcher')) {
        $module_installer->install(['language_switcher']);
        \Drupal::logger('constructor')->notice('Installed language_switcher module for multilingual support.');
      }
      else {
        \Drupal::logger('constructor')->warning('language_switcher module not found.');
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('constructor')->error('Failed to install language_switcher module: @message', [
        '@message' => $e->getMessage(),
      ]);
    }
  }
  else {
    \Drupal::logger('constructor')->notice('Language switcher not installed: less than 2 languages configured.');
  }

  $context['results'][] = 'language_switcher';
}

/**
 * Batch operation: Apply content type modules (like content_faq).
 */
function constructor_batch_apply_content_type_modules($constructor_settings, &$context) {
  $context['message'] = t('Installing content type modules...');

  $modules = $constructor_settings['content_type_modules'] ?? [];

  if (!empty($modules) && is_array($modules)) {
    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
    $module_installer = \Drupal::service('module_installer');

    foreach ($modules as $module) {
      if (!empty($module) && is_string($module)) {
        try {
          // Check if module exists before installing.
          $module_handler = \Drupal::service('extension.list.module');
          if ($module_handler->exists($module)) {
            $module_installer->install([$module]);
            \Drupal::logger('constructor')->notice('Installed content type module: @module', ['@module' => $module]);
          }
        }
        catch (\Exception $e) {
          \Drupal::logger('constructor')->error('Failed to install module @module: @message', [
            '@module' => $module,
            '@message' => $e->getMessage(),
          ]);
        }
      }
    }
  }

  $context['results'][] = 'content_type_modules';
}

/**
 * Batch operation: Apply content types.
 */
function constructor_batch_apply_content_types($constructor_settings, &$context) {
  $context['message'] = t('Creating content types...');
  constructor_apply_content_types($context, $constructor_settings);
}

/**
 * Batch operation: Apply modules.
 */
function constructor_batch_apply_modules($constructor_settings, &$context) {
  $context['message'] = t('Enabling modules...');
  constructor_apply_modules($context, $constructor_settings);
}

/**
 * Batch operation: Rebuild container after module installations.
 *
 * This is critical to ensure all entity types are properly registered
 * before any operations that use them (like creating blocks).
 */
function constructor_batch_rebuild_container(&$context) {
  $context['message'] = t('Rebuilding system...');

  try {
    // Rebuild the container to register all entity types.
    \Drupal::service('kernel')->rebuildContainer();

    // Clear specific caches without triggering route rebuilding.
    \Drupal::cache('discovery')->deleteAll();
    \Drupal::cache('config')->deleteAll();

    // Reset entity type manager.
    \Drupal::entityTypeManager()->clearCachedDefinitions();

    \Drupal::logger('constructor')->notice('Container rebuilt.');
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->error('Failed to rebuild container: @message', ['@message' => $e->getMessage()]);
  }

  $context['results'][] = 'container_rebuild';
}

/**
 * Batch operation: Apply layout.
 */
function constructor_batch_apply_layout($constructor_settings, &$context) {
  $context['message'] = t('Configuring layout...');
  constructor_apply_layout($context, $constructor_settings);
}

/**
 * Batch operation: Apply AI settings.
 */
function constructor_batch_apply_ai_settings($constructor_settings, &$context) {
  $context['message'] = t('Configuring AI integration...');
  constructor_apply_ai_settings($context, $constructor_settings);
}

/**
 * Batch operation: Place content blocks on frontpage.
 *
 * @deprecated Use constructor_batch_place_all_blocks() instead.
 */
function constructor_batch_place_content_blocks($constructor_settings, &$context) {
  $context['message'] = t('Registering content blocks...');
  // This function is now replaced by constructor_batch_place_all_blocks().
  $context['results'][] = 'content_blocks_deprecated';
}

/**
 * Batch operation: Generate AI content (FAQ nodes).
 */
function constructor_batch_generate_ai_content($constructor_settings, &$context) {
  $context['message'] = t('Generating AI content...');

  $content_type_modules = $constructor_settings['content_type_modules'] ?? [];
  $ai_settings = $constructor_settings['ai_settings'] ?? [];
  $site_basics = $constructor_settings['site_basics'] ?? [];
  $languages = $constructor_settings['languages'] ?? [];

  // Check if FAQ module is enabled.
  if (!in_array('content_faq', $content_type_modules)) {
    \Drupal::logger('constructor')->notice('AI content skipped: FAQ module not selected.');
    $context['results'][] = 'ai_content_skipped';
    return;
  }

  // Check if AI has API key configured.
  if (empty($ai_settings['api_key'])) {
    \Drupal::logger('constructor')->notice('AI content skipped: No API key configured.');
    $context['results'][] = 'ai_content_no_api';
    return;
  }

  // Get site description.
  $site_description = $site_basics['site_description'] ?? '';
  $site_name = $site_basics['site_name'] ?? 'Website';

  if (empty($site_description)) {
    $site_description = "A professional website for $site_name";
  }

  // Get main language.
  $main_language = $languages['default_language'] ?? 'en';
  $additional_languages = $languages['additional_languages'] ?? [];

  // Filter additional_languages to only valid langcodes (remove empty values and default language).
  $additional_languages = array_filter($additional_languages, function ($langcode) use ($main_language) {
    return !empty($langcode) && $langcode !== $main_language;
  });

  // Check if we have additional languages for translation.
  $has_translations = !empty($additional_languages);

  \Drupal::logger('constructor')->notice('Starting AI FAQ generation for site: @desc, language: @lang, translations: @trans', [
    '@desc' => $site_description,
    '@lang' => $main_language,
    '@trans' => $has_translations ? implode(', ', $additional_languages) : 'none',
  ]);

  try {
    // Generate FAQ content using OpenAI.
    $faqs = _constructor_generate_faq_with_ai($site_description, $main_language, $ai_settings);

    \Drupal::logger('constructor')->notice('AI returned @count FAQs.', ['@count' => count($faqs)]);

    if (!empty($faqs)) {
      // Create FAQ nodes.
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');

      // Get or create a Content Editor user for AI-generated content.
      $content_editor_uid = _constructor_get_content_editor_user();

      foreach ($faqs as $index => $faq) {
        \Drupal::logger('constructor')->notice('Creating FAQ @num: @question', [
          '@num' => $index + 1,
          '@question' => $faq['question'],
        ]);

        $node = $node_storage->create([
          'type' => 'faq',
          'title' => $faq['question'],
          'field_faq_answer' => [
            'value' => $faq['answer'],
            'format' => 'full_html',
          ],
          'status' => 1,
          'langcode' => $main_language,
          'uid' => $content_editor_uid,
        ]);
        $node->save();

        // NOTE: Translation is handled by CompleteSetupController in a fresh HTTP request
        // to avoid entity hook issues during installation batch.
      }

      \Drupal::logger('constructor')->notice('Generated @count FAQ nodes with AI.', ['@count' => count($faqs)]);
    }
    else {
      \Drupal::logger('constructor')->warning('AI returned empty FAQ list.');
    }
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->error('Failed to generate AI content: @message', ['@message' => $e->getMessage()]);
  }

  // Generate Team Members if content_team module is enabled.
  if (in_array('content_team', $content_type_modules)) {
    _constructor_generate_team_members_with_ai($site_name, $site_description, $main_language, $ai_settings, $content_editor_uid ?? 1);
  }

  $context['results'][] = 'ai_content';
}

/**
 * Enable translation for FAQ content type.
 */
function _constructor_enable_faq_translation() {
  // Check if content_translation module is enabled.
  if (!\Drupal::moduleHandler()->moduleExists('content_translation')) {
    \Drupal::logger('constructor')->notice('Content translation module not enabled, skipping FAQ translation setup.');
    return FALSE;
  }

  // Check if language module is enabled.
  if (!\Drupal::moduleHandler()->moduleExists('language')) {
    \Drupal::logger('constructor')->notice('Language module not enabled, skipping FAQ translation setup.');
    return FALSE;
  }

  try {
    // Enable translation for the FAQ content type.
    $config = \Drupal::configFactory()->getEditable('language.content_settings.node.faq');
    // IMPORTANT: The 'id' key is required by ContentLanguageSettings entity.
    $config->set('id', 'node.faq');
    $config->set('langcode', 'en');
    $config->set('status', TRUE);
    $config->set('target_entity_type_id', 'node');
    $config->set('target_bundle', 'faq');
    $config->set('default_langcode', 'site_default');
    $config->set('language_alterable', TRUE);
    $config->set('third_party_settings.content_translation.enabled', TRUE);
    $config->save();

    \Drupal::logger('constructor')->notice('Enabled translation for FAQ content type.');
    return TRUE;
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->error('Failed to enable FAQ translation: @message', ['@message' => $e->getMessage()]);
    return FALSE;
  }
}

/**
 * Enable translation for Team content type.
 */
function _constructor_enable_team_translation() {
  // Check if content_translation module is enabled.
  if (!\Drupal::moduleHandler()->moduleExists('content_translation')) {
    \Drupal::logger('constructor')->notice('Content translation module not enabled, skipping Team translation setup.');
    return FALSE;
  }

  // Check if language module is enabled.
  if (!\Drupal::moduleHandler()->moduleExists('language')) {
    \Drupal::logger('constructor')->notice('Language module not enabled, skipping Team translation setup.');
    return FALSE;
  }

  try {
    // Enable translation for the Team content type.
    $config = \Drupal::configFactory()->getEditable('language.content_settings.node.team_member');
    // IMPORTANT: The 'id' key is required by ContentLanguageSettings entity.
    $config->set('id', 'node.team_member');
    $config->set('langcode', 'en');
    $config->set('status', TRUE);
    $config->set('target_entity_type_id', 'node');
    $config->set('target_bundle', 'team_member');
    $config->set('default_langcode', 'site_default');
    $config->set('language_alterable', TRUE);
    $config->set('third_party_settings.content_translation.enabled', TRUE);
    $config->save();

    \Drupal::logger('constructor')->notice('Enabled translation for Team content type.');
    return TRUE;
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->error('Failed to enable Team translation: @message', ['@message' => $e->getMessage()]);
    return FALSE;
  }
}

/**
 * Generate FAQ content using OpenAI API.
 */
function _constructor_generate_faq_with_ai($site_description, $language, $ai_settings) {
  $api_key = $ai_settings['api_key'];
  $model = $ai_settings['text_model'] ?? 'gpt-4';

  // Language names for prompt.
  $language_names = [
    'en' => 'English',
    'uk' => 'Ukrainian',
    'de' => 'German',
    'fr' => 'French',
    'es' => 'Spanish',
  ];
  $language_name = $language_names[$language] ?? 'English';

  $prompt = "Generate 5 frequently asked questions (FAQs) for a website with the following description: \"$site_description\".

Please respond in $language_name language.

Return the response as a JSON array with objects containing 'question' and 'answer' keys. Each answer should be 2-3 sentences. Example format:
[
  {\"question\": \"Question text here?\", \"answer\": \"Answer text here.\"},
  ...
]

Only return the JSON array, no other text.";

  try {
    $client = \Drupal::httpClient();
    $response = $client->post('https://api.openai.com/v1/chat/completions', [
      'headers' => [
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
      ],
      'json' => [
        'model' => $model,
        'messages' => [
          ['role' => 'user', 'content' => $prompt],
        ],
        'temperature' => 0.7,
        'max_tokens' => 2000,
      ],
      'timeout' => 60,
    ]);

    $data = json_decode($response->getBody()->getContents(), TRUE);
    $content = $data['choices'][0]['message']['content'] ?? '';

    // Parse JSON from response.
    $content = trim($content);
    // Remove markdown code blocks if present.
    $content = preg_replace('/^```json\s*/', '', $content);
    $content = preg_replace('/\s*```$/', '', $content);

    $faqs = json_decode($content, TRUE);

    if (is_array($faqs) && !empty($faqs)) {
      return $faqs;
    }
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->error('OpenAI API error: @message', ['@message' => $e->getMessage()]);
  }

  return [];
}

/**
 * Generate team members using AI.
 */
function _constructor_generate_team_members_with_ai($site_name, $site_description, $language, $ai_settings, $author_uid) {
  $api_key = $ai_settings['api_key'];
  $model = $ai_settings['text_model'] ?? 'gpt-4';


  $language_names = [
    'en' => 'English',
    'uk' => 'Ukrainian',
    'de' => 'German',
    'fr' => 'French',
    'es' => 'Spanish',
  ];
  $language_name = $language_names[$language] ?? 'English';

  $prompt = "Generate 5 fictional team members for a company with the following description: \"$site_description\".

Please respond in $language_name language.

Return the response as a JSON array with objects containing 'name', 'position', and 'bio' keys. Example format:
[
  {\"name\": \"Full Name\", \"position\": \"Job Title\", \"bio\": \"A detailed biography paragraph about this person's background, expertise, and role in the company.\"},
  ...
]

Make the names, positions and bios realistic and diverse. The bio should be HTML formatted with <p> tags. Only return the JSON array, no other text.";

  try {
    $client = \Drupal::httpClient();
    $response = $client->post('https://api.openai.com/v1/chat/completions', [
      'headers' => [
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
      ],
      'json' => [
        'model' => $model,
        'messages' => [
          ['role' => 'user', 'content' => $prompt],
        ],
        'temperature' => 0.7,
        'max_tokens' => 1000,
      ],
      'timeout' => 60,
    ]);

    $data = json_decode($response->getBody()->getContents(), TRUE);
    $content = $data['choices'][0]['message']['content'] ?? '';

    // Parse JSON from response.
    $content = trim($content);
    $content = preg_replace('/^```json\s*/', '', $content);
    $content = preg_replace('/\s*```$/', '', $content);

    $team_members = json_decode($content, TRUE);

    if (is_array($team_members) && !empty($team_members)) {
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');

      foreach ($team_members as $index => $member) {
        $node = $node_storage->create([
          'type' => 'team_member',
          'title' => $member['name'],
          'field_team_position' => $member['position'],
          'field_team_bio' => [
            'value' => $member['bio'] ?? '',
            'format' => 'full_html',
          ],
          'status' => 1,
          'langcode' => $language,
          'uid' => $author_uid,
        ]);
        $node->save();

        \Drupal::logger('constructor')->notice('Created team member: @name (@position)', [
          '@name' => $member['name'],
          '@position' => $member['position'],
        ]);
      }

      \Drupal::logger('constructor')->notice('Generated @count team members with AI.', ['@count' => count($team_members)]);
    }
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->error('Failed to generate team members with AI: @message', ['@message' => $e->getMessage()]);
  }
}

/**
 * Generate services content using OpenAI API.
 */
function _constructor_generate_services_with_ai($site_name, $site_description, $language, $ai_settings, $author_uid) {
  $api_key = $ai_settings['api_key'];
  $model = $ai_settings['text_model'] ?? 'gpt-4';

  // Default Unsplash images for services.
  $unsplash_images = [
    'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=500&h=400&fit=crop&q=80',
    'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=500&h=400&fit=crop&q=80',
    'https://images.unsplash.com/photo-1553877522-43269d4ea984?w=500&h=400&fit=crop&q=80',
    'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=500&h=400&fit=crop&q=80',
    'https://images.unsplash.com/photo-1556761175-5973dc0f32e7?w=500&h=400&fit=crop&q=80',
  ];

  $language_names = [
    'en' => 'English',
    'uk' => 'Ukrainian',
    'de' => 'German',
    'fr' => 'French',
    'es' => 'Spanish',
  ];
  $language_name = $language_names[$language] ?? 'English';

  $prompt = "Generate 5 services for a company with the following description: \"$site_description\".

Please respond in $language_name language.

Return the response as a JSON array with objects containing 'name', 'description', and 'body' keys. Example format:
[
  {\"name\": \"Service Name\", \"description\": \"Brief description (1-2 sentences)\", \"body\": \"Detailed description with 2-3 paragraphs about the service, its benefits, and how it helps clients.\"},
  ...
]

Make the services realistic and relevant to the business. The body should be HTML formatted with <p> tags. Only return the JSON array, no other text.";

  try {
    $client = \Drupal::httpClient();
    $response = $client->post('https://api.openai.com/v1/chat/completions', [
      'headers' => [
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
      ],
      'json' => [
        'model' => $model,
        'messages' => [
          ['role' => 'user', 'content' => $prompt],
        ],
        'temperature' => 0.7,
        'max_tokens' => 1500,
      ],
      'timeout' => 60,
    ]);

    $data = json_decode($response->getBody()->getContents(), TRUE);
    $content = $data['choices'][0]['message']['content'] ?? '';

    // Parse JSON from response.
    $content = trim($content);
    $content = preg_replace('/^```json\s*/', '', $content);
    $content = preg_replace('/\s*```$/', '', $content);

    $services = json_decode($content, TRUE);

    if (is_array($services) && !empty($services)) {
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');

      foreach ($services as $index => $service) {
        $node = $node_storage->create([
          'type' => 'service',
          'title' => $service['name'],
          // Description is plain text.
          'field_service_description' => $service['description'],
          'field_service_body' => [
            'value' => $service['body'] ?? '',
            'format' => 'full_html',
          ],
          // Note: Images are now generated by the batch operation using field_service_image.
          'status' => 1,
          'langcode' => $language,
          'uid' => $author_uid,
        ]);
        $node->save();

        \Drupal::logger('constructor')->notice('Created service: @name', [
          '@name' => $service['name'],
        ]);
      }

      \Drupal::logger('constructor')->notice('Generated @count services with AI.', ['@count' => count($services)]);
    }
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->error('Failed to generate services with AI: @message', ['@message' => $e->getMessage()]);
  }
}

/**
 * Enable translation for Service content type.
 */
function _constructor_enable_service_translation() {
  // Check if content_translation module is enabled.
  if (!\Drupal::moduleHandler()->moduleExists('content_translation')) {
    \Drupal::logger('constructor')->notice('Content translation module not enabled, skipping Service translation setup.');
    return FALSE;
  }

  // Check if language module is enabled.
  if (!\Drupal::moduleHandler()->moduleExists('language')) {
    \Drupal::logger('constructor')->notice('Language module not enabled, skipping Service translation setup.');
    return FALSE;
  }

  try {
    // Enable translation for the Service content type.
    $config = \Drupal::configFactory()->getEditable('language.content_settings.node.service');
    // IMPORTANT: The 'id' key is required by ContentLanguageSettings entity.
    $config->set('id', 'node.service');
    $config->set('langcode', 'en');
    $config->set('status', TRUE);
    $config->set('target_entity_type_id', 'node');
    $config->set('target_bundle', 'service');
    $config->set('default_langcode', 'site_default');
    $config->set('language_alterable', TRUE);
    $config->set('third_party_settings.content_translation.enabled', TRUE);
    $config->save();

    \Drupal::logger('constructor')->notice('Enabled translation for Service content type.');
    return TRUE;
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->error('Failed to enable Service translation: @message', ['@message' => $e->getMessage()]);
    return FALSE;
  }
}

/**
 * Generate articles content using OpenAI API.
 */
function _constructor_generate_articles_with_ai($site_name, $site_description, $language, $ai_settings, $author_uid) {
  $api_key = $ai_settings['api_key'];
  $model = $ai_settings['text_model'] ?? 'gpt-4';

  // Default YouTube video IDs for articles with video.
  $youtube_videos = [
    'bTqVqk7FSmY',
  ];

  // Default Unsplash images for articles.
  $unsplash_images = [
    'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=800&h=600&fit=crop&q=80',
    'https://images.unsplash.com/photo-1494976388531-d1058494cdd8?w=800&h=600&fit=crop&q=80',
    'https://images.unsplash.com/photo-1603584173870-7f23fdae1b7a?w=800&h=600&fit=crop&q=80',
    'https://images.unsplash.com/photo-1593941707882-a5bba14938c7?w=800&h=600&fit=crop&q=80',
    'https://images.unsplash.com/photo-1560472354-b33ff0c44a43?w=800&h=600&fit=crop&q=80',
  ];

  $language_names = [
    'en' => 'English',
    'uk' => 'Ukrainian',
    'de' => 'German',
    'fr' => 'French',
    'es' => 'Spanish',
  ];
  $language_name = $language_names[$language] ?? 'English';

  $prompt = "Generate 5 articles for a company with the following description: \"$site_description\".

Please respond in $language_name language.

Return the response as a JSON array with objects containing 'title' and 'body' keys. Example format:
[
  {\"title\": \"Article Title Here\", \"body\": \"Full article content with 2-3 paragraphs, HTML formatted with <p> tags.\"},
  ...
]

The articles should be informative blog posts, news, or case studies relevant to the business. Each article should be 2-3 paragraphs. Only return the JSON array, no other text.";

  try {
    $client = \Drupal::httpClient();
    $response = $client->post('https://api.openai.com/v1/chat/completions', [
      'headers' => [
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
      ],
      'json' => [
        'model' => $model,
        'messages' => [
          ['role' => 'user', 'content' => $prompt],
        ],
        'temperature' => 0.7,
        'max_tokens' => 2000,
      ],
      'timeout' => 60,
    ]);

    $data = json_decode($response->getBody()->getContents(), TRUE);
    $content = $data['choices'][0]['message']['content'] ?? '';

    // Parse JSON from response.
    $content = trim($content);
    $content = preg_replace('/^```json\s*/', '', $content);
    $content = preg_replace('/\s*```$/', '', $content);

    $articles = json_decode($content, TRUE);

    if (is_array($articles) && !empty($articles)) {
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');

      foreach ($articles as $index => $article) {
        // First 2 articles get YouTube videos, rest get images.
        $video_url = '';
        if ($index < 2 && isset($youtube_videos[$index])) {
          $video_url = 'https://www.youtube.com/watch?v=' . $youtube_videos[$index];
        }

        $node_data = [
          'type' => 'article',
          'title' => $article['title'],
          'field_article_body' => [
            'value' => $article['body'] ?? '',
            'format' => 'full_html',
          ],
          'field_article_video_url' => $video_url,
          'status' => 1,
          'langcode' => $language,
          'uid' => $author_uid,
        ];

        $node = $node_storage->create($node_data);
        $node->save();

        \Drupal::logger('constructor')->notice('Created article: @title', [
          '@title' => $article['title'],
        ]);
      }

      \Drupal::logger('constructor')->notice('Generated @count articles with AI.', ['@count' => count($articles)]);
    }
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->error('Failed to generate articles with AI: @message', ['@message' => $e->getMessage()]);
  }
}

/**
 * Generate products content using OpenAI API.
 */
function _constructor_generate_products_with_ai($site_name, $site_description, $language, $ai_settings, $author_uid) {
  $api_key = $ai_settings['api_key'];
  $model = $ai_settings['text_model'] ?? 'gpt-4';

  // Default Unsplash product images.
  $unsplash_images = [
    'https://images.unsplash.com/photo-1572635196237-14b3f281503f?w=400&h=500&fit=crop&q=80',
    'https://images.unsplash.com/photo-1591047139829-d91aecb6caea?w=400&h=500&fit=crop&q=80',
    'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400&h=500&fit=crop&q=80',
    'https://images.unsplash.com/photo-1560343090-f0409e92791a?w=400&h=500&fit=crop&q=80',
    'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400&h=500&fit=crop&q=80',
    'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=400&h=500&fit=crop&q=80',
  ];

  // Default categories.
  $categories = ['T-Shirt', 'Shoes', 'Jackets', 'Accessories', 'Shorts', 'Hat'];

  $language_names = [
    'en' => 'English',
    'uk' => 'Ukrainian',
    'de' => 'German',
    'fr' => 'French',
    'es' => 'Spanish',
  ];
  $language_name = $language_names[$language] ?? 'English';

  // First, create product categories.
  _constructor_create_product_categories($categories, $language);

  $prompt = "Generate 6 products for a company with the following description: \"$site_description\".

Please respond in $language_name language.

Return the response as a JSON array with objects containing 'name', 'description', 'price', 'sale_price', 'category', 'sku' keys. Example format:
[
  {\"name\": \"Product Name\", \"description\": \"Product description (2-3 sentences)\", \"price\": 99.99, \"sale_price\": null, \"category\": \"T-Shirt\", \"sku\": \"SKU-001\"},
  ...
]

Categories must be one of: " . implode(', ', $categories) . "
Some products should have sale_price (lower than price), others should have null for sale_price.
Prices should be realistic (between 25 and 500).
Only return the JSON array, no other text.";

  try {
    $client = \Drupal::httpClient();
    $response = $client->post('https://api.openai.com/v1/chat/completions', [
      'headers' => [
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
      ],
      'json' => [
        'model' => $model,
        'messages' => [
          ['role' => 'user', 'content' => $prompt],
        ],
        'temperature' => 0.7,
        'max_tokens' => 1500,
      ],
      'timeout' => 60,
    ]);

    $data = json_decode($response->getBody()->getContents(), TRUE);
    $content = $data['choices'][0]['message']['content'] ?? '';

    // Parse JSON from response.
    $content = trim($content);
    $content = preg_replace('/^```json\s*/', '', $content);
    $content = preg_replace('/\s*```$/', '', $content);

    $products = json_decode($content, TRUE);

    if (is_array($products) && !empty($products)) {
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

      // Default colors and sizes.
      $default_colors = 'Black:#1f2937,Green:#059669,Gold:#fcd34d,Pink:#f9a8d4,Gray:#9ca3af';
      $default_sizes = 'Small,Medium,Large,XL,XXL';

      foreach ($products as $index => $product) {
        // Find category term.
        $category_tid = NULL;
        $terms = $term_storage->loadByProperties([
          'vid' => 'product_category',
          'name' => $product['category'] ?? 'Accessories',
        ]);
        if ($terms) {
          $category_tid = reset($terms)->id();
        }

        // Generate product image with AI if API key is available.
        $product_image = NULL;
        if (!empty($api_key)) {
          $product_image = _constructor_generate_product_image_with_ai(
            $product['name'],
            $product['category'] ?? 'Product',
            $api_key,
            $index
          );
        }

        $node_values = [
          'type' => 'product',
          'title' => $product['name'],
          'field_product_body' => [
            'value' => '<p>' . ($product['description'] ?? '') . '</p>',
            'format' => 'full_html',
          ],
          'field_product_price' => $product['price'] ?? 99.99,
          'field_product_sale_price' => $product['sale_price'] ?? NULL,
          'field_product_category' => $category_tid,
          'field_product_sku' => $product['sku'] ?? 'SKU-' . ($index + 1),
          'field_product_in_stock' => TRUE,
          'field_product_featured' => $index < 2,
          'field_product_colors' => $default_colors,
          'field_product_sizes' => $default_sizes,
          'status' => 1,
          'langcode' => $language,
          'uid' => $author_uid,
        ];

        // Add image if generated.
        if ($product_image) {
          $node_values['field_product_images'] = [
            [
              'target_id' => $product_image->id(),
              'alt' => $product['name'],
            ],
          ];
        }

        $node = $node_storage->create($node_values);
        $node->save();

        \Drupal::logger('constructor')->notice('Created product: @name (with AI image: @img)', [
          '@name' => $product['name'],
          '@img' => $product_image ? 'yes' : 'no',
        ]);
      }

      \Drupal::logger('constructor')->notice('Generated @count products with AI.', ['@count' => count($products)]);
    }
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->error('Failed to generate products with AI: @message', ['@message' => $e->getMessage()]);
  }
}

/**
 * Generate a product image using AI (DALL-E).
 *
 * @param string $product_name
 *   The product name.
 * @param string $category
 *   The product category.
 * @param string $api_key
 *   The OpenAI API key.
 * @param int $index
 *   The product index.
 *
 * @return \Drupal\file\FileInterface|null
 *   The generated file entity or NULL on failure.
 */
function _constructor_generate_product_image_with_ai($product_name, $category, $api_key, $index) {
  try {
    $client = \Drupal::httpClient();

    // Create a photorealistic product photography prompt.
    $prompt = "A {$category} called '{$product_name}', " .
      "clean white background, realistic studio lighting, centered composition, " .
      "photorealistic, ultra-realistic, high-resolution, sharp details, realistic lighting";

    // Generate image using DALL-E.
    $response = $client->post('https://api.openai.com/v1/images/generations', [
      'headers' => [
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
      ],
      'json' => [
        'model' => 'dall-e-3',
        'prompt' => $prompt,
        'n' => 1,
        'size' => '1024x1024',
        'quality' => 'standard',
      ],
      'timeout' => 120,
    ]);

    $data = json_decode($response->getBody()->getContents(), TRUE);
    $image_url = $data['data'][0]['url'] ?? NULL;

    if ($image_url) {
      // Download the image.
      $image_response = $client->get($image_url, ['timeout' => 60]);
      $image_data = (string) $image_response->getBody();

      if (!empty($image_data)) {
        // Prepare directory.
        $directory = 'public://products';
        $file_system = \Drupal::service('file_system');
        $file_system->prepareDirectory($directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY | \Drupal\Core\File\FileSystemInterface::MODIFY_PERMISSIONS);

        // Create safe filename.
        $transliteration = \Drupal::service('transliteration');
        $clean_name = $transliteration->transliterate($product_name, 'en');
        $clean_name = preg_replace('/[^a-z0-9]+/i', '-', strtolower($clean_name));
        $clean_name = trim($clean_name, '-');
        $clean_name = substr($clean_name, 0, 40);

        $filename = 'product-' . ($index + 1) . '-' . $clean_name . '-' . time() . '.png';
        $destination = $directory . '/' . $filename;

        // Save file.
        $file_repository = \Drupal::service('file.repository');
        $file = $file_repository->writeData($image_data, $destination, \Drupal\Core\File\FileSystemInterface::EXISTS_RENAME);

        if ($file) {
          $file->setPermanent();
          $file->save();

          \Drupal::logger('constructor')->notice('Generated AI image for product: @name', [
            '@name' => $product_name,
          ]);

          return $file;
        }
      }
    }
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->warning('Failed to generate product image for @name: @message', [
      '@name' => $product_name,
      '@message' => $e->getMessage(),
    ]);
  }

  return NULL;
}

/**
 * Generate a team member photo using AI (DALL-E).
 *
 * @param string $name
 *   The team member name.
 * @param string $position
 *   The team member position.
 * @param string $site_description
 *   The site description for context.
 * @param string $api_key
 *   The OpenAI API key.
 * @param int $index
 *   The team member index.
 *
 * @return \Drupal\file\FileInterface|null
 *   The generated file entity or NULL on failure.
 */
function _constructor_generate_team_photo_with_ai($name, $position, $site_description, $api_key, $index) {
  try {
    $client = \Drupal::httpClient();

    // Create a headshot photo prompt.
    $prompt = "A business professional, position: {$position}, " .
      "clean neutral background, natural lighting, sharp focus, " .
      "business attire, confident pose, natural expression, " .
      "photorealistic, ultra-realistic, high-resolution, sharp details, realistic lighting";

    // Generate image using DALL-E.
    $response = $client->post('https://api.openai.com/v1/images/generations', [
      'headers' => [
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
      ],
      'json' => [
        'model' => 'dall-e-3',
        'prompt' => $prompt,
        'n' => 1,
        'size' => '1024x1024',
        'quality' => 'standard',
      ],
      'timeout' => 120,
    ]);

    $data = json_decode($response->getBody()->getContents(), TRUE);
    $image_url = $data['data'][0]['url'] ?? NULL;

    if ($image_url) {
      // Download the image.
      $image_response = $client->get($image_url, ['timeout' => 60]);
      $image_data = (string) $image_response->getBody();

      if (!empty($image_data)) {
        // Prepare directory.
        $directory = 'public://team-photos';
        $file_system = \Drupal::service('file_system');
        $file_system->prepareDirectory($directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY | \Drupal\Core\File\FileSystemInterface::MODIFY_PERMISSIONS);

        // Create safe filename.
        $transliteration = \Drupal::service('transliteration');
        $clean_name = $transliteration->transliterate($name, 'en');
        $clean_name = preg_replace('/[^a-z0-9]+/i', '-', strtolower($clean_name));
        $clean_name = trim($clean_name, '-');
        $clean_name = substr($clean_name, 0, 40);

        $filename = 'team-' . ($index + 1) . '-' . $clean_name . '-' . time() . '.png';
        $destination = $directory . '/' . $filename;

        // Save file.
        $file_repository = \Drupal::service('file.repository');
        $file = $file_repository->writeData($image_data, $destination, \Drupal\Core\File\FileSystemInterface::EXISTS_RENAME);

        if ($file) {
          $file->setPermanent();
          $file->save();

          \Drupal::logger('constructor')->notice('Generated AI photo for team member: @name', [
            '@name' => $name,
          ]);

          return $file;
        }
      }
    }
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->warning('Failed to generate team photo for @name: @message', [
      '@name' => $name,
      '@message' => $e->getMessage(),
    ]);
  }

  return NULL;
}

/**
 * Generate a service image using AI (DALL-E).
 *
 * @param string $service_name
 *   The service name.
 * @param string $description
 *   The service description.
 * @param string $site_description
 *   The site description for context.
 * @param string $api_key
 *   The OpenAI API key.
 * @param int $index
 *   The service index.
 *
 * @return \Drupal\file\FileInterface|null
 *   The generated file entity or NULL on failure.
 */
function _constructor_generate_service_image_with_ai($service_name, $description, $site_description, $api_key, $index) {
  try {
    $client = \Drupal::httpClient();

    // Create a service image prompt.
    $prompt = "'{$service_name}' service in action, natural lighting, " .
      "photorealistic, ultra-realistic, high-resolution, sharp details, realistic lighting";

    // Generate image using DALL-E.
    $response = $client->post('https://api.openai.com/v1/images/generations', [
      'headers' => [
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
      ],
      'json' => [
        'model' => 'dall-e-3',
        'prompt' => $prompt,
        'n' => 1,
        'size' => '1024x1024',
        'quality' => 'standard',
      ],
      'timeout' => 120,
    ]);

    $data = json_decode($response->getBody()->getContents(), TRUE);
    $image_url = $data['data'][0]['url'] ?? NULL;

    if ($image_url) {
      // Download the image.
      $image_response = $client->get($image_url, ['timeout' => 60]);
      $image_data = (string) $image_response->getBody();

      if (!empty($image_data)) {
        // Prepare directory.
        $directory = 'public://services';
        $file_system = \Drupal::service('file_system');
        $file_system->prepareDirectory($directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY | \Drupal\Core\File\FileSystemInterface::MODIFY_PERMISSIONS);

        // Create safe filename.
        $transliteration = \Drupal::service('transliteration');
        $clean_name = $transliteration->transliterate($service_name, 'en');
        $clean_name = preg_replace('/[^a-z0-9]+/i', '-', strtolower($clean_name));
        $clean_name = trim($clean_name, '-');
        $clean_name = substr($clean_name, 0, 40);

        $filename = 'service-' . ($index + 1) . '-' . $clean_name . '-' . time() . '.png';
        $destination = $directory . '/' . $filename;

        // Save file.
        $file_repository = \Drupal::service('file.repository');
        $file = $file_repository->writeData($image_data, $destination, \Drupal\Core\File\FileSystemInterface::EXISTS_RENAME);

        if ($file) {
          $file->setPermanent();
          $file->save();

          \Drupal::logger('constructor')->notice('Generated AI image for service: @name', [
            '@name' => $service_name,
          ]);

          return $file;
        }
      }
    }
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->warning('Failed to generate service image for @name: @message', [
      '@name' => $service_name,
      '@message' => $e->getMessage(),
    ]);
  }

  return NULL;
}

/**
 * Generate an article image using AI (DALL-E).
 *
 * @param string $title
 *   The article title.
 * @param string $site_description
 *   The site description for context.
 * @param string $api_key
 *   The OpenAI API key.
 * @param int $index
 *   The article index.
 *
 * @return \Drupal\file\FileInterface|null
 *   The generated file entity or NULL on failure.
 */
function _constructor_generate_article_image_with_ai($title, $site_description, $api_key, $index) {
  try {
    $client = \Drupal::httpClient();

    // Create an article image prompt.
    $prompt = "Article about '{$title}', natural lighting, " .
      "photorealistic, ultra-realistic, high-resolution, sharp details, realistic lighting";

    // Generate image using DALL-E.
    $response = $client->post('https://api.openai.com/v1/images/generations', [
      'headers' => [
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
      ],
      'json' => [
        'model' => 'dall-e-3',
        'prompt' => $prompt,
        'n' => 1,
        'size' => '1024x1024',
        'quality' => 'standard',
      ],
      'timeout' => 120,
    ]);

    $data = json_decode($response->getBody()->getContents(), TRUE);
    $image_url = $data['data'][0]['url'] ?? NULL;

    if ($image_url) {
      // Download the image.
      $image_response = $client->get($image_url, ['timeout' => 60]);
      $image_data = (string) $image_response->getBody();

      if (!empty($image_data)) {
        // Prepare directory.
        $directory = 'public://articles';
        $file_system = \Drupal::service('file_system');
        $file_system->prepareDirectory($directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY | \Drupal\Core\File\FileSystemInterface::MODIFY_PERMISSIONS);

        // Create safe filename.
        $transliteration = \Drupal::service('transliteration');
        $clean_name = $transliteration->transliterate($title, 'en');
        $clean_name = preg_replace('/[^a-z0-9]+/i', '-', strtolower($clean_name));
        $clean_name = trim($clean_name, '-');
        $clean_name = substr($clean_name, 0, 40);

        $filename = 'article-' . ($index + 1) . '-' . $clean_name . '-' . time() . '.png';
        $destination = $directory . '/' . $filename;

        // Save file.
        $file_repository = \Drupal::service('file.repository');
        $file = $file_repository->writeData($image_data, $destination, \Drupal\Core\File\FileSystemInterface::EXISTS_RENAME);

        if ($file) {
          $file->setPermanent();
          $file->save();

          \Drupal::logger('constructor')->notice('Generated AI image for article: @title', [
            '@title' => $title,
          ]);

          return $file;
        }
      }
    }
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->warning('Failed to generate article image for @title: @message', [
      '@title' => $title,
      '@message' => $e->getMessage(),
    ]);
  }

  return NULL;
}

/**
 * Create product categories.
 */
function _constructor_create_product_categories($categories, $language) {
  $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

  foreach ($categories as $category_name) {
    // Check if term already exists.
    $existing = $term_storage->loadByProperties([
      'vid' => 'product_category',
      'name' => $category_name,
    ]);

    if (empty($existing)) {
      $term = $term_storage->create([
        'vid' => 'product_category',
        'name' => $category_name,
        'langcode' => $language,
      ]);
      $term->save();

      \Drupal::logger('constructor')->notice('Created product category: @name', [
        '@name' => $category_name,
      ]);
    }
  }
}

/**
 * Enable translation for Product content type.
 */
function _constructor_enable_product_translation() {
  // Check if content_translation module is enabled.
  if (!\Drupal::moduleHandler()->moduleExists('content_translation')) {
    \Drupal::logger('constructor')->notice('Content translation module not enabled, skipping Product translation setup.');
    return FALSE;
  }

  // Check if language module is enabled.
  if (!\Drupal::moduleHandler()->moduleExists('language')) {
    \Drupal::logger('constructor')->notice('Language module not enabled, skipping Product translation setup.');
    return FALSE;
  }

  try {
    // Enable translation for the Product content type.
    $config = \Drupal::configFactory()->getEditable('language.content_settings.node.product');
    $config->set('id', 'node.product');
    $config->set('langcode', 'en');
    $config->set('status', TRUE);
    $config->set('target_entity_type_id', 'node');
    $config->set('target_bundle', 'product');
    $config->set('default_langcode', 'site_default');
    $config->set('language_alterable', TRUE);
    $config->set('third_party_settings.content_translation.enabled', TRUE);
    $config->save();

    \Drupal::logger('constructor')->notice('Enabled translation for Product content type.');
    return TRUE;
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->error('Failed to enable Product translation: @message', ['@message' => $e->getMessage()]);
    return FALSE;
  }
}

/**
 * Enable translation for Article content type.
 */
function _constructor_enable_article_translation() {
  // Check if content_translation module is enabled.
  if (!\Drupal::moduleHandler()->moduleExists('content_translation')) {
    \Drupal::logger('constructor')->notice('Content translation module not enabled, skipping Article translation setup.');
    return FALSE;
  }

  // Check if language module is enabled.
  if (!\Drupal::moduleHandler()->moduleExists('language')) {
    \Drupal::logger('constructor')->notice('Language module not enabled, skipping Article translation setup.');
    return FALSE;
  }

  try {
    // Enable translation for the Article content type.
    $config = \Drupal::configFactory()->getEditable('language.content_settings.node.article');
    // IMPORTANT: The 'id' key is required by ContentLanguageSettings entity.
    $config->set('id', 'node.article');
    $config->set('langcode', 'en');
    $config->set('status', TRUE);
    $config->set('target_entity_type_id', 'node');
    $config->set('target_bundle', 'article');
    $config->set('default_langcode', 'site_default');
    $config->set('language_alterable', TRUE);
    $config->set('third_party_settings.content_translation.enabled', TRUE);
    $config->save();

    \Drupal::logger('constructor')->notice('Enabled translation for Article content type.');
    return TRUE;
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->error('Failed to enable Article translation: @message', ['@message' => $e->getMessage()]);
    return FALSE;
  }
}

/**
 * Get or create a Content Editor user for AI-generated content.
 *
 * @return int
 *   The user ID of the Content Editor user.
 */
function _constructor_get_content_editor_user() {
  $user_storage = \Drupal::entityTypeManager()->getStorage('user');

  // Try to find an existing user with Content Editor role.
  $query = $user_storage->getQuery()
    ->condition('roles', 'content_editor')
    ->condition('status', 1)
    ->range(0, 1)
    ->accessCheck(FALSE);
  $uids = $query->execute();

  if (!empty($uids)) {
    return reset($uids);
  }

  // Create a new Content Editor user.
  $user = $user_storage->create([
    'name' => 'content_editor',
    'mail' => 'content_editor@' . \Drupal::request()->getHost(),
    'status' => 1,
    'roles' => ['content_editor'],
  ]);
  $user->save();

  \Drupal::logger('constructor')->notice('Created Content Editor user with uid @uid.', ['@uid' => $user->id()]);

  return $user->id();
}

/**
 * Batch operation: Place language switcher block in header.
 *
 * @deprecated Use constructor_batch_place_all_blocks() instead.
 */
function constructor_batch_place_language_switcher_block($constructor_settings, &$context) {
  $context['message'] = t('Registering language switcher...');
  // This function is now replaced by constructor_batch_place_all_blocks().
  $context['results'][] = 'language_switcher_deprecated';
}

/**
 * Batch operation: Configure frontpage.
 */
function constructor_batch_configure_frontpage($constructor_settings, &$context) {
  $context['message'] = t('Configuring frontpage...');

  // Set front page to use views or a custom route instead of /node.
  // For now, we'll just disable the default content message by ensuring
  // the frontpage doesn't fall back to node listing.
  $config = \Drupal::configFactory()->getEditable('system.site');

  // Check if there's a specific frontpage node, otherwise use the theme's frontpage.
  // We set it to a non-existent path that our theme handles.
  $config->set('page.front', '/frontpage');
  $config->save();

  $context['results'][] = 'frontpage';
}

/**
 * Batch operation: Set default theme.
 */
function constructor_batch_set_default_theme(&$context) {
  $context['message'] = t('Setting default theme...');

  // Enable and set constructor_theme as default.
  $theme_handler = \Drupal::service('theme_handler');
  $theme_installer = \Drupal::service('theme_installer');

  // Install constructor_theme if not already installed.
  if (!$theme_handler->themeExists('constructor_theme')) {
    try {
      $theme_installer->install(['constructor_theme']);
    }
    catch (\Exception $e) {
      \Drupal::logger('constructor')->error('Failed to install constructor_theme: @message', ['@message' => $e->getMessage()]);
    }
  }

  // Set as default theme.
  $config = \Drupal::configFactory()->getEditable('system.theme');
  $config->set('default', 'constructor_theme')->save();

  // Also set as admin theme (optional - can use different admin theme).
  // $config->set('admin', 'constructor_theme')->save();

  $context['results'][] = 'theme';
}

/**
 * Batch operation: Configure theme settings (dark mode, color scheme).
 */
function constructor_batch_configure_theme_settings($constructor_settings, &$context) {
  $context['message'] = t('Configuring theme settings...');

  // Dark mode is always enabled by default.
  // Can be changed later in theme settings at /admin/appearance/settings/constructor_theme.
  $enable_dark_mode = TRUE;
  $color_scheme = 'blue';

  try {
    // Save theme settings to constructor_theme.settings config.
    $config = \Drupal::configFactory()->getEditable('constructor_theme.settings');
    $config->set('enable_dark_mode', $enable_dark_mode);
    $config->set('color_scheme', $color_scheme);
    $config->save();

    \Drupal::logger('constructor')->notice('Theme settings saved: dark_mode=@dark, color=@color', [
      '@dark' => $enable_dark_mode ? 'yes' : 'no',
      '@color' => $color_scheme,
    ]);
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->error('Failed to configure theme settings: @message', ['@message' => $e->getMessage()]);
  }

  $context['results'][] = 'theme_settings';
}

/**
 * Batch operation: Create main menu links.
 *
 * Note: Menu translations are handled separately by constructor_batch_translate_menus()
 * which runs after content_translation module is installed.
 */
function constructor_batch_create_main_menu($constructor_settings, &$context) {
  $context['message'] = t('Creating main menu links...');

  $content_type_modules = $constructor_settings['content_type_modules'] ?? [];

  try {
    $menu_link_storage = \Drupal::entityTypeManager()->getStorage('menu_link_content');

    // Create Home link.
    $home_link = $menu_link_storage->create([
      'title' => 'Home',
      'link' => ['uri' => 'internal:/'],
      'menu_name' => 'main',
      'weight' => -10,
      'expanded' => FALSE,
    ]);
    $home_link->save();
    \Drupal::logger('constructor')->notice('Created Home menu link.');

    // Create FAQ link if FAQ module is enabled.
    if (in_array('content_faq', $content_type_modules)) {
      $faq_link = $menu_link_storage->create([
        'title' => 'FAQ',
        'link' => ['uri' => 'internal:/faq'],
        'menu_name' => 'main',
        'weight' => 0,
        'expanded' => FALSE,
      ]);
      $faq_link->save();
      \Drupal::logger('constructor')->notice('Created FAQ menu link.');
    }

    // Create Team link if Team module is enabled.
    if (in_array('content_team', $content_type_modules)) {
      $team_link = $menu_link_storage->create([
        'title' => 'Team',
        'link' => ['uri' => 'internal:/team'],
        'menu_name' => 'main',
        'weight' => 5,
        'expanded' => FALSE,
      ]);
      $team_link->save();
      \Drupal::logger('constructor')->notice('Created Team menu link.');
    }

    // Create Services link if Services module is enabled.
    if (in_array('content_services', $content_type_modules)) {
      $services_link = $menu_link_storage->create([
        'title' => 'Services',
        'link' => ['uri' => 'internal:/services'],
        'menu_name' => 'main',
        'weight' => 6,
        'expanded' => FALSE,
      ]);
      $services_link->save();
      \Drupal::logger('constructor')->notice('Created Services menu link.');
    }

    // Create Articles link if Article module is enabled.
    if (in_array('content_article', $content_type_modules)) {
      $articles_link = $menu_link_storage->create([
        'title' => 'Articles',
        'link' => ['uri' => 'internal:/articles'],
        'menu_name' => 'main',
        'weight' => 7,
        'expanded' => FALSE,
      ]);
      $articles_link->save();
      \Drupal::logger('constructor')->notice('Created Articles menu link.');
    }

    // Create Products link if Commerce module is enabled.
    if (in_array('content_commerce', $content_type_modules)) {
      $products_link = $menu_link_storage->create([
        'title' => 'Products',
        'link' => ['uri' => 'internal:/products'],
        'menu_name' => 'main',
        'weight' => 8,
        'expanded' => FALSE,
      ]);
      $products_link->save();
      \Drupal::logger('constructor')->notice('Created Products menu link.');
    }

    // Note: Block placement skipped during installation to avoid entity type issues.
    \Drupal::logger('constructor')->notice('Block placement skipped during installation.');

    // Create footer menu links (translations handled separately).
    _constructor_create_footer_menu_simple($menu_link_storage);
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->error('Failed to create main menu links: @message', ['@message' => $e->getMessage()]);
  }

  $context['results'][] = 'main_menu';
}

/**
 * Create footer menu links with parent sections (simple version without translations).
 *
 * @param \Drupal\Core\Entity\EntityStorageInterface $menu_link_storage
 *   The menu link content storage.
 */
function _constructor_create_footer_menu_simple($menu_link_storage) {
  $footer_sections = [
    'Product' => [
      'weight' => 0,
      'items' => [
        'Features' => 0,
        'Integrations' => 1,
        'Pricing' => 2,
        'Changelog' => 3,
      ],
    ],
    'Company' => [
      'weight' => 1,
      'items' => [
        'About us' => 0,
        'Blog' => 1,
        'Careers' => 2,
        'Press' => 3,
      ],
    ],
    'Resources' => [
      'weight' => 2,
      'items' => [
        'Documentation' => 0,
        'Help Center' => 1,
        'Contact' => 2,
        'Status' => 3,
      ],
    ],
    'Legal' => [
      'weight' => 3,
      'items' => [
        'Privacy' => 0,
        'Terms' => 1,
        'Cookie Policy' => 2,
      ],
    ],
  ];

  foreach ($footer_sections as $section_title => $section_data) {
    // Create parent menu item.
    $parent_link = $menu_link_storage->create([
      'title' => $section_title,
      'link' => ['uri' => 'internal:/'],
      'menu_name' => 'footer',
      'weight' => $section_data['weight'],
      'expanded' => TRUE,
    ]);
    $parent_link->save();

    $parent_uuid = $parent_link->uuid();

    // Create child menu items.
    foreach ($section_data['items'] as $item_title => $item_weight) {
      $child_link = $menu_link_storage->create([
        'title' => $item_title,
        'link' => ['uri' => 'internal:/'],
        'menu_name' => 'footer',
        'parent' => 'menu_link_content:' . $parent_uuid,
        'weight' => $item_weight,
        'expanded' => FALSE,
      ]);
      $child_link->save();
    }
  }

  \Drupal::logger('constructor')->notice('Created footer menu links.');
}

/**
 * Enable translation for menu_link_content entity type.
 */
function _constructor_enable_menu_translation() {
  // Check if content_translation module is enabled.
  if (!\Drupal::moduleHandler()->moduleExists('content_translation')) {
    \Drupal::logger('constructor')->notice('Content translation module not enabled, skipping menu translation setup.');
    return FALSE;
  }

  // Check if language module is enabled.
  if (!\Drupal::moduleHandler()->moduleExists('language')) {
    \Drupal::logger('constructor')->notice('Language module not enabled, skipping menu translation setup.');
    return FALSE;
  }

  try {
    // Enable translation for menu_link_content.
    $config = \Drupal::configFactory()->getEditable('language.content_settings.menu_link_content.menu_link_content');
    $config->set('id', 'menu_link_content.menu_link_content');
    $config->set('langcode', 'en');
    $config->set('status', TRUE);
    $config->set('target_entity_type_id', 'menu_link_content');
    $config->set('target_bundle', 'menu_link_content');
    $config->set('default_langcode', 'site_default');
    $config->set('language_alterable', TRUE);
    $config->set('third_party_settings.content_translation.enabled', TRUE);
    $config->save();

    \Drupal::logger('constructor')->notice('Enabled translation for menu_link_content.');
    return TRUE;
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->error('Failed to enable menu translation: @message', ['@message' => $e->getMessage()]);
    return FALSE;
  }
}

/**
 * Batch operation: Translate menu items.
 */
function constructor_batch_translate_menus($constructor_settings, &$context) {
  $context['message'] = t('Translating menu items...');

  $languages = $constructor_settings['languages'] ?? [];
  $default_language = $languages['default_language'] ?? 'en';
  $additional_languages = $languages['additional_languages'] ?? [];

  // Filter additional languages.
  $additional_languages = array_filter($additional_languages, function ($langcode) use ($default_language) {
    return !empty($langcode) && $langcode !== $default_language;
  });

  if (empty($additional_languages)) {
    $context['results'][] = 'menu_translation_skipped';
    return;
  }

  // Enable menu translation first.
  _constructor_enable_menu_translation();

  // Main menu translations.
  $main_menu_translations = [
    'Home' => [
      'uk' => 'Головна',
      'de' => 'Startseite',
      'fr' => 'Accueil',
      'es' => 'Inicio',
      'pl' => 'Strona główna',
      'it' => 'Home',
      'pt' => 'Início',
      'nl' => 'Home',
      'cs' => 'Domů',
      'sk' => 'Domov',
    ],
    'FAQ' => [
      'uk' => 'Питання та відповіді',
      'de' => 'FAQ',
      'fr' => 'FAQ',
      'es' => 'Preguntas frecuentes',
      'pl' => 'FAQ',
      'it' => 'FAQ',
      'pt' => 'Perguntas frequentes',
      'nl' => 'FAQ',
      'cs' => 'Časté dotazy',
      'sk' => 'Časté otázky',
    ],
    'Team' => [
      'uk' => 'Команда',
      'de' => 'Team',
      'fr' => 'Équipe',
      'es' => 'Equipo',
      'pl' => 'Zespół',
      'it' => 'Team',
      'pt' => 'Equipe',
      'nl' => 'Team',
      'cs' => 'Tým',
      'sk' => 'Tím',
    ],
    'Services' => [
      'uk' => 'Послуги',
      'de' => 'Dienstleistungen',
      'fr' => 'Services',
      'es' => 'Servicios',
      'pl' => 'Usługi',
      'it' => 'Servizi',
      'pt' => 'Serviços',
      'nl' => 'Diensten',
      'cs' => 'Služby',
      'sk' => 'Služby',
    ],
    'Articles' => [
      'uk' => 'Статті',
      'de' => 'Artikel',
      'fr' => 'Articles',
      'es' => 'Artículos',
      'pl' => 'Artykuły',
      'it' => 'Articoli',
      'pt' => 'Artigos',
      'nl' => 'Artikelen',
      'cs' => 'Články',
      'sk' => 'Články',
    ],
    'Products' => [
      'uk' => 'Продукти',
      'de' => 'Produkte',
      'fr' => 'Produits',
      'es' => 'Productos',
      'pl' => 'Produkty',
      'it' => 'Prodotti',
      'pt' => 'Produtos',
      'nl' => 'Producten',
      'cs' => 'Produkty',
      'sk' => 'Produkty',
    ],
    'Gallery' => [
      'uk' => 'Галерея',
      'de' => 'Galerie',
      'fr' => 'Galerie',
      'es' => 'Galería',
      'pl' => 'Galeria',
      'it' => 'Galleria',
      'pt' => 'Galeria',
      'nl' => 'Galerij',
      'cs' => 'Galerie',
      'sk' => 'Galéria',
    ],
  ];

  // Footer menu translations.
  $footer_translations = [
    'Product' => ['uk' => 'Продукт', 'de' => 'Produkt', 'fr' => 'Produit', 'es' => 'Producto', 'pl' => 'Produkt'],
    'Features' => ['uk' => 'Можливості', 'de' => 'Funktionen', 'fr' => 'Fonctionnalités', 'es' => 'Características', 'pl' => 'Funkcje'],
    'Integrations' => ['uk' => 'Інтеграції', 'de' => 'Integrationen', 'fr' => 'Intégrations', 'es' => 'Integraciones', 'pl' => 'Integracje'],
    'Pricing' => ['uk' => 'Ціни', 'de' => 'Preise', 'fr' => 'Tarifs', 'es' => 'Precios', 'pl' => 'Cennik'],
    'Changelog' => ['uk' => 'Журнал змін', 'de' => 'Änderungsprotokoll', 'fr' => 'Journal des modifications', 'es' => 'Registro de cambios', 'pl' => 'Dziennik zmian'],
    'Company' => ['uk' => 'Компанія', 'de' => 'Unternehmen', 'fr' => 'Entreprise', 'es' => 'Empresa', 'pl' => 'Firma'],
    'About us' => ['uk' => 'Про нас', 'de' => 'Über uns', 'fr' => 'À propos', 'es' => 'Sobre nosotros', 'pl' => 'O nas'],
    'Blog' => ['uk' => 'Блог', 'de' => 'Blog', 'fr' => 'Blog', 'es' => 'Blog', 'pl' => 'Blog'],
    'Careers' => ['uk' => "Кар'єра", 'de' => 'Karriere', 'fr' => 'Carrières', 'es' => 'Carreras', 'pl' => 'Kariera'],
    'Press' => ['uk' => 'Преса', 'de' => 'Presse', 'fr' => 'Presse', 'es' => 'Prensa', 'pl' => 'Prasa'],
    'Resources' => ['uk' => 'Ресурси', 'de' => 'Ressourcen', 'fr' => 'Ressources', 'es' => 'Recursos', 'pl' => 'Zasoby'],
    'Documentation' => ['uk' => 'Документація', 'de' => 'Dokumentation', 'fr' => 'Documentation', 'es' => 'Documentación', 'pl' => 'Dokumentacja'],
    'Help Center' => ['uk' => 'Центр допомоги', 'de' => 'Hilfezentrum', 'fr' => "Centre d'aide", 'es' => 'Centro de ayuda', 'pl' => 'Centrum pomocy'],
    'Contact' => ['uk' => 'Контакти', 'de' => 'Kontakt', 'fr' => 'Contact', 'es' => 'Contacto', 'pl' => 'Kontakt'],
    'Status' => ['uk' => 'Статус', 'de' => 'Status', 'fr' => 'Statut', 'es' => 'Estado', 'pl' => 'Status'],
    'Legal' => ['uk' => 'Правова інформація', 'de' => 'Rechtliches', 'fr' => 'Mentions légales', 'es' => 'Legal', 'pl' => 'Prawne'],
    'Privacy' => ['uk' => 'Конфіденційність', 'de' => 'Datenschutz', 'fr' => 'Confidentialité', 'es' => 'Privacidad', 'pl' => 'Prywatność'],
    'Terms' => ['uk' => 'Умови', 'de' => 'Nutzungsbedingungen', 'fr' => 'Conditions', 'es' => 'Términos', 'pl' => 'Regulamin'],
    'Cookie Policy' => ['uk' => 'Політика cookies', 'de' => 'Cookie-Richtlinie', 'fr' => 'Politique de cookies', 'es' => 'Política de cookies', 'pl' => 'Polityka cookies'],
  ];

  // Merge all translations.
  $all_translations = array_merge($main_menu_translations, $footer_translations);

  try {
    $menu_link_storage = \Drupal::entityTypeManager()->getStorage('menu_link_content');

    // Load all menu items.
    $menu_links = $menu_link_storage->loadMultiple();

    foreach ($menu_links as $menu_link) {
      $title = $menu_link->getTitle();

      if (!isset($all_translations[$title])) {
        continue;
      }

      foreach ($additional_languages as $langcode) {
        if (!isset($all_translations[$title][$langcode])) {
          continue;
        }

        // Check if translation already exists.
        if ($menu_link->hasTranslation($langcode)) {
          continue;
        }

        try {
          $menu_link->addTranslation($langcode, [
            'title' => $all_translations[$title][$langcode],
          ]);
          $menu_link->save();
          \Drupal::logger('constructor')->notice('Translated menu "@title" to @lang: @translated', [
            '@title' => $title,
            '@lang' => $langcode,
            '@translated' => $all_translations[$title][$langcode],
          ]);
        }
        catch (\Exception $e) {
          \Drupal::logger('constructor')->warning('Failed to translate menu "@title" to @lang: @error', [
            '@title' => $title,
            '@lang' => $langcode,
            '@error' => $e->getMessage(),
          ]);
        }
      }
    }

    \Drupal::logger('constructor')->notice('Completed menu translations.');
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->error('Failed to translate menus: @message', ['@message' => $e->getMessage()]);
  }

  $context['results'][] = 'menu_translations';
}

/**
 * Create footer menu links with parent sections.
 *
 * @param \Drupal\Core\Entity\EntityStorageInterface $menu_link_storage
 *   The menu link content storage.
 * @param string $default_language
 *   The default language code.
 * @param array $additional_languages
 *   Array of additional language codes.
 */
function _constructor_create_footer_menu($menu_link_storage, $default_language = 'en', $additional_languages = []) {
  // Footer menu translations.
  $footer_translations = [
    'Product' => [
      'uk' => 'Продукт',
      'de' => 'Produkt',
      'fr' => 'Produit',
      'es' => 'Producto',
      'pl' => 'Produkt',
    ],
    'Features' => [
      'uk' => 'Можливості',
      'de' => 'Funktionen',
      'fr' => 'Fonctionnalités',
      'es' => 'Características',
      'pl' => 'Funkcje',
    ],
    'Integrations' => [
      'uk' => 'Інтеграції',
      'de' => 'Integrationen',
      'fr' => 'Intégrations',
      'es' => 'Integraciones',
      'pl' => 'Integracje',
    ],
    'Pricing' => [
      'uk' => 'Ціни',
      'de' => 'Preise',
      'fr' => 'Tarifs',
      'es' => 'Precios',
      'pl' => 'Cennik',
    ],
    'Changelog' => [
      'uk' => 'Журнал змін',
      'de' => 'Änderungsprotokoll',
      'fr' => 'Journal des modifications',
      'es' => 'Registro de cambios',
      'pl' => 'Dziennik zmian',
    ],
    'Company' => [
      'uk' => 'Компанія',
      'de' => 'Unternehmen',
      'fr' => 'Entreprise',
      'es' => 'Empresa',
      'pl' => 'Firma',
    ],
    'About us' => [
      'uk' => 'Про нас',
      'de' => 'Über uns',
      'fr' => 'À propos',
      'es' => 'Sobre nosotros',
      'pl' => 'O nas',
    ],
    'Blog' => [
      'uk' => 'Блог',
      'de' => 'Blog',
      'fr' => 'Blog',
      'es' => 'Blog',
      'pl' => 'Blog',
    ],
    'Careers' => [
      'uk' => "Кар'єра",
      'de' => 'Karriere',
      'fr' => 'Carrières',
      'es' => 'Carreras',
      'pl' => 'Kariera',
    ],
    'Press' => [
      'uk' => 'Преса',
      'de' => 'Presse',
      'fr' => 'Presse',
      'es' => 'Prensa',
      'pl' => 'Prasa',
    ],
    'Resources' => [
      'uk' => 'Ресурси',
      'de' => 'Ressourcen',
      'fr' => 'Ressources',
      'es' => 'Recursos',
      'pl' => 'Zasoby',
    ],
    'Documentation' => [
      'uk' => 'Документація',
      'de' => 'Dokumentation',
      'fr' => 'Documentation',
      'es' => 'Documentación',
      'pl' => 'Dokumentacja',
    ],
    'Help Center' => [
      'uk' => 'Центр допомоги',
      'de' => 'Hilfezentrum',
      'fr' => "Centre d'aide",
      'es' => 'Centro de ayuda',
      'pl' => 'Centrum pomocy',
    ],
    'Contact' => [
      'uk' => 'Контакти',
      'de' => 'Kontakt',
      'fr' => 'Contact',
      'es' => 'Contacto',
      'pl' => 'Kontakt',
    ],
    'Status' => [
      'uk' => 'Статус',
      'de' => 'Status',
      'fr' => 'Statut',
      'es' => 'Estado',
      'pl' => 'Status',
    ],
    'Legal' => [
      'uk' => 'Правова інформація',
      'de' => 'Rechtliches',
      'fr' => 'Mentions légales',
      'es' => 'Legal',
      'pl' => 'Prawne',
    ],
    'Privacy' => [
      'uk' => 'Конфіденційність',
      'de' => 'Datenschutz',
      'fr' => 'Confidentialité',
      'es' => 'Privacidad',
      'pl' => 'Prywatność',
    ],
    'Terms' => [
      'uk' => 'Умови',
      'de' => 'Nutzungsbedingungen',
      'fr' => 'Conditions',
      'es' => 'Términos',
      'pl' => 'Regulamin',
    ],
    'Cookie Policy' => [
      'uk' => 'Політика cookies',
      'de' => 'Cookie-Richtlinie',
      'fr' => 'Politique de cookies',
      'es' => 'Política de cookies',
      'pl' => 'Polityka cookies',
    ],
  ];

  $footer_sections = [
    'Product' => [
      'weight' => 0,
      'items' => [
        'Features' => 0,
        'Integrations' => 1,
        'Pricing' => 2,
        'Changelog' => 3,
      ],
    ],
    'Company' => [
      'weight' => 1,
      'items' => [
        'About us' => 0,
        'Blog' => 1,
        'Careers' => 2,
        'Press' => 3,
      ],
    ],
    'Resources' => [
      'weight' => 2,
      'items' => [
        'Documentation' => 0,
        'Help Center' => 1,
        'Contact' => 2,
        'Status' => 3,
      ],
    ],
    'Legal' => [
      'weight' => 3,
      'items' => [
        'Privacy' => 0,
        'Terms' => 1,
        'Cookie Policy' => 2,
      ],
    ],
  ];

  $has_translations = !empty($additional_languages) && \Drupal::moduleHandler()->moduleExists('content_translation');

  foreach ($footer_sections as $section_title => $section_data) {
    // Create parent menu item.
    $parent_link = $menu_link_storage->create([
      'title' => $section_title,
      'link' => ['uri' => 'internal:/'],
      'menu_name' => 'footer',
      'weight' => $section_data['weight'],
      'expanded' => TRUE,
      'langcode' => $default_language,
    ]);
    $parent_link->save();

    // Add translations for parent.
    if ($has_translations && isset($footer_translations[$section_title])) {
      foreach ($additional_languages as $langcode) {
        if (isset($footer_translations[$section_title][$langcode])) {
          try {
            $parent_link->addTranslation($langcode, [
              'title' => $footer_translations[$section_title][$langcode],
            ]);
            $parent_link->save();
          }
          catch (\Exception $e) {
            // Log but continue.
          }
        }
      }
    }

    $parent_uuid = $parent_link->uuid();

    // Create child menu items.
    foreach ($section_data['items'] as $item_title => $item_weight) {
      $child_link = $menu_link_storage->create([
        'title' => $item_title,
        'link' => ['uri' => 'internal:/'],
        'menu_name' => 'footer',
        'parent' => 'menu_link_content:' . $parent_uuid,
        'weight' => $item_weight,
        'expanded' => FALSE,
        'langcode' => $default_language,
      ]);
      $child_link->save();

      // Add translations for child.
      if ($has_translations && isset($footer_translations[$item_title])) {
        foreach ($additional_languages as $langcode) {
          if (isset($footer_translations[$item_title][$langcode])) {
            try {
              $child_link->addTranslation($langcode, [
                'title' => $footer_translations[$item_title][$langcode],
              ]);
              $child_link->save();
            }
            catch (\Exception $e) {
              // Log but continue.
            }
          }
        }
      }
    }
  }

  \Drupal::logger('constructor')->notice('Created footer menu links.');
}

/**
 * Batch operation: Create full_html text format and Content Editor role.
 */
function constructor_batch_create_full_html_format(&$context) {
  $context['message'] = t('Creating Full HTML text format and Content Editor role...');

  // Check if filter module is enabled.
  if (!\Drupal::moduleHandler()->moduleExists('filter')) {
    $context['results'][] = 'full_html_skipped';
    return;
  }

  $role_storage = \Drupal::entityTypeManager()->getStorage('user_role');

  try {
    // Create Content Editor role if it doesn't exist.
    $content_editor_role = $role_storage->load('content_editor');
    if (!$content_editor_role) {
      $content_editor_role = $role_storage->create([
        'id' => 'content_editor',
        'label' => 'Content Editor',
        'weight' => 2,
      ]);
      $content_editor_role->save();
      \Drupal::logger('constructor')->notice('Created Content Editor role.');
    }

    // Grant content permissions to Content Editor.
    $content_editor_permissions = [
      'access content',
      'view own unpublished content',
      'create article content',
      'edit own article content',
      'delete own article content',
      'create page content',
      'edit own page content',
      'delete own page content',
      'create faq content',
      'edit own faq content',
      'delete own faq content',
      'create service content',
      'edit own service content',
      'delete own service content',
      'access toolbar',
      'access administration pages',
      'view the administration theme',
    ];

    foreach ($content_editor_permissions as $permission) {
      $content_editor_role->grantPermission($permission);
    }
    $content_editor_role->save();

    // Create the full_html format if it doesn't exist.
    $format_storage = \Drupal::entityTypeManager()->getStorage('filter_format');
    if (!$format_storage->load('full_html')) {
      $format = $format_storage->create([
        'format' => 'full_html',
        'name' => 'Full HTML',
        'weight' => 1,
        'filters' => [
          'filter_htmlcorrector' => [
            'status' => TRUE,
            'weight' => 10,
          ],
        ],
      ]);
      $format->save();
      \Drupal::logger('constructor')->notice('Created Full HTML text format.');
    }

    // Grant Full HTML permission to administrator and content_editor roles.
    $admin_role = $role_storage->load('administrator');
    if ($admin_role) {
      $admin_role->grantPermission('use text format full_html');
      $admin_role->save();
    }

    // Grant Full HTML permission to content_editor.
    $content_editor_role->grantPermission('use text format full_html');
    $content_editor_role->save();

    \Drupal::logger('constructor')->notice('Granted Full HTML permission to Content Editor role.');
    $context['results'][] = 'full_html';
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->error('Failed to create Full HTML format: @message', ['@message' => $e->getMessage()]);
  }
}

/**
 * Batch operation: Configure development settings.
 */
function constructor_batch_configure_development_settings(&$context) {
  $context['message'] = t('Configuring development settings...');

  try {
    // Disable CSS/JS aggregation via config.
    $system_performance = \Drupal::configFactory()->getEditable('system.performance');
    $system_performance->set('css.preprocess', FALSE);
    $system_performance->set('js.preprocess', FALSE);
    $system_performance->set('cache.page.max_age', 0);
    $system_performance->save();
    \Drupal::logger('constructor')->notice('Disabled CSS/JS aggregation.');

    // Set development settings in key-value store (this makes the checkboxes checked in admin UI).
    $development_settings = \Drupal::keyValue('development_settings');

    // Enable "Do not cache markup" - disables render cache, dynamic page cache, and page cache.
    $development_settings->set('disable_rendered_output_cache_bins', TRUE);

    // Enable Twig development mode.
    $development_settings->set('twig_debug', TRUE);
    $development_settings->set('twig_cache_disable', TRUE);

    \Drupal::logger('constructor')->notice('Enabled development settings: disable_rendered_output_cache_bins, twig_debug, twig_cache_disable.');

    // Invalidate the container to apply settings.
    \Drupal::service('kernel')->invalidateContainer();

    $context['results'][] = 'development_settings';
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->error('Failed to configure development settings: @message', ['@message' => $e->getMessage()]);
  }
}

/**
 * Batch operation: Clear caches (simple version).
 */
function constructor_batch_clear_caches(&$context) {
  $context['message'] = t('Clearing caches...');

  try {
    \Drupal::cache()->deleteAll();
    \Drupal::cache('render')->deleteAll();
    \Drupal::cache('discovery')->deleteAll();
    \Drupal::cache('config')->deleteAll();
    \Drupal::logger('constructor')->notice('Caches cleared.');
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->warning('Cache clear had issues: @message', ['@message' => $e->getMessage()]);
  }

  $context['results'][] = 'caches';
}

/**
 * Batch operation: Final cache clear with full flush.
 *
 * This runs after all modules are installed and configured.
 * It ensures all entity types are properly registered before
 * AI content generation and block placement.
 */
function constructor_batch_final_cache_clear(&$context) {
  $context['message'] = t('Preparing for content generation...');

  try {
    // Full cache flush to ensure everything is properly registered.
    drupal_flush_all_caches();
    \Drupal::logger('constructor')->notice('Final cache flush completed.');
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->warning('Final cache flush had issues: @message', ['@message' => $e->getMessage()]);
    // Try simpler cache clear as fallback.
    try {
      \Drupal::cache()->deleteAll();
      \Drupal::cache('render')->deleteAll();
      \Drupal::cache('discovery')->deleteAll();
      \Drupal::cache('config')->deleteAll();
    }
    catch (\Exception $e2) {
      // Ignore secondary errors.
    }
  }

  $context['results'][] = 'final_cache_clear';
}

/**
 * Batch operation: Place all blocks.
 *
 * This runs at the very end after all entity types are registered.
 */
function constructor_batch_place_all_blocks($constructor_settings, &$context) {
  $context['message'] = t('Placing blocks...');

  $content_type_modules = $constructor_settings['content_type_modules'] ?? [];
  $languages = $constructor_settings['languages'] ?? [];

  try {
    $block_storage = \Drupal::entityTypeManager()->getStorage('block');

    // Place site branding block in header region.
    if (!$block_storage->load('constructor_theme_branding')) {
      $branding_block = $block_storage->create([
        'id' => 'constructor_theme_branding',
        'theme' => 'constructor_theme',
        'region' => 'header',
        'weight' => 0,
        'status' => TRUE,
        'plugin' => 'system_branding_block',
        'settings' => [
          'id' => 'system_branding_block',
          'label' => 'Site branding',
          'label_display' => '0',
          'provider' => 'system',
          'use_site_logo' => TRUE,
          'use_site_name' => TRUE,
          'use_site_slogan' => FALSE,
        ],
      ]);
      $branding_block->save();
      \Drupal::logger('constructor')->notice('Created site branding block.');
    }

    // Place main menu block in primary_menu region.
    if (!$block_storage->load('constructor_theme_main_menu')) {
      $main_menu_block = $block_storage->create([
        'id' => 'constructor_theme_main_menu',
        'theme' => 'constructor_theme',
        'region' => 'primary_menu',
        'weight' => 0,
        'status' => TRUE,
        'plugin' => 'system_menu_block:main',
        'settings' => [
          'id' => 'system_menu_block:main',
          'label' => 'Main navigation',
          'label_display' => '0',
          'provider' => 'system',
          'level' => 1,
          'depth' => 1,
          'expand_all_items' => FALSE,
        ],
      ]);
      $main_menu_block->save();
      \Drupal::logger('constructor')->notice('Created main menu block.');
    }

    // Place Hero block if constructor_hero module is installed.
    if (\Drupal::moduleHandler()->moduleExists('constructor_hero')) {
      if (!$block_storage->load('constructor_theme_hero_block')) {
        $hero_block = $block_storage->create([
          'id' => 'constructor_theme_hero_block',
          'theme' => 'constructor_theme',
          'region' => 'content',
          'weight' => -10,
          'status' => TRUE,
          'plugin' => 'hero_block',
          'settings' => [
            'id' => 'hero_block',
            'label' => 'Hero Block',
            'label_display' => '0',
            'provider' => 'constructor_hero',
            'title_prefix' => 'Put',
            'title_highlight' => 'people',
            'title_suffix' => 'first',
            'description' => 'Maximize your potential and simplify your work with our productivity-focused management platform.',
            'show_email_form' => TRUE,
            'email_placeholder' => 'Enter your email',
            'email_button_text' => 'Get Started',
            'show_stats' => TRUE,
            'stats_number' => '200+',
            'stats_label' => 'Happy Clients',
            'show_rating' => TRUE,
            'rating_score' => '5.0',
            'rating_count' => '8k',
            'rating_label' => 'Ratings',
            'image_url' => 'https://images.unsplash.com/photo-1559136555-9303baea8ebd?w=600&h=400&fit=crop&q=80',
            'image_alt' => 'Team collaboration',
            'show_floating_card' => TRUE,
            'floating_card_title' => 'Team Management',
            'floating_card_subtitle' => 'Efficient workflow',
            'floating_card_image' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=80&h=80&fit=crop&q=80',
          ],
          'visibility' => [
            'request_path' => [
              'id' => 'request_path',
              'negate' => FALSE,
              'pages' => "<front>\n/frontpage",
            ],
          ],
        ]);
        $hero_block->save();
        \Drupal::logger('constructor')->notice('Created Hero block.');
      }

      // Place What We Do block.
      if (!$block_storage->load('constructor_theme_what_we_do_block')) {
        $what_we_do_block = $block_storage->create([
          'id' => 'constructor_theme_what_we_do_block',
          'theme' => 'constructor_theme',
          'region' => 'content',
          'weight' => -5,
          'status' => TRUE,
          'plugin' => 'what_we_do_block',
          'settings' => [
            'id' => 'what_we_do_block',
            'label' => 'What We Do Block',
            'label_display' => '0',
            'provider' => 'constructor_hero',
            'badge_text' => 'Our Services',
            'title_prefix' => 'What We',
            'title_highlight' => 'Do',
            'title_suffix' => '',
            'description' => 'We provide innovative solutions that help businesses grow and thrive in today\'s competitive market.',
            'show_primary_button' => TRUE,
            'primary_button_text' => 'Our Services',
            'primary_button_url' => '/services',
            'show_secondary_link' => TRUE,
            'secondary_link_text' => 'Learn More',
            'secondary_link_url' => '/about',
          ],
          'visibility' => [
            'request_path' => [
              'id' => 'request_path',
              'negate' => FALSE,
              'pages' => "<front>\n/frontpage",
            ],
          ],
        ]);
        $what_we_do_block->save();
        \Drupal::logger('constructor')->notice('Created What We Do block.');
      }

      // Place Booking Modal block.
      if (!$block_storage->load('constructor_theme_booking_modal_block')) {
        $booking_modal_block = $block_storage->create([
          'id' => 'constructor_theme_booking_modal_block',
          'theme' => 'constructor_theme',
          'region' => 'content',
          'weight' => 100,
          'status' => TRUE,
          'plugin' => 'booking_modal_block',
          'settings' => [
            'id' => 'booking_modal_block',
            'label' => 'Booking Modal',
            'label_display' => '0',
            'provider' => 'constructor_hero',
            'modal_title' => 'Book a Consultation',
            'modal_subtitle' => 'Fill out the form below and we will get back to you shortly.',
            'show_header_button' => TRUE,
            'header_button_text' => 'Book Now',
            'show_name_field' => TRUE,
            'name_label' => 'Full Name',
            'name_placeholder' => 'Enter your full name',
            'name_required' => TRUE,
            'show_email_field' => TRUE,
            'email_label' => 'Email',
            'email_placeholder' => 'Enter your email address',
            'email_required' => TRUE,
            'show_company_field' => TRUE,
            'company_label' => 'Company',
            'company_placeholder' => 'Enter your company name',
            'company_required' => FALSE,
            'show_phone_field' => TRUE,
            'phone_label' => 'Phone',
            'phone_placeholder' => 'Enter your phone number',
            'phone_required' => FALSE,
            'show_message_field' => TRUE,
            'message_label' => 'Message',
            'message_placeholder' => 'Tell us about your project...',
            'message_required' => FALSE,
            'submit_button_text' => 'Submit Request',
          ],
          'visibility' => [],
        ]);
        $booking_modal_block->save();
        \Drupal::logger('constructor')->notice('Created Booking Modal block.');
      }
    }

    // Place FAQ block if content_faq module is installed.
    if (in_array('content_faq', $content_type_modules) &&
        \Drupal::moduleHandler()->moduleExists('content_faq')) {
      if (!$block_storage->load('constructor_theme_faq_block')) {
        $faq_block = $block_storage->create([
          'id' => 'constructor_theme_faq_block',
          'theme' => 'constructor_theme',
          'region' => 'content',
          'weight' => 10,
          'status' => TRUE,
          'plugin' => 'faq_block',
          'settings' => [
            'id' => 'faq_block',
            'label' => 'FAQ Block',
            'label_display' => '0',
            'provider' => 'content_faq',
            'title' => 'Frequently Asked Questions',
            'subtitle' => 'FAQs',
            'button_text' => 'Contact Us',
            'button_url' => '/contact',
            'limit' => 5,
          ],
          'visibility' => [
            'request_path' => [
              'id' => 'request_path',
              'negate' => FALSE,
              'pages' => "<front>\n/frontpage",
            ],
          ],
        ]);
        $faq_block->save();
        \Drupal::logger('constructor')->notice('Created FAQ block.');
      }
    }

    // Place Team block if content_team module is installed.
    if (in_array('content_team', $content_type_modules) &&
        \Drupal::moduleHandler()->moduleExists('content_team')) {
      if (!$block_storage->load('constructor_theme_team_block')) {
        $team_block = $block_storage->create([
          'id' => 'constructor_theme_team_block',
          'theme' => 'constructor_theme',
          'region' => 'content',
          'weight' => 5,
          'status' => TRUE,
          'plugin' => 'team_block',
          'settings' => [
            'id' => 'team_block',
            'label' => 'Team Block',
            'label_display' => '0',
            'provider' => 'content_team',
            'title' => 'Your Career,<br>Your Connection',
            'subtitle' => 'Join us',
            'description' => "We're always looking for passionate individuals to join our team.",
            'button_text' => 'View Open Positions',
            'button_url' => '/careers',
            'limit' => 10,
          ],
          'visibility' => [
            'request_path' => [
              'id' => 'request_path',
              'negate' => FALSE,
              'pages' => "<front>\n/frontpage",
            ],
          ],
        ]);
        $team_block->save();
        \Drupal::logger('constructor')->notice('Created Team block.');
      }
    }

    // Place Services block if content_services module is installed.
    if (in_array('content_services', $content_type_modules) &&
        \Drupal::moduleHandler()->moduleExists('content_services')) {
      if (!$block_storage->load('constructor_theme_services_block')) {
        $services_block = $block_storage->create([
          'id' => 'constructor_theme_services_block',
          'theme' => 'constructor_theme',
          'region' => 'content',
          'weight' => 3,
          'status' => TRUE,
          'plugin' => 'services_block',
          'settings' => [
            'id' => 'services_block',
            'label' => 'Services Block',
            'label_display' => '0',
            'provider' => 'content_services',
            'title' => 'Our Services',
            'subtitle' => 'We provide comprehensive solutions to help you achieve your goals',
            'button_text' => 'View All',
            'button_url' => '/services',
            'limit' => 6,
          ],
          'visibility' => [
            'request_path' => [
              'id' => 'request_path',
              'negate' => FALSE,
              'pages' => "<front>\n/frontpage",
            ],
          ],
        ]);
        $services_block->save();
        \Drupal::logger('constructor')->notice('Created Services block.');
      }

      // Also place Service Methods block.
      if (!$block_storage->load('constructor_theme_service_methods_block')) {
        $methods_block = $block_storage->create([
          'id' => 'constructor_theme_service_methods_block',
          'theme' => 'constructor_theme',
          'region' => 'content',
          'weight' => 4,
          'status' => TRUE,
          'plugin' => 'service_methods_block',
          'settings' => [
            'id' => 'service_methods_block',
            'label' => 'Our Methods',
            'label_display' => '0',
            'provider' => 'content_services',
            'title' => 'Our Methods',
            'subtitle' => 'We blend innovation with expertise to create sustainable solutions that work for your needs',
            'image_url' => 'https://images.unsplash.com/photo-1605000797499-95a51c5269ae?w=600&h=500&fit=crop&q=80',
          ],
          'visibility' => [
            'request_path' => [
              'id' => 'request_path',
              'negate' => FALSE,
              'pages' => "<front>\n/frontpage",
            ],
          ],
        ]);
        $methods_block->save();
        \Drupal::logger('constructor')->notice('Created Service Methods block.');
      }
    }

    // Place Articles block if content_article module is installed.
    if (in_array('content_article', $content_type_modules) &&
        \Drupal::moduleHandler()->moduleExists('content_article')) {
      if (!$block_storage->load('constructor_theme_articles_block')) {
        $articles_block = $block_storage->create([
          'id' => 'constructor_theme_articles_block',
          'theme' => 'constructor_theme',
          'region' => 'content',
          'weight' => 7,
          'status' => TRUE,
          'plugin' => 'articles_block',
          'settings' => [
            'id' => 'articles_block',
            'label' => 'Articles Block',
            'label_display' => '0',
            'provider' => 'content_article',
            'title' => 'Real Impact of Driving Electric',
            'subtitle' => 'Use Cases',
            'description' => 'From daily comfort to operational savings, these are the real advantages drivers experience using our smart electric vehicles.',
            'show_more_link' => TRUE,
            'more_link_text' => 'Learn More',
            'more_link_url' => '/articles',
            'limit' => 3,
          ],
          'visibility' => [
            'request_path' => [
              'id' => 'request_path',
              'negate' => FALSE,
              'pages' => "<front>\n/frontpage",
            ],
          ],
        ]);
        $articles_block->save();
        \Drupal::logger('constructor')->notice('Created Articles block.');
      }

      // Article Video Block.
      if (!$block_storage->load('constructor_theme_article_video_block')) {
        $article_video_block = $block_storage->create([
          'id' => 'constructor_theme_article_video_block',
          'theme' => 'constructor_theme',
          'region' => 'content',
          'weight' => 6,
          'status' => TRUE,
          'plugin' => 'article_video_block',
          'settings' => [
            'id' => 'article_video_block',
            'label' => 'Article Video Block',
            'label_display' => '0',
            'provider' => 'content_article',
            'title' => 'Cultivating success together',
            'subtitle' => 'Watch how we help achieve bigger and better results',
          ],
          'visibility' => [
            'request_path' => [
              'id' => 'request_path',
              'negate' => FALSE,
              'pages' => "<front>\n/frontpage",
            ],
          ],
        ]);
        $article_video_block->save();
        \Drupal::logger('constructor')->notice('Created Article Video block.');
      }
    }

    // Place Product blocks if content_commerce module is installed.
    if (in_array('content_commerce', $content_type_modules) &&
        \Drupal::moduleHandler()->moduleExists('content_commerce')) {
      // Product Carousel Block.
      if (!$block_storage->load('constructor_theme_product_carousel_block')) {
        $product_carousel_block = $block_storage->create([
          'id' => 'constructor_theme_product_carousel_block',
          'theme' => 'constructor_theme',
          'region' => 'content',
          'weight' => 8,
          'status' => TRUE,
          'plugin' => 'product_carousel_block',
          'settings' => [
            'id' => 'product_carousel_block',
            'label' => 'Product Carousel',
            'label_display' => '0',
            'provider' => 'content_commerce',
            'limit' => 6,
            'collection_title' => 'NEW COLLECTION',
            'collection_subtitle' => 'An iconic collection inspired by the past and reinvented for the future.',
            'collection_brands' => 'Premium,Classic,Modern',
            'collection_link_text' => 'Discover the collection',
            'collection_link_url' => '/products',
          ],
          'visibility' => [
            'request_path' => [
              'id' => 'request_path',
              'negate' => FALSE,
              'pages' => "<front>\n/frontpage",
            ],
          ],
        ]);
        $product_carousel_block->save();
        \Drupal::logger('constructor')->notice('Created Product Carousel block.');
      }

      // Product Sale Hero Block.
      if (!$block_storage->load('constructor_theme_product_sale_hero_block')) {
        $product_sale_hero_block = $block_storage->create([
          'id' => 'constructor_theme_product_sale_hero_block',
          'theme' => 'constructor_theme',
          'region' => 'content',
          'weight' => 9,
          'status' => TRUE,
          'plugin' => 'product_sale_hero_block',
          'settings' => [
            'id' => 'product_sale_hero_block',
            'label' => 'Product Sale Hero',
            'label_display' => '0',
            'provider' => 'content_commerce',
            'badge_text' => 'Hottest Sale',
            'cta_text' => 'Add to Cart',
            'product_id' => '',
          ],
          'visibility' => [
            'request_path' => [
              'id' => 'request_path',
              'negate' => FALSE,
              'pages' => "<front>\n/frontpage",
            ],
          ],
        ]);
        $product_sale_hero_block->save();
        \Drupal::logger('constructor')->notice('Created Product Sale Hero block.');
      }
    }

    // Place Contact Form block if contact_form module is installed.
    if (\Drupal::moduleHandler()->moduleExists('contact_form')) {
      if (!$block_storage->load('constructor_theme_contact_form_block')) {
        $contact_form_block = $block_storage->create([
          'id' => 'constructor_theme_contact_form_block',
          'theme' => 'constructor_theme',
          'region' => 'content',
          'weight' => 15,
          'status' => TRUE,
          'plugin' => 'contact_form_block',
          'settings' => [
            'id' => 'contact_form_block',
            'label' => 'Contact Form',
            'label_display' => '0',
            'provider' => 'contact_form',
            'section_title' => 'Contact us',
            'section_subtitle' => 'Get in Touch with Our Team',
            'section_description' => "We're here to answer your questions, discuss your project, and help you find the best solutions for your software needs. Reach out to us, and let's start building something great together.",
            'form_title' => "Let's Talk About Your Project",
            'contact_title' => 'Prefer a Direct Approach?',
            'phone' => '+1-234-567-8901',
            'email' => 'contact@example.com',
            'working_hours' => 'Monday to Friday, 9 AM - 6 PM (GMT)',
            'office_title' => 'Visit Our Office',
            'address' => '123 Main Street, Innovation City, 56789',
            'map_latitude' => '34.0507',
            'map_longitude' => '-118.2437',
            'directions_url' => 'https://www.google.com/maps',
            'success_title' => 'Thank You!',
            'success_message' => "Your message has been sent successfully. We'll get back to you within 24 hours.",
            'success_button_text' => 'Close',
          ],
          'visibility' => [
            'request_path' => [
              'id' => 'request_path',
              'negate' => FALSE,
              'pages' => "<front>\n/frontpage",
            ],
          ],
        ]);
        $contact_form_block->save();
        \Drupal::logger('constructor')->notice('Created Contact Form block.');
      }
    }

    // Place Gallery block if gallery module is installed.
    if (in_array('gallery', $content_type_modules) &&
        \Drupal::moduleHandler()->moduleExists('gallery')) {
      if (!$block_storage->load('constructor_theme_gallery_block')) {
        $gallery_block = $block_storage->create([
          'id' => 'constructor_theme_gallery_block',
          'theme' => 'constructor_theme',
          'region' => 'content',
          'weight' => 20,
          'status' => TRUE,
          'plugin' => 'gallery_block',
          'settings' => [
            'id' => 'gallery_block',
            'label' => 'Gallery',
            'label_display' => '0',
            'provider' => 'gallery',
            'label_text' => 'Our Gallery',
            'title' => "Explore the Gallery and\nSee the Future Unfold",
            'description' => 'From sleek details to real-life moments of interaction, this gallery captures the essence of what makes the future feel real today.',
            'button_text' => 'Explore the Gallery',
            'button_url' => '/gallery',
            'limit' => 8,
          ],
          'visibility' => [
            'request_path' => [
              'id' => 'request_path',
              'negate' => FALSE,
              'pages' => "<front>\n/frontpage",
            ],
          ],
        ]);
        $gallery_block->save();
        \Drupal::logger('constructor')->notice('Created Gallery block.');
      }
    }

    // Place Pricing Plans block if pricing_plans module is installed.
    if (in_array('pricing_plans', $content_type_modules) &&
        \Drupal::moduleHandler()->moduleExists('pricing_plans')) {
      if (!$block_storage->load('constructor_theme_pricing_block')) {
        $pricing_block = $block_storage->create([
          'id' => 'constructor_theme_pricing_block',
          'theme' => 'constructor_theme',
          'region' => 'content',
          'weight' => 18,
          'status' => TRUE,
          'plugin' => 'pricing_block',
          'settings' => [
            'id' => 'pricing_block',
            'label' => 'Pricing Plans',
            'label_display' => '0',
            'provider' => 'pricing_plans',
            'title' => 'Choose Your Plan',
            'description' => 'Affordable and adaptable pricing to suit your goals.',
            'annual_label' => 'Bill annually',
            'monthly_label' => 'Bill monthly',
            'discount_text' => '10% OFF',
            'success_title' => 'Thank You!',
            'success_message' => 'We will contact you shortly to discuss your plan.',
            'success_button_text' => 'Close',
          ],
          'visibility' => [
            'request_path' => [
              'id' => 'request_path',
              'negate' => FALSE,
              'pages' => "<front>\n/frontpage",
            ],
          ],
        ]);
        $pricing_block->save();
        \Drupal::logger('constructor')->notice('Created Pricing Plans block.');
      }
    }

    // Place language switcher block if language_switcher module is installed.
    if (\Drupal::moduleHandler()->moduleExists('language_switcher') &&
        !empty($languages['enable_multilingual'])) {
      if (!$block_storage->load('constructor_theme_language_switcher')) {
        $lang_block = $block_storage->create([
          'id' => 'constructor_theme_language_switcher',
          'theme' => 'constructor_theme',
          'region' => 'secondary_menu',
          'weight' => 10,
          'status' => TRUE,
          'plugin' => 'language_switcher_block',
          'settings' => [
            'id' => 'language_switcher_block',
            'label' => 'Language Switcher',
            'label_display' => '0',
            'provider' => 'language_switcher',
          ],
          'visibility' => [],
        ]);
        $lang_block->save();
        \Drupal::logger('constructor')->notice('Created language switcher block.');
      }
    }

    \Drupal::logger('constructor')->notice('All blocks placed successfully.');
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->error('Failed to place blocks: @message', ['@message' => $e->getMessage()]);
  }

  $context['results'][] = 'blocks_placed';
}

/**
 * Batch operation: Generate FAQ content with AI.
 */
function constructor_batch_generate_faq($constructor_settings, &$context) {
  $ai_settings = $constructor_settings['ai_settings'] ?? [];
  $site_basics = $constructor_settings['site_basics'] ?? [];
  $languages = $constructor_settings['languages'] ?? [];

  // Skip if no API key.
  if (empty($ai_settings['api_key'])) {
    $context['results'][] = 'faq_skipped_no_api';
    $context['finished'] = 1;
    return;
  }

  // Initialize on first call.
  if (!isset($context['sandbox']['initialized'])) {
    $site_description = $site_basics['site_description'] ?? '';
    $site_name = $site_basics['site_name'] ?? 'Website';
    $main_language = $languages['default_language'] ?? 'en';

    if (empty($site_description)) {
      $site_description = "A professional website for $site_name";
    }

    $context['message'] = t('Fetching FAQ content from AI...');

    try {
      // Phase 0: Get FAQ data from AI (one API call).
      $faqs = _constructor_generate_faq_with_ai($site_description, $main_language, $ai_settings);
      $context['sandbox']['faqs'] = $faqs ?: [];
      $context['sandbox']['main_language'] = $main_language;
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($context['sandbox']['faqs']);
      $context['sandbox']['initialized'] = TRUE;

      \Drupal::logger('constructor')->notice('FAQ AI returned @count items.', [
        '@count' => $context['sandbox']['max'],
      ]);
    }
    catch (\Exception $e) {
      \Drupal::logger('constructor')->error('FAQ AI error: @message', ['@message' => $e->getMessage()]);
      $context['sandbox']['faqs'] = [];
      $context['sandbox']['max'] = 0;
      $context['sandbox']['initialized'] = TRUE;
    }

    // If we got data, we're not finished yet.
    if ($context['sandbox']['max'] > 0) {
      $context['finished'] = 0;
      return;
    }
  }

  // Process one FAQ node per call.
  if ($context['sandbox']['progress'] < $context['sandbox']['max']) {
    $faq = $context['sandbox']['faqs'][$context['sandbox']['progress']];
    $main_language = $context['sandbox']['main_language'];

    $context['message'] = t('Creating FAQ @current of @total: @title', [
      '@current' => $context['sandbox']['progress'] + 1,
      '@total' => $context['sandbox']['max'],
      '@title' => mb_substr($faq['question'], 0, 50) . '...',
    ]);

    try {
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      $content_editor_uid = _constructor_get_content_editor_user();

      $node = $node_storage->create([
        'type' => 'faq',
        'title' => $faq['question'],
        'field_faq_answer' => [
          'value' => $faq['answer'],
          'format' => 'full_html',
        ],
        'status' => 1,
        'langcode' => $main_language,
        'uid' => $content_editor_uid,
      ]);
      $node->save();

      \Drupal::logger('constructor')->notice('Created FAQ node @num: @title', [
        '@num' => $context['sandbox']['progress'] + 1,
        '@title' => $faq['question'],
      ]);
    }
    catch (\Exception $e) {
      \Drupal::logger('constructor')->error('FAQ node creation error: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    $context['sandbox']['progress']++;
  }

  // Calculate progress.
  if ($context['sandbox']['max'] > 0) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
  else {
    $context['finished'] = 1;
  }

  if ($context['finished'] >= 1) {
    $context['results'][] = 'faq_generated';
    \Drupal::logger('constructor')->notice('Completed FAQ generation: @count nodes.', [
      '@count' => $context['sandbox']['progress'],
    ]);
  }
}

/**
 * Batch operation: Generate Team content with AI (progressive).
 */
function constructor_batch_generate_team($constructor_settings, &$context) {
  $ai_settings = $constructor_settings['ai_settings'] ?? [];
  $site_basics = $constructor_settings['site_basics'] ?? [];
  $languages = $constructor_settings['languages'] ?? [];

  // Skip if no API key.
  if (empty($ai_settings['api_key'])) {
    $context['results'][] = 'team_skipped_no_api';
    $context['finished'] = 1;
    return;
  }

  // Initialize on first call.
  if (!isset($context['sandbox']['initialized'])) {
    $site_description = $site_basics['site_description'] ?? '';
    $site_name = $site_basics['site_name'] ?? 'Website';
    $main_language = $languages['default_language'] ?? 'en';

    $context['message'] = t('Fetching Team content from AI...');

    try {
      // Get team data from AI.
      $team_members = _constructor_get_team_data_from_ai($site_name, $site_description, $main_language, $ai_settings);
      $context['sandbox']['team_members'] = $team_members ?: [];
      $context['sandbox']['main_language'] = $main_language;
      $context['sandbox']['site_description'] = $site_description;
      $context['sandbox']['api_key'] = $ai_settings['api_key'];
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($context['sandbox']['team_members']);
      $context['sandbox']['initialized'] = TRUE;

      \Drupal::logger('constructor')->notice('Team AI returned @count members.', [
        '@count' => $context['sandbox']['max'],
      ]);
    }
    catch (\Exception $e) {
      \Drupal::logger('constructor')->error('Team AI error: @message', ['@message' => $e->getMessage()]);
      $context['sandbox']['team_members'] = [];
      $context['sandbox']['max'] = 0;
      $context['sandbox']['initialized'] = TRUE;
    }

    if ($context['sandbox']['max'] > 0) {
      $context['finished'] = 0;
      return;
    }
  }

  // Process one team member per call.
  if ($context['sandbox']['progress'] < $context['sandbox']['max']) {
    $member = $context['sandbox']['team_members'][$context['sandbox']['progress']];
    $main_language = $context['sandbox']['main_language'];

    $context['message'] = t('Creating Team Member @current of @total: @name', [
      '@current' => $context['sandbox']['progress'] + 1,
      '@total' => $context['sandbox']['max'],
      '@name' => $member['name'] ?? 'Unknown',
    ]);

    try {
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      $content_editor_uid = _constructor_get_content_editor_user();

      $node_data = [
        'type' => 'team_member',
        'title' => $member['name'],
        'field_team_position' => $member['position'] ?? '',
        'field_team_bio' => [
          'value' => $member['bio'] ?? '',
          'format' => 'full_html',
        ],
        'status' => 1,
        'langcode' => $main_language,
        'uid' => $content_editor_uid,
      ];

      // Generate AI photo for team member.
      $api_key = $context['sandbox']['api_key'] ?? '';
      $site_description = $context['sandbox']['site_description'] ?? '';
      if (!empty($api_key)) {
        $photo = _constructor_generate_team_photo_with_ai(
          $member['name'],
          $member['position'] ?? '',
          $site_description,
          $api_key,
          $context['sandbox']['progress']
        );
        if ($photo) {
          $node_data['field_team_photo'] = [
            'target_id' => $photo->id(),
            'alt' => $member['name'],
          ];
        }
      }

      $node = $node_storage->create($node_data);
      $node->save();

      \Drupal::logger('constructor')->notice('Created Team Member @num: @name', [
        '@num' => $context['sandbox']['progress'] + 1,
        '@name' => $member['name'],
      ]);
    }
    catch (\Exception $e) {
      \Drupal::logger('constructor')->error('Team node creation error: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    $context['sandbox']['progress']++;
  }

  // Calculate progress.
  if ($context['sandbox']['max'] > 0) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
  else {
    $context['finished'] = 1;
  }

  if ($context['finished'] >= 1) {
    $context['results'][] = 'team_generated';
    \Drupal::logger('constructor')->notice('Completed Team generation: @count nodes.', [
      '@count' => $context['sandbox']['progress'],
    ]);
  }
}

/**
 * Get team member data from AI.
 */
function _constructor_get_team_data_from_ai($site_name, $site_description, $language, $ai_settings) {
  $api_key = $ai_settings['api_key'];
  $model = $ai_settings['text_model'] ?? 'gpt-4';

  $language_names = [
    'en' => 'English',
    'uk' => 'Ukrainian',
    'de' => 'German',
    'fr' => 'French',
    'es' => 'Spanish',
  ];
  $language_name = $language_names[$language] ?? 'English';

  $prompt = "Generate 6 team members for a company called \"$site_name\" with description: \"$site_description\".

Please respond in $language_name language.

Return the response as a JSON array with objects containing 'name', 'position', 'bio' keys. Example format:
[
  {\"name\": \"John Smith\", \"position\": \"CEO\", \"bio\": \"Brief biography (2-3 sentences)\"},
  ...
]

Include diverse roles like CEO, CTO, Designer, Developer, Marketing, etc.
Only return the JSON array, no other text.";

  $client = \Drupal::httpClient();
  $response = $client->post('https://api.openai.com/v1/chat/completions', [
    'headers' => [
      'Authorization' => 'Bearer ' . $api_key,
      'Content-Type' => 'application/json',
    ],
    'json' => [
      'model' => $model,
      'messages' => [
        ['role' => 'user', 'content' => $prompt],
      ],
      'temperature' => 0.7,
      'max_tokens' => 1500,
    ],
    'timeout' => 60,
  ]);

  $data = json_decode($response->getBody()->getContents(), TRUE);
  $content = $data['choices'][0]['message']['content'] ?? '';

  // Parse JSON from response.
  $content = trim($content);
  $content = preg_replace('/^```json\s*/', '', $content);
  $content = preg_replace('/\s*```$/', '', $content);

  return json_decode($content, TRUE) ?: [];
}

/**
 * Batch operation: Generate Services content with AI (progressive).
 */
function constructor_batch_generate_services($constructor_settings, &$context) {
  $ai_settings = $constructor_settings['ai_settings'] ?? [];
  $site_basics = $constructor_settings['site_basics'] ?? [];
  $languages = $constructor_settings['languages'] ?? [];

  // Skip if no API key.
  if (empty($ai_settings['api_key'])) {
    $context['results'][] = 'services_skipped_no_api';
    $context['finished'] = 1;
    return;
  }

  // Initialize on first call.
  if (!isset($context['sandbox']['initialized'])) {
    $site_description = $site_basics['site_description'] ?? '';
    $site_name = $site_basics['site_name'] ?? 'Website';
    $main_language = $languages['default_language'] ?? 'en';

    $context['message'] = t('Fetching Services content from AI...');

    try {
      $services = _constructor_get_services_data_from_ai($site_name, $site_description, $main_language, $ai_settings);
      $context['sandbox']['services'] = $services ?: [];
      $context['sandbox']['main_language'] = $main_language;
      $context['sandbox']['site_description'] = $site_description;
      $context['sandbox']['api_key'] = $ai_settings['api_key'];
      $context['sandbox']['enable_image_generation'] = $ai_settings['enable_image_generation'] ?? TRUE;
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($context['sandbox']['services']);
      $context['sandbox']['initialized'] = TRUE;

      \Drupal::logger('constructor')->notice('Services AI returned @count items.', [
        '@count' => $context['sandbox']['max'],
      ]);
    }
    catch (\Exception $e) {
      \Drupal::logger('constructor')->error('Services AI error: @message', ['@message' => $e->getMessage()]);
      $context['sandbox']['services'] = [];
      $context['sandbox']['max'] = 0;
      $context['sandbox']['initialized'] = TRUE;
    }

    if ($context['sandbox']['max'] > 0) {
      $context['finished'] = 0;
      return;
    }
  }

  // Process one service per call.
  if ($context['sandbox']['progress'] < $context['sandbox']['max']) {
    $service = $context['sandbox']['services'][$context['sandbox']['progress']];
    $main_language = $context['sandbox']['main_language'];

    $context['message'] = t('Creating Service @current of @total: @title', [
      '@current' => $context['sandbox']['progress'] + 1,
      '@total' => $context['sandbox']['max'],
      '@title' => $service['name'] ?? 'Unknown',
    ]);

    try {
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      $content_editor_uid = _constructor_get_content_editor_user();

      $node_data = [
        'type' => 'service',
        'title' => $service['name'],
        'field_service_description' => $service['description'] ?? '',
        'field_service_body' => [
          'value' => $service['body'] ?? '',
          'format' => 'full_html',
        ],
        'status' => 1,
        'langcode' => $main_language,
        'uid' => $content_editor_uid,
      ];

      // Generate AI image for service (only if image generation is enabled).
      $api_key = $context['sandbox']['api_key'] ?? '';
      $site_description = $context['sandbox']['site_description'] ?? '';
      $enable_image_generation = $context['sandbox']['enable_image_generation'] ?? TRUE;
      if (!empty($api_key) && $enable_image_generation) {
        $image = _constructor_generate_service_image_with_ai(
          $service['name'],
          $service['description'] ?? '',
          $site_description,
          $api_key,
          $context['sandbox']['progress']
        );
        if ($image) {
          $node_data['field_service_image'] = [
            'target_id' => $image->id(),
            'alt' => $service['name'],
          ];
        }
      }

      $node = $node_storage->create($node_data);
      $node->save();

      \Drupal::logger('constructor')->notice('Created Service @num: @name', [
        '@num' => $context['sandbox']['progress'] + 1,
        '@name' => $service['name'],
      ]);
    }
    catch (\Exception $e) {
      \Drupal::logger('constructor')->error('Service node creation error: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    $context['sandbox']['progress']++;
  }

  // Calculate progress.
  if ($context['sandbox']['max'] > 0) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
  else {
    $context['finished'] = 1;
  }

  if ($context['finished'] >= 1) {
    $context['results'][] = 'services_generated';
    \Drupal::logger('constructor')->notice('Completed Services generation: @count nodes.', [
      '@count' => $context['sandbox']['progress'],
    ]);
  }
}

/**
 * Get services data from AI.
 */
function _constructor_get_services_data_from_ai($site_name, $site_description, $language, $ai_settings) {
  $api_key = $ai_settings['api_key'];
  $model = $ai_settings['text_model'] ?? 'gpt-4';

  $language_names = [
    'en' => 'English',
    'uk' => 'Ukrainian',
    'de' => 'German',
    'fr' => 'French',
    'es' => 'Spanish',
  ];
  $language_name = $language_names[$language] ?? 'English';

  // Available Heroicons.
  $icons = ['star', 'heart', 'bolt', 'cube', 'chart-bar', 'cog', 'light-bulb', 'shield-check', 'globe', 'users'];

  $prompt = "Generate 6 services for a company called \"$site_name\" with description: \"$site_description\".

Please respond in $language_name language.

Return the response as a JSON array with objects containing 'name', 'description', 'body', 'icon' keys. Example format:
[
  {\"name\": \"Service Name\", \"description\": \"Short plain text description (1-2 sentences, no HTML)\", \"body\": \"<p>Detailed service description with 2-3 paragraphs.</p><p>Include benefits, features, and how it helps clients.</p>\", \"icon\": \"star\"},
  ...
]

The 'description' should be plain text (no HTML).
The 'body' should be HTML formatted with <p> tags for detailed content.
Available icons: " . implode(', ', $icons) . "
Only return the JSON array, no other text.";

  $client = \Drupal::httpClient();
  $response = $client->post('https://api.openai.com/v1/chat/completions', [
    'headers' => [
      'Authorization' => 'Bearer ' . $api_key,
      'Content-Type' => 'application/json',
    ],
    'json' => [
      'model' => $model,
      'messages' => [
        ['role' => 'user', 'content' => $prompt],
      ],
      'temperature' => 0.7,
      'max_tokens' => 1500,
    ],
    'timeout' => 60,
  ]);

  $data = json_decode($response->getBody()->getContents(), TRUE);
  $content = $data['choices'][0]['message']['content'] ?? '';

  $content = trim($content);
  $content = preg_replace('/^```json\s*/', '', $content);
  $content = preg_replace('/\s*```$/', '', $content);

  return json_decode($content, TRUE) ?: [];
}

/**
 * Batch operation: Generate Articles content with AI (progressive).
 */
function constructor_batch_generate_articles($constructor_settings, &$context) {
  $ai_settings = $constructor_settings['ai_settings'] ?? [];
  $site_basics = $constructor_settings['site_basics'] ?? [];
  $languages = $constructor_settings['languages'] ?? [];

  // Skip if no API key.
  if (empty($ai_settings['api_key'])) {
    $context['results'][] = 'articles_skipped_no_api';
    $context['finished'] = 1;
    return;
  }

  // Initialize on first call.
  if (!isset($context['sandbox']['initialized'])) {
    $site_description = $site_basics['site_description'] ?? '';
    $site_name = $site_basics['site_name'] ?? 'Website';
    $main_language = $languages['default_language'] ?? 'en';

    $context['message'] = t('Fetching Articles content from AI...');

    try {
      $articles = _constructor_get_articles_data_from_ai($site_name, $site_description, $main_language, $ai_settings);
      $context['sandbox']['articles'] = $articles ?: [];
      $context['sandbox']['main_language'] = $main_language;
      $context['sandbox']['site_description'] = $site_description;
      $context['sandbox']['api_key'] = $ai_settings['api_key'];
      $context['sandbox']['enable_image_generation'] = $ai_settings['enable_image_generation'] ?? TRUE;
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($context['sandbox']['articles']);
      $context['sandbox']['initialized'] = TRUE;

      \Drupal::logger('constructor')->notice('Articles AI returned @count items.', [
        '@count' => $context['sandbox']['max'],
      ]);
    }
    catch (\Exception $e) {
      \Drupal::logger('constructor')->error('Articles AI error: @message', ['@message' => $e->getMessage()]);
      $context['sandbox']['articles'] = [];
      $context['sandbox']['max'] = 0;
      $context['sandbox']['initialized'] = TRUE;
    }

    if ($context['sandbox']['max'] > 0) {
      $context['finished'] = 0;
      return;
    }
  }

  // Process one article per call.
  if ($context['sandbox']['progress'] < $context['sandbox']['max']) {
    $article = $context['sandbox']['articles'][$context['sandbox']['progress']];
    $main_language = $context['sandbox']['main_language'];

    $context['message'] = t('Creating Article @current of @total: @title', [
      '@current' => $context['sandbox']['progress'] + 1,
      '@total' => $context['sandbox']['max'],
      '@title' => mb_substr($article['title'] ?? 'Unknown', 0, 40) . '...',
    ]);

    try {
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      $content_editor_uid = _constructor_get_content_editor_user();

      $node_data = [
        'type' => 'article',
        'title' => $article['title'],
        'field_article_body' => [
          'value' => $article['body'] ?? '',
          'format' => 'full_html',
        ],
        'field_article_video_url' => $article['video_url'] ?? NULL,
        'status' => 1,
        'langcode' => $main_language,
        'uid' => $content_editor_uid,
      ];

      // Generate AI image for articles without video (only if image generation is enabled).
      $api_key = $context['sandbox']['api_key'] ?? '';
      $site_description = $context['sandbox']['site_description'] ?? '';
      $enable_image_generation = $context['sandbox']['enable_image_generation'] ?? TRUE;
      if (!empty($api_key) && empty($article['video_url']) && $enable_image_generation) {
        $image = _constructor_generate_article_image_with_ai(
          $article['title'],
          $site_description,
          $api_key,
          $context['sandbox']['progress']
        );
        if ($image) {
          $node_data['field_article_image'] = [
            'target_id' => $image->id(),
            'alt' => $article['title'],
          ];
        }
      }

      $node = $node_storage->create($node_data);
      $node->save();

      \Drupal::logger('constructor')->notice('Created Article @num: @title', [
        '@num' => $context['sandbox']['progress'] + 1,
        '@title' => $article['title'],
      ]);
    }
    catch (\Exception $e) {
      \Drupal::logger('constructor')->error('Article node creation error: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    $context['sandbox']['progress']++;
  }

  // Calculate progress.
  if ($context['sandbox']['max'] > 0) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
  else {
    $context['finished'] = 1;
  }

  if ($context['finished'] >= 1) {
    $context['results'][] = 'articles_generated';
    \Drupal::logger('constructor')->notice('Completed Articles generation: @count nodes.', [
      '@count' => $context['sandbox']['progress'],
    ]);
  }
}

/**
 * Get articles data from AI.
 */
function _constructor_get_articles_data_from_ai($site_name, $site_description, $language, $ai_settings) {
  $api_key = $ai_settings['api_key'];
  $model = $ai_settings['text_model'] ?? 'gpt-4';

  $language_names = [
    'en' => 'English',
    'uk' => 'Ukrainian',
    'de' => 'German',
    'fr' => 'French',
    'es' => 'Spanish',
  ];
  $language_name = $language_names[$language] ?? 'English';

  // Sample YouTube video URLs for variety.
  $video_urls = [
    'https://www.youtube.com/watch?v=bTqVqk7FSmY',
  ];

  $prompt = "Generate 4 blog articles for a company called \"$site_name\" with description: \"$site_description\".

Please respond in $language_name language.

Return the response as a JSON array with objects containing 'title', 'body', 'video_url' keys. Example format:
[
  {\"title\": \"Article Title\", \"body\": \"Article content (3-5 paragraphs)\", \"video_url\": null},
  ...
]

Make 1-2 articles have a YouTube video URL (use one of these: " . implode(', ', $video_urls) . ").
Others should have null for video_url.
Only return the JSON array, no other text.";

  $client = \Drupal::httpClient();
  $response = $client->post('https://api.openai.com/v1/chat/completions', [
    'headers' => [
      'Authorization' => 'Bearer ' . $api_key,
      'Content-Type' => 'application/json',
    ],
    'json' => [
      'model' => $model,
      'messages' => [
        ['role' => 'user', 'content' => $prompt],
      ],
      'temperature' => 0.7,
      'max_tokens' => 2500,
    ],
    'timeout' => 60,
  ]);

  $data = json_decode($response->getBody()->getContents(), TRUE);
  $content = $data['choices'][0]['message']['content'] ?? '';

  $content = trim($content);
  $content = preg_replace('/^```json\s*/', '', $content);
  $content = preg_replace('/\s*```$/', '', $content);

  return json_decode($content, TRUE) ?: [];
}

/**
 * Batch operation: Generate Products content with AI (progressive).
 *
 * Uses two phases:
 * - Phase 1: Get product data from AI and create categories
 * - Phase 2+: Create one product with AI image per request
 */
function constructor_batch_generate_products($constructor_settings, &$context) {
  $ai_settings = $constructor_settings['ai_settings'] ?? [];
  $site_basics = $constructor_settings['site_basics'] ?? [];
  $languages = $constructor_settings['languages'] ?? [];

  // Skip if no API key.
  if (empty($ai_settings['api_key'])) {
    $context['results'][] = 'products_skipped_no_api';
    $context['finished'] = 1;
    return;
  }

  // Initialize on first call.
  if (!isset($context['sandbox']['initialized'])) {
    $site_description = $site_basics['site_description'] ?? '';
    $site_name = $site_basics['site_name'] ?? 'Website';
    $main_language = $languages['default_language'] ?? 'en';

    $context['message'] = t('Fetching Products data from AI...');

    try {
      // Get product data from AI and create categories.
      $products = _constructor_get_products_data_from_ai($site_name, $site_description, $main_language, $ai_settings);
      $context['sandbox']['products'] = $products ?: [];
      $context['sandbox']['main_language'] = $main_language;
      $context['sandbox']['api_key'] = $ai_settings['api_key'];
      $context['sandbox']['enable_image_generation'] = $ai_settings['enable_image_generation'] ?? TRUE;
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($context['sandbox']['products']);
      $context['sandbox']['initialized'] = TRUE;

      \Drupal::logger('constructor')->notice('Products AI returned @count items.', [
        '@count' => $context['sandbox']['max'],
      ]);
    }
    catch (\Exception $e) {
      \Drupal::logger('constructor')->error('Products AI error: @message', ['@message' => $e->getMessage()]);
      $context['sandbox']['products'] = [];
      $context['sandbox']['max'] = 0;
      $context['sandbox']['initialized'] = TRUE;
    }

    if ($context['sandbox']['max'] > 0) {
      $context['finished'] = 0;
      return;
    }
  }

  // Process one product per call (including AI image generation).
  if ($context['sandbox']['progress'] < $context['sandbox']['max']) {
    $product = $context['sandbox']['products'][$context['sandbox']['progress']];
    $main_language = $context['sandbox']['main_language'];
    $api_key = $context['sandbox']['api_key'];
    $index = $context['sandbox']['progress'];

    $context['message'] = t('Creating Product @current of @total: @name (with AI image)', [
      '@current' => $index + 1,
      '@total' => $context['sandbox']['max'],
      '@name' => mb_substr($product['name'] ?? 'Unknown', 0, 30),
    ]);

    try {
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
      $content_editor_uid = _constructor_get_content_editor_user();

      // Find category term.
      $category_tid = NULL;
      $terms = $term_storage->loadByProperties([
        'vid' => 'product_category',
        'name' => $product['category'] ?? 'Accessories',
      ]);
      if ($terms) {
        $category_tid = reset($terms)->id();
      }

      // Generate product image with AI (only if image generation is enabled).
      $product_image = NULL;
      $enable_image_generation = $context['sandbox']['enable_image_generation'] ?? TRUE;
      if ($enable_image_generation) {
        $product_image = _constructor_generate_product_image_with_ai(
          $product['name'],
          $product['category'] ?? 'Product',
          $api_key,
          $index
        );
      }

      // Default colors and sizes.
      $default_colors = 'Black:#1f2937,Green:#059669,Gold:#fcd34d,Pink:#f9a8d4,Gray:#9ca3af';
      $default_sizes = 'Small,Medium,Large,XL,XXL';

      $node_values = [
        'type' => 'product',
        'title' => $product['name'],
        'field_product_body' => [
          'value' => '<p>' . ($product['description'] ?? '') . '</p>',
          'format' => 'full_html',
        ],
        'field_product_price' => $product['price'] ?? 99.99,
        'field_product_sale_price' => $product['sale_price'] ?? NULL,
        'field_product_category' => $category_tid,
        'field_product_sku' => $product['sku'] ?? 'SKU-' . ($index + 1),
        'field_product_in_stock' => TRUE,
        'field_product_featured' => $index < 2,
        'field_product_colors' => $default_colors,
        'field_product_sizes' => $default_sizes,
        'status' => 1,
        'langcode' => $main_language,
        'uid' => $content_editor_uid,
      ];

      // Add image if generated.
      if ($product_image) {
        $node_values['field_product_images'] = [
          [
            'target_id' => $product_image->id(),
            'alt' => $product['name'],
          ],
        ];
      }

      $node = $node_storage->create($node_values);
      $node->save();

      \Drupal::logger('constructor')->notice('Created Product @num: @name (image: @img)', [
        '@num' => $index + 1,
        '@name' => $product['name'],
        '@img' => $product_image ? 'yes' : 'no',
      ]);
    }
    catch (\Exception $e) {
      \Drupal::logger('constructor')->error('Product creation error: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    $context['sandbox']['progress']++;
  }

  // Calculate progress.
  if ($context['sandbox']['max'] > 0) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
  else {
    $context['finished'] = 1;
  }

  if ($context['finished'] >= 1) {
    $context['results'][] = 'products_generated';
    \Drupal::logger('constructor')->notice('Completed Products generation: @count nodes.', [
      '@count' => $context['sandbox']['progress'],
    ]);
  }
}

/**
 * Get products data from AI and create categories.
 */
function _constructor_get_products_data_from_ai($site_name, $site_description, $language, $ai_settings) {
  $api_key = $ai_settings['api_key'];
  $model = $ai_settings['text_model'] ?? 'gpt-4';

  $language_names = [
    'en' => 'English',
    'uk' => 'Ukrainian',
    'de' => 'German',
    'fr' => 'French',
    'es' => 'Spanish',
  ];
  $language_name = $language_names[$language] ?? 'English';

  // Default categories.
  $categories = ['T-Shirt', 'Shoes', 'Jackets', 'Accessories', 'Shorts', 'Hat'];

  // First, create product categories.
  _constructor_create_product_categories($categories, $language);

  $prompt = "Generate 6 products for a company with the following description: \"$site_description\".

Please respond in $language_name language.

Return the response as a JSON array with objects containing 'name', 'description', 'price', 'sale_price', 'category', 'sku' keys. Example format:
[
  {\"name\": \"Product Name\", \"description\": \"Product description (2-3 sentences)\", \"price\": 99.99, \"sale_price\": null, \"category\": \"T-Shirt\", \"sku\": \"SKU-001\"},
  ...
]

Categories must be one of: " . implode(', ', $categories) . "
Some products should have sale_price (lower than price), others should have null for sale_price.
Prices should be realistic (between 25 and 500).
Only return the JSON array, no other text.";

  $client = \Drupal::httpClient();
  $response = $client->post('https://api.openai.com/v1/chat/completions', [
    'headers' => [
      'Authorization' => 'Bearer ' . $api_key,
      'Content-Type' => 'application/json',
    ],
    'json' => [
      'model' => $model,
      'messages' => [
        ['role' => 'user', 'content' => $prompt],
      ],
      'temperature' => 0.7,
      'max_tokens' => 1500,
    ],
    'timeout' => 60,
  ]);

  $data = json_decode($response->getBody()->getContents(), TRUE);
  $content = $data['choices'][0]['message']['content'] ?? '';

  $content = trim($content);
  $content = preg_replace('/^```json\s*/', '', $content);
  $content = preg_replace('/\s*```$/', '', $content);

  return json_decode($content, TRUE) ?: [];
}

/**
 * Batch operation: Generate Gallery images with AI (progressive).
 *
 * Generates one image per HTTP request to avoid timeouts.
 */
function constructor_batch_generate_gallery($constructor_settings, &$context) {
  $ai_settings = $constructor_settings['ai_settings'] ?? [];
  $site_basics = $constructor_settings['site_basics'] ?? [];

  // Check if gallery module is installed.
  if (!\Drupal::moduleHandler()->moduleExists('gallery')) {
    $context['results'][] = 'gallery_skipped_no_module';
    $context['finished'] = 1;
    return;
  }

  // Check if image generation is enabled.
  $enable_image_generation = $ai_settings['enable_image_generation'] ?? TRUE;
  if (!$enable_image_generation) {
    $context['message'] = t('Image generation disabled, using fallback gallery images...');
    _constructor_generate_gallery_fallback();
    $context['results'][] = 'gallery_fallback_disabled';
    $context['finished'] = 1;
    return;
  }

  // Get site description for relevant image generation.
  $site_description = $site_basics['site_description'] ?? '';
  $site_name = $site_basics['site_name'] ?? 'Professional Company';

  // Image prompts for gallery - 10 images based on site description.
  $prompts = _constructor_get_gallery_prompts($site_description, $site_name);

  // Initialize on first call.
  if (!isset($context['sandbox']['initialized'])) {
    $api_key = $ai_settings['api_key'] ?? '';

    // If no API key, use fallback immediately.
    if (empty($api_key)) {
      $context['message'] = t('No API key, using fallback gallery images...');
      _constructor_generate_gallery_fallback();
      $context['results'][] = 'gallery_fallback';
      $context['finished'] = 1;
      return;
    }

    $context['sandbox']['api_key'] = $api_key;
    $context['sandbox']['prompts'] = $prompts;
    $context['sandbox']['images'] = [];
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['max'] = count($prompts);
    $context['sandbox']['initialized'] = TRUE;

    $context['message'] = t('Starting gallery image generation (0/@total)...', [
      '@total' => $context['sandbox']['max'],
    ]);
    $context['finished'] = 0;
    return;
  }

  // Generate one image per call.
  if ($context['sandbox']['progress'] < $context['sandbox']['max']) {
    $index = $context['sandbox']['progress'];
    $prompt = $context['sandbox']['prompts'][$index];
    $api_key = $context['sandbox']['api_key'];

    $context['message'] = t('Generating gallery image @current of @total...', [
      '@current' => $index + 1,
      '@total' => $context['sandbox']['max'],
    ]);

    try {
      $image = _constructor_generate_single_gallery_image($prompt, $index, $api_key);
      if ($image) {
        $context['sandbox']['images'][] = $image;
        \Drupal::logger('constructor')->notice('Generated gallery image @num.', ['@num' => $index + 1]);
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('constructor')->warning('Gallery image @num failed: @message', [
        '@num' => $index + 1,
        '@message' => $e->getMessage(),
      ]);
    }

    $context['sandbox']['progress']++;
  }

  // Calculate progress.
  $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];

  // When all done, save images to config.
  if ($context['finished'] >= 1) {
    $images = $context['sandbox']['images'];

    if (!empty($images)) {
      \Drupal::configFactory()
        ->getEditable('gallery.images')
        ->set('images', $images)
        ->save();
      \Drupal::logger('constructor')->notice('Saved @count gallery images.', ['@count' => count($images)]);
    }
    else {
      // Fall back if no images generated.
      \Drupal::logger('constructor')->notice('No AI images generated, using fallback.');
      _constructor_generate_gallery_fallback();
    }

    $context['results'][] = 'gallery_generated';
  }
}

/**
 * Generate a single gallery image using DALL-E.
 */
function _constructor_generate_single_gallery_image($prompt, $index, $api_key) {
  $client = \Drupal::httpClient();

  // Generate image using DALL-E.
  $response = $client->post('https://api.openai.com/v1/images/generations', [
    'headers' => [
      'Authorization' => 'Bearer ' . $api_key,
      'Content-Type' => 'application/json',
    ],
    'json' => [
      'model' => 'dall-e-3',
      'prompt' => $prompt,
      'n' => 1,
      'size' => '1024x1024',
      'quality' => 'standard',
    ],
    'timeout' => 120,
  ]);

  $data = json_decode($response->getBody()->getContents(), TRUE);
  $image_url = $data['data'][0]['url'] ?? NULL;

  if (!$image_url) {
    return NULL;
  }

  // Download the image.
  $image_response = $client->get($image_url, ['timeout' => 60]);
  $image_data = (string) $image_response->getBody();

  if (empty($image_data)) {
    return NULL;
  }

  // Prepare directory.
  $directory = 'public://gallery';
  $file_system = \Drupal::service('file_system');
  $file_system->prepareDirectory($directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY | \Drupal\Core\File\FileSystemInterface::MODIFY_PERMISSIONS);

  $filename = 'gallery-ai-' . ($index + 1) . '-' . time() . '.png';
  $destination = $directory . '/' . $filename;

  // Save file.
  $file_repository = \Drupal::service('file.repository');
  $file = $file_repository->writeData($image_data, $destination, \Drupal\Core\File\FileSystemInterface::EXISTS_RENAME);

  if (!$file) {
    return NULL;
  }

  $file->setPermanent();
  $file->save();

  return [
    'id' => 'gallery_ai_' . ($index + 1),
    'url' => \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri()),
    'thumb' => \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri()),
    'alt' => 'Gallery image ' . ($index + 1),
    'width' => 1024,
    'height' => 1024,
    'fid' => $file->id(),
    'created' => time(),
  ];
}

/**
 * Get gallery image prompts based on site description.
 *
 * @param string $site_description
 *   The site description.
 * @param string $site_name
 *   The site name.
 *
 * @return array
 *   Array of image prompts for gallery generation.
 */
function _constructor_get_gallery_prompts($site_description, $site_name) {
  // Photorealistic prompt suffix for all gallery images.
  $photo_suffix = ', photorealistic, ultra-realistic, high-resolution, sharp details, realistic lighting';

  // If we have a site description, generate contextual prompts.
  if (!empty($site_description)) {
    return [
      "$site_name business environment, office space with real people working, natural lighting" . $photo_suffix,
      "Team collaboration meeting related to $site_description, candid shot of professionals discussing" . $photo_suffix,
      "Product or service for $site_name, realistic studio lighting" . $photo_suffix,
      "Workspace interior for $site_description, natural window lighting, real office environment" . $photo_suffix,
      "Business technology in use at $site_name, person using laptop or tablet" . $photo_suffix,
      "Customer service interaction related to $site_description, warm handshake or consultation" . $photo_suffix,
      "Behind the scenes at $site_name workplace, authentic candid moment of employees" . $photo_suffix,
      "Creative work process for $site_description, hands working on project, detailed close-up" . $photo_suffix,
      "Business success celebration at $site_name, team achievement moment" . $photo_suffix,
      "Brand environment and culture of $site_name, interior with natural lighting" . $photo_suffix,
    ];
  }

  // Default prompts if no site description is available.
  return [
    'Office interior with large windows and natural light, real workspace environment' . $photo_suffix,
    'Creative team brainstorming session in meeting room, candid shot of real professionals' . $photo_suffix,
    'Product on display with soft natural lighting' . $photo_suffix,
    'Glass office building exterior, geometric facade' . $photo_suffix,
    'Business professional using laptop at desk, natural lighting' . $photo_suffix,
    'Serene nature landscape with mountains and lake at golden hour sunset' . $photo_suffix,
    'Kitchen interior design with marble countertops, natural lighting' . $photo_suffix,
    'Creative professional working in art studio, warm natural window light' . $photo_suffix,
    'Urban cityscape at blue hour with glowing building lights and reflections' . $photo_suffix,
    'Product display on clean white background, realistic studio lighting' . $photo_suffix,
  ];
}

/**
 * Generate gallery images using AI (DALL-E).
 *
 * @deprecated Use constructor_batch_generate_gallery() instead for progressive generation.
 */
function _constructor_generate_gallery_with_ai($site_name, $site_description, $ai_settings) {
  $api_key = $ai_settings['api_key'] ?? '';

  // If no API key, use fallback.
  if (empty($api_key)) {
    \Drupal::logger('constructor')->notice('Gallery AI: No API key, using fallback images.');
    _constructor_generate_gallery_fallback();
    return;
  }

  // Image prompts for gallery - 10 images.
  $photo_suffix = ', photorealistic, ultra-realistic, high-resolution, sharp details, realistic lighting';
  $prompts = [
    'Office interior with large windows and natural light, real workspace environment' . $photo_suffix,
    'Creative team brainstorming session in meeting room, candid shot of real professionals' . $photo_suffix,
    'Product on display with soft natural lighting' . $photo_suffix,
    'Glass office building exterior, geometric facade' . $photo_suffix,
    'Business professional using laptop at desk, natural lighting' . $photo_suffix,
    'Serene nature landscape with mountains and lake at golden hour sunset' . $photo_suffix,
    'Kitchen interior design with marble countertops, natural lighting' . $photo_suffix,
    'Creative professional working in art studio, warm natural window light' . $photo_suffix,
    'Urban cityscape at blue hour with glowing building lights and reflections' . $photo_suffix,
    'Product display on clean white background, realistic studio lighting' . $photo_suffix,
  ];

  $gallery_images = [];
  $file_system = \Drupal::service('file_system');
  $directory = 'public://gallery';
  $file_system->prepareDirectory($directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY | \Drupal\Core\File\FileSystemInterface::MODIFY_PERMISSIONS);

  $client = \Drupal::httpClient();
  $generated_count = 0;

  foreach ($prompts as $index => $prompt) {
    try {
      // Generate image using DALL-E.
      $response = $client->post('https://api.openai.com/v1/images/generations', [
        'headers' => [
          'Authorization' => 'Bearer ' . $api_key,
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'model' => 'dall-e-3',
          'prompt' => $prompt,
          'n' => 1,
          'size' => '1024x1024',
          'quality' => 'standard',
        ],
        'timeout' => 120,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      $image_url = $data['data'][0]['url'] ?? NULL;

      if ($image_url) {
        // Download and save the image.
        $image_response = $client->get($image_url, ['timeout' => 60]);
        $image_data = (string) $image_response->getBody();

        if (!empty($image_data)) {
          $filename = 'gallery-ai-' . ($index + 1) . '-' . time() . '.png';
          $destination = $directory . '/' . $filename;

          $file_repository = \Drupal::service('file.repository');
          $file = $file_repository->writeData($image_data, $destination, \Drupal\Core\File\FileSystemInterface::EXISTS_RENAME);

          if ($file) {
            $file->setPermanent();
            $file->save();

            $gallery_images[] = [
              'id' => 'gallery_ai_' . ($index + 1),
              'url' => \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri()),
              'thumb' => \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri()),
              'alt' => 'Gallery image ' . ($index + 1),
              'width' => 1024,
              'height' => 1024,
              'fid' => $file->id(),
              'created' => time(),
            ];
            $generated_count++;

            \Drupal::logger('constructor')->notice('Generated gallery image @num with AI.', ['@num' => $index + 1]);
          }
        }
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('constructor')->warning('Failed to generate gallery image @num: @message', [
        '@num' => $index + 1,
        '@message' => $e->getMessage(),
      ]);
    }
  }

  // If we generated at least some images, save them.
  if (!empty($gallery_images)) {
    \Drupal::configFactory()
      ->getEditable('gallery.images')
      ->set('images', $gallery_images)
      ->save();

    \Drupal::logger('constructor')->notice('Generated @count gallery images with AI.', ['@count' => $generated_count]);
  }
  else {
    // Fall back if no images generated.
    _constructor_generate_gallery_fallback();
  }
}

/**
 * Fallback gallery images using Unsplash.
 */
function _constructor_generate_gallery_fallback() {
  $images = [
    [
      'url' => 'https://images.unsplash.com/photo-1518780664697-55e3ad937233?w=1200&h=1200&fit=crop&q=90',
      'thumb' => 'https://images.unsplash.com/photo-1518780664697-55e3ad937233?w=400&h=400&fit=crop&q=80',
      'alt' => 'House by the lake',
      'width' => 1200,
      'height' => 1200,
    ],
    [
      'url' => 'https://images.unsplash.com/photo-1511818966892-d7d671e672a2?w=1200&h=1200&fit=crop&q=90',
      'thumb' => 'https://images.unsplash.com/photo-1511818966892-d7d671e672a2?w=400&h=400&fit=crop&q=80',
      'alt' => 'Modern architecture',
      'width' => 1200,
      'height' => 1200,
    ],
    [
      'url' => 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1200&h=1200&fit=crop&q=90',
      'thumb' => 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=400&h=400&fit=crop&q=80',
      'alt' => 'Spiral building in flowers',
      'width' => 1200,
      'height' => 1200,
    ],
    [
      'url' => 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=1200&h=1200&fit=crop&q=90',
      'thumb' => 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=400&h=400&fit=crop&q=80',
      'alt' => 'Interior with ocean view',
      'width' => 1200,
      'height' => 1200,
    ],
    [
      'url' => 'https://images.unsplash.com/photo-1469474968028-56623f02e42e?w=1200&h=900&fit=crop&q=90',
      'thumb' => 'https://images.unsplash.com/photo-1469474968028-56623f02e42e?w=400&h=300&fit=crop&q=80',
      'alt' => 'Mountain landscape',
      'width' => 1200,
      'height' => 900,
    ],
    [
      'url' => 'https://images.unsplash.com/photo-1487958449943-2429e8be8625?w=1200&h=900&fit=crop&q=90',
      'thumb' => 'https://images.unsplash.com/photo-1487958449943-2429e8be8625?w=400&h=300&fit=crop&q=80',
      'alt' => 'Futuristic architecture',
      'width' => 1200,
      'height' => 900,
    ],
    [
      'url' => 'https://images.unsplash.com/photo-1518005020951-eccb494ad742?w=1200&h=900&fit=crop&q=90',
      'thumb' => 'https://images.unsplash.com/photo-1518005020951-eccb494ad742?w=400&h=300&fit=crop&q=80',
      'alt' => 'Light corridor',
      'width' => 1200,
      'height' => 900,
    ],
    [
      'url' => 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=1200&h=900&fit=crop&q=90',
      'thumb' => 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=400&h=300&fit=crop&q=80',
      'alt' => 'Modern house exterior',
      'width' => 1200,
      'height' => 900,
    ],
    [
      'url' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=1200&h=1200&fit=crop&q=90',
      'thumb' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&h=400&fit=crop&q=80',
      'alt' => 'Creative workspace',
      'width' => 1200,
      'height' => 1200,
    ],
    [
      'url' => 'https://images.unsplash.com/photo-1531973576160-7125cd663d86?w=1200&h=1200&fit=crop&q=90',
      'thumb' => 'https://images.unsplash.com/photo-1531973576160-7125cd663d86?w=400&h=400&fit=crop&q=80',
      'alt' => 'Team meeting',
      'width' => 1200,
      'height' => 1200,
    ],
  ];

  $gallery_images = [];
  foreach ($images as $index => $image) {
    $gallery_images[] = [
      'id' => 'gallery_' . ($index + 1),
      'url' => $image['url'],
      'thumb' => $image['thumb'],
      'alt' => $image['alt'],
      'width' => $image['width'],
      'height' => $image['height'],
      'fid' => NULL,
      'created' => time(),
    ];
  }

  \Drupal::configFactory()
    ->getEditable('gallery.images')
    ->set('images', $gallery_images)
    ->save();

  \Drupal::logger('constructor')->notice('Created 10 fallback gallery images from Unsplash.');
}

/**
 * Batch operation: Generate AI content WITHOUT translation.
 *
 * @deprecated Use the individual batch functions instead.
 * This creates FAQ and Team nodes but skips translation.
 * Translation is done in a separate batch after content_translation is installed.
 */
function constructor_batch_generate_ai_content_no_translation($constructor_settings, &$context) {
  $context['message'] = t('Generating AI content...');

  $content_type_modules = $constructor_settings['content_type_modules'] ?? [];
  $ai_settings = $constructor_settings['ai_settings'] ?? [];
  $site_basics = $constructor_settings['site_basics'] ?? [];
  $languages = $constructor_settings['languages'] ?? [];

  // Check if FAQ module is enabled.
  if (!in_array('content_faq', $content_type_modules)) {
    \Drupal::logger('constructor')->notice('AI content skipped: FAQ module not selected.');
    $context['results'][] = 'ai_content_skipped';
    return;
  }

  // Check if AI has API key configured.
  if (empty($ai_settings['api_key'])) {
    \Drupal::logger('constructor')->notice('AI content skipped: No API key configured.');
    $context['results'][] = 'ai_content_no_api';
    return;
  }

  // Get site description.
  $site_description = $site_basics['site_description'] ?? '';
  $site_name = $site_basics['site_name'] ?? 'Website';

  if (empty($site_description)) {
    $site_description = "A professional website for $site_name";
  }

  // Get main language.
  $main_language = $languages['default_language'] ?? 'en';

  \Drupal::logger('constructor')->notice('Starting AI FAQ generation for site: @desc, language: @lang (translations deferred)', [
    '@desc' => $site_description,
    '@lang' => $main_language,
  ]);

  try {
    // Generate FAQ content using OpenAI.
    $faqs = _constructor_generate_faq_with_ai($site_description, $main_language, $ai_settings);

    \Drupal::logger('constructor')->notice('AI returned @count FAQs.', ['@count' => count($faqs)]);

    if (!empty($faqs)) {
      // Create FAQ nodes WITHOUT translation.
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');

      // Get or create a Content Editor user for AI-generated content.
      $content_editor_uid = _constructor_get_content_editor_user();

      foreach ($faqs as $index => $faq) {
        \Drupal::logger('constructor')->notice('Creating FAQ @num: @question', [
          '@num' => $index + 1,
          '@question' => $faq['question'],
        ]);

        $node = $node_storage->create([
          'type' => 'faq',
          'title' => $faq['question'],
          'field_faq_answer' => [
            'value' => $faq['answer'],
            'format' => 'full_html',
          ],
          'status' => 1,
          'langcode' => $main_language,
          'uid' => $content_editor_uid,
        ]);
        $node->save();
      }

      \Drupal::logger('constructor')->notice('Generated @count FAQ nodes (translations deferred).', ['@count' => count($faqs)]);
    }
    else {
      \Drupal::logger('constructor')->warning('AI returned empty FAQ list.');
    }
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->error('Failed to generate AI content: @message', ['@message' => $e->getMessage()]);
  }

  // Generate Team Members if content_team module is enabled.
  if (in_array('content_team', $content_type_modules)) {
    _constructor_generate_team_members_with_ai($site_name, $site_description, $main_language, $ai_settings, $content_editor_uid ?? 1);
  }

  // Generate Services if content_services module is enabled.
  if (in_array('content_services', $content_type_modules)) {
    _constructor_generate_services_with_ai($site_name, $site_description, $main_language, $ai_settings, $content_editor_uid ?? 1);
  }

  // Generate Articles if content_article module is enabled.
  if (in_array('content_article', $content_type_modules)) {
    _constructor_generate_articles_with_ai($site_name, $site_description, $main_language, $ai_settings, $content_editor_uid ?? 1);
  }

  // Generate Products if content_commerce module is enabled.
  if (in_array('content_commerce', $content_type_modules)) {
    _constructor_generate_products_with_ai($site_name, $site_description, $main_language, $ai_settings, $content_editor_uid ?? 1);
  }

  $context['results'][] = 'ai_content_no_translation';
}

/**
 * Batch operation: Install content_translation module.
 *
 * This runs AFTER all content is created to avoid entityBundleInfoAlter errors.
 */
function constructor_batch_install_content_translation($constructor_settings, &$context) {
  $context['message'] = t('Installing content translation module...');

  $language_settings = $constructor_settings['languages'] ?? [];
  $additional_languages = $language_settings['additional_languages'] ?? [];
  $default_language = $language_settings['default_language'] ?? 'en';

  // Filter additional_languages to only valid langcodes.
  $additional_languages = array_filter($additional_languages, function ($langcode) use ($default_language) {
    return !empty($langcode) && $langcode !== $default_language;
  });

  // Only install if we have additional languages or content translation was explicitly requested.
  $needs_content_translation = !empty($additional_languages) || !empty($language_settings['enable_content_translation']);

  if (!$needs_content_translation) {
    \Drupal::logger('constructor')->notice('Content translation module not needed - skipping.');
    $context['results'][] = 'content_translation_skipped';
    return;
  }

  // Check if already installed.
  if (\Drupal::moduleHandler()->moduleExists('content_translation')) {
    \Drupal::logger('constructor')->notice('Content translation module already installed.');
    $context['results'][] = 'content_translation_exists';
    return;
  }

  try {
    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
    $module_installer = \Drupal::service('module_installer');
    $module_installer->install(['content_translation']);

    \Drupal::logger('constructor')->notice('Content translation module installed successfully.');

    // Rebuild container to register the module.
    \Drupal::service('kernel')->rebuildContainer();

    // Clear caches.
    drupal_flush_all_caches();

    $context['results'][] = 'content_translation_installed';
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->error('Failed to install content_translation module: @message', ['@message' => $e->getMessage()]);
    $context['results'][] = 'content_translation_failed';
  }
}

/**
 * Batch operation: Translate FAQ content.
 */
function constructor_batch_translate_faq($constructor_settings, &$context) {
  $ai_settings = $constructor_settings['ai_settings'] ?? [];
  $languages = $constructor_settings['languages'] ?? [];

  $default_language = $languages['default_language'] ?? 'en';
  $additional_languages = $languages['additional_languages'] ?? [];
  $additional_languages = array_filter($additional_languages, function ($langcode) use ($default_language) {
    return !empty($langcode) && $langcode !== $default_language;
  });

  if (empty($additional_languages) || empty($ai_settings['api_key'])) {
    $context['results'][] = 'faq_translation_skipped';
    $context['finished'] = 1;
    return;
  }

  // Initialize on first call.
  if (!isset($context['sandbox']['initialized'])) {
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nids = $node_storage->getQuery()
      ->condition('type', 'faq')
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->execute();

    $context['sandbox']['nids'] = array_values($nids);
    $context['sandbox']['languages'] = array_values($additional_languages);
    $context['sandbox']['ai_settings'] = $ai_settings;
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['max'] = count($nids) * count($additional_languages);
    $context['sandbox']['current_node'] = 0;
    $context['sandbox']['current_lang'] = 0;
    $context['sandbox']['initialized'] = TRUE;

    if ($context['sandbox']['max'] == 0) {
      $context['finished'] = 1;
      $context['results'][] = 'faq_translation_skipped';
      return;
    }

    $context['finished'] = 0;
    return;
  }

  // Process one node+language combination per call.
  $nids = $context['sandbox']['nids'];
  $langs = $context['sandbox']['languages'];
  $node_index = $context['sandbox']['current_node'];
  $lang_index = $context['sandbox']['current_lang'];

  if ($node_index < count($nids)) {
    $nid = $nids[$node_index];
    $langcode = $langs[$lang_index];

    $context['message'] = t('Translating FAQ @node to @lang...', [
      '@node' => $node_index + 1,
      '@lang' => $langcode,
    ]);

    try {
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
      if ($node && !$node->hasTranslation($langcode)) {
        _constructor_translate_single_node($node, $langcode, $context['sandbox']['ai_settings'], 'faq');
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('constructor')->error('FAQ translation error: @message', ['@message' => $e->getMessage()]);
    }

    $context['sandbox']['progress']++;

    // Move to next language or next node.
    $context['sandbox']['current_lang']++;
    if ($context['sandbox']['current_lang'] >= count($langs)) {
      $context['sandbox']['current_lang'] = 0;
      $context['sandbox']['current_node']++;
    }
  }

  // Calculate progress.
  $context['finished'] = $context['sandbox']['max'] > 0
    ? $context['sandbox']['progress'] / $context['sandbox']['max']
    : 1;

  if ($context['finished'] >= 1) {
    $context['results'][] = 'faq_translated';
  }
}

/**
 * Batch operation: Translate Team content.
 *
 * Progressive operation: one node+language per request.
 */
function constructor_batch_translate_team($constructor_settings, &$context) {
  $ai_settings = $constructor_settings['ai_settings'] ?? [];
  $languages = $constructor_settings['languages'] ?? [];

  $default_language = $languages['default_language'] ?? 'en';
  $additional_languages = $languages['additional_languages'] ?? [];
  $additional_languages = array_filter($additional_languages, function ($langcode) use ($default_language) {
    return !empty($langcode) && $langcode !== $default_language;
  });

  if (empty($additional_languages) || empty($ai_settings['api_key'])) {
    $context['results'][] = 'team_translation_skipped';
    $context['finished'] = 1;
    return;
  }

  // Initialize on first call.
  if (!isset($context['sandbox']['initialized'])) {
    // Enable content translation for this content type.
    _constructor_enable_content_translation('team_member');

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nids = $node_storage->getQuery()
      ->condition('type', 'team_member')
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->execute();

    $context['sandbox']['nids'] = array_values($nids);
    $context['sandbox']['languages'] = array_values($additional_languages);
    $context['sandbox']['ai_settings'] = $ai_settings;
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['max'] = count($nids) * count($additional_languages);
    $context['sandbox']['current_node'] = 0;
    $context['sandbox']['current_lang'] = 0;
    $context['sandbox']['initialized'] = TRUE;

    if ($context['sandbox']['max'] == 0) {
      $context['finished'] = 1;
      $context['results'][] = 'team_translation_skipped';
      return;
    }

    $context['finished'] = 0;
    return;
  }

  // Process one node+language combination per call.
  $nids = $context['sandbox']['nids'];
  $langs = $context['sandbox']['languages'];
  $node_index = $context['sandbox']['current_node'];
  $lang_index = $context['sandbox']['current_lang'];

  if ($node_index < count($nids)) {
    $nid = $nids[$node_index];
    $langcode = $langs[$lang_index];

    $context['message'] = t('Translating Team @node to @lang...', [
      '@node' => $node_index + 1,
      '@lang' => $langcode,
    ]);

    try {
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
      if ($node && !$node->hasTranslation($langcode)) {
        _constructor_translate_single_node($node, $langcode, $context['sandbox']['ai_settings'], 'team_member');
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('constructor')->error('Team translation error: @message', ['@message' => $e->getMessage()]);
    }

    $context['sandbox']['progress']++;

    // Move to next language or next node.
    $context['sandbox']['current_lang']++;
    if ($context['sandbox']['current_lang'] >= count($langs)) {
      $context['sandbox']['current_lang'] = 0;
      $context['sandbox']['current_node']++;
    }
  }

  // Calculate progress.
  $context['finished'] = $context['sandbox']['max'] > 0
    ? $context['sandbox']['progress'] / $context['sandbox']['max']
    : 1;

  if ($context['finished'] >= 1) {
    $context['results'][] = 'team_translated';
  }
}

/**
 * Batch operation: Translate Services content.
 *
 * Progressive operation: one node+language per request.
 */
function constructor_batch_translate_services($constructor_settings, &$context) {
  $ai_settings = $constructor_settings['ai_settings'] ?? [];
  $languages = $constructor_settings['languages'] ?? [];

  $default_language = $languages['default_language'] ?? 'en';
  $additional_languages = $languages['additional_languages'] ?? [];
  $additional_languages = array_filter($additional_languages, function ($langcode) use ($default_language) {
    return !empty($langcode) && $langcode !== $default_language;
  });

  if (empty($additional_languages) || empty($ai_settings['api_key'])) {
    $context['results'][] = 'services_translation_skipped';
    $context['finished'] = 1;
    return;
  }

  // Initialize on first call.
  if (!isset($context['sandbox']['initialized'])) {
    // Enable content translation for this content type.
    _constructor_enable_content_translation('service');

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nids = $node_storage->getQuery()
      ->condition('type', 'service')
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->execute();

    $context['sandbox']['nids'] = array_values($nids);
    $context['sandbox']['languages'] = array_values($additional_languages);
    $context['sandbox']['ai_settings'] = $ai_settings;
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['max'] = count($nids) * count($additional_languages);
    $context['sandbox']['current_node'] = 0;
    $context['sandbox']['current_lang'] = 0;
    $context['sandbox']['initialized'] = TRUE;

    if ($context['sandbox']['max'] == 0) {
      $context['finished'] = 1;
      $context['results'][] = 'services_translation_skipped';
      return;
    }

    $context['finished'] = 0;
    return;
  }

  // Process one node+language combination per call.
  $nids = $context['sandbox']['nids'];
  $langs = $context['sandbox']['languages'];
  $node_index = $context['sandbox']['current_node'];
  $lang_index = $context['sandbox']['current_lang'];

  if ($node_index < count($nids)) {
    $nid = $nids[$node_index];
    $langcode = $langs[$lang_index];

    $context['message'] = t('Translating Service @node to @lang...', [
      '@node' => $node_index + 1,
      '@lang' => $langcode,
    ]);

    try {
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
      if ($node && !$node->hasTranslation($langcode)) {
        _constructor_translate_single_node($node, $langcode, $context['sandbox']['ai_settings'], 'service');
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('constructor')->error('Service translation error: @message', ['@message' => $e->getMessage()]);
    }

    $context['sandbox']['progress']++;

    // Move to next language or next node.
    $context['sandbox']['current_lang']++;
    if ($context['sandbox']['current_lang'] >= count($langs)) {
      $context['sandbox']['current_lang'] = 0;
      $context['sandbox']['current_node']++;
    }
  }

  // Calculate progress.
  $context['finished'] = $context['sandbox']['max'] > 0
    ? $context['sandbox']['progress'] / $context['sandbox']['max']
    : 1;

  if ($context['finished'] >= 1) {
    $context['results'][] = 'services_translated';
  }
}

/**
 * Batch operation: Translate Articles content.
 *
 * Progressive operation: one node+language per request.
 */
function constructor_batch_translate_articles($constructor_settings, &$context) {
  $ai_settings = $constructor_settings['ai_settings'] ?? [];
  $languages = $constructor_settings['languages'] ?? [];

  $default_language = $languages['default_language'] ?? 'en';
  $additional_languages = $languages['additional_languages'] ?? [];
  $additional_languages = array_filter($additional_languages, function ($langcode) use ($default_language) {
    return !empty($langcode) && $langcode !== $default_language;
  });

  if (empty($additional_languages) || empty($ai_settings['api_key'])) {
    $context['results'][] = 'articles_translation_skipped';
    $context['finished'] = 1;
    return;
  }

  // Initialize on first call.
  if (!isset($context['sandbox']['initialized'])) {
    // Enable content translation for this content type.
    _constructor_enable_content_translation('article');

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nids = $node_storage->getQuery()
      ->condition('type', 'article')
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->execute();

    $context['sandbox']['nids'] = array_values($nids);
    $context['sandbox']['languages'] = array_values($additional_languages);
    $context['sandbox']['ai_settings'] = $ai_settings;
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['max'] = count($nids) * count($additional_languages);
    $context['sandbox']['current_node'] = 0;
    $context['sandbox']['current_lang'] = 0;
    $context['sandbox']['initialized'] = TRUE;

    if ($context['sandbox']['max'] == 0) {
      $context['finished'] = 1;
      $context['results'][] = 'articles_translation_skipped';
      return;
    }

    $context['finished'] = 0;
    return;
  }

  // Process one node+language combination per call.
  $nids = $context['sandbox']['nids'];
  $langs = $context['sandbox']['languages'];
  $node_index = $context['sandbox']['current_node'];
  $lang_index = $context['sandbox']['current_lang'];

  if ($node_index < count($nids)) {
    $nid = $nids[$node_index];
    $langcode = $langs[$lang_index];

    $context['message'] = t('Translating Article @node to @lang...', [
      '@node' => $node_index + 1,
      '@lang' => $langcode,
    ]);

    try {
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
      if ($node && !$node->hasTranslation($langcode)) {
        _constructor_translate_single_node($node, $langcode, $context['sandbox']['ai_settings'], 'article');
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('constructor')->error('Article translation error: @message', ['@message' => $e->getMessage()]);
    }

    $context['sandbox']['progress']++;

    // Move to next language or next node.
    $context['sandbox']['current_lang']++;
    if ($context['sandbox']['current_lang'] >= count($langs)) {
      $context['sandbox']['current_lang'] = 0;
      $context['sandbox']['current_node']++;
    }
  }

  // Calculate progress.
  $context['finished'] = $context['sandbox']['max'] > 0
    ? $context['sandbox']['progress'] / $context['sandbox']['max']
    : 1;

  if ($context['finished'] >= 1) {
    $context['results'][] = 'articles_translated';
  }
}

/**
 * Batch operation: Translate Products content.
 *
 * Progressive operation: one node+language per request.
 */
function constructor_batch_translate_products($constructor_settings, &$context) {
  $ai_settings = $constructor_settings['ai_settings'] ?? [];
  $languages = $constructor_settings['languages'] ?? [];

  $default_language = $languages['default_language'] ?? 'en';
  $additional_languages = $languages['additional_languages'] ?? [];
  $additional_languages = array_filter($additional_languages, function ($langcode) use ($default_language) {
    return !empty($langcode) && $langcode !== $default_language;
  });

  if (empty($additional_languages) || empty($ai_settings['api_key'])) {
    $context['results'][] = 'products_translation_skipped';
    $context['finished'] = 1;
    return;
  }

  // Initialize on first call.
  if (!isset($context['sandbox']['initialized'])) {
    // Enable content translation for this content type.
    _constructor_enable_content_translation('product');

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nids = $node_storage->getQuery()
      ->condition('type', 'product')
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->execute();

    $context['sandbox']['nids'] = array_values($nids);
    $context['sandbox']['languages'] = array_values($additional_languages);
    $context['sandbox']['ai_settings'] = $ai_settings;
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['max'] = count($nids) * count($additional_languages);
    $context['sandbox']['current_node'] = 0;
    $context['sandbox']['current_lang'] = 0;
    $context['sandbox']['initialized'] = TRUE;

    if ($context['sandbox']['max'] == 0) {
      $context['finished'] = 1;
      $context['results'][] = 'products_translation_skipped';
      return;
    }

    $context['finished'] = 0;
    return;
  }

  // Process one node+language combination per call.
  $nids = $context['sandbox']['nids'];
  $langs = $context['sandbox']['languages'];
  $node_index = $context['sandbox']['current_node'];
  $lang_index = $context['sandbox']['current_lang'];

  if ($node_index < count($nids)) {
    $nid = $nids[$node_index];
    $langcode = $langs[$lang_index];

    $context['message'] = t('Translating Product @node to @lang...', [
      '@node' => $node_index + 1,
      '@lang' => $langcode,
    ]);

    try {
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
      if ($node && !$node->hasTranslation($langcode)) {
        _constructor_translate_single_node($node, $langcode, $context['sandbox']['ai_settings'], 'product');
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('constructor')->error('Product translation error: @message', ['@message' => $e->getMessage()]);
    }

    $context['sandbox']['progress']++;

    // Move to next language or next node.
    $context['sandbox']['current_lang']++;
    if ($context['sandbox']['current_lang'] >= count($langs)) {
      $context['sandbox']['current_lang'] = 0;
      $context['sandbox']['current_node']++;
    }
  }

  // Calculate progress.
  $context['finished'] = $context['sandbox']['max'] > 0
    ? $context['sandbox']['progress'] / $context['sandbox']['max']
    : 1;

  if ($context['finished'] >= 1) {
    $context['results'][] = 'products_translated';
  }
}

/**
 * Batch operation: Translate Pricing Plans content.
 */
function constructor_batch_translate_pricing_plans($constructor_settings, &$context) {
  $ai_settings = $constructor_settings['ai_settings'] ?? [];
  $languages = $constructor_settings['languages'] ?? [];

  $default_language = $languages['default_language'] ?? 'en';
  $additional_languages = $languages['additional_languages'] ?? [];
  $additional_languages = array_filter($additional_languages, function ($langcode) use ($default_language) {
    return !empty($langcode) && $langcode !== $default_language;
  });

  if (empty($additional_languages) || empty($ai_settings['api_key'])) {
    $context['results'][] = 'pricing_plans_translation_skipped';
    $context['finished'] = 1;
    return;
  }

  // Initialize on first call.
  if (!isset($context['sandbox']['initialized'])) {
    // Enable content translation for this content type.
    _constructor_enable_content_translation('pricing_plan');

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nids = $node_storage->getQuery()
      ->condition('type', 'pricing_plan')
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->execute();

    $context['sandbox']['nids'] = array_values($nids);
    $context['sandbox']['languages'] = array_values($additional_languages);
    $context['sandbox']['ai_settings'] = $ai_settings;
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['max'] = count($nids) * count($additional_languages);
    $context['sandbox']['current_node'] = 0;
    $context['sandbox']['current_lang'] = 0;
    $context['sandbox']['initialized'] = TRUE;

    if ($context['sandbox']['max'] == 0) {
      $context['finished'] = 1;
      $context['results'][] = 'pricing_plans_translation_skipped';
      return;
    }

    $context['finished'] = 0;
    return;
  }

  // Process one node+language combination per call.
  $nids = $context['sandbox']['nids'];
  $langs = $context['sandbox']['languages'];
  $node_index = $context['sandbox']['current_node'];
  $lang_index = $context['sandbox']['current_lang'];

  if ($node_index < count($nids)) {
    $nid = $nids[$node_index];
    $langcode = $langs[$lang_index];

    $context['message'] = t('Translating Pricing Plan @node to @lang...', [
      '@node' => $node_index + 1,
      '@lang' => $langcode,
    ]);

    try {
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
      if ($node && !$node->hasTranslation($langcode)) {
        _constructor_translate_single_node($node, $langcode, $context['sandbox']['ai_settings'], 'pricing_plan');
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('constructor')->error('Pricing Plan translation error: @message', ['@message' => $e->getMessage()]);
    }

    $context['sandbox']['progress']++;

    // Move to next language or next node.
    $context['sandbox']['current_lang']++;
    if ($context['sandbox']['current_lang'] >= count($langs)) {
      $context['sandbox']['current_lang'] = 0;
      $context['sandbox']['current_node']++;
    }
  }

  // Calculate progress.
  $context['finished'] = $context['sandbox']['max'] > 0
    ? $context['sandbox']['progress'] / $context['sandbox']['max']
    : 1;

  if ($context['finished'] >= 1) {
    $context['results'][] = 'pricing_plans_translated';
  }
}

/**
 * Batch operation: Translate existing content.
 *
 * @deprecated Use the individual translation batch functions instead.
 */
function constructor_batch_translate_existing_content($constructor_settings, &$context) {
  $context['message'] = t('Translating content...');

  $content_type_modules = $constructor_settings['content_type_modules'] ?? [];
  $ai_settings = $constructor_settings['ai_settings'] ?? [];
  $site_basics = $constructor_settings['site_basics'] ?? [];
  $languages = $constructor_settings['languages'] ?? [];

  if (empty($ai_settings['api_key'])) {
    \Drupal::logger('constructor')->notice('Translations skipped: No API key.');
    $context['results'][] = 'translations_skipped_no_key';
    return;
  }

  if (!\Drupal::moduleHandler()->moduleExists('content_translation')) {
    \Drupal::logger('constructor')->notice('Translations skipped: content_translation not installed.');
    $context['results'][] = 'translations_skipped_no_module';
    return;
  }

  $default_language = $languages['default_language'] ?? 'en';
  $additional_languages = $languages['additional_languages'] ?? [];
  $additional_languages = array_filter($additional_languages, function ($langcode) use ($default_language) {
    return !empty($langcode) && $langcode !== $default_language;
  });

  if (empty($additional_languages)) {
    \Drupal::logger('constructor')->notice('Translations skipped: No additional languages.');
    $context['results'][] = 'translations_skipped_no_languages';
    return;
  }

  $site_description = $site_basics['site_description'] ?? '';

  // Translate FAQ nodes.
  if (in_array('content_faq', $content_type_modules)) {
    $context['message'] = t('Translating FAQ content...');
    _constructor_translate_faq_nodes($additional_languages, $site_description, $ai_settings);
  }

  // Translate Team nodes.
  if (in_array('content_team', $content_type_modules)) {
    $context['message'] = t('Translating Team content...');
    _constructor_translate_team_nodes($additional_languages, $site_description, $ai_settings);
  }

  // Translate Service nodes.
  if (in_array('content_services', $content_type_modules)) {
    $context['message'] = t('Translating Service content...');
    _constructor_translate_service_nodes($additional_languages, $site_description, $ai_settings);
  }

  // Translate Article nodes.
  if (in_array('content_article', $content_type_modules)) {
    $context['message'] = t('Translating Article content...');
    _constructor_translate_article_nodes($additional_languages, $site_description, $ai_settings);
  }

  // Translate Product nodes.
  if (in_array('content_commerce', $content_type_modules)) {
    $context['message'] = t('Translating Product content...');
    _constructor_translate_product_nodes($additional_languages, $site_description, $ai_settings);
  }

  $context['results'][] = 'translations_completed';
  \Drupal::logger('constructor')->notice('Content translation batch completed.');
}

/**
 * Enables content translation for a bundle.
 */
function _constructor_enable_content_translation(string $bundle): bool {
  try {
    $config = \Drupal::configFactory()->getEditable("language.content_settings.node.$bundle");
    $config->set('id', "node.$bundle");
    $config->set('langcode', 'en');
    $config->set('status', TRUE);
    $config->set('target_entity_type_id', 'node');
    $config->set('target_bundle', $bundle);
    $config->set('default_langcode', 'site_default');
    $config->set('language_alterable', TRUE);
    $config->set('third_party_settings.content_translation.enabled', TRUE);
    $config->save();

    // Clear entity and field definition caches.
    \Drupal::entityTypeManager()->clearCachedDefinitions();
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    \Drupal::cache('config')->deleteAll();

    \Drupal::logger('constructor')->notice('Enabled translation for @bundle.', ['@bundle' => $bundle]);
    return TRUE;
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->error('Failed to enable translation for @bundle: @message', [
      '@bundle' => $bundle,
      '@message' => $e->getMessage(),
    ]);
    return FALSE;
  }
}

/**
 * Generate Pathauto alias for a translated entity.
 *
 * @param \Drupal\Core\Entity\ContentEntityInterface $entity
 *   The entity (node, term, etc.) that was translated.
 * @param string $langcode
 *   The language code of the translation.
 */
function _constructor_generate_pathauto_alias($entity, string $langcode): void {
  try {
    // Check if pathauto module is enabled.
    if (!\Drupal::moduleHandler()->moduleExists('pathauto')) {
      return;
    }

    // Get the translation.
    if (!$entity->hasTranslation($langcode)) {
      return;
    }
    $translation = $entity->getTranslation($langcode);

    // Use pathauto generator service.
    /** @var \Drupal\pathauto\PathautoGeneratorInterface $pathauto_generator */
    $pathauto_generator = \Drupal::service('pathauto.generator');

    // Generate the alias for the translation.
    $pathauto_generator->updateEntityAlias($translation, 'insert');

    \Drupal::logger('constructor')->notice('Generated Pathauto alias for @type @id in @lang', [
      '@type' => $entity->getEntityTypeId(),
      '@id' => $entity->id(),
      '@lang' => $langcode,
    ]);
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->warning('Failed to generate Pathauto alias for @type @id in @lang: @message', [
      '@type' => $entity->getEntityTypeId(),
      '@id' => $entity->id(),
      '@lang' => $langcode,
      '@message' => $e->getMessage(),
    ]);
  }
}

/**
 * Translates a single node to a single language.
 *
 * Used by progressive batch translation operations.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The node to translate.
 * @param string $langcode
 *   The target language code.
 * @param array $ai_settings
 *   AI settings including api_key and text_model.
 * @param string $content_type
 *   The content type (faq, team_member, service, article, product).
 *
 * @return bool
 *   TRUE if translation was successful.
 */
function _constructor_translate_single_node($node, string $langcode, array $ai_settings, string $content_type): bool {
  $api_key = $ai_settings['api_key'] ?? '';
  if (empty($api_key)) {
    return FALSE;
  }

  $model = $ai_settings['text_model'] ?? 'gpt-4';
  $language_names = _constructor_get_language_names();
  $language_name = $language_names[$langcode] ?? $langcode;

  try {
    // Skip if already translated.
    if ($node->hasTranslation($langcode)) {
      return FALSE;
    }

    // Build prompt and translation values based on content type.
    $prompt = '';
    $translation_values = ['uid' => $node->getOwnerId()];

    switch ($content_type) {
      case 'faq':
        $original_question = $node->getTitle();
        $original_answer = $node->get('field_faq_answer')->value ?? '';
        $prompt = "Translate the following FAQ to $language_name:\n\nQuestion: $original_question\nAnswer: $original_answer\n\nReturn as JSON with 'question' and 'answer' keys. Only return the JSON.";
        break;

      case 'team_member':
        $original_name = $node->getTitle();
        $original_position = $node->get('field_team_position')->value ?? '';
        $prompt = "Translate the following job position to $language_name:\n\nPosition: $original_position\n\nReturn as JSON with 'position' key. Only return the JSON.";
        // Name stays the same for team members.
        $translation_values['title'] = $original_name;
        break;

      case 'service':
        $original_name = $node->getTitle();
        $original_description = $node->get('field_service_description')->value ?? '';
        $original_body = $node->get('field_service_body')->value ?? '';
        $prompt = "Translate the following service to $language_name:\n\nName: $original_name\nDescription: $original_description\nBody: $original_body\n\nReturn as JSON with 'name', 'description', and 'body' keys. The description is plain text. The body should be HTML formatted. Only return the JSON.";
        break;

      case 'article':
        $original_title = $node->getTitle();
        $original_body = $node->get('field_article_body')->value ?? '';
        $prompt = "Translate the following article to $language_name:\n\nTitle: $original_title\nBody: $original_body\n\nReturn as JSON with 'title' and 'body' keys. The body should be HTML formatted. Only return the JSON.";
        break;

      case 'product':
        $original_title = $node->getTitle();
        $original_body = $node->get('field_product_body')->value ?? '';
        $prompt = "Translate the following product to $language_name:\n\nName: $original_title\nDescription: $original_body\n\nReturn as JSON with 'name' and 'description' keys. The description should be HTML formatted. Only return the JSON.";
        break;

      case 'pricing_plan':
        $original_title = $node->getTitle();
        $original_description = $node->get('field_plan_description')->value ?? '';
        $original_features = $node->get('field_plan_features')->value ?? '';
        $original_cta = $node->get('field_plan_cta_text')->value ?? '';
        $original_badge = $node->get('field_plan_badge_text')->value ?? '';
        $prompt = "Translate the following pricing plan to $language_name:\n\nTitle: $original_title\nDescription: $original_description\nFeatures (one per line):\n$original_features\nButton Text: $original_cta\nBadge Text: $original_badge\n\nReturn as JSON with 'title', 'description', 'features', 'cta', and 'badge' keys. Features should be newline-separated. Only return the JSON.";
        break;

      default:
        return FALSE;
    }

    // Call OpenAI for translation.
    $translation_data = _constructor_call_openai($api_key, $model, $prompt);

    // Process translation data based on content type.
    switch ($content_type) {
      case 'faq':
        if (!empty($translation_data['question']) && !empty($translation_data['answer'])) {
          $translation_values['title'] = $translation_data['question'];
          $translation_values['field_faq_answer'] = [
            'value' => $translation_data['answer'],
            'format' => 'full_html',
          ];
        }
        else {
          return FALSE;
        }
        break;

      case 'team_member':
        if (!empty($translation_data['position'])) {
          $translation_values['field_team_position'] = $translation_data['position'];
          // Copy image from original node.
          if ($node->hasField('field_team_photo') && !$node->get('field_team_photo')->isEmpty()) {
            $translation_values['field_team_photo'] = $node->get('field_team_photo')->getValue();
          }
        }
        else {
          return FALSE;
        }
        break;

      case 'service':
        if (!empty($translation_data['name']) && !empty($translation_data['description'])) {
          $translation_values['title'] = $translation_data['name'];
          // Description is plain text.
          $translation_values['field_service_description'] = $translation_data['description'];
          // Body is HTML formatted.
          if (!empty($translation_data['body'])) {
            $translation_values['field_service_body'] = [
              'value' => $translation_data['body'],
              'format' => 'full_html',
            ];
          }
          // Copy image from original node.
          if ($node->hasField('field_service_image') && !$node->get('field_service_image')->isEmpty()) {
            $translation_values['field_service_image'] = $node->get('field_service_image')->getValue();
          }
        }
        else {
          return FALSE;
        }
        break;

      case 'article':
        if (!empty($translation_data['title']) && !empty($translation_data['body'])) {
          $translation_values['title'] = $translation_data['title'];
          $translation_values['field_article_body'] = [
            'value' => $translation_data['body'],
            'format' => 'full_html',
          ];
          // Copy image from original node.
          if ($node->hasField('field_article_image') && !$node->get('field_article_image')->isEmpty()) {
            $translation_values['field_article_image'] = $node->get('field_article_image')->getValue();
          }
          // Copy video URL from original node.
          if ($node->hasField('field_article_video_url') && !$node->get('field_article_video_url')->isEmpty()) {
            $translation_values['field_article_video_url'] = $node->get('field_article_video_url')->getValue();
          }
        }
        else {
          return FALSE;
        }
        break;

      case 'product':
        if (!empty($translation_data['name'])) {
          $translation_values['title'] = $translation_data['name'];
          if (!empty($translation_data['description'])) {
            $translation_values['field_product_body'] = [
              'value' => $translation_data['description'],
              'format' => 'full_html',
            ];
          }
          // Copy images from original node.
          if ($node->hasField('field_product_images') && !$node->get('field_product_images')->isEmpty()) {
            $translation_values['field_product_images'] = $node->get('field_product_images')->getValue();
          }
          // Copy other product fields.
          $product_fields = [
            'field_product_price',
            'field_product_sale_price',
            'field_product_colors',
            'field_product_sizes',
            'field_product_category',
          ];
          foreach ($product_fields as $field_name) {
            if ($node->hasField($field_name) && !$node->get($field_name)->isEmpty()) {
              $translation_values[$field_name] = $node->get($field_name)->getValue();
            }
          }
        }
        else {
          return FALSE;
        }
        break;

      case 'pricing_plan':
        if (!empty($translation_data['title'])) {
          $translation_values['title'] = $translation_data['title'];
          if (!empty($translation_data['description'])) {
            $translation_values['field_plan_description'] = $translation_data['description'];
          }
          if (!empty($translation_data['features'])) {
            $translation_values['field_plan_features'] = $translation_data['features'];
          }
          if (!empty($translation_data['cta'])) {
            $translation_values['field_plan_cta_text'] = $translation_data['cta'];
          }
          if (!empty($translation_data['badge'])) {
            $translation_values['field_plan_badge_text'] = $translation_data['badge'];
          }
          // Copy numeric fields from original node.
          $pricing_fields = [
            'field_plan_monthly_price',
            'field_plan_annual_price',
            'field_plan_is_recommended',
            'field_plan_weight',
            'field_plan_currency_symbol',
          ];
          foreach ($pricing_fields as $field_name) {
            if ($node->hasField($field_name) && !$node->get($field_name)->isEmpty()) {
              $translation_values[$field_name] = $node->get($field_name)->getValue();
            }
          }
        }
        else {
          return FALSE;
        }
        break;
    }

    // Add translation.
    $node->addTranslation($langcode, $translation_values);
    $node->save();

    // Generate Pathauto alias for the translation.
    _constructor_generate_pathauto_alias($node, $langcode);

    \Drupal::logger('constructor')->notice('Translated @type to @lang: @title', [
      '@type' => $content_type,
      '@lang' => $langcode,
      '@title' => $node->getTitle(),
    ]);

    return TRUE;
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->error('@type translation error for @title: @message', [
      '@type' => $content_type,
      '@title' => $node->getTitle(),
      '@message' => $e->getMessage(),
    ]);
    return FALSE;
  }
}

/**
 * Translates FAQ nodes.
 *
 * @deprecated Use constructor_batch_translate_faq() with progressive operations instead.
 */
function _constructor_translate_faq_nodes(array $languages, string $site_description, array $ai_settings) {
  if (!_constructor_enable_content_translation('faq')) {
    return;
  }

  try {
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nids = $node_storage->getQuery()
      ->condition('type', 'faq')
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->execute();

    if (empty($nids)) {
      return;
    }

    $nodes = $node_storage->loadMultiple($nids);
    $count = 0;
    foreach ($nodes as $node) {
      if (_constructor_translate_faq_node($node, $languages, $ai_settings)) {
        $count++;
      }
    }
    \Drupal::logger('constructor')->notice('Translated @count FAQ nodes.', ['@count' => $count]);
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->error('FAQ translation error: @message', ['@message' => $e->getMessage()]);
  }
}

/**
 * Translates a single FAQ node.
 *
 * @deprecated Use _constructor_translate_single_node() instead.
 */
function _constructor_translate_faq_node($node, array $languages, array $ai_settings): bool {
  $api_key = $ai_settings['api_key'];
  $model = $ai_settings['text_model'] ?? 'gpt-4';
  $original_question = $node->getTitle();
  $original_answer = $node->get('field_faq_answer')->value;

  $language_names = _constructor_get_language_names();
  $translated = FALSE;

  foreach ($languages as $langcode) {
    if ($langcode === $node->language()->getId()) {
      continue;
    }

    try {
      if ($node->hasTranslation($langcode)) {
        continue;
      }
    }
    catch (\Exception $e) {
      // If hasTranslation fails, try anyway
    }

    $language_name = $language_names[$langcode] ?? $langcode;

    try {
      $prompt = "Translate the following FAQ to $language_name:\n\nQuestion: $original_question\nAnswer: $original_answer\n\nReturn as JSON with 'question' and 'answer' keys. Only return the JSON.";

      $translation_data = _constructor_call_openai($api_key, $model, $prompt);

      if (!empty($translation_data['question']) && !empty($translation_data['answer'])) {
        $node->addTranslation($langcode, [
          'title' => $translation_data['question'],
          'field_faq_answer' => [
            'value' => $translation_data['answer'],
            'format' => 'full_html',
          ],
          'uid' => $node->getOwnerId(),
        ]);
        $node->save();
        // Generate Pathauto alias for the translation.
        _constructor_generate_pathauto_alias($node, $langcode);
        $translated = TRUE;
        \Drupal::logger('constructor')->notice('Translated FAQ to @lang: @title', [
          '@lang' => $langcode,
          '@title' => $original_question,
        ]);
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('constructor')->error('FAQ translation error: @message', ['@message' => $e->getMessage()]);
    }
  }

  return $translated;
}

/**
 * Translates Team nodes.
 *
 * @deprecated Use constructor_batch_translate_team() with progressive operations instead.
 */
function _constructor_translate_team_nodes(array $languages, string $site_description, array $ai_settings) {
  if (!_constructor_enable_content_translation('team_member')) {
    return;
  }

  try {
    \Drupal::entityTypeManager()->getStorage('node')->resetCache();

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nids = $node_storage->getQuery()
      ->condition('type', 'team_member')
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->execute();

    \Drupal::logger('constructor')->notice('Found @count Team nodes to translate.', ['@count' => count($nids)]);

    if (empty($nids)) {
      return;
    }

    $nodes = $node_storage->loadMultiple($nids);
    $count = 0;
    foreach ($nodes as $node) {
      if (_constructor_translate_team_node($node, $languages, $ai_settings)) {
        $count++;
      }
    }
    \Drupal::logger('constructor')->notice('Translated @count Team nodes.', ['@count' => $count]);
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->error('Team translation error: @message', ['@message' => $e->getMessage()]);
  }
}

/**
 * Translates a single Team node.
 *
 * @deprecated Use _constructor_translate_single_node() instead.
 */
function _constructor_translate_team_node($node, array $languages, array $ai_settings): bool {
  $api_key = $ai_settings['api_key'];
  $model = $ai_settings['text_model'] ?? 'gpt-4';
  $original_name = $node->getTitle();
  $original_position = $node->get('field_team_position')->value ?? '';

  $language_names = _constructor_get_language_names();
  $translated = FALSE;

  foreach ($languages as $langcode) {
    if ($langcode === $node->language()->getId()) {
      continue;
    }

    try {
      if ($node->hasTranslation($langcode)) {
        continue;
      }
    }
    catch (\Exception $e) {
      // If hasTranslation fails, try anyway
    }

    $language_name = $language_names[$langcode] ?? $langcode;

    try {
      $prompt = "Translate the following job position to $language_name:\n\nPosition: $original_position\n\nReturn as JSON with 'position' key. Only return the JSON.";

      $translation_data = _constructor_call_openai($api_key, $model, $prompt);

      if (!empty($translation_data['position'])) {
        $node->addTranslation($langcode, [
          'title' => $original_name,
          'field_team_position' => $translation_data['position'],
          'uid' => $node->getOwnerId(),
        ]);
        $node->save();
        // Generate Pathauto alias for the translation.
        _constructor_generate_pathauto_alias($node, $langcode);
        $translated = TRUE;
        \Drupal::logger('constructor')->notice('Translated Team to @lang: @name', [
          '@lang' => $langcode,
          '@name' => $original_name,
        ]);
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('constructor')->error('Team translation error for @name: @message', [
        '@name' => $original_name,
        '@message' => $e->getMessage(),
      ]);
    }
  }

  return $translated;
}

/**
 * Translates Service nodes.
 *
 * @deprecated Use constructor_batch_translate_services() with progressive operations instead.
 */
function _constructor_translate_service_nodes(array $languages, string $site_description, array $ai_settings) {
  if (!_constructor_enable_content_translation('service')) {
    return;
  }

  try {
    \Drupal::entityTypeManager()->getStorage('node')->resetCache();

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nids = $node_storage->getQuery()
      ->condition('type', 'service')
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->execute();

    \Drupal::logger('constructor')->notice('Found @count Service nodes to translate.', ['@count' => count($nids)]);

    if (empty($nids)) {
      return;
    }

    $nodes = $node_storage->loadMultiple($nids);
    $count = 0;
    foreach ($nodes as $node) {
      if (_constructor_translate_service_node($node, $languages, $ai_settings)) {
        $count++;
      }
    }
    \Drupal::logger('constructor')->notice('Translated @count Service nodes.', ['@count' => $count]);
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->error('Service translation error: @message', ['@message' => $e->getMessage()]);
  }
}

/**
 * Translates a single Service node.
 *
 * @deprecated Use _constructor_translate_single_node() instead.
 */
function _constructor_translate_service_node($node, array $languages, array $ai_settings): bool {
  $api_key = $ai_settings['api_key'] ?? '';
  if (empty($api_key)) {
    return FALSE;
  }

  $model = $ai_settings['text_model'] ?? 'gpt-4';
  $language_names = _constructor_get_language_names();

  $original_name = $node->getTitle();
  $original_description = $node->get('field_service_description')->value ?? '';

  $translated = FALSE;

  foreach ($languages as $langcode) {
    if (empty($langcode)) {
      continue;
    }

    try {
      if ($node->hasTranslation($langcode)) {
        continue;
      }
    }
    catch (\Exception $e) {
      // If hasTranslation fails, try anyway
    }

    $language_name = $language_names[$langcode] ?? $langcode;

    try {
      $prompt = "Translate the following service to $language_name:\n\nName: $original_name\nDescription: $original_description\n\nReturn as JSON with 'name' and 'description' keys. Only return the JSON.";

      $translation_data = _constructor_call_openai($api_key, $model, $prompt);

      if (!empty($translation_data['name']) && !empty($translation_data['description'])) {
        $node->addTranslation($langcode, [
          'title' => $translation_data['name'],
          'field_service_description' => [
            'value' => $translation_data['description'],
            'format' => 'full_html',
          ],
          'uid' => $node->getOwnerId(),
        ]);
        $node->save();
        // Generate Pathauto alias for the translation.
        _constructor_generate_pathauto_alias($node, $langcode);
        $translated = TRUE;
        \Drupal::logger('constructor')->notice('Translated Service to @lang: @name', [
          '@lang' => $langcode,
          '@name' => $translation_data['name'],
        ]);
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('constructor')->error('Service translation error for @name: @message', [
        '@name' => $original_name,
        '@message' => $e->getMessage(),
      ]);
    }
  }

  return $translated;
}

/**
 * Translates Article nodes.
 *
 * @deprecated Use constructor_batch_translate_articles() with progressive operations instead.
 */
function _constructor_translate_article_nodes(array $languages, string $site_description, array $ai_settings) {
  if (!_constructor_enable_content_translation('article')) {
    return;
  }

  try {
    \Drupal::entityTypeManager()->getStorage('node')->resetCache();

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nids = $node_storage->getQuery()
      ->condition('type', 'article')
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->execute();

    \Drupal::logger('constructor')->notice('Found @count Article nodes to translate.', ['@count' => count($nids)]);

    if (empty($nids)) {
      return;
    }

    $nodes = $node_storage->loadMultiple($nids);
    $count = 0;
    foreach ($nodes as $node) {
      if (_constructor_translate_article_node($node, $languages, $ai_settings)) {
        $count++;
      }
    }
    \Drupal::logger('constructor')->notice('Translated @count Article nodes.', ['@count' => $count]);
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->error('Article translation error: @message', ['@message' => $e->getMessage()]);
  }
}

/**
 * Translates a single Article node.
 *
 * @deprecated Use _constructor_translate_single_node() instead.
 */
function _constructor_translate_article_node($node, array $languages, array $ai_settings): bool {
  $api_key = $ai_settings['api_key'] ?? '';
  if (empty($api_key)) {
    return FALSE;
  }

  $model = $ai_settings['text_model'] ?? 'gpt-4';
  $language_names = _constructor_get_language_names();

  $original_title = $node->getTitle();
  $original_body = $node->get('field_article_body')->value ?? '';

  $translated = FALSE;

  foreach ($languages as $langcode) {
    if (empty($langcode)) {
      continue;
    }

    try {
      if ($node->hasTranslation($langcode)) {
        continue;
      }
    }
    catch (\Exception $e) {
      // If hasTranslation fails, try anyway
    }

    $language_name = $language_names[$langcode] ?? $langcode;

    try {
      $prompt = "Translate the following article to $language_name:\n\nTitle: $original_title\nBody: $original_body\n\nReturn as JSON with 'title' and 'body' keys. The body should be HTML formatted. Only return the JSON.";

      $translation_data = _constructor_call_openai($api_key, $model, $prompt);

      if (!empty($translation_data['title']) && !empty($translation_data['body'])) {
        $node->addTranslation($langcode, [
          'title' => $translation_data['title'],
          'field_article_body' => [
            'value' => $translation_data['body'],
            'format' => 'full_html',
          ],
          'uid' => $node->getOwnerId(),
        ]);
        $node->save();
        // Generate Pathauto alias for the translation.
        _constructor_generate_pathauto_alias($node, $langcode);
        $translated = TRUE;
        \Drupal::logger('constructor')->notice('Translated Article to @lang: @title', [
          '@lang' => $langcode,
          '@title' => $translation_data['title'],
        ]);
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('constructor')->error('Article translation error for @title: @message', [
        '@title' => $original_title,
        '@message' => $e->getMessage(),
      ]);
    }
  }

  return $translated;
}

/**
 * Translates Product nodes.
 *
 * @deprecated Use constructor_batch_translate_products() with progressive operations instead.
 */
function _constructor_translate_product_nodes(array $languages, string $site_description, array $ai_settings) {
  if (!_constructor_enable_content_translation('product')) {
    return;
  }

  try {
    \Drupal::entityTypeManager()->getStorage('node')->resetCache();

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nids = $node_storage->getQuery()
      ->condition('type', 'product')
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->execute();

    \Drupal::logger('constructor')->notice('Found @count Product nodes to translate.', ['@count' => count($nids)]);

    if (empty($nids)) {
      return;
    }

    $nodes = $node_storage->loadMultiple($nids);
    $count = 0;
    foreach ($nodes as $node) {
      if (_constructor_translate_product_node($node, $languages, $ai_settings)) {
        $count++;
      }
    }
    \Drupal::logger('constructor')->notice('Translated @count Product nodes.', ['@count' => $count]);
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->error('Product translation error: @message', ['@message' => $e->getMessage()]);
  }
}

/**
 * Translates a single Product node.
 *
 * @deprecated Use _constructor_translate_single_node() instead.
 */
function _constructor_translate_product_node($node, array $languages, array $ai_settings): bool {
  $api_key = $ai_settings['api_key'] ?? '';
  if (empty($api_key)) {
    return FALSE;
  }

  $model = $ai_settings['text_model'] ?? 'gpt-4';
  $language_names = _constructor_get_language_names();

  $original_title = $node->getTitle();
  // Products use field_product_body, not body.
  $original_body = $node->get('field_product_body')->value ?? '';

  $translated = FALSE;

  foreach ($languages as $langcode) {
    if (empty($langcode)) {
      continue;
    }

    try {
      if ($node->hasTranslation($langcode)) {
        continue;
      }
    }
    catch (\Exception $e) {
      // If hasTranslation fails, try anyway
    }

    $language_name = $language_names[$langcode] ?? $langcode;

    try {
      $prompt = "Translate the following product to $language_name:\n\nName: $original_title\nDescription: $original_body\n\nReturn as JSON with 'name' and 'description' keys. The description should be HTML formatted. Only return the JSON.";

      $translation_data = _constructor_call_openai($api_key, $model, $prompt);

      if (!empty($translation_data['name'])) {
        $translation_values = [
          'title' => $translation_data['name'],
          'uid' => $node->getOwnerId(),
        ];
        if (!empty($translation_data['description'])) {
          // Products use field_product_body, not body.
          $translation_values['field_product_body'] = [
            'value' => $translation_data['description'],
            'format' => 'full_html',
          ];
        }
        $node->addTranslation($langcode, $translation_values);
        $node->save();
        // Generate Pathauto alias for the translation.
        _constructor_generate_pathauto_alias($node, $langcode);
        $translated = TRUE;
        \Drupal::logger('constructor')->notice('Translated Product to @lang: @title', [
          '@lang' => $langcode,
          '@title' => $translation_data['name'],
        ]);
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('constructor')->error('Product translation error for @title: @message', [
        '@title' => $original_title,
        '@message' => $e->getMessage(),
      ]);
    }
  }

  return $translated;
}

/**
 * Calls OpenAI API for translation.
 */
function _constructor_call_openai(string $api_key, string $model, string $prompt): array {
  $client = \Drupal::httpClient();
  $response = $client->post('https://api.openai.com/v1/chat/completions', [
    'headers' => [
      'Authorization' => 'Bearer ' . $api_key,
      'Content-Type' => 'application/json',
    ],
    'json' => [
      'model' => $model,
      'messages' => [['role' => 'user', 'content' => $prompt]],
      'temperature' => 0.3,
      'max_tokens' => 500,
    ],
    'timeout' => 30,
  ]);

  $data = json_decode($response->getBody()->getContents(), TRUE);
  $content = $data['choices'][0]['message']['content'] ?? '';
  $content = trim($content);
  $content = preg_replace('/^```json\s*/', '', $content);
  $content = preg_replace('/\s*```$/', '', $content);

  return json_decode($content, TRUE) ?? [];
}

/**
 * Gets language names for translation prompts.
 */
function _constructor_get_language_names(): array {
  return [
    'en' => 'English',
    'uk' => 'Ukrainian',
    'de' => 'German',
    'fr' => 'French',
    'es' => 'Spanish',
    'it' => 'Italian',
    'pl' => 'Polish',
    'nl' => 'Dutch',
    'ru' => 'Russian',
  ];
}

/**
 * Batch operation: Update translations.
 */
function constructor_batch_update_translations(&$context) {
  $context['message'] = t('Updating translations...');

  // Check if locale module is enabled.
  if (!\Drupal::moduleHandler()->moduleExists('locale')) {
    $context['results'][] = 'translations_skipped';
    return;
  }

  // Get all installed languages except English.
  $languages = \Drupal::languageManager()->getLanguages();
  $langcodes = [];
  foreach ($languages as $langcode => $language) {
    if ($langcode !== 'en') {
      $langcodes[] = $langcode;
    }
  }

  if (empty($langcodes)) {
    $context['results'][] = 'translations_no_languages';
    return;
  }

  // Import custom translations from modules and theme.
  _constructor_import_custom_translations($langcodes);

  $context['results'][] = 'translations';
}

/**
 * Import custom translations from modules and theme.
 *
 * @param array $langcodes
 *   Array of language codes to import translations for.
 */
function _constructor_import_custom_translations(array $langcodes) {
  $app_root = \Drupal::root();

  // Define paths to search for translation files.
  $translation_paths = [
    // Profile translations.
    $app_root . '/profiles/custom/constructor/translations',
    // Custom modules.
    $app_root . '/modules/custom/constructor_core/translations',
    $app_root . '/modules/custom/constructor_hero/translations',
    $app_root . '/modules/custom/content_article/translations',
    $app_root . '/modules/custom/content_commerce/translations',
    $app_root . '/modules/custom/content_faq/translations',
    $app_root . '/modules/custom/content_services/translations',
    $app_root . '/modules/custom/content_team/translations',
    $app_root . '/modules/custom/contact_form/translations',
    $app_root . '/modules/custom/gallery/translations',
    $app_root . '/modules/custom/language_switcher/translations',
    $app_root . '/modules/custom/openai_provider/translations',
    $app_root . '/modules/custom/simple_metatag/translations',
    $app_root . '/modules/custom/simple_sitemap_generator/translations',
    // Theme translations.
    $app_root . '/themes/custom/constructor_theme/translations',
  ];

  $locale_storage = \Drupal::service('locale.storage');
  $imported_count = 0;

  foreach ($langcodes as $langcode) {
    foreach ($translation_paths as $path) {
      // Check for {langcode}.po file.
      $po_file = $path . '/' . $langcode . '.po';
      if (!file_exists($po_file)) {
        continue;
      }

      try {
        // Use Gettext file reader.
        $reader = new \Drupal\Component\Gettext\PoStreamReader();
        $reader->setURI($po_file);
        $reader->open();

        $header = $reader->getHeader();
        if (!$header) {
          \Drupal::logger('constructor')->warning('Invalid PO file: @file', ['@file' => $po_file]);
          continue;
        }

        // Import each translation string.
        while ($item = $reader->readItem()) {
          if (empty($item->getSource()) || empty($item->getTranslation())) {
            continue;
          }

          // Find or create the source string.
          $source_string = $locale_storage->findString([
            'source' => $item->getSource(),
            'context' => $item->getContext() ?: '',
          ]);

          if (!$source_string) {
            $source_string = $locale_storage->createString([
              'source' => $item->getSource(),
              'context' => $item->getContext() ?: '',
            ])->save();
          }

          // Save the translation.
          $locale_storage->createTranslation([
            'lid' => $source_string->lid,
            'language' => $langcode,
            'translation' => $item->getTranslation(),
          ])->save();

          $imported_count++;
        }

        $reader->close();
      }
      catch (\Exception $e) {
        \Drupal::logger('constructor')->error('Failed to import @file: @message', [
          '@file' => $po_file,
          '@message' => $e->getMessage(),
        ]);
      }
    }

    // Clear locale cache for this language.
    _locale_invalidate_js($langcode);
    \Drupal::cache()->delete('locale:' . $langcode);
  }

  \Drupal::logger('constructor')->notice('Imported @count translation strings.', ['@count' => $imported_count]);
}

/**
 * Batch finished callback for finalize.
 */
function constructor_finalize_batch_finished($success, $results, $operations) {
  if ($success) {
    \Drupal::messenger()->addStatus(t('Configuration applied successfully.'));
  }
  else {
    \Drupal::messenger()->addError(t('Configuration encountered errors.'));
  }
}

/**
 * Batch finished callback for translations.
 */
function constructor_translations_batch_finished($success, $results, $operations) {
  if ($success) {
    \Drupal::messenger()->addStatus(t('Translations updated.'));
  }
}

/**
 * Batch callback: Finalize the installation.
 */
function constructor_finalize_installation(&$install_state) {
  $operations = [];

  // Apply saved configurations from the wizard.
  // Note: Languages are applied in LanguagesForm submit handler.
  $operations[] = ['constructor_apply_content_types', []];
  $operations[] = ['constructor_apply_modules', []];
  $operations[] = ['constructor_apply_layout', []];
  $operations[] = ['constructor_apply_ai_settings', []];
  $operations[] = ['constructor_clear_caches', []];

  $batch = [
    'title' => t('Finalizing installation'),
    'operations' => $operations,
    'finished' => 'constructor_finalize_finished',
  ];

  return $batch;
}

/**
 * Batch operation: Apply language configuration.
 *
 * @param array $context
 *   The batch context.
 * @param array $constructor_settings
 *   The constructor settings from install_state.
 */
function constructor_apply_languages(&$context, array $constructor_settings = []) {
  $context['message'] = t('Configuring languages...');

  \Drupal::logger('constructor')->notice('=== constructor_apply_languages STARTED ===');

  $language_settings = $constructor_settings['languages'] ?? [];
  $additional_languages = $language_settings['additional_languages'] ?? [];
  $default_language = $language_settings['default_language'] ?? 'en';
  $enable_multilingual = !empty($language_settings['enable_multilingual']);

  \Drupal::logger('constructor')->notice('Language settings: default=@default, enable_multilingual=@multi, additional_count=@count, additional=@additional', [
    '@default' => $default_language,
    '@multi' => $enable_multilingual ? 'TRUE' : 'FALSE',
    '@count' => count($additional_languages),
    '@additional' => implode(', ', $additional_languages),
  ]);

  // Filter additional_languages to only valid langcodes.
  $additional_languages = array_filter($additional_languages, function ($langcode) use ($default_language) {
    return !empty($langcode) && $langcode !== $default_language;
  });

  // Check if we need multilingual support.
  $needs_multilingual = $enable_multilingual || !empty($additional_languages) || $default_language !== 'en';

  \Drupal::logger('constructor')->notice('needs_multilingual = @needs (enable_multilingual=@m, additional_count=@c, default_not_en=@d)', [
    '@needs' => $needs_multilingual ? 'TRUE' : 'FALSE',
    '@m' => $enable_multilingual ? 'TRUE' : 'FALSE',
    '@c' => count($additional_languages),
    '@d' => $default_language !== 'en' ? 'TRUE' : 'FALSE',
  ]);

  if ($needs_multilingual) {
    \Drupal::logger('constructor')->notice('Entering multilingual setup block...');
    try {
      // NOTE: We only install 'language' and 'locale' here.
      // 'content_translation' is installed LAST in PHASE 6 to avoid
      // entityBundleInfoAlter hook errors during content creation.
      $modules_to_enable = ['language'];

      if (!empty($language_settings['enable_interface_translation'])) {
        $modules_to_enable[] = 'locale';
      }

      // Enable language modules (NOT content_translation - that comes later).
      /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
      $module_installer = \Drupal::service('module_installer');
      $module_installer->install($modules_to_enable);

      \Drupal::logger('constructor')->notice('Installed language modules (content_translation deferred): @modules', [
        '@modules' => implode(', ', $modules_to_enable),
      ]);

      // Rebuild container IMMEDIATELY to ensure language entity type is available.
      \Drupal::service('kernel')->rebuildContainer();

      // Clear all caches to ensure entity types are registered.
      drupal_flush_all_caches();

      // Reset the entity type manager to pick up new entity types.
      \Drupal::entityTypeManager()->clearCachedDefinitions();

      // Create and set the default language if it's not English.
      if ($default_language !== 'en') {
        // Use ConfigurableLanguage static methods directly.
        $language_entity = \Drupal\language\Entity\ConfigurableLanguage::load($default_language);

        if (!$language_entity) {
          $language_entity = \Drupal\language\Entity\ConfigurableLanguage::createFromLangcode($default_language);
          $language_entity->save();
          \Drupal::logger('constructor')->notice('Created language entity: @lang', ['@lang' => $default_language]);
        }

        // Update system.site default_langcode.
        \Drupal::configFactory()
          ->getEditable('system.site')
          ->set('default_langcode', $default_language)
          ->save();

        \Drupal::logger('constructor')->notice('Set default language to: @lang', ['@lang' => $default_language]);
      }

      // Add additional languages.
      if (!empty($additional_languages)) {
        foreach ($additional_languages as $langcode) {
          if (empty($langcode) || $langcode === $default_language) {
            continue;
          }

          // Use ConfigurableLanguage static methods directly.
          $language_entity = \Drupal\language\Entity\ConfigurableLanguage::load($langcode);

          if (!$language_entity) {
            $language_entity = \Drupal\language\Entity\ConfigurableLanguage::createFromLangcode($langcode);
            $language_entity->save();
            \Drupal::logger('constructor')->notice('Added language: @lang', ['@lang' => $langcode]);
          }
        }
      }

      // Clear caches to apply all changes.
      drupal_flush_all_caches();

      \Drupal::logger('constructor')->notice('=== Multilingual setup completed successfully ===');
    }
    catch (\Exception $e) {
      \Drupal::logger('constructor')->error('Failed to configure languages: @message', [
        '@message' => $e->getMessage(),
      ]);
    }
  }
  else {
    \Drupal::logger('constructor')->notice('Skipping multilingual setup - not needed');
  }

  \Drupal::logger('constructor')->notice('=== constructor_apply_languages ENDED ===');
  $context['results'][] = 'languages';
}

/**
 * Batch operation: Apply content types configuration.
 *
 * @param array $context
 *   The batch context.
 * @param array $constructor_settings
 *   The constructor settings from install_state.
 */
function constructor_apply_content_types(&$context, array $constructor_settings = []) {
  $context['message'] = t('Creating content types...');

  $config = $constructor_settings['content_types'] ?? [];

  if (!empty($config)) {
    foreach ($config as $content_type) {
      // Create content type logic will be implemented here.
      _constructor_create_content_type($content_type);
    }
  }

  $context['results'][] = 'content_types';
}

/**
 * Batch operation: Apply modules configuration.
 *
 * @param array $context
 *   The batch context.
 * @param array $constructor_settings
 *   The constructor settings from install_state.
 */
function constructor_apply_modules(&$context, array $constructor_settings = []) {
  $context['message'] = t('Enabling modules...');

  // Always install these modules by default (except contact and media_library).
  $default_modules = [
    'search',
    'media',
    'responsive_image',
    'menu_link_content',
    'openai_provider',
    'simple_metatag',
    'simple_sitemap_generator',
    'constructor_hero',
    'form_sender',
    'contact_form',
  ];

  /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
  $module_installer = \Drupal::service('module_installer');
  $module_installer->install($default_modules);

  \Drupal::logger('constructor')->notice('Installed default modules: @modules', [
    '@modules' => implode(', ', $default_modules),
  ]);

  $context['results'][] = 'modules';
}

/**
 * Batch operation: Apply layout configuration.
 *
 * @param array $context
 *   The batch context.
 * @param array $constructor_settings
 *   The constructor settings from install_state.
 */
function constructor_apply_layout(&$context, array $constructor_settings = []) {
  $context['message'] = t('Configuring layout...');

  $layout = $constructor_settings['layout'] ?? [];

  if (!empty($layout)) {
    // Apply block placements.
    _constructor_apply_block_placements($layout);
  }

  $context['results'][] = 'layout';
}

/**
 * Batch operation: Apply AI settings.
 *
 * @param array $context
 *   The batch context.
 * @param array $constructor_settings
 *   The constructor settings from install_state.
 */
function constructor_apply_ai_settings(&$context, array $constructor_settings = []) {
  $context['message'] = t('Configuring AI integration...');

  $ai_settings = $constructor_settings['ai_settings'] ?? [];

  if (!empty($ai_settings)) {
    // Save OpenAI Provider configuration.
    $config = \Drupal::configFactory()->getEditable('openai_provider.settings');
    if (!empty($ai_settings['api_key'])) {
      $config->set('api_key', $ai_settings['api_key']);
    }
    if (!empty($ai_settings['text_model'])) {
      $config->set('text_model', $ai_settings['text_model']);
    }
    if (!empty($ai_settings['image_model'])) {
      $config->set('image_model', $ai_settings['image_model']);
    }
    if (isset($ai_settings['temperature'])) {
      $config->set('temperature', $ai_settings['temperature']);
    }
    if (isset($ai_settings['max_tokens'])) {
      $config->set('max_tokens', $ai_settings['max_tokens']);
    }
    if (!empty($ai_settings['image_size'])) {
      $config->set('image_size', $ai_settings['image_size']);
    }
    if (!empty($ai_settings['image_quality'])) {
      $config->set('image_quality', $ai_settings['image_quality']);
    }
    $config->save();
  }

  $context['results'][] = 'ai_settings';
}

/**
 * Batch operation: Clear caches.
 */
function constructor_clear_caches(&$context) {
  $context['message'] = t('Clearing caches...');
  drupal_flush_all_caches();
  $context['results'][] = 'caches';
}

/**
 * Batch finished callback.
 */
function constructor_finalize_finished($success, $results, $operations) {
  if ($success) {
    \Drupal::messenger()->addStatus(t('Installation completed successfully.'));
  }
  else {
    \Drupal::messenger()->addError(t('Installation encountered errors.'));
  }
}

/**
 * Helper function to create a content type.
 */
function _constructor_create_content_type($config) {
  $entity_type_manager = \Drupal::entityTypeManager();
  $storage = $entity_type_manager->getStorage('node_type');

  // Check if content type already exists.
  if ($storage->load($config['type'])) {
    return;
  }

  $content_type = $storage->create([
    'type' => $config['type'],
    'name' => $config['name'],
    'description' => $config['description'] ?? '',
  ]);
  $content_type->save();

  // Create fields if defined.
  if (!empty($config['fields'])) {
    foreach ($config['fields'] as $field_config) {
      _constructor_create_field($config['type'], $field_config);
    }
  }
}

/**
 * Helper function to create a field.
 */
function _constructor_create_field($bundle, $field_config) {
  $entity_type_manager = \Drupal::entityTypeManager();

  // Create field storage if it doesn't exist.
  $field_storage_config = $entity_type_manager->getStorage('field_storage_config');
  $field_name = $field_config['field_name'];

  if (!$field_storage_config->load("node.$field_name")) {
    $field_storage = $field_storage_config->create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => $field_config['type'],
      'cardinality' => $field_config['cardinality'] ?? 1,
    ]);
    $field_storage->save();
  }

  // Create field instance.
  $field_config_storage = $entity_type_manager->getStorage('field_config');
  if (!$field_config_storage->load("node.$bundle.$field_name")) {
    $field = $field_config_storage->create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'bundle' => $bundle,
      'label' => $field_config['label'],
      'required' => $field_config['required'] ?? FALSE,
    ]);
    $field->save();

    // Configure form display.
    $form_display = $entity_type_manager->getStorage('entity_form_display')
      ->load("node.$bundle.default");
    if (!$form_display) {
      $form_display = $entity_type_manager->getStorage('entity_form_display')
        ->create([
          'targetEntityType' => 'node',
          'bundle' => $bundle,
          'mode' => 'default',
          'status' => TRUE,
        ]);
    }
    $form_display->setComponent($field_name, [
      'type' => $field_config['widget'] ?? 'string_textfield',
    ])->save();

    // Configure view display.
    $view_display = $entity_type_manager->getStorage('entity_view_display')
      ->load("node.$bundle.default");
    if (!$view_display) {
      $view_display = $entity_type_manager->getStorage('entity_view_display')
        ->create([
          'targetEntityType' => 'node',
          'bundle' => $bundle,
          'mode' => 'default',
          'status' => TRUE,
        ]);
    }
    $view_display->setComponent($field_name, [
      'type' => $field_config['formatter'] ?? 'string',
    ])->save();
  }
}

/**
 * Helper function to apply block placements.
 */
function _constructor_apply_block_placements($layout) {
  $block_storage = \Drupal::entityTypeManager()->getStorage('block');

  foreach ($layout as $region => $blocks) {
    $weight = 0;
    foreach ($blocks as $block_id => $block_config) {
      $block = $block_storage->load($block_id);
      if ($block) {
        $block->setRegion($region);
        $block->setWeight($weight);
        $block->save();
        $weight++;
      }
    }
  }
}

/**
 * Enable translation for a taxonomy vocabulary.
 *
 * @param string $vocabulary_id
 *   The vocabulary machine name.
 *
 * @return bool
 *   TRUE if successful, FALSE otherwise.
 */
function _constructor_enable_taxonomy_translation(string $vocabulary_id): bool {
  // Check if content_translation module is enabled.
  if (!\Drupal::moduleHandler()->moduleExists('content_translation')) {
    \Drupal::logger('constructor')->notice('Content translation module not enabled, skipping @vocab translation setup.', ['@vocab' => $vocabulary_id]);
    return FALSE;
  }

  try {
    $config = \Drupal::configFactory()->getEditable("language.content_settings.taxonomy_term.$vocabulary_id");
    $config->set('id', "taxonomy_term.$vocabulary_id");
    $config->set('langcode', 'en');
    $config->set('status', TRUE);
    $config->set('target_entity_type_id', 'taxonomy_term');
    $config->set('target_bundle', $vocabulary_id);
    $config->set('default_langcode', 'site_default');
    $config->set('language_alterable', TRUE);
    $config->set('third_party_settings.content_translation.enabled', TRUE);
    $config->save();

    // Clear entity and field definition caches.
    \Drupal::entityTypeManager()->clearCachedDefinitions();
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    \Drupal::cache('config')->deleteAll();

    \Drupal::logger('constructor')->notice('Enabled translation for taxonomy vocabulary @vocab.', ['@vocab' => $vocabulary_id]);
    return TRUE;
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->error('Failed to enable translation for taxonomy @vocab: @message', [
      '@vocab' => $vocabulary_id,
      '@message' => $e->getMessage(),
    ]);
    return FALSE;
  }
}

/**
 * Batch operation: Translate taxonomy terms.
 *
 * @param array $constructor_settings
 *   Constructor settings array.
 * @param string $vocabulary_id
 *   The vocabulary machine name.
 * @param array $context
 *   Batch context.
 */
function constructor_batch_translate_taxonomy($constructor_settings, $vocabulary_id, &$context) {
  $ai_settings = $constructor_settings['ai_settings'] ?? [];
  $languages = $constructor_settings['languages'] ?? [];

  $default_language = $languages['default_language'] ?? 'en';
  $additional_languages = $languages['additional_languages'] ?? [];
  $additional_languages = array_filter($additional_languages, function ($langcode) use ($default_language) {
    return !empty($langcode) && $langcode !== $default_language;
  });

  if (empty($additional_languages) || empty($ai_settings['api_key'])) {
    $context['results'][] = $vocabulary_id . '_taxonomy_translation_skipped';
    $context['finished'] = 1;
    return;
  }

  // Initialize on first call.
  if (!isset($context['sandbox']['initialized'])) {
    // Enable taxonomy translation for this vocabulary.
    _constructor_enable_taxonomy_translation($vocabulary_id);

    $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $tids = $term_storage->getQuery()
      ->condition('vid', $vocabulary_id)
      ->accessCheck(FALSE)
      ->execute();

    $context['sandbox']['tids'] = array_values($tids);
    $context['sandbox']['languages'] = array_values($additional_languages);
    $context['sandbox']['ai_settings'] = $ai_settings;
    $context['sandbox']['vocabulary_id'] = $vocabulary_id;
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['max'] = count($tids) * count($additional_languages);
    $context['sandbox']['current_term'] = 0;
    $context['sandbox']['current_lang'] = 0;
    $context['sandbox']['initialized'] = TRUE;

    if ($context['sandbox']['max'] == 0) {
      $context['finished'] = 1;
      $context['results'][] = $vocabulary_id . '_taxonomy_translation_skipped';
      return;
    }

    $context['finished'] = 0;
    return;
  }

  // Process one term+language combination per call.
  $tids = $context['sandbox']['tids'];
  $langs = $context['sandbox']['languages'];
  $term_index = $context['sandbox']['current_term'];
  $lang_index = $context['sandbox']['current_lang'];

  if ($term_index < count($tids)) {
    $tid = $tids[$term_index];
    $langcode = $langs[$lang_index];

    $context['message'] = t('Translating @vocab term @term to @lang...', [
      '@vocab' => $context['sandbox']['vocabulary_id'],
      '@term' => $term_index + 1,
      '@lang' => $langcode,
    ]);

    try {
      $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
      $term = $term_storage->load($tid);

      if ($term && !$term->hasTranslation($langcode)) {
        // Get the term name to translate.
        $original_name = $term->getName();

        // Translate the term name using AI.
        $translated_name = _constructor_translate_taxonomy_term_with_ai(
          $original_name,
          $langcode,
          $context['sandbox']['ai_settings']
        );

        if ($translated_name) {
          // Create the translation.
          $translation = $term->addTranslation($langcode, [
            'name' => $translated_name,
          ]);
          $translation->save();

          // Generate Pathauto alias for the translation.
          _constructor_generate_pathauto_alias($term, $langcode);

          \Drupal::logger('constructor')->notice('Translated term "@name" to @lang: "@translated"', [
            '@name' => $original_name,
            '@lang' => $langcode,
            '@translated' => $translated_name,
          ]);
        }
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('constructor')->error('Failed to translate taxonomy term: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    // Move to next language or next term.
    $context['sandbox']['current_lang']++;
    if ($context['sandbox']['current_lang'] >= count($langs)) {
      $context['sandbox']['current_lang'] = 0;
      $context['sandbox']['current_term']++;
    }
    $context['sandbox']['progress']++;
  }

  // Calculate progress.
  if ($context['sandbox']['max'] > 0) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
  else {
    $context['finished'] = 1;
  }

  if ($context['finished'] >= 1) {
    $context['results'][] = $context['sandbox']['vocabulary_id'] . '_taxonomy_translated';
    \Drupal::logger('constructor')->notice('Completed taxonomy translation for @vocab: @count terms.', [
      '@vocab' => $context['sandbox']['vocabulary_id'],
      '@count' => $context['sandbox']['current_term'],
    ]);
  }
}

/**
 * Translate a taxonomy term name using AI.
 *
 * @param string $term_name
 *   The original term name.
 * @param string $langcode
 *   The target language code.
 * @param array $ai_settings
 *   AI settings including api_key and text_model.
 *
 * @return string|null
 *   The translated term name or NULL on failure.
 */
function _constructor_translate_taxonomy_term_with_ai(string $term_name, string $langcode, array $ai_settings): ?string {
  $api_key = $ai_settings['api_key'] ?? '';
  if (empty($api_key)) {
    return NULL;
  }

  $language_names = [
    'en' => 'English',
    'uk' => 'Ukrainian',
    'de' => 'German',
    'fr' => 'French',
    'es' => 'Spanish',
    'it' => 'Italian',
    'pl' => 'Polish',
    'pt' => 'Portuguese',
    'nl' => 'Dutch',
    'ja' => 'Japanese',
    'zh' => 'Chinese',
    'ko' => 'Korean',
    'ar' => 'Arabic',
    'ru' => 'Russian',
  ];
  $language_name = $language_names[$langcode] ?? $langcode;

  $prompt = "Translate the following product category name to $language_name. Only return the translated text, nothing else:\n\n\"$term_name\"";

  try {
    $client = \Drupal::httpClient();
    $response = $client->post('https://api.openai.com/v1/chat/completions', [
      'headers' => [
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
      ],
      'json' => [
        'model' => $ai_settings['text_model'] ?? 'gpt-4o-mini',
        'messages' => [
          ['role' => 'user', 'content' => $prompt],
        ],
        'temperature' => 0.3,
        'max_tokens' => 100,
      ],
      'timeout' => 30,
    ]);

    $data = json_decode($response->getBody()->getContents(), TRUE);
    $translated = $data['choices'][0]['message']['content'] ?? '';
    $translated = trim($translated, " \t\n\r\0\x0B\"'");

    if (!empty($translated)) {
      return $translated;
    }
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->error('AI translation error for term "@name": @message', [
      '@name' => $term_name,
      '@message' => $e->getMessage(),
    ]);
  }

  return NULL;
}
