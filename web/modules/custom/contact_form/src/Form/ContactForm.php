<?php

namespace Drupal\contact_form\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\form_sender\FormSenderService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Contact form that sends data via Form Sender.
 */
class ContactForm extends FormBase {

  /**
   * The form sender service.
   *
   * @var \Drupal\form_sender\FormSenderService
   */
  protected $formSender;

  /**
   * Constructs a ContactForm object.
   */
  public function __construct(FormSenderService $form_sender) {
    $this->formSender = $form_sender;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_sender')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contact_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes']['id'] = 'contact-form';
    $form['#attributes']['class'][] = 'contact-form';
    $form['#attributes']['class'][] = 'space-y-5';
    $form['#prefix'] = '<div id="contact-form-wrapper">';
    $form['#suffix'] = '</div>';

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#placeholder' => $this->t('Your full name'),
      '#required' => TRUE,
      '#attributes' => [
        'class' => ['w-full', 'px-4', 'py-3', 'bg-white', 'dark:bg-slate-800', 'border', 'border-gray-300', 'dark:border-slate-600', 'rounded-lg', 'focus:outline-none', 'focus:ring-2', 'focus:ring-blue-500/20', 'focus:border-blue-500', 'dark:text-white', 'dark:placeholder-gray-400'],
      ],
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email Address'),
      '#placeholder' => $this->t("We'll get back to you here"),
      '#required' => TRUE,
      '#attributes' => [
        'class' => ['w-full', 'px-4', 'py-3', 'bg-white', 'dark:bg-slate-800', 'border', 'border-gray-300', 'dark:border-slate-600', 'rounded-lg', 'focus:outline-none', 'focus:ring-2', 'focus:ring-blue-500/20', 'focus:border-blue-500', 'dark:text-white', 'dark:placeholder-gray-400'],
      ],
    ];

    $form['company'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Company Name'),
      '#placeholder' => $this->t('Let us know who you represent'),
      '#attributes' => [
        'class' => ['w-full', 'px-4', 'py-3', 'bg-white', 'dark:bg-slate-800', 'border', 'border-gray-300', 'dark:border-slate-600', 'rounded-lg', 'focus:outline-none', 'focus:ring-2', 'focus:ring-blue-500/20', 'focus:border-blue-500', 'dark:text-white', 'dark:placeholder-gray-400'],
      ],
    ];

    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#placeholder' => $this->t("What's this about?"),
      '#required' => TRUE,
      '#attributes' => [
        'class' => ['w-full', 'px-4', 'py-3', 'bg-white', 'dark:bg-slate-800', 'border', 'border-gray-300', 'dark:border-slate-600', 'rounded-lg', 'focus:outline-none', 'focus:ring-2', 'focus:ring-blue-500/20', 'focus:border-blue-500', 'dark:text-white', 'dark:placeholder-gray-400'],
      ],
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#placeholder' => $this->t('Tell us how we can help'),
      '#required' => TRUE,
      '#rows' => 4,
      '#attributes' => [
        'class' => ['w-full', 'px-4', 'py-3', 'bg-white', 'dark:bg-slate-800', 'border', 'border-gray-300', 'dark:border-slate-600', 'rounded-lg', 'focus:outline-none', 'focus:ring-2', 'focus:ring-blue-500/20', 'focus:border-blue-500', 'dark:text-white', 'dark:placeholder-gray-400', 'resize-none'],
      ],
    ];

    // Honeypot field for spam protection (hidden from users).
    $form['website_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Website'),
      '#attributes' => [
        'class' => ['contact-form-honeypot'],
        'autocomplete' => 'off',
        'tabindex' => '-1',
      ],
      '#label_attributes' => [
        'class' => ['contact-form-honeypot'],
      ],
      '#wrapper_attributes' => [
        'class' => ['contact-form-honeypot'],
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send Message'),
      '#attributes' => [
        'class' => ['w-full', 'py-3.5', 'bg-blue-500', 'text-white', 'font-medium', 'rounded-lg', 'hover:bg-blue-600', 'transition-colors', 'contact-form-submit'],
      ],
    ];

    // Attach contact form library.
    $form['#attached']['library'][] = 'contact_form/contact_form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Check honeypot field - if filled, it's likely a bot.
    // Silently reject spam by setting a flag, then reject in submit.
    $honeypot = $form_state->getValue('website_url');
    if (!empty($honeypot)) {
      $form_state->set('is_spam', TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Silently reject spam (honeypot filled) - pretend success to fool bots.
    if ($form_state->get('is_spam')) {
      return;
    }

    $values = $form_state->getValues();

    $this->formSender->send([
      'subject' => $values['subject'],
      'message' => $values['message'],
      'form_type' => 'contact',
      'data' => [
        'name' => $values['name'],
        'email' => $values['email'],
        'company' => $values['company'] ?: '-',
        'subject' => $values['subject'],
      ],
    ]);
  }

}
