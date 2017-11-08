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
class CRM_I3val_Handler_SddUpdate extends CRM_I3val_ActivityHandler {

  public static $group_name = 'i3val_sdd_updates';
  public static $field2label = NULL;

  protected static function getField2Label() {
    if (self::$field2label === NULL) {
      self::$field2label = array( 'reference'       => E::ts('Mandate Reference'),
                                  'source'          => E::ts('Source'),
                                  'iban'            => E::ts('IBAN'),
                                  'bic'             => E::ts('BIC'),
                                  'date'            => E::ts('Signature Date'),
                                  'validation_date' => E::ts('Validation Date'),
                                  'start_date'      => E::ts('Start Date'),
                                  'end_date'        => E::ts('End Date'),
                                  'frequency'       => E::ts('Frequency'),
                                  'cycle_day'       => E::ts('Cycle Day'),
                                  'financial_type'  => E::ts('Financial Type'),
                                  'campaign'        => E::ts('Campaign'),
                                  'amount'          => E::ts('Amount'),
                                );
    }
    return self::$field2label;
  }

  /**
   * get the main key/identifier for this handler
   */
  public function getKey() {
    return 'sdd';
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
    // is there any restriction?
    return array('source', 'iban', 'bic', 'date', 'validation_date', 'start_date', 'end_date', 'frequency', 'cycle_day', 'financial_type', 'campaing', 'amount');
  }

  /**
   * Get the JSON specification file defining the custom group used for this data
   */
  public function getCustomGroupSpeficationFile() {
    return 'sdd_updates_custom_group.json';
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

    $action = CRM_Utils_Array::value('i3val_sdd_updates_action', $values, '');
    if ($action) {
      $prefix = $this->getKey() . '_';

      $reference = $activity[self::$group_name . ".reference"];
      $old_mandate = $this->getMandate(array('reference' => $reference));
      $change_date = date('YmdHis');

      // CREATE NEW MANDATE
      $new_mandate = array();
      $this->applyUpdateData($new_mandate, $values, '%s', "{$prefix}%s_applied");
      // copy all fields from the old mandate
      foreach ($old_mandate as $key => $value) {
        if (empty($new_mandate[$key])) {
          $new_mandate[$key] = $old_mandate[$key];
        }
      }
      // some adjustments...
      unset($new_mandate['reference']);
      $new_mandate['start_date'] = $change_date;

      // TODO: fix dates?

      error_log("CREATE NEW " . json_encode($new_mandate));
      // civicrm_api3('SepaMandate', 'createfull', $new_mandate);


      // CANCEL the old mandate
      // FIXME: use "now" instead of "today" once that's fixed in CiviSEPA
      // CRM_Sepa_BAO_SEPAMandate::terminateMandate($old_mandate['id'], "today", 'i3val');

      // update data
      $this->applyUpdateData($activity_update, $values, self::$group_name . '.%s_applied', "{$prefix}%s_applied");

      $activity_update[self::$group_name . ".action"] = E::ts("Created replacement mandate.");

    } else {
      $activity_update[self::$group_name . ".action"] = E::ts("Data discarded.");
    }

    // $email_update = array();
    // $prefix = $this->getKey() . '_';
    //
    // switch ($action) {
    //   case 'add_primary':
    //     $email_update['is_primary'] = 1;
    //   case 'add':
    //     $activity_update[self::$group_name . ".action"] = E::ts("New email added.");
    //     $email_update['contact_id'] = $values['contact_id'];
    //     $this->applyUpdateData($email_update, $values, '%s', "{$prefix}%s_applied");
    //     $this->applyUpdateData($activity_update, $values, self::$group_name . '.%s_applied', "{$prefix}%s_applied");
    //     break;

    //   case 'update':
    //     $activity_update[self::$group_name . ".action"]= E::ts("Email updated");
    //     $email_update['id']         = $values['i3val_email_updates_email_id'];
    //     $email_update['contact_id'] = $values['contact_id']; // not necessary, but causes notices in 4.6
    //     $this->applyUpdateData($email_update, $values, '%s', "{$prefix}%s_applied");
    //     $this->applyUpdateData($activity_update, $values, self::$group_name . '.%s_applied', "{$prefix}%s_applied");
    //     break;

    //   case 'duplicate':
    //     $activity_update[self::$group_name . ".action"] = E::ts("Entry already existed.");
    //     break;

    //   default:
    //   case 'discard':
    //     $activity_update[self::$group_name . ".action"] = E::ts("Data discarded.");
    //     break;
    // }

    // if (!empty($email_update)) {
    //   // perform update
    //   $this->resolveFields($email_update);
    //   error_log("EMAIL UPDATE: " . json_encode($email_update));
    //   civicrm_api3('Email', 'create', $email_update);
    // }

    return $activity_update;
  }



  /**
   * Load and assign necessary data to the form
   */
  public function renderActivityData($activity, $form) {
    $field2label = self::getField2Label();
    $group_name  = $this->getCustomGroupName();
    $prefix      = $this->getKey() . '_';
    $values      = $this->compileValues(self::$group_name, $field2label, $activity);

    // find existing mandate
    $existing_mandate = $this->getMandate(array('reference' => $activity["{$group_name}.reference"]));

    $form->add('hidden', 'i3val_sdd_updates_mandate_id', $existing_mandate['id']);
    $this->resolveFields($existing_mandate);
    $this->addCurrentValues($values, $existing_mandate);

    // set the frequency labels
    if (isset($values['frequency']['original'])) {
      $frequency = $values['frequency'];
      $values['frequency']['original']  = $this->getFrequencyLabel($values['frequency']['original']);
      $values['frequency']['submitted'] = $this->getFrequencyLabel($values['frequency']['submitted']);
      $values['frequency']['current']   = $this->getFrequencyLabel($values['frequency']['current']);
    }

    $this->applyUpdateData($form_values, $values, "{$prefix}%s");
    $form->assign('i3val_sdd_values', $form_values);
    $form->assign('i3val_sdd_fields', $field2label);
    $form->assign('i3val_sdd_mandate', $existing_mandate);

    // create input fields and apply checkboxes
    $active_fields = array();
    foreach ($field2label as $fieldname => $fieldlabel) {
      $form_fieldname = "{$prefix}{$fieldname}";

      // if there is no values, omit field
      if (empty($values[$fieldname]['submitted']) && empty($values[$fieldname]['original'])) {
        continue;
      }

      // this field has data:
      $active_fields[$form_fieldname] = $fieldlabel;

      if (strstr($fieldname, 'date')) {
        // date field
        $form->addDate(
          "{$fieldname}_applied",
          $fieldlabel,
          FALSE,
          array('formatType' => 'activityDate')
        );

      } elseif ($fieldname == 'frequency') {
        // frequency dropdown
        $form->add(
          'select',
          "{$form_fieldname}_applied",
          $fieldlabel,
          $this->getFrequencyList(),
          FALSE,
          array('class' => 'crm-select2')
        );

        if (!empty($frequency['submitted'])) {
          // error_log("FREQ " . json_encode($frequency));
          $form->setDefaults(array("{$form_fieldname}_applied" => $frequency['submitted']));
        } else {
          $form->setDefaults(array("{$form_fieldname}_applied" => $frequency['original']));
        }
        continue; // don't let them overwrite our defaults

      } else {
        // text field
        $form->add(
          'text',
          "{$form_fieldname}_applied",
          $fieldlabel
        );
      }

      // calculate proposed value
      if (!empty($values[$fieldname]['applied'])) {
        $form->setDefaults(array("{$form_fieldname}_applied" => $values[$fieldname]['applied']));
      } elseif (!empty($values[$fieldname]['submitted'])) {
        $form->setDefaults(array("{$form_fieldname}_applied" => $values[$fieldname]['submitted']));
      } else {
        $form->setDefaults(array("{$form_fieldname}_applied" => $values[$fieldname]['original']));
      }
    }

    // add processing options
    $form->add(
      'select',
      "i3val_sdd_updates_action",
      E::ts("Action"),
      array(0 => E::ts("Don't apply"), 1 => E::ts("Create replacement mandate")),
      TRUE,
      array('class' => 'huge crm-select2')
    );
    $form->setDefaults(array("i3val_sdd_updates_action" => 1));


    $form->assign('i3val_active_sdd_fields', $active_fields);
  }

  /**
   * Get the path of the template rendering the form
   */
  public function getTemplate() {
    return 'CRM/I3val/Handler/SddUpdate.tpl';
  }

  /**
   * Resolve the text field names (e.g. 'location_type')
   *  to their ID representations ('location_type_id').
   */
  protected function resolveFields(&$data, $add_default = FALSE) {
    parent::resolveFields($data, $add_default);

    // resolve frequency
    if (!empty($data['frequency'])) {
      $data['frequency_unit']     = 'month';
      $data['frequency_interval'] = 12 / $data['frequency'];

    } elseif (!empty($data['frequency_interval']) && !empty($data['frequency_unit'])) {
      if ($data['frequency_unit'] == 'year') {
        $data['frequency'] = $data['frequency_interval'] = (12 / $data['frequency_interval']) / 12;
      } elseif ($data['frequency_unit'] == 'month') {
        $data['frequency'] = 12 / $data['frequency_interval'];
      }
    }

    // TODO: more
  }

  /**
   * get the lables for the numeric freuquency numbers
   */
  protected function getFrequencyLabel($frequency) {
    $frequency = (int) $frequency;
    switch ($frequency) {
      case 0:
        return '';
      case 1:
        return E::ts('annually');
      case 2:
        return E::ts('semi-annually');
      case 3:
        return E::ts('trimestral');
      case 4:
        return E::ts('quarterly');
      case 6:
        return E::ts('bi-monthly');
      case 12:
        return E::ts('monthly');
      default:
        return E::ts('every %1 months', array(1 => $frequency));
    }
  }

  /**
   * get a list of the eligible frequency labels
   */
  protected function getFrequencyList($label_only = FALSE) {
    $wanted_frequencies = array(1, 2, 3, 4, 6, 12); // TODO: move to config
    $list = array();
    foreach ($wanted_frequencies as $frequency) {
      $label = $this->getFrequencyLabel($frequency);
      if ($label_only) {
        $list[$label] = $label;
      } else {
        $list[$frequency] = $label;
      }
    }
    return $list;
  }

  /**
   * get the mandate based on ID or reference
   */
  protected function getMandate($params) {
    // first: find the mandate
    $mandate_search = array();
    if (!empty($params['id'])) {
      $mandate_search['id'] = $params['id'];
    }
    if (!empty($params['reference'])) {
      $mandate_search['reference'] = $params['reference'];
    }
    if (empty($mandate_search)) {
      throw new Exception("SepaMandate updates need id or reference.", 1);
    }

    try {
      $mandate = civicrm_api3('SepaMandate', 'getsingle', $mandate_search);
      if ($mandate['entity_table'] == 'civicrm_contribution_recur') {
        $contribution = civicrm_api3('ContributionRecur', 'getsingle', array('id' => $mandate['entity_id']));
      } else {
        $contribution = civicrm_api3('Contribution', 'getsingle', array('id' => $mandate['entity_id']));
      }
      return array_merge($mandate, $contribution);
    } catch (Exception $ex) {
      throw new Exception("SepaMandate not found.", 1);
    }
  }

  /**
   * Calculate the data to be created and add it to the $activity_data Activity.create params
   * @todo specify
   */
  public function generateDiffData($entity, $entity_id, $entity_data, $submitted_data, &$activity_data) {
    // works with entity 'SepaMandate'
    if ($entity != 'SepaMandate') {
      throw new Exception("SepaMandate can only be performed on SepaMandate.request_update.", 1);
    }

    // load mandate
    $mandate = $this->getMandate($submitted_data);

    // some checks
    if ($mandate['type'] == 'OOFF') {
      throw new Exception("Cannot update OOFF mandates", 1);
    }
    if ($mandate['status'] == 'COMPLETED' || $mandate['status'] == 'INVALID' || $mandate['contribution_status_id'] != 2) {
      throw new Exception("Mandate is already closed", 1);
    }

    // OK, we have the mandate, look for differences
    $mandate_diff       = array();
    $main_attributes   = $this->getMainFields();
    $all_attributes    = $this->getFields();
    $custom_group_name = $this->getCustomGroupName();

    $this->resolveFields($submitted_data);
    $this->resolveFields($mandate);
    error_log("MANDATE " . json_encode($mandate));

    // first: check all main attriutes for differences
    $differing_attributes = array();
    foreach ($main_attributes as $field_name) {
      if (isset($submitted_data[$field_name])) {
        // an update was submitted
        $original_value = CRM_Utils_Array::value($field_name, $mandate, '');
        if ($submitted_data[$field_name] != $original_value) {
          $differing_attributes[] = $field_name;
          $mandate_diff["{$custom_group_name}.{$field_name}_submitted"] = $submitted_data[$field_name];
          $mandate_diff["{$custom_group_name}.{$field_name}_original"]  = $original_value;
        }
      }
    }

    // BIC and IBAN should be together
    if (in_array('bic', $differing_attributes) || in_array('iban', $differing_attributes)) {
      $mandate_diff["{$custom_group_name}.bic_submitted"]  = CRM_Utils_Array::value('bic', $submitted_data, '');
      $mandate_diff["{$custom_group_name}.bic_original"]   = CRM_Utils_Array::value('bic', $mandate, '');
      $mandate_diff["{$custom_group_name}.iban_submitted"] = CRM_Utils_Array::value('iban', $submitted_data, '');
      $mandate_diff["{$custom_group_name}.iban_original"]  = CRM_Utils_Array::value('iban', $mandate, '');
    }

    // amount and frequency should be together
    if (in_array('amount', $differing_attributes) || in_array('frequency', $differing_attributes)) {
      $mandate_diff["{$custom_group_name}.amount_submitted"]    = CRM_Utils_Array::value('amount', $submitted_data, '');
      $mandate_diff["{$custom_group_name}.amount_original"]     = CRM_Utils_Array::value('amount', $mandate, '');
      $mandate_diff["{$custom_group_name}.frequency_submitted"] = CRM_Utils_Array::value('frequency', $submitted_data, '');
      $mandate_diff["{$custom_group_name}.frequency_original"]  = CRM_Utils_Array::value('frequency', $mandate, '');
    }

    // check if there is a difference
    if (!empty($mandate_diff)) {
      // there is a difference -> add to activity
      foreach ($mandate_diff as $key => $value) {
        $activity_data[$key] = $value;
      }

      // add some basic data
      $activity_data['target_id'] = $mandate['contact_id'];
      $activity_data["{$custom_group_name}.reference"] = $mandate['reference'];
    }
  }
}
