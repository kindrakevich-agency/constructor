<?php

namespace Drupal\content_commerce\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Commerce settings configuration form.
 */
class CommerceSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['content_commerce.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_commerce_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('content_commerce.settings');

    $form['currency'] = [
      '#type' => 'select',
      '#title' => $this->t('Currency'),
      '#options' => [
        'USD' => $this->t('US Dollar (USD)'),
        'EUR' => $this->t('Euro (EUR)'),
        'GBP' => $this->t('British Pound (GBP)'),
        'UAH' => $this->t('Ukrainian Hryvnia (UAH)'),
        'PLN' => $this->t('Polish Zloty (PLN)'),
        'CAD' => $this->t('Canadian Dollar (CAD)'),
        'AUD' => $this->t('Australian Dollar (AUD)'),
        'JPY' => $this->t('Japanese Yen (JPY)'),
        'CNY' => $this->t('Chinese Yuan (CNY)'),
      ],
      '#default_value' => $config->get('currency') ?: 'USD',
    ];

    $form['currency_symbol'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Currency Symbol'),
      '#default_value' => $config->get('currency_symbol') ?: '$',
      '#size' => 5,
      '#maxlength' => 10,
      '#description' => $this->t('Symbol to display with prices (e.g., $, €, £, ₴).'),
    ];

    $form['currency_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Currency Symbol Position'),
      '#options' => [
        'before' => $this->t('Before price ($100)'),
        'after' => $this->t('After price (100$)'),
      ],
      '#default_value' => $config->get('currency_position') ?: 'before',
    ];

    $form['shipping_info'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Shipping Information'),
      '#default_value' => $config->get('shipping_info') ?: '',
      '#description' => $this->t('Shipping information text displayed on product pages.'),
      '#rows' => 4,
    ];

    $form['default_colors'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Colors'),
      '#default_value' => $config->get('default_colors') ?: 'Black:#1f2937,Green:#059669,Gold:#fcd34d,Pink:#f9a8d4,Gray:#9ca3af',
      '#description' => $this->t('Default color options for new products. Format: Label:#hex,Label2:#hex2'),
      '#maxlength' => 512,
    ];

    $form['default_sizes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Sizes'),
      '#default_value' => $config->get('default_sizes') ?: 'Small,Medium,Large,XL,XXL',
      '#description' => $this->t('Default size options for new products. Comma-separated list.'),
      '#maxlength' => 256,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('content_commerce.settings')
      ->set('currency', $form_state->getValue('currency'))
      ->set('currency_symbol', $form_state->getValue('currency_symbol'))
      ->set('currency_position', $form_state->getValue('currency_position'))
      ->set('shipping_info', $form_state->getValue('shipping_info'))
      ->set('default_colors', $form_state->getValue('default_colors'))
      ->set('default_sizes', $form_state->getValue('default_sizes'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
