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
class CRM_I3val_Handler_EmailUpdate extends CRM_I3val_Handler_DetailUpdate {

  public static $group_name = 'i3val_email_updates';
  public static $field2label = NULL;

  public function getField2Label() {
    if (self::$field2label === NULL) {
      self::$field2label = [
        'email'         => E::ts('Email'),
        'location_type' => E::ts('Location Type'),
      ];
    }
    return self::$field2label;
  }

  /**
   * get the main key/identifier for this handler
   */
  public function getKey() {
    return 'email';
  }

  /**
   * get a human readable name for this handler
   */
  public function getName() {
    return E::ts('Email Update');
  }

  /**
   * returns a list of CiviCRM entities this handler can process
   */
  public function handlesEntities() {
    return ['Contact', 'Email'];
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
    return ['email'];
  }

  /**
   * Get the JSON specification file defining the custom group used for this data
   */
  public function getCustomGroupSpeficationFiles() {
    return [__DIR__ . '/../../../resources/email_updates_custom_group.json'];
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
   * Apply the changes
   *
   * @return array with changes to the activity
   */
  public function applyChanges($activity, $values, $objects = []) {
    $activity_update = [];
    if (!$this->hasData($activity)) {
      // NO DATA, no updates
      return $activity_update;
    }

    $email_update = [];
    $prefix = $this->getKey() . '_';
    $action = $values['i3val_email_updates_action'] ?? '';
    switch ($action) {
      case 'add_primary':
        $email_update['is_primary'] = 1;
      case 'add':
        $activity_update[self::$group_name . '.action'] = E::ts('New email added.');
        $email_update['contact_id'] = $values['contact_id'];
        $this->applyUpdateData($email_update, $values, '%s', "{$prefix}%s_applied");
        $this->applyUpdateData($activity_update, $values, self::$group_name . '.%s_applied', "{$prefix}%s_applied");
        break;

      case 'update':
        $activity_update[self::$group_name . '.action'] = E::ts('Email updated');
        $email_update['id']         = $values['i3val_email_updates_email_id'];
        // not necessary, but causes notices in 4.6
        $email_update['contact_id'] = $values['contact_id'];
        $this->applyUpdateData($email_update, $values, '%s', "{$prefix}%s_applied");
        $this->applyUpdateData($activity_update, $values, self::$group_name . '.%s_applied', "{$prefix}%s_applied");
        break;

      case 'duplicate':
        $activity_update[self::$group_name . '.action'] = E::ts('Entry already existed.');
        break;

      default:
      case 'discard':
        $activity_update[self::$group_name . '.action'] = E::ts('Data discarded.');
        break;
    }

    if (!empty($email_update)) {
      // perform update
      $this->resolveFields($email_update);
      CRM_I3val_Session::log('EMAIL UPDATE: ' . json_encode($email_update));
      civicrm_api3('Email', 'create', $email_update);
    }

    return $activity_update;
  }

  /**
   * Load and assign necessary data to the form
   */
  public function renderActivityData($activity, $form) {
    $field2label = $this->getField2Label();
    $prefix = $this->getKey() . '_';
    $values = $this->compileValues(self::$group_name, $field2label, $activity);

    // find existing email
    $default_action  = 'add';
    $email_submitted = $this->getMyValues($activity);
    $email_submitted['contact_id'] = $form->contact['id'];
    $existing_email = $this->getExistingEmail($email_submitted, $default_action);
    if ($existing_email) {
      $form->add('hidden', 'i3val_email_updates_email_id', $existing_email['id']);
      $this->addCurrentValues($values, $existing_email);
    }
    else {
      $form->add('hidden', 'i3val_email_updates_email_id', 0);
    }

    $this->applyUpdateData($form_values, $values, "{$prefix}%s");
    $form->assign('i3val_email_values', $form_values);
    $form->assign('i3val_email_fields', $field2label);

    // create input fields and apply checkboxes
    $active_fields = [];
    foreach ($field2label as $fieldname => $fieldlabel) {
      $form_fieldname = "{$prefix}{$fieldname}";

      // LOCATION TYPE is always there...
      if ($fieldname == 'location_type') {
        // will be added below, since it clashes with the other fields
        $active_fields[$form_fieldname] = $fieldlabel;

        // add the text input
        $form->add(
          'select',
          "{$form_fieldname}_applied",
          $fieldlabel,
          $this->getLocationTypeList(),
          FALSE,
          ['class' => 'crm-select2']
        );

        if (isset($values[$fieldname]['submitted'])) {
          $matching_location_type = $this->getMatchingLocationType($values[$fieldname]['submitted']);
        }
        else {
          $matching_location_type = $this->getDefaultLocationType();
        }
        $form->setDefaults(["{$form_fieldname}_applied" => $matching_location_type['display_name']]);

      }
      else {
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
          $form->setDefaults(["{$form_fieldname}_applied" => $values[$fieldname]['applied']]);
        }
        else {
          $form->setDefaults(["{$form_fieldname}_applied" => $values[$fieldname]['submitted']]);
        }
      }
    }

    // add processing options
    $options = $this->getProcessingOptions($email_submitted, $existing_email, 'Email');
    $form->add(
      'select',
      'i3val_email_updates_action',
      E::ts('Action'),
      $options,
      TRUE,
      ['class' => 'huge crm-select2']
    );

    $configuration = CRM_I3val_Configuration::getConfiguration();
    $form->setDefaults([
      'i3val_email_updates_action' => $configuration->pickDefaultAction($options, $default_action),
    ]);

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
  public function generateDiffData($entity, $submitted_data, &$activity_data) {
    // make sure the location type is resolved
    $this->resolveFields($submitted_data);

    switch ($entity) {
      case 'Contact':
        $submitted_data['contact_id'] = $submitted_data['id'];
        $email = $this->getExistingEmail($submitted_data);
        $this->generateEntityDiffData('Email', $email['id'], $email, $submitted_data, $activity_data, FALSE);
        break;

      case 'Email':
        $email = civicrm_api3('Email', 'getsingle', ['id' => $submitted_data['id']]);
        $this->resolveFields($email);
        $this->generateEntityDiffData('Email', $email['id'], $email, $submitted_data, $activity_data, FALSE);
        break;

      default:
        // nothing to do
        break;
    }
  }

  /**
   * find a matching email based on
   *  email, location_type (and contact_id obviously)
   *
   * phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
   */
  protected function getExistingEmail($values, &$default_action = NULL) {
  // phpcs:enable
    $email_submitted = [];
    $this->applyUpdateData($email_submitted, $values);
    if (empty($email_submitted) || empty($values['contact_id'])) {
      // there's nothing we can do
      return NULL;
    }

    // first, make sure that the location type is resolved
    $this->resolveFields($email_submitted, TRUE);

    // then: load all emails
    $query = civicrm_api3('Email', 'get', [
      'contact_id'   => $values['contact_id'],
      'option.limit' => 0,
      'option.sort'  => 'is_primary desc',
      'sequential'   => 1,
    ]);
    $emails = $query['values'];

    // first: find by exact values
    foreach ($emails as $email) {
      if ($this->hasEqualMainFields($email_submitted, $email)) {
        $this->resolveFields($email);
        if ($email_submitted['location_type'] == $email['location_type']) {
          // even the location type is identical
          if ($this->hasEqualMainFields($email_submitted, $email, TRUE)) {
            $default_action = 'duplicate';
          }
          else {
            $default_action = 'update';
          }
        }
        else {
          // location type differs
          $default_action = 'update';
        }
        return $email;
      }
    }

    // second: find by location type
    if (isset($values['location_type_id'])) {
      foreach ($emails as $email) {
        if ($values['location_type_id'] == $email['location_type_id']) {
          $this->resolveFields($email);
          $default_action = 'update';
          return $email;
        }
      }
    }

    // third: find by similarity
    $best_matching_email = NULL;
    $highest_similarity  = -1;
    foreach ($emails as $email) {
      $similarity = $this->getMainFieldSimilarity($email_submitted, $email);
      if ($similarity > $highest_similarity) {
        $highest_similarity = $similarity;
        $best_matching_email = $email;
      }
    }
    $this->resolveFields($best_matching_email);
    $default_action = 'add';
    return $best_matching_email;
  }

}
