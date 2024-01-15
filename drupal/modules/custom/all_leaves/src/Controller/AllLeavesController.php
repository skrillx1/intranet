<?php

/**
 * @file
 * Contains Drupal\all_leaves\Controller\AllLeavesController.
 *
 * Submitted by Rustum Goden, a dev intern at Caraga State University Cabadbaran Campus.
 */

namespace Drupal\all_leaves\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;
use Drupal\file\Entity\File;

/**
 * Controller for managing leave requests and approvals.
 */
class AllLeavesController extends ControllerBase {

    /**
     * The database connection service.
     *
     * @var \Drupal\Core\Database\Connection
     */
    protected $database;

    /**
     * Constructs a new AllLeavesController object.
     *
     * @param \Drupal\Core\Database\Connection $database
     *   The database connection service.
     */
    public function __construct(Connection $database) {
        $this->database = $database;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        return new static($container->get('database'));
    }

    /**
     * Displays the leave requests in a table with actions.
     *
     * @return array
     *   The render array containing the leave requests table and a "Request Leave" button.
     */
    public function displayLeaves() {
        // Retrieve the current user's account name.
        $username = \Drupal::currentUser()->getAccountName();
        // Cache tags for the leave request table.
        $cache_tags = ['request_leave_table'];

        // Get the current user.
        $current_user = \Drupal::currentUser();

        // Load the user entity to get the Employee ID field value.
        $user = \Drupal\user\Entity\User::load($current_user->id());

        // Get the Employee ID field value.
        $account_department = $user->get('field_account')->first()->getValue()['value'];

        // Table header.
        $header = [
            'Username',
            'Start Date',
            'End Date',
            'Date Filed',
            'Leave Type',
            'Status',
            'Action',
        ];

        // Query leave requests from the database.
        $query = $this->database->select('request_leave_table', 'rlt')
        ->fields('rlt', ['id', 'start_date', 'end_date', 'date_filed', 'leave_type', 'status', 'other', 'username', 'account_department'])
        ->condition('rlt.status', 'Pending', '<>')
        ->addTag('request_leave_table_cache_tag');

        // Conditionally add a condition based on the department.
        if ($account_department !== 'human_resources') {
        $query->condition('rlt.account_department', $account_department);
        }

        $results = $query->execute()->fetchAll();

        // Prepare rows for the leave request table.
        $rows = [];
        foreach ($results as $result) {
            $leaveType = !empty($result->other) ? $result->leave_type . ' - ' . $result->other : $result->leave_type;
            $viewUrl = Url::fromRoute('all_leaves.view_leave', ['id' => $result->id]);

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
                        '#attributes' => ['id' => ['leave']],
                            'view' => [
                                '#type' => 'link',
                                '#title' => $this->t('<i class="bi bi-eye"></i>'),
                                '#url' => $viewUrl,
                                '#attributes' => ['class' => ['view-link']],
                            ],
                        ],
                    ],
            ];
        }

        // Build the leave request table.
        $table = [
            '#type' => 'table',
            '#header' => $header,
            '#rows' => $rows,
            '#cache' => [
                'tags' => $cache_tags,
                'max-age' => 0,
            ],
        ];

        // Build the container with the "Request Leave" button and the leave request table.
        $table_with_button = [
            '#type' => 'container',
            '#attributes' => ['class' => ['all-leaves-container']],
            'table' => $table,
        ];

        return $table_with_button;
    }



    /**
     * Displays details of a specific leave request.
     *
     * @param int $id
     *   The ID of the leave request.
     *
     * @return array
     *   The render array containing the leave request details.
     */
    public function viewLeave($id) {
        // Cache tags for the leave request table.
        $cache_tags = ['request_leave_table'];

        // Query the specific leave request from the database.
        $query = $this->database->select('request_leave_table', 'rlt')
            ->fields('rlt', ['id', 'start_date', 'end_date', 'date_filed', 'leave_type', 'status', 'other', 'supporting_documents'])
            ->condition('rlt.id', $id)
            ->addTag('request_leave_table_cache_tag');
        $result = $query->execute()->fetch();

        // If the leave request is not found, display an error message and redirect to leave list.
        if (empty($result)) {
            drupal_set_message($this->t('Leave not found'), 'error');
            return $this->redirect('all_leaves.display_leaves');
        }

        // Prepare leave request details for display.
        $leaveType = !empty($result->other) ? $result->leave_type . ' - ' . $result->other : $result->leave_type;

        $backUrl = Url::fromRoute('all_leaves.display_leaves');
        $backLink = Link::fromTextAndUrl($this->t('Back to Leave List'), $backUrl);

        $convertedStartDate = date('F j, Y', strtotime($result->start_date));
        $convertedEndDate = date('F j, Y', strtotime($result->end_date));
        $convertedDateFiled = date('F j, Y', strtotime($result->date_filed));

        $supportingDocumentFiles = !empty($result->supporting_documents) ? File::loadMultiple([$result->supporting_documents]) : [];
        $supportingDocuments = [];
        foreach ($supportingDocumentFiles as $file) {
            $file_url_generator = \Drupal::service('file_url_generator');
            $file_url = $file_url_generator->generateAbsoluteString($file->getFileUri());
            $filename = $file->getFilename();
            $supportingDocuments[] = '<a href="' . $file_url . '" target="_blank">' . $filename . '</a>';
        }
        $supportingDocuments = !empty($supportingDocuments) ? implode(', ', $supportingDocuments) : 'None';

        $details = [
            '#markup' => $this->t('
                <p class="leave-stats">' . $result->status . '</p>
                <div class="leave--type"><strong>' . $leaveType . '</strong></div>
                <div class="leave-duration"><i class="bi bi-clock-fill"></i><strong>Duration </strong>'.'<p class="leave-date">' .$convertedStartDate . ' - ' . $convertedEndDate .'</p>'.'</div>
                <div class="leave-date-filed"><i class="bi bi-calendar-week-fill"></i><strong>Date Filed </strong>' . '<p class="leave-date">' . $convertedDateFiled .'</p>' . '</div>
                <div class="leave-support-docs"><strong>Supporting Document </strong>' . $supportingDocuments . '</div>',
            ),
        ];
        
        // Build the render array for leave request details.
        $output = [
            '#theme' => 'item_list',
            '#attributes' => ['class' => ['view-leave']],
            '#items' => $details,
            '#cache' => [
                'tags' => $cache_tags,
                'max-age' => 0,
            ],
        ];

        // Add the "Back to Leave List" link to the render array.
        $output['back_link'] = [
            '#markup' => $backLink->toString(),
        ];
        return $output;
    }

}
