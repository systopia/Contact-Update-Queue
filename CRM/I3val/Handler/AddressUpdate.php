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

  public function getField2Label() {
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
   * get a human readable name for this handler
   */
  public function getName() {
    return E::ts("Address Update");
  }

  /**
   * returns a list of CiviCRM entities this handler can process
   */
  public function handlesEntities() {
    return array('Contact', 'Address');
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
    $field2label = $this->getField2Label();
    return array_keys($field2label);
  }

  /**
   * Get the main attributes. If these are not present,
   *  no record at all is created
   */
  protected function getMainFields() {
    return array('location_type',
                 'street_address',
                 'postal_code',
                 'supplemental_address_1',
                 'supplemental_address_2',
                 'city',
                 'country');
  }

  /**
   * Get the JSON specification file defining the custom group used for this data
   */
  public function getCustomGroupSpeficationFiles() {
    return array(__DIR__ . '/../../../resources/address_updates_custom_group.json');
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
    // TODO: REFACTOR!
    $activity_update = array();
    if (!$this->hasData($activity)) {
      // NO DATA, no updates
      return $activity_update;
    }

    $address_update = array();
    $prefix = $this->getKey() . '_';
    $action = CRM_Utils_Array::value('i3val_address_updates_action', $values, '');
    switch ($action) {
      case 'add_primary':
        $address_update['is_primary'] = 1;
      case 'add':
        $activity_update[self::$group_name . ".action"] = E::ts("New address added.");
        $address_update['contact_id'] = $values['contact_id'];
        $this->applyUpdateData($address_update, $values, '%s', "{$prefix}%s_applied");
        $this->applyUpdateData($activity_update, $values, self::$group_name . '.%s_applied', "{$prefix}%s_applied");
        break;

      case 'update':
        $activity_update[self::$group_name . ".action"]= E::ts("Address updated");
        $address_update['id']         = $values['i3val_address_updates_address_id'];
        $address_update['contact_id'] = $values['contact_id']; // not necessary, but causes notices in 4.6
        $this->applyUpdateData($address_update, $values, '%s', "{$prefix}%s_applied");
        $this->applyUpdateData($activity_update, $values, self::$group_name . '.%s_applied', "{$prefix}%s_applied");
        break;

      case 'duplicate':
        $activity_update[self::$group_name . ".action"] = E::ts("Entry already existed.");
        break;

      case 'share':
        $activity_update[self::$group_name . ".action"] = E::ts("Address shared");
        break;

      default:
      case 'discard':
        $activity_update[self::$group_name . ".action"] = E::ts("Data discarded.");
        break;
    }

    if (!empty($address_update)) {
      // perform update
      $this->resolveFields($address_update);
      CRM_I3val_Session::log("ADDRESS UPDATE: " . json_encode($address_update));
      $result = civicrm_api3('Address', 'create', $address_update);
      $address_update['id'] = $result['id'];
    }

    // apply address sharing (if there is any)
    $this->applyAddressSharingChanges($activity, $action, $values, $activity_update, $address_update);

    return $activity_update;
  }



  /**
   * Load and assign necessary data to the form
   */
  public function renderActivityData($activity, $form) {
    $config = CRM_I3val_Configuration::getConfiguration();
    $field2label = $this->getField2Label();
    $prefix = $this->getKey() . '_';
    $values = $this->compileValues(self::$group_name, $field2label, $activity);

    // add default values
    $this->applyUpdateData($form_values, $values, "{$prefix}%s");
    $form->assign('i3val_address_fields', $field2label);
    $form->assign('i3val_address_values', $form_values);

    // create input fields and apply checkboxes
    $active_fields = array();
    foreach ($field2label as $fieldname => $fieldlabel) {
      $form_fieldname = "{$prefix}{$fieldname}";
      $active_fields[$form_fieldname] = $fieldlabel;

      // LOCATION TYPE is always there...
      switch ($fieldname) {
        case 'location_type':
          // add the text input
          $form->add(
              'select',
              "{$form_fieldname}_applied",
              $fieldlabel,
              $this->getLocationTypeList(),
              FALSE,
              array('class' => 'crm-select2')
          );
          break;

        case 'country':
          $form->add(
              'select',
              "{$form_fieldname}_applied",
              $fieldlabel,
              $this->getCountryList(),
              FALSE,
              array('class' => 'crm-select2')
          );
          break;

        default:
          $form->add(
              'text',
              "{$form_fieldname}_applied",
              $fieldlabel
          );
          break;
      }
    }

    // add primary field
    $form_fieldname = "{$prefix}_is_primary";
    $form->add(
        'select',
        $form_fieldname,
        E::ts("Primary?"),
        ['0' => E::ts("Address"), '1' => E::ts("Primary Address")],
        FALSE,
        array('class' => 'crm-select2')
    );

    // TODO: ADD address sharing (if submitted?)
//    $this->renderAddressSharingPanel($activity, $form, $address_submitted);

    // add processing options
    $addresses = $this->getExistingAddresses($form->contact['id']);
    $address_submitted = $this->getMyValues($activity);
    $options = $this->getCustomProcessingOptions($address_submitted, $addresses, $default_action);
//    $this->adjustAddressSharingOptions($options, $activity);
    $form->add(
      'select',
      "i3val_address_updates_action",
      E::ts("Action"),
      $options,
      TRUE,
      array('class' => 'huge crm-select2')
    );
    $configuration = CRM_I3val_Configuration::getConfiguration();
    $form->setDefaults(array(
      "i3val_address_updates_action" => $configuration->pickDefaultAction($options, $default_action)));

    $form->assign('i3val_active_address_fields', $active_fields);

    // add JS code
    CRM_Core_Resources::singleton()->addVars('i3val_address_update', ['addresses' => $addresses]);
    CRM_Core_Resources::singleton()->addScriptFile('be.aivl.i3val', 'js/address_update_logic.js');
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
  public function generateDiffData($entity, $submitted_data, &$activity_data) {
    // make sure the location type is resolved
    parent::resolveFields($submitted_data); // don't resolve country

    switch ($entity) {
      case 'Contact':
        $submitted_data['contact_id'] = $submitted_data['id'];
        $addresses = $this->getExistingAddresses($submitted_data['contact_id']);
        $address = $this->getMatchingAddress($submitted_data, $addresses);
        $this->generateEntityDiffData('Address', $address['id'], $address, $submitted_data, $activity_data);
        $this->addAddressSharingDiffData($address, $submitted_data, $activity_data);
        break;

      case 'Address':
        $address = civicrm_api3('Address', 'getsingle', array('id' => $submitted_data['id']));
        // make sure we have the contact id
        if (empty($submitted_data['contact_id'])) {
          $submitted_data['contact_id'] = $address['contact_id'];
        }
        $this->resolveFields($address);
        $this->generateEntityDiffData('Address', $address['id'], $address, $submitted_data, $activity_data);
        $this->addAddressSharingDiffData($address, $submitted_data, $activity_data);
        break;

      default:
        // nothing to do
        break;
    }
  }

  /**
   * Get the country ID based on a string
   */
  protected function getCountryID($country) {
    $countryId2Name = CRM_Core_PseudoConstant::country();

    if (empty($country)) {
      return NULL;

    } elseif (is_numeric($country)) {
      if (isset($countryId2Name[$country])) {
        return $country;
      } else {
        // invalid ID
        return NULL;
      }

    } elseif (strlen($country) == 2) {
      // two characters? maybe it's an iso code
      $country_id = $this->getCountryIDbyCode($country);
      if (!empty($country_id)) {
        return $country_id;
      }

    } else {
      // try to find it as a name match
      foreach ($countryId2Name as $country_id=> $country_name) {
        if (strtolower($country) == strtolower($country_name)) {
          return $country_id;
        }
      }

      // if this didn't help, try by similarity
      $max_similarity = 0.0;
      $best_match     = NULL;

      foreach ($countryId2Name as $country_id=> $country_name) {
        similar_text($country, $country_name, $similarity);
        if ($similarity > $max_similarity) {
          $max_similarity = $similarity;
          $best_match = $country_id;
        }
      }
      return $best_match;
    }

    return NULL;
  }

  /**
   * get a list of the two-digit country code => country_id
   */
  protected function getCountryIDbyCode($code) {
    $country_code = substr(strtoupper($code), 0, 2);

    if (isset(self::$_countryCode2Id[$country_code])) {
      // cache hit
      return self::$_countryCode2Id[$country_code];

    } else {
      // we have to do the lookup
      $lookup = civicrm_api3('Country', 'get', array(
        'iso_code'     => $country_code,
        'option.limit' => 0,
        'return'       => 'id'));
      if (empty($lookup['id'])) {
        self::$_countryCode2Id[$country_code] = 0;
      } else {
        self::$_countryCode2Id[$country_code] = $lookup['id'];
      }
      return self::$_countryCode2Id[$country_code];
    }
  }

  /**
   * Resolve the text field names (e.g. 'location_type')
   *  to their ID representations ('location_type_id').
   */
  protected function resolveFields(&$data, $add_default = FALSE) {
    parent::resolveFields($data, $add_default);

    // resolve country/country_id (country_id takes preference)
    $countryId2Name = CRM_Core_PseudoConstant::country();
    if (!empty($data['country_id'])) {
      $country_id = $this->getCountryID($data['country_id']);
      if ($country_id) {
        $data['country_id'] = $country_id;
        $data['country']    = $countryId2Name[$country_id];
      } else {
        unset($data['country_id']);
      }

    } elseif (!empty($data['country'])) {
      $country_id = $this->getCountryID($data['country']);
      if ($country_id) {
        $data['country_id'] = $country_id;
        $data['country']    = $countryId2Name[$country_id];
      } else {
        unset($data['country']);
      }
    }
  }

  /**
   * get the default country
   */
  protected function getDefaultCountryID() {
    $config = CRM_Core_Config::singleton();
    return $config->defaultContactCountry;
  }

  /**
   * get the default country
   */
  protected function getDefaultCountryName() {
    $country_id = $this->getDefaultCountryID();
    $countryId2Name = CRM_Core_PseudoConstant::country();
    return $countryId2Name[$country_id];
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
   * Get a list of the currently used addresses
   * @param $contact_id int contact ID
   *
   * @return array [location type ID -> address data]
   * @throws Exception if loading fails or multiple addresses for same location type present
   */
  protected function getExistingAddresses($contact_id) {
    // load all addresses
    static $addresses = NULL;
    if ($addresses === NULL) {
      try {
        $query = civicrm_api3('Address', 'get', [
            'contact_id'   => $contact_id,
            'option.limit' => 0,
            'option.sort'  => 'is_primary desc',
            'sequential'   => 1]);
        $addresses = [];

        // enrich data
        foreach ($query['values'] as $address) {
          $this->resolveFields($address);
          if (isset($addresses[$address['location_type_id']])) {
            throw new Exception("Contact [{$contact_id}] has multiple addresses for location type '{$address['location_type']}'. Please fix!");
          } else {
            $addresses[$address['location_type_id']] = $address;
          }
        }
      } catch (Exception $ex) {
        throw new Exception("Error while loading addresses for contact [{$contact_id}]: " . $ex->getMessage());
      }
    }
    return $addresses;
  }

  /**
   * find a matching address based on
   *  address, location_type (and contact_id obviously)
   *
   * @param $values    array address fields submitted
   * @param $addresses array existing addresses
   */
  protected function getMatchingAddress($values, $addresses) {
    // extract the submitted data
    $address_submitted = [];
    $this->applyUpdateData($address_submitted, $values);
    $this->resolveFields($address_submitted, TRUE);
    if (empty($address_submitted)) {
      return NULL;
    }

    // make sure there is a location type
    if (empty($address_submitted['location_type_id'])) {
      $default_location_type = $this->getDefaultLocationType();
      $address_submitted['location_type_id'] = $default_location_type['id'];
    }

    // now: return the address with the location type
    $location_type_id = $address_submitted['location_type_id'];
    if (isset($addresses[$location_type_id])) {
      return $addresses[$location_type_id];
    } else {
      return NULL;
    }
  }


  /**
   * get the processing options (caution: different signature)
   */
  protected function getCustomProcessingOptions($data_submitted, $addresses, &$default_action = NULL) {
    // this handler has different options than the other detail handlers
    $options = [];
    $location_type_list = $this->getIndexedLocationTypeList();
    $can_create = $this->canCreateAddressWithData($data_submitted);
    $location_type = CRM_Utils_Array::value('location_type', $data_submitted);

    // add matching address first
    $matching_address = $this->getMatchingAddress($data_submitted, $addresses);
    $matching_location_type = CRM_Utils_Array::value('location_type', $matching_address);
    if ($matching_address) {
      // do this one first
      if ($can_create) {
        $options["update {$matching_location_type}"] = E::ts("Update %1 Address", [1 => $matching_location_type]);
        $options["replace {$matching_location_type}"] = E::ts("Replace %1 Address", [1 => $matching_location_type]);
        if ($this->shouldUpdateAddress($matching_address, $data_submitted)) {
          // if it's similar enough, it might just be an adjustment:
          $default_action = "update {$matching_location_type}";
        } else {
          // if it's different, it's probably an all new address
          $default_action = "replace {$matching_location_type}";
        }
      } else {
        // we don't have enough data to create a new one, i.e. we _have_ to update
        $options["update {$matching_location_type}"] = E::ts("Update %1 Address", [1 => $matching_location_type]);
        $default_action = "update {$matching_location_type}";
      }
    } elseif (!empty($data_submitted['location_type']['submitted'])) {
      $location_type = $data_submitted['location_type']['submitted'];
      $options["add {$location_type}"] = E::ts("Create %1 Address", [1 => $location_type]);
      $default_action = "add {$location_type}";
    }

    // add all/other address options
    foreach ($location_type_list as $location_type_id => $location_type_name) {
      if ($location_type_name == $matching_location_type) {
        // already added above
        continue;
      }

      if (isset($addresses[$location_type_id])) {
        // there _is_ an address of this location type:
        $options["update {$location_type_name}"] = E::ts("Update %1 Address", [1 => $location_type_name]);
        if ($can_create) {
          $options["replace {$location_type_name}"] = E::ts("Replace %1 Address", [1 => $location_type_name]);
        }
      } else {
        if ($can_create) {
          $options["add {$location_type_name}"] = E::ts("Create %1 Address", [1 => $location_type_name]);
        }
      }
    }

    $options['discard']   = E::ts("Discard %1 data (do nothing)", array(1 => 'Address'));
    $options['duplicate'] = E::ts("%1 already exists (do nothing)", array(1 => 'Address'));
    return $options;
  }

  /**
   * Check if there is address data in the
   *
   * @todo: make configurable?
   */
  protected function hasAddressData($data) {
    if (   !empty($data['street_address'])
        || !empty($data['postal_code'])
        || !empty($data['city'])) {
      return TRUE;
    } else {
      return FALSE;
    }
  }

  /**
   * Check if there is enough address data to create a new address
   * @todo: make configurable?
   */
  protected function canCreateAddressWithData($data) {
    if (   !empty($data['street_address'])
        || !empty($data['postal_code'])
        || !empty($data['city'])) {
      return TRUE;
    } else {
      return FALSE;
    }
  }


  /**
   * Check if this can be considered an update to an existing address, or
   *  whether it's too different and is properly a new address
   *
   * @param $matching_address array address data
   * @param $data_submitted   array change data
   *
   * @return boolean
   */
  protected function shouldUpdateAddress($matching_address, $data_submitted) {
    if ($matching_address) {
      $fields         = $this->getFields();
      $similarity     = 1.0;
      $fields_checked = 0;

      foreach ($fields as $field_name) {
        $submitted_value = CRM_Utils_Array::value($field_name, $data_submitted);
        if (!empty($submitted_value)) {
          $current_value = CRM_Utils_Array::value($field_name, $matching_address, '');
          similar_text($current_value, $submitted_value, $field_similarity);
          $similarity *= $field_similarity;
          $fields_checked += 1;
        }
      }

      return    $fields_checked >= 2
             && $similarity > 0.7;
    }
    return FALSE;
  }


  /******************************************************
   **               Address Sharing                    **
   ******************************************************/

  /**
   * If the shared_with_contact_id is given this function
   *  renders the "address sharing" panel to deal with this
   *
   * @deprecated
   */
  protected function renderAddressSharingPanel($activity, $form, $address_submitted) {
    $group_name = $this->getCustomGroupName();
    if (!empty($activity["{$group_name}.shared_with_contact_id"])) {
      $shared_with_contact_id = $activity["{$group_name}.shared_with_contact_id"];
      $other_contacts = civicrm_api3('Contact', 'get', array(
        'id'     => $shared_with_contact_id,
        'return' => 'is_deleted,display_name,contact_type,id'));
      if (empty($other_contacts['values'][$shared_with_contact_id])) {
        CRM_Core_Session::setStatus(E::ts("Referenced shared address contact [%1] could not be found.", array(1 => $shared_with_contact_id)), E::ts('Warning'), 'info');
        return;
      }
      $other_contact = $other_contacts['values'][$shared_with_contact_id];
      $other_contact['link'] = CRM_Utils_System::url("civicrm/contact/view", "reset=1&cid={$other_contact['id']}");

      // pull other addresses and add a display name
      $other_address_options = array();
      $other_addresses = civicrm_api3('Address', 'get', array(
        'contact_id'   => $shared_with_contact_id,
        'option.limit' => 0,
        'sequential'   => 1
      ))['values'];
      foreach ($other_addresses as $addr) {
        $this->resolveFields($addr);
        $other_address_options[$addr['id']] =
          "({$addr['location_type']}) {$addr['street_address']}, {$addr['postal_code']} {$addr['city']}";
      }
      if ($this->hasAddressData($address_submitted)) {
        $other_address_options['new'] = E::ts("new address");
      }
      $other_address_options['none'] = E::ts("don't share");

      // create dropdown
      $form->add(
        'select',
        'i3val_address_sharing_addresses',
        E::ts("Share with"),
        $other_address_options,
        FALSE,
        array('class' => 'crm-select2 huge')
      );

      // create location type dropdown
      $form->add(
        'select',
        'i3val_address_sharing_location_type',
        E::ts("Share"),
        $this->getLocationTypeList(),
        FALSE,
        array('class' => 'crm-select2')
      );

      // assign stuff
      $form->add('hidden', 'i3val_address_sharing_contact_id', $other_contact['id']);
      $form->assign('i3val_address_sharing_contact', $other_contact);
    }
  }

  /**
   * Get address sharing contact ID
   * It will recognise the contact_id in two parameters:
   *  - shared_with_contact_id
   *  - address_master_contact_id
   *
   */
  protected function getAddressSharingContactID($submitted_data) {
    if (!empty($submitted_data['shared_with_contact_id']) && is_numeric($submitted_data['shared_with_contact_id'])) {
      return $submitted_data['shared_with_contact_id'];
    }
    if (!empty($submitted_data['address_master_contact_id']) && is_numeric($submitted_data['address_master_contact_id'])) {
      return $submitted_data['address_master_contact_id'];
    }
    $activity_shared_attribute = $this->getCustomGroupName() . '.shared_with_contact_id';
    if (!empty($submitted_data[$activity_shared_attribute]) && is_numeric($submitted_data[$activity_shared_attribute])) {
      return $submitted_data[$activity_shared_attribute];
    }
    return NULL;
  }

  /**
   * Process diff for the address sharing feature
   * It will recognise the contact_id in two parameters:
   *  - shared_with_contact_id
   *  - address_master_contact_id
   *
   */
  protected function addAddressSharingDiffData($existing_address, $submitted_data, &$activity_data) {
    $group_name = $this->getCustomGroupName();
    $shared_with_contact_id = $this->getAddressSharingContactID($submitted_data);
    if ($shared_with_contact_id) {
      // if address share is requested, check if it's already there
      $master_id = CRM_Utils_Array::value('master_id', $existing_address);
      if ($master_id) {
        $master_address = civicrm_api3('Address', 'getsingle', array(
          'id'     => $master_id,
          'return' => 'contact_id'));
        if ($master_address['contact_id'] == $shared_with_contact_id) {
          // the address is already shared with this contact -> do nothing
          return;
        }
      }

      // all good, let's book it
      $activity_data["{$group_name}.shared_with_contact_id"] = $shared_with_contact_id;
    }
  }

  /**
   * apply address sharing (if there is any)
   */
  protected function applyAddressSharingChanges($activity, $action, $values, &$activity_update, $address_update) {
    if (empty($values['i3val_address_sharing_addresses'])) {
      // nothing to do here...
      return;
    }

    // get some variables
    $contact_id = $values['contact_id'];
    $address_id = CRM_Utils_Array::value('id', $address_update);
    $group_name = $this->getCustomGroupName();
    $other_contact_id = $values['i3val_address_sharing_contact_id'];

    // ok, let's see what the user wants:
    switch ($values['i3val_address_sharing_addresses']) {
      case 'none': // DON'T SHARE
        $activity_update["{$group_name}.action"] .= ' | ' . E::ts("Address not shared");
        break;

      case 'new': // CREATE NEW ADDRESS WITH OTHER CONTACT
        if (empty($address_id)) {
          // this shouldn't happen...
          CRM_Core_Session::setStatus(E::ts("Active address not found!", array(1 => $shared_with_contact_id)), E::ts('Error'), 'error');
        } else {
          // load the current address, and use the date to
          //   create a new address with the other contact
          $address = $this->getStrippedAddressData($address_id);
          $address['contact_id']    = $other_contact_id;
          $address['master_id']     = ''; // reset old sharing (if exists)
          $address['location_type'] = $values['i3val_address_sharing_location_type'];
          $this->resolveFields($address);
          $new_address = civicrm_api3('Address', 'create', $address);

          // now, set the the master_id of the old (contact) address to the newly created one
          civicrm_api3('Address', 'create', array(
            'id'         => $address_id,
            'contact_id' => $contact_id, // API crashes if not provided
            'master_id'  => $new_address['id']));

          // not to forget: call the function to sync shared address
          CRM_Core_BAO_Address::processSharedAddress($new_address['id'], $address);

          // finally: share the good news
          $activity_update["{$group_name}.action"] .= ' | ' . E::ts("Address shared");
        }
        break;

      default: // SHARE WITH EXISTING ADDRESS
        $shared_address_id = $values['i3val_address_sharing_addresses'];
        if (!is_numeric($shared_address_id)) {
          // this shoudln't happen...
          CRM_Core_Session::setStatus(E::ts("Selected address not found!", array(1 => $shared_with_contact_id)), E::ts('Error'), 'error');
          break;
        }
        if ($action == 'share') {
          // this means that our contact has no address yet
          //    and wants to copy the master's one.
          $address = $this->getStrippedAddressData($shared_address_id);
          $address['contact_id']    = $contact_id;
          $address['master_id']     = $shared_address_id;
          $address['location_type'] = $values['address_location_type_applied'];
          $this->resolveFields($address);
          $new_address = civicrm_api3('Address', 'create', $address);

        } else {
          // this means that our address ($address_id) has been
          //  created/updated but should also be shared:

          // just a quick security check...
          if (!is_numeric($address_id)) {
            // this shoudln't happen...
            if ($action == 'discard') {
              CRM_Core_Session::setStatus(E::ts("You selected an address to share, but to discard the address. As a result, NO sharing was applied."), E::ts('Not shared!'), 'warn');
            } else {
              CRM_Core_Session::setStatus(E::ts("Something went wrong, cannot find the sharing address."), E::ts('Error'), 'error');
            }
            break;
          } else {
            // now, set the new address as master_id
            civicrm_api3('Address', 'create', array(
              'id'         => $address_id,
              'contact_id' => $values['contact_id'], // API crashes if not provided
              'master_id'  => $shared_address_id));
          }

          // but also, apply the same update to the master address
          $address_update['id']         = $shared_address_id;
          $address_update['contact_id'] = $other_contact_id;
          $address_update['master_id']  = '';
          unset($address_update['is_billing']);
          unset($address_update['is_primary']);
          unset($address_update['location_type_id']);
          civicrm_api3('Address', 'create', $address_update);
        }

        // not to forget: call the function to sync shared address
        $shared_address = civicrm_api3('Address', 'getsingle', array('id' => $shared_address_id));
        CRM_Core_BAO_Address::processSharedAddress($shared_address_id, $shared_address);

        // finally: share the good news
        $activity_update["{$group_name}.action"] .= ' | ' . E::ts("Address shared");
        break;
    }
  }

  /**
   * Add an extra 'share' option if address sharing is active
   *  AND there is NO data to create our own address
   *
   *  This option will copy the shared address without updates
   */
  protected function adjustAddressSharingOptions(&$options, $activity_data) {
    $shared_with_contact_id = $this->getAddressSharingContactID($activity_data);
    if (empty($shared_with_contact_id)) {
      // address sharing not active
      return;
    }

    // if no address can be created (lack of data), but
    //   there is an address sharing link add an option to share it
    if (!isset($options['add']) && $shared_with_contact_id) {
      // check if that contact has addresses
      $address_count = civicrm_api3('Address', 'getcount', array('contact_id' => $shared_with_contact_id));
      if ($address_count > 0) {
        $options['share'] = E::ts("Share selected address");
      }
    }
  }

  /**
   * Get an address and strip the instance related data
   */
  protected function getStrippedAddressData($address_id) {
    $address = civicrm_api3('Address', 'getsingle', array('id' => $address_id));
    unset($address['id']);
    unset($address['is_primary']);
    unset($address['is_billing']);
    unset($address['master_id']);
    unset($address['location_type_id']);
    unset($address['contact_id']);
    return $address;
  }

}
