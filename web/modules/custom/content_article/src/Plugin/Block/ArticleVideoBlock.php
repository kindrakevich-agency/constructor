<?php

namespace Drupal\content_article\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an Article Video Block.
 *
 * Displays a random video article in a full-width video banner style.
 *
 * @Block(
 *   id = "article_video_block",
 *   admin_label = @Translation("Article Video Block"),
 *   category = @Translation("Content")
 * )
 */
class ArticleVideoBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * Constructs a new ArticleVideoBlock instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'title' => 'Cultivating success together',
      'subtitle' => 'Watch how we help farmers achieve bigger and better harvests',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $this->configuration['title'],
    ];

    $form['subtitle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subtitle'),
      '#default_value' => $this->configuration['subtitle'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['title'] = $form_state->getValue('title');
    $this->configuration['subtitle'] = $form_state->getValue('subtitle');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $current_langcode = $this->languageManager->getCurrentLanguage()->getId();

    // Load Article nodes that have video URL set.
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'article')
      ->condition('status', 1)
      ->condition('field_article_video_url', '', '<>')
      ->accessCheck(TRUE);

    $nids = $query->execute();

    if (empty($nids)) {
      return [];
    }

    // Get a random article.
    $random_nid = array_rand(array_flip($nids));
    $node = $this->entityTypeManager->getStorage('node')->load($random_nid);

    if (!$node) {
      return [];
    }

    // Get translated version if available.
    if ($node->hasTranslation($current_langcode)) {
      $node = $node->getTranslation($current_langcode);
    }

    // Get YouTube video ID.
    $video_url = $node->get('field_article_video_url')->value ?? '';
    $youtube_id = content_article_extract_youtube_id($video_url);

    if (!$youtube_id) {
      return [];
    }

    return [
      '#theme' => 'article_video_block',
      '#title' => $this->configuration['title'],
      '#subtitle' => $this->configuration['subtitle'],
      '#youtube_id' => $youtube_id,
      '#article_title' => $node->getTitle(),
      '#article_url' => $node->toUrl()->toString(),
      '#attached' => [
        'library' => [
          'constructor_theme/video',
        ],
      ],
      '#cache' => [
        'tags' => ['node_list:article'],
        'contexts' => ['languages:language_content'],
        'max-age' => 0,
      ],
    ];
  }

}
