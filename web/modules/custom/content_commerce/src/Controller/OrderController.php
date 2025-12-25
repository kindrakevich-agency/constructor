<?php

namespace Drupal\content_commerce\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\form_sender\FormSenderService;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for order form API.
 */
class OrderController extends ControllerBase {

  /**
   * The form sender service.
   *
   * @var \Drupal\form_sender\FormSenderService
   */
  protected $formSender;

  /**
   * Constructs an OrderController object.
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
   * Handle order form submission via JSON API.
   */
  public function submit(Request $request) {
    $data = json_decode($request->getContent(), TRUE);

    // Validate required fields.
    $errors = [];
    if (empty($data['name'])) {
      $errors['name'] = $this->t('Name is required.');
    }
    if (empty($data['phone'])) {
      $errors['phone'] = $this->t('Phone is required.');
    }

    if (!empty($errors)) {
      return new JsonResponse([
        'success' => FALSE,
        'errors' => $errors,
      ], 400);
    }

    // Get product information.
    $productId = $data['productId'] ?? NULL;
    $productTitle = '-';
    $productPrice = '-';
    if ($productId) {
      $product = Node::load($productId);
      if ($product) {
        $productTitle = $product->getTitle();
        if ($product->hasField('field_price') && !$product->get('field_price')->isEmpty()) {
          $productPrice = $product->get('field_price')->value;
        }
      }
    }

    // Send the form data.
    $this->formSender->send([
      'subject' => $this->t('New Order: @product', ['@product' => $productTitle]),
      'message' => $this->t('Order for product: @product', ['@product' => $productTitle]),
      'form_type' => 'order',
      'data' => [
        'name' => $data['name'] ?? '-',
        'phone' => $data['phone'] ?? '-',
        'address' => $data['address'] ?? '-',
        'product' => $productTitle,
        'price' => $productPrice,
        'quantity' => $data['quantity'] ?? '1',
        'color' => $data['color'] ?? '-',
        'size' => $data['size'] ?? '-',
      ],
    ]);

    return new JsonResponse(['success' => TRUE]);
  }

}
