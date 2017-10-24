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

  public $command     = NULL;
  public $activity_id = NULL;
  public $contact     = NULL;

  /**
   * build the form.
   *
   * there's two modes:
   *   by acitivity id - just process that one activity
   *   no parameters   - just process all pending ones
   */
  public function buildQuickForm() {
    $configuration = CRM_I3val_Configuration::getConfiguration();
    $session = CRM_I3val_Session::getSession();

    // find out what there is to be done
    $activity_id      = CRM_Utils_Request::retrieve('aid',  'Integer');
    $last_activity_id = CRM_Utils_Request::retrieve('laid', 'Integer');
    $total_count      = CRM_Utils_Request::retrieve('count', 'Integer');
    $index            = CRM_Utils_Request::retrieve('idx', 'Integer');

    // preserve the parameters in hidden fields
    $this->add('hidden', 'aid',  $activity_id);
    $this->add('hidden', 'laid', $last_activity_id);

    if ($activity_id) {
      $activity_id = $session->getNextPendingActivity('single', $activity_id);
    } elseif ($last_activity_id) {
      $activity_id = $session->getNextPendingActivity('next', $last_activity_id);
    } else {
      $activity_id = $session->getNextPendingActivity('first');
    }
    $this->activity_id = $activity_id;

    // Check if DONE....
    if (!$activity_id) {
      CRM_Core_Session::setStatus(ts("No more activities pending."), ts('All done!'), 'info');
      CRM_Utils_System::redirect(CRM_Utils_System::url("civicrm/dashboard"));
      return;
    }

    // some bookkeeping
    $this->assign('progress', $session->getProgress());
    $this->assign('processed_count', $session->getProcessedCount());
    $this->assign('pending_count', $session->getPendingCount());

    if (!$index) {
      $index = 1;
    }
    $this->index = $index;
    $this->add('hidden', 'idx', $index);
    $this->assign('index', $index);

    // load activity
    $this->activity = civicrm_api3('Activity', 'getsingle', array('id' => $activity_id));
    CRM_I3val_CustomData::labelCustomFields($this->activity);

    // load contact
    $contact_id = civicrm_api3('ActivityContact', 'getvalue', array(
      'return'         => 'contact_id',
      'activity_id'    => $this->activity_id,
      'record_type_id' => 'Activity Targets'));
    if (empty($contact_id)) {
      // NO CONTACT
      CRM_Core_Session::setStatus(ts("Activity not connected to a contact!"), ts('Error'), 'error');
      return;
    }
    $this->contact = civicrm_api3('Contact', 'getsingle', array('id' => $contact_id));

    $this->renderActivity($this->activity);
    $this->assign('activity', $this->activity);
    $this->assign('history', $this->getContactHistory($activity_id));

    // render activity form
    $handlers = $configuration->getHandlersForActivityType($this->activity['activity_type_id']);
    $handler_templates = array();
    foreach ($handlers as $handler) {
      $handler->renderActivityData($this->activity, $this);
      $handler_templates[] = $handler->getTemplate();
    }
    $this->assign('handler_templates', $handler_templates);

    // add control structures
    $this->add(
      'select',
      'postpone',
      ts('postpone'),
      $configuration->getPostponeOptions()
    );

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Apply'),
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'flag',
        'name' => ts('Problem'),
      ),
      array(
        'type' => 'postpone',
        'name' => ts('Postpone'),
      ),
    ));

    parent::buildQuickForm();
  }

  /**
   * Redirect all (custom) actions ('command', 'flag', and 'submit')
   * to submit
   */
  public function handle($command) {
    switch ($command) {
      case 'postpone':
      case 'flag':
      case 'submit':
        $this->command = $command;
        $command = 'submit';
        break;

      default:
        break;
    }

    parent::handle($command);
  }

  /**
   * General postprocessing
   */
  public function postProcess() {
    $configuration = CRM_I3val_Configuration::getConfiguration();

    switch ($this->command) {
      case 'postpone':
        // Process this later
        $configuration->postponeActivity($this->activity_id);
        CRM_Core_Session::setStatus(ts("Update request has been marked to be reviewed again later"), ts('Postponed!'), 'info');
        break;

      case 'flag':
        // Mark
        $configuration->flagActivity($this->activity_id);
        CRM_Core_Session::setStatus(ts("Update request has been flagged."), ts('Flagged!'), 'info');
        break;

      case 'submit':
        // Apply changes
        $this->applyChanges();
        break;

      default:
        CRM_Core_Session::setStatus(ts("Unkown action."), ts('Error'), 'error');
        break;
    }

    // go to the next one
    $next_index = $this->index + 1;
    $next_url = CRM_Utils_System::url("civicrm/i3val/desktop", "reset=1&laid={$this->activity_id}&count={$this->total_count}&idx={$next_index}");
    CRM_Utils_System::redirect($next_url);

    // shouldn't get here:
    // parent::postProcess();
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
   * Apply the changes
   */
  protected function applyChanges() {
    // extract changes
    $changes = array();
    $values  = $_REQUEST; // TODO: why doesn't $this->exportValues(); work?
    $objects = array('contact' => $this->contact, 'activity' => $this->activity);

    $configuration = CRM_I3val_Configuration::getConfiguration();
    $handlers = $configuration->getHandlersForActivityType($this->activity['activity_type_id']);
    foreach ($handlers as $handler) {
      $handler_fields = $handler->getFields();
      foreach ($handler_fields as $field_name) {
        if (!empty($values["{$field_name}_apply"])) {
          $changes[$field_name] = $values["{$field_name}_applied"];
        }
      }
    }
    error_log("CHANGES " . json_encode($changes));

    // verify changes
    // TODO: move to validate function
    $errors = array();
    foreach ($handlers as $handler) {
      $errors = array_merge($errors, $handler->verifyChanges($this->activity, $changes, $objects));
    }
    if (!empty($errors)) {
      // TODO: error handling
    }

    // apply changes
    $activity_update = array();
    foreach ($handlers as $handler) {
      $changed_fields = $handler->applyChanges($this->activity, $changes, $objects);
      $activity_update = array_merge($activity_update, $changed_fields);
    }

    // update the acvitiy
    $activity_update['status_id'] = 2; // Completed
    CRM_I3val_CustomData::resolveCustomFields($activity_update);
    error_log("UPDATE ACTIVITY " . json_encode($activity_update));
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
