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

  /**
   * get the configuration namespace
   */
  public static function getConfigNamespace() {
    // TODO: move into setting
    return 'AIVL';
  }

  private static $configuration = NULL;
  /**
   * get the configuration singleton
   */
  public static function getConfiguration() {
    if (self::$configuration === NULL) {
      $namespace = self::getConfigNamespace();
      if ($namespace) {
        $class = "CRM_I3val_{$namespace}_Configuration";
        self::$configuration = new $class();
      } else {
        self::$configuration = new CRM_I3val_Configuration();
      }
    }
    return self::$configuration;
  }


  public function __construct() {
  }


  /**
   * How long is a session valid
   */
  public function getSessionTTL() {
    return "4 hours";
  }


  /**
   * This is one of the central configuration elements
   */
  protected function getActivityType2HandlerClass() {
    // TODO: create config UI
    $contact_update_id = CRM_Core_OptionGroup::getValue('activity_type', 'FWTM Contact Update', 'name');
    $mandate_update_id = CRM_Core_OptionGroup::getValue('activity_type', 'FWTM Mandate Update', 'name');
    return array(
      $contact_update_id => array(CRM_I3val_Handler_ContactUpdate,
                                  CRM_I3val_Handler_AddressUpdate,
                                  CRM_I3val_Handler_EmailUpdate,
                                  CRM_I3val_Handler_PhoneUpdate),
      $mandate_update_id => array(CRM_I3val_Handler_SddUpdate)
    );
  }

  /**
   * This is one of the central configuration elements
   */
  protected function getEntity2HandlerClass() {
    // TODO: create config UI
    return array(
      'Contact'     => array(CRM_I3val_Handler_ContactUpdate,
                             CRM_I3val_Handler_AddressUpdate,
                             CRM_I3val_Handler_EmailUpdate,
                             CRM_I3val_Handler_PhoneUpdate),
      'Email'       => array(CRM_I3val_Handler_EmailUpdate),
      'Phone'       => array(CRM_I3val_Handler_PhoneUpdate),
      'Address'     => array(CRM_I3val_Handler_AddressUpdate),
      'SepaMandate' => array(CRM_I3val_Handler_SddUpdate),
    );
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
   * get a hander instance for the given activity type
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
    // TODO:
    return CRM_Core_OptionGroup::getValue('activity_type', 'FWTM Contact Update', 'name');
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
    $activityType2HandlerClass = $this->getActivityType2HandlerClass();
    if ($this->activity_types == NULL) {
      $query = civicrm_api3('OptionValue', 'get', array(
        'option_group_id' => 'activity_type',
        'value'           => array('IN' => array_keys($activityType2HandlerClass)),
        'options.limit'   => 0,
        'return'          => 'value,name,label'
        ));
      $this->activity_types = array();
      foreach ($query['values'] as $optionValue) {
        $this->activity_types[$optionValue['value']] = $optionValue;
      }
    }
    return $this->activity_types;
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
   */
  public function getAssignee() {
    // TODO: create a setting for this
    return 2;
  }

  /**
   * get the activity status ID meaning "flagged as problem"
   */
  public function getErrorStatusID() {
    // TODO: create a setting for this
    return 3;
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
   * Get the ID of the currenty active user
   */
  public function getCurrentUserID() {
    // TODO: sanity checks needed?
    return CRM_Core_Session::getLoggedInContactID();
  }
}
