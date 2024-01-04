<?php

namespace Drupal\google_calendar_service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Google Calendar entities.
 *
 * @ingroup calendar
 */
class CalendarListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    $header['id'] = $this->t('Google Calendar ID');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\google_calendar_service\Entity\GoogleCalendar */
    $url = Url::fromRoute('entity.gcs_calendar.canonical', ['gcs_calendar' => $entity->id()]);
    $row['name'] = Link::fromTextAndUrl($this->t($entity->label()), $url);
    $row['id'] = $entity->getGoogleCalendarId();

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = [];
    if ($entity->access('update') &&
        $entity->hasLinkTemplate('edit-form')) {

      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'weight' => 10,
        'url' => $entity->toUrl('edit-form'),
      ];
    }
    if ($entity->access('delete') &&
        $entity->hasLinkTemplate('delete-form')) {

      $operations['delete'] = [
        'title' => $this->t('Delete'),
        'weight' => 100,
        'url' => $entity->toUrl('delete-form'),
      ];
    }

    $operations['import'] = [
      'title' => $this->t('Import Events'),
      'weight' => 15,
      'url' => Url::fromRoute(
        'google_calendar_service.import_controller',
        [
          'calendar' => $entity->id(),
        ]
      ),
    ];

    $operations['events'] = [
      'title' => t('List Events'),
      'weight' => 15,
      'url' => Url::fromUserInput(
        '/calendar/' . $entity->id() . '/events'
      ),
    ];

    return $operations;
  }

}
