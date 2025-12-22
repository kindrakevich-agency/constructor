<?php

namespace Drupal\constructor\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for completing post-installation setup.
 *
 * Note: Content translation is now handled in the batch process
 * (constructor_translate_content_batch). This controller is kept
 * as a fallback redirect.
 */
class CompleteSetupController extends ControllerBase {

  /**
   * Completes the post-installation setup.
   *
   * Just redirects to home page. Translation is handled in batch.
   */
  public function completeSetup() {
    $state = \Drupal::state();

    // Clean up any leftover state flags from previous versions.
    $state->delete('constructor.needs_post_install_setup');
    $state->delete('constructor.post_install_settings');

    \Drupal::messenger()->addStatus($this->t('Site setup completed.'));

    return new RedirectResponse('/');
  }

}
