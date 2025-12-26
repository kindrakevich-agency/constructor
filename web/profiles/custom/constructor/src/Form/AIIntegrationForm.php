<?php

namespace Drupal\constructor\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\openai_provider\Service\OpenAIClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Step 5: AI Integration form.
 */
class AIIntegrationForm extends InstallerFormBase {

  /**
   * The OpenAI client service.
   *
   * @var \Drupal\openai_provider\Service\OpenAIClient|null
   */
  protected $openaiClient;

  /**
   * Constructs an AIIntegrationForm object.
   */
  public function __construct(StateInterface $state, ?OpenAIClient $openai_client = NULL) {
    parent::__construct($state);
    $this->openaiClient = $openai_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $openai_client = NULL;
    if ($container->has('openai_provider.client')) {
      $openai_client = $container->get('openai_provider.client');
    }

    return new static(
      $container->get('state'),
      $openai_client
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'constructor_ai_integration_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getStepNumber(): int {
    return 4;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildStepForm(array $form, FormStateInterface $form_state): array {
    $saved_values = $this->getFromState('ai_settings', []);

    // OpenAI Integration Section
    $form['ai_intro'] = $this->createSectionHeader(
      $this->t('OpenAI Integration'),
      $this->t('Configure OpenAI Provider module for AI-powered content generation and image creation.')
    );

    // API Configuration
    $form['api_section'] = $this->createSectionHeader(
      $this->t('API Configuration'),
      ''
    );

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OpenAI API Key'),
      '#default_value' => $saved_values['api_key'] ?? '',
      '#required' => FALSE,
      '#maxlength' => 255,
      '#description' => $this->t('Enter your OpenAI API key. Get one from <a href="https://platform.openai.com/api-keys" target="_blank" class="text-blue-600 hover:underline">platform.openai.com</a>. You can skip this step and configure later.'),
      '#attributes' => [
        'placeholder' => 'sk-...',
        'autocomplete' => 'off',
        'class' => [
          'w-full', 'px-4', 'py-3', 'border', 'border-gray-200', 'rounded-lg',
          'text-gray-900', 'font-mono', 'text-sm',
        ],
      ],
    ];

    // Get models
    $text_models = $this->getTextModels();
    $image_models = $this->getImageModels();

    // Model Settings Section
    $form['model_section'] = $this->createSectionHeader(
      $this->t('Model Settings'),
      ''
    );

    $form['models_grid'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['grid', 'grid-cols-1', 'md:grid-cols-2', 'gap-6', 'mb-6']],
    ];

    $form['models_grid']['text_model'] = $this->createSelectField(
      $this->t('Text Generation Model'),
      $text_models,
      $saved_values['text_model'] ?? 'gpt-4o-mini',
      FALSE,
      $this->t('Select the default AI model for text/content generation.')
    );

    $form['models_grid']['image_model'] = $this->createSelectField(
      $this->t('Image Generation Model'),
      $image_models,
      $saved_values['image_model'] ?? 'dall-e-3',
      FALSE,
      $this->t('Select the default AI model for image generation.')
    );

    $form['params_grid'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['grid', 'grid-cols-1', 'md:grid-cols-2', 'gap-6', 'mb-8']],
    ];

    $form['params_grid']['temperature'] = [
      '#type' => 'number',
      '#title' => $this->t('Temperature'),
      '#min' => 0,
      '#max' => 2,
      '#step' => 0.1,
      '#default_value' => $saved_values['temperature'] ?? 0.3,
      '#description' => $this->t('Controls randomness. Lower = more focused, higher = more creative.'),
      '#attributes' => [
        'class' => ['w-full', 'px-4', 'py-3', 'border', 'border-gray-200', 'rounded-lg'],
      ],
    ];

    $form['params_grid']['max_tokens'] = $this->createSelectField(
      $this->t('Max Tokens'),
      [
        '500' => $this->t('Short responses (500 tokens)'),
        '1000' => $this->t('Medium responses (1,000 tokens)'),
        '2000' => $this->t('Standard articles (2,000 tokens)'),
        '4000' => $this->t('Long articles (4,000 tokens)'),
        '8000' => $this->t('Extended content (8,000 tokens)'),
        '16000' => $this->t('Maximum (16,000 tokens)'),
      ],
      $saved_values['max_tokens'] ?? '4000',
      FALSE,
      $this->t('Maximum length of generated content.')
    );

    // Image Generation Settings
    $form['image_section'] = $this->createSectionHeader(
      $this->t('Image Generation Settings'),
      ''
    );

    $form['enable_image_generation_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['p-4', 'border', 'border-gray-200', 'rounded-lg', 'bg-white', 'mb-6']],
    ];

    $form['enable_image_generation_wrapper']['enable_image_generation'] = [
      '#type' => 'checkbox',
      '#title' => '<span class="font-medium text-gray-900">' . $this->t('Generate AI images') . '</span>',
      '#default_value' => $saved_values['enable_image_generation'] ?? TRUE,
      '#description' => $this->t('Generate images using DALL-E for content. If unchecked, only text content will be generated.'),
    ];

    $form['image_grid'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['grid', 'grid-cols-1', 'md:grid-cols-2', 'gap-6', 'mb-8']],
      '#states' => [
        'visible' => [
          ':input[name="enable_image_generation"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['image_grid']['image_size'] = $this->createSelectField(
      $this->t('Default Image Size'),
      [
        '1024x1024' => '1024x1024 (Square)',
        '1792x1024' => '1792x1024 (Landscape)',
        '1024x1792' => '1024x1792 (Portrait)',
      ],
      $saved_values['image_size'] ?? '1024x1024',
      FALSE,
      $this->t('Default size for DALL-E generated images.')
    );

    $form['image_grid']['image_quality'] = $this->createSelectField(
      $this->t('Image Quality'),
      [
        'standard' => $this->t('Standard'),
        'hd' => $this->t('HD (Higher detail, slower)'),
      ],
      $saved_values['image_quality'] ?? 'standard',
      FALSE,
      $this->t('Quality setting for DALL-E 3 images.')
    );

    // Note: Test connection feature can be added after installation in OpenAI settings.
    $form['test_info'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['mt-8', 'p-4', 'bg-blue-50', 'rounded-lg', 'border', 'border-blue-200']],
    ];

    $form['test_info']['message'] = [
      '#markup' => '<p class="text-sm text-blue-800">' . $this->t('You can test your API connection after installation in the OpenAI Provider settings.') . '</p>',
    ];

    return $form;
  }

  /**
   * Get text models from OpenAI Provider or defaults.
   */
  protected function getTextModels(): array {
    if ($this->openaiClient) {
      return $this->openaiClient->getTextModels();
    }

    return [
      'gpt-4o' => 'GPT-4o (Latest)',
      'gpt-4o-mini' => 'GPT-4o Mini (Fast & Cheap)',
      'gpt-4-turbo' => 'GPT-4 Turbo',
      'gpt-4' => 'GPT-4',
      'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
    ];
  }

  /**
   * Get image models from OpenAI Provider or defaults.
   */
  protected function getImageModels(): array {
    if ($this->openaiClient) {
      return $this->openaiClient->getImageModels();
    }

    return [
      'dall-e-3' => 'DALL-E 3 (High Quality)',
      'dall-e-2' => 'DALL-E 2 (Faster)',
    ];
  }

  /**
   * AJAX callback to test API connection.
   */
  public function testApiConnection(array &$form, FormStateInterface $form_state) {
    $api_key = $form_state->getValue('api_key');
    $result = [
      '#type' => 'container',
      '#attributes' => ['id' => 'api-test-result', 'class' => ['mt-4']],
    ];

    if (empty($api_key)) {
      $result['message'] = [
        '#markup' => '<div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">' . $this->t('Please enter an API key first.') . '</div>',
      ];
      return $result;
    }

    try {
      $client = \Drupal::httpClient();
      $response = $client->request('GET', 'https://api.openai.com/v1/models', [
        'headers' => [
          'Authorization' => 'Bearer ' . $api_key,
          'Content-Type' => 'application/json',
        ],
        'timeout' => 10,
      ]);

      if ($response->getStatusCode() === 200) {
        $body = json_decode((string) $response->getBody(), TRUE);
        $model_count = count($body['data'] ?? []);
        $result['message'] = [
          '#markup' => '<div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg flex items-center gap-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>' . $this->t('API connection successful! Found @count models.', ['@count' => $model_count]) . '</div>',
        ];
      }
    }
    catch (\Exception $e) {
      $result['message'] = [
        '#markup' => '<div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">' . $this->t('API connection failed: @message', ['@message' => $e->getMessage()]) . '</div>',
      ];
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function submitStepForm(array &$form, FormStateInterface $form_state): void {
    $values = [
      // AI settings.
      'api_key' => $form_state->getValue('api_key'),
      'text_model' => $form_state->getValue('text_model'),
      'image_model' => $form_state->getValue('image_model'),
      'temperature' => $form_state->getValue('temperature'),
      'max_tokens' => $form_state->getValue('max_tokens'),
      // Image generation settings.
      'enable_image_generation' => (bool) $form_state->getValue('enable_image_generation'),
      'image_size' => $form_state->getValue('image_size'),
      'image_quality' => $form_state->getValue('image_quality'),
    ];

    $this->saveToState('ai_settings', $values);
  }

}
