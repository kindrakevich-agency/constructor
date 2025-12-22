<?php

namespace Drupal\constructor\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Step 4: Design & Layout form.
 */
class DesignLayoutForm extends InstallerFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'constructor_design_layout_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getStepNumber(): int {
    return 4;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildStepForm(array $form, FormStateInterface $form_state): array {
    $saved_values = $this->getFromState('layout', []);

    // Theme Settings Section
    $form['theme_section'] = $this->createSectionHeader(
      $this->t('Theme Settings'),
      $this->t('Configure the visual appearance of your site.')
    );

    $form['theme_grid'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['grid', 'grid-cols-1', 'md:grid-cols-2', 'gap-6', 'mb-8']],
    ];

    $form['theme_grid']['color_scheme'] = $this->createSelectField(
      $this->t('Color Scheme'),
      [
        'blue' => $this->t('Blue (Default)'),
        'green' => $this->t('Green'),
        'purple' => $this->t('Purple'),
        'orange' => $this->t('Orange'),
        'red' => $this->t('Red'),
        'dark' => $this->t('Dark'),
      ],
      $saved_values['color_scheme'] ?? 'blue',
      FALSE,
      $this->t('Select the primary color scheme for your site.')
    );

    $form['theme_grid']['dark_mode_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['p-4', 'border', 'border-gray-200', 'rounded-lg']],
    ];

    $form['theme_grid']['dark_mode_wrapper']['enable_dark_mode'] = [
      '#type' => 'checkbox',
      '#title' => '<span class="font-medium text-gray-900">' . $this->t('Enable dark mode toggle') . '</span>',
      '#default_value' => $saved_values['enable_dark_mode'] ?? TRUE,
      '#description' => $this->t('Allow visitors to switch between light and dark themes.'),
    ];

    // Dark mode sub-options (shown when enable_dark_mode is checked).
    $form['theme_grid']['dark_mode_wrapper']['dark_mode_options'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['ml-6', 'mt-3', 'space-y-2']],
      '#states' => [
        'visible' => [
          ':input[name="enable_dark_mode"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['theme_grid']['dark_mode_wrapper']['dark_mode_options']['dark_mode_default'] = [
      '#type' => 'checkbox',
      '#title' => '<span class="text-gray-700">' . $this->t('Make dark mode default') . '</span>',
      '#default_value' => $saved_values['dark_mode_default'] ?? FALSE,
      '#description' => $this->t('Site will load in dark mode by default.'),
    ];

    $form['theme_grid']['dark_mode_wrapper']['dark_mode_options']['hide_dark_mode_selector'] = [
      '#type' => 'checkbox',
      '#title' => '<span class="text-gray-700">' . $this->t('Hide dark/light mode selector') . '</span>',
      '#default_value' => $saved_values['hide_dark_mode_selector'] ?? FALSE,
      '#description' => $this->t('Hide the toggle from visitors (use with "Make dark mode default" for dark-only theme).'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function submitStepForm(array &$form, FormStateInterface $form_state): void {
    $values = [
      'color_scheme' => $form_state->getValue('color_scheme'),
      'enable_dark_mode' => $form_state->getValue('enable_dark_mode'),
      'dark_mode_default' => $form_state->getValue('dark_mode_default'),
      'hide_dark_mode_selector' => $form_state->getValue('hide_dark_mode_selector'),
    ];

    $this->saveToState('layout', $values);
  }

}
