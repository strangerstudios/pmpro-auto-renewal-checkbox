<?php
/*
Plugin Name: Paid Memberships Pro - Auto-Renewal Checkbox
Plugin URI: www.paidmembershipspro.com/add-ons/plus-add-ons/pmpro-auto-renewal-checkbox/
Description: Make auto-renewal optional at checkout with a checkbox.
Version: .1
Author: Stranger Studios
Author URI: http://www.strangerstudios.com
*/

/*
	Settings, Globals and Constants
*/
define("PMPRO_AUTO_RENEWAL_CHECKBOX_DIR", dirname(__FILE__));

/*
	Load plugin textdomain.
*/
function pmproarc_load_textdomain() {
  load_plugin_textdomain( 'pmproarc', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' ); 
}
add_action( 'plugins_loaded', 'pmproarc_load_textdomain' );


/*
	Add settings to the edit levels page
*/
//show the checkbox on the edit level page
function pmproarc_pmpro_membership_level_after_other_settings()
{	
	$level_id = intval($_REQUEST['edit']);
	$options = pmproarc_getOptions($level_id);	
?>
<div id="arc_setting_div">
	<h3 class="topborder"><?php _e('Auto-Renewal Settings', 'pmproarc');?></h3>
	<p><?php _e('Change this setting to make-auto renewals optional at checkout.', 'pmproarc');?></p>
	<table>
	<tbody class="form-table">
		<tr>
			<th scope="row" valign="top"><label for="arc_setting"><?php _e('Auto-Renewal Optional?', 'pmproarc');?></label></th>
			<td>
				<select id="arc_setting" name="arc_setting">
					<option value="0" <?php selected($options['setting'], 0);?>><?php _e('No. All checkouts will setup recurring billing.', 'pmproarc');?></option>
					<option value="1" <?php selected($options['setting'], 1);?>><?php _e('Yes. Default to unchecked.', 'pmproarc');?></option>
					<option value="2" <?php selected($options['setting'], 2);?>><?php _e('Yes. Default to checked.', 'pmproarc');?></option>
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
			
			jQuery(document).ready(function(){
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

//save pay by check settings when the level is saved/added
function pmproarc_pmpro_save_membership_level($level_id)
{
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
function pmproarc_getOptions($level_id)
{
	if($level_id > 0)
	{
		//option for level, check the DB
		$options = get_option('pmpro_auto_renewal_checkbox_options_' . $level_id, array('setting'=>0));
	}
	else
	{
		//default for new level
		$options = array('setting'=>0);
	}
	
	return $options;
}

/*
	Show the box at checkout and update the level.
*/
//draw the box
function pmproarc_pmpro_checkout_boxes()
{
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
	if(!empty($discount_code))
		return;

	if(isset($_REQUEST['autorenew_present']))
		$autorenew = intval($_REQUEST['autorenew']);
	elseif($options['setting'] == 2)
		$autorenew = 1;
	else
		$autorenew = 0;
		
	if(!$pmpro_review)
	{
	?>
	<table id="pmpro_payment_method" class="pmpro_checkout top1em" width="100%" cellpadding="0" cellspacing="0" border="0">
		<thead>
			<tr>
				<th><?php _e('Would you like to set up automatic renewals?', 'pmproarc');?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>
					<div>
						<input type="checkbox" id="autorenew" name="autorenew" value="1" <?php checked($autorenew, 1);?> />
						<input type="hidden" id="autorenew_present" name="autorenew_present" value="1" />
						<label class="pmpro_normal pmpro_clickable" for="autorenew">
							<?php 
								//setup a temp level with initial = billing amount so the short level cost text looks nice
								$temp_level = pmpro_getLevel($pmpro_level->id);
								$temp_level->initial_payment = $temp_level->billing_amount;
								printf(__('Yes, renew at %s', 'pmproarc'), pmpro_getLevelCost($temp_level, false, true)); 
							?>
						</label>
					</div>
				</td>
			</tr>
		</tbody>
	</table>
	<?php
	}
	else
	{
		if(!empty($_SESSION['autorenew']))		
		?>
		<input type="hidden" id="autorenew" name="autorenew" value="<?php $autorenew;?>" />
		<input type="hidden" id="autorenew_present" name="autorenew_present" value="1" />
		<?php
	}
}
add_action('pmpro_checkout_boxes', 'pmproarc_pmpro_checkout_boxes', 15);

//save autorenew to session for PayPal Express
function pmproarc_pmpro_paypalexpress_session_vars()
{
	if(isset($_REQUEST['autorenew_present']))
		$autorenew = intval($_REQUEST['autorenew']);
	else
		$autorenew = 0;
		
	$_SESSION['autorenew'] = $autorenew;
	$_SESSION['autorenew_present'] = 1;		
}
add_action('pmpro_paypalexpress_session_vars', 'pmproarc_pmpro_paypalexpress_session_vars');

//update level based on selection
function pmproarc_checkout_level($level)
{
	global $discount_code;
	
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
	if(!empty($discount_code))
		return $level;

	if(isset($_REQUEST['autorenew_present']))
		$autorenew = intval($_REQUEST['autorenew']);		
	elseif(isset($_SESSION['autorenew_present']))
		$autorenew = intval($_SESSION['autorenew']);
	else
		$autorenew = 0;		
		
	if(!$autorenew)
	{
		$level->billing_amount = 0;
		$level->cycle_number = 0;
	}
		
	return $level;
}
add_filter("pmpro_checkout_level", "pmproarc_checkout_level", 7);