<?php
/*-------------------------------------------------------+
| Ilja's Input Validation Extension                      |
| Amnesty International Vlaanderen                       |
| Copyright (C) 2017 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

declare(strict_types = 1);

use CRM_I3val_ExtensionUtil as E;

define('MAX_CONFIG_COUNT', 10);

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_I3val_Form_Configuration extends CRM_Core_Form {

  protected static $_activityTypes = NULL;
  protected static $_class2label = NULL;

  public function buildQuickForm() {

    // add form elements
    $this->add(
      'select',
      'session_ttl',
      E::ts('Session Timeout'),
      $this->getTTLOptions(),
      TRUE
    );

    $this->add(
      'select',
      'session_size',
      E::ts('Session Batch Size'),
      $this->getBatchOptions(),
      TRUE
    );

    $this->add(
      'select',
      'quickhistory',
      E::ts('Quick History'),
      $this->getActivityTypes(),
      FALSE,
      ['class' => 'crm-select2 huge', 'multiple' => 'multiple']
    );

    $this->add(
        'checkbox',
        'allow_clearing',
        E::ts('Allow Clearing Fields')
    );

    $this->add(
      'text',
      'strip_chars',
      E::ts('Input Trim Characters')
    );

    $this->add(
      'text',
      'empty_token',
      E::ts('Empty String Token')
    );

    $this->add(
      'select',
      'flag_status',
      E::ts('Flaged Request Status'),
      $this->getActivityStatusList(),
      TRUE
    );

    $this->add(
      'select',
      'default_action',
      E::ts('Default Action'),
      $this->getDefaultActionList(),
      TRUE
    );

    // add activity configurations
    for ($i = 1; $i <= 10; $i++) {
      $this->add(
        'select',
        "activity_type_id_{$i}",
        E::ts('Activity Type'),
        $this->getActivityTypes(),
        FALSE,
        ['class' => 'huge']
      );

      $this->add(
        'select',
        "handler_classes_{$i}",
        E::ts('Handlers'),
        $this->getHandlerClasses(),
        FALSE,
        ['class' => 'crm-select2 huge', 'multiple' => 'multiple']
      );
    }
    $this->assign('configurations', range(1, MAX_CONFIG_COUNT));
    $this->assign('configuration_count', MAX_CONFIG_COUNT);

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts('Save'),
        'isDefault' => TRUE,
      ],
    ]);

    // let's add some style...
    CRM_Core_Resources::singleton()->addStyleFile('be.aivl.i3val', 'css/i3val.css');

    parent::buildQuickForm();
  }

  /**
   * Getter for $_defaultValues.
   *
   * @return array
   */
  public function setDefaultValues() {
    $current_config = CRM_I3val_Configuration::getRawConfig();

    // explode configurations
    $configurations = $current_config['configurations'] ?? [];
    $i = 1;
    foreach ($configurations as $configuration) {
      $current_config["activity_type_id_{$i}"] = $configuration['activity_type_id'];
      $current_config["handler_classes_{$i}"]  = $configuration['handlers'];
      $i += 1;
    }

    return $current_config;
  }

  /**
   * update the configuration
   *
   * phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
   */
  public function postProcess() {
    $values = $this->exportValues();

    // read the raw config blob
    $current_config = CRM_I3val_Configuration::getRawConfig();

    $current_config['session_ttl']    = $values['session_ttl'] ?? $current_config['session_ttl'] ?? NULL;
    $current_config['default_action'] = $values['default_action'] ?? $current_config['default_action'] ?? NULL;
    $current_config['session_size']   = $values['session_size'] ?? $current_config['session_size'] ?? NULL;
    $current_config['strip_chars']    = $values['strip_chars'] ?? $current_config['strip_chars'] ?? NULL;
    $current_config['empty_token']    = $values['empty_token'] ?? $current_config['empty_token'] ?? NULL;
    $current_config['flag_status']    = $values['flag_status'] ?? $current_config['flag_status'] ?? NULL;
    $current_config['quickhistory']   = $values['quickhistory'] ?? $current_config['quickhistory'] ?? NULL;
    $current_config['allow_clearing'] = $values['allow_clearing'] ?? FALSE;

    // extract configurations
    $configurations = [];
    for ($i = 1; $i <= MAX_CONFIG_COUNT; $i++) {
      $activity_type_id = $values["activity_type_id_{$i}"] ?? NULL;
      if ($activity_type_id) {
        $configurations[] = [
          'activity_type_id' => $activity_type_id,
          'handlers'         => $values["handler_classes_{$i}"] ?? [],
        ];
      }
    }
    $current_config['configurations'] = $configurations;

    // write the raw config blob
    CRM_I3val_Configuration::setRawConfig($current_config);

    // update the custom groups
    CRM_I3val_Configuration::synchroniseCustomFields();

    CRM_Core_Session::setStatus(
      E::ts('I3Val configuration was updated successfully.'),
      E::ts('Configuration Saved'),
      'info'
    );

    parent::postProcess();
  }

  /**
   * get some predefined TTL values
   */
  protected function getTTLOptions() {
    return [
      '1 hour'  => E::ts('1 hour'),
      '2 hour'  => E::ts('2 hours'),
      '4 hours' => E::ts('4 hours'),
      '8 hours' => E::ts('8 hours'),
      '1 day'   => E::ts('1 day'),
    ];
  }

  /**
   * get some predefined TTL values
   */
  protected function getBatchOptions() {
    return [
      '25' => E::ts('regular (25'),
      '50' => E::ts('big (50)'),
      '10' => E::ts('small (10)'),
      '1'  => E::ts('single'),
    ];
  }

  /**
   * get the list of options as the default action
   */
  protected function getDefaultActionList() {
    return [
      'detect'     => E::ts('Auto'),
      'add'        => E::ts('Create New'),
      'overwrite'  => E::ts('Overwrite'),
      'ignore'     => E::ts('Do Nothing'),
    ];
  }

  /**
   * get a list of activity status options
   */
  protected function getActivityStatusList() {
    $status_list = [];

    $option_values = civicrm_api3('OptionValue', 'get', [
      'option_group_id' => 'activity_status',
      'option.limit'    => 0,
      'is_active'       => 1,
      'return'          => 'value,label',
      'sequential'      => 1,
    ]);
    foreach ($option_values['values'] as $value) {
      $status_list[$value['value']] = $value['label'];
    }
    return $status_list;
  }

  /**
   * get a list of activity status options
   */
  protected function getActivityTypes() {
    if (self::$_activityTypes === NULL) {
      $types_list = [
        '' => E::ts('-disabled-'),
      ];

      $option_values = civicrm_api3('OptionValue', 'get', [
        'option_group_id' => 'activity_type',
        'option.limit'    => 0,
        'is_active'       => 1,
        'return'          => 'value,label',
        'sequential'      => 1,
      ]);
      foreach ($option_values['values'] as $value) {
        $types_list[$value['value']] = $value['label'];
      }
      self::$_activityTypes = $types_list;
    }
    return self::$_activityTypes;
  }

  /**
   * Get the list of known handlers
   */
  protected function getHandlerClasses() {
    if (self::$_class2label === NULL) {
      $class_candidates = [];
      $paths = explode(PATH_SEPARATOR, get_include_path());
      foreach ($paths as $path) {
        $folder = $path . 'CRM/I3val/Handler';

        if (is_dir($folder)) {
          $files = scandir($folder);
          foreach ($files as $file) {
            if (preg_match('#(?P<class>^[a-z]+)[.]php$#i', $file, $match)) {
              $class_name = "CRM_I3val_Handler_{$match['class']}";
              $class_candidates[] = $class_name;
            }
          }
        }
      }

      // verify our finds
      $class2label = [];

      foreach ($class_candidates as $class_name) {
        try {
          $class = new ReflectionClass($class_name);
          if ($class->isSubclassOf('CRM_I3val_ActivityHandler') && !$class->isAbstract()) {
            /** @var \CRM_I3val_ActivityHandler $handler */
            $handler = new $class_name();
            $class2label[$class_name] = $handler->getName();
          }
        }
        catch (Error $e) {
          // @ignoreException
          // couldn't instantiate, ignore
        }
      }

      self::$_class2label = $class2label;
    }

    return self::$_class2label;
  }

}
