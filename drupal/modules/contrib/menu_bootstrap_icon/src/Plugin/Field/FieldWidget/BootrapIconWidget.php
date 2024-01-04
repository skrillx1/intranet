<?php

namespace Drupal\menu_bootstrap_icon\Plugin\Field\FieldWidget;

use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;

/**
 * Plugin implementation of the 'link' widget.
 *
 * @FieldWidget(
 *   id = "bootstrap_icon_link",
 *   label = @Translation("Link (with bootstrap icon)"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class BootrapIconWidget extends LinkWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'icon' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $config_factory = \Drupal::configFactory();
    $config = $config_factory->getEditable('menu_bootstrap_icon.settings');
    $element = parent::settingsForm($form, $form_state);
    $iconDefault = $this->getSetting('icon') ?? '';
    $element['icon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Icon'),
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

    if ($config->get('use_cdn')) {
      $element['#attached']['library'][] = 'menu_bootstrap_icon/cdn';
    }
    $element['#attached']['library'][] = 'menu_bootstrap_icon/iconspicker';
    if (!empty($searchList = $config->get('search_list'))) {
      if (is_string($searchList)) {
        $searchList = Yaml::decode($searchList);
      }
      $element['#attached']['drupalSettings']['menu_bootstrap_icon']['icons'] = $searchList;
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $config_factory = \Drupal::configFactory();
    $config = $config_factory->getEditable('menu_bootstrap_icon.settings');
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $id = Html::getUniqueId('bootstrap-link-' . $this->fieldDefinition->getName() . '-icon');
    $item = $items[$delta];
    $options = $item->get('options')->getValue();
    $attributes = $options['attributes'] ?? [];
    $iconDefault = $attributes['data-icon'] ?? $this->getSetting('icon');
    $element['options']['attributes']['data-icon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icon class'),
      '#id' => $id,
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

    if ($config->get('use_cdn')) {
      $form['#attached']['library'][] = 'menu_bootstrap_icon/cdn';
    }
    $form['#attached']['library'][] = 'menu_bootstrap_icon/iconspicker';
    if (!empty($searchList = $config->get('search_list'))) {
      if (is_string($searchList)) {
        $searchList = Yaml::decode($searchList);
      }
      $form['#attached']['drupalSettings']['menu_bootstrap_icon']['icons'] = $searchList;
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    if ($icon = $this->getSetting('icon')) {
      $defaulText = $this->t('Default icon:');
      $summary[] = [
        '#markup' => $defaulText . ' <i class="' . $icon . '"></i>',
      ];
    }
    return $summary;
  }

}
