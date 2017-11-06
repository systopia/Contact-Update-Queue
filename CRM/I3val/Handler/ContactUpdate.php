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
class CRM_I3val_Handler_ContactUpdate extends CRM_I3val_ActivityHandler {

  public static $group_name = 'i3val_contact_updates';
  public static $field2label = NULL;

  protected static function getField2Label() {
    if (self::$field2label === NULL) {
      self::$field2label = array( 'first_name'        => E::ts('First Name'),
                                  'last_name'         => E::ts('Last Name'),
                                  'organization_name' => E::ts('Organisation Name'),
                                  'household_name'    => E::ts('Household Name'),
                                  'prefix'            => E::ts('Prefix'),
                                  'suffix'            => E::ts('Suffix'),
                                  'gender'            => E::ts('Gender'),
                                  'birth_date'        => E::ts('Birth Date'));
    }
    return self::$field2label;
  }

  /**
   * get the main key/identifier for this handler
   */
  public function getKey() {
    return 'contact';
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
    return 'contact_updates_custom_group.json';
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
    $contact = $objects['contact'];
    $activity_update = array();
    if (!$this->hasData($activity)) {
      // NO DATA, no updates
      return $activity_update;
    }

    // calculate generic update
    $this->applyUpdateData($contact_update, $values, '%s', "%s_applied");
    $this->applyUpdateData($activity_update, $values, self::$group_name . '.%s_applied', "%s_applied");

    // remove the ones that are not flagged as 'apply'
    foreach (array_keys($contact_update) as $key) {
      $apply_key = "{$key}_apply";
      if (empty($values[$apply_key])) {
        unset($contact_update[$key]);
      }
    }

    // execute update
    if (!empty($contact_update)) {
      $contact_update['id'] = $contact['id'];
      $this->resolveFields($contact_update);
      error_log("UPDATE contact " . json_encode($contact_update));
      civicrm_api3('Contact', 'create', $contact_update);
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

    // exceptions for current values
    if (isset($values['prefix']) && !empty($form->contact['individual_prefix'])) {
      $values['prefix']['current'] = $form->contact['individual_prefix'];
    }
    if (isset($values['suffix']) && !empty($form->contact['individual_suffix'])) {
      $values['suffix']['current'] = $form->contact['individual_suffix'];
    }

    $form->assign('i3val_contact_fields', $field2label);
    $form->assign('i3val_contact_values', $values);


    // create input fields and apply checkboxes
    $active_fields = array();
    foreach ($field2label as $fieldname => $fieldlabel) {
      // if there is no values, omit field
      if (empty($values[$fieldname]['submitted'])) {
        continue;
      }

      // this field has data:
      $active_fields[$fieldname] = $fieldlabel;

      // generate input field
      if (in_array($fieldname, array('prefix', 'suffix', 'gender'))) {
        // add the text input
        $form->add(
          'select',
          "{$fieldname}_applied",
          $fieldlabel,
          $this->getOptionList($fieldname)
        );
      } elseif ($fieldname == 'birth_date') {
        $form->addDate(
          "{$fieldname}_applied",
          $fieldlabel,
          FALSE,
          array('formatType' => 'activityDate')
        );
      } else {
        // add the text input
        $form->add(
          'text',
          "{$fieldname}_applied",
          $fieldlabel
        );
      }

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

    $form->assign('i3val_active_contact_fields', $active_fields);
  }

  /**
   * Get the path of the template rendering the form
   */
  public function getTemplate() {
    return 'CRM/I3val/Handler/ContactUpdate.tpl';
  }

  /**
   * Calculate the data to be created and add it to the $activity_data Activity.create params
   * @todo specify
   */
  public function generateDiffData($entity, $entity_id, $entity_data, $submitted_data, &$activity_data) {
    // resolve prefix/suffix
    if (!empty($entity_data['individual_prefix'])) {
      $entity_data['prefix'] = $entity_data['individual_prefix'];
    }
    if (!empty($entity_data['individual_suffix'])) {
      $entity_data['suffix'] = $entity_data['individual_suffix'];
    }

    $this->resolveFields($entity_data);
    $this->resolveFields($submitted_data);

    $raw_diff = $this->createDiff($entity_data, $submitted_data);
    // TODO: sort out special cases (e.g. dates)

    foreach ($raw_diff as $key => $value) {
      $activity_data[$key] = $value;
    }
  }

  /**
   * Resolve the text field names (e.g. 'gender')
   *  to their ID representations ('gender_id').
   */
  protected function resolveFields(&$data, $add_default = FALSE) {
    parent::resolveFields($data, $add_default);
    $this->resolveOptionValueField($data, 'gender', 'gender');
    $this->resolveOptionValueField($data, 'individual_prefix', 'prefix');
    $this->resolveOptionValueField($data, 'individual_suffix', 'suffix');
  }


  /**
   * Get dropdown lists
   */
  protected function getOptionList($fieldname) {
    $option_group_name = NULL;

    switch ($fieldname) {
      case 'gender':
        return $this->getOptionValueList('gender');

      case 'prefix':
        return $this->getOptionValueList('individual_prefix');

      case 'suffix':
        return $this->getOptionValueList('individual_suffix');

      default:
        return $this->getOptionValueList($fieldname);
    }
  }
}
