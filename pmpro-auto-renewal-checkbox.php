<?php
/*
Plugin Name: Paid Memberships Pro - Auto-Renewal Checkbox
Plugin URI: https://www.paidmembershipspro.com/add-ons/auto-renewal-checkbox-membership-checkout/
Description: Make auto-renewal optional at checkout with a checkbox.
Version: 0.3.0
Author: Paid Memberships Pro
Author URI: https://www.paidmembershipspro.com
Text Domain: pmpro-auto-renewal-checkbox
Domain Path: /languages
*/

/*
	Settings, Globals and Constants
*/
define("PMPRO_AUTO_RENEWAL_CHECKBOX_DIR", dirname(__FILE__));

/**
 * Load the functionality from PMPro Cancel on Next Payment Date Add On.
 * This file should be removed once CONPD is merged into core.
 */
require_once( PMPRO_AUTO_RENEWAL_CHECKBOX_DIR . '/includes/cancel-on-next-payment-date.php' );

/*
	Load plugin textdomain.
*/
function pmproarc_load_textdomain() {
  load_plugin_textdomain( 'pmpro-auto-renewal-checkbox', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'pmproarc_load_textdomain' );


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
	<p><?php esc_html_e('Change this setting to make-auto renewals optional at checkout.', 'pmpro-auto-renewal-checkbox');?></p>
	<table>
	<tbody class="form-table">
		<tr>
			<th scope="row" valign="top"><label for="arc_setting"><?php esc_html_e('Auto-Renewal Optional?', 'pmpro-auto-renewal-checkbox');?></label></th>
			<td>
				<select id="arc_setting" name="arc_setting">
					<option value="0" <?php selected($options['setting'], 0);?>><?php esc_html_e('No. All checkouts will setup recurring billing.', 'pmpro-auto-renewal-checkbox');?></option>
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
	<div id="pmpro_autorenewal_checkbox" class="pmpro_checkout">
		<hr />
		<h3>
			<span class="pmpro_checkout-h3-name"><?php esc_html_e('Would you like to set up automatic renewals?', 'pmpro-auto-renewal-checkbox');?></span>
		</h3>
		<div class="pmpro_checkout-fields">
			<div class="pmpro_checkout-field-checkbox pmpro_checkout_field-autorenew">
				<input type="checkbox" id="autorenew" name="autorenew" value="1" <?php checked($autorenew, 1);?> />
				<input type="hidden" id="autorenew_present" name="autorenew_present" value="1" />
				<label class="pmprorh_checkbox_label pmpro_clickable" for="autorenew">
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
			</div>  <!-- end pmpro_checkout-field -->
		</div> <!-- end pmpro_checkout-fields -->
	</div> <!-- end pmpro_payment_method -->
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
	if ( ! empty( $discount_code ) || ! empty( $_REQUEST['discount_code'] ) )
		return $level;

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
	//is this level recurring? does the user already have this level?
	if(!empty($order->membership_level) && pmpro_isLevelRecurring($order->membership_level) && pmpro_hasMembershipLevel($order->membership_level->id)) {
		//check for current expiration
		$current_level = pmpro_getMembershipLevelForUser();
		if(!empty($current_level) && pmpro_isLevelExpiring($current_level)) {
			$startdate = date('Y-m-d', strtotime($startdate, current_time('timestamp')) + $current_level->enddate + (3600*24) - current_time('timestamp')) . 'T0:0:0';
		}
	}
    
	return $startdate;
}
add_filter('pmpro_profile_start_date', 'pmproarc_profile_start_date_delay_subscription', 9, 2);

/*
	If checking out without recurring with an active recurring subscription for the same level,
	extend from the next payment date instead of the date of checkout.
*/
function pmproarc_checkout_level_extend_memberships($level)
{
	//does this level expire? are they an existing user of this level?
	if(!empty($level) && !empty($level->expiration_number) && pmpro_hasMembershipLevel($level->id))
	{
		//we want to make sure that we use APIs to get next payment date when available
		add_filter('pmpro_next_payment', array('PMProGateway_stripe', 'pmpro_next_payment'), 10, 3);
		add_filter('pmpro_next_payment', array('PMProGateway_paypalexpress', 'pmpro_next_payment'), 10, 3);

		//recurring memberships will have a next payment date
		$next_payment_date = pmpro_next_payment();

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
	}

	return $level;
}
add_filter("pmpro_checkout_level", "pmproarc_checkout_level_extend_memberships", 15);

/*
  Change cancellation to set expiration date for next payment instead of cancelling immediately.

  Assumes orders are generated for each payment (i.e. your webhooks/etc are setup correctly).

  Since 2015-09-21 and PMPro v1.8.5.6 contains code to look up next payment dates via Stripe and PayPal Express APIs.
*/
//before cancelling, save the next_payment_timestamp to a global for later use. (Requires PMPro 1.8.5.6 or higher.)
function pmproarc_pmpro_before_change_membership_level($level_id, $user_id)
{
	//are we on the cancel page?
	global $pmpro_pages, $wpdb, $pmpro_stripe_event, $pmpro_next_payment_timestamp;
	if( $level_id == 0 && (
    is_page( $pmpro_pages['cancel'] ) ||
    ( is_admin() &&
      ( empty($_REQUEST['from']) || $_REQUEST['from'] != 'profile' ) && // Don't give back if editing profile
      ( ! isset( $_REQUEST['action'] ) && $_REQUEST['action'] != 'delete' ) // Don't give back if user deleted
    )
  ) ) {
		//get last order
		$order = new MemberOrder();
		$order->getLastMemberOrder($user_id, "success");
		
		//get level to check if it already has an end date
		if(!empty($order) && !empty($order->membership_id)) {
			$level = $wpdb->get_row("SELECT * FROM $wpdb->pmpro_memberships_users WHERE membership_id = '" . esc_sql( $order->membership_id ) . "' AND user_id = '" . esc_sql( $user_id ) . "' ORDER BY id DESC LIMIT 1");
		}
				
		//figure out the next payment timestamp
		if(empty($level) || (!empty($level->enddate) && $level->enddate != '0000-00-00 00:00:00')) {
			//level already has an end date. set to false so we really cancel.
			$pmpro_next_payment_timestamp = false;			
		} elseif(!empty($order) && $order->gateway == "stripe") {
			//if stripe, try to use the API
			if(!empty($pmpro_stripe_event)) {
				//cancel initiated from Stripe webhook
				if(!empty($pmpro_stripe_event->data->object->current_period_end)) {
					$customer = $order->Gateway->getCustomer($order);
					if( !empty( $customer ) && empty( $customer->delinquent ) ) {
						// cancelling early, next payment at period end
						$pmpro_next_payment_timestamp = $pmpro_stripe_event->data->object->current_period_end;
					} else {
						// delinquent, so next payment is in the past
						$pmpro_next_payment_timestamp = $pmpro_stripe_event->data->object->current_period_start;
					}
				}
			} else {
				//cancel initiated from PMPro
				$pmpro_next_payment_timestamp = PMProGateway_stripe::pmpro_next_payment("", $user_id, "success");
			}
		} elseif(!empty($order) && $order->gateway == "paypalexpress") {
			if(!empty($_POST['next_payment_date']) && $_POST['next_payment_date'] != 'N/A') {
				//cancel initiated from IPN
				$pmpro_next_payment_timestamp = strtotime($_POST['next_payment_date'], current_time('timestamp'));
			} else {
				//cancel initiated from PMPro
				$pmpro_next_payment_timestamp = PMProGateway_paypalexpress::pmpro_next_payment("", $user_id, "success");
			}
		} else {
			//use built in PMPro function to guess next payment date
			$pmpro_next_payment_timestamp = pmpro_next_payment($user_id);
		}
	}
}
add_action('pmpro_before_change_membership_level', 'pmproarc_pmpro_before_change_membership_level', 10, 2);

//give users their level back with an expiration
function pmproarc_pmpro_after_change_membership_level($level_id, $user_id)
{
	//are we on the cancel page?
	global $pmpro_pages, $wpdb, $pmpro_next_payment_timestamp;
  if(
    $pmpro_next_payment_timestamp !== false &&
    $level_id == 0 && (
      is_page( $pmpro_pages['cancel'] ) ||
      ( is_admin() &&
        ( empty($_REQUEST['from']) || $_REQUEST['from'] != 'profile' ) && // Don't give back if editing profile
        ( ! isset( $_REQUEST['action'] ) && $_REQUEST['action'] != 'delete' ) // Don't give back if user deleted
        )
  ) ) {
		/*
			okay, let's give the user his old level back with an expiration based on his subscription date
		*/
		//get last order
		$order = new MemberOrder();
		$order->getLastMemberOrder($user_id, "cancelled");

		//can't do this if we can't find the order
		if(empty($order->id))
			return false;

		//get the last level they had
		$level = $wpdb->get_row("SELECT * FROM $wpdb->pmpro_memberships_users WHERE membership_id = '" . $order->membership_id . "' AND user_id = '" . $user_id . "' ORDER BY id DESC LIMIT 1");

		//can't do this if the level isn't recurring
		if(empty($level->cycle_number))
			return false;

		//can't do if we can't find an old level
		if(empty($level))
			return false;

		//last payment date
		$lastdate = date("Y-m-d", $order->timestamp);

		/*
			next payment date
		*/
		//if stripe or PayPal, try to use the API
		if(!empty($pmpro_next_payment_timestamp))
		{
			$nextdate = $pmpro_next_payment_timestamp;
		}
		else
		{
			$nextdate = $wpdb->get_var("SELECT UNIX_TIMESTAMP('" . $lastdate . "' + INTERVAL " . $level->cycle_number . " " . $level->cycle_period . ")");
		}

		//if the date in the future?
		if($nextdate - current_time('timestamp') > 0)
		{						
			//give them their level back with the expiration date set
			$old_level = $wpdb->get_row("SELECT * FROM $wpdb->pmpro_memberships_users WHERE membership_id = '" . $order->membership_id . "' AND user_id = '" . $user_id . "' ORDER BY id DESC LIMIT 1", ARRAY_A);
			$old_level['enddate'] = date("Y-m-d H:i:s", $nextdate);

			//disable this hook so we don't loop
			remove_action("pmpro_after_change_membership_level", "pmproarc_pmpro_after_change_membership_level", 10, 2);

			//change level
			pmpro_changeMembershipLevel($old_level, $user_id);

			//add the action back just in case
			add_action("pmpro_after_change_membership_level", "pmproarc_pmpro_after_change_membership_level", 10, 2);

			//change message shown on cancel page
			add_filter("gettext", "pmproarc_gettext_cancel_text", 10, 3);
		}
	}
}
add_action("pmpro_after_change_membership_level", "pmproarc_pmpro_after_change_membership_level", 10, 2);

//this replaces the cancellation text so people know they'll still have access for a certain amount of time
function pmproarc_gettext_cancel_text($translated_text, $text, $domain)
{
	if($domain == "paid-memberships-pro" && $text == "Your membership has been cancelled.")
	{
		global $current_user;
        $translated_text = esc_html__('Your recurring subscription has been canceled. Your active membership will expire on ', 'pmpro-auto-renewal-checkbox') . date(get_option("date_format"), pmpro_next_payment($current_user->ID, "cancelled"));

	}

	return $translated_text;
}

//want to update the cancellation email as well
function pmproarc_pmpro_email_body($body, $email)
{
	if($email->template == "cancel")
	{
		global $wpdb;
		$user_id = $wpdb->get_var("SELECT ID FROM $wpdb->users WHERE user_email = '" . esc_sql($email->email) . "' LIMIT 1");
		if(!empty($user_id))
		{
			$expiration_date = pmpro_next_payment( $user_id, 'cancelled' );

			//if the date in the future?
			if($expiration_date - current_time('timestamp') > 0)
			{						
				$body .= "<p>Your access will expire on " . date(get_option("date_format"), $expiration_date) . ".</p>";
			}
		}
	}

	return $body;
}
add_filter("pmpro_email_body", "pmproarc_pmpro_email_body", 10, 2);

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
