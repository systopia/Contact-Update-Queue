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

class CRM_I3val_Configuration {

  // cache of relevant activity types
  protected $activity_types = NULL;
  protected $activity_queue = NULL;

  private static $configuration = NULL;

  private $config = NULL;

  /**
   * get the configuration singleton
   */
  public static function getConfiguration() {
    if (self::$configuration === NULL) {
      self::$configuration = new CRM_I3val_Configuration();
    }
    return self::$configuration;
  }


  public function __construct() {
    $this->config = self::getRawConfig();
  }


  /**
   * How long is a session valid
   */
  public function getSessionTTL() {
    return CRM_Utils_Array::value('session_ttl', $this->config, "4 hours");
  }

  /**
   * Sanitise input accorting to the configuration
   */
  public function sanitiseInput(&$input) {
    $strip_chars = CRM_Utils_Array::value('strip_chars', $this->config, '');
    if ($strip_chars) {
      foreach ($input as $key => &$value) {
        $value = trim($value, $strip_chars);
      }
    }
  }

  /**
   * This is one of the central configuration elements
   */
  protected function getActivityType2HandlerClass() {
    $activity2handlers = array();
    $configurations = CRM_Utils_Array::value('configurations', $this->config, array());
    foreach ($configurations as $configuration) {
      $activity2handlers[$configuration['activity_type_id']] = $configuration['handlers'];
    }
    return $activity2handlers;
  }

  /**
   * This is one of the central configuration elements
   */
  protected function getActiveHandlerClasses() {
    $handler_list = array();
    $configurations = CRM_Utils_Array::value('configurations', $this->config, array());
    foreach ($configurations as $configuration) {
      foreach ($configuration['handlers'] as $handler_class) {
        $handler_list[$handler_class] = 1;
      }
    }

    return array_keys($handler_list);
  }

  /**
   * This is one of the central configuration elements
   */
  protected function getEntity2HandlerClass() {
    $entity2handlers = array();

    $handlers = $this->getActiveHandlerClasses();
    foreach ($handlers as $handler_class) {
      $handler = new $handler_class();
      $entities = $handler->handlesEntities();
      foreach ($entities as $entity) {
        $entity2handlers[$entity][] = $handler_class;
      }
    }

    return $entity2handlers;
  }

  /**
   * get Handlers for entity
   */
  public function getHandlersForEntity($entity) {
    $handlers = array();
    $entity2HandlerClass = $this->getEntity2HandlerClass();
    if (isset($entity2HandlerClass[$entity])) {
      foreach ($entity2HandlerClass[$entity] as $handlerClass) {
        $handlers[] = new $handlerClass();
      }
    }
    return $handlers;
  }


  /**
   * get a handler instance for the given activity type
   */
  public function getHandlersForActivityType($activity_type_id) {
    $handlers = array();
    $activityType2HandlerClass = $this->getActivityType2HandlerClass();
    if (isset($activityType2HandlerClass[$activity_type_id])) {
      foreach ($activityType2HandlerClass[$activity_type_id] as $handlerClass) {
        $handlers[] = new $handlerClass();
      }
    }
    return $handlers;
  }


  /**
   * get the default activity type for the given entity
   */
  public function getDefaultActivityTypeForEntity($entity) {
    $configurations = CRM_Utils_Array::value('configurations', $this->config, array());
    foreach ($configurations as $configuration) {
      foreach ($configuration['handlers'] as $handler_class) {
        $handler = new $handler_class();
        $entities = $handler->handlesEntities();
        if (in_array($entity, $entities)) {
          return $configuration['activity_type_id'];
        }
      }
    }

    return NULL;
  }

  /**
   * get the activity types based on the current user
   */
  public function getEligibleActivityTypes() {
    // TODO: evaluate permissions
    return $this->getActivityTypes();
  }

  /**
   * get an array id => label of the relevant activity types
   */
  public function getActivityTypes() {
    $activity_types = array();
    $configurations = CRM_Utils_Array::value('configurations', $this->config, array());
    foreach ($configurations as $configuration) {
      $activity_type_id = $configuration['activity_type_id'];
      $activity_types[$activity_type_id] = "Unknown";
    }

    // TODO: cache labels?
    $labels = civicrm_api3('OptionValue', 'get', array(
      'option_group_id' => 'activity_type',
      'value'           => array('IN' => array_keys($activity_types)),
      'return'          => 'value,label',
      'option.limit'    => 0
    ));
    foreach ($labels['values'] as $option_value) {
      $activity_types[$option_value['value']] = $option_value['label'];
    }
    error_log("TYPES " . json_encode($activity_types));
    // return array_keys($activity_types);
    return $activity_types;
  }

  /**
   * get an array id => label of the relevant activity statuses
   */
  public function getLiveActivityStatuses() {
    // TODO: config?
    $statuses = array(
      1, // Scheduled
      // TODO Waiting
      );
    return $statuses;
  }



  /**
   * get the contact this activity should be assigend to
   * @deprecated
   */
  public function getAssignee() {
    // TODO: create a setting for this
    return 215988;
  }

  /**
   * get the activity status ID meaning "flagged as problem"
   */
  public function getErrorStatusID() {
    return CRM_Utils_Array::value('flag_status', $this->config, 3);
  }

  /**
   * get options for postpone button
   * format is days => label
   */
  public function getPostponeOptions() {
    return array(
      '1'  => ts("1 day"),
      '2'  => ts("2 days"),
      '7'  => ts("1 week"),
      '14' => ts("2 weeks"),
      '30' => ts("1 month"),
      );
  }


  /**
   * determine the current user ID
   * @see https://github.com/CiviCooP/org.civicoop.apiuidfix
   */
  public static function getCurrentUserID($fallback_id = NULL) {
    // try the session first
    $session = CRM_Core_Session::singleton();
    $userId = $session->get('userID');
    if (!empty($userId)) {
      return $userId;
    }

    // check via API key, i.e. when coming through REST-API
    $api_key = CRM_Utils_Request::retrieve('api_key', 'String', $store, FALSE, NULL, 'REQUEST');
    if (!$api_key || strtolower($api_key) == 'null') {
      return self::getFallbackUserID($fallback_id); // nothing we can do
    }

    // load user via API KEU
    $valid_user = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $api_key, 'id', 'api_key');

    // If we didn't find a valid user, die
    if (!empty($valid_user)) {
      //now set the UID into the session
      return $valid_user;
    }

    return self::getFallbackUserID($fallback_id); // nothing we can do
  }

  /**
   * get the fallback user
   */
  protected static function getFallbackUserID($fallback_id = NULL) {
    // TODO: configure
    if ($fallback_id) {
      return $fallback_id;
    }

    // TODO: configure?

    // now: last resort: just get any contact
    $any_contact = civicrm_api3('Contact', 'get', array('option.limit' => 1, 'return' => 'id'));
    return $any_contact['id'];
  }

  /**
   * get the raw config array
   */
  public static function getRawConfig() {
    $value = CRM_Core_BAO_Setting::getItem('i3val', 'i3val_config');
    if (!is_array($value)) {
      return array();
    } else {
      return $value;
    }
  }

  /**
   * set the raw config array
   */
  public static function setRawConfig($config) {
    CRM_Core_BAO_Setting::setItem($config, 'i3val', 'i3val_config');
  }
}
