<?php

namespace Drupal\form_sender;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Service for sending form data via Email and Telegram.
 *
 * Usage:
 * @code
 * $sender = \Drupal::service('form_sender');
 * $result = $sender->send([
 *   'subject' => 'New Order',
 *   'message' => 'Order details here...',
 *   'form_type' => 'order',
 *   'data' => [
 *     'name' => 'John Doe',
 *     'email' => 'john@example.com',
 *     'phone' => '+1234567890',
 *   ],
 * ]);
 * @endcode
 */
class FormSenderService {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a FormSenderService object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    MailManagerInterface $mail_manager,
    ClientInterface $http_client,
    LoggerChannelFactoryInterface $logger_factory,
    LanguageManagerInterface $language_manager
  ) {
    $this->configFactory = $config_factory;
    $this->mailManager = $mail_manager;
    $this->httpClient = $http_client;
    $this->logger = $logger_factory->get('form_sender');
    $this->languageManager = $language_manager;
  }

  /**
   * Send form data via configured channels.
   *
   * @param array $data
   *   The data to send. Supported keys:
   *   - subject: (string) Subject/title for the message.
   *   - message: (string) Main message body.
   *   - form_type: (string) Type of form (e.g., 'contact', 'order', 'booking').
   *   - to: (string) Recipient email address (required for email sending).
   *   - data: (array) Additional structured data (key-value pairs).
   *   - html: (bool) Whether message is HTML (default: FALSE).
   *
   * @return array
   *   Result array with keys:
   *   - success: (bool) Whether at least one method succeeded.
   *   - email: (bool|null) Email send result (NULL if disabled).
   *   - telegram: (bool|null) Telegram send result (NULL if disabled).
   *   - errors: (array) List of error messages.
   */
  public function send(array $data): array {
    $config = $this->configFactory->get('form_sender.settings');
    $result = [
      'success' => FALSE,
      'email' => NULL,
      'telegram' => NULL,
      'errors' => [],
    ];

    $subject = $data['subject'] ?? 'Form Submission';
    $message = $this->formatMessage($data);

    // Send via Email.
    if ($config->get('email_enabled')) {
      $result['email'] = $this->sendEmail($subject, $message, $data);
      if (!$result['email']) {
        $result['errors'][] = 'Failed to send email.';
      }
    }

    // Send via Telegram.
    if ($config->get('telegram_enabled')) {
      $result['telegram'] = $this->sendTelegram($subject, $message, $data);
      if (!$result['telegram']) {
        $result['errors'][] = 'Failed to send Telegram message.';
      }
    }

    // Success if at least one method worked.
    $result['success'] = $result['email'] === TRUE || $result['telegram'] === TRUE;

    return $result;
  }

  /**
   * Format message from data array.
   *
   * @param array $data
   *   The data array.
   *
   * @return string
   *   Formatted message.
   */
  protected function formatMessage(array $data): string {
    $message = $data['message'] ?? '';

    // Append structured data if provided.
    if (!empty($data['data']) && is_array($data['data'])) {
      if (!empty($message)) {
        $message .= "\n\n";
      }
      $message .= "---\n";
      foreach ($data['data'] as $key => $value) {
        $label = ucfirst(str_replace('_', ' ', $key));
        $message .= "{$label}: {$value}\n";
      }
    }

    return $message;
  }

  /**
   * Send message via Email.
   *
   * @param string $subject
   *   Email subject.
   * @param string $message
   *   Email body.
   * @param array $data
   *   Original data array.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  protected function sendEmail(string $subject, string $message, array $data): bool {
    $config = $this->configFactory->get('form_sender.settings');
    $to = $config->get('email_to');

    if (empty($to)) {
      $this->logger->error('Email recipient not configured.');
      return FALSE;
    }

    $prefix = $config->get('email_subject_prefix') ?: '[Form Submission]';
    $full_subject = $prefix . ' ' . $subject;

    $langcode = $this->languageManager->getDefaultLanguage()->getId();

    $params = [
      'subject' => $full_subject,
      'message' => $message,
      'data' => $data,
    ];

    try {
      $result = $this->mailManager->mail(
        'form_sender',
        'form_submission',
        $to,
        $langcode,
        $params,
        NULL,
        TRUE
      );

      if ($result['result']) {
        $this->logger->info('Email sent successfully to @to', ['@to' => $to]);
        return TRUE;
      }
      else {
        $this->logger->error('Failed to send email to @to', ['@to' => $to]);
        return FALSE;
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Email exception: @message', ['@message' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * Send message via Telegram.
   *
   * @param string $subject
   *   Message subject/title.
   * @param string $message
   *   Message body.
   * @param array $data
   *   Original data array.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  protected function sendTelegram(string $subject, string $message, array $data): bool {
    $config = $this->configFactory->get('form_sender.settings');
    $bot_token = $config->get('telegram_bot_token');
    $chat_id = $config->get('telegram_chat_id');

    if (empty($bot_token) || empty($chat_id)) {
      $this->logger->error('Telegram bot token or chat ID not configured.');
      return FALSE;
    }

    // Format message for Telegram.
    $form_type = $data['form_type'] ?? 'form';
    $telegram_message = "<b>{$subject}</b>\n";
    $telegram_message .= "<i>Type: {$form_type}</i>\n\n";
    $telegram_message .= $this->escapeHtml($message);

    $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";

    try {
      $response = $this->httpClient->request('POST', $url, [
        'json' => [
          'chat_id' => $chat_id,
          'text' => $telegram_message,
          'parse_mode' => 'HTML',
          'disable_web_page_preview' => TRUE,
        ],
        'timeout' => 10,
      ]);

      $body = json_decode($response->getBody()->getContents(), TRUE);

      if (!empty($body['ok'])) {
        $this->logger->info('Telegram message sent successfully to @chat', ['@chat' => $chat_id]);
        return TRUE;
      }
      else {
        $this->logger->error('Telegram API error: @error', [
          '@error' => $body['description'] ?? 'Unknown error',
        ]);
        return FALSE;
      }
    }
    catch (GuzzleException $e) {
      $this->logger->error('Telegram request failed: @message', ['@message' => $e->getMessage()]);
      return FALSE;
    }
    catch (\Exception $e) {
      $this->logger->error('Telegram exception: @message', ['@message' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * Escape HTML entities for Telegram.
   *
   * @param string $text
   *   Text to escape.
   *
   * @return string
   *   Escaped text.
   */
  protected function escapeHtml(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  }

}
