<?php
/*-------------------------------------------------------+
| Contact Update Queue Extension                         |
| Funded by Amnesty International Vlaanderen             |
| Copyright (C) 2018 SYSTOPIA                            |
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

use CRM_Contactupdatequeue_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Contactupdatequeue_Upgrader extends CRM_Contactupdatequeue_Upgrader_Base {

  /**
   * Make sure the new 'formal_title' field is there
   *
   * @see https://github.com/systopia/Contact-Update-Queue/issues/6
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_0501() {
    $this->ctx->log->info('Applying update upgrade_0501');
    // make sure the new 'formal_title' field is there (see Contactupdatequeue-6)
    CRM_Contactupdatequeue_Configuration::synchroniseCustomFields();
    return TRUE;
  }
}
