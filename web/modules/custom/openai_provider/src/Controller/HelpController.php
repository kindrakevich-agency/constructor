<?php

namespace Drupal\openai_provider\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for OpenAI API documentation page.
 */
class HelpController extends ControllerBase {

  /**
   * Displays the API documentation.
   *
   * @return array
   *   Render array.
   */
  public function content() {
    $build = [];

    $build['intro'] = [
      '#markup' => '<p>' . $this->t('This module provides integration with OpenAI services (GPT and DALL-E). Use the <code>openai_provider.client</code> service in your custom modules.') . '</p>',
    ];

    // Quick Start.
    $build['quick_start'] = [
      '#type' => 'details',
      '#title' => $this->t('Quick Start'),
      '#open' => TRUE,
    ];

    $build['quick_start']['content'] = [
      '#markup' => '
<h4>1. Add dependency to your module</h4>
<pre><code># your_module.info.yml
dependencies:
  - openai_provider:openai_provider</code></pre>

<h4>2. Inject the service</h4>
<pre><code># your_module.services.yml
services:
  your_module.service:
    class: Drupal\your_module\Service\YourService
    arguments: [\'@openai_provider.client\']</code></pre>

<h4>3. Use in your code</h4>
<pre><code>// Simple text generation
$client = \Drupal::service(\'openai_provider.client\');
$response = $client->prompt(\'Write a poem about Drupal\');

// With system message
$response = $client->prompt(
  \'Explain quantum computing\',
  \'You are a helpful assistant that explains things simply.\'
);

// With specific model
$response = $client->prompt(\'Hello\', NULL, \'gpt-4o\');</code></pre>',
    ];

    // Text Generation API.
    $build['text_api'] = [
      '#type' => 'details',
      '#title' => $this->t('Text Generation API (GPT)'),
      '#open' => TRUE,
    ];

    $build['text_api']['content'] = [
      '#markup' => '
<h4>prompt($prompt, $system_message = NULL, $model = NULL)</h4>
<p>Send a prompt to OpenAI and get a text response. Returns the generated text or NULL on failure.</p>
<pre><code>$client = \Drupal::service(\'openai_provider.client\');

// Basic usage
$text = $client->prompt(\'Explain quantum computing in simple terms\');

// With system instruction
$text = $client->prompt(
  \'What is Drupal?\',
  \'You are a helpful assistant that explains things for beginners.\'
);

// With specific model
$text = $client->prompt(\'Hello\', NULL, \'gpt-4o-mini\');</code></pre>

<h4>Available Text Models</h4>
<ul>
  <li><code>gpt-4o</code> - GPT-4o (Latest, most capable)</li>
  <li><code>gpt-4o-mini</code> - GPT-4o Mini (Fast & cheap, default)</li>
  <li><code>gpt-4-turbo</code> - GPT-4 Turbo</li>
  <li><code>gpt-4</code> - GPT-4</li>
  <li><code>gpt-3.5-turbo</code> - GPT-3.5 Turbo (Fastest)</li>
  <li><code>o1-preview</code> - o1-preview (Advanced reasoning)</li>
  <li><code>o1-mini</code> - o1-mini (Reasoning, faster)</li>
</ul>

<h4>Configuration Options</h4>
<p>Default values are set in the module settings:</p>
<ul>
  <li><strong>Temperature</strong> - Controls randomness (0.0-2.0)</li>
  <li><strong>Max Tokens</strong> - Maximum response length</li>
</ul>',
    ];

    // Image Generation API.
    $build['image_api'] = [
      '#type' => 'details',
      '#title' => $this->t('Image Generation API (DALL-E)'),
      '#open' => TRUE,
    ];

    $build['image_api']['content'] = [
      '#markup' => '
<h4>generateImage($prompt, array $options = [])</h4>
<p>Generate images using DALL-E.</p>
<pre><code>$client = \Drupal::service(\'openai_provider.client\');

$result = $client->generateImage(\'A sunset over mountains\', [
  \'model\' => \'dall-e-3\',       // dall-e-3 or dall-e-2
  \'size\' => \'1024x1024\',       // Image dimensions
  \'quality\' => \'hd\',           // standard or hd (DALL-E 3 only)
]);

if ($result[\'success\']) {
  $image_url = $result[\'url\'];  // Temporary URL to download
} else {
  $error = $result[\'error\'];
}</code></pre>

<h4>saveImageFromUrl($url, $filename, $directory = \'public://openai_images\')</h4>
<p>Download and save a generated image to Drupal file system.</p>
<pre><code>$result = $client->generateImage(\'A beautiful flower\');

if ($result[\'success\']) {
  $file = $client->saveImageFromUrl(
    $result[\'url\'],
    \'my-flower-image\',
    \'public://my_images\'
  );

  if ($file) {
    $fid = $file->id();
    $uri = $file->getFileUri();
  }
}</code></pre>

<h4>Available Image Models</h4>
<ul>
  <li><code>dall-e-3</code> - DALL-E 3 (Highest quality, default)</li>
  <li><code>dall-e-2</code> - DALL-E 2 (Faster, lower cost)</li>
</ul>

<h4>Image Sizes</h4>
<ul>
  <li><code>1024x1024</code> - Square (default)</li>
  <li><code>1792x1024</code> - Landscape (DALL-E 3 only)</li>
  <li><code>1024x1792</code> - Portrait (DALL-E 3 only)</li>
</ul>

<h4>Quality Options (DALL-E 3 only)</h4>
<ul>
  <li><code>standard</code> - Standard quality (default)</li>
  <li><code>hd</code> - HD quality (more detail)</li>
</ul>',
    ];

    // Utility Methods.
    $build['utility_api'] = [
      '#type' => 'details',
      '#title' => $this->t('Utility Methods'),
      '#open' => FALSE,
    ];

    $build['utility_api']['content'] = [
      '#markup' => '
<h4>isConfigured()</h4>
<p>Check if the API key is configured.</p>
<pre><code>if ($client->isConfigured()) {
  // API is ready to use
}</code></pre>

<h4>testConnection()</h4>
<p>Test the API connection.</p>
<pre><code>$result = $client->testConnection();
if ($result[\'success\']) {
  $message = $result[\'message\'];
}</code></pre>

<h4>getTextModels() / getImageModels()</h4>
<p>Get arrays of available models.</p>
<pre><code>$text_models = $client->getTextModels();
// Returns: [\'gpt-4o\' => \'GPT-4o (Latest)\', ...]

$image_models = $client->getImageModels();
// Returns: [\'dall-e-3\' => \'DALL-E 3 (High Quality)\', ...]</code></pre>',
    ];

    // Full Example.
    $build['example'] = [
      '#type' => 'details',
      '#title' => $this->t('Full Example: Content Generator'),
      '#open' => FALSE,
    ];

    $build['example']['content'] = [
      '#markup' => '
<pre><code>&lt;?php

namespace Drupal\my_module\Service;

use Drupal\openai_provider\Service\OpenAIClient;

class ContentGenerator {

  protected $openai;

  public function __construct(OpenAIClient $openai) {
    $this->openai = $openai;
  }

  public function generateArticle($topic) {
    // Check if configured
    if (!$this->openai->isConfigured()) {
      throw new \Exception(\'OpenAI is not configured\');
    }

    // Generate article text
    $article_text = $this->openai->prompt(
      "Write a detailed article about: {$topic}",
      \'You are a professional content writer. Write engaging, well-structured articles.\'
    );

    if (!$article_text) {
      throw new \Exception(\'Failed to generate article text\');
    }

    // Generate featured image
    $image_result = $this->openai->generateImage(
      "Professional blog header image for article about: {$topic}",
      [
        \'size\' => \'1792x1024\',
        \'quality\' => \'hd\',
      ]
    );

    $image_file = NULL;
    if ($image_result[\'success\']) {
      $image_file = $this->openai->saveImageFromUrl(
        $image_result[\'url\'],
        \'article-\' . time()
      );
    }

    return [
      \'text\' => $article_text,
      \'image\' => $image_file,
    ];
  }

}</code></pre>',
    ];

    // Error Handling.
    $build['errors'] = [
      '#type' => 'details',
      '#title' => $this->t('Error Handling'),
      '#open' => FALSE,
    ];

    $build['errors']['content'] = [
      '#markup' => '
<p>Text generation returns NULL on failure. Image generation returns an array with <code>success</code> key.</p>
<pre><code>// Text generation
$text = $client->prompt($prompt);
if ($text === NULL) {
  // Check logs for details
  \Drupal::logger(\'my_module\')->error(\'OpenAI text generation failed\');
}

// Image generation
$result = $client->generateImage($prompt);
if (!$result[\'success\']) {
  $error = $result[\'error\'];
  \Drupal::logger(\'my_module\')->error(\'DALL-E Error: @error\', [
    \'@error\' => $error,
  ]);
}</code></pre>

<h4>Common Errors</h4>
<ul>
  <li><strong>API key is not configured</strong> - Set the API key in settings</li>
  <li><strong>Rate limit exceeded</strong> - Too many requests, wait and retry</li>
  <li><strong>Content policy violation</strong> - Prompt violated OpenAI policies</li>
  <li><strong>Invalid model</strong> - Check model name spelling</li>
  <li><strong>Insufficient quota</strong> - Add credits to your OpenAI account</li>
</ul>',
    ];

    return $build;
  }

}
