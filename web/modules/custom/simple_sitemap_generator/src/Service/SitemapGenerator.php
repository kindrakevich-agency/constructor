<?php

namespace Drupal\simple_sitemap_generator\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Service for generating XML sitemaps.
 */
class SitemapGenerator {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The domain negotiator (optional, only available if domain module is installed).
   *
   * @var \Drupal\domain\DomainNegotiatorInterface|null
   */
  protected $domainNegotiator;

  /**
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a SitemapGenerator object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param mixed|null $domain_negotiator
   *   The domain negotiator (optional, can be null if domain module is not installed).
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config_factory,
    Connection $database,
    $domain_negotiator,
    UrlGeneratorInterface $url_generator,
    TimeInterface $time,
    FileUrlGeneratorInterface $file_url_generator,
    LanguageManagerInterface $language_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->database = $database;
    $this->domainNegotiator = $domain_negotiator;
    $this->urlGenerator = $url_generator;
    $this->time = $time;
    $this->fileUrlGenerator = $file_url_generator;
    $this->languageManager = $language_manager;
  }

  /**
   * Gets the configuration.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The configuration object.
   */
  protected function getConfig() {
    return $this->configFactory->get('simple_sitemap_generator.settings');
  }

  /**
   * Checks if domain module is available.
   *
   * @return bool
   *   TRUE if domain module is installed and negotiator is available.
   */
  protected function hasDomainSupport() {
    return $this->domainNegotiator !== NULL && \Drupal::moduleHandler()->moduleExists('domain');
  }

  /**
   * Gets the base URL for a domain.
   *
   * @param string $domain_id
   *   The domain ID.
   *
   * @return string
   *   The base URL (e.g., https://example.com).
   */
  protected function getDomainBaseUrl($domain_id) {
    if ($this->hasDomainSupport() && $domain_id !== 'default') {
      $domain = $this->entityTypeManager->getStorage('domain')->load($domain_id);
      if ($domain) {
        return $domain->getScheme() . $domain->getHostname();
      }
    }
    return \Drupal::request()->getSchemeAndHttpHost();
  }

  /**
   * Gets sitemap for the current domain.
   *
   * @param int|null $page
   *   The page number, or NULL for index.
   *
   * @return string
   *   The XML sitemap content.
   */
  public function getSitemap($page = NULL) {
    $domain_id = 'default';
    if ($this->hasDomainSupport()) {
      $domain = $this->domainNegotiator->getActiveDomain();
      $domain_id = $domain ? $domain->id() : 'default';
    }

    // Check cache.
    $cached = $this->getCache($domain_id, $page ?? 0);
    if ($cached) {
      return $cached;
    }

    // Generate sitemap.
    if ($page === NULL) {
      // Check if we need index or single sitemap.
      $total_urls = $this->getTotalUrls($domain_id);
      $urls_per_sitemap = $this->getConfig()->get('urls_per_sitemap') ?: 5000;

      if ($total_urls > $urls_per_sitemap) {
        $xml = $this->generateSitemapIndex($domain_id, $total_urls, $urls_per_sitemap);
      }
      else {
        $xml = $this->generateSitemap($domain_id, 0);
      }
    }
    else {
      // Page numbers are 1-indexed externally, but generateSitemap uses 0-indexed.
      $xml = $this->generateSitemap($domain_id, $page - 1);
    }

    // Cache the result.
    $this->setCache($domain_id, $page ?? 0, $xml);

    return $xml;
  }

  /**
   * Gets the total number of URLs for a domain (including all translations).
   *
   * @param string $domain_id
   *   The domain ID.
   *
   * @return int
   *   The total number of URLs.
   */
  protected function getTotalUrls($domain_id) {
    $count = 0;

    // Count nodes with all translations using database query.
    $config = $this->getConfig();
    $enabled_types = $config->get('enabled_content_types') ?: [];

    if (!empty($enabled_types)) {
      $query = $this->database->select('node_field_data', 'nfd');
      $query->condition('nfd.status', 1);
      $query->condition('nfd.type', $enabled_types, 'IN');

      // Only join with domain table if domain module is available.
      if ($this->hasDomainSupport() && $domain_id !== 'default') {
        $query->join('node__field_domain_access', 'da', 'nfd.nid = da.entity_id AND nfd.langcode = da.langcode');
        $query->condition('da.field_domain_access_target_id', $domain_id);
      }

      $count += $query->countQuery()->execute()->fetchField();
    }

    // Count custom URLs.
    $custom_query = $this->database->select('simple_sitemap_custom_urls', 'u')
      ->condition('status', 1);

    // Only filter by domain if domain module is available.
    if ($this->hasDomainSupport() && $domain_id !== 'default') {
      $custom_query->condition('domain_id', $domain_id);
    }

    $count += $custom_query->countQuery()->execute()->fetchField();

    return $count;
  }

  /**
   * Generates a sitemap index.
   *
   * @param string $domain_id
   *   The domain ID.
   * @param int $total_urls
   *   Total number of URLs.
   * @param int $urls_per_sitemap
   *   URLs per sitemap.
   *
   * @return string
   *   The sitemap index XML.
   */
  protected function generateSitemapIndex($domain_id, $total_urls, $urls_per_sitemap) {
    $base_url = $this->getDomainBaseUrl($domain_id);

    $pages = ceil($total_urls / $urls_per_sitemap);

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    for ($i = 1; $i <= $pages; $i++) {
      $xml .= '  <sitemap>' . "\n";
      $xml .= '    <loc>' . htmlspecialchars($base_url . '/sitemap.xml?page=' . $i) . '</loc>' . "\n";
      $xml .= '    <lastmod>' . date('c', $this->time->getRequestTime()) . '</lastmod>' . "\n";
      $xml .= '  </sitemap>' . "\n";
    }

    $xml .= '</sitemapindex>';

    return $xml;
  }

  /**
   * Generates a sitemap page.
   *
   * @param string $domain_id
   *   The domain ID.
   * @param int $page
   *   The page number (0-indexed).
   *
   * @return string
   *   The sitemap XML.
   */
  protected function generateSitemap($domain_id, $page = 0) {
    $config = $this->getConfig();
    $urls_per_sitemap = $config->get('urls_per_sitemap') ?: 5000;
    $include_images = $config->get('include_images') ?? TRUE;
    $include_lastmod = $config->get('include_lastmod') ?? TRUE;
    $default_priority = $config->get('default_priority') ?: '0.5';
    $default_changefreq = $config->get('default_changefreq') ?: 'weekly';

    $base_url = $this->getDomainBaseUrl($domain_id);

    $offset = $page * $urls_per_sitemap;
    $urls = [];

    // Get nodes with all translations.
    $enabled_types = $config->get('enabled_content_types') ?: [];
    $languages = $this->languageManager->getLanguages();

    if (!empty($enabled_types)) {
      // Query all translations for nodes.
      $query = $this->database->select('node_field_data', 'nfd');
      $query->fields('nfd', ['nid', 'langcode', 'changed', 'title']);
      $query->condition('nfd.status', 1);
      $query->condition('nfd.type', $enabled_types, 'IN');

      // Only filter by domain if domain module is available.
      if ($this->hasDomainSupport() && $domain_id !== 'default') {
        $query->join('node__field_domain_access', 'da', 'nfd.nid = da.entity_id AND nfd.langcode = da.langcode');
        $query->condition('da.field_domain_access_target_id', $domain_id);
      }

      $query->orderBy('nfd.changed', 'DESC');
      $query->range($offset, $urls_per_sitemap);

      $results = $query->execute()->fetchAll();

      if (!empty($results)) {
        // Pre-fetch all translation info for these nodes to avoid loading each node.
        $nids = array_unique(array_column($results, 'nid'));
        $translations_map = $this->getTranslationsMap($nids);

        foreach ($results as $row) {
          // Build URL path based on language.
          $url_path = $this->buildNodeUrl($row->nid, $row->langcode, $languages);

          $url_data = [
            'loc' => $base_url . $url_path,
            'priority' => $default_priority,
            'changefreq' => $default_changefreq,
          ];

          if ($include_lastmod) {
            $url_data['lastmod'] = date('c', $row->changed);
          }

          // Add hreflang for all available translations (from pre-fetched data).
          if (!empty($translations_map[$row->nid])) {
            $url_data['hreflang'] = [];
            foreach ($translations_map[$row->nid] as $langcode => $trans_path) {
              $url_data['hreflang'][$langcode] = $base_url . $trans_path;
            }
          }

          // Add images if enabled (only load node if images are needed).
          if ($include_images) {
            $node = $this->entityTypeManager->getStorage('node')->load($row->nid);
            if ($node && $node->hasTranslation($row->langcode)) {
              $node = $node->getTranslation($row->langcode);
              $images = $this->getNodeImages($node, $base_url);
              if (!empty($images)) {
                $url_data['images'] = $images;
              }
            }
            // Clear node from memory.
            unset($node);
          }

          $urls[] = $url_data;
        }
      }
    }

    // Get custom URLs.
    $custom_query = $this->database->select('simple_sitemap_custom_urls', 'u')
      ->fields('u')
      ->condition('status', 1)
      ->orderBy('priority', 'DESC');

    // Only filter by domain if domain module is available.
    if ($this->hasDomainSupport() && $domain_id !== 'default') {
      $custom_query->condition('domain_id', $domain_id);
    }

    // Adjust range based on how many nodes we got.
    $remaining = $urls_per_sitemap - count($urls);
    if ($remaining > 0) {
      $custom_offset = max(0, $offset - $this->getNodeCount($domain_id, $enabled_types));
      $custom_query->range($custom_offset, $remaining);

      $custom_urls = $custom_query->execute()->fetchAll();

      foreach ($custom_urls as $custom) {
        $loc = $custom->url;
        // If relative path, prepend base URL.
        if (strpos($loc, '/') === 0) {
          $loc = $base_url . $loc;
        }

        $urls[] = [
          'loc' => $loc,
          'priority' => $custom->priority,
          'changefreq' => $custom->changefreq,
        ];
      }
    }

    return $this->buildSitemapXml($urls, $include_images);
  }

  /**
   * Gets translations map for given node IDs.
   *
   * @param array $nids
   *   Array of node IDs.
   *
   * @return array
   *   Map of nid => [langcode => url_path].
   */
  protected function getTranslationsMap(array $nids) {
    if (empty($nids)) {
      return [];
    }

    $map = [];
    $languages = $this->languageManager->getLanguages();
    $default_langcode = $this->languageManager->getDefaultLanguage()->getId();

    // Query all translations for these nodes.
    $query = $this->database->select('node_field_data', 'nfd');
    $query->leftJoin('path_alias', 'pa', "pa.path = CONCAT('/node/', nfd.nid) AND pa.langcode = nfd.langcode");
    $query->fields('nfd', ['nid', 'langcode']);
    $query->addField('pa', 'alias');
    $query->condition('nfd.nid', $nids, 'IN');
    $query->condition('nfd.status', 1);

    $results = $query->execute()->fetchAll();

    foreach ($results as $row) {
      $path = $row->alias ?: '/node/' . $row->nid;
      // Add language prefix for non-default languages.
      if ($row->langcode !== $default_langcode && isset($languages[$row->langcode])) {
        $path = '/' . $row->langcode . $path;
      }
      $map[$row->nid][$row->langcode] = $path;
    }

    return $map;
  }

  /**
   * Builds URL path for a node in a specific language.
   *
   * @param int $nid
   *   Node ID.
   * @param string $langcode
   *   Language code.
   * @param array $languages
   *   Available languages.
   *
   * @return string
   *   URL path.
   */
  protected function buildNodeUrl($nid, $langcode, array $languages) {
    $default_langcode = $this->languageManager->getDefaultLanguage()->getId();

    // Try to get alias.
    $alias = $this->database->select('path_alias', 'pa')
      ->fields('pa', ['alias'])
      ->condition('pa.path', '/node/' . $nid)
      ->condition('pa.langcode', $langcode)
      ->execute()
      ->fetchField();

    $path = $alias ?: '/node/' . $nid;

    // Add language prefix for non-default languages.
    if ($langcode !== $default_langcode && isset($languages[$langcode])) {
      $path = '/' . $langcode . $path;
    }

    return $path;
  }

  /**
   * Gets node count for domain (including all translations).
   *
   * @param string $domain_id
   *   The domain ID.
   * @param array $enabled_types
   *   Enabled content types.
   *
   * @return int
   *   The node count.
   */
  protected function getNodeCount($domain_id, array $enabled_types) {
    if (empty($enabled_types)) {
      return 0;
    }

    $query = $this->database->select('node_field_data', 'nfd');
    $query->condition('nfd.status', 1);
    $query->condition('nfd.type', $enabled_types, 'IN');

    // Only filter by domain if domain module is available.
    if ($this->hasDomainSupport() && $domain_id !== 'default') {
      $query->join('node__field_domain_access', 'da', 'nfd.nid = da.entity_id AND nfd.langcode = da.langcode');
      $query->condition('da.field_domain_access_target_id', $domain_id);
    }

    return $query->countQuery()->execute()->fetchField();
  }

  /**
   * Gets images from a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   * @param string $base_url
   *   The base URL for the domain.
   *
   * @return array
   *   Array of image URLs.
   */
  protected function getNodeImages($node, $base_url) {
    $images = [];

    // Check common image field names.
    $image_fields = ['field_image', 'field_images', 'field_media_image'];

    foreach ($image_fields as $field_name) {
      if ($node->hasField($field_name) && !$node->get($field_name)->isEmpty()) {
        $field = $node->get($field_name);

        foreach ($field as $item) {
          if ($item->entity) {
            // Handle media entities.
            if ($item->entity->getEntityTypeId() === 'media') {
              $media = $item->entity;
              if ($media->hasField('field_media_image') && !$media->get('field_media_image')->isEmpty()) {
                $file = $media->get('field_media_image')->entity;
                if ($file) {
                  $images[] = [
                    'loc' => $this->buildImageUrl($file->getFileUri(), $base_url),
                    'title' => $node->getTitle(),
                  ];
                }
              }
            }
            // Handle file entities directly.
            elseif ($item->entity->getEntityTypeId() === 'file') {
              $images[] = [
                'loc' => $this->buildImageUrl($item->entity->getFileUri(), $base_url),
                'title' => $node->getTitle(),
              ];
            }
          }
        }
      }
    }

    return $images;
  }

  /**
   * Builds an absolute image URL using the domain base URL.
   *
   * @param string $uri
   *   The file URI (e.g., public://image.jpg).
   * @param string $base_url
   *   The domain base URL.
   *
   * @return string
   *   The absolute image URL.
   */
  protected function buildImageUrl($uri, $base_url) {
    // Get the relative path from the URI.
    $relative_url = $this->fileUrlGenerator->generateString($uri);

    // If it starts with /, prepend the base URL.
    if (strpos($relative_url, '/') === 0) {
      return $base_url . $relative_url;
    }

    // If it's already absolute but uses wrong host, rebuild it.
    if (preg_match('#^https?://[^/]+(.*)$#', $relative_url, $matches)) {
      return $base_url . $matches[1];
    }

    return $base_url . '/' . $relative_url;
  }

  /**
   * Builds the sitemap XML.
   *
   * @param array $urls
   *   Array of URL data.
   * @param bool $include_images
   *   Whether to include image namespace.
   *
   * @return string
   *   The sitemap XML.
   */
  protected function buildSitemapXml(array $urls, $include_images = TRUE) {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
    $xml .= ' xmlns:xhtml="http://www.w3.org/1999/xhtml"';
    if ($include_images) {
      $xml .= ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"';
    }
    $xml .= '>' . "\n";

    foreach ($urls as $url) {
      $xml .= '  <url>' . "\n";
      $xml .= '    <loc>' . htmlspecialchars($url['loc']) . '</loc>' . "\n";

      // Add hreflang tags for multilingual support.
      if (!empty($url['hreflang'])) {
        foreach ($url['hreflang'] as $langcode => $href) {
          $xml .= '    <xhtml:link rel="alternate" hreflang="' . $langcode . '" href="' . htmlspecialchars($href) . '" />' . "\n";
        }
      }

      if (!empty($url['lastmod'])) {
        $xml .= '    <lastmod>' . $url['lastmod'] . '</lastmod>' . "\n";
      }

      if (!empty($url['changefreq'])) {
        $xml .= '    <changefreq>' . $url['changefreq'] . '</changefreq>' . "\n";
      }

      if (!empty($url['priority'])) {
        $xml .= '    <priority>' . $url['priority'] . '</priority>' . "\n";
      }

      // Add images.
      if (!empty($url['images']) && $include_images) {
        foreach ($url['images'] as $image) {
          $xml .= '    <image:image>' . "\n";
          $xml .= '      <image:loc>' . htmlspecialchars($image['loc']) . '</image:loc>' . "\n";
          if (!empty($image['title'])) {
            $xml .= '      <image:title>' . htmlspecialchars($image['title']) . '</image:title>' . "\n";
          }
          $xml .= '    </image:image>' . "\n";
        }
      }

      $xml .= '  </url>' . "\n";
    }

    $xml .= '</urlset>';

    return $xml;
  }

  /**
   * Gets cached sitemap.
   *
   * @param string $domain_id
   *   The domain ID.
   * @param int $page
   *   The page number.
   *
   * @return string|null
   *   The cached sitemap or NULL.
   */
  protected function getCache($domain_id, $page) {
    $cache_lifetime = $this->getConfig()->get('cache_lifetime') ?: 86400;

    if ($cache_lifetime == 0) {
      return NULL;
    }

    $result = $this->database->select('simple_sitemap_cache', 'c')
      ->fields('c', ['data', 'created'])
      ->condition('domain_id', $domain_id)
      ->condition('page', $page)
      ->execute()
      ->fetchObject();

    if ($result && ($this->time->getRequestTime() - $result->created) < $cache_lifetime) {
      return $result->data;
    }

    return NULL;
  }

  /**
   * Sets cached sitemap.
   *
   * @param string $domain_id
   *   The domain ID.
   * @param int $page
   *   The page number.
   * @param string $data
   *   The sitemap XML.
   */
  protected function setCache($domain_id, $page, $data) {
    $this->database->merge('simple_sitemap_cache')
      ->keys([
        'domain_id' => $domain_id,
        'page' => $page,
      ])
      ->fields([
        'data' => $data,
        'created' => $this->time->getRequestTime(),
      ])
      ->execute();
  }

  /**
   * Clears cache for a domain or all domains.
   *
   * @param string|null $domain_id
   *   The domain ID, or NULL to clear all.
   */
  public function clearCache($domain_id = NULL) {
    $query = $this->database->delete('simple_sitemap_cache');

    if ($domain_id) {
      $query->condition('domain_id', $domain_id);
    }

    $query->execute();
  }

  /**
   * Regenerates sitemaps for all domains (or single default sitemap if no domain module).
   *
   * @return array
   *   Array of results per domain.
   */
  public function regenerateAll() {
    $results = [];

    // Clear all cache.
    $this->clearCache();

    // Get all domains or use default if domain module is not available.
    if ($this->hasDomainSupport()) {
      $domains = $this->entityTypeManager->getStorage('domain')->loadMultipleSorted();
    }
    else {
      // Create a pseudo-domain for single-site mode.
      $domains = [
        'default' => (object) [
          'id' => 'default',
          'label' => 'Default',
        ],
      ];
    }

    foreach ($domains as $domain) {
      $domain_id = is_object($domain) && method_exists($domain, 'id') ? $domain->id() : $domain->id;
      $domain_label = is_object($domain) && method_exists($domain, 'label') ? $domain->label() : $domain->label;
      $total_urls = $this->getTotalUrls($domain_id);

      $results[$domain_id] = [
        'label' => $domain_label,
        'urls' => $total_urls,
      ];

      // Pre-generate sitemap.
      $urls_per_sitemap = $this->getConfig()->get('urls_per_sitemap') ?: 5000;

      if ($total_urls > $urls_per_sitemap) {
        // Generate index.
        $xml = $this->generateSitemapIndex($domain_id, $total_urls, $urls_per_sitemap);
        $this->setCache($domain_id, 0, $xml);

        // Generate each page.
        $pages = ceil($total_urls / $urls_per_sitemap);
        for ($i = 0; $i < $pages; $i++) {
          $page_xml = $this->generateSitemap($domain_id, $i);
          $this->setCache($domain_id, $i + 1, $page_xml);
        }

        $results[$domain_id]['pages'] = $pages;
      }
      else {
        $xml = $this->generateSitemap($domain_id, 0);
        $this->setCache($domain_id, 0, $xml);
        $results[$domain_id]['pages'] = 1;
      }
    }

    return $results;
  }

}
