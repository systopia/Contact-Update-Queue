/*-------------------------------------------------------+
| Contact Update Queue Extension                         |
| Funded by Amnesty International Vlaanderen             |
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
    function contactupdatequeue_address_action_changed() {
        let action = cj("#contactupdatequeue_address_updates_action").val();
        let action_class = action.split(' ')[0];
        let location_type = '';
        switch (action_class) {
            case 'add':
                // CREATE NEW: only use submitted fields, and hide the current values
                location_type = action.split(' ')[1];
                contactupdatequeue_address_setFields('applied', 'submitted', true);
                contactupdatequeue_address_showCurrentValues(false);
                break;

            case 'update':
                location_type = action.split(' ')[1];
                contactupdatequeue_address_setFields('applied', location_type, true);
                contactupdatequeue_address_setFields('applied', 'submitted', false);
                contactupdatequeue_address_setFields('current', location_type, true);
                contactupdatequeue_address_showCurrentValues(true);
                break;

            case 'replace':
                location_type = action.split(' ')[1];
                contactupdatequeue_address_setFields('applied', 'submitted', true);
                contactupdatequeue_address_setFields('current', location_type, true);
                contactupdatequeue_address_showCurrentValues(true);
                break;

            default: // duplicate/discard
                contactupdatequeue_address_setFields('applied', 'original', true);
                contactupdatequeue_address_showCurrentValues(false);
                break;

        }
    }

    /**
     * Set the values of the given field group ('applied', 'current', ...) to
     *  the values provided by the source ('submitted' or location type)
     */
    function contactupdatequeue_address_setFields(field_set, source, clear_missing) {
        for (const field_name of CRM.vars.contactupdatequeue_address_update.field_names) {
            // get value from source
            let field_value = '';
            let source_data = {};

            // first: get source data
            if (source == 'original' || source == 'submitted') {
                // data is present in vars
                source_data =  CRM.vars.contactupdatequeue_address_update[source];
            } else {
                // data is one of the current addresses, 'source' is a location type
                let location_type_id = CRM.vars.contactupdatequeue_address_update.location_types[source];
                if (location_type_id in CRM.vars.contactupdatequeue_address_update.addresses) {
                    source_data = CRM.vars.contactupdatequeue_address_update.addresses[location_type_id];
                }
            }

            // read value
            if (field_name in source_data) {
                field_value = source_data[field_name];
            }

            // copy to target
            if (field_value !== '' || clear_missing) {
                // first: take care of drop-down defaults
                if (field_name == 'country' && field_value == '') {
                    field_value = CRM.vars.contactupdatequeue_address_update.default_country;
                }

                if (field_set === 'applied') {
                    cj("[name=address_" + field_name + "_applied").val(field_value).change();

                } else if (field_set === 'current') {
                    // set value
                    if (field_name == 'is_primary') {
                        let yes_no = CRM.vars.contactupdatequeue_address_update.yes_no[field_value];
                        cj(".contactupdatequeue-address-current-address_" + field_name + " span").text(yes_no);
                    } else {
                        cj(".contactupdatequeue-address-current-address_" + field_name + " span").text(field_value);
                    }

                    // enable copy
                    if (field_value !== '') {
                        cj(".contactupdatequeue-address-current-address_" + field_name + " .address-value-copy").show();
                        cj(".contactupdatequeue-address-current-address_" + field_name + " .address-value-copy").attr('value', field_value);
                    } else {
                        cj(".contactupdatequeue-address-current-address_" + field_name + " .address-value-copy").hide();
                    }
                }
            }
        }
    }

    /**
     * Show (or hide) the current value columns
     */
    function contactupdatequeue_address_showCurrentValues(show) {
        if (show) {
            cj(".contactupdatequeue-address-current").show(100);
        } else {
            cj(".contactupdatequeue-address-current").hide(100);
        }
    }

    // add change listener to action select
    cj("#contactupdatequeue_address_updates_action").change(contactupdatequeue_address_action_changed);
  contactupdatequeue_address_action_changed();
});
