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

    // Get all available languages from Drupal's standard list.
    $language_options = [];
    try {
      $languages = LanguageManager::getStandardLanguageList();
      foreach ($languages as $langcode => $language) {
        $language_options[$langcode] = $language[0];
      }
    }
    catch (\Exception $e) {
      // Fallback to common languages if standard list fails.
      $language_options = [
        'en' => 'English',
        'uk' => 'Ukrainian',
        'de' => 'German',
        'fr' => 'French',
        'es' => 'Spanish',
        'it' => 'Italian',
        'pl' => 'Polish',
        'pt-pt' => 'Portuguese',
        'nl' => 'Dutch',
        'ru' => 'Russian',
        'ja' => 'Japanese',
        'zh-hans' => 'Chinese, Simplified',
        'ar' => 'Arabic',
      ];
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

    $is_multilingual = !empty($saved_values['enable_multilingual']);

    $form['enable_multilingual'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable multilingual support'),
      '#default_value' => $is_multilingual,
      '#description' => $this->t('Check this to add support for multiple languages on your site.'),
      '#attributes' => [
        'data-multilingual-toggle' => 'true',
      ],
    ];

    // Additional Languages Section - hidden by default, shown via JS when checkbox is checked.
    $wrapper_style = $is_multilingual ? '' : 'display: none;';

    $form['additional_languages'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select additional languages'),
      '#options' => $language_options,
      '#default_value' => $saved_values['additional_languages'] ?? [],
      '#prefix' => '<div id="multilingual-settings-wrapper" style="' . $wrapper_style . '"><div class="mb-6 mt-8 pt-6 border-t border-gray-200"><h2 class="text-xl font-semibold text-gray-900 mb-2">' . $this->t('Additional Languages') . '</h2><p class="text-gray-500 text-sm">' . $this->t('Select the additional languages you want to enable.') . '</p></div>',
    ];

    $form['enable_content_translation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable content translation'),
      '#default_value' => $saved_values['enable_content_translation'] ?? TRUE,
      '#description' => $this->t('Allow translating content into multiple languages.'),
      '#prefix' => '<div class="mb-6 mt-8 pt-6 border-t border-gray-200"><h2 class="text-xl font-semibold text-gray-900 mb-2">' . $this->t('Translation Settings') . '</h2></div>',
    ];

    $form['enable_interface_translation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable interface translation'),
      '#default_value' => $saved_values['enable_interface_translation'] ?? TRUE,
      '#description' => $this->t('Allow translating the user interface.'),
      '#suffix' => '</div>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function submitStepForm(array &$form, FormStateInterface $form_state): void {
    // Get enable_multilingual value (checkbox returns 1 or 0).
    $enable_multilingual = (bool) $form_state->getValue('enable_multilingual');

    // Get values from root level.
    $languages_raw = $form_state->getValue('additional_languages') ?? [];
    $additional_languages = array_filter($languages_raw);

    $values = [
      'default_language' => $form_state->getValue('default_language'),
      'enable_multilingual' => $enable_multilingual,
      'additional_languages' => array_values($additional_languages),
      'enable_content_translation' => (bool) $form_state->getValue('enable_content_translation'),
      'enable_interface_translation' => (bool) $form_state->getValue('enable_interface_translation'),
    ];

    // Log what we're saving for debugging.
    \Drupal::logger('constructor')->notice('LanguagesForm saving: default=@default, multilingual=@multi, additional=@add, raw_languages=@raw', [
      '@default' => $values['default_language'],
      '@multi' => $values['enable_multilingual'] ? 'yes' : 'no',
      '@add' => implode(',', $values['additional_languages']),
      '@raw' => print_r($languages_raw, TRUE),
    ]);

    // Save to state - actual language installation happens in finalize step.
    $this->saveToState('languages', $values);
  }

}
