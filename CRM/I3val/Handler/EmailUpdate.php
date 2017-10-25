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

use CRM_I3val_ExtensionUtil as E;

/**
 * this class will handle performing the changes
 *  that are passed on from the API call
 */
class CRM_I3val_Handler_EmailUpdate extends CRM_I3val_ActivityHandler {

  public static $group_name = 'i3val_email_updates';
  public static $field2label = NULL;

  protected static function getField2Label() {
    if (self::$field2label === NULL) {
      self::$field2label = array( 'email'         => E::ts('Email'),
                                  'location_type' => E::ts('Location Type'));
    }
    return self::$field2label;
  }

  /**
   * get the list of
   */
  public function getFields() {
    $field2label = self::getField2Label();
    return array_keys($field2label);
  }

  /**
   * Get the JSON specification file defining the custom group used for this data
   */
  public function getCustomGroupSpeficationFile() {
    return 'email_updates_custom_group.json';
  }

  /**
   * Get the custom group name
   */
  public function getCustomGroupName() {
    return self::$group_name;
  }

  /**
   * Verify whether the changes make sense
   *
   * @return array $key -> error message
   */
  public function verifyChanges($activity, $changes, $objects = array()) {
    // TODO: check?
    return array();
  }

  /**
   * Apply the changes
   *
   * @return array with changes to the activity
   */
  public function applyChanges($activity, $changes, $objects = array()) {
    $activity_update = array();
    $contact_update = array();
    $contact = $objects['contact'];
    $my_changes = $this->getMyChanges($changes);

    // compile update
    foreach ($my_changes as $fieldname => $value) {
      if ($value != $contact[$fieldname]) {
        $contact_update[$fieldname] = $value;
      }
      $activity_update[self::$group_name . ".{$fieldname}_submitted"] = $value;
    }
    $contact = $objects['contact'];

    // execute update
    if (!empty($contact_update)) {
      $contact_update['id'] = $contact['id'];
      error_log("UPDATE address " . json_encode($contact_update));
      // civicrm_api3('Contact', 'create', $contact_update);
    }

    return $activity_update;
  }



  /**
   * Load and assign necessary data to the form
   */
  public function renderActivityData($activity, $form) {
    $field2label = self::getField2Label();
    $values = $this->compileValues(self::$group_name, $field2label, $activity);
    $this->addCurrentValues($values, $form->contact);

    $form->assign('i3val_email_fields', $field2label);
    $form->assign('i3val_email_values', $values);

    // create input fields and apply checkboxes
    $active_fields = array();
    foreach ($field2label as $fieldname => $fieldlabel) {
      // if there is no values, omit field
      if (empty($values[$fieldname]['submitted'])) {
        continue;
      }

      // this field has data:
      $active_fields[$fieldname] = $fieldlabel;

      // add the text input
      $form->add(
        'text',
        "{$fieldname}_applied",
        $fieldlabel
      );

      if (!empty($values[$fieldname]['applied'])) {
        $form->setDefaults(array("{$fieldname}_applied" => $values[$fieldname]['applied']));
      } else {
        $form->setDefaults(array("{$fieldname}_applied" => $values[$fieldname]['submitted']));
      }

      // add the apply checkbox
      $form->add(
        'checkbox',
        "{$fieldname}_apply",
        $fieldlabel
      );
      $form->setDefaults(array("{$fieldname}_apply" => 1));
    }

    $form->assign('i3val_active_email_fields', $active_fields);
  }

  /**
   * Get the path of the template rendering the form
   */
  public function getTemplate() {
    return 'CRM/I3val/Handler/EmailUpdate.tpl';
  }


  /**
   * Calculate the data to be created and add it to the $activity_data Activity.create params
   * @todo specify
   */
  public function createData($entity, $entity_id, $entity_data, $submitted_data, &$activity_data) {
    // TODO: entity = Address

    $raw_diff = $this->createDiff($entity_data, $submitted_data);
    if ($raw_diff) {
      // address updates work differently: if there is a change, add ALL address fields
      $field_names = $this->getFields();
      $custom_group_name = $this->getCustomGroupName();
      foreach ($field_names as $field_name) {
        $activity_data["{$custom_group_name}.{$field_name}_original"] = CRM_Utils_Array::value($fieldname, $entity_data, '');

        if (isset($submitted_data[$field_name])) {
          $activity_data["{$custom_group_name}.{$field_name}_submitted"] = $submitted_data[$field_name];
        }
      }
    }
  }
}
