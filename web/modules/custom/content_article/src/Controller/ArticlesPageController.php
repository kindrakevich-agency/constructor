<?php

namespace Drupal\content_article\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the Articles listing page.
 */
class ArticlesPageController extends ControllerBase {

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
   * Constructs an ArticlesPageController object.
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
   * Builds the Articles page content.
   */
  public function content() {
    $articles = [];
    $current_langcode = $this->languageManager->getCurrentLanguage()->getId();

    // Load all published Article nodes.
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'article')
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

        // Get image URL.
        $image_url = '';
        $image_alt = '';
        if ($node->hasField('field_article_image') && !$node->get('field_article_image')->isEmpty()) {
          $image_field = $node->get('field_article_image')->first();
          if ($image_field) {
            $file_id = $image_field->get('target_id')->getValue();
            if ($file_id) {
              $file = File::load($file_id);
              if ($file) {
                $image_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
              }
            }
            $image_alt = $image_field->get('alt')->getValue() ?? $node->getTitle();
          }
        }

        // Get YouTube video ID.
        $video_url = $node->get('field_article_video_url')->value ?? '';
        $youtube_id = content_article_extract_youtube_id($video_url);

        // Get body.
        $body = '';
        if ($node->hasField('field_article_body') && !$node->get('field_article_body')->isEmpty()) {
          $body = $node->get('field_article_body')->value;
        }

        $articles[] = [
          'id' => $node->id(),
          'title' => $node->getTitle(),
          'body' => $body,
          'url' => $node->toUrl()->toString(),
          'image_url' => $image_url,
          'image_alt' => $image_alt,
          'youtube_id' => $youtube_id,
          'has_video' => !empty($youtube_id),
          'created' => $node->getCreatedTime(),
        ];
      }
    }

    return [
      '#theme' => 'articles_page',
      '#articles' => $articles,
      '#attached' => [
        'library' => [
          'content_article/articles-block',
        ],
      ],
      '#cache' => [
        'tags' => ['node_list:article'],
        'contexts' => ['languages:language_content'],
      ],
    ];
  }

}
