<?php
/*
Plugin Name: Network Restricted Members
Version:     0.3
Description: Restrict user access to selected sites on open multisite networks.
Author:      Luís Rodrigues
Author URI:  http://log.pt/
Text Domain: network-restricted-members
License:     GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

/**
 * Main class for the Network Restricted Members plugin.
 */
class Network_Restricted_Members {

	/**
	 * Option for whether to restrict newly created users.
	 *
	 * @var string
	 */
	const OPTION_NEW_USERS = 'network_restricted_members_new_users';

	/**
	 * Option to restrict new users.
	 *
	 * @var string
	 */
	const OPTION_NEW_USERS_RESTRICT = 'restrict';

	/**
	 * Initialize plugin and add action and filter hooks.
	 */
	function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		/**
		 * Network setting hooks
		 */
		add_action( 'wpmu_options', array( $this, 'options' ), 0 );
		add_action( 'update_wpmu_options', array( $this, 'options_update' ), 0 );

		/**
		 * User creation hooks
		 */
		add_action( 'user_register', array( $this, 'user_register' ), 1 );

		/**
		 * User profile hooks
		 */
		add_action( 'show_user_profile', array( $this, 'user_profile' ) );
		add_action( 'edit_user_profile', array( $this, 'user_profile' ) );
		add_action( 'personal_options_update', array( $this, 'user_profile_update' ) );
		add_action( 'edit_user_profile_update', array( $this, 'user_profile_update' ) );

		/**
		 * Authentication checks
		 */
		add_action( 'template_redirect', array( $this, 'authenticate' ), 0 );
		add_action( 'bp_screens', array( $this, 'authenticate' ), 5 );
	}

	/**
	 * Prepare plugin for i18n.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'network-restricted-members', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Display network option to force new users to be restricted by default.
	 *
	 * @return [type] [description]
	 */
	public function options() {

		// If user is not a network admin, bail:
		if ( ! is_super_admin() ) {
			return;
		}

		?>
			<h3><?php _e( 'Network Member Restrictions', 'network-restricted-members' ); ?></h3>
			<table class="form-table">
			<tr id="<?php echo static::OPTION_NEW_USERS ?>">
				<th scope="row"><?php
					_e( 'Restrict new users', 'network-restricted-members' );
				?></th>
				<td>
					<label>
						<input type="checkbox"
							id="<?php echo static::OPTION_NEW_USERS ?>"
							name="<?php echo static::OPTION_NEW_USERS ?>"
							value="<?php echo static::OPTION_NEW_USERS_RESTRICT ?>"
							<?php checked( static::OPTION_NEW_USERS_RESTRICT, get_site_option( static::OPTION_NEW_USERS ) ) ?>
						>
						<?php _e( "New users will only be able to access sites they're invited to.", 'network-restricted-members' ); ?>
					</label>
				</td>
			</tr>
			</table>
		<?php

	}

	/**
	 * Save options.
	 *
	 * - `network_restricted_members_new_users`: Whether to restrict new users
	 *   by default
	 */
	public function options_update() {
		// If user is not a network admin, bail:
		if ( ! is_super_admin() ) {
			return;
		}

		$new_users = isset( $_POST[ static::OPTION_NEW_USERS ] )
			? $_POST[ static::OPTION_NEW_USERS ] : '';

		update_site_option( static::OPTION_NEW_USERS, $new_users );
	}

	/**
	 * Set the restriction flag on new user creation or registration.
	 *
	 * @param integer $user_id ID for the newly created user.
	 */
	public function user_register( $user_id ) {
		$new_users = get_site_option( static::OPTION_NEW_USERS );

		if ( $new_users !== static::OPTION_NEW_USERS_RESTRICT ) {
			return;
		}

		static::set_user_restricted( $user_id, true );
	}

	/**
	 * Display user profile option for network restriction.
	 *
	 * @param WP_User $user User object.
	 */
	public function user_profile( $user ) {

		// If user is not a network admin, bail:
		if ( ! is_super_admin() ) {
			return;
		}

		// If user being edited is a network admin, bail:
		if ( is_super_admin( $user->ID ) ) {
			return;
		}

		?>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><?php
						_e( 'Restrict User Access', 'network-restricted-members' );
					?></th>
					<td>
						<label>
							<input type="checkbox" name="network_restricted" id="network_restricted" value="1"
								<?php checked( 1, get_the_author_meta( 'network_restricted', $user->ID ) ) ?>>

								<?php _e( "Restrict this user's access to sites they're a member of", 'network-restricted-members' ) ?>
						</label>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Validate and set user profile option for network restriction.
	 *
	 * @param  integer $user_id User ID whose profile is being updated.
	 */
	public function user_profile_update( $user_id ) {

		// If user is not a network admin, bail:
		if ( ! is_super_admin() ) {
			return;
		}

		$restricted = isset( $_POST['network_restricted'] )
			? (boolean) $_POST['network_restricted'] : false;

		static::set_user_restricted( $user_id, $restricted );
	}

	/**
	 * Validates user access on template redirection requests.
	 */
	public function authenticate() {
		// Stop if already at the login page:
		if ( did_action( 'login_init' ) ) {
			return;
		}

		if ( is_user_logged_in() && ! is_network_admin() ) {
			if ( static::is_user_restricted() && ! is_user_member_of_blog() ) {
				$this->access_denied();
			}
		}
	}

	/**
	 * Display access denied message to a restricted user when they attempt to
	 * access a site they're not a member of.
	 */
	protected function access_denied() {

		// If not logged in or a network admin, bail:
		if ( ! is_user_logged_in() || is_network_admin() ) {
			return;
		}

		$blogs = get_blogs_of_user( get_current_user_id() );

		if ( wp_list_filter( $blogs, array( 'userblog_id' => get_current_blog_id() ) ) ) {
			return;
		}

		$blog_name = get_bloginfo( 'name' );

		$output  = '<p>' . sprintf( __( 'You attempted to access "%1$s", but you do not currently have privileges on this site. If you believe you should be able to access "%1$s", please contact your network administrator.', 'network-restricted-members' ), $blog_name ) . '</p>';
		$output .= '<p>' . __( 'If you reached this screen by accident and meant to visit one of your own sites, here are some shortcuts to help you find your way.', 'network-restricted-members' ) . '</p>';

		$output .= '<h3>' . __( 'Your Sites', 'network-restricted-members' ) . '</h3>';
		$output .= '<table>';

		foreach ( $blogs as $blog ) {
			$output .= '<tr>';
			$output .= "<td>{$blog->blogname}</td>";
			$output .= '<td><a href="' . esc_url( get_admin_url( $blog->userblog_id ) ) . '">' . __( 'Visit Dashboard', 'network-restricted-members' ) . '</a> | ' .
				'<a href="' . esc_url( get_home_url( $blog->userblog_id ) ). '">' . __( 'View Site', 'network-restricted-members' ) . '</a></td>';
			$output .= '</tr>';
		}

		$output .= '</table>';

		wp_die( $output );
	}

	/**
	 * Whether a user on the network is restricted.
	 *
	 * Inspired by the `is_user_spammy()` multisite core function.
	 *
	 * @param  integer  $user_id User ID (defaults to the current user ID).
	 * @return boolean           Whether the user is restricted on the network.
	 *
	 * @see is_user_spammy()
	 */
	public static function is_user_restricted( $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$restricted = $user_id ? get_the_author_meta( 'network_restricted', $user_id ) : false;

		return $user_id && ! empty( $restricted ) && $restricted == 1;
	}

	/**
	 * Set user restriction status.
	 *
	 * @param integer $user_id  ID of the user to restrict.
	 * @param boolean $restrict User is restricted (default is `true`).
	 */
	public static function set_user_restricted( $user_id, $restricted = true ) {
		update_user_meta( $user_id, 'network_restricted', $restricted ? 1 : 0 );
	}

}

add_action( 'plugins_loaded', function () {
	if ( is_multisite() ) {
		new Network_Restricted_Members();
	}
} );