<?php
/*-------------------------------------------------------+
| Contact Update Queue Extension                         |
| Funded by Amnesty International Vlaanderen             |
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

use CRM_Contactupdatequeue_ExtensionUtil as E;

/**
 * abstract class providing infrastructure for
 *  contact detail updates (email,address,phone)
 */
abstract class CRM_Contactupdatequeue_Handler_DetailUpdate extends CRM_Contactupdatequeue_ActivityHandler {

  /** location type cache */
  static $_location_types = NULL;

  /**
   * Get the main attributes. If these are not present,
   *  no record at all is created
   */
  abstract protected function getMainFields();

  /**
   * get the general processing options
   */
  protected function getProcessingOptions($data_submitted, $data_existing, $entity_name) {

    $options = array();
    $options['add']         = E::ts("Add new %1", array(1 => $entity_name));
    $options['add_primary'] = E::ts("Add new %1 as primary", array(1 => $entity_name));

    if ($data_existing) {
      $options['update'] = E::ts("Overwrite existing %1", array(1 => $entity_name));
    }

    $options['discard']   = E::ts("Discard %1 data (do nothing)", array(1 => $entity_name));
    $options['duplicate'] = E::ts("%1 already exists (do nothing)", array(1 => $entity_name));
    return $options;
  }

  /**
   * get a dropdown list of location types
   */
  protected function getLocationTypeList() {
    $location_types = $this->_getLocationTypes();
    $location_type_list = array();
    foreach ($location_types as $location_type) {
      $location_type_list[$location_type['display_name']] = $location_type['display_name'];
    }
    return $location_type_list;
  }

  /**
   * get a list of location types
   *
   * @return array location type id -> location type name
   */
  protected function getIndexedLocationTypeList() {
    $location_types = $this->_getLocationTypes();
    $location_type_list = array();
    foreach ($location_types as $location_type_id => $location_type) {
      $location_type_list[$location_type_id] = $location_type['display_name'];
    }
    return $location_type_list;
  }

  /**
   * get the default location type object
   *
   * @return array location type data
   */
  protected function getDefaultLocationType() {
    $location_types = $this->_getLocationTypes();
    foreach ($location_types as $location_type) {
      if (!empty($location_type['is_default'])) {
        return $location_type;
      }
    }
    // there is no default! Return first value
    return reset($location_types);
  }

  /**
   * find a matching location type for the given name
   *
   * @return array location type data
   */
  protected function getMatchingLocationType($location_type_name) {
    $location_types = $this->_getLocationTypes();
    $location_type_name = strtolower(trim($location_type_name));
    foreach ($location_types as $location_type) {
      if (   $location_type_name == strtolower($location_type['name'])
          || $location_type_name == strtolower($location_type['display_name']) )
        return $location_type;
    }

    // location type not found
    return $this->getDefaultLocationType();
  }

  /**
   * find a matching location type for the given name
   *
   * @return array location type data
   */
  protected function getLocationTypeByID($location_type_id) {
    $location_types = $this->_getLocationTypes();
    foreach ($location_types as $location_type) {
      if ($location_type['id'] == $location_type_id) {
        return $location_type;
      }
    }

    return NULL;
  }

  /**
   * Resolve the text field names (e.g. 'location_type')
   *  to their ID representations ('location_type_id').
   */
  protected function resolveFields(&$data, $add_default = FALSE) {
    parent::resolveFields($data, $add_default);
    $this->resolveLocationType($data, $add_default);
  }

  /**
   * make sure that if there is any location_type data
   * present, that 'location_type' and 'location_type_id' are
   * properly set
   */
  protected function resolveLocationType(&$data, $add_default = FALSE) {
    if (!empty($data['location_type_id'])) {
      if (is_numeric($data['location_type_id'])) {
        $location_type = $this->getLocationTypeByID($data['location_type_id']);
        if ($location_type) {
          $data['location_type'] = $location_type['display_name'];
        } else {
          unset($data['location_type_id']);
        }
      } else {
        $location_type = $this->getMatchingLocationType($data['location_type_id']);
        $data['location_type_id'] = $location_type['id'];
        $data['location_type']    = $location_type['display_name'];
      }
    } elseif (!empty($data['location_type'])) {
      $location_type = $this->getMatchingLocationType($data['location_type']);
      $data['location_type_id'] = $location_type['id'];
      $data['location_type']    = $location_type['display_name'];
    }

    if (empty($data['location_type_id']) && $add_default) {
      // no location type set -> add default
      $location_type = $this->getDefaultLocationType();
      $data['location_type_id'] = $location_type['id'];
      $data['location_type']    = $location_type['display_name'];
    }
  }

  /**
   * compare all main fields of the two entities
   *
   * @return TRUE if equal
   */
  protected function hasEqualMainFields($entity1, $entity2, $identical = FALSE) {
    $main_fields = $this->getMainFields();
    foreach ($main_fields as $field_name) {
      $entity1_value = CRM_Utils_Array::value($field_name, $entity1, '');
      $entity2_value = CRM_Utils_Array::value($field_name, $entity2, '');
      if (!$identical) {
        $entity1_value = strtolower(trim($entity1_value));
        $entity2_value = strtolower(trim($entity2_value));
      }
      if ($entity1_value != $entity2_value) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Compare all main fields of the two entities
   *  using similar_text()
   *
   * @return TRUE if equal
   */
  protected function getMainFieldSimilarity($entity1, $entity2) {
    $similarity_sum   = 0.0;
    $similarity_count = 0;

    $main_fields = $this->getMainFields();
    foreach ($main_fields as $field_name) {
      $entity1_value = CRM_Utils_Array::value($field_name, $entity1, '');
      $entity2_value = CRM_Utils_Array::value($field_name, $entity2, '');
      // TODO: exclude empty strings similiarty?
      similar_text($entity1_value, $entity2_value, $similarity);
      $similarity_sum   += $similarity;
      $similarity_count += 1;
    }

    if ($similarity_count) {
      return ((double) $similarity_sum / (double) $similarity_count);
    } else {
      return 0;
    }
  }

  /**
   * load the location types via API
   */
  private function _getLocationTypes() {
    if (self::$_location_types === NULL) {
      $query = civicrm_api3('LocationType', 'get', array(
        'is_active'    => 1,
        'option.limit' => 0,
        'sequential'   => 0,
      ));
      self::$_location_types = $query['values'];
    }
    return self::$_location_types;
  }

  /**
   * Generic implementation for details.
   *  clients need to pass detail entity, NOT contact
   */
  public function generateEntityDiffData($entity, $entity_id, $original_data, $submitted_data, &$activity_data, $can_process_empty_fields = TRUE) {
    $diff_data         = array();
    $main_attributes   = $this->getMainFields();
    $all_attributes    = $this->getFields();
    $custom_group_name = $this->getCustomGroupName();

    $activity_data['target_id'] = $submitted_data['contact_id'];

    // make sure that there is at least one non-empty main field
    if (!$can_process_empty_fields) {
      $data_found = FALSE;
      foreach ($main_attributes as $field_name) {
        $data_found |= !empty($submitted_data[$field_name]);
      }
      if (!$data_found) {
        return;
      }
    }

    // first: check all main attributes
    foreach ($main_attributes as $field_name) {
      if (isset($submitted_data[$field_name])) {
        $current_value = CRM_Utils_Array::value($field_name, $original_data, '');
        if ($submitted_data[$field_name] != $current_value) {
          // an update was submitted
          $diff_data["{$custom_group_name}.{$field_name}_submitted"] = $submitted_data[$field_name];
          $diff_data["{$custom_group_name}.{$field_name}_original"]  = $current_value;
        }
      }
    }

    if (!empty($diff_data)) {
      // there is something there -> run it again
      foreach ($all_attributes as $field_name) {
        if (isset($submitted_data[$field_name])) {
          // an update was submitted
          $original_value = CRM_Utils_Array::value($field_name, $original_data, '');
          if (in_array($field_name, $main_attributes) || ($submitted_data[$field_name] != $original_value)) {
            $activity_data["{$custom_group_name}.{$field_name}_submitted"] = $submitted_data[$field_name];
            $activity_data["{$custom_group_name}.{$field_name}_original"]  = $original_value;
          }
        }
      }
    }
  }
}
