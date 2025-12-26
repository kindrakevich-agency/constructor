<?php

namespace Drupal\constructor_core;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\FileInterface;
use Drupal\image\Entity\ImageStyle;

/**
 * Helper service for generating responsive image data.
 */
class ResponsiveImageHelper {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Constructs a ResponsiveImageHelper object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FileUrlGeneratorInterface $file_url_generator) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * Get responsive image data for a file.
   *
   * @param \Drupal\file\FileInterface|string $file
   *   The file entity or file URI.
   * @param string $responsive_style
   *   The responsive image style ID (e.g., 'product', 'article', 'hero').
   * @param string $alt
   *   The alt text for the image.
   *
   * @return array
   *   Array with 'srcset', 'sizes', 'src', and 'alt' keys.
   */
  public function getResponsiveImageData($file, string $responsive_style, string $alt = ''): array {
    $uri = $file instanceof FileInterface ? $file->getFileUri() : $file;

    if (empty($uri)) {
      return [];
    }

    // Define image styles for each responsive style.
    $style_mappings = $this->getStyleMappings($responsive_style);

    if (empty($style_mappings)) {
      // Fallback to original image.
      return [
        'src' => $this->fileUrlGenerator->generateAbsoluteString($uri),
        'srcset' => '',
        'sizes' => '',
        'alt' => $alt,
      ];
    }

    $srcset_parts = [];
    $fallback_url = '';

    foreach ($style_mappings['styles'] as $style_name => $width) {
      $style = ImageStyle::load($style_name);
      if ($style) {
        $url = $style->buildUrl($uri);
        $srcset_parts[] = $url . ' ' . $width . 'w';

        // Use the middle-sized image as fallback.
        if (empty($fallback_url)) {
          $fallback_url = $url;
        }
      }
    }

    // If no styles worked, use original.
    if (empty($srcset_parts)) {
      return [
        'src' => $this->fileUrlGenerator->generateAbsoluteString($uri),
        'srcset' => '',
        'sizes' => '',
        'alt' => $alt,
      ];
    }

    return [
      'src' => $fallback_url,
      'srcset' => implode(', ', $srcset_parts),
      'sizes' => $style_mappings['sizes'],
      'alt' => $alt,
    ];
  }

  /**
   * Get style mappings for a responsive style.
   *
   * @param string $responsive_style
   *   The responsive image style ID.
   *
   * @return array
   *   Array with 'styles' and 'sizes' keys.
   */
  protected function getStyleMappings(string $responsive_style): array {
    $mappings = [
      'product' => [
        'styles' => [
          'product_card_400x533' => 400,
          'product_card_600x800' => 600,
          'large_1024x1024' => 1024,
        ],
        'sizes' => '(min-width: 1024px) 25vw, (min-width: 768px) 33vw, 50vw',
      ],
      'product_main' => [
        'styles' => [
          'medium_640x640' => 640,
          'large_1024x1024' => 1024,
          'xlarge_1920x1920' => 1920,
        ],
        'sizes' => '(min-width: 1024px) 50vw, 100vw',
      ],
      'article' => [
        'styles' => [
          'article_card_400x300' => 400,
          'article_card_800x600' => 800,
          'article_featured_1200x800' => 1200,
        ],
        'sizes' => '(min-width: 1024px) 50vw, (min-width: 768px) 50vw, 100vw',
      ],
      'hero' => [
        'styles' => [
          'hero_640x360' => 640,
          'hero_1024x576' => 1024,
          'hero_1920x1080' => 1920,
        ],
        'sizes' => '100vw',
      ],
      'gallery' => [
        'styles' => [
          'gallery_thumb_400x400' => 400,
          'medium_640x640' => 640,
          'gallery_full_1200x900' => 1200,
        ],
        'sizes' => '(min-width: 1024px) 25vw, (min-width: 768px) 33vw, 50vw',
      ],
      'team' => [
        'styles' => [
          'team_avatar_200x200' => 200,
          'team_avatar_400x400' => 400,
        ],
        'sizes' => '(min-width: 768px) 200px, 150px',
      ],
      'service' => [
        'styles' => [
          'service_card_400x300' => 400,
          'service_card_800x600' => 800,
        ],
        'sizes' => '(min-width: 1024px) 33vw, (min-width: 768px) 50vw, 100vw',
      ],
      'square' => [
        'styles' => [
          'small_320x320' => 320,
          'medium_640x640' => 640,
          'large_1024x1024' => 1024,
        ],
        'sizes' => '(min-width: 1024px) 33vw, (min-width: 768px) 50vw, 100vw',
      ],
    ];

    return $mappings[$responsive_style] ?? [];
  }

  /**
   * Build a responsive image render array.
   *
   * @param \Drupal\file\FileInterface|string $file
   *   The file entity or file URI.
   * @param string $responsive_style
   *   The responsive image style ID.
   * @param string $alt
   *   The alt text.
   * @param array $attributes
   *   Additional attributes for the img tag.
   *
   * @return array
   *   A render array.
   */
  public function buildResponsiveImage($file, string $responsive_style, string $alt = '', array $attributes = []): array {
    $data = $this->getResponsiveImageData($file, $responsive_style, $alt);

    if (empty($data)) {
      return [];
    }

    $img_attributes = array_merge([
      'src' => $data['src'],
      'alt' => $data['alt'],
      'loading' => 'lazy',
    ], $attributes);

    if (!empty($data['srcset'])) {
      $img_attributes['srcset'] = $data['srcset'];
      $img_attributes['sizes'] = $data['sizes'];
    }

    return [
      '#type' => 'html_tag',
      '#tag' => 'img',
      '#attributes' => $img_attributes,
    ];
  }

}
