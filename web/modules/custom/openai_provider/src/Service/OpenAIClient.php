<?php

namespace Drupal\openai_provider\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Component\Transliteration\TransliterationInterface;
use GuzzleHttp\ClientInterface;
use OpenAI;

/**
 * Service for interacting with OpenAI API.
 */
class OpenAIClient {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The transliteration service.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * Constructs an OpenAIClient object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $http_client, FileSystemInterface $file_system, TransliterationInterface $transliteration) {
    $this->configFactory = $config_factory;
    $this->httpClient = $http_client;
    $this->fileSystem = $file_system;
    $this->transliteration = $transliteration;
  }

  /**
   * Gets the OpenAI configuration.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The configuration object.
   */
  protected function getConfig() {
    return $this->configFactory->get('openai_provider.settings');
  }

  /**
   * Checks if the OpenAI API is configured.
   *
   * @return bool
   *   TRUE if configured, FALSE otherwise.
   */
  public function isConfigured(): bool {
    $config = $this->getConfig();
    return !empty($config->get('api_key'));
  }

  /**
   * Tests the connection to OpenAI API.
   *
   * @return array
   *   Array with 'success' boolean and 'message' string.
   */
  public function testConnection(): array {
    if (!$this->isConfigured()) {
      return [
        'success' => FALSE,
        'message' => 'API key is not configured.',
      ];
    }

    try {
      $client = $this->getClient();
      $response = $client->models()->list();

      return [
        'success' => TRUE,
        'message' => 'Connection successful. Found ' . count($response->data) . ' models.',
      ];
    }
    catch (\Exception $e) {
      return [
        'success' => FALSE,
        'message' => $e->getMessage(),
      ];
    }
  }

  /**
   * Gets the OpenAI client instance.
   *
   * @return \OpenAI\Client
   *   The OpenAI client.
   */
  protected function getClient() {
    $config = $this->getConfig();
    return OpenAI::client($config->get('api_key'));
  }

  /**
   * Gets available text models.
   *
   * @return array
   *   Array of model options.
   */
  public function getTextModels(): array {
    return [
      'gpt-4o' => 'GPT-4o (Latest)',
      'gpt-4o-mini' => 'GPT-4o Mini (Fast & Cheap)',
      'gpt-4-turbo' => 'GPT-4 Turbo',
      'gpt-4' => 'GPT-4',
      'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
      'o1-preview' => 'o1-preview (Reasoning)',
      'o1-mini' => 'o1-mini (Reasoning, Fast)',
    ];
  }

  /**
   * Gets available image models.
   *
   * @return array
   *   Array of model options.
   */
  public function getImageModels(): array {
    return [
      'dall-e-3' => 'DALL-E 3 (High Quality)',
      'dall-e-2' => 'DALL-E 2 (Faster)',
    ];
  }

  /**
   * Sends a prompt to OpenAI and returns the response.
   *
   * @param string $prompt
   *   The prompt to send.
   * @param string|null $system_message
   *   Optional system message.
   * @param string|null $model
   *   Optional model override.
   *
   * @return string|null
   *   The AI response or NULL on failure.
   */
  public function prompt(string $prompt, ?string $system_message = NULL, ?string $model = NULL): ?string {
    if (!$this->isConfigured()) {
      \Drupal::logger('openai_provider')->error('OpenAI API key is not configured');
      return NULL;
    }

    $config = $this->getConfig();
    $model = $model ?? $config->get('text_model') ?? 'gpt-4o-mini';

    try {
      $client = $this->getClient();

      $messages = [];
      if (!empty($system_message)) {
        $messages[] = ['role' => 'system', 'content' => $system_message];
      }
      $messages[] = ['role' => 'user', 'content' => $prompt];

      $response = $client->chat()->create([
        'model' => $model,
        'messages' => $messages,
        'temperature' => (float) ($config->get('temperature') ?? 0.7),
        'max_tokens' => (int) ($config->get('max_tokens') ?? 4096),
      ]);

      return $response->choices[0]->message->content ?? NULL;
    }
    catch (\Exception $e) {
      \Drupal::logger('openai_provider')->error('OpenAI API request failed: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Generates an image using DALL-E.
   *
   * @param string $prompt
   *   The image generation prompt.
   * @param array $options
   *   Optional generation options.
   *
   * @return array
   *   Array with 'success', 'url', 'error' keys.
   */
  public function generateImage(string $prompt, array $options = []): array {
    if (!$this->isConfigured()) {
      return [
        'success' => FALSE,
        'error' => 'OpenAI API key is not configured',
      ];
    }

    $config = $this->getConfig();
    $model = $options['model'] ?? $config->get('image_model') ?? 'dall-e-3';
    $size = $options['size'] ?? $config->get('image_size') ?? '1024x1024';
    $quality = $options['quality'] ?? $config->get('image_quality') ?? 'standard';

    try {
      $client = $this->getClient();

      $params = [
        'model' => $model,
        'prompt' => mb_substr($prompt, 0, 4000, 'UTF-8'),
        'n' => 1,
        'size' => $size,
      ];

      // DALL-E 3 supports quality parameter.
      if ($model === 'dall-e-3') {
        $params['quality'] = $quality;
      }

      $response = $client->images()->create($params);

      $image_url = $response->data[0]->url ?? NULL;

      if ($image_url) {
        return [
          'success' => TRUE,
          'url' => $image_url,
        ];
      }

      return [
        'success' => FALSE,
        'error' => 'No image URL returned',
      ];
    }
    catch (\Exception $e) {
      \Drupal::logger('openai_provider')->error('DALL-E image generation failed: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'error' => $e->getMessage(),
      ];
    }
  }

  /**
   * Downloads and saves an image from URL.
   *
   * @param string $url
   *   The image URL.
   * @param string $filename
   *   Base filename (without extension).
   * @param string $directory
   *   The destination directory URI.
   *
   * @return \Drupal\file\FileInterface|null
   *   The saved file entity or NULL on failure.
   */
  public function saveImageFromUrl(string $url, string $filename, string $directory = 'public://openai_images'): ?\Drupal\file\FileInterface {
    try {
      $response = $this->httpClient->get($url, ['timeout' => 60]);
      $image_data = (string) $response->getBody();

      if (empty($image_data)) {
        return NULL;
      }

      // Prepare filename.
      $clean_filename = $this->transliteration->transliterate($filename, 'en');
      $clean_filename = preg_replace('/[^a-z0-9]+/i', '-', strtolower($clean_filename));
      $clean_filename = trim($clean_filename, '-');
      $clean_filename = substr($clean_filename, 0, 50);

      $extension = 'png';
      $content_type = $response->getHeader('Content-Type');
      if (!empty($content_type) && strpos($content_type[0], 'image/jpeg') !== FALSE) {
        $extension = 'jpg';
      }

      $full_filename = $clean_filename . '-openai.' . $extension;

      // Prepare directory.
      $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

      // Save file.
      $destination = $directory . '/' . $full_filename;
      $file_repository = \Drupal::service('file.repository');
      $file = $file_repository->writeData($image_data, $destination, FileSystemInterface::EXISTS_RENAME);

      return $file ?: NULL;
    }
    catch (\Exception $e) {
      \Drupal::logger('openai_provider')->error('Failed to save image: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

}
