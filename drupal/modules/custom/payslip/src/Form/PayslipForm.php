<?php

/**
 * @file
 * Contains Drupal\payslip\Form\PayslipForm.
 *
 * Submitted by Rustum Goden, a dev intern from Caraga State University Cabadbaran Campus.
 */

namespace Drupal\payslip\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Implements a payslip form.
 */
class PayslipForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'payslip_payslip_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['files'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Payslip Files'),
      '#description' => $this->t('Only CSV files are allowed.'),
      '#upload_location' => 'public://payslips/',
      '#multiple' => TRUE,
      '#required' => TRUE,
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
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
    $files = $form_state->getValue('files');

    if (!empty($files)) {
      foreach ($files as $file_id) {
        $file = File::load($file_id);

        if ($file) {
          // Read data from the CSV file.
          $csv_data = $this->readCsvFile($file->getFileUri());

          if (isset($csv_data[27]) && isset($csv_data[28])) {
            $index_27_data = $csv_data[27];
            $index_28_data = $csv_data[28];

            $new_filename = 'Payroll for ' . $index_27_data . ' - ' . $index_28_data . '.csv';

            // Check if the file already exists in the directory.
            $destination_uri = 'public://payslips/' . $new_filename;
            if (file_exists($destination_uri)) {
              // If the file exists, delete it before moving the new file.
              \Drupal::service('file_system')->delete($destination_uri);
            }

            // Move the file to the new URI with the renamed file.
            \Drupal::service('file_system')->move($file->getFileUri(), $destination_uri);
            $file->setFileUri($destination_uri);
            $file->setFilename($new_filename);
            $file->save();

            // Mark the file as permanent after moving.
            $file->setPermanent();
            $file->save();

            // Use the messenger service to display a success message.
            \Drupal::messenger()->addMessage($this->t('Payslip saved successfully.'));
          } else {
            // Use the messenger service to display a warning message.
            \Drupal::messenger()->addWarning($this->t('Unable to read data from the CSV file.'));
          }
        }
      }
    } else {
      // Use the messenger service to display a warning message.
      \Drupal::messenger()->addWarning($this->t('No files were uploaded.'));
    }
  }


  /**
   * Read data from a CSV file.
   *
   * @param string $file_uri
   *   The URI of the CSV file.
   *
   * @return array|bool
   *   An array of data from the CSV file or FALSE on failure.
   */
  private function readCsvFile($file_uri) {
    $file_contents = file_get_contents($file_uri);

    // Check if the file contents were successfully read.
    if ($file_contents !== FALSE) {
      // Parse the CSV data using a comma as the separator.
      $csv_data = str_getcsv($file_contents, "\n", '"', "\\"); 

      // Check if the CSV data is not empty.
      if (!empty($csv_data)) {
        // Extract the second line (index 1) of the CSV file.
        $second_line_data = str_getcsv($csv_data[1], ",", '"', "\\");

        // Check if the second line data is not empty.
        if (!empty($second_line_data)) {
          return $second_line_data;
        }
      }
    }

    return FALSE;
  }


  /**
   * Save form data and file IDs to the database.
   *
   * @param array $file_ids
   *   The array of file IDs.
   */
  private function saveFormData($file_ids) {
    // Save the form data to the database table.
    \Drupal::database()->insert('payslip')
      ->fields([
        'files' => implode(',', $file_ids),
      ])
      ->execute();
  }
}
