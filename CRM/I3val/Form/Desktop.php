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

use CRM_I3val_ExtensionUtil as E;

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

    // make sure there is a configuration
    $activity_types = $configuration->getActivityTypes();
    if (empty($activity_types)) {
      CRM_Core_Session::setStatus(E::ts("Please configure I3Val first."), E::ts('Warning'), 'info');
      CRM_Utils_System::redirect(CRM_Utils_System::url("civicrm/admin/i3val"));
      return;
    }

    // get the subset if requested
    $requested_types_string = CRM_Utils_Request::retrieve('types',  'String');
    $selected_types = array_keys($activity_types);
    if ($requested_types_string) {
      $requested_types = explode(',', $requested_types_string);
      $selected_types = array();
      foreach ($requested_types as $requested_type) {
        $selected_types[] = (int) $requested_type;
      }

      // restrict to the configured ones:
      $selected_types = array_intersect($selected_types, array_keys($activity_types));
      if (empty($selected_types)) {
        CRM_Core_Session::setStatus(E::ts("The types requested are not configured."), E::ts('Warning'), 'info');
        CRM_Utils_System::redirect(CRM_Utils_System::url("civicrm/admin/i3val"));
        return;
      }
    }

    $session = CRM_I3val_Session::getSession();

    // check for reset
    $reset = CRM_Utils_Request::retrieve('reset',  'Integer');
    if ($reset) {
      $sibling_request = CRM_Utils_Request::retrieve('sibling_queue',  'String');
      if ($sibling_request) {
        $session->jumpToSiblingQueue($sibling_request);
      } else {
        $session->reset($selected_types);
      }
    }

    // fetch current activity
    $this->activity_id = $session->getCurrentActivityID($selected_types);
    if ($session->isSiblingQueue()) {
      CRM_Utils_System::setTitle(E::ts("Processing requested update #%1 [Contact Queue]", array(1 => $this->activity_id)));
    } else {
      CRM_Utils_System::setTitle(E::ts("Processing requested update #%1", array(1 => $this->activity_id)));
    }

    // Check if DONE....
    if (!$this->activity_id) {
      // still need to check whether this was a sibling queue
      if ($session->isSiblingQueue()) {
        // CRM_Core_Session::setStatus(E::ts("All updates for that contact have been processed"), E::ts('Done'), 'info');
        CRM_Utils_System::redirect($session->getContinuationURL());
        return;
      } else {
        CRM_Core_Session::setStatus(E::ts("No more update requests pending. You're already done!"), E::ts('All done!'), 'info');
        CRM_Utils_System::redirect(CRM_Utils_System::url("civicrm/dashboard"));
        return;
      }
    }

    // some bookkeeping
    $this->assign('progress',        $session->getProgress());
    $this->assign('processed_count', $session->getProcessedCount());
    $this->assign('pending_count',   $session->getPendingCount());

    // load activity
    $this->activity = civicrm_api3('Activity', 'getsingle', array('id' => $this->activity_id));
    CRM_I3val_CustomData::labelCustomFields($this->activity);

    // load contact
    $contact_id = NULL;
    try {
      $contact_id = civicrm_api3('ActivityContact', 'getvalue', array(
        'return'         => 'contact_id',
        'activity_id'    => $this->activity_id,
        'record_type_id' => 'Activity Targets'));
      $this->contact = civicrm_api3('Contact', 'getsingle', array('id' => $contact_id));
    } catch (Exception $e) {
      // NO CONTACT -> flag and move on.
      CRM_Core_Session::setStatus(E::ts("Activity not connected to a contact (any more)!"), E::ts('Error'), 'error');
      CRM_Core_Session::setStatus(E::ts("Requested update has been flagged as a problem."), E::ts('Flagged!'), 'info');
      $session->flagActivity($this->activity_id);
      $session->markProcessed($this->activity_id);
      $url = CRM_Utils_System::url("civicrm/i3val/desktop");
      CRM_Utils_System::redirect($url);
    }

    $this->add('hidden', 'contact_id', $contact_id);

    $this->renderActivity($this->activity);
    $this->assign('activity', $this->activity);

    // render history
    $history_types = $configuration->getQuickHistoryTypes();
    if (empty($history_types)) {
      // history is disabled
      $this->assign('history', 'NO');
    } else {
      $this->assign('history', $this->getContactHistory($this->activity_id, $history_types));
    }

    // show 'contact queue' message
    if (!$session->isSiblingQueue()) {
      $sibling_activities = CRM_I3val_Session::getSiblingActivityIDs($this->activity_id);
      if (count($sibling_activities) > 1) {
        if (count($sibling_activities) > 2) {
          CRM_Core_Session::setStatus(E::ts("There are %1 other changes scheduled for this contact. Click <strong><a href='%2'>HERE</a></strong> if you want to process those in one batch.",
              array(1 => count($sibling_activities) - 1,
                  2 => $url = CRM_Utils_System::url("civicrm/i3val/desktop", "reset=1&sibling_queue={$this->activity_id}"))),
              E::ts("Related Updates Scheduled!"),
              'warning');
        } else {
          CRM_Core_Session::setStatus(E::ts("There is another change scheduled for this contact. Click <strong><a href='%1'>HERE</a></strong> if you want to process both in one batch.",
              array(1 => $url = CRM_Utils_System::url("civicrm/i3val/desktop", "reset=1&sibling_queue={$this->activity_id}"))),
              E::ts("Related Updates Scheduled!"),
              'warning');
        }
      }
    }

    // render activity form
    try {
      $handlers = $configuration->getHandlersForActivityType($this->activity['activity_type_id']);
      $handler_templates = array();
      foreach ($handlers as $handler) {
        if ($handler->hasData($this->activity)) {
          $handler->renderActivityData($this->activity, $this);
          $handler_templates[] = $handler->getTemplate();
        }
      }
      $this->assign('handler_templates', $handler_templates);
    } catch (Exception $e) {
      // NO CONTACT -> flag and move on.
      CRM_Core_Session::setStatus(E::ts("Activity [%1] is faulty and has been flagged as problematic.", array(1 => $this->activity_id)), E::ts('Error'), 'error');
      $session->flagActivity($this->activity_id);
      $session->markProcessed($this->activity_id);
      $url = CRM_Utils_System::url("civicrm/i3val/desktop");
      CRM_Utils_System::redirect($url);
    }

    // add control structures
    $this->add(
      'select',
      'postpone',
      E::ts('postpone'),
      $configuration->getPostponeOptions()
    );

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Apply'),
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'flag',
        'name' => E::ts('Flag Problem'),
      ),
      array(
        'type' => 'postpone',
        'name' => E::ts('Postpone for:'),
      ),
    ));


    // let's add some style...
    CRM_Core_Resources::singleton()->addStyleFile('be.aivl.i3val', 'css/i3val.css');

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
    $session = CRM_I3val_Session::getSession();
    $configuration = CRM_I3val_Configuration::getConfiguration();
    $timestamp = NULL;

    // check, if activity is still LIVE (See I3Val-14)
    if (!$configuration->isActivityLive($this->activity_id)) {
      $this->command = 'changed';
    }

    switch ($this->command) {
      case 'postpone':
        // Process this later
        $postpone_option = CRM_Utils_Request::retrieve('postpone', 'String');
        $timestamp = $session->postponeActivity($this->activity_id, $postpone_option);
        CRM_Core_Session::setStatus(E::ts("Requested update has been marked to be reviewed again later"), E::ts('Postponed!'), 'info');
        break;

      case 'flag':
        // Mark
        $session->flagActivity($this->activity_id);
        CRM_Core_Session::setStatus(E::ts("Requested update has been flagged as a problem."), E::ts('Flagged!'), 'info');
        break;

      case 'submit':
        // Apply changes
        $timestamp = $this->applyChanges();

        // add to recent items
        CRM_Utils_Recent::add(
            $this->contact['display_name'],
            CRM_Utils_System::url("civicrm/contact/view", "reset=1&cid={$this->contact['id']}"),
            $this->contact['id'],
            $this->contact['contact_type'],
            $this->contact['id'],
            $this->contact['display_name']);
        break;

      case 'changed':
        CRM_Core_Session::setStatus(E::ts("The change request [%1] presented here had already been processed elsewhere.", [1 => $this->activity_id]), E::ts('Skipped'), 'error');
        break;

      default:
        CRM_Core_Session::setStatus(E::ts("Unkown action."), E::ts('Error'), 'error');
        return;
    }

    // mark received
    $session->markProcessed($this->activity_id, $timestamp);


    // go to the next one
    // $next_url = CRM_Utils_System::url("civicrm/i3val/desktop");
    // CRM_Utils_System::redirect($next_url);

    // redirect to schedule reload
    $url = CRM_Utils_System::url("civicrm/i3val/desktop");
    CRM_Utils_System::redirect($url);

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
      'option.limit'   => 1));
    $contact = civicrm_api3('Contact', 'getsingle', array(
      'id'     => $target['contact_id'],
      'return' => 'display_name'));
    $activity['with_name'] = $contact['display_name'];
    $activity['with_id']   = $contact['id'];
    $activity['with_link'] = CRM_Utils_System::url("civicrm/contact/view", "reset=1&cid={$contact['id']}");
    $activity['image'] = CRM_Contact_BAO_Contact_Utils::getImage(empty($this->contact['contact_sub_type']) ? $this->contact['contact_type'] : $this->contact['contact_sub_type'], FALSE, $this->contact['id']);


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
    $values  = $_REQUEST; // TODO: why doesn't $this->exportValues(); work?
    $objects = array('contact' => $this->contact, 'activity' => $this->activity);
    $activity_date_time = $this->activity['activity_date_time'];

    $configuration = CRM_I3val_Configuration::getConfiguration();
    $errors = array();
    $activity_update = array('id' => $this->activity_id);
    $handlers = $configuration->getHandlersForActivityType($this->activity['activity_type_id']);
    foreach ($handlers as $handler) {
      // first: verify changes
      $handler_errors = $handler->verifyChanges($this->activity, $values, $objects);
      if (empty($errors)) {
        // do the update
        $changed_fields = $handler->applyChanges($this->activity, $values, $objects);
        $activity_update = array_merge($activity_update, $changed_fields);
      } else {
        // collect the errors
        $errors = array_merge($errors, $handler_errors);
      }
    }

    if (!empty($errors)) {
      // TODO: Error handling
    }

    // update the acvitiy
    $activity_update['status_id'] = 2; // Completed
    $activity_update['activity_date_time'] = date('YmdHis'); // NOW
    CRM_I3val_CustomData::resolveCustomFields($activity_update);
    CRM_I3val_Session::log("UPDATE ACTIVITY " . json_encode($activity_update));
    civicrm_api3('Activity', 'create', $activity_update);

    return $activity_date_time;
  }

  /**
   * efficiently get the contact history for the contact
   */
  protected function getContactHistory($activity_id, $activity_types) {
    $history = array();

    $activity_type_list = implode(',', $activity_types);
    if (empty($activity_type_list)) {
      return $history;
    }

    $activity_id = (int) $activity_id;
    $sql = "
      SELECT
        activity.activity_date_time   AS date,
        activity.id                   AS activity_id,
        contact_lead.contact_id       AS contact_id,
        activity.subject              AS subject,
        status.label                  AS status,
        activity_type.label           AS type,
        added_by_contact.display_name AS added_by
      FROM civicrm_activity_contact contact_lead
      LEFT JOIN civicrm_activity_contact history      ON contact_lead.contact_id = history.contact_id
                                                        AND history.record_type_id IN (3)
      LEFT JOIN civicrm_activity activity             ON activity.id = history.activity_id

      -- status
      LEFT JOIN civicrm_option_value status           ON activity.status_id = status.value
      LEFT JOIN civicrm_option_group status_og        ON status_og.id = status.option_group_id

      -- type
      LEFT JOIN civicrm_option_value activity_type    ON activity.activity_type_id = activity_type.value
      LEFT JOIN civicrm_option_group activity_type_og ON activity_type_og.id = activity_type.option_group_id

      -- added by
      LEFT JOIN civicrm_activity_contact added_by     ON history.activity_id = added_by.activity_id
                                                        AND added_by.record_type_id = 2
      LEFT JOIN civicrm_contact added_by_contact      ON added_by_contact.id = added_by.contact_id

      WHERE contact_lead.activity_id    = {$activity_id}
        AND contact_lead.record_type_id = 3
        AND activity.activity_date_time > (NOW() - INTERVAL 6 MONTH)
        AND status_og.name              = 'activity_status'
        AND activity_type_og.name       = 'activity_type'
        AND activity_type.value         IN ({$activity_type_list})
      ORDER BY activity.activity_date_time DESC
      LIMIT 25;
    ";
    $query = CRM_Core_DAO::executeQuery($sql);
    while ($query->fetch()) {
      $history[] = array(
        'date'        => $query->date,
        'status'      => $query->status,
        'subject'     => $query->subject,
        'type'        => $query->type,
        'added_by'    => $query->added_by,
        'activity_id' => $query->activity_id,
        'contact_id'  => $query->contact_id,
        'url'         => CRM_Utils_System::url("civicrm/activity", "action=view&reset=1&id={$query->activity_id}"),
        );
    }

    return $history;
  }
}
