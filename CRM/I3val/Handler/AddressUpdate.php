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
class CRM_I3val_Handler_AddressUpdate extends CRM_I3val_Handler_DetailUpdate {

  public static $group_name = 'i3val_address_updates';
  public static $field2label = NULL;

  protected static function getField2Label() {
    if (self::$field2label === NULL) {
      self::$field2label = array( 'street_address'         => E::ts('Street Address'),
                                  'postal_code'            => E::ts('Postal Code'),
                                  'supplemental_address_1' => E::ts('Supplemental Address 1'),
                                  'supplemental_address_2' => E::ts('Supplemental Address 2'),
                                  'city'                   => E::ts('City'),
                                  'country'                => E::ts('Country'),
                                  'location_type'          => E::ts('Location Type'));
    }
    return self::$field2label;
  }

  /**
   * get the main key/identifier for this handler
   */
  public function getKey() {
    return 'address';
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
    return array('street_address',
                 'postal_code',
                 'supplemental_address_1',
                 'supplemental_address_2',
                 'city',
                 'country');
  }

  /**
   * Get the JSON specification file defining the custom group used for this data
   */
  public function getCustomGroupSpeficationFile() {
    return 'address_updates_custom_group.json';
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

    $address_update = array();
    $action = CRM_Utils_Array::value('i3val_address_updates_action', $values, '');
    switch ($action) {
      case 'add_primary':
        $address_update['is_primary'] = 1;
      case 'add':
        $activity_update[self::$group_name . ".action"] = E::ts("New address added.");
        $address_update['contact_id'] = $values['contact_id'];
        $this->applyUpdateData($address_update, $values, '%s', '%s_applied');
        $this->applyUpdateData($activity_update, $values, self::$group_name . '.%s_applied', '%s_applied');
        break;

      case 'update':
        $activity_update[self::$group_name . ".action"]= E::ts("Address updated");
        $address_update['id']         = $values['i3val_address_updates_address_id'];
        $address_update['contact_id'] = $values['contact_id']; // not necessary, but causes notices in 4.6
        $this->applyUpdateData($address_update, $values, '%s', '%s_applied');
        $this->applyUpdateData($activity_update, $values, self::$group_name . '.%s_applied', '%s_applied');
        break;

      case 'duplicate':
        $activity_update[self::$group_name . ".action"] = E::ts("Entry already existed.");
        break;

      default:
      case 'discard':
        $activity_update[self::$group_name . ".action"] = E::ts("Data discarded.");
        break;
    }

    if (!empty($address_update)) {
      // perform update
      $this->resolveFields($address_update);
      error_log("ADDRESS UPDATE: " . json_encode($address_update));
      civicrm_api3('Address', 'create', $address_update);
    }

    return $activity_update;
  }



  /**
   * Load and assign necessary data to the form
   */
  public function renderActivityData($activity, $form) {
    $field2label = self::getField2Label();
    $values = $this->compileValues(self::$group_name, $field2label, $activity);

    // find existing address
    $default_action  = 'add';
    $address_submitted = $this->getMyValues($activity);
    $address_submitted['contact_id'] = $form->contact['id'];
    $existing_address = $this->getExistingAddress($address_submitted, $default_action);
    if ($existing_address) {
      $form->add('hidden', 'i3val_address_updates_address_id', $existing_address['id']);
      $this->addCurrentValues($values, $existing_address);
    } else {
      $form->add('hidden', 'i3val_address_updates_address_id', 0);
    }

    $form->assign('i3val_address_fields', $field2label);
    $form->assign('i3val_address_values', $values);

    // create input fields and apply checkboxes
    $active_fields = array();
    foreach ($field2label as $fieldname => $fieldlabel) {
      // LOCATION TYPE is always there...
      if ($fieldname=='location_type') {
        $active_fields[$fieldname] = $fieldlabel;

        // add the text input
        $form->add(
          'select',
          "{$fieldname}_applied",
          $fieldlabel,
          $this->getLocationTypeList(),
          array('class' => 'crm-select2')
        );
        if (isset($values[$fieldname]['submitted'])) {
          $matching_location_type = $this->getMatchingLocationType($values[$fieldname]['submitted']);
        } else {
          $matching_location_type = $this->getDefaultLocationType();
        }
        $form->setDefaults(array("{$fieldname}_applied" => $matching_location_type['id']));

      } else {
        // if there is no values, omit field
        if (empty($values[$fieldname]['submitted'])) {
          continue;
        }

        // this field has data:
        $active_fields[$fieldname] = $fieldlabel;

        $form->add(
          'text',
          "{$fieldname}_applied",
          $fieldlabel
        );

        // calculate proposed value
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
      "i3val_address_updates_action",
      E::ts("Action"),
      $this->getProcessingOptions($address_submitted, $existing_address, 'address'),
      TRUE,
      array('class' => 'huge')
    );
    $form->setDefaults(array("i3val_address_updates_action" => $default_action));

    $form->assign('i3val_active_address_fields', $active_fields);
  }

  /**
   * Get the path of the template rendering the form
   */
  public function getTemplate() {
    return 'CRM/I3val/Handler/AddressUpdate.tpl';
  }


  /**
   * Calculate the data to be created and add it to the $activity_data Activity.create params
   * @todo specify
   */
  public function generateDiffData($entity, $entity_id, $entity_data, $submitted_data, &$activity_data) {
    // make sure the location type is resolved
    $this->resolveFields($entity_data);
    $this->resolveFields($submitted_data);

    switch ($entity) {
      case 'Contact':
        $submitted_data['contact_id'] = $entity_id;
        $address = $this->getExistingAddress($submitted_data);
        parent::generateDiffData('Address', $address['id'], $address, $submitted_data, $activity_data);
        break;

      case 'Address':
        parent::generateDiffData('Address', $entity_id, $entity_data, $submitted_data, $activity_data);
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
    // TODO: resolve country ID?
  }


  /**
   * Get a list of (eligible) countries
   */
  protected function getCountryList() {
    $country_list = array();
    $countries = CRM_Core_PseudoConstant::country();
    foreach ($countries as $country_id => $country_name) {
      $country_list[$country_name] = $country_name;
    }
    return $country_list;
  }

  /**
   * find a matching address based on
   *  address, location_type (and contact_id obviously)
   */
  protected function getExistingAddress($values, &$default_action) {
    $address_submitted = array();
    $this->applyUpdateData($address_update, $values);
    if (empty($address_submitted) || empty($values['contact_id'])) {
      // there's nothing we can do
      return NULL;
    }

    // first, make sure that the location type is resolved
    $this->resolveFields($address_submitted, TRUE);

    // then: load all addresss
    $query = civicrm_api3('Address', 'get', array(
      'contact_id'   => $values['contact_id'],
      'option.limit' => 0,
      'option.sort'  => 'is_primary desc',
      'sequential'   => 1));
    $addresss = $query['values'];

    // first: find by exact values
    foreach ($addresss as $address) {
      if ($this->hasEqualMainFields($address_submitted, $address)) {
        $this->resolveFields($address);
        if ($address_submitted['location_type'] == $address['location_type']) {
          // even the location type is identical
          if ($this->hasEqualMainFields($address_submitted, $address, TRUE)) {
            $default_action = 'duplicate';
          } else {
            $default_action = 'update';
          }
        } else {
          // location type differs
          $default_action = 'update';
        }
        return $address;
      }
    }

    // second: find by location type
    if (isset($values['location_type_id'])) {
      foreach ($addresss as $address) {
        if ($values['location_type_id'] == $address['location_type_id']) {
          $this->resolveFields($address);
          $default_action = 'update';
          return $address;
        }
      }
    }

    // third: find by similarity
    $best_matching_address = NULL;
    $highest_similarity    = 0;
    foreach ($addresss as $address) {
      $similarity = $this->getMainFieldSimilarity($address_submitted, $address);
      if ($similarity > $highest_similarity) {
        $highest_similarity = $similarity;
        $best_matching_address = $address;
      }
    }
    $this->resolveFields($best_matching_address);
    $default_action = 'add';
    return $best_matching_address;
  }
}
