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

define('I3VAL_DEBUG_LOGGING', TRUE);

/**
 * This class will store data about this processing session
 * In particular, it will store the TODO list for the desktop
 */
class CRM_I3val_Session {

  /**
   * these properties are stored in the user's (browser) session
   */
  const SESSION_PROPERTIES = [
      'cache_key',
      'start_time',
      'activity_id',
      'processed_count',
      'activity_types',
      'open_count',
      'queue_type',
      'queue_stack'
  ];

  /** @var CRM_I3val_Session the single session object  */
  protected static $_singleton = NULL;

  /** @var CRM_Core_Session access to the session data */
  protected $user_session = NULL;

  /**
   * get the current user's session
   */
  public static function getSession() {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_I3val_Session();
    }
    return self::$_singleton;
  }

  /**
   * Logging function for debugging purposes
   * Works if I3VAL_DEBUG_LOGGING is TRUE
   */
  public static function log($message) {
    if (I3VAL_DEBUG_LOGGING) {
      $session = self::getSession();
      $cache_key = $session->get('cache_key');
      if ($cache_key) {
        $cache_key = substr($cache_key, 0, 4);
      } else {
        $cache_key = 'NONE';
      }
      CRM_Core_Error::debug_log_message("I3Val [{$cache_key}]: {$message}");
    }
  }

  /**
   * the session status contains of the following
   *   attributes, stored in the user session
   */
  protected function __construct() {
    $this->user_session = CRM_Core_Session::singleton();
  }

  /**
   * See if this is initialised
   */
  protected function isInitialised() {
    $session_key = $this->get('cache_key');
    return $session_key != NULL;
  }

  /**
   * get the current activity_id
   *
   * @param $activity_types
   */
  public function getCurrentActivityID($activity_types) {
    // lazy initialisation
    if (!$this->isInitialised()) {
      $this->reset($activity_types);
    }

    return $this->get('activity_id');
  }

  /**
   * mark this activity as processed
   */
  public function markProcessed($activity_id, $timestamp = NULL) {
    self::log("Marking [{$activity_id}] as processed");
    $activity_id = (int) $activity_id;

    // increase processed count
    $processed_count = $this->getProcessedCount();
    $this->set('processed_count', $processed_count + 1);

    // remove from session(s)
    $this->releaseActivityID($activity_id);

    // get next
    $next_activity_id = $this->getNext(TRUE, $timestamp);
    $this->set('activity_id', $next_activity_id);
  }


  /**
   * Get the next activity id from our list
   */
  protected function getNext($grab_more_if_needed = TRUE, $after_timestamp = NULL) {
    $cache_key = $this->getSessionKey();
    $live_status_list = implode(',', CRM_I3val_Configuration::getConfiguration()->getLiveActivityStatuses());
    $next_activity_id = CRM_Core_DAO::singleValueQuery("
        SELECT cache.activity_id AS activity_id
        FROM i3val_session_cache cache
        LEFT JOIN civicrm_activity activity ON activity.id = cache.activity_id
        WHERE cache.session_key = '{$cache_key}'
          AND activity.status_id IN ({$live_status_list})
        ORDER BY cache.id ASC
        LIMIT 1");
    if (!$next_activity_id && $grab_more_if_needed) {
      // try to get more
      self::log("No more items in session");
      $session_size = CRM_I3val_Configuration::getConfiguration()->getSessionSize();
      $this->grabMoreActivities($session_size, $after_timestamp);
      $next_activity_id = $this->getNext(FALSE, $after_timestamp);
    }
    return $next_activity_id;
  }

  /**
   * free the given activity
   */
  protected function releaseActivityID($activity_id) {
    self::log("Releasing [{$activity_id}]");
    $activity_id = (int) $activity_id;
    CRM_Core_DAO::singleValueQuery("DELETE FROM i3val_session_cache WHERE activity_id = {$activity_id}");
  }

  /**
   * Stores the parameters of the current queue
   *  in the session
   */
  protected function pushQueueParams() {
    self::log("Pushing queue parameters");
    $current_stack = json_decode($this->get('queue_stack'), true);
    if (empty($current_stack)) {
      $current_stack = [];
    }

    // push the current session
    $session_status = [];
    foreach (self::SESSION_PROPERTIES as $property) {
      if ($property != 'queue_stack') {
        $session_status[$property] = $this->get($property);
      }
    }
    $current_stack[] = $session_status;

    // and write out
    $this->set('queue_stack', json_encode($current_stack));
    self::log("Queue stack size is now " . count($current_stack));
  }

  /**
   * Restores the last queue parameters
   */
  protected function popQueueParams() {
    $current_stack = json_decode($this->get('queue_stack'), true);
    if (empty($current_stack)) {
      self::log("Cannot restore queue parameters, stack is empty");
      return;
    }
    self::log("Restoring queue parameters");
    $session_status = array_pop($current_stack);
    foreach ($session_status as $property => $value) {
      if ($property != 'queue_stack') {
        $this->set($property, $value);
      }
    }

    // update stack
    $this->set('queue_stack', json_encode($current_stack));
    self::log("Queue stack size is now " . count($current_stack));
  }



  /**
   * Internal session reset function
   */
  protected function _reset($destroy_old_session = true) {
    self::log("Reset requested");

    // destroy the user's current session (if any)
    $cache_key = $this->get('cache_key');
    if ($cache_key && $destroy_old_session) {
      $this->destroySession($cache_key);
    }

    // remove outdated cache entries
    $this->purgeCache();

    // create a new session
    $cache_key = sha1('i3val' . microtime(TRUE) . rand());
    $this->set('cache_key', $cache_key);
    $this->set('start_time', date('YmdHis'));
    $this->set('activity_id', 0);
    $this->set('processed_count', 0);
  }

  /**
   * reset the user session:
   * 1) clear values
   * 2) fill prev_next_cache with the next couple of items
   */
  public function reset($activity_types) {
    // do a generic reset
    $this->_reset();

    // sanitise activity types
    if (empty($activity_types)) {
      $configuration = CRM_I3val_Configuration::getConfiguration();
      $activity_types = array_keys($configuration->getActivityTypes());
    }

    $this->set('activity_types', implode(',', $activity_types));
    $this->set('open_count', $this->calculateOpenActivityCount());

    // fill next cache
    $first_activity_id = $this->getNext();
    $this->set('activity_id', $first_activity_id);
    $this->set('queue_type', 'regular');
  }

  /**
   * Will drop the current session and
   * create a new session with only the sibling activities to the given one
   *
   * @param $activity_id
   */
  public function jumpToSiblingQueue($activity_id) {
    self::log("Jump to sibling queue");
    $this->pushQueueParams();
    // do a generic reset
    $this->_reset(false);

    // now create a session with only those IDs
    $sibling_activity_ids = self::getSiblingActivityIDs($activity_id);
    $sibling_activity_count = count($sibling_activity_ids);
    $this->set('open_count', $sibling_activity_count);
    $this->set('activity_id', reset($sibling_activity_ids));
    $this->set('queue_type', 'sibling');

    // make sure those are not in another queue
    $activity_id_list = implode(',', $sibling_activity_ids);
    $my_session_keys = '"' . implode('","', $this->getSessionKeys()) . '"';
    $free_query_sql = "
    SELECT activity.id AS activity_id
    FROM civicrm_activity activity
    LEFT JOIN i3val_session_cache session ON session.activity_id = activity.id
    WHERE activity.id IN ($activity_id_list)
      AND (session.activity_id IS NULL OR session.session_key IN ({$my_session_keys}))
    ORDER BY activity.activity_date_time ASC;";
    $free_query = CRM_Core_DAO::executeQuery($free_query_sql);
    $sibling_activity_ids_free = array();
    while ($free_query->fetch()) {
      $sibling_activity_ids_free[] = $free_query->activity_id;
    }
    $sibling_activity_count_blocked = $sibling_activity_count - count($sibling_activity_ids_free);

    // warn if some of them are blocked by another session
    if ($sibling_activity_count_blocked) {
      CRM_Core_Session::setStatus(E::ts("%1 of the scheduled activities for this contact are currently processed by somebody else", array(1 => $sibling_activity_count)),
          E::ts("Concurrent Processing"),
          'warning');
    }

    // build the query: all pending activities that are not already assigned
    $configuration = CRM_I3val_Configuration::getConfiguration();
    $cache_key     = $this->getSessionKey();
    $session_ttl   = $configuration->getSessionTTL();
    $expires       = date('YmdHis', strtotime("+{$session_ttl}"));
    foreach ($sibling_activity_ids_free as $activity_id) {
      CRM_Core_DAO::executeQuery("INSERT INTO i3val_session_cache (session_key, activity_id, expires) VALUES (%1, %2, %3)", array(
          1 => array($cache_key,   'String'),
          2 => array($activity_id, 'Integer'),
          3 => array($expires,     'String')));
    }
  }

  /**
   * Will return to the previous session
   */
  public function returnFromSiblingQueue() {
    $this->popQueueParams();


    // make sure that we continue with the next one
    $continuation_activity_id = $this->getNext();
    $this->set('activity_id', $continuation_activity_id);

    // and increase processed count by 1 (at least)
    $old_processed_count = (int) $this->get('processed_count');
    $this->set('processed_count', $old_processed_count + 1);
  }

  /**
   * get the cache key
   * WARNING: throws exception if not set (not intialised)
   */
  public function getSessionKey() {
    $cache_key = $this->get('cache_key');
    if ($cache_key) {
      return $cache_key;
    } else {
      throw new Exception("Session not intialised!", 1);
    }
  }

  /**
   * Get all session keys, including the ones of the main queue, should this be a subqueue
   * WARNING: throws exception if not set (not intialised)
   */
  public function getSessionKeys() {
    $keys = [$this->getSessionKey()];

    // get additional keys from the stack
    $current_stack = json_decode($this->get('queue_stack'), true);
    if (is_array($current_stack)) {
      foreach ($current_stack as $session) {
        if (!empty($session['cache_key'])) {
          $keys[] = $session['cache_key'];
        }
      }
    }

    return $keys;
  }

  /**
   * get the number of processed items
   */
  public function getProcessedCount() {
    return (int) $this->get('processed_count');
  }

  /**
   * Get a progress between 0..1
   * @return float
   */
  public function getProgress($including_current = TRUE) {
    $open_count = (int) $this->getOpenActivityCount();
    if ($open_count) {
      $processed_count = $this->getProcessedCount();
      if ($including_current) {
        $processed_count += 1;
      }
      $progress = (float) $processed_count / (float) $open_count;
      return min(1.0, $progress);
    } else {
      // open_count is 0
      return 1.0;
    }
  }

  /**
   * Get the (approximate) number still pending activites
   */
  public function getPendingCount() {
    $open_count = $this->getOpenActivityCount();
    $processed_count = $this->getProcessedCount();
    return max($open_count - $processed_count, 0);
  }

  /**
   * Get the (approximate) number of open activities
   */
  public function getOpenActivityCount() {
    $open_count = $this->get('open_count');
    if ($open_count === NULL) {
      $open_count = $this->calculateOpenActivityCount();
      $this->set('open_count', $open_count);
    }
    return $open_count;
  }

  /**
   * Assign another {$max_count} activities to this session
   */
  protected function grabMoreActivities($max_count = 0, $after_timestamp = NULL) {
    if ($this->isSiblingQueue()) return;

    $after_activity_id = $this->get('activity_id');
    self::log("grabMoreActivities: max: {$max_count}, after: [{$after_activity_id}], earliest: " . ($after_timestamp ? date('Y-m-d H:i:s', strtotime($after_timestamp)) : 'none'));

    $configuration = CRM_I3val_Configuration::getConfiguration();
    $activity_status_ids = implode(',', $configuration->getLiveActivityStatuses());
    $activity_type_ids   = $this->get('activity_types');
    if (empty($activity_status_ids) || empty($activity_type_ids)) {
      return NULL;
    }

    // see if there is a marker
    $after_activity_id = (int) $after_activity_id;
    if ($after_timestamp) {
      $timestamp = date('YmdHis', strtotime($after_timestamp));
      $extra_join = '';
      $extra_where_clause = "AND activity.activity_date_time >= '{$timestamp}'";
    } elseif ($after_activity_id) {
      $extra_join = "JOIN civicrm_activity reference ON reference.id = {$after_activity_id}";
      $extra_where_clause = "AND activity.id <> {$after_activity_id} AND activity.activity_date_time >= reference.activity_date_time";
    } else {
      $extra_join = '';
      $extra_where_clause = '';
    }

    if ($max_count) {
      $limit = 'LIMIT ' . (int) $max_count;
    } else {
      $limit = '';
    }

    // build the query: all pending activities that are not already assigned
    $cache_key   = $this->getSessionKey();
    $session_ttl = $configuration->getSessionTTL();
    $expires     = date('YmdHis', strtotime("+{$session_ttl}"));
    $sql = "SELECT activity.id AS activity_id
            FROM civicrm_activity activity
            LEFT JOIN i3val_session_cache ON activity.id = i3val_session_cache.activity_id
            {$extra_join}
            WHERE activity.activity_type_id IN ({$activity_type_ids})
              AND activity.status_id IN ({$activity_status_ids})
              AND i3val_session_cache.session_key IS NULL
              AND activity.activity_date_time < NOW()
              {$extra_where_clause}
            ORDER BY activity.activity_date_time ASC, activity.id ASC
            {$limit}";
    $entries = CRM_Core_DAO::executeQuery($sql);
    while ($entries->fetch()) {
      CRM_Core_DAO::executeQuery("INSERT INTO i3val_session_cache (session_key, activity_id, expires) VALUES (%1, %2, %3)", array(
        1 => array($cache_key,            'String'),
        2 => array($entries->activity_id, 'Integer'),
        3 => array($expires,              'String')));
      self::log("Grabbed [{$entries->activity_id}]");
    }
    $entries->free();
  }

  /**
   * will remove all data for the given session
   */
  protected function destroySession($cache_key) {
    self::log("destroySession");
    CRM_Core_DAO::executeQuery("DELETE FROM i3val_session_cache WHERE session_key = '{$cache_key}'");
  }

  /**
   * simply remove all expired entries from the cache
   */
  protected function purgeCache() {
    self::log("purgeCache");
    CRM_Core_DAO::executeQuery("DELETE FROM i3val_session_cache WHERE expires < NOW()");
  }

  /**
   * store stuff in the session
   */
  protected function set($name, $value) {
    $this->user_session->set($name, $value, 'i3val_');
  }

  /**
   * retrieve stuff from the session
   */
  protected function get($name) {
    return $this->user_session->get($name, 'i3val_');
  }

  /**
   * Check if this is a sibling queue
   */
  public function isSiblingQueue() {
    $queue_type = $this->get('queue_type');
    return $queue_type == 'sibling';
  }

  /**
   * Get a fresh queue link with the same parameters
   */
  public function getContinuationURL() {
    $activity_types = $this->get('activity_types');
    if (empty($activity_types)) {
      return CRM_Utils_System::url("civicrm/i3val/desktop", "restart=1&reset=1");
    } else {
      return CRM_Utils_System::url("civicrm/i3val/desktop", "restart=1&reset=1&types={$activity_types}");
    }
  }

  /**
   * Calculate the pending activity count
   */
  protected function calculateOpenActivityCount() {
    $configuration = CRM_I3val_Configuration::getConfiguration();
    $activity_status_ids = implode(',', $configuration->getLiveActivityStatuses());
    $activity_type_ids   = $this->get('activity_types');
    if (empty($activity_status_ids) || empty($activity_type_ids)) {
      return 0;
    }

    $sql = "SELECT COUNT(activity.id)
            FROM civicrm_activity activity
            WHERE activity.activity_type_id IN ({$activity_type_ids})
              AND activity.activity_date_time < NOW()
              AND activity.status_id IN ({$activity_status_ids})";
    return CRM_Core_DAO::singleValueQuery($sql);
  }

  /**
   * POSTPONE activity
   */
  public function postponeActivity($activity_id, $delay) {
    self::log("POSTPONING [{$activity_id}] by {$delay}");
    $activity_id = (int) $activity_id;
    if ($activity_id) {
      // get the old activity timestamp
      $current_status = civicrm_api3('Activity', 'getsingle', array(
        'id'     => $activity_id,
        'return' => 'activity_date_time',
      ));

      if ($delay) {
        $new_date = date('YmdHis', strtotime("+{$delay}"));
      } else {
        $new_date = date('YmdHis');
      }

      civicrm_api3('Activity', 'create', array(
        'id'                 => $activity_id,
        'activity_date_time' => $new_date
      ));

      return $current_status['activity_date_time'];
    }
  }

  /**
   * FLAG activity
   */
  public function flagActivity($activity_id) {
    self::log("FLAGGING [{$activity_id}]");
    $activity_id = (int) $activity_id;
    if ($activity_id) {
      $configuration = CRM_I3val_Configuration::getConfiguration();
      $error_status_id = $configuration->getErrorStatusID();
      civicrm_api3('Activity', 'create', array(
        'id'        => $activity_id,
        'status_id' => $error_status_id
      ));
    }
  }

  /**
   * Get a list of all pending request activity IDs that refer to the same contact than the one
   * referred to by the given activity ID
   *
   * @param $activity_id
   * @return array activity IDs
   */
  public static function getSiblingActivityIDs($activity_id) {
    $sibling_ids = array();
    $configuration = CRM_I3val_Configuration::getConfiguration();
    $activity_status_ids = implode(',', $configuration->getLiveActivityStatuses());
    $activity_types = implode(',', array_keys($configuration->getActivityTypes()));
    $sql_query = "
      SELECT DISTINCT(activity.id) AS activity_id
      FROM civicrm_activity_contact       search
      LEFT JOIN civicrm_activity_contact related ON related.contact_id = search.contact_id AND related.record_type_id = 3
      LEFT JOIN civicrm_activity        activity ON activity.id = related.activity_id    
      WHERE search.record_type_id = 3
        AND search.activity_id = {$activity_id}
        AND activity.activity_type_id IN ({$activity_types})
        AND activity.status_id IN ({$activity_status_ids})
      ORDER BY activity.activity_date_time ASC;";
    $query = CRM_Core_DAO::executeQuery($sql_query);
    while ($query->fetch()) {
      $sibling_ids[] = $query->activity_id;
    }
    return $sibling_ids;
  }
}
