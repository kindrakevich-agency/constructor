<?php

namespace Drupal\openai_provider\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\openai_provider\Service\OpenAIClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure OpenAI API settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The OpenAI client service.
   *
   * @var \Drupal\openai_provider\Service\OpenAIClient
   */
  protected $openaiClient;

  /**
   * Constructs a SettingsForm object.
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
    return 'openai_provider_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['openai_provider.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('openai_provider.settings');

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#description' => $this->t('Enter your OpenAI API key. Get one from <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Dashboard</a>.'),
      '#default_value' => $config->get('api_key'),
      '#required' => TRUE,
      '#maxlength' => 255,
    ];

    $form['text_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Text Generation Settings'),
      '#open' => TRUE,
    ];

    $form['text_settings']['text_model'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Text Model'),
      '#description' => $this->t('Select the default model for text generation.'),
      '#options' => $this->openaiClient->getTextModels(),
      '#default_value' => $config->get('text_model') ?? 'gpt-4o-mini',
    ];

    $form['text_settings']['temperature'] = [
      '#type' => 'number',
      '#title' => $this->t('Temperature'),
      '#description' => $this->t('Controls randomness. Lower values (0.0-0.3) for focused output, higher values (0.7-1.0) for creative output.'),
      '#default_value' => $config->get('temperature') ?? 0.7,
      '#min' => 0,
      '#max' => 2,
      '#step' => 0.1,
    ];

    $form['text_settings']['max_tokens'] = [
      '#type' => 'number',
      '#title' => $this->t('Max Tokens'),
      '#description' => $this->t('Maximum number of tokens in the response.'),
      '#default_value' => $config->get('max_tokens') ?? 4096,
      '#min' => 100,
      '#max' => 128000,
    ];

    $form['image_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Image Generation Settings (DALL-E)'),
      '#open' => TRUE,
    ];

    $form['image_settings']['image_model'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Image Model'),
      '#description' => $this->t('Select the default model for image generation.'),
      '#options' => $this->openaiClient->getImageModels(),
      '#default_value' => $config->get('image_model') ?? 'dall-e-3',
    ];

    $form['image_settings']['image_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Size'),
      '#description' => $this->t('Default size for generated images.'),
      '#options' => [
        '1024x1024' => '1024x1024 (Square)',
        '1792x1024' => '1792x1024 (Landscape)',
        '1024x1792' => '1024x1792 (Portrait)',
      ],
      '#default_value' => $config->get('image_size') ?? '1024x1024',
    ];

    $form['image_settings']['image_quality'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Quality'),
      '#description' => $this->t('Quality setting for DALL-E 3 images.'),
      '#options' => [
        'standard' => $this->t('Standard'),
        'hd' => $this->t('HD (Higher detail)'),
      ],
      '#default_value' => $config->get('image_quality') ?? 'standard',
    ];

    // Connection status.
    if ($config->get('api_key')) {
      $result = $this->openaiClient->testConnection();
      $form['connection_status'] = [
        '#type' => 'item',
        '#title' => $this->t('Connection Status'),
        '#markup' => $result['success']
          ? '<span style="color: green;">✓ ' . $this->t('Connected') . '</span>'
          : '<span style="color: red;">✗ ' . $result['message'] . '</span>',
        '#weight' => -10,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('openai_provider.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('text_model', $form_state->getValue('text_model'))
      ->set('temperature', $form_state->getValue('temperature'))
      ->set('max_tokens', $form_state->getValue('max_tokens'))
      ->set('image_model', $form_state->getValue('image_model'))
      ->set('image_size', $form_state->getValue('image_size'))
      ->set('image_quality', $form_state->getValue('image_quality'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
