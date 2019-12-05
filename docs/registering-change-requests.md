# Registering change requests

This is solely done using the API actions provided for existing entities by this
extension:

-   Contact
-   Address
-   Email
-   Phone
-   SepaMandate (for integration with the
    [CiviSEPA extension](https://github.com/project60/org.project60.sepa))

The action's name is `request_update` and requires the entity ID (`id`) as a
parameter to be provided. It also accepts all parameters valid for the
respective entity, i.e. those will be taken into account when processing data
differences. Additionally, the action accepts the following parameters used for
the I3Val activity that is to be created:

-   *Request note* (`i3val_note`)
-   *Schedule date* (`i3val_schedule_date`)
-   *Parent activity ID* (`i3val_parent_id`)

If there are data differences, an activity will be created, documenting them in
a structure that allows parsing by the I3Val Desktop.

!!! info
    Registering change requests through the I3Val API would usually be done by other
    CiviCRM extensions that want to utilize I3Val's functionality for validating
    their data. Most notably, the
    [*Extended Contact Manager* (*XCM*)](https://github.com/systopia/de.systopia.xcm)
    extension provides an option for registering differences of contact data
    when identifying contacts with a set of parameters.
