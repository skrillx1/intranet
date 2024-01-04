<?php

namespace Drupal\css_grid\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Class for a CSS Grid Layout.
 */
class CssGrid extends LayoutDefault implements PluginFormInterface {

  /**
   * Gap unit options.
   */
  public function gapUnitOptions() {
    return [
      '%' => '%',
      'rem' => 'rem',
      'px' => 'px',
    ];
  }

  /**
   * Default unit options.
   */
  public function defaultUnitOptions() {
    return [
      'fr' => 'fr',
      'auto' => 'auto',
      'max-content' => 'max-content',
      'min-content' => 'min-content',
      'minmax' => 'minmax',
    ] + $this->gapUnitOptions();
  }

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration() {
    return [
      'grid_cells' => '',
      'grid_columns' => [],
      'grid_rows' => [],
      'grid_gap' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['#tree'] = TRUE;
    $form['#prefix'] = '<div class="css-grid-layout-settings">';
    $form['#suffix'] = '</div>';

    $form['grid_columns'] = [
      '#type' => 'details',
      '#title' => 'grid-template-columns',
      '#prefix' => '<div id="css-grid-columns-wrapper">',
      '#suffix' => '</div>',
      '#open' => TRUE,
      '#weight' => 1,
    ];

    if (!$form_state->has('grid_columns')) {
      $form_state->set('grid_columns', $this->configuration['grid_columns']);
    }

    // Build a table for plugin settings.
    $form['grid_columns']['items'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['area-size', 'form-item']],
    ];

    // Retrieve items from form state.
    $column_items = $form_state->get('grid_columns');
    $num_column_items = count($column_items);

    // Create form inputs for each item.
    for ($i = 0; $i < $num_column_items; $i++) {
      $form['grid_columns']['items'][$i] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['input-container', 'grid']],
        'value' => [
          '#type' => 'textfield',
          '#title' => $this->t('Span @index', ['@index' => $i + 1]),
          '#title_display' => 'invisible',
          '#default_value' => $column_items[$i]['value'] ?? 1,
          '#min' => 0,
          '#step' => .5,
        ],
        'unit' => [
          '#type' => 'select',
          '#title' => $this->t('Unit @index', ['@index' => $i + 1]),
          '#title_display' => 'invisible',
          '#default_value' => $column_items[$i]['unit'] ?? 'fr',
          '#options' => $this->defaultUnitOptions(),
        ],
      ];
    }

    $form['grid_columns']['actions'] = [
      '#type' => 'actions',
    ];

    // Add an item button.
    $form['grid_columns']['actions']['add_item'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add column'),
      '#submit' => [[$this, 'addRow']],
      '#ajax' => [
        'callback' => [$this, 'layoutColumnsSettingsCallback'],
        'wrapper' => 'css-grid-columns-wrapper',
      ],
      '#limit_validation_errors' => [],
      '#name' => 'grid_columns',
      '#weight' => 2,
      '#prefix' => '<div class="css-grid-buttons">',
      '#suffix' => '</div>',
    ];

    if ($num_column_items > 1) {
      $form['grid_columns']['actions']['remove_item'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove item'),
        '#submit' => [[$this, 'removeLastRow']],
        '#ajax' => [
          'callback' => [$this, 'layoutColumnsSettingsCallback'],
          'wrapper' => 'css-grid-columns-wrapper',
        ],
        '#limit_validation_errors' => [],
        '#name' => 'grid_columns',
        '#weight' => 1,
        '#prefix' => '<div class="css-grid-buttons">',
        '#suffix' => '</div>',
      ];
    }

    $form['grid_rows'] = [
      '#type' => 'details',
      '#title' => 'grid-template-rows',
      '#prefix' => '<div id="css-grid-rows-wrapper">',
      '#suffix' => '</div>',
      '#open' => TRUE,
      '#weight' => 1,
    ];

    if (!$form_state->has('grid_rows')) {
      $form_state->set('grid_rows', $this->configuration['grid_rows']);
    }

    // Build a table for plugin settings.
    $form['grid_rows']['items'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['area-size', 'form-item']],
    ];

    // Retrieve items from form state.
    $row_items = $form_state->get('grid_rows');
    $num_row_items = count($row_items);

    // Create form inputs for each item.
    for ($i = 0; $i < $num_row_items; $i++) {
      $form['grid_rows']['items'][$i] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['input-container', 'grid']],
        'value' => [
          '#type' => 'textfield',
          '#title' => $this->t('Span @index', ['@index' => $i + 1]),
          '#title_display' => 'invisible',
          '#default_value' => $row_items[$i]['value'] ?? 1,
          '#min' => 0,
          '#step' => .5,
        ],
        'unit' => [
          '#type' => 'select',
          '#title' => $this->t('Unit @index', ['@index' => $i + 1]),
          '#title_display' => 'invisible',
          '#default_value' => $row_items[$i]['unit'] ?? 'fr',
          '#options' => $this->defaultUnitOptions(),
        ],
      ];
    }

    $form['grid_rows']['actions'] = [
      '#type' => 'actions',
    ];

    // Add an item button.
    $form['grid_rows']['actions']['add_item'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add row'),
      '#submit' => [[$this, 'addRow']],
      '#ajax' => [
        'callback' => [$this, 'layoutRowsSettingsCallback'],
        'wrapper' => 'css-grid-rows-wrapper',
      ],
      '#limit_validation_errors' => [],
      '#name' => 'grid_rows',
      '#weight' => 2,
      '#prefix' => '<div class="css-grid-buttons">',
      '#suffix' => '</div>',
    ];

    if ($num_row_items > 1) {
      $form['grid_rows']['actions']['remove_item'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove item'),
        '#submit' => [[$this, 'removeLastRow']],
        '#ajax' => [
          'callback' => [$this, 'layoutRowsSettingsCallback'],
          'wrapper' => 'css-grid-rows-wrapper',
        ],
        '#limit_validation_errors' => [],
        '#name' => 'grid_rows',
        '#weight' => 1,
        '#prefix' => '<div class="css-grid-buttons">',
        '#suffix' => '</div>',
      ];
    }

    $form['grid_gap'] = [
      '#type' => 'details',
      '#title' => $this->t('gap'),
      '#prefix' => '<div id="css-grid-gap-wrapper">',
      '#suffix' => '</div>',
      '#open' => TRUE,
      '#weight' => 3,
    ];

    // Build a table for plugin settings.
    $form['grid_gap']['items'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['area-size', 'form-item']],
    ];

    $form['grid_gap']['items']['row_gap'] = [
      '#type' => 'container',
      '#prefix' => 'row-gap',
      '#attributes' => ['class' => ['input-container', 'input-container--inline']],
      'value' => [
        '#type' => 'number',
        '#default_value' => $this->configuration['grid_gap'][0]['value'] ?? 0,
        '#max' => 20,
        '#min' => 0,
        '#step' => .5,
      ],
      'unit' => [
        '#type' => 'select',
        '#default_value' => $this->configuration['grid_gap'][0]['unit'] ?? 'px',
        '#options' => $this->gapUnitOptions(),
      ],
    ];

    $form['grid_gap']['items']['column_gap'] = [
      '#type' => 'container',
      '#prefix' => $this->t('column-gap'),
      '#attributes' => ['class' => ['input-container', 'input-container--inline']],
      'value' => [
        '#type' => 'number',
        '#default_value' => $this->configuration['grid_gap'][1]['value'] ?? 0,
        '#max' => 20,
        '#min' => 0,
        '#step' => .5,
      ],
      'unit' => [
        '#type' => 'select',
        '#default_value' => $this->configuration['grid_gap'][1]['unit'] ?? 'px',
        '#options' => $this->gapUnitOptions(),
      ],
    ];

    // Attach our form styling.
    $form['#attached']['library'][] = 'css_grid/css_grid';

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (empty($values['grid_columns']['items']) || empty($values['grid_rows']['items']) ) {
      $form_state->setErrorByName('css_grid_items', $this->t('A grid requires at least one column and one row.'));
    }
  }

  /**
   * {@inheritDoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValues();
    // Initialize our grid_cell config.
    $this->configuration['grid_cells'] = '';

    $config_types = ['grid_columns', 'grid_rows'];
    foreach ($values as $key => $value) {
      if (in_array($key, $config_types)) {
        if (isset($value['items'])) {
          $items = $value['items'];
          // Collect grid region section settings.
          $section_settings = [];
          if ($items) {
            for ($i = 0; $i < count($items); $i++) {
              $section_settings[$i]['value'] = $items[$i]['value'];
              $section_settings[$i]['unit'] = $items[$i]['unit'];
              $section_settings[$i]['type'] = $key;
            }
            // Store the processed settings as plugin config.
            $this->configuration[$key] = $section_settings;
            // Calculate and store the number of cells to render.
            if (is_numeric($this->configuration['grid_cells'])) {
              $this->configuration['grid_cells'] *= count($items);
            }
            else {
              $this->configuration['grid_cells'] = count($items);
            }
          }
        }
      }
      if ($key === 'grid_gap') {
        $items = $value['items'];
        $i = 0;
        foreach ($items as $key => $value) {
          $gap_settings[$i]['value'] = $value['value'];
          $gap_settings[$i]['unit'] = $value['unit'];
          $gap_settings[$i]['type'] = $key;
          $i++;
        }
        $this->configuration['grid_gap'] = $gap_settings;
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public function build(array $regions) {
    $this->setPluginDefinitionRegions();
    $build = parent::build($regions);

    $build['#attributes'] = [
      'class' => ['css-grid-layout'],
      'style' => [
        'display: grid;',
        'grid-template-columns: ' . $this->computeGridProperties('grid_columns') . ';',
        'grid-template-rows: ' . $this->computeGridProperties('grid_rows') . ';',
        'gap: ' . $this->computeGridProperties('grid_gap') . ';',
      ],
    ];

    return $build;
  }

  /**
   * Set our dynamic block regions.
   */
  protected function setPluginDefinitionRegions() {
    $regionMap = [];

    $grid_cells = $this->configuration['grid_cells'];
    foreach (range(1, $grid_cells) as $i) {
      $regionMap['content_' . $i] = [
        'label' => $this->t('Content @i', ['@i' => $i]),
      ];
    }
    $this->pluginDefinition->setRegions($regionMap);
  }

  /**
   * Compute the grid properties.
   *
   * @param string $property
   *   The grid config property.
   */
  protected function computeGridProperties($property) {
    $grid_property = $this->configuration[$property];
    if (is_array($grid_property)) {
      array_walk($grid_property, function (&$value) {
        if (is_array($value)) {
          switch ($value['unit']) {
            case 'auto':
            case 'min-content':
            case 'max-content':
              $value = $value['unit'];
              break;

            case 'minmax':
              $value = $value['unit'] . '(' . $value['value'] . ')';
              break;

            default:
              $value = $value['value'] . $value['unit'];
              break;
          }
        }
      });

      return implode(' ', $grid_property);
    }
  }

  /**
   * Remove last row.
   *
   * @param array $form
   *   The array form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The FormStateInterface.
   */
  public function removeLastRow(array &$form, FormStateInterface $form_state) {
    $trigger_element = $form_state->getTriggeringElement();
    $type = $trigger_element['#name'];

    $items = [];
    if ($form_state->has($type)) {
      $items = $form_state->get($type);
      array_pop($items);
    }
    $form_state->set($type, $items);
    $form_state->setRebuild();
  }

  /**
   * Add new row.
   *
   * @param array $form
   *   The array form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The FormStateInterface.
   */
  public function addRow(array &$form, FormStateInterface $form_state) {
    $trigger_element = $form_state->getTriggeringElement();
    $type = $trigger_element['#name'];

    $items = [];
    if ($form_state->has($type)) {
      $items = $form_state->get($type);
      $nextItem = count($items);
      $items[$nextItem]['type'] = $type;
    }
    else {
      $nextItem = count($items);
      $items[$nextItem];
    }

    $form_state->set($type, $items);
    $form_state->setRebuild();
  }

  /**
   * Layout settings callback.
   *
   * @param array $form
   *   The array form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The FormStateInterface.
   *
   * @return array
   *   Return the layout settings form.
   */
  public function layoutColumnsSettingsCallback(array &$form, FormStateInterface $form_state) {
    return $form['layout_settings']['grid_columns'];
  }

  /**
   * Layout settings callback.
   *
   * @param array $form
   *   The array form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The FormStateInterface.
   *
   * @return array
   *   Return the layout settings form.
   */
  public function layoutRowsSettingsCallback(array &$form, FormStateInterface $form_state) {
    return $form['layout_settings']['grid_rows'];
  }

}
