<?php

namespace Drupal\constructor\EventSubscriber;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\State\StateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirects to complete setup after installation.
 */
class PostInstallRedirectSubscriber implements EventSubscriberInterface {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new PostInstallRedirectSubscriber.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * Redirects to complete setup if needed.
   */
  public function onRequest(RequestEvent $event) {
    if (!$event->isMainRequest()) {
      return;
    }

    $needs_setup = $this->state->get('constructor.needs_post_install_setup', FALSE);
    if (!$needs_setup) {
      return;
    }

    $request = $event->getRequest();
    $current_path = $request->getPathInfo();

    // Don't redirect if already on the setup page.
    if ($current_path === '/admin/constructor/complete-setup') {
      return;
    }

    // Don't redirect for admin paths to allow troubleshooting.
    if (strpos($current_path, '/admin/') === 0) {
      return;
    }

    // Don't redirect for system paths.
    if (strpos($current_path, '/core/') === 0 || strpos($current_path, '/sites/') === 0) {
      return;
    }

    // Redirect to complete setup.
    $response = new TrustedRedirectResponse('/admin/constructor/complete-setup');
    $event->setResponse($response);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onRequest', 100];
    return $events;
  }

}
