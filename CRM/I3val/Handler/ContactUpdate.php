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

/**
 * this class will handle performing the changes
 *  that are passed on from the API call
 */
class CRM_I3val_Handler_ContactUpdate extends CRM_I3val_ActivityHandler {

  public static $group_name = 'fwtm_contact_updates';
  public static $fields     = array('first_name' => 'First Name',
                                    'last_name'  => 'Last Name');


  /**
   * get the list of
   */
  public function getFields() {
    return array_keys(self::$fields);
  }

  /**
   * Verify whether the changes make sense
   *
   * @return array $key -> error message
   */
  public function verifyChanges($activity, $changes, $objects = array()) {
    // TODO
    return array();
  }

  /**
   * Apply the changes
   *
   * @return array with changes to the activity
   */
  public function applyChanges($activity, $changes, $objects = array()) {
    // TODO
    return array();
  }



  /**
   * Load and assign necessary data to the form
   */
  public function renderActivityData($activity, $form) {
    $values = $this->compileValues(self::$group_name, self::$fields, $activity);
    $this->addCurrentValues($values, $form->contact);

    $form->assign('i3val_contact_fields', self::$fields);
    $form->assign('i3val_contact_values', $values);

    // create input fields and apply checkboxes
    foreach (self::$fields as $fieldname => $fieldlabel) {
      // add the text input
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

      // add the apply checkbox
      $form->add(
        'checkbox',
        "{$fieldname}_apply",
        $fieldlabel
      );
      $form->setDefaults(array("{$fieldname}_apply" => 1));
    }
  }

  /**
   * Get the path of the template rendering the form
   */
  public function getTemplate() {
    return 'CRM/I3val/Handler/ContactUpdate.tpl';
  }

}
