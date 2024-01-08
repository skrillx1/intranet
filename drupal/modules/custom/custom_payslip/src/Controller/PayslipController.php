<?php

/**
 * @file
 * Contains \Drupal\custom_payslip\Controller\PayslipController.
 *
 * Submitted by Rustum Goden, a dev intern from Caraga State University Cabadbaran Campus.
 */

namespace Drupal\custom_payslip\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Render\MarkupInterface;

/**
 * Controller for displaying PDF files.
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
   * Displays all PDF files from the 'public://payslips/' directory in a table.
   *
   * @return array
   *   A render array.
   */
  public function viewPayslipFiles() {
    // Get the current user.
    $current_user = \Drupal::currentUser();

    // Get the username.
    $username = $current_user->getAccountName();

    // Modify the directory based on the username.
    $directory = 'public://payslips/' . $username;

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

    // Scan the directory for PDF files.
    $files = $this->fileSystem->scanDirectory($directory, '/.*\.pdf/i');

    // if the directory exists. Check if there are any PDF files in the directory.
    if (empty($files)) {
      // Display a message if there are no PDF files in the directory.
      return [
        '#markup' => $this->t('There are no payslips yet.'),
        '#cache' => [
          'max-age' => 0,
        ]
      ];
    }

    $rows = [];
    foreach ($files as $file) {
      $file_uri = $directory . '/' . $file->filename;
      $filename = $file->filename;

      $file_link = $this->generateFileLink($file_uri, $filename);
      $download_link = $this->generateDownloadLink($file_uri, $filename);

      $rows[] = [
        'filename' => $file_link,
        'download_link' => $download_link,
      ];
    }

    // Define table headers.
    $header = [
      'filename' => $this->t('Filename'),
      'download_link' => $this->t('Action'),
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
   * Generates a link to view or download a file.
   *
   * @param string $file_uri
   *   The file URI.
   * @param string $filename
   *   The filename.
   * @param bool $download
   *   Whether to generate a download link.
   *
   * @return string
   *   The HTML link.
   */
  private function generateFileLink($file_uri, $filename, $download = FALSE) {
    // Get the file URL using the file_url_generator service.
    $file_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file_uri);

    // Use Link::fromTextAndUrl for creating the link.
    $link_text = $filename;
    $url = Url::fromUri($file_url);

    if ($download) {
      $link_text = $this->t('Download PDF');
      $url->setOption('query', ['download' => 'true']);

      // Add the Content-Disposition header to force download.
      $headers = [
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
      ];
      $url->setOption('headers', $headers);
    }

    // Add the download attribute to trigger automatic download.
    $options = ['attributes' => ['download' => $filename]];
    $link = Link::fromTextAndUrl($link_text, $url, $options)->toString();

    return $link;
  }

  /**
   * Generates a link to download a file.
   *
   * @param string $file_uri
   *   The file URI.
   * @param string $filename
   *   The filename.
   *
   * @return string
   *   The HTML link.
   */
  private function generateDownloadLink($file_uri, $filename) {
    // Encode the file URI for inclusion in the URL.
    $encoded_file_uri = rawurlencode($file_uri);

    // Create a URL that points to the custom controller method for handling downloads.
    $url = Url::fromRoute('custom_payslip.download_controller', ['file_uri' => $encoded_file_uri]);

    // Add the download attribute to trigger automatic download.
    $options = ['attributes' => ['download' => $filename]];
    $link_text = $this->t('Download PDF');
    $link = Link::fromTextAndUrl($link_text, $url, $options)->toString();

    return $link;
  }


  /**
  * Controller method to handle file downloads.
  *
  * @param string $file_uri
  *   The file URI.
  *
  * @return \Symfony\Component\HttpFoundation\Response
  *   The response object.
  */
  public function downloadController($file_uri) {
    $response = new Response();
    $response->headers->set('Content-Type', 'application/pdf');
    $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($file_uri) . '"');
    $response->setContent(file_get_contents($file_uri));

    return $response;
  }

}
