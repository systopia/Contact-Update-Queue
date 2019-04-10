# Configuration

Once installed, the I3Val extension provides a configuration interface
accessible at *Administration* → *System Settings* → *I3Val Configuration* with
the following configuration options:

## Processing Options

This section defines options for processing change requests using the I3Val
batch processing interface.

### Quick History

### Default Action

This option defines the default action to select for a change request. Selecting
"Automatic" will try to choose the most appropriate action for each entry.

### Flagged Request Status

This option defines the activity status to set the change request activity to
when flagging as problematic.

### Session Timeout

This option defines the time until a composed batch of change requests times out
and the contained change requests can be used within new batches.

### Session Batch Size

This option defines how many change requests will be put together in a batch.

## Update Request Options

This section defines options for data stored with change requests when
processing them using the I3Val batch processing interface. Those are generally
common validators for providing more sanity of input data automatically.

### Input Trim Characters

Characters within this option will be trimmed from the input data, if present in
front or at the end of the input values. This is usually be set to space
characters, that may be present when data is coming from public forms, but may
be set to e.g. separators or quotation marks being used in external data
sources.

### Empty String Token

If there are input values that should be ignored, this option is to specify such
values. E.g. if empty data is submitted as "None" or "NULL" and those values are
being set here, the input values will be seen as non-existent and be ignored.

## Data Configuration

### Activity Type

The activity type is the primary parameter for deciding which I3Val
configuration to use for processing change requests.

### Handlers

You may define which data handlers should be used to process change requests per
activity type.
