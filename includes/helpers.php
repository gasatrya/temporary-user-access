<?php
/**
 * Helper functions for Temporary User Access plugin
 *
 * @package TemporaryUserAccess
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if current user can manage expiry settings
 *
 * @return bool
 */
function tua_current_user_can_manage_expiry() {
	return current_user_can( 'create_users' ) && tua_is_current_user_admin();
}

/**
 * Check if the current user is an administrator
 *
 * @return bool
 */
function tua_is_current_user_admin() {
	return current_user_can( 'manage_options' );
}

/**
 * Check if a user is an administrator
 *
 * @param int $user_id User ID.
 * @return bool
 */
function tua_is_user_admin( $user_id ) {
	$user = get_userdata( $user_id );
	return $user && in_array( 'administrator', (array) $user->roles, true );
}

/**
 * Validate date format and ensure it's in the future.
 *
 * @param string $date Date string.
 * @return bool
 */
function tua_validate_date( $date ) {
	// Empty date is valid (permanent account)
	if ( empty( $date ) ) {
		return true;
	}

	// Validate format
	$d = DateTime::createFromFormat( 'Y-m-d', $date );
	if ( ! $d || $d->format( 'Y-m-d' ) !== $date ) {
		return false;
	}

	// Get current date in WordPress timezone
	$wp_timezone = wp_timezone();
	$now         = new DateTime( 'now', $wp_timezone );
	$today       = $now->format( 'Y-m-d' );

	// Date must be today or in the future
	return $date >= $today;
}

/**
 * Log a plugin action to debug.log for debugging purposes.
 *
 * @param string $message Log message.
 * @param array  $context Optional context data to include in log.
 */
function tua_log( $message, $context = array() ) {
	if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
		return;
	}

	$context_str = empty( $context ) ? '' : ' | Context: ' . wp_json_encode( $context );
	error_log( '[Temporary User Access] ' . $message . $context_str );
}

/**
 * Get expiry timestamp in WordPress timezone
 *
 * @param string $expiry_date Expiry date in YYYY-MM-DD format.
 * @return int|false Timestamp in WordPress timezone or false on failure.
 */
function tua_get_expiry_timestamp( $expiry_date ) {
	if ( empty( $expiry_date ) ) {
		return false;
	}

	// Parse date in WordPress timezone
	$timezone = wp_timezone();
	$datetime = DateTime::createFromFormat( 'Y-m-d', $expiry_date, $timezone );

	if ( ! $datetime ) {
		return false;
	}

	// Set to end of day (23:59:59) for expiry
	$datetime->setTime( 23, 59, 59 );

	return $datetime->getTimestamp();
}

/**
 * Get current timestamp in WordPress timezone
 *
 * @return int Current timestamp in WordPress timezone.
 */
function tua_get_current_timestamp() {
	// phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- Intentional: need WordPress timezone, not UTC
	return (int) current_time( 'U' );
}
