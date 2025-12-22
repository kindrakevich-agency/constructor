<?php

namespace Drupal\content_faq\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
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
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a FaqPageController object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('language_manager')
    );
  }

  /**
   * Builds the FAQ page content.
   */
  public function content() {
    $faqs = [];
    $current_langcode = $this->languageManager->getCurrentLanguage()->getId();

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
        // Get translated version if available.
        if ($node->hasTranslation($current_langcode)) {
          $node = $node->getTranslation($current_langcode);
        }

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
        'contexts' => ['languages:language_content'],
      ],
    ];
  }

}
