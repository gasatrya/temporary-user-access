<?php
/**
 * Authentication functionality for Temporary User Access plugin
 *
 * @package TemporaryUserAccess
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if user account is expired
 *
 * @param int $user_id User ID.
 * @return bool
 */
function tua_is_user_expired( $user_id ) {
	$expiry_date = get_user_meta( $user_id, TUA_USER_EXPIRY_DATE, true );
	// Check if expiry date has passed
	if ( ! empty( $expiry_date ) ) {
		$current_time     = tua_get_current_timestamp();
		$expiry_timestamp = tua_get_expiry_timestamp( $expiry_date );

		if ( $expiry_timestamp && $expiry_timestamp <= $current_time ) {
			return true;
		}
	}

	return false;
}

/**
 * Authenticate user and check for expiry
 *
 * @param WP_User|WP_Error|null $user User object, error, or null.
 * @param string                $username Username.
 * @param string                $password Password.
 * @return WP_User|WP_Error|null
 */
function tua_authenticate_user( $user, $username, $password ) {
	// If authentication already failed, return early
	if ( is_wp_error( $user ) || null === $user ) {
		return $user;
	}

	// Skip check for admin users
	if ( tua_is_user_admin( $user->ID ) ) {
		return $user;
	}

	// Check if user account is expired
	if ( tua_is_user_expired( $user->ID ) ) {
		return new WP_Error(
			'account_expired',
			__( '<strong>Error</strong>: Your account has expired. Please contact the administrator.', 'temporary-user-access' )
		);
	}

	return $user;
}
add_filter( 'authenticate', 'tua_authenticate_user', 30, 3 );

/**
 * Set auth cookie expiration for users with expiry dates
 *
 * @param int $expiration Expiration time in seconds.
 * @param int $user_id    User ID.
 * @return int
 */
function tua_auth_cookie_expiration( $expiration, $user_id ) {
	// Admin users get normal expiration
	if ( tua_is_user_admin( $user_id ) ) {
		return $expiration;
	}

	// Check if user has an expiry date set
	$expiry_date = get_user_meta( $user_id, TUA_USER_EXPIRY_DATE, true );

	if ( ! empty( $expiry_date ) ) {
		// Set 1-hour expiration for users with expiry dates
		return HOUR_IN_SECONDS;
	}

	return $expiration;
}
add_filter( 'auth_cookie_expiration', 'tua_auth_cookie_expiration', 10, 3 );
