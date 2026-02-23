<?php
/**
 * Helper class for Temporary User Access plugin.
 *
 * @package TemporaryUserAccess\Utils
 */

namespace TemporaryUserAccess\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use DateTime;
use DateTimeZone;

/**
 * Helpers class
 */
final class Helpers {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Nothing to initialize here.
	}

	/**
	 * Check if current user can manage expiry settings.
	 *
	 * @return bool
	 */
	public static function current_user_can_manage_expiry(): bool {
		return current_user_can( 'create_users' ) && self::is_current_user_admin();
	}

	/**
	 * Check if the current user is an administrator.
	 *
	 * @return bool
	 */
	public static function is_current_user_admin(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Check if a user is an administrator.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function is_user_admin( int $user_id ): bool {
		$user = get_userdata( $user_id );
		return $user && in_array( 'administrator', (array) $user->roles, true );
	}

	/**
	 * Validate date format and ensure it's in the future.
	 *
	 * @param string $date Date string.
	 * @return bool
	 */
	public static function validate_date( string $date ): bool {
		// Empty date is valid (permanent account).
		if ( empty( $date ) ) {
			return true;
		}

		// Validate format.
		$d = DateTime::createFromFormat( 'Y-m-d', $date );
		if ( ! $d || $d->format( 'Y-m-d' ) !== $date ) {
			return false;
		}

		// Get current date in WordPress timezone.
		$today = current_time( 'Y-m-d' );

		// Date must be today or in the future.
		return $date >= $today;
	}

	/**
	 * Log a plugin action to debug.log for debugging purposes.
	 *
	 * @param string $message Log message.
	 * @param array  $context Optional context data to include in log.
	 */
	public static function log( string $message, array $context = array() ): void {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$context_str = empty( $context ) ? '' : ' | Context: ' . wp_json_encode( $context );
		error_log( '[Temporary User Access] ' . $message . $context_str ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}

	/**
	 * Get expiry timestamp in WordPress timezone.
	 *
	 * @param string $expiry_date Expiry date in YYYY-MM-DD format.
	 * @return int|false Timestamp in WordPress timezone or false on failure.
	 */
	public static function get_expiry_timestamp( string $expiry_date ) {
		if ( empty( $expiry_date ) ) {
			return false;
		}

		// Parse date in WordPress timezone.
		$timezone = wp_timezone();
		$datetime = DateTime::createFromFormat( 'Y-m-d', $expiry_date, $timezone );

		if ( ! $datetime ) {
			return false;
		}

		// Set to end of day (23:59:59) for expiry.
		$datetime->setTime( 23, 59, 59 );

		return $datetime->getTimestamp();
	}

	/**
	 * Get current timestamp in WordPress timezone.
	 *
	 * @return int Current timestamp in WordPress timezone.
	 */
	public static function get_current_timestamp(): int {
		return time();
	}
}
