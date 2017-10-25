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
   * get a hander instance for the given activity type
   */
  public function getHandlersForActivityType($activity_type_id) {
    // TODO:
    return array(
      new CRM_I3val_Handler_ContactUpdate()
    );
  }




  /**
   * get the activity types based on the current user
   */
  public function getEligibleActivityTypes() {
    return $this->getActivityTypes();
  }

  /**
   * get an array id => label of the relevant activity types
   */
  public function getActivityTypes() {
    if ($this->activity_types == NULL) {
      $query = civicrm_api3('OptionValue', 'get', array(
        'option_group_id' => 'activity_type',
        'name'            => array('IN' => array("FWTM Contact Update", "FWTM Mandate Update")),
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

  /**
   * Get an array(field_name => array) of all the
   * fields that can be recorded in an update activity
   */
  public function getContactUpdateFields() {
    return array(
      'first_name' => array(
          'title'        => 'First Name',
          'type'         => CRM_Utils_Type::T_STRING,
          'custom_group' => 'fwtm_contact_updates'),

      'last_name' => array(
          'title'        => 'Last Name',
          'type'         => CRM_Utils_Type::T_STRING,
          'custom_group' => 'fwtm_contact_updates'),
      );
  }


  /**
   * Get an array(field_name => title) of all the
   * fields that can be recorded in an update activity
   */
  public function getMandateUpdateFields() {

  }
}
