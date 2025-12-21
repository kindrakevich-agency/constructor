<?php

namespace Drupal\content_faq\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the FAQ page.
 */
class FaqPageController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a FaqPageController object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Builds the FAQ page content.
   */
  public function content() {
    $faqs = [];

    // Load all published FAQ nodes.
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'faq')
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->accessCheck(TRUE);

    $nids = $query->execute();

    if (!empty($nids)) {
      $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

      foreach ($nodes as $node) {
        $faqs[] = [
          'question' => $node->getTitle(),
          'answer' => $node->get('field_faq_answer')->value,
        ];
      }
    }

    return [
      '#theme' => 'faq_page',
      '#faqs' => $faqs,
      '#attached' => [
        'library' => [
          'content_faq/faq-accordion',
        ],
      ],
      '#cache' => [
        'tags' => ['node_list:faq'],
      ],
    ];
  }

}
