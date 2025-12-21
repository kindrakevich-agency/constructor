<?php

namespace Drupal\simple_sitemap_generator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\Datetime\TimeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for adding/editing custom URLs.
 */
class CustomUrlForm extends FormBase {

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
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The URL record being edited.
   *
   * @var object|null
   */
  protected $record;

  /**
   * Constructs a CustomUrlForm object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entity_type_manager, TimeInterface $time) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_sitemap_generator_custom_url';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    // Load existing record if editing.
    if ($id) {
      $this->record = $this->database->select('simple_sitemap_custom_urls', 'u')
        ->fields('u')
        ->condition('id', $id)
        ->execute()
        ->fetchObject();
    }

    // Load domains (only if domain module is installed).
    $domain_options = [];
    $has_domain_module = \Drupal::moduleHandler()->moduleExists('domain');

    if ($has_domain_module) {
      $domain_storage = $this->entityTypeManager->getStorage('domain');
      $domains = $domain_storage->loadMultipleSorted();
      foreach ($domains as $domain) {
        $domain_options[$domain->id()] = $domain->label();
      }
    }
    else {
      $domain_options['default'] = $this->t('Default');
    }

    $form['domain_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Domain'),
      '#options' => $domain_options,
      '#required' => TRUE,
      '#default_value' => $this->record->domain_id ?? ($has_domain_module ? NULL : 'default'),
      '#description' => $this->t('Select the domain this URL belongs to.'),
      '#access' => $has_domain_module || count($domain_options) > 1,
    ];

    $form['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL Path'),
      '#required' => TRUE,
      '#default_value' => $this->record->url ?? '',
      '#description' => $this->t('Enter the URL path (e.g., /about-us or https://example.com/page).'),
      '#maxlength' => 2048,
    ];

    $form['priority'] = [
      '#type' => 'select',
      '#title' => $this->t('Priority'),
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
      '#default_value' => $this->record->priority ?? '0.5',
      '#description' => $this->t('URL priority (0.0 to 1.0).'),
    ];

    $form['changefreq'] = [
      '#type' => 'select',
      '#title' => $this->t('Change frequency'),
      '#options' => [
        'always' => $this->t('Always'),
        'hourly' => $this->t('Hourly'),
        'daily' => $this->t('Daily'),
        'weekly' => $this->t('Weekly'),
        'monthly' => $this->t('Monthly'),
        'yearly' => $this->t('Yearly'),
        'never' => $this->t('Never'),
      ],
      '#default_value' => $this->record->changefreq ?? 'weekly',
      '#description' => $this->t('How frequently the page is likely to change.'),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Active'),
      '#default_value' => $this->record->status ?? 1,
      '#description' => $this->t('Only active URLs will be included in the sitemap.'),
    ];

    if ($id) {
      $form['id'] = [
        '#type' => 'hidden',
        '#value' => $id,
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => \Drupal\Core\Url::fromRoute('simple_sitemap_generator.custom_urls'),
      '#attributes' => ['class' => ['button']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $url = $form_state->getValue('url');

    // Basic URL validation.
    if (!preg_match('/^(\/|https?:\/\/)/', $url)) {
      $form_state->setErrorByName('url', $this->t('URL must start with / or http:// or https://'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = [
      'domain_id' => $form_state->getValue('domain_id'),
      'url' => $form_state->getValue('url'),
      'priority' => $form_state->getValue('priority'),
      'changefreq' => $form_state->getValue('changefreq'),
      'status' => $form_state->getValue('status') ? 1 : 0,
    ];

    $id = $form_state->getValue('id');

    if ($id) {
      // Update existing.
      $this->database->update('simple_sitemap_custom_urls')
        ->fields($values)
        ->condition('id', $id)
        ->execute();

      $this->messenger()->addStatus($this->t('Custom URL updated.'));
    }
    else {
      // Insert new.
      $values['created'] = $this->time->getRequestTime();
      $this->database->insert('simple_sitemap_custom_urls')
        ->fields($values)
        ->execute();

      $this->messenger()->addStatus($this->t('Custom URL added.'));
    }

    $form_state->setRedirect('simple_sitemap_generator.custom_urls');
  }

}
