<?php

namespace Drupal\constructor_core\TwigExtension;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension for language-aware URLs.
 */
class UrlExtension extends AbstractExtension {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a UrlExtension object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'constructor_core_url';
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new TwigFunction('lang_path', [$this, 'getLangPath']),
      new TwigFunction('lang_url', [$this, 'getLangUrl']),
    ];
  }

  /**
   * Get language-aware path for internal route.
   *
   * @param string $path
   *   The internal path (e.g., '/articles', '/contact').
   *
   * @return string
   *   The language-prefixed path.
   */
  public function getLangPath(string $path): string {
    try {
      // Ensure path starts with /.
      if (strpos($path, '/') !== 0) {
        $path = '/' . $path;
      }

      // Create URL from user input (internal path).
      $url = Url::fromUserInput($path);

      // Set the language to current language.
      $current_language = $this->languageManager->getCurrentLanguage();
      $url->setOption('language', $current_language);

      // Return the string representation.
      return $url->toString();
    }
    catch (\Exception $e) {
      // Fallback to original path if URL generation fails.
      return $path;
    }
  }

  /**
   * Get language-aware absolute URL for internal route.
   *
   * @param string $path
   *   The internal path (e.g., '/articles', '/contact').
   *
   * @return string
   *   The full language-prefixed URL.
   */
  public function getLangUrl(string $path): string {
    try {
      // Ensure path starts with /.
      if (strpos($path, '/') !== 0) {
        $path = '/' . $path;
      }

      // Create URL from user input (internal path).
      $url = Url::fromUserInput($path);

      // Set the language to current language and make absolute.
      $current_language = $this->languageManager->getCurrentLanguage();
      $url->setOption('language', $current_language);
      $url->setAbsolute(TRUE);

      // Return the string representation.
      return $url->toString();
    }
    catch (\Exception $e) {
      // Fallback to original path if URL generation fails.
      return $path;
    }
  }

}
