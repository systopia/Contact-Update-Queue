<?php
/*-------------------------------------------------------+
| Ilja's Input Validation Extension                      |
| Amnesty International Vlaanderen                       |
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

use CRM_I3val_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_I3val_Upgrader extends CRM_Extension_Upgrader_Base {

  /**
   * Make sure the new 'formal_title' field is there
   *
   * @see https://github.com/systopia/be.aivl.i3val/issues/6
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_0501() {
    $this->ctx->log->info('Applying update upgrade_0501');
    // make sure the new 'formal_title' field is there (see I3VAL-6)
    CRM_I3val_Configuration::synchroniseCustomFields();
    return TRUE;
  }
}
