<?php

namespace Drupal\content_team\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Team Block.
 *
 * @Block(
 *   id = "team_block",
 *   admin_label = @Translation("Team Block"),
 *   category = @Translation("Content")
 * )
 */
class TeamBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * Constructs a new TeamBlock instance.
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
      'title' => 'Your Career,<br>Your Connection',
      'subtitle' => 'Join us',
      'description' => "We're always looking for passionate individuals to join our team. Explore exciting opportunities and grow your career with us.",
      'button_text' => 'View Open Positions',
      'button_url' => '/careers',
      'limit' => 10,
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
      '#description' => $this->t('HTML is allowed (e.g., &lt;br&gt; for line breaks).'),
    ];

    $form['subtitle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subtitle'),
      '#default_value' => $this->configuration['subtitle'],
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $this->configuration['description'],
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
      '#title' => $this->t('Number of team members to display'),
      '#default_value' => $this->configuration['limit'],
      '#min' => 1,
      '#max' => 50,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['title'] = $form_state->getValue('title');
    $this->configuration['subtitle'] = $form_state->getValue('subtitle');
    $this->configuration['description'] = $form_state->getValue('description');
    $this->configuration['button_text'] = $form_state->getValue('button_text');
    $this->configuration['button_url'] = $form_state->getValue('button_url');
    $this->configuration['limit'] = $form_state->getValue('limit');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $team_members = [];
    $current_langcode = $this->languageManager->getCurrentLanguage()->getId();

    // Default Unsplash images for fallback.
    $default_images = [
      'https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=400&h=500&fit=crop&q=80',
      'https://images.unsplash.com/photo-1560250097-0b93528c311a?w=400&h=500&fit=crop&q=80',
      'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=400&h=500&fit=crop&q=80',
      'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=400&h=500&fit=crop&q=80',
      'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&h=500&fit=crop&q=80',
    ];

    // Default gradients.
    $default_gradients = [
      'linear-gradient(180deg, #fde4cf 0%, #ffcfd2 100%)',
      'linear-gradient(180deg, #fbc2eb 0%, #a6c1ee 100%)',
      'linear-gradient(180deg, #a1c4fd 0%, #c2e9fb 100%)',
      'linear-gradient(180deg, #d4fc79 0%, #96e6a1 100%)',
      'linear-gradient(180deg, #ffecd2 0%, #fcb69f 100%)',
    ];

    // Load team member nodes.
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'team_member')
      ->condition('status', 1)
      ->sort('created', 'ASC')
      ->range(0, $this->configuration['limit'])
      ->accessCheck(TRUE);

    $nids = $query->execute();

    if (!empty($nids)) {
      $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

      $index = 0;
      foreach ($nodes as $node) {
        // Get translated version if available.
        if ($node->hasTranslation($current_langcode)) {
          $node = $node->getTranslation($current_langcode);
        }

        $image_url = '';

        // Check for actual photo field first.
        if ($node->hasField('field_team_photo') && !$node->get('field_team_photo')->isEmpty()) {
          $file = $node->get('field_team_photo')->entity;
          if ($file instanceof File) {
            $image_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
          }
        }

        // Fall back to image URL field.
        if (empty($image_url) && $node->hasField('field_team_image_url')) {
          $image_url = $node->get('field_team_image_url')->value;
        }

        // Use default image if still not set.
        if (empty($image_url)) {
          $image_url = $default_images[$index % count($default_images)];
        }

        $gradient = '';
        if ($node->hasField('field_team_gradient')) {
          $gradient = $node->get('field_team_gradient')->value;
        }

        // Use default gradient if not set.
        if (empty($gradient)) {
          $gradient = $default_gradients[$index % count($default_gradients)];
        }

        $team_members[] = [
          'name' => $node->getTitle(),
          'position' => $node->get('field_team_position')->value,
          'image_url' => $image_url,
          'gradient' => $gradient,
        ];
        $index++;
      }
    }

    return [
      '#theme' => 'team_block_content',
      '#team_members' => $team_members,
      '#title' => $this->configuration['title'],
      '#subtitle' => $this->configuration['subtitle'],
      '#description' => $this->configuration['description'],
      '#button_text' => $this->configuration['button_text'],
      '#button_url' => $this->configuration['button_url'],
      '#attached' => [
        'library' => [
          'content_team/team-carousel',
        ],
      ],
      '#cache' => [
        'tags' => ['node_list:team_member'],
        'contexts' => ['languages:language_content'],
      ],
    ];
  }

}
