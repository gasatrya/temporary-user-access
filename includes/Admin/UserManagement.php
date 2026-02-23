<?php
/**
 * User Management class for Temporary User Access plugin.
 *
 * @package TemporaryUserAccess\Admin
 */

namespace TemporaryUserAccess\Admin;

use TemporaryUserAccess\Utils\Helpers;
use TemporaryUserAccess\Auth\Authentication;
use DateTime;
use WP_User;
use WP_Error;

/**
 * UserManagement class
 */
class UserManagement {

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
		add_action( 'user_new_form', array( $this, 'add_user_registration_fields' ) );
		add_action( 'show_user_profile', array( $this, 'add_user_profile_fields' ) );
		add_action( 'edit_user_profile', array( $this, 'add_user_profile_fields' ) );

		add_filter( 'user_profile_update_errors', array( $this, 'validate_user_fields' ), 10, 3 );

		add_action( 'user_register', array( $this, 'save_user_fields' ) );
		add_action( 'profile_update', array( $this, 'save_user_fields' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		add_filter( 'manage_users_columns', array( $this, 'add_expiry_columns' ) );
		add_filter( 'manage_users_custom_column', array( $this, 'show_expiry_columns' ), 10, 3 );
		add_filter( 'manage_users_sortable_columns', array( $this, 'make_expiry_status_sortable' ) );
		add_action( 'pre_get_users', array( $this, 'handle_expiry_status_sorting' ) );
	}

	/**
	 * Add expiry fields to user registration form.
	 */
	public function add_user_registration_fields(): void {
		// Only show fields for users who can manage expiry settings.
		if ( ! Helpers::current_user_can_manage_expiry() ) {
			return;
		}

		?>
		<h3><?php esc_html_e( 'Account Expiry Settings', 'temporary-user-access' ); ?></h3>

		<table class="form-table">
			<?php wp_nonce_field( 'tempusac_save_user_expiry', 'tempusac_expiry_nonce' ); ?>

			<tr>
				<th><label for="user_expiry_date"><?php esc_html_e( 'Account Expiry Date', 'temporary-user-access' ); ?></label></th>
				<td>
					<input type="date" name="user_expiry_date" id="user_expiry_date" class="regular-text" />
					<p class="description"><?php esc_html_e( 'Optional: Set when this account should expire', 'temporary-user-access' ); ?></p>
				</td>
			</tr>

			<tr>
				<th><label for="user_auto_delete"><?php esc_html_e( 'Auto-delete Settings', 'temporary-user-access' ); ?></label></th>
				<td>
					<label for="user_auto_delete">
						<input type="checkbox" name="user_auto_delete" id="user_auto_delete" value="1" />
						<?php
						/* translators: %d: number of days for grace period */
						echo esc_html( sprintf( __( 'Auto-delete user after expiry (%d days grace period)', 'temporary-user-access' ), TEMPUSAC_GRACE_PERIOD_DAYS ) );
						?>
					</label>
					<p class="description">
						<?php esc_html_e( 'User account will be permanently deleted. Posts will be reassigned to admin.', 'temporary-user-access' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Add expiry fields to user profile edit form.
	 *
	 * @param WP_User $user User object.
	 */
	public function add_user_profile_fields( $user ): void {
		// Don't show fields for admin users or if current user can't manage expiry.
		if ( Helpers::is_user_admin( (int) $user->ID ) || ! Helpers::current_user_can_manage_expiry() ) {
			return;
		}

		$expiry_date = get_user_meta( $user->ID, TEMPUSAC_USER_EXPIRY_DATE, true );
		$auto_delete = get_user_meta( $user->ID, TEMPUSAC_USER_AUTO_DELETE, true );

		?>
		<h3><?php esc_html_e( 'Account Expiry Settings', 'temporary-user-access' ); ?></h3>

		<table class="form-table">
			<?php wp_nonce_field( 'tempusac_save_user_expiry', 'tempusac_expiry_nonce' ); ?>
			<tr>
				<th><label for="user_expiry_date"><?php esc_html_e( 'Account Expiry Date', 'temporary-user-access' ); ?></label></th>
				<td>
					<input type="date" name="user_expiry_date" id="user_expiry_date" class="regular-text" 
							value="<?php echo esc_attr( (string) $expiry_date ); ?>" />

					<?php if ( ! empty( $expiry_date ) ) : ?>
						<button type="button" id="user_expiry_clear_btn" class="button">
							<?php esc_html_e( 'Clear', 'temporary-user-access' ); ?>
						</button>
						<p>
							<label for="user_expiry_clear">
								<input type="checkbox" name="user_expiry_clear" id="user_expiry_clear" value="1" />
								<?php esc_html_e( 'Clear expiry date (make account permanent)', 'temporary-user-access' ); ?>
							</label>
						</p>
						<span id="user_expiry_clear_msg" class="screen-reader-text" aria-live="polite"></span>
					<?php endif; ?>
					<p class="description"><?php esc_html_e( 'Optional: Set when this account should expire', 'temporary-user-access' ); ?></p>
				</td>
			</tr>

			<tr>
				<th><label for="user_auto_delete"><?php esc_html_e( 'Auto-delete Settings', 'temporary-user-access' ); ?></label></th>
				<td>
					<label for="user_auto_delete">
						<input type="checkbox" name="user_auto_delete" id="user_auto_delete" value="1" 
								<?php checked( $auto_delete, '1' ); ?> />
						<?php
						/* translators: %d: number of days for grace period */
						echo esc_html( sprintf( __( 'Auto-delete user after expiry (%d days grace period)', 'temporary-user-access' ), TEMPUSAC_GRACE_PERIOD_DAYS ) );
						?>
					</label>
					<p class="description">
						<?php esc_html_e( 'User account will be permanently deleted. Posts will be reassigned to admin.', 'temporary-user-access' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Validate user expiry fields.
	 *
	 * @param WP_Error $errors  WP_Error object.
	 * @return WP_Error
	 */
	public function validate_user_fields( WP_Error $errors ): WP_Error {
		// Only validate if our field is present in the request.
		if ( ! isset( $_POST['user_expiry_date'] ) ) {
			return $errors;
		}

		// Check permissions first.
		if ( ! Helpers::current_user_can_manage_expiry() ) {
			return $errors;
		}

		// Verify nonce. Security check to prevent unauthorized access.
		if ( ! isset( $_POST['tempusac_expiry_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tempusac_expiry_nonce'] ) ), 'tempusac_save_user_expiry' ) ) {
			$errors->add( 'tempusac_security_fail', __( '<strong>Error</strong>: Security check failed. Please try again.', 'temporary-user-access' ) );
			return $errors;
		}

		// Validate expiry date format.
		$expiry_date = sanitize_text_field( wp_unslash( $_POST['user_expiry_date'] ) );
		if ( ! empty( $expiry_date ) && ! Helpers::validate_date( $expiry_date ) ) {
			$errors->add( 'invalid_expiry_date', __( '<strong>Error</strong>: Please enter a valid expiry date that is in the future.', 'temporary-user-access' ) );
		}

		return $errors;
	}

	/**
	 * Save user expiry fields.
	 *
	 * @param int $user_id User ID.
	 */
	public function save_user_fields( int $user_id ): void {
		// Only proceed if our fields are present in the request.
		if ( ! isset( $_POST['user_expiry_date'] ) && ! isset( $_POST['user_expiry_clear'] ) && ! isset( $_POST['user_auto_delete'] ) ) {
			return;
		}

		// Verify nonce. Essential for preventing CSRF attacks.
		if ( ! isset( $_POST['tempusac_expiry_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tempusac_expiry_nonce'] ) ), 'tempusac_save_user_expiry' ) ) {
			return;
		}

		// Check permissions. Nonce is not for authorization.
		if ( ! Helpers::current_user_can_manage_expiry() ) {
			return;
		}

		// Double-check specific permissions based on context.
		if ( doing_action( 'user_register' ) ) {
			if ( ! current_user_can( 'create_users' ) ) {
				return;
			}
		} elseif ( doing_action( 'profile_update' ) ) {
			if ( ! current_user_can( 'edit_user', $user_id ) ) {
				return;
			}
		}

		// For existing users, don't save if they're admins (extra safety layer).
		if ( $user_id > 0 && Helpers::is_user_admin( $user_id ) ) {
			return;
		}

		// Save expiry date.
		// If admin checked clear expiry, remove meta regardless of date input.
		if ( isset( $_POST['user_expiry_clear'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['user_expiry_clear'] ) ) ) {
			delete_user_meta( $user_id, TEMPUSAC_USER_EXPIRY_DATE );
			Helpers::log( 'User expiry cleared', array( 'user_id' => $user_id ) );
		} elseif ( isset( $_POST['user_expiry_date'] ) ) {
			$expiry_date = sanitize_text_field( wp_unslash( $_POST['user_expiry_date'] ) );
			if ( empty( $expiry_date ) ) {
				delete_user_meta( $user_id, TEMPUSAC_USER_EXPIRY_DATE );
			} else {
				update_user_meta( $user_id, TEMPUSAC_USER_EXPIRY_DATE, $expiry_date );
			}
		}

		// Save auto-delete setting.
		if ( isset( $_POST['user_auto_delete'] ) ) {
			update_user_meta( $user_id, TEMPUSAC_USER_AUTO_DELETE, '1' );
		} else {
			delete_user_meta( $user_id, TEMPUSAC_USER_AUTO_DELETE );
		}
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_admin_scripts( string $hook ): void {
		// List of user-related pages where we need our scripts/styles.
		$user_pages = array( 'user-new.php', 'user-edit.php', 'profile.php', 'users.php' );

		if ( ! in_array( $hook, $user_pages, true ) ) {
			return;
		}

		// Enqueue admin styles.
		wp_enqueue_style(
			'wp-tempusac-admin',
			plugins_url( 'assets/admin.css', TEMPUSAC_BASENAME ),
			array(),
			TEMPUSAC_VERSION
		);

		// Enqueue admin JavaScript for profile pages.
		if ( 'users.php' !== $hook ) {
			wp_enqueue_script(
				'wp-tempusac-admin',
				plugins_url( 'assets/admin.js', TEMPUSAC_BASENAME ),
				array( 'jquery' ),
				TEMPUSAC_VERSION,
				true
			);

			// Localize script to pass data from PHP to JavaScript.
			wp_localize_script(
				'wp-tempusac-admin',
				'tempusac_admin',
				array(
					'expiry_cleared_text' => __( 'Expiry cleared', 'temporary-user-access' ),
				)
			);
		}
	}

	/**
	 * Add Expiry columns to users list.
	 *
	 * @param array $columns Users list columns.
	 * @return array
	 */
	public function add_expiry_columns( array $columns ): array {
		$columns['expiry_status'] = __( 'Status', 'temporary-user-access' );
		$columns['expires']       = __( 'Expires', 'temporary-user-access' );
		return $columns;
	}

	/**
	 * Display Expiry columns content.
	 *
	 * @param string $value       Column value.
	 * @param string $column_name Column name.
	 * @param int    $user_id     User ID.
	 * @return string
	 */
	public function show_expiry_columns( string $value, string $column_name, int $user_id ): string {
		// Skip for admin users.
		if ( Helpers::is_user_admin( $user_id ) ) {
			if ( 'expiry_status' === $column_name ) {
				return '<span class="dashicons dashicons-shield" title="' . esc_attr__( 'Administrator accounts are exempt from expiry', 'temporary-user-access' ) . '"></span>';
			}
			if ( 'expires' === $column_name ) {
				return '—';
			}
			return $value;
		}

		$expiry_date = (string) get_user_meta( $user_id, TEMPUSAC_USER_EXPIRY_DATE, true );

		// Expiry Status column.
		if ( 'expiry_status' === $column_name ) {
			if ( ! empty( $expiry_date ) ) {
				$expiry_timestamp = Helpers::get_expiry_timestamp( $expiry_date );
				$current_time     = Helpers::get_current_timestamp();

				if ( $expiry_timestamp && $expiry_timestamp <= $current_time ) {
					return '<span class="expiry-status expired">' . esc_html__( 'Expired', 'temporary-user-access' ) . '</span>';
				}
			}

			return '<span class="expiry-status active">' . esc_html__( 'Active', 'temporary-user-access' ) . '</span>';
		}

		// Expires column.
		if ( 'expires' === $column_name ) {
			if ( empty( $expiry_date ) ) {
				return '—';
			}

			$expiry_timestamp = Helpers::get_expiry_timestamp( $expiry_date );
			$current_time     = Helpers::get_current_timestamp();

			if ( $expiry_timestamp && $expiry_timestamp <= $current_time ) {
				return '<span class="expiry-status expired">' . esc_html__( 'Expired', 'temporary-user-access' ) . '</span>';
			}

			// Calculate days difference using date comparison.
			$expiry_date_obj  = DateTime::createFromFormat( 'Y-m-d', $expiry_date, wp_timezone() );
			$current_date_obj = new DateTime( 'today', wp_timezone() );

			if ( $expiry_date_obj ) {
				$interval = $current_date_obj->diff( $expiry_date_obj );
				$days     = (int) $interval->format( '%r%a' );

				if ( 0 === $days ) {
					// Today.
					return esc_html__( 'Today', 'temporary-user-access' );
				}

				if ( 1 === $days ) {
					// Tomorrow.
					return esc_html__( 'Tomorrow', 'temporary-user-access' );
				}

				if ( $days > 1 ) {
					/* translators: %s: number of days */
					return esc_html( sprintf( _n( '%d day', '%d days', $days, 'temporary-user-access' ), $days ) );
				}
			}

			// Fallback to original calculation if date parsing fails.
			$time_diff = (int) $expiry_timestamp - $current_time;
			$days      = (int) floor( $time_diff / DAY_IN_SECONDS );

			/* translators: %s: number of days */
			return esc_html( sprintf( _n( '%d day', '%d days', $days, 'temporary-user-access' ), $days ) );
		}

		return $value;
	}

	/**
	 * Make Expiry Status column sortable.
	 *
	 * @param array $columns Sortable columns.
	 * @return array
	 */
	public function make_expiry_status_sortable( array $columns ): array {
		$columns['expiry_status'] = 'expiry_status';
		return $columns;
	}

	/**
	 * Handle Expiry Status column sorting.
	 *
	 * @param WP_User_Query $query User query object.
	 */
	public function handle_expiry_status_sorting( $query ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- URL parameters for sorting users table.
		if ( ! is_admin() || empty( $_REQUEST['orderby'] ) || 'expiry_status' !== sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- URL parameters for sorting users table.
		$order = isset( $_REQUEST['order'] ) && 'desc' === sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) ? 'DESC' : 'ASC';

		// Sort by expiry date meta (users without expiry will be ordered last).
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required for user sorting functionality.
		$query->query_vars['meta_query'] = array(
			'relation' => 'OR',
			array(
				'key'     => TEMPUSAC_USER_EXPIRY_DATE,
				'compare' => 'EXISTS',
			),
			array(
				'key'     => TEMPUSAC_USER_EXPIRY_DATE,
				'compare' => 'NOT EXISTS',
			),
		);

		$query->query_vars['orderby'] = 'meta_value';
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for user sorting functionality.
		$query->query_vars['meta_key'] = TEMPUSAC_USER_EXPIRY_DATE;
		$query->query_vars['order']    = $order;
	}
}
