<?php
/**
 * Plugin Name: Paid Memberships Pro - Auto-Renewal Checkbox
 * Plugin URI: https://www.paidmembershipspro.com/add-ons/auto-renewal-checkbox-membership-checkout/
 * Description: Make auto-renewal optional at checkout with a checkbox.
 * Version: 0.3.3
 * Author: Paid Memberships Pro
 * Author URI: https://www.paidmembershipspro.com
 * Text Domain: pmpro-auto-renewal-checkbox
 * Domain Path: /languages
 * License: GPL-3.0
 */

/*
	Settings, Globals and Constants
*/
define("PMPRO_AUTO_RENEWAL_CHECKBOX_DIR", dirname(__FILE__));

/*
	Load plugin textdomain.
*/
function pmproarc_load_textdomain() {
  load_plugin_textdomain( 'pmpro-auto-renewal-checkbox', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'pmproarc_load_textdomain' );

/**
 * Load Cancel On Next Payment Date functionality if
 * PMPro CONPD is not installed and using a PMPro version below 3.0.
 *
 * This file should be removed soon after CONPD is merged into core.
 */
function pmproarc_load_cancel_on_next_payment_date() {
	global $pagenow;

	// Make sure that we are not on the admin plugins.php page.
	// If we are, we don't want to load our version of CONPD
	// in case the real plugin is being activated.
	if ( is_admin() && $pagenow === 'plugins.php' ) {
		return;
	}

	if ( ! function_exists( 'pmproconpd_pmpro_change_level' ) && ! class_exists( 'PMPro_Subscription' ) ) {
		require_once( PMPRO_AUTO_RENEWAL_CHECKBOX_DIR . '/includes/cancel-on-next-payment-date.php' );
	}
}
add_action( 'init', 'pmproarc_load_cancel_on_next_payment_date' );

/*
	Add settings to the edit levels page
*/
//show the checkbox on the edit level page
function pmproarc_pmpro_membership_level_after_other_settings() {
	$level_id = intval($_REQUEST['edit']);
	$options = pmproarc_getOptions($level_id);
?>
<div id="arc_setting_div">
	<h3 class="topborder"><?php esc_html_e('Auto-Renewal Settings', 'pmpro-auto-renewal-checkbox');?></h3>
	<p><?php esc_html_e( 'Change this setting to make auto-renewals optional at checkout.', 'pmpro-auto-renewal-checkbox' );?></p>
	<table>
	<tbody class="form-table">
		<tr>
			<th scope="row" valign="top"><label for="arc_setting"><?php esc_html_e('Auto-Renewal Optional?', 'pmpro-auto-renewal-checkbox');?></label></th>
			<td>
				<select id="arc_setting" name="arc_setting">
					<option value="0" <?php selected($options['setting'], 0);?>><?php esc_html_e('No. All checkouts will set up recurring billing.', 'pmpro-auto-renewal-checkbox');?></option>
					<option value="1" <?php selected($options['setting'], 1);?>><?php esc_html_e('Yes. Default to unchecked.', 'pmpro-auto-renewal-checkbox');?></option>
					<option value="2" <?php selected($options['setting'], 2);?>><?php esc_html_e('Yes. Default to checked.', 'pmpro-auto-renewal-checkbox');?></option>
				</select>
			</td>
		</tr>
		<script>
			function toggleARCOptions() {
				if(jQuery('#recurring').is(':checked')) {
					jQuery('#arc_setting_div').show();
				} else {
					jQuery('#arc_setting_div').hide();
				}
			}

			jQuery(document).ready(function() {
				//hide/show recurring fields on page load
				toggleARCOptions();

				//hide/show fields when recurring settings change
				jQuery('#recurring').change(function() { toggleARCOptions() });
			});
		</script>
	</tbody>
	</table>
</div>
<?php
}
add_action('pmpro_membership_level_after_other_settings', 'pmproarc_pmpro_membership_level_after_other_settings');

//save auto-renewal settings when the level is saved/added
function pmproarc_pmpro_save_membership_level($level_id) {
	//get values
	if(isset($_REQUEST['arc_setting']))
		$arc_setting = intval($_REQUEST['arc_setting']);
	else
		$arc_setting = 0;

	//build array
	$options = array(
		'setting' => $arc_setting,
	);

	//save
	update_option('pmpro_auto_renewal_checkbox_options_' . intval($level_id), $options, "", "no");
}
add_action("pmpro_save_membership_level", "pmproarc_pmpro_save_membership_level");

/*
	Helper function to get options.
*/
function pmproarc_getOptions($level_id) {
	if($level_id > 0) {
		//option for level, check the DB
		$options = get_option('pmpro_auto_renewal_checkbox_options_' . $level_id, array('setting'=>0));
	} else {
		//default for new level
		$options = array('setting'=>0);
	}

	return $options;
}

/*
	Show the box at checkout and update the level.
*/
//draw the box
function pmproarc_pmpro_checkout_boxes() {
	global $pmpro_level, $pmpro_review, $discount_code;

	//only for certain levels
	$options = pmproarc_getOptions($pmpro_level->id);

	if(empty($options) || empty($options['setting']))
		return;

	//maybe the level doesn't have a recurring billing amount
	$olevel = pmpro_getLevel($pmpro_level->id);
	if(!pmpro_isLevelRecurring($olevel))
		return;

	//not if this is an addon package
	if(!empty($_REQUEST['ap']) || !empty($_SESSION['ap']))
		return;

	//not if using a discount code
	if(!empty($discount_code) || !empty($_REQUEST['discount_code']))
		return;

	if(isset($_REQUEST['autorenew_present']) && isset($_REQUEST['autorenew']))
		$autorenew = intval($_REQUEST['autorenew']);
	elseif(isset($_SESSION['autorenew']))
		$autorenew = $_SESSION['autorenew'];
	elseif($options['setting'] == 2)
		$autorenew = 1;
	else
		$autorenew = 0;

	if(!$pmpro_review) {
		?>
		<fieldset id="pmpro_autorenewal_checkbox" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fieldset', 'pmpro_autorenewal_checkbox' ) ); ?>">
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
					<legend class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_legend' ) ); ?>">
						<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_heading pmpro_font-large' ) ); ?>">
							<?php esc_html_e( 'Would you like to set up automatic renewals?', 'pmpro-auto-renewal-checkbox' ); ?>
						</h2>
					</legend>
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields' ) ); ?>">
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-checkbox' ) ); ?>">
							<label for="autorenew" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label pmpro_form_label-inline pmpro_clickable' ) ); ?>">
								<input type="checkbox" id="autorenew" name="autorenew" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-checkbox' ) ); ?>" value="1" <?php checked( $autorenew, 1 ); ?> />
								<input type="hidden" id="autorenew_present" name="autorenew_present" value="1" />
								<?php
									//setup a temp level with initial = billing amount so the short level cost text looks nice
									$temp_level = pmpro_getLevel($pmpro_level->id);
									remove_filter("pmpro_checkout_level", "pmproarc_checkout_level", 7);
									$temp_level = apply_filters('pmpro_checkout_level', $temp_level);
									add_filter("pmpro_checkout_level", "pmproarc_checkout_level", 7);
									$temp_level->initial_payment = $temp_level->billing_amount;
	
									/* translators: Level Cost */
									printf(__('Yes, renew at %s', 'pmpro-auto-renewal-checkbox'), pmpro_getLevelCost($temp_level, false, true));
								?>
							</label>
						</div> <!-- end pmpro_form_field -->
					</div> <!-- end pmpro_form_fields -->
				</div> <!-- end pmpro_card_content -->
			</div> <!-- end pmpro_card -->
		</fieldset> <!-- end pmpro_autorenewal_checkbox -->
		<?php	
	} else {
		if(!empty($_SESSION['autorenew']))
		?>
		<input type="hidden" id="autorenew" name="autorenew" value="<?php echo intval($autorenew);?>" />
		<input type="hidden" id="autorenew_present" name="autorenew_present" value="1" />
		<?php
	}
}
add_action('pmpro_checkout_boxes', 'pmproarc_pmpro_checkout_boxes', 15);

//save autorenew to session for PayPal Express
function pmproarc_pmpro_paypalexpress_session_vars() {
	if(isset($_REQUEST['autorenew_present']) && isset($_REQUEST['autorenew']))
		$autorenew = intval($_REQUEST['autorenew']);
	else
		$autorenew = 0;

	$_SESSION['autorenew'] = $autorenew;
	$_SESSION['autorenew_present'] = 1;
}
add_action('pmpro_paypalexpress_session_vars', 'pmproarc_pmpro_paypalexpress_session_vars');
add_action('pmpro_before_send_to_twocheckout', 'pmprorh_rf_pmpro_paypalexpress_session_vars', 10, 0);

//update level based on selection
function pmproarc_checkout_level($level) {
	global $discount_code;

	//no level anymore, just return it
	if( empty( $level ) )
		return $level;

	//only for certain levels
	$options = pmproarc_getOptions($level->id);
	if(empty($options) || empty($options['setting']))
		return $level;

	//maybe the level doesn't have a recurring billing amount
	if(!pmpro_isLevelRecurring($level))
		return $level;

	//not if addon package
	if(!empty($_REQUEST['ap']) || !empty($_SESSION['ap']))
		return $level;

	//not if using a discount code
	if ( ! empty( $discount_code ) || ! empty( $_REQUEST['discount_code'] ) || ! empty( $_REQUEST['pmpro_discount_code'] ) ) {
		return $level;
	}

	if(isset($_REQUEST['autorenew_present']) && empty($_REQUEST['autorenew']))
		$autorenew = 0;
	elseif(isset($_REQUEST['autorenew_present']))
		$autorenew = intval($_REQUEST['autorenew']);
	elseif(isset($_SESSION['autorenew_present']))
		$autorenew = intval($_SESSION['autorenew']);
	elseif($options['setting'] == 2)
		$autorenew = 1;
	else
		$autorenew = 0;

	if(!$autorenew) {
		//setup expiration
		$level->expiration_number = $level->cycle_number;
		$level->expiration_period = $level->cycle_period;

		//remove recurring billing
		$level->billing_amount = 0;
		$level->cycle_number = 0;
	} else {
        // Disable Set Expiration Date
        remove_filter( 'pmpro_checkout_level', 'pmprosed_pmpro_checkout_level' );
        remove_filter( 'pmpro_discount_code_level', 'pmprosed_pmpro_checkout_level', 10, 2 );
    }

	return $level;
}
add_filter("pmpro_checkout_level", "pmproarc_checkout_level", 7);

/*
	If checking out for a recurring version of your current level, and you have an expiration/enddate,
	then set the profile to start on the day your membership was going to expire.

	For example, if your membership expires in 10 days and you checkout for a recurring monthly subscription
	for the same level, then you will the initial payment at checkout and your next payment will be one month
	from your old expiration date.

	We filter this a little early so other custom code (e.g. prorating code) will override this.
*/
function pmproarc_profile_start_date_delay_subscription($startdate, $order) {
	// If the level is not recurring, bail.
	if ( empty( $order->membership_level ) || ! pmpro_isLevelRecurring( $order->membership_level ) ) {
		return $startdate;
	}

	// If the user has this level, get it.
	$current_level = pmpro_getSpecificMembershipLevelForUser( $order->user_id, $order->membership_level->id );

	// If this user does not have this level, bail.
	if ( empty( $current_level ) ) {
		return $startdate;
	}

	// If the user's level is not expiring, bail.
	if ( ! pmpro_isLevelExpiring( $current_level ) || empty( $current_level->enddate ) ) {
		return $startdate;
	}

	// Calculate the number of seconds until expiration.
	$seconds_until_expiration = $current_level->enddate - current_time( 'timestamp' );
	$seconds_until_expiration = max( 0, $seconds_until_expiration );

	// Calculate the date of the next payment.
	$startdate = date( 'Y-m-d H:i:s', strtotime( $startdate, current_time( 'timestamp' ) ) + $seconds_until_expiration );
    
	return $startdate;
}
add_filter( 'pmpro_profile_start_date', 'pmproarc_profile_start_date_delay_subscription', 9, 2 );

/**
 * Hook the legacy pmproarc_profile_start_date_delay_subscription() function if running a PMPro version before v3.4.
 * Otherwise, pmproarc_checkout_level_extend_memberships() will be used to extend memberships when purchasing recurring levels.
 *
 * @since TBD
 */
function pmprosd_hook_pmpro_profile_start_date() {
	if ( version_compare( PMPRO_VERSION, '3.4', '<' ) ) {
		add_filter( 'pmpro_profile_start_date', 'pmproarc_profile_start_date_delay_subscription', 10, 2 );
	}
}
add_action( 'init', 'pmprosd_hook_pmpro_profile_start_date' );

/*
 * If checking out for a level that the user already has, extend the membership from their next payment date or expiration date.
 *
 * @since TBD Updated to extend memberships when purchasing recurring levels as well.
 *
 * @param object $level The level object.
 */
function pmproarc_checkout_level_extend_memberships( $level ) {
	// If we don't have a level for some reason, bail.
	if ( empty( $level ) ) {
		return $level;
	}

	// If the user does not already have this level, bail.
	$user_level = pmpro_getSpecificMembershipLevelForUser( get_current_user_id(), $level->id );
	if ( empty( $user_level ) ) {
		return $level;
	}

	// Check whether an expiring or recurring level is being purchased.
	if ( ! empty( $level->expiration_number ) ) {
		// The level being purchased has an expiration date.
		// Core PMPro will extend the expiration date if the user already has a level with an expiration date (see pmpro_checkout_level_extend_memberships()).
		// So here, we only need to extend the expiration date if the user has a subscription for this level.
		if ( class_exists( 'PMPro_Subscription' ) ) {
			// Check if the user has a subscription for this level.
			$subscriptions = PMPro_Subscription::get_subscriptions_for_user( get_current_user_id(), $level->id );

			// If the user has no subscriptions, we don't need to alter the level.
			if ( empty( $subscriptions ) ) {
				return $level;
			}

			// Get the next payment date for the user's subscription.
			$next_payment_date = reset( $subscriptions )->get_next_payment_date();
		} else {
			// Backwards compatibility for PMPro 2.x.
			//we want to make sure that we use APIs to get next payment date when available
			add_filter('pmpro_next_payment', array('PMProGateway_stripe', 'pmpro_next_payment'), 10, 3);
			add_filter('pmpro_next_payment', array('PMProGateway_paypalexpress', 'pmpro_next_payment'), 10, 3);

			//recurring memberships will have a next payment date
			$next_payment_date = pmpro_next_payment();

			// If the next payment date is not set, we don't need to alter the level.
			if ( empty( $next_payment_date ) ) {
				return $level;
			}
		}

		//calculate days left
		$todays_date = current_time('timestamp');
		$time_left = $next_payment_date + (3600*24) - $todays_date;

		//time left?
		if($time_left > 0)
		{
			//convert to days and add to the expiration date (assumes expiration was 1 year)
			$days_left = floor($time_left/(60*60*24));

			//figure out days based on period
			if($level->expiration_period == "Day")
				$total_days = $days_left + $level->expiration_number;
			elseif($level->expiration_period == "Week")
				$total_days = $days_left + $level->expiration_number * 7;
			elseif($level->expiration_period == "Month")
				$total_days = $days_left + $level->expiration_number * 30;
			elseif($level->expiration_period == "Year")
				$total_days = $days_left + $level->expiration_number * 365;

			//update number and period
			$level->expiration_number = $total_days;
			$level->expiration_period = "Day";
		}
	} elseif ( pmpro_isLevelRecurring( $level ) ) {
		// The level being purchased is recurring.
		// If the user already has a recurring level, they shouldn't need to check out again.
		// So here, we only want to extend the profile start date if the user currently has an expiring level.
		if ( empty( $user_level->enddate ) ) {
			return $level;
		}

		// If a profile start date is already set (possibly by Subscription Delays or prorations), respect that.
		if ( ! empty( $level->profile_start_date ) ) {
			return $level;
		}

		// Add the billing cycle and number of periods to the user's current expiration date.
		$level->profile_start_date = date( 'Y-m-d H:i:s', strtotime( '+' . $level->cycle_number . ' ' . $level->cycle_period, $user_level->enddate ) );
	}

	return $level;
}
add_filter("pmpro_checkout_level", "pmproarc_checkout_level_extend_memberships", 15);

/*
 Function to add links to the plugin row meta
*/
function pmproarc_plugin_row_meta($links, $file) {
	if(strpos($file, 'pmpro-auto-renewal-checkbox') !== false) {
		$new_links = array(
			'<a href="' . esc_url('https://www.paidmembershipspro.com/add-ons/auto-renewal-checkbox-membership-checkout/' ) . '" title="' . esc_attr( __( 'View Documentation', 'pmpro-auto-renewal-checkbox' ) ) . '">' . __( 'Docs', 'pmpro-auto-renewal-checkbox' ) . '</a>',
			'<a href="' . esc_url('https://paidmembershipspro.com/support/') . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro-auto-renewal-checkbox' ) ) . '">' . __( 'Support', 'pmpro-auto-renewal-checkbox' ) . '</a>',
		);
		$links = array_merge($links, $new_links);
	}
	return $links;
}
add_filter('plugin_row_meta', 'pmproarc_plugin_row_meta', 10, 2);
