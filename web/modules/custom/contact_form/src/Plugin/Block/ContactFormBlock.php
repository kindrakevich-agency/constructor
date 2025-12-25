<?php

namespace Drupal\contact_form\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Contact Form' block.
 *
 * @Block(
 *   id = "contact_form_block",
 *   admin_label = @Translation("Contact Form"),
 *   category = @Translation("Forms")
 * )
 */
class ContactFormBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a new ContactFormBlock.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      // Header settings.
      'section_title' => 'Contact us',
      'section_subtitle' => 'Get in Touch with Our Team',
      'section_description' => "We're here to answer your questions, discuss your project, and help you find the best solutions for your software needs. Reach out to us, and let's start building something great together.",
      'form_title' => "Let's Talk About Your Project",
      // Contact info.
      'contact_title' => 'Prefer a Direct Approach?',
      'phone' => '+62-8234-5674-8901',
      'email' => 'contact@example.com',
      'working_hours' => 'Monday to Friday, 9 AM - 6 PM (GMT)',
      // Map settings.
      'office_title' => 'Visit Our Office',
      'address' => '123 SaaS Street, Innovation City, Techland 56789',
      'map_latitude' => '34.0507',
      'map_longitude' => '-118.2437',
      'directions_url' => 'https://www.google.com/maps',
      // Thank you message.
      'success_title' => 'Thank You!',
      'success_message' => "Your message has been sent successfully. We'll get back to you within 24 hours.",
      'success_button_text' => 'Close',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->configuration;

    // Header settings.
    $form['header'] = [
      '#type' => 'details',
      '#title' => $this->t('Header Settings'),
      '#open' => TRUE,
    ];

    $form['header']['section_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Section title'),
      '#default_value' => $config['section_title'],
    ];

    $form['header']['section_subtitle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Section subtitle'),
      '#default_value' => $config['section_subtitle'],
    ];

    $form['header']['section_description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Section description'),
      '#default_value' => $config['section_description'],
      '#rows' => 3,
    ];

    $form['header']['form_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Form title'),
      '#default_value' => $config['form_title'],
    ];

    // Contact info settings.
    $form['contact'] = [
      '#type' => 'details',
      '#title' => $this->t('Contact Information'),
      '#open' => TRUE,
    ];

    $form['contact']['contact_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Contact section title'),
      '#default_value' => $config['contact_title'],
    ];

    $form['contact']['phone'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Phone number'),
      '#default_value' => $config['phone'],
    ];

    $form['contact']['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#default_value' => $config['email'],
    ];

    $form['contact']['working_hours'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Working hours'),
      '#default_value' => $config['working_hours'],
    ];

    // Map settings.
    $form['map'] = [
      '#type' => 'details',
      '#title' => $this->t('Map & Address'),
      '#open' => TRUE,
    ];

    $form['map']['office_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Office card title'),
      '#default_value' => $config['office_title'],
    ];

    $form['map']['address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Address'),
      '#default_value' => $config['address'],
    ];

    $form['map']['map_latitude'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Map latitude'),
      '#default_value' => $config['map_latitude'],
      '#description' => $this->t('e.g., 34.0507'),
    ];

    $form['map']['map_longitude'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Map longitude'),
      '#default_value' => $config['map_longitude'],
      '#description' => $this->t('e.g., -118.2437'),
    ];

    $form['map']['directions_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Directions URL'),
      '#default_value' => $config['directions_url'],
      '#description' => $this->t('Link to Google Maps or similar.'),
    ];

    // Success message settings.
    $form['success'] = [
      '#type' => 'details',
      '#title' => $this->t('Success Message'),
      '#open' => TRUE,
    ];

    $form['success']['success_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Success title'),
      '#default_value' => $config['success_title'],
    ];

    $form['success']['success_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Success message'),
      '#default_value' => $config['success_message'],
      '#rows' => 3,
    ];

    $form['success']['success_button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Close button text'),
      '#default_value' => $config['success_button_text'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Header settings.
    $this->configuration['section_title'] = $form_state->getValue(['header', 'section_title']);
    $this->configuration['section_subtitle'] = $form_state->getValue(['header', 'section_subtitle']);
    $this->configuration['section_description'] = $form_state->getValue(['header', 'section_description']);
    $this->configuration['form_title'] = $form_state->getValue(['header', 'form_title']);

    // Contact info.
    $this->configuration['contact_title'] = $form_state->getValue(['contact', 'contact_title']);
    $this->configuration['phone'] = $form_state->getValue(['contact', 'phone']);
    $this->configuration['email'] = $form_state->getValue(['contact', 'email']);
    $this->configuration['working_hours'] = $form_state->getValue(['contact', 'working_hours']);

    // Map settings.
    $this->configuration['office_title'] = $form_state->getValue(['map', 'office_title']);
    $this->configuration['address'] = $form_state->getValue(['map', 'address']);
    $this->configuration['map_latitude'] = $form_state->getValue(['map', 'map_latitude']);
    $this->configuration['map_longitude'] = $form_state->getValue(['map', 'map_longitude']);
    $this->configuration['directions_url'] = $form_state->getValue(['map', 'directions_url']);

    // Success message.
    $this->configuration['success_title'] = $form_state->getValue(['success', 'success_title']);
    $this->configuration['success_message'] = $form_state->getValue(['success', 'success_message']);
    $this->configuration['success_button_text'] = $form_state->getValue(['success', 'success_button_text']);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->configuration;

    // Build map URL from coordinates.
    $lat = $config['map_latitude'];
    $lng = $config['map_longitude'];
    $bbox_offset = 0.01;
    $map_url = sprintf(
      'https://www.openstreetmap.org/export/embed.html?bbox=%s,%s,%s,%s&layer=mapnik&marker=%s,%s',
      $lng - $bbox_offset,
      $lat - $bbox_offset,
      $lng + $bbox_offset,
      $lat + $bbox_offset,
      $lat,
      $lng
    );

    // Get the form.
    $form = $this->formBuilder->getForm('Drupal\contact_form\Form\ContactForm');

    return [
      '#theme' => 'contact_form_block',
      '#section_title' => $config['section_title'],
      '#section_subtitle' => $config['section_subtitle'],
      '#section_description' => $config['section_description'],
      '#form_title' => $config['form_title'],
      '#contact_title' => $config['contact_title'],
      '#phone' => $config['phone'],
      '#email' => $config['email'],
      '#working_hours' => $config['working_hours'],
      '#office_title' => $config['office_title'],
      '#address' => $config['address'],
      '#map_url' => $map_url,
      '#directions_url' => $config['directions_url'],
      '#form' => $form,
      '#attached' => [
        'library' => [
          'contact_form/contact_form',
        ],
        'drupalSettings' => [
          'contactForm' => [
            'successTitle' => $config['success_title'],
            'successSubtitle' => $this->t('Your message has been received'),
            'successMessage' => $config['success_message'],
            'successButtonText' => $config['success_button_text'],
          ],
        ],
      ],
    ];
  }

}
