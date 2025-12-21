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

    // Step 8: Modules.
    $tasks['constructor_install_modules'] = [
      'display_name' => t('Modules'),
      'display' => TRUE,
      'type' => 'form',
      'function' => 'Drupal\constructor\Form\ModulesForm',
    ];

    // Step 9: Design & Layout.
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

    // Update translations step.
    $tasks['constructor_update_translations'] = [
      'display_name' => t('Updating Translations'),
      'display' => TRUE,
      'type' => 'batch',
      'function' => 'constructor_update_translations_batch',
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
    'languages' => $key_value->get('languages', []),
    'content_types' => $key_value->get('content_types', []),
    'modules' => $key_value->get('modules', []),
    'modules_to_enable' => $key_value->get('modules_to_enable', []),
    'layout' => $key_value->get('layout', []),
    'ai_settings' => $key_value->get('ai_settings', []),
  ];

  $operations = [];

  // Apply languages.
  $operations[] = ['constructor_batch_apply_languages', [$constructor_settings]];

  // Apply content types.
  $operations[] = ['constructor_batch_apply_content_types', [$constructor_settings]];

  // Apply modules.
  $operations[] = ['constructor_batch_apply_modules', [$constructor_settings]];

  // Apply layout.
  $operations[] = ['constructor_batch_apply_layout', [$constructor_settings]];

  // Apply AI settings.
  $operations[] = ['constructor_batch_apply_ai_settings', [$constructor_settings]];

  // Set default theme.
  $operations[] = ['constructor_batch_set_default_theme', []];

  // Clear caches.
  $operations[] = ['constructor_batch_clear_caches', []];

  return [
    'title' => t('Applying Configuration'),
    'operations' => $operations,
    'finished' => 'constructor_finalize_batch_finished',
  ];
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
  constructor_apply_languages($context, $constructor_settings);
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
 * Batch operation: Clear caches.
 */
function constructor_batch_clear_caches(&$context) {
  $context['message'] = t('Clearing caches...');
  drupal_flush_all_caches();
  $context['results'][] = 'caches';
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

  $language_settings = $constructor_settings['languages'] ?? [];

  if (!empty($language_settings['enable_multilingual'])) {
    $modules_to_enable = ['language'];

    if (!empty($language_settings['enable_content_translation'])) {
      $modules_to_enable[] = 'content_translation';
    }

    if (!empty($language_settings['enable_interface_translation'])) {
      $modules_to_enable[] = 'locale';
    }

    // Enable language modules.
    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
    $module_installer = \Drupal::service('module_installer');
    $module_installer->install($modules_to_enable);

    // Add additional languages.
    if (!empty($language_settings['additional_languages'])) {
      foreach ($language_settings['additional_languages'] as $langcode) {
        // Skip if it's the default language.
        if ($langcode === $language_settings['default_language']) {
          continue;
        }
        // Create the language if it doesn't exist.
        if (!\Drupal::languageManager()->getLanguage($langcode)) {
          $language = \Drupal\language\Entity\ConfigurableLanguage::createFromLangcode($langcode);
          $language->save();
        }
      }
    }
  }

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

  // Get the flat array of module names to enable.
  $modules = $constructor_settings['modules_to_enable'] ?? [];

  if (!empty($modules) && is_array($modules)) {
    // Filter to only string values (module names).
    $modules = array_filter($modules, 'is_string');

    if (!empty($modules)) {
      /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
      $module_installer = \Drupal::service('module_installer');
      $module_installer->install($modules);
    }
  }

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
