<?php

namespace Drupal\request_delete_leaves\Controller;

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
class RequestDeleteLeavesController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a RequestDeleteLeavesController object.
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
      'Username',
      'Start Date',
      'End Date',
      'Date Filed',
      'Leave Type',
      'Status',
      'Action',
    ];

    $query = $this->database->select('request_leave_table', 'rlt')
      ->fields('rlt', ['id', 'username', 'start_date', 'end_date', 'date_filed', 'leave_type', 'status', 'other', 'delete_request'])
      ->condition('rlt.delete_request', 1)
      ->addTag('request_leave_table_cache_tag');
    $results = $query->execute()->fetchAll();

    $rows = [];
    foreach ($results as $result) {
      $leaveType = !empty($result->other) ? $result->leave_type . ' - ' . $result->other : $result->leave_type;
      $deleteUrl = Url::fromRoute('request_delete_leaves.delete_request_leaves', ['id' => $result->id]);
      $deleteLink = Link::fromTextAndUrl($this->t('Delete'), $deleteUrl);

      $rows[] = [
        $result->username,
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
              'delete' => [
                '#markup' => $deleteLink->toString(),
              ],
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

    $button = [
        '#type' => 'link',
        '#title' => $this->t('Request Leave'),
        '#url' => Url::fromUri('http://localhost:30080/request-leave'),
        '#attributes' => [
        'class' => ['request-delete-leaves-button'],
        ],
    ];

    $table_with_button = [
        '#type' => 'container',
        '#attributes' => [
        'class' => ['request-delete-leaves-container'],
        ],
        'button' => $button,
        'table' => $table,
    ];

    return $table_with_button;
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
  public function deleteLeaves($id) {
    $this->database->delete('request_leave_table')
      ->condition('id', $id)
      ->execute();
  
    return $this->redirect('request_delete_leaves.display_leaves');
  }

}
