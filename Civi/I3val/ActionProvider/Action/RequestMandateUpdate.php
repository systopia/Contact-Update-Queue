<?php
/*-------------------------------------------------------+
| Ilja's Input Validation Extension                      |
| Amnesty International Vlaanderen                       |
| Copyright (C) 2017-2020 SYSTOPIA                       |
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

namespace Civi\I3val\ActionProvider\Action;

use CRM_I3val_ExtensionUtil as E;

use Civi\ActionProvider\Action\AbstractAction;
use Civi\ActionProvider\Parameter\ParameterBagInterface;
use Civi\ActionProvider\Parameter\Specification;
use Civi\ActionProvider\Parameter\SpecificationBag;

class RequestMandateUpdate extends AbstractAction {

  /**
   * Returns the specification of the configuration options for the actual action.
   * @return \Civi\ActionProvider\Parameter\SpecificationBag specs
   */
  public function getConfigurationSpecification() {
    // get the current configuration
    $configuration = \CRM_I3val_Configuration::getConfiguration();

    // allowed status requests
    $requested_status = [
      ''         => E::ts('No Status Change'),
      'COMPLETE' => E::ts('Terminate Mandate (COMPLETE)'),
      'INVALID'  => E::ts('Mark Mandate as Invalid (INVALID)'),
    ];

    // specify config
    return new SpecificationBag([
      new Specification(
        'activity_type_id',
        'Integer',
        E::ts('Default Activity Type'),
        FALSE,
        NULL,
        NULL,
        $configuration->getActivityTypes(),
        FALSE
      ),
      new Specification('i3val_note', 'String', E::ts('Note')),
      new Specification('status', 'String', E::ts('Requested Status'), FALSE, NULL, NULL, $requested_status, FALSE),
    ]);
  }

  /**
   * Returns the specification of the parameters of the actual action.
   *
   * @return \Civi\ActionProvider\Parameter\SpecificationBag specs
   */
  public function getParameterSpecification() {
    $specs = [];
    // add metadata
    $specs[] = new Specification('activity_type_id', 'Integer', E::ts('Activity Type'), FALSE, NULL, NULL, NULL, FALSE);
    // this parameter was originally named id but the text was contact ID. It is renamed to contact_id as that is
    // also what the action allows the user to specify. If the mandate ID is actually required it should be a new
    // parameter named mandate_id
    $specs[] = new Specification('contact_id', 'Integer', E::ts('Contact ID'), FALSE, NULL, NULL, NULL, FALSE);
    $specs[] = new Specification('i3val_note', 'String', E::ts('Note'), FALSE, NULL, NULL, NULL, FALSE);
    $specs[] = new Specification(
      'i3val_schedule_date', 'String', E::ts('Requested Change Date'), FALSE, NULL, NULL, NULL, FALSE
    );
    $specs[] = new Specification(
      'i3val_parent_id', 'Integer', E::ts('Linked Activity ID'), FALSE, NULL, NULL, NULL, FALSE
    );
    $specs[] = new Specification('source', 'String', E::ts('Source'), FALSE, NULL, NULL, NULL, FALSE);
    $specs[] = new Specification('sdd_reason', 'String', E::ts('Cancel Reason'), FALSE, NULL, NULL, NULL, FALSE);
    // add mandate identifier
    $specs[] = new Specification('reference', 'String', E::ts('Mandate Reference'), TRUE, NULL, NULL, NULL, FALSE);
    // basic fields
    $specs[] = new Specification('status', 'String', E::ts('Status'), FALSE, NULL, NULL, NULL, FALSE);
    $specs[] = new Specification('iban', 'String', E::ts('IBAN'), FALSE, NULL, NULL, NULL, FALSE);
    $specs[] = new Specification('bic', 'String', E::ts('BIC'), FALSE, NULL, NULL, NULL, FALSE);
    // add date fields
    $specs[] = new Specification('date', 'Date', E::ts('Signature Date'), FALSE, NULL, NULL, NULL, FALSE);
    $specs[] = new Specification('validation_date', 'Date', E::ts('Validation Date'), FALSE, NULL, NULL, NULL, FALSE);
    $specs[] = new Specification('start_date', 'Date', E::ts('Start Date'), FALSE, NULL, NULL, NULL, FALSE);
    $specs[] = new Specification('end_date', 'Date', E::ts('End Date'), FALSE, NULL, NULL, NULL, FALSE);
    // add collection fields
    // note that frequency expects the number of collections per year! So monhtly would be 12, quarterly 4 etc.
    $specs[] = new Specification('frequency', 'Integer', E::ts('Frequency'), FALSE, NULL, NULL, NULL, FALSE);
    $specs[] = new Specification('cycle_day', 'Integer', E::ts('Cycle Day'), FALSE, NULL, NULL, NULL, FALSE);
    $specs[] = new Specification('financial_type', 'String', E::ts('Financial Type'), FALSE, NULL, NULL, NULL, FALSE);
    $specs[] = new Specification('campaign', 'String', E::ts('Campaign'), FALSE, NULL, NULL, NULL, FALSE);
    $specs[] = new Specification('amount', 'Float', E::ts('Amount'), FALSE, NULL, NULL, NULL, FALSE);
    return new SpecificationBag($specs);
  }

  /**
   * @return \Civi\ActionProvider\Parameter\SpecificationBag
   */
  public function getOutputSpecification() {
    return new SpecificationBag([
      new Specification('result', 'String', E::ts('Result Request'), FALSE),
    ]);
  }

  /**
   * Run the action
   *
   * @param \Civi\ActionProvider\Parameter\ParameterBagInterface $parameters
   *   The parameters to this action.
   * @param \Civi\ActionProvider\Parameter\ParameterBagInterface $output
   *      The parameters this action can send back
   * @return void
   */
  protected function doAction(ParameterBagInterface $parameters, ParameterBagInterface $output) {
    $params = $parameters->toArray();
    // override if necessary
    foreach (['activity_type_id', 'i3val_note', 'status'] as $field_name) {
      if (empty($params[$field_name])) {
        $params[$field_name] = $this->configuration->getParameter($field_name);
      }
    }
    // fix contact_id if required (sepa mandate will treat id as id of the mandate!)
    // hackish fix because after clearing caches and deleting everything from the templates_c folder the
    // parameter id kept recurring. This hack will fix this, and only cause problems if the id is
    // actually used on purpose meaning the mandate id. But then a new parameter called mandate_id should
    // be introduced
    if (!isset($params['contact_id']) && isset($params['id'])) {
      $params['contact_id'] = $params['id'];
      unset($params['id']);
    }
    // execute
    $result = \civicrm_api3('SepaMandate', 'request_update', $params);
    if (is_array($result['values'])) {
      $output->setParameter('result', json_encode($result['values']));
    }
    else {
      $output->setParameter('result', $result['values']);
    }
  }

}
