# Magento 2 shipping country notice: a checkout notice with a working link

A small Magento 2 module that shows a notice under the Country field of the checkout shipping address form, with a real link in it. It accompanies the [iLogr](https://ilogr.com/) guide **[Add HTML Tags in the Notice Section of Checkout Form Fields in Magento 2.4](https://ilogr.com/add-html-tags-in-notice-section-of-checkout-form-fields-magento2/)**, which explains why the stock checkout prints a notice as plain text and why this module gives that one field a template of its own.

With the module enabled, the shipping form shows, under Country:

> Should you wish for your purchase to be shipped internationally, please [contact us](https://example.test/contact/).

> **Before you use it:** this is example code, provided as is, without warranty. It changes what your store shows at checkout. Try it on a development machine or a staging copy first, never directly on a live store, site or database, and take a backup of the files and the database before you install it. You use it entirely at your own risk. iLogr cannot see your store and nobody who wrote or published this code accepts responsibility for damage, data loss or downtime.

## Requirements

- Magento Open Source or Adobe Commerce **2.4.9 or later**, with the default UI-component checkout (the Luma checkout was tested).
- PHP 8.3, 8.4 or 8.5, which is what Magento 2.4.9 itself requires.

2.4.9 is the minimum because it is the only version this module has been run on, and the Composer constraints match its packages. The code itself uses only long-standing Magento APIs, so an earlier 2.4 release may work, but that has not been tested and the constraints would need loosening.

## Install

Pick one way. Do it on a development or staging copy first.

**Copy into `app/code`:**

```bash
cd /path/to/magento
mkdir -p app/code/ILogr
git clone https://github.com/ilogrblog/magento2-shipping-country-notice.git app/code/ILogr/ShippingCountryNotice
rm -rf app/code/ILogr/ShippingCountryNotice/.git
bin/magento module:enable ILogr_ShippingCountryNotice
bin/magento setup:upgrade
bin/magento cache:flush
rm -rf pub/static/frontend var/view_preprocessed
```

**Or with Composer:**

```bash
cd /path/to/magento
composer config repositories.ilogr-shipping-country-notice vcs https://github.com/ilogrblog/magento2-shipping-country-notice
composer require ilogr/module-shipping-country-notice:dev-main
bin/magento module:enable ILogr_ShippingCountryNotice
bin/magento setup:upgrade
bin/magento cache:flush
rm -rf pub/static/frontend var/view_preprocessed
```

On a store in production mode, run `bin/magento setup:di:compile` and `bin/magento setup:static-content:deploy` as you normally do. The module has no database tables and no admin settings.

To remove it: `bin/magento module:disable ILogr_ShippingCountryNotice`, then delete `app/code/ILogr/ShippingCountryNotice` (or run `composer remove ilogr/module-shipping-country-notice`) and flush the cache.

## Check that it works

Add any product to the cart as a guest and open the checkout. Under Country there must be a notice with a link on "contact us", the other fields must look as they did before, and the browser console must show no errors.

| Check | Expected |
| --- | --- |
| Notice under Country | The sentence above, with "contact us" as a link to your contact page |
| Link markup | One `<a>` inside the notice, with an `href` that ends in `/contact/` |
| Other fields (for example Telephone) | No notice, unchanged |
| Country select | Still has an `aria-describedby` that points at the notice |

## After a Magento upgrade

The module template is a copy of Magento's own `field.html` with one word changed, so compare it with the core file after every upgrade. This script only reads files:

```bash
bin/compare-template.sh /path/to/magento
```

It prints `OK` when the module template equals the core file with only `text: element.notice` changed to `html: element.notice`, and prints the difference and exits with status 1 otherwise. If it differs, copy the core file again and change only that one binding.

## Is it safe to render HTML in a notice?

Only if the string is yours. The `html` binding writes what it receives into the page, so never copy a request parameter, a customer field or unreviewed admin text into the notice. The module builds the link from an escaped URL and escaped link text, so nothing in it depends on raw input. Only the Country field uses the HTML template; every other field keeps Magento's own template and its escaping.

## Run the tests

The unit tests use the PHPUnit and the unit-test setup that come with a Magento development install. Install the module in that Magento first, then:

```bash
bin/run-unit-tests.sh /path/to/magento
```

They check that the Country field gets the notice and the template, that other fields are not touched, that a hostile URL is escaped, that a checkout without the Country field is left exactly as it was, and that the template file exists, uses the `html` binding and has balanced comments.

## What is in this repository

| Path | What it is |
| --- | --- |
| `registration.php`, `etc/module.xml` | Registers the module `ILogr_ShippingCountryNotice` after `Magento_Checkout`. |
| `etc/di.xml` | Attaches the plugin to Magento's checkout layout processor. |
| `Model/Checkout/LayoutProcessorPlugin.php` | Sets the notice and the template on the shipping Country field. |
| `view/frontend/web/template/form/field-html-notice.html` | Magento's field template with the notice shown as HTML. |
| `Test/Unit/` | Unit tests. |
| `bin/compare-template.sh` | Read-only check of the template against the installed core file. |
| `bin/run-unit-tests.sh` | Runs the unit tests in a Magento development install. |
| `composer.json` | Composer package `ilogr/module-shipping-country-notice`. |

## Tested

Magento Open Source 2.4.9 (framework 103.0.9, Magento_Ui 101.2.9, Magento_Checkout 100.4.9) with PHP 8.4, MariaDB 11.4 and the Luma theme. Checked as a guest in a headless Chrome. PHP_CodeSniffer with the Magento 2 coding standard reports no errors or warnings on the PHP files, and PHPStan at level 5 reports no errors. This module changes the shipping address form only, and only checkouts built from Magento's UI components.

## Licence

MIT, see `LICENSE`. The one exception is `view/frontend/web/template/form/field-html-notice.html`: it is a copy of Magento's `field.html` with one word changed, and it keeps Magento's own notice at the top. Magento publishes that file under the Open Software License 3.0 and the Academic Free License 3.0.

More code that accompanies iLogr guides: [github.com/ilogrblog](https://github.com/ilogrblog).
