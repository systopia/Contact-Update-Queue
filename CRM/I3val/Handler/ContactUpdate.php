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
class CRM_I3val_Handler_ContactUpdate extends CRM_I3val_ActivityHandler {

  public static $group_name = 'i3val_contact_updates';
  public static $field2label = NULL;

  public function getField2Label() {
    if (self::$field2label === NULL) {
      self::$field2label = array( 'first_name'         => E::ts('First Name'),
                                  'last_name'          => E::ts('Last Name'),
                                  'formal_title'       => E::ts('Formal Title'),
                                  'organization_name'  => E::ts('Organisation Name'),
                                  'household_name'     => E::ts('Household Name'),
                                  'preferred_language' => E::ts('Preferred Language'),
                                  'job_title'          => E::ts('Job Title'),
                                  'prefix'             => E::ts('Prefix'),
                                  'suffix'             => E::ts('Suffix'),
                                  'gender'             => E::ts('Gender'),
                                  'birth_date'         => E::ts('Birth Date'),
                                  'do_not_email'       => E::ts('Do not Email'),
                                  'do_not_phone'       => E::ts('Do not Phone'),
                                  'do_not_mail'        => E::ts('Do not Mail'),
                                  'do_not_sms'         => E::ts('Do not SMS'),
                                  'do_not_trade'       => E::ts('Do not trade'),
                                  'is_opt_out'         => E::ts('Opt-Out'),
                                  'is_deceased'        => E::ts('Deceased'),
                                  'deceased_date'      => E::ts('Deceased Date'),
                                );
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
    return E::ts("Contact Update");
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
    return array(__DIR__ . '/../../../resources/contact_updates_custom_group.json');
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
          $this->getOptionValueList('languages')
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

    // load contact
    $contact = civicrm_api3('Contact', 'getsingle', array('id' => $submitted_data['id']));
    $activity_data['target_id'] = $contact['id'];

    // resolve prefix/suffix
    if (!empty($contact['individual_prefix'])) {
      $contact['prefix'] = $contact['individual_prefix'];
    }
    if (!empty($contact['individual_suffix'])) {
      $contact['suffix'] = $contact['individual_suffix'];
    }

    $this->resolveFields($contact);
    $this->resolvePreferredLanguageToLabel($contact);
    $this->resolveFields($submitted_data);
    $this->resolvePreferredLanguageToLabel($submitted_data);

    $raw_diff = $this->createDiff($contact, $submitted_data);
    // TODO: sort out special cases (e.g. dates)

    foreach ($raw_diff as $key => $value) {
      $activity_data[$key] = $value;
    }
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
        return $this->getOptionValueList('gender');

      case 'prefix':
        return $this->getOptionValueList('individual_prefix');

      case 'suffix':
        return $this->getOptionValueList('individual_suffix');

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
