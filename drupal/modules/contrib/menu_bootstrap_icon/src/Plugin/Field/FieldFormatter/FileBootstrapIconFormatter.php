<?php

namespace Drupal\menu_bootstrap_icon\Plugin\Field\FieldFormatter;

use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'file_micon' formatter.
 *
 * @FieldFormatter(
 *   id = "file_bootstrap_icon",
 *   label = @Translation("Bootstrap icon file"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class FileBootstrapIconFormatter extends FileFormatterBase {

  /**
   * FileUrlGenerator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a FileDownloadLink object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   FileUrlGenerator service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Configuration factory.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, FileUrlGeneratorInterface $file_url_generator, ConfigFactoryInterface $config_factory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->fileUrlGenerator = $file_url_generator;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('file_url_generator'),
      $container->get('config.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = [
      'icon' => 'bi-file-earmark',
      'position' => 'before',
      'target' => FALSE,
      'google_viewer' => TRUE,
    ];
    return $settings + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if ($position = $this->getSetting('position')) {
      $summary[] = $this->t('Icon position: @value', ['@value' => Unicode::ucfirst($position)]);
    }
    if ($this->getSetting('google_viewer')) {
      $summary[] = $this->t('View file with google viewer');
    }
    if ($this->getSetting('target')) {
      $summary[] = $this->t('Open link in new window');
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
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
      '#empty_option' => $this->t('Not set'),
      '#default_value' => $this->getSetting('position'),
    ];
    $elements['target'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open link in new window'),
      '#return_value' => '_blank',
      '#default_value' => $this->getSetting('target'),
      '#states' => [
        'invisible' => [
          ':input[name*="text_only"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $elements['google_viewer'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("View file with google viewer"),
      '#default_value' => $this->getSetting('google_viewer'),
    ];
    $config = $this->configFactory->getEditable('menu_bootstrap_icon.settings');
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
    $elements = [];
    $iconDefault = $this->getSetting('icon');
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
      $options = [];
      if ($this->getSetting('target')) {
        $options['attributes']['target'] = '_blank';
      }
      $link_text = $file->getFilename();
      $path_parts = pathinfo($link_text);
      $ext = $path_parts['extension'];
      $position = $this->getSetting('position');
      $mime = $file->getMimeType();
      $iconClass = $this->getIconClass($ext, $mime);
      if (!$iconClass) {
        $iconClass = $iconDefault;
      }
      else {
        $iconClass = 'bi bi-' . $iconClass;
      }
      $googledocExtension = ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
      if ($this->getSetting('google_viewer') && in_array($ext, $googledocExtension)) {
        $url = 'https://docs.google.com/gview?embedded=true&url=' . $url;
      }
      $elements[$delta] = Link::fromTextAndUrl($link_text, Url::fromUri($url, $options))
        ->toRenderable();

      switch ($position) {
        case 'before':
          $elements[$delta]['#prefix'] = '<i class="' . $iconClass . '"></i>';
          break;

        case 'after':
          $elements[$delta]['#suffix'] = '<i class="' . $iconClass . '"></i>';
          break;

        case 'icon_only':
          $elements[$delta]['#title'] = [
            '#type' => 'html_tag',
            '#tag' => 'i',
            '#attributes' => [
              'class' => ['bi', 'bi-' . $iconClass],
              'title' => $link_text,
              'data-bs-toggle' => "tooltip",
            ],
          ];
          break;

      }
      $elements[$delta]['#cache']['tags'] = $file->getCacheTags();
    }

    return $elements;
  }

  /**
   * Mime icon options.
   */
  protected function mimeMap($mime_type) {
    switch ($mime_type) {
      // Image types.
      case 'image/jpeg':
      case 'image/png':
      case 'image/gif':
      case 'image/bmp':
        return 'file-earmark-image';

      // Word document types.
      case 'application/msword':
      case 'application/vnd.ms-word.document.macroEnabled.12':
      case 'application/vnd.oasis.opendocument.text':
      case 'application/vnd.oasis.opendocument.text-template':
      case 'application/vnd.oasis.opendocument.text-master':
      case 'application/vnd.oasis.opendocument.text-web':
      case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
      case 'application/vnd.stardivision.writer':
      case 'application/vnd.sun.xml.writer':
      case 'application/vnd.sun.xml.writer.template':
      case 'application/vnd.sun.xml.writer.global':
      case 'application/vnd.wordperfect':
      case 'application/x-abiword':
      case 'application/x-applix-word':
      case 'application/x-kword':
      case 'application/x-kword-crypt':
        return 'file-earmark-word';

      // Spreadsheet document types.
      case 'application/vnd.ms-excel':
      case 'application/vnd.ms-excel.sheet.macroEnabled.12':
      case 'application/vnd.oasis.opendocument.spreadsheet':
      case 'application/vnd.oasis.opendocument.spreadsheet-template':
      case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
      case 'application/vnd.stardivision.calc':
      case 'application/vnd.sun.xml.calc':
      case 'application/vnd.sun.xml.calc.template':
      case 'application/vnd.lotus-1-2-3':
      case 'application/x-applix-spreadsheet':
      case 'application/x-gnumeric':
      case 'application/x-kspread':
      case 'application/x-kspread-crypt':
        return 'file-earmark-spreadsheet';

      // Presentation document types.
      case 'application/vnd.ms-powerpoint':
      case 'application/vnd.ms-powerpoint.presentation.macroEnabled.12':
      case 'application/vnd.oasis.opendocument.presentation':
      case 'application/vnd.oasis.opendocument.presentation-template':
      case 'application/vnd.openxmlformats-officedocument.presentationml.presentation':
      case 'application/vnd.stardivision.impress':
      case 'application/vnd.sun.xml.impress':
      case 'application/vnd.sun.xml.impress.template':
      case 'application/x-kpresenter':
        return 'file-earmark-ppt';

      // Compressed archive types.
      case 'application/zip':
      case 'application/x-zip':
      case 'application/stuffit':
      case 'application/x-stuffit':
      case 'application/x-7z-compressed':
      case 'application/x-ace':
      case 'application/x-arj':
      case 'application/x-bzip':
      case 'application/x-bzip-compressed-tar':
      case 'application/x-compress':
      case 'application/x-compressed-tar':
      case 'application/x-cpio-compressed':
      case 'application/x-deb':
      case 'application/x-gzip':
      case 'application/x-java-archive':
      case 'application/x-lha':
      case 'application/x-lhz':
      case 'application/x-lzop':
      case 'application/x-rar':
      case 'application/x-rpm':
      case 'application/x-tzo':
      case 'application/x-tar':
      case 'application/x-tarz':
      case 'application/x-tgz':
        return 'file-earmark-zip';

      // Script file types.
      case 'application/ecmascript':
      case 'application/javascript':
      case 'application/mathematica':
      case 'application/vnd.mozilla.xul+xml':
      case 'application/x-asp':
      case 'application/x-awk':
      case 'application/x-cgi':
      case 'application/x-csh':
      case 'application/x-m4':
      case 'application/x-perl':
      case 'application/x-php':
      case 'application/x-ruby':
      case 'application/x-shellscript':
      case 'text/vnd.wap.wmlscript':
      case 'text/x-emacs-lisp':
      case 'text/x-haskell':
      case 'text/x-literate-haskell':
      case 'text/x-lua':
      case 'text/x-makefile':
      case 'text/x-matlab':
      case 'text/x-python':
      case 'text/x-sql':
      case 'text/x-tcl':
        return 'file-earmark-code';

      // HTML aliases.
      case 'application/xhtml+xml':
        return 'filetype-html';

      // Executable types.
      case 'application/x-macbinary':
      case 'application/x-ms-dos-executable':
      case 'application/x-pef-executable':
        return 'filetype-exe';

      // Acrobat types.
      case 'application/pdf':
      case 'application/x-pdf':
      case 'applications/vnd.pdf':
      case 'text/pdf':
      case 'text/x-pdf':
        return 'file-earmark-pdf';

      default:
        return FALSE;
    }
  }

  /**
   * {@inheritDoc}
   */
  private function getIconClass(string $ext, $mime) {
    $filetype = [
      'aac', 'm4p', 'mp3', 'mp4', 'mov', 'wav',
      'ai', 'bmp', 'gif', 'jpg', 'png', 'psd', 'svg', 'raw',
      'cs', 'css', 'scss', 'sass', 'html', 'otf', 'woff', 'ttf',
      'csv', 'xls', 'xlsx', 'ppt', 'pptx', 'sql',
      'doc', 'docx', 'txt', 'pdf',
      'heic', 'java', 'js', 'json', 'jsx', 'php', 'py', 'rb',
      'key', 'md', 'mdx',
      'tiff', 'tsx',
      'xml', 'yml',
      'exe', 'sh',
    ];
    if (in_array($ext, $filetype)) {
      return 'filetype-' . $ext;
    }
    return $this->mimeMap($mime);
  }

}
