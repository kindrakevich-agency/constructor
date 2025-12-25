<?php

namespace Drupal\form_sender\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Form Sender settings.
 */
class FormSenderSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_sender_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['form_sender.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('form_sender.settings');

    // Email settings.
    $form['email'] = [
      '#type' => 'details',
      '#title' => $this->t('Email Settings'),
      '#open' => TRUE,
    ];

    $form['email']['email_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable email sending'),
      '#description' => $this->t('Send form submissions via email.'),
      '#default_value' => $config->get('email_enabled'),
    ];

    $form['email']['email_to'] = [
      '#type' => 'email',
      '#title' => $this->t('Recipient email'),
      '#description' => $this->t('Email address to receive form submissions.'),
      '#default_value' => $config->get('email_to'),
    ];

    $form['email']['email_subject_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email subject prefix'),
      '#description' => $this->t('Prefix for email subject lines.'),
      '#default_value' => $config->get('email_subject_prefix') ?: '[Form Submission]',
    ];

    // Telegram settings.
    $form['telegram'] = [
      '#type' => 'details',
      '#title' => $this->t('Telegram Settings'),
      '#open' => TRUE,
    ];

    $form['telegram']['telegram_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Telegram sending'),
      '#description' => $this->t('Send form submissions via Telegram bot.'),
      '#default_value' => $config->get('telegram_enabled'),
    ];

    $form['telegram']['telegram_bot_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bot token'),
      '#description' => $this->t('Telegram bot token from @BotFather.'),
      '#default_value' => $config->get('telegram_bot_token'),
    ];

    $form['telegram']['telegram_chat_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Chat ID'),
      '#description' => $this->t('Telegram chat ID or channel username (e.g., @channelname or -1001234567890).'),
      '#default_value' => $config->get('telegram_chat_id'),
    ];

    // Test section.
    $form['test'] = [
      '#type' => 'details',
      '#title' => $this->t('Test Sending'),
      '#open' => FALSE,
    ];

    $form['test']['test_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Test message'),
      '#description' => $this->t('Enter a test message to send.'),
      '#default_value' => 'This is a test message from Form Sender module.',
      '#rows' => 3,
    ];

    $form['test']['send_test'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send Test'),
      '#submit' => ['::sendTest'],
      '#limit_validation_errors' => [],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Validate email settings.
    if ($form_state->getValue('email_enabled')) {
      if (empty($form_state->getValue('email_to'))) {
        $form_state->setErrorByName('email_to', $this->t('Recipient email is required when email sending is enabled.'));
      }
    }

    // Validate Telegram settings.
    if ($form_state->getValue('telegram_enabled')) {
      if (empty($form_state->getValue('telegram_bot_token'))) {
        $form_state->setErrorByName('telegram_bot_token', $this->t('Bot token is required when Telegram sending is enabled.'));
      }
      if (empty($form_state->getValue('telegram_chat_id'))) {
        $form_state->setErrorByName('telegram_chat_id', $this->t('Chat ID is required when Telegram sending is enabled.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('form_sender.settings')
      ->set('email_enabled', $form_state->getValue('email_enabled'))
      ->set('email_to', $form_state->getValue('email_to'))
      ->set('email_subject_prefix', $form_state->getValue('email_subject_prefix'))
      ->set('telegram_enabled', $form_state->getValue('telegram_enabled'))
      ->set('telegram_bot_token', $form_state->getValue('telegram_bot_token'))
      ->set('telegram_chat_id', $form_state->getValue('telegram_chat_id'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Send test message.
   */
  public function sendTest(array &$form, FormStateInterface $form_state) {
    // Save config first.
    $this->config('form_sender.settings')
      ->set('email_enabled', $form_state->getValue('email_enabled'))
      ->set('email_to', $form_state->getValue('email_to'))
      ->set('email_subject_prefix', $form_state->getValue('email_subject_prefix'))
      ->set('telegram_enabled', $form_state->getValue('telegram_enabled'))
      ->set('telegram_bot_token', $form_state->getValue('telegram_bot_token'))
      ->set('telegram_chat_id', $form_state->getValue('telegram_chat_id'))
      ->save();

    $test_message = $form_state->getValue('test_message') ?: 'Test message';

    /** @var \Drupal\form_sender\FormSenderService $sender */
    $sender = \Drupal::service('form_sender');

    $result = $sender->send([
      'subject' => 'Test Message',
      'message' => $test_message,
      'form_type' => 'test',
    ]);

    if ($result['success']) {
      $methods = [];
      if (!empty($result['email'])) {
        $methods[] = 'Email';
      }
      if (!empty($result['telegram'])) {
        $methods[] = 'Telegram';
      }

      if (!empty($methods)) {
        $this->messenger()->addStatus($this->t('Test message sent successfully via: @methods', [
          '@methods' => implode(', ', $methods),
        ]));
      }
      else {
        $this->messenger()->addWarning($this->t('No sending methods are enabled. Enable at least one method.'));
      }
    }
    else {
      $this->messenger()->addError($this->t('Failed to send test message. Check the logs for details.'));
    }
  }

}
