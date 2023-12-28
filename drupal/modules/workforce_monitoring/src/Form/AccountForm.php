<?php

namespace Drupal\workforce_monitoring\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Date form.
 */
class AccountForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'workforce_monitoring_account_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get the list of accounts from somewhere (replace this with your actual data source).
    $account_options = [
      '' => $this->t('All Account'),
      'Admin Maintenance' => $this->t('Admin Maintenance'),
      'ALEVA' => $this->t('ALEVA'),
      'APA Financial' => $this->t('APA Financial'),
      'BlueRyse' => $this->t('BlueRyse'),
      'CareMedica' => $this->t('CareMedica'),
      'CareMedica Florida' => $this->t('CareMedica Florida'),
      'Custom Offsets' => $this->t('Custom Offsets'),
      'Decks Direct' => $this->t('Decks Direct'),
      'Development (R&D)' => $this->t('Development (R&D)'),
      'DM TAS' => $this->t('DM TAS'),
      'Everything Kitchen' => $this->t('Everything Kitchen'),
      'Filter Water' => $this->t('Filter Water'),
      'FUJI Xerox' => $this->t('FUJI Xerox'),
      'HIFI CS' => $this->t('HIFI CS'),
      'HIFI DE' => $this->t('HIFI DE'),
      'Holland & Bulbs' => $this->t('Holland & Bulbs'),
      'Ideal Tech - Amazon' => $this->t('Ideal Tech - Amazon'),
      'Ideal Tech - Boca General' => $this->t('Ideal Tech - Boca General'),
      'Ideal Tech - CFTHH' => $this->t('Ideal Tech - CFTHH'),
      'Ideal Tech - FIMDA' => $this->t('Ideal Tech - FIMDA'),
      'Ideal Tech - Greenwich Med' => $this->t('Ideal Tech - Greenwich Med'),
      'Intern' => $this->t('Intern'),
      'IRP' => $this->t('IRP'),
      'Kelly Andersons Group' => $this->t('Kelly Andersons Group'),
      'Managers' => $this->t('Managers'),
      'MAPerformance' => $this->t('MAPerformance'),
      'Mass Depot' => $this->t('Mass Depot'),
      'Mass Depot CS' => $this->t('Mass Depot CS'),
      'MCM Logistics' => $this->t('MCM Logistics'),
      'My Memory' => $this->t('My Memory'),
      'Onogo UK' => $this->t('Onogo UK'),
      'PBIM' => $this->t('PBIM'),
      'Power Meter City' => $this->t('Power Meter City'),
      'Quality Assurance' => $this->t('Quality Assurance'),
      'RSI' => $this->t('RSI'),
      'SnowJoe DE' => $this->t('SnowJoe DE'),
      'SnowJoe ECT' => $this->t('SnowJoe ECT'),
      'SnowJoe CSR' => $this->t('SnowJoe CSR'),
      'Stamina' => $this->t('Stamina'),
      'Supervisors' => $this->t('Supervisors'),
      'The Lamp Stand' => $this->t('The Lamp Stand'),
      'UNIMED' => $this->t('UNIMED'),
      'KURU' => $this->t('KURU'),
      'Hearth Song' => $this->t('Hearth Song'),
      'Cruise Web' => $this->t('Cruise Web'),
      'Popov Leather' => $this->t('Popov Leather'),
      'Ideal Tech - Heart Wellness Group' => $this->t('Ideal Tech - Heart Wellness Group'),
    ];

    $form['account_select'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Account'),
      '#options' => $account_options,
      '#default_value' => $form_state->getValue('account_select', ''),
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
    // Handle form submission if needed.
    \Drupal::state()->set('workforce_monitoring_selected_account', $form_state->getValue('account_select'));
  }
}
