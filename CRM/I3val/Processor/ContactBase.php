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
 * @deprecated
 */
class CRM_I3val_Processor_ContactBase extends CRM_I3val_Processor {

  /**
   * return all fields this processor feels responsible for
   */
  protected function getFields() {
    return array(
      'first_name', 'middle_name', 'last_name', 'household_name', 'organization_name',
      'prefix_id', 'suffix_id', 'gender_id', 'formal_title',
      'do_not_email', 'do_not_phone', 'do_not_mail', 'do_not_sms', 'do_not_trade', 'is_opt_out',
      'preferred_communication_method', 'preferred_language',
      'legal_identifier', 'nick_name', 'legal_name', 'sic_code',
      'job_title', 'birth_date',
      );
  }

  /**
   * resolve IDs for fields like gender_id, etc.
   */
  protected function resolveValue($key, $value) {
    switch ($key) {
      case 'prefix_id':
        if (is_numeric($value)) {
          return $value;
        } else {
          return CRM_Core_OptionGroup::getValue('individual_prefix', $value);
        }

      case 'suffix_id':
        if (is_numeric($value)) {
          return $value;
        } else {
          return CRM_Core_OptionGroup::getValue('individual_suffix', $value);
        }

      case 'gender_id':
        if (is_numeric($value)) {
          return $value;
        } else {
          return CRM_Core_OptionGroup::getValue('gender', $value);
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

    // basically just check if none of the attributes have changed
    //  (unless they're the same as the submission)
    foreach ($mychanges as $field_name => $requested_value) {
      $original_value_field = CRM_I3val_CustomData::getCustomField('fwtm_contact_updates', "{$field_name}_original");
      $current_value   = CRM_Utils_Array::value($field_name, $contact, '');
      $original_value  = CRM_Utils_Array::value("custom_{$original_value_field['id']}", $activity, '');

      error_log("{$field_name}: original '{$original_value}', current '{$current_value}', requested '{$requested_value}'");
      if ($current_value != $requested_value) {
        // there's an actual change
        if ($original_value != $current_value) {
          // ... but the value has changed since the activty
          $error_list[] = "The field '{$field_name}' has changed since the update request was created. The recorded value is '{$original_value}'.";
        }
      }
    }

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
    $change_list = array();
    $contact_updates = array();

    $mychanges = $this->getChangesForMyFields($changes);
    foreach ($mychanges as $field_name => $requested_value) {
      $current_value   = CRM_Utils_Array::value($field_name, $contact, '');
      if ($current_value == $requested_value) {
        $change_list[] = "The field '{$field_name}' had already been changed to '{$requested_value}'.";
      } else {
        $contact_updates[$field_name] = $requested_value;
        $change_list[] = "'{$field_name}' has be updated from '{$current_value}' to '{$requested_value}'";
      }
    }

    if (!empty($contact_updates)) {
      $contact_updates['id'] = $contact['id'];
      civicrm_api3('Contact', 'create', $contact_updates);
    }

    return $change_list;
  }
}
