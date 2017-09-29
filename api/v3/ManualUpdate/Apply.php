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
 * ManualUpdate.apply (optional)
 * Applies the changes for a given activity
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @deprecated
 */
function civicrm_api3_manual_update_apply($params) {
  return civicrm_api3_create_error("DEPRECATED!");

  // load activity and contact
  $activity = civicrm_api3('Activity', 'getsingle', array('id' => $params['activity_id']));
  if ($activity['status_id'] != CRM_Core_OptionGroup::getValue('activity_status', 'Scheduled', 'name')) {
    return civicrm_api3_create_error("Activity not in status 'Scheduled'");
  }

  if (  $activity['activity_type_id'] != CRM_Core_OptionGroup::getValue('activity_type', 'FWTM Contact Update', 'name')
     && $activity['activity_type_id'] != CRM_Core_OptionGroup::getValue('activity_type', 'FWTM Mandate Update', 'name')) {
    return civicrm_api3_create_error("Activity not of FWTM type.");
  }

  $contact_id = civicrm_api3('ActivityContact', 'getvalue', array(
    'return'         => 'contact_id',
    'activity_id'    => $params['activity_id'],
    'record_type_id' => 'Activity Targets'));
  if (empty($contact_id)) {
    return civicrm_api3_create_error("No contact linked to this activity.");
  }
  $contact = civicrm_api3('Contact', 'getsingle', array('id' => $contact_id));

  // verify changes
  $errors = CRM_I3val_Processor::verifyAllChanges($contact, $activity, $params);
  if (!empty($errors)) {
    return civicrm_api3_create_error("Validation of changes failed", array('error_list' => $errors));
  }

  // apply changes
  $updates = CRM_I3val_Processor::applyAllChanges($contact, $activity, $params);

  // compile activity update
  $activity_update = array(
    'id'        => $activity['id'],
    'status_id' => CRM_Core_OptionGroup::getValue('activity_status', 'Completed', 'name')
    );
  $field_specs = CRM_I3val_Configuration::getContactUpdateFields();
  foreach ($field_specs as $field_name => $field_spec) {
    if (isset($params[$field_name])) {
      $activity_update["{$field_spec['custom_group']}.{$field_name}_applied"] = $params[$field_name];
    }
  }
  CRM_I3val_CustomData::resolveCustomFields($activity_update);
  error_log("ACTIVITY UPDATE " . json_encode($activity_update));
  $activity = civicrm_api3('Activity', 'create', $activity_update);

  return civicrm_api3_create_success($updates);
}


/**
 * ManualUpdate.contactupdate (optional)
 * Create an activity for manual review of contact updates
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 */
function _civicrm_api3_manual_update_apply_spec(&$spec) {
  $spec['activity_id'] = array(
    'title'       => 'Activity ID',
    'description' => 'The activity to be executed with the appended values. Its statis will be set to "Completed"',
    'required'    => TRUE,
    'type'        => CRM_Utils_Type::T_INT,
  );

  // add individual fields
  $field_specs = CRM_I3val_Configuration::getContactUpdateFields();
  foreach ($field_specs as $field_name => $field_spec) {
    $spec[$field_name] = array(
      'title'       => $field_spec['title'],
      'required'    => FALSE,
      'type'        => $field_spec['type'],
    );
  }
}
