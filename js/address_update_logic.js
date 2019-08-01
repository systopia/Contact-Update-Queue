/*-------------------------------------------------------+
| Ilja's Input Validation Extension                      |
| Amnesty International Vlaanderen                       |
| Copyright (C) 2019 SYSTOPIA                            |
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
 * These is the list of all current addresses
 */
let i3val_address_all = CRM.vars.i3val_address_update.addresses;

cj(document).ready(function() {
    /**
     * Updates the panel based on the selected action
     */
    function i3val_address_action_changed() {
        let action = cj("#i3val_address_updates_action").val();
        let action_class = action.split(' ')[0];
        console.log(action_class);
    }

    // add change listener to action select
    cj("#i3val_address_updates_action").change(i3val_address_action_changed);
    i3val_address_action_changed();
});
