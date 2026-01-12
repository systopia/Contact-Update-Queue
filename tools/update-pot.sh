#!/bin/bash

set -euo pipefail

readonly SCRIPT_PATH="$0"
SCRIPT_NAME=$(basename "$SCRIPT_PATH")
readonly SCRIPT_NAME
SCRIPT_DIR=$(dirname "$SCRIPT_PATH")
readonly SCRIPT_DIR

usage() {
  cat <<EOD
Usage: $SCRIPT_NAME [-h|--help]
  -h, --help
            Print this help.

Extracts translatable strings using civistrings and updates the .pot file.
EOD
}

if [ $# -eq 1 ]; then
  usage
  if [ $1 = -h ] || [ $1 = --help ]; then
    exit
  fi
  exit 1
elif [ $# -gt 1 ]; then
  usage
  exit 1
fi

cd "$SCRIPT_DIR/.."

[ -d l10n ] || mkdir l10n
civistrings -o "l10n/i3val.pot" - < <(git ls-files)

# append strings from the resource files
echo
echo "appending all 'title' values from ./resources/*.json"
fgrep '"title":' resources/*.json | sed -E 's/.*"title" *: *"/\
#: resources\/*.json\
msgid "/' | sed 's/",/"\
msgstr ""/' >> l10n/i3val.pot

echo "appending all 'label' values from ./resources/*.json"
fgrep '"label":' resources/* | sed -E 's/.*"label" *: *"/\
#: resources\/*.json\
msgid "/' | sed 's/",/"\
msgstr ""/' >> l10n/i3val.pot

echo "cleaning out duplicates..."
msguniq -o l10n/i3val.pot l10n/i3val.pot
