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

class CRM_I3val_Logic {

  /**
   * Create a Manual Update Activity with the given data
   *
   * @param $contact_id  int    the contact to be updated
   * @param $update      array  the new values
   * @param $params      array  additional parameters like 'activity_id' for the trigger activity
   */
  public static function createManualContactUpdateActivity($contact_id, $update, $params = array()) {
    // first: load contact
    $contact = civicrm_api3('Contact', 'getsingle', array('id' => $contact_id));

    // generate diff data
    $activity_data = self::createDiff($contact, $update, CRM_I3val_Configuration::getContactUpdateFields());

    // add basic activity params
    self::addActivityParams($params, $contact_id, $activity_data);

    // add specific activity params
    // TODO:
    $activity_data['subject'] = "Manual Contact Updgrade (TODO)";
    $activity_data['activity_type_id'] = CRM_Core_OptionGroup::getValue('activity_type', 'FWTM Contact Update', 'name');

    // create activity, reload and return
    error_log("IN: " . json_encode($activity_data));
    CRM_I3val_CustomData::resolveCustomFields($activity_data);
    error_log("OUT: " . json_encode($activity_data));
    $activity = civicrm_api3('Activity', 'create', $activity_data);
    return civicrm_api3('Activity', 'getsingle', array('id' => $activity['id']));
  }


  /**
   * Generate the orginal/submitted data for the given fields
   *
   * @param $original_data  array the data as it's currently present in DB
   * @param $submitted_data array the data as it's been submitted
   * @param $field_specs    array see CRM_I3val_Configuration::getContactUpdateFields()
   */
  protected static function createDiff($original_data, $submitted_data, $field_specs) {
    $diff_data = array();
    foreach ($field_specs as $field_name => $field_spec) {
      if (isset($submitted_data[$field_name])) {
        // an update was submitted
        $diff_data["{$field_spec['custom_group']}.{$field_name}_submitted"] = $submitted_data[$field_name];
        $diff_data["{$field_spec['custom_group']}.{$field_name}_original"]  = CRM_Utils_Array::value($field_name, $original_data, '');
      }
    }
    return $diff_data;
  }

  /**
   * Add the generic activity parameters, partly derived from the $params
   *
   * @param $params         array the parameters present
   * @param $activity_data  array the activity parameters will be added to this array
   */
  protected static function addActivityParams($params, $contact_id, &$activity_data) {
    $activity_data['activity_date_time'] = date('YmdHis'); // NOW
    $activity_data['status_id'] = CRM_Core_OptionGroup::getValue('activity_status', 'Scheduled', 'name');

    if (!empty($params['activity_id'])) {
      $activity_data['parent_id'] = $params['activity_id'];

      $trigger_activity = civicrm_api3('Activity', 'getsingle', array('id' => $params['activity_id']));
      if (!empty($trigger_activity['campaign_id'])) {
        $activity_data['campaign_id'] = $trigger_activity['campaign_id'];
      }
    }

    // assign contacts
    $activity_data['assignee_id'] = CRM_I3val_Configuration::getAssignee();
    $activity_data['source_contact_id'] = CRM_I3val_Configuration::getCurrentUserID();
    $activity_data['target_id'] = $contact_id;
  }

}
