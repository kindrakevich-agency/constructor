<?php

namespace Drupal\content_commerce\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Product Carousel Block.
 *
 * @Block(
 *   id = "product_carousel_block",
 *   admin_label = @Translation("Product Carousel Block"),
 *   category = @Translation("Commerce")
 * )
 */
class ProductCarouselBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new ProductCarouselBlock instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->configFactory = $config_factory;
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
      $container->get('language_manager'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'limit' => 6,
      'collection_title' => 'NEW PROMAX EYEWEAR COLLECTION',
      'collection_subtitle' => 'An iconic collection inspired by the past and reinvented for the future.',
      'collection_brands' => 'POLICE,Ray-Ban,GUCCI',
      'collection_link_text' => 'Discover the collection',
      'collection_link_url' => '/products',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of products'),
      '#default_value' => $this->configuration['limit'],
      '#min' => 1,
      '#max' => 20,
    ];

    $form['collection_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Collection Title'),
      '#default_value' => $this->configuration['collection_title'],
    ];

    $form['collection_subtitle'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Collection Subtitle'),
      '#default_value' => $this->configuration['collection_subtitle'],
      '#rows' => 2,
    ];

    $form['collection_brands'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Collection Brands'),
      '#default_value' => $this->configuration['collection_brands'],
      '#description' => $this->t('Comma-separated list of brand names to display.'),
    ];

    $form['collection_link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Collection Link Text'),
      '#default_value' => $this->configuration['collection_link_text'],
    ];

    $form['collection_link_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Collection Link URL'),
      '#default_value' => $this->configuration['collection_link_url'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['limit'] = $form_state->getValue('limit');
    $this->configuration['collection_title'] = $form_state->getValue('collection_title');
    $this->configuration['collection_subtitle'] = $form_state->getValue('collection_subtitle');
    $this->configuration['collection_brands'] = $form_state->getValue('collection_brands');
    $this->configuration['collection_link_text'] = $form_state->getValue('collection_link_text');
    $this->configuration['collection_link_url'] = $form_state->getValue('collection_link_url');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $current_langcode = $this->languageManager->getCurrentLanguage()->getId();
    $config = $this->configFactory->get('content_commerce.settings');
    $currency_symbol = $config->get('currency_symbol') ?: '$';

    // Load featured products first, then recent ones.
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'product')
      ->condition('status', 1)
      ->sort('field_product_featured', 'DESC')
      ->sort('created', 'DESC')
      ->range(0, $this->configuration['limit'])
      ->accessCheck(TRUE);

    $nids = $query->execute();
    $products = $this->loadProducts($nids, $current_langcode, $currency_symbol);

    if (empty($products)) {
      return [];
    }

    // Parse brands.
    $brands = [];
    if (!empty($this->configuration['collection_brands'])) {
      $brands = array_map('trim', explode(',', $this->configuration['collection_brands']));
    }

    return [
      '#theme' => 'product_carousel_block',
      '#products' => $products,
      '#collection_title' => $this->configuration['collection_title'],
      '#collection_subtitle' => $this->configuration['collection_subtitle'],
      '#collection_brands' => $brands,
      '#collection_link_text' => $this->configuration['collection_link_text'],
      '#collection_link_url' => $this->configuration['collection_link_url'],
      '#attached' => [
        'library' => [
          'constructor_theme/swiper',
          'content_commerce/product',
        ],
      ],
      '#cache' => [
        'tags' => ['node_list:product'],
        'contexts' => ['languages:language_content'],
      ],
    ];
  }

  /**
   * Load products with translations.
   */
  protected function loadProducts(array $nids, $langcode, $currency_symbol) {
    $products = [];
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

    foreach ($nodes as $node) {
      if ($node->hasTranslation($langcode)) {
        $node = $node->getTranslation($langcode);
      }

      // Get first image.
      $image_url = NULL;
      $images = $node->get('field_product_images')->referencedEntities();
      if (!empty($images)) {
        $image_url = \Drupal::service('file_url_generator')->generateAbsoluteString($images[0]->getFileUri());
      }

      // Get category.
      $category = NULL;
      $category_ref = $node->get('field_product_category')->referencedEntities();
      if (!empty($category_ref)) {
        $cat = reset($category_ref);
        if ($cat->hasTranslation($langcode)) {
          $cat = $cat->getTranslation($langcode);
        }
        $category = $cat->getName();
      }

      $products[] = [
        'id' => $node->id(),
        'title' => $node->getTitle(),
        'url' => $node->toUrl()->toString(),
        'image_url' => $image_url,
        'category' => $category,
      ];
    }

    return $products;
  }

}
