<?php

namespace Drupal\constructor\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for completing post-installation setup.
 *
 * This runs in a fresh HTTP request after installation, ensuring all
 * entity types are fully registered before enabling content translation.
 */
class CompleteSetupController extends ControllerBase {

  /**
   * Completes the post-installation setup.
   */
  public function completeSetup() {
    $state = \Drupal::state();
    $needs_setup = $state->get('constructor.needs_post_install_setup', FALSE);

    if (!$needs_setup) {
      return new RedirectResponse('/');
    }

    $constructor_settings = $state->get('constructor.post_install_settings', []);

    // Run translations in fresh request.
    $this->runTranslations($constructor_settings);

    // Clear flags.
    $state->delete('constructor.needs_post_install_setup');
    $state->delete('constructor.post_install_settings');

    \Drupal::logger('constructor')->notice('Post-installation setup completed.');
    \Drupal::messenger()->addStatus($this->t('Site setup completed. Content has been translated.'));

    return new RedirectResponse('/');
  }

  /**
   * Runs content translations.
   */
  protected function runTranslations(array $settings) {
    $content_type_modules = $settings['content_type_modules'] ?? [];
    $ai_settings = $settings['ai_settings'] ?? [];
    $site_basics = $settings['site_basics'] ?? [];
    $languages = $settings['languages'] ?? [];

    if (empty($ai_settings['api_key'])) {
      \Drupal::logger('constructor')->notice('Translations skipped: No API key.');
      return;
    }

    if (!\Drupal::moduleHandler()->moduleExists('content_translation')) {
      \Drupal::logger('constructor')->notice('Translations skipped: content_translation not installed.');
      return;
    }

    $default_language = $languages['default_language'] ?? 'en';
    $additional_languages = $languages['additional_languages'] ?? [];
    $additional_languages = array_filter($additional_languages, function ($langcode) use ($default_language) {
      return !empty($langcode) && $langcode !== $default_language;
    });

    if (empty($additional_languages)) {
      \Drupal::logger('constructor')->notice('Translations skipped: No additional languages.');
      return;
    }

    $site_description = $site_basics['site_description'] ?? '';

    // Translate FAQ nodes.
    if (in_array('content_faq', $content_type_modules)) {
      $this->translateFaqNodes($additional_languages, $site_description, $ai_settings);
    }

    // Translate Team nodes.
    if (in_array('content_team', $content_type_modules)) {
      $this->translateTeamNodes($additional_languages, $site_description, $ai_settings);
    }
  }

  /**
   * Translates FAQ nodes.
   */
  protected function translateFaqNodes(array $languages, string $site_description, array $ai_settings) {
    // Enable translation for FAQ - THIS IS THE KEY PART.
    // We do this in fresh request where entity types are registered.
    if (!$this->enableContentTranslation('faq')) {
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
        if ($this->translateFaqNode($node, $languages, $ai_settings)) {
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
   * Translates Team nodes.
   */
  protected function translateTeamNodes(array $languages, string $site_description, array $ai_settings) {
    if (!$this->enableContentTranslation('team_member')) {
      return;
    }

    try {
      // Reset the node storage to get fresh instances.
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
        if ($this->translateTeamNode($node, $languages, $ai_settings)) {
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
   * Enables content translation for a bundle.
   */
  protected function enableContentTranslation(string $bundle): bool {
    try {
      $config = \Drupal::configFactory()->getEditable("language.content_settings.node.$bundle");
      // IMPORTANT: The 'id' key is required by ContentLanguageSettings entity.
      // Format is: {entity_type}.{bundle}
      $config->set('id', "node.$bundle");
      $config->set('langcode', 'en');
      $config->set('status', TRUE);
      $config->set('target_entity_type_id', 'node');
      $config->set('target_bundle', $bundle);
      $config->set('default_langcode', 'site_default');
      $config->set('language_alterable', TRUE);
      $config->set('third_party_settings.content_translation.enabled', TRUE);
      $config->save();

      // CRITICAL: Clear entity and field definition caches so nodes recognize
      // they are translatable. Without this, isTranslatable() returns FALSE.
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
   * Translates a single FAQ node.
   */
  protected function translateFaqNode($node, array $languages, array $ai_settings): bool {
    // Skip isTranslatable() check - we just enabled translation for this bundle
    // and caches may not be fully updated. Just try to translate.

    $api_key = $ai_settings['api_key'];
    $model = $ai_settings['text_model'] ?? 'gpt-4';
    $original_question = $node->getTitle();
    $original_answer = $node->get('field_faq_answer')->value;

    $language_names = $this->getLanguageNames();
    $translated = FALSE;

    foreach ($languages as $langcode) {
      if ($langcode === $node->language()->getId()) {
        continue;
      }

      // Check if translation already exists
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

        $translation_data = $this->callOpenAI($api_key, $model, $prompt);

        if (!empty($translation_data['question']) && !empty($translation_data['answer'])) {
          $node->addTranslation($langcode, [
            'title' => $translation_data['question'],
            'field_faq_answer' => [
              'value' => $translation_data['answer'],
              'format' => 'basic_html',
            ],
          ]);
          $node->save();
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
   * Translates a single Team node.
   */
  protected function translateTeamNode($node, array $languages, array $ai_settings): bool {
    // Skip isTranslatable() check - we just enabled translation for this bundle
    // and caches may not be fully updated. Just try to translate.

    $api_key = $ai_settings['api_key'];
    $model = $ai_settings['text_model'] ?? 'gpt-4';
    $original_name = $node->getTitle();
    $original_position = $node->get('field_team_position')->value ?? '';

    $language_names = $this->getLanguageNames();
    $translated = FALSE;

    foreach ($languages as $langcode) {
      if ($langcode === $node->language()->getId()) {
        continue;
      }

      // Check if translation already exists
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
        \Drupal::logger('constructor')->notice('Starting Team translation for @name to @lang', [
          '@name' => $original_name,
          '@lang' => $langcode,
        ]);

        $prompt = "Translate the following job position to $language_name:\n\nPosition: $original_position\n\nReturn as JSON with 'position' key. Only return the JSON.";

        $translation_data = $this->callOpenAI($api_key, $model, $prompt);

        if (!empty($translation_data['position'])) {
          $node->addTranslation($langcode, [
            'title' => $original_name,
            'field_team_position' => $translation_data['position'],
          ]);
          $node->save();
          $translated = TRUE;
          \Drupal::logger('constructor')->notice('Translated Team to @lang: @name', [
            '@lang' => $langcode,
            '@name' => $original_name,
          ]);
        }
        else {
          \Drupal::logger('constructor')->warning('No translation returned for Team @name', ['@name' => $original_name]);
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
   * Calls OpenAI API.
   */
  protected function callOpenAI(string $api_key, string $model, string $prompt): array {
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
   * Gets language names.
   */
  protected function getLanguageNames(): array {
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

}
