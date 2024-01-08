<?php

/**
 * @file
 * Contains Drupal\custom_payslip\Form\PayslipForm.
 *
 * Submitted by Rustum Goden, a dev intern from Caraga State University Cabadbaran Campus.
 */

namespace Drupal\custom_payslip\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystemInterface;

/**
 * Implements a payslip form.
 */
class PayslipForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_payslip_payslip_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Set the default value of the date field to the current date.
    $current_date = \Drupal::service('date.formatter')->format(time(), 'custom', 'Y-m-d');

    $form['date'] = [
      '#type' => 'date',
      '#title' => $this->t('Date'),
      '#required' => TRUE,
      '#default_value' => $current_date,
    ];

    $form['files'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Payslip Files'),
      '#description' => $this->t('Only PDF files are allowed.'),
      '#upload_location' => 'public://payslips/',
      '#multiple' => TRUE,
      '#required' => TRUE,
      '#upload_validators' => [
        'file_validate_extensions' => ['pdf'],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $date = $form_state->getValue('date');
    $files = $form_state->getValue('files');

    if (!empty($files)) {
      // Save the form data and file IDs to the database table.
      $file_ids = array_filter($files);
      $this->saveFormData($date, $file_ids);

      // Mark the files as permanent after saving.
      foreach ($file_ids as $file_id) {
        $file = File::load($file_id);
        if ($file) {
          // Create subdirectories and move the file into them.
          $filename = pathinfo($file->getFilename(), PATHINFO_FILENAME);
          $subdirectory = 'public://payslips/' . $filename;

          // Append the date to the filename before moving.
          $new_filename = $filename . '_' . str_replace('-', '', $date) . '.' . pathinfo($file->getFilename(), PATHINFO_EXTENSION);

          \Drupal::service('file_system')->prepareDirectory($subdirectory, FileSystemInterface::CREATE_DIRECTORY);

          // Update the file URI with the new filename.
          $new_file_uri = $subdirectory . '/' . $new_filename;
          \Drupal::service('file_system')->move($file->getFileUri(), $new_file_uri);

          // Update the file entity with the new URI.
          $file->setFileUri($new_file_uri);
          $file->save();

          // Mark the file as permanent after moving.
          $file->setPermanent();
          $file->save();
        }
      }

      // Use the messenger service to display a success message.
      \Drupal::messenger()->addMessage($this->t('Payslip saved successfully.'));
    } else {
      // Use the messenger service to display a warning message.
      \Drupal::messenger()->addWarning($this->t('No files were uploaded.'));
    }
  }

  /**
   * Save form data and file IDs to the database.
   *
   * @param string $date
   *   The date value.
   * @param array $file_ids
   *   The array of file IDs.
   */
  private function saveFormData($date, $file_ids) {
    // Save the form data to the database table.
    // You can use the database service or an entity for this purpose.
    // For example, using the database service:
    \Drupal::database()->insert('custom_payslip')
      ->fields([
        'date' => $date,
        'files' => implode(',', $file_ids),
      ])
      ->execute();
  }
}
