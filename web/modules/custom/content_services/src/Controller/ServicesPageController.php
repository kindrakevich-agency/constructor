<?php

namespace Drupal\content_services\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the Services page.
 */
class ServicesPageController extends ControllerBase {

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
   * Constructs a ServicesPageController object.
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
   * Builds the Services page content.
   */
  public function content() {
    $services = [];
    $current_langcode = $this->languageManager->getCurrentLanguage()->getId();

    // Load all published Service nodes.
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'service')
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

        // Get image URL from field_service_image (image field) or fallback.
        $image_url = 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=500&h=400&fit=crop&q=80';
        if ($node->hasField('field_service_image') && !$node->get('field_service_image')->isEmpty()) {
          $file = $node->get('field_service_image')->entity;
          if ($file) {
            $image_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
          }
        }

        $services[] = [
          'name' => $node->getTitle(),
          'description' => $node->get('field_service_description')->value,
          'image_url' => $image_url,
          'url' => $node->toUrl()->toString(),
        ];
      }
    }

    return [
      '#theme' => 'services_page',
      '#services' => $services,
      '#cache' => [
        'tags' => ['node_list:service'],
        'contexts' => ['languages:language_content'],
      ],
    ];
  }

}
