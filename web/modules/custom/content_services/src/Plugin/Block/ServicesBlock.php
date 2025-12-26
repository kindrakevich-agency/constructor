<?php

namespace Drupal\content_services\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Services Block.
 *
 * @Block(
 *   id = "services_block",
 *   admin_label = @Translation("Services Block"),
 *   category = @Translation("Content")
 * )
 */
class ServicesBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * Constructs a new ServicesBlock instance.
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
      'subtitle' => 'We bridge the gap between tradition and technology, helping you achieve your goals with ease',
      'button_text' => 'View All',
      'button_url' => '/services',
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

    $form['button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button Text'),
      '#default_value' => $this->configuration['button_text'],
    ];

    $form['button_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button URL'),
      '#default_value' => $this->configuration['button_url'],
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
    $this->configuration['button_text'] = $form_state->getValue('button_text');
    $this->configuration['button_url'] = $form_state->getValue('button_url');
    $this->configuration['limit'] = $form_state->getValue('limit');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $services = [];
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

        // Get image URL from field_service_image (image field) or fallback.
        $image_url = 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=500&h=400&fit=crop&q=80';
        if ($node->hasField('field_service_image') && !$node->get('field_service_image')->isEmpty()) {
          $file = $node->get('field_service_image')->entity;
          if ($file) {
            $image_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
          }
        }

        $services[] = [
          'name' => $node->getTitle(),
          'description' => $node->get('field_service_description')->value,
          'image_url' => $image_url,
          'url' => $node->toUrl()->toString(),
        ];
      }
    }

    return [
      '#theme' => 'services_block_content',
      '#services' => $services,
      '#title' => $this->configuration['title'],
      '#subtitle' => $this->configuration['subtitle'],
      '#button_text' => $this->configuration['button_text'],
      '#button_url' => $this->configuration['button_url'],
      '#cache' => [
        'tags' => ['node_list:service'],
        'contexts' => ['languages:language_content'],
      ],
    ];
  }

}
