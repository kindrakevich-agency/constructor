<?php

namespace Drupal\content_services\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
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
      'image_fid' => NULL,
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

    $form['image_fid'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Image'),
      '#upload_location' => 'public://service-methods/',
      '#upload_validators' => [
        'FileExtension' => ['extensions' => 'png jpg jpeg gif webp'],
        'FileSizeLimit' => ['fileLimit' => 10 * 1024 * 1024],
      ],
      '#default_value' => $this->configuration['image_fid'] ? [$this->configuration['image_fid']] : [],
      '#description' => $this->t('Upload an image for the left side. Leave empty to show a placeholder.'),
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

    // Handle file upload.
    $image_fid = $form_state->getValue('image_fid');
    $fid = !empty($image_fid) ? reset($image_fid) : NULL;
    if ($fid) {
      $file = File::load($fid);
      if ($file) {
        $file->setPermanent();
        $file->save();
      }
    }
    $this->configuration['image_fid'] = $fid;
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

    // Get image URL and URI.
    $image_url = NULL;
    $image_uri = NULL;
    if (!empty($this->configuration['image_fid'])) {
      $file = File::load($this->configuration['image_fid']);
      if ($file) {
        $image_uri = $file->getFileUri();
        $image_url = \Drupal::service('file_url_generator')->generateAbsoluteString($image_uri);
      }
    }

    return [
      '#theme' => 'service_methods_block',
      '#title' => $this->configuration['title'],
      '#subtitle' => $this->configuration['subtitle'],
      '#image_url' => $image_url,
      '#image_uri' => $image_uri,
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
