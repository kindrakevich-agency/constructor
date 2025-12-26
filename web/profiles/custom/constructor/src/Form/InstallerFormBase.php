<?php

namespace Drupal\constructor\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Constructor installation forms.
 */
abstract class InstallerFormBase extends FormBase {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Get the installation steps.
   *
   * @return array
   *   The installation steps with translated titles.
   */
  protected function getSteps(): array {
    return [
      1 => [
        'id' => 'site_basics',
        'title' => $this->t('Site Basics'),
        'description' => $this->t('Configure basic site settings'),
      ],
      2 => [
        'id' => 'languages',
        'title' => $this->t('Languages'),
        'description' => $this->t('Select languages for your site'),
      ],
      3 => [
        'id' => 'content_types',
        'title' => $this->t('Content Types'),
        'description' => $this->t('Define your content structure'),
      ],
      4 => [
        'id' => 'ai_integration',
        'title' => $this->t('AI Integration'),
        'description' => $this->t('Set up AI content generation'),
      ],
    ];
  }

  /**
   * Constructs an InstallerFormBase object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state')
    );
  }

  /**
   * Get the current step number.
   *
   * @return int
   *   The current step number.
   */
  abstract protected function getStepNumber(): int;

  /**
   * Build the step-specific form elements.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The modified form array.
   */
  abstract protected function buildStepForm(array $form, FormStateInterface $form_state): array;

  /**
   * Submit handler for the step-specific form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  abstract protected function submitStepForm(array &$form, FormStateInterface $form_state): void;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $current_step = $this->getStepNumber();
    $steps = $this->getSteps();
    $step_info = $steps[$current_step];

    // Set form title for the installer.
    $form['#title'] = $this->t('@title', ['@title' => $step_info['title']]);

    // Add Tailwind form wrapper classes.
    $form['#attributes']['class'][] = 'space-y-6';

    // Build step-specific form.
    $form = $this->buildStepForm($form, $form_state);

    // Add navigation buttons with Tailwind classes.
    $form['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['flex', 'justify-end', 'gap-4', 'mt-8', 'pt-6', 'border-t', 'border-gray-200']],
    ];

    // Next/Continue button.
    $form['actions']['next'] = [
      '#type' => 'submit',
      '#value' => $current_step === count($steps) ? $this->t('Finish Installation') : $this->t('Continue'),
      '#name' => 'next',
      '#button_type' => 'primary',
      '#attributes' => [
        'class' => [
          'px-8', 'py-3', 'bg-slate-800', 'text-white', 'font-medium',
          'rounded-lg', 'hover:bg-slate-900', 'transition-colors', 'cursor-pointer',
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->submitStepForm($form, $form_state);
    // Ensure form doesn't rebuild - let Drupal installer proceed to next task.
    $form_state->setRebuild(FALSE);
  }

  /**
   * Save data to key_value storage for later processing.
   *
   * During Drupal installation, we use key_value storage which persists
   * to the database and works reliably between HTTP requests.
   *
   * @param string $key
   *   The state key (without prefix).
   * @param mixed $value
   *   The value to save.
   */
  protected function saveToState(string $key, $value): void {
    try {
      // Use key_value storage which is more reliable during installation.
      $key_value = \Drupal::keyValue('constructor_install');
      $key_value->set($key, $value);
    }
    catch (\Exception $e) {
      \Drupal::logger('constructor')->error('saveToState error for "@key": @message', [
        '@key' => $key,
        '@message' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Get data from key_value storage.
   *
   * @param string $key
   *   The state key (without prefix).
   * @param mixed $default
   *   The default value.
   *
   * @return mixed
   *   The stored value or default.
   */
  protected function getFromState(string $key, $default = NULL) {
    try {
      $key_value = \Drupal::keyValue('constructor_install');
      $value = $key_value->get($key);
      return $value !== NULL ? $value : $default;
    }
    catch (\Exception $e) {
      return $default;
    }
  }

  /**
   * Helper to create a Tailwind-styled text field.
   */
  protected function createTextField(string $title, string $default = '', bool $required = FALSE, string $placeholder = '', string $description = ''): array {
    return [
      '#type' => 'textfield',
      '#title' => $title,
      '#default_value' => $default,
      '#required' => $required,
      '#attributes' => [
        'placeholder' => $placeholder,
        'class' => [
          'w-full', 'px-4', 'py-3', 'border', 'border-gray-200', 'rounded-lg',
          'text-gray-900', 'placeholder-gray-400', 'focus:outline-none',
          'focus:ring-2', 'focus:ring-blue-500', 'focus:border-transparent',
        ],
      ],
      '#description' => $description,
      '#wrapper_attributes' => ['class' => ['mb-6']],
      '#label_attributes' => ['class' => ['block', 'text-sm', 'font-medium', 'text-gray-700', 'mb-2']],
    ];
  }

  /**
   * Helper to create a Tailwind-styled email field.
   */
  protected function createEmailField(string $title, string $default = '', bool $required = FALSE, string $placeholder = '', string $description = ''): array {
    $field = $this->createTextField($title, $default, $required, $placeholder, $description);
    $field['#type'] = 'email';
    return $field;
  }

  /**
   * Helper to create a Tailwind-styled select field.
   */
  protected function createSelectField(string $title, array $options, string $default = '', bool $required = FALSE, string $description = ''): array {
    return [
      '#type' => 'select',
      '#title' => $title,
      '#options' => $options,
      '#default_value' => $default,
      '#required' => $required,
      '#attributes' => [
        'class' => [
          'w-full', 'px-4', 'py-3', 'border', 'border-gray-200', 'rounded-lg',
          'text-gray-900', 'bg-white', 'focus:outline-none', 'focus:ring-2',
          'focus:ring-blue-500', 'focus:border-transparent', 'appearance-none',
        ],
      ],
      '#description' => $description,
      '#wrapper_attributes' => ['class' => ['mb-6', 'relative']],
      '#label_attributes' => ['class' => ['block', 'text-sm', 'font-medium', 'text-gray-700', 'mb-2']],
    ];
  }

  /**
   * Helper to create a Tailwind-styled checkboxes field.
   */
  protected function createCheckboxesField(string $title, array $options, array $default = [], string $description = ''): array {
    return [
      '#type' => 'checkboxes',
      '#title' => $title,
      '#options' => $options,
      '#default_value' => $default,
      '#description' => $description,
      '#wrapper_attributes' => ['class' => ['mb-6']],
      '#label_attributes' => ['class' => ['block', 'text-sm', 'font-medium', 'text-gray-700', 'mb-3']],
    ];
  }

  /**
   * Helper to create a section header.
   */
  protected function createSectionHeader(string $title, string $description = ''): array {
    $markup = '<div class="mb-6">';
    $markup .= '<h2 class="text-xl font-semibold text-gray-900 mb-2">' . $title . '</h2>';
    if ($description) {
      $markup .= '<p class="text-gray-500 text-sm">' . $description . '</p>';
    }
    $markup .= '</div>';

    return [
      '#markup' => $markup,
    ];
  }

}
