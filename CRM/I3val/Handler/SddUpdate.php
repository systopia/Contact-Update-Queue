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

use CRM_I3val_ExtensionUtil as E;

/**
 * this class will handle performing the changes
 *  that are passed on from the API call
 */
class CRM_I3val_Handler_SddUpdate extends CRM_I3val_ActivityHandler {

  public static $group_name = 'i3val_sdd_updates';
  public static $field2label = NULL;

  public function getField2Label() {
    if (self::$field2label === NULL) {
      self::$field2label = [
        'reference'       => E::ts('Mandate Reference'),
        'source'          => E::ts('Source'),
        'iban'            => E::ts('IBAN'),
        'bic'             => E::ts('BIC'),
        'date'            => E::ts('Signature Date'),
        'validation_date' => E::ts('Validation Date'),
        'start_date'      => E::ts('Start Date'),
        'end_date'        => E::ts('End Date'),
        'frequency'       => E::ts('Frequency'),
        'cycle_day'       => E::ts('Cycle Day'),
        'reason'          => E::ts('Reason'),
        'financial_type'  => E::ts('Financial Type'),
        'campaign'        => E::ts('Campaign'),
        'amount'          => E::ts('Amount (Installment)'),
      ];
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
   * get a human readable name for this handler
   */
  public function getName() {
    return E::ts('CiviSEPA Mandate Update');
  }

  /**
   * returns a list of CiviCRM entities this handler can process
   */
  public function handlesEntities() {
    return ['SepaMandate'];
  }

  /**
   * get the list of
   */
  public function getFields() {
    $field2label = $this->getField2Label();
    return array_keys($field2label);
  }

  /**
   * Get the main attributes. If these are not present,
   *  no record at all is created
   */
  protected function getMainFields() {
    // is there any restriction?
    return [
      'source',
      'iban',
      'bic',
      'date',
      'validation_date',
      'start_date',
      'end_date',
      'frequency',
      'cycle_day',
      'financial_type',
      'campaing',
      'amount',
    ];
  }

  /**
   * Get the JSON specification file defining the custom group used for this data
   */
  public function getCustomGroupSpeficationFiles() {
    return [__DIR__ . '/../../../resources/sdd_updates_custom_group.json'];
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
  public function verifyChanges($activity, $values, $objects = []) {
    // TODO: check?
    return [];
  }

  /**
   * Load and assign necessary data to the form
   *
   * phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
   */
  public function renderActivityData($activity, $form) {
  // phpcs:enable
    $field2label   = self::getField2Label();
    $group_name    = $this->getCustomGroupName();
    $prefix        = $this->getKey() . '_';
    $values        = $this->compileValues(self::$group_name, $field2label, $activity);
    $active_fields = [];

    // find existing mandate
    $existing_mandate = $this->getMandate(['reference' => $activity["{$group_name}.reference"]]);

    $form->add('hidden', 'i3val_sdd_updates_mandate_id', $existing_mandate['id']);
    $this->resolveFields($existing_mandate);
    $this->addCurrentValues($values, $existing_mandate);

    // set the frequency labels
    if (isset($values['frequency']['original'])) {
      $frequency = $values['frequency'];

      foreach (['original', 'submitted', 'current'] as $key) {
        if (isset($values['frequency'][$key])) {
          $values['frequency'][$key] = $this->getFrequencyLabel($values['frequency'][$key]);
        }
        else {
          $values['frequency'][$key] = '';
        }
      }
    }

    // SPECIAL CASE: SOMEBODY WANTS TO CANCEL THE MANDATE
    $requested_status = $activity["{$group_name}.status"] ?? '';
    if ($requested_status == 'COMPLETE' || $requested_status == 'INVALID') {
      $this->renderCancelMandate($activity, $form, $existing_mandate);
      return;
    }

    // take special care of amounts
    if (isset($values['amount']['submitted']) && $values['amount']['submitted'] == '0.00') {
      $values['amount']['submitted'] = '';
    }

    // add cancel reason
    $form_values["{$prefix}reason"]['submitted'] = $activity["{$group_name}.reason_submitted"] ?? '';
    $form->add(
      'text',
      "{$prefix}reason_applied",
      E::ts('Update Reason')
    );
    $form->setDefaults(["{$prefix}reason_applied" => $form_values["{$prefix}reason"]['submitted']]);
    $active_fields["{$prefix}reason"] = E::ts('Update Reason');
    $field2label['reason'] = E::ts('Update Reason');

    $this->applyUpdateData($form_values, $values, "{$prefix}%s");
    $form->assign('i3val_sdd_values', $form_values);
    $form->assign('i3val_sdd_fields', $field2label);
    $form->assign('i3val_sdd_mandate', $existing_mandate);

    // create input fields and apply checkboxes
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
          ['formatType' => 'activityDate']
        );

      }
      elseif ($fieldname == 'frequency') {
        // frequency dropdown
        $form->add(
          'select',
          "{$form_fieldname}_applied",
          $fieldlabel,
          $this->getFrequencyList(),
          FALSE,
          ['class' => 'crm-select2']
              );

        if (!empty($frequency['submitted'])) {
          $form->setDefaults(["{$form_fieldname}_applied" => $frequency['submitted']]);
        }
        else {
          $form->setDefaults(["{$form_fieldname}_applied" => $frequency['original'] ?? NULL]);
        }
        // don't let them overwrite our defaults
        continue;

      }
      else {
        // text field
        $form->add(
          'text',
          "{$form_fieldname}_applied",
          $fieldlabel
              );
      }

      // calculate proposed value
      if (!empty($values[$fieldname]['applied'])) {
        $form->setDefaults(["{$form_fieldname}_applied" => $values[$fieldname]['applied']]);
      }
      elseif (!empty($values[$fieldname]['submitted'])) {
        $form->setDefaults(["{$form_fieldname}_applied" => $values[$fieldname]['submitted']]);
      }
      else {
        $form->setDefaults(["{$form_fieldname}_applied" => $values[$fieldname]['original']]);
      }
    }

    // add processing options
    $form->add(
      'select',
      'i3val_sdd_updates_action',
      E::ts('Action'),
      [0 => E::ts("Don't apply"), 1 => E::ts('Update Mandate')],
      TRUE,
      ['class' => 'huge crm-select2']
    );
    $form->setDefaults(['i3val_sdd_updates_action' => 1]);

    // calculate error fields
    $error_fields = [];
    if (isset($values['iban']['submitted'])) {
      $error = CRM_Sepa_Logic_Verification::verifyIBAN($values['iban']['submitted']);
      if ($error) {
        $error_fields["{$prefix}iban_submitted"] = $error;
      }
    }

    $form->assign('i3val_sdd_errors', json_encode($error_fields));
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

    }
    elseif (!empty($data['frequency_interval']) && !empty($data['frequency_unit'])) {
      if ($data['frequency_unit'] == 'year') {
        $data['frequency'] = $data['frequency_interval'] = (12 / $data['frequency_interval']) / 12;
      }
      elseif ($data['frequency_unit'] == 'month') {
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
        return E::ts('every %1 months', [1 => $frequency]);
    }
  }

  /**
   * get a list of the eligible frequency labels
   */
  protected function getFrequencyList($label_only = FALSE) {
    // TODO: move to config
    $wanted_frequencies = [1, 2, 3, 4, 6, 12];
    $list = [];
    foreach ($wanted_frequencies as $frequency) {
      $label = $this->getFrequencyLabel($frequency);
      if ($label_only) {
        $list[$label] = $label;
      }
      else {
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
    $mandate_search = [];
    if (!empty($params['id'])) {
      $mandate_search['id'] = $params['id'];
    }
    if (!empty($params['reference'])) {
      $mandate_search['reference'] = $params['reference'];
    }
    if (empty($mandate_search)) {
      throw new Exception('SepaMandate updates need id or reference.', 1);
    }

    try {
      $mandate = civicrm_api3('SepaMandate', 'getsingle', $mandate_search);
      if ($mandate['entity_table'] == 'civicrm_contribution_recur') {
        $contribution = civicrm_api3('ContributionRecur', 'getsingle', ['id' => $mandate['entity_id']]);
      }
      else {
        $contribution = civicrm_api3('Contribution', 'getsingle', ['id' => $mandate['entity_id']]);
      }
      $mandate_data = array_merge($contribution, $mandate);
      $this->resolveFields($mandate_data);

      // render url
      $mandate_data['url'] = CRM_Utils_System::url('civicrm/sepa/xmandate', 'reset=1&mid=' . $mandate_data['id'], TRUE);

      return $mandate_data;
    }
    catch (Exception $ex) {
      throw new Exception('SepaMandate not found.', 1, $ex);
    }
  }

  /**
   * Calculate the data to be created and add it to the $activity_data Activity.create params
   * @todo specify
   *
   * phpcs:disable Generic.Metrics.CyclomaticComplexity.MaxExceeded
   */
  public function generateDiffData($entity, $submitted_data, &$activity_data) {
  // phpcs:enable
    // works with entity 'SepaMandate'
    if ($entity != 'SepaMandate') {
      throw new Exception('SepaMandate can only be performed on SepaMandate.request_update.', 1);
    }

    // load mandate and other stuff
    $activity_update = [];
    $mandate = $this->getMandate($submitted_data);
    $activity_update['target_id'] = $mandate['contact_id'];
    $custom_group_name = $this->getCustomGroupName();
    $requested_status = $submitted_data['status'] ?? '';

    // add reason
    $activity_update["{$custom_group_name}.reason_submitted"] = $submitted_data['sdd_reason'] ?? '';

    // SPECIAL CASE: SOMEBODY WANTS TO CANCEL THE MANDATE
    if ($requested_status == 'COMPLETE' || $requested_status == 'INVALID') {
      // somebody just wants to cancel the mandate
      if ($mandate['status'] != $requested_status) {
        $activity_data["{$custom_group_name}.reason_submitted"] = $submitted_data['sdd_reason'] ?? '';
        $activity_data['target_id'] = $mandate['contact_id'];
        $activity_data["{$custom_group_name}.reference"] = $mandate['reference'];
        $activity_data["{$custom_group_name}.status"] = $requested_status;
      }
      return;
    }

    // some checks
    if ($mandate['type'] == 'OOFF') {
      throw new Exception('Cannot update OOFF mandates', 1);
    }
    if ($mandate['status'] == 'COMPLETE' || $mandate['status'] == 'INVALID'
      || $mandate['contribution_status_id'] != 2
    ) {
      throw new Exception('Mandate is already closed', 1);
    }

    // OK, we have the mandate, look for differences
    $mandate_diff      = [];
    $main_attributes   = $this->getMainFields();
    $all_attributes    = $this->getFields();
    $custom_group_name = $this->getCustomGroupName();

    $this->resolveFields($submitted_data);
    $this->resolveFields($mandate);

    // first: check all main attriutes for differences
    $differing_attributes = [];
    foreach ($main_attributes as $field_name) {
      if (isset($submitted_data[$field_name])) {
        // an update was submitted
        $original_value = $mandate[$field_name] ?? '';
        if ($submitted_data[$field_name] != $original_value) {
          $differing_attributes[] = $field_name;
          $mandate_diff["{$custom_group_name}.{$field_name}_submitted"] = $submitted_data[$field_name];
          $mandate_diff["{$custom_group_name}.{$field_name}_original"]  = $original_value;
        }
      }
    }

    // BIC and IBAN should be together
    if (in_array('bic', $differing_attributes) || in_array('iban', $differing_attributes)) {
      $mandate_diff["{$custom_group_name}.bic_submitted"]  = $submitted_data['bic'] ?? '';
      $mandate_diff["{$custom_group_name}.bic_original"]   = $mandate['bic'] ?? '';
      $mandate_diff["{$custom_group_name}.iban_submitted"] = $submitted_data['iban'] ?? '';
      $mandate_diff["{$custom_group_name}.iban_original"]  = $mandate['iban'] ?? '';
    }

    // amount and frequency should be together
    if (in_array('amount', $differing_attributes) || in_array('frequency', $differing_attributes)) {
      $mandate_diff["{$custom_group_name}.amount_submitted"]    = $submitted_data['amount'] ?? '';
      $mandate_diff["{$custom_group_name}.amount_original"]     = $mandate['amount'] ?? '';
      $mandate_diff["{$custom_group_name}.frequency_submitted"] = $submitted_data['frequency'] ?? '';
      $mandate_diff["{$custom_group_name}.frequency_original"]  = $mandate['frequency'] ?? '';
    }

    // check if there is a difference
    if (!empty($mandate_diff)) {
      // there is a difference -> add to activity
      foreach ($mandate_diff as $key => $value) {
        $activity_data[$key] = $value;
      }

      // also add the basic IDs
      foreach ($activity_update as $key => $value) {
        $activity_data[$key] = $value;
      }

      // add some basic data
      $activity_data['target_id'] = $mandate['contact_id'];
      $activity_data["{$custom_group_name}.reference"] = $mandate['reference'];
      if (isset($submitted_data['sdd_reference_new'])) {
        $activity_data["{$custom_group_name}.reference_replaced"] = $submitted_data['sdd_reference_new'];
      }
    }
  }

  /**
   * Apply the changes
   *
   * @return array with changes to the activity
   *
   * phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
   */
  public function applyChanges($activity, $values, $objects = []) {
  // phpcs:enable
    $activity_update = [];
    if (!$this->hasData($activity)) {
      // NO DATA, no updates
      return $activity_update;
    }

    $action = $values['i3val_sdd_updates_action'] ?? '';

    if ($action) {
      // collect some basic values
      $prefix            = $this->getKey() . '_';
      $reference         = $activity[self::$group_name . '.reference'];
      $old_mandate       = $this->getMandate(['reference' => $reference]);
      $cancel_reason     = $values["{$prefix}reason_applied"] ?? '';
      $new_status        = $values["{$prefix}status_applied"] ?? '';
      $mandate_processed = FALSE;
      $this->applyUpdateData($update, $values, '%s', "{$prefix}%s_applied");
      $this->resolveFields($update);

      // CANCEL THE MANDATE?
      if ($new_status == 'COMPLETE' || $new_status == 'INVALID') {
        // CANCEL the old mandate
        CRM_Sepa_BAO_SEPAMandate::terminateMandate($old_mandate['id'], date('Y-m-d'), $cancel_reason);
        $activity_update[self::$group_name . '.action'] = E::ts('Mandate cancelled');
        $activity_update[self::$group_name . '.reason_applied'] = $cancel_reason;
        $activity_update[self::$group_name . '.status'] = $new_status;
        $mandate_processed = TRUE;
      }

      // no? check if we can MEND THE MANDATE
      if (!$mandate_processed) {
        $mandate_processed = $this->mendCurrentMandate($old_mandate, $update, $activity, $values, $activity_update);
        if ($mandate_processed) {
          $activity[self::$group_name . '.reference_replaced'] = $old_mandate['reference'];
        }
      }

      if (!$mandate_processed) {
        // didn't work? Then we'll have to...
        // ...CREATE A NEW MANDATE!
        $new_mandate = [
          'type'   => 'RCUR',
          'status' => 'FRST',
        ];
        $this->applyUpdateData($new_mandate, $values, '%s', "{$prefix}%s_applied");
        $this->resolveFields($new_mandate);

        // adjust start date
        if (empty($new_mandate['start_date']) || strtotime($new_mandate['start_date']) < strtotime('now')) {
          $new_mandate['start_date'] = date('YmdHis');
        }

        // adjust reference
        if (!empty($activity[self::$group_name . '.reference_replaced'])) {
          $new_mandate['reference'] = $this->uniqueMandateReference(
            $activity[self::$group_name . '.reference_replaced']
          );
        }

        // copy other fields
        $copy_fields = [
          'iban',
          'bic',
          'contact_id',
          'frequency_interval',
          'frequency_unit',
          'amount',
          'source',
          'creditor_id',
          'financial_type_id',
          'campaign_id',
          'cycle_day',
          'currency',
        ];
        foreach ($copy_fields as $field_name) {
          if (empty($new_mandate[$field_name]) && isset($old_mandate[$field_name])) {
            $new_mandate[$field_name] = $old_mandate[$field_name];
          }
        }

        CRM_I3val_Session::log('CREATE NEW ' . json_encode($new_mandate));
        $create_mandate_result = civicrm_api3('SepaMandate', 'createfull', $new_mandate);
        $created_mandate = $this->getMandate($create_mandate_result);

        // CANCEL the old mandate
        CRM_Sepa_BAO_SEPAMandate::terminateMandate(
          $old_mandate['id'],
          date('Y-m-d', strtotime($new_mandate['start_date'])),
          $cancel_reason
        );

        // update data
        $this->resolveFields($old_mandate);
        $this->resolveFields($new_mandate);
        $this->applyUpdateData($activity_update, $new_mandate, self::$group_name . '.%s_applied', '%s');
        $this->applyUpdateData($activity_update, $old_mandate, self::$group_name . '.%s_original', '%s');

        $activity_update[self::$group_name . '.reference_replaced'] = $created_mandate['reference'];
        $activity_update[self::$group_name . '.action'] = E::ts('Created replacement mandate.');
      }

    }
    else {
      $activity_update[self::$group_name . '.action'] = E::ts('Data discarded.');
    }

    return $activity_update;
  }

  /**
   * Try to adjust the existing mandate
   *
   * @return boolean
   *   TRUE if it worked
   */
  public function mendCurrentMandate($old_mandate, $update, $activity, $values, &$activity_update) {
    // check whether changes are allowed
    $mandate_modifications_allowed = Civi::settings()->get('allow_mandate_modification');
    if (empty($mandate_modifications_allowed)) {
      // mandate modifications are not allowed
      return FALSE;
    }

    // check which values have really changed
    $changes = [];
    // this should be resolved anyway
    unset($update['frequency']);
    // some changes should not prevent mandate updates (see I3VAL-29)
    unset($update['reason']);
    foreach ($update as $key => $value) {
      if ($value != $old_mandate[$key]) {
        $changes[$key] = $value;
      }
    }

    // now see if there's something we can do
    if (!empty($changes['amount']) && count($changes) == 1) {
      // if only the amount has changed, go ahead:
      // @todo Replace deprecated code.
      $success = CRM_Sepa_BAO_SEPAMandate::adjustAmount($old_mandate['id'], $changes['amount']);
      if ($success) {
        $activity_update[self::$group_name . '.amount_applied'] = $changes['amount'];
        $activity_update[self::$group_name . '.action'] = E::ts('Mandate adjusted: changed amount');
        return TRUE;
      }
    }

    // todo: add more modifying options here.

    // if we get here, modification is not possible
    return FALSE;
  }

  /**
   * render cancel fields (rather than update)
   */
  protected function renderCancelMandate($activity, $form, $existing_mandate) {
    $group_name  = $this->getCustomGroupName();
    $prefix      = $this->getKey() . '_';

    // add status
    $form_values["{$prefix}status"]['submitted'] = $activity["{$group_name}.status"];
    $form_values["{$prefix}status"]['current']   = $existing_mandate['status'];
    $form_values["{$prefix}status"]['applied']   = $activity["{$group_name}.status"];
    $form->add(
      'select',
      "{$prefix}status_applied",
      E::ts('Status'),
      ['COMPLETE' => 'COMPLETE'],
      FALSE,
      ['class' => 'crm-select2']
    );
    $form->setDefaults(["{$prefix}status_applied" => $activity["{$group_name}.status"]]);
    $active_fields["{$prefix}status"] = E::ts('Status');
    $field2label['status'] = E::ts('Status');

    // add cancel reason
    $form_values["{$prefix}reason"]['submitted'] = $activity["{$group_name}.reason_submitted"];
    $form->add(
      'text',
      "{$prefix}reason_applied",
      E::ts('Cancel Reason')
    );
    $form->setDefaults(["{$prefix}reason_applied" => $form_values["{$prefix}reason"]['submitted']]);
    $active_fields["{$prefix}reason"] = E::ts('Cancel Reason');
    $field2label['reason'] = E::ts('Cancel Reason');

    // pass on to form
    $form->assign('i3val_active_sdd_fields', $active_fields);
    $form->assign('i3val_sdd_values', $form_values);
    $form->assign('i3val_sdd_fields', $field2label);
    $form->assign('i3val_sdd_mandate', $existing_mandate);
    $form->assign('i3val_sdd_hide_original', 1);
    $form->assign('i3val_sdd_is_cancel', 1);

    // add processing options
    $form->add(
      'select',
      'i3val_sdd_updates_action',
      E::ts('Action'),
      [0 => E::ts("Don't apply"), 1 => E::ts('Cancel Mandate')],
      TRUE,
      ['class' => 'huge crm-select2']
    );
    $form->setDefaults(['i3val_sdd_updates_action' => 1]);
  }

  /**
   * Make sure that the given mandate reference is unique, i.e. not already in use.
   *
   * @param string $mandate_reference
   *   the desired mandate reference
   *
   * @return string
   *   an unused mandate reference. either the input value, or the input-value with a suffix.
   */
  protected function uniqueMandateReference($mandate_reference) {
    if (empty($mandate_reference)) {
      // fallback if we get an empty mandate reference (shouldn't happen)
      $mandate_reference = 'SDD-I3VAL';
    }
    $requested_mandate_reference = $mandate_reference;
    $query = 'SELECT id FROM civicrm_sdd_mandate WHERE reference = %1';
    $counter = 1;
    while (CRM_Core_DAO::singleValueQuery($query, [1 => [$mandate_reference, 'String']])) {
      // getting here means, the current mandate_reference is already in use
      $mandate_reference = "{$requested_mandate_reference}-{$counter}";
      $counter++;
    }

    return $mandate_reference;
  }

  /**
   * IBAN lookup service
   */
  public static function service_checkIBAN($params) {
    $reply = [];
    if (isset($params['iban'])) {
      $reply['iban'] = strtoupper(trim($params['iban']));
      if (strlen($reply['iban']) > 0) {
        $error = CRM_Sepa_Logic_Verification::verifyIBAN($reply['iban']);
        if ($error) {
          $reply['error'] = $error;
        }

        // look up the BIC as well
        if (function_exists('bic_civicrm_config')) {
          try {
            $lookup = civicrm_api3('Bic', 'getfromiban', ['iban' => $reply['iban']]);
            if (isset($lookup['bic'])) {
              $reply['bic'] = $lookup['bic'];
            }
          }
          catch (Exception $e) {
            // @ignoreException
            // cannot handle it...
          }
        }
      }
      $null = NULL;
      return civicrm_api3_create_success($null, $params, $null, $null, $null, $reply);
    }
    else {
      return civicrm_api3_create_error('No iban submitted');
    }
  }

}
