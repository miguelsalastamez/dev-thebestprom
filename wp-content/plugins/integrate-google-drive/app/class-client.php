<?php

/**
 * Handle the Google Client
 */

namespace IGD;

defined( 'ABSPATH' ) || exit();

class Client {

	private static $instance = null;

	public $client;

	private $client_id;

	private $client_secret;

	private $redirect_uri;

	public $account_id;
	public $account = [];

	public function __construct( $account_id = null ) {

		if ( ! class_exists( 'IGDGoogle_Client' ) ) {
			require_once IGD_PATH . '/vendors/Google-sdk/src/Google/autoload.php';
		}

		// Initialize properties
		$this->client = null;

		// Get account information
		if ( empty( $account_id ) ) {
			$account = Account::instance()->get_active_account();
		} else {
			$account = Account::instance()->get_accounts( $account_id );
		}

		// Set account data safely
		$this->account_id = ! empty( $account['id'] ) ? sanitize_text_field( $account['id'] ) : $account_id;
		$this->account    = is_array( $account ) ? $account : [];

		// Set OAuth credentials with proper escaping
		$this->client_id     = sanitize_text_field( apply_filters( 'igd/client_id', '885430345856-7dfh13l81oql8d8toae6ecs0865jbmsh.apps.googleusercontent.com' ) );
		$this->client_secret = sanitize_text_field( apply_filters( 'igd/client_secret', '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' ) );
		$this->redirect_uri  = esc_url_raw( apply_filters( 'igd/redirect_uri', 'https://softlabbd.com/integrate-google-drive-oauth.php' ) );
	}

	/**
	 * @throws \Exception
	 */
	public function get_client() {
		if ( empty( $this->client ) ) {
			$this->client = $this->start_client();
		}

		return $this->client;
	}

	/**
	 * @throws \Exception
	 */
	public function start_client() {

		try {
			$this->client = new \IGDGoogle_Client();
		} catch ( \Exception $exception ) {
			$error_msg = sprintf(
				'[Integrate Google Drive - Error]: Couldn\'t start Google Client: %s',
				sanitize_text_field( $exception->getMessage() )
			);
			error_log( $error_msg );

			return $exception;
		}

		$this->client->setApplicationName( 'Integrate Google Drive - ' . sanitize_text_field( IGD_VERSION ) );

		$this->client->setClientId( $this->client_id );
		$this->client->setClientSecret( $this->client_secret );
		$this->client->setRedirectUri( $this->redirect_uri );
		$this->client->setApprovalPrompt( 'force' );
		$this->client->setAccessType( 'offline' );

		// Build and encode state parameter with proper escaping
		$state = apply_filters( 'igd_auth_state', admin_url( 'admin.php?page=integrate-google-drive&action=authorization' ) );
		$state = sanitize_url( $state );
		$this->client->setState( base64_encode( $state ) );

		$this->client->setScopes( [
			'https://www.googleapis.com/auth/drive',
		] );

		// Return early if no account data
		if ( empty( $this->account ) || ! is_array( $this->account ) ) {
			return $this->client;
		}

		$authorization = new Authorization( $this->account );

		if ( ! $authorization->has_access_token() ) {
			return $this->client;
		}

		$access_token = $authorization->get_access_token();

		if ( empty( $access_token ) ) {
			return $this->client;
		}

		$this->client->setAccessToken( $access_token );

		// Check if token is expired
		if ( ! $this->client->isAccessTokenExpired() ) {
			return $this->client;
		}

		// Token is expired, refresh it
		return $authorization->refresh_token( $this->account );
	}

	public function get_auth_url() {
		return $this->get_client()->createAuthUrl();
	}

	public function create_access_token() {

		try {
			// Sanitize code parameter from GET request
			if ( ! isset( $_GET['code'] ) ) {
				return new \WP_Error( 'missing_code', esc_html__( 'Authorization code is missing.', 'integrate-google-drive' ) );
			}

			$code = sanitize_text_field( wp_unslash( $_GET['code'] ) );

			// Verify state parameter
			if ( ! isset( $_GET['state'] ) ) {
				return new \WP_Error( 'missing_state', esc_html__( 'State parameter is missing.', 'integrate-google-drive' ) );
			}

			// Decode and sanitize state
			$state_encoded = sanitize_text_field( wp_unslash( $_GET['state'] ) );
			$state_url     = esc_url_raw( base64_decode( $state_encoded, true ) );

			if ( false === $state_url ) {
				return new \WP_Error( 'invalid_state', esc_html__( 'Invalid state parameter.', 'integrate-google-drive' ) );
			}

			$access_token = $this->get_client()->authenticate( $code );

			$service = App::instance( $this->account_id )->getService();

			try {
				$about = $service->about->get( [ 'fields' => 'storageQuota,user' ] );

				$data = [
					'id'      => sanitize_text_field( $about->getUser()->getPermissionId() ?? '' ),
					'name'    => sanitize_text_field( $about->getUser()->getDisplayName() ?? '' ),
					'email'   => sanitize_email( $about->getUser()->getEmailAddress() ?? '' ),
					'photo'   => esc_url_raw( $about->getUser()->getPhotoLink() ?? '' ),
					'storage' => [
						'usage' => intval( $about->getStorageQuota()->getUsage() ?? 0 ),
						'limit' => intval( $about->getStorageQuota()->getLimit() ?? 0 ),
					],
					'lost'    => false,
					'root_id' => sanitize_text_field( $service->files->get( 'root' )->getId() ?? '' ),
				];
			} catch ( \Exception $exception ) {
				$error_msg = sprintf(
					'[Integrate Google Drive - Error]: Failed to fetch user info: %s',
					sanitize_text_field( $exception->getMessage() )
				);
				error_log( $error_msg );
				wp_die( esc_html__( 'Error fetching user information. Please try again.', 'integrate-google-drive' ) );
			}

			// Parse state query for user_id
			$state_query = wp_parse_url( $state_url, PHP_URL_QUERY );
			parse_str( $state_query, $state );

			$user_id         = ! empty( $state['user_id'] ) ? intval( $state['user_id'] ) : 'admin';
			$data['user_id'] = [ $user_id ];

			$data = Account::instance( $user_id )->update_account( $data );
			Account::instance( $user_id )->set_active_account_id( $data['id'] );

			$authorization = new Authorization( $data );
			$authorization->set_access_token( $access_token );

			// Remove lost authorization notice
			$timestamps = wp_next_scheduled( 'igd_lost_authorization_notice', [ 'account_id' => $data['id'] ] );
			if ( ! empty( $timestamps ) ) {
				wp_unschedule_event( $timestamps, 'igd_lost_authorization_notice', [ 'account_id' => $data['id'] ] );
			}

		} catch ( \Exception $exception ) {
			$error_msg = sprintf(
				'[Integrate Google Drive - Error]: Couldn\'t generate Access Token: %s',
				sanitize_text_field( $exception->getMessage() )
			);
			error_log( $error_msg );

			return new \WP_Error( 'token_error', esc_html__( 'Error communicating with API. Please try again.', 'integrate-google-drive' ) );
		}

		return true;

	}

	public static function instance( $account_id = null ) {

		if ( is_null( self::$instance ) || self::$instance->account_id !== $account_id ) {
			self::$instance = new self( $account_id );
		}

		return self::$instance;
	}

}
