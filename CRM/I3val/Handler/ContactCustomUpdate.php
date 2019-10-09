<?php
/*-------------------------------------------------------+
| Ilja's Input Validation Extension                      |
| Amnesty International Vlaanderen                       |
| Copyright (C) 2019 SYSTOPIA                            |
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
class CRM_I3val_Handler_ContactCustomUpdate extends CRM_I3val_ActivityHandler {

  public static $group_name = 'i3val_contact_custom_updates';
  public static $field2label = NULL;

  public function getField2Label() {
    if (self::$field2label === NULL) {
      self::$field2label = ['value' => E::ts('Value')];
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
   * get a human readable name for this handler
   */
  public function getName() {
    return E::ts("Contact Custom Fields Update");
  }

  /**
   * returns a list of CiviCRM entities this handler can process
   */
  public function handlesEntities() {
    return array('Contact');
  }

  /**
   * get the list of
   */
  public function getFields() {
    $field2label = $this->getField2Label();
    return array_keys($field2label);
  }

  /**
   * Get the JSON specification file defining the custom group used for this data
   */
  public function getCustomGroupSpeficationFiles() {
    return array(__DIR__ . '/../../../resources/contact_custom_updates_custom_group.json');
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
    $contact_update = array();
    $this->applyUpdateData($contact_update, $values, '%s', "%s_applied");

    // remove the ones that are not flagged as 'apply'
    foreach (array_keys($contact_update) as $key) {
      $apply_key = "{$key}_apply";
      if (!isset($values[$apply_key]) || !strlen($values[$apply_key])) {
        unset($contact_update[$key]);
      }
    }
    $this->applyUpdateData($activity_update, $contact_update, self::$group_name . '.%s_applied', '%s');

    // execute update
    if (!empty($contact_update)) {
      $contact_update['id'] = $contact['id'];
      $this->resolveFields($contact_update);
      $this->resolvePreferredLanguageToLabel($contact_update, FALSE);
      CRM_I3val_Session::log("UPDATE contact " . json_encode($contact_update));
      civicrm_api3('Contact', 'create', $contact_update);
    }

    return $activity_update;
  }


  /**
   * Load and assign necessary data to the form
   */
  public function renderActivityData($activity, $form) {
    $config = CRM_I3val_Configuration::getConfiguration();
    $field2label = self::getField2Label();
    $values = $this->compileValues(self::$group_name, $field2label, $activity);
    $this->resolvePreferredLanguageToLabel($form->contact);
    $this->addCurrentValues($values, $form->contact);

    // exceptions for current values
    if (isset($values['prefix']) && !empty($form->contact['individual_prefix'])) {
      $values['prefix']['current'] = $form->contact['individual_prefix'];
    }
    if (isset($values['suffix']) && !empty($form->contact['individual_suffix'])) {
      $values['suffix']['current'] = $form->contact['individual_suffix'];
    }

    // create input fields and apply checkboxes
    $active_fields = array();
    $checkbox_fields = array(); // these will be displayed as checkboxes rather than strings

    foreach ($field2label as $fieldname => $fieldlabel) {
      // if there is no values, omit field
      if ($config->clearingFieldsAllowed()) {
        if (empty($values[$fieldname]['submitted']) && empty($values[$fieldname]['original'])) {
          continue;
        }
      } else {
        if (!isset($values[$fieldname]['submitted']) || !strlen($values[$fieldname]['submitted'])) {
          continue;
        }
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

      } elseif ($fieldname == 'birth_date' || $fieldname == 'deceased_date') {
        $form->addDate(
          "{$fieldname}_applied",
          $fieldlabel,
          FALSE,
          array('formatType' => 'activityDate')
        );

        // format date (drop time)
        if (isset($values[$fieldname]['submitted'])) {
          $values[$fieldname]['submitted'] = substr($values[$fieldname]['submitted'], 0, 10);
        }
        if (isset($values[$fieldname]['original'])) {
          $values[$fieldname]['original'] = substr($values[$fieldname]['original'], 0, 10);
        }

      } elseif ($fieldname == 'preferred_language') {
        $form->add(
          'select',
          "{$fieldname}_applied",
          $fieldlabel,
          $this->getOptionValueList('languages', 'label', E::ts("none"))
        );



      } elseif (substr($fieldname, 0, 3) == 'do_' || substr($fieldname, 0, 3) == 'is_') {
        $checkbox_fields[$fieldname] = 1;
        $form->add(
          'checkbox',
          "{$fieldname}_applied",
          $fieldlabel
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
        $form->setDefaults(array("{$fieldname}_applied" => isset($values[$fieldname]['submitted']) ? $values[$fieldname]['submitted'] : ''));
      }

      // add the apply checkbox
      $form->add(
        'checkbox',
        "{$fieldname}_apply",
        $fieldlabel
      );
      $form->setDefaults(array("{$fieldname}_apply" => 1));
    }

    $form->assign('i3val_contact_fields', $field2label);
    $form->assign('i3val_contact_values', $values);
    $form->assign('i3val_active_contact_fields', $active_fields);
    $form->assign('i3val_active_contact_checkboxes', $checkbox_fields);
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
  public function generateDiffData($entity, $submitted_data, &$activity_data) {
    if ($entity != 'Contact') {
      throw new Exception("Can only process Contact entity");
    }

    // make sure the custom fields are in the 'custom_xx' format
    CRM_I3val_CustomData::resolveCustomFields($submitted_data);

    // get all custom fields
    $submitted_custom_data = [];
    $custom_field_ids = [];
    foreach ($submitted_data as $field_name => $field_data) {
      if (preg_match('/^custom_(?<custom_field_id>[0-9]+)$/', $field_name, $match)) {
        $submitted_custom_data[$field_name] = $field_data;
        $custom_field_ids[] = $match['custom_field_id'];
      }
    }

    if (!empty($submitted_custom_data)) {
      // load that custom data
      $current_custom_data = [];
      $custom_data_query = civicrm_api3('CustomValue', 'get', [
          'option.limit' => 0,
          'entity_id'    => $submitted_data['id'],
          'entity_table' => 'civicrm_contact']);
      foreach ($custom_data_query['values'] as $field_id => $field_data) {
        $current_custom_data["custom_{$field_id}"] = $field_data['latest'];
      }

      // resolve / diff
      $change_index = 1;
      foreach ($custom_field_ids as $field_id) {
        $submitted_value = CRM_Utils_Array::value("custom_{$field_id}", $submitted_custom_data, '');
        $current_value   = CRM_Utils_Array::value("custom_{$field_id}", $current_custom_data, '');
        if ($this->differs($field_id, $submitted_value, $current_value)) {
          // there is a difference -> add a record
          $custom_id_field = CRM_I3val_CustomData::getCustomFieldKey(self::$group_name, 'custom_field_id');
          $original_field  = CRM_I3val_CustomData::getCustomFieldKey(self::$group_name, 'value_original');
          $submitted_field = CRM_I3val_CustomData::getCustomFieldKey(self::$group_name, 'value_submitted');
          $activity_data["{$custom_id_field}:-{$change_index}"] = $field_id;
          $activity_data["{$original_field}:-{$change_index}"]  = $current_value;
          $activity_data["{$submitted_field}:-{$change_index}"] = $submitted_value;
          $change_index++;
        }
      }
    }
  }

  /**
   * Check whether the presented values differ in the context of the given custom field
   *
   * @param $field_id integer custom field id
   * @param $value1   mixed   value 1
   * @param $value2   mixed   value 2
   * @return boolean
   */
  protected function differs($field_id, $value1, $value2) {
    // todo: implement
    return $value1 != $value2;
  }

  /**
   * Resolve the text field names (e.g. 'gender')
   *  to their ID representations ('gender_id').
   */
  protected function resolveFields(&$data, $add_default = FALSE) {
    parent::resolveFields($data, $add_default);
    $this->resolveOptionValueField($data, 'gender', 'gender', 'gender_id');
    $this->resolveOptionValueField($data, 'individual_prefix', 'prefix', 'prefix_id');
    $this->resolveOptionValueField($data, 'individual_suffix', 'suffix', 'suffix_id');
  }


  /**
   * Get dropdown lists
   */
  protected function getOptionList($fieldname) {
    $option_group_name = NULL;

    switch ($fieldname) {
      case 'gender':
        return $this->getOptionValueList('gender', 'label', E::ts('none'));

      case 'prefix':
        return $this->getOptionValueList('individual_prefix', 'label', E::ts('none'));

      case 'suffix':
        return $this->getOptionValueList('individual_suffix', 'label', E::ts('none'));

      default:
        return $this->getOptionValueList($fieldname);
    }
  }

  /**
   * Since the brilliant preferred_language field has no *_id
   * counterpart, we are forced to decide whether we want to
   * store the key or the label. The API needs the ID, while
   * our activities will store the label
   */
  protected function resolvePreferredLanguageToLabel(&$data, $to_label = TRUE) {
    if (!empty($data['preferred_language'])) {
      if ($to_label) {
        // we want to make sure that the label is used...
        if (preg_match("#^[a-z]{2}_[A-Z]{2}$#", $data['preferred_language'])) {
          // ... but it is the key -> find the right label
          $languages = $this->getOptionValues('languages', 'name');
          if (isset($languages[$data['preferred_language']])) {
            $data['preferred_language'] = $languages[$data['preferred_language']]['label'];
          } else {
            // not found
            unset($data['preferred_language']);
          }
        }

      } else {
        // we want to make sure that the key is there...
        if (!preg_match("#^[a-z]{2}_[A-Z]{2}$#", $data['preferred_language'])) {
          // ...but it seems like it isn't -> find best match
          $option_value = $this->getMatchingOptionValue('languages', $data['preferred_language'], TRUE, 'name');
          $data['preferred_language'] = $option_value['name'];
        }
      }
    }
  }
}
