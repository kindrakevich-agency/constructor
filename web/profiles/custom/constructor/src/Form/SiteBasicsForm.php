<?php

namespace Drupal\constructor\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Step 2: Site Basics form.
 */
class SiteBasicsForm extends InstallerFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'constructor_site_basics_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getStepNumber(): int {
    return 2;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildStepForm(array $form, FormStateInterface $form_state): array {
    $saved_values = $this->getFromState('site_basics', []);

    // Site Information Section
    $form['site_section'] = $this->createSectionHeader(
      $this->t('Site Information'),
      $this->t('Enter basic information about your website.')
    );

    $form['site_name'] = $this->createTextField(
      $this->t('Site Name'),
      $saved_values['site_name'] ?? '',
      TRUE,
      $this->t('My Awesome Website')
    );

    $form['site_slogan'] = $this->createTextField(
      $this->t('Site Slogan'),
      $saved_values['site_slogan'] ?? '',
      FALSE,
      $this->t('A catchy tagline for your site')
    );

    $form['site_email'] = $this->createEmailField(
      $this->t('Site Email'),
      $saved_values['site_email'] ?? '',
      TRUE,
      $this->t('admin@example.com'),
      $this->t('This email will be used for system notifications.')
    );

    // Admin Account Section
    $form['admin_section'] = $this->createSectionHeader(
      $this->t('Administrator Account'),
      $this->t('Create the main administrator account for your site.')
    );

    $form['account_name'] = $this->createTextField(
      $this->t('Username'),
      $saved_values['account_name'] ?? 'admin',
      TRUE,
      $this->t('admin')
    );

    $form['account_email'] = $this->createEmailField(
      $this->t('Email'),
      $saved_values['account_email'] ?? '',
      TRUE,
      $this->t('admin@example.com')
    );

    $form['account_pass'] = [
      '#type' => 'password_confirm',
      '#required' => TRUE,
      '#size' => 25,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function submitStepForm(array &$form, FormStateInterface $form_state): void {
    $values = [
      'site_name' => $form_state->getValue('site_name'),
      'site_slogan' => $form_state->getValue('site_slogan'),
      'site_email' => $form_state->getValue('site_email'),
      'account_name' => $form_state->getValue('account_name'),
      'account_email' => $form_state->getValue('account_email'),
      'account_pass' => $form_state->getValue('account_pass'),
    ];

    $this->saveToState('site_basics', $values);

    // Apply site configuration immediately.
    $config = \Drupal::configFactory()->getEditable('system.site');
    $config->set('name', $values['site_name']);
    $config->set('slogan', $values['site_slogan']);
    $config->set('mail', $values['site_email']);
    $config->save();

    // Update admin account with user's chosen credentials.
    $account = \Drupal::entityTypeManager()->getStorage('user')->load(1);
    if ($account) {
      $account->set('name', $values['account_name']);
      $account->set('mail', $values['account_email']);
      if (!empty($values['account_pass'])) {
        $account->setPassword($values['account_pass']);
      }
      $account->save();
    }
  }

}
