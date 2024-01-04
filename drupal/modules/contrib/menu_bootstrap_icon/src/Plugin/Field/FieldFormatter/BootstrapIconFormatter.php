<?php

namespace Drupal\menu_bootstrap_icon\Plugin\Field\FieldFormatter;

use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;

/**
 * Plugin implementation of the 'bootstrap_icon_link' formatter.
 *
 * @FieldFormatter(
 *   id = "bootstrap_icon_link",
 *   label = @Translation("Link (with bootstrap icon)"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class BootstrapIconFormatter extends LinkFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'icon' => '',
      'position' => 'before',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $settings = $this->getSettings();
    if ($icon = $this->getSetting('icon')) {
      $defaulText = $this->t('Default icon:');
      $summary[] = [
        '#markup' => $defaulText . ' <i class="' . $icon . '"></i>',
      ];
    }

    if (!empty($settings['position'])) {
      $summary[] = $this->t('Icon position: @value', ['@value' => Unicode::ucfirst($settings['position'])]);
    }
    if (!empty($settings['target'])) {
      $summary[] = $this->t('Open link in new window');
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $config_factory = \Drupal::configFactory();
    $config = $config_factory->getEditable('menu_bootstrap_icon.settings');
    $elements = parent::settingsForm($form, $form_state);
    $iconDefault = $this->getSetting('icon');
    $elements['icon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icon default'),
      '#default_value' => $iconDefault,
      '#description' => '<i class="icon-preview ' . $iconDefault . '"></i>',
      '#attributes' => [
        'class' => [
          'iconpicker',
          'w-auto',
        ],
      ],
      '#wrapper_attributes' => [
        'class' => ['d-flex', 'align-items-center'],
      ],
    ];
    $elements['position'] = [
      '#type' => 'select',
      '#title' => $this->t('Icon position'),
      '#options' => [
        'before' => $this->t('Before'),
        'after' => $this->t('After'),
        'icon_only' => $this->t('Icon only'),
      ],
      '#default_value' => $this->getSetting('position'),
      '#required' => TRUE,
      '#weight' => -10,
    ];

    if ($config->get('use_cdn')) {
      $elements['#attached']['library'][] = 'menu_bootstrap_icon/cdn';
    }
    $elements['#attached']['library'][] = 'menu_bootstrap_icon/iconspicker';
    if (!empty($searchList = $config->get('search_list'))) {
      if (is_string($searchList)) {
        $searchList = Yaml::decode($searchList);
      }
      $elements['#attached']['drupalSettings']['menu_bootstrap_icon']['icons'] = $searchList;
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = parent::viewElements($items, $langcode);
    $default_position = $this->getSetting('position');
    foreach ($element as &$item) {
      $icon = $this->getSetting('icon');
      if (!$icon && !empty($item['#options']['attributes']['data-icon'])) {
        $icon = $item['#options']['attributes']['data-icon'];
      }
      if ($icon) {
        $position = !empty($item['#options']['attributes']['data-icon-position']) ? $item['#options']['attributes']['data-icon-position'] : $default_position;
        switch ($position) {
          // Display after label:
          case 'after':
            $item['#title'] = [
              ['#markup' => $item['#title'] . '<i class="' . $icon . '"></i>'],
            ];
            break;

          // Display icon only:
          case 'icon_only':
            $item['#title'] = [
              '#markup' => '<i class="' . $icon . '"></i>',
            ];
            break;

          default:
            $item['#title'] = [
              ['#markup' => '<i class="' . $icon . '"></i>' . $item['#title']],
            ];
            break;

        }
        unset($item['#options']['attributes']['data-icon']);
      }
    }
    return $element;
  }

}
