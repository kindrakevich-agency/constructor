<?php

namespace Drupal\gallery\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Gallery Block.
 *
 * @Block(
 *   id = "gallery_block",
 *   admin_label = @Translation("Gallery Block"),
 *   category = @Translation("Gallery")
 * )
 */
class GalleryBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_text' => 'Our Gallery',
      'title' => "Explore the Gallery and\nSee the Future Unfold",
      'description' => 'From sleek details to real-life moments of interaction, this gallery captures the essence of what makes the future feel real today.',
      'button_text' => 'Explore the Gallery',
      'button_url' => '/gallery',
      'limit' => 8,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['content'] = [
      '#type' => 'details',
      '#title' => $this->t('Content'),
      '#open' => TRUE,
    ];

    $form['content']['label_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label Text'),
      '#default_value' => $this->configuration['label_text'],
      '#description' => $this->t('Small label above the title (e.g., "Our Gallery").'),
    ];

    $form['content']['title'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Title'),
      '#default_value' => $this->configuration['title'],
      '#rows' => 2,
    ];

    $form['content']['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $this->configuration['description'],
      '#rows' => 3,
    ];

    $form['content']['button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button Text'),
      '#default_value' => $this->configuration['button_text'],
    ];

    $form['content']['button_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button URL'),
      '#default_value' => $this->configuration['button_url'],
    ];

    $form['content']['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of images'),
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
    $this->configuration['label_text'] = $form_state->getValue(['content', 'label_text']);
    $this->configuration['title'] = $form_state->getValue(['content', 'title']);
    $this->configuration['description'] = $form_state->getValue(['content', 'description']);
    $this->configuration['button_text'] = $form_state->getValue(['content', 'button_text']);
    $this->configuration['button_url'] = $form_state->getValue(['content', 'button_url']);
    $this->configuration['limit'] = $form_state->getValue(['content', 'limit']);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $images = gallery_get_images();

    // Limit images.
    $limit = $this->configuration['limit'] ?? 8;
    $images = array_slice($images, 0, $limit);

    return [
      '#theme' => 'gallery_block',
      '#label_text' => $this->configuration['label_text'],
      '#title' => $this->configuration['title'],
      '#description' => $this->configuration['description'],
      '#button_text' => $this->configuration['button_text'],
      '#button_url' => $this->configuration['button_url'],
      '#images' => $images,
      '#attached' => [
        'library' => [
          'gallery/gallery',
        ],
      ],
      '#cache' => [
        'tags' => ['gallery_images'],
      ],
    ];
  }

}
