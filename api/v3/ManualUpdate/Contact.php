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
 * ManualUpdate.contact (optional)
 * Create an activity for manual review of contact updates
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 */
function civicrm_api3_manual_update_contact($params) {
  // first, try to identify the contact
  if (!empty($params['contact_id'])) {
    $contact_id = (int) $params['contact_id'];

  } elseif (!empty($params['activity_id'])) {
    // load contact from activity
    $contact_id = civicrm_api3('ActivityContact', 'getvalue', array(
      'return'         => 'contact_id',
      'activity_id'    => $params['activity_id'],
      'record_type_id' => 'Activity Targets'));
  }

  // we NEED the activity ID
  if (empty($contact_id)) {
    return civicrm_api3_create_error("Couldn't identify contact.");
  }

  // sort params into update data and remaining params
  $update = array();
  $fields = CRM_I3val_Configuration::getContactUpdateFields();
  foreach (array_keys($params) as $field_name) {
    if (isset($fields[$field_name])) {
      // this is a field to be updated
      $update[$field_name] = $params[$field_name];
      unset($params[$field_name]);
    }
  }

  try {
    $activity = CRM_I3val_Logic::createManualContactUpdateActivity($contact_id, $update, $params);
    return civicrm_api3_create_success($activity);
  } catch (Exception $e) {
    return civicrm_api3_create_error($e->getMessage());
  }
}


/**
 * ManualUpdate.contact (optional)
 * Create an activity for manual review of contact updates
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 */
function _civicrm_api3_manual_update_contact_spec(&$spec) {
  $spec['activity_id'] = array(
    'title'       => 'Activity ID',
    'description' => 'The activity that triggered this update. The contact will be taken from this.',
    'required'    => FALSE,
    'type'        => CRM_Utils_Type::T_INT,
  );

  $spec['contact_id'] = array(
    'title'       => 'Contact ID',
    'description' => 'The contact this update refers to. Required unless activity_id is given',
    'required'    => FALSE,
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
