<?php

namespace Drupal\constructor\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Step 5: Design & Layout form.
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
    return 5;
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
      '#attributes' => ['class' => ['flex', 'items-center', 'p-4', 'border', 'border-gray-200', 'rounded-lg']],
    ];

    $form['theme_grid']['dark_mode_wrapper']['enable_dark_mode'] = [
      '#type' => 'checkbox',
      '#title' => '<span class="font-medium text-gray-900">' . $this->t('Enable dark mode toggle') . '</span>',
      '#default_value' => $saved_values['enable_dark_mode'] ?? FALSE,
      '#description' => $this->t('Allow visitors to switch between light and dark themes.'),
    ];

    // Front Page Layout Section
    $form['front_section'] = $this->createSectionHeader(
      $this->t('Front Page Layout'),
      $this->t('Configure the layout and content for your front page.')
    );

    $form['front_page_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Front Page Type'),
      '#options' => [
        'default' => $this->t('Default content listing'),
        'node' => $this->t('Specific page/node'),
        'custom' => $this->t('Custom blocks layout'),
      ],
      '#default_value' => $saved_values['front_page_type'] ?? 'default',
      '#attributes' => ['class' => ['space-y-3']],
      '#wrapper_attributes' => ['class' => ['mb-8', 'space-y-2']],
    ];

    // Block Regions Section
    $form['regions_section'] = $this->createSectionHeader(
      $this->t('Block Regions'),
      $this->t('Select which blocks to display in each region.')
    );

    // Header Region
    $form['header_region'] = [
      '#type' => 'details',
      '#title' => $this->t('Header Region'),
      '#open' => TRUE,
      '#attributes' => ['class' => ['mb-4', 'border', 'border-gray-200', 'rounded-lg', 'p-4']],
    ];

    $form['header_region']['header_branding'] = [
      '#type' => 'checkbox',
      '#title' => '<span class="font-medium text-gray-900">' . $this->t('Site branding (logo & name)') . '</span>',
      '#default_value' => $saved_values['header']['branding'] ?? TRUE,
      '#wrapper_attributes' => ['class' => ['flex', 'items-start', 'gap-3', 'p-3', 'border', 'border-gray-100', 'rounded-lg', 'mb-2']],
    ];

    $form['header_region']['header_main_menu'] = [
      '#type' => 'checkbox',
      '#title' => '<span class="font-medium text-gray-900">' . $this->t('Main navigation menu') . '</span>',
      '#default_value' => $saved_values['header']['main_menu'] ?? TRUE,
      '#wrapper_attributes' => ['class' => ['flex', 'items-start', 'gap-3', 'p-3', 'border', 'border-gray-100', 'rounded-lg', 'mb-2']],
    ];

    $form['header_region']['header_search'] = [
      '#type' => 'checkbox',
      '#title' => '<span class="font-medium text-gray-900">' . $this->t('Search form') . '</span>',
      '#default_value' => $saved_values['header']['search'] ?? TRUE,
      '#wrapper_attributes' => ['class' => ['flex', 'items-start', 'gap-3', 'p-3', 'border', 'border-gray-100', 'rounded-lg']],
    ];

    // Sidebar Region
    $form['sidebar_region'] = [
      '#type' => 'details',
      '#title' => $this->t('Sidebar Region'),
      '#open' => FALSE,
      '#attributes' => ['class' => ['mb-4', 'border', 'border-gray-200', 'rounded-lg', 'p-4']],
    ];

    $form['sidebar_region']['enable_sidebar'] = [
      '#type' => 'checkbox',
      '#title' => '<span class="font-medium text-gray-900">' . $this->t('Enable sidebar') . '</span>',
      '#default_value' => $saved_values['sidebar']['enabled'] ?? TRUE,
      '#wrapper_attributes' => ['class' => ['flex', 'items-start', 'gap-3', 'p-3', 'border', 'border-gray-100', 'rounded-lg', 'mb-2']],
    ];

    $form['sidebar_region']['sidebar_menu'] = [
      '#type' => 'checkbox',
      '#title' => '<span class="font-medium text-gray-900">' . $this->t('Secondary navigation') . '</span>',
      '#default_value' => $saved_values['sidebar']['menu'] ?? FALSE,
      '#wrapper_attributes' => ['class' => ['flex', 'items-start', 'gap-3', 'p-3', 'border', 'border-gray-100', 'rounded-lg', 'mb-2']],
      '#states' => [
        'visible' => [
          ':input[name="enable_sidebar"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['sidebar_region']['sidebar_recent'] = [
      '#type' => 'checkbox',
      '#title' => '<span class="font-medium text-gray-900">' . $this->t('Recent content block') . '</span>',
      '#default_value' => $saved_values['sidebar']['recent'] ?? TRUE,
      '#wrapper_attributes' => ['class' => ['flex', 'items-start', 'gap-3', 'p-3', 'border', 'border-gray-100', 'rounded-lg', 'mb-2']],
      '#states' => [
        'visible' => [
          ':input[name="enable_sidebar"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['sidebar_region']['sidebar_tags'] = [
      '#type' => 'checkbox',
      '#title' => '<span class="font-medium text-gray-900">' . $this->t('Popular tags cloud') . '</span>',
      '#default_value' => $saved_values['sidebar']['tags'] ?? FALSE,
      '#wrapper_attributes' => ['class' => ['flex', 'items-start', 'gap-3', 'p-3', 'border', 'border-gray-100', 'rounded-lg']],
      '#states' => [
        'visible' => [
          ':input[name="enable_sidebar"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Footer Region
    $form['footer_region'] = [
      '#type' => 'details',
      '#title' => $this->t('Footer Region'),
      '#open' => FALSE,
      '#attributes' => ['class' => ['mb-4', 'border', 'border-gray-200', 'rounded-lg', 'p-4']],
    ];

    $form['footer_region']['footer_menu'] = [
      '#type' => 'checkbox',
      '#title' => '<span class="font-medium text-gray-900">' . $this->t('Footer menu') . '</span>',
      '#default_value' => $saved_values['footer']['menu'] ?? TRUE,
      '#wrapper_attributes' => ['class' => ['flex', 'items-start', 'gap-3', 'p-3', 'border', 'border-gray-100', 'rounded-lg', 'mb-2']],
    ];

    $form['footer_region']['footer_copyright'] = [
      '#type' => 'checkbox',
      '#title' => '<span class="font-medium text-gray-900">' . $this->t('Copyright notice') . '</span>',
      '#default_value' => $saved_values['footer']['copyright'] ?? TRUE,
      '#wrapper_attributes' => ['class' => ['flex', 'items-start', 'gap-3', 'p-3', 'border', 'border-gray-100', 'rounded-lg', 'mb-2']],
    ];

    $form['footer_region']['footer_social'] = [
      '#type' => 'checkbox',
      '#title' => '<span class="font-medium text-gray-900">' . $this->t('Social media links') . '</span>',
      '#default_value' => $saved_values['footer']['social'] ?? FALSE,
      '#wrapper_attributes' => ['class' => ['flex', 'items-start', 'gap-3', 'p-3', 'border', 'border-gray-100', 'rounded-lg']],
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
      'front_page_type' => $form_state->getValue('front_page_type'),
      'header' => [
        'branding' => $form_state->getValue('header_branding'),
        'main_menu' => $form_state->getValue('header_main_menu'),
        'search' => $form_state->getValue('header_search'),
      ],
      'sidebar' => [
        'enabled' => $form_state->getValue('enable_sidebar'),
        'menu' => $form_state->getValue('sidebar_menu'),
        'recent' => $form_state->getValue('sidebar_recent'),
        'tags' => $form_state->getValue('sidebar_tags'),
      ],
      'footer' => [
        'menu' => $form_state->getValue('footer_menu'),
        'copyright' => $form_state->getValue('footer_copyright'),
        'social' => $form_state->getValue('footer_social'),
      ],
    ];

    $this->saveToState('layout', $values);
  }

}
