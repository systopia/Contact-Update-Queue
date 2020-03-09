<?php
/*-------------------------------------------------------+
| Ilja's Input Validation Extension                      |
| Amnesty International Vlaanderen                       |
| Copyright (C) 2020 SYSTOPIA                            |
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
 * Tool to convert old XCM-style change messages to I3Val activities
 * Class CRM_I3val_Converter
 */
class CRM_I3val_Converter {

  protected $success_count = 0;
  protected $failed_count = 0;

  /**
   * Get the mapping of the row header to the corresponding i3val field
   * @return array mapping
   */
  protected function getParameterMapping() {
    return [
        ts('First Name')     => 'i3val_contact_updates.first_name',
        ts('Last Name')      => 'i3val_contact_updates.last_name',
        'gender_id'               => 'i3val_contact_updates.gender',
        ts('City')           => 'i3val_address_updates.city',
        ts('Street Address') => 'i3val_address_updates.street_address',
        ts('Postal Code')    => 'i3val_address_updates.postal_code',
        ts('Country')        => 'i3val_address_updates.country',
        ts('Email')          => 'i3val_email_updates.email',
        'Phone'                   => 'i3val_phone_updates.phone',
        'phone_numeric'           => 'i3val_phone_updates.phone',
    ];
  }


  /**
   * @param $selector          array activity selector for the activities to be converted
   * @param $activity_type_id  int   target I3Val object (activity_type_id)
   * @param $params            array additional options
   */
  public function convert($selector, $activity_type_id, $params) {
    // load activities
    $selector['return'] = 'id,details,status_id';
    $activities = civicrm_api3('Activity', 'get', $selector);

    foreach ($activities['values'] as $activity) {
      // build update
      $activity_update = [
          'id'               => $activity['id'],
          'activity_type_id' => $activity_type_id,
      ];

      // derive parameters
      $raw_data = CRM_Utils_Array::value('details', $activity, '');
      $success = $this->extract($raw_data, $activity_update, $params);
      if ($success) {
        // write back activity
        $this->success_count++;
        CRM_I3val_CustomData::resolveCustomFields($activity_update);
        if (empty($params['dry_run'])) {
          civicrm_api3('Activity', 'create', $activity_update);
        }
      } else {
        $this->failed_count++;
      }
    }

    return [$this->success_count, $this->failed_count];
  }

  /**
   * Extract the contained changes
   *
   * @param string $data
   * @param array $activity
   * @param array $params
   * @return boolean
   * @throws Exception if a parameter couldn't be mapped
   */
  public function extract (string $data, array &$activity, array $params): bool {
    // TODO: switch styles?
    $attributes = $this->parse_xcm($data);

    // mapping
    $mapped_values = $this->mapParameters($attributes, $this->getParameterMapping());
    foreach ($mapped_values as $mapped_key => $mapped_value) {
      $activity[$mapped_key] = $mapped_value;
    }

    return !empty($attributes) && !empty($mapped_values);
  }

  /**
   * Map the parsed parameters to the I3Val custom fields
   *
   * @param $attributes array attributes parsed: foreign_key => [old value, new value]
   * @param $mapping    array know mapping
   * @return array I3val custom field values
   * @throws Exception if something can't be mapped
   */
  public function mapParameters($attributes, $mapping) {
    $result = [];
    foreach ($attributes as $foreign_key => $tuple) {
      // break if there is an unmapped property
      if (!isset($mapping[$foreign_key])) {
        // check if it's a custom field
        if (substr($foreign_key,0 , 7) == 'custom_') {
          continue;
        }
        throw new Exception("Foreign Key '{$foreign_key}' unknown");
      }

      // prepare values
      $original_value  = $tuple[0];
      $submitted_value = $tuple[1];

      // extract location type
      $location_type = $this->extractLocationType($submitted_value);
      if ($location_type) {
        $prefix = explode('.', $mapping[$foreign_key])[0];
        $result["{$prefix}.location_type_submitted"] = $location_type;
      }

      $result["{$mapping[$foreign_key]}_original"]  = $original_value;
      $result["{$mapping[$foreign_key]}_submitted"] = $submitted_value;
    }
    return $result;
  }

  /**
   * Parse the XCM-style HTML blob
   *
   * @param string $html
   * @return array
   */
  public function parse_xcm (string $html): array {

    preg_match_all('/<tr>\s*<td>(.*)<\/td>\s*<td>(.*)<\/td>\s*<td>(.*)<\/td>\s*<\/tr>/m', $html, $matches, PREG_SET_ORDER);

    if ($matches === null) {
      $matches = [];
    }

    $result = [];
    foreach ($matches as $match) {
      $resultEntry = [$match[2], $match[3]];
      $result[$match[1]] = $resultEntry;
    }

    return $result;
  }

  /**
   * Extract a location type from the value, if it's attached as ' (location_type)'
   *
   * @param $value string the value. if found, the location type will be stripped
   * @return string|null the location type name if found or null otherwise
   */
  public function extractLocationType(&$value) {
    static $location_type_names = NULL;
    if ($location_type_names === NULL) {
      $location_type_names = [];
      // load location types
      $query = civicrm_api3('LocationType', 'get', ['option.limit' => 0]);
      foreach ($query['values'] as $location_type) {
        $location_type_names[] = $location_type['display_name'];
      }
    }

    // now, try to find it in the string
    foreach ($location_type_names as $location_type_name) {
      if (preg_match("/ [(]{$location_type_name}[)]$/", $value)) {
        // we found it!
        $value = preg_replace("/ [(]{$location_type_name}[)]$/", "", $value);
        return $location_type_name;
      }
    }

    // not found:
    return NULL;
  }
}
