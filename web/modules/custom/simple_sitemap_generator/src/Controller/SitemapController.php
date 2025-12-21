<?php

namespace Drupal\simple_sitemap_generator\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\simple_sitemap_generator\Service\SitemapGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Url;

/**
 * Controller for sitemap output.
 */
class SitemapController extends ControllerBase {

  /**
   * The sitemap generator service.
   *
   * @var \Drupal\simple_sitemap_generator\Service\SitemapGenerator
   */
  protected $sitemapGenerator;

  /**
   * Constructs a SitemapController object.
   *
   * @param \Drupal\simple_sitemap_generator\Service\SitemapGenerator $sitemap_generator
   *   The sitemap generator service.
   */
  public function __construct(SitemapGenerator $sitemap_generator) {
    $this->sitemapGenerator = $sitemap_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_sitemap_generator.generator')
    );
  }

  /**
   * Outputs the sitemap XML.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The sitemap response.
   */
  public function sitemap(Request $request) {
    $page = $request->query->get('page');

    // Convert page to integer if provided, NULL for index.
    $page = $page !== NULL ? (int) $page : NULL;

    $xml = $this->sitemapGenerator->getSitemap($page);

    $response = new Response($xml, 200, [
      'Content-Type' => 'application/xml; charset=utf-8',
      'X-Robots-Tag' => 'noindex, follow',
    ]);

    // Add cache headers.
    $config = $this->config('simple_sitemap_generator.settings');
    $cache_lifetime = $config->get('cache_lifetime') ?: 86400;

    if ($cache_lifetime > 0) {
      $response->setMaxAge($cache_lifetime);
      $response->setPublic();
    }

    return $response;
  }

  /**
   * Regenerates all sitemaps.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response.
   */
  public function regenerate() {
    $results = $this->sitemapGenerator->regenerateAll();

    $count = 0;
    $total_urls = 0;

    foreach ($results as $result) {
      $count++;
      $total_urls += $result['urls'];
    }

    $this->messenger()->addStatus($this->t('Regenerated sitemaps for @count domains with @urls total URLs.', [
      '@count' => $count,
      '@urls' => $total_urls,
    ]));

    return $this->redirect('simple_sitemap_generator.settings');
  }

}
