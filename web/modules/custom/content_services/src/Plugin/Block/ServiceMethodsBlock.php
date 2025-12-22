<?php

namespace Drupal\content_services\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Service Methods Block with accordion.
 *
 * Displays service nodes in an accordion format.
 *
 * @Block(
 *   id = "service_methods_block",
 *   admin_label = @Translation("Service Methods Block"),
 *   category = @Translation("Content")
 * )
 */
class ServiceMethodsBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * Constructs a new ServiceMethodsBlock instance.
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
      'title' => 'Our Services',
      'subtitle' => 'We blend tradition with innovation to create sustainable practices that work for today\'s world',
      'image_url' => 'https://images.unsplash.com/photo-1605000797499-95a51c5269ae?w=600&h=500&fit=crop&q=80',
      'limit' => 6,
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
      '#type' => 'textarea',
      '#title' => $this->t('Subtitle'),
      '#default_value' => $this->configuration['subtitle'],
      '#rows' => 2,
    ];

    $form['image_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Image URL'),
      '#default_value' => $this->configuration['image_url'],
      '#description' => $this->t('URL for the left side image.'),
    ];

    $form['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of services to display'),
      '#default_value' => $this->configuration['limit'],
      '#min' => 1,
      '#max' => 12,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['title'] = $form_state->getValue('title');
    $this->configuration['subtitle'] = $form_state->getValue('subtitle');
    $this->configuration['image_url'] = $form_state->getValue('image_url');
    $this->configuration['limit'] = $form_state->getValue('limit');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $methods = [];
    $current_langcode = $this->languageManager->getCurrentLanguage()->getId();

    // Load Service nodes.
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'service')
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

        $methods[] = [
          'title' => $node->getTitle(),
          'description' => $node->get('field_service_description')->value ?: '',
          'url' => $node->toUrl()->toString(),
        ];
      }
    }

    return [
      '#theme' => 'service_methods_block',
      '#title' => $this->configuration['title'],
      '#subtitle' => $this->configuration['subtitle'],
      '#image_url' => $this->configuration['image_url'],
      '#methods' => $methods,
      '#attached' => [
        'library' => [
          'constructor_theme/accordion',
        ],
      ],
      '#cache' => [
        'tags' => ['node_list:service'],
        'contexts' => ['languages:language_content'],
      ],
    ];
  }

}
