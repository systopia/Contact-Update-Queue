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
 * Dispatch any request to a service provided by the handlers
 */
function civicrm_api3_i3val_service($params) {
  $handler_class = 'CRM_I3val_Handler_' . $params['handler'];
  if (class_exists($handler_class)) {
    $method = 'service_' . $params['service'];
    if (method_exists($handler_class, $method)) {
      return $handler_class::$method($params);
    } else {
      return civicrm_api3_create_error("Handler '{$handler_class}' doesn't provide servivce '{$method}'.");
    }
  } else {
    return civicrm_api3_create_error("Handler '{$handler_class}' doesn't exist.");
  }
}


/**
 * Dispatch any request to a service provided by the handlers
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 */
function _civicrm_api3_i3val_service_spec(&$spec) {
  $spec['handler'] = array(
    'title'       => 'Handler Name',
    'description' => 'The name of the handler',
    'required'    => TRUE,
    'type'        => CRM_Utils_Type::T_STRING,
  );
  $spec['service'] = array(
    'title'       => 'Service Name',
    'description' => 'Will be dispatched to the given static service_XX function of the handler',
    'required'    => TRUE,
    'type'        => CRM_Utils_Type::T_STRING,
  );
}
