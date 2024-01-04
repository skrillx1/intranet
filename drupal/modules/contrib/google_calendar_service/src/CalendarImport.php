<?php

namespace Drupal\google_calendar_service;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\google_calendar_service\Entity\Calendar;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Exception;
use DateTime;
use DateTimeZone;
use Drupal\Core\Database\Connection;

/**
 * Class CalendarImport.
 *
 * @package google_calendar_service
 */
class CalendarImport {

  /**
   * Google Calendar service definition.
   *
   * @var \Google_Service_Calendar
   */
  protected $service;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * CalendarImport constructor.
   *
   * @param \Google_Client $google_client
   *   The google client.
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   The config.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel factory.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   */
  public function __construct(
    Google_Client $google_client,
    ConfigFactory $config,
    EntityTypeManagerInterface $entityTypeManager,
    LoggerChannelFactoryInterface $logger,
    Connection $database) {

    $this->service = new Google_Service_Calendar($google_client);
    $this->config = $config->getEditable(
      'google_calendar_service.last_imports'
    );
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $logger->get('google_calendar_service');
    $this->database = $database;
  }

  /**
   * The import of calendar events.
   *
   * @param \Drupal\google_calendar_service\Entity\Calendar $calendar
   *   The calendar.
   * @param bool $ignore_sync_token
   *   The sync token.
   *
   * @return bool
   *   Return bool.
   */
  public function import(Calendar $calendar, $ignore_sync_token = FALSE) {
    try {
      $cid = $calendar->getGoogleCalendarId();

      $config_key = "config_for_calendar_$cid";
      $sync_token = $ignore_sync_token ? NULL : $this->config->get($config_key);

      $google_calendar = $this->service->calendars->get($cid);
      $startDate = $calendar->getStartDate();
      $endDate = $calendar->getEndDate();
      $setAll = $calendar->getSetAll();

      if ($setAll == 1) {
        $range = FALSE;
      }
      else {
        $range['timeMin'] = date(DateTime::RFC3339, $startDate);
        $range['timeMax'] = date(DateTime::RFC3339, strtotime(
          '+1 day',
          $endDate
        ));
      }

      // Init dummy page token.
      $next_page_token = NULL;

      $page_count = 0;
      do {
        $page = $this->getPage($cid, $sync_token, $next_page_token, $range);
        if (!$page) {
          return FALSE;
        }

        $next_page_token = $page->next_page_token;
        $next_sync_token = $page->next_sync_token;
        $items = $page->getItems();

        if (count($items) > 0) {
          $this->syncEvents($items, $calendar, $google_calendar->getTimeZone());
        }

        $page_count++;
      } while ($next_page_token && $page_count < 10);

      // Set sync token.
      $this->config->set($config_key, $next_sync_token);
      $this->config->save();

      $this->logger->info(
        'Calendar: @calendar imported successfully.',
        [
          '@calendar' => $calendar->label(),
        ]
      );

      return TRUE;
    }
    catch (Google_Service_Exception $e) {
      // Catch non-authorized exception.
      if ($e->getCode() == 401) {
        return FALSE;
      }
    }
  }

  /**
   * Get page.
   *
   * @param int $cid
   *   The calendar id.
   * @param string $sync_token
   *   The sync token.
   * @param bool $page_token
   *   The page token.
   * @param bool $range
   *   The range.
   *
   * @return bool|\Google_Service_Calendar_Events
   *   Return google calendar.
   */
  private function getPage(
    $cid,
    $sync_token,
    $page_token = NULL,
    $range = FALSE) {

    $fields = 'description,end,endTimeUnspecified,htmlLink,id,location,';
    $fields .= 'originalStartTime,recurrence,recurringEventId,sequence,';
    $fields .= 'start,summary,attendees,organizer,extendedProperties,status';
    try {
      $opts = [
        'singleEvents' => TRUE,
        'fields' => "items($fields)",
      ];

      if (!empty($page_token)) {
        $opts['pageToken'] = $page_token;
      }

      if ($sync_token) {
        $opts['nextSyncToken'] = $sync_token;
      }
      else {
        $opts['orderBy'] = 'startTime';

        if (is_array($range)) {
          $opts['timeMin'] = $range['timeMin'];
          $opts['timeMax'] = $range['timeMax'];
        }
      }
      // List events api.
      $response = $this->service->events->listEvents($cid, $opts);

    }
    catch (Google_Service_Exception $e) {
      // Catch token expired and re-pull.
      if ($e->getCode() == 410) {
        $response = $this->getPage($cid, NULL, $range);
      }
      else {
        $response = FALSE;
      }
    }

    return $response;
  }

  /**
   * Sync events.
   *
   * @param array $events
   *   The list of events.
   * @param string $calendar
   *   The calendar.
   * @param string $timezone
   *   The timezone.
   */
  private function syncEvents(array $events, $calendar, $timezone) {
    // Get list of event Ids.
    $event_ids = [];

    foreach ($events as $event) {
      $event_ids[] = $event['id'];
    }

    // Query to get list of existing events.
    $query = $this->entityTypeManager
      ->getStorage('gcs_calendar_event')
      ->getQuery()
      ->condition('calendar', $calendar->id(), 'IN')
      ->condition('event_id', $event_ids, 'IN')
      ->accessCheck(FALSE);

    $existent_event_ids = $query->execute();

    $existent_events = $this->entityTypeManager
      ->getStorage('gcs_calendar_event')
      ->loadMultiple($existent_event_ids);

    // Index the existing event nodes by Google Calendar Id for easier lookup.
    $indexed_events = [];
    foreach ($existent_events as $event) {
      $indexed_events[$event->getGoogleEventId()] = $event;
    }

    // Delete events if are not in the $events.
    if (!empty($existent_event_ids)) {
      $this->database->delete('gcs_calendar_event')
        ->condition('calendar', $calendar->id(), 'IN')
        ->condition('id', array_values($existent_event_ids), 'NOT IN')
        ->execute();
    }

    // Iterate over events and update Drupal nodes accordingly.
    foreach ($events as $event) {
      // Get the event node.
      $event_entity = isset($indexed_events[$event['id']]) ?
        $indexed_events[$event['id']] :
        NULL;

      // Cutoff for deleted events.
      if ($event['status'] == 'cancelled') {
        if ($event_entity) {
          // If event is cancelled and we have an associated event node,
          // remove it.
          $event_entity->delete();
        }

        continue;
      }

      // Handle new or updated events.
      $start_date = $event['start']['date'] ?
        new DateTime($event['start']['date'], new DateTimeZone($timezone)) :
        DateTime::createFromFormat(
          DateTime::ISO8601,
          $event['start']['dateTime']
        );

      $end_date = $event['end']['date'] ?
        new DateTime($event['end']['date'], new DateTimeZone($timezone)) :
        DateTime::createFromFormat(
          DateTime::ISO8601,
          $event['end']['dateTime']
        );

      // Config fields.
      $fields = [
        'name' => (isset($event['summary']) && strlen($event['summary']) > 255) ? substr($event['summary'], 0, 254) . '...' : $event['summary'],
        'event_id' => [
          'value' => $event['id'],
        ],
        'calendar' => [
          'target_id' => $calendar->id(),
        ],
        'description' => [
          'value' => $event['description'],
          'format' => 'plain_text',
        ],
        'location' => [
          'value' => $event['location'],
        ],
        'start_date' => [
          'value' => $start_date->setTimezone(new DateTimeZone('UTC'))
            ->getTimestamp(),
        ],
        'end_date' => [
          'value' => $end_date->setTimezone(new DateTimeZone('UTC'))
            ->getTimestamp(),
        ],
        'event_url' => [
          'value' => $event['htmlLink'],
        ],
      ];

      if (!$event_entity) {
        $event_entity = $this->entityTypeManager
          ->getStorage('gcs_calendar_event')
          ->create($fields);
      }
      else {
        // Update the existing node in place.
        foreach ($fields as $key => $value) {
          $event_entity->set($key, $value);
        }
      }

      // Save it!
      $event_entity->save();
    }
  }

}
