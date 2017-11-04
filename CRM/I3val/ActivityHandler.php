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
abstract class CRM_I3val_ActivityHandler {

  public static $columns = array('original', 'submitted', 'applied');

  /**
   * get the main key/identifier for this handler
   */
  public abstract function getKey();

  /**
   * get the list of fields
   */
  public abstract function getFields();

  /**
   * Verify whether the changes make sense
   *
   * @return array $key -> error message
   */
  public abstract function verifyChanges($activity, $values, $objects = array());

  /**
   * Get the JSON specification file defining the custom group used for this data
   */
  public abstract function getCustomGroupSpeficationFile();

  /**
   * Get the custom group name
   */
  public abstract function getCustomGroupName();

  /**
   * Apply the changes
   *
   * @return array with changes to the activity
   */
  public abstract function applyChanges($activity, $values, $objects = array());

  /**
   * Load and assign necessary data to the form
   */
  public abstract function renderActivityData($activity, $form);

  /**
   * Get the path of the template rendering the form
   */
  public abstract function getTemplate();

  /**
   * Calculate the data to be created and add it to the $activity_data Activity.create params
   * @todo specify
   */
  public abstract function generateDiffData($entity, $entity_id, $entity_data, $submitted_data, &$activity_data);


  /**
   * Check if this activity has data, i.e. should this panel even be rendered?
   * Overwrite if wrong
   */
  public function hasData($activity) {
    // simply check if there is an entry with our group_name
    $sentinel = $this->getCustomGroupName() . '.';
    $sentinel_length = strlen($sentinel);
    foreach ($activity as $key => $value) {
      if ($sentinel == substr($key, 0, $sentinel_length)) {
        return TRUE;
      }
    }
    return FALSE;
  }


  /**
   * Generate the orginal/submitted data for the given fields
   *
   * @param $original_data  array the data as it's currently present in DB
   * @param $submitted_data array the data as it's been submitted
   */
  protected function createDiff($original_data, $submitted_data) {
    $diff_data = array();
    $field_names = $this->getFields();
    $custom_group_name = $this->getCustomGroupName();
    foreach ($field_names as $field_name) {
      if (isset($submitted_data[$field_name])) {
        // an update was submitted
        $original_value = CRM_Utils_Array::value($field_name, $original_data, '');
        if ($submitted_data[$field_name] != $original_value) {
          $diff_data["{$custom_group_name}.{$field_name}_submitted"] = $submitted_data[$field_name];
          $diff_data["{$custom_group_name}.{$field_name}_original"]  = $original_value;
        }
      }
    }
    return $diff_data;
  }

  /**
   * Extract the values of a certain type ('original', 'submitted', 'applied')
   */
  protected function getMyValues($activity, $type = 'submitted') {
    $data       = array();
    $group_name = $this->getCustomGroupName();
    $fields     = $this->getFields();
    foreach ($fields as $fieldname) {
      $key = "{$group_name}.{$fieldname}_{$type}";
      if (isset($activity[$key])) {
        $data[$fieldname] = $activity[$key];
      }
    }
    return $data;
  }

  /**
   * Compile the activity data into a structure compatible
   * with the templates
   */
  protected function compileValues($group_name, $fields, $activity) {
    $data = array();
    foreach ($fields as $fieldname => $field_label) {
      foreach (self::$columns as $column) {
        $key = "{$group_name}.{$fieldname}_{$column}";
        if (isset($activity[$key])) {
          $data[$fieldname][$column] = $activity[$key];
        }
      }
    }
    return $data;
  }

  /**
   * add the 'current' version to all fields if provided in the $data
   */
  protected function addCurrentValues(&$values, $data) {
    foreach ($values as $fieldname => &$fieldvalues) {
      if (isset($data[$fieldname])) {
        $fieldvalues['current'] = $data[$fieldname];
      }
    }
  }

  /**
   * restrict the given changes to the ones submitted
   */
  protected function getMyChanges($changes) {
    $fields = $this->getFields();
    $my_changes = array();

    foreach ($changes as $fieldname => $value) {
      if (in_array($fieldname, $fields)) {
        $my_changes[$fieldname] = $value;
      }
    }
    return $my_changes;
  }
}
