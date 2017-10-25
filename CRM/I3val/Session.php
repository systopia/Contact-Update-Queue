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

/**
 * This class will store data about this processing session
 * In particular, it will store the TODO list for the desktop
 */
class CRM_I3val_Session {

  protected static $_singleton = NULL;

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
   * the session status contains of the following
   *   attributes, stored in the user session
   * 'start_time'
   * 'processed_count'
   * 'open_count'
   * 'cache_key'
   */
  protected function __construct() {
    $this->user_session = CRM_Core_Session::singleton();
  }


  /**
   * main entry point: get the next activity.
   */
  public function getNextPendingActivity($mode, $reference = NULL) {
    switch ($mode) {
      default:
      case 'first':
        $this->reset();
        return $this->getNext();
        break;

      case 'next':
        return $this->getNext();
        break;

      case 'single':  # jump to one activity
        $this->reset($reference);
        return $this->getNext();
        break;
    }
  }

  /**
   * free the given activity
   */
  protected function releaseActivityID($activity_id) {
    $activity_id = (int) $activity_id;
    CRM_Core_DAO::singleValueQuery("DELETE FROM i3val_session_cache WHERE activity_id = {$activity_id}");
  }

  /**
   * Get the next activity id from our list
   */
  protected function getNext($grab_more_if_needed = TRUE) {
    $cache_key = $this->getCacheKey();
    $next_activity_id = CRM_Core_DAO::singleValueQuery("
        SELECT activity_id
        FROM i3val_session_cache
        WHERE session_key = '{$cache_key}'
        ORDER BY id ASC
        LIMIT 1");
    if (!$next_activity_id && $grab_more_if_needed) {
      // try to get more
      $this->grabMoreActivities(10);
      $next_activity_id = $this->getNext(FALSE);
    }
    return $next_activity_id;
  }

  /**
   * reset the user session:
   * 1) clear values
   * 2) fill prev_next_cache with the next couple of items
   *
   * @param $activity_id if given, create a session with only this activity
   */
  protected function reset($activity_id = NULL) {
    // destroy the user's current session (if any)
    $cache_key = $this->get('cache_key');
    if ($cache_key) {
      $this->destroySession($cache_key);
    }

    // remove outdated cache entries
    $this->purgeCache();

    // create a new session
    $cache_key = sha1('i3val' . microtime(TRUE) . rand());
    $this->set('cache_key', $cache_key);
    $this->set('start_time', date('YmdHis'));
    $this->set('processed_count', 0);
    $this->set('open_count', $this->calculateOpenActivityCount());

    // fill next cache
    if ($activity_id) {

    } else {
      $this->grabMoreActivities(10);
    }
  }

  /**
   * get the cache key
   * WARNING: throws exception if not set (not intialised)
   */
  public function getCacheKey() {
    $cache_key = $this->get('cache_key');
    if ($cache_key) {
      return $cache_key;
    } else {
      throw new Exception("Session not intialised!", 1);
    }
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
  public function getProgress() {
    $open_count = (int) $this->getOpenActivityCount();
    if ($open_count) {
      $processed_count = $this->getProcessedCount();
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
  protected function grabMoreActivities($max_count = 0, $after_activity_id = 0) {
    $configuration = CRM_I3val_Configuration::getConfiguration();
    $activity_status_ids = implode(',', $configuration->getLiveActivityStatuses());
    $activity_type_ids   = implode(',', array_keys($configuration->getActivityTypes()));
    if (empty($activity_status_ids) || empty($activity_type_ids)) {
      return NULL;
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

    if ($max_count) {
      $limit = 'LIMIT ' . (int) $max_count;
    } else {
      $limit = '';
    }

    // build the query: all pending activities that are not already assigned
    $cache_key   = $this->getCacheKey();
    $session_ttl = $configuration->getSessionTTL();
    $expires     = date('YmdHis', strtotime("+{$session_ttl}"));
    $sql = "SELECT activity.id AS activity_id
            FROM civicrm_activity activity
            LEFT JOIN i3val_session_cache ON activity.id = i3val_session_cache.activity_id
            {$extra_join}
            WHERE activity.activity_type_id IN ({$activity_type_ids})
              AND activity.status_id IN ({$activity_status_ids})
              AND i3val_session_cache.session_key IS NULL
              {$extra_where_clause}
            ORDER BY activity.activity_date_time ASC, activity.id ASC
            {$limit}";
    $entries = CRM_Core_DAO::executeQuery($sql);
    while ($entries->fetch()) {
      CRM_Core_DAO::executeQuery("INSERT INTO i3val_session_cache (session_key, activity_id, expires) VALUES (%1, %2, %3)", array(
        1 => array($cache_key,            'String'),
        2 => array($entries->activity_id, 'Integer'),
        3 => array($expires,              'String')));
    }
    $entries->free();
  }

  /**
   * will remove all data for the given session
   */
  protected function destroySession($cache_key) {
    CRM_Core_DAO::executeQuery("DELETE FROM i3val_session_cache WHERE session_key = '{$cache_key}'");
  }

  /**
   * simply remove all expired entries from the cache
   */
  protected function purgeCache() {
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
   * Calculate the pending activity count
   */
  protected function calculateOpenActivityCount() {
    $configuration = CRM_I3val_Configuration::getConfiguration();
    $activity_status_ids = implode(',', $configuration->getLiveActivityStatuses());
    $activity_type_ids   = implode(',', array_keys($configuration->getActivityTypes()));
    $sql = "SELECT COUNT(activity.id)
            FROM civicrm_activity activity
            WHERE activity.activity_type_id IN ({$activity_type_ids})
              AND activity.status_id IN ({$activity_status_ids})";
    return CRM_Core_DAO::singleValueQuery($sql);
  }








  // public function getActivityQueue($after_activity_id = NULL, $type = NULL) {
  //   if ($this->activity_queue === NULL) {
  //     // TODO: DB cache queue?
  //     $this->activity_queue = array();
  //     $activity_status_ids = implode(',', $this->getLiveActivityStatuses());
  //     $activity_type_ids   = implode(',', array_keys($this->getEligibleActivityTypes()));
  //     if (empty($activity_status_ids) || empty($activity_type_ids)) {
  //       return $this->activity_queue;
  //     }

  //     // see if there is a marker
  //     $after_activity_id = (int) $after_activity_id;
  //     if ($after_activity_id) {
  //       $extra_join = "JOIN civicrm_activity reference ON reference.id = {$after_activity_id}";
  //       $extra_where_clause = "AND activity.id <> {$after_activity_id} AND activity.activity_date_time >= reference.activity_date_time";
  //     } else {
  //       $extra_join = '';
  //       $extra_where_clause = '';
  //     }

  //     // get queue from DB
  //     $queue_sql = "SELECT activity.id AS activity_id
  //                   FROM civicrm_activity activity
  //                   {$extra_join}
  //                   WHERE activity.activity_type_id IN ({$activity_type_ids})
  //                     AND activity.status_id IN ({$activity_status_ids})
  //                     AND activity.activity_date_time < NOW()
  //                     {$extra_where_clause}
  //                   ORDER BY activity.activity_date_time ASC, activity.id ASC";
  //     $queue = CRM_Core_DAO::executeQuery($queue_sql);
  //     while ($queue->fetch()) {
  //       $this->activity_queue[] = $queue->activity_id;
  //     }
  //     $queue->free();
  //   }
  //   return $this->activity_queue;
  // }


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


  // /**
  //  * get the count of the current queue
  //  */
  // public function getOpenActivityCount() {
  //   error_log("GET COUNT");
  //   $queue = $this->getActivityQueue();
  //   return count($queue);
  // }

  /**
   * calculate information on the pending activties
   */
  // public function getNextPendingActivity($mode, $reference = NULL) {
  //   switch ($mode) {
  //     case 'first':
  //       $this->reset();
  //       $queue = $this->getActivityQueue();
  //       return reset($queue);
  //       // $next_activity_id = $this->getNextPendingActivitySQL();
  //       break;

  //     case 'next':
  //       $queue = $this->getActivityQueue($reference);
  //       return reset($queue);
  //       // $next_activity_id = $this->getNextPendingActivitySQL($reference);
  //       if (!$next_activity_id) {
  //         // there is no more related activities
  //         return $this->getNextPendingActivity('first');
  //       }
  //       break;

  //     case 'single':  # jump to one activity
  //       return $reference;
  //       break;

  //     default:
  //       $next_activity_id = NULL;
  //       break;
  //   }

  //   return $next_activity_id;
  // }



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
  // public function getOpenActivityCount() {
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
}
