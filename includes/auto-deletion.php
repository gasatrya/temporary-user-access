<?php
/**
 * Auto-deletion functionality for Temporary User Access plugin
 *
 * @package TemporaryUserAccess
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if user should be auto-deleted
 *
 * @param int $user_id User ID.
 * @return bool
 */
function wp_tua_should_auto_delete_user( $user_id ) {
	$auto_delete = get_user_meta( $user_id, WP_TUA_USER_AUTO_DELETE, true );
	$expiry_date = get_user_meta( $user_id, WP_TUA_USER_EXPIRY_DATE, true );

	// Check if auto-delete is enabled and user has expiry date
	if ( '1' !== $auto_delete || empty( $expiry_date ) ) {
		return false;
	}

	// Check if user is expired
	if ( ! wp_tua_is_user_expired( $user_id ) ) {
		return false;
	}

	// Check grace period
	$expiry_timestamp = wp_tua_get_expiry_timestamp( $expiry_date );
	$current_time     = wp_tua_get_current_timestamp();
	$grace_period_end = $expiry_timestamp + ( WP_TUA_GRACE_PERIOD_DAYS * DAY_IN_SECONDS );

	return $current_time >= $grace_period_end;
}

/**
 * Auto-delete expired users via WP-Cron
 */
function wp_tua_auto_delete_expired_users() {
	// Security check: ensure this function is only called via WP-Cron
	if ( ! defined( 'DOING_CRON' ) || ! DOING_CRON ) {
		return;
	}

	// Load user functions needed for wp_delete_user()
	if ( ! function_exists( 'wp_delete_user' ) ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
	}

	// Prevent race condition: check if auto-deletion is already running
	$lock_key = 'wp_tua_auto_delete_lock';
	if ( get_transient( $lock_key ) ) {
		return;
	}

	// Set lock for 10 minutes to prevent concurrent execution
	set_transient( $lock_key, time(), 10 * MINUTE_IN_SECONDS );

	$users = get_users(
		array(
			'fields'     => 'ID',
			'number'     => WP_TUA_AUTO_DELETE_BATCH_SIZE, // Limit batch size for performance
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Intentional meta_query for auto-deletion feature
			'meta_query' => array(
				array(
					'key'     => WP_TUA_USER_AUTO_DELETE,
					'value'   => '1',
					'compare' => '=',
				),
			),
		)
	);

	// Track operation results
	$processed_count = 0;
	$success_count   = 0;
	$failure_count   = 0;

	foreach ( $users as $user_id ) {
		++$processed_count;
		if ( wp_tua_should_auto_delete_user( $user_id ) ) {
			if ( wp_tua_delete_user_with_content_preservation( $user_id ) ) {
				++$success_count;
			} else {
				++$failure_count;
			}
		}
	}

	// Log batch operation summary
	wp_tua_log(
		'Auto-deletion batch completed',
		array(
			'batch_size' => WP_TUA_AUTO_DELETE_BATCH_SIZE,
			'processed'  => $processed_count,
			'successful' => $success_count,
			'failed'     => $failure_count,
		)
	);

	// Release the lock
	delete_transient( $lock_key );
}
add_action( 'wp_tua_auto_delete_cron', 'wp_tua_auto_delete_expired_users' );

/**
 * Manual deletion capability - can be called from other functions
 *
 * @param int $user_id User ID to delete.
 * @return bool
 */
function wp_tua_manual_delete_user( $user_id ) {
	if ( ! current_user_can( 'delete_users' ) ) {
		return false;
	}

	if ( wp_tua_is_user_admin( $user_id ) ) {
		return false;
	}

	// Load user functions needed for wp_delete_user()
	if ( ! function_exists( 'wp_delete_user' ) ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
	}

	return wp_tua_delete_user_with_content_preservation( $user_id );
}

/**
 * Delete user while preserving their content
 *
 * @param int $user_id User ID to delete.
 * @return bool True on success, false on failure.
 */
function wp_tua_delete_user_with_content_preservation( $user_id ) {
	// Validate user exists before attempting deletion
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		wp_tua_log( 'User deletion skipped: user does not exist', array( 'user_id' => $user_id ) );
		return true; // Not an error - user already gone
	}

	// Get admin user to reassign content to
	$admin_users = get_users(
		array(
			'role'   => 'administrator',
			'number' => 1,
			'fields' => 'ID',
		)
	);

	$reassign_id = ! empty( $admin_users ) ? $admin_users[0] : 1;

	// Validate reassignment user
	$reassign_user = get_userdata( $reassign_id );
	if ( ! $reassign_user || $reassign_id === $user_id ) {
		// If reassignment user is invalid or same as deleted user, find another admin
		$reassign_id = wp_tua_get_valid_reassignment_user( $user_id );
		if ( ! $reassign_id ) {
			// No valid reassignment user found, cannot delete with content preservation
			wp_tua_log( 'User deletion failed: no valid reassignment user', array( 'user_id' => $user_id ) );
			return false;
		}
	}

	// Attempt to delete user with error checking
	$deletion_result = wp_delete_user( $user_id, $reassign_id );

	if ( false === $deletion_result ) {
		wp_tua_log(
			'User deletion failed: wp_delete_user returned false',
			array(
				'user_id'       => $user_id,
				'reassigned_to' => $reassign_id,
			)
		);
		return false;
	}

	// Log successful deletion
	wp_tua_log(
		'User deleted successfully',
		array(
			'user_id'       => $user_id,
			'reassigned_to' => $reassign_id,
		)
	);

	return true;
}

/**
 * Get a valid user to reassign content to
 *
 * @param int $exclude_user_id User ID to exclude from reassignment.
 * @return int|false Valid user ID or false if none found.
 */
function wp_tua_get_valid_reassignment_user( $exclude_user_id ) {
	// Try to find any administrator
	$admin_users = get_users(
		array(
			'role'    => 'administrator',
			'number'  => 10, // Get multiple to find a valid one
			'fields'  => 'ID',
			'exclude' => array( $exclude_user_id ),
		)
	);

	foreach ( $admin_users as $admin_id ) {
		$admin_user = get_userdata( $admin_id );
		if ( $admin_user && $admin_id !== $exclude_user_id ) {
			return $admin_id;
		}
	}

	// If no administrators found, try any user with edit_posts capability
	$editor_users = get_users(
		array(
			'capability' => 'edit_posts',
			'number'     => 5,
			'fields'     => 'ID',
			'exclude'    => array( $exclude_user_id ),
		)
	);

	foreach ( $editor_users as $editor_id ) {
		$editor_user = get_userdata( $editor_id );
		if ( $editor_user && $editor_id !== $exclude_user_id ) {
			return $editor_id;
		}
	}

	// No valid reassignment user found
	return false;
}
