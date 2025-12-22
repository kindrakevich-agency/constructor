<?php

namespace Drupal\constructor_hero\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Hero Block.
 *
 * @Block(
 *   id = "hero_block",
 *   admin_label = @Translation("Hero Block"),
 *   category = @Translation("Constructor")
 * )
 */
class HeroBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'title' => 'Put',
      'title_highlight' => 'people',
      'title_suffix' => 'first',
      'description' => 'Fast, user-friendly and engaging - turn HR into people and culture and streamline your daily operations with your own branded app.',
      'show_email_form' => TRUE,
      'email_placeholder' => 'Enter work email',
      'button_text' => 'Book a demo',
      'show_stats' => TRUE,
      'stat1_value' => '75.2%',
      'stat1_label' => 'Average daily activity',
      'stat2_value' => '~20k',
      'stat2_label' => 'Average daily users',
      'show_rating' => TRUE,
      'rating_value' => '4.5',
      'rating_text' => 'Average user rating',
      'image_url' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=800&h=600&fit=crop&q=80',
      'image_alt' => 'Team collaboration',
      'show_floating_card' => TRUE,
      'floating_card_value' => '2,500+',
      'floating_card_text' => 'Active users',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // Title Section.
    $form['title_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Title'),
      '#open' => TRUE,
    ];

    $form['title_section']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title prefix'),
      '#default_value' => $this->configuration['title'],
      '#description' => $this->t('Text before the highlighted word.'),
    ];

    $form['title_section']['title_highlight'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Highlighted word'),
      '#default_value' => $this->configuration['title_highlight'],
      '#description' => $this->t('Word with underline decoration.'),
    ];

    $form['title_section']['title_suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title suffix'),
      '#default_value' => $this->configuration['title_suffix'],
      '#description' => $this->t('Text after the highlighted word.'),
    ];

    $form['title_section']['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $this->configuration['description'],
      '#rows' => 3,
    ];

    // Email Form Section.
    $form['email_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Email Form'),
      '#open' => TRUE,
    ];

    $form['email_section']['show_email_form'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show email form'),
      '#default_value' => $this->configuration['show_email_form'],
    ];

    $form['email_section']['email_placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email placeholder'),
      '#default_value' => $this->configuration['email_placeholder'],
      '#states' => [
        'visible' => [
          ':input[name="settings[email_section][show_email_form]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['email_section']['button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button text'),
      '#default_value' => $this->configuration['button_text'],
      '#states' => [
        'visible' => [
          ':input[name="settings[email_section][show_email_form]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Stats Section.
    $form['stats_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Statistics'),
      '#open' => FALSE,
    ];

    $form['stats_section']['show_stats'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show statistics'),
      '#default_value' => $this->configuration['show_stats'],
    ];

    $form['stats_section']['stat1_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Stat 1 value'),
      '#default_value' => $this->configuration['stat1_value'],
      '#states' => [
        'visible' => [
          ':input[name="settings[stats_section][show_stats]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['stats_section']['stat1_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Stat 1 label'),
      '#default_value' => $this->configuration['stat1_label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[stats_section][show_stats]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['stats_section']['stat2_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Stat 2 value'),
      '#default_value' => $this->configuration['stat2_value'],
      '#states' => [
        'visible' => [
          ':input[name="settings[stats_section][show_stats]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['stats_section']['stat2_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Stat 2 label'),
      '#default_value' => $this->configuration['stat2_label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[stats_section][show_stats]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Rating Section.
    $form['rating_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Rating'),
      '#open' => FALSE,
    ];

    $form['rating_section']['show_rating'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show rating'),
      '#default_value' => $this->configuration['show_rating'],
    ];

    $form['rating_section']['rating_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Rating value'),
      '#default_value' => $this->configuration['rating_value'],
      '#states' => [
        'visible' => [
          ':input[name="settings[rating_section][show_rating]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['rating_section']['rating_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Rating text'),
      '#default_value' => $this->configuration['rating_text'],
      '#states' => [
        'visible' => [
          ':input[name="settings[rating_section][show_rating]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Image Section.
    $form['image_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Image'),
      '#open' => FALSE,
    ];

    $form['image_section']['image_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Image URL'),
      '#default_value' => $this->configuration['image_url'],
      '#description' => $this->t('URL of the hero image.'),
    ];

    $form['image_section']['image_alt'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Image alt text'),
      '#default_value' => $this->configuration['image_alt'],
    ];

    // Floating Card Section.
    $form['floating_card_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Floating Card'),
      '#open' => FALSE,
    ];

    $form['floating_card_section']['show_floating_card'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show floating card'),
      '#default_value' => $this->configuration['show_floating_card'],
    ];

    $form['floating_card_section']['floating_card_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Floating card value'),
      '#default_value' => $this->configuration['floating_card_value'],
      '#states' => [
        'visible' => [
          ':input[name="settings[floating_card_section][show_floating_card]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['floating_card_section']['floating_card_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Floating card text'),
      '#default_value' => $this->configuration['floating_card_text'],
      '#states' => [
        'visible' => [
          ':input[name="settings[floating_card_section][show_floating_card]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Title Section.
    $title_section = $form_state->getValue('title_section');
    $this->configuration['title'] = $title_section['title'];
    $this->configuration['title_highlight'] = $title_section['title_highlight'];
    $this->configuration['title_suffix'] = $title_section['title_suffix'];
    $this->configuration['description'] = $title_section['description'];

    // Email Section.
    $email_section = $form_state->getValue('email_section');
    $this->configuration['show_email_form'] = $email_section['show_email_form'];
    $this->configuration['email_placeholder'] = $email_section['email_placeholder'];
    $this->configuration['button_text'] = $email_section['button_text'];

    // Stats Section.
    $stats_section = $form_state->getValue('stats_section');
    $this->configuration['show_stats'] = $stats_section['show_stats'];
    $this->configuration['stat1_value'] = $stats_section['stat1_value'];
    $this->configuration['stat1_label'] = $stats_section['stat1_label'];
    $this->configuration['stat2_value'] = $stats_section['stat2_value'];
    $this->configuration['stat2_label'] = $stats_section['stat2_label'];

    // Rating Section.
    $rating_section = $form_state->getValue('rating_section');
    $this->configuration['show_rating'] = $rating_section['show_rating'];
    $this->configuration['rating_value'] = $rating_section['rating_value'];
    $this->configuration['rating_text'] = $rating_section['rating_text'];

    // Image Section.
    $image_section = $form_state->getValue('image_section');
    $this->configuration['image_url'] = $image_section['image_url'];
    $this->configuration['image_alt'] = $image_section['image_alt'];

    // Floating Card Section.
    $floating_card_section = $form_state->getValue('floating_card_section');
    $this->configuration['show_floating_card'] = $floating_card_section['show_floating_card'];
    $this->configuration['floating_card_value'] = $floating_card_section['floating_card_value'];
    $this->configuration['floating_card_text'] = $floating_card_section['floating_card_text'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $stats = [];
    if ($this->configuration['show_stats']) {
      if (!empty($this->configuration['stat1_value'])) {
        $stats[] = [
          'value' => $this->configuration['stat1_value'],
          'label' => $this->configuration['stat1_label'],
        ];
      }
      if (!empty($this->configuration['stat2_value'])) {
        $stats[] = [
          'value' => $this->configuration['stat2_value'],
          'label' => $this->configuration['stat2_label'],
        ];
      }
    }

    return [
      '#theme' => 'hero_block',
      '#title' => $this->configuration['title'],
      '#title_highlight' => $this->configuration['title_highlight'],
      '#title_suffix' => $this->configuration['title_suffix'],
      '#description' => $this->configuration['description'],
      '#show_email_form' => $this->configuration['show_email_form'],
      '#email_placeholder' => $this->configuration['email_placeholder'],
      '#button_text' => $this->configuration['button_text'],
      '#show_stats' => $this->configuration['show_stats'],
      '#stats' => $stats,
      '#show_rating' => $this->configuration['show_rating'],
      '#rating_value' => $this->configuration['rating_value'],
      '#rating_text' => $this->configuration['rating_text'],
      '#image_url' => $this->configuration['image_url'],
      '#image_alt' => $this->configuration['image_alt'],
      '#show_floating_card' => $this->configuration['show_floating_card'],
      '#floating_card_value' => $this->configuration['floating_card_value'],
      '#floating_card_text' => $this->configuration['floating_card_text'],
      '#attached' => [
        'library' => [
          'constructor_hero/hero-block',
        ],
      ],
    ];
  }

}
