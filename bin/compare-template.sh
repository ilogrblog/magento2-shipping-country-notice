#!/usr/bin/env bash
# Read-only check: does the module's field template still match Magento's own field.html,
# apart from the one word that turns the notice into HTML?
# Usage: bin/compare-template.sh /path/to/magento
set -euo pipefail

root="${1:-}"
if [ -z "$root" ] || [ ! -f "$root/bin/magento" ]; then
    echo "Usage: bin/compare-template.sh /path/to/magento (the folder that contains bin/magento)" >&2
    exit 2
fi
echo "Read-only check: nothing is written to $root."

here="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
module_file="$here/view/frontend/web/template/form/field-html-notice.html"

core_file=""
for candidate in \
    "$root/vendor/magento/module-ui/view/frontend/web/templates/form/field.html" \
    "$root/app/code/Magento/Ui/view/frontend/web/templates/form/field.html"; do
    if [ -f "$candidate" ]; then core_file="$candidate"; break; fi
done
if [ -z "$core_file" ]; then
    echo "Could not find Magento_Ui's form/field.html under $root" >&2
    exit 2
fi

expected="$(mktemp)"
trap 'rm -f "$expected"' EXIT
sed 's/data-bind="text: element\.notice"/data-bind="html: element.notice"/' "$core_file" > "$expected"

if diff -u "$expected" "$module_file"; then
    echo "OK: the module template equals Magento's field.html with only the notice binding changed from text to html."
else
    echo "DIFFERENT: the module template no longer matches the field.html of this Magento version (see the diff above)." >&2
    echo "Copy the core file again and change only 'text: element.notice' to 'html: element.notice'." >&2
    exit 1
fi
