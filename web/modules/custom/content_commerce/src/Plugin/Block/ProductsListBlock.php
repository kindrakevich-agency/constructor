<?php

namespace Drupal\content_commerce\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
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
   * The file URL generator.
   *
   * @var \Drupal\file\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Constructs a new ProductsListBlock instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, ConfigFactoryInterface $config_factory, FileUrlGeneratorInterface $file_url_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->configFactory = $config_factory;
    $this->fileUrlGenerator = $file_url_generator;
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
      $container->get('config.factory'),
      $container->get('file_url_generator')
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
      'hero_products' => [],
      'feature_card_1_title' => 'Laid-Back Luxe',
      'feature_card_1_subtitle' => 'Comfort meets effortless style',
      'feature_card_1_image' => '',
      'feature_card_1_url' => '/products',
      'feature_card_1_products' => [],
      'feature_card_2_title' => 'Everyday Cool',
      'feature_card_2_subtitle' => 'Relaxed fits for work, play, and everything in between.',
      'feature_card_2_image' => '',
      'feature_card_2_url' => '/products',
      'feature_card_2_products' => [],
      'products_title' => 'Trending Now – Step Into Style',
      'limit' => 8,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // Hero Banner Section.
    $form['hero'] = [
      '#type' => 'details',
      '#title' => $this->t('Hero Banner - "Find your Color"'),
      '#open' => TRUE,
    ];

    $form['hero']['hero_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hero Title'),
      '#default_value' => $this->configuration['hero_title'],
      '#description' => $this->t('Use line breaks for multi-line titles.'),
    ];

    $form['hero']['hero_subtitle'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Hero Subtitle'),
      '#default_value' => $this->configuration['hero_subtitle'],
      '#rows' => 2,
    ];

    $form['hero']['hero_products'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Featured Products'),
      '#target_type' => 'node',
      '#selection_settings' => [
        'target_bundles' => ['product'],
      ],
      '#tags' => TRUE,
      '#default_value' => $this->getProductEntities($this->configuration['hero_products']),
      '#description' => $this->t('Select products to feature in the hero banner. First product image will be used as hero image if no custom image is set.'),
    ];

    $form['hero']['hero_image'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hero Image URL (override)'),
      '#default_value' => $this->configuration['hero_image'],
      '#description' => $this->t('External image URL. Leave empty to use first selected product image.'),
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

    // Feature Card 1 Section.
    $form['feature_card_1'] = [
      '#type' => 'details',
      '#title' => $this->t('Feature Card 1 - "Laid-Back Luxe"'),
      '#open' => FALSE,
    ];

    $form['feature_card_1']['feature_card_1_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Card Title'),
      '#default_value' => $this->configuration['feature_card_1_title'],
    ];

    $form['feature_card_1']['feature_card_1_subtitle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Card Subtitle'),
      '#default_value' => $this->configuration['feature_card_1_subtitle'],
    ];

    $form['feature_card_1']['feature_card_1_products'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Featured Products'),
      '#target_type' => 'node',
      '#selection_settings' => [
        'target_bundles' => ['product'],
      ],
      '#tags' => TRUE,
      '#default_value' => $this->getProductEntities($this->configuration['feature_card_1_products']),
      '#description' => $this->t('Select products to feature. First product image will be used as card image if no custom image is set.'),
    ];

    $form['feature_card_1']['feature_card_1_image'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Card Image URL (override)'),
      '#default_value' => $this->configuration['feature_card_1_image'],
      '#description' => $this->t('Leave empty to use first selected product image.'),
    ];

    $form['feature_card_1']['feature_card_1_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Card URL'),
      '#default_value' => $this->configuration['feature_card_1_url'],
    ];

    // Feature Card 2 Section.
    $form['feature_card_2'] = [
      '#type' => 'details',
      '#title' => $this->t('Feature Card 2 - "Everyday Cool"'),
      '#open' => FALSE,
    ];

    $form['feature_card_2']['feature_card_2_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Card Title'),
      '#default_value' => $this->configuration['feature_card_2_title'],
    ];

    $form['feature_card_2']['feature_card_2_subtitle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Card Subtitle'),
      '#default_value' => $this->configuration['feature_card_2_subtitle'],
    ];

    $form['feature_card_2']['feature_card_2_products'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Featured Products'),
      '#target_type' => 'node',
      '#selection_settings' => [
        'target_bundles' => ['product'],
      ],
      '#tags' => TRUE,
      '#default_value' => $this->getProductEntities($this->configuration['feature_card_2_products']),
      '#description' => $this->t('Select products to feature. First product image will be used as card image if no custom image is set.'),
    ];

    $form['feature_card_2']['feature_card_2_image'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Card Image URL (override)'),
      '#default_value' => $this->configuration['feature_card_2_image'],
      '#description' => $this->t('Leave empty to use first selected product image.'),
    ];

    $form['feature_card_2']['feature_card_2_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Card URL'),
      '#default_value' => $this->configuration['feature_card_2_url'],
    ];

    // Products Grid Section.
    $form['products'] = [
      '#type' => 'details',
      '#title' => $this->t('Products Grid'),
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
   * Get product entities from stored IDs.
   */
  protected function getProductEntities(array $product_ids) {
    if (empty($product_ids)) {
      return NULL;
    }
    return $this->entityTypeManager->getStorage('node')->loadMultiple($product_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Hero Banner.
    $this->configuration['hero_title'] = $form_state->getValue(['hero', 'hero_title']);
    $this->configuration['hero_subtitle'] = $form_state->getValue(['hero', 'hero_subtitle']);
    $this->configuration['hero_image'] = $form_state->getValue(['hero', 'hero_image']);
    $this->configuration['hero_cta_text'] = $form_state->getValue(['hero', 'hero_cta_text']);
    $this->configuration['hero_cta_url'] = $form_state->getValue(['hero', 'hero_cta_url']);
    $this->configuration['hero_products'] = $this->extractProductIds($form_state->getValue(['hero', 'hero_products']));

    // Feature Card 1.
    $this->configuration['feature_card_1_title'] = $form_state->getValue(['feature_card_1', 'feature_card_1_title']);
    $this->configuration['feature_card_1_subtitle'] = $form_state->getValue(['feature_card_1', 'feature_card_1_subtitle']);
    $this->configuration['feature_card_1_image'] = $form_state->getValue(['feature_card_1', 'feature_card_1_image']);
    $this->configuration['feature_card_1_url'] = $form_state->getValue(['feature_card_1', 'feature_card_1_url']);
    $this->configuration['feature_card_1_products'] = $this->extractProductIds($form_state->getValue(['feature_card_1', 'feature_card_1_products']));

    // Feature Card 2.
    $this->configuration['feature_card_2_title'] = $form_state->getValue(['feature_card_2', 'feature_card_2_title']);
    $this->configuration['feature_card_2_subtitle'] = $form_state->getValue(['feature_card_2', 'feature_card_2_subtitle']);
    $this->configuration['feature_card_2_image'] = $form_state->getValue(['feature_card_2', 'feature_card_2_image']);
    $this->configuration['feature_card_2_url'] = $form_state->getValue(['feature_card_2', 'feature_card_2_url']);
    $this->configuration['feature_card_2_products'] = $this->extractProductIds($form_state->getValue(['feature_card_2', 'feature_card_2_products']));

    // Products Grid.
    $this->configuration['products_title'] = $form_state->getValue(['products', 'products_title']);
    $this->configuration['limit'] = $form_state->getValue(['products', 'limit']);
  }

  /**
   * Extract product IDs from entity autocomplete value.
   */
  protected function extractProductIds($value) {
    if (empty($value)) {
      return [];
    }
    if (is_array($value)) {
      $ids = [];
      foreach ($value as $item) {
        if (is_array($item) && isset($item['target_id'])) {
          $ids[] = $item['target_id'];
        }
        elseif (is_object($item)) {
          $ids[] = $item->id();
        }
        elseif (is_numeric($item)) {
          $ids[] = (int) $item;
        }
      }
      return $ids;
    }
    return [];
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

    // Get hero image from products or fallback.
    $hero_image = $this->configuration['hero_image'];
    $hero_products = [];
    if (!empty($this->configuration['hero_products'])) {
      $hero_products = $this->loadBannerProducts($this->configuration['hero_products'], $current_langcode, $currency_symbol);
      if (empty($hero_image) && !empty($hero_products)) {
        $hero_image = $hero_products[0]['image_url'] ?? NULL;
      }
    }
    if (empty($hero_image)) {
      $hero_image = 'https://images.unsplash.com/photo-1469334031218-e382a71b716b?w=600&h=400&fit=crop&q=80';
    }

    // Get feature card 1 image from products or fallback.
    $feature_card_1_image = $this->configuration['feature_card_1_image'];
    $feature_card_1_products = [];
    if (!empty($this->configuration['feature_card_1_products'])) {
      $feature_card_1_products = $this->loadBannerProducts($this->configuration['feature_card_1_products'], $current_langcode, $currency_symbol);
      if (empty($feature_card_1_image) && !empty($feature_card_1_products)) {
        $feature_card_1_image = $feature_card_1_products[0]['image_url'] ?? NULL;
      }
    }
    if (empty($feature_card_1_image)) {
      $feature_card_1_image = 'https://images.unsplash.com/photo-1509631179647-0177331693ae?w=400&h=350&fit=crop&q=80';
    }

    // Get feature card 2 image from products or fallback.
    $feature_card_2_image = $this->configuration['feature_card_2_image'];
    $feature_card_2_products = [];
    if (!empty($this->configuration['feature_card_2_products'])) {
      $feature_card_2_products = $this->loadBannerProducts($this->configuration['feature_card_2_products'], $current_langcode, $currency_symbol);
      if (empty($feature_card_2_image) && !empty($feature_card_2_products)) {
        $feature_card_2_image = $feature_card_2_products[0]['image_url'] ?? NULL;
      }
    }
    if (empty($feature_card_2_image)) {
      $feature_card_2_image = 'https://images.unsplash.com/photo-1485968579580-b6d095142e6e?w=400&h=350&fit=crop&q=80';
    }

    // Build feature cards.
    $feature_cards = [
      [
        'title' => $this->configuration['feature_card_1_title'],
        'subtitle' => $this->configuration['feature_card_1_subtitle'],
        'image' => $feature_card_1_image,
        'url' => $this->configuration['feature_card_1_url'],
        'gradient' => 'from-rose-100 to-rose-200 dark:from-rose-900/30 dark:to-rose-800/30',
        'products' => $feature_card_1_products,
      ],
      [
        'title' => $this->configuration['feature_card_2_title'],
        'subtitle' => $this->configuration['feature_card_2_subtitle'],
        'image' => $feature_card_2_image,
        'url' => $this->configuration['feature_card_2_url'],
        'gradient' => 'from-sky-100 to-sky-200 dark:from-sky-900/30 dark:to-sky-800/30',
        'products' => $feature_card_2_products,
      ],
    ];

    return [
      '#theme' => 'products_list_block',
      '#hero_title' => $this->configuration['hero_title'],
      '#hero_subtitle' => $this->configuration['hero_subtitle'],
      '#hero_image' => $hero_image,
      '#hero_cta_text' => $this->configuration['hero_cta_text'],
      '#hero_cta_url' => $this->configuration['hero_cta_url'],
      '#hero_products' => $hero_products,
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
   * Load banner products with their data.
   */
  protected function loadBannerProducts(array $product_ids, $langcode, $currency_symbol) {
    $products = [];
    if (empty($product_ids)) {
      return $products;
    }

    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($product_ids);
    foreach ($nodes as $node) {
      if ($node->bundle() !== 'product' || !$node->isPublished()) {
        continue;
      }

      if ($node->hasTranslation($langcode)) {
        $node = $node->getTranslation($langcode);
      }

      // Get first image.
      $image_url = NULL;
      $image_uri = NULL;
      if ($node->hasField('field_product_images')) {
        $images = $node->get('field_product_images')->referencedEntities();
        if (!empty($images)) {
          $image_uri = $images[0]->getFileUri();
          $image_url = $this->fileUrlGenerator->generateAbsoluteString($image_uri);
        }
      }

      $price = $node->get('field_product_price')->value ?? 0;
      $sale_price = $node->get('field_product_sale_price')->value;

      $products[] = [
        'id' => $node->id(),
        'title' => $node->getTitle(),
        'url' => $node->toUrl()->toString(),
        'image_url' => $image_url,
        'image_uri' => $image_uri,
        'price' => $price,
        'sale_price' => $sale_price,
        'formatted_price' => $currency_symbol . number_format((float) $price, 2),
        'formatted_sale_price' => $sale_price ? $currency_symbol . number_format((float) $sale_price, 2) : NULL,
      ];
    }

    return $products;
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
      $image_uri = NULL;
      $images = $node->get('field_product_images')->referencedEntities();
      if (!empty($images)) {
        $image_uri = $images[0]->getFileUri();
        $image_url = $this->fileUrlGenerator->generateAbsoluteString($image_uri);
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
        'image_uri' => $image_uri,
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
