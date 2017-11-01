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
class CRM_I3val_Handler_EmailUpdate extends CRM_I3val_Handler_DetailUpdate {

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
    error_log("CHANGES: " . json_encode($values));

    $email_update = array();
    switch ($values['i3val_email_updates_action']) {
      case 'add_primary':
        $email_update['is_primary'] = 1;
      case 'add':
        $activity_update[self::$group_name . ".action"] = E::ts("New email added.");
        $activity_update[self::$group_name . ".location_type_applied"] = $values['location_type_applied'];
        $activity_update[self::$group_name . ".email_applied"]         = $values['email_applied'];
        $email_update['contact_id']    = $values['contact_id'];
        $email_update['email']         = $values['email_applied'];
        $email_update['location_type'] = $values['location_type_applied'];
        $this->resolveLocationType($email_update);
        break;

      case 'update':
        $activity_update[self::$group_name . ".action"] = E::ts("Email corrected");
        $activity_update[self::$group_name . ".location_type_applied"] = $values['location_type_applied'];
        $activity_update[self::$group_name . ".email_applied"]         = $values['email_applied'];
        $email_update['id']            = $values['i3val_email_updates_email_id'];
        $email_update['email']         = $values['email_applied'];
        $email_update['location_type'] = $values['location_type_applied'];
        $this->resolveLocationType($email_update);
        break;

      case 'duplicate':
        $activity_update[self::$group_name . ".action"] = E::ts("Entry already existed.");
        break;

      default:
      case 'discard':
        $activity_update[self::$group_name . ".action"] = E::ts("Data discarded.");
        break;
    }

    if (!empty($email_update)) {
      // perform update
      error_log("EMAIL API: " . json_encode($email_update));
      civicrm_api3('Email', 'create', $email_update);
    }

    return $activity_update;
  }



  /**
   * Load and assign necessary data to the form
   */
  public function renderActivityData($activity, $form) {
    $field2label = self::getField2Label();
    $values = $this->compileValues(self::$group_name, $field2label, $activity);

    // find existing email
    $default_action  = 'add';
    $email_submitted = $this->getMyValues($activity);
    $email_submitted['contact_id'] = $form->contact['id'];
    $existing_email = $this->getExistingEmail($email_submitted, $default_action);
    if ($existing_email) {
      $form->add('hidden', 'i3val_email_updates_email_id', $existing_email['id']);
      $this->addCurrentValues($values, $existing_email);
    } else {
      $form->add('hidden', 'i3val_email_updates_email_id', 0);
    }

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
      // generate input field
      if ($fieldname=='location_type') {
        // add the text input
        $form->add(
          'select',
          "{$fieldname}_applied",
          $fieldlabel,
          $this->getLocationTypeList(),
          array('class' => 'crm-select2')
        );
        $matching_location_type = $this->getMatchingLocationType($values[$fieldname]['submitted']);
        $form->setDefaults(array("{$fieldname}_applied" => $matching_location_type['id']));

      } else {
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
      }
    }

    // add processing options
    $form->add(
      'select',
      "i3val_email_updates_action",
      E::ts("Action"),
      $this->getProcessingOptions($email_submitted, $existing_email, 'email'),
      TRUE,
      array('class' => 'huge')
    );
    $form->setDefaults(array("i3val_email_updates_action" => $default_action));

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
  public function generateDiffData($entity, $entity_id, $entity_data, $submitted_data, &$activity_data) {
    // make sure the location type is resolved
    $this->resolveLocationType($entity_data);
    $this->resolveLocationType($submitted_data);

    switch ($entity) {
      case 'Contact':
        $submitted_data['contact_id'] = $entity_id;
        $email = $this->getExistingEmail($submitted_data);
        parent::generateDiffData('Email', $email['id'], $email, $submitted_data, $activity_data);
        break;

      case 'Email':
        parent::generateDiffData('Email', $entity_id, $entity_data, $submitted_data, $activity_data);
        break;

      default:
        // nothing to do
        break;
    }
  }



  /**
   * find a matching email based on
   *  email, location_type (and contact_id obviously)
   */
  protected function getExistingEmail($values, &$default_action) {
    if (empty($values['email']) || empty($values['contact_id'])) {
      // there's nothing we can do
      return NULL;
    }

    // first, make sure that the location type is resolved
    $this->resolveLocationType($values);

    // then: load all emails
    $query = civicrm_api3('Email', 'get', array(
      'contact_id'   => $values['contact_id'],
      'option.limit' => 0,
      'option.sort'  => 'is_primary desc',
      'sequential'   => 1));
    $emails = $query['values'];

    // first: find by email
    $email_value = trim(strtolower($values['email']));
    foreach ($emails as $email) {
      if ($email_value == trim(strtolower($email['email']))) {
        $this->resolveLocationType($email);
        if (   $values['email'] == $email['email']
            && $values['location_type_id'] == $email['location_type_id']) {
          $default_action = 'duplicate';
        } else {
          $default_action = 'update';
        }
        return $email;
      }
    }

    // second: find by location type
    if (isset($values['location_type_id'])) {
      foreach ($emails as $email) {
        if ($values['location_type_id'] == $email['location_type_id']) {
          $this->resolveLocationType($email);
          $default_action = 'update';
          return $email;
        }
      }
    }

    // third: find by similarity
    $best_matching_email = NULL;
    $highest_similarity  = 0;
    foreach ($emails as $email) {
      similar_text($values['email'], $email['email'], $similarity);
      if ($similarity > $highest_similarity) {
        $highest_similarity = $similarity;
        $best_matching_email = $email;
      }
    }
    $this->resolveLocationType($best_matching_email);
    $default_action = 'add';
    return $best_matching_email;
  }
}
