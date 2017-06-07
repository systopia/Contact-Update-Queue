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
class CRM_I3val_Processor_Address extends CRM_I3val_Processor {

  /**
   * return all fields this processor feels responsible for
   */
  protected function getFields() {
    return array(
      'street_address', 'city', 'postal_code', 'state_province_id', 'country_id',
      'supplemental_address_1', 'supplemental_address_2', 'supplemental_address_3',
      );
  }

  /**
   * resolve IDs for fields like gender_id, etc.
   */
  protected function resolveValue($key, $value) {
    switch ($key) {
      case 'country_id':
        if (is_numeric($value)) {
          return $value;
        } else {
          return TODO;
        }

      case 'state_province_id':
        if (is_numeric($value)) {
          return $value;
        } else {
          return TODO;
        }

      default:
        return parent::resolveValue($key, $value);
    }
  }

  /**
   * verify that the given changes are sensible
   *
   * @param $contact  array contact data
   * @param $activity array activity data
   * @param $changes  array the changes to be verified
   *
   * @return array list of errors
   */
  protected function verifyChanges($contact, $activity, $changes) {
    $error_list = array();
    $mychanges = $this->getChangesForMyFields($changes);

    // TODO: load address, and check if address had been changed before

    // // basically just check if none of the attributes have changed
    // //  (unless they're the same as the submission)
    // foreach ($mychanges as $field_name => $requested_value) {
    //   $original_value_field = CRM_I3val_CustomData::getCustomField('fwtm_contact_updates', "{$field_name}_original");
    //   $current_value   = CRM_Utils_Array::value($field_name, $contact, '');
    //   $original_value  = CRM_Utils_Array::value("custom_{$original_value_field['id']}", $activity, '');

    //   error_log("{$field_name}: original '{$original_value}', current '{$current_value}', requested '{$requested_value}'");
    //   if ($current_value != $requested_value) {
    //     // there's an actual change
    //     if ($original_value != $current_value) {
    //       // ... but the value has changed since the activty
    //       $error_list[] = "The field '{$field_name}' has changed since the update request was created. The recorded value is '{$original_value}'.";
    //     }
    //   }
    // }

    return $error_list;
  }

  /**
   * Apply the given changes
   *
   * @param $contact  array contact data
   * @param $activity array activity data
   * @param $changes  array the changes to be verified
   *
   * @return array list of changes
   */
  protected function applyChanges($contact, $activity, $changes) {
    return array();
  }
}
