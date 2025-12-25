<?php

namespace Drupal\gallery\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Controller for gallery admin pages.
 */
class GalleryAdminController extends ControllerBase {

  /**
   * List all gallery images.
   */
  public function list() {
    $images = gallery_get_images();

    $header = [
      $this->t('Image'),
      $this->t('Alt Text'),
      $this->t('Date Added'),
      $this->t('Operations'),
    ];

    $rows = [];
    foreach ($images as $image) {
      $thumb_url = $image['thumb'] ?? $image['url'] ?? '';
      $image_markup = '';
      if (!empty($thumb_url)) {
        $image_markup = '<img src="' . $thumb_url . '" alt="' . ($image['alt'] ?? '') . '" style="max-width: 100px; max-height: 100px; border-radius: 8px;">';
      }

      $date = !empty($image['created']) ? date('Y-m-d H:i', $image['created']) : '-';

      $operations = [
        '#type' => 'operations',
        '#links' => [
          'delete' => [
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute('gallery.admin.delete', ['id' => $image['id']]),
          ],
        ],
      ];

      $rows[] = [
        ['data' => ['#markup' => $image_markup]],
        $image['alt'] ?? '-',
        $date,
        ['data' => $operations],
      ];
    }

    $build = [];

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No gallery images found. <a href="@url">Add an image</a>.', [
        '@url' => Url::fromRoute('gallery.admin.add')->toString(),
      ]),
      '#attributes' => [
        'class' => ['gallery-admin-table'],
      ],
    ];

    $build['#attached']['html_head'][] = [
      [
        '#type' => 'html_tag',
        '#tag' => 'style',
        '#value' => '
          .gallery-admin-table img {
            display: block;
          }
          .gallery-admin-table td {
            vertical-align: middle;
          }
        ',
      ],
      'gallery_admin_styles',
    ];

    return $build;
  }

}
