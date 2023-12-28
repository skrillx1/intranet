<?php

namespace Drupal\my_leaves\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystemInterface;

/**
 * Controller for displaying leave information.
 */
class MyLeavesController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a MyLeavesController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Displays leave information.
   *
   * @return array
   *   A render array containing the leave information.
   */
  public function displayLeaves() {
    $username = \Drupal::currentUser()->getAccountName();
    $cache_tags = ['request_leave_table'];

    $header = [
      'Start Date',
      'End Date',
      'Date Filed',
      'Leave Type',
      'Status',
      'Action',
    ];

    $query = $this->database->select('request_leave_table', 'rlt')
      ->fields('rlt', ['id', 'start_date', 'end_date', 'date_filed', 'leave_type', 'status', 'other', 'delete_request'])
      ->condition('rlt.username', $username)
      ->addTag('request_leave_table_cache_tag');
    $results = $query->execute()->fetchAll();

    $rows = [];
    foreach ($results as $result) {
      $leaveType = !empty($result->other) ? $result->leave_type . ' - ' . $result->other : $result->leave_type;
      $viewUrl = Url::fromRoute('my_leaves.view_leave', ['id' => $result->id]);
      
      // Check if the status is 'Approved' or 'Denied' to disable edit and delete actions.
      $edit_disabled = $result->status == 'Approved' || $result->status == 'Denied' || $result->status == 'In Progress';
      $delete_disabled = $result->status == 'Approved' || $result->status == 'Denied' || $result->delete_request == 1;
      
      $editUrl = Url::fromRoute('my_leaves.edit_leave_form', ['id' => $result->id]);
      $deleteUrl = Url::fromUri('http://localhost:30080/delete-leave/' . $result->id);

      $editLink = [
        '#markup' => $edit_disabled ? '<span class="edit-link disabled">' . $this->t('Edit ') . '</span>' : Link::fromTextAndUrl($this->t('Edit'), $editUrl)->toString(),
      ];
      
      $deleteLink = [
        '#markup' => $delete_disabled ? '<span class="delete-link disabled">' . $this->t('Delete ') . '</span>' : Link::fromTextAndUrl($this->t('Delete'), $deleteUrl)->toString(),
      ];
      
      $rows[] = [
        $result->start_date,
        $result->end_date,
        $result->date_filed,
        $leaveType,
        $result->status,
        [
          'data' => [
            '#type' => 'container',
            'actions' => [
              '#type' => 'container',
              'view' => [ 
                '#type' => 'link',
                '#title' => $this->t('View '),
                '#url' => $viewUrl,
                '#attributes' => [
                  'class' => ['view-link'],
                ],
              ],
              'edit' => $editLink,
              'delete' => $deleteLink,
            ],
          ],
        ],
      ];
    }

    $table = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#cache' => [
        'tags' => $cache_tags,
        'max-age' => 0,
      ],
    ];

    // Create the button element.
    $button = [
        '#type' => 'link',
        '#title' => $this->t('Request Leave'),
        '#url' => Url::fromUri('http://localhost:30080/request-leave'),
        '#attributes' => [
        'class' => ['my-leaves-button'],
        ],
    ];

    // Add the button to the top of the table.
    $table_with_button = [
        '#type' => 'container',
        '#attributes' => [
        'class' => ['my-leaves-container'],
        ],
        'button' => $button,
        'table' => $table,
    ];

    return $table_with_button;
  }

  /**
   * Displays details of a specific leave.
   *
   * @param int $id
   *   The ID of the leave to display.
   *
   * @return array
   *   A render array containing the leave details.
   */
  public function viewLeave($id) {
    $cache_tags = ['request_leave_table'];

    $query = $this->database->select('request_leave_table', 'rlt')
      ->fields('rlt', ['id', 'start_date', 'end_date', 'date_filed', 'leave_type', 'status', 'other', 'supporting_documents'])
      ->condition('rlt.id', $id)
      ->addTag('request_leave_table_cache_tag');
    $result = $query->execute()->fetch();

    if (empty($result)) {
      drupal_set_message($this->t('Leave not found'), 'error');
      return $this->redirect('my_leaves.display_leaves');
    }

    $leaveType = !empty($result->other) ? $result->leave_type . ' - ' . $result->other : $result->leave_type;

    $backUrl = Url::fromRoute('my_leaves.display_leaves');
    $backLink = Link::fromTextAndUrl($this->t('Back to Leave List'), $backUrl);

    $convertedStartDate = \Drupal::service('date.formatter')->format(strtotime($result->start_date), 'custom', 'F j, Y');
    $convertedEndDate = \Drupal::service('date.formatter')->format(strtotime($result->end_date), 'custom', 'F j, Y');
    $convertedDateFiled = \Drupal::service('date.formatter')->format(strtotime($result->date_filed), 'custom', 'F j, Y');

    $supportingDocumentFiles = [];
    if (!empty($result->supporting_documents)) {
      $supportingDocumentFiles = File::loadMultiple([$result->supporting_documents]);
    }
    if (!empty($supportingDocumentFiles)) {
      $supportingDocuments = [];
      foreach ($supportingDocumentFiles as $file) {
        $file_url_generator = \Drupal::service('file_url_generator');
        $file_url = $file_url_generator->generateAbsoluteString($file->getFileUri());
        $filename = $file->getFilename();
        $supportingDocuments[] = '<a href="' . $file_url . '" target="_blank">' . $filename . '</a>';
      }
      $supportingDocuments = implode(', ', $supportingDocuments);
    } else {
      $supportingDocuments = 'None';
    }

    $details = [
      '#markup' => $this->t('
        <p><strong>' . $leaveType . '</strong></p>
        <p><strong>Status: </strong>' . $result->status . '</p>
        <p><strong>Duration: </strong>' . $convertedStartDate . ' - ' . $convertedEndDate . '</p>
        <p><strong>Date Filed: </strong>' . $convertedDateFiled . '</p>
        <p><strong>Supporting Document: </strong>' . $supportingDocuments . '</p>', 
      ),
    ];

    $output = [
      '#theme' => 'item_list',
      '#items' => $details,
      '#cache' => [
        'tags' => $cache_tags,
        'max-age' => 0,
      ]
    ];

    // Add a back link.
    $output['back_link'] = [
      '#markup' => $backLink->toString(),
    ];

    return $output;
  }

  /**
   * Deletes a leave record.
   *
   * @param int $id
   *   The ID of the leave to delete.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response.
   */
  public function deleteLeave($id) {
    // Get the status of the leave.
    $status = $this->database->select('request_leave_table', 'rlt')
      ->fields('rlt', ['status'])
      ->condition('id', $id)
      ->execute()
      ->fetchField();

    // Update the delete_request column if the status is "In Progress".
    if ($status == 'In Progress') {
      $this->database->update('request_leave_table')
        ->fields(['delete_request' => TRUE])
        ->condition('id', $id)
        ->execute();

      // Show a success message.
      $this->messenger()->addMessage($this->t('Leave delete request has been successfully initiated.'), 'status');
    }

    // Delete the leave record if the status is "Pending".
    elseif ($status == 'Pending') {
      $this->database->delete('request_leave_table')
        ->condition('id', $id)
        ->execute();

      // Show a success message.
      $this->messenger()->addMessage($this->t('Leave has been successfully deleted.'), 'status');
    }

    // Redirect back to the leave list or another appropriate page.
    return $this->redirect('my_leaves.display_leaves');
  }


}
