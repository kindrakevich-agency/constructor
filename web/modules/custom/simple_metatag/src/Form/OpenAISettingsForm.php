<?php

namespace Drupal\simple_metatag\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for OpenAI SEO text generation settings.
 */
class OpenAISettingsForm extends FormBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs an OpenAISettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_metatag_openai_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('simple_metatag.openai_settings');

    $form['api_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('OpenAI API Settings'),
      '#open' => TRUE,
    ];

    $form['api_settings']['openai_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OpenAI API Key'),
      '#description' => $this->t('Enter your OpenAI API key. Get one at <a href="@url" target="_blank">OpenAI Platform</a>.', [
        '@url' => 'https://platform.openai.com/api-keys',
      ]),
      '#default_value' => $config->get('api_key'),
      '#maxlength' => 255,
      '#size' => 80,
      '#required' => TRUE,
    ];

    $form['api_settings']['openai_model'] = [
      '#type' => 'select',
      '#title' => $this->t('OpenAI Model'),
      '#description' => $this->t('Select the OpenAI model to use for generating SEO text.'),
      '#options' => [
        'gpt-4o' => 'GPT-4o (Recommended)',
        'gpt-4o-mini' => 'GPT-4o Mini (Faster, cheaper)',
        'gpt-4-turbo' => 'GPT-4 Turbo',
        'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Legacy)',
      ],
      '#default_value' => $config->get('model') ?: 'gpt-4o-mini',
      '#required' => TRUE,
    ];

    $form['api_settings']['node_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of Nodes'),
      '#description' => $this->t('How many latest nodes (with this term) to send to AI for context. More nodes = better context but higher cost.'),
      '#default_value' => $config->get('node_count') ?: 1,
      '#min' => 1,
      '#max' => 10,
      '#required' => TRUE,
    ];

    $form['api_settings']['seo_length'] = [
      '#type' => 'number',
      '#title' => $this->t('SEO Text Length (characters)'),
      '#description' => $this->t('Target length for generated SEO content for category pages. Recommended: 800-1500 characters for full SEO text with headings and paragraphs.'),
      '#default_value' => $config->get('seo_length') ?: 1000,
      '#min' => 200,
      '#max' => 5000,
      '#required' => TRUE,
    ];

    $form['generation_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('SEO Text Generation'),
      '#open' => TRUE,
    ];

    $form['generation_settings']['vocabulary'] = [
      '#type' => 'select',
      '#title' => $this->t('Taxonomy Vocabulary'),
      '#description' => $this->t('Select the taxonomy vocabulary to process.'),
      '#options' => $this->getVocabularyOptions(),
      '#default_value' => $config->get('vocabulary'),
      '#empty_option' => $this->t('- Select vocabulary -'),
    ];

    $form['generation_settings']['term_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Term ID'),
      '#description' => $this->t('Enter a specific term ID to process only that term. Leave blank to process all terms in the selected vocabulary.'),
      '#default_value' => $config->get('term_id'),
      '#size' => 10,
    ];

    $form['generation_settings']['regenerate_existing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Regenerate existing SEO content'),
      '#description' => $this->t('If checked, will regenerate SEO text and meta tags even if they already exist. If unchecked, will skip terms that already have meta title.'),
      '#default_value' => $config->get('regenerate_existing') ?? FALSE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['save_settings'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Settings'),
      '#submit' => ['::saveSettings'],
    ];

    $form['actions']['generate_seo'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create SEO texts for terms'),
      '#submit' => ['::generateSeoTexts'],
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Save settings submit handler.
   */
  public function saveSettings(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('simple_metatag.openai_settings');
    $config->set('api_key', $form_state->getValue('openai_api_key'));
    $config->set('model', $form_state->getValue('openai_model'));
    $config->set('node_count', $form_state->getValue('node_count'));
    $config->set('seo_length', $form_state->getValue('seo_length'));
    $config->set('vocabulary', $form_state->getValue('vocabulary'));
    $config->set('term_id', $form_state->getValue('term_id'));
    $config->set('regenerate_existing', $form_state->getValue('regenerate_existing'));
    $config->save();

    $this->messenger()->addStatus($this->t('OpenAI settings have been saved.'));
  }

  /**
   * Generate SEO texts submit handler.
   */
  public function generateSeoTexts(array &$form, FormStateInterface $form_state) {
    // First, save the settings.
    $this->saveSettings($form, $form_state);

    $vocabulary = $form_state->getValue('vocabulary');
    $term_id = $form_state->getValue('term_id');

    if (empty($vocabulary)) {
      $this->messenger()->addError($this->t('Please select a vocabulary.'));
      return;
    }

    // Get terms to process.
    $terms = [];
    if (!empty($term_id)) {
      // Process single term.
      $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term_id);
      if ($term && $term->bundle() == $vocabulary) {
        $terms[] = $term_id;
      }
      else {
        $this->messenger()->addError($this->t('Invalid term ID or term does not belong to the selected vocabulary.'));
        return;
      }
    }
    else {
      // Process all terms in vocabulary.
      $query = \Drupal::entityQuery('taxonomy_term')
        ->condition('vid', $vocabulary)
        ->accessCheck(FALSE);
      $terms = $query->execute();
    }

    if (empty($terms)) {
      $this->messenger()->addWarning($this->t('No terms found to process.'));
      return;
    }

    // Setup batch operation.
    $batch = [
      'title' => $this->t('Generating SEO texts for taxonomy terms'),
      'operations' => [],
      'finished' => '\Drupal\simple_metatag\Form\OpenAISettingsForm::batchFinished',
      'init_message' => $this->t('Starting SEO text generation...'),
      'progress_message' => $this->t('Processed @current out of @total terms.'),
      'error_message' => $this->t('An error occurred during SEO text generation.'),
    ];

    // Add each term as a batch operation.
    foreach ($terms as $tid) {
      $batch['operations'][] = [
        '\Drupal\simple_metatag\Form\OpenAISettingsForm::batchProcessTerm',
        [$tid],
      ];
    }

    batch_set($batch);
  }

  /**
   * Batch operation callback for processing a single term.
   *
   * @param int $term_id
   *   The term ID to process.
   * @param array $context
   *   The batch context.
   */
  public static function batchProcessTerm($term_id, &$context) {
    $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');

    $term = $term_storage->load($term_id);
    if (!$term) {
      $context['results']['skipped'][] = $term_id;
      return;
    }

    // Get all language versions of this term.
    $languages = $term->getTranslationLanguages();

    // Process each language version separately.
    foreach ($languages as $language) {
      $langcode = $language->getId();

      // Check if this translation actually exists (not just a fallback).
      if (!$term->hasTranslation($langcode)) {
        continue;
      }

      $translated_term = $term->getTranslation($langcode);

      // Skip if this is a fallback to default language when it shouldn't be.
      if ($translated_term->language()->getId() !== $langcode) {
        continue;
      }

      $term_name = $translated_term->getName();
      $term_language = $langcode;

    // Get configuration.
    $config = \Drupal::config('simple_metatag.openai_settings');
    $node_count = $config->get('node_count') ?: 1;
    $regenerate_existing = $config->get('regenerate_existing') ?? FALSE;

    // Check if we should skip this term (already has meta tags and regenerate is off).
    if (!$regenerate_existing) {
      $database = \Drupal::database();
      $existing_meta = $database->select('simple_metatag_entity', 'sme')
        ->fields('sme', ['title'])
        ->condition('entity_type', 'taxonomy_term')
        ->condition('entity_id', $term_id)
        ->condition('langcode', $term_language)
        ->execute()
        ->fetchField();

      if (!empty($existing_meta)) {
        $context['results']['skipped'][] = [
          'term_id' => $term_id,
          'term_name' => $term_name,
          'language' => $term_language,
          'reason' => 'Already has meta tags (regenerate disabled)',
        ];
        $context['message'] = t('Processing term: @name [@lang] (skipped - already exists)', [
          '@name' => $term_name,
          '@lang' => $term_language,
        ]);
        continue;
      }
    }

    // Find the latest nodes attached to this term in the same language.
    $query = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('langcode', $term_language)
      ->accessCheck(FALSE)
      ->sort('created', 'DESC')
      ->range(0, $node_count);

    // Check all taxonomy reference fields.
    $entity_field_manager = \Drupal::service('entity_field.manager');
    $node_fields = $entity_field_manager->getFieldMapByFieldType('entity_reference');

    $conditions = [];
    if (isset($node_fields['node'])) {
      foreach ($node_fields['node'] as $field_name => $field_info) {
        $field_definitions = $entity_field_manager->getFieldDefinitions('node', reset($field_info['bundles']));
        if (isset($field_definitions[$field_name])) {
          $settings = $field_definitions[$field_name]->getSettings();
          if (isset($settings['target_type']) && $settings['target_type'] === 'taxonomy_term') {
            $conditions[] = $field_name;
          }
        }
      }
    }

    // Build OR condition group for all taxonomy fields.
    if (!empty($conditions)) {
      $orGroup = $query->orConditionGroup();
      foreach ($conditions as $field_name) {
        $orGroup->condition($field_name, $term_id);
      }
      $query->condition($orGroup);
    }
    else {
      // No taxonomy fields found, skip this language.
      $context['results']['skipped'][] = $term_id;
      $context['message'] = t('Processing term: @name [@lang] (skipped - no taxonomy fields)', [
        '@name' => $term_name,
        '@lang' => $term_language,
      ]);
      continue;
    }

    $nids = $query->execute();

    // If no nodes found, skip this language.
    if (empty($nids)) {
      $context['results']['skipped'][] = [
        'term_id' => $term_id,
        'term_name' => $term_name,
        'language' => $term_language,
        'reason' => 'No nodes found',
      ];
      $context['message'] = t('Processing term: @name [@lang] (skipped - no content found)', [
        '@name' => $term_name,
        '@lang' => $term_language,
      ]);
      continue;
    }

    // Load all nodes and combine their body text.
    $nodes = $node_storage->loadMultiple($nids);
    $body_texts = [];

    foreach ($nodes as $node) {
      if ($node->hasField('body') && !$node->get('body')->isEmpty()) {
        $text = $node->get('body')->value;
        // Strip HTML tags.
        $text = strip_tags($text);
        // Limit each node's text to 1500 characters.
        $text = mb_substr($text, 0, 1500);
        if (!empty($text)) {
          $body_texts[] = $text;
        }
      }
    }

    if (empty($body_texts)) {
      $context['results']['skipped'][] = [
        'term_id' => $term_id,
        'term_name' => $term_name,
        'language' => $term_language,
        'reason' => 'No body content',
      ];
      $context['message'] = t('Processing term: @name [@lang] (skipped - no body content)', [
        '@name' => $term_name,
        '@lang' => $term_language,
      ]);
      continue;
    }

    // Combine all body texts with separators.
    $combined_body = implode("\n\n---\n\n", $body_texts);

    // Generate SEO text using OpenAI.
    $api_key = $config->get('api_key');
    $model = $config->get('model') ?: 'gpt-4o-mini';
    $seo_length = $config->get('seo_length') ?: 155;

    $seo_data = self::generateSeoContentWithOpenAI($api_key, $model, $term_name, $combined_body, $seo_length, $term_language);

    if ($seo_data && !empty($seo_data['content'])) {
      // Save SEO content to term description for this language.
      $translated_term->setDescription($seo_data['content']);
      $translated_term->save();

      // Save meta title and description to simple_metatag_entity table.
      if (!empty($seo_data['meta_title']) || !empty($seo_data['meta_description'])) {
        $database = \Drupal::database();

        // Check if entry exists for this language.
        $existing = $database->select('simple_metatag_entity', 'sme')
          ->fields('sme', ['id'])
          ->condition('entity_type', 'taxonomy_term')
          ->condition('entity_id', $term_id)
          ->condition('langcode', $term_language)
          ->execute()
          ->fetchField();

        $fields = [
          'entity_type' => 'taxonomy_term',
          'entity_id' => $term_id,
          'langcode' => $term_language,
          'title' => $seo_data['meta_title'] ?? '',
          'description' => $seo_data['meta_description'] ?? '',
        ];

        if ($existing) {
          // Update existing.
          $database->update('simple_metatag_entity')
            ->fields($fields)
            ->condition('id', $existing)
            ->execute();
        }
        else {
          // Insert new.
          $database->insert('simple_metatag_entity')
            ->fields($fields)
            ->execute();
        }
      }

      $context['results']['success'][] = [
        'term_id' => $term_id,
        'term_name' => $term_name,
        'language' => $term_language,
        'nodes_used' => count($nids),
        'meta_title_length' => strlen($seo_data['meta_title']),
        'meta_desc_length' => strlen($seo_data['meta_description']),
        'content_length' => strlen($seo_data['content']),
      ];
      $context['message'] = t('Processing term: @name [@lang] (success)', [
        '@name' => $term_name,
        '@lang' => $term_language,
      ]);
    }
    else {
      $context['results']['failed'][] = [
        'term_id' => $term_id,
        'term_name' => $term_name,
        'language' => $term_language,
        'reason' => 'OpenAI generation failed',
      ];
      $context['message'] = t('Processing term: @name [@lang] (failed)', [
        '@name' => $term_name,
        '@lang' => $term_language,
      ]);
    }
    } // End foreach language
  }

  /**
   * Generate SEO content using OpenAI API.
   *
   * @param string $api_key
   *   The OpenAI API key.
   * @param string $model
   *   The OpenAI model to use.
   * @param string $term_name
   *   The term name.
   * @param string $body_text
   *   The body text from the latest node(s).
   * @param int $seo_length
   *   Target length for SEO content.
   * @param string $language
   *   The language code (e.g., 'en', 'es', 'uk').
   *
   * @return array|null
   *   Array with 'meta_title', 'meta_description', and 'content', or NULL on failure.
   */
  protected static function generateSeoContentWithOpenAI($api_key, $model, $term_name, $body_text, $seo_length = 1000, $language = 'en') {
    try {
      // Check if OpenAI client library is available.
      if (!class_exists('\OpenAI')) {
        \Drupal::logger('simple_metatag')->error('OpenAI PHP client library is not installed. Run: composer require openai-php/client');
        return NULL;
      }

      // Validate API key.
      if (empty($api_key)) {
        \Drupal::logger('simple_metatag')->error('OpenAI API key is empty. Please configure it in the settings.');
        return NULL;
      }

      $client = \OpenAI::client($api_key);

      // Map language codes to full language names.
      $language_names = [
        'en' => 'English',
        'es' => 'Spanish',
        'uk' => 'Ukrainian',
        'ru' => 'Russian',
        'de' => 'German',
        'fr' => 'French',
        'it' => 'Italian',
        'pt' => 'Portuguese',
        'pl' => 'Polish',
      ];
      $language_name = $language_names[$language] ?? $language;

      $system_prompt = "You are an SEO expert who writes compelling, optimized content for category pages. You generate meta titles, meta descriptions, and page content in {$language_name}.";

      $user_prompt = "Based on the following article content and category name, generate complete SEO package for a category page.\n\n";
      $user_prompt .= "IMPORTANT RULES:\n";
      $user_prompt .= "1. Generate ALL content in {$language_name} language\n";
      $user_prompt .= "2. DO NOT include any dates, years, or time references (like '2024', 'in 2023', 'this year', 'recently', etc.)\n";
      $user_prompt .= "3. Keep content timeless and evergreen\n\n";
      $user_prompt .= "Category: {$term_name}\n\n";
      $user_prompt .= "Related article content:\n{$body_text}\n\n";
      $user_prompt .= "Generate the following in JSON format (in {$language_name}):\n";
      $user_prompt .= "1. meta_title: SEO-optimized page title (50-60 characters, include category name, NO dates/years)\n";
      $user_prompt .= "2. meta_description: Compelling meta description (150-160 characters, NO dates/years)\n";
      $user_prompt .= "3. content: HTML content for category page (approximately {$seo_length} characters, NO dates/years)\n";
      $user_prompt .= "   - Start with ONE <h2> heading\n";
      $user_prompt .= "   - Follow with 2-4 <p> paragraphs\n";
      $user_prompt .= "   - SEO-friendly, informative, timeless content\n\n";
      $user_prompt .= "IMPORTANT: Respond with valid JSON only. Use \\n for newlines in content, escape quotes.\n\n";
      $user_prompt .= "Format:\n";
      $user_prompt .= "{\n";
      $user_prompt .= '  "meta_title": "Your title here",' . "\n";
      $user_prompt .= '  "meta_description": "Your description here",' . "\n";
      $user_prompt .= '  "content": "<h2>Heading</h2><p>Paragraph</p>"' . "\n";
      $user_prompt .= "}\n\n";
      $user_prompt .= "Do NOT use literal newlines in JSON values. Use \\n instead.\n";

      // Log what we're sending to OpenAI.
      \Drupal::logger('simple_metatag')->info('Sending request to OpenAI for term: @term. Model: @model. Body length: @length chars. Target SEO length: @seo_length', [
        '@term' => $term_name,
        '@model' => $model,
        '@length' => strlen($body_text),
        '@seo_length' => $seo_length,
      ]);

      // Calculate max tokens based on SEO length (roughly 1 token = 4 chars).
      $max_tokens = (int) ceil($seo_length / 3);

      // Try to make the API call and catch any errors.
      try {
        $request_data = [
          'model' => $model,
          'messages' => [
            [
              'role' => 'system',
              'content' => $system_prompt,
            ],
            [
              'role' => 'user',
              'content' => $user_prompt,
            ],
          ],
          'max_tokens' => $max_tokens,
          'temperature' => 0.7,
          'response_format' => ['type' => 'json_object'],
        ];

        // Log the full request for debugging.
        \Drupal::logger('simple_metatag')->debug('OpenAI request data: @data', [
          '@data' => json_encode($request_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ]);

        $response = $client->chat()->create($request_data);
      }
      catch (\TypeError $e) {
        // This happens when the API returns an unexpected response structure.
        \Drupal::logger('simple_metatag')->error('OpenAI API returned malformed response. This usually means invalid API key or API error. Error: @error', [
          '@error' => $e->getMessage()
        ]);
        \Drupal::messenger()->addError(t('OpenAI API error: Invalid API key or malformed response. Check your API key and try again.'));
        return NULL;
      }
      catch (\Throwable $e) {
        \Drupal::logger('simple_metatag')->error('Error during OpenAI API call: @error', [
          '@error' => $e->getMessage()
        ]);
        return NULL;
      }

      // Check if response has the expected structure.
      if (!isset($response->choices) || empty($response->choices)) {
        \Drupal::logger('simple_metatag')->error('OpenAI API returned unexpected response structure. Response: @response', [
          '@response' => print_r($response, TRUE)
        ]);
        return NULL;
      }

      if (isset($response->choices[0]->message->content)) {
        $generated_text = trim($response->choices[0]->message->content);

        // With JSON mode enabled, response should already be valid JSON.
        // Just remove any potential markdown markers.
        $generated_text = preg_replace('/^```json\s*/i', '', $generated_text);
        $generated_text = preg_replace('/^```\s*/i', '', $generated_text);
        $generated_text = preg_replace('/\s*```$/i', '', $generated_text);
        $generated_text = trim($generated_text);

        // Fix: Minify JSON by removing whitespace around structural elements.
        // OpenAI returns formatted JSON with newlines between keys, we need to compact it.
        // First collapse all whitespace sequences to a single space.
        $generated_text = preg_replace('/\s+/', ' ', $generated_text);
        // Then remove all spaces around JSON structural characters: { } [ ] , :
        $generated_text = preg_replace('/\s*([{}[\],:])\s*/', '$1', $generated_text);
        $generated_text = trim($generated_text);

        // Parse JSON response.
        $seo_data = json_decode($generated_text, TRUE);

        if (json_last_error() !== JSON_ERROR_NONE) {
          // Log the raw response for debugging - show only control characters as hex
          $hex_preview = '';
          for ($i = 0; $i < min(500, strlen($generated_text)); $i++) {
            $char = $generated_text[$i];
            $ord = ord($char);
            // Only show control characters (0x00-0x1F) and DEL (0x7F) as hex
            if ($ord < 32 || $ord === 127) {
              $hex_preview .= sprintf('[0x%02X]', $ord);
            } else {
              $hex_preview .= $char;
            }
          }

          \Drupal::logger('simple_metatag')->error('Failed to parse OpenAI JSON response for term: @term. Error: @error. Raw (first 500 chars, control chars as hex): @preview', [
            '@term' => $term_name,
            '@error' => json_last_error_msg(),
            '@preview' => $hex_preview,
          ]);
          return NULL;
        }

        // Validate response structure.
        if (empty($seo_data['meta_title']) || empty($seo_data['meta_description']) || empty($seo_data['content'])) {
          \Drupal::logger('simple_metatag')->error('OpenAI response missing required fields for term: @term. Data: @data', [
            '@term' => $term_name,
            '@data' => print_r($seo_data, TRUE),
          ]);
          return NULL;
        }

        // Clean up the content (remove any remaining markdown).
        $seo_data['content'] = preg_replace('/^```html\s*/i', '', $seo_data['content']);
        $seo_data['content'] = preg_replace('/\s*```$/i', '', $seo_data['content']);
        $seo_data['content'] = trim($seo_data['content']);

        // Log successful generation.
        \Drupal::logger('simple_metatag')->info('Successfully generated SEO content for term: @term. Title: @title (@title_len chars), Description: @desc (@desc_len chars), Content: @content_len chars', [
          '@term' => $term_name,
          '@title' => $seo_data['meta_title'],
          '@title_len' => strlen($seo_data['meta_title']),
          '@desc' => $seo_data['meta_description'],
          '@desc_len' => strlen($seo_data['meta_description']),
          '@content_len' => strlen($seo_data['content']),
        ]);

        return $seo_data;
      }

      \Drupal::logger('simple_metatag')->error('OpenAI API response missing content. Response: @response', [
        '@response' => print_r($response, TRUE)
      ]);
      return NULL;
    }
    catch (\OpenAI\Exceptions\ErrorException $e) {
      // Handle OpenAI specific errors (invalid key, rate limits, etc.).
      \Drupal::logger('simple_metatag')->error('OpenAI API error: @message. Type: @type. Code: @code', [
        '@message' => $e->getMessage(),
        '@type' => $e->getErrorType(),
        '@code' => $e->getErrorCode(),
      ]);

      // Show user-friendly message.
      \Drupal::messenger()->addError(t('OpenAI API error: @message', ['@message' => $e->getMessage()]));
      return NULL;
    }
    catch (\Exception $e) {
      \Drupal::logger('simple_metatag')->error('Unexpected error calling OpenAI API: @message. Stack trace: @trace', [
        '@message' => $e->getMessage(),
        '@trace' => $e->getTraceAsString(),
      ]);
      return NULL;
    }
  }

  /**
   * Batch finished callback.
   *
   * @param bool $success
   *   Whether the batch completed successfully.
   * @param array $results
   *   The results array.
   * @param array $operations
   *   The operations array.
   */
  public static function batchFinished($success, $results, $operations) {
    $messenger = \Drupal::messenger();

    if ($success) {
      $success_count = isset($results['success']) ? count($results['success']) : 0;
      $skipped_count = isset($results['skipped']) ? count($results['skipped']) : 0;
      $failed_count = isset($results['failed']) ? count($results['failed']) : 0;
      $total_count = $success_count + $skipped_count + $failed_count;

      // Calculate statistics from successful generations.
      $stats = [
        'total_nodes_used' => 0,
        'total_meta_title_chars' => 0,
        'total_meta_desc_chars' => 0,
        'total_content_chars' => 0,
        'languages' => [],
      ];

      if (isset($results['success'])) {
        foreach ($results['success'] as $item) {
          $stats['total_nodes_used'] += $item['nodes_used'] ?? 0;
          $stats['total_meta_title_chars'] += $item['meta_title_length'] ?? 0;
          $stats['total_meta_desc_chars'] += $item['meta_desc_length'] ?? 0;
          $stats['total_content_chars'] += $item['content_length'] ?? 0;

          if (!empty($item['language'])) {
            $lang = $item['language'];
            $stats['languages'][$lang] = ($stats['languages'][$lang] ?? 0) + 1;
          }
        }
      }

      // Calculate averages.
      $avg_nodes = $success_count > 0 ? round($stats['total_nodes_used'] / $success_count, 1) : 0;
      $avg_title_len = $success_count > 0 ? round($stats['total_meta_title_chars'] / $success_count) : 0;
      $avg_desc_len = $success_count > 0 ? round($stats['total_meta_desc_chars'] / $success_count) : 0;
      $avg_content_len = $success_count > 0 ? round($stats['total_content_chars'] / $success_count) : 0;

      // Build language breakdown.
      $lang_breakdown = [];
      foreach ($stats['languages'] as $lang => $count) {
        $lang_breakdown[] = "$lang: $count";
      }
      $lang_summary = !empty($lang_breakdown) ? implode(', ', $lang_breakdown) : 'N/A';

      // Show user message.
      $messenger->addStatus(t('SEO text generation completed. Success: @success, Skipped: @skipped, Failed: @failed', [
        '@success' => $success_count,
        '@skipped' => $skipped_count,
        '@failed' => $failed_count,
      ]));

      // Log detailed analytics.
      \Drupal::logger('simple_metatag')->info('Batch SEO generation completed. Total: @total terms | Success: @success | Skipped: @skipped | Failed: @failed | Languages: @languages | Avg nodes/term: @avg_nodes | Avg lengths - Title: @avg_title chars, Desc: @avg_desc chars, Content: @avg_content chars', [
        '@total' => $total_count,
        '@success' => $success_count,
        '@skipped' => $skipped_count,
        '@failed' => $failed_count,
        '@languages' => $lang_summary,
        '@avg_nodes' => $avg_nodes,
        '@avg_title' => $avg_title_len,
        '@avg_desc' => $avg_desc_len,
        '@avg_content' => $avg_content_len,
      ]);

      // Log details of skipped and failed items.
      if (isset($results['skipped']) && !empty($results['skipped'])) {
        $skipped_details = [];
        foreach ($results['skipped'] as $item) {
          $skipped_details[] = "{$item['term_name']} [{$item['language']}]: {$item['reason']}";
        }
        \Drupal::logger('simple_metatag')->warning('Skipped terms: @details', [
          '@details' => implode(' | ', $skipped_details),
        ]);
      }

      if (isset($results['failed']) && !empty($results['failed'])) {
        $failed_details = [];
        foreach ($results['failed'] as $item) {
          $failed_details[] = "{$item['term_name']} [{$item['language']}]: {$item['reason']}";
        }
        \Drupal::logger('simple_metatag')->error('Failed terms: @details', [
          '@details' => implode(' | ', $failed_details),
        ]);
      }
    }
    else {
      $messenger->addError(t('An error occurred during SEO text generation.'));
      \Drupal::logger('simple_metatag')->error('Batch SEO generation failed.');
    }
  }

  /**
   * Get vocabulary options for select field.
   *
   * @return array
   *   Array of vocabulary options.
   */
  protected function getVocabularyOptions() {
    $options = [];
    $vocabularies = Vocabulary::loadMultiple();

    foreach ($vocabularies as $vocabulary) {
      $options[$vocabulary->id()] = $vocabulary->label();
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This method is required but not used as we have custom submit handlers.
  }

}
