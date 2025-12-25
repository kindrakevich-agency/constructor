<?php

namespace Drupal\gallery\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for gallery page.
 */
class GalleryController extends ControllerBase {

  /**
   * Gallery page.
   */
  public function page() {
    $images = gallery_get_images();

    return [
      '#theme' => 'gallery_page',
      '#title' => $this->t('Gallery'),
      '#description' => $this->t('Explore our collection of images.'),
      '#images' => $images,
      '#attached' => [
        'library' => [
          'gallery/gallery',
        ],
      ],
      '#cache' => [
        'tags' => ['gallery_images'],
      ],
    ];
  }

}
