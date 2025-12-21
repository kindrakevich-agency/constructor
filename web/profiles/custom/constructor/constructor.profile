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

  $operations = [];

  // Apply languages.
  $operations[] = ['constructor_batch_apply_languages', [$constructor_settings]];

  // Apply content type modules (like content_faq).
  $operations[] = ['constructor_batch_apply_content_type_modules', [$constructor_settings]];

  // Apply content types.
  $operations[] = ['constructor_batch_apply_content_types', [$constructor_settings]];

  // Apply modules.
  $operations[] = ['constructor_batch_apply_modules', [$constructor_settings]];

  // Apply layout.
  $operations[] = ['constructor_batch_apply_layout', [$constructor_settings]];

  // Apply AI settings.
  $operations[] = ['constructor_batch_apply_ai_settings', [$constructor_settings]];

  // Set default theme FIRST (before placing blocks).
  $operations[] = ['constructor_batch_set_default_theme', []];

  // Generate AI content (FAQ nodes).
  $operations[] = ['constructor_batch_generate_ai_content', [$constructor_settings]];

  // Place content blocks on frontpage (after theme is set).
  $operations[] = ['constructor_batch_place_content_blocks', [$constructor_settings]];

  // Configure frontpage.
  $operations[] = ['constructor_batch_configure_frontpage', [$constructor_settings]];

  // Create full_html text format.
  $operations[] = ['constructor_batch_create_full_html_format', []];

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
  constructor_apply_languages($context, $constructor_settings);
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
 */
function constructor_batch_place_content_blocks($constructor_settings, &$context) {
  $context['message'] = t('Placing content blocks...');

  $content_type_modules = $constructor_settings['content_type_modules'] ?? [];

  \Drupal::logger('constructor')->notice('Content type modules: @modules', ['@modules' => implode(', ', $content_type_modules)]);

  // If content_faq module was installed, place the FAQ block on frontpage.
  if (in_array('content_faq', $content_type_modules)) {
    \Drupal::logger('constructor')->notice('FAQ module detected, placing block...');

    try {
      // Check if content_faq module is actually installed.
      if (!\Drupal::moduleHandler()->moduleExists('content_faq')) {
        \Drupal::logger('constructor')->warning('content_faq module not installed yet.');
        $context['results'][] = 'content_blocks_skipped';
        return;
      }

      $block_storage = \Drupal::entityTypeManager()->getStorage('block');

      // Check if block already exists and update or create it.
      $existing_block = $block_storage->load('constructor_theme_faq_block');
      if ($existing_block) {
        // Update existing block to ensure correct settings.
        $existing_block->setRegion('content');
        $existing_block->setWeight(10);
        $existing_block->enable();
        $existing_block->save();
        \Drupal::logger('constructor')->notice('FAQ block updated: region=content, status=enabled.');
      }
      else {
        // Create the FAQ block placement.
        $block = $block_storage->create([
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
        $block->save();
        \Drupal::logger('constructor')->notice('FAQ block created: region=content, status=enabled.');
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('constructor')->error('Failed to place FAQ block: @message', ['@message' => $e->getMessage()]);
    }
  }
  else {
    \Drupal::logger('constructor')->notice('FAQ module not in content_type_modules, skipping block placement.');
  }

  $context['results'][] = 'content_blocks';
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

  \Drupal::logger('constructor')->notice('Starting AI FAQ generation for site: @desc, language: @lang', [
    '@desc' => $site_description,
    '@lang' => $main_language,
  ]);

  try {
    // Generate FAQ content using OpenAI.
    $faqs = _constructor_generate_faq_with_ai($site_description, $main_language, $ai_settings);

    \Drupal::logger('constructor')->notice('AI returned @count FAQs.', ['@count' => count($faqs)]);

    if (!empty($faqs)) {
      // Create FAQ nodes.
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');

      // Enable translation for FAQ content type if multilingual.
      if (!empty($languages['enable_multilingual']) && !empty($additional_languages)) {
        _constructor_enable_faq_translation();
      }

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
            'format' => 'basic_html',
          ],
          'status' => 1,
          'langcode' => $main_language,
        ]);
        $node->save();

        // Create translations if multilingual.
        if (!empty($languages['enable_multilingual']) && !empty($additional_languages)) {
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

  $context['results'][] = 'ai_content';
}

/**
 * Enable translation for FAQ content type.
 */
function _constructor_enable_faq_translation() {
  // Check if content_translation module is enabled.
  if (!\Drupal::moduleHandler()->moduleExists('content_translation')) {
    \Drupal::logger('constructor')->notice('Content translation module not enabled, skipping FAQ translation setup.');
    return;
  }

  try {
    // Enable translation for the FAQ content type.
    $config = \Drupal::configFactory()->getEditable('language.content_settings.node.faq');
    $config->set('langcode', 'en');
    $config->set('status', TRUE);
    $config->set('target_entity_type_id', 'node');
    $config->set('target_bundle', 'faq');
    $config->set('default_langcode', 'site_default');
    $config->set('language_alterable', TRUE);
    $config->set('third_party_settings.content_translation.enabled', TRUE);
    $config->save();

    \Drupal::logger('constructor')->notice('Enabled translation for FAQ content type.');
  }
  catch (\Exception $e) {
    \Drupal::logger('constructor')->error('Failed to enable FAQ translation: @message', ['@message' => $e->getMessage()]);
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
 * Translate FAQ node to additional languages.
 */
function _constructor_translate_faq_node($node, $languages, $site_description, $ai_settings) {
  // Reload node to get latest translation settings.
  $node_storage = \Drupal::entityTypeManager()->getStorage('node');
  $node = $node_storage->load($node->id());

  if (!$node || !$node->isTranslatable()) {
    \Drupal::logger('constructor')->notice('Node @nid is not translatable, skipping.', ['@nid' => $node ? $node->id() : 'null']);
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
 * Batch operation: Create full_html text format.
 */
function constructor_batch_create_full_html_format(&$context) {
  $context['message'] = t('Creating Full HTML text format...');

  // Check if filter module is enabled and format doesn't exist.
  if (!\Drupal::moduleHandler()->moduleExists('filter')) {
    $context['results'][] = 'full_html_skipped';
    return;
  }

  $format_storage = \Drupal::entityTypeManager()->getStorage('filter_format');
  if ($format_storage->load('full_html')) {
    \Drupal::logger('constructor')->notice('Full HTML format already exists.');
    $context['results'][] = 'full_html_exists';
    return;
  }

  try {
    // Create the full_html format.
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

    // Grant permission to administrator role if it exists.
    $role_storage = \Drupal::entityTypeManager()->getStorage('user_role');
    $admin_role = $role_storage->load('administrator');
    if ($admin_role) {
      $admin_role->grantPermission('use text format full_html');
      $admin_role->save();
    }

    \Drupal::logger('constructor')->notice('Created Full HTML text format.');
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
    // Disable CSS/JS aggregation.
    $system_performance = \Drupal::configFactory()->getEditable('system.performance');
    $system_performance->set('css.preprocess', FALSE);
    $system_performance->set('js.preprocess', FALSE);
    $system_performance->save();
    \Drupal::logger('constructor')->notice('Disabled CSS/JS aggregation.');

    // Disable caching (render cache, dynamic page cache, page cache).
    // Set cache max-age to 0.
    $system_performance->set('cache.page.max_age', 0);
    $system_performance->save();

    // Disable render cache and dynamic page cache via settings.
    // These need to be in settings.php, but we can also use development.services.yml.
    // For now, we'll create/update the development.services.yml and settings.local.php.

    $sites_path = \Drupal::root() . '/sites/default';

    // Create settings.local.php with development settings.
    $settings_local_content = <<<'PHP'
<?php

/**
 * @file
 * Local development settings.
 *
 * This file is auto-generated by the Constructor installation profile.
 */

// Disable render caching.
$settings['cache']['bins']['render'] = 'cache.backend.null';

// Disable Dynamic Page Cache.
$settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';

// Disable Page Cache.
$settings['cache']['bins']['page'] = 'cache.backend.null';

// Enable local development services.
$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/development.services.yml';

// Show all error messages.
$config['system.logging']['error_level'] = 'verbose';

// Disable CSS/JS aggregation.
$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;
PHP;

    file_put_contents($sites_path . '/settings.local.php', $settings_local_content);
    \Drupal::logger('constructor')->notice('Created settings.local.php with development settings.');

    // Enable settings.local.php in settings.php if not already.
    $settings_php_path = $sites_path . '/settings.php';
    $settings_content = file_get_contents($settings_php_path);

    // Check if settings.local.php include is already uncommented.
    if (strpos($settings_content, "include \$app_root . '/' . \$site_path . '/settings.local.php'") === FALSE) {
      // Add the include at the end.
      $include_code = <<<'PHP'

// Load local development settings.
if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}
PHP;
      file_put_contents($settings_php_path, $settings_content . $include_code);
      \Drupal::logger('constructor')->notice('Added settings.local.php include to settings.php.');
    }

    // Create/update development.services.yml with twig debug.
    $dev_services_path = \Drupal::root() . '/sites/development.services.yml';
    $dev_services_content = <<<'YAML'
# Local development services.
#
# Auto-generated by Constructor installation profile.
parameters:
  http.response.debug_cacheability_headers: true
  twig.config:
    debug: true
    auto_reload: true
    cache: false
services:
  cache.backend.null:
    class: Drupal\Core\Cache\NullBackendFactory
YAML;

    file_put_contents($dev_services_path, $dev_services_content);
    \Drupal::logger('constructor')->notice('Created development.services.yml with Twig debug enabled.');

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
