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
class CRM_I3val_Handler_PhoneUpdate extends CRM_I3val_Handler_DetailUpdate {

  public static $group_name = 'i3val_phone_updates';
  public static $field2label = NULL;

  protected static function getField2Label() {
    if (self::$field2label === NULL) {
      self::$field2label = array( 'phone'         => E::ts('Phone'),
                                  'phone_type'    => E::ts('Phone Type'),
                                  'location_type' => E::ts('Location Type'));
    }
    return self::$field2label;
  }

  /**
   * get the main key/identifier for this handler
   */
  public function getKey() {
    return 'phone';
  }

  /**
   * get the list of
   */
  public function getFields() {
    $field2label = self::getField2Label();
    return array_keys($field2label);
  }

  /**
   * Get the main attributes. If these are not present,
   *  no record at all is created
   */
  protected function getMainFields() {
    return array('phone', 'phone_type');
  }

  /**
   * Get the JSON specification file defining the custom group used for this data
   */
  public function getCustomGroupSpeficationFile() {
    return 'phone_updates_custom_group.json';
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
  public function verifyChanges($activity, $values, $objects = array()) {
    // TODO: check?
    return array();
  }

  /**
   * Apply the changes
   *
   * @return array with changes to the activity
   */
  public function applyChanges($activity, $values, $objects = array()) {
    $activity_update = array();
    if (!$this->hasData($activity)) {
      // NO DATA, no updates
      return $activity_update;
    }

    $phone_update = array();
    $prefix = $this->getKey() . '_';
    $action = CRM_Utils_Array::value('i3val_phone_updates_action', $values, '');
    switch ($action) {
      case 'add_primary':
        $phone_update['is_primary'] = 1;
      case 'add':
        $activity_update[self::$group_name . ".action"] = E::ts("New phone added.");
        $phone_update['contact_id'] = $values['contact_id'];
        $this->applyUpdateData($phone_update, $values, '%s', "{$prefix}%s_applied");
        $this->applyUpdateData($activity_update, $values, self::$group_name . '.%s_applied', "{$prefix}%s_applied");
        break;

      case 'update':
        $activity_update[self::$group_name . ".action"]= E::ts("Phone updated");
        $phone_update['id']         = $values['i3val_phone_updates_phone_id'];
        $phone_update['contact_id'] = $values['contact_id']; // not necessary, but causes notices in 4.6
        $this->applyUpdateData($phone_update, $values, '%s', "{$prefix}%s_applied");
        $this->applyUpdateData($activity_update, $values, self::$group_name . '.%s_applied', "{$prefix}%s_applied");
        break;

      case 'duplicate':
        $activity_update[self::$group_name . ".action"] = E::ts("Entry already existed.");
        break;

      default:
      case 'discard':
        $activity_update[self::$group_name . ".action"] = E::ts("Data discarded.");
        break;
    }

    if (!empty($phone_update)) {
      // perform update
      $this->resolveFields($phone_update);
      error_log("PHONE UPDATE: " . json_encode($phone_update));
      civicrm_api3('Phone', 'create', $phone_update);
    }

    return $activity_update;
  }



  /**
   * Load and assign necessary data to the form
   */
  public function renderActivityData($activity, $form) {
    $field2label = self::getField2Label();
    $prefix = $this->getKey() . '_';
    $values = $this->compileValues(self::$group_name, $field2label, $activity);

    // find existing phone
    $default_action  = 'add';
    $phone_submitted = $this->getMyValues($activity);
    $phone_submitted['contact_id'] = $form->contact['id'];
    $existing_phone = $this->getExistingPhone($phone_submitted, $default_action);
    if ($existing_phone) {
      $form->add('hidden', 'i3val_phone_updates_phone_id', $existing_phone['id']);
      $this->addCurrentValues($values, $existing_phone);
    } else {
      $form->add('hidden', 'i3val_phone_updates_phone_id', 0);
    }

    $this->applyUpdateData($form_values, $values, "{$prefix}%s");
    $form->assign('i3val_phone_values', $form_values);
    $form->assign('i3val_phone_fields', $field2label);

    // create input fields and apply checkboxes
    $active_fields = array();
    foreach ($field2label as $fieldname => $fieldlabel) {
      $form_fieldname = "{$prefix}{$fieldname}";

      // LOCATION TYPE is always there...
      if ($fieldname=='location_type') {
        // will be added below, since it clashes with the other fields
        $active_fields[$form_fieldname] = $fieldlabel;

        // add the text input
        $form->add(
          'select',
          "{$form_fieldname}_applied",
          $fieldlabel,
          $this->getLocationTypeList(),
          FALSE,
          array('class' => 'crm-select2')
        );

        if (isset($values[$fieldname]['submitted'])) {
          $matching_location_type = $this->getMatchingLocationType($values[$fieldname]['submitted']);
        } else {
          $matching_location_type = $this->getDefaultLocationType();
        }
        $form->setDefaults(array("{$form_fieldname}_applied" => $matching_location_type['display_name']));

      } elseif ($fieldname=='phone_type') {
        // will be added below, since it clashes with the other fields
        $active_fields[$form_fieldname] = $fieldlabel;

        // add the text input
        $form->add(
          'select',
          "{$form_fieldname}_applied",
          $fieldlabel,
          $this->getOptionValueList('phone_type'),
          FALSE,
          array('class' => 'crm-select2')
        );

        if (isset($values[$fieldname]['submitted'])) {
          $matching_phone_type = $this->getMatchingOptionValue('phone_type', $values[$fieldname]['submitted']);
        } else {
          $matching_phone_type = $this->getDefaultOptionValue('phone_type');
        }
        $form->setDefaults(array("{$form_fieldname}_applied" => $matching_phone_type['label']));


      } else {
        // if there is no values, omit field
        if (empty($values[$fieldname]['submitted'])) {
          continue;
        }

        // this field has data:
        $active_fields[$form_fieldname] = $fieldlabel;

        $form->add(
          'text',
          "{$form_fieldname}_applied",
          $fieldlabel
        );

        // calculate proposed value
        if (!empty($values[$fieldname]['applied'])) {
          $form->setDefaults(array("{$form_fieldname}_applied" => $values[$fieldname]['applied']));
        } else {
          $form->setDefaults(array("{$form_fieldname}_applied" => $values[$fieldname]['submitted']));
        }
      }
    }

    // add processing options
    $form->add(
      'select',
      "i3val_phone_updates_action",
      E::ts("Action"),
      $this->getProcessingOptions($phone_submitted, $existing_phone, 'phone'),
      TRUE,
      array('class' => 'huge crm-select2')
    );
    $form->setDefaults(array("i3val_phone_updates_action" => $default_action));


    $form->assign('i3val_active_phone_fields', $active_fields);
  }

  /**
   * Get the path of the template rendering the form
   */
  public function getTemplate() {
    return 'CRM/I3val/Handler/PhoneUpdate.tpl';
  }


  /**
   * Calculate the data to be created and add it to the $activity_data Activity.create params
   * @todo specify
   */
  public function generateDiffData($entity, $submitted_data, &$activity_data) {
    // make sure the location type is resolved
    $this->resolveFields($submitted_data);

    switch ($entity) {
      case 'Contact':
        $submitted_data['contact_id'] = $submitted_data['id'];
        $phone = $this->getExistingPhone($submitted_data);
        $this->generateEntityDiffData('Phone', $phone['id'], $phone, $submitted_data, $activity_data);
        break;

      case 'Phone':
        $phone = civicrm_api3('Phone', 'getsingle', array('id' => $submitted_data['id']));
        $this->resolveFields($phone);
        $this->generateEntityDiffData('Phone', $phone['id'], $phone, $submitted_data, $activity_data);
        break;

      default:
        // nothing to do
        break;
    }
  }

  /**
   * Resolve the text field names (e.g. 'location_type')
   *  to their ID representations ('location_type_id').
   */
  protected function resolveFields(&$data, $add_default = FALSE) {
    parent::resolveFields($data, $add_default);

    // resolve phone type
    $this->resolveOptionValueField($data, 'phone_type', 'phone_type');
  }

  /**
   * find a matching phone based on
   *  phone, location_type (and contact_id obviously)
   */
  protected function getExistingPhone($values, &$default_action) {
    $phone_submitted = array();
    $this->applyUpdateData($phone_submitted, $values);
    if (empty($phone_submitted) || empty($values['contact_id'])) {
      // there's nothing we can do
      return NULL;
    }

    // first, make sure that the location type is resolved
    $this->resolveFields($phone_submitted, TRUE);

    // then: load all phones
    $query = civicrm_api3('Phone', 'get', array(
      'contact_id'   => $values['contact_id'],
      'option.limit' => 0,
      'option.sort'  => 'is_primary desc',
      'sequential'   => 1));
    $phones = $query['values'];

    // first: find by exact values
    foreach ($phones as $phone) {
      if ($this->hasEqualMainFields($phone_submitted, $phone)) {
        $this->resolveFields($phone);
        if ($phone_submitted['location_type'] == $phone['location_type']) {
          // even the location type is identical
          if ($this->hasEqualMainFields($phone_submitted, $phone, TRUE)) {
            $default_action = 'duplicate';
          } else {
            $default_action = 'update';
          }
        } else {
          // location type differs
          $default_action = 'update';
        }
        return $phone;
      }
    }

    // second: find by location type
    if (isset($values['location_type_id'])) {
      foreach ($phones as $phone) {
        if ($values['location_type_id'] == $phone['location_type_id']) {
          $this->resolveFields($phone);
          $default_action = 'update';
          return $phone;
        }
      }
    }

    // third: find by similarity
    $best_matching_phone = NULL;
    $highest_similarity    = 0;
    foreach ($phones as $phone) {
      $similarity = $this->getMainFieldSimilarity($phone_submitted, $phone);
      if ($similarity > $highest_similarity) {
        $highest_similarity = $similarity;
        $best_matching_phone = $phone;
      }
    }
    $this->resolveFields($best_matching_phone);
    $default_action = 'add';
    return $best_matching_phone;
  }
}
