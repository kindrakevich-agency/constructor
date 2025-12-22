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

  // Map Drupal install tasks to our 9-step wizard.
  // Steps: 1-Language, 2-Database, 3-Site Basics, 4-Languages, 5-Content Types, 6-Modules, 7-Design, 8-AI, 9-Finalize
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
    // Constructor custom wizard steps (steps 3-9).
    'constructor_install_site_basics' => 3,
    'constructor_install_languages' => 4,
    'constructor_install_content_types' => 5,
    'constructor_install_modules' => 6,
    'constructor_install_design_layout' => 7,
    'constructor_install_ai_integration' => 8,
    'constructor_finalize_installation' => 9,
    'constructor_update_translations' => 9,
    'install_finished' => 9,
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

    // Step 8: Design & Layout.
    $tasks['constructor_install_design_layout'] = [
      'display_name' => t('Design & Layout'),
      'display' => TRUE,
      'type' => 'form',
      'function' => 'Drupal\constructor\Form\DesignLayoutForm',
    ];

    // Step 10: AI Integration.
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
    // NOTE: We install the module but DON'T enable translation for content types
    // during this batch. That's done in the post-install controller to avoid
    // entity hook issues.
    $tasks['constructor_install_translation_module'] = [
      'display_name' => t('Installing Translation Module'),
      'display' => TRUE,
      'type' => 'batch',
      'function' => 'constructor_install_translation_module_batch',
    ];

    // Update interface translations step.
    $tasks['constructor_update_translations'] = [
      'display_name' => t('Updating Translations'),
      'display' => TRUE,
      'type' => 'batch',
      'function' => 'constructor_update_translations_batch',
    ];

    // Set up post-install state for content translation.
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

  // Create full_html text format.
  $operations[] = ['constructor_batch_create_full_html_format', []];

  // Configure frontpage.
  $operations[] = ['constructor_batch_configure_frontpage', [$constructor_settings]];

  // Configure development settings.
  $operations[] = ['constructor_batch_configure_development_settings', []];

  // ========== PHASE 4: Final Cache Clear ==========
  // Clear all caches to ensure entity types are fully registered.
  $operations[] = ['constructor_batch_final_cache_clear', []];

  // ========== PHASE 5: AI Content (WITHOUT translations) ==========
  // Generate AI content (FAQ and Team nodes) - NO translations yet.
  $operations[] = ['constructor_batch_generate_ai_content_no_translation', [$constructor_settings]];

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

  // Only set up post-install if we have additional languages and API key.
  $ai_settings = $constructor_settings['ai_settings'] ?? [];
  if (!empty($additional_languages) && !empty($ai_settings['api_key'])) {
    // Set state for post-install controller.
    $state = \Drupal::state();
    $state->set('constructor.needs_post_install_setup', TRUE);
    $state->set('constructor.post_install_settings', $constructor_settings);

    \Drupal::logger('constructor')->notice('Post-install translation setup configured.');

    // Set the redirect destination.
    $install_state['parameters']['destination'] = '/admin/constructor/complete-setup';
  }
  else {
    \Drupal::logger('constructor')->notice('Post-install translation not needed (no additional languages or no API key).');
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

  // Translate existing content - NOW in a new HTTP request.
  $operations[] = ['constructor_batch_translate_existing_content', [$constructor_settings]];

  return [
    'title' => t('Translating Content'),
    'operations' => $operations,
    'finished' => 'constructor_translate_content_finished',
  ];
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

  // Default Unsplash images for team members.
  $unsplash_images = [
    'https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=400&h=500&fit=crop&q=80',
    'https://images.unsplash.com/photo-1560250097-0b93528c311a?w=400&h=500&fit=crop&q=80',
    'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=400&h=500&fit=crop&q=80',
    'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=400&h=500&fit=crop&q=80',
    'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&h=500&fit=crop&q=80',
  ];

  // Default gradients.
  $gradients = [
    'linear-gradient(180deg, #fde4cf 0%, #ffcfd2 100%)',
    'linear-gradient(180deg, #fbc2eb 0%, #a6c1ee 100%)',
    'linear-gradient(180deg, #a1c4fd 0%, #c2e9fb 100%)',
    'linear-gradient(180deg, #d4fc79 0%, #96e6a1 100%)',
    'linear-gradient(180deg, #ffecd2 0%, #fcb69f 100%)',
  ];

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
          'field_team_image_url' => $unsplash_images[$index % count($unsplash_images)],
          'field_team_gradient' => $gradients[$index % count($gradients)],
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
          'field_service_description' => [
            'value' => $service['description'],
            'format' => 'full_html',
          ],
          'field_service_body' => [
            'value' => $service['body'] ?? '',
            'format' => 'full_html',
          ],
          'field_service_image_url' => $unsplash_images[$index % count($unsplash_images)],
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
 * Translate FAQ node to additional languages using AI.
 */
function _constructor_translate_faq_node($node, $languages, $site_description, $ai_settings) {
  // Check if content_translation module is enabled.
  if (!\Drupal::moduleHandler()->moduleExists('content_translation')) {
    \Drupal::logger('constructor')->notice('Content translation module not enabled, skipping FAQ translation.');
    return;
  }

  // Check if node is translatable.
  try {
    if (!$node->isTranslatable()) {
      \Drupal::logger('constructor')->notice('Node @nid is not translatable, skipping.', ['@nid' => $node->id()]);
      return;
    }
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->warning('Could not check translatability: @message', ['@message' => $e->getMessage()]);
    return;
  }

  $api_key = $ai_settings['api_key'];
  $model = $ai_settings['text_model'] ?? 'gpt-4';
  $original_question = $node->getTitle();
  $original_answer = $node->get('field_faq_answer')->value;

  \Drupal::logger('constructor')->notice('Translating FAQ: @title to @langs', [
    '@title' => $original_question,
    '@langs' => implode(', ', $languages),
  ]);

  $language_names = [
    'en' => 'English',
    'uk' => 'Ukrainian',
    'de' => 'German',
    'fr' => 'French',
    'es' => 'Spanish',
    'it' => 'Italian',
    'pl' => 'Polish',
    'pt-pt' => 'Portuguese',
    'nl' => 'Dutch',
    'ru' => 'Russian',
    'ja' => 'Japanese',
    'zh-hans' => 'Chinese (Simplified)',
    'ar' => 'Arabic',
  ];

  foreach ($languages as $langcode) {
    if ($langcode === $node->language()->getId()) {
      continue;
    }

    // Skip if translation already exists.
    if ($node->hasTranslation($langcode)) {
      continue;
    }

    $language_name = $language_names[$langcode] ?? $langcode;

    try {
      $prompt = "Translate the following FAQ to $language_name:

Question: $original_question
Answer: $original_answer

Return as JSON with 'question' and 'answer' keys. Only return the JSON, no other text.";

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

      $translation_data = json_decode($content, TRUE);

      if (!empty($translation_data['question']) && !empty($translation_data['answer'])) {
        $node->addTranslation($langcode, [
          'title' => $translation_data['question'],
          'field_faq_answer' => [
            'value' => $translation_data['answer'],
            'format' => 'basic_html',
          ],
        ]);
        $node->save();
        \Drupal::logger('constructor')->notice('Added @lang translation for FAQ: @title', [
          '@lang' => $langcode,
          '@title' => $original_question,
        ]);
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('constructor')->error('Translation error for @lang: @message', [
        '@lang' => $langcode,
        '@message' => $e->getMessage(),
      ]);
    }
  }
}

/**
 * Translate Team node to additional languages using AI.
 */
function _constructor_translate_team_node($node, $languages, $site_description, $ai_settings) {
  // Check if content_translation module is enabled.
  if (!\Drupal::moduleHandler()->moduleExists('content_translation')) {
    return;
  }

  // Check if node is translatable.
  try {
    if (!$node->isTranslatable()) {
      return;
    }
  }
  catch (\Exception $e) {
    return;
  }

  $api_key = $ai_settings['api_key'];
  $model = $ai_settings['text_model'] ?? 'gpt-4';
  $original_name = $node->getTitle();
  $original_position = $node->get('field_team_position')->value ?? '';

  $language_names = [
    'en' => 'English',
    'uk' => 'Ukrainian',
    'de' => 'German',
    'fr' => 'French',
    'es' => 'Spanish',
    'it' => 'Italian',
    'pl' => 'Polish',
    'pt-pt' => 'Portuguese',
    'nl' => 'Dutch',
    'ru' => 'Russian',
    'ja' => 'Japanese',
    'zh-hans' => 'Chinese (Simplified)',
    'ar' => 'Arabic',
  ];

  foreach ($languages as $langcode) {
    if ($langcode === $node->language()->getId()) {
      continue;
    }

    // Skip if translation already exists.
    if ($node->hasTranslation($langcode)) {
      continue;
    }

    $language_name = $language_names[$langcode] ?? $langcode;

    try {
      // For team members, we only translate the position (name stays the same).
      $prompt = "Translate the following job position to $language_name:

Position: $original_position

Return as JSON with 'position' key. Only return the JSON, no other text.";

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
          'temperature' => 0.3,
          'max_tokens' => 200,
        ],
        'timeout' => 30,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      $content = $data['choices'][0]['message']['content'] ?? '';

      $content = trim($content);
      $content = preg_replace('/^```json\s*/', '', $content);
      $content = preg_replace('/\s*```$/', '', $content);

      $translation_data = json_decode($content, TRUE);

      if (!empty($translation_data['position'])) {
        $node->addTranslation($langcode, [
          'title' => $original_name,
          'field_team_position' => $translation_data['position'],
        ]);
        $node->save();
        \Drupal::logger('constructor')->notice('Added @lang translation for Team member: @name', [
          '@lang' => $langcode,
          '@name' => $original_name,
        ]);
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('constructor')->error('Team translation error for @lang: @message', [
        '@lang' => $langcode,
        '@message' => $e->getMessage(),
      ]);
    }
  }
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

  $layout = $constructor_settings['layout'] ?? [];

  try {
    // Save theme settings to constructor_theme.settings config.
    $config = \Drupal::configFactory()->getEditable('constructor_theme.settings');
    $config->set('enable_dark_mode', !empty($layout['enable_dark_mode']));
    $config->set('color_scheme', $layout['color_scheme'] ?? 'blue');
    $config->save();

    \Drupal::logger('constructor')->notice('Theme settings saved: dark_mode=@dark, color=@color', [
      '@dark' => !empty($layout['enable_dark_mode']) ? 'yes' : 'no',
      '@color' => $layout['color_scheme'] ?? 'blue',
    ]);
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->error('Failed to configure theme settings: @message', ['@message' => $e->getMessage()]);
  }

  $context['results'][] = 'theme_settings';
}

/**
 * Batch operation: Create main menu links.
 */
function constructor_batch_create_main_menu($constructor_settings, &$context) {
  $context['message'] = t('Creating main menu links...');

  $content_type_modules = $constructor_settings['content_type_modules'] ?? [];

  try {
    $menu_link_storage = \Drupal::entityTypeManager()->getStorage('menu_link_content');

    // Create Home link.
    $home_link = $menu_link_storage->create([
      'title' => t('Home'),
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
        'title' => t('FAQ'),
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
        'title' => t('Team'),
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
        'title' => t('Services'),
        'link' => ['uri' => 'internal:/services'],
        'menu_name' => 'main',
        'weight' => 6,
        'expanded' => FALSE,
      ]);
      $services_link->save();
      \Drupal::logger('constructor')->notice('Created Services menu link.');
    }

    // Create Example page link.
    $example_link = $menu_link_storage->create([
      'title' => t('Example'),
      'link' => ['uri' => 'internal:/example'],
      'menu_name' => 'main',
      'weight' => 10,
      'expanded' => FALSE,
    ]);
    $example_link->save();
    \Drupal::logger('constructor')->notice('Created Example menu link.');

    // Note: Block placement skipped during installation to avoid entity type issues.
    // Main menu and branding blocks will be available via Block Layout UI.
    \Drupal::logger('constructor')->notice('Block placement skipped during installation.');
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->error('Failed to create main menu links: @message', ['@message' => $e->getMessage()]);
  }

  $context['results'][] = 'main_menu';
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
 * Batch operation: Generate AI content WITHOUT translation.
 *
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
 * Batch operation: Translate existing content.
 *
 * NOTE: Translation is now handled by CompleteSetupController in a fresh
 * HTTP request to avoid entity hook issues during installation batch.
 * This function is kept for backwards compatibility but does nothing.
 */
function constructor_batch_translate_existing_content($constructor_settings, &$context) {
  $context['message'] = t('Preparing translations...');

  // Translation is deferred to post-install controller to avoid
  // entity hook issues during the installation batch.
  \Drupal::logger('constructor')->notice('Content translation will be completed in post-install setup.');
  $context['results'][] = 'translations_deferred';
}

/**
 * Batch operation: Update translations.
 */
function constructor_batch_update_translations(&$context) {
  $context['message'] = t('Updating translations...');

  // Check if locale module is enabled.
  if (\Drupal::moduleHandler()->moduleExists('locale')) {
    // Update translations from available sources.
    try {
      if (function_exists('locale_translation_batch_status_check')) {
        // Check for updates.
        \Drupal::moduleHandler()->loadInclude('locale', 'bulk.inc');
        \Drupal::moduleHandler()->loadInclude('locale', 'compare.inc');
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('constructor')->notice('Translation update: @message', ['@message' => $e->getMessage()]);
    }
  }

  $context['results'][] = 'translations';
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
