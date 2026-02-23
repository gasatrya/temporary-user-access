<?php
/**
 * Core Plugin Class
 *
 * @package TemporaryUserAccess
 */

namespace TemporaryUserAccess;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core Plugin Class
 */
class Core {

	/**
	 * Instance of this class.
	 *
	 * @var Core
	 */
	private static $instance = null;

	/**
	 * Get instance of this class.
	 *
	 * @return Core
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->define_constants();
		$this->init();
	}

	/**
	 * Define plugin constants.
	 */
	private function define_constants() {
		if ( ! defined( 'TEMPUSAC_VERSION' ) ) {
			define( 'TEMPUSAC_VERSION', '1.0.0' );
		}
		if ( ! defined( 'TEMPUSAC_PLUGIN_DIR' ) ) {
			define( 'TEMPUSAC_PLUGIN_DIR', plugin_dir_path( __DIR__ ) );
		}
		if ( ! defined( 'TEMPUSAC_PLUGIN_URL' ) ) {
			define( 'TEMPUSAC_PLUGIN_URL', plugin_dir_url( __DIR__ ) );
		}
		if ( ! defined( 'TEMPUSAC_BASENAME' ) ) {
			define( 'TEMPUSAC_BASENAME', plugin_basename( TEMPUSAC_PLUGIN_DIR . 'temporary-user-access.php' ) );
		}

		// Define user meta keys.
		define( 'TEMPUSAC_USER_EXPIRY_DATE', '_user_expiry_date' );
		define( 'TEMPUSAC_USER_ACCOUNT_STATUS', '_user_account_status' );
		define( 'TEMPUSAC_USER_AUTO_DELETE', '_user_auto_delete' );
		define( 'TEMPUSAC_USER_GRACE_PERIOD', '_user_expiry_grace_period' );

		// Define account status constants.
		define( 'TEMPUSAC_STATUS_ACTIVE', 'active' );
		define( 'TEMPUSAC_STATUS_EXPIRED', 'expired' );

		// Define configurable constants.
		define( 'TEMPUSAC_AUTO_DELETE_BATCH_SIZE', 50 );
		define( 'TEMPUSAC_GRACE_PERIOD_DAYS', 7 );
	}

	/**
	 * Initialize plugin components.
	 */
	private function init() {
		// Initialize Utils.
		new Utils\Helpers();

		// Initialize Authentication.
		new Auth\Authentication();

		// Initialize Admin User Management.
		if ( is_admin() ) {
			new Admin\UserManagement();
		}

		// Initialize Cron tasks.
		new Cron\AutoDeletion();

		// Set up hooks.
		$this->setup_hooks();
	}

	/**
	 * Set up plugin hooks.
	 */
	private function setup_hooks() {
		register_activation_hook( TEMPUSAC_BASENAME, array( $this, 'activate' ) );
		register_deactivation_hook( TEMPUSAC_BASENAME, array( $this, 'deactivate' ) );
	}

	/**
	 * Plugin activation hook.
	 */
	public function activate() {
		update_option( 'tempusac_plugin_version', TEMPUSAC_VERSION );

		// Schedule auto-deletion cron job.
		if ( ! wp_next_scheduled( 'tempusac_auto_delete_cron' ) ) {
			wp_schedule_event( time(), 'hourly', 'tempusac_auto_delete_cron' );
		}
	}

	/**
	 * Plugin deactivation hook.
	 */
	public function deactivate() {
		delete_option( 'tempusac_plugin_version' );

		// Remove auto-deletion cron job.
		wp_clear_scheduled_hook( 'tempusac_auto_delete_cron' );
	}
}
