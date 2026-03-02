<?php
/**
 * Settings class for ExpiryFlow plugin.
 *
 * @package ExpiryFlow\Admin
 */

namespace ExpiryFlow\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ExpiryFlow\Utils\Helpers;

/**
 * Settings class
 */
class Settings {

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
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Add settings sub-menu under Users.
	 */
	public function add_settings_page(): void {
		add_users_page(
			__( 'ExpiryFlow Settings', 'expiryflow' ),
			__( 'ExpiryFlow', 'expiryflow' ),
			'manage_options',
			'expiryflow-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings, sections, and fields.
	 */
	public function register_settings(): void {
		register_setting(
			'expiryflow_settings_group',
			'expiryflow_grace_period',
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_grace_period' ),
				'default'           => EXPIRYFLOW_GRACE_PERIOD_DAYS,
			)
		);

		add_settings_section(
			'expiryflow_auto_delete_section',
			__( 'Auto-deletion Settings', 'expiryflow' ),
			null,
			'expiryflow-settings'
		);

		add_settings_field(
			'expiryflow_grace_period',
			__( 'Grace Period (Days)', 'expiryflow' ),
			array( $this, 'render_grace_period_field' ),
			'expiryflow-settings',
			'expiryflow_auto_delete_section'
		);
	}

	/**
	 * Sanitize grace period input.
	 *
	 * @param mixed $input Input value.
	 * @return int
	 */
	public function sanitize_grace_period( $input ): int {
		$input = (int) $input;
		// Ensure minimum 0 days and maximum 365 days.
		if ( $input < 0 ) {
			return 0;
		}
		if ( $input > 365 ) {
			return 365;
		}
		return $input;
	}

	/**
	 * Render grace period input field.
	 */
	public function render_grace_period_field(): void {
		$value = Helpers::get_grace_period();
		?>
		<input type="number" name="expiryflow_grace_period" value="<?php echo (int) $value; ?>" class="small-text" min="0" max="365" />
		<p class="description">
			<?php esc_html_e( 'Number of days to wait after a user account expires before it is automatically deleted.', 'expiryflow' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'expiryflow_settings_group' );
				do_settings_sections( 'expiryflow-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
