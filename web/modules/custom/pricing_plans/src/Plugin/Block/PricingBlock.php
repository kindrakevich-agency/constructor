<?php

namespace Drupal\pricing_plans\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Pricing Block.
 *
 * @Block(
 *   id = "pricing_block",
 *   admin_label = @Translation("Pricing Block"),
 *   category = @Translation("Pricing")
 * )
 */
class PricingBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * Constructs a new PricingBlock instance.
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
      'title' => 'Choose Your Plan',
      'description' => 'Affordable and adaptable pricing to suit your goals.',
      'annual_label' => 'Bill annually',
      'monthly_label' => 'Bill monthly',
      'discount_text' => '10% OFF',
      'success_title' => 'Thank You!',
      'success_subtitle' => 'Your request has been received',
      'success_message' => 'We will contact you shortly to discuss your plan.',
      'success_button_text' => 'Close',
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

    $form['content']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $this->configuration['title'],
    ];

    $form['content']['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $this->configuration['description'],
      '#rows' => 2,
    ];

    $form['billing'] = [
      '#type' => 'details',
      '#title' => $this->t('Billing Toggle'),
      '#open' => FALSE,
    ];

    $form['billing']['annual_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Annual Billing Label'),
      '#default_value' => $this->configuration['annual_label'],
    ];

    $form['billing']['monthly_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Monthly Billing Label'),
      '#default_value' => $this->configuration['monthly_label'],
    ];

    $form['billing']['discount_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Discount Badge Text'),
      '#default_value' => $this->configuration['discount_text'],
    ];

    $form['success'] = [
      '#type' => 'details',
      '#title' => $this->t('Success Modal'),
      '#open' => FALSE,
    ];

    $form['success']['success_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Success Title'),
      '#default_value' => $this->configuration['success_title'],
    ];

    $form['success']['success_subtitle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Success Subtitle'),
      '#default_value' => $this->configuration['success_subtitle'],
    ];

    $form['success']['success_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Success Message'),
      '#default_value' => $this->configuration['success_message'],
      '#rows' => 2,
    ];

    $form['success']['success_button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Success Button Text'),
      '#default_value' => $this->configuration['success_button_text'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['title'] = $form_state->getValue(['content', 'title']);
    $this->configuration['description'] = $form_state->getValue(['content', 'description']);
    $this->configuration['annual_label'] = $form_state->getValue(['billing', 'annual_label']);
    $this->configuration['monthly_label'] = $form_state->getValue(['billing', 'monthly_label']);
    $this->configuration['discount_text'] = $form_state->getValue(['billing', 'discount_text']);
    $this->configuration['success_title'] = $form_state->getValue(['success', 'success_title']);
    $this->configuration['success_subtitle'] = $form_state->getValue(['success', 'success_subtitle']);
    $this->configuration['success_message'] = $form_state->getValue(['success', 'success_message']);
    $this->configuration['success_button_text'] = $form_state->getValue(['success', 'success_button_text']);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $current_langcode = $this->languageManager->getCurrentLanguage()->getId();

    // Load pricing plans.
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'pricing_plan')
      ->condition('status', 1)
      ->sort('field_plan_weight', 'ASC')
      ->sort('created', 'ASC')
      ->accessCheck(TRUE);

    $nids = $query->execute();
    $plans = $this->loadPlans($nids, $current_langcode);

    return [
      '#theme' => 'pricing_block',
      '#title' => $this->configuration['title'],
      '#description' => $this->configuration['description'],
      '#annual_label' => $this->configuration['annual_label'],
      '#monthly_label' => $this->configuration['monthly_label'],
      '#discount_text' => $this->configuration['discount_text'],
      '#plans' => $plans,
      '#attached' => [
        'library' => [
          'pricing_plans/pricing',
        ],
        'drupalSettings' => [
          'pricingPlans' => [
            'successTitle' => $this->configuration['success_title'],
            'successSubtitle' => $this->configuration['success_subtitle'],
            'successMessage' => $this->configuration['success_message'],
            'successButtonText' => $this->configuration['success_button_text'],
            'formLabels' => [
              'name' => $this->t('Name'),
              'email' => $this->t('Email'),
              'phone' => $this->t('Phone'),
              'company' => $this->t('Company'),
              'message' => $this->t('Message'),
              'submit' => $this->t('Submit'),
              'modalTitle' => $this->t('Get Started'),
              'modalSubtitle' => $this->t('Fill out the form and we will contact you'),
            ],
          ],
        ],
      ],
      '#cache' => [
        'tags' => ['node_list:pricing_plan'],
        'contexts' => ['languages:language_content'],
      ],
    ];
  }

  /**
   * Load pricing plans with translations.
   */
  protected function loadPlans(array $nids, $langcode) {
    $plans = [];
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

    foreach ($nodes as $node) {
      if ($node->hasTranslation($langcode)) {
        $node = $node->getTranslation($langcode);
      }

      $features = [];
      $features_text = $node->get('field_plan_features')->value ?? '';
      if (!empty($features_text)) {
        $features = array_filter(array_map('trim', explode("\n", $features_text)));
      }

      $plans[] = [
        'id' => $node->id(),
        'title' => $node->getTitle(),
        'description' => $node->get('field_plan_description')->value ?? '',
        'monthly_price' => (float) ($node->get('field_plan_monthly_price')->value ?? 0),
        'annual_price' => (float) ($node->get('field_plan_annual_price')->value ?? 0),
        'features' => $features,
        'cta_text' => $node->get('field_plan_cta_text')->value ?? 'Get started',
        'is_recommended' => (bool) ($node->get('field_plan_is_recommended')->value ?? FALSE),
        'badge_text' => $node->get('field_plan_badge_text')->value ?? '',
        'currency_symbol' => $node->get('field_plan_currency_symbol')->value ?? '$',
      ];
    }

    return $plans;
  }

}
