<?php
/**
 * Auto-deletion class for Temporary User Access plugin.
 *
 * @package TempUsAc\Cron
 */

namespace TempUsAc\Cron;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use TempUsAc\Utils\Helpers;
use TempUsAc\Auth\Authentication;

/**
 * AutoDeletion class
 */
class AutoDeletion {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Set up plugin hooks.
	 */
	private function setup_hooks(): void {
		add_action( 'tempusac_auto_delete_cron', array( $this, 'auto_delete_expired_users' ) );
	}

	/**
	 * Check if user should be auto-deleted.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public function should_auto_delete_user( int $user_id ): bool {
		$auto_delete = get_user_meta( $user_id, TEMPUSAC_USER_AUTO_DELETE, true );
		$expiry_date = get_user_meta( $user_id, TEMPUSAC_USER_EXPIRY_DATE, true );

		// Check if auto-delete is enabled and user has expiry date.
		if ( '1' !== $auto_delete || empty( $expiry_date ) ) {
			return false;
		}

		// Check if user is expired.
		if ( ! Authentication::is_user_expired( $user_id ) ) {
			return false;
		}

		// Check grace period.
		$expiry_timestamp = Helpers::get_expiry_timestamp( $expiry_date );
		$current_time     = Helpers::get_current_timestamp();
		$grace_period_end = $expiry_timestamp + ( Helpers::get_grace_period() * DAY_IN_SECONDS );

		return $current_time >= $grace_period_end;
	}

	/**
	 * Auto-delete expired users via WP-Cron.
	 */
	public function auto_delete_expired_users(): void {
		// Security check: ensure this function is only called via WP-Cron.
		if ( ! defined( 'DOING_CRON' ) || ! DOING_CRON ) {
			return;
		}

		// Prevent race condition: check if auto-deletion is already running.
		$lock_key = 'tempusac_auto_delete_lock';
		if ( get_transient( $lock_key ) ) {
			return;
		}

		// Set lock for 10 minutes to prevent concurrent execution.
		set_transient( $lock_key, time(), 10 * MINUTE_IN_SECONDS );

		/**
		 * Calculate the cutoff date for the grace period.
		 * We only want users whose expiry date was at least X days ago.
		 */
		$wp_timezone = wp_timezone();
		$cutoff_date = new \DateTime( 'now', $wp_timezone );
		$cutoff_date->modify( '-' . Helpers::get_grace_period() . ' days' );
		$formatted_cutoff = $cutoff_date->format( 'Y-m-d' );

		$users = get_users(
			array(
				'fields'     => 'ID',
				'number'     => TEMPUSAC_AUTO_DELETE_BATCH_SIZE, // Limit batch size for performance.
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Intentional meta_query for auto-deletion feature.
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'     => TEMPUSAC_USER_AUTO_DELETE,
						'value'   => '1',
						'compare' => '=',
					),
					array(
						'key'     => TEMPUSAC_USER_EXPIRY_DATE,
						'value'   => $formatted_cutoff,
						'compare' => '<=',
						'type'    => 'DATE',
					),
				),
			)
		);

		$to_delete = array();

		foreach ( $users as $user_id ) {
			// Final check to ensure all conditions are met (safety redundancy).
			if ( $this->should_auto_delete_user( (int) $user_id ) ) {
				$to_delete[] = (int) $user_id;
			}
		}

		if ( ! empty( $to_delete ) ) {
			$this->trigger_loopback_deletion( $to_delete );
		}

		// Release the lock.
		delete_transient( $lock_key );
	}

	/**
	 * Trigger user deletion via an AJAX loopback request.
	 * This provides the necessary admin context for core functions.
	 *
	 * @param array $user_ids Array of user IDs to delete.
	 */
	private function trigger_loopback_deletion( array $user_ids ): void {
		// Generate a secure token.
		$token = wp_generate_password( 32, false );
		set_transient( 'tempusac_cron_token', $token, 5 * MINUTE_IN_SECONDS );

		// Prepare the loopback request.
		$url = admin_url( 'admin-ajax.php' );

		$response = wp_remote_post(
			$url,
			array(
				'method'      => 'POST',
				'timeout'     => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking'    => true,
				'headers'     => array(),
				'body'        => array(
					'action'   => 'tempusac_process_auto_deletion',
					'token'    => $token,
					'user_ids' => $user_ids,
				),
				'cookies'     => array(),
			)
		);

		if ( is_wp_error( $response ) ) {
			Helpers::log( 'Auto-deletion loopback failed', array( 'error' => $response->get_error_message() ) );
		} else {
			Helpers::log( 'Auto-deletion loopback triggered', array( 'user_count' => count( $user_ids ) ) );
		}
	}
}
