<?php
/**
 * Settings class for Temporary User Access plugin.
 *
 * @package TempUsAc\Admin
 */

namespace TempUsAc\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use TempUsAc\Utils\Helpers;

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
			__( 'User Access Settings', 'temporary-user-access' ),
			__( 'Access Settings', 'temporary-user-access' ),
			'manage_options',
			'tempusac-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings, sections, and fields.
	 */
	public function register_settings(): void {
		register_setting(
			'tempusac_settings_group',
			'tempusac_grace_period',
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_grace_period' ),
				'default'           => TEMPUSAC_GRACE_PERIOD_DAYS,
			)
		);

		add_settings_section(
			'tempusac_auto_delete_section',
			__( 'Auto-deletion Settings', 'temporary-user-access' ),
			null,
			'tempusac-settings'
		);

		add_settings_field(
			'tempusac_grace_period',
			__( 'Grace Period (Days)', 'temporary-user-access' ),
			array( $this, 'render_grace_period_field' ),
			'tempusac-settings',
			'tempusac_auto_delete_section'
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
		<input type="number" name="tempusac_grace_period" value="<?php echo (int) $value; ?>" class="small-text" min="0" max="365" />
		<p class="description">
			<?php esc_html_e( 'Number of days to wait after a user account expires before it is automatically deleted.', 'temporary-user-access' ); ?>
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
				settings_fields( 'tempusac_settings_group' );
				do_settings_sections( 'tempusac-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
