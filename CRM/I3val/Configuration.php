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

declare(strict_types = 1);

use CRM_I3val_ExtensionUtil as E;

class CRM_I3val_Configuration {

  private static ?self $configuration = NULL;

  private array $config;

  /**
   * get the configuration singleton
   */
  public static function getConfiguration() {
    if (self::$configuration === NULL) {
      self::$configuration = new CRM_I3val_Configuration();
    }
    return self::$configuration;
  }

  public function __construct() {
    $this->config = self::getRawConfig();
  }

  /**
   * How long is a session valid
   */
  public function getSessionTTL() {
    return $this->config['session_ttl'] ?? '4 hours';
  }

  /**
   * How many items should be blocked at once for a session
   */
  public function getSessionSize() {
    return $this->config['session_size'] ?? 10;
  }

  /**
   * Should clear/empty fields be offered?
   */
  public function clearingFieldsAllowed() {
    return $this->config['allow_clearing'] ?? FALSE;
  }

  /**
   * Sanitise input accorting to the configuration
   */
  public function sanitiseInput(&$input) {
    $strip_chars = $this->config['strip_chars'] ?? '';
    if ($strip_chars) {
      foreach ($input as $key => &$value) {
        $value = trim($value, $strip_chars);
      }
    }

    $empty_token = $this->config['empty_token'] ?? NULL;
    if ($empty_token) {
      // if the empty token is set, all '' fields will be removed,
      foreach (array_keys($input) as $key) {
        if ($input[$key] == '') {
          unset($input[$key]);
        }
      }

      // ... and all fields that have the token set will be set to ''
      foreach ($input as $key => &$value) {
        if ($value == $empty_token) {
          $value = '';
        }
      }
    }
  }

  /**
   * This is one of the central configuration elements
   *
   * @return array<int, list<class-string<CRM_I3val_ActivityHandler>>>
   */
  protected function getActivityType2HandlerClasses(): array {
    $activity2handlers = [];
    $configurations = $this->config['configurations'] ?? [];
    foreach ($configurations as $configuration) {
      $activity2handlers[$configuration['activity_type_id']] = $configuration['handlers'];
    }
    return $activity2handlers;
  }

  /**
   * Returns a list of all active handler *classes*
   *
   * @return list<class-string<CRM_I3val_ActivityHandler>>
   */
  protected function getActiveHandlerClasses(): array {
    $handler_list = [];
    $configurations = $this->config['configurations'] ?? [];
    foreach ($configurations as $configuration) {
      /** @var class-string<CRM_I3val_ActivityHandler> $handler_class */
      foreach ($configuration['handlers'] as $handler_class) {
        $handler_list[$handler_class] = 1;
      }
    }

    return array_keys($handler_list);
  }

  /**
   * This is one of the central configuration elements
   *
   * @return array<string, list<class-string<\CRM_I3val_ActivityHandler>>>
   */
  protected function getEntity2HandlerClass(): array {
    $entity2handlers = [];

    $handlers = $this->getActiveHandlerClasses();
    foreach ($handlers as $handler_class) {
      $handler = new $handler_class();
      $entities = $handler->handlesEntities();
      foreach ($entities as $entity) {
        $entity2handlers[$entity][] = $handler_class;
      }
    }

    return $entity2handlers;
  }

  /**
   * get Handlers for entity
   *
   * @return list<\CRM_I3val_ActivityHandler>
   */
  public function getHandlersForEntity($entity): array {
    $handlers = [];
    $entity2HandlerClass = $this->getEntity2HandlerClass();
    if (isset($entity2HandlerClass[$entity])) {
      foreach ($entity2HandlerClass[$entity] as $handlerClass) {
        $handlers[] = new $handlerClass();
      }
    }
    return $handlers;
  }

  /**
   * get a handler instance for the given activity type
   *
   * @return list<\CRM_I3val_ActivityHandler>
   */
  public function getHandlersForActivityType($activity_type_id) {
    $handlers = [];
    $activityType2HandlerClass = $this->getActivityType2HandlerClasses();
    if (isset($activityType2HandlerClass[$activity_type_id])) {
      foreach ($activityType2HandlerClass[$activity_type_id] as $handlerClass) {
        $handlers[] = new $handlerClass();
      }
    }
    return $handlers;
  }

  /**
   * get the default activity type for the given entity
   */
  public function getDefaultActivityTypeForEntity($entity) {
    $configurations = $this->config['configurations'] ?? [];
    foreach ($configurations as $configuration) {
      /** @var class-string<CRM_I3val_ActivityHandler> $handler_class */
      foreach ($configuration['handlers'] as $handler_class) {
        $handler = new $handler_class();
        $entities = $handler->handlesEntities();
        if (in_array($entity, $entities)) {
          return $configuration['activity_type_id'];
        }
      }
    }

    return NULL;
  }

  /**
   * get the activity types based on the current user
   */
  public function getEligibleActivityTypes() {
    // TODO: evaluate permissions
    return $this->getActivityTypes();
  }

  /**
   * Select a default action from the list
   *
   * phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
   */
  public function pickDefaultAction($options, $proposed_default) {
  // phpcs:enable
    $settings_default = $this->getDefaultAction();
    switch ($settings_default) {
      default:
      case 'detect':
        // simply return default value.
        break;

      case 'add':
        if (substr($proposed_default, 0, 3) == 'add' || $proposed_default == 'share' || $proposed_default == 'new') {
          return $proposed_default;
        }
        foreach ($options as $option => $label) {
          if (substr($option, 0, 3) == 'add' || $option == 'share' || $option == 'new') {
            return $option;
          }
        }
        // fallback: simply return default
        break;

      case 'ignore':
        if ($proposed_default == 'discard' || $proposed_default == 'duplicate') {
          return $proposed_default;
        }
        foreach ($options as $option => $label) {
          if ($option == 'discard' || $option == 'duplicate') {
            return $option;
          }
        }
        // fallback: simply return default
        break;

      case 'overwrite':
        if ($proposed_default == 'update') {
          return $proposed_default;
        }
        foreach ($options as $option => $label) {
          if ($option == 'update') {
            return $option;
          }
        }
        // fallback: simply return default
        break;
    }

    // default
    return $proposed_default;
  }

  /**
   * get the default action
   */
  public function getDefaultAction() {
    if (empty($this->config['default_action'])) {
      // default value
      return 'detect';
    }
    else {
      return $this->config['default_action'];
    }
  }

  /**
   * get an array id => label of the relevant activity types
   */
  public function getActivityTypes() {
    $activity_types = [];
    $configurations = $this->config['configurations'] ?? [];
    foreach ($configurations as $configuration) {
      $activity_type_id = $configuration['activity_type_id'];
      $activity_types[$activity_type_id] = 'Unknown';
    }

    // TODO: cache labels?
    if (!empty($activity_types)) {
      $labels = civicrm_api3('OptionValue', 'get', [
        'option_group_id' => 'activity_type',
        'value'           => ['IN' => array_keys($activity_types)],
        'return'          => 'value,label',
        'option.limit'    => 0,
      ]);
      foreach ($labels['values'] as $option_value) {
        $activity_types[$option_value['value']] = $option_value['label'];
      }
    }

    return $activity_types;
  }

  /**
   * get an array id => label of the relevant activity statuses
   */
  public function getLiveActivityStatuses() {
    // TODO: config?
    $statuses = [
    // Scheduled
      1,
      // TODO Waiting?
    ];
    return $statuses;
  }

  /**
   * Check if the activity is LIVE, i.e. as one of the LIVE statuses
   *
   * @param $activity_id int activity ID
   * @return boolean true if it is live
   * @see getLiveActivityStatuses
   */
  public function isActivityLive($activity_id) {
    $activity_id = (int) $activity_id;
    if ($activity_id) {
      $activity_status_ids = implode(',', $this->getLiveActivityStatuses());
      $activity_live = CRM_Core_DAO::singleValueQuery(
        "SELECT id FROM civicrm_activity WHERE id = {$activity_id} AND status_id IN ({$activity_status_ids})"
      );
      return !empty($activity_live);
    }
    else {
      // no ID? not LIVE!
      return FALSE;
    }
  }

  /**
   * get the activity types to be shown in the quick history
   */
  public function getQuickHistoryTypes() {
    return $this->config['quickhistory'] ?? [];
  }

  /**
   * get the contact this activity should be assigend to
   * @deprecated
   */
  public function getAssignee() {
    // TODO: create a setting for this
    return 215988;
  }

  /**
   * get the activity status ID meaning "flagged as problem"
   */
  public function getErrorStatusID() {
    return $this->config['flag_status'] ?? 3;
  }

  /**
   * get options for postpone button
   * format is days => label
   */
  public function getPostponeOptions() {
    return [
      '10 min'  => E::ts('10 minutes'),
      '1 hour'  => E::ts('1 hour'),
      '3 hour'  => E::ts('3 hours'),
      '1 day'   => E::ts('1 day'),
      '2 days'  => E::ts('2 days'),
      '7 days'  => E::ts('1 week'),
      '14 days' => E::ts('2 weeks'),
      '30 days' => E::ts('1 month'),
    ];
  }

  /**
   * determine the current user ID
   * @see https://github.com/CiviCooP/org.civicoop.apiuidfix
   */
  public static function getCurrentUserID($fallback_id = NULL) {
    // try the session first
    $session = CRM_Core_Session::singleton();
    $userId = $session->get('userID');
    if (!empty($userId)) {
      return $userId;
    }

    // check via API key, i.e. when coming through REST-API
    /** @var string|null $api_key */
    $api_key = CRM_Utils_Request::retrieve('api_key', 'String');
    if (!$api_key || strtolower($api_key) == 'null') {
      // nothing we can do
      return self::getFallbackUserID($fallback_id);
    }

    // load user via API KEU
    // @phpstan-ignore argument.type
    $valid_user = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $api_key, 'id', 'api_key');

    // If we didn't find a valid user, die
    if (!empty($valid_user)) {
      //now set the UID into the session
      return $valid_user;
    }

    // nothing we can do
    return self::getFallbackUserID($fallback_id);
  }

  /**
   * get the fallback user
   */
  protected static function getFallbackUserID($fallback_id = NULL) {
    // TODO: configure
    if ($fallback_id) {
      return $fallback_id;
    }

    // TODO: configure?

    // now: last resort: just get any contact
    $any_contact = civicrm_api3('Contact', 'get', ['option.limit' => 1, 'return' => 'id']);
    return $any_contact['id'];
  }

  /**
   * get the raw config array
   */
  public static function getRawConfig(): array {
    $value = CRM_Core_BAO_Setting::getItem('i3val', 'i3val_config');
    if (!is_array($value)) {
      return [];
    }
    else {
      return $value;
    }
  }

  /**
   * set the raw config array
   */
  public static function setRawConfig(array $config): void {
    Civi::settings()->set('i3val_config', $config);
    self::$configuration = NULL;
  }

  /**
   * adjust the custom fields to the given configuration, that includes:
   *  - updating/creating the custom groups of active handers
   *  - assigning these groups to the associated activity types
   */
  public static function synchroniseCustomFields() {
    // first: create all handler classes
    $customData = new CRM_I3val_CustomData('be.aivl.i3val');

    $config = self::getConfiguration();
    $activeHanders = $config->getActiveHandlerClasses();
    /** @var class-string<CRM_I3val_ActivityHandler> $handler_class */
    foreach ($activeHanders as $handler_class) {
      $handler = new $handler_class();
      $spec_files = $handler->getCustomGroupSpeficationFiles();
      foreach ($spec_files as $spec_file) {
        $customData->syncCustomGroup($spec_file);
      }
    }

    // then, make sure the activity assignments match
    $group2types = [];

    // find out which custom groups need to be available for which activity types
    $type2class = $config->getActivityType2HandlerClasses();
    foreach ($type2class as $activity_type_id => $handler_classes) {
      /** @var class-string<CRM_I3val_ActivityHandler> $handler_class */
      foreach ($handler_classes as $handler_class) {
        $handler = new $handler_class();
        $group_name = $handler->getCustomGroupName();
        $group2types[$group_name][] = $activity_type_id;
      }
    }

    // then adjust the settings
    foreach ($group2types as $group_name => $activity_type_ids) {
      $custom_group = civicrm_api3('CustomGroup', 'get', ['name' => $group_name]);
      if ($custom_group['id']) {
        $custom_group = reset($custom_group['values']);
        civicrm_api3('CustomGroup', 'create', [
          'id'                          => $custom_group['id'],
        // prevent PHP notices
          'title'                       => $custom_group['title'],
        // prevent PHP notices
          'extends'                     => 'Activity',
        // prevent destructive API
          'style'                       => 'Inline',
          'is_active'                   => 1,
          'extends_entity_column_value' => CRM_Utils_Array::implodePadded($activity_type_ids),
        ]);
      }
      else {
        // ERROR handling
        error_log("ERROR! Couldn't find custom group '{$group_name}'");
      }
    }
  }

}
