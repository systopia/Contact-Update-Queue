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
 * Create an activity for manual review of contact updates
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 */
function civicrm_api3_contact_request_update($params) {
  // first, try to identify the contact
  $contact_id = (int) $params['id'];

  try {
    // $activity = CRM_I3val_Logic::createContactUpdateRequest($contact_id, $params);
    $activity = CRM_I3val_Logic::createEntityUpdateRequest('Contact', $params);
    if ($activity) {
      return civicrm_api3_create_success($activity);
    } else {
      return civicrm_api3_create_success("No relevant changes detected.");
    }

  } catch (Exception $e) {
    return civicrm_api3_create_error($e->getMessage());
  }
}


/**
 * Create an activity for manual review of contact updates
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 */
function _civicrm_api3_contact_request_update_spec(&$spec) {
  // _civicrm_api3_contact_create_spec($spec);

  $spec['id'] = array(
    'title'       => 'Contact ID',
    'description' => 'The contact this update refers to.',
    'required'    => TRUE,
    'type'        => CRM_Utils_Type::T_INT,
  );
  $spec['i3val_note'] = array(
    'title'       => 'Request note',
    'description' => 'Add a note for the reviewer',
    'required'    => FALSE,
    'type'        => CRM_Utils_Type::T_STRING,
  );
  $spec['i3val_parent_id'] = array(
    'title'       => 'Parent activity ID',
    'description' => 'The change request should be recorded as a follow-up to the given activity ID',
    'required'    => FALSE,
    'type'        => CRM_Utils_Type::T_INT,
  );
}
