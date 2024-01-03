<?php
namespace Drupal\workforce_monitoring\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\workforce_monitoring\Form\DateForm;
use Drupal\workforce_monitoring\Form\AccountForm;
use DateTime;

/**
 * Controller for the workforce monitoring page.
 */

class WorkforceMonitoringController extends ControllerBase {

  /**
   * Returns the content for the workforce monitoring page.
   *
   * @return array
   *   A render array containing the breaksContent of the page.
   */
  public function breaksContent() {
    $selectedDate = \Drupal::state()->get('workforce_monitoring_selected_date', date('Y-m-d'));
    $newFormattedDate = date('F d, Y', strtotime($selectedDate));

    $dateForm = $this->formBuilder()->getForm(DateForm::class);
    $current_date = $selectedDate;
    $data = $this->fetchBreaksData($current_date);

    $totalOverBreakRows = count($data['overBreakRows']);
    $totalRows = count($data['rows']);

    $mainTableMarkup = $this->renderTable($data['mainTable']);
    $overBreakTableMarkup = $this->renderTable($data['overBreakTable']);

    $button_url = Url::fromUserInput('/workforce-monitoring/onBreaks');
    $button_link = Link::fromTextAndUrl($this->t('On Breaks'), $button_url);
    $button_markup = $button_link->toRenderable();
    $button_prefix = \Drupal::service('renderer')->renderPlain($button_markup);

    return [
      'date_form' => $dateForm,
      '#markup' => $this->t(
        '<div class="over-break-head">' . $button_prefix . '<br><h3>' . $newFormattedDate . '</h3><h4>Over-Break Logs</h4>Employees Over Break: <strong>' . $totalOverBreakRows . '</strong></div>' . $overBreakTableMarkup .
        '<div class="all-employee"><br><h4>Logs Summary</h4>Total Number of Employees Logged In: <strong>' . $totalRows . '</strong> </div>' . $mainTableMarkup
      ),
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Returns the content for the workforce monitoring page.
   *
   * @return array
   *   A render array containing the onBreaksContent of the page.
   */
  public function onBreaksContent() {
    
    $currentHour = date('G');
    if ($currentHour == 12 && date('i') == 59) {
        $current_date = date('Y-m-d', strtotime('+1 day'));
    } else {
        if ($currentHour >= 13 && $currentHour <= 23) {
            $current_date = date('Y-m-d');
        } else {
            $current_date = date('Y-m-d', strtotime('-1 day'));
        }
    }

    $AccountForm = $this->formBuilder()->getForm(AccountForm::class);
    $data = $this->fetchOnBreaksData($current_date);

    $totalOverBreakRows = count($data['overBreakRows']);
    $overBreakTableMarkup = $this->renderTable($data['overBreakTable']);

    $button_url = Url::fromUserInput('/workforce-monitoring/breaks');
    $button_link = Link::fromTextAndUrl($this->t('All Logs'), $button_url);
    $button_markup = $button_link->toRenderable();
    $button_prefix = \Drupal::service('renderer')->renderPlain($button_markup);

    $refreshableContent = '<div id="refreshableContent">' . $button_prefix
    . '<div class="legend"><strong>Legend:</strong><span class="warning">Warning</span>'
    . '<span class="break1">1st Break</span><span class="break2">2nd Break</span><span class="lunch">Lunch</span></div>'  
    . '<div id="onBreaksContent"><strong>' . $data['todayDate'] . '</strong><br>Employees Over Break: <strong>' . $totalOverBreakRows . '</strong>'
    . $overBreakTableMarkup
    . '</div></div>';

    return [
      'account_form' => $AccountForm,
      '#markup' => $refreshableContent,
      '#attached' => [
        'library' => [
          'workforce_monitoring/onBreaksRefresh',
        ],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }
  /**
   * Helper function to fetch breaks data.
   *
   * @param string $current_date
   *   The current date.
   *
   * @return array
   *   The breaks data.
   */
  private function fetchBreaksData($current_date) {
    $external_database = Database::getConnection('default','external');
    $todayDate = date('F j, Y');
    $selectedAccount = \Drupal::state()->get('workforce_monitoring_selected_account');

    $query = $external_database->select('Breaks', 'b')
      ->fields('b', [
        'EmpNo',
        'ShiftDate',
        'Login',
        'BrkIn1',
        'BrkOut1',
        'LunchIn',
        'LunchOut',
        'BrkIn2',
        'BrkOut2',
        'Logoff'
      ]);

    $query->leftJoin('Employee', 'e', 'e.EmpNo = b.EmpNo');
    $query->leftJoin('Acct', 'a', 'e.AcctID = a.AcctID');
    $query->fields('e', ['FName', 'LName', 'AcctID']);
    $query->fields('a', ['AcctName']);
    $query->condition('ShiftDate', $current_date);

    if ($selectedAccount) {
      $query->condition('a.AcctName', $selectedAccount);
    }

    $query->orderBy('e.FName');

    $result = $query->execute();

    $rows = [];
    $overBreakRows = [];
    foreach ($result as $row) {
      $brk1In = $row->BrkIn1 ? strtotime($row->BrkIn1) : null;
      $brk1Out = $row->BrkOut1 ? strtotime($row->BrkOut1) : null;
      $brk1Duration = $brk1In && $brk1Out ? ($brk1Out - $brk1In) : null;
      $isOverBreak1 = $brk1Duration && $brk1Duration > 900;
      $b1ob = $isOverBreak1 ? gmdate('H:i:s', $brk1Duration - 900) : null;

      $brk2In = $row->BrkIn2 ? strtotime($row->BrkIn2) : null;
      $brk2Out = $row->BrkOut2 ? strtotime($row->BrkOut2) : null;
      $brk2Duration = $brk2In && $brk2Out ? ($brk2Out - $brk2In) : null;
      $isOverBreak2 = $brk2Duration && $brk2Duration > 900;
      $b2ob = $isOverBreak2 ? gmdate('H:i:s', $brk2Duration - 900) : null;

      $lunchin = $row->LunchIn ? strtotime($row->LunchIn) : null;
      $lunchout = $row->LunchOut ? strtotime($row->LunchOut) : null;
      $lunchDuration = $lunchin && $lunchout ? ($lunchout - $lunchin) : null;
      $isOverBreakLunch = $lunchDuration && $lunchDuration > 3600;
      $lob = $isOverBreakLunch ? gmdate('H:i:s', $lunchDuration - 3600) : null;

      if ($b1ob === null && $lob === null && $b2ob === null) {
        $rows[] = [
          'Name' => $row->FName . " " . $row->LName,
          'Login' => $row->Login ? date('h:i:s A', strtotime($row->Login)) : null,
          'BrkIn1' => $row->BrkIn1 ? date('h:i:s A', strtotime($row->BrkIn1)) : null,
          'BrkOut1' => $row->BrkOut1 ? date('h:i:s A', strtotime($row->BrkOut1)) : null,
          'LunchIn' => $row->LunchIn ? date('h:i:s A', strtotime($row->LunchIn)) : null,
          'LunchOut' => $row->LunchOut ? date('h:i:s A', strtotime($row->LunchOut)) : null,
          'BrkIn2' => $row->BrkIn2 ? date('h:i:s A', strtotime($row->BrkIn2)) : null,
          'BrkOut2' => $row->BrkOut2 ? date('h:i:s A', strtotime($row->BrkOut2)) : null,
          'Logoff' => $row->Logoff ? date('h:i:s A', strtotime($row->Logoff)) : null,
          'Account' => $row->AcctName,
        ];
      } else if ($isOverBreak1 || $isOverBreak2 || $isOverBreakLunch) {
        $overBreakRows[] = [
          'Name' => $row->FName . " " . $row->LName,
          'Account' => $row->AcctName,
          'B1OB' => $b1ob,
          'LOB' => $lob,
          'B2OB' => $b2ob,
        ];
      }
    }

    $overBreakTableHeader = [
      'Name',
      'Account',
      '1st Break',
      'Lunch Break',
      '2nd Break',
    ];

    $mainTableHeader = [
      'Name',
      'Log In',
      'Brk1 In',
      'Brk1 Out',
      'Lnch In',
      'Lnch Out',
      'Brk2 In',
      'Brk2 Out',
      'Log Out',
      'Account',
    ];

    $overBreakTableRows = [
      '#theme' => 'table',
      '#header' => $overBreakTableHeader,
      '#rows' => $overBreakRows,
      '#attributes' => ['id' => ['ob-table']],
    ];

    $mainTableRows = [
      '#theme' => 'table',
      '#header' => $mainTableHeader,
      '#rows' => $rows,
      '#attributes' => ['id' => ['main-table']],
    ];

    return [
      'todayDate' => $todayDate,
      'overBreakRows' => $overBreakRows,
      'rows' => $rows,
      'mainTable' => $mainTableRows,
      'overBreakTable' => $overBreakTableRows,
    ];
  }

  /**
   * Helper function to fetch breaks data.
   *
   * @param string $current_date
   *   The current date.
   *
   * @return array
   *   The breaks data.
   */
  private function fetchOnBreaksData($current_date) {
    $external_database = Database::getConnection('default','external');
    $todayDate = date('F j, Y');
    $selectedAccount = \Drupal::state()->get('workforce_monitoring_selected_account');

    $query = $external_database->select('Breaks', 'b')
      ->fields('b', [
        'EmpNo',
        'ShiftDate',
        'Login',
        'BrkIn1',
        'BrkOut1',
        'LunchIn',
        'LunchOut',
        'BrkIn2',
        'BrkOut2',
        'Logoff'
      ]);

    $query->leftJoin('Employee', 'e', 'e.EmpNo = b.EmpNo');
    $query->leftJoin('Acct', 'a', 'e.AcctID = a.AcctID');
    $query->fields('e', ['FName', 'LName', 'AcctID']);
    $query->fields('a', ['AcctName']);
    $query->condition('ShiftDate', $current_date);

    if ($selectedAccount) {
      $query->condition('a.AcctName', $selectedAccount);
    }

    $query->orderBy('b.BrkIn1', 'DESC');
    $query->orderBy('b.BrkIn2', 'DESC');
    $query->orderBy('b.LunchIn', 'DESC');

    $result = $query->execute();

    $overBreakRows = [];
    foreach ($result as $row) {
      $currentStatus = null;
      $isOnBreak = false;
      $breakStart = null;
      $breakEnd = null;
      $warningFlag = false;

      if ($row->BrkIn1 !== null && $row->BrkOut1 === null) {
        $currentStatus = '1ST BREAK';
        $isOnBreak = true;
        $breakStart = date('h:i:s A', strtotime($row->BrkIn1));
        $breakEnd = date('h:i:s A', strtotime($breakStart) + (15 * 60));

        $startTime = strtotime($breakStart);
        $currentTime = strtotime('now');
        $duration = $currentTime - $startTime;

        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);
        $seconds = $duration % 60;
        $durationFormatted = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

        if ($minutes >= 13) {
          $warningFlag = true;

        }
      } 
      
      if ($row->BrkIn2 !== null && $row->BrkOut2 === null) {
        $currentStatus = '2ND BREAK';
        $isOnBreak = true;
        $breakStart = date('h:i:s A', strtotime($row->BrkIn2));
        $breakEnd = date('h:i:s A', strtotime($breakStart) + (15 * 60));

        $startTime = strtotime($breakStart);
        $currentTime = strtotime('now');
        $duration = $currentTime - $startTime;

        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);
        $seconds = $duration % 60;
        $durationFormatted = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

        if ($minutes >= 13) {
          $warningFlag = true;
        
        }
      }

      if ($row->LunchIn !== null && $row->LunchOut === null) {
        $currentStatus = 'LUNCH';
        $isOnBreak = true ;
        $breakStart = date('h:i:s A', strtotime($row->LunchIn));
        $breakEnd = date('h:i:s A', strtotime($breakStart) + (60 * 60));
        
        $startTime = strtotime($breakStart);
        $currentTime = strtotime('now');
        $duration = $currentTime - $startTime;

        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);
        $seconds = $duration % 60;
        $durationFormatted = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

        if ($minutes >= 55) {
          $warningFlag = true;

        }
      }
      
      if ($isOnBreak === true) {
   
        $overBreakRows[] = [
          'Name' => $row->FName . " " . $row->LName,
          'Department' => $row->AcctName,
          'Start' => $breakStart,
          'End' => $breakEnd,
          'Duration' => $durationFormatted,
          'Status' => $currentStatus,
          'warning' => $warningFlag,
        ];
        
      }
    }

    $overBreakTableHeader = [
      'Name',
      'Department',
      'Start',
      'End',
      'Duration',
      'Type',
    ];

    $overBreakTableRows = [
      '#theme' => 'table',
      '#attributes' => ['id' => ['break']],
      '#header' => $overBreakTableHeader,
      '#rows' => $overBreakRows,
      
    ];
    
    return [
      'todayDate' => $todayDate,
      'overBreakRows' => $overBreakRows,
      'overBreakTable' => $overBreakTableRows,
    ];
  }

  /**
   * Helper function to render a table.
   *
   * @param array $table
   *   The table to be rendered.
   *
   * @return string
   *   The HTML markup of the table.
   */
  private function renderTable(array $table) {
    return \Drupal::service('renderer')->render($table);
  }

};