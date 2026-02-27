<?php
/**
 * Core Plugin Class
 *
 * @package GateFlow
 */

namespace GateFlow;

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
		if ( ! defined( 'GATEFLOW_VERSION' ) ) {
			define( 'GATEFLOW_VERSION', '1.0.2' );
		}
		if ( ! defined( 'GATEFLOW_PLUGIN_DIR' ) ) {
			define( 'GATEFLOW_PLUGIN_DIR', plugin_dir_path( __DIR__ ) );
		}
		if ( ! defined( 'GATEFLOW_PLUGIN_URL' ) ) {
			define( 'GATEFLOW_PLUGIN_URL', plugin_dir_url( __DIR__ ) );
		}
		if ( ! defined( 'GATEFLOW_BASENAME' ) ) {
			define( 'GATEFLOW_BASENAME', plugin_basename( GATEFLOW_PLUGIN_DIR . 'gateflow.php' ) );
		}

		// Define user meta keys.
		define( 'GATEFLOW_USER_EXPIRY_DATE', '_gateflow_expiry_date' );
		define( 'GATEFLOW_USER_ACCOUNT_STATUS', '_gateflow_account_status' );
		define( 'GATEFLOW_USER_AUTO_DELETE', '_gateflow_auto_delete' );
		define( 'GATEFLOW_USER_GRACE_PERIOD', '_gateflow_grace_period' );

		// Define account status constants.
		define( 'GATEFLOW_STATUS_ACTIVE', 'active' );
		define( 'GATEFLOW_STATUS_EXPIRED', 'expired' );

		// Define configurable constants.
		define( 'GATEFLOW_AUTO_DELETE_BATCH_SIZE', 50 );
		define( 'GATEFLOW_GRACE_PERIOD_DAYS', 2 );
	}

	/**
	 * Initialize plugin components.
	 */
	private function init() {
		// Initialize Utils.
		new Utils\Helpers();

		// Initialize Authentication.
		new Auth\Authentication();

		// Initialize Admin components.
		if ( is_admin() ) {
			new Admin\UserManagement();
			new Admin\Settings();
		}

		// Initialize Cron tasks.
		new Cron\AutoDeletion();

		// Failsafe: Ensure cron is scheduled if it was missed during activation.
		if ( ! wp_next_scheduled( 'gateflow_auto_delete_cron' ) ) {
			wp_schedule_event( time(), 'hourly', 'gateflow_auto_delete_cron' );
		}

		// Set up hooks.
		$this->setup_hooks();
	}

	/**
	 * Set up plugin hooks.
	 */
	private function setup_hooks() {
		register_activation_hook( GATEFLOW_BASENAME, array( $this, 'activate' ) );
		register_deactivation_hook( GATEFLOW_BASENAME, array( $this, 'deactivate' ) );
	}

	/**
	 * Plugin activation hook.
	 */
	public function activate() {
		update_option( 'gateflow_plugin_version', GATEFLOW_VERSION );

		// Schedule auto-deletion cron job.
		if ( ! wp_next_scheduled( 'gateflow_auto_delete_cron' ) ) {
			wp_schedule_event( time(), 'hourly', 'gateflow_auto_delete_cron' );
		}
	}

	/**
	 * Plugin deactivation hook.
	 */
	public function deactivate() {
		delete_option( 'gateflow_plugin_version' );

		// Remove auto-deletion cron job.
		wp_clear_scheduled_hook( 'gateflow_auto_delete_cron' );
	}
}
