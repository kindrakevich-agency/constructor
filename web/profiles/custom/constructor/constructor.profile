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

  // Apply languages.
  $operations[] = ['constructor_batch_apply_languages', [$constructor_settings]];

  // Install language switcher module if multiple languages.
  $operations[] = ['constructor_batch_install_language_switcher', [$constructor_settings]];

  // Apply content type modules (like content_faq).
  $operations[] = ['constructor_batch_apply_content_type_modules', [$constructor_settings]];

  // Apply content types.
  $operations[] = ['constructor_batch_apply_content_types', [$constructor_settings]];

  // Apply modules.
  $operations[] = ['constructor_batch_apply_modules', [$constructor_settings]];

  // CRITICAL: Rebuild container after all module installations.
  // This ensures entity types are properly registered before using them.
  $operations[] = ['constructor_batch_rebuild_container', []];

  // Apply layout.
  $operations[] = ['constructor_batch_apply_layout', [$constructor_settings]];

  // Apply AI settings.
  $operations[] = ['constructor_batch_apply_ai_settings', [$constructor_settings]];

  // Set default theme FIRST (before placing blocks).
  $operations[] = ['constructor_batch_set_default_theme', []];

  // Configure theme settings (dark mode, color scheme).
  $operations[] = ['constructor_batch_configure_theme_settings', [$constructor_settings]];

  // Create main menu links.
  $operations[] = ['constructor_batch_create_main_menu', [$constructor_settings]];

  // Create full_html text format (needed before AI content).
  $operations[] = ['constructor_batch_create_full_html_format', []];

  // Generate AI content (FAQ nodes).
  $operations[] = ['constructor_batch_generate_ai_content', [$constructor_settings]];

  // Place content blocks on frontpage (after theme is set).
  $operations[] = ['constructor_batch_place_content_blocks', [$constructor_settings]];

  // Place language switcher block in header (after theme is set).
  $operations[] = ['constructor_batch_place_language_switcher_block', [$constructor_settings]];

  // Configure frontpage.
  $operations[] = ['constructor_batch_configure_frontpage', [$constructor_settings]];

  // Configure development settings.
  $operations[] = ['constructor_batch_configure_development_settings', []];

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
 * Note: Block placement during installation can cause issues with
 * content_translation module hooks. We skip automatic block placement
 * and let users place blocks manually via the Block Layout UI.
 */
function constructor_batch_place_content_blocks($constructor_settings, &$context) {
  $context['message'] = t('Registering content blocks...');

  $content_type_modules = $constructor_settings['content_type_modules'] ?? [];

  \Drupal::logger('constructor')->notice('Content type modules for blocks: @modules', [
    '@modules' => implode(', ', $content_type_modules),
  ]);

  // Skip block placement during installation to avoid entity type issues.
  // Blocks will be available in Block Layout UI for manual placement.
  // This avoids the content_translation hook triggering before entity types are ready.
  \Drupal::logger('constructor')->notice('Block placement skipped during installation. Use Block Layout UI to place FAQ and Team blocks.');

  $context['results'][] = 'content_blocks_registered';
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

      // Enable translation for FAQ content type if we have additional languages.
      $translation_enabled = FALSE;
      if ($has_translations) {
        $translation_enabled = _constructor_enable_faq_translation();
      }

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

        // Create translations if we have additional languages and translation was enabled.
        if ($has_translations && $translation_enabled) {
          _constructor_translate_faq_node($node, $additional_languages, $site_description, $ai_settings);
        }
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
 *
 * NOTE: Content translation is disabled during installation to avoid
 * entity type registration issues. This function is kept for future use.
 */
function _constructor_enable_faq_translation() {
  // Content translation is not enabled during installation.
  \Drupal::logger('constructor')->notice('FAQ translation setup skipped during installation.');
  return FALSE;
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

Return the response as a JSON array with objects containing 'name' and 'position' keys. Example format:
[
  {\"name\": \"Full Name\", \"position\": \"Job Title\"},
  ...
]

Make the names and positions realistic and diverse. Only return the JSON array, no other text.";

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
 * Translate FAQ node to additional languages.
 *
 * NOTE: Content translation is disabled during installation to avoid
 * entity type registration issues. This function is kept for future use.
 */
function _constructor_translate_faq_node($node, $languages, $site_description, $ai_settings) {
  // Content translation is not enabled during installation.
  \Drupal::logger('constructor')->notice('FAQ node translation skipped during installation.');
  return;

  // The code below is kept for future reference but not executed.
  $unused_code = FALSE;
  if ($unused_code) {

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
  ];

  foreach ($languages as $langcode) {
    if ($langcode === $node->language()->getId()) {
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
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('constructor')->error('Translation error for @lang: @message', [
        '@lang' => $langcode,
        '@message' => $e->getMessage(),
      ]);
    }
  }
  } // End of unused code block.
}

/**
 * Batch operation: Place language switcher block in header.
 *
 * Note: Block placement during installation can cause issues with
 * content_translation module hooks. We skip automatic block placement.
 */
function constructor_batch_place_language_switcher_block($constructor_settings, &$context) {
  $context['message'] = t('Registering language switcher...');

  // Check if language_switcher module is installed.
  if (!\Drupal::moduleHandler()->moduleExists('language_switcher')) {
    \Drupal::logger('constructor')->notice('Language switcher module not installed.');
    $context['results'][] = 'language_switcher_block_skipped';
    return;
  }

  // Skip block placement during installation to avoid entity type issues.
  \Drupal::logger('constructor')->notice('Language switcher block placement skipped during installation. Use Block Layout UI to place it.');

  $context['results'][] = 'language_switcher_block';
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
 * Batch operation: Clear caches.
 */
function constructor_batch_clear_caches(&$context) {
  $context['message'] = t('Clearing caches...');

  try {
    // Use a simpler cache clear that doesn't trigger route rebuilding.
    // Full cache flush can cause issues with content_translation hooks.
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
      $modules_to_enable = ['language'];

      // NOTE: content_translation is NOT enabled during installation to avoid
      // entity type registration issues. Users can enable it manually after
      // installation via /admin/modules if needed.

      if (!empty($language_settings['enable_interface_translation'])) {
        $modules_to_enable[] = 'locale';
      }

      // Enable language modules.
      /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
      $module_installer = \Drupal::service('module_installer');
      $module_installer->install($modules_to_enable);

      \Drupal::logger('constructor')->notice('Installed language modules: @modules', [
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
