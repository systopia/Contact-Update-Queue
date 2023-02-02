#!/bin/sh

l10n_tools="../civi_l10n_tools"

# extract all 'regular' ts() string
${l10n_tools}/bin/create-pot-files-extensions.sh contactupdatequeue  ./ l10n

# append strings from the resource files
echo
echo "appending all 'title' values from ./resources/*.json"
fgrep '"title":' resources/*.json | sed -E 's/.*"title" *: *"/\
#: resources\/*.json\
msgid "/' | sed 's/",/"\
msgstr ""/' >> l10n/contactupdatequeue.pot

echo "appending all 'label' values from ./resources/*.json"
fgrep '"label":' resources/* | sed -E 's/.*"label" *: *"/\
#: resources\/*.json\
msgid "/' | sed 's/",/"\
msgstr ""/' >> l10n/contactupdatequeue.pot

echo "cleaning out duplicates..."
msguniq l10n/contactupdatequeue.pot | sponge l10n/contactupdatequeue.pot
