<?php

/**
 * @file
 * Contains \Drupal\payslip\Controller\PayslipController.
 *
 * Submitted by Rustum Goden, a dev intern from Caraga State University Cabadbaran Campus.
 */

namespace Drupal\payslip\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Render\MarkupInterface;

/**
 * Controller for displaying CSV files.
 */
class PayslipController extends ControllerBase {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a new PayslipController object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(FileSystemInterface $file_system) {
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_system')
    );
  }

  /**
   * Displays the content of a CSV file filtered by Company ID#.
   *
   * @param string $filename
   *   The filename of the CSV file.
   *
   * @return array
   *   A render array.
   */
  public function viewPayslipFileContent($filename) {
    // Get the current user.
    $current_user = \Drupal::currentUser();

    // Load the user entity to get the Employee ID field value.
    $user = \Drupal\user\Entity\User::load($current_user->id());

    // Get the Employee ID field value.
    $employee_id = $user->get('field_employee_id')->first()->getValue()['value'];

    $directory = 'public://payslips/';
    $file_uri = $directory . '/' . $filename;

    // Read the content of the CSV file.
    $file_content = file_get_contents($file_uri);

    // Explode the content into rows.
    $rows = explode("\n", $file_content);

    // Get labels from the first row.
    $labels = str_getcsv(array_shift($rows));

    // Filter rows based on Company ID#.
    $filtered_rows = [];
    foreach ($rows as $row) {
      $data = str_getcsv($row);
      if (!empty($data) && isset($data[3]) && $data[3] == $employee_id) {
        $filtered_rows[] = $data;
        // Display the filtered content with labels in a render array.
        $output['file_content'] = [
          '#markup' => $this->t('

              <div class="payslip-view">

              <p class="col-6"><strong>Employee Name:</strong><span class="employee-name line"> @employee_name </span></p>
              <p class="col-6"><strong>Title/Position:</strong><span class="position line"> @position </span></p>
              <p class="col-6"><strong>Company ID#:</strong><span class="company-id line"> @company_id </span></p>
              <p class="col-6"><strong>NET PAY:</strong><span class="net-pay line"> @net_pay </span></p>
              <p><strong>Company Address:</strong><span class="line address"> 7/F MDCT Building, Leyte Loop, Cebu Business Park, Cebu City 6000 Philippines</span></p>
              <p class="date-received-slip">I have received the below payment on this day of: <strong>@date</strong></p>
              
              <table class="earnings">
              <thead>
                <tr>
                <th style="width:30%">EARNINGS</th>
                <th style="width:20%"></th>
                <th style="width:35%"></th>
                <th style="width:15%"></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                <td colspan="4" style="text-align: center; font-weight: 500;">PAY PERIOD</td>
                </tr>
                <tr>
                  <td>Start Period</td>
                  <td>@start_period</td>
                  <td>End Period</td>
                  <td>@end_period</td>
                </tr>
                <tr>
                  <td>EARNING TYPE</td>
                  <td></td>
                  <td>RATE</td>
                  <td>AMOUNT</td>
                </tr>
                <tr>
                  <td>Basic</td>
                  <td></td>
                  <td></td>
                  <td>@basic</td>
                </tr>
                <tr>
                  <td>Regular Holiday</td>
                  <td></td>
                  <td></td>
                  <td>@regular_holiday</td>
                </tr>
                <tr>
                  <td>Special Holiday</td>
                  <td></td>
                  <td></td>
                  <td>@special_holiday</td>
                </tr>
                <tr>
                  <td>Attendance Bunos</td>
                  <td></td>
                  <td></td>
                  <td>@attendance_bunos</td>
                </tr>
                <tr>
                  <td>Overtime Pay (hours)</td>
                  <td>@overtime_pay_hours</td>
                  <td>@overtime_pay_rate</td>
                  <td></td>
                </tr>
                <tr>
                  <td>Holiday OT (hours)</td>
                  <td>@holiday_ot</td>
                  <td></td>
                  <td></td>
                </tr>
                <tr>
                  <td>Night Differential</td>
                  <td></td>
                  <td></td>
                  <td>@night_differential</td>
                </tr>
                <tr>
                  <td>Hazard Pay</td>
                  <td></td>
                  <td></td>
                  <td>@hazard_pay</td>
                </tr>
                <tr>
                  <td>Other Adjustment</td>
                  <td></td>
                  <td></td>
                  <td>@other_adjustment</td>
                </tr>
                <tr>
                <td colspan="3">TOTAL INCOME</td>
                <td>@total_income</td>
                </tr>
                </tbody>
                </table>
               <table class="deductions">
                <thead>
                <tr>
                <th style="width:30%">DEDUCTIONS</th>
                <th style="width:20%"></th>
                <th style="width:30%"></th>
                <th style="width:20%"></th>
                </tr>
                </thead>
                <tbody>
                <tr style="font-weight: 500;">
                  <td>DEDUCTION TYPE</td>
                  <td></td>
                  <td>RATE</td>
                  <td>AMOUNT</td>
                </tr>
                <tr>
                  <td>Absence</td>
                  <td></td>
                  <td></td>
                  <td>@absence</td>
                </tr>
                <tr>
                  <td>Over Breaks</td>
                  <td></td>
                  <td></td>
                  <td>@over_break</td>
                </tr>
                <tr>
                  <td>Tardy(Min)</td>
                  <td></td>
                  <td></td>
                  <td>@tardy</td>
                </tr>
                <tr>
                  <td>SSS</td>
                  <td></td>
                  <td></td>
                  <td>@sss</td>
                </tr>
                <tr>
                  <td>Philhealth</td>
                  <td></td>
                  <td></td>
                  <td>@philhealth</td>
                </tr>
                <tr>
                  <td>Pag-Ibig</td>
                  <td></td>
                  <td></td>
                  <td>@pag_ibig</td>
                </tr>
                <tr>
                  <td>W/TAX</td>
                  <td></td>
                  <td></td>
                  <td>@w_tax</td>
                </tr>
                <tr>
                  <td>Others</td>
                  <td></td>
                  <td></td>
                  <td>@others_sss</td>
                </tr>
                <tr>
                <td colspan="3">TOTAL DEDUCTIONS</td>
                  <td>@total_deduction</td>
                </tr>
                </tbody>
              </table>

              <p class="signature"><strong>@employee_name</strong></br>Employee Signature Over Printed Name</p>
              <br>
              <br>
              ', 
              [
                '@date' => $data[0],
                '@employee_name' => $data[1],
                '@position' => $data[2],
                '@company_id' => $data[3],
                '@basic' => $data[4],
                '@regular_holiday' => $data[5],
                '@special_holiday' => $data[6],
                '@attendance_bunos' => $data[7],
                '@overtime_pay_rate' => $data[8],
                '@overtime_pay_hours' => $data[9],
                '@holiday_ot' => $data[10],
                '@night_differential' => $data[11],
                '@hazard_pay' => $data[13],
                '@other_adjustment' => $data[14],
                '@over_break' => $data[15],
                '@count_over_break' => $data[16],
                '@absence' => $data[17],
                '@tardy' => $data[18],
                '@sss' => $data[19],
                '@philhealth' => $data[20],
                '@pag_ibig' => $data[21],
                '@w_tax' => $data[22],
                '@others_sss' => $data[23],
                '@total_deduction' => $data[24],
                '@total_income' => $data[25],
                '@net_pay' => $data[26],
                '@start_period' => $data[27],
                '@end_period' => $data[28],
              ]),
          '#cache' => [
            'max-age' => 0,
          ]
        ];
      }
    }
    if (empty($filtered_rows)) {
      $output['file_content'] = [
        '#markup' => $this->t('No payslip content found for the specified Company ID#.'),
        '#cache' => [
          'max-age' => 0,
        ]
      ];
    }
    return $output;

  }

  /**
   * Displays all CSV files from the 'public://payslips/' directory in a table.
   *
   * @return array
   *   A render array.
   */
  public function viewPayslipFiles() {
    $directory = 'public://payslips/';

    // Check if the directory exists.
    if (!file_exists($directory) || !is_dir($directory)) {
      // Display a message if the directory doesn't exist or is not a regular directory.
      return [
        '#markup' => $this->t('There are no payslips yet.'),
        '#cache' => [
          'max-age' => 0,
        ]
      ];
    }

    // Scan the directory for CSV files.
    $files = $this->fileSystem->scanDirectory($directory, '/.*\.csv/i');

    // if the directory exists. Check if there are any CSV files in the directory.
    if (empty($files)) {
      // Display a message if there are no CSV files in the directory.
      return [
        '#markup' => $this->t('There are no payslips yet.'),
        '#cache' => [
          'max-age' => 0,
        ]
      ];
    }

    $rows = [];
    foreach ($files as $file) {
      $filename = $file->filename;

      $file_link = $this->generateFileLink($filename);
      $rows[] = [
        'filename' => $file_link,
      ];
    }

    // Define table headers.
    $header = [
      'filename' => $this->t('My Payroll Summaray'),
    ];

    // Use cache tags to associate the cache with the files.
    $cache_tags = [];
    foreach ($files as $file) {
      $cache_tags[] = 'file:' . $file->uri;
    }

    // Build the table.
    $output['table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#cache' => [
        'contexts' => [
          'user',
        ],
        'tags' => $cache_tags,
      ],
    ];

    return $output;
  }

  /**
   * Generates a link to view a file.
   *
   * @param string $filename
   *   The filename.
   *
   * @return string
   *   The HTML link.
   */
  private function generateFileLink($filename) {
    // Remove the file extension from the filename.
    $filenameWithoutExtension = pathinfo($filename, PATHINFO_FILENAME);

    // Add the ".csv" extension to the filename.
    $filenameWithCsvExtension = $filenameWithoutExtension . '.csv';

    // Use Link::createFromRoute for creating the link.
    $link_text = $filenameWithoutExtension;
    $url = Url::fromRoute('payslip.file_content', ['filename' => $filenameWithCsvExtension]);

    $link = Link::fromTextAndUrl($link_text, $url)->toString();

    return $link;
  }



}