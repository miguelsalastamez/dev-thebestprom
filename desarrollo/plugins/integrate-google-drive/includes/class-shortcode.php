<?php

namespace IGD;

defined( 'ABSPATH' ) || exit();

/**
 * Class Shortcode
 *
 * Handles rendering of various shortcode modules (browser, gallery, media, etc).
 * Manages permissions, access control, authentication, and module-specific logic.
 *
 * @package IGD
 */
class Shortcode {
	/**
	 * Singleton instance
	 *
	 * @var Shortcode|null
	 */
	protected static $instance = null;

	/**
	 * Current module type (browser, gallery, media, etc)
	 *
	 * @var string|null
	 */
	private $type = null;

	/**
	 * Shortcode configuration data
	 *
	 * @var array
	 */
	private $data;

	/**
	 * Static reference to current shortcode
	 *
	 * @var mixed
	 */
	private static $current_shortcode;

	/**
	 * Current user object (when logged in)
	 *
	 * @var \WP_User|null
	 */
	private $current_user = null;

	/**
	 * Constructor - registers shortcode
	 */
	public function __construct() {
		add_shortcode( 'integrate_google_drive', [ $this, 'render_shortcode' ] );
	}

	/**
	 * Render the shortcode output.
	 *
	 * @param array $atts     Shortcode attributes (id or data).
	 * @param mixed $data     Optional shortcode data (if passed directly).
	 *
	 * @return string The rendered shortcode HTML or empty string if not available.
	 */
	public function render_shortcode( $atts = [], $data = null ) {
		$this->fetch_data( $atts, $data );

		// Return early if shortcode data is not found
		if ( empty( $this->data ) ) {
			return '';
		}

		// Check if shortcode is enabled
		if ( ! $this->check_status() ) {
			return '';
		}

		// Set up security and current user
		$this->setup_security();

		// Enqueue frontend scripts
		$this->enqueue_scripts();

		// Check access permissions and display appropriate screen if needed
		$access_screen = $this->get_access_screen();
		if ( null !== $access_screen ) {
			return $access_screen;
		}

		// Apply module configuration and render
		$this->apply_module_configuration();

		return $this->generate_html();
	}

	/**
	 * Set up security measures like nonce and current user.
	 */
	private function setup_security() {
		if ( ! is_user_logged_in() ) {
			$this->data['nonce'] = wp_create_nonce( 'igd-shortcode-nonce' );
		} else {
			$this->current_user = wp_get_current_user();
		}
	}

	/**
	 * Get access denial or login screen if needed.
	 *
	 * @return string|null The access screen HTML or null if access granted.
	 */
	private function get_access_screen() {
		// Check if user should have access
		if ( $this->check_should_show()  ) {
			return null; // Access granted
		}

		// Display login screen for non-logged-in users
		if ( ! is_user_logged_in() && ( ! isset( $this->data['displayLogin'] ) || ! empty( $this->data['displayLogin'] ) ) ) {
			return $this->get_login_screen();
		}

		// Display access denied message
		if ( ! isset( $this->data['showAccessDeniedMessage'] ) || ! empty( $this->data['showAccessDeniedMessage'] ) ) {
			return $this->get_access_denied_message();
		}

		return null; // No screen to display
	}


	/**
	 * Fetch shortcode data from attributes or database.
	 *
	 * @param array $atts Shortcode attributes.
	 * @param mixed $data Optional pre-provided data.
	 */
	private function fetch_data( $atts, $data ) {
		// Use provided data or retrieve from attributes
		if ( empty( $data ) ) {
			$data = $this->get_data_from_attributes( $atts );
		}

		$this->type = $data['type'] ?? '';
		$this->data = $data;
	}

	/**
	 * Extract shortcode data from attributes.
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return array|null The decoded/retrieved shortcode data.
	 */
	private function get_data_from_attributes( $atts ) {
		// Check for encoded data attribute
		if ( ! empty( $atts['data'] ) ) {
			return json_decode( base64_decode( $atts['data'], true ), true );
		}

		// Check for shortcode ID attribute
		if ( empty( $atts['id'] ) ) {
			return null;
		}

		$id = intval( $atts['id'] );
		if ( $id <= 0 ) {
			return null;
		}

		// Fetch shortcode configuration from database
		$shortcode = $this->get_shortcode( $id );
		return ! empty( $shortcode ) ? $shortcode['config'] : null;
	}

    private function apply_module_configuration() {

        $this->set_permissions();

        $this->get_initial_search_term();

        $this->set_filters();

        $this->set_notifications();

        $this->process_files();

        // Filter files if necessary.
        $this->check_filters();

        $this->set_account();

        // Set shortcode id, nonce and transient
        $this->set_shortcode_transient();

    }

    private function set_shortcode_transient() {

        // Set ID
        if ( empty( $this->data['id'] ) ) {
            $this->data['id'] = $this->data['uniqueId'] ?? 'igd_' . md5( maybe_serialize( $this->data ) );
        }

        // Set transient
        $transient_key = 'shortcode_' . $this->data['id'];
        if ( ! get_transient( $transient_key ) ) {
            set_transient( $transient_key, $this->data, 7 * DAY_IN_SECONDS );
        }
    }

	/**
	 * Check if shortcode is enabled.
	 *
	 * @return bool True if shortcode is enabled, false otherwise.
	 */
	private function check_status() {
		$status = $this->data['status'] ?? 'on';
		return 'off' !== $status;
	}

	/**
	 * Get the access denied message HTML.
	 *
	 * @return string The HTML markup for access denied message.
	 */
	private function get_access_denied_message() {
		// Get custom message from settings or use default
		$title       = esc_html__( 'Access Denied', 'integrate-google-drive' );
		$description = esc_html__( "We're sorry, but your account does not currently have access to this content. To gain access, please contact the site administrator who can assist in linking your account to the appropriate content. Thank you.", 'integrate-google-drive' );
		$default_message = sprintf( '<h3>%s</h3><p>%s</p>', $title, $description );

		$access_denied_message = igd_get_settings( 'accessDeniedMessage', $default_message );
		$access_denied_message = wp_kses_post( $access_denied_message );

		return sprintf( '<div class="igd-access-denied-placeholder">%s</div>', $access_denied_message );
	}

	/**
	 * Get the login screen HTML.
	 *
	 * @return string The HTML markup for login screen.
	 */
	public function get_login_screen() {
		// Set up login form arguments
		$login_form_args = [
			'echo'           => false,
			'label_username' => esc_html__( 'Username or Email', 'integrate-google-drive' ),
			'label_password' => esc_html__( 'Password', 'integrate-google-drive' ),
			'label_remember' => esc_html__( 'Remember Me', 'integrate-google-drive' ),
			'label_log_in'   => esc_html__( 'Log In', 'integrate-google-drive' ),
			'form_id'        => 'igd-login-form',
			'id_username'    => 'igd-user-login',
			'id_password'    => 'igd-user-pass',
			'id_remember'    => 'igd-remember-me',
			'id_submit'      => 'igd-login-submit',
		];

		$login_form = wp_login_form( $login_form_args );
		$title      = esc_html__( 'Login Required!', 'integrate-google-drive' );
		$description = esc_html__( 'Please log in to access this module.', 'integrate-google-drive' );
		$default_message = sprintf( '<h3>%s</h3><p>%s</p>', $title, $description );

		// Get login settings
		$login_type    = igd_get_settings( 'loginType', 'form' );
		$login_url     = igd_get_settings( 'loginUrl', wp_login_url( $_SERVER['REQUEST_URI'] ) );
		$login_message = igd_get_settings( 'loginMessage', $default_message );

		// Use redirect link if login type is set to redirect
		if ( 'redirect' === $login_type ) {
			$login_form = '<a href="' . esc_url( $login_url ) . '" class="igd-btn btn-primary igd-login-link">' . esc_html__( 'Login', 'integrate-google-drive' ) . '</a>';
		}

		// Build HTML markup
		$html  = '<div class="igd-login-screen" data-shortcode_id="' . esc_attr( $this->data['id'] ?? '' ) . '">';
		$html .= wp_kses_post( $login_message );
		$html .= $login_form;
		$html .= '<p class="igd-form-error"></p>';
		$html .= '</div>';

		return $html;
	}

    private function set_permissions() {

        // Check file actions Permissions
        if ( in_array( $this->type, [ 'browser', 'gallery', 'media', 'search', 'slider' ] ) ) {

            // Preview
            $this->data['preview'] = ( ! isset( $this->data['preview'] ) || ( ! empty( $this->data['preview'] ) ) ) && $this->check_permission( 'preview' );

            // Download
            $this->data['download'] = ( ! isset( $this->data['download'] ) || ( ! empty( $this->data['download'] ) )) && $this->check_permission( 'download' ) ;

            // Delete
            $this->data['canDelete'] = ! empty( $this->data['canDelete'] ) && $this->check_permission( 'canDelete' );

            // Rename
            $this->data['rename'] = ! empty( $this->data['rename'] ) && $this->check_permission( 'rename' );

            // Upload
            $this->data['upload'] = ( ! empty( $this->data['upload'] ) || ( ! isset( $this->data['upload'] )) && ! empty( $this->data['isFormUploader'] ) ) && $this->check_permission( 'upload' );

            // New Folder
            $this->data['newFolder'] = ! empty( $this->data['newFolder'] ) && $this->check_permission( 'newFolder' );

            // moveCopy
            $this->data['moveCopy'] = ! empty( $this->data['moveCopy'] ) && $this->check_permission( 'moveCopy' );

            // Share
            $this->data['allowShare'] = ! empty( $this->data['allowShare'] ) && $this->check_permission( 'allowShare' );

            // Search
            $this->data['allowSearch'] = ( 'search' == $this->type || ( ! empty( $this->data['allowSearch'] ) ) && $this->check_permission( 'allowSearch' ) );

            // Create
            $this->data['createDoc'] = ! empty( $this->data['createDoc'] ) && $this->check_permission( 'createDoc' );

            // Edit
            $this->data['edit'] = ! empty( $this->data['edit'] ) && $this->check_permission( 'edit' );

            // Direct Link
            $this->data['copyLink'] = ! empty( $this->data['copyLink'] ) && $this->check_permission( 'copyLink' );

            // Details
            $this->data['details'] = ! empty( $this->data['details'] ) && $this->check_permission( 'details' );

            // Details
            $this->data['comment'] = ! empty( $this->data['comment'] ) && $this->check_permission( 'comment' );

            // photoProof
            $this->data['photoProof'] = ! empty( $this->data['photoProof'] ) && $this->check_permission( 'photoProof' );
        }

    }

	/**
	 * Check if user has specific permission.
	 *
	 * @param string $permission_type The permission type to check.
	 *
	 * @return bool True if user has permission, false otherwise.
	 */
	private function check_permission( $permission_type ) {
		$type_user_key_map = [
			'preview'     => 'previewUsers',
			'download'    => 'downloadUsers',
			'upload'      => 'uploadUsers',
			'allowShare'  => 'shareUsers',
			'createDoc'   => 'createDocumentUsers',
			'edit'        => 'editUsers',
			'copyLink'    => 'copyLinkUsers',
			'details'     => 'detailsUsers',
			'allowSearch' => 'searchUsers',
			'canDelete'   => 'deleteUsers',
			'rename'      => 'renameUsers',
			'moveCopy'    => 'moveCopyUsers',
			'newFolder'   => 'newFolderUsers',
			'comment'     => 'commentUsers',
			'photoProof'  => 'photoProofUsers',
		];

		$user_key = $type_user_key_map[ $permission_type ] ?? null;
		$users    = ( $user_key && isset( $this->data[ $user_key ] ) ) ? $this->data[ $user_key ] : [ 'everyone' ];

		// Everyone has permission by default
		if ( in_array( 'everyone', $users, true ) ) {
			return true;
		}

		// Check if current user has permission
		if ( ! $this->current_user ) {
			return false;
		}

		// Check if user's role matches
		if ( ! empty( array_intersect( $this->current_user->roles, $users ) ) ) {
			return true;
		}

		// Check if current user ID is in allowed list
		if ( in_array( $this->current_user->ID, $users, true ) ) {
			return true;
		}

		return false;
	}

    private function get_initial_search_term() {
        if ( ! empty( $this->data['allowSearch'] ) && ! empty( $this->data['initialSearchTerm'] ) && strpos( $this->data['initialSearchTerm'], '%' ) !== false ) {

            $search_template = $this->data['initialSearchTerm'];
            $tag_args        = [
                    'name' => $search_template,
            ];

            // Add user data
            if ( igd_contains_tags( 'user', $search_template ) ) {
                if ( $this->current_user ) {
                    $tag_args['user'] = get_userdata( get_current_user_id() );
                }
            }

            // Add the current post to the args
            if ( igd_contains_tags( 'post', $search_template ) ) {
                global $post;
                if ( ! empty( $post ) ) {
                    $tag_args['post'] = $post;

                    // if post is a product get the product
                    if ( $post->post_type == 'product' ) {
                        $product = wc_get_product( $post->ID );
                        if ( ! empty( $product ) ) {
                            $tag_args['wc_product'] = $product;
                        }
                    }

                }
            }

            $this->data['initialSearchTerm'] = igd_replace_template_tags( $tag_args );

        }

    }

    private function process_files() {

        // Check ACF dynamic field files
        if ( ! empty( $this->data['acfDynamicFiles'] ) ) {
            $this->data['folders'] = ! empty( $this->data['acfFieldKey'] ) ? array_values( $this->get_acf_dynamic_field_files( $this->data['acfFieldKey'] ) ) : [];
        }

        // Check if uploader module and selected folder is enabled
        if ( 'uploader' == $this->type && ! empty( $this->data['uploadFolderSelection'] ) ) {
            if ( empty( $this->data['folders'] ) ) {
                $this->data['folders'] = array_values( $this->data['uploadFolders'] ) ?? [];
            }
        }

        // First, we check if the 'type' is one of the specified values and 'folders' is not empty.
        if ( ! in_array( $this->type, [
                        'browser',
                        'gallery',
                        'media',
                        'slider',
                        'embed',
                        'list',
                        'review',
                ] ) || empty( $this->data['folders'] ) ) {
            return;
        }

        // Process based on whether there is a single folder or multiple.
        $is_single_folder = 'list' != $this->type && count( $this->data['folders'] ) == 1 && igd_is_dir( reset( $this->data['folders'] ) );

        if ( $is_single_folder ) {
            $this->process_single_folder();
        } else {
            $this->get_files_from_server();
        }

        // If the type is 'slider' and the user can use premium code, process the files accordingly.
        if ( $this->type == 'slider' ) {
            $this->get_slider_files();
        }

        // If list module, and folderFiles is enabled get the files from the folders.
        if ( $this->type == 'list' && ! empty( $this->data['folderFiles'] ) ) {
            $files = [];

            foreach ( $this->data['folders'] as $item ) {
                if ( igd_is_dir( $item ) ) {
                    // Merge files from folder into $files array if it's a directory.
                    $files = array_merge( $files, igd_get_child_items( $item ) );
                } else {
                    // Otherwise, just add the single file.
                    $files[] = $item;
                }
            }

            $this->data['folders'] = array_values( $files );
        }

    }

    private function get_acf_dynamic_field_files( $field_key ) {
        // Return early if the field key is empty or the 'get_field' function does not exist
        if ( empty( $field_key ) || ! function_exists( 'get_field' ) ) {
            return [];
        }

        // Retrieve the files using the field key
        $files = get_field( $field_key );

        // If files are not empty, process each file
        if ( ! empty( $files ) && is_array( $files ) ) {
            $files = array_map( function ( $file ) {
                if ( isset( $file['account_id'] ) ) {
                    // Rename the 'account_id' key to 'accountId'
                    $file['accountId']   = $file['account_id'];
                    $file['webViewLink'] = $file['view_link'];
                }

                return $file;
            }, $files );
        }

        return $files ?: []; // Ensure the function always returns an array
    }

    private function get_slider_files() {

        $files = [];

        foreach ( $this->data['folders'] as $key => $folder ) {
            if ( igd_is_dir( $folder ) ) {
                // Merge files from folder into $files array if it's a directory.
                $files = array_merge( $files, igd_get_all_child_files( $folder ) );
            } else {
                // Otherwise, just add the single file.
                $files[] = $folder;
            }
        }

        // Filter the $files array to exclude directories and files without 'thumbnailLink'.
        $filtered_files = array_filter( $files, function ( $file ) {
            return ! igd_is_dir( $file ) && ! empty( $file['thumbnailLink'] );
        } );

        // Merge and re-index the folders with the filtered files.
        $this->data['folders'] = array_values( $filtered_files );
    }

    private function process_single_folder() {
        $folder = reset( $this->data['folders'] );

        $this->data['initParentFolder'] = $folder;

        if ( is_array( $folder ) ) {
            $folder_id = $folder['id'];

            $args = [
                    'folder'      => $folder,
                    'fileNumbers' => ! empty( $this->data['fileNumbers'] ) ? $this->data['fileNumbers'] : - 1,
                    'filters'     => ! empty( $this->data['filters'] ) ? $this->data['filters'] : [],
            ];

            if ( ! empty( $this->data['sort'] ) ) {
                $args['sort'] = $this->data['sort'];
            }

            // lazy load items
            if ( in_array( $this->type, [ 'browser', 'gallery','review', ] ) ) {
                if ( ! isset( $this->data['lazyLoad'] ) || ! empty( $this->data['lazyLoad'] ) ) {
                    $args['limit'] = ! empty( $this->data['lazyLoadNumber'] ) ? $this->data['lazyLoadNumber'] : 100;
                }
            }

            // Fetch files
            $account_id = ! empty( $folder['accountId'] ) ? $folder['accountId'] : '';
            $files_data = App::instance( $account_id )->get_files( $args );

            if ( isset( $files_data['files'] ) ) {
                $this->data['folders'] = array_values( $files_data['files'] );
            }

            // Update the arguments for the next iteration
            $should_update_page_number = ! empty( $this->data['lazyLoad'] ) && ! empty( $this->data['lazyLoadType'] ) && 'pagination' != $this->data['lazyLoadType'];
            $page_number               = $should_update_page_number ? ( $files_data['nextPageNumber'] ?? 0 ) : 1;

            $this->data['initParentFolder']['pageNumber'] = $page_number;

            if ( ! empty( $files_data['count'] ) ) {
                $this->data['initParentFolder']['count'] = $files_data['count'];
            }

        }

    }

    private function get_files_from_server() {
        $folders = $this->data['folders'] ?? [];
        if ( empty( $folders ) || ! is_array( $folders ) ) {
            return;
        }

        $cache_key = 'igd_latest_fetch_' . md5( maybe_serialize( $folders ) );

        // Fetch files from server if cache expired
        if ( ! get_transient( $cache_key ) ) {
            set_transient( $cache_key, true, HOUR_IN_SECONDS );

            $first_folder = reset( $folders );
            $account_id   = $first_folder['accountId'] ?? null;
            if ( ! $account_id ) {
                error_log( 'IGD ERROR: Missing account ID in folder data.' );

                return;
            }

            $app     = App::instance( $account_id );
            $client  = $app->client;
            $service = $app->getService();

            $client->setUseBatch( true );
            $batch = new \IGDGoogle_Http_Batch( $client );

            foreach ( $folders as $key => $folder ) {
                if ( empty( $folder['id'] ) ) {
                    unset( $folders[ $key ] );
                    continue;
                }

                try {
                    $request = ! empty( $folder['shared-drives'] )
                            ? $service->drives->get( $folder['id'], [ 'fields' => '*' ] )
                            : $service->files->get( $folder['id'], [
                                    'supportsAllDrives' => true,
                                    'fields'            => $app->file_fields,
                            ] );
                    $batch->add( $request, (string) $key );
                } catch ( \Exception $e ) {
                    error_log( 'IGD SDK ERROR (Request): ' . $e->getMessage() );
                    continue;
                }
            }

            try {
                $batch_result = $batch->execute();
            } catch ( \Exception $e ) {
                error_log( 'IGD SDK ERROR (Batch Execute): ' . $e->getMessage() );
                $client->setUseBatch( false );

                return;
            }

            $client->setUseBatch( false );

            foreach ( $batch_result as $key => $file ) {
                $index = (int) str_replace( 'response-', '', $key );

                if ( empty( $file ) || is_a( $file, 'IGDGoogle_Service_Exception' ) || is_a( $file, 'IGDGoogle_Exception' ) ) {
                    unset( $folders[ $index ] );
                    continue;
                }

                // Check file limit
                if ( isset( $this->data['fileNumbers'] ) && $this->data['fileNumbers'] > 0 && count( $folders ) > $this->data['fileNumbers'] ) {
                    unset( $folders[ $index ] );
                    continue;
                }

                // Normalize file data
                if ( is_a( $file, 'IGDGoogle_Service_Drive_DriveList' ) ) {
                    $file = igd_drive_map( $file, $account_id );
                } else {
                    $file = igd_file_map( $file, $account_id );
                }

                Files::add_file( $file );
                $folders[ $index ] = $file;
            }

        } else {

            // Load from cache
            foreach ( $folders as $key => $file ) {
                if ( empty( $file ) || empty( $file['id'] ) || empty( $file['accountId'] ) ) {
                    unset( $folders[ $key ] );
                    continue;
                }

                $account_id = $file['accountId'];
                $file_id    = $file['id'];
                $fetched    = App::instance( $account_id )->get_file_by_id( $file_id );

                if ( empty( $fetched ) || ! is_array( $fetched ) ) {
                    unset( $folders[ $key ] );
                    continue;
                }

                $folders[ $key ] = $fetched;
            }

            $this->data['folders'] = $folders;
        }

        // Apply file number limit
        if ( isset( $this->data['fileNumbers'] ) && $this->data['fileNumbers'] > 0 && count( $this->data['folders'] ) > $this->data['fileNumbers'] ) {
            $this->data['folders'] = array_slice( $this->data['folders'], 0, $this->data['fileNumbers'] );
        }

        // initially sort selected files if needed
        if ( ! empty( $this->data['initialFilesSorting'] ) ) {
            $sort                  = $this->data['sort'] ?? [];
            $this->data['folders'] = igd_sort_files( $this->data['folders'], $sort );
        }

        // Make sure folders are array formatted to be used in js
        $this->data['folders'] = array_values( $this->data['folders'] );

    }

    private function set_filters() {

        $filters = [
                'allowExtensions'       => ! empty( $this->data['allowExtensions'] ) ? str_replace( ' ', '', $this->data['allowExtensions'] ) : '',
                'allowAllExtensions'    => $this->data['allowAllExtensions'] ?? false,
                'allowExceptExtensions' => ! empty( $this->data['allowExceptExtensions'] ) ? str_replace( ' ', '', $this->data['allowExceptExtensions'] ) : '',

                'allowNames'       => $this->data['allowNames'] ?? '',
                'allowAllNames'    => $this->data['allowAllNames'] ?? '',
                'allowExceptNames' => $this->data['allowExceptNames'] ?? '',

                'nameFilterOptions' => $this->data['nameFilterOptions'] ?? [ 'files' ],

                'showFiles'   => $this->data['showFiles'] ?? true,
                'showFolders' => $this->data['showFolders'] ?? true,
        ];

        if ( 'gallery' == $this->type ) {
            $filters['isGallery'] = true;
        }

        if ( 'media' == $this->type ) {
            $filters['isMedia'] = true;
        }

        $this->data['filters'] = $filters;

    }

    private function check_filters() {

        if ( ! igd_should_filter_files( $this->data['filters'] ) ) {
            return;
        }

        if ( in_array( $this->type, [
                        'browser',
                        'gallery',
                        'media',
                        'search',
                        'slider',
                        'review',
                        'list',
                        'embed',
                ] ) && ! empty( $this->data['folders'] ) ) {

            $filters = $this->data['filters'];

            $this->data['folders'] = array_values( array_filter(
                    $this->data['folders'],
                    function ( $item ) use ( $filters ) {
                        return igd_should_allow( $item, $filters );
                    }
            ) );
        }

    }

    private function set_notifications() {

        $type = $this->type;
        $data = $this->data;

        // Determine if default notifications should be enabled
        $enable_default = ( $type === 'review' || ( $type === 'gallery' && ! empty( $data['photoProof'] ) ) ) && ! isset( $data['enableNotification'] );

        // Exit early if notifications are disabled
        if ( ! $enable_default && empty( $data['enableNotification'] ) ) {
            return;
        }

        // Assign default or provided notification values
        $this->data['notifications'] = [
                'downloadNotification'        => $data['downloadNotification'] ?? true,
                'proofNotification'           => $data['proofNotification'] ?? true,
                'uploadNotification'          => $data['uploadNotification'] ?? true,
                'deleteNotification'          => $data['deleteNotification'] ?? true,
                'playNotification'            => $data['playNotification'] ?? ( $type === 'media' ),
                'searchNotification'          => $data['searchNotification'] ?? ( $type === 'search' ),
                'viewNotification'            => $data['viewNotification'] ?? true,
                'notificationEmail'           => $data['notificationEmail'] ?? '%admin_email%',
                'skipCurrentUserNotification' => $data['skipCurrentUserNotification'] ?? true,
        ];

    }

    // Set active account
    protected function set_account() {

        $this->data['accounts'] = Account::instance()->get_accounts();
        $this->data['account']  = Account::instance()->get_active_account();

        if ( empty( $this->data['allFolders'] ) && ! empty( $this->data['folders'] ) ) {
            $folder = reset( $this->data['folders'] );

            $this->data['account'] = Account::instance()->get_accounts( $folder['accountId'] );
        }
    }

    private function generate_html() {

        $width  = ! empty( $this->data['moduleWidth'] ) ? $this->data['moduleWidth'] : '100%';
        $height = ! empty( $this->data['moduleHeight'] ) ? $this->data['moduleHeight'] : '';

        switch ( $this->type ) {
            case 'embed':
                $html = sprintf( '<div class="igd igd-shortcode-wrap igd-shortcode-embed">%s</div>', igd_get_embed_content( $this->data ) );
                break;
            default:
                ob_start();
                ?>
                <div class="igd igd-shortcode-wrap igd-shortcode-<?php echo esc_attr( $this->type ); ?>"
                     data-shortcode-data="<?php echo esc_attr(base64_encode( json_encode( $this->data ) )); ?>"
                     style="--module-width: <?php echo esc_attr( $width ); ?>; --module-height: <?php echo esc_attr( $height ); ?>;"
                ></div>
                <?php
                $html = ob_get_clean();
                break;
        }

        return $html;
    }

	/**
	 * Check if the shortcode should be shown.
	 *
	 * @return bool True if shortcode should be shown, false otherwise.
	 */
	public function check_should_show() {
		$display_for = $this->data['displayFor'] ?? 'everyone';

		// If private folders are set, user must be logged in
		if ( ! empty( $this->data['privateFolders'] ) ) {
			return is_user_logged_in();
		}

		// If display is set to everyone, show to all users
		if ( 'everyone' === $display_for ) {
			return true;
		}

		// If not set to logged in users, return false
		if ( 'loggedIn' !== $display_for ) {
			return false;
		}

		// User must be logged in
		if ( ! $this->current_user ) {
			return false;
		}

		// Check user permissions
		return $this->check_user_has_permission();
	}

	/**
	 * Check if current logged-in user has permission to view.
	 *
	 * @return bool True if user has permission, false otherwise.
	 */
	private function check_user_has_permission() {
		$display_users    = $this->data['displayUsers'] ?? [];
		$display_everyone = filter_var( $this->data['displayEveryone'] ?? false, FILTER_VALIDATE_BOOLEAN );
		$display_except   = $this->data['displayExcept'] ?? [];

		// Separate roles from user IDs
		$user_roles        = array_filter( $display_users, 'is_string' );
		$except_user_roles = array_filter( $display_except, 'is_string' );

		// If display_everyone is true and user is not in exceptions
		if ( $display_everyone ) {
			if ( ! in_array( $this->current_user->ID, $display_except, true ) &&
				 empty( array_intersect( $this->current_user->roles, $except_user_roles ) ) ) {
				return true;
			}
		}

		// Check if user's role or ID is in allowed list
		if ( in_array( 'everyone', $user_roles, true ) ||
			 ! empty( array_intersect( $this->current_user->roles, $user_roles ) ) ||
			 in_array( $this->current_user->ID, $display_users, true ) ) {
			return true;
		}

		// If no restrictions set and display_everyone is false, allow
		if ( empty( $display_users ) && ! $display_everyone ) {
			return true;
		}

		return false;
	}

    public function enqueue_scripts() {
        wp_enqueue_style( 'igd-frontend' );
        wp_enqueue_script( 'igd-frontend' );
    }

    /**
     * @return Shortcode|null
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /***---- Module Builder Methods ----***/
    public static function get_shortcode( $id ) {
        global $wpdb;

        $table = $wpdb->prefix . 'integrate_google_drive_shortcodes';

        $result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id=%d", $id ), ARRAY_A );

        if ( ! empty( $result ) ) {
            $result['config']    = maybe_unserialize( $result['config'] );
            $result['locations'] = ! empty( $result['locations'] ) ? maybe_unserialize( $result['locations'] ) : [];
        }

        return $result;

    }

    public static function get_shortcodes() {
        global $wpdb;

        $table = $wpdb->prefix . 'integrate_google_drive_shortcodes';

        $shortcodes = $wpdb->get_results( "SELECT * FROM $table ORDER BY id DESC", ARRAY_A );

        if ( ! empty( $shortcodes ) ) {
            foreach ( $shortcodes as &$shortcode ) {
                $shortcode['config']    = maybe_unserialize( $shortcode['config'] );
                $shortcode['locations'] = ! empty( $shortcode['locations'] ) ? maybe_unserialize( $shortcode['locations'] ) : [];
            }
        }

        return $shortcodes;

    }

    public static function get_shortcodes_count() {
        global $wpdb;

        $table = $wpdb->prefix . 'integrate_google_drive_shortcodes';

        return $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
    }

    public static function update_shortcode( $posted, $force_insert = false ) {
        global $wpdb;

        $table = $wpdb->prefix . 'integrate_google_drive_shortcodes';
        $id    = ! empty( $posted['id'] ) ? intval( $posted['id'] ) : 0;

        // Sanitize & prepare fields
        $status = ! empty( $posted['status'] ) ? sanitize_key( $posted['status'] ) : 'on';
        $type   = ! empty( $posted['type'] ) ? sanitize_key( $posted['type'] ) : 'embed';
        $title  = ! empty( $posted['title'] ) ? sanitize_text_field( $posted['title'] ) : ''; // fallback added after insert

        $config = ! empty( $posted['config'] ) ? maybe_serialize( $posted['config'] ) : maybe_serialize( $posted );

        $data = [
                'title'   => $title, // will update later if empty
                'status'  => $status,
                'type'    => $type,
                'user_id' => get_current_user_id(),
                'config'  => $config,
        ];

        $data_format = [ '%s', '%s', '%s', '%d', '%s' ];

        if ( ! empty( $posted['created_at'] ) ) {
            $data['created_at'] = sanitize_text_field( $posted['created_at'] );
            $data_format[]      = '%s';
        }

        if ( ! empty( $posted['updated_at'] ) ) {
            $data['updated_at'] = sanitize_text_field( $posted['updated_at'] );
            $data_format[]      = '%s';
        }

        // Insert or update
        if ( $force_insert || ! $id ) {
            $wpdb->insert( $table, $data, $data_format );

            return $wpdb->insert_id;
        }

        // Update
        $wpdb->update( $table, $data, [ 'id' => $id ], $data_format, [ '%d' ] );

        return $id;
    }

    public static function duplicate_shortcode( $id ) {

        if ( empty( $id ) ) {
            return false;
        }

        $shortcode = self::get_shortcode( $id );

        if ( $shortcode ) {
            $shortcode               = (array) $shortcode;
            $shortcode['title']      = 'Copy of ' . $shortcode['title'];
            $shortcode['created_at'] = current_time( 'mysql' );
            $shortcode['updated_at'] = current_time( 'mysql' );
            $shortcode['locations']  = serialize( [] );

            $insert_id = self::update_shortcode( $shortcode, true );

            $data = array_merge( $shortcode, [
                    'id'        => $insert_id,
                    'config'    => $shortcode['config'],
                    'locations' => [],
            ] );

            return $data;
        }

        return false;
    }

    public static function delete_shortcode( $id = false ) {
        global $wpdb;
        $table = $wpdb->prefix . 'integrate_google_drive_shortcodes';

        if ( $id ) {
            $wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );
        } else {
            $wpdb->query( "TRUNCATE TABLE $table" );
        }

    }

    public static function view() { ?>
        <div id="igd-shortcode-builder"></div>
    <?php }

    /***---- Shortcode Data Methods ----***/
    public static function get_shortcode_data( $shortcode_id ) {

        $data = null;

        if ( strpos( $shortcode_id, 'igd_' ) !== false ) {
            $data = get_transient( 'shortcode_' . $shortcode_id );
        } else {
            $shortcode = Shortcode::get_shortcode( $shortcode_id );

            if ( ! empty( $shortcode ) ) {
                $data = $shortcode['config'];
            }
        }

        $instance       = new self();
        $instance->data = $data;
        $instance->type = $data['type'] ?? '';

        $instance->apply_module_configuration();

        return $instance->data;
    }

    public static function set_current_shortcode( $shortcode_id ) {

        $shortcode_data = self::get_shortcode_data( $shortcode_id );

        if ( ! empty( $shortcode_data ) ) {
            self::$current_shortcode = $shortcode_data;
        }

    }

    public static function get_current_shortcode() {
        return self::$current_shortcode;
    }

    /**
     * Check if the user has specific action permission
     *
     * @param string $action
     * @param $data
     * @param $shortcode_data
     *
     * @return bool
     */
    public static function can_do( $action = '', $posted = [], $shortcode_data = false ) {

        if ( ! $shortcode_data ) {
            $shortcode_data = self::get_current_shortcode();
        }

        // Early exit if shortcode_data is empty
        if ( empty( $shortcode_data ) ) {
            return is_user_logged_in();
        }


        // Handle case where all folders are accessible
        if ( ! empty( $shortcode_data['allFolders'] ) || ! empty( $shortcode_data['privateFolders'] ) ) {
            if ( in_array( $action, [ 'get_files', 'search_files', 'switch_account' ] ) ) {
                return true;
            }
        }

        $module_type = $shortcode_data['type'] ?? '';

        switch ( $action ) {
            case 'get_files':

                // Handle specific folder access
                if ( ! empty( $folder = $posted['folder'] ) ) {

                    $shortcode_folder_ids = array_map( function ( $folder ) {
                        return $folder['id'];
                    }, $shortcode_data['folders'] );

                    $breadcrumbs_keys = array_keys( igd_get_breadcrumb( $folder ) );

                    // check if any breadcrumb is in shortcode folders
                    if ( ! empty( array_intersect( $breadcrumbs_keys, $shortcode_folder_ids ) ) ) {
                        return true;
                    }

                    return false;
                }

                break;
            case 'search_files':
                // Handle file search with specific conditions
                return ! empty( $shortcode_data['allowSearch'] ) || 'search' == $module_type;
            case 'get_file';
                return ! empty( $shortcode_data['details'] );
            case 'delete_files';
                return ! empty( $shortcode_data['canDelete'] );
            case 'new_folder';
                return ! empty( $shortcode_data['newFolder'] );
            case 'move_copy';
                return ! empty( $shortcode_data['moveCopy'] );
            case 'rename';
                return ! empty( $shortcode_data['rename'] );
            case 'preview';
                return ! isset( $shortcode_data['preview'] ) || ! empty( $shortcode_data['preview'] );
            case 'create_doc';
                return ! empty( $shortcode_data['createDoc'] );
            case 'update_file_permission';
                return 'media' == $module_type && ! empty( $shortcode_data['allowEmbedPlayer'] );
            case 'photo_proof';
                return 'gallery' == $module_type && ! empty( $shortcode_data['photoProof'] );
            case 'upload';
                return in_array( $module_type, [ 'uploader', 'browser' ] );
            case 'share';
                return ! empty( $shortcode_data['allowShare'] );
            case 'download';
                return ! empty( $shortcode_data['download'] );

            default:
                // No valid action matched
                return false;
        }

        // Action provided does not result in a clear decision
        return false;
    }

}

Shortcode::instance();
