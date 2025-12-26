<?php

namespace Drupal\constructor_hero\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Booking Modal Block.
 *
 * This block renders a booking modal that can be triggered by buttons with
 * the class 'open-booking-modal'. Also provides a header button that can
 * be enabled/disabled.
 *
 * @Block(
 *   id = "booking_modal_block",
 *   admin_label = @Translation("Booking Modal"),
 *   category = @Translation("Constructor")
 * )
 */
class BookingModalBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'modal_title' => 'Book a Demo',
      'modal_description' => 'Fill out the form below and we\'ll get back to you shortly.',
      'show_header_button' => TRUE,
      'header_button_text' => 'Book a demo',
      'show_name_field' => TRUE,
      'name_field_label' => 'Full Name',
      'name_field_placeholder' => 'John Doe',
      'name_field_required' => TRUE,
      'show_email_field' => TRUE,
      'email_field_label' => 'Email',
      'email_field_placeholder' => 'john@example.com',
      'email_field_required' => TRUE,
      'show_company_field' => FALSE,
      'company_field_label' => 'Company',
      'company_field_placeholder' => 'Your company name',
      'company_field_required' => FALSE,
      'show_phone_field' => TRUE,
      'phone_field_label' => 'Phone Number',
      'phone_field_placeholder' => '+1 (555) 000-0000',
      'phone_field_required' => FALSE,
      'show_message_field' => TRUE,
      'message_field_label' => 'Message',
      'message_field_placeholder' => 'Tell us about your needs...',
      'message_field_required' => FALSE,
      'submit_button_text' => 'Submit Request',
      'success_title' => 'Thank You!',
      'success_message' => "We've received your request and will contact you shortly.",
      'success_button_text' => 'Close',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // Modal Settings.
    $form['modal_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Modal Settings'),
      '#open' => TRUE,
    ];

    $form['modal_section']['modal_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Modal title'),
      '#default_value' => $this->configuration['modal_title'],
    ];

    $form['modal_section']['modal_description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Modal description'),
      '#default_value' => $this->configuration['modal_description'],
      '#rows' => 2,
    ];

    // Header Button Settings.
    $form['header_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Header Button'),
      '#open' => TRUE,
    ];

    $form['header_section']['show_header_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show "Book a demo" button in header'),
      '#default_value' => $this->configuration['show_header_button'],
      '#description' => $this->t('Display a button in the header that opens the booking modal.'),
    ];

    $form['header_section']['header_button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Header button text'),
      '#default_value' => $this->configuration['header_button_text'],
      '#states' => [
        'visible' => [
          ':input[name="settings[header_section][show_header_button]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Name Field.
    $form['name_field'] = [
      '#type' => 'details',
      '#title' => $this->t('Name Field'),
      '#open' => FALSE,
    ];

    $form['name_field']['show_name_field'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show name field'),
      '#default_value' => $this->configuration['show_name_field'],
    ];

    $form['name_field']['name_field_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $this->configuration['name_field_label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[name_field][show_name_field]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['name_field']['name_field_placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#default_value' => $this->configuration['name_field_placeholder'],
      '#states' => [
        'visible' => [
          ':input[name="settings[name_field][show_name_field]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['name_field']['name_field_required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Required'),
      '#default_value' => $this->configuration['name_field_required'],
      '#states' => [
        'visible' => [
          ':input[name="settings[name_field][show_name_field]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Email Field.
    $form['email_field'] = [
      '#type' => 'details',
      '#title' => $this->t('Email Field'),
      '#open' => FALSE,
    ];

    $form['email_field']['show_email_field'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show email field'),
      '#default_value' => $this->configuration['show_email_field'],
    ];

    $form['email_field']['email_field_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $this->configuration['email_field_label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[email_field][show_email_field]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['email_field']['email_field_placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#default_value' => $this->configuration['email_field_placeholder'],
      '#states' => [
        'visible' => [
          ':input[name="settings[email_field][show_email_field]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['email_field']['email_field_required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Required'),
      '#default_value' => $this->configuration['email_field_required'],
      '#states' => [
        'visible' => [
          ':input[name="settings[email_field][show_email_field]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Company Field.
    $form['company_field'] = [
      '#type' => 'details',
      '#title' => $this->t('Company Field'),
      '#open' => FALSE,
    ];

    $form['company_field']['show_company_field'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show company field'),
      '#default_value' => $this->configuration['show_company_field'],
    ];

    $form['company_field']['company_field_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $this->configuration['company_field_label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[company_field][show_company_field]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['company_field']['company_field_placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#default_value' => $this->configuration['company_field_placeholder'],
      '#states' => [
        'visible' => [
          ':input[name="settings[company_field][show_company_field]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['company_field']['company_field_required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Required'),
      '#default_value' => $this->configuration['company_field_required'],
      '#states' => [
        'visible' => [
          ':input[name="settings[company_field][show_company_field]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Phone Field.
    $form['phone_field'] = [
      '#type' => 'details',
      '#title' => $this->t('Phone Field'),
      '#open' => FALSE,
    ];

    $form['phone_field']['show_phone_field'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show phone field'),
      '#default_value' => $this->configuration['show_phone_field'],
    ];

    $form['phone_field']['phone_field_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $this->configuration['phone_field_label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[phone_field][show_phone_field]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['phone_field']['phone_field_placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#default_value' => $this->configuration['phone_field_placeholder'],
      '#states' => [
        'visible' => [
          ':input[name="settings[phone_field][show_phone_field]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['phone_field']['phone_field_required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Required'),
      '#default_value' => $this->configuration['phone_field_required'],
      '#states' => [
        'visible' => [
          ':input[name="settings[phone_field][show_phone_field]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Message Field.
    $form['message_field'] = [
      '#type' => 'details',
      '#title' => $this->t('Message Field'),
      '#open' => FALSE,
    ];

    $form['message_field']['show_message_field'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show message field'),
      '#default_value' => $this->configuration['show_message_field'],
    ];

    $form['message_field']['message_field_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $this->configuration['message_field_label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[message_field][show_message_field]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['message_field']['message_field_placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#default_value' => $this->configuration['message_field_placeholder'],
      '#states' => [
        'visible' => [
          ':input[name="settings[message_field][show_message_field]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['message_field']['message_field_required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Required'),
      '#default_value' => $this->configuration['message_field_required'],
      '#states' => [
        'visible' => [
          ':input[name="settings[message_field][show_message_field]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Submit Button.
    $form['submit_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Submit Button'),
      '#open' => FALSE,
    ];

    $form['submit_section']['submit_button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Submit button text'),
      '#default_value' => $this->configuration['submit_button_text'],
    ];

    // Success Message.
    $form['success_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Success Message'),
      '#open' => FALSE,
    ];

    $form['success_section']['success_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Success title'),
      '#default_value' => $this->configuration['success_title'],
    ];

    $form['success_section']['success_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Success message'),
      '#default_value' => $this->configuration['success_message'],
      '#rows' => 2,
    ];

    $form['success_section']['success_button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Close button text'),
      '#default_value' => $this->configuration['success_button_text'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Modal Section.
    $modal_section = $form_state->getValue('modal_section');
    $this->configuration['modal_title'] = $modal_section['modal_title'];
    $this->configuration['modal_description'] = $modal_section['modal_description'];

    // Header Section.
    $header_section = $form_state->getValue('header_section');
    $this->configuration['show_header_button'] = $header_section['show_header_button'];
    $this->configuration['header_button_text'] = $header_section['header_button_text'];

    // Name Field.
    $name_field = $form_state->getValue('name_field');
    $this->configuration['show_name_field'] = $name_field['show_name_field'];
    $this->configuration['name_field_label'] = $name_field['name_field_label'];
    $this->configuration['name_field_placeholder'] = $name_field['name_field_placeholder'];
    $this->configuration['name_field_required'] = $name_field['name_field_required'];

    // Email Field.
    $email_field = $form_state->getValue('email_field');
    $this->configuration['show_email_field'] = $email_field['show_email_field'];
    $this->configuration['email_field_label'] = $email_field['email_field_label'];
    $this->configuration['email_field_placeholder'] = $email_field['email_field_placeholder'];
    $this->configuration['email_field_required'] = $email_field['email_field_required'];

    // Company Field.
    $company_field = $form_state->getValue('company_field');
    $this->configuration['show_company_field'] = $company_field['show_company_field'];
    $this->configuration['company_field_label'] = $company_field['company_field_label'];
    $this->configuration['company_field_placeholder'] = $company_field['company_field_placeholder'];
    $this->configuration['company_field_required'] = $company_field['company_field_required'];

    // Phone Field.
    $phone_field = $form_state->getValue('phone_field');
    $this->configuration['show_phone_field'] = $phone_field['show_phone_field'];
    $this->configuration['phone_field_label'] = $phone_field['phone_field_label'];
    $this->configuration['phone_field_placeholder'] = $phone_field['phone_field_placeholder'];
    $this->configuration['phone_field_required'] = $phone_field['phone_field_required'];

    // Message Field.
    $message_field = $form_state->getValue('message_field');
    $this->configuration['show_message_field'] = $message_field['show_message_field'];
    $this->configuration['message_field_label'] = $message_field['message_field_label'];
    $this->configuration['message_field_placeholder'] = $message_field['message_field_placeholder'];
    $this->configuration['message_field_required'] = $message_field['message_field_required'];

    // Submit Section.
    $submit_section = $form_state->getValue('submit_section');
    $this->configuration['submit_button_text'] = $submit_section['submit_button_text'];

    // Success Section.
    $success_section = $form_state->getValue('success_section');
    $this->configuration['success_title'] = $success_section['success_title'];
    $this->configuration['success_message'] = $success_section['success_message'];
    $this->configuration['success_button_text'] = $success_section['success_button_text'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $fields = [];

    if ($this->configuration['show_name_field']) {
      $fields[] = [
        'type' => 'text',
        'name' => 'name',
        'label' => $this->configuration['name_field_label'],
        'placeholder' => $this->configuration['name_field_placeholder'],
        'required' => $this->configuration['name_field_required'],
      ];
    }

    if ($this->configuration['show_email_field']) {
      $fields[] = [
        'type' => 'email',
        'name' => 'email',
        'label' => $this->configuration['email_field_label'],
        'placeholder' => $this->configuration['email_field_placeholder'],
        'required' => $this->configuration['email_field_required'],
      ];
    }

    if ($this->configuration['show_company_field']) {
      $fields[] = [
        'type' => 'text',
        'name' => 'company',
        'label' => $this->configuration['company_field_label'],
        'placeholder' => $this->configuration['company_field_placeholder'],
        'required' => $this->configuration['company_field_required'],
      ];
    }

    if ($this->configuration['show_phone_field']) {
      $fields[] = [
        'type' => 'tel',
        'name' => 'phone',
        'label' => $this->configuration['phone_field_label'],
        'placeholder' => $this->configuration['phone_field_placeholder'],
        'required' => $this->configuration['phone_field_required'],
      ];
    }

    if ($this->configuration['show_message_field']) {
      $fields[] = [
        'type' => 'textarea',
        'name' => 'message',
        'label' => $this->configuration['message_field_label'],
        'placeholder' => $this->configuration['message_field_placeholder'],
        'required' => $this->configuration['message_field_required'],
      ];
    }

    // Build field requirements for validation.
    $fieldRequirements = [];
    foreach ($fields as $field) {
      $fieldRequirements[$field['name']] = [
        'required' => $field['required'],
      ];
    }

    return [
      '#theme' => 'booking_modal',
      '#title' => $this->configuration['modal_title'],
      '#description' => $this->configuration['modal_description'],
      '#fields' => $fields,
      '#submit_text' => $this->configuration['submit_button_text'],
      '#show_header_button' => $this->configuration['show_header_button'],
      '#header_button_text' => $this->configuration['header_button_text'],
      '#attached' => [
        'library' => [
          'constructor_hero/booking-modal',
        ],
        'drupalSettings' => [
          'bookingModal' => [
            'successTitle' => $this->configuration['success_title'],
            'successSubtitle' => $this->t('Your request has been received'),
            'successMessage' => $this->configuration['success_message'],
            'successButtonText' => $this->configuration['success_button_text'],
            'fields' => $fieldRequirements,
          ],
        ],
      ],
    ];
  }

}
