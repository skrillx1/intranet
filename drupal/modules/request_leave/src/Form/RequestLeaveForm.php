<?php

namespace Drupal\request_leave\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\file\Entity\File;

class RequestLeaveForm extends FormBase {

  protected $messenger;

  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger')
    );
  }

  public function getFormId() {
    return 'request_leave_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes']['enctype'] = 'multipart/form-data';
    $form['date_filed'] = [
        '#type' => 'date',
        '#title' => $this->t('Date Filed'),
        '#default_value' => date('Y-m-d'),
        '#required' => TRUE,
        '#disabled' => TRUE,
    ];
    
    $form['start_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Start Date'),
      '#required' => TRUE,
    ];

    $form['end_date'] = [
      '#type' => 'date',
      '#title' => $this->t('End Date'),
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
    ];

    $form['status'] = [
      '#type' => 'value',
      '#value' => 'Pending',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $username = \Drupal::currentUser()->getAccountName();
  
    $leave_type = $values['leave_type'];
    $other = isset($values['other']) ? $values['other'] : '';
  
    // Save the uploaded file.
    $file = $form_state->getValue('supporting_documents');
    $file_uploaded = NULL;

    if (!empty($file[0])) {
      $file_uploaded = File::load($file[0]);
      if ($file_uploaded) {
        $file_uploaded->setPermanent();
        $file_uploaded->save();
      }
    }

  
    // Get the file ID.
    $file_id = $file_uploaded ? $file_uploaded->id() : NULL;
  
    // Insert the form values into the database.
    $database = \Drupal::database();
    $database->insert('request_leave_table')
      ->fields([
        'start_date' => $values['start_date'],
        'end_date' => $values['end_date'],
        'date_filed' => $values['date_filed'],
        'leave_type' => $leave_type,
        'other' => $other,
        'supporting_documents' => $file_id,
        'status' => $values['status'],
        'username' => $username,
      ])
      ->execute();
  
    $this->messenger->addMessage($this->t('Leave request submitted successfully.'));
  }

  public function toggleOtherField(array &$form, FormStateInterface $form_state) {
    $leaveType = $form_state->getValue('leave_type');
    $showOtherField = $leaveType === 'Other';
  
    $form['other_field_wrapper']['other']['#access'] = $showOtherField;
  
    return $form['other_field_wrapper'];
  }

}
