<?php
/**
 * @file
 * Contains Drupal\google_calendar_service\Plugin\QueueWorker\CalendarImportProcessorBase.php
 */

namespace Drupal\google_calendar_service\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\google_calendar_service\CalendarImport;
use Drupal\google_calendar_service\Entity\CalendarInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Queue Worker for import calendar events.
 *
 * @QueueWorker(
 *   id = "gcs_calendar_import_processor",
 *   title = "Calendar Import Processor",
 *   cron = {"time" = 60}
 * )
 */
class CalendarImportProcessor extends QueueWorkerBase implements
ContainerFactoryPluginInterface {

  /**
   * Drupal\google_calendar_service\GoogleCalendarImport definition.
   *
   * @var \Drupal\google_calendar_service\GoogleCalendarImport
   */
  protected $calendarImport;

  /**
   * CalendarImportProcessor constructor.
   *
   * @param \Drupal\google_calendar_service\CalendarImport $calendar_import
   *   The calendar import.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, CalendarImport $calendar_import) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->calendarImport = $calendar_import;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition) {

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('google_calendar_service.import_events')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($calendar): void {
    $this->calendarImport->import($calendar);
  }

}
