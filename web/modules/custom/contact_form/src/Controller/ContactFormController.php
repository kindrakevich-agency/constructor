<?php

namespace Drupal\contact_form\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\form_sender\FormSenderService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for contact form API.
 */
class ContactFormController extends ControllerBase {

  /**
   * The form sender service.
   *
   * @var \Drupal\form_sender\FormSenderService
   */
  protected $formSender;

  /**
   * Constructs a ContactFormController object.
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
   * Handle contact form submission via JSON API.
   */
  public function submit(Request $request) {
    $data = json_decode($request->getContent(), TRUE);

    // Validate required fields.
    $errors = [];
    if (empty($data['name'])) {
      $errors['name'] = $this->t('Name is required.');
    }
    if (empty($data['email'])) {
      $errors['email'] = $this->t('Email is required.');
    }
    elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
      $errors['email'] = $this->t('Please enter a valid email address.');
    }
    if (empty($data['subject'])) {
      $errors['subject'] = $this->t('Subject is required.');
    }
    if (empty($data['message'])) {
      $errors['message'] = $this->t('Message is required.');
    }

    // Check honeypot - if filled, silently pretend success.
    if (!empty($data['website_url'])) {
      return new JsonResponse(['success' => TRUE]);
    }

    if (!empty($errors)) {
      return new JsonResponse([
        'success' => FALSE,
        'errors' => $errors,
      ], 400);
    }

    // Send the form data.
    $this->formSender->send([
      'subject' => $data['subject'],
      'message' => $data['message'],
      'form_type' => 'contact',
      'data' => [
        'name' => $data['name'],
        'email' => $data['email'],
        'company' => $data['company'] ?? '-',
        'subject' => $data['subject'],
      ],
    ]);

    return new JsonResponse(['success' => TRUE]);
  }

}
