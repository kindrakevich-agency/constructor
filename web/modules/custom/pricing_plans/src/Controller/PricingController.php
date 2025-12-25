<?php

namespace Drupal\pricing_plans\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\form_sender\FormSenderService;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for pricing form API.
 */
class PricingController extends ControllerBase {

  /**
   * The form sender service.
   *
   * @var \Drupal\form_sender\FormSenderService
   */
  protected $formSender;

  /**
   * Constructs a PricingController object.
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
   * Handle pricing form submission via JSON API.
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

    if (!empty($errors)) {
      return new JsonResponse([
        'success' => FALSE,
        'errors' => $errors,
      ], 400);
    }

    // Get plan information.
    $planId = $data['planId'] ?? NULL;
    $planTitle = $data['planTitle'] ?? '-';
    $planPrice = $data['planPrice'] ?? '-';
    $billingType = $data['billingType'] ?? 'annual';

    if ($planId) {
      $plan = Node::load($planId);
      if ($plan && $plan->bundle() === 'pricing_plan') {
        $planTitle = $plan->getTitle();
        if ($billingType === 'monthly') {
          $planPrice = $plan->get('field_plan_monthly_price')->value ?? '-';
        }
        else {
          $planPrice = $plan->get('field_plan_annual_price')->value ?? $plan->get('field_plan_monthly_price')->value ?? '-';
        }
      }
    }

    // Send the form data.
    $this->formSender->send([
      'subject' => $this->t('Pricing Plan Inquiry: @plan', ['@plan' => $planTitle]),
      'message' => $this->t('New pricing plan inquiry for @plan', ['@plan' => $planTitle]),
      'form_type' => 'pricing',
      'data' => [
        'name' => $data['name'] ?? '-',
        'email' => $data['email'] ?? '-',
        'phone' => $data['phone'] ?? '-',
        'company' => $data['company'] ?? '-',
        'message' => $data['message'] ?? '-',
        'plan' => $planTitle,
        'price' => $planPrice,
        'billing' => $billingType === 'monthly' ? 'Monthly' : 'Annual',
      ],
    ]);

    return new JsonResponse(['success' => TRUE]);
  }

}
