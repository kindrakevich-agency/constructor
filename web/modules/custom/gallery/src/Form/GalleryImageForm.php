<?php

namespace Drupal\gallery\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Form for adding gallery images.
 */
class GalleryImageForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gallery_image_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Image'),
      '#description' => $this->t('Upload an image for the gallery. Allowed formats: png, gif, jpg, jpeg, webp.'),
      '#upload_location' => 'public://gallery/',
      '#upload_validators' => [
        'FileExtension' => [
          'extensions' => 'png gif jpg jpeg webp',
        ],
        'FileSizeLimit' => [
          'fileLimit' => 10 * 1024 * 1024,
        ],
      ],
      '#required' => TRUE,
    ];

    $form['alt'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Alt Text'),
      '#description' => $this->t('Describe the image for accessibility.'),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Image'),
      '#button_type' => 'primary',
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => \Drupal\Core\Url::fromRoute('gallery.admin'),
      '#attributes' => [
        'class' => ['button'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $fid = $form_state->getValue('image')[0];
    $alt = $form_state->getValue('alt');

    if ($fid) {
      $file = File::load($fid);
      if ($file) {
        // Make the file permanent.
        $file->setPermanent();
        $file->save();

        // Get file URL.
        $file_url_generator = \Drupal::service('file_url_generator');
        $url = $file_url_generator->generateAbsoluteString($file->getFileUri());

        // Get image dimensions.
        $image_factory = \Drupal::service('image.factory');
        $image = $image_factory->get($file->getFileUri());
        $width = $image->getWidth() ?: 1200;
        $height = $image->getHeight() ?: 900;

        // Add to gallery.
        gallery_add_image([
          'url' => $url,
          'thumb' => $url,
          'alt' => $alt,
          'width' => $width,
          'height' => $height,
          'fid' => $fid,
        ]);

        // Invalidate cache.
        \Drupal::service('cache_tags.invalidator')->invalidateTags(['gallery_images']);

        $this->messenger()->addMessage($this->t('Image has been added to the gallery.'));
      }
    }

    $form_state->setRedirect('gallery.admin');
  }

}
