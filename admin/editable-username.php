<?php
/**
 * Editable Username on Profile Page
 *
 * Allows administrators (or users editing their own profile)
 * to modify the `user_login` field from the WordPress profile screen.
 *
 * IMPORTANT:
 * - WordPress does not natively allow username changes.
 * - This snippet performs a direct database update.
 * - Use carefully in production environments.
 *
 * Tested up to: WordPress 6.x
 * Scope: Admin area only
 *
 * Usage:
 * Add to your theme's functions.php file
 * OR include in a custom functionality plugin.
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add editable username field to profile screen.
 */
function wpps_editable_username_field( $user ) {

    if ( ! current_user_can( 'edit_user', $user->ID ) ) {
        return;
    }
    ?>
    <h3>Account Username</h3>
    <table class="form-table" role="presentation">
        <tr>
            <th><label for="user_login">Username</label></th>
            <td>
                <input type="text"
                       name="user_login"
                       id="user_login"
                       value="<?php echo esc_attr( $user->user_login ); ?>"
                       class="regular-text" />
                <p class="description">
                    Change your login username.
                </p>
            </td>
        </tr>
    </table>
    <?php
}
add_action( 'show_user_profile', 'wpps_editable_username_field' );
add_action( 'edit_user_profile', 'wpps_editable_username_field' );

/**
 * Save the updated username.
 */
function wpps_save_editable_username( $user_id ) {

    if ( ! current_user_can( 'edit_user', $user_id ) ) {
        return false;
    }

    if ( empty( $_POST['user_login'] ) ) {
        return false;
    }

    $new_username = sanitize_user( wp_unslash( $_POST['user_login'] ), true );

    $user = get_userdata( $user_id );

    if ( $user && $user->user_login === $new_username ) {
        return true;
    }

    if ( username_exists( $new_username ) ) {
        return false;
    }

    global $wpdb;

    $wpdb->update(
        $wpdb->users,
        array( 'user_login' => $new_username ),
        array( 'ID' => $user_id ),
        array( '%s' ),
        array( '%d' )
    );

    clean_user_cache( $user_id );

    return true;
}
add_action( 'personal_options_update', 'wpps_save_editable_username' );
add_action( 'edit_user_profile_update', 'wpps_save_editable_username' );
