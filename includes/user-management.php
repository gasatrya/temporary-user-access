<?php
/**
 * User Management functionality for Temporary User Access plugin
 *
 * @package TemporaryUserAccess
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add expiry fields to user registration form
 */
function tua_add_user_registration_fields() {
	// Only show fields for users who can manage expiry settings
	if ( ! tua_current_user_can_manage_expiry() ) {
		return;
	}

	?>
	<h3><?php esc_html_e( 'Account Expiry Settings', 'temporary-user-access' ); ?></h3>
	
	<table class="form-table">
		<?php wp_nonce_field( 'tua_create_user_expiry', 'tua_expiry_nonce' ); ?>

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
					echo esc_html( sprintf( __( 'Auto-delete user after expiry (%d days grace period)', 'temporary-user-access' ), TUA_GRACE_PERIOD_DAYS ) );
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
add_action( 'user_new_form', 'tua_add_user_registration_fields' );

/**
 * Add expiry fields to user profile edit form
 *
 * @param WP_User $user User object.
 */
function tua_add_user_profile_fields( $user ) {
	// Don't show fields for admin users or if current user can't manage expiry
	if ( tua_is_user_admin( $user->ID ) || ! tua_current_user_can_manage_expiry() ) {
		return;
	}

	$expiry_date = get_user_meta( $user->ID, TUA_USER_EXPIRY_DATE, true );
	$auto_delete = get_user_meta( $user->ID, TUA_USER_AUTO_DELETE, true );

	?>
	<h3><?php esc_html_e( 'Account Expiry Settings', 'temporary-user-access' ); ?></h3>
	
	<table class="form-table">
		<tr>
			<th><label for="user_expiry_date"><?php esc_html_e( 'Account Expiry Date', 'temporary-user-access' ); ?></label></th>
			<td>
				<input type="date" name="user_expiry_date" id="user_expiry_date" class="regular-text" 
						value="<?php echo esc_attr( $expiry_date ); ?>" />

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
		
		<!-- Account status is derived from expiry date; no manual setting -->
		
		<tr>
			<th><label for="user_auto_delete"><?php esc_html_e( 'Auto-delete Settings', 'temporary-user-access' ); ?></label></th>
			<td>
				<label for="user_auto_delete">
					<input type="checkbox" name="user_auto_delete" id="user_auto_delete" value="1" 
							<?php checked( $auto_delete, '1' ); ?> />
					<?php
					/* translators: %d: number of days for grace period */
					echo esc_html( sprintf( __( 'Auto-delete user after expiry (%d days grace period)', 'temporary-user-access' ), TUA_GRACE_PERIOD_DAYS ) );
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
add_action( 'show_user_profile', 'tua_add_user_profile_fields' );
add_action( 'edit_user_profile', 'tua_add_user_profile_fields' );

/**
 * Validate user expiry fields
 *
 * @param WP_Error $errors WP_Error object.
 * @param bool     $update Whether updating existing user.
 * @param stdClass $user User object.
 * @return WP_Error
 */
function tua_validate_user_fields( $errors, $update, $user ) {
	// Only validate if user can manage expiry settings
	if ( ! tua_current_user_can_manage_expiry() ) {
		return $errors;
	}

	// Verify nonce based on context
	$nonce_verified = false;

	if ( $update && isset( $user->ID ) ) {
		// Updating existing user - verify WordPress's built-in nonce
		if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'update-user_' . $user->ID ) ) {
			$nonce_verified = true;
		}
	} elseif ( ! $update ) {
		// Creating new user - verify custom nonce
		if ( isset( $_POST['tua_expiry_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tua_expiry_nonce'] ) ), 'tua_create_user_expiry' ) ) {
			$nonce_verified = true;
		}
	}

	// If nonce not verified, don't validate (WordPress core will handle the form security)
	if ( ! $nonce_verified ) {
		return $errors;
	}

	// Validate expiry date format if provided
	if ( ! empty( $_POST['user_expiry_date'] ) ) {
		$expiry_date = sanitize_text_field( wp_unslash( $_POST['user_expiry_date'] ) );
		if ( ! tua_validate_date( $expiry_date ) ) {
			$errors->add( 'invalid_expiry_date', __( 'Please enter a valid expiry date that is in the future.', 'temporary-user-access' ) );
		}
	}

	return $errors;
}
add_filter( 'user_profile_update_errors', 'tua_validate_user_fields', 10, 3 );

/**
 * Save user expiry fields
 *
 * @param int $user_id User ID.
 */
function tua_save_user_fields( $user_id ) {

	// Verify nonce based on context
	$nonce_verified = false;

	if ( doing_action( 'user_register' ) ) {
		// New user creation - verify custom nonce
		if ( isset( $_POST['tua_expiry_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tua_expiry_nonce'] ) ), 'tua_create_user_expiry' ) ) {
			$nonce_verified = true;
		}
		// Check create users permission
		if ( ! current_user_can( 'create_users' ) ) {
			return;
		}
	} elseif ( doing_action( 'profile_update' ) ) {
		// Profile update - verify WordPress's built-in nonce
		if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'update-user_' . $user_id ) ) {
			$nonce_verified = true;
		}
		// Check edit user permission
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}
	}

	// If nonce not verified, don't save
	if ( ! $nonce_verified ) {
		return;
	}

	// Only save if current user can manage expiry settings
	if ( ! tua_current_user_can_manage_expiry() ) {
		return;
	}

	// For existing users, don't save if they're admins
	if ( $user_id > 0 && tua_is_user_admin( $user_id ) ) {
		return;
	}

	// Save expiry date
	// If admin checked clear expiry, remove meta regardless of date input
	if ( isset( $_POST['user_expiry_clear'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['user_expiry_clear'] ) ) ) {
		delete_user_meta( $user_id, TUA_USER_EXPIRY_DATE );
		tua_log( 'User expiry cleared', array( 'user_id' => $user_id ) );
	} elseif ( isset( $_POST['user_expiry_date'] ) ) {
		$expiry_date = sanitize_text_field( wp_unslash( $_POST['user_expiry_date'] ) );
		if ( empty( $expiry_date ) ) {
			delete_user_meta( $user_id, TUA_USER_EXPIRY_DATE );
		} else {
			update_user_meta( $user_id, TUA_USER_EXPIRY_DATE, $expiry_date );
		}
	}

	// Save auto-delete setting
	if ( isset( $_POST['user_auto_delete'] ) ) {
		update_user_meta( $user_id, TUA_USER_AUTO_DELETE, '1' );
	} else {
		delete_user_meta( $user_id, TUA_USER_AUTO_DELETE );
	}
}
add_action( 'user_register', 'tua_save_user_fields' );
add_action( 'profile_update', 'tua_save_user_fields' );

/**
 * Enqueue admin scripts and styles
 *
 * @param string $hook The current admin page hook.
 */
function tua_enqueue_admin_scripts( $hook ) {
	if ( 'user-new.php' === $hook || 'user-edit.php' === $hook || 'profile.php' === $hook ) {
		// Enqueue our admin JavaScript
		wp_enqueue_script(
			'wp-tua-admin',
			plugins_url( 'assets/admin.js', __DIR__ ),
			array( 'jquery' ),
			TUA_VERSION,
			true
		);

		// Localize script to pass data from PHP to JavaScript
		wp_localize_script(
			'wp-tua-admin',
			'tua_admin',
			array(
				'expiry_cleared_text' => __( 'Expiry cleared', 'temporary-user-access' ),
			)
		);
	}
}
add_action( 'admin_enqueue_scripts', 'tua_enqueue_admin_scripts' );

/**
 * Add Expiry columns to users list
 *
 * @param array $columns Users list columns.
 * @return array
 */
function tua_add_expiry_columns( $columns ) {
	$columns['expiry_status'] = __( 'Status', 'temporary-user-access' );
	$columns['expires']       = __( 'Expires', 'temporary-user-access' );
	return $columns;
}
add_filter( 'manage_users_columns', 'tua_add_expiry_columns' );

/**
 * Display Expiry columns content
 *
 * @param string $value Column value.
 * @param string $column_name Column name.
 * @param int    $user_id User ID.
 * @return string
 */
function tua_show_expiry_columns( $value, $column_name, $user_id ) {
	// Skip for admin users
	if ( tua_is_user_admin( $user_id ) ) {
		if ( 'expiry_status' === $column_name ) {
			return '<span class="dashicons dashicons-shield" title="' . esc_attr__( 'Administrator accounts are exempt from expiry', 'temporary-user-access' ) . '"></span>';
		}
		if ( 'expires' === $column_name ) {
			return '—';
		}
		return $value;
	}

	$expiry_date = get_user_meta( $user_id, TUA_USER_EXPIRY_DATE, true );

	// Expiry Status column
	if ( 'expiry_status' === $column_name ) {
		if ( ! empty( $expiry_date ) ) {
			$expiry_timestamp = tua_get_expiry_timestamp( $expiry_date );
			$current_time     = tua_get_current_timestamp();

			if ( $expiry_timestamp && $expiry_timestamp <= $current_time ) {
				return '<span class="expiry-status expired" style="color: #dc3232; font-weight: bold;">' . esc_html__( 'Expired', 'temporary-user-access' ) . '</span>';
			}
		}

		return '<span class="expiry-status active" style="color: #46b450;">' . esc_html__( 'Active', 'temporary-user-access' ) . '</span>';
	}

	// Expires column
	if ( 'expires' === $column_name ) {
		if ( empty( $expiry_date ) ) {
			return '—';
		}

		$expiry_timestamp = tua_get_expiry_timestamp( $expiry_date );
		$current_time     = tua_get_current_timestamp();

		if ( $expiry_timestamp && $expiry_timestamp <= $current_time ) {
			return '<span style="color: #dc3232; font-weight: bold;">' . esc_html__( 'Expired', 'temporary-user-access' ) . '</span>';
		}

		// Calculate days difference using date comparison
		$expiry_date_obj  = DateTime::createFromFormat( 'Y-m-d', $expiry_date, wp_timezone() );
		$current_date_obj = new DateTime( 'today', wp_timezone() );

		if ( $expiry_date_obj ) {
			$interval = $current_date_obj->diff( $expiry_date_obj );
			$days     = (int) $interval->format( '%r%a' );

			if ( 0 === $days ) {
				// Today
				return esc_html__( 'Today', 'temporary-user-access' );
			}

			if ( 1 === $days ) {
				// Tomorrow
				return esc_html__( 'Tomorrow', 'temporary-user-access' );
			}

			if ( $days > 1 ) {
				/* translators: %s: number of days */
				return esc_html( sprintf( _n( '%d day', '%d days', $days, 'temporary-user-access' ), $days ) );
			}
		}

		// Fallback to original calculation if date parsing fails
		$time_diff = $expiry_timestamp - $current_time;
		$days      = floor( $time_diff / DAY_IN_SECONDS );

		/* translators: %s: number of days */
		return esc_html( sprintf( _n( '%d day', '%d days', $days, 'temporary-user-access' ), $days ) );
	}

	return $value;
}
add_filter( 'manage_users_custom_column', 'tua_show_expiry_columns', 10, 3 );

/**
 * Make Expiry Status column sortable
 *
 * @param array $columns Sortable columns.
 * @return array
 */
function tua_make_expiry_status_sortable( $columns ) {
	$columns['expiry_status'] = 'expiry_status';
	return $columns;
}
add_filter( 'manage_users_sortable_columns', 'tua_make_expiry_status_sortable' );

/**
 * Handle Expiry Status column sorting
 *
 * @param WP_User_Query $query User query object.
 */
function tua_handle_expiry_status_sorting( $query ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- URL parameters for sorting users table
	if ( ! is_admin() || empty( $_REQUEST['orderby'] ) || 'expiry_status' !== sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- URL parameters for sorting users table
	$order = isset( $_REQUEST['order'] ) && 'desc' === sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) ? 'DESC' : 'ASC';

	// Sort by expiry date meta (users without expiry will be ordered last)
	// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required for user sorting functionality
	$query->query_vars['meta_query'] = array(
		'relation' => 'OR',
		array(
			'key'     => TUA_USER_EXPIRY_DATE,
			'compare' => 'EXISTS',
		),
		array(
			'key'     => TUA_USER_EXPIRY_DATE,
			'compare' => 'NOT EXISTS',
		),
	);

	$query->query_vars['orderby'] = 'meta_value';
	// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for user sorting functionality
	$query->query_vars['meta_key'] = TUA_USER_EXPIRY_DATE;
	$query->query_vars['order']    = $order;
}
add_action( 'pre_get_users', 'tua_handle_expiry_status_sorting' );

/**
 * Add CSS for users list table styling
 */
function tua_users_list_styles() {
	echo '<style>
        .expiry-status.expired { color: #dc3232; font-weight: bold; }
        .expiry-status.active { color: #46b450; }
        .column-expiry_status { width: 120px; }
        .column-expires { width: 100px; }
    </style>';
}
add_action( 'admin_head-users.php', 'tua_users_list_styles' );

?>
