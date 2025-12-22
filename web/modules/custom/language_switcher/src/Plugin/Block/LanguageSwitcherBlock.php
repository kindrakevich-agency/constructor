<?php

namespace Drupal\language_switcher\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Language Switcher Block.
 *
 * @Block(
 *   id = "language_switcher_block",
 *   admin_label = @Translation("Language Switcher"),
 *   category = @Translation("Language")
 * )
 */
class LanguageSwitcherBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new LanguageSwitcherBlock instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $languages = $this->languageManager->getLanguages();
    $current_language = $this->languageManager->getCurrentLanguage();

    // Only show if more than one language is enabled.
    if (count($languages) <= 1) {
      return [];
    }

    // Language metadata with flags and native names.
    $language_data = $this->getLanguageData();

    $language_links = [];
    foreach ($languages as $langcode => $language) {
      $url = Url::fromRoute('<current>', [], ['language' => $language]);

      $language_links[$langcode] = [
        'langcode' => $langcode,
        'name' => $language->getName(),
        'native_name' => $language_data[$langcode]['native_name'] ?? $language->getName(),
        'flag' => $language_data[$langcode]['flag'] ?? '',
        'country' => $language_data[$langcode]['country'] ?? '',
        'url' => $url->toString(),
        'is_current' => ($langcode === $current_language->getId()),
      ];
    }

    return [
      '#theme' => 'language_switcher_block',
      '#languages' => $language_links,
      '#current_language' => [
        'langcode' => $current_language->getId(),
        'name' => $current_language->getName(),
        'native_name' => $language_data[$current_language->getId()]['native_name'] ?? $current_language->getName(),
        'flag' => $language_data[$current_language->getId()]['flag'] ?? '',
      ],
      '#attached' => [
        'library' => [
          'language_switcher/language-switcher',
        ],
        'drupalSettings' => [
          'languageSwitcher' => [
            'languages' => $language_links,
            'currentLanguage' => $current_language->getId(),
          ],
        ],
      ],
      '#cache' => [
        'contexts' => ['languages:language_interface', 'url.path'],
      ],
    ];
  }

  /**
   * Get language metadata (flags, native names, countries).
   */
  protected function getLanguageData() {
    return [
      'en' => [
        'native_name' => 'English',
        'flag' => '🇺🇸',
        'country' => 'United States',
      ],
      'uk' => [
        'native_name' => 'Українська',
        'flag' => '🇺🇦',
        'country' => 'Ukraine',
      ],
      'de' => [
        'native_name' => 'Deutsch',
        'flag' => '🇩🇪',
        'country' => 'Germany',
      ],
      'fr' => [
        'native_name' => 'Français',
        'flag' => '🇫🇷',
        'country' => 'France',
      ],
      'es' => [
        'native_name' => 'Español',
        'flag' => '🇪🇸',
        'country' => 'Spain',
      ],
      'it' => [
        'native_name' => 'Italiano',
        'flag' => '🇮🇹',
        'country' => 'Italy',
      ],
      'pl' => [
        'native_name' => 'Polski',
        'flag' => '🇵🇱',
        'country' => 'Poland',
      ],
      'pt' => [
        'native_name' => 'Português',
        'flag' => '🇵🇹',
        'country' => 'Portugal',
      ],
      'nl' => [
        'native_name' => 'Nederlands',
        'flag' => '🇳🇱',
        'country' => 'Netherlands',
      ],
      'ru' => [
        'native_name' => 'Русский',
        'flag' => '🇷🇺',
        'country' => 'Russia',
      ],
      'ja' => [
        'native_name' => '日本語',
        'flag' => '🇯🇵',
        'country' => 'Japan',
      ],
      'zh' => [
        'native_name' => '中文',
        'flag' => '🇨🇳',
        'country' => 'China',
      ],
      'ko' => [
        'native_name' => '한국어',
        'flag' => '🇰🇷',
        'country' => 'South Korea',
      ],
      'ar' => [
        'native_name' => 'العربية',
        'flag' => '🇸🇦',
        'country' => 'Saudi Arabia',
      ],
    ];
  }

}
