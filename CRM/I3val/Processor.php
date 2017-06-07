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
 * this class will handle performing the changes
 *  that are passed on from the API call
 */
abstract class CRM_I3val_Processor {

  protected static $processor_list = NULL;

  protected static function getProcessors() {
    if (self::$processor_list === NULL) {
      self::$processor_list = array();
      $files = scandir(__DIR__ . '/Processor');
      foreach ($files as $file) {
        if (preg_match('#^(?P<name>\w+)[.]php$#', $file, $match)) {
          $processor_class = "CRM_I3val_Processor_{$match['name']}";
          self::$processor_list[] = new $processor_class();
        }
      }
    }
    return self::$processor_list;
  }

  /**
   * See if the changes make sense
   */
  public static function verifyAllChanges($contact, $activity, $changes) {
    $processor_list = self::getProcessors();
    $error_list = array();
    foreach ($processor_list as $processor) {
      $error_list += $processor->verifyChanges($contact, $activity, $changes);
    }
    return $error_list;
  }

  /**
   * apply the changes
   */
  public static function applyAllChanges($contact, $activity, $changes) {
    $processor_list = self::getProcessors();
    $change_list = array();
    foreach ($processor_list as $processor) {
      $change_list += $processor->applyChanges($contact, $activity, $changes);
    }
    return $change_list;
  }


  /**
   * return all fields this processor feels responsible for
   */
  protected abstract function getFields();

  /**
   * verify that the given changes are sensible
   *
   * @param $contact  array contact data
   * @param $activity array activity data
   * @param $changes  array the changes to be verified
   *
   * @return array list of errors
   */
  protected abstract function verifyChanges($contact, $activity, $changes);

  /**
   * Apply the given changes
   *
   * @param $contact  array contact data
   * @param $activity array activity data
   * @param $changes  array the changes to be verified
   *
   * @return array list of changes
   */
  protected abstract function applyChanges($contact, $activity, $changes);

  /**
   * resolve IDs for fields like gender_id, etc.
   */
  protected function resolveValue($key, $value) {
    // to be implemented in classes
    return $value;
  }


  /**
   * get a restricted, resolved set of changes for the fields of this processor
   */
  protected function getChangesForMyFields($changes) {
    $myfields = $this->getFields();
    $mychanges = array();
    foreach ($changes as $key => $value) {
      if (in_array($key, $myfields)) {
        $mychanges[$key] = $this->resolveValue($key, $value);
      }
    }
    return $mychanges;
  }
}
