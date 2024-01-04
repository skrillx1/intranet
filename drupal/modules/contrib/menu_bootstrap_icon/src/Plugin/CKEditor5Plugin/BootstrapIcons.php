<?php

namespace Drupal\menu_bootstrap_icon\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\EditorInterface;

/**
 * CKEditor 5 Bootstrap Icon plugin.
 */
class BootstrapIcons extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface {

  use CKEditor5PluginConfigurableTrait;

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration() {
    return [
      'cdn_bootstrap' => FALSE,
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['cdn_bootstrap'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Icon bootstrap CDN'),
      '#description' => $this->t("Enable if your admin theme does not support icons like <a href='https://www.drupal.org/project/bootstrap5_admin'>bootstrap 5 admin</a> theme"),
      '#default_value' => $this->configuration['cdn_bootstrap'] ?? FALSE,
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['cdn_bootstrap'] = (boolean) $form_state->getValue('cdn_bootstrap') ?? FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * Get search list bootstrap icon in editor config.
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    $config_factory = \Drupal::configFactory();
    $config = $config_factory->getEditable('menu_bootstrap_icon.settings');
    $searchList = $config->get('search_list');

    if (is_string($searchList)) {
      $searchList = Yaml::decode($searchList);
    }
    $cdn = FALSE;
    if (!empty($this->configuration['cdn_bootstrap'])) {
      $library_discovery = \Drupal::service('library.discovery');
      $library_info = $library_discovery->getLibraryByName('menu_bootstrap_icon', 'icons');
      $cdn = $library_info["css"][0]["data"];
    }
    return [
      'bootstrapicons' => [
        'search_list' => $searchList,
        'cdn' => $cdn,
      ],
    ];
  }

}
