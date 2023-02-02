/*-------------------------------------------------------+
| Contact Update Queue Extension                         |
| Funded by Amnesty International Vlaanderen             |
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

var contactupdatequeue_activity_id = INJECTED_ACTIVITY_ID;
var contactupdatequeue_activity_type_id = INJECTED_ACTIVITY_TYPE_ID;
var contactupdatequeue_panel = INJECTED_PANEL;
var contactupdatequeue_selector = "td[id^=contactupdatequeue_contact_updates_]";


function contactupdatequeue_tryDoingUpdate() {
  var wrapper = cj(contactupdatequeue_selector);
  if (wrapper.length == 1) {
    contactupdatequeue_renderInteractiveTable(wrapper);
  }
}

/**
 * transform the standard rendering of our custom group
 * a nice table
 */
function contactupdatequeue_renderInteractiveTable(wrapperElement) {
  // check if has already been transformed
  if (wrapperElement.find("table.contactupdatequeue").length) return;

  var body = wrapperElement.find("div.crm-accordion-body");
  if (body.length != 1) return;

  // create table stucture
  body.html(''); // clear out
  body.append(contactupdatequeue_panel['html']);
}


// UPDATE TRIGGERS
cj(document).ready(function() {
  contactupdatequeue_tryDoingUpdate();
});
