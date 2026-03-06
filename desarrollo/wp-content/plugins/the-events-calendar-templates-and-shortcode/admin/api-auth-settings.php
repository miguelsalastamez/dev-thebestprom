<?php
namespace ECTREG;

/*
|--------------------------------------
|   API DATA VERIFICATION SETTINGS PAGE
|--------------------------------------
*/
class ECT_Settings {

		private $verification_status;
		private $PREFIX;
		private $PLUGIN_NAME;
		private $PLUGIN_VER;
		private $AUTH_PAGE;
		private $PLUGIN_URL;
		private $settings_api;
		private $licenseMessage;
		private $Response;
		private $Base_File;
		private $plugin_purchase_url;
		private $plugin_documentation_url;
	public function __construct() {
		 $this->Base_File   = ECT_PRO_FILE;
		$this->PLUGIN_NAME  = ECT_ApiConf::PLUGIN_NAME;
		$this->PREFIX       = ECT_ApiConf::PLUGIN_PREFIX;
		$this->AUTH_PAGE    = ECT_ApiConf::PLUGIN_AUTH_PAGE;
		$this->PLUGIN_URL   = ECT_ApiConf::PLUGIN_URL;
		$this->PLUGIN_VER   = ECT_ApiConf::PLUGIN_VERSION;
		$this->settings_api = \cool_plugins_events_registration_Settings::init();
		// $this->settings_api = new Settings_API;
		$this->plugin_purchase_url      = 'https://eventscalendaraddons.com/plugin/events-shortcodes-pro/';
		$this->plugin_documentation_url = 'https://docs.coolplugins.net/doc/the-events-calendar-shortcode-templates-pro/';
		$this->verification_status      = 'License is not verified yet! ';
		$this->settings_api->add_registration_page();
		add_action( 'admin_enqueue_scripts', array( $this, 'load_settings_scripts' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );

			$this->settings_api->add_section( $this->PREFIX . '_license_registration', __( 'Events Shortcodes & Templates Pro', 'cmb2' ) );
			$this->settings_api->add_field(
				$this->PREFIX . '_license_registration',
				array(
					array(
						'name'          => $this->PREFIX . '-purchase-code',
						'id'            => $this->PREFIX . '-purchase-code',
						'class'         => $this->PREFIX . '-settings-field required',
						'label'         => 'Enter License Key',
						// 'desc'  => $this->save_purchase_code(),
						  'placeholder' => __( 'Your Purchase /License Code', 'cmb2' ),
						'type'          => 'text',
						'default'       => '',
					),
					array(
						'name'        => $this->PREFIX . '-client-emailid',
						'id'          => $this->PREFIX . '-client-emailid',
						'class'       => $this->PREFIX . '-settings-field required',
						'label'       => 'Enter Email Id',
						'desc'        => $this->save_purchase_code(),
						'placeholder' => get_option( 'admin_email' ),
						'type'        => 'text',
						'default'     => get_option( 'admin_email' ),
					),
					array(
						'name'    => $this->PREFIX . '-validate-purchase-code',
						'id'      => $this->PREFIX . '-validate-purchase-code',
						'class'   => $this->PREFIX . '-settings-field',
						'desc'    => $this->ValidatePurchase(),
						'type'    => 'html',
						'default' => '',
					),
					array(
						'name'    => $this->PREFIX . '-find-purchase-code',
						'id'      => $this->PREFIX . '-find-purchase-code',
						'class'   => $this->PREFIX . '-settings-field',
						'label'   => 'Important Points',
						'desc'    => $this->find_purchase_code(),
						'type'    => 'html',
						'default' => '',
					),
				)
			);

			add_action( 'admin_notices', array( $this, 'admin_registration_notice' ) );

			add_action( 'wp_ajax_' . $this->PREFIX . '_uninstall_license', array( $this, 'uninstall_license' ) );

	}

	/*
	|---------------------------------------------------
	|   Initialize settings
	|---------------------------------------------------
	*/
	public function admin_init() {

			$this->settings_api->admin_init();
	}


	function find_purchase_code() {
		$html = '
		<h4 class="cool-license-q">Q1) Where can I find my license key?</h4>
		<p class="cool-license-a">You can find license key inside your purchase order email or /my-account section in the <a href="https://eventscalendaraddons.com/my-account/" target="_blank">website.</a>
		<br/>
		or Codecanyon users can find their license key by following these steps - <a href="https://help.market.envato.com/hc/en-us/articles/202822600-Where-Is-My-Purchase-Code-" target="_blank">check here</a></p>

		<h4 class="cool-license-q">Q2) Can I use single site license on another domain?</h4>
		<p class="cool-license-a">You need to deactivate license from current active site to use it on another domain. Remember to deactivate license before moving your site to another domain or server.</p>

		<h4 class="cool-license-q">Q3) Having trouble in license activation?</h4>
		<p class="cool-license-a">Please submit a support ticket at <a href="https://coolplugins.net/support/" target="_blank">https://coolplugins.net/support/</a> along with your license key and domain url.</p>
		';
		return $html;
	}


	function save_purchase_code() {
		$html = "<div id='" . $this->PREFIX . "-verify-permission'><span class='" . $this->PREFIX . "-notice-red'>&#9989; I agree to share my purchase code and email for plugin verification and to receive future updates notifications!</span></div><div id='" . $this->PREFIX . "-activation-button'>" . $this->settings_api->_return_submit_button( 'Verify Key' ) . "</div>
            <div id='" . $this->PREFIX . "-deactivation-button'><a id='" . $this->PREFIX . "-uninstall-license' class='button button-secondary button-hero'>Uninstall Licence</a><br/><span class='" . $this->PREFIX . "-notice-red uninstall'>(* Uninstall license to use it on other website or hosting.)</span></div>";
		return $html;
	}

	/*
	|---------------------------------------------------------------
	| This function generate custom message on loading the settings
	|---------------------------------------------------------------
	*/
	public function ValidatePurchase() {
		$purchase    = $this->ce_get_option( $this->PREFIX . '-purchase-code' );
		$admin_email = $this->ce_get_option( $this->PREFIX . '-client-emailid' );
		if ( isset( $_GET['settings-updated'] ) || ! empty( $purchase ) ) {
			if ( ! empty( $purchase ) ) {
				$registration = "<div class='wrap'>";
				$registration = "<div class='" . $this->PREFIX . "-verification-notice'>
                    <p><strong>License Verification Status:</strong>";
				$verified     = Ectbase::CheckWPPlugin( $purchase, $admin_email, $this->licenseMessage, $this->Response, $this->Base_File );

				if ( $verified && $this->Response->is_valid ) {
					$this->verification_status = 'Verified!';
					set_transient( $this->PREFIX . '_api_data_verification', 'done', 0 );
					$registration .= "<span class='" . $this->PREFIX . "_verification_enable'>&nbsp; &#9989; &nbsp;</span>";
				} else {
					$registration .= "<span class='" . $this->PREFIX . "_verification_disable'>&nbsp; &#10060; &nbsp;</span>";
					$this->flush_cache();
					$this->verification_status .= $this->licenseMessage;
				}

				$registration .= $this->verification_status;
				$registration .= '</p></div>';

				$registration .= "<p><strong>Developer's Support Validity Status:</strong>";
				if ( $verified && strpos( $this->Response->support_end, 'No support' ) == false ) {
					if ( $this->Response->support_end == 'Unlimited' ) {
						$date = new \DateTime();
						$date->modify( '+99 years' );
					} else {
						$date = new \DateTime( $this->Response->support_end );
					}
					$nowDate = new \DateTime();
					$diff    = $date->diff( $nowDate );
					if ( ( $diff->y > 0 || $diff->m > 0 || $diff->d > 0 || $diff->h > 0 || $diff->i > 0 ) && $diff->invert == 0 ) {
						$registration .= "<span class='" . $this->PREFIX . "_verification_disable'>&nbsp; &#10060; &nbsp;</span>";
					} else {
						$registration .= "<span class='" . $this->PREFIX . "_verification_enable'>&nbsp; &#9989; &nbsp;</span>";
					}
					$registration .= $this->Response->support_end . '</p></div>';
				} else {
					$registration .= "<span class='" . $this->PREFIX . "_verification_disable'>&nbsp; &#10060; &nbsp; N/A</span>";
				}
				$current_date      = date( 'Y-m-d H:i:s' );
				$current_date_time = strtotime( $current_date );
				$support_time      = ! empty( $this->Response->support_end ) ? strtotime( $this->Response->support_end ) : '';
				if ( ! empty( $support_time ) && $support_time < $current_date_time ) {
					$registration .= "<span class='" . $this->PREFIX . "-renew-support-notice'><ol><li>Your support validity has expired, please renew it to get support for plugin bugs and updates.</li><li>If you have already renewed your support then uninstall and install license once to update your support status.</li></ol></span>";
				}
				return $registration;
			} else {
				$empty_code = "<span class='" . $this->PREFIX . "-notice-red'>**Purchase code can not be empty!</span>";
				return $empty_code;
			}
		} else {
			$empty_code = "<span class='" . $this->PREFIX . "-notice-red'>&#9785; Don't have a license? <a href='" . $this->plugin_purchase_url . "' target='_blank'>Check Here To Purchase</a></span>";
			return $empty_code;
		}
	}
		/*
	|---------------------------------------------------------
	|   Gather settings field-values like get_options()
	|---------------------------------------------------------
	*/
	public function ce_get_option( $option, $default = '' ) {
		$section = $this->PREFIX . '_license_registration';
		$options = get_option( $section );

		if ( isset( $options[ $option ] ) ) {
			return $options[ $option ];
		}

		return $default;
	}
	/*
	|---------------------------------------------------------
	|   Display shortcode at the bottom of settings page.
	|---------------------------------------------------------
	|   An API token must be generated to use the shortcode
	|---------------------------------------------------------
	*/
	function display_support_form() {
		$html = "<div class='notice alightleft'>
                <h3>Submit a ticket</h3>
                <textarea id='ccpa_plugin_support' rows='8' cols='50'></textarea><br/>
                <a class='button-primary' id='support_btn'>Submit</a></div>";
		echo $html;
	}
	/*
	|---------------------------------------------------------
	|   Function accessed through AJAX
	|---------------------------------------------------------
	|   uninstall license
	|---------------------------------------------------------
	*/
	function uninstall_license() {
		$message = '';
		if ( wp_verify_nonce( $_REQUEST['_password'], 'purchase-verify' ) == true ) {
			$response = Ectbase::RemoveLicenseKey( $this->Base_File, $message );

			if ( $response == false ) {
				die(
					json_encode(
						array(
							'Response' => '403',
							'Message'  => 'Unable to contact to the server at the moment.',
						)
					)
				);
			}
		} else {
			die(
				json_encode(
					array(
						'Response' => '403',
						'Message'  => 'Access denied due to expired/unauthorized url access.',
					)
				)
			);
		}

		$this->flush_cache();
		die(
			json_encode(
				array(
					'Response' => '200',
					'Message'  => $message,
				)
			)
		);
	}
	/*
	|---------------------------------------------------------
	|   Submit ticket from WordPress back-end
	|---------------------------------------------------------
	*/
	function submit_ticket() {
		$email_from = get_option( 'admin_email' );
		$email_to   = 'contact@cooltimeline.com';

		$message  = '<html><body>';
		$message .= '<h3>A ticket is received from WordPress admin support form.</h3>';
		$message .= '<p><strong>URL</strong>:' . get_site_url() . '</p>';
		$message .= '<p><strong>Admin Email</strong>:' . $email_from . '</p>';
		$message .= '<p>Message: ' . $_POST['request'] . '<p>';
		$message .= '</body></html>';

		$subject = '[' . get_site_url() . ']Support ticket from WordPress back-end';

		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type:text/html;charset=UTF-8' . "\r\n";
		$headers .= 'From:<' . $email_from . "> \r\n";
		$headers .= 'Reply-To: ' . $email_to . "\r\n";

		$mail = wp_mail( $email_to, $subject, $message, $headers );
		echo 'Ticket submited successfully';
		exit();
	}
	/*
	|----------------------------------------------------------------
	|   Admin registration notice for un-registered admin users only
	|----------------------------------------------------------------
	*/
	function admin_registration_notice() {
		if ( ! current_user_can( 'delete_posts' ) || get_transient( $this->PREFIX . '_api_data_verification' ) == 'done' ) {
			return;
		}
		$current_user = wp_get_current_user();
		$user_name    = $current_user->display_name;
		$currnt_scren = get_current_screen();
		$parent_base  = isset( $currnt_scren->parent_base ) ? $currnt_scren->parent_base : '';
		if ( $parent_base == 'cool-plugins-events-addon' ) {
			?>
				<div class="license-warning notice notice-error is-dismissible">
					<p>Hi, <strong><?php echo ucwords( $user_name ); ?></strong>! Please <strong><a href="<?php echo esc_url( get_admin_url( null, 'admin.php?page=cool-events-registration' ) ); ?>">enter and activate</a></strong> your license key for <strong><?php echo $this->PLUGIN_NAME; ?></strong> plugin for unrestricted and full access of all premium features.</p>
				</div>
			<?php
		}
	}

	/*
	|------------------------------------------------------------
	|   Load css/js script(s) file(s) for settings admin page
	|------------------------------------------------------------
	*/
	function load_settings_scripts() {

		if ( isset( $_GET['page'] ) && $_GET['page'] == 'cool-events-registration' ) {
			wp_enqueue_style( $this->PREFIX . '-settings-style', $this->PLUGIN_URL . 'assets/css/api-auth-settings.css', null, $this->PLUGIN_VER );
			wp_enqueue_script( $this->PREFIX . '-settings-script', $this->PLUGIN_URL . 'assets/js/api-auth-settings.js', array( 'jquery' ), $this->PLUGIN_VER );

			wp_localize_script(
				$this->PREFIX . '-settings-script',
				'ajax_object',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'verify'   => wp_create_nonce( 'purchase-verify' ),
				)
			);
		}

	}

	/*
	|-----------------------------------------------------------|
	|   Flush cache: All Home sweeping code must be here        |
	|   Run after license uninstall or failed verification      |
	|-----------------------------------------------------------|
	*/
	function flush_cache() {
		$settings = get_option( $this->PREFIX . '_license_registration' );
		unset( $settings[ $this->PREFIX . '-purchase-code' ] );
		update_option( $this->PREFIX . '_license_registration', $settings );
		delete_transient( $this->PREFIX . '_api_data_verification' );
	}

}   // end of class
