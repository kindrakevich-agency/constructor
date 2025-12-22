<?php

namespace Drupal\constructor_hero\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a What We Do Block.
 *
 * @Block(
 *   id = "what_we_do_block",
 *   admin_label = @Translation("What We Do Block"),
 *   category = @Translation("Constructor")
 * )
 */
class WhatWeDoBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'badge_text' => 'What We Do',
      'title' => 'Where people and culture meets',
      'title_highlight' => 'daily',
      'title_suffix' => 'operations',
      'description' => 'All of our features are based around being user-friendly, fast and effective. Clean and functional tech in the hands of everyone.',
      'show_primary_button' => TRUE,
      'primary_button_text' => 'Book a demo',
      'show_secondary_link' => TRUE,
      'secondary_link_text' => 'How others use it',
      'secondary_link_url' => '#',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // Content Section.
    $form['content_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Content'),
      '#open' => TRUE,
    ];

    $form['content_section']['badge_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Badge text'),
      '#default_value' => $this->configuration['badge_text'],
      '#description' => $this->t('Small badge above the title.'),
    ];

    $form['content_section']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title prefix'),
      '#default_value' => $this->configuration['title'],
      '#description' => $this->t('Text before the highlighted word.'),
    ];

    $form['content_section']['title_highlight'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Highlighted word'),
      '#default_value' => $this->configuration['title_highlight'],
      '#description' => $this->t('Word with underline decoration.'),
    ];

    $form['content_section']['title_suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title suffix'),
      '#default_value' => $this->configuration['title_suffix'],
      '#description' => $this->t('Text after the highlighted word.'),
    ];

    $form['content_section']['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $this->configuration['description'],
      '#rows' => 3,
    ];

    // Buttons Section.
    $form['buttons_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Buttons'),
      '#open' => TRUE,
    ];

    $form['buttons_section']['show_primary_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show primary button (Book a demo)'),
      '#default_value' => $this->configuration['show_primary_button'],
    ];

    $form['buttons_section']['primary_button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Primary button text'),
      '#default_value' => $this->configuration['primary_button_text'],
      '#states' => [
        'visible' => [
          ':input[name="settings[buttons_section][show_primary_button]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['buttons_section']['show_secondary_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show secondary link'),
      '#default_value' => $this->configuration['show_secondary_link'],
    ];

    $form['buttons_section']['secondary_link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secondary link text'),
      '#default_value' => $this->configuration['secondary_link_text'],
      '#states' => [
        'visible' => [
          ':input[name="settings[buttons_section][show_secondary_link]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['buttons_section']['secondary_link_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secondary link URL'),
      '#default_value' => $this->configuration['secondary_link_url'],
      '#states' => [
        'visible' => [
          ':input[name="settings[buttons_section][show_secondary_link]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Content Section.
    $content_section = $form_state->getValue('content_section');
    $this->configuration['badge_text'] = $content_section['badge_text'];
    $this->configuration['title'] = $content_section['title'];
    $this->configuration['title_highlight'] = $content_section['title_highlight'];
    $this->configuration['title_suffix'] = $content_section['title_suffix'];
    $this->configuration['description'] = $content_section['description'];

    // Buttons Section.
    $buttons_section = $form_state->getValue('buttons_section');
    $this->configuration['show_primary_button'] = $buttons_section['show_primary_button'];
    $this->configuration['primary_button_text'] = $buttons_section['primary_button_text'];
    $this->configuration['show_secondary_link'] = $buttons_section['show_secondary_link'];
    $this->configuration['secondary_link_text'] = $buttons_section['secondary_link_text'];
    $this->configuration['secondary_link_url'] = $buttons_section['secondary_link_url'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'what_we_do_block',
      '#badge_text' => $this->configuration['badge_text'],
      '#title' => $this->configuration['title'],
      '#title_highlight' => $this->configuration['title_highlight'],
      '#title_suffix' => $this->configuration['title_suffix'],
      '#description' => $this->configuration['description'],
      '#show_primary_button' => $this->configuration['show_primary_button'],
      '#primary_button_text' => $this->configuration['primary_button_text'],
      '#show_secondary_link' => $this->configuration['show_secondary_link'],
      '#secondary_link_text' => $this->configuration['secondary_link_text'],
      '#secondary_link_url' => $this->configuration['secondary_link_url'],
      '#attached' => [
        'library' => [
          'constructor_hero/hero-block',
        ],
      ],
    ];
  }

}
