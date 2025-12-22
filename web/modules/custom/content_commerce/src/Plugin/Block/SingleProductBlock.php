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
 * Provides a Single Product Block.
 *
 * @Block(
 *   id = "single_product_block",
 *   admin_label = @Translation("Single Product Block"),
 *   category = @Translation("Commerce")
 * )
 */
class SingleProductBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * Constructs a new SingleProductBlock instance.
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
      'product_id' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['product_id'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Product'),
      '#target_type' => 'node',
      '#selection_settings' => [
        'target_bundles' => ['product'],
      ],
      '#default_value' => $this->configuration['product_id'] ? $this->entityTypeManager->getStorage('node')->load($this->configuration['product_id']) : NULL,
      '#description' => $this->t('Select a product to display. Leave empty to show a random featured product.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['product_id'] = $form_state->getValue('product_id');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $current_langcode = $this->languageManager->getCurrentLanguage()->getId();
    $config = $this->configFactory->get('content_commerce.settings');
    $currency_symbol = $config->get('currency_symbol') ?: '$';
    $shipping_info = $config->get('shipping_info') ?: '';

    $node = NULL;

    // Load specific product or random featured product.
    if (!empty($this->configuration['product_id'])) {
      $node = $this->entityTypeManager->getStorage('node')->load($this->configuration['product_id']);
    }
    else {
      // Get random featured product.
      $query = $this->entityTypeManager->getStorage('node')->getQuery()
        ->condition('type', 'product')
        ->condition('status', 1)
        ->condition('field_product_featured', 1)
        ->accessCheck(TRUE);

      $nids = $query->execute();
      if (!empty($nids)) {
        $random_nid = array_rand(array_flip($nids));
        $node = $this->entityTypeManager->getStorage('node')->load($random_nid);
      }
      else {
        // Fallback to any product.
        $query = $this->entityTypeManager->getStorage('node')->getQuery()
          ->condition('type', 'product')
          ->condition('status', 1)
          ->range(0, 1)
          ->accessCheck(TRUE);
        $nids = $query->execute();
        if (!empty($nids)) {
          $node = $this->entityTypeManager->getStorage('node')->load(reset($nids));
        }
      }
    }

    if (!$node) {
      return [];
    }

    // Get translated version.
    if ($node->hasTranslation($current_langcode)) {
      $node = $node->getTranslation($current_langcode);
    }

    // Get all images.
    $images = [];
    $field_values = $node->get('field_product_images');
    foreach ($field_values as $delta => $item) {
      $file = $item->entity;
      if ($file) {
        $images[] = [
          'url' => \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri()),
          'alt' => $item->alt ?: $node->getTitle(),
        ];
      }
    }

    // Parse colors.
    $colors = [];
    $color_string = $node->get('field_product_colors')->value ?? '';
    if (!empty($color_string)) {
      $pairs = explode(',', $color_string);
      foreach ($pairs as $pair) {
        $parts = explode(':', trim($pair));
        if (count($parts) === 2) {
          $colors[] = [
            'label' => trim($parts[0]),
            'hex' => trim($parts[1]),
          ];
        }
      }
    }

    // Parse sizes.
    $sizes = [];
    $size_string = $node->get('field_product_sizes')->value ?? '';
    if (!empty($size_string)) {
      $sizes = array_map('trim', explode(',', $size_string));
    }

    $price = $node->get('field_product_price')->value ?? 0;
    $sale_price = $node->get('field_product_sale_price')->value;

    // Format price display.
    $price_display = $currency_symbol . number_format((float) $price, 2);
    if ($sale_price) {
      $price_display = $currency_symbol . number_format((float) $sale_price, 2) . ' - ' . $currency_symbol . number_format((float) $price, 2);
    }

    return [
      '#theme' => 'single_product_block',
      '#product_id' => $node->id(),
      '#product_title' => $node->getTitle(),
      '#product_price' => $price_display,
      '#product_sale_price' => $sale_price ? $currency_symbol . number_format((float) $sale_price, 2) : NULL,
      '#currency_symbol' => $currency_symbol,
      '#images' => $images,
      '#colors' => $colors,
      '#sizes' => $sizes,
      '#shipping_info' => $shipping_info,
      '#product_url' => $node->toUrl()->toString(),
      '#attached' => [
        'library' => [
          'content_commerce/product',
        ],
      ],
      '#cache' => [
        'tags' => $node->getCacheTags(),
        'contexts' => ['languages:language_content'],
        'max-age' => 0,
      ],
    ];
  }

}
