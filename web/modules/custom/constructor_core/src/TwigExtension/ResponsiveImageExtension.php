<?php

namespace Drupal\constructor_core\TwigExtension;

use Drupal\constructor_core\ResponsiveImageHelper;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\Entity\File;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Twig extension for responsive images.
 */
class ResponsiveImageExtension extends AbstractExtension {

  /**
   * The responsive image helper.
   *
   * @var \Drupal\constructor_core\ResponsiveImageHelper
   */
  protected $responsiveImageHelper;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Constructs a ResponsiveImageExtension object.
   */
  public function __construct(ResponsiveImageHelper $responsive_image_helper, FileUrlGeneratorInterface $file_url_generator) {
    $this->responsiveImageHelper = $responsive_image_helper;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'constructor_core_responsive_image';
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new TwigFilter('responsive_image_srcset', [$this, 'getResponsiveImageSrcset']),
      new TwigFilter('responsive_image_sizes', [$this, 'getResponsiveImageSizes']),
      new TwigFilter('image_style_url', [$this, 'getImageStyleUrl']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new TwigFunction('responsive_image', [$this, 'renderResponsiveImage'], ['is_safe' => ['html']]),
      new TwigFunction('responsive_image_data', [$this, 'getResponsiveImageData']),
    ];
  }

  /**
   * Get responsive image srcset string.
   *
   * @param string|int $file
   *   File URI, URL, or file ID.
   * @param string $style
   *   The responsive image style ID.
   *
   * @return string
   *   The srcset string.
   */
  public function getResponsiveImageSrcset($file, string $style): string {
    $uri = $this->resolveFileUri($file);
    if (empty($uri)) {
      return '';
    }

    $data = $this->responsiveImageHelper->getResponsiveImageData($uri, $style);
    return $data['srcset'] ?? '';
  }

  /**
   * Get responsive image sizes string.
   *
   * @param string $style
   *   The responsive image style ID.
   *
   * @return string
   *   The sizes string.
   */
  public function getResponsiveImageSizes(string $style): string {
    // Just return from data helper.
    $data = $this->responsiveImageHelper->getResponsiveImageData('public://placeholder.jpg', $style);
    return $data['sizes'] ?? '';
  }

  /**
   * Get image style URL.
   *
   * @param string|int $file
   *   File URI, URL, or file ID.
   * @param string $style_name
   *   The image style name.
   *
   * @return string
   *   The styled image URL.
   */
  public function getImageStyleUrl($file, string $style_name): string {
    $uri = $this->resolveFileUri($file);
    if (empty($uri)) {
      return '';
    }

    $style = \Drupal\image\Entity\ImageStyle::load($style_name);
    if ($style) {
      return $style->buildUrl($uri);
    }

    return $this->fileUrlGenerator->generateAbsoluteString($uri);
  }

  /**
   * Render a responsive image HTML tag.
   *
   * @param string|int $file
   *   File URI, URL, or file ID.
   * @param string $style
   *   The responsive image style ID.
   * @param string $alt
   *   The alt text.
   * @param string $class
   *   Additional CSS classes.
   *
   * @return string
   *   The HTML img tag.
   */
  public function renderResponsiveImage($file, string $style, string $alt = '', string $class = ''): string {
    $uri = $this->resolveFileUri($file);
    if (empty($uri)) {
      return '';
    }

    $data = $this->responsiveImageHelper->getResponsiveImageData($uri, $style, $alt);

    if (empty($data)) {
      return '';
    }

    $attrs = [
      'src="' . htmlspecialchars($data['src']) . '"',
      'alt="' . htmlspecialchars($data['alt']) . '"',
      'loading="lazy"',
    ];

    if (!empty($data['srcset'])) {
      $attrs[] = 'srcset="' . htmlspecialchars($data['srcset']) . '"';
      $attrs[] = 'sizes="' . htmlspecialchars($data['sizes']) . '"';
    }

    if (!empty($class)) {
      $attrs[] = 'class="' . htmlspecialchars($class) . '"';
    }

    return '<img ' . implode(' ', $attrs) . '>';
  }

  /**
   * Get responsive image data as array.
   *
   * @param string|int $file
   *   File URI, URL, or file ID.
   * @param string $style
   *   The responsive image style ID.
   * @param string $alt
   *   The alt text.
   *
   * @return array
   *   Array with src, srcset, sizes, and alt.
   */
  public function getResponsiveImageData($file, string $style, string $alt = ''): array {
    $uri = $this->resolveFileUri($file);
    if (empty($uri)) {
      return [];
    }

    return $this->responsiveImageHelper->getResponsiveImageData($uri, $style, $alt);
  }

  /**
   * Resolve file to URI.
   *
   * @param mixed $file
   *   File entity, file ID, URI, or URL.
   *
   * @return string|null
   *   The file URI or NULL.
   */
  protected function resolveFileUri($file): ?string {
    if (empty($file)) {
      return NULL;
    }

    // If it's a file entity.
    if ($file instanceof \Drupal\file\FileInterface) {
      return $file->getFileUri();
    }

    // If it's a numeric file ID.
    if (is_numeric($file)) {
      $file_entity = File::load($file);
      if ($file_entity) {
        return $file_entity->getFileUri();
      }
      return NULL;
    }

    // If it's a string.
    if (is_string($file)) {
      // If it starts with public:// or private://, it's a URI.
      if (preg_match('/^(public|private|temporary):\/\//', $file)) {
        return $file;
      }

      // If it's a URL, try to convert to URI.
      if (strpos($file, '/sites/default/files/') !== FALSE) {
        // Extract the path after /sites/default/files/.
        preg_match('/\/sites\/default\/files\/(.+)$/', $file, $matches);
        if (!empty($matches[1])) {
          return 'public://' . urldecode($matches[1]);
        }
      }

      // Return as-is if we can't determine the type.
      return $file;
    }

    return NULL;
  }

}
