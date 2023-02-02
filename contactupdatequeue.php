<?php
/*-------------------------------------------------------+
| Contact Update Queue Extension                         |
| Funded by Amnesty International Vlaanderen             |
| Copyright (C) 2019 SYSTOPIA                            |
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

require_once 'contactupdatequeue.civix.php';

use \Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Implements hook_civicrm_container()
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_container/
 */
function contactupdatequeue_civicrm_container(ContainerBuilder $container) {
  if (class_exists("Civi\Contactupdatequeue\ContainerSpecs")) {
    $container->addCompilerPass(new Civi\Contactupdatequeue\ContainerSpecs());
  }
}

/**
 * Implements hook_civicrm_buildForm()
 *  to inject JS code for UI manipulations
 */
function contactupdatequeue_civicrm_buildForm($formName, &$form) {
  if ($formName == 'CRM_Activity_Form_Activity') {
    // TODO: Implement
    // CRM_Contactupdatequeue_Logic::adjustAcitivityView($form->_activityId, $form->_activityTypeId);
  }
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function contactupdatequeue_civicrm_config(&$config) {
  _contactupdatequeue_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function contactupdatequeue_civicrm_xmlMenu(&$files) {
  _contactupdatequeue_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function contactupdatequeue_civicrm_install() {
  _contactupdatequeue_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function contactupdatequeue_civicrm_postInstall() {
  _contactupdatequeue_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function contactupdatequeue_civicrm_uninstall() {
  _contactupdatequeue_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function contactupdatequeue_civicrm_enable() {
  _contactupdatequeue_civix_civicrm_enable();

  // create the session table
  $config = CRM_Core_Config::singleton();
  $sqlfile = dirname(__FILE__) . '/sql/install.sql';
  CRM_Utils_File::sourceSQLFile($config->dsn, $sqlfile, NULL, false);
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function contactupdatequeue_civicrm_disable() {
  // remove the session table
  $config = CRM_Core_Config::singleton();
  $sqlfile = dirname(__FILE__) . '/sql/uninstall.sql';
  CRM_Utils_File::sourceSQLFile($config->dsn, $sqlfile, NULL, false);

  _contactupdatequeue_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function contactupdatequeue_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _contactupdatequeue_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function contactupdatequeue_civicrm_managed(&$entities) {
  _contactupdatequeue_civix_civicrm_managed($entities);
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
function contactupdatequeue_civicrm_caseTypes(&$caseTypes) {
  _contactupdatequeue_civix_civicrm_caseTypes($caseTypes);
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
function contactupdatequeue_civicrm_angularModules(&$angularModules) {
  _contactupdatequeue_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function contactupdatequeue_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _contactupdatequeue_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 */
function contactupdatequeue_civicrm_navigationMenu(&$menu) {
  _contactupdatequeue_civix_insert_navigation_menu($menu, 'Contacts', array(
    'label'      => ts('Process Pending Update Requests', array('domain' => 'be.aivl.i3val')),
    'name'       => 'contactupdatequeue_desktop',
    'url'        => 'civicrm/contactupdatequeue/desktop?reset=1&restart=1',
    'permission' => 'edit all contacts',
    'operator'   => 'OR',
    'separator'  => 1,
  ));

  _contactupdatequeue_civix_insert_navigation_menu($menu, 'Administer/System Settings', array(
    'label'      => ts('Configure Contact Update Queue', array('domain' => 'be.aivl.i3val')),
    'name'       => 'contactupdatequeue_config',
    'url'        => 'civicrm/admin/contactupdatequeue?reset=1',
    'permission' => 'administer CiviCRM',
    'operator'   => 'OR',
    'separator'  => 1,
  ));

  _contactupdatequeue_civix_navigationMenu($menu);
}
