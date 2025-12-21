<?php

namespace Drupal\constructor\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManager;

/**
 * Step 1: Languages form - Configure additional languages for your site.
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
    return 1;
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

    $form['enable_multilingual'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable multilingual support'),
      '#default_value' => $saved_values['enable_multilingual'] ?? FALSE,
      '#description' => $this->t('Enable this to add support for multiple languages.'),
      '#wrapper_attributes' => ['class' => ['mb-6', 'flex', 'items-start', 'gap-3']],
    ];

    // Additional Languages Section
    $form['additional_section'] = [
      '#markup' => '<div class="mb-6 mt-8 pt-6 border-t border-gray-200"><h2 class="text-xl font-semibold text-gray-900 mb-2">' . $this->t('Additional Languages') . '</h2></div>',
      '#states' => [
        'visible' => [
          ':input[name="enable_multilingual"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['languages'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select additional languages'),
      '#options' => $language_options,
      '#default_value' => $saved_values['additional_languages'] ?? [],
      '#description' => $this->t('Select the additional languages you want to enable.'),
      '#wrapper_attributes' => ['class' => ['mb-6']],
      '#states' => [
        'visible' => [
          ':input[name="enable_multilingual"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Translation Settings Section
    $form['translation_section'] = [
      '#markup' => '<div class="mb-6 mt-8 pt-6 border-t border-gray-200"><h2 class="text-xl font-semibold text-gray-900 mb-2">' . $this->t('Translation Settings') . '</h2></div>',
      '#states' => [
        'visible' => [
          ':input[name="enable_multilingual"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['enable_content_translation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable content translation'),
      '#default_value' => $saved_values['enable_content_translation'] ?? TRUE,
      '#description' => $this->t('Allow translating content into multiple languages.'),
      '#wrapper_attributes' => ['class' => ['mb-4']],
      '#states' => [
        'visible' => [
          ':input[name="enable_multilingual"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['enable_interface_translation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable interface translation'),
      '#default_value' => $saved_values['enable_interface_translation'] ?? TRUE,
      '#description' => $this->t('Allow translating the user interface.'),
      '#wrapper_attributes' => ['class' => ['mb-4']],
      '#states' => [
        'visible' => [
          ':input[name="enable_multilingual"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function submitStepForm(array &$form, FormStateInterface $form_state): void {
    $additional_languages = array_filter($form_state->getValue('languages') ?? []);

    $values = [
      'default_language' => $form_state->getValue('default_language'),
      'enable_multilingual' => $form_state->getValue('enable_multilingual'),
      'additional_languages' => $additional_languages,
      'enable_content_translation' => $form_state->getValue('enable_content_translation'),
      'enable_interface_translation' => $form_state->getValue('enable_interface_translation'),
    ];

    $this->saveToState('languages', $values);

    // Enable language modules if multilingual is enabled.
    if ($values['enable_multilingual']) {
      $modules_to_enable = ['language'];

      if ($values['enable_content_translation']) {
        $modules_to_enable[] = 'content_translation';
      }

      if ($values['enable_interface_translation']) {
        $modules_to_enable[] = 'locale';
      }

      /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
      $module_installer = \Drupal::service('module_installer');
      $module_installer->install($modules_to_enable);

      // Add additional languages.
      if (!empty($additional_languages)) {
        foreach ($additional_languages as $langcode) {
          if ($langcode !== $values['default_language']) {
            $language = \Drupal\language\Entity\ConfigurableLanguage::createFromLangcode($langcode);
            $language->save();
          }
        }
      }
    }
  }

}
