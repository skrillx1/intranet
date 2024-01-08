<?php

/**
 * @file
 * contains \Drupal\my_leaves\Form\EditLeaveForm
 * 
 * Submitted by Rustum Goden, a dev intern from Caraga State University Cabadbaran Campus.
 */

namespace Drupal\my_leaves\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EditLeaveForm extends FormBase {

  protected $database;

  // Constructor to inject the database service.
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  // Factory method to create an instance of the form with dependency injection.
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  public function getFormId() {
    return 'edit_leave_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    // Fetch leave data based on the provided $id.
    $leave_data = $this->fetchLeaveData($id);

    // Add form elements to edit leave data.
    $form['#attributes']['enctype'] = 'multipart/form-data';

    $form['date_filed'] = [
      '#type' => 'date',
      '#title' => $this->t('Date Filed'),
      '#default_value' => isset($leave_data->date_filed) ? $leave_data->date_filed : date('Y-m-d'),
      '#required' => TRUE,
      '#disabled' => TRUE,
    ];

    $form['start_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Start Date'),
      '#default_value' => isset($leave_data->start_date) ? $leave_data->start_date : '',
      '#required' => TRUE,
    ];

    $form['end_date'] = [
      '#type' => 'date',
      '#title' => $this->t('End Date'),
      '#default_value' => isset($leave_data->end_date) ? $leave_data->end_date : '',
      '#required' => TRUE,
    ];

    $form['leave_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Leave Type'),
      '#options' => [
        'Paid Vacation Leave' => $this->t('Paid Vacation Leave'),
        'Unpaid Vacation Leave' => $this->t('Unpaid Vacation Leave'),
        'Paid Sick Leave' => $this->t('Paid Sick Leave'),
        'Unpaid Sick Leave' => $this->t('Unpaid Sick Leave'),
        'Other' => $this->t('Other'),
      ],
      '#default_value' => isset($leave_data->leave_type) ? $leave_data->leave_type : '',
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::toggleOtherField',
        'wrapper' => 'other-field-wrapper',
      ],
    ];

    $form['other_field_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'other-field-wrapper'],
    ];

    $form['other_field_wrapper']['other'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Other'),
      '#default_value' => isset($leave_data->other) ? $leave_data->other : '',
      '#states' => [
        'visible' => [
          ':input[name="leave_type"]' => ['value' => 'other'],
        ],
      ],
    ];

    $form['supporting_documents'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Supporting Documents'),
      '#description' => $this->t('Upload any supporting documents.'),
      '#upload_location' => 'public://supporting_documents',
      '#upload_validators' => [
        'file_validate_extensions' => ['pdf doc docx'], 
      ],
      '#default_value' => isset($leave_data->supporting_documents) ? [$leave_data->supporting_documents] : [],
    ];

    $form['status'] = [
      '#type' => 'value',
      '#value' => isset($leave_data->status) ? $leave_data->status : 'Pending',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

     // Add a hidden field for "id".
    $form['id'] = [
      '#type' => 'hidden',
      '#value' => isset($leave_data->id) ? $leave_data->id : NULL,
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve form values.
    $values = $form_state->getValues();
  
    // Update the leave record in the database.
    $this->database->update('request_leave_table')
      ->fields([
        'start_date' => $values['start_date'],
        'end_date' => $values['end_date'],
        'leave_type' => $values['leave_type'],
        'other' => $values['other'],
        'supporting_documents' => reset($values['supporting_documents']),
        // Add more fields as needed.
      ])
      ->condition('id', $values['id'])
      ->execute();
  
    // Display a message indicating that the update was successful.
    \Drupal::messenger()->addMessage($this->t('Leave record updated successfully.'));
  
    // Redirect to another page if needed.
    // $form_state->setRedirect('my_leaves.view_leave', ['id' => $values['id']]);
    $form_state->setRedirect('my_leaves.display_leaves');
  }

  // Helper function to fetch leave data from the database.
  protected function fetchLeaveData($id) {
    $query = $this->database->select('request_leave_table', 'rlt')
      ->fields('rlt', ['id', 'start_date', 'end_date', 'date_filed', 'leave_type', 'status', 'other', 'supporting_documents'])
      ->condition('rlt.id', $id)
      ->addTag('request_leave_table_cache_tag');

    $result = $query->execute()->fetch();

    return $result ?: [];
  }
}
