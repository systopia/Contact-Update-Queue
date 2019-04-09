# Configuration

Once installed, the I3Val extension provides a configuration interface
accessible at *Administration* → *System Settings* → *I3Val Configuration* with
the following configuration options:

## Processing Options

This sections defines options for processing change requests using the I3Val
batch processing interface.

### Quick History

### Default Action

### Flagged Request Status

### Session Timeout

### Session Batch Size

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

### Handlers
