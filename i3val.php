<?php
/*-------------------------------------------------------+
| Ilja's Input Validation Extension                      |
| Amnesty International Vlaanderen                       |
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

declare(strict_types = 1);

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
require_once 'i3val.civix.php';
// phpcs:enable

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Implements hook_civicrm_container().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_container/
 */
function i3val_civicrm_container(ContainerBuilder $container) {
  if (class_exists('Civi\I3val\ContainerSpecs')) {
    $container->addCompilerPass(new Civi\I3val\ContainerSpecs());
  }
}

/**
 * Implements hook_civicrm_buildForm().
 *
 * Inject JS code for UI manipulations
 */
function i3val_civicrm_buildForm($formName, &$form) {
  // phpcs:disable Generic.CodeAnalysis.EmptyStatement.DetectedIf
  if ($formName === 'CRM_Activity_Form_Activity') {
    // phpcs:disable Squiz.PHP.CommentedOutCode.Found
    // TODO: Implement
    // CRM_I3val_Logic::adjustAcitivityView($form->_activityId, $form->_activityTypeId);
  }
  // phpcs:enable
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
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function i3val_civicrm_install() {
  _i3val_civix_civicrm_install();
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
  CRM_Utils_File::sourceSQLFile($config->dsn, $sqlfile, NULL, FALSE);
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
  CRM_Utils_File::sourceSQLFile($config->dsn, $sqlfile, NULL, FALSE);

}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 */
function i3val_civicrm_navigationMenu(&$menu) {
  _i3val_civix_insert_navigation_menu($menu, 'Contacts', [
    'label'      => ts('Process Pending Update Requests', ['domain' => 'be.aivl.i3val']),
    'name'       => 'i3val_desktop',
    'url'        => 'civicrm/i3val/desktop?reset=1&restart=1',
    'permission' => 'edit all contacts',
    'operator'   => 'OR',
    'separator'  => 1,
  ]);

  _i3val_civix_insert_navigation_menu($menu, 'Administer/System Settings', [
    'label'      => ts('Configure I3Val', ['domain' => 'be.aivl.i3val']),
    'name'       => 'i3val_config',
    'url'        => 'civicrm/admin/i3val?reset=1',
    'permission' => 'administer CiviCRM',
    'operator'   => 'OR',
    'separator'  => 1,
  ]);

  _i3val_civix_navigationMenu($menu);
}
