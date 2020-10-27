<?php
/*-------------------------------------------------------+
| Ilja's Input Validation Extension                      |
| Amnesty International Vlaanderen                       |
| Copyright (C) 2020 SYSTOPIA                            |
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
 * Convert old XCM-Style activities into I3Val activities
 */
function civicrm_api3_i3val_convert($params) {
  $converter = new CRM_I3val_Converter();
  list($success_count, $failed_count) = $converter->convert($params, $params['target_activity_type_id'], $params);
  if ($failed_count == 0) {
    return civicrm_api3_create_success("{$success_count} activities converted, {$failed_count} failed");
  } else {
    return civicrm_api3_create_error("{$success_count} activities converted, {$failed_count} failed");
  }
}


/**
 * Convert old XCM-Style activities into I3Val activities
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 */
function _civicrm_api3_i3val_convert_spec(&$spec) {
  $spec['target_activity_type_id'] = [
      'name'        => 'target_activity_type_id',
      'title'       => 'Target: Activity Type ID',
      'description' => 'Select the activity type IDs you want to convert',
      'required'    => TRUE,
      'type'        => CRM_Utils_Type::T_INT,
  ];
}
