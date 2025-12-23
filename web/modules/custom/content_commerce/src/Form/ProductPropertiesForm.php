<?php

namespace Drupal\content_commerce\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Product properties configuration form.
 */
class ProductPropertiesForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['content_commerce.properties'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_commerce_properties_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('content_commerce.properties');

    // Colors Section.
    $form['colors_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Color Options'),
      '#open' => TRUE,
      '#description' => $this->t('Define available color options for products. Each color needs a name and hex code.'),
    ];

    $colors = $config->get('colors') ?: $this->getDefaultColors();
    $num_colors = $form_state->get('num_colors');
    if ($num_colors === NULL) {
      $num_colors = count($colors) ?: 1;
      $form_state->set('num_colors', $num_colors);
    }

    $form['colors_section']['colors'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'colors-wrapper'],
      '#tree' => TRUE,
    ];

    for ($i = 0; $i < $num_colors; $i++) {
      $form['colors_section']['colors'][$i] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['container-inline', 'color-row']],
      ];

      $form['colors_section']['colors'][$i]['name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Color Name'),
        '#title_display' => $i === 0 ? 'before' : 'invisible',
        '#default_value' => $colors[$i]['name'] ?? '',
        '#size' => 20,
        '#maxlength' => 50,
        '#placeholder' => $this->t('e.g., Black'),
      ];

      $form['colors_section']['colors'][$i]['hex'] = [
        '#type' => 'color',
        '#title' => $this->t('Hex Code'),
        '#title_display' => $i === 0 ? 'before' : 'invisible',
        '#default_value' => $colors[$i]['hex'] ?? '#000000',
      ];

      $form['colors_section']['colors'][$i]['hex_text'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Hex Value'),
        '#title_display' => $i === 0 ? 'before' : 'invisible',
        '#default_value' => $colors[$i]['hex'] ?? '#000000',
        '#size' => 10,
        '#maxlength' => 7,
        '#placeholder' => '#000000',
        '#attributes' => ['class' => ['hex-text-input']],
      ];
    }

    $form['colors_section']['add_color'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Color'),
      '#submit' => ['::addColorCallback'],
      '#ajax' => [
        'callback' => '::colorsAjaxCallback',
        'wrapper' => 'colors-wrapper',
      ],
      '#limit_validation_errors' => [],
    ];

    if ($num_colors > 1) {
      $form['colors_section']['remove_color'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove Last Color'),
        '#submit' => ['::removeColorCallback'],
        '#ajax' => [
          'callback' => '::colorsAjaxCallback',
          'wrapper' => 'colors-wrapper',
        ],
        '#limit_validation_errors' => [],
      ];
    }

    // Sizes Section.
    $form['sizes_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Size Options'),
      '#open' => TRUE,
      '#description' => $this->t('Define available size options for products.'),
    ];

    $sizes = $config->get('sizes') ?: $this->getDefaultSizes();
    $num_sizes = $form_state->get('num_sizes');
    if ($num_sizes === NULL) {
      $num_sizes = count($sizes) ?: 1;
      $form_state->set('num_sizes', $num_sizes);
    }

    $form['sizes_section']['sizes'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'sizes-wrapper'],
      '#tree' => TRUE,
    ];

    for ($i = 0; $i < $num_sizes; $i++) {
      $form['sizes_section']['sizes'][$i] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['container-inline', 'size-row']],
      ];

      $form['sizes_section']['sizes'][$i]['name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Size'),
        '#title_display' => $i === 0 ? 'before' : 'invisible',
        '#default_value' => $sizes[$i]['name'] ?? '',
        '#size' => 15,
        '#maxlength' => 30,
        '#placeholder' => $this->t('e.g., Medium'),
      ];

      $form['sizes_section']['sizes'][$i]['code'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Code'),
        '#title_display' => $i === 0 ? 'before' : 'invisible',
        '#default_value' => $sizes[$i]['code'] ?? '',
        '#size' => 8,
        '#maxlength' => 10,
        '#placeholder' => $this->t('e.g., M'),
      ];

      $form['sizes_section']['sizes'][$i]['sort_order'] = [
        '#type' => 'number',
        '#title' => $this->t('Order'),
        '#title_display' => $i === 0 ? 'before' : 'invisible',
        '#default_value' => $sizes[$i]['sort_order'] ?? ($i * 10),
        '#min' => 0,
        '#max' => 999,
        '#size' => 5,
      ];
    }

    $form['sizes_section']['add_size'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Size'),
      '#submit' => ['::addSizeCallback'],
      '#ajax' => [
        'callback' => '::sizesAjaxCallback',
        'wrapper' => 'sizes-wrapper',
      ],
      '#limit_validation_errors' => [],
    ];

    if ($num_sizes > 1) {
      $form['sizes_section']['remove_size'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove Last Size'),
        '#submit' => ['::removeSizeCallback'],
        '#ajax' => [
          'callback' => '::sizesAjaxCallback',
          'wrapper' => 'sizes-wrapper',
        ],
        '#limit_validation_errors' => [],
      ];
    }

    // Materials Section.
    $form['materials_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Material Options'),
      '#open' => TRUE,
      '#description' => $this->t('Define available material options for products.'),
    ];

    $materials = $config->get('materials') ?: [];
    $num_materials = $form_state->get('num_materials');
    if ($num_materials === NULL) {
      $num_materials = count($materials) ?: 1;
      $form_state->set('num_materials', $num_materials);
    }

    $form['materials_section']['materials'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'materials-wrapper'],
      '#tree' => TRUE,
    ];

    for ($i = 0; $i < $num_materials; $i++) {
      $form['materials_section']['materials'][$i] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['container-inline', 'material-row']],
      ];

      $form['materials_section']['materials'][$i]['name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Material'),
        '#title_display' => $i === 0 ? 'before' : 'invisible',
        '#default_value' => $materials[$i]['name'] ?? '',
        '#size' => 30,
        '#maxlength' => 100,
        '#placeholder' => $this->t('e.g., Cotton'),
      ];

      $form['materials_section']['materials'][$i]['description'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Description'),
        '#title_display' => $i === 0 ? 'before' : 'invisible',
        '#default_value' => $materials[$i]['description'] ?? '',
        '#size' => 40,
        '#maxlength' => 255,
        '#placeholder' => $this->t('e.g., 100% organic cotton'),
      ];
    }

    $form['materials_section']['add_material'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Material'),
      '#submit' => ['::addMaterialCallback'],
      '#ajax' => [
        'callback' => '::materialsAjaxCallback',
        'wrapper' => 'materials-wrapper',
      ],
      '#limit_validation_errors' => [],
    ];

    if ($num_materials > 1) {
      $form['materials_section']['remove_material'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove Last Material'),
        '#submit' => ['::removeMaterialCallback'],
        '#ajax' => [
          'callback' => '::materialsAjaxCallback',
          'wrapper' => 'materials-wrapper',
        ],
        '#limit_validation_errors' => [],
      ];
    }

    // Brands Section.
    $form['brands_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Brand Options'),
      '#open' => TRUE,
      '#description' => $this->t('Define available brand options for products.'),
    ];

    $brands = $config->get('brands') ?: [];
    $num_brands = $form_state->get('num_brands');
    if ($num_brands === NULL) {
      $num_brands = count($brands) ?: 1;
      $form_state->set('num_brands', $num_brands);
    }

    $form['brands_section']['brands'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'brands-wrapper'],
      '#tree' => TRUE,
    ];

    for ($i = 0; $i < $num_brands; $i++) {
      $form['brands_section']['brands'][$i] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['container-inline', 'brand-row']],
      ];

      $form['brands_section']['brands'][$i]['name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Brand Name'),
        '#title_display' => $i === 0 ? 'before' : 'invisible',
        '#default_value' => $brands[$i]['name'] ?? '',
        '#size' => 30,
        '#maxlength' => 100,
        '#placeholder' => $this->t('e.g., Nike'),
      ];

      $form['brands_section']['brands'][$i]['website'] = [
        '#type' => 'url',
        '#title' => $this->t('Website'),
        '#title_display' => $i === 0 ? 'before' : 'invisible',
        '#default_value' => $brands[$i]['website'] ?? '',
        '#size' => 40,
        '#maxlength' => 255,
        '#placeholder' => $this->t('e.g., https://nike.com'),
      ];
    }

    $form['brands_section']['add_brand'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Brand'),
      '#submit' => ['::addBrandCallback'],
      '#ajax' => [
        'callback' => '::brandsAjaxCallback',
        'wrapper' => 'brands-wrapper',
      ],
      '#limit_validation_errors' => [],
    ];

    if ($num_brands > 1) {
      $form['brands_section']['remove_brand'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove Last Brand'),
        '#submit' => ['::removeBrandCallback'],
        '#ajax' => [
          'callback' => '::brandsAjaxCallback',
          'wrapper' => 'brands-wrapper',
        ],
        '#limit_validation_errors' => [],
      ];
    }

    // Custom Options Groups Section.
    $form['custom_options_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Custom Option Groups'),
      '#open' => TRUE,
      '#description' => $this->t('Create custom option groups for products (e.g., Warranty, Style, Finish). Each group can have multiple options.'),
    ];

    $custom_options = $config->get('custom_options') ?: [];
    $num_custom_groups = $form_state->get('num_custom_groups');
    if ($num_custom_groups === NULL) {
      $num_custom_groups = count($custom_options) ?: 0;
      $form_state->set('num_custom_groups', $num_custom_groups);
    }

    $form['custom_options_section']['custom_options'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'custom-options-wrapper'],
      '#tree' => TRUE,
    ];

    for ($i = 0; $i < $num_custom_groups; $i++) {
      $form['custom_options_section']['custom_options'][$i] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Option Group @num', ['@num' => $i + 1]),
        '#attributes' => ['class' => ['custom-option-group']],
      ];

      $form['custom_options_section']['custom_options'][$i]['name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Group Name'),
        '#default_value' => $custom_options[$i]['name'] ?? '',
        '#size' => 30,
        '#maxlength' => 100,
        '#placeholder' => $this->t('e.g., Warranty'),
        '#required' => FALSE,
      ];

      $form['custom_options_section']['custom_options'][$i]['type'] = [
        '#type' => 'select',
        '#title' => $this->t('Selection Type'),
        '#options' => [
          'checkboxes' => $this->t('Multiple selection (checkboxes)'),
          'select' => $this->t('Single selection (dropdown)'),
        ],
        '#default_value' => $custom_options[$i]['type'] ?? 'checkboxes',
      ];

      $form['custom_options_section']['custom_options'][$i]['options'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Options'),
        '#default_value' => isset($custom_options[$i]['options']) ? implode("\n", $custom_options[$i]['options']) : '',
        '#description' => $this->t('Enter one option per line.'),
        '#rows' => 4,
        '#placeholder' => $this->t("1 Year\n2 Years\n3 Years\nLifetime"),
      ];
    }

    $form['custom_options_section']['add_custom_group'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Option Group'),
      '#submit' => ['::addCustomGroupCallback'],
      '#ajax' => [
        'callback' => '::customOptionsAjaxCallback',
        'wrapper' => 'custom-options-wrapper',
      ],
      '#limit_validation_errors' => [],
    ];

    if ($num_custom_groups > 0) {
      $form['custom_options_section']['remove_custom_group'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove Last Group'),
        '#submit' => ['::removeCustomGroupCallback'],
        '#ajax' => [
          'callback' => '::customOptionsAjaxCallback',
          'wrapper' => 'custom-options-wrapper',
        ],
        '#limit_validation_errors' => [],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Returns default colors.
   */
  protected function getDefaultColors() {
    return [
      ['name' => 'Black', 'hex' => '#1f2937'],
      ['name' => 'White', 'hex' => '#ffffff'],
      ['name' => 'Gray', 'hex' => '#9ca3af'],
      ['name' => 'Red', 'hex' => '#dc2626'],
      ['name' => 'Blue', 'hex' => '#2563eb'],
      ['name' => 'Green', 'hex' => '#059669'],
    ];
  }

  /**
   * Returns default sizes.
   */
  protected function getDefaultSizes() {
    return [
      ['name' => 'Extra Small', 'code' => 'XS', 'sort_order' => 0],
      ['name' => 'Small', 'code' => 'S', 'sort_order' => 10],
      ['name' => 'Medium', 'code' => 'M', 'sort_order' => 20],
      ['name' => 'Large', 'code' => 'L', 'sort_order' => 30],
      ['name' => 'Extra Large', 'code' => 'XL', 'sort_order' => 40],
      ['name' => 'XXL', 'code' => 'XXL', 'sort_order' => 50],
    ];
  }

  /**
   * Ajax callback for colors.
   */
  public function colorsAjaxCallback(array &$form, FormStateInterface $form_state) {
    return $form['colors_section']['colors'];
  }

  /**
   * Submit handler for adding a color.
   */
  public function addColorCallback(array &$form, FormStateInterface $form_state) {
    $num_colors = $form_state->get('num_colors');
    $form_state->set('num_colors', $num_colors + 1);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for removing a color.
   */
  public function removeColorCallback(array &$form, FormStateInterface $form_state) {
    $num_colors = $form_state->get('num_colors');
    if ($num_colors > 1) {
      $form_state->set('num_colors', $num_colors - 1);
    }
    $form_state->setRebuild();
  }

  /**
   * Ajax callback for sizes.
   */
  public function sizesAjaxCallback(array &$form, FormStateInterface $form_state) {
    return $form['sizes_section']['sizes'];
  }

  /**
   * Submit handler for adding a size.
   */
  public function addSizeCallback(array &$form, FormStateInterface $form_state) {
    $num_sizes = $form_state->get('num_sizes');
    $form_state->set('num_sizes', $num_sizes + 1);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for removing a size.
   */
  public function removeSizeCallback(array &$form, FormStateInterface $form_state) {
    $num_sizes = $form_state->get('num_sizes');
    if ($num_sizes > 1) {
      $form_state->set('num_sizes', $num_sizes - 1);
    }
    $form_state->setRebuild();
  }

  /**
   * Ajax callback for materials.
   */
  public function materialsAjaxCallback(array &$form, FormStateInterface $form_state) {
    return $form['materials_section']['materials'];
  }

  /**
   * Submit handler for adding a material.
   */
  public function addMaterialCallback(array &$form, FormStateInterface $form_state) {
    $num_materials = $form_state->get('num_materials');
    $form_state->set('num_materials', $num_materials + 1);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for removing a material.
   */
  public function removeMaterialCallback(array &$form, FormStateInterface $form_state) {
    $num_materials = $form_state->get('num_materials');
    if ($num_materials > 1) {
      $form_state->set('num_materials', $num_materials - 1);
    }
    $form_state->setRebuild();
  }

  /**
   * Ajax callback for brands.
   */
  public function brandsAjaxCallback(array &$form, FormStateInterface $form_state) {
    return $form['brands_section']['brands'];
  }

  /**
   * Submit handler for adding a brand.
   */
  public function addBrandCallback(array &$form, FormStateInterface $form_state) {
    $num_brands = $form_state->get('num_brands');
    $form_state->set('num_brands', $num_brands + 1);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for removing a brand.
   */
  public function removeBrandCallback(array &$form, FormStateInterface $form_state) {
    $num_brands = $form_state->get('num_brands');
    if ($num_brands > 1) {
      $form_state->set('num_brands', $num_brands - 1);
    }
    $form_state->setRebuild();
  }

  /**
   * Ajax callback for custom options.
   */
  public function customOptionsAjaxCallback(array &$form, FormStateInterface $form_state) {
    return $form['custom_options_section']['custom_options'];
  }

  /**
   * Submit handler for adding a custom group.
   */
  public function addCustomGroupCallback(array &$form, FormStateInterface $form_state) {
    $num_custom_groups = $form_state->get('num_custom_groups');
    $form_state->set('num_custom_groups', $num_custom_groups + 1);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for removing a custom group.
   */
  public function removeCustomGroupCallback(array &$form, FormStateInterface $form_state) {
    $num_custom_groups = $form_state->get('num_custom_groups');
    if ($num_custom_groups > 0) {
      $form_state->set('num_custom_groups', $num_custom_groups - 1);
    }
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Process colors.
    $colors = [];
    $colors_values = $form_state->getValue('colors') ?: [];
    foreach ($colors_values as $color) {
      if (!empty($color['name'])) {
        $colors[] = [
          'name' => $color['name'],
          'hex' => $color['hex_text'] ?: $color['hex'],
        ];
      }
    }

    // Process sizes.
    $sizes = [];
    $sizes_values = $form_state->getValue('sizes') ?: [];
    foreach ($sizes_values as $size) {
      if (!empty($size['name'])) {
        $sizes[] = [
          'name' => $size['name'],
          'code' => $size['code'] ?: strtoupper(substr($size['name'], 0, 3)),
          'sort_order' => (int) ($size['sort_order'] ?? 0),
        ];
      }
    }
    // Sort by sort_order.
    usort($sizes, fn($a, $b) => $a['sort_order'] <=> $b['sort_order']);

    // Process materials.
    $materials = [];
    $materials_values = $form_state->getValue('materials') ?: [];
    foreach ($materials_values as $material) {
      if (!empty($material['name'])) {
        $materials[] = [
          'name' => $material['name'],
          'description' => $material['description'] ?? '',
        ];
      }
    }

    // Process brands.
    $brands = [];
    $brands_values = $form_state->getValue('brands') ?: [];
    foreach ($brands_values as $brand) {
      if (!empty($brand['name'])) {
        $brands[] = [
          'name' => $brand['name'],
          'website' => $brand['website'] ?? '',
        ];
      }
    }

    // Process custom options groups.
    $custom_options = [];
    $custom_options_values = $form_state->getValue('custom_options') ?: [];
    foreach ($custom_options_values as $group) {
      if (!empty($group['name'])) {
        $options_text = $group['options'] ?? '';
        $options = array_filter(array_map('trim', explode("\n", $options_text)));

        $custom_options[] = [
          'id' => strtolower(preg_replace('/[^a-z0-9]+/i', '_', $group['name'])),
          'name' => $group['name'],
          'type' => $group['type'] ?? 'checkboxes',
          'options' => array_values($options),
        ];
      }
    }

    $this->config('content_commerce.properties')
      ->set('colors', $colors)
      ->set('sizes', $sizes)
      ->set('materials', $materials)
      ->set('brands', $brands)
      ->set('custom_options', $custom_options)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
