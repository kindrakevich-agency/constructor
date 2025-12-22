<?php

namespace Drupal\simple_metatag\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Google Tag (gtag.js) settings per domain.
 */
class GtagSettingsForm extends ConfigFormBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a GtagSettingsForm object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['simple_metatag.gtag_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_metatag_gtag_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('simple_metatag.gtag_settings');
    $gtag_codes = $config->get('gtag_codes') ?: [];

    // Check if Domain module is available.
    $domain_module_exists = $this->moduleHandler->moduleExists('domain');

    if ($domain_module_exists) {
      $form['description'] = [
        '#type' => 'markup',
        '#markup' => '<p>' . $this->t('Configure Google Tag (gtag.js) codes for different domains. The appropriate tag will be automatically injected into your site\'s &lt;head&gt; section based on the current domain.') . '</p>',
      ];

      // Get available domains.
      $domains = $this->getAvailableDomains();

      $form['gtag_codes'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Google Tag Codes'),
        '#tree' => TRUE,
      ];

      // Add a field for each domain.
      foreach ($domains as $domain_id => $domain_label) {
        $form['gtag_codes'][$domain_id] = [
          '#type' => 'textarea',
          '#title' => $this->t('Google Tag for @domain', ['@domain' => $domain_label]),
          '#default_value' => $gtag_codes[$domain_id] ?? '',
          '#rows' => 8,
          '#description' => $this->t('Paste your complete Google Tag (gtag.js) code here, including the &lt;script&gt; tags.'),
        ];
      }

      // Add option for a global fallback.
      $form['gtag_codes']['_default'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Default Google Tag (All Domains)'),
        '#default_value' => $gtag_codes['_default'] ?? '',
        '#rows' => 8,
        '#description' => $this->t('This Google Tag will be used as a fallback for domains not specifically configured above.'),
      ];
    }
    else {
      // Single Google Tag Code field when Domain module is not installed.
      $form['description'] = [
        '#type' => 'markup',
        '#markup' => '<p>' . $this->t('Configure Google Tag (gtag.js) code. The tag will be automatically injected into your site\'s &lt;head&gt; section.') . '</p>',
      ];

      $form['gtag_codes'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Google Tag Code'),
        '#tree' => TRUE,
      ];

      $form['gtag_codes']['_default'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Google Tag Code'),
        '#default_value' => $gtag_codes['_default'] ?? '',
        '#rows' => 8,
        '#description' => $this->t('Paste your complete Google Tag (gtag.js) code here, including the &lt;script&gt; tags. Example:<br><code>&lt;script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX"&gt;&lt;/script&gt;<br>&lt;script&gt;<br>window.dataLayer = window.dataLayer || [];<br>function gtag(){dataLayer.push(arguments);}<br>gtag(\'js\', new Date());<br>gtag(\'config\', \'G-XXXXXXXXXX\');<br>&lt;/script&gt;</code>'),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $gtag_codes = $form_state->getValue('gtag_codes');

    // Remove empty values.
    $gtag_codes = array_filter($gtag_codes, function($value) {
      return !empty(trim($value));
    });

    $this->config('simple_metatag.gtag_settings')
      ->set('gtag_codes', $gtag_codes)
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Get available domains.
   *
   * @return array
   *   Array of domain IDs/hostnames keyed by domain ID.
   */
  protected function getAvailableDomains() {
    $domains = [];

    // Only return domains if Domain module is available.
    if ($this->moduleHandler->moduleExists('domain')) {
      $domain_storage = \Drupal::entityTypeManager()->getStorage('domain');
      $domain_entities = $domain_storage->loadMultiple();

      foreach ($domain_entities as $domain) {
        $domains[$domain->id()] = $domain->label() . ' (' . $domain->getHostname() . ')';
      }
    }

    return $domains;
  }

}
