<?php
/**
 * Plugin Name: Temporary User Access
 * Plugin URI: https://www.ctaflow.com/temporary-user-access
 * Description: Extend WordPress user management with expiration functionality for temporary user accounts. Set expiry dates, enable auto-deletion, and keep your user database clean.
 * Version: 1.0.0
 * Author: Ga Satrya
 * Author URI: https://www.ctaflow.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: temporary-user-access
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package TemporaryUserAccess
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'TUA_VERSION', '1.0.0' );
define( 'TUA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TUA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'TUA_BASENAME', plugin_basename( __FILE__ ) );

// Define user meta keys
define( 'TUA_USER_EXPIRY_DATE', '_user_expiry_date' );
define( 'TUA_USER_ACCOUNT_STATUS', '_user_account_status' );
define( 'TUA_USER_AUTO_DELETE', '_user_auto_delete' );
define( 'TUA_USER_GRACE_PERIOD', '_user_expiry_grace_period' );

// Define account status constants
define( 'TUA_STATUS_ACTIVE', 'active' );
define( 'TUA_STATUS_EXPIRED', 'expired' );

// Define configurable constants
define( 'TUA_AUTO_DELETE_BATCH_SIZE', 50 );
define( 'TUA_GRACE_PERIOD_DAYS', 7 );

// Include additional functionality files
require_once TUA_PLUGIN_DIR . 'includes/helpers.php';
require_once TUA_PLUGIN_DIR . 'includes/authentication.php';
require_once TUA_PLUGIN_DIR . 'includes/user-management.php';
require_once TUA_PLUGIN_DIR . 'includes/auto-deletion.php';

/**
 * Plugin activation hook
 */
function tua_activate_plugin() {
	// Set up any required database tables or options
	update_option( 'tua_plugin_version', TUA_VERSION );

	// Schedule auto-deletion cron job
	if ( ! wp_next_scheduled( 'tua_auto_delete_cron' ) ) {
		wp_schedule_event( time(), 'hourly', 'tua_auto_delete_cron' );
	}
}
register_activation_hook( __FILE__, 'tua_activate_plugin' );

/**
 * Plugin deactivation hook
 */
function tua_deactivate_plugin() {
	// Clean up temporary data or options if needed
	delete_option( 'tua_plugin_version' );

	// Remove auto-deletion cron job
	wp_clear_scheduled_hook( 'tua_auto_delete_cron' );
}
register_deactivation_hook( __FILE__, 'tua_deactivate_plugin' );
