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
 * Provides a Products List Block.
 *
 * @Block(
 *   id = "products_list_block",
 *   admin_label = @Translation("Products List Block"),
 *   category = @Translation("Commerce")
 * )
 */
class ProductsListBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * Constructs a new ProductsListBlock instance.
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
      'hero_title' => 'Find your Color, Own the Season',
      'hero_subtitle' => 'From sunrise to sunset — looks made for every summer moment.',
      'hero_image' => '',
      'hero_cta_text' => 'Explore the Collection',
      'hero_cta_url' => '/products',
      'feature_card_1_title' => 'Laid-Back Luxe',
      'feature_card_1_subtitle' => 'Comfort meets effortless style',
      'feature_card_1_image' => '',
      'feature_card_1_url' => '/products',
      'feature_card_2_title' => 'Everyday Cool',
      'feature_card_2_subtitle' => 'Relaxed fits for work, play, and everything in between.',
      'feature_card_2_image' => '',
      'feature_card_2_url' => '/products',
      'products_title' => 'Trending Now – Step Into Style',
      'limit' => 8,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['hero'] = [
      '#type' => 'details',
      '#title' => $this->t('Hero Banner'),
      '#open' => TRUE,
    ];

    $form['hero']['hero_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hero Title'),
      '#default_value' => $this->configuration['hero_title'],
    ];

    $form['hero']['hero_subtitle'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Hero Subtitle'),
      '#default_value' => $this->configuration['hero_subtitle'],
      '#rows' => 2,
    ];

    $form['hero']['hero_image'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hero Image URL'),
      '#default_value' => $this->configuration['hero_image'],
      '#description' => $this->t('External image URL for hero banner.'),
    ];

    $form['hero']['hero_cta_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hero CTA Text'),
      '#default_value' => $this->configuration['hero_cta_text'],
    ];

    $form['hero']['hero_cta_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hero CTA URL'),
      '#default_value' => $this->configuration['hero_cta_url'],
    ];

    $form['feature_cards'] = [
      '#type' => 'details',
      '#title' => $this->t('Feature Cards'),
      '#open' => FALSE,
    ];

    $form['feature_cards']['feature_card_1_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Card 1 Title'),
      '#default_value' => $this->configuration['feature_card_1_title'],
    ];

    $form['feature_cards']['feature_card_1_subtitle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Card 1 Subtitle'),
      '#default_value' => $this->configuration['feature_card_1_subtitle'],
    ];

    $form['feature_cards']['feature_card_1_image'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Card 1 Image URL'),
      '#default_value' => $this->configuration['feature_card_1_image'],
    ];

    $form['feature_cards']['feature_card_1_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Card 1 URL'),
      '#default_value' => $this->configuration['feature_card_1_url'],
    ];

    $form['feature_cards']['feature_card_2_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Card 2 Title'),
      '#default_value' => $this->configuration['feature_card_2_title'],
    ];

    $form['feature_cards']['feature_card_2_subtitle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Card 2 Subtitle'),
      '#default_value' => $this->configuration['feature_card_2_subtitle'],
    ];

    $form['feature_cards']['feature_card_2_image'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Card 2 Image URL'),
      '#default_value' => $this->configuration['feature_card_2_image'],
    ];

    $form['feature_cards']['feature_card_2_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Card 2 URL'),
      '#default_value' => $this->configuration['feature_card_2_url'],
    ];

    $form['products'] = [
      '#type' => 'details',
      '#title' => $this->t('Products'),
      '#open' => TRUE,
    ];

    $form['products']['products_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Products Section Title'),
      '#default_value' => $this->configuration['products_title'],
    ];

    $form['products']['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of products'),
      '#default_value' => $this->configuration['limit'],
      '#min' => 4,
      '#max' => 24,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['hero_title'] = $form_state->getValue(['hero', 'hero_title']);
    $this->configuration['hero_subtitle'] = $form_state->getValue(['hero', 'hero_subtitle']);
    $this->configuration['hero_image'] = $form_state->getValue(['hero', 'hero_image']);
    $this->configuration['hero_cta_text'] = $form_state->getValue(['hero', 'hero_cta_text']);
    $this->configuration['hero_cta_url'] = $form_state->getValue(['hero', 'hero_cta_url']);
    $this->configuration['feature_card_1_title'] = $form_state->getValue(['feature_cards', 'feature_card_1_title']);
    $this->configuration['feature_card_1_subtitle'] = $form_state->getValue(['feature_cards', 'feature_card_1_subtitle']);
    $this->configuration['feature_card_1_image'] = $form_state->getValue(['feature_cards', 'feature_card_1_image']);
    $this->configuration['feature_card_1_url'] = $form_state->getValue(['feature_cards', 'feature_card_1_url']);
    $this->configuration['feature_card_2_title'] = $form_state->getValue(['feature_cards', 'feature_card_2_title']);
    $this->configuration['feature_card_2_subtitle'] = $form_state->getValue(['feature_cards', 'feature_card_2_subtitle']);
    $this->configuration['feature_card_2_image'] = $form_state->getValue(['feature_cards', 'feature_card_2_image']);
    $this->configuration['feature_card_2_url'] = $form_state->getValue(['feature_cards', 'feature_card_2_url']);
    $this->configuration['products_title'] = $form_state->getValue(['products', 'products_title']);
    $this->configuration['limit'] = $form_state->getValue(['products', 'limit']);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $current_langcode = $this->languageManager->getCurrentLanguage()->getId();
    $config = $this->configFactory->get('content_commerce.settings');
    $currency_symbol = $config->get('currency_symbol') ?: '$';

    // Get categories.
    $categories = $this->getCategories($current_langcode);

    // Load products.
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'product')
      ->condition('status', 1)
      ->sort('field_product_featured', 'DESC')
      ->sort('created', 'DESC')
      ->range(0, $this->configuration['limit'])
      ->accessCheck(TRUE);

    $nids = $query->execute();
    $products = $this->loadProducts($nids, $current_langcode, $currency_symbol);

    // Build feature cards.
    $feature_cards = [
      [
        'title' => $this->configuration['feature_card_1_title'],
        'subtitle' => $this->configuration['feature_card_1_subtitle'],
        'image' => $this->configuration['feature_card_1_image'] ?: 'https://images.unsplash.com/photo-1509631179647-0177331693ae?w=400&h=350&fit=crop&q=80',
        'url' => $this->configuration['feature_card_1_url'],
        'gradient' => 'from-rose-100 to-rose-200 dark:from-rose-900/30 dark:to-rose-800/30',
      ],
      [
        'title' => $this->configuration['feature_card_2_title'],
        'subtitle' => $this->configuration['feature_card_2_subtitle'],
        'image' => $this->configuration['feature_card_2_image'] ?: 'https://images.unsplash.com/photo-1485968579580-b6d095142e6e?w=400&h=350&fit=crop&q=80',
        'url' => $this->configuration['feature_card_2_url'],
        'gradient' => 'from-sky-100 to-sky-200 dark:from-sky-900/30 dark:to-sky-800/30',
      ],
    ];

    return [
      '#theme' => 'products_list_block',
      '#hero_title' => $this->configuration['hero_title'],
      '#hero_subtitle' => $this->configuration['hero_subtitle'],
      '#hero_image' => $this->configuration['hero_image'] ?: 'https://images.unsplash.com/photo-1469334031218-e382a71b716b?w=600&h=400&fit=crop&q=80',
      '#hero_cta_text' => $this->configuration['hero_cta_text'],
      '#hero_cta_url' => $this->configuration['hero_cta_url'],
      '#feature_cards' => $feature_cards,
      '#products_title' => $this->configuration['products_title'],
      '#categories' => $categories,
      '#products' => $products,
      '#currency_symbol' => $currency_symbol,
      '#attached' => [
        'library' => [
          'content_commerce/product',
        ],
      ],
      '#cache' => [
        'tags' => ['node_list:product', 'taxonomy_term_list:product_category'],
        'contexts' => ['languages:language_content'],
      ],
    ];
  }

  /**
   * Get all product categories.
   */
  protected function getCategories($langcode) {
    $categories = [];
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree('product_category');

    foreach ($terms as $term) {
      $term_entity = $this->entityTypeManager->getStorage('taxonomy_term')->load($term->tid);
      if ($term_entity && $term_entity->hasTranslation($langcode)) {
        $term_entity = $term_entity->getTranslation($langcode);
      }
      $categories[] = [
        'tid' => $term->tid,
        'name' => $term_entity ? $term_entity->getName() : $term->name,
        'slug' => strtolower(str_replace(' ', '-', $term->name)),
      ];
    }

    return $categories;
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
      $category_slug = 'all';
      $category_ref = $node->get('field_product_category')->referencedEntities();
      if (!empty($category_ref)) {
        $cat = reset($category_ref);
        $category_slug = strtolower(str_replace(' ', '-', $cat->getName()));
        if ($cat->hasTranslation($langcode)) {
          $cat = $cat->getTranslation($langcode);
        }
        $category = $cat->getName();
      }

      $price = $node->get('field_product_price')->value ?? 0;
      $sale_price = $node->get('field_product_sale_price')->value;

      $products[] = [
        'id' => $node->id(),
        'title' => $node->getTitle(),
        'url' => $node->toUrl()->toString(),
        'image_url' => $image_url,
        'price' => $price,
        'sale_price' => $sale_price,
        'formatted_price' => $currency_symbol . number_format((float) $price, 2),
        'formatted_sale_price' => $sale_price ? $currency_symbol . number_format((float) $sale_price, 2) : NULL,
        'category' => $category,
        'category_slug' => $category_slug,
        'in_stock' => (bool) ($node->get('field_product_in_stock')->value ?? TRUE),
      ];
    }

    return $products;
  }

}
