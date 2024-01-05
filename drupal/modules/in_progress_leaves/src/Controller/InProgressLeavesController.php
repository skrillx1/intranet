<?php

namespace Drupal\in_progress_leaves\Controller;

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

class InProgressLeavesController extends ControllerBase {

    protected $database;

    public function __construct(Connection $database) {
        $this->database = $database;
    }

    public static function create(ContainerInterface $container) {
        return new static($container->get('database'));
    }

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
            ->fields('rlt', ['id', 'start_date', 'end_date', 'date_filed', 'leave_type', 'status', 'other', 'username'])
            ->condition('rlt.status', 'In Progress', '=')
            ->addTag('request_leave_table_cache_tag');
        $results = $query->execute()->fetchAll();

        $rows = [];
        foreach ($results as $result) {
            $leaveType = !empty($result->other) ? $result->leave_type . ' - ' . $result->other : $result->leave_type;
            $viewUrl = Url::fromRoute('in_progress_leaves.view_leave', ['id' => $result->id]);

            $approve_disabled = $deny_disabled = $result->status == 'Approved' || $result->status == 'Denied';
            $approveUrl = Url::fromRoute('in_progress_leaves.approve_leave', ['id' => $result->id]);
            $denyUrl = Url::fromRoute('in_progress_leaves.deny_leave', ['id' => $result->id]);

            $approveLink = ['#markup' => $approve_disabled ? $this->t('<span class="approve-link disabled"><i class="bi bi-check2"></i></span>') : Link::fromTextAndUrl($this->t('<i class="bi bi-check2"></i>'), $approveUrl)->toString()];
            $denyLink = ['#markup' => $deny_disabled ? $this->t('<span class="deny-link disabled"><i class="bi bi-x-lg"></i></span>') : Link::fromTextAndUrl($this->t('<i class="bi bi-x-lg"></i>'), $denyUrl)->toString()];

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
                            'view' => [
                                '#type' => 'link',
                                '#title' => $this->t('<i class="bi bi-eye"></i>'),
                                '#url' => $viewUrl,
                                '#attributes' => ['class' => ['view-link']],
                            ],
                            'approve' => $approveLink,
                            'deny' => $denyLink,
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
            '#title' => $this->t('<i class="bi bi-megaphone"></i>Request Leave'),
            '#url' => Url::fromUri('http://localhost:30080/request-leave'),
            '#attributes' => ['class' => ['in-progress-leaves-button']],
        ];

        $table_with_button = [
            '#type' => 'container',
            '#attributes' => ['class' => ['in-progress-leaves-container']],
            'button' => $button,
            'table' => $table,
        ];

        return $table_with_button;
    }

    public function viewLeave($id) {
        $cache_tags = ['request_leave_table'];

        $query = $this->database->select('request_leave_table', 'rlt')
            ->fields('rlt', ['id', 'start_date', 'end_date', 'date_filed', 'leave_type', 'status', 'other', 'supporting_documents'])
            ->condition('rlt.id', $id)
            ->addTag('request_leave_table_cache_tag');
        $result = $query->execute()->fetch();

        if (empty($result)) {
            drupal_set_message($this->t('Leave not found'), 'error');
            return $this->redirect('in_progress_leaves.display_leaves');
        }

        $leaveType = !empty($result->other) ? $result->leave_type . ' - ' . $result->other : $result->leave_type;

        $backUrl = Url::fromRoute('in_progress_leaves.display_leaves');
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
            ],
        ];

        $output['back_link'] = [
            '#markup' => $backLink->toString(),
        ];

        return $output;
    }

    public function updateLeaveStatus($id, $status) {
        $update = $this->database->update('request_leave_table')
            ->fields(['status' => $status])
            ->condition('id', $id)
            ->execute();

        Cache::invalidateTags(['request_leave_table']);

        return new RedirectResponse(Url::fromRoute('in_progress_leaves.display_leaves')->toString());
    }

    public function approveLeave($id) {
        return $this->updateLeaveStatus($id, 'Approved');
    }

    public function denyLeave($id) {
        return $this->updateLeaveStatus($id, 'Denied');
    }
}
