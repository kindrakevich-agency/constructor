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
    $default_types = [
      'page' => [
        'name' => 'Basic Page',
        'description' => 'Use basic pages for static content like About us, Contact.',
        'default' => TRUE,
      ],
      'article' => [
        'name' => 'Article',
        'description' => 'Use articles for time-sensitive content like news, blog posts.',
        'default' => TRUE,
      ],
      'landing_page' => [
        'name' => 'Landing Page',
        'description' => 'Flexible landing pages with customizable sections.',
        'default' => FALSE,
      ],
      'event' => [
        'name' => 'Event',
        'description' => 'Events with date, location, and registration.',
        'default' => FALSE,
      ],
      'service' => [
        'name' => 'Service',
        'description' => 'Services or products your organization offers.',
        'default' => FALSE,
      ],
      'team_member' => [
        'name' => 'Team Member',
        'description' => 'Staff profiles with photo, bio, and contact info.',
        'default' => FALSE,
      ],
      'faq' => [
        'name' => 'FAQ',
        'description' => 'Frequently asked questions with answers. Includes FAQ block.',
        'default' => FALSE,
        'module' => 'content_faq',
      ],
      'testimonial' => [
        'name' => 'Testimonial',
        'description' => 'Customer testimonials and reviews.',
        'default' => FALSE,
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

    // Custom Content Type Section
    $form['custom_section'] = $this->createSectionHeader(
      $this->t('Add Custom Content Type'),
      $this->t('Optionally create a custom content type during installation.')
    );

    $form['custom_type'] = [
      '#type' => 'details',
      '#title' => $this->t('Custom Content Type'),
      '#open' => FALSE,
      '#attributes' => ['class' => ['mb-6', 'border', 'border-gray-200', 'rounded-lg', 'p-4']],
    ];

    $form['custom_type']['custom_machine_name'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine Name'),
      '#machine_name' => [
        'exists' => [$this, 'contentTypeExists'],
      ],
      '#required' => FALSE,
      '#attributes' => [
        'class' => ['w-full', 'px-4', 'py-3', 'border', 'border-gray-200', 'rounded-lg'],
      ],
      '#wrapper_attributes' => ['class' => ['mb-4']],
    ];

    $form['custom_type']['custom_name'] = $this->createTextField(
      $this->t('Display Name'),
      '',
      FALSE,
      $this->t('e.g., Portfolio Item')
    );

    $form['custom_type']['custom_description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#rows' => 2,
      '#required' => FALSE,
      '#attributes' => [
        'class' => ['w-full', 'px-4', 'py-3', 'border', 'border-gray-200', 'rounded-lg', 'resize-none'],
        'placeholder' => $this->t('Brief description of this content type...'),
      ],
      '#wrapper_attributes' => ['class' => ['mb-4']],
    ];

    // Default Fields Info Section
    $form['fields_section'] = $this->createSectionHeader(
      $this->t('Default Fields'),
      ''
    );

    $form['fields_info'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['p-4', 'bg-gray-50', 'rounded-lg', 'mb-6']],
    ];

    $form['fields_info']['content'] = [
      '#markup' => '<p class="text-sm text-gray-600 mb-3">' . $this->t('Each content type will include these base fields by default:') . '</p>
        <ul class="space-y-2 text-sm text-gray-600">
          <li class="flex items-center gap-2">
            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <strong class="text-gray-900">Title</strong> — The content title
          </li>
          <li class="flex items-center gap-2">
            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <strong class="text-gray-900">Body</strong> — Main content area with WYSIWYG editor
          </li>
          <li class="flex items-center gap-2">
            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <strong class="text-gray-900">Image</strong> — Featured image with multi-upload support
          </li>
          <li class="flex items-center gap-2">
            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <strong class="text-gray-900">Tags</strong> — Taxonomy reference for categorization <span class="text-gray-400">(Article only)</span>
          </li>
        </ul>
        <p class="text-sm text-gray-500 mt-3">' . $this->t('You can customize fields for each content type after installation.') . '</p>',
    ];

    return $form;
  }

  /**
   * Check if a content type exists.
   */
  public function contentTypeExists($value) {
    return (bool) \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->load($value);
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
    $type_configs = [
      'page' => [
        'name' => 'Basic Page',
        'description' => 'Use basic pages for static content.',
      ],
      'article' => [
        'name' => 'Article',
        'description' => 'Use articles for time-sensitive content.',
      ],
      'landing_page' => [
        'name' => 'Landing Page',
        'description' => 'Flexible landing pages with sections.',
      ],
      'event' => [
        'name' => 'Event',
        'description' => 'Events with date and location.',
        'extra_fields' => [
          'field_event_date' => [
            'field_name' => 'field_event_date',
            'type' => 'datetime',
            'label' => 'Event Date',
            'widget' => 'datetime_default',
            'formatter' => 'datetime_default',
            'required' => TRUE,
          ],
          'field_location' => [
            'field_name' => 'field_location',
            'type' => 'string',
            'label' => 'Location',
            'widget' => 'string_textfield',
            'formatter' => 'string',
            'required' => FALSE,
          ],
        ],
      ],
      'service' => [
        'name' => 'Service',
        'description' => 'Services your organization offers.',
      ],
      'team_member' => [
        'name' => 'Team Member',
        'description' => 'Staff profiles.',
        'extra_fields' => [
          'field_position' => [
            'field_name' => 'field_position',
            'type' => 'string',
            'label' => 'Position',
            'widget' => 'string_textfield',
            'formatter' => 'string',
            'required' => FALSE,
          ],
          'field_email' => [
            'field_name' => 'field_email',
            'type' => 'email',
            'label' => 'Email',
            'widget' => 'email_default',
            'formatter' => 'email_mailto',
            'required' => FALSE,
          ],
        ],
      ],
      'faq' => [
        'name' => 'FAQ',
        'description' => 'Frequently asked questions.',
        'module' => 'content_faq',
      ],
      'testimonial' => [
        'name' => 'Testimonial',
        'description' => 'Customer testimonials.',
        'extra_fields' => [
          'field_author_name' => [
            'field_name' => 'field_author_name',
            'type' => 'string',
            'label' => 'Author Name',
            'widget' => 'string_textfield',
            'formatter' => 'string',
            'required' => TRUE,
          ],
          'field_company' => [
            'field_name' => 'field_company',
            'type' => 'string',
            'label' => 'Company',
            'widget' => 'string_textfield',
            'formatter' => 'string',
            'required' => FALSE,
          ],
        ],
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

    // Handle custom content type.
    $custom_machine_name = $form_state->getValue('custom_machine_name');
    $custom_name = $form_state->getValue('custom_name');
    if (!empty($custom_machine_name) && !empty($custom_name)) {
      $content_types[$custom_machine_name] = [
        'type' => $custom_machine_name,
        'name' => $custom_name,
        'description' => $form_state->getValue('custom_description') ?? '',
        'enabled' => TRUE,
        'fields' => $default_fields,
      ];
    }

    $this->saveToState('content_types', $content_types);
    $this->saveToState('content_type_modules', $content_type_modules);
  }

}
