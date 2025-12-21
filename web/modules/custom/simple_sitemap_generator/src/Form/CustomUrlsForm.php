<?php

namespace Drupal\simple_sitemap_generator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays and manages custom URLs for sitemap.
 */
class CustomUrlsForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a CustomUrlsForm object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entity_type_manager) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_sitemap_generator_custom_urls';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['add_url'] = [
      '#type' => 'link',
      '#title' => $this->t('Add custom URL'),
      '#url' => Url::fromRoute('simple_sitemap_generator.add_custom_url'),
      '#attributes' => [
        'class' => ['button', 'button--primary'],
      ],
    ];

    // Load domains for mapping (only if domain module is installed).
    $domain_names = [];
    if (\Drupal::moduleHandler()->moduleExists('domain')) {
      $domain_storage = $this->entityTypeManager->getStorage('domain');
      $domains = $domain_storage->loadMultipleSorted();
      foreach ($domains as $domain) {
        $domain_names[$domain->id()] = $domain->label();
      }
    }
    else {
      $domain_names['default'] = $this->t('Default');
    }

    // Load custom URLs.
    $query = $this->database->select('simple_sitemap_custom_urls', 'u')
      ->fields('u')
      ->orderBy('domain_id')
      ->orderBy('url');
    $results = $query->execute()->fetchAll();

    $form['urls'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('URL'),
        $this->t('Domain'),
        $this->t('Priority'),
        $this->t('Change freq'),
        $this->t('Status'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('No custom URLs configured.'),
    ];

    foreach ($results as $row) {
      $form['urls'][$row->id] = [
        'url' => ['#markup' => $row->url],
        'domain' => ['#markup' => $domain_names[$row->domain_id] ?? $row->domain_id],
        'priority' => ['#markup' => $row->priority],
        'changefreq' => ['#markup' => $row->changefreq],
        'status' => ['#markup' => $row->status ? $this->t('Active') : $this->t('Disabled')],
        'operations' => [
          '#type' => 'operations',
          '#links' => [
            'edit' => [
              'title' => $this->t('Edit'),
              'url' => Url::fromRoute('simple_sitemap_generator.edit_custom_url', ['id' => $row->id]),
            ],
            'delete' => [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('simple_sitemap_generator.delete_custom_url', ['id' => $row->id]),
            ],
          ],
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This form doesn't have a submit action.
  }

}
