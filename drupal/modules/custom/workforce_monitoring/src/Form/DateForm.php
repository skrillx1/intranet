<?php

namespace Drupal\workforce_monitoring\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Date form.
 */
class DateForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'workforce_monitoring_date_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['date'] = [
      '#type' => 'date',
      '#title' => $this->t('Select Date'),
      '#default_value' => \Drupal::request()->query->get('date') ?? date('Y-m-d'),
    ];

    $form['account'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Account'),
      '#options' => [
        '' => 'All Account',
        'Admin Maintenance' => 'Admin Maintenance',
        'ALEVA' => 'ALEVA',
        'APA Financial' => 'APA Financial',
        'BlueRyse' => 'BlueRyse',
        'CareMedica' => 'CareMedica',
        'CareMedica Florida' => 'CareMedica Florida',
        'Custom Offsets' => 'Custom Offsets',
        'Decks Direct' => 'Decks Direct',
        'Development (R&D)' => 'Development (R&D)',
        'DM TAS' => 'DM TAS',
        'Everything Kitchen' => 'Everything Kitchen',
        'Filter Water' => 'Filter Water',
        'FUJI Xerox' => 'FUJI Xerox',
        'HIFI CS' => 'HIFI CS',
        'HIFI DE' => 'HIFI DE',
        'Holland & Bulbs' => 'Holland & Bulbs',
        'Ideal Tech - Amazon' => 'Ideal Tech - Amazon',
        'Ideal Tech - Boca General' => 'Ideal Tech - Boca General',
        'Ideal Tech - CFTHH' => 'Ideal Tech - CFTHH',
        'Ideal Tech - FIMDA' => 'Ideal Tech - FIMDA',
        'Ideal Tech - Greenwich Med' => 'Ideal Tech - Greenwich Med',
        'Intern' => 'Intern',
        'IRP' => 'IRP',
        'Kelly Andersons Group' => 'Kelly Andersons Group',
        'Managers' => 'Managers',
        'MAPerformance' => 'MAPerformance',
        'Mass Depot' => 'Mass Depot',
        'Mass Depot CS' => 'Mass Depot CS',
        'MCM Logistics' => 'MCM Logistics',
        'My Memory' => 'My Memory',
        'Onogo UK' => 'Onogo UK',
        'PBIM' => 'PBIM',
        'Power Meter City' => 'Power Meter City',
        'Quality Assurance' => 'Quality Assurance',
        'RSI' => 'RSI',
        'SnowJoe DE' => 'SnowJoe DE',
        'SnowJoe ECT' => 'SnowJoe ECT',
        'SnowJoe CSR' => 'SnowJoe CSR',
        'Stamina' => 'Stamina',
        'Supervisors' => 'Supervisors',
        'The Lamp Stand' => 'The Lamp Stand',
        'UNIMED' => 'UNIMED',
        'KURU' => 'KURU',
        'Hearth Song' => 'Hearth Song',
        'Cruise Web' => 'Cruise Web',
        'Popov Leather' => 'Popov Leather',
        'Ideal Tech - Heart Wellness Group' => 'Ideal Tech - Heart Wellness Group',
      ],
      '#default_value' => null,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Set the selected date in the session for later use.
    \Drupal::state()->set('workforce_monitoring_selected_date', $form_state->getValue('date'));
    \Drupal::state()->set('workforce_monitoring_selected_account', $form_state->getValue('account'));
  }
}
