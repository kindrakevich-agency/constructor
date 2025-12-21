<?php

namespace Drupal\openai_provider\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\openai_provider\Service\OpenAIClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for testing OpenAI API functionality.
 */
class TestForm extends FormBase {

  /**
   * The OpenAI client service.
   *
   * @var \Drupal\openai_provider\Service\OpenAIClient
   */
  protected $openaiClient;

  /**
   * Constructs a TestForm object.
   *
   * @param \Drupal\openai_provider\Service\OpenAIClient $openai_client
   *   The OpenAI client service.
   */
  public function __construct(OpenAIClient $openai_client) {
    $this->openaiClient = $openai_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('openai_provider.client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openai_provider_test';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (!$this->openaiClient->isConfigured()) {
      $form['warning'] = [
        '#type' => 'markup',
        '#markup' => '<div class="messages messages--warning">' . $this->t('OpenAI API key is not configured. Please configure it in the <a href=":url">settings</a>.', [':url' => '/admin/config/services/openai']) . '</div>',
      ];
      return $form;
    }

    $form['test_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Test Type'),
      '#options' => [
        'text' => $this->t('Text Generation (GPT)'),
        'image' => $this->t('Image Generation (DALL-E)'),
      ],
      '#default_value' => 'text',
    ];

    $form['text_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Text Generation Test'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="test_type"]' => ['value' => 'text'],
        ],
      ],
    ];

    $form['text_settings']['text_prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Prompt'),
      '#default_value' => $this->t('Write a short poem about coding.'),
      '#rows' => 3,
    ];

    $form['text_settings']['text_model'] = [
      '#type' => 'select',
      '#title' => $this->t('Model'),
      '#options' => $this->openaiClient->getTextModels(),
      '#default_value' => 'gpt-4o-mini',
    ];

    $form['image_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Image Generation Test'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="test_type"]' => ['value' => 'image'],
        ],
      ],
    ];

    $form['image_settings']['image_prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Image Prompt'),
      '#default_value' => $this->t('A futuristic city skyline at sunset, digital art style'),
      '#rows' => 3,
    ];

    $form['image_settings']['image_model'] = [
      '#type' => 'select',
      '#title' => $this->t('Model'),
      '#options' => $this->openaiClient->getImageModels(),
      '#default_value' => 'dall-e-3',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Run Test'),
      '#button_type' => 'primary',
    ];

    // Display result if available.
    $result = $form_state->get('test_result');
    if ($result) {
      $form['result'] = [
        '#type' => 'details',
        '#title' => $this->t('Test Result'),
        '#open' => TRUE,
        '#weight' => 100,
      ];

      if ($result['type'] === 'text') {
        $form['result']['output'] = [
          '#type' => 'markup',
          '#markup' => '<div class="openai-test-result"><pre>' . htmlspecialchars($result['content']) . '</pre></div>',
        ];
      }
      elseif ($result['type'] === 'image') {
        if ($result['success']) {
          $form['result']['output'] = [
            '#type' => 'markup',
            '#markup' => '<div class="openai-test-result"><img src="' . $result['url'] . '" style="max-width: 512px; height: auto;" alt="Generated image" /></div>',
          ];
        }
        else {
          $form['result']['output'] = [
            '#type' => 'markup',
            '#markup' => '<div class="messages messages--error">' . $result['error'] . '</div>',
          ];
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $test_type = $form_state->getValue('test_type');

    if ($test_type === 'text') {
      $prompt = $form_state->getValue('text_prompt');
      $model = $form_state->getValue('text_model');

      $response = $this->openaiClient->prompt($prompt, NULL, $model);

      if ($response) {
        $form_state->set('test_result', [
          'type' => 'text',
          'content' => $response,
        ]);
        $this->messenger()->addStatus($this->t('Text generation successful.'));
      }
      else {
        $this->messenger()->addError($this->t('Text generation failed. Check the logs for details.'));
      }
    }
    elseif ($test_type === 'image') {
      $prompt = $form_state->getValue('image_prompt');
      $model = $form_state->getValue('image_model');

      $result = $this->openaiClient->generateImage($prompt, ['model' => $model]);

      if ($result['success']) {
        $form_state->set('test_result', [
          'type' => 'image',
          'success' => TRUE,
          'url' => $result['url'],
        ]);
        $this->messenger()->addStatus($this->t('Image generation successful.'));
      }
      else {
        $form_state->set('test_result', [
          'type' => 'image',
          'success' => FALSE,
          'error' => $result['error'],
        ]);
        $this->messenger()->addError($this->t('Image generation failed: @error', ['@error' => $result['error']]));
      }
    }

    $form_state->setRebuild(TRUE);
  }

}
