<?php

namespace Drupal\content_article\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an Articles Block.
 *
 * @Block(
 *   id = "articles_block",
 *   admin_label = @Translation("Articles Block"),
 *   category = @Translation("Content")
 * )
 */
class ArticlesBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * Constructs a new ArticlesBlock instance.
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
      'title' => 'Real Impact of Driving Electric',
      'subtitle' => 'Use Cases',
      'description' => 'From daily comfort to operational savings, these are the real advantages drivers experience using our smart electric vehicles.',
      'show_more_link' => TRUE,
      'more_link_text' => 'Learn More',
      'more_link_url' => '/articles',
      'limit' => 3,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Block Title'),
      '#default_value' => $this->configuration['title'],
    ];

    $form['subtitle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subtitle'),
      '#default_value' => $this->configuration['subtitle'],
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $this->configuration['description'],
      '#rows' => 3,
    ];

    $form['show_more_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show "View All" link'),
      '#default_value' => $this->configuration['show_more_link'],
    ];

    $form['more_link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link Text'),
      '#default_value' => $this->configuration['more_link_text'],
      '#states' => [
        'visible' => [
          ':input[name="settings[show_more_link]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['more_link_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link URL'),
      '#default_value' => $this->configuration['more_link_url'],
      '#states' => [
        'visible' => [
          ':input[name="settings[show_more_link]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of articles to display'),
      '#default_value' => $this->configuration['limit'],
      '#min' => 1,
      '#max' => 10,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['title'] = $form_state->getValue('title');
    $this->configuration['subtitle'] = $form_state->getValue('subtitle');
    $this->configuration['description'] = $form_state->getValue('description');
    $this->configuration['show_more_link'] = $form_state->getValue('show_more_link');
    $this->configuration['more_link_text'] = $form_state->getValue('more_link_text');
    $this->configuration['more_link_url'] = $form_state->getValue('more_link_url');
    $this->configuration['limit'] = $form_state->getValue('limit');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $articles = [];
    $current_langcode = $this->languageManager->getCurrentLanguage()->getId();

    // Load Article nodes.
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'article')
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->range(0, $this->configuration['limit'])
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

        // Get body summary.
        $body = '';
        if ($node->hasField('field_article_body') && !$node->get('field_article_body')->isEmpty()) {
          $body = $node->get('field_article_body')->value;
          // Trim to ~150 characters for teaser.
          if (strlen(strip_tags($body)) > 150) {
            $body = substr(strip_tags($body), 0, 150) . '...';
          }
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
        ];
      }
    }

    return [
      '#theme' => 'articles_block_content',
      '#articles' => $articles,
      '#title' => $this->configuration['title'],
      '#subtitle' => $this->configuration['subtitle'],
      '#description' => $this->configuration['description'],
      '#show_more_link' => $this->configuration['show_more_link'],
      '#more_link_text' => $this->configuration['more_link_text'],
      '#more_link_url' => $this->configuration['more_link_url'],
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
