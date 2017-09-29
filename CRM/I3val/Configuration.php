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



  public function getActivityQueue($after_activity_id = NULL, $type = NULL) {
    if ($this->activity_queue === NULL) {
      // TODO: DB cache queue?
      $this->activity_queue = array();
      $activity_status_ids = implode(',', $this->getLiveActivityStatuses());
      $activity_type_ids   = implode(',', array_keys($this->getEligibleActivityTypes()));
      if (empty($activity_status_ids) || empty($activity_type_ids)) {
        return $this->activity_queue;
      }

      // see if there is a marker
      $after_activity_id = (int) $after_activity_id;
      if ($after_activity_id) {
        $extra_join = "JOIN civicrm_activity reference ON reference.id = {$after_activity_id}";
        $extra_where_clause = "AND activity.id <> {$after_activity_id} AND activity.activity_date_time >= reference.activity_date_time";
      } else {
        $extra_join = '';
        $extra_where_clause = '';
      }

      // get queue from DB
      $queue_sql = "SELECT activity.id AS activity_id
                    FROM civicrm_activity activity
                    {$extra_join}
                    WHERE activity.activity_type_id IN ({$activity_type_ids})
                      AND activity.status_id IN ({$activity_status_ids})
                      AND activity.activity_date_time < NOW()
                      {$extra_where_clause}
                    ORDER BY activity.activity_date_time ASC, activity.id ASC";
      $queue = CRM_Core_DAO::executeQuery($queue_sql);
      while ($queue->fetch()) {
        $this->activity_queue[] = $queue->activity_id;
      }
      $queue->free();
    }
    return $this->activity_queue;
  }


  /**
   * POSTPONE activity
   */
  public function postponeActivity($activity_id, $mode = NULL) {
    $activity_id = (int) $activity_id;
    if ($activity_id) {
      error_log("ENABLE POSTPONE");
      return;
      civicrm_api3('Activity', 'create', array(
        'id'                 => $activity_id,
        'activity_date_time' => date('YmdHis')
      ));
    }
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
   * get the count of the current queue
   */
  public function getPendingActivityCount() {
    error_log("GET COUNT");
    $queue = $this->getActivityQueue();
    return count($queue);
  }

  /**
   * calculate information on the pending activties
   */
  public function getNextPendingActivity($mode, $reference = NULL) {
    switch ($mode) {
      case 'first':
        $queue = $this->getActivityQueue();
        return reset($queue);
        // $next_activity_id = $this->getNextPendingActivitySQL();
        break;

      case 'next':
        $queue = $this->getActivityQueue($reference);
        return reset($queue);
        // $next_activity_id = $this->getNextPendingActivitySQL($reference);
        if (!$next_activity_id) {
          // there is no more related activities
          return $this->getNextPendingActivity('first');
        }
        break;

      case 'single':  # jump to one activity
        return $reference;
        break;

      default:
        $next_activity_id = NULL;
        break;
    }

    return $next_activity_id;
  }


  // /**
  //  * get the next pending activity
  //  */
  // protected function getNextPendingActivitySQL($after_activity_id = NULL) {
  //   $activity_status_ids = implode(',', $this->getLiveActivityStatuses());
  //   $activity_type_ids   = implode(',', array_keys($this->getActivityTypes()));
  //   if (empty($activity_status_ids) || empty($activity_type_ids)) {
  //     return NULL;
  //   }

  //   // see if there is a marker
  //   $after_activity_id = (int) $after_activity_id;
  //   if ($after_activity_id) {
  //     $extra_join = "JOIN civicrm_activity reference ON reference.id = {$after_activity_id}";
  //     $extra_where_clause = "AND activity.id <> {$after_activity_id} AND activity.activity_date_time >= reference.activity_date_time";
  //   } else {
  //     $extra_join = '';
  //     $extra_where_clause = '';
  //   }

  //   // build the query
  //   $sql = "SELECT activity.id
  //           FROM civicrm_activity activity
  //           {$extra_join}
  //           WHERE activity.activity_type_id IN ({$activity_type_ids})
  //             AND activity.status_id IN ({$activity_status_ids})
  //             {$extra_where_clause}
  //           ORDER BY activity.activity_date_time ASC, id ASC
  //           LIMIT 1";
  //   return CRM_Core_DAO::singleValueQuery($sql);
  // }

  // /**
  //  * get the pending activity count
  //  */
  // public function getPendingActivityCount() {
  //   $activity_status_ids = implode(',', $this->getLiveActivityStatuses());
  //   $activity_type_ids   = implode(',', array_keys($this->getEligibleActivityTypes()));
  //   if (empty($activity_status_ids) || empty($activity_type_ids)) {
  //     return NULL;
  //   }

  //   // build the query
  //   $sql = "SELECT COUNT(civicrm_activity.id)
  //           FROM civicrm_activity
  //           WHERE activity_type_id IN ({$activity_type_ids})
  //             AND status_id IN ({$activity_status_ids})";
  //   return CRM_Core_DAO::singleValueQuery($sql);
  // }

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
