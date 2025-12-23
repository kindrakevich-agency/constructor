<?php

namespace Drupal\content_commerce\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Commerce store configuration form.
 */
class CommerceConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['content_commerce.config'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_commerce_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('content_commerce.config');

    // Store Information.
    $form['store_info'] = [
      '#type' => 'details',
      '#title' => $this->t('Store Information'),
      '#open' => TRUE,
    ];

    $form['store_info']['store_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Store Name'),
      '#default_value' => $config->get('store_name') ?: '',
      '#description' => $this->t('The name of your store.'),
      '#maxlength' => 255,
    ];

    $form['store_info']['store_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Store Email'),
      '#default_value' => $config->get('store_email') ?: '',
      '#description' => $this->t('Email address for order notifications and customer inquiries.'),
    ];

    $form['store_info']['store_phone'] = [
      '#type' => 'tel',
      '#title' => $this->t('Store Phone'),
      '#default_value' => $config->get('store_phone') ?: '',
      '#description' => $this->t('Contact phone number.'),
    ];

    $form['store_info']['store_address'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Store Address'),
      '#default_value' => $config->get('store_address') ?: '',
      '#description' => $this->t('Physical store address.'),
      '#rows' => 3,
    ];

    // Tax Settings.
    $form['tax'] = [
      '#type' => 'details',
      '#title' => $this->t('Tax Settings'),
      '#open' => TRUE,
    ];

    $form['tax']['tax_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Tax'),
      '#default_value' => $config->get('tax_enabled') ?: FALSE,
      '#description' => $this->t('Enable tax calculation on products.'),
    ];

    $form['tax']['tax_rate'] = [
      '#type' => 'number',
      '#title' => $this->t('Tax Rate (%)'),
      '#default_value' => $config->get('tax_rate') ?: 0,
      '#min' => 0,
      '#max' => 100,
      '#step' => 0.01,
      '#description' => $this->t('Default tax rate percentage (e.g., 20 for 20% VAT).'),
      '#states' => [
        'visible' => [
          ':input[name="tax_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['tax']['tax_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tax Label'),
      '#default_value' => $config->get('tax_label') ?: 'VAT',
      '#description' => $this->t('Label for tax (e.g., VAT, Tax, GST).'),
      '#maxlength' => 50,
      '#states' => [
        'visible' => [
          ':input[name="tax_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['tax']['prices_include_tax'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Prices include tax'),
      '#default_value' => $config->get('prices_include_tax') ?: FALSE,
      '#description' => $this->t('Check if product prices already include tax.'),
      '#states' => [
        'visible' => [
          ':input[name="tax_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Features.
    $form['features'] = [
      '#type' => 'details',
      '#title' => $this->t('Store Features'),
      '#open' => TRUE,
    ];

    $form['features']['enable_wishlist'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Wishlist'),
      '#default_value' => $config->get('enable_wishlist') ?: FALSE,
      '#description' => $this->t('Allow customers to save products to a wishlist.'),
    ];

    $form['features']['enable_compare'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Product Compare'),
      '#default_value' => $config->get('enable_compare') ?: FALSE,
      '#description' => $this->t('Allow customers to compare products.'),
    ];

    $form['features']['enable_reviews'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Product Reviews'),
      '#default_value' => $config->get('enable_reviews') ?: FALSE,
      '#description' => $this->t('Allow customers to leave product reviews.'),
    ];

    $form['features']['enable_stock_notification'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Stock Notifications'),
      '#default_value' => $config->get('enable_stock_notification') ?: FALSE,
      '#description' => $this->t('Allow customers to subscribe for back-in-stock notifications.'),
    ];

    // Order Settings.
    $form['orders'] = [
      '#type' => 'details',
      '#title' => $this->t('Order Settings'),
      '#open' => TRUE,
    ];

    $form['orders']['order_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Order Number Prefix'),
      '#default_value' => $config->get('order_prefix') ?: 'ORD-',
      '#description' => $this->t('Prefix for order numbers (e.g., ORD-, INV-).'),
      '#maxlength' => 20,
    ];

    $form['orders']['min_order_amount'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum Order Amount'),
      '#default_value' => $config->get('min_order_amount') ?: 0,
      '#min' => 0,
      '#step' => 0.01,
      '#description' => $this->t('Minimum order amount required for checkout. Set to 0 for no minimum.'),
    ];

    $form['orders']['free_shipping_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Free Shipping Threshold'),
      '#default_value' => $config->get('free_shipping_threshold') ?: 0,
      '#min' => 0,
      '#step' => 0.01,
      '#description' => $this->t('Order amount for free shipping. Set to 0 to disable.'),
    ];

    $form['orders']['send_order_confirmation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send Order Confirmation Email'),
      '#default_value' => $config->get('send_order_confirmation') ?? TRUE,
      '#description' => $this->t('Send email confirmation to customer after order.'),
    ];

    $form['orders']['send_admin_notification'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send Admin Notification'),
      '#default_value' => $config->get('send_admin_notification') ?? TRUE,
      '#description' => $this->t('Send notification email to store email when new order is placed.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('content_commerce.config')
      // Store info.
      ->set('store_name', $form_state->getValue('store_name'))
      ->set('store_email', $form_state->getValue('store_email'))
      ->set('store_phone', $form_state->getValue('store_phone'))
      ->set('store_address', $form_state->getValue('store_address'))
      // Tax.
      ->set('tax_enabled', $form_state->getValue('tax_enabled'))
      ->set('tax_rate', $form_state->getValue('tax_rate'))
      ->set('tax_label', $form_state->getValue('tax_label'))
      ->set('prices_include_tax', $form_state->getValue('prices_include_tax'))
      // Features.
      ->set('enable_wishlist', $form_state->getValue('enable_wishlist'))
      ->set('enable_compare', $form_state->getValue('enable_compare'))
      ->set('enable_reviews', $form_state->getValue('enable_reviews'))
      ->set('enable_stock_notification', $form_state->getValue('enable_stock_notification'))
      // Orders.
      ->set('order_prefix', $form_state->getValue('order_prefix'))
      ->set('min_order_amount', $form_state->getValue('min_order_amount'))
      ->set('free_shipping_threshold', $form_state->getValue('free_shipping_threshold'))
      ->set('send_order_confirmation', $form_state->getValue('send_order_confirmation'))
      ->set('send_admin_notification', $form_state->getValue('send_admin_notification'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
