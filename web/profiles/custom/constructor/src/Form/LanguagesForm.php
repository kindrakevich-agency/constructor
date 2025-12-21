<?php

namespace Drupal\constructor\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManager;

/**
 * Step 2: Languages form - Configure additional languages for your site.
 */
class LanguagesForm extends InstallerFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'constructor_languages_form';
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
    $saved_values = $this->getFromState('languages', []);

    // Get available languages.
    $languages = LanguageManager::getStandardLanguageList();
    $language_options = [];
    foreach ($languages as $langcode => $language) {
      $language_options[$langcode] = $language[0];
    }

    // Language Configuration Section
    $form['lang_section'] = $this->createSectionHeader(
      $this->t('Languages'),
      $this->t('Select languages for your site. Choose your main language and optionally add more languages.')
    );

    $form['default_language'] = $this->createSelectField(
      $this->t('Default Language'),
      $language_options,
      $saved_values['default_language'] ?? 'en',
      TRUE,
      $this->t('Select the primary language for your site.')
    );

    $is_multilingual = $saved_values['enable_multilingual'] ?? FALSE;
    $hidden_style = $is_multilingual ? '' : 'display: none;';

    $form['enable_multilingual'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable multilingual support'),
      '#default_value' => $is_multilingual,
      '#description' => $this->t('Enable this to add support for multiple languages.'),
      '#attributes' => [
        'data-multilingual-toggle' => 'true',
      ],
    ];

    // Wrapper for all multilingual settings - hidden by default via inline style.
    $form['multilingual_settings'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'multilingual-settings-wrapper',
        'style' => $hidden_style,
      ],
    ];

    // Additional Languages Section
    $form['multilingual_settings']['additional_section'] = [
      '#markup' => '<div class="mb-6 mt-8 pt-6 border-t border-gray-200"><h2 class="text-xl font-semibold text-gray-900 mb-2">' . $this->t('Additional Languages') . '</h2></div>',
    ];

    $form['multilingual_settings']['languages'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select additional languages'),
      '#options' => $language_options,
      '#default_value' => $saved_values['additional_languages'] ?? [],
      '#description' => $this->t('Select the additional languages you want to enable.'),
    ];

    // Translation Settings Section
    $form['multilingual_settings']['translation_section'] = [
      '#markup' => '<div class="mb-6 mt-8 pt-6 border-t border-gray-200"><h2 class="text-xl font-semibold text-gray-900 mb-2">' . $this->t('Translation Settings') . '</h2></div>',
    ];

    $form['multilingual_settings']['enable_content_translation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable content translation'),
      '#default_value' => $saved_values['enable_content_translation'] ?? TRUE,
      '#description' => $this->t('Allow translating content into multiple languages.'),
    ];

    $form['multilingual_settings']['enable_interface_translation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable interface translation'),
      '#default_value' => $saved_values['enable_interface_translation'] ?? TRUE,
      '#description' => $this->t('Allow translating the user interface.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function submitStepForm(array &$form, FormStateInterface $form_state): void {
    // Get values from the nested multilingual_settings container.
    $multilingual_settings = $form_state->getValue('multilingual_settings') ?? [];
    $additional_languages = array_filter($multilingual_settings['languages'] ?? []);

    $values = [
      'default_language' => $form_state->getValue('default_language'),
      'enable_multilingual' => $form_state->getValue('enable_multilingual'),
      'additional_languages' => $additional_languages,
      'enable_content_translation' => $multilingual_settings['enable_content_translation'] ?? FALSE,
      'enable_interface_translation' => $multilingual_settings['enable_interface_translation'] ?? FALSE,
    ];

    // Save to state - actual language installation happens in finalize step.
    $this->saveToState('languages', $values);
  }

}
