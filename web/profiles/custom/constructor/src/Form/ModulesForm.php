<?php

namespace Drupal\constructor\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Step 4: Modules form.
 */
class ModulesForm extends InstallerFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'constructor_modules_form';
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
    $saved_values = $this->getFromState('modules', []);

    // Core optional modules.
    $core_modules = [
      'contact' => [
        'name' => 'Contact',
        'description' => 'Enables contact forms on your site.',
        'default' => TRUE,
      ],
      'search' => [
        'name' => 'Search',
        'description' => 'Enables site-wide keyword searching.',
        'default' => TRUE,
      ],
      'media' => [
        'name' => 'Media',
        'description' => 'Media management and library.',
        'default' => TRUE,
      ],
      'media_library' => [
        'name' => 'Media Library',
        'description' => 'Enhanced media browser and selection.',
        'default' => TRUE,
      ],
      'responsive_image' => [
        'name' => 'Responsive Image',
        'description' => 'Provides responsive image styles.',
        'default' => TRUE,
      ],
      'menu_link_content' => [
        'name' => 'Custom Menu Links',
        'description' => 'Allows creating custom menu items.',
        'default' => TRUE,
      ],
      'shortcut' => [
        'name' => 'Shortcut',
        'description' => 'Quick links for administrators.',
        'default' => FALSE,
      ],
    ];

    // Custom modules (bundled with the profile).
    $custom_modules = [
      'openai_provider' => [
        'name' => 'OpenAI Provider',
        'description' => 'AI-powered content generation using OpenAI API.',
        'default' => TRUE,
      ],
      'simple_metatag' => [
        'name' => 'Simple Metatag',
        'description' => 'Basic meta tags for SEO optimization.',
        'default' => TRUE,
      ],
      'simple_sitemap_generator' => [
        'name' => 'Simple Sitemap Generator',
        'description' => 'Generate XML sitemaps for search engines.',
        'default' => TRUE,
      ],
      'form_sender' => [
        'name' => 'Form Sender',
        'description' => 'Send form submissions via Email and Telegram.',
        'default' => TRUE,
      ],
      'contact_form' => [
        'name' => 'Contact Form',
        'description' => 'Contact form block for frontpage with Email/Telegram notifications.',
        'default' => TRUE,
      ],
    ];

    // Core Modules Section
    $form['core_section'] = $this->createSectionHeader(
      $this->t('Core Modules'),
      $this->t('Select additional Drupal core modules to enable.')
    );

    $form['core_modules_grid'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['grid', 'grid-cols-1', 'md:grid-cols-2', 'gap-4', 'mb-8']],
    ];

    foreach ($core_modules as $module_id => $module_info) {
      $default_value = isset($saved_values['core'][$module_id]) ? $saved_values['core'][$module_id] : $module_info['default'];

      $form['core_modules_grid'][$module_id] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['p-4', 'border', 'border-gray-200', 'rounded-lg', 'hover:border-blue-300', 'transition-colors']],
      ];

      $form['core_modules_grid'][$module_id]['checkbox'] = [
        '#type' => 'checkbox',
        '#title' => '<span class="font-medium text-gray-900">' . $module_info['name'] . '</span>',
        '#default_value' => $default_value,
        '#parents' => ['core_modules', $module_id],
      ];

      $form['core_modules_grid'][$module_id]['desc'] = [
        '#markup' => '<p class="text-sm text-gray-500 ml-6 mt-1">' . $module_info['description'] . '</p>',
      ];
    }

    // Custom Modules Section - only show if there are custom modules.
    if (!empty($custom_modules)) {
      $form['custom_section'] = $this->createSectionHeader(
        $this->t('Custom Modules'),
        $this->t('These custom modules are included with Constructor profile.')
      );

      $form['custom_modules_grid'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['grid', 'grid-cols-1', 'md:grid-cols-2', 'gap-4', 'mb-8']],
      ];

      foreach ($custom_modules as $module_id => $module_info) {
        $is_required = !empty($module_info['required']);

        $form['custom_modules_grid'][$module_id] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['p-4', 'border', 'border-gray-200', 'rounded-lg', 'bg-gray-50']],
        ];

        $form['custom_modules_grid'][$module_id]['checkbox'] = [
          '#type' => 'checkbox',
          '#title' => '<span class="font-medium text-gray-900">' . $module_info['name'] . '</span>' .
                      ($is_required ? ' <span class="text-xs text-blue-600 font-medium">(Required)</span>' : ''),
          '#default_value' => TRUE,
          '#disabled' => $is_required,
          '#parents' => ['custom_modules', $module_id],
        ];

        $form['custom_modules_grid'][$module_id]['desc'] = [
          '#markup' => '<p class="text-sm text-gray-500 ml-6 mt-1">' . $module_info['description'] . '</p>',
        ];
      }
    }

    // SEO Settings Section
    $form['seo_section'] = $this->createSectionHeader(
      $this->t('SEO Settings'),
      $this->t('Configure search engine optimization settings.')
    );

    $form['enable_seo_defaults'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Configure default SEO settings'),
      '#default_value' => $saved_values['enable_seo_defaults'] ?? TRUE,
      '#description' => $this->t('Set up default metatag patterns and sitemap configuration.'),
      '#wrapper_attributes' => ['class' => ['mb-4']],
    ];

    $form['robots_txt'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Generate robots.txt'),
      '#default_value' => $saved_values['robots_txt'] ?? TRUE,
      '#description' => $this->t('Create a default robots.txt file for search engines.'),
      '#wrapper_attributes' => ['class' => ['mb-4']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function submitStepForm(array &$form, FormStateInterface $form_state): void {
    $core_modules = array_filter($form_state->getValue('core_modules') ?? []);
    $custom_modules = array_filter($form_state->getValue('custom_modules') ?? []);

    $values = [
      'core' => $core_modules,
      'custom' => $custom_modules,
      'enable_seo_defaults' => $form_state->getValue('enable_seo_defaults'),
      'robots_txt' => $form_state->getValue('robots_txt'),
    ];

    $this->saveToState('modules', $values);

    // Prepare the list of modules to enable (only module names as strings).
    $modules_to_enable = array_merge(
      array_keys($core_modules),
      array_keys($custom_modules)
    );

    // Ensure we only have valid string module names.
    $modules_to_enable = array_values(array_filter($modules_to_enable, 'is_string'));

    $this->saveToState('modules_to_enable', $modules_to_enable);
  }

}
