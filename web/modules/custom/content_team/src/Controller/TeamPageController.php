<?php

namespace Drupal\content_team\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the Team page.
 */
class TeamPageController extends ControllerBase {

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
   * Constructs a TeamPageController object.
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
   * Builds the Team page content.
   */
  public function content() {
    $team_members = [];
    $current_langcode = $this->languageManager->getCurrentLanguage()->getId();

    // Default Unsplash images for fallback.
    $default_images = [
      'https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=400&h=500&fit=crop&q=80',
      'https://images.unsplash.com/photo-1560250097-0b93528c311a?w=400&h=500&fit=crop&q=80',
      'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=400&h=500&fit=crop&q=80',
      'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=400&h=500&fit=crop&q=80',
      'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&h=500&fit=crop&q=80',
    ];

    // Gradient CSS classes (defined in constructor_theme/css/team-gradients.css).
    $gradient_classes = [
      'team-gradient-peach',
      'team-gradient-pink-blue',
      'team-gradient-blue',
      'team-gradient-green',
      'team-gradient-cream',
    ];

    // Load all published team_member nodes.
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'team_member')
      ->condition('status', 1)
      ->sort('created', 'ASC')
      ->accessCheck(TRUE);

    $nids = $query->execute();

    if (!empty($nids)) {
      $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

      $index = 0;
      foreach ($nodes as $node) {
        // Get translated version if available.
        if ($node->hasTranslation($current_langcode)) {
          $node = $node->getTranslation($current_langcode);
        }

        $image_url = '';

        // Check for actual photo field.
        if ($node->hasField('field_team_photo') && !$node->get('field_team_photo')->isEmpty()) {
          $file = $node->get('field_team_photo')->entity;
          if ($file instanceof File) {
            $image_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
          }
        }

        // Use default image if no photo uploaded.
        if (empty($image_url)) {
          $image_url = $default_images[$index % count($default_images)];
        }

        // Use CSS class for gradient based on index.
        $gradient_class = $gradient_classes[$index % count($gradient_classes)];

        $team_members[] = [
          'name' => $node->getTitle(),
          'position' => $node->get('field_team_position')->value,
          'image_url' => $image_url,
          'gradient_class' => $gradient_class,
          'url' => $node->toUrl()->toString(),
        ];
        $index++;
      }
    }

    return [
      '#theme' => 'team_page',
      '#team_members' => $team_members,
      '#attached' => [
        'library' => [
          'content_team/team-carousel',
        ],
      ],
      '#cache' => [
        'tags' => ['node_list:team_member'],
        'contexts' => ['languages:language_content'],
      ],
    ];
  }

}
