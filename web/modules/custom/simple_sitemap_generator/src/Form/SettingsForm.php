<?php

namespace Drupal\simple_sitemap_generator\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Simple Sitemap Generator settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a SettingsForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_sitemap_generator_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['simple_sitemap_generator.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('simple_sitemap_generator.settings');

    // Status section.
    $form['status'] = [
      '#type' => 'details',
      '#title' => $this->t('Sitemap Status'),
      '#open' => TRUE,
    ];

    // Get domains and show sitemap links.
    $domain_storage = $this->entityTypeManager->getStorage('domain');
    $domains = $domain_storage->loadMultipleSorted();

    if (!empty($domains)) {
      $form['status']['domain_sitemaps'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Domain'),
          $this->t('Sitemap URL'),
          $this->t('Status'),
        ],
        '#empty' => $this->t('No domains configured.'),
      ];

      foreach ($domains as $domain) {
        $sitemap_url = $domain->getScheme() . $domain->getHostname() . '/sitemap.xml';
        $form['status']['domain_sitemaps'][$domain->id()] = [
          'domain' => ['#markup' => $domain->label()],
          'url' => [
            '#type' => 'link',
            '#title' => $sitemap_url,
            '#url' => Url::fromUri($sitemap_url),
            '#attributes' => ['target' => '_blank'],
          ],
          'status' => ['#markup' => $this->t('Active')],
        ];
      }
    }

    $form['status']['regenerate'] = [
      '#type' => 'link',
      '#title' => $this->t('Regenerate all sitemaps'),
      '#url' => Url::fromRoute('simple_sitemap_generator.regenerate'),
      '#attributes' => [
        'class' => ['button', 'button--primary'],
      ],
    ];

    // Content types section.
    $form['content_types'] = [
      '#type' => 'details',
      '#title' => $this->t('Content Types'),
      '#open' => TRUE,
    ];

    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    $options = [];
    foreach ($node_types as $type) {
      $options[$type->id()] = $type->label();
    }

    $form['content_types']['enabled_content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Include content types'),
      '#options' => $options,
      '#default_value' => $config->get('enabled_content_types') ?: [],
      '#description' => $this->t('Select which content types should be included in the sitemap.'),
    ];

    // Settings section.
    $form['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Sitemap Settings'),
      '#open' => TRUE,
    ];

    $form['settings']['default_priority'] = [
      '#type' => 'select',
      '#title' => $this->t('Default priority'),
      '#options' => [
        '0.0' => '0.0',
        '0.1' => '0.1',
        '0.2' => '0.2',
        '0.3' => '0.3',
        '0.4' => '0.4',
        '0.5' => '0.5',
        '0.6' => '0.6',
        '0.7' => '0.7',
        '0.8' => '0.8',
        '0.9' => '0.9',
        '1.0' => '1.0',
      ],
      '#default_value' => $config->get('default_priority') ?: '0.5',
      '#description' => $this->t('Default priority for URLs in the sitemap.'),
    ];

    $form['settings']['default_changefreq'] = [
      '#type' => 'select',
      '#title' => $this->t('Default change frequency'),
      '#options' => [
        'always' => $this->t('Always'),
        'hourly' => $this->t('Hourly'),
        'daily' => $this->t('Daily'),
        'weekly' => $this->t('Weekly'),
        'monthly' => $this->t('Monthly'),
        'yearly' => $this->t('Yearly'),
        'never' => $this->t('Never'),
      ],
      '#default_value' => $config->get('default_changefreq') ?: 'weekly',
      '#description' => $this->t('How frequently the page is likely to change.'),
    ];

    $form['settings']['urls_per_sitemap'] = [
      '#type' => 'number',
      '#title' => $this->t('URLs per sitemap'),
      '#default_value' => $config->get('urls_per_sitemap') ?: 5000,
      '#min' => 100,
      '#max' => 50000,
      '#description' => $this->t('Maximum number of URLs per sitemap file. If exceeded, multiple sitemaps will be created.'),
    ];

    $form['settings']['cache_lifetime'] = [
      '#type' => 'select',
      '#title' => $this->t('Cache lifetime'),
      '#options' => [
        0 => $this->t('No cache'),
        3600 => $this->t('1 hour'),
        21600 => $this->t('6 hours'),
        43200 => $this->t('12 hours'),
        86400 => $this->t('1 day'),
        604800 => $this->t('1 week'),
      ],
      '#default_value' => $config->get('cache_lifetime') ?: 86400,
      '#description' => $this->t('How long to cache generated sitemaps.'),
    ];

    $form['settings']['include_images'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include images'),
      '#default_value' => $config->get('include_images') ?? TRUE,
      '#description' => $this->t('Include image information in sitemap entries.'),
    ];

    $form['settings']['include_lastmod'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include last modification date'),
      '#default_value' => $config->get('include_lastmod') ?? TRUE,
      '#description' => $this->t('Include lastmod tag for each URL.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $enabled_types = array_filter($form_state->getValue('enabled_content_types'));

    $this->config('simple_sitemap_generator.settings')
      ->set('enabled_content_types', array_values($enabled_types))
      ->set('default_priority', $form_state->getValue('default_priority'))
      ->set('default_changefreq', $form_state->getValue('default_changefreq'))
      ->set('urls_per_sitemap', $form_state->getValue('urls_per_sitemap'))
      ->set('cache_lifetime', $form_state->getValue('cache_lifetime'))
      ->set('include_images', $form_state->getValue('include_images'))
      ->set('include_lastmod', $form_state->getValue('include_lastmod'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
