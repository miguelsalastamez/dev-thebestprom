<?php

namespace IGD;

defined( 'ABSPATH' ) || exit();

class Private_Folders {
	/**
	 * @var null
	 */
	protected static $instance = null;

	/**
	 * Get users data
	 *
	 * @return array
	 */
	public function get_user_data( $args = [] ) {
		$default = [
			'number'  => 999,
			'offset'  => 0,
			'role'    => '',
			'search'  => '',
			'order'   => 'asc',
			'orderby' => 'ID',
			'fields'  => 'all_with_meta',
		];

		$args        = wp_parse_args( $args, $default );
		$users_query = new \WP_User_Query( $args );

		$user_data = array_map( function ( $user ) {
			$folders = array_values(
				array_filter(
					(array) get_user_meta($user->ID, 'igd_folders', true),
					function($folder) {
						// Remove null or empty folders
						return is_array($folder) && !empty($folder['id']);
					}
				)
			);

			return [
				'id'       => $user->ID,
				'avatar'   => igd_get_user_gravatar( $user->ID ),
				'username' => $user->user_login,
				'name'     => $user->display_name,
				'email'    => $user->user_email,
				'role'     => implode( ', ', $this->get_role_list( $user ) ),
				'folders'  => $folders,
			];
		}, $users_query->get_results() );

		$avail_roles = count_users()['avail_roles'];

		// if no editor, contributor, author, subscriber roles, then add them
		$default_roles = [ 'administrator', 'editor', 'contributor', 'author', 'subscriber' ];
		foreach ( $default_roles as $role ) {
			if ( ! array_key_exists( $role, $avail_roles ) ) {
				$avail_roles[ $role ] = 0;
			}
		}

		return [
			'roles' => $avail_roles,
			'total' => $users_query->get_total(),
			'users' => array_values( $user_data ),
		];
	}

	/**
	 * Get user role list
	 *
	 * @param $user
	 *
	 * @return mixed|void
	 */
	public function get_role_list( $user ) {

		$wp_roles = wp_roles();

		$role_list = [];
		foreach ( $user->roles as $role ) {
			if ( isset( $wp_roles->role_names[ $role ] ) ) {
				$role_list[ $role ] = translate_user_role( $wp_roles->role_names[ $role ] );
			}
		}

		if ( empty( $role_list ) ) {
			$role_list['none'] = _x( 'None', 'No user roles', 'integrate-google-drive' );
		}

		return apply_filters( 'get_role_list', $role_list, $user );
	}

	public static function view() { ?>
        <div id="igd-private-folders-app"></div>
	<?php }

	/**
	 * @return Private_Folders|null
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}