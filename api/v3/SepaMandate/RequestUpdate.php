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

declare(strict_types = 1);

/**
 * Create an activity for manual review of contact updates
 */
function civicrm_api3_sepa_mandate_request_update($params) {
  try {
    $activity = CRM_I3val_Logic::createEntityUpdateRequest('SepaMandate', $params);
    if ($activity) {
      return civicrm_api3_create_success($activity);
    }
    else {
      return civicrm_api3_create_success('No relevant changes detected.');
    }

  }
  catch (Exception $e) {
    // @ignoreException
    return civicrm_api3_create_error($e->getMessage());
  }
}

/**
 * Create an activity for manual review of contact updates
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 */
function _civicrm_api3_sepa_mandate_request_update_spec(&$spec) {
  $spec['id'] = [
    'title'       => 'Mandate ID',
    'description' => 'References an existing mandate',
    'required'    => FALSE,
    'type'        => CRM_Utils_Type::T_INT,
  ];
  $spec['reference'] = [
    'title'       => 'Mandate Reference',
    'description' => 'If reference exists, this is recorded as an update',
    'required'    => FALSE,
    'type'        => CRM_Utils_Type::T_STRING,
  ];
  $spec['sdd_reference_new'] = [
    'title'       => 'New Mandate Reference',
    'description' => 'Reference for the new mandate, should one be created.',
    'required'    => FALSE,
    'type'        => CRM_Utils_Type::T_STRING,
  ];
  $spec['i3val_note'] = [
    'title'       => 'Request note',
    'description' => 'Add a note for the reviewer',
    'required'    => FALSE,
    'type'        => CRM_Utils_Type::T_STRING,
  ];
  $spec['i3val_schedule_date'] = [
    'title'       => 'Schedule date',
    'description' => 'The date this change should be applied. Default is asap.',
    'required'    => FALSE,
    'type'        => CRM_Utils_Type::T_STRING,
  ];
  $spec['i3val_parent_id'] = [
    'title'       => 'Parent activity ID',
    'description' => 'The change request should be recorded as a follow-up to the given activity ID',
    'required'    => FALSE,
    'type'        => CRM_Utils_Type::T_STRING,
  ];
  $spec['activity_type_id'] = [
    'title'       => 'Activity Type ID',
    'description' => 'Allows you to determine the I3Val configuration (attached to the activity type)',
    'required'    => FALSE,
    'type'        => CRM_Utils_Type::T_INT,
  ];
}
