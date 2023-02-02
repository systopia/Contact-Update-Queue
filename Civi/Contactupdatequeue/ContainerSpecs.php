<?php
/*-------------------------------------------------------+
| Contact Update Queue Extension                         |
| Funded by Amnesty International Vlaanderen             |
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

namespace Civi\Contactupdatequeue;

use CRM_Contactupdatequeue_ExtensionUtil as E;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ContainerSpecs implements CompilerPassInterface {

  /**
   * Register this one action: RequestContactUpdate
   */
  public function process(ContainerBuilder $container) {
    if (!$container->hasDefinition('action_provider')) {
      return;
    }
    $typeFactoryDefinition = $container->getDefinition('action_provider');
    $typeFactoryDefinition->addMethodCall('addAction', ['RequestContactUpdate', 'Civi\Contactupdatequeue\ActionProvider\Action\RequestContactUpdate', E::ts('Request Contact Update'), [
        \Civi\ActionProvider\Action\AbstractAction::SINGLE_CONTACT_ACTION_TAG,
        \Civi\ActionProvider\Action\AbstractAction::DATA_MANIPULATION_TAG
    ]]);
    $typeFactoryDefinition->addMethodCall('addAction', ['RequestMandateUpdate', 'Civi\Contactupdatequeue\ActionProvider\Action\RequestMandateUpdate', E::ts('Request CiviSEPA Mandate Update'), [
        \Civi\ActionProvider\Action\AbstractAction::SINGLE_CONTACT_ACTION_TAG,
        \Civi\ActionProvider\Action\AbstractAction::DATA_MANIPULATION_TAG
    ]]);
  }
}
