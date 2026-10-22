#!/usr/bin/env bash
# Runs the module's unit tests with the PHPUnit that ships with a Magento development install.
# The module must already be installed in that Magento (app/code or Composer) so that its classes autoload.
# Read-only apart from PHPUnit's own temporary files. Usage: bin/run-unit-tests.sh /path/to/magento
set -euo pipefail

root="${1:-}"
if [ -z "$root" ] || [ ! -f "$root/bin/magento" ]; then
    echo "Usage: bin/run-unit-tests.sh /path/to/magento (the folder that contains bin/magento)" >&2
    exit 2
fi
if [ ! -x "$root/vendor/bin/phpunit" ] || [ ! -f "$root/dev/tests/unit/phpunit.xml.dist" ]; then
    echo "This Magento has no PHPUnit or dev/tests/unit. Use a development install (composer install without --no-dev)." >&2
    exit 2
fi
if ! php -r 'require $argv[1] . "/vendor/autoload.php"; exit(class_exists("ILogr\\ShippingCountryNotice\\Model\\Checkout\\LayoutProcessorPlugin") ? 0 : 1);' "$root"; then
    echo "The module classes are not autoloadable from $root. Install the module there first (see README)." >&2
    exit 2
fi
echo "Running the unit tests against $root (read-only)."

here="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$root/dev/tests/unit"
exec ../../../vendor/bin/phpunit -c phpunit.xml.dist --no-coverage "$here/Test/Unit"
