=== Paid Memberships Pro - Auto-Renewal Checkbox ===
Contributors: strangerstudios
Tags: pmpro, paid memberships pro, members, memberships, auto-renewal, renewal, checkbox
Requires at least: 5.4
Tested up to: 6.6
Stable tag: 0.3.3
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Make auto renewal optional at checkout with a checkbox.

== Description ==

Adds an option to PMPro membership levels: Auto Renewal Optional. If set and a recurring billing amount is present, a checkbox is added to the checkout page to optional enable auto renewal.

This does not work with PMPro discount codes yet. If a customer uses a discount code, the auto renewal checkbox will be ignored and the default recurring billing settings from the discount code will be used.

== Installation ==

1. Upload the `pmpro-auto-renewal-checkbox` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Edit your membership levels and set the "Auto Renewal" options for each level.

== Changelog ==
= 0.3.3 - 2024-09-24
* ENHANCEMENT: Updated UI for compatibility with PMPro v3.1. #35 (@MaximilianoRicoTabo, @kimcoleman)
* BUG FIX: Added compatibility for prefixed discount code request var. #36 (@dparker1005)

= 0.3.2 - 2024-02-23 =
* ENHANCEMENT: Added compatibility with Multiple Memberships Per User for the PMPro v3.0 update. #33 (@dparker1005)

= 0.3.1 - 2023-10-12 =
* ENHANCEMENT: Added German translation files. #31 (@michaelbeil)
* ENHANCEMENT: Updating `<h3>` tags to `<h2>` tags for better accessibility. #30 (@michaelbeil)
* BUG FIX/ENHANCEMENT: Updating the local Cancel on Next Payment Date code to match the code in the corresponding Add On. #23 (@dparker1005)
* BUG FIX/ENHANCEMENT: Marking plugin as incompatible with Multiple Memberships Per User for the PMPro v3.0 update. #27 (@dparker1005)

= 0.3.0 - 2021-09-08 =
* BUG FIX/ENHANCEMENT: Updated the "cancel on next payment date" logic to work how the latest version of the CONPD plugin works. Will also use the CONPD plugin instead of the included code, if the CONPD add on is active. The CONPD add on will be merged into PMPro core sometime in the future.
* BUG FIX: Fixed some localization issues.
* BUG FIX: When a discount code is used, this plugin will NOT try to adjust the level at checkout to be recurring or not. Instead, it uses the settings from the discount code. We fixed an issue where the plugin sometimes DID try to adjust the level in these cases.

= 0.2.9 =
* ENHANCEMENT: Prepared for localization.
* BUG FIX/ENHANCEMENT: If you are also using the Set Expiration Dates add on, we will no longer set the expiration date if you had the recurring checkbox checked.
* BUG FIX/ENHANCEMENT: Now settings session variables for 2Checkout gateway.
* BUG FIX: No longer trying to give a user their level back if they are being deleted.

= .2.8 =
* BUG FIX: Fixed issues with cancellation.

= .2.7 =
* BUG FIX: Replacing Cancel page text when membership is extended instead of cancelled.
* BUG FIX: Appending expiration date to cancellation email when membership is extended instead of cancelled.
* BUG FIX: Fixed bug when setting subscription start date with PayPal Express.

= .2.6 =
* BUG FIX: When using Stripe, checking if the customer is delinquent before set the expiration date to the "current_period_end" value.
* BUG FIX: Fixed warning at checkout that sometimes conflicted with checkout via gateways like PayPal.

= .2.5 =
* BUG FIX/ENHANCEMENT: Fixed issue where checkbox was sometimes showing up right of the label instead of to the left of it.
* BUG FIX/ENHANCEMENT: Fixed issue where the auto-renewal logic was sometimes being checked when a discount code was used (auto-renewal should be ignored if a discount code is used).
* ENHANCEMENT: Running the pmpro_checkout_level filter on before figuring out the renewal price on the checkout page.

= .2.4 =
* ENHANCEMENT: Improved fields display on membership checkout page to use no tables for compatibility with Paid Memberships Pro v1.9.4.

= .2.3 =
* BUG FIX: Fixed issue where autorenew value was not being used when checking out via PayPal Express or another offsite gateway.

= .2.2 =
* BUG/ENHANCEMENT: Fixed the plugin URI
* ENHANCEMENT: Added meta links to the plugins page for docs and support.

= .2.1 =
* BUG: Fixed code that removed/added filters to prevent loops in the pmproarc_pmpro_after_change_membership_level function.

= .2 =
* ENHANCEMENT: Added code so when users cancel a membership with a recurring subscription, they retain their membership until their next payment date.
* ENHANCEMENT: Added code to handle cases where users are checking out and changing from recurring to non-recurring or vice versa. When changing from non-recurring to recurring, the subscription will be delayed until the user's old expiration date. When changing from recurring to non-recurring, the user's remaining days until their next payment will be added to their expiration date. (Calculations may be off for gateways other than Stripe and PayPal Express).

= .1 =
* First version.
