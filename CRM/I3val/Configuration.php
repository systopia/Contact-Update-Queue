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

class CRM_I3val_Configuration {

  /**
   * get the contact this activity should be assigend to
   */
  public static function getAssignee() {
    // TODO: create a setting for this
    return 2;
  }

  /**
   * Get the ID of the currenty active user
   */
  public static function getCurrentUserID() {
    // TODO: sanity checks needed?
    return CRM_Core_Session::getLoggedInContactID();
  }

  /**
   * Get an array(field_name => array) of all the
   * fields that can be recorded in an update activity
   */
  public static function getContactUpdateFields() {
    return array(
      'first_name' => array(
          'title'        => 'First Name',
          'type'         => CRM_Utils_Type::T_STRING,
          'custom_group' => 'fwtm_contact_updates'),

      'last_name' => array(
          'title'        => 'Last Name',
          'type'         => CRM_Utils_Type::T_STRING,
          'custom_group' => 'fwtm_contact_updates'),
      );
  }


  /**
   * Get an array(field_name => title) of all the
   * fields that can be recorded in an update activity
   */
  public static function getMandateUpdateFields() {

  }
}
