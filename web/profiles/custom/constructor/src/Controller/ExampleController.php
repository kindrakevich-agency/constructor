<?php

namespace Drupal\constructor\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for the theme example page.
 */
class ExampleController extends ControllerBase {

  /**
   * Returns the example page displaying all theme blocks.
   *
   * @return array
   *   A render array for the example page.
   */
  public function page() {
    return [
      '#theme' => 'constructor_example_page',
      '#attached' => [
        'library' => [
          'constructor_theme/example-page',
        ],
      ],
    ];
  }

}
