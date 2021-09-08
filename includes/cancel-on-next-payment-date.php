<?php
// Copied from PMPro Cancel on Next Payment Date Add On:
// https://github.com/strangerstudios/pmpro-cancel-on-next-payment-date/

// Also changed prefixes from pmproconpd_ to pmproarc_conpd_ and added compatibility
// code to each function to avoid issues.

/**
 * If the user has a payment coming up, don't cancel.
 * Instead update their expiration date and keep their level.
 *
 * @param int   $level            The ID of the membership level we're changing to for the user.
 * @param int   $user_id          The User ID we're changing membership information for.
 * @param array $old_level_status The status for the old level's change (if applicable).
 * @param int   $cancel_level     The level being cancelled (if applicable).
 *
 * @global int $pmpro_next_payment_timestamp The UNIX epoch value for the next payment.
 *
 * @return int The ID of the membership level we're changing to for the user.
 */
function pmproarc_conpd_pmpro_change_level( $level, $user_id, $old_level_status, $cancel_level ) {
	if ( function_exists( 'pmproconpd_pmpro_change_level' ) ) {
		return $level;
	}
	global $pmpro_pages, $wpdb, $pmpro_next_payment_timestamp;

	// Bypass if not level 0.
	if ( 0 !== (int) $level ) {
		return $level;
	}
	
	// Bypass if cancelling due to an error, e.g. a payment was missed.
	if ( 'error' === $old_level_status ) {
		return $level;
	}

	$is_on_cancel_page = is_page( $pmpro_pages['cancel'] );
	$is_on_profile_page = is_admin() && ( ! empty( $_REQUEST['from'] && 'profile' === $_REQUEST['from'] ) );

	// Bypass if not on cancellation page or a non-profile admin page.
	// Webhook IPN calls that go through admin-ajax are non-profile admin pages.
	if ( ! $is_on_cancel_page && ( ! is_admin() || $is_on_profile_page ) ) {
		return $level;
	}

	// Default to false. In case we're changing membership levels multiple times during this page load.
	$pmpro_next_payment_timestamp = false;

	// Get the last order.
	$order = new MemberOrder();
	$order->getLastMemberOrder( $user_id, 'success', $cancel_level );

	// Get level to check if it already has an end date.
	if ( ! empty( $order ) && ! empty( $order->membership_id ) ) {
		$check_level = $wpdb->get_row(
			$wpdb->prepare( "
					SELECT *
					FROM `{$wpdb->pmpro_memberships_users}`
					WHERE
						`membership_id` = %d
						AND `user_id` = %d
					ORDER BY `id` DESC
					LIMIT 1
				",
				$order->membership_id,
				$user_id
			)
		);
	}

	// Figure out the next payment timestamp.
	if ( empty( $check_level ) || ( ! empty( $check_level->enddate ) && '0000-00-00 00:00:00' !== $check_level->enddate ) ) {
		// Level already has an end date. Set to false so we really cancel.
		$pmpro_next_payment_timestamp = false;
	} elseif ( ! empty( $order ) && 'stripe' === $order->gateway ) {
		$pmpro_next_payment_timestamp = PMProGateway_stripe::pmpro_next_payment( '', $user_id, 'success' );
	} elseif ( ! empty( $order ) && 'paypalexpress' === $order->gateway ) {
		// Check the transaction type.
		if ( ! empty( $_POST['txn_type'] ) && 'recurring_payment_failed' === $_POST['txn_type'] ) {
			// Payment failed, so we're past due. No extension.
			$pmpro_next_payment_timestamp = false;
		} else {
			// Check the next payment date passed in or via API.
			if ( ! empty( $_POST['next_payment_date'] ) && 'N/A' !== $_POST['next_payment_date'] ) {
				// Cancellation is being initiated from the IPN.
				$pmpro_next_payment_timestamp = strtotime( $_POST['next_payment_date'], current_time( 'timestamp' ) );
			} elseif ( ! empty( $_POST['next_payment_date'] ) && 'N/A' === $_POST['next_payment_date'] ) {
				// Use the built in PMPro function to guess next payment date.
				$pmpro_next_payment_timestamp = pmpro_next_payment( $user_id );
			} else {
				// Cancel is being initiated from PMPro.
				$pmpro_next_payment_timestamp = PMProGateway_paypalexpress::pmpro_next_payment( '', $user_id, 'success' );
			}
		}
	} else {
		// Use the built in PMPro function to guess next payment date.
		$pmpro_next_payment_timestamp = pmpro_next_payment( $user_id );
	}

	/**
	 * Allow filtering the next payment timestamp to cancel on based on gateway or any other customization.
	 *
	 * @since 0.4
	 *
	 * @param string|false $pmpro_next_payment_timestamp The next timestamp to cancel at (false to cancel right away).
	 * @param string       $gateway                      The order gateway.
	 * @param int          $level                        The membership level ID.
	 * @param int          $user_id                      The member user ID.
	 */
	$pmpro_next_payment_timestamp = apply_filters( 'pmproconpd_next_payment_timestamp_to_cancel_on', $pmpro_next_payment_timestamp, $order->gateway, $level, $user_id );

	// Bypass if we are not extending.
	if ( false === $pmpro_next_payment_timestamp ) {
		return $level;
	}

	// Make sure they keep their level.
	$level = $cancel_level;

	// Cancel their last order.
	if ( ! empty( $order ) ) {
		// This also triggers the cancellation email.
		$order->cancel();
	}

	// Update the expiration date.
	$expiration_date = date( 'Y-m-d H:i:s', $pmpro_next_payment_timestamp );

	$wpdb->update(
		$wpdb->pmpro_memberships_users,
		[
			'enddate' => $expiration_date,
		],
		[
			'status'        => 'active',
			'membership_id' => $level,
			'user_id'       => $user_id,
		],
		[
			'%s',
		],
		[
			'%s',
			'%d',
			'%d',
		]
	);

	if ( $is_on_cancel_page ) {
		// Change the message shown on Cancel page.
		add_filter( 'gettext', 'pmproarc_conpd_gettext_cancel_text', 10, 3 );
	} else {
		// Unset global in case other members expire, e.g. during expiration cron.
		unset( $pmpro_next_payment_timestamp );
	}

	return $level;
}
add_filter( 'pmpro_change_level', 'pmproarc_conpd_pmpro_change_level', 10, 4 );

/**
 * Replace the cancellation text so people know they'll still have access for a certain amount of time.
 *
 * @param string $translated_text The translated text.
 * @param string $text            The original text.
 * @param string $domain          The text domain.
 *
 * @return string The updated translated text.
 */
function pmproarc_conpd_gettext_cancel_text( $translated_text, $text, $domain ) {
	if ( function_exists( 'pmproconpd_gettext_cancel_text' ) ) {
		return $translated_text;
	}
	global $pmpro_next_payment_timestamp;

	// Double check that we have reinstated their membership through this Add On.
	if ( empty( $pmpro_next_payment_timestamp ) ) {
		return $translated_text;
	}

	if ( ( 'pmpro' === $domain || 'paid-memberships-pro' === $domain ) && 'Your membership has been cancelled.' === $text ) {
		global $current_user;

		// translators: %s: The date the subscription will expire on.
		$translated_text = sprintf( __( 'Your recurring subscription has been cancelled. Your active membership will expire on %s.', 'pmpro-cancel-on-next-payment-date' ), date( get_option( 'date_format' ), $pmpro_next_payment_timestamp ) );
	}

	return $translated_text;
}

/**
 * Update the cancellation email text so people know they'll still have access for a certain amount of time.
 *
 * @param string $body  The email body content.
 * @param string $email The email address this email will be sent to.
 *
 * @return string The updated email body content.
 */
function pmproarc_conpd_pmpro_email_body( $body, $email ) {
	if ( function_exists( 'pmproconpd_pmpro_email_body' ) ) {
		return $body;
	}
	global $pmpro_next_payment_timestamp;

	/**
	 * Only filter the 'cancel' template and
	 * double check that we have reinstated their membership through this Add On.
	 */
	if ( 'cancel' !== $email->template || empty( $pmpro_next_payment_timestamp ) ) {
		return $body;
	}

	global $wpdb;

	$user_id = $wpdb->get_var(
		$wpdb->prepare( "
				SELECT `ID`
				FROM `{$wpdb->users}`
				WHERE `user_email` = %s
				LIMIT 1
			",
			$email->email
		)
	);

	// Bypass if no user ID found.
	if ( empty( $user_id ) ) {
		return $body;
	}

	// Bypass if date is not in the future.
	if ( ( $pmpro_next_payment_timestamp - current_time( 'timestamp' ) ) <= 0 ) {
		return $body;
	}

	$expiry_date = date_i18n( get_option( 'date_format' ), $pmpro_next_payment_timestamp );

	// translators: %s: The date that access will expire on.
	$body .= '<p>' . sprintf( __( 'Your access will expire on %s.', 'pmpro-cancel-on-next-payment-date' ), $expiry_date ) . '</p>';

	return $body;
}

add_filter( 'pmpro_email_body', 'pmproarc_conpd_pmpro_email_body', 10, 2 );

/**
 * Update the email data to set start and end date variables.
 *
 * @param array $data The current email template variable data.
 * @param string $email The email being sent.
 *
 * @return array The updated email template variable data.
 */
function pmproarc_conpd_pmpro_email_data( $data, $email ) {
	if ( function_exists( 'pmproconpd_pmpro_email_data' ) ) {
		return $data;
	}
	global $pmpro_next_payment_timestamp;

	// Double check that we have reinstated their membership through this Add On.
	if ( empty( $pmpro_next_payment_timestamp ) ) {
		return $data;
	}

	global $wpdb;

	$user_id = $wpdb->get_var(
		$wpdb->prepare( "
				SELECT `ID`
				FROM `{$wpdb->users}`
				WHERE `user_email` = %s
				LIMIT 1
			",
			$data['user_email']
		)
	);

	// Bypass if no user ID found.
	if ( empty( $user_id ) ) {
		return $data;
	}

	// Set the !!startdate!! variable.
	$membership_level = pmpro_getMembershipLevelForUser($user_id, true);
	$data['startdate'] = date_i18n( get_option( 'date_format' ), $membership_level->startdate );

	// Set the !!enddate!! variable.
	$data['enddate'] = date_i18n( get_option( 'date_format' ), $pmpro_next_payment_timestamp );

	return $data;
}
add_filter( 'pmpro_email_data', 'pmproarc_conpd_pmpro_email_data', 10, 2 );
