<?php

namespace Drupal\content_commerce\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for products listing page.
 */
class ProductsPageController extends ControllerBase {

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
   * Constructs a ProductsPageController object.
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
   * Products listing page.
   */
  public function page() {
    $current_langcode = $this->languageManager->getCurrentLanguage()->getId();

    // Get all categories.
    $categories = $this->getCategories($current_langcode);

    // Build product query - show all products on main /products page.
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'product')
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->accessCheck(TRUE);

    $nids = $query->execute();
    $products = $this->loadProducts($nids, $current_langcode);

    // Get commerce settings.
    $config = $this->config('content_commerce.settings');
    $currency_symbol = $config->get('currency_symbol') ?: '$';

    return [
      '#theme' => 'products_page',
      '#products' => $products,
      '#categories' => $categories,
      '#current_category' => 'all',
      '#currency_symbol' => $currency_symbol,
      '#attached' => [
        'library' => [
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
        'slug' => $term->name,
        'url' => $term_entity ? $term_entity->toUrl()->toString() : '/products',
      ];
    }

    return $categories;
  }

  /**
   * Load products with translations.
   */
  protected function loadProducts(array $nids, $langcode) {
    $products = [];
    $config = $this->config('content_commerce.settings');
    $currency_symbol = $config->get('currency_symbol') ?: '$';

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
        $image_url = \Drupal::service('file_url_generator')->generateAbsoluteString($image_uri);
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
        'in_stock' => (bool) ($node->get('field_product_in_stock')->value ?? TRUE),
        'featured' => (bool) ($node->get('field_product_featured')->value ?? FALSE),
      ];
    }

    return $products;
  }

}
