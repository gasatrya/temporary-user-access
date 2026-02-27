<?php
/**
 * GateFlow
 *
 * @package           GateFlow
 * @author            ctaflow
 * @copyright         2026 ctaflow
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       GateFlow
 * Plugin URI:        https://www.ctaflow.com/plugins/gateflow
 * Description:       Extend WordPress user management with expiration functionality for temporary user accounts. Set expiry dates, enable auto-deletion, and keep your user database clean.
 * Version:           1.0.1
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Ga Satrya
 * Author URI:        https://www.ctaflow.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       gateflow
 * Domain Path:       /languages
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manual PSR-4 Autoloader
 */
spl_autoload_register(
	function ( $class_name ) {
		$prefix   = 'GateFlow\\';
		$base_dir = __DIR__ . '/includes/';

		$len = strlen( $prefix );
		if ( 0 !== strncmp( $prefix, $class_name, $len ) ) {
			return;
		}

		$relative_class = substr( $class_name, $len );
		$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

/**
 * Initialize the plugin immediately.
 * This is necessary for activation hooks to work correctly.
 */
\GateFlow\Core::get_instance();
