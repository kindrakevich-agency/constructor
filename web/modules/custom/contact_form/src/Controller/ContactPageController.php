<?php

namespace Drupal\contact_form\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the contact page.
 */
class ContactPageController extends ControllerBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a ContactPageController object.
   */
  public function __construct(FormBuilderInterface $form_builder) {
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
  }

  /**
   * Renders the contact page with the contact form.
   */
  public function page() {
    // Default configuration values (same as ContactFormBlock defaults).
    $config = [
      'section_title' => $this->t('Contact us'),
      'section_subtitle' => $this->t('Get in Touch with Our Team'),
      'section_description' => $this->t("We're here to answer your questions, discuss your project, and help you find the best solutions for your software needs. Reach out to us, and let's start building something great together."),
      'form_title' => $this->t("Let's Talk About Your Project"),
      'contact_title' => $this->t('Prefer a Direct Approach?'),
      'phone' => '+62-8234-5674-8901',
      'email' => 'contact@example.com',
      'working_hours' => $this->t('Monday to Friday, 9 AM - 6 PM (GMT)'),
      'office_title' => $this->t('Visit Our Office'),
      'address' => '123 SaaS Street, Innovation City, Techland 56789',
      'map_latitude' => '34.0507',
      'map_longitude' => '-118.2437',
      'directions_url' => 'https://www.google.com/maps',
      'success_title' => $this->t('Thank You!'),
      'success_message' => $this->t("Your message has been sent successfully. We'll get back to you within 24 hours."),
      'success_button_text' => $this->t('Close'),
    ];

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
      '#cache' => [
        'contexts' => ['url.path'],
      ],
    ];
  }

}
