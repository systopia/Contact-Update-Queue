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

cj(document).ready(function() {
    /**
     * Updates the panel based on the selected action
     */
    function i3val_address_action_changed() {
        let action = cj("#i3val_address_updates_action").val();
        let action_class = action.split(' ')[0];
        let location_type = '';
        switch (action_class) {
            case 'add':
                // CREATE NEW: only use submitted fields, and hide the current values
                location_type = action.split(' ')[1];
                i3val_address_setFields('applied', 'submitted', true);
                i3val_address_showCurrentValues(false);
                break;

            case 'update':
                location_type = action.split(' ')[1];
                i3val_address_setFields('applied', location_type, true);
                i3val_address_setFields('applied', 'submitted', false);
                i3val_address_setFields('current', location_type, true);
                i3val_address_showCurrentValues(true);
                break;

            case 'replace':
                location_type = action.split(' ')[1];
                i3val_address_setFields('applied', 'submitted', true);
                i3val_address_setFields('current', location_type, true);
                i3val_address_showCurrentValues(true);
                break;

            default: // duplicate/discard
                i3val_address_setFields('applied', 'original', true);
                i3val_address_showCurrentValues(false);
                break;

        }
    }

    /**
     * Set the values of the given field group ('applied', 'current', ...) to
     *  the values provided by the source ('submitted' or location type)
     */
    function i3val_address_setFields(field_set, source, clear_missing) {
        for (const field_name of CRM.vars.i3val_address_update.field_names) {
            // get value from source
            let field_value = '';
            let source_data = {};

            // first: get source data
            if (source == 'original' || source == 'submitted') {
                // data is present in vars
                source_data =  CRM.vars.i3val_address_update[source];
            } else {
                // data is one of the current addresses, 'source' is a location type
                let location_type_id = CRM.vars.i3val_address_update.location_types[source];
                if (location_type_id in CRM.vars.i3val_address_update.addresses) {
                    source_data = CRM.vars.i3val_address_update.addresses[location_type_id];
                }
            }

            // read value
            if (field_name in source_data) {
                field_value = source_data[field_name];
            }

            // copy to target
            if (field_value !== '' || clear_missing) {
                if (field_set === 'applied') {
                    cj("[name=address_" + field_name + "_applied").val(field_value);
                } else if (field_set === 'current') {
                    // set value
                    cj(".i3val-address-current-address_" + field_name + " span").text(field_value);

                    // enable copy
                    if (field_value !== '') {
                        cj(".i3val-address-current-address_" + field_name + " .address-value-copy").show();
                        cj(".i3val-address-current-address_" + field_name + " .address-value-copy").attr('value', field_value);
                    } else {
                        cj(".i3val-address-current-address_" + field_name + " .address-value-copy").hide();
                    }
                }
            }
        }
    }

    /**
     * Show (or hide) the current value columns
     */
    function i3val_address_showCurrentValues(show) {
        if (show) {
            cj(".i3val-address-current").show(100);
        } else {
            cj(".i3val-address-current").hide(100);
        }
    }

    // add change listener to action select
    cj("#i3val_address_updates_action").change(i3val_address_action_changed);
    i3val_address_action_changed();
});
