<?php

namespace Drupal\constructor\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Step 3: Content Types form.
 */
class ContentTypesForm extends InstallerFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'constructor_content_types_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getStepNumber(): int {
    return 3;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildStepForm(array $form, FormStateInterface $form_state): array {
    $saved_values = $this->getFromState('content_types', []);

    // Default content types to suggest.
    // Only core types + custom module types. All checked by default.
    $default_types = [
      'page' => [
        'name' => 'Basic Page',
        'description' => 'Use basic pages for static content like About us, Contact.',
        'default' => TRUE,
      ],
      'article' => [
        'name' => 'Article',
        'description' => 'Blog posts and news with images and video. Includes Articles block.',
        'default' => TRUE,
        'module' => 'content_article',
      ],
      'team_member' => [
        'name' => 'Team Member',
        'description' => 'Staff profiles with photo and position. Includes Team carousel block.',
        'default' => TRUE,
        'module' => 'content_team',
      ],
      'faq' => [
        'name' => 'FAQ',
        'description' => 'Frequently asked questions with answers. Includes FAQ block.',
        'default' => TRUE,
        'module' => 'content_faq',
      ],
      'service' => [
        'name' => 'Service',
        'description' => 'Services offered by your organization. Includes Services block.',
        'default' => TRUE,
        'module' => 'content_services',
      ],
      'product' => [
        'name' => 'Product',
        'description' => 'E-commerce products with images, pricing, and properties. Includes Product blocks.',
        'default' => FALSE,
        'module' => 'content_commerce',
      ],
    ];

    // Content Types Section Header
    $form['content_section'] = $this->createSectionHeader(
      $this->t('Select Content Types'),
      $this->t('Choose the content types you need for your site. You can add or modify them later.')
    );

    $form['content_types_grid'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['grid', 'grid-cols-1', 'md:grid-cols-2', 'gap-4', 'mb-8']],
    ];

    foreach ($default_types as $type_id => $type_info) {
      $default_value = isset($saved_values[$type_id]) ? $saved_values[$type_id]['enabled'] : $type_info['default'];

      $form['content_types_grid'][$type_id] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['p-4', 'border', 'border-gray-200', 'rounded-lg', 'hover:border-blue-300', 'transition-colors']],
      ];

      $form['content_types_grid'][$type_id]['checkbox'] = [
        '#type' => 'checkbox',
        '#title' => '<span class="font-medium text-gray-900">' . $type_info['name'] . '</span>',
        '#default_value' => $default_value,
        '#parents' => ['types', $type_id, 'enabled'],
      ];

      $form['content_types_grid'][$type_id]['desc'] = [
        '#markup' => '<p class="text-sm text-gray-500 ml-6 mt-1">' . $type_info['description'] . '</p>',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function submitStepForm(array &$form, FormStateInterface $form_state): void {
    $types = $form_state->getValue('types') ?? [];
    $content_types = [];

    // Default field configuration (without Tags - Tags is Article only).
    $default_fields = [
      'body' => [
        'field_name' => 'body',
        'type' => 'text_with_summary',
        'label' => 'Body',
        'widget' => 'text_textarea_with_summary',
        'formatter' => 'text_default',
        'required' => FALSE,
      ],
      'field_image' => [
        'field_name' => 'field_image',
        'type' => 'image',
        'label' => 'Image',
        'cardinality' => -1,
        'widget' => 'image_image',
        'formatter' => 'image',
        'required' => FALSE,
      ],
    ];

    // Tags field - only for Article content type.
    $tags_field = [
      'field_name' => 'field_tags',
      'type' => 'entity_reference',
      'label' => 'Tags',
      'cardinality' => -1,
      'widget' => 'entity_reference_autocomplete_tags',
      'formatter' => 'entity_reference_label',
      'required' => FALSE,
      'settings' => [
        'target_type' => 'taxonomy_term',
        'handler_settings' => [
          'target_bundles' => ['tags'],
        ],
      ],
    ];

    // Type-specific configurations.
    // Only core types + custom module types.
    $type_configs = [
      'page' => [
        'name' => 'Basic Page',
        'description' => 'Use basic pages for static content.',
      ],
      'article' => [
        'name' => 'Article',
        'description' => 'Blog posts and news with images and video.',
        'module' => 'content_article',
      ],
      'team_member' => [
        'name' => 'Team Member',
        'description' => 'Staff profiles.',
        'module' => 'content_team',
      ],
      'faq' => [
        'name' => 'FAQ',
        'description' => 'Frequently asked questions.',
        'module' => 'content_faq',
      ],
      'service' => [
        'name' => 'Service',
        'description' => 'Services offered by your organization.',
        'module' => 'content_services',
      ],
      'product' => [
        'name' => 'Product',
        'description' => 'E-commerce products with images and pricing.',
        'module' => 'content_commerce',
      ],
    ];

    // Track modules to enable for content types.
    $content_type_modules = [];

    foreach ($types as $type_id => $type_data) {
      if (!empty($type_data['enabled'])) {
        $config = $type_configs[$type_id] ?? [];

        // If this content type is provided by a module, just track the module.
        if (!empty($config['module'])) {
          $content_type_modules[] = $config['module'];
          continue;
        }

        $fields = $default_fields;

        // Add Tags field only for Article content type.
        if ($type_id === 'article') {
          $fields['field_tags'] = $tags_field;
        }

        // Add extra fields if defined.
        if (!empty($config['extra_fields'])) {
          $fields = array_merge($fields, $config['extra_fields']);
        }

        $content_types[$type_id] = [
          'type' => $type_id,
          'name' => $config['name'] ?? ucfirst(str_replace('_', ' ', $type_id)),
          'description' => $config['description'] ?? '',
          'enabled' => TRUE,
          'fields' => $fields,
        ];
      }
    }

    $this->saveToState('content_types', $content_types);
    $this->saveToState('content_type_modules', $content_type_modules);
  }

}
