<?php
/*-------------------------------------------------------+
| Contact Update Queue Extension                         |
| Funded by Amnesty International Vlaanderen             |
| Copyright (C) 2017-2019 SYSTOPIA                       |
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

namespace Civi\Contactupdatequeue\ActionProvider\Action;

use CRM_Contactupdatequeue_ExtensionUtil as E;

use \Civi\ActionProvider\Action\AbstractAction;
use \Civi\ActionProvider\Parameter\ParameterBagInterface;
use \Civi\ActionProvider\Parameter\Specification;
use \Civi\ActionProvider\Parameter\SpecificationBag;

class RequestContactUpdate extends AbstractAction {

  /**
   * Returns the specification of the configuration options for the actual action.
   *
   * @return SpecificationBag specs
   */
  public function getConfigurationSpecification() {
    // get the current configuration
    $configuration = \CRM_Contactupdatequeue_Configuration::getConfiguration();

    // specify config
    return new SpecificationBag([
        new Specification('activity_type_id', 'Integer', E::ts('Default Activity Type'), false, null, null, $configuration->getActivityTypes(), false),
    ]);
  }

  /**
   * Returns the specification of the parameters of the actual action.
   *
   * @return SpecificationBag specs
   */
  public function getParameterSpecification() {
    $specs = [];
    $specs[] = new Specification('activity_type_id', 'Integer', E::ts('Activity Type'), false, null, null, null, false);
    $specs[] = new Specification('id', 'Integer', E::ts('Contact ID'), false, null, null, null, false);
    $specs[] = new Specification('contactupdatequeue_note', 'String', E::ts('Note'), false, null, null, null, false);
    $specs[] = new Specification('contactupdatequeue_note_schedule_date', 'String', E::ts('Requested Change Date'), false, null, null, null, false);
    $specs[] = new Specification('contactupdatequeue_note_parent_id', 'Integer', E::ts('Linked Activity ID'), false, null, null, null, false);

    // calculate handler input fields
    $config = \CRM_Contactupdatequeue_note_Configuration::getConfiguration();
    $handlers = $config->getHandlersForEntity('Contact');
    foreach ($handlers as $handler) {
      /** @var $handler \CRM_Contactupdatequeue_ActivityHandler */
      $fields =  $handler->getField2Label();
      foreach ($fields as $field_name => $field_label) {
        $specs[] = new Specification($field_name, 'String', $field_label, false, null, null, null, false);
      }
    }

    return new SpecificationBag($specs);
  }

  /**
   * @return SpecificationBag
   */
  public function getOutputSpecification() {
    return new SpecificationBag([
      new Specification("result", "String", E::ts("Result Request"), FALSE),
    ]);
  }

  /**
   * Run the action
   *
   * @param ParameterBagInterface $parameters
   *   The parameters to this action.
   * @param ParameterBagInterface $output
   * 	 The parameters this action can send back
   * @return void
   */
  protected function doAction(ParameterBagInterface $parameters, ParameterBagInterface $output) {
    $params = $parameters->toArray();
    // override if necessary
    foreach (['activity_type_id', 'contactupdatequeue_note_note', 'contactupdatequeue_note_schedule_date', 'contactupdatequeue_note_parent_id'] as $field_name) {
      if (empty($params[$field_name])) {
        $params[$field_name] = $this->configuration->getParameter($field_name);
      }
    }
    // copy contact_id if set
    $contactId = $parameters->getParameter('contact_id');
    if ($contactId) {
      $params['id'] = $contactId;
    }

    // execute
    $result = \civicrm_api3('Contact', 'request_update', $params);
    if (is_array($result['values'])) {
      $output->setParameter('result', json_encode($result['values']));
    } else {
      $output->setParameter('result', $result['values']);
    }
  }
}
