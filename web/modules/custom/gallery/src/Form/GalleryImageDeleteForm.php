<?php

namespace Drupal\gallery\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form for deleting gallery images.
 */
class GalleryImageDeleteForm extends ConfirmFormBase {

  /**
   * The image ID to delete.
   *
   * @var string
   */
  protected $imageId;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gallery_image_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this image?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('gallery.admin');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    $this->imageId = $id;

    // Find the image to show preview.
    $images = gallery_get_images();
    foreach ($images as $image) {
      if ($image['id'] === $id) {
        $thumb_url = $image['thumb'] ?? $image['url'] ?? '';
        if (!empty($thumb_url)) {
          $form['preview'] = [
            '#markup' => '<div style="margin-bottom: 20px;"><img src="' . $thumb_url . '" alt="' . ($image['alt'] ?? '') . '" style="max-width: 200px; max-height: 200px; border-radius: 8px;"></div>',
          ];
        }
        break;
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (gallery_delete_image($this->imageId)) {
      // Invalidate cache.
      \Drupal::service('cache_tags.invalidator')->invalidateTags(['gallery_images']);
      $this->messenger()->addMessage($this->t('Image has been deleted.'));
    }
    else {
      $this->messenger()->addError($this->t('Failed to delete image.'));
    }

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
