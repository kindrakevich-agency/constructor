<?php

namespace Drupal\constructor_hero\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\form_sender\FormSenderService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for booking form API.
 */
class BookingController extends ControllerBase {

  /**
   * The form sender service.
   *
   * @var \Drupal\form_sender\FormSenderService
   */
  protected $formSender;

  /**
   * Constructs a BookingController object.
   */
  public function __construct(FormSenderService $form_sender) {
    $this->formSender = $form_sender;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_sender')
    );
  }

  /**
   * Handle booking form submission via JSON API.
   */
  public function submit(Request $request) {
    $data = json_decode($request->getContent(), TRUE);

    // Validate required fields.
    $errors = [];
    if (!empty($data['fields']['name']['required']) && empty($data['name'])) {
      $errors['name'] = $this->t('Name is required.');
    }
    if (!empty($data['fields']['email']['required'])) {
      if (empty($data['email'])) {
        $errors['email'] = $this->t('Email is required.');
      }
      elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = $this->t('Please enter a valid email address.');
      }
    }
    if (!empty($data['fields']['phone']['required']) && empty($data['phone'])) {
      $errors['phone'] = $this->t('Phone is required.');
    }
    if (!empty($data['fields']['company']['required']) && empty($data['company'])) {
      $errors['company'] = $this->t('Company is required.');
    }
    if (!empty($data['fields']['message']['required']) && empty($data['message'])) {
      $errors['message'] = $this->t('Message is required.');
    }

    if (!empty($errors)) {
      return new JsonResponse([
        'success' => FALSE,
        'errors' => $errors,
      ], 400);
    }

    // Send the form data.
    $this->formSender->send([
      'subject' => $this->t('New Booking Request'),
      'message' => $data['message'] ?? '-',
      'form_type' => 'booking',
      'data' => [
        'name' => $data['name'] ?? '-',
        'email' => $data['email'] ?? '-',
        'phone' => $data['phone'] ?? '-',
        'company' => $data['company'] ?? '-',
      ],
    ]);

    return new JsonResponse(['success' => TRUE]);
  }

}
