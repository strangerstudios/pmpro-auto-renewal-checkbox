=== Paid Memberships Pro: Auto-Renewal Checkbox ===
Contributors: strangerstudios
Tags: pmpro, paid memberships pro, members, memberships, auto-renewal, renewal, checkbox
Requires at least: 4
Tested up to: 4.7.2
Stable tag: .2.2

Make auto renewal optional at checkout with a checkbox.

== Description ==

Adds an option to PMPro membership levels: Auto Renewal Optional. If set and a recurring billing amount is present, a checkbox is added to the checkout page to optional enable auto renewal.

This does not work with PMPro discount codes yet. If a customer uses a discount code, the auto renewal checkbox will be ignored and the default recurring billing settings from the discount code will be used.

== Installation ==

1. Upload the `pmpro-auto-renewal-checkbox` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Edit your membership levels and set the "Auto Renewal" options for each level.

== Changelog ==
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