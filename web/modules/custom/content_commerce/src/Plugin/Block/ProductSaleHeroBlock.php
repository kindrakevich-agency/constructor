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
 * Provides a Product Sale Hero Block.
 *
 * @Block(
 *   id = "product_sale_hero_block",
 *   admin_label = @Translation("Product Sale Hero Block"),
 *   category = @Translation("Commerce")
 * )
 */
class ProductSaleHeroBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * Constructs a new ProductSaleHeroBlock instance.
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
      'badge_text' => 'Hottest Sale',
      'cta_text' => 'Buy',
      'product_id' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['badge_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Badge Text'),
      '#default_value' => $this->configuration['badge_text'],
    ];

    $form['cta_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CTA Button Text'),
      '#default_value' => $this->configuration['cta_text'],
    ];

    $form['product_id'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Product'),
      '#target_type' => 'node',
      '#selection_settings' => [
        'target_bundles' => ['product'],
      ],
      '#default_value' => $this->configuration['product_id'] ? $this->entityTypeManager->getStorage('node')->load($this->configuration['product_id']) : NULL,
      '#description' => $this->t('Select a specific product or leave empty to show a random sale product.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['badge_text'] = $form_state->getValue('badge_text');
    $this->configuration['cta_text'] = $form_state->getValue('cta_text');
    $this->configuration['product_id'] = $form_state->getValue('product_id');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $current_langcode = $this->languageManager->getCurrentLanguage()->getId();
    $config = $this->configFactory->get('content_commerce.settings');
    $currency_symbol = $config->get('currency_symbol') ?: '$';

    $node = NULL;

    // Load specific product or random sale product.
    if (!empty($this->configuration['product_id'])) {
      $node = $this->entityTypeManager->getStorage('node')->load($this->configuration['product_id']);
    }
    else {
      // Get random product with sale price.
      $query = $this->entityTypeManager->getStorage('node')->getQuery()
        ->condition('type', 'product')
        ->condition('status', 1)
        ->condition('field_product_sale_price', '', '<>')
        ->accessCheck(TRUE);

      $nids = $query->execute();
      if (!empty($nids)) {
        $random_nid = array_rand(array_flip($nids));
        $node = $this->entityTypeManager->getStorage('node')->load($random_nid);
      }
    }

    if (!$node) {
      return [];
    }

    // Get translated version.
    if ($node->hasTranslation($current_langcode)) {
      $node = $node->getTranslation($current_langcode);
    }

    // Get first image.
    $image_url = NULL;
    $images = $node->get('field_product_images')->referencedEntities();
    if (!empty($images)) {
      $image_url = \Drupal::service('file_url_generator')->generateAbsoluteString($images[0]->getFileUri());
    }

    $price = $node->get('field_product_price')->value ?? 0;
    $sale_price = $node->get('field_product_sale_price')->value;
    $description = $node->get('field_product_body')->value ?? '';

    return [
      '#theme' => 'product_sale_hero_block',
      '#badge_text' => $this->configuration['badge_text'],
      '#product_title' => $node->getTitle(),
      '#product_description' => strip_tags($description),
      '#original_price' => $currency_symbol . number_format((float) $price, 2),
      '#sale_price' => $sale_price ? $currency_symbol . number_format((float) $sale_price, 2) : NULL,
      '#currency_symbol' => $currency_symbol,
      '#image_url' => $image_url,
      '#product_url' => $node->toUrl()->toString(),
      '#cta_text' => $this->configuration['cta_text'],
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
