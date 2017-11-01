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
 * abstract class providing infrastructure for
 *  contact detail updates (email,address,phone)
 */
abstract class CRM_I3val_Handler_DetailUpdate extends CRM_I3val_ActivityHandler {

  /** location type cache */
  static $_location_types = NULL;

  /**
   * get the general processing options
   */
  protected function getProcessingOptions($data_submitted, $data_existing, $attribute) {
    $options = array();
    $options['add']         = E::ts("Add '%1'", array(1 => $data_submitted[$attribute]));
    $options['add_primary'] = E::ts("Add '%1' as primary", array(1 => $data_submitted[$attribute]));

    if ($data_existing) {
      $options['update'] = E::ts("Overwrite '%1'", array(1 => $data_existing[$attribute]));
    }

    $options['discard']   = E::ts("Discard '%1' (do nothing)", array(1 => $data_submitted[$attribute]));
    $options['duplicate'] = E::ts("Already Exists (do nothing)");
    return $options;
  }

  /**
   * get a dropdown list of location types
   */
  protected function getLocationTypeList() {
    $location_types = $this->_getLocationTypes();
    $location_type_list = array();
    foreach ($location_types as $location_type) {
      $location_type_list[$location_type['id']] = $location_type['display_name'];
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
   * make sure that if there is any location_type data
   * present, that 'location_type' and 'location_type_id' are
   * properly set
   */
  protected function resolveLocationType(&$data) {
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
  public function generateDiffData($entity, $entity_id, $entity_data, $submitted_data, &$activity_data) {
    $raw_diff = $this->createDiff($entity_data, $submitted_data);

    // todo exception for location type

    foreach ($raw_diff as $key => $value) {
      $activity_data[$key] = $value;
    }
  }
}
