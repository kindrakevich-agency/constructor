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

    // Default gradients.
    $default_gradients = [
      'linear-gradient(180deg, #fde4cf 0%, #ffcfd2 100%)',
      'linear-gradient(180deg, #fbc2eb 0%, #a6c1ee 100%)',
      'linear-gradient(180deg, #a1c4fd 0%, #c2e9fb 100%)',
      'linear-gradient(180deg, #d4fc79 0%, #96e6a1 100%)',
      'linear-gradient(180deg, #ffecd2 0%, #fcb69f 100%)',
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

        // Check for actual photo field first.
        if ($node->hasField('field_team_photo') && !$node->get('field_team_photo')->isEmpty()) {
          $file = $node->get('field_team_photo')->entity;
          if ($file instanceof File) {
            $image_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
          }
        }

        // Fall back to image URL field.
        if (empty($image_url) && $node->hasField('field_team_image_url')) {
          $image_url = $node->get('field_team_image_url')->value;
        }

        // Use default image if still empty.
        if (empty($image_url)) {
          $image_url = $default_images[$index % count($default_images)];
        }

        $gradient = '';
        if ($node->hasField('field_team_gradient')) {
          $gradient = $node->get('field_team_gradient')->value;
        }
        if (empty($gradient)) {
          $gradient = $default_gradients[$index % count($default_gradients)];
        }

        $team_members[] = [
          'name' => $node->getTitle(),
          'position' => $node->get('field_team_position')->value,
          'image_url' => $image_url,
          'gradient' => $gradient,
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
