<?php

namespace Drupal\constructor\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for the frontpage.
 */
class FrontpageController extends ControllerBase {

  /**
   * Returns the frontpage content.
   *
   * @return array
   *   A render array for the frontpage.
   */
  public function content() {
    // Return empty content - the page template will render header, footer,
    // and any blocks placed in the content region.
    return [
      '#markup' => '',
      '#cache' => [
        'contexts' => ['url.path'],
      ],
    ];
  }

}
