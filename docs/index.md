# I3Val Input Validation

## Preface

This extension was created with the working title "Ilja's Initiative for Input
Validation", and was initially sponsored by *Amnesty International Vlaanderen*,
thus the unusual original name. When it was adopted by Vluchtelingenwerk Vlaanderen too the name was changed to Contact Update Queue.

Its purpose is to provide convenient tools for validating data input from
external sources into CiviCRM, such as public event registration or contribution
forms or APIs from third-party systems, e.g. external payment providers
synchronising their data using web hooks.

Usually, feeding data into CiviCRM in an unsupervised manner requires either
full confidence in the data source, so that data can be safely updated or
created without validation, or data changes to be rejected. Since the CiviCRM
API does not provide a convenient way to document such rejections for later
manual review, this extension tries to act as a mediator between the API and the
user by providing functionality for processing rejected data changes in a batch
processing interface (the I3Val Desktop).

## Features

The I3Val extension provides a user interface for manually processing data
changes documented by its API, allowing the user to step through them one by one
and providing options for approving or finally rejecting the requested changes
or manually altering the input before saving to the CiviCRM database and closing
the data change request.

You can have as many different change request configurations as you want, by
attaching any amount of handlers to an activity type. You can then also decide
to process only a subset by using a link like this:
```
https://{mydomain}/civicrm/i3val/desktop?reset=1&restart=1&types=123,124
```
The parameter ``types`` here refers to the configurations, i.e. the activity type IDs.

## Technical background

The basic workflow for using this extension is to request updates to CiviCRM
Core entities *Contact*, *Address*, *Phone*, *Email*, *SepaMandate* or others (to be implemented) using the
appropriate API action. This should happen after some logic has already
processed the data that can be safely updated, because the I3Val extension only
documents differing changes as activities, that can be processed with a user
interface later on. Information about the requested changes are being put into
custom fields on the activities.

!!! info
    An example for processing data input is the
    [*Extended Contact Manager* (*XCM*)](https://github.com/systopia/de.systopia.xcm)
    extension, which provides rules for adding, overwriting, or rejecting contact
    data, and provides an option to document differences with the I3Val extension.
