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
 * Desktop form controller
 *
 * The desktop lets you iterate through the pending items
 */
class CRM_I3val_Form_Desktop extends CRM_Core_Form {

  /**
   * build the form.
   *
   * there's two modes:
   *   by acitivity id - just process that one activity
   *   no parameters   - just process all pending ones
   */
  public function buildQuickForm() {
    $configuration = CRM_I3val_Configuration::getConfiguration();

    // find out what there is to be done
    $activity_id      = CRM_Utils_Request::retrieve('aid', 'Integer');
    $last_activity_id = CRM_Utils_Request::retrieve('laid', 'Integer');

    if ($activity_id) {
      $activity_id = $configuration->getNextPendingActivity('single', $activity_id);
    } elseif ($last_activity_id) {
      $activity_id = $configuration->getNextPendingActivity('next', $last_activity_id);
    } else {
      $activity_id = $configuration->getNextPendingActivity('first');
    }

    if (!$activity_id) {
      // TODO: NOTHING FOUND!
      return;
    }

    // load activity
    $activity = civicrm_api3('Activity', 'getsingle', array('id' => $activity_id));
    $this->renderActivity($activity);
    $this->assign('activity', $activity);
    $this->assign('history', $this->getContactHistory($activity_id));
    $this->assign('total_count', $configuration->getPendingActivityCount());

    // render activity form
    $handler = $configuration->getHandlerForActivityType($activity['activity_type_id']);
    $handler->renderActivityData($activity, $this);
    $this->assign('handler_template', $handler->getTemplate());

    // add control structures
    $this->add(
      'select',
      'postpone',
      ts('postpone'),
      $configuration->getPostponeOptions()
    );

    $this->addButtons(array(
      array(
        'type' => 'postpone',
        'name' => ts('Postpone'),
      ),
      array(
        'type' => 'fail',
        'name' => ts('Problem'),
      ),
      array(
        'type' => 'apply',
        'name' => ts('Apply'),
        'isDefault' => TRUE,
      ),
    ));

    parent::buildQuickForm();
  }


  public function postProcess() {
    $values = $this->exportValues();
    // TODO
    parent::postProcess();
  }


  /**
   * fill/look up some paramters of the activity
   */
  protected function renderActivity(&$activity) {
    // load the with contact
    $target = civicrm_api3('ActivityContact', 'getsingle', array(
      'sequential'     => 1,
      'activity_id'    => $activity['id'],
      'record_type_id' => 3,
      'options.limit'  => 1));
    $contact = civicrm_api3('Contact', 'getsingle', array(
      'id'     => $target['contact_id'],
      'return' => 'display_name'));
    $activity['with_name'] = $contact['display_name'];
    $activity['with_id']   = $contact['id'];
    $activity['with_link'] = CRM_Utils_System::url("civicrm/contact/view", "reset=1&cid={$contact['id']}");

    // load the status
    $activity['status'] = civicrm_api3('OptionValue', 'getvalue', array(
      'return'          => "label",
      'value'           => $activity['status_id'],
      'option_group_id' => "activity_status"));

    // load campaign
    if (!empty($activity['campaign_id'])) {
      $activity['status'] = civicrm_api3('OptionValue', 'getvalue', array(
        'return' => "title",
        'id'     => $activity['campaign_id']));
    }
  }

  /**
   * efficiently get the contact history for the contact
   */
  protected function getContactHistory($activity_id) {
    $activity_id = (int) $activity_id;
    $sql = "
      SELECT
        activity.activity_date_time   AS date,
        activity.subject              AS subject,
        status.label                  AS status,
        activity_type.label           AS type,
        added_by_contact.display_name AS added_by
      FROM civicrm_activity_contact contact_lead
      LEFT JOIN civicrm_activity_contact history ON contact_lead.contact_id = history.contact_id
                                                 AND history.record_type_id IN (3)
      LEFT JOIN civicrm_activity activity ON activity.id = history.activity_id

      -- status
      LEFT JOIN civicrm_option_value status ON activity.status_id = status.value
      LEFT JOIN civicrm_option_group status_og ON status_og.id = status.option_group_id

      -- type
      LEFT JOIN civicrm_option_value activity_type    ON activity.activity_type_id = activity_type.value
      LEFT JOIN civicrm_option_group activity_type_og ON activity_type_og.id = activity_type.option_group_id

      -- added by
      LEFT JOIN civicrm_activity_contact added_by ON history.activity_id = added_by.activity_id
                                                  AND added_by.record_type_id = 2
      LEFT JOIN civicrm_contact added_by_contact  ON added_by_contact.id = added_by.contact_id

      WHERE contact_lead.activity_id = {$activity_id}
        AND contact_lead.record_type_id = 3
        AND activity.activity_date_time > (NOW() - INTERVAL 6 MONTH)
        AND status_og.name = 'activity_status'
        AND activity_type_og.name = 'activity_type'
      ORDER BY activity.activity_date_time DESC
      LIMIT 25;
    ";
    $query = CRM_Core_DAO::executeQuery($sql);
    $history = array();
    while ($query->fetch()) {
      $history[] = array(
        'date'     => $query->date,
        'status'   => $query->status,
        'subject'  => $query->subject,
        'type'     => $query->type,
        'added_by' => $query->added_by,
        );
    }

    return $history;
  }
}
