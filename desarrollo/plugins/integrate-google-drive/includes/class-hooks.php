<?php

namespace IGD;

defined( 'ABSPATH' ) || exit();

/**
 * Class Hooks
 *
 * Handles WordPress hooks, filters, and actions for the Integrate Google Drive plugin.
 * Manages authentication, file operations, cron jobs, and various plugin features.
 *
 * @package IGD
 */
class Hooks {
	/**
	 * Singleton instance
	 *
	 * @var Hooks|null
	 */
	protected static $instance = null;

	/**
	 * Constructor - registers all WordPress hooks and filters
	 */
	public function __construct() {

		// Set custom app credentials
		$this->setup_custom_credentials();

		// IGD render form upload field data
		add_filter( 'igd_render_form_field_data', [ $this, 'render_form_field_data' ], 10, 2 );

		// Handle oAuth authorization
		add_action( 'admin_init', [ $this, 'handle_authorization' ] );

		// Add modules rewrite rules
		add_action( 'init', [ $this, 'add_rewrite_rules' ] );

		// Register query var
		add_filter( 'query_vars', [ $this, 'add_query_vars' ] );

		// Handle file operations
		$this->setup_file_operations();

		// Handle migration
		add_action( 'igd_migration_background_process', [ $this, 'run_migration' ] );
	}

	/**
	 * Set up custom Google credentials
	 */
	private function setup_custom_credentials() {
		$clientID     = igd_get_settings( 'clientID' );
		$clientSecret = igd_get_settings( 'clientSecret' );
		$ownApp       = igd_get_settings( 'ownApp' );

		if ( ! empty( $ownApp ) && ! empty( $clientID ) && ! empty( $clientSecret ) ) {
			add_filter( 'igd/client_id', function () use ( $clientID ) {
				return $clientID;
			} );

			add_filter( 'igd/client_secret', function () use ( $clientSecret ) {
				return $clientSecret;
			} );

			add_filter( 'igd/redirect_uri', function () {
				return admin_url( '?action=integrate-google-drive-authorization' );
			} );
		}
	}

	/**
	 * Set up file operation handlers
	 */
	private function setup_file_operations() {
		// Preview image handling
		add_action( 'template_redirect', [ $this, 'preview_image' ] );

		// Direct content display
		add_action( 'template_redirect', [ $this, 'direct_content' ] );

		// Secure embed handling
		add_action( 'template_redirect', [ $this, 'secure_embed' ] );

		// Direct download
		add_action( 'template_redirect', [ $this, 'direct_download' ] );

		// Direct stream
		add_action( 'template_redirect', [ $this, 'direct_stream' ] );

		// Update lost account
		add_action( 'igd_lost_authorization_notice', [ $this, 'update_lost_account' ] );

		// Render modules and tables
		add_action( 'template_redirect', [ $this, 'render_module_view' ] );
		add_action( 'template_redirect', [ $this, 'render_table_view' ] );

	}

	/**
	 * Run database migrations in batches.
	 *
	 * Processes migrations incrementally to avoid timeouts on large datasets.
	 */
	public function run_migration() {
		if ( ! class_exists( '\IGD\Migration_1_5_1' ) ) {
			include_once IGD_INCLUDES . '/updates/class-migration-1.5.1.php';
		}

		$instance = Migration_1_5_1::instance();

		while ( true ) {
			$result = $instance->run_batch();

			// Check if migration is complete
			if ( ! empty( $result['completed'] ) ) {
				delete_option( 'igd_migration_1_5_1_status' );
				break;
			}

			// Optional: sleep to avoid timeouts on large migrations
			usleep( 200000 ); // 200ms
		}
	}

	/**
	 * Add rewrite rules for modules and tables.
	 *
	 * Enables clean URLs for direct module and table access.
	 */
	public function add_rewrite_rules() {
		// Register query tags
		add_rewrite_tag( '%igd-modules%', '([0-9]+)' );
		add_rewrite_tag( '%igd-tables%', '([0-9]+)' );

		// Module rewrite rule
		add_rewrite_rule(
			'^igd-modules/([0-9]+)/?$',
			'index.php?igd-modules=$matches[1]',
			'top'
		);

		// Table rewrite rule
		add_rewrite_rule(
			'^igd-tables/([0-9]+)/?$',
			'index.php?igd-tables=$matches[1]',
			'top'
		);

	}

	/**
	 * Render module view by ID.
	 *
	 * Creates a fake post and renders the module shortcode.
	 */
	public function render_module_view() {
		$module_id = get_query_var( 'igd-modules' );

		if ( ! $module_id ) {
			return;
		}

		$id = esc_attr( $module_id );

		$module = Shortcode::get_shortcode( $id );

		if ( ! $module ) {
			wp_die( esc_html__( 'Invalid module ID.', 'integrate-google-drive' ), 400 );
		}

		$title = $module['title'] ?? 'IGD Modules';

		// Capture the shortcode output
		ob_start();
		echo do_shortcode( '[integrate_google_drive id="' . $id . '"]' );
		$post_content = ob_get_clean();

		// Create and setup fake post
		$this->setup_fake_post( $post_content, $title, 'igd-modules', $id );
	}

	/**
	 * Render table view by ID.
	 *
	 * Creates a fake post and renders the table shortcode.
	 */
	public function render_table_view() {
		$module_id = get_query_var( 'igd-tables' );

		if ( ! $module_id ) {
			return;
		}

		$id = esc_attr( $module_id );

		$module = Shortcode::get_shortcode( $id );

		if ( ! $module ) {
			wp_die( esc_html__( 'Invalid Table ID.', 'integrate-google-drive' ), 400 );
		}

		$title = $module['title'] ?? 'IGD Table';

		// Capture the shortcode output
		ob_start();
		echo do_shortcode( '[igd_table id="' . $id . '"]' );
		$post_content = ob_get_clean();

		// Create and setup fake post
		$this->setup_fake_post( $post_content, $title, 'igd-tables', $id );
	}

	/**
	 * Set up a fake WordPress post for rendering.
	 *
	 * @param string $post_content The post content (HTML).
	 * @param string $title        The post title.
	 * @param string $post_name    The post name/slug.
	 * @param int    $id           The module or table ID.
	 */
	private function setup_fake_post( $post_content, $title, $post_name, $id ) {
		global $wp_query, $post;

		// Create the fake post object data
		$fake_post_data = [
			'ID'                => 0,
			'post_author'       => 0,
			'post_date'         => current_time( 'mysql' ),
			'post_date_gmt'     => current_time( 'mysql', 1 ),
			'post_modified'     => current_time( 'mysql' ),
			'post_modified_gmt' => current_time( 'mysql', 1 ),
			'post_content'      => $post_content,
			'post_title'        => $title,
			'post_excerpt'      => '',
			'post_status'       => 'publish',
			'post_type'         => 'page',
			'post_name'         => $post_name,
			'post_parent'       => 0,
			'guid'              => home_url( sprintf( '/?%s=%d', $post_name, $id ) ),
			'menu_order'        => 0,
			'ping_status'       => 'closed',
			'comment_status'    => 'closed',
			'comment_count'     => 0,
			'filter'            => 'raw',
		];

		// Create WP_Post object
		$post = new \WP_Post( (object) $fake_post_data );

		// Set global WP_Query properties
		$wp_query->post              = $post;
		$wp_query->posts             = [ $post ];
		$wp_query->queried_object    = $post;
		$wp_query->queried_object_id = $post->ID;
		$wp_query->post_count        = 1;
		$wp_query->is_page           = true;
		$wp_query->is_single         = true;
		$wp_query->is_singular       = true;
		$wp_query->is_home           = false;
		$wp_query->is_404            = false;
		$wp_query->max_num_pages     = 1;

		// Set up postdata
		setup_postdata( $post );

		// Optionally remove conflicting filters
		remove_all_filters( 'the_content' );
		remove_all_filters( 'the_excerpt' );
		remove_all_filters( 'the_title' );
	}

	public function update_lost_account( $account_id = null ) {
		if ( ! $account_id ) {
			$account_id = igd_get_active_account_id();
		}

		$account = Account::instance()->get_accounts( $account_id );

		if ( empty( $account ) ) {
			return;
		}

		$account['lost'] = true;

		Account::instance()->update_account( $account );
	}

	public function direct_download() {

		if ( get_query_var( 'igd_download' ) ) {

			$file_id = ! empty( $_REQUEST['id'] ) ? sanitize_text_field( $_REQUEST['id'] ) : '';

			$file_ids = ! empty( $_REQUEST['file_ids'] ) ? json_decode( base64_decode( sanitize_text_field( $_REQUEST['file_ids'] ) ) ) : [];

			$account_id   = ! empty( $_REQUEST['accountId'] ) ? sanitize_text_field( $_REQUEST['accountId'] ) : '';
			$mimetype     = ! empty( $_REQUEST['mimetype'] ) ? sanitize_text_field( $_REQUEST['mimetype'] ) : 'default';
			$ignore_limit = ! empty( $_REQUEST['ignore_limit'] );

			if ( ! empty( $file_ids ) ) {
				$request_id = ! empty( $_REQUEST['id'] ) ? sanitize_text_field( $_REQUEST['id'] ) : '';

				igd_download_zip( $file_ids, $request_id, $account_id );
			} elseif ( ! empty( $file_id ) ) {
				Download::instance( $file_id, $account_id, $mimetype, false, $ignore_limit )->start_download();
			}

			exit();
		}
	}

	public function direct_stream() {

		if ( get_query_var( 'igd_stream' ) ) {

			$file_id    = ! empty( $_REQUEST['id'] ) ? sanitize_text_field( $_REQUEST['id'] ) : '';
			$account_id = ! empty( $_REQUEST['account_id'] ) ? sanitize_text_field( $_REQUEST['account_id'] ) : '';

			Stream::instance( $file_id, $account_id )->stream_content();

			exit();
		}

	}

	/**
	 * Add query variables for plugin functionality.
	 *
	 * @param array $vars Existing query variables.
	 *
	 * @return array Updated query variables.
	 */
	public function add_query_vars( $vars ) {
		$new_vars = [
			'igd_preview_image',
			'igd_download',
			'igd_stream',
			'direct_file',
			'secure_embed',
			'igd-modules',
			'igd-tables',
		];

		return array_merge( $vars, $new_vars );
	}

	public function preview_image() {

		// Avoid any output before headers
		if ( headers_sent() || ! get_query_var( 'igd_preview_image' ) ) {
			return;
		}

		ob_start();

		// Input sanitization
		$id         = sanitize_text_field( $_GET['id'] ?? '' );
		$account_id = sanitize_text_field( $_GET['accountId'] ?? '' );
		$size       = sanitize_key( $_GET['size'] ?? 'medium' );
		$w          = isset( $_GET['width'] ) ? max( 1, intval( $_GET['width'] ) ) : 300;
		$h          = isset( $_GET['height'] ) ? max( 1, intval( $_GET['height'] ) ) : 300;

		if ( empty( $id ) ) {
			status_header( 400 );
			echo "File ID is required.";
			exit;
		}

		$app  = App::instance( $account_id );
		$file = $app->get_file_by_id( $id );

		if ( ! $file ) {
			status_header( 404 );
			echo "File not found.";
			exit;
		}

		$last_modified = strtotime( $file['updated'] );
		$etag          = md5( $last_modified );

		// Check for browser caching
		if (
			( isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) && strtotime( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) === $last_modified ) ||
			( isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) && trim( $_SERVER['HTTP_IF_NONE_MATCH'] ) === $etag )
		) {
			status_header( 304 );
			exit;
		}

		// Set caching headers
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $last_modified ) . ' GMT' );
		header( 'Etag: ' . $etag );
		header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + 300 ) . ' GMT' );
		header( 'Cache-Control: must-revalidate' );

		// Determine thumbnail size suffix
		$suffix_map = [
			'custom'  => "=w{$w}-h{$h}",
			'small'   => '=w300-h300',
			'medium'  => '=h600-nu',
			'large'   => '=w1024-h768-p-k-nu',
			'full'    => '=s0',
			'default' => '=w200-h190-p-k-nu-iv1',
		];
		$attributes = $suffix_map[ $size ] ?? $suffix_map['default'];

		$thumbnail_file = $id . $attributes . '.png';
		$thumbnail_path = IGD_CACHE_DIR . '/' . $thumbnail_file;

		// Serve cached image if exists
		if ( file_exists( $thumbnail_path ) && filemtime( $thumbnail_path ) === $last_modified ) {
			$info = getimagesize( $thumbnail_path );
			if ( $info ) {
				header( "Content-Type: {$info['mime']}" );
				readfile( $thumbnail_path );
				exit;
			}
		}

		// Download thumbnail from Google
		$download_url = "https://lh3.google.com/u/0/d/{$id}{$attributes}";

		try {
			$client  = $app->client;
			$request = new \IGDGoogle_Http_Request( $download_url, 'GET' );

			$client->getIo()->setOptions( [
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_FOLLOWLOCATION => true,
			] );

			$response = $client->getAuth()->authenticatedRequest( $request );
			$headers  = $response->getResponseHeaders();
			$body     = $response->getResponseBody();

			$content_type = $headers['content-type'] ?? '';
			if ( strpos( $content_type, 'image' ) === false ) {
				status_header( 400 );
				echo "Invalid response type.";
				exit;
			}

			// Cache and serve
			file_put_contents( $thumbnail_path, $body );
			touch( $thumbnail_path, $last_modified );

			$info = getimagesize( $thumbnail_path );
			if ( $info ) {
				header( "Content-Type: {$info['mime']}" );
				readfile( $thumbnail_path );
				exit;
			}

			status_header( 500 );
			echo "Failed to process the thumbnail.";
			exit;

		} catch ( \Exception $e ) {
			status_header( 500 );
			echo "Error: " . esc_html( $e->getMessage() );
			exit;
		}
	}

	public function handle_authorization() {
		if ( empty( $_GET['action'] ) ) {
			return;
		}

		if ( 'authorization' == sanitize_key( $_GET['action'] ) ) {
			$client = Client::instance();

			$client->create_access_token();

			echo '<script type="text/javascript">window.opener.parent.location.reload(); window.close();</script>';

			exit();
		}
	}

	public function direct_content() {

		if ( $direct_file = get_query_var( 'direct_file' ) ) {

			$file = igd_sanitize_array(json_decode( base64_decode( $direct_file ), true ));

			if ( empty( $file['id'] ) ) {
				wp_die( 'Invalid file ID', 400 );
			}

			$file_id    = $file['id'];
			$account_id = $file['accountId'] ?? '';

			// Retrieve the file and check permissions
			$file        = App::instance( $account_id )->get_file_by_id( $file_id );
			$permissions = Permissions::instance( $account_id );

			if ( ! igd_is_dir( $file ) && ! $permissions->has_permission( $file ) ) {
				$permissions->set_permission( $file );
			}

			$is_dir = igd_is_dir( $file );

			add_filter( 'show_admin_bar', '__return_false' );

			// Remove all WordPress actions
			remove_all_actions( 'wp_head' );
			remove_all_actions( 'wp_print_styles' );
			remove_all_actions( 'wp_print_head_scripts' );
			remove_all_actions( 'wp_footer' );

			// Handle `wp_head`
			add_action( 'wp_head', 'wp_enqueue_scripts', 1 );
			add_action( 'wp_head', 'wp_print_styles', 8 );
			add_action( 'wp_head', 'wp_print_head_scripts', 9 );
			add_action( 'wp_head', 'wp_site_icon' );

			// Handle `wp_footer`
			add_action( 'wp_footer', 'wp_print_footer_scripts', 20 );

			// Handle `wp_enqueue_scripts`
			remove_all_actions( 'wp_enqueue_scripts' );

			// Also remove all scripts hooked into after_wp_tiny_mce.
			remove_all_actions( 'after_wp_tiny_mce' );

			Enqueue::instance()->frontend_scripts();

			$type = $is_dir ? 'browser' : 'embed';

			?>

            <!doctype html>
            <html lang="<?php language_attributes(); ?>" class="igd-direct-content-html">
            <head>
                <meta charset="<?php bloginfo( 'charset' ); ?>">
                <meta name="viewport"
                      content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
                <meta http-equiv="X-UA-Compatible" content="ie=edge">
                <title><?php echo esc_html( $file['name'] ); ?></title>

				<?php wp_enqueue_style( 'google-font-roboto', 'https://fonts.googleapis.com/css?family=Roboto:400,500,700&display=swap' ); ?>

				<?php do_action( 'wp_head' ); ?>

            </head>
            <body>

            <div id="igd-direct-content" class="type-<?php echo esc_attr( $type ); ?>">
				<?php

				$data = [
					'folders'            => [ $file ],
					'type'               => $type,
					'allowPreviewPopout' => false,
				];

				echo Shortcode::instance()->render_shortcode( [], $data );

				?>
            </div>

			<?php do_action( 'wp_footer' ); ?>

            </body>
            </html>

			<?php
			exit();
		}
	}

	public function secure_embed() {

		// Check if the query variable 'secure_embed' is set
		if ( ! get_query_var( 'secure_embed' ) ) {
			return;
		}

		// Validate the referer to ensure the request originates from your website
		$referer  = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
		$home_url = esc_url_raw( home_url() );

		// Deny access if the referer is not from your website
		if ( strpos( $referer, $home_url ) !== 0 ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page directly.', 'integrate-google-drive' ), 403 );
		}

		// Validate nonce for security
		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'igd' ) ) {
			wp_die( esc_html__( 'Invalid embed URL.', 'integrate-google-drive' ), 400 );
		}

		// Ensure 'secure_embed' and 'id' parameters are provided
		$secure_embed    = sanitize_text_field( $_REQUEST['secure_embed'] ?? '' );
		$encoded_file_id = sanitize_text_field( $_REQUEST['id'] ?? '' );
		if ( empty( $secure_embed ) || empty( $encoded_file_id ) ) {
			wp_die( esc_html__( 'Invalid embed URL.', 'integrate-google-drive' ), 400 );
		}

		// Decode and validate the file ID and account ID
		$file_id            = base64_decode( $encoded_file_id, true );
		$encoded_account_id = sanitize_text_field( $_REQUEST['account_id'] ?? '' );
		$account_id         = ! empty( $encoded_account_id ) ? base64_decode( $encoded_account_id, true ) : '';

		if ( ! $file_id ) {
			wp_die( esc_html__( 'Invalid embed URL.', 'integrate-google-drive' ), 400 );
		}

		// Retrieve the file and check permissions
		$file        = App::instance( $account_id )->get_file_by_id( $file_id );
		$permissions = Permissions::instance( $account_id );

		if ( ! $permissions->has_permission( $file ) ) {
			$permissions->set_permission( $file );
		}

		// Redirect to the secure embed URL
		$redirect_url = "https://drive.google.com/file/d/{$file_id}/preview";
		wp_redirect( esc_url_raw( $redirect_url ) );

		exit();
	}

	public function render_form_field_data( $data, $as_html ) {
		$uploaded_files = json_decode( $data, 1 );

		if ( empty( $uploaded_files ) ) {
			return $data;
		}

		$file_count = count( $uploaded_files );

		// Render TEXT only
		if ( ! $as_html ) {
			$formatted_value =
				// translators: %d: number of files
				sprintf( _n( '%d file uploaded to Google Drive', '%d files uploaded to Google Drive', $file_count, 'integrate-google-drive' ), $file_count );

			$formatted_value .= "\r\n";

			foreach ( $uploaded_files as $file ) {
				$view_link       = sprintf( 'https://drive.google.com/file/d/%1$s/view', $file['id'] );
				$formatted_value .= $file['name'] . " - (" . $view_link . "), \r\n";
			}

			return $formatted_value;
		}

		$heading = sprintf(
			'<h3 style="margin-bottom: 15px;">%s</h3>',
			sprintf(
			// translators: %d: number of files
				_n(
					'%d file uploaded to Google Drive',
					'%d files uploaded to Google Drive',
					$file_count,
					'integrate-google-drive'
				),
				$file_count
			)
		);

		// Render HTML
		ob_start();

		echo esc_html($heading);

		foreach ( $uploaded_files as $file ) {

			$file_url = sprintf( 'https://drive.google.com/file/d/%1$s/view', $file['id'] );

			?>
            <div style="display: block; margin-bottom: 5px;font-weight: 600;">
				<?php echo esc_html( $file['name'] ); ?> -
                <a style="text-decoration: none;font-weight: 400;"
                   href="<?php echo esc_url_raw( $file_url ); ?>"
                   target="_blank"><?php echo esc_url_raw( $file_url ); ?></a>
            </div>
		<?php }

		//Remove any newlines
		return trim( preg_replace( '/\s+/', ' ', ob_get_clean() ) );
	}

	/**
	 * @return Hooks|null
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

Hooks::instance();