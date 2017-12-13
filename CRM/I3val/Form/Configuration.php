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
      E::ts("Timeout"),
      $this->getTTLOptions(),
      TRUE
    );

    $this->add(
      'select',
      'session_size',
      E::ts("Batch Size"),
      $this->getBatchOptions(),
      TRUE
    );

    $this->add(
      'select',
      'quickhistory',
      E::ts("Quick History"),
      $this->getActivityTypes(),
      FALSE,
      array('class' => 'crm-select2 huge', 'multiple' => 'multiple')
    );

    $this->add(
      'text',
      'strip_chars',
      E::ts("Input Trim Characters")
    );

    $this->add(
      'text',
      'empty_token',
      E::ts("Empty String Token")
    );

    $this->add(
      'select',
      'flag_status',
      E::ts("Flaged Request Status"),
      $this->getActivityStatusList(),
      TRUE
    );

    // add activity configurations
    for ($i=1; $i <= 10; $i++) {
      $this->add(
        'select',
        "activity_type_id_{$i}",
        E::ts("Activity Type"),
        $this->getActivityTypes(),
        FALSE,
        array('class' => 'huge')
      );

      $this->add(
        'select',
        "handler_classes_{$i}",
        E::ts("Handlers"),
        $this->getHandlerClasses(),
        FALSE,
        array('class' => 'crm-select2 huge', 'multiple' => 'multiple')
      );
    }
    $this->assign('configurations', range(1, MAX_CONFIG_COUNT));
    $this->assign('configuration_count', MAX_CONFIG_COUNT);

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Save'),
        'isDefault' => TRUE,
      ),
    ));

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
    $configurations =CRM_Utils_Array::value('configurations', $current_config, array());
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
   */
  public function postProcess() {
    $values = $this->exportValues();

    // read the raw config blob
    $current_config = CRM_I3val_Configuration::getRawConfig();

    $current_config['session_ttl']  = CRM_Utils_Array::value('session_ttl',  $values, CRM_Utils_Array::value('session_ttl',  $current_config));
    $current_config['session_size'] = CRM_Utils_Array::value('session_size', $values, CRM_Utils_Array::value('session_size', $current_config));
    $current_config['strip_chars']  = CRM_Utils_Array::value('strip_chars',  $values, CRM_Utils_Array::value('strip_chars',  $current_config));
    $current_config['empty_token']  = CRM_Utils_Array::value('empty_token',  $values, CRM_Utils_Array::value('empty_token',  $current_config));
    $current_config['flag_status']  = CRM_Utils_Array::value('flag_status',  $values, CRM_Utils_Array::value('flag_status',  $current_config));
    $current_config['quickhistory'] = CRM_Utils_Array::value('quickhistory', $values, CRM_Utils_Array::value('quickhistory', $current_config));

    // extract configurations
    $configurations = array();
    for ($i=1; $i <= MAX_CONFIG_COUNT; $i++) {
      $activity_type_id = CRM_Utils_Array::value("activity_type_id_{$i}", $values);
      if ($activity_type_id) {
        $configurations[] = array(
          'activity_type_id' => $activity_type_id,
          'handlers'         => CRM_Utils_Array::value("handler_classes_{$i}", $values, array())
        );
      }
    }
    $current_config['configurations'] = $configurations;

    // write the raw config blob
    CRM_I3val_Configuration::setRawConfig($current_config);

    // update the custom groups
    CRM_I3val_Configuration::synchroniseCustomFields();

    parent::postProcess();
  }



  /**
   * get some predefined TTL values
   */
  protected function getTTLOptions() {
    return array(
      '1 hour'  => E::ts("1 hour"),
      '2 hour'  => E::ts("2 hours"),
      '4 hours' => E::ts("4 hours"),
      '8 hours' => E::ts("8 hours"),
      '1 day'   => E::ts("1 day"),
    );
  }

  /**
   * get some predefined TTL values
   */
  protected function getBatchOptions() {
    return array(
      '25' => E::ts("regular (25"),
      '50' => E::ts("big (50)"),
      '10' => E::ts("small (10)"),
      '1'  => E::ts("single"),
    );
  }

  /**
   * get a list of activity status options
   */
  protected function getActivityStatusList() {
    $status_list = array();

    $option_values = civicrm_api3('OptionValue', 'get', array(
      'option_group_id' => 'activity_status',
      'option.limit'    => 0,
      'is_active'       => 1,
      'return'          => 'value,label',
      'sequential'      => 1,
    ));
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
      $types_list = array(
        '' => E::ts("-disabled-")
      );

      $option_values = civicrm_api3('OptionValue', 'get', array(
        'option_group_id' => 'activity_type',
        'option.limit'    => 0,
        'is_active'       => 1,
        'return'          => 'value,label',
        'sequential'      => 1,
      ));
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
      $class_candidates = array();
      $paths = explode(PATH_SEPARATOR, get_include_path());
      foreach ($paths as $path) {
        $folder = $path . 'CRM/I3val/Handler';

        if (is_dir($folder)) {
          $files = scandir($folder);
          foreach ($files as $file) {
            if (preg_match("#(?P<class>^[a-z]+)[.]php$#i", $file, $match)) {
              $class_name = "CRM_I3val_Handler_{$match['class']}";
              $class_candidates[] = $class_name;
            }
          }
        }
      }

      // verify our finds
      $class2label = array();

      foreach ($class_candidates as $class_name) {
        try {
          $class = new ReflectionClass($class_name);
          if ($class->isSubclassOf('CRM_I3val_ActivityHandler') && !$class->isAbstract()) {
            $handler = new $class_name();
            $class2label[$class_name] = $handler->getName();
          }
        } catch (Error $e) {
          // couldn't instantiate, ignore
        }
      }

      self::$_class2label = $class2label;
    }

    return self::$_class2label;
  }
}
