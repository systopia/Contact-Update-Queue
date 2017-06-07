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

var i3val_activity_id = INJECTED_ACTIVITY_ID;
var i3val_activity_type_id = INJECTED_ACTIVITY_TYPE_ID;
var i3val_panel = INJECTED_PANEL;
var i3val_selector = "td[id^=fwtm_contact_updates_]";


function i3val_tryDoingUpdate() {
  var wrapper = cj(i3val_selector);
  if (wrapper.length == 1) {
    i3val_renderInteractiveTable(wrapper);
  }
}

/**
 * transform the standard rendering of our custom group
 * a nice table
 */
function i3val_renderInteractiveTable(wrapperElement) {
  // check if has already been transformed
  if (wrapperElement.find("table.i3val").length) return;

  var body = wrapperElement.find("div.crm-accordion-body");
  if (body.length != 1) return;

  // create table stucture
  body.html(''); // clear out
  body.append(i3val_panel['html']);
}


// UPDATE TRIGGERS
cj(document).ready(function() {
  i3val_tryDoingUpdate();
});
