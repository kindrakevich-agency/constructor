<?php

namespace Drupal\content_faq\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a FAQ Block.
 *
 * @Block(
 *   id = "faq_block",
 *   admin_label = @Translation("FAQ Block"),
 *   category = @Translation("Content")
 * )
 */
class FaqBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new FaqBlock instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'title' => 'Frequently Ask Questions',
      'subtitle' => 'FAQs',
      'button_text' => 'Contact Us',
      'button_url' => '/contact',
      'limit' => 5,
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
      '#title' => $this->t('Number of FAQs to display'),
      '#default_value' => $this->configuration['limit'],
      '#min' => 1,
      '#max' => 20,
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
    $faqs = [];

    // Load FAQ nodes.
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'faq')
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->range(0, $this->configuration['limit'])
      ->accessCheck(TRUE);

    $nids = $query->execute();

    if (!empty($nids)) {
      $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

      foreach ($nodes as $node) {
        $faqs[] = [
          'question' => $node->getTitle(),
          'answer' => $node->get('field_faq_answer')->value,
        ];
      }
    }

    return [
      '#theme' => 'faq_block_content',
      '#faqs' => $faqs,
      '#title' => $this->configuration['title'],
      '#subtitle' => $this->configuration['subtitle'],
      '#button_text' => $this->configuration['button_text'],
      '#button_url' => $this->configuration['button_url'],
      '#attached' => [
        'library' => [
          'content_faq/faq-accordion',
        ],
      ],
      '#cache' => [
        'tags' => ['node_list:faq'],
      ],
    ];
  }

}
