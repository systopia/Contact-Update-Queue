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

require_once 'i3val.civix.php';

/**
 * Implements hook_civicrm_buildForm()
 *  to inject JS code for UI manipulations
 */
function i3val_civicrm_buildForm($formName, &$form) {
  if ($formName == 'CRM_Activity_Form_Activity') {
    // CRM_I3val_Logic::adjustAcitivityView($form->_activityId, $form->_activityTypeId);
  }
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function i3val_civicrm_config(&$config) {
  _i3val_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function i3val_civicrm_xmlMenu(&$files) {
  _i3val_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function i3val_civicrm_install() {
  _i3val_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function i3val_civicrm_postInstall() {
  _i3val_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function i3val_civicrm_uninstall() {
  _i3val_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function i3val_civicrm_enable() {
  _i3val_civix_civicrm_enable();

  // create the session table
  $config = CRM_Core_Config::singleton();
  $sqlfile = dirname(__FILE__) . '/sql/install.sql';
  CRM_Utils_File::sourceSQLFile($config->dsn, $sqlfile, NULL, false);

  // create custom data
  // TODO: move to configuration
  require_once 'CRM/I3val/CustomData.php';
  $customData = new CRM_I3val_CustomData('de.systopia.contract');
  $customData->syncOptionGroup(__DIR__ . '/resources/activity_types_option_group.json');
  $customData->syncCustomGroup(__DIR__ . '/resources/contact_updates_custom_group.json');
  // $customData->syncCustomGroup(__DIR__ . '/resources/address_updates_custom_group.json');
  $customData->syncCustomGroup(__DIR__ . '/resources/email_updates_custom_group.json');
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function i3val_civicrm_disable() {
  // remove the session table
  $config = CRM_Core_Config::singleton();
  $sqlfile = dirname(__FILE__) . '/sql/uninstall.sql';
  CRM_Utils_File::sourceSQLFile($config->dsn, $sqlfile, NULL, false);

  _i3val_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function i3val_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _i3val_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function i3val_civicrm_managed(&$entities) {
  _i3val_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function i3val_civicrm_caseTypes(&$caseTypes) {
  _i3val_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function i3val_civicrm_angularModules(&$angularModules) {
  _i3val_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function i3val_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _i3val_civix_civicrm_alterSettingsFolders($metaDataFolders);
}
