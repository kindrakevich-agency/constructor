<?php

namespace Drupal\constructor\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Minimal configuration form that auto-submits.
 *
 * This replaces Drupal's install_configure_form to avoid duplicate password
 * entry. The actual configuration is done in SiteBasicsForm.
 */
class MinimalConfigureForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'constructor_minimal_configure_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Set the form title required by installer.
    $form['#title'] = $this->t('Configuring site');

    // Auto-submit message.
    $form['message'] = [
      '#markup' => '<div class="text-center py-8"><p class="text-gray-600">' . $this->t('Preparing your site...') . '</p></div>',
    ];

    // Hidden submit button that will be auto-clicked by JavaScript.
    $form['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['hidden']],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Continue'),
      '#attributes' => ['id' => 'auto-submit-btn'],
    ];

    // Auto-submit JavaScript.
    $form['#attached']['html_head'][] = [
      [
        '#type' => 'html_tag',
        '#tag' => 'script',
        '#value' => 'document.addEventListener("DOMContentLoaded", function() { setTimeout(function() { var btn = document.getElementById("auto-submit-btn"); if (btn) btn.click(); }, 100); });',
      ],
      'auto_submit_script',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Create temporary admin account with a random password.
    // SiteBasicsForm will update this with the user's chosen credentials.
    $account = \Drupal::entityTypeManager()->getStorage('user')->load(1);

    if ($account) {
      // Admin account already exists, just update minimal required fields.
      $account->set('name', 'admin');
      $account->set('mail', 'admin@localhost.temp');
      $account->setPassword(\Drupal::service('password_generator')->generate(32));
      $account->activate();
      $account->save();
    }

    // Set minimal site configuration.
    $config = \Drupal::configFactory()->getEditable('system.site');
    $config->set('name', 'Constructor Site');
    $config->set('mail', 'admin@localhost.temp');
    $config->save();

    // Set default country and timezone to avoid errors.
    $config = \Drupal::configFactory()->getEditable('system.date');
    $config->set('timezone.default', 'UTC');
    $config->set('country.default', '');
    $config->save();
  }

}
