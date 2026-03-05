<?php

namespace IGD;

defined( 'ABSPATH' ) || exit;

class Elementor {
	/**
	 * @var null
	 */
	protected static $instance = null;

	public function __construct() {

		add_action( 'elementor/editor/after_enqueue_scripts', [ $this, 'editor_scripts' ] );
		add_action( 'elementor/preview/enqueue_scripts', [ $this, 'preview_scripts' ] );
		add_action( 'elementor/frontend/before_enqueue_scripts', [ $this, 'frontend_scripts' ] );

		// Register default widgets
		add_action( 'elementor/elements/categories_registered', [ $this, 'add_categories' ] );

		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			if ( version_compare( ELEMENTOR_VERSION, '3.5.0', '>=' ) ) {
				add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
			} else {
				add_action( 'elementor/widgets/widgets_registered', array( $this, 'register_widgets' ) );
			}
		}

	}

	public function editor_scripts() {
		wp_enqueue_style( 'igd-elementor-editor', IGD_ASSETS . '/css/elementor-editor.css', [], IGD_VERSION );
		wp_style_add_data( 'igd-elementor-editor', 'rtl', 'replace' );
	}

	public function frontend_scripts() {
		Enqueue::instance()->frontend_scripts();

		wp_enqueue_style( 'igd-frontend' );
		wp_enqueue_script( 'igd-frontend' );
	}

	public function preview_scripts() {

		// Check if select2 is already registered
		wp_enqueue_style( 'igd-select2', IGD_ASSETS . '/vendor/select2/css/select2.min.css', [], '4.0.13' );
		wp_enqueue_script( 'igd-select2', IGD_ASSETS . '/vendor/select2/js/select2.full.min.js', [ 'jquery' ], '4.0.13', true );

		Enqueue::instance()->admin_scripts();

		wp_enqueue_script( 'igd-elementor', IGD_ASSETS . '/js/elementor.js', [
			'jquery',
			'react',
			'react-dom',
			'wp-components',
		], IGD_VERSION, true );
	}

	public function register_widgets( $widgets_manager ) {

		include_once IGD_INCLUDES . '/elementor/class-elementor-shortcodes-widget.php';
		if ( method_exists( $widgets_manager, 'register' ) ) {
			$widgets_manager->register( new Shortcodes_Widget() );
		} else {
			$widgets_manager->register_widget_type( new Shortcodes_Widget() );
		}

	}

	public function add_categories( $elements_manager ) {
		$elements_manager->add_category( 'integrate_google_drive', [
				'title' => __( 'Integrate Google Drive', 'integrate-google-drive' ),
				'icon'  => 'fa fa-plug',
			]
		);
	}

	public static function builder_empty_placeholder( $type, $module_id = null ) {

		$key = str_replace( 'igd_', '', $type );

		$titles = [
			'igd_shortcodes' => __( 'Google Drive Modules', 'integrate-google-drive' ),
		];

		$title = $titles[ $type ] ?? __( 'Google Drive Module', 'integrate-google-drive' );

		$shortcodes = Shortcode::get_shortcodes();

		$description  = 'shortcodes' == $key ? __( 'Choose a saved module to insert.', 'integrate-google-drive' ) : sprintf( __( 'Choose a saved %s module to insert.', 'integrate-google-drive' ), $title );
		$description2 = 'shortcodes' == $key ? __( 'Build a new Google Drive module from scratch.', 'integrate-google-drive' ) : sprintf( __( 'Build a new %s module from scratch.', 'integrate-google-drive' ), $title );

		?>

        <div class="igd-module-placeholder">

            <div class="module-icon icon-<?php echo esc_attr( $key ); ?>">
                <img src="<?php echo esc_url( IGD_ASSETS . '/images/shortcode-builder/types/' . $key . '.svg' ); ?>">
            </div>

            <h2 class="title"><?php echo esc_html( $title ); ?></h2>

            <h3 class="subtitle"><?php esc_html_e( 'Insert Existing Module', 'integrate-google-drive' ); ?></h3>

            <p class="description"><?php echo esc_html( $description ); ?></p>

            <select class="form-control module_id" name="module_id">

                <option><?php esc_html_e( 'Select Shortcode', 'integrate-google-drive' ) ?></option>

				<?php foreach ( $shortcodes as $shortcode ) : ?>
                    <option
                            value="<?php echo esc_attr( $shortcode['id'] ); ?>" <?php selected( $module_id, $shortcode['id'] ); ?>
                            data-image="<?php echo esc_url( IGD_ASSETS . '/images/shortcode-builder/types/' . $shortcode['type'] . '.svg' ); ?>"
                    >
						<?php echo esc_html( $shortcode['title'] ); ?>
                    </option>
				<?php endforeach; ?>
            </select>

			<?php if ( $module_id ) : ?>
                <button type="button" class="igd-btn btn-primary btn-configure"
                        onclick="setTimeout(() => { window.parent.jQuery(`[data-event='igd:editor:edit_module']`).trigger('click') }, 100)">
                    <i class="dashicons dashicons-admin-generic"></i>
                    <span><?php esc_html_e( 'Configure Module', 'integrate-google-drive' ); ?></span>
                </button>
			<?php else : ?>
                <div class="divider"><?php esc_html_e( 'OR', 'integrate-google-drive' ); ?></div>
                <h3 class="subtitle"><?php esc_html_e( 'Create New Module', 'integrate-google-drive' ); ?></h3>

                <p class="description"><?php echo esc_html( $description2 ); ?></p>

                <button type="button" class="igd-btn btn-primary"
                        onclick="setTimeout(() => { window.parent.elementor.channels.editor.trigger('igd:editor:add_module') }, 100)">
                    <i class="dashicons dashicons-plus"></i>
                    <span><?php esc_html_e( 'Add New Module', 'integrate-google-drive' ); ?></span>
                </button>
			<?php endif; ?>

        </div>
		<?php
	}

	/**
	 * @return Elementor|null
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

Elementor::instance();