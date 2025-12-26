<?php

namespace Drupal\constructor_hero\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

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
      'input_placeholder' => 'Enter your email',
      'input_fill_type' => 'email',
      'button_text' => 'Book a demo',
      'show_button_in_header' => FALSE,
      'show_stats' => TRUE,
      'stat1_value' => '75.2%',
      'stat1_label' => 'Average daily activity',
      'stat2_value' => '~20k',
      'stat2_label' => 'Average daily users',
      'show_rating' => TRUE,
      'rating_value' => '4.5',
      'rating_text' => 'Average user rating',
      'image_fid' => NULL,
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

    $form['email_section']['input_placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Input placeholder'),
      '#default_value' => $this->configuration['input_placeholder'],
      '#states' => [
        'visible' => [
          ':input[name="settings[email_section][show_email_form]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['email_section']['input_fill_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Fill field in popup'),
      '#options' => [
        'email' => $this->t('Fill email'),
        'phone' => $this->t('Fill phone'),
      ],
      '#default_value' => $this->configuration['input_fill_type'],
      '#description' => $this->t('When the user enters a value and clicks the button, this field will be pre-filled in the booking popup.'),
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

    $form['email_section']['show_button_in_header'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show button in header'),
      '#description' => $this->t('Display the button in the site header (desktop and mobile). The button will use the "Button text" value above.'),
      '#default_value' => $this->configuration['show_button_in_header'],
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

    $form['image_section']['image_fid'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Hero Image'),
      '#upload_location' => 'public://hero-images/',
      '#upload_validators' => [
        'FileExtension' => ['extensions' => 'png jpg jpeg gif webp'],
        'FileSizeLimit' => ['fileLimit' => 10 * 1024 * 1024],
      ],
      '#default_value' => $this->configuration['image_fid'] ? [$this->configuration['image_fid']] : [],
      '#description' => $this->t('Upload an image for the hero section. Leave empty to show a placeholder.'),
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
    $this->configuration['input_placeholder'] = $email_section['input_placeholder'];
    $this->configuration['input_fill_type'] = $email_section['input_fill_type'];
    $this->configuration['button_text'] = $email_section['button_text'];
    $this->configuration['show_button_in_header'] = $email_section['show_button_in_header'];

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
    $image_fid = !empty($image_section['image_fid']) ? reset($image_section['image_fid']) : NULL;
    if ($image_fid) {
      $file = File::load($image_fid);
      if ($file) {
        $file->setPermanent();
        $file->save();
      }
    }
    $this->configuration['image_fid'] = $image_fid;
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
      '#theme' => 'hero_block',
      '#title' => $this->configuration['title'],
      '#title_highlight' => $this->configuration['title_highlight'],
      '#title_suffix' => $this->configuration['title_suffix'],
      '#description' => $this->configuration['description'],
      '#show_email_form' => $this->configuration['show_email_form'],
      '#input_placeholder' => $this->configuration['input_placeholder'],
      '#input_fill_type' => $this->configuration['input_fill_type'],
      '#button_text' => $this->configuration['button_text'],
      '#show_stats' => $this->configuration['show_stats'],
      '#stats' => $stats,
      '#show_rating' => $this->configuration['show_rating'],
      '#rating_value' => $this->configuration['rating_value'],
      '#rating_text' => $this->configuration['rating_text'],
      '#image_url' => $image_url,
      '#image_uri' => $image_uri,
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
